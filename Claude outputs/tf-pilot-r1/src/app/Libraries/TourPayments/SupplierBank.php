<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r1 — a supplier's verified bank-payment information, kept beside the supplier master (tpd_supplier_bank).
 *
 * The supplier master keeps its own bank columns (sup_bank_acc, bank_name, branch_name, acc_holder); nothing there is
 * changed. This record holds what the master has no room for — beneficiary, currency, SWIFT, the invoice and
 * remittance e-mails, and the verification: who verified the details, when, and a fingerprint of what they verified.
 * If the account on the supplier master is changed afterwards, the fingerprint no longer matches and the record
 * shows CHANGED until someone with the verification privilege looks again.
 */
final class SupplierBank
{
    public static function masterRow($db, string $acc): ?array
    {
        try {
            return $db->query("SELECT Sup_Acc_No, Name, email, sup_bank_acc, bank_name, branch_name, acc_holder, bulk_payment, cr_dyz, flag, sup_cat, TP, Mobile FROM supplier WHERE Sup_Acc_No = ? LIMIT 1", [$acc])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function record($db, string $acc): ?array
    {
        try {
            return $db->query("SELECT * FROM tpd_supplier_bank WHERE sup_acc = ?", [$acc])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The fingerprint of the details as they stand on the master and on the record. */
    public static function fingerprint(?array $master, ?array $rec): string
    {
        $norm = static fn ($v) => strtolower(preg_replace('/\s+/', '', (string) $v));
        return sha1(implode('|', [
            $norm($master['sup_bank_acc'] ?? ''), $norm($master['bank_name'] ?? ''), $norm($master['branch_name'] ?? ''), $norm($master['acc_holder'] ?? ''),
            $norm($rec['account_no'] ?? ''), $norm($rec['bank_name'] ?? ''), $norm($rec['branch'] ?? ''), $norm($rec['beneficiary_name'] ?? ''), $norm($rec['swift'] ?? ''), $norm($rec['currency'] ?? ''),
        ]));
    }

    /**
     * The details to pay with: the record where it exists, the master's columns behind it, and the state —
     * verified / changed / unverified / inactive — with who verified and when.
     */
    public static function forPayment($db, string $acc): array
    {
        $m = self::masterRow($db, $acc);
        $r = self::record($db, $acc);
        $status = 'unverified';
        if ($r) {
            $status = (string) $r['status'];
            if ($status === 'verified' && $r['verified_hash'] && $r['verified_hash'] !== self::fingerprint($m, $r)) {
                $status = 'changed';
            }
        }
        // tf-pilot-r1: the full account number only for the roles that pay or verify; everyone else gets the last four digits
        $full = Perms::bankDetails();
        $show = static fn ($no) => $full ? (string) $no : BankAccounts::mask((string) $no, 4);
        return [
            'sup_acc' => $acc,
            'supplier_name' => $m['Name'] ?? ($r['beneficiary_name'] ?? $acc),
            'beneficiary_name' => $r['beneficiary_name'] ?? ($m['acc_holder'] ?? ''),
            'bank_name' => $r['bank_name'] ?? ($m['bank_name'] ?? ''),
            'branch' => $r['branch'] ?? ($m['branch_name'] ?? ''),
            'account_no' => $show($r['account_no'] ?? ($m['sup_bank_acc'] ?? '')),
            'account_no_masked' => !$full,
            'currency' => $r['currency'] ?? 'LKR',
            'swift' => $r['swift'] ?? '',
            'invoice_email' => $r['invoice_email'] ?? '',
            'remittance_email' => $r['remittance_email'] ?? ($m['email'] ?? ''),
            'master_email' => $m['email'] ?? '',
            'status' => $status,
            'verified_by' => $r['verified_by'] ?? null, 'verified_at' => $r['verified_at'] ?? null,
            'note' => $r['note'] ?? null, 'has_record' => (bool) $r,
            'master' => $m ? ['account_no' => $show($m['sup_bank_acc']), 'bank_name' => $m['bank_name'], 'branch' => $m['branch_name'], 'holder' => $m['acc_holder'], 'bulk' => (int) $m['bulk_payment'], 'credit_days' => $m['cr_dyz']] : null,
            'differs_from_master' => $r && $m && trim((string) $m['sup_bank_acc']) !== '' && (string) $r['account_no'] !== '' && preg_replace('/\s+/', '', (string) $r['account_no']) !== preg_replace('/\s+/', '', (string) $m['sup_bank_acc']),
        ];
    }

    public static function save($db, string $acc, array $in, string $by): array
    {
        $m = self::masterRow($db, $acc);
        if (!$m) {
            return ['ok' => false, 'message' => 'No supplier with account ' . $acc . '.'];
        }
        $prev = self::record($db, $acc);
        $row = [
            'sup_acc' => $acc,
            'beneficiary_name' => trim((string) ($in['beneficiary_name'] ?? '')) ?: null,
            'bank_name' => trim((string) ($in['bank_name'] ?? '')) ?: null,
            'branch' => trim((string) ($in['branch'] ?? '')) ?: null,
            'account_no' => preg_replace('/[^0-9A-Za-z\-]/', '', (string) ($in['account_no'] ?? '')) ?: null,
            'currency' => strtoupper(substr(trim((string) ($in['currency'] ?? 'LKR')), 0, 3)) ?: 'LKR',
            'swift' => strtoupper(trim((string) ($in['swift'] ?? ''))) ?: null,
            'invoice_email' => self::email($in['invoice_email'] ?? ''),
            'remittance_email' => self::email($in['remittance_email'] ?? ''),
            'note' => trim((string) ($in['note'] ?? '')) ?: null,
            'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($in['inactive'])) {
            $row['status'] = 'inactive';
        } elseif ($prev) {
            // a saved change to the account itself drops the verification; the rest keeps it
            $core = static fn ($a) => [$a['account_no'] ?? null, $a['bank_name'] ?? null, $a['branch'] ?? null, $a['beneficiary_name'] ?? null, $a['swift'] ?? null, $a['currency'] ?? null];
            $row['status'] = ($prev['status'] === 'verified' && $core($prev) === $core($row)) ? 'verified' : 'unverified';
            if ($row['status'] !== 'verified') {
                $row['verified_by'] = null;
                $row['verified_at'] = null;
                $row['verified_hash'] = null;
            }
        } else {
            $row['status'] = 'unverified';
        }
        if ($prev) {
            $db->table('tpd_supplier_bank')->where('sup_acc', $acc)->update($row);
        } else {
            $db->table('tpd_supplier_bank')->insert($row);
        }
        $after = self::record($db, $acc);
        Audit::log($db, ['action' => 'supplier_bank_saved', 'ref_type' => 'supplier', 'ref_id' => $acc, 'prev' => self::strip($prev), 'new' => self::strip($after)]);
        return ['ok' => true, 'message' => 'Supplier bank details saved' . ($row['status'] === 'unverified' ? ' — not yet verified.' : '.'), 'status' => $row['status']];
    }

    /** Verification by a holder of the privilege: the details as they stand now are fingerprinted. */
    public static function verify($db, string $acc, string $by, string $note = ''): array
    {
        $m = self::masterRow($db, $acc);
        $r = self::record($db, $acc);
        if (!$m) {
            return ['ok' => false, 'message' => 'No supplier with account ' . $acc . '.'];
        }
        if (!$r) {
            // verify what the master holds
            $db->table('tpd_supplier_bank')->insert(['sup_acc' => $acc, 'beneficiary_name' => $m['acc_holder'] ?: null, 'bank_name' => $m['bank_name'] ?: null, 'branch' => $m['branch_name'] ?: null, 'account_no' => $m['sup_bank_acc'] ?: null, 'currency' => 'LKR', 'remittance_email' => self::email($m['email'] ?? ''), 'status' => 'unverified', 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')]);
            $r = self::record($db, $acc);
        }
        if (trim((string) ($r['account_no'] ?? '')) === '' && trim((string) ($m['sup_bank_acc'] ?? '')) === '') {
            return ['ok' => false, 'message' => 'There is no account number to verify. Enter the supplier\'s bank details first.'];
        }
        $prev = self::strip($r);
        $db->table('tpd_supplier_bank')->where('sup_acc', $acc)->update(['status' => 'verified', 'verified_by' => $by, 'verified_at' => date('Y-m-d H:i:s'), 'verified_hash' => self::fingerprint($m, $r), 'note' => trim($note) !== '' ? trim($note) : $r['note'], 'updated_by' => $by, 'updated_at' => date('Y-m-d H:i:s')]);
        Audit::log($db, ['action' => 'supplier_bank_verified', 'ref_type' => 'supplier', 'ref_id' => $acc, 'prev' => $prev, 'new' => self::strip(self::record($db, $acc)), 'reason' => $note]);
        return ['ok' => true, 'message' => 'Verified by ' . $by . '.'];
    }

    private static function strip(?array $a): ?array
    {
        if (!$a) {
            return null;
        }
        unset($a['id'], $a['updated_by'], $a['updated_at'], $a['verified_hash']);
        if (!empty($a['account_no'])) {
            $a['account_no'] = BankAccounts::mask($a['account_no'], 4);
        }
        return $a;
    }

    public static function email($v): ?string
    {
        $v = trim((string) $v);
        return $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    /** All suppliers with their state, for the master page. */
    public static function listing($db, string $q = '', int $limit = 300): array
    {
        $where = '';
        $bind = [];
        if (trim($q) !== '') {
            $where = " WHERE (s.Name LIKE ? OR s.Sup_Acc_No LIKE ?)";
            $bind = ['%' . trim($q) . '%', '%' . trim($q) . '%'];
        }
        try {
            $rows = $db->query("SELECT s.Sup_Acc_No, s.Name, s.email, s.sup_bank_acc, s.bank_name, s.branch_name, s.acc_holder, s.bulk_payment, s.flag,
                                       b.beneficiary_name, b.bank_name AS b_bank, b.branch AS b_branch, b.account_no AS b_account, b.currency, b.swift, b.invoice_email, b.remittance_email, b.status, b.verified_by, b.verified_at, b.verified_hash, b.note
                                FROM supplier s LEFT JOIN tpd_supplier_bank b ON b.sup_acc = s.Sup_Acc_No {$where}
                                ORDER BY (b.status = 'verified') ASC, s.Name ASC LIMIT " . (int) $limit, $bind)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as &$r) {
            $status = $r['status'] ?: 'unverified';
            if ($status === 'verified' && $r['verified_hash']) {
                $m = ['sup_bank_acc' => $r['sup_bank_acc'], 'bank_name' => $r['bank_name'], 'branch_name' => $r['branch_name'], 'acc_holder' => $r['acc_holder']];
                $rec = ['account_no' => $r['b_account'], 'bank_name' => $r['b_bank'], 'branch' => $r['b_branch'], 'beneficiary_name' => $r['beneficiary_name'], 'swift' => $r['swift'], 'currency' => $r['currency']];
                if ($r['verified_hash'] !== self::fingerprint($m, $rec)) {
                    $status = 'changed';
                }
            }
            $r['state'] = $status;
            unset($r['verified_hash']);
        }
        unset($r);
        return $rows;
    }
}
