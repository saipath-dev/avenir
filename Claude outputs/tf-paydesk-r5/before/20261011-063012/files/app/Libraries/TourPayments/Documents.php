<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r1 — the documents of a payment: the internal payment voucher / instruction, and the supplier's
 * remittance advice (one per voucher, per supplier, never combined). Both are built from the voucher as it stands
 * in the chain plus what the desk recorded about it; both render as HTML, and as PDF with TCPDF when it is there.
 */
final class Documents
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function company(): array
    {
        $meta = static function ($db, $k, $d = null) {
            try {
                $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
                return $r['v'] ?? $d;
            } catch (\Throwable $e) {
                return $d;
            }
        };
        return [
            'name' => $meta($this->db, 'company_name', 'Avenir Holidays (Pvt) Ltd'),
            'address' => $meta($this->db, 'company_address', ''),
            'phone' => $meta($this->db, 'company_phone', ''),
            'email' => $meta($this->db, 'company_email', ''),
            'logo' => is_file(FCPATH . 'assets/images/Logo-new.png') ? FCPATH . 'assets/images/Logo-new.png' : null,
        ];
    }

    /** Everything about one voucher: the chain's record and the desk's. */
    public function voucher(int $vnum): ?array
    {
        $v = $this->db->query("SELECT * FROM voucher WHERE Vnum = ? LIMIT 1", [$vnum])->getRowArray();
        if (!$v) {
            return null;
        }
        $lines = $this->db->query("SELECT vi.*, c.manual_id, c.consignee, c.start_date, c.end_date, c.passenger_count, c.agent_name FROM v_inv vi LEFT JOIN consignee c ON c.tour_id = vi.Invoice WHERE vi.V_tkn = ? ORDER BY vi.ID", [$vnum])->getResultArray();
        $tourId = $lines[0]['Invoice'] ?? $v['ref_po'];
        $tour = $tourId ? $this->db->query("SELECT tour_id, manual_id, consignee, start_date, end_date, passenger_count, agent_name, agent FROM consignee WHERE tour_id = ? LIMIT 1", [(string) $tourId])->getRowArray() : null;
        $ext = null;
        $req = null;
        try {
            $ext = $this->db->query("SELECT * FROM tpd_request_ext WHERE voucher_vnum = ? ORDER BY id DESC LIMIT 1", [$vnum])->getRowArray() ?: null;
            if ($ext) {
                $req = $this->db->query("SELECT * FROM payment_request WHERE id = ?", [(int) $ext['pr_id']])->getRowArray() ?: null;
            }
        } catch (\Throwable $e) {
        }
        if (!$req && $tourId) {
            // the request this voucher answered: same tour, same supplier, accepted at or before it was raised
            $adv = (int) $v['advances_pay'] === 1 ? 'Transport Advance' : null;
            $req = $this->db->query("SELECT * FROM payment_request WHERE tour_id = ? AND hotel_acc = ? AND payment_request IN (2,4,6)" . ($adv ? " AND type = 'Transport Advance'" : " AND type <> 'Transport Advance'") . " AND (accept_date IS NULL OR DATE(accept_date) <= DATE(?)) ORDER BY id DESC LIMIT 1", [(string) $tourId, (string) $v['Acc_No'], $v['C_Date']])->getRowArray() ?: null;
            if ($req) {
                try {
                    $ext = $this->db->query("SELECT * FROM tpd_request_ext WHERE pr_id = ?", [(int) $req['id']])->getRowArray() ?: null;
                } catch (\Throwable $e) {
                }
            }
        }
        $sup = SupplierBank::forPayment($this->db, (string) $v['Acc_No']);
        $init = $this->row('tpd_bank_initiation', $vnum);
        $app = $this->row('tpd_bank_approval', $vnum);
        $rem = $this->row('tpd_remittance', $vnum);
        $docs = [];
        try {
            $docs = $this->db->query("SELECT * FROM tpd_document WHERE vnum = ? OR (ref_type = 'request' AND ref_id = ?)
                                      OR (ref_type = 'line' AND tour_id = ? AND (ref_id = ? OR ref_id LIKE ? OR ref_id = ?)) ORDER BY id",
                [$vnum, (string) ($req['id'] ?? ''), (string) $tourId, 'hotel:' . $v['Acc_No'], 'activity:' . $v['Acc_No'] . ':%', 'driver_advance:' . $v['Acc_No']])->getResultArray();
        } catch (\Throwable $e) {
        }
        $acctId = $init['bank_account_id'] ?? $app['bank_account_id'] ?? null;
        $acct = $acctId ? BankAccounts::get($this->db, (int) $acctId) : BankAccounts::byLedger($this->db, $v['Bank_acc'] ?? null);
        $usd = $v['doller_amt'] !== null && (float) $v['doller_amt'] > 0;
        $rate = null;
        if ($usd) {
            $rate = $ext && $ext['usd_rate'] ? (float) $ext['usd_rate'] : ((float) ($lines[0]['Rate'] ?? 0) > 0 ? (float) $lines[0]['Rate'] : round((float) $v['Amount'] / (float) $v['doller_amt'], 4));
        }
        $dates = array_values(array_filter(array_map('trim', explode(',', (string) ($v['V_num'] ?? '')))));
        $hotelDates = [];
        if ($tourId && $req && (string) $req['type'] === 'Hotel') {
            $hotelDates = array_column($this->db->query("SELECT tour_date FROM tour_quotations WHERE quotation_id = ? AND hotel_acc = ? AND confirm <> 3 ORDER BY tour_date", [(string) $tourId, (string) $v['Acc_No']])->getResultArray(), 'tour_date');
        }
        $stage = (int) $v['Aflag'] === 2 ? 'reversed' : ((int) $v['finalized'] === 1 ? ($rem && $rem['status'] === 'sent' ? 'notified' : ($rem ? 'notify_pending' : 'paid')) : ($init ? 'bank_initiated' : 'voucher_issued'));
        // tf-paydesk-r2: a supplier batch voucher — its items (tours, invoices) with the approval figures and, once Paid, the final ones
        $batch = Batches::byVnum($this->db, $vnum);
        $batchItems = [];
        $figures = null;
        if ($batch) {
            $batchItems = Batches::items($this->db, (int) $batch['id']);
            $finalRate = $app && $app['usd_rate'] !== null ? (float) $app['usd_rate'] : ($init && $init['usd_rate'] !== null ? (float) $init['usd_rate'] : null);
            $figures = Batches::figures($batchItems, $finalRate);
            $figures['final'] = (bool) $app;
            $figures['stage'] = $app ? 'final' : ($init ? 'initiation' : 'provisional');
            if ($usd && $finalRate) {
                $rate = $finalRate;
            }
            // several tours on one voucher: the document names them all
            if ($batchItems && count(array_unique(array_column($batchItems, 'tour_id'))) > 1) {
                $tour = $tour ?: ['tour_id' => $tourId, 'manual_id' => implode(', ', array_unique(array_column($batchItems, 'manual_id'))), 'consignee' => '', 'start_date' => null, 'end_date' => null, 'passenger_count' => 0, 'agent_name' => '', 'agent' => ''];
            }
        }
        return [
            'voucher' => $v, 'lines' => $lines, 'tour' => $tour, 'request' => $req, 'ext' => $ext,
            'supplier' => $sup, 'initiation' => $init, 'approval' => $app, 'remittance' => $rem, 'documents' => $docs,
            'batch' => $batch, 'batch_items' => $batchItems, 'figures' => $figures,
            'bank_account' => $acct, 'usd' => $usd, 'usd_rate' => $rate, 'service_dates' => $hotelDates ?: $dates,
            'stage' => $stage, 'stage_label' => PayDesk::STATUS[$stage][1] ?? $stage,
            'audit' => Audit::forVoucher($this->db, $vnum), 'company' => $this->company(),
            'description' => $lines[0]['Description'] ?? ($v['des'] !== '0' ? $v['des'] : ''),
            'kind' => (int) $v['advances_pay'] === 1 ? 'Driver advance' : (($req['type'] ?? '') === 'Hotel' ? 'Hotel' : (($req['type'] ?? '') === 'lunch' ? 'Service' : 'Payment')),
        ];
    }

    private function row(string $table, int $vnum): ?array
    {
        try {
            return $this->db->query("SELECT * FROM `{$table}` WHERE vnum = ? LIMIT 1", [$vnum])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The remittance advice's words and e-mail draft for a paid voucher. */
    public function remittanceDraft(array $d): array
    {
        $v = $d['voucher'];
        $sup = $d['supplier'];
        $tour = $d['tour'];
        $name = trim((string) ($sup['supplier_name'] ?: $v['S_name']));
        $subject = 'Payment completed — ' . ($tour['manual_id'] ?? 'Tour') . ' · ' . ($tour['consignee'] ?? '') . ' · Payment Voucher No. ' . $v['Vnum'];
        $items = $d['batch_items'] ?? [];
        $multi = count($items) > 1;
        $body = '<p>Dear ' . esc($name) . ',</p>'
            . '<p>We confirm that the following payment has been completed. Please check your bank account.</p>'
            . '<p>The payment details and remittance advice are attached.</p>'
            . '<table cellpadding="4" style="border-collapse:collapse;font-size:13px;">'
            . ($multi ? '<tr><td style="color:#555;">Tours</td><td><b>' . esc(implode(', ', array_unique(array_column($items, 'manual_id')))) . '</b> (' . count($items) . ' invoices — see the list below)</td></tr>'
                      : '<tr><td style="color:#555;">Tour</td><td><b>' . esc($tour['manual_id'] ?? '') . '</b> · ' . esc($tour['consignee'] ?? '') . '</td></tr>'
                      . '<tr><td style="color:#555;">Service dates</td><td>' . esc($this->dateSpan($d['service_dates'])) . '</td></tr>')
            . '<tr><td style="color:#555;">Amount paid</td><td><b>LKR ' . number_format((float) ($d['approval']['lkr_amount'] ?? $v['Amount']), 2) . '</b>' . ($d['usd'] ? ' (USD ' . number_format((float) $v['doller_amt'], 2) . ' at ' . number_format((float) $d['usd_rate'], 2) . ')' : '') . '</td></tr>'
            . '<tr><td style="color:#555;">Payment date</td><td>' . esc((string) ($d['approval']['paid_date'] ?? $v['bank_date'] ?? '')) . '</td></tr>'
            . '<tr><td style="color:#555;">Our reference</td><td><b>Payment Voucher No. ' . (int) $v['Vnum'] . '</b>' . ($d['batch'] ? ' · batch ' . esc($d['batch']['batch_no']) : '') . ($d['initiation']['bank_ref'] ?? $d['approval']['bank_ref'] ?? null ? ' · bank ref ' . esc((string) ($d['approval']['bank_ref'] ?? $d['initiation']['bank_ref'])) : '') . '</td></tr>'
            . '</table>';
        if ($multi) {
            $fig = $d['figures'] ?? null;
            $body .= '<table cellpadding="4" style="border-collapse:collapse;font-size:12.5px;margin-top:8px;border:1px solid #ddd;"><tr style="background:#f3e7f3;"><th align="left">Tour</th><th align="left">Guest</th><th align="left">Service</th><th align="left">Invoice</th><th align="right">Amount</th><th align="right">LKR paid</th></tr>';
            foreach ($items as $k => $it) {
                $lkr = $fig['lines'][$k]['actual_lkr'] ?? $it['approval_lkr'];
                $body .= '<tr><td>' . esc($it['manual_id']) . '</td><td>' . esc($it['guest']) . '</td><td>' . esc($it['kind']) . ($it['service_dates'] ? ' · ' . esc($this->dateSpan($it['service_dates'])) : '') . '</td><td>' . esc($it['invoice_no'] ?: '—') . '</td><td align="right">' . ($it['currency'] === 'USD' ? 'USD ' . number_format((float) $it['usd_amount'], 2) : 'LKR ' . number_format((float) $it['lkr_amount'], 2)) . '</td><td align="right">' . number_format((float) $lkr, 2) . '</td></tr>';
            }
            $body .= '</table>';
        }
        $body .= '<p>Kind regards,<br>Avenir Holidays</p>';
        return ['to' => $sup['remittance_email'] ?: ($sup['master_email'] ?: ''), 'subject' => $subject, 'body' => $body, 'supplier_name' => $name];
    }

    public function dateSpan(array $dates): string
    {
        $dates = array_values(array_filter(array_map(static fn ($x) => substr(trim((string) $x), 0, 10), $dates)));
        if (!$dates) {
            return '';
        }
        sort($dates);
        $a = date('j M Y', strtotime($dates[0]));
        $b = date('j M Y', strtotime(end($dates)));
        return $a === $b ? $a : $a . ' – ' . $b;
    }

    /** HTML → PDF bytes with TCPDF, or null when the library is not on the server. */
    public function pdf(string $html, string $title): ?string
    {
        if (!class_exists('\TCPDF') && is_file(ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php')) {
            require_once ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        if (!class_exists('\TCPDF')) {
            return null;
        }
        $doc = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $doc->SetCreator('Avenir Holidays');
        $doc->SetAuthor('Avenir Holidays');
        $doc->SetTitle($title);
        $doc->setPrintHeader(false);
        $doc->setPrintFooter(false);
        $doc->SetMargins(12, 12, 12);
        $doc->SetAutoPageBreak(true, 14);
        $doc->SetFont('dejavusans', '', 9.5);
        $doc->AddPage();
        $doc->writeHTML($html, true, false, true, false, '');
        return $doc->Output($title . '.pdf', 'S');
    }

    public static function dir(string $sub): string
    {
        $d = WRITEPATH . 'tpd/' . trim($sub, '/') . '/';
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
            @file_put_contents(WRITEPATH . 'tpd/.htaccess', "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
            @file_put_contents(WRITEPATH . 'tpd/index.html', '');
        }
        return $d;
    }
}
