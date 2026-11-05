<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r7 (17 Oct 2026) — the unified Payment Desk: one read-only picture of the whole payment chain, tour and
 * non-tour. It only reads; every action on the page goes to the endpoint that already performs it (approval on the Tour
 * Payment Dashboard, batches on Supplier Payables, initiation and Paid through tour/tour_payment_dashboard/initiate and
 * /paid, bulk and batch payments on their own pages, adoption through tour/payment_desk/adopt). No supplier bank account
 * number is read here.
 */
final class Overview
{
    private const LIMIT = 300;

    public static function build($db, array $perms): array
    {
        $out = ['generated_at' => date('Y-m-d H:i:s'), 'today' => date('Y-m-d'), 'perms' => $perms, 'adoption_ready' => Adoption::ready($db)];
        $sections = ['requests', 'payables', 'batches', 'initiation', 'authorisation', 'paid', 'non_tour', 'not_on_desk', 'reconciliation', 'exceptions'];
        $out['timing_ms'] = [];
        foreach ($sections as $s) {
            $t0 = microtime(true);
            try {
                $out[$s] = self::$s($db);
            } catch (\Throwable $e) {
                $out[$s] = ['error' => 'This section could not be read: ' . $e->getMessage(), 'rows' => []];
            }
            $out['timing_ms'][$s] = (int) round((microtime(true) - $t0) * 1000);
        }
        $out['counts'] = [];
        foreach ($sections as $s) {
            $out['counts'][$s] = isset($out[$s]['rows']) ? count($out[$s]['rows']) : 0;
        }
        $out['counts']['exceptions'] = isset($out['exceptions']['items']) ? array_sum(array_map(static fn ($x) => $x['key'] === 'refusals' ? 0 : count($x['rows']), $out['exceptions']['items'])) : 0;
        $out['bank_accounts'] = BankAccounts::forSelection($db);
        return $out;
    }

    /** Requests waiting for management (requested, or waiting for a rate). */
    private static function requests($db): array
    {
        $rows = $db->query("SELECT pr.id, pr.tour_id, pr.manual_id, pr.hotel_name, pr.type, pr.requestAmount, pr.payment_request, pr.payment_request_user, pr.payment_request_date, c.tour_cancel
                            FROM payment_request pr LEFT JOIN consignee c ON c.tour_id = pr.tour_id
                            WHERE pr.payment_request IN (0, 1) ORDER BY pr.payment_request_date DESC, pr.id DESC LIMIT " . self::LIMIT)->getResultArray();
        return ['rows' => array_map(static fn ($r) => ['pr_id' => (int) $r['id'], 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'supplier' => $r['hotel_name'], 'type' => $r['type'], 'amount' => (float) $r['requestAmount'],
            'status' => (int) $r['payment_request'] === 0 ? 'waiting for the rate' : 'requested', 'by' => $r['payment_request_user'], 'at' => $r['payment_request_date'], 'tour_cancelled' => (int) ($r['tour_cancel'] ?? 0) === 1], $rows)];
    }

    /** Approved payments waiting under their supplier for a batch (Supplier Payables). */
    private static function payables($db): array
    {
        if (!Batches::accumulates($db)) {
            return ['rows' => [], 'note' => 'Supplier Payables is not set up on this install.'];
        }
        $rows = [];
        foreach (Batches::awaiting($db) as $s) {
            $items = $s['items'] ?? [];
            $rows[] = ['sup_acc' => $s['sup_acc'], 'supplier' => $s['supplier'], 'items' => count($items), 'disputed' => count(array_filter($items, static fn ($i) => !empty($i['disputed']))),
                'usd_total' => round((float) $s['usd_total'], 2), 'lkr_total' => round((float) $s['lkr_total'], 2), 'approval_lkr_total' => round((float) $s['approval_lkr_total'], 2),
                'tours' => array_values(array_unique(array_filter(array_map(static fn ($i) => $i['manual_id'] ?? null, $items)))), 'rate_missing' => !empty($s['rate_missing'])];
        }
        return ['rows' => $rows];
    }

    /** Supplier batches not yet complete (and the ones completed in the last 14 days). */
    private static function batches($db): array
    {
        $since = date('Y-m-d', strtotime('-14 days'));
        $rows = [];
        foreach (Batches::listing($db, '', '', self::LIMIT) as $b) {
            $stage = (string) ($b['stage'] ?? $b['status']);
            if (in_array($stage, ['paid', 'notified', 'cancelled'], true) && (string) ($b['paid_date'] ?? $b['updated_at'] ?? '') < $since) {
                continue;
            }
            $rows[] = ['id' => (int) $b['id'], 'batch_no' => $b['batch_no'], 'mode' => $b['mode'] ?? 'single', 'vnum' => $b['vnum'] !== null ? (int) $b['vnum'] : null, 'supplier' => $b['supplier_name'] ?? null,
                'currency' => $b['currency'], 'stage' => $stage, 'stage_label' => $b['stage_label'] ?? $stage, 'items' => (int) ($b['item_count'] ?? 0), 'usd_total' => $b['usd_total'] !== null ? (float) $b['usd_total'] : null,
                'approval_lkr_total' => $b['approval_lkr_total'] !== null ? (float) $b['approval_lkr_total'] : null, 'initiated_by' => $b['initiated_by'] ?? null, 'paid_by' => $b['paid_by'] ?? null, 'paid_date' => $b['paid_date'] ?? null,
                'tours' => $b['tours'] ?? [], 'created_at' => $b['created_at'] ?? null];
        }
        return ['rows' => $rows];
    }

    /** Vouchers the desk knows (request record, batch or adoption), not paid, not cancelled — with their tour and how the desk knows them. */
    private static function deskVouchers($db): array
    {
        $adopt = Adoption::ready($db);
        $sql = "SELECT v.ID, v.Vnum, v.S_name, v.Acc_No, v.Amount, v.doller_amt, v.advances_pay, v.Aflag, v.finalized, v.Bank, v.Bank_acc, v.ref_po, v.type, v.sup, v.C_Date,
                       (SELECT x.pr_id FROM tpd_request_ext x WHERE x.voucher_vnum = v.Vnum LIMIT 1) AS pr_id,
                       (SELECT b.id FROM tpd_batch b WHERE b.vnum = v.Vnum LIMIT 1) AS batch_id,
                       (SELECT b.batch_no FROM tpd_batch b WHERE b.vnum = v.Vnum LIMIT 1) AS batch_no,
                       (SELECT b.parent_id FROM tpd_batch b WHERE b.vnum = v.Vnum LIMIT 1) AS parent_id,
                       " . ($adopt ? "(SELECT a.kind FROM tpd_adoption a WHERE a.vnum = v.Vnum) AS adopted_kind, (SELECT a.bank_leg FROM tpd_adoption a WHERE a.vnum = v.Vnum) AS bank_leg," : "NULL AS adopted_kind, NULL AS bank_leg,") . "
                       i.id AS init_id, i.initiated_by, i.initiated_at, i.payment_date, i.amount AS init_amount, i.charges, i.total_debit, i.bank_ref, i.ledger_code AS init_ledger, i.usd_rate AS init_rate,
                       ap.id AS lock_id, ap.ledger_bank_credit, ap.reconciled
                FROM voucher v
                LEFT JOIN tpd_bank_initiation i ON i.vnum = v.Vnum
                LEFT JOIN tpd_bank_approval ap ON ap.vnum = v.Vnum
                WHERE v.finalized = 0 AND v.Aflag IN (0, 1)
                  AND (EXISTS (SELECT 1 FROM tpd_request_ext x2 WHERE x2.voucher_vnum = v.Vnum) OR EXISTS (SELECT 1 FROM tpd_batch b2 WHERE b2.vnum = v.Vnum)" . ($adopt ? " OR EXISTS (SELECT 1 FROM tpd_adoption a2 WHERE a2.vnum = v.Vnum)" : "") . ")
                ORDER BY v.Vnum DESC LIMIT " . self::LIMIT;
        $rows = $db->query($sql)->getResultArray();
        $kinds = Adoption::kinds($db, $rows);
        $out = [];
        foreach ($rows as $r) {
            // tf-paydesk-r7h build 5.2 (D-3): on the desk only through a desk record that agrees with the voucher (or a batch / adoption)
            if (!$r['batch_id'] && $r['pr_id'] && !($adopt && Adoption::get($db, (int) $r['Vnum'])) && Adoption::deskRecordState($db, (int) $r['Vnum']) !== 'agrees') {
                continue;
            }
            $k = $kinds[(int) $r['Vnum']];
            $cur = Currency::ofVoucher($db, $r);   // tf-paydesk-r7g: USD shown only when established from the source records
            $out[] = ['vnum' => (int) $r['Vnum'], 'payee' => $r['S_name'], 'amount' => (float) $r['Amount'], 'usd' => $cur['currency'] === 'USD' ? $cur['usd'] : null, 'currency' => $cur['established'] ? $cur['currency'] : null, 'aflag' => (int) $r['Aflag'],
                'bank' => $r['Bank'], 'bank_ledger' => $r['Bank_acc'], 'type' => $r['type'], 'kind' => $r['adopted_kind'] ?: $k['kind'], 'tour_id' => $k['tour_id'], 'manual_id' => $k['manual_id'], 'ref' => $r['ref_po'],
                'via' => $r['batch_id'] ? 'batch' : ($r['pr_id'] ? 'request' : 'adoption'), 'batch_id' => $r['batch_id'] ? (int) $r['batch_id'] : null, 'batch_no' => $r['batch_no'], 'bulk_parent' => $r['parent_id'] ? (int) $r['parent_id'] : null,
                'bank_leg' => (int) ($r['bank_leg'] ?? 0) === 1, 'initiated' => $r['init_id'] !== null, 'initiated_by' => $r['initiated_by'], 'initiated_at' => $r['initiated_at'], 'payment_date' => $r['payment_date'],
                'charges' => $r['charges'] !== null ? (float) $r['charges'] : null, 'total_debit' => $r['total_debit'] !== null ? (float) $r['total_debit'] : null, 'bank_ref' => $r['bank_ref'], 'init_ledger' => $r['init_ledger'],
                'locked' => $r['lock_id'] !== null, 'created' => $r['C_Date']];
        }
        return $out;
    }

    private static ?array $desk = null;

    private static function desk($db): array
    {
        return self::$desk ??= self::deskVouchers($db);
    }

    /** The accountant's queue: desk vouchers without a bank initiation. */
    private static function initiation($db): array
    {
        return ['rows' => array_values(array_filter(self::desk($db), static fn ($r) => !$r['initiated']))];
    }

    /** The bank approver's queue: initiated, not Paid (an interrupted Paid shows here too, with its lock). */
    private static function authorisation($db): array
    {
        return ['rows' => array_values(array_filter(self::desk($db), static fn ($r) => $r['initiated']))];
    }

    /** Paid on the desk in the last 30 days, with the ledger read-back and the remittance advice. */
    private static function paid($db): array
    {
        $rows = $db->query("SELECT a.vnum, a.paid_date, a.lkr_amount, a.usd_amount, a.usd_rate, a.fx_diff, a.charges, a.total_debit, a.initiated_by, a.approved_by, a.approved_at, a.ledger_bank_credit, a.reconciled, a.note,
                                   v.S_name, v.ref_po, r.status AS remit_status, r.sent_at
                            FROM tpd_bank_approval a LEFT JOIN voucher v ON v.Vnum = a.vnum LEFT JOIN tpd_remittance r ON r.vnum = a.vnum
                            WHERE a.paid_date >= ? ORDER BY a.approved_at DESC LIMIT " . self::LIMIT, [date('Y-m-d', strtotime('-30 days'))])->getResultArray();
        return ['rows' => array_map(static fn ($r) => ['vnum' => (int) $r['vnum'], 'payee' => $r['S_name'], 'ref' => $r['ref_po'], 'paid_date' => $r['paid_date'], 'lkr' => (float) $r['lkr_amount'], 'usd' => $r['usd_amount'] !== null ? (float) $r['usd_amount'] : null,
            'rate' => $r['usd_rate'] !== null ? (float) $r['usd_rate'] : null, 'fx_diff' => $r['fx_diff'] !== null ? (float) $r['fx_diff'] : null, 'charges' => $r['charges'] !== null ? (float) $r['charges'] : null, 'total_debit' => (float) $r['total_debit'],
            'initiated_by' => $r['initiated_by'], 'paid_by' => $r['approved_by'], 'paid_at' => $r['approved_at'], 'read_back' => $r['ledger_bank_credit'] !== null ? (float) $r['ledger_bank_credit'] : null, 'reconciled' => (int) $r['reconciled'] === 1,
            'remittance' => $r['remit_status'], 'remittance_sent' => $r['sent_at'], 'old_route' => stripos((string) $r['note'], 'Marked finalized on Bank Payment Check') === 0], $rows)];
    }

    /** Non-tour payments: on the desk (adopted), or waiting for adoption. */
    private static function non_tour($db): array
    {
        $on = array_values(array_filter(self::desk($db), static fn ($r) => $r['kind'] === 'non_tour'));
        $off = array_values(array_filter(self::notOnDesk($db), static fn ($r) => $r['kind'] === 'non_tour'));
        return ['rows' => array_merge($on, $off)];
    }

    private static ?array $off = null;

    /** Unpaid bank-transfer vouchers the desk has no record of (no request record, batch or adoption): they are adopted first. */
    private static function notOnDesk($db): array
    {
        if (self::$off !== null) {
            return self::$off;
        }
        $adopt = Adoption::ready($db);
        $rows = $db->query("SELECT v.ID, v.Vnum, v.S_name, v.Acc_No, v.Amount, v.Aflag, v.Bank, v.Bank_acc, v.ref_po, v.type, v.sup, v.C_Date, v.login, v.des
                            FROM voucher v
                            WHERE v.finalized = 0 AND v.Aflag IN (0, 1) AND v.v_typ != 'Petty Cash' AND v.type IN ('Bank Transfer', 'BT')
                              AND NOT EXISTS (SELECT 1 FROM tpd_request_ext x WHERE x.voucher_vnum = v.Vnum)
                              AND NOT EXISTS (SELECT 1 FROM tpd_batch b WHERE b.vnum = v.Vnum)" . ($adopt ? " AND NOT EXISTS (SELECT 1 FROM tpd_adoption a WHERE a.vnum = v.Vnum)" : "") . "
                            ORDER BY v.Vnum DESC LIMIT " . self::LIMIT)->getResultArray();
        // tf-paydesk-r7h build 5.2 (D-3): a voucher named only by a desk record that does not agree with it is not on the desk either
        $named = $db->query("SELECT v.ID, v.Vnum, v.S_name, v.Acc_No, v.Amount, v.Aflag, v.Bank, v.Bank_acc, v.ref_po, v.type, v.sup, v.C_Date, v.login, v.des
                            FROM voucher v
                            WHERE v.finalized = 0 AND v.Aflag IN (0, 1) AND v.v_typ != 'Petty Cash' AND v.type IN ('Bank Transfer', 'BT')
                              AND EXISTS (SELECT 1 FROM tpd_request_ext x WHERE x.voucher_vnum = v.Vnum)
                              AND NOT EXISTS (SELECT 1 FROM tpd_batch b WHERE b.vnum = v.Vnum)" . ($adopt ? " AND NOT EXISTS (SELECT 1 FROM tpd_adoption a WHERE a.vnum = v.Vnum)" : "") . "
                            ORDER BY v.Vnum DESC LIMIT " . self::LIMIT)->getResultArray();
        $held = [];
        foreach ($named as $r) {
            $st = Adoption::deskRecordState($db, (int) $r['Vnum']);
            if ($st !== 'agrees') {
                $held[(int) $r['Vnum']] = $st;
                $rows[] = $r;
            }
        }
        usort($rows, static fn ($a, $b) => (int) $b['Vnum'] <=> (int) $a['Vnum']);
        $rows = array_slice($rows, 0, self::LIMIT);
        $kinds = Adoption::kinds($db, $rows);
        $out = [];
        foreach ($rows as $r) {
            $k = $kinds[(int) $r['Vnum']];
            $out[] = ['desk_record' => $held[(int) $r['Vnum']] ?? 'none', 'vnum' => (int) $r['Vnum'], 'payee' => $r['S_name'], 'amount' => (float) $r['Amount'], 'aflag' => (int) $r['Aflag'], 'bank' => $r['Bank'], 'bank_ledger' => $r['Bank_acc'], 'type' => $r['type'],
                'kind' => $k['kind'], 'tour_id' => $k['tour_id'], 'manual_id' => $k['manual_id'], 'ref' => $r['ref_po'], 'mode' => Adoption::mode($r), 'via' => null, 'created' => $r['C_Date'], 'created_by' => $r['login'], 'description' => $r['des'],
                'initiated' => false, 'adopted' => false];
        }
        return self::$off = $out;
    }

    private static function not_on_desk($db): array
    {
        return ['rows' => self::notOnDesk($db)];
    }

    /** The Daily Bank Reconciliation's accounts and their latest day (read from its own tables, never posted to). */
    private static function reconciliation($db): array
    {
        if ((int) ($db->query("SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('brc_account_map', 'brc_day')")->getRowArray()['n'] ?? 0) < 2) {
            return ['rows' => [], 'note' => 'The Daily Bank Reconciliation is not installed here.'];
        }
        $rows = $db->query("SELECT m.id, m.bank_account_id, m.ledger_code, m.currency, m.rec_start, m.status, a.label,
                                   (SELECT MAX(d.rec_date) FROM brc_day d WHERE d.bank_account_id = m.bank_account_id) AS last_day,
                                   (SELECT d.status_label FROM brc_day d WHERE d.bank_account_id = m.bank_account_id ORDER BY d.rec_date DESC LIMIT 1) AS last_status
                            FROM brc_account_map m LEFT JOIN tpd_bank_account a ON a.id = m.bank_account_id ORDER BY m.id")->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $paid = $db->query("SELECT COUNT(*) AS n, COALESCE(SUM(total_debit), 0) AS s FROM tpd_bank_approval WHERE ledger_code = ? AND paid_date >= ?", [$r['ledger_code'], date('Y-m-d', strtotime('-30 days'))])->getRowArray();
            $out[] = ['map_id' => (int) $r['id'], 'bank_account_id' => (int) $r['bank_account_id'], 'label' => $r['label'], 'ledger_code' => $r['ledger_code'], 'currency' => $r['currency'], 'status' => $r['status'], 'rec_start' => $r['rec_start'],
                'last_day' => $r['last_day'], 'last_status' => $r['last_status'], 'desk_paid_30d' => (int) $paid['n'], 'desk_paid_30d_total' => (float) $paid['s']];
        }
        return ['rows' => $out];
    }

    /** What needs a person's attention: Paid without read-back, unreconciled ledger, paid-without-posting, posting-without-flag, interrupted claims, refusals. */
    private static function exceptions($db): array
    {
        $items = [];
        $q = static fn ($sql, $p = []) => $db->query($sql, $p)->getResultArray();
        $items[] = ['key' => 'no_read_back', 'title' => 'Paid lock without a ledger read-back', 'help' => 'Marked Paid outside the desk\'s full Paid (for example through the older Mark Finalized) — the ledger was never read back against the bank debit.',
            'rows' => $q("SELECT a.vnum, a.paid_date, a.lkr_amount, a.approved_by, LEFT(a.note, 80) AS note FROM tpd_bank_approval a WHERE a.ledger_bank_credit IS NULL ORDER BY a.approved_at DESC LIMIT 100")];
        $items[] = ['key' => 'not_reconciled', 'title' => 'Paid, but the ledger does not reconcile with the bank debit', 'help' => 'The credits to the paying account differ from the total the bank debited.',
            'rows' => $q("SELECT a.vnum, a.paid_date, a.total_debit, a.ledger_bank_credit, a.approved_by FROM tpd_bank_approval a WHERE a.ledger_bank_credit IS NOT NULL AND a.reconciled = 0 ORDER BY a.approved_at DESC LIMIT 100")];
        $items[] = ['key' => 'paid_unposted', 'title' => 'Marked Paid but never authorised (no posting)', 'help' => 'finalized = 1 while the voucher is still unauthorised.',
            'rows' => $q("SELECT Vnum AS vnum, S_name AS payee, Amount AS amount, finalized_user, bank_date FROM voucher WHERE finalized = 1 AND Aflag = 0 ORDER BY Vnum DESC LIMIT 100")];
        $items[] = ['key' => 'posted_unflagged', 'title' => 'Authorisation rows on an unauthorised voucher', 'help' => 'Ledger rows of the chain\'s authorisation exist although the voucher is still status 0 — refer to Accounts before any payment.',
            'rows' => $q("SELECT v.Vnum AS vnum, v.S_name AS payee, v.Amount AS amount, COUNT(*) AS ledger_rows FROM voucher v JOIN account_runing r ON r.Recpt_No = v.Vnum AND UPPER(r.Discription) LIKE 'VOUCHER PAYMENT%' WHERE v.Aflag = 0 GROUP BY v.Vnum, v.S_name, v.Amount ORDER BY v.Vnum DESC LIMIT 100")];
        $claims = [];
        try {
            $claims = $q("SELECT vnum, route, user, claimed_at, state FROM pg_auth_claim WHERE state = 'posting' AND claimed_at < (NOW() - INTERVAL 10 MINUTE) ORDER BY claimed_at LIMIT 100");
        } catch (\Throwable $e) {
        }
        $items[] = ['key' => 'stuck_claims', 'title' => 'Authorisation interrupted (claim older than 10 minutes)', 'help' => 'The guard recovers these on the next attempt; listed so that nobody is surprised.', 'rows' => $claims];
        $items[] = ['key' => 'initiated_cancelled', 'title' => 'Bank initiation on a cancelled voucher', 'help' => 'The bank may have been instructed for a voucher that was later cancelled.',
            'rows' => $q("SELECT i.vnum, i.initiated_by, i.initiated_at, i.total_debit, v.Aflag AS aflag FROM tpd_bank_initiation i JOIN voucher v ON v.Vnum = i.vnum WHERE v.Aflag IN (2, 3) ORDER BY i.id DESC LIMIT 100")];
        $items[] = self::duplicates($db);   // tf-paydesk-r7d
        if (Adoption::ready($db)) {
            $items[] = ['key' => 'bank_leg_missing', 'title' => 'Paid Bank-Control voucher without its bank leg', 'help' => 'Adopted with the bank-leg confirmation, Paid, but the bank leg is not recorded.',
                'rows' => $q("SELECT a.vnum, a.payee, a.amount, a.adopted_by FROM tpd_adoption a JOIN voucher v ON v.Vnum = a.vnum WHERE a.bank_leg = 1 AND a.bank_leg_entry IS NULL AND v.finalized = 1 LIMIT 100")];
        }
        $items[] = ['key' => 'refusals', 'title' => 'Refused attempts in the last 7 days', 'help' => 'Kept in the audit trail; a number that grows may mean a page left open or a training need.',
            'rows' => $q("SELECT action, COUNT(*) AS n, MAX(at) AS last_at FROM tpd_audit WHERE action IN ('finalize_refused', 'paid_refused', 'initiation_refused', 'separation_blocked', 'access_denied', 'adoption_refused', 'auth_refused', 'csrf_refused') AND at >= ? GROUP BY action ORDER BY n DESC", [date('Y-m-d', strtotime('-7 days'))])];
        return ['items' => $items, 'rows' => []];
    }

    /**
     * tf-paydesk-r7d: duplicate ledger postings on vouchers Paid on the desk in the last 90 days — more than one authorisation
     * journal, more than one bank-leg journal, or a bank-charge / exchange journal that the voucher's Paid did not record.
     * Refused attempts are audit lines only (see "Refused attempts"); they post nothing and never appear here. Read-only.
     */
    private static function duplicates($db): array
    {
        $item = ['key' => 'duplicate_postings', 'title' => 'Duplicate ledger postings (vouchers Paid on the desk, last 90 days)',
            'help' => 'A second authorisation or bank-leg journal for one voucher, or a charge / exchange journal its Paid did not record. Refused attempts are not postings and are listed separately.', 'rows' => []];
        try {
            $since = date('Y-m-d', strtotime('-90 days'));
            $paid = $db->query("SELECT vnum, charge_entries, fx_entries FROM tpd_bank_approval WHERE ledger_bank_credit IS NOT NULL AND paid_date >= ?", [$since])->getResultArray();
            if (!$paid) {
                return $item;
            }
            $byV = [];
            foreach ($paid as $p) {
                $byV[(int) $p['vnum']] = ['charges' => array_filter(array_map('intval', explode(',', (string) $p['charge_entries']))), 'fx' => array_filter(array_map('intval', explode(',', (string) $p['fx_entries'])))];
            }
            $list = implode(',', array_keys($byV));
            foreach ($db->query("SELECT Recpt_No AS vnum, COUNT(DISTINCT CASE WHEN Discription LIKE 'VOUCHER PAYMENT - Voucher Payment%' THEN ID END) AS auth,
                                        COUNT(DISTINCT CASE WHEN Discription LIKE 'VOUCHER PAYMENT - Bank leg%' THEN ID END) AS leg
                                 FROM account_runing WHERE Recpt_No IN ($list) AND Discription LIKE 'VOUCHER PAYMENT - %' GROUP BY Recpt_No HAVING auth > 1 OR leg > 1")->getResultArray() as $r) {
                $item['rows'][] = ['vnum' => (int) $r['vnum'], 'kind' => (int) $r['auth'] > 1 ? 'authorisation' : 'bank leg', 'journals' => (int) $r['auth'] > 1 ? (int) $r['auth'] : (int) $r['leg'], 'not_recorded' => ''];
            }
            $found = [];
            foreach ($db->query("SELECT DISTINCT ID, Discription FROM account_runing WHERE Date >= ? AND (Discription LIKE 'Bank charges Payment Voucher No. %' OR Discription LIKE 'Exchange gain Payment Voucher No. %' OR Discription LIKE 'Exchange loss Payment Voucher No. %')", [$since])->getResultArray() as $r) {
                if (preg_match('/^(Bank charges|Exchange gain|Exchange loss) Payment Voucher No\. (\d+) \//', (string) $r['Discription'], $m) && isset($byV[(int) $m[2]])) {
                    $found[(int) $m[2]][$m[1] === 'Bank charges' ? 'charges' : 'fx'][(int) $r['ID']] = true;
                }
            }
            foreach ($found as $vn => $kinds) {
                foreach ($kinds as $k => $ids) {
                    $extra = array_diff(array_keys($ids), $byV[$vn][$k]);
                    if ($extra) {
                        $item['rows'][] = ['vnum' => $vn, 'kind' => $k === 'charges' ? 'bank charges' : 'exchange difference', 'journals' => count($ids), 'not_recorded' => implode(',', $extra)];
                    }
                }
            }
        } catch (\Throwable $e) {
            $item['error'] = 'Could not check: ' . $e->getMessage();
        }
        return $item;
    }
}
