<?php

namespace App\Libraries\TourFlow;

/**
 * tf-stage6 (29 Oct 2026) — Customers & Agents: the customer ledger (table customers, the existing records and codes) read
 * with what the tours and inquiries say about each one, the agent flag (the existing `agent` column), and two settings an
 * agent carries for quotations: the quotation currency and a default markup percentage (schema 55). The accounts-side
 * `margin` column (Debtor Invoice) is not touched. Every change of the flag or the settings is written to tf_activity.
 */
final class CustomersAgents
{
    public const URL = 'master_files/customer/agents';

    /** The agent list Shantha reviewed on 29 Oct 2026 (rows 1–8); flagged once at install if still unflagged. */
    public const INITIAL_AGENTS = ['1-2-5-10634', '1-2-5-10635', '1-2-5-10833', '1-2-5-10664', '1-2-5-11054', '1-2-5-11408', '1-2-5-11586', '1-2-5-11584'];

    private static ?bool $installed = null;

    public static function installed($db): bool
    {
        if (self::$installed === null) {
            try {
                self::$installed = (bool) $db->query("SHOW COLUMNS FROM `customers` LIKE 'quote_currency'")->getResultArray();
            } catch (\Throwable $e) {
                self::$installed = false;
            }
        }
        return self::$installed;
    }

    private static function base(): string
    {
        return "SELECT c.ID AS id, c.Sup_Acc_No AS acc, c.Name AS name, c.long_name, c.prefix, c.country, c.email, c.TP AS phone, c.Mobile AS mobile, c.Address AS address,
                       c.contact_person, c.agent, c.quote_currency, c.quote_markup_pct, c.agent_set_by, c.agent_set_at, c.agent_note,
                       (SELECT COUNT(*) FROM consignee t WHERE t.agent = c.Sup_Acc_No) AS tours,
                       (SELECT MAX(t.start_date) FROM consignee t WHERE t.agent = c.Sup_Acc_No) AS last_tour,
                       (SELECT COUNT(*) FROM tf_intake i WHERE i.agent_acc = c.Sup_Acc_No) AS inquiries
                FROM customers c";
    }

    /** The list: agents first, then by tours, then by name. $filter: '' | agents | direct. */
    public static function list($db, string $q = '', string $filter = '', int $limit = 600): array
    {
        $where = ["c.Name IS NOT NULL AND c.Name <> ''"];
        $bind  = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(c.Name LIKE ? OR c.long_name LIKE ? OR c.Sup_Acc_No LIKE ? OR c.country LIKE ? OR c.email LIKE ? OR c.prefix LIKE ?)';
            array_push($bind, $like, $like, $like, $like, $like, $like);
        }
        if ($filter === 'agents') {
            $where[] = 'c.agent = 1';
        } elseif ($filter === 'direct') {
            $where[] = '(c.agent IS NULL OR c.agent <> 1)';
        }
        $rows = $db->query(self::base() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY c.agent DESC, tours DESC, c.Name LIMIT ' . (int) $limit, $bind)->getResultArray();
        foreach ($rows as &$r) {
            $r = self::shape($r);
        }
        unset($r);
        return $rows;
    }

    public static function counts($db): array
    {
        $r = $db->query("SELECT COUNT(*) n, SUM(agent = 1) agents FROM customers WHERE Name IS NOT NULL AND Name <> ''")->getRowArray() ?: [];
        return ['all' => (int) ($r['n'] ?? 0), 'agents' => (int) ($r['agents'] ?? 0), 'direct' => (int) ($r['n'] ?? 0) - (int) ($r['agents'] ?? 0)];
    }

    private static function shape(array $r): array
    {
        $r['is_agent']  = (int) ($r['agent'] ?? 0) === 1;
        $r['tours']     = (int) $r['tours'];
        $r['inquiries'] = (int) $r['inquiries'];
        $r['quote_markup_pct'] = $r['quote_markup_pct'] === null || $r['quote_markup_pct'] === '' ? null : (float) $r['quote_markup_pct'];
        return $r;
    }

    /** One record with its recent tours, inquiries and history. */
    public static function one($db, string $acc): ?array
    {
        $r = $db->query(self::base() . ' WHERE c.Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
        if (!$r) {
            return null;
        }
        $r = self::shape($r);
        $r['recent_tours'] = $db->query('SELECT tour_id, manual_id, consignee, start_date, end_date, passenger_count, tour_cancel FROM consignee WHERE agent = ? ORDER BY start_date DESC LIMIT 10', [$acc])->getResultArray();
        try {
            $r['recent_inquiries'] = $db->query('SELECT id, inquiry_no, status, tour_name, arrival_date, pax_total FROM tf_intake WHERE agent_acc = ? AND archived_at IS NULL ORDER BY id DESC LIMIT 10', [$acc])->getResultArray();
        } catch (\Throwable $e) {
            $r['recent_inquiries'] = [];
        }
        try {
            $r['history'] = $db->query("SELECT action, summary, by_name, by_user, `at` FROM tf_activity WHERE entity = 'customer' AND entity_id = ? ORDER BY id DESC LIMIT 30", [(int) $r['id']])->getResultArray();
        } catch (\Throwable $e) {
            $r['history'] = [];
        }
        return $r;
    }

    /** What the quotation reads for an agent: currency and default markup (null = the company default). */
    public static function forInquiry($db, ?string $acc): array
    {
        $out = ['is_agent' => false, 'quote_currency' => null, 'quote_markup_pct' => null, 'name' => null];
        if (!$acc || !self::installed($db)) {
            return $out;
        }
        $r = $db->query('SELECT Name, agent, quote_currency, quote_markup_pct FROM customers WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
        if (!$r) {
            return $out;
        }
        return ['is_agent' => (int) $r['agent'] === 1, 'quote_currency' => $r['quote_currency'] ?: null, 'quote_markup_pct' => $r['quote_markup_pct'] === null || $r['quote_markup_pct'] === '' ? null : (float) $r['quote_markup_pct'], 'name' => $r['Name']];
    }

    private static function log($db, int $customerId, string $action, string $summary, array $changes, string $user, ?int $userId, ?string $name): void
    {
        try {
            $db->query('INSERT INTO tf_activity (entity, entity_id, intake_id, tour_id, action, summary, changes_json, by_user_id, by_user, by_name, `at`) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                ['customer', $customerId, null, null, $action, mb_substr($summary, 0, 500), json_encode($changes, JSON_UNESCAPED_UNICODE), $userId, $user, $name, date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
        }
    }

    /** Set or clear the agent flag, with the reason, who and when. */
    public static function setAgent($db, string $acc, bool $on, string $user, ?int $userId, ?string $name, string $note = ''): array
    {
        $r = $db->query('SELECT ID, Name, agent FROM customers WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
        if (!$r) {
            return ['ok' => false, 'message' => 'No customer with code ' . $acc . '.'];
        }
        $was = (int) $r['agent'] === 1;
        if ($was === $on) {
            return ['ok' => true, 'message' => $r['Name'] . ' is already ' . ($on ? 'an agent' : 'a direct customer') . '.'];
        }
        $db->query('UPDATE customers SET agent = ?, agent_set_by = ?, agent_set_at = ?, agent_note = ? WHERE ID = ?', [$on ? 1 : 0, $name ?: $user, date('Y-m-d H:i:s'), $note !== '' ? mb_substr($note, 0, 255) : null, (int) $r['ID']]);
        self::log($db, (int) $r['ID'], $on ? 'agent_set' : 'agent_cleared', ($on ? 'Marked as an agent' : 'Marked as a direct customer') . ($note !== '' ? ' — ' . $note : ''), [['what' => 'agent', 'from' => $was ? 'yes' : 'no', 'to' => $on ? 'yes' : 'no']], $user, $userId, $name);
        return ['ok' => true, 'message' => $r['Name'] . ' is now ' . ($on ? 'an agent: the inquiry offers it first and can read its quotation settings.' : 'a direct customer.')];
    }

    /** The quotation currency and default markup of an agent. */
    public static function setQuote($db, string $acc, ?string $currency, $pct, string $user, ?int $userId, ?string $name): array
    {
        $r = $db->query('SELECT ID, Name, agent, quote_currency, quote_markup_pct FROM customers WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
        if (!$r) {
            return ['ok' => false, 'message' => 'No customer with code ' . $acc . '.'];
        }
        $currency = strtoupper(trim((string) $currency));
        if ($currency !== '' && !preg_match('/^[A-Z]{3}$/', $currency)) {
            return ['ok' => false, 'message' => 'The quotation currency is a three-letter code (USD, EUR, JPY …) or blank for the company setting.'];
        }
        $pct = trim((string) $pct);
        if ($pct !== '' && (!is_numeric($pct) || (float) $pct < 0 || (float) $pct > 100)) {
            return ['ok' => false, 'message' => 'The default markup is a percentage between 0 and 100, or blank for the company default.'];
        }
        $pctV = $pct === '' ? null : round((float) $pct, 2);
        $db->query('UPDATE customers SET quote_currency = ?, quote_markup_pct = ? WHERE ID = ?', [$currency ?: null, $pctV, (int) $r['ID']]);
        $changes = [];
        if (($r['quote_currency'] ?: null) !== ($currency ?: null)) {
            $changes[] = ['what' => 'quotation currency', 'from' => $r['quote_currency'] ?: 'company setting', 'to' => $currency ?: 'company setting'];
        }
        $old = $r['quote_markup_pct'] === null || $r['quote_markup_pct'] === '' ? null : (float) $r['quote_markup_pct'];
        if ($old !== $pctV) {
            $changes[] = ['what' => 'default markup %', 'from' => $old === null ? 'company default' : (string) $old, 'to' => $pctV === null ? 'company default' : (string) $pctV];
        }
        if ($changes) {
            self::log($db, (int) $r['ID'], 'quote_settings', 'Quotation settings changed: ' . implode('; ', array_map(static fn ($c) => $c['what'] . ' ' . $c['from'] . ' → ' . $c['to'], $changes)), $changes, $user, $userId, $name);
        }
        return ['ok' => true, 'message' => $changes ? 'Quotation settings saved for ' . $r['Name'] . '.' : 'Nothing changed.'];
    }

    /** Schema 55: the reviewed list is flagged once; everything else stays as it is. */
    public static function baseline($db): string
    {
        if (!self::installed($db)) {
            return 'Customers & Agents: the columns are missing; nothing flagged.';
        }
        $n = 0;
        foreach (self::INITIAL_AGENTS as $acc) {
            $r = $db->query('SELECT ID, agent FROM customers WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
            if ($r && (int) $r['agent'] !== 1) {
                $db->query('UPDATE customers SET agent = 1, agent_set_by = ?, agent_set_at = ?, agent_note = ? WHERE ID = ?', ['installer', date('Y-m-d H:i:s'), 'Stage 6 agent list, reviewed 29 Oct 2026', (int) $r['ID']]);
                self::log($db, (int) $r['ID'], 'agent_set', 'Marked as an agent — Stage 6 agent list, reviewed 29 Oct 2026', [['what' => 'agent', 'from' => 'no', 'to' => 'yes']], 'installer', null, 'Installer');
                $n++;
            }
        }
        return 'Customers & Agents (schema 55): ' . $n . ' customer(s) flagged as agents from the reviewed list; the rest stay direct customers.';
    }
}
