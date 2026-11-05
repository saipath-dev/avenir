<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\BankRec\Access;
use App\Libraries\BankRec\Corrections;
use App\Libraries\BankRec\Matching;
use App\Libraries\BankRec\Rec;
use App\Libraries\BankRec\Schema;
use App\Libraries\BankRec\Statements;
use App\Libraries\MenuLoader;
use App\Libraries\TourPayments\Audit;
use App\Libraries\TourPayments\Perms;

/**
 * tf-bankrec-r1 (Step 2) — Daily Bank Reconciliation: accounts and their confirmed ledger mapping, statements (upload or
 * typed, never posted), matching, classification, transfers with the day's rate, bank charges, proposed corrections for
 * review (never posted), opening items and the daily record (prepared by one user, reviewed by another).
 *
 * Every change is a POST with CodeIgniter's CSRF token and the right privilege, and is written to the audit trail with the
 * values before and after; refusals are audited too. Nothing here writes to the ledger (account_runing) or its flags.
 */
class DailyBankRecController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function json(int $code, string $message, array $extra = [])
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => $code, 'message' => $message, 'csrf' => csrf_hash()] + $extra);
    }

    private function refuse(int $code, string $message, string $what)
    {
        try {
            Audit::log($this->db(), ['action' => 'access_denied', 'ref_type' => 'brc', 'ref_id' => mb_substr($what, 0, 80), 'new' => ['method' => $this->request->getMethod(), 'path' => $this->request->getUri()->getPath()], 'reason' => $message]);
        } catch (\Throwable $e) {
        }
        return $this->json($code, $message);
    }

    /** Privilege + CSRF + schema for every change. Returns a response when refused, null when allowed. */
    private function guard(string $cap, string $what)
    {
        if (!Access::can($cap)) {
            return $this->refuse(403, $cap === 'review' ? 'This needs the reconciliation review privilege.' : ($cap === 'prepare' ? 'This needs the reconciliation prepare privilege.' : 'This needs the Daily Bank Reconciliation privilege.'), $what);
        }
        try {
            service('security')->verify($this->request);
        } catch (\CodeIgniter\Security\Exceptions\SecurityException $e) {
            return $this->refuse(403, 'The security token is missing or out of date; reload the page and try again.', $what);
        }
        if ($what !== 'setup' && !Schema::installed($this->db())) {
            return $this->json(409, 'The reconciliation is not set up yet (System Administrator: Set up).');
        }
        return null;
    }

    private function viewGuard(string $what)
    {
        if (!Access::can('view')) {
            return $this->refuse(403, 'This needs the Daily Bank Reconciliation privilege.', $what);
        }
        if (!Schema::installed($this->db())) {
            return $this->json(409, 'The reconciliation is not set up yet.');
        }
        return null;
    }

    private function in(string $k, $default = '')
    {
        $v = $this->request->getPost($k);
        if ($v === null) {
            $j = null;
            if (str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
                try {
                    $j = $this->request->getJSON(true);
                } catch (\Throwable $e) {
                    $j = null;
                }
            }
            $v = is_array($j) && array_key_exists($k, $j) ? $j[$k] : $default;
        }
        return is_string($v) ? trim($v) : $v;
    }

    /** [code, message, id?, checks?] from the libraries → JSON */
    private function out(array $r, array $extra = [])
    {
        foreach (array_slice($r, 2) as $v) {
            if (is_int($v)) {
                $extra['id'] = $v;
            } elseif (is_array($v)) {
                $extra['checks'] = $v;
            }
        }
        return $this->json($r[0], $r[1], $extra);
    }

    private function page(string $view, array $extra = [])
    {
        if (!Access::can('view')) {
            $this->refuse(403, 'Daily Bank Reconciliation page refused.', 'page');
            return redirect()->to(base_url('403'));
        }
        $data = (new MenuLoader())->loadMenuData();
        $db = $this->db();
        $data['brc'] = Access::all();
        $data['brc_status'] = Schema::status($db);
        return view($view, $data + $extra);
    }

    // ---------------------------------------------------------------- pages

    public function index()
    {
        return $this->page('pages/accounts/bankrec/index');
    }

    public function account($id = null)
    {
        $acct = Schema::installed($this->db()) ? Rec::account($this->db(), (int) $id) : null;
        if (!$acct && Access::can('view')) {
            return redirect()->to(base_url('accounts/daily_bank_rec'));
        }
        return $this->page('pages/accounts/bankrec/account', ['acct' => $acct]);
    }

    public function fx()
    {
        return $this->page('pages/accounts/bankrec/fx');
    }

    // ---------------------------------------------------------------- read-only data

    public function api_accounts()
    {
        if ($r = $this->viewGuard('accounts')) {
            return $r;
        }
        $db = $this->db();
        $rows = Rec::accounts($db);
        foreach ($rows as &$a) {
            $last = $db->query("SELECT MAX(period_to) AS d FROM brc_statement WHERE bank_account_id = ? AND status = 'accepted'", [$a['id']])->getRowArray();
            $a['last_statement_to'] = $last['d'] ?? null;
            $a['drafts'] = (int) ($db->query("SELECT COUNT(*) AS n FROM brc_statement WHERE bank_account_id = ? AND status = 'draft'", [$a['id']])->getRowArray()['n'] ?? 0);
            $a['day'] = null;
            if ($a['map_state'] === 'confirmed') {
                $d = Rec::day($db, $a['id'], $a['last_statement_to'] ?: max($a['map']['rec_start'], date('Y-m-d')));
                $a['day'] = ['date' => $d['date'], 'status_code' => $d['status_code'], 'status_label' => $d['status_label'], 'bank' => $d['bank'] ?? null, 'book' => $d['book'] ?? null,
                    'difference_lkr' => $d['reconciliation']['difference_lkr'] ?? null, 'counts' => $d['counts'] ?? null, 'reasons' => array_slice($d['reasons'], 0, 3)];
            }
        }
        $ledgers = $db->query("SELECT L_Code, PName FROM account_posting WHERE B_Code = 1 AND T_Code = 6 AND H_Code = 8 ORDER BY PName")->getResultArray();
        return $this->json(200, 'ok', ['accounts' => $rows, 'ledgers' => $ledgers, 'access' => Access::all()]);
    }

    public function api_day()
    {
        if ($r = $this->viewGuard('day')) {
            return $r;
        }
        $id = (int) $this->request->getGet('account');
        $date = (string) $this->request->getGet('date');
        if (!Rec::validDate($date)) {
            return $this->json(400, 'Give a valid date.');
        }
        $d = Rec::day($this->db(), $id, $date);
        return $this->json($d['ok'] ? 200 : 404, $d['ok'] ? 'ok' : $d['message'], ['day' => $d]);
    }

    public function api_statements()
    {
        if ($r = $this->viewGuard('statements')) {
            return $r;
        }
        $id = (int) $this->request->getGet('account');
        $rows = Rec::statements($this->db(), $id);
        foreach ($rows as &$s) {
            $c = json_decode((string) $s['checks_json'], true) ?: [];
            $s['n_errors'] = count($c['errors'] ?? []);
            $s['n_warnings'] = count($c['warnings'] ?? []);
            unset($s['checks_json']);
        }
        return $this->json(200, 'ok', ['statements' => $rows]);
    }

    public function api_statement()
    {
        if ($r = $this->viewGuard('statement')) {
            return $r;
        }
        $db = $this->db();
        $id = (int) $this->request->getGet('id');
        $s = $db->query('SELECT * FROM brc_statement WHERE id = ?', [$id])->getRowArray();
        if (!$s) {
            return $this->json(404, 'Unknown statement.');
        }
        $chk = $s['status'] === 'void' ? (json_decode((string) $s['checks_json'], true) ?: []) : Statements::recheck($db, $id);
        unset($s['checks_json']);
        return $this->json(200, 'ok', ['statement' => $s, 'lines' => Statements::linesOf($db, $id), 'checks' => $chk]);
    }

    public function api_open()
    {
        if ($r = $this->viewGuard('open')) {
            return $r;
        }
        $id = (int) $this->request->getGet('account');
        $from = (string) $this->request->getGet('from');
        $to = (string) $this->request->getGet('to');
        if (!Rec::validDate($from) || !Rec::validDate($to)) {
            return $this->json(400, 'Give a valid period.');
        }
        $o = Matching::openItems($this->db(), $id, $from, $to, (int) ($this->request->getGet('before') ?: 0));
        unset($o['map']);
        $act = Rec::activeLinks($this->db(), $id);
        return $this->json(200, 'ok', ['open' => $o, 'matches' => $act['matches'], 'marks' => array_values(array_merge($act['line_mark'], $act['book_mark']))]);
    }

    public function api_suggest()
    {
        if ($r = $this->viewGuard('suggest')) {
            return $r;
        }
        $id = (int) $this->request->getGet('account');
        $from = (string) $this->request->getGet('from');
        $to = (string) $this->request->getGet('to');
        if (!Rec::validDate($from) || !Rec::validDate($to)) {
            return $this->json(400, 'Give a valid period.');
        }
        return $this->json(200, 'ok', ['suggestions' => Matching::suggest($this->db(), $id, $from, $to), 'duplicates' => Matching::duplicateCandidates($this->db(), $id, $from, $to)]);
    }

    public function api_rates()
    {
        if ($r = $this->viewGuard('rates')) {
            return $r;
        }
        $db = $this->db();
        $from = (string) ($this->request->getGet('from') ?: date('Y-m-d', strtotime('-60 days')));
        $to = (string) ($this->request->getGet('to') ?: date('Y-m-d', strtotime('+1 day')));
        $rates = $db->query('SELECT * FROM brc_fx_rate WHERE rate_date BETWEEN ? AND ? ORDER BY rate_date DESC, currency', [$from, $to])->getResultArray();
        $sys = [];
        try {
            $sys = $db->query('SELECT rate_date, rate FROM daily_usd_rate WHERE rate_date BETWEEN ? AND ? ORDER BY rate_date DESC', [$from, $to])->getResultArray();
        } catch (\Throwable $e) {
        }
        return $this->json(200, 'ok', ['rates' => $rates, 'system_usd' => $sys, 'transfers' => Matching::transfers($db, $from, $to),
            'transfer_suggestions' => Access::can('prepare') ? Matching::suggestTransfers($db, $from, $to) : []]);
    }

    public function api_corrections()
    {
        if ($r = $this->viewGuard('corrections')) {
            return $r;
        }
        $id = (int) $this->request->getGet('account') ?: null;
        $db = $this->db();
        $opening = $id ? $db->query('SELECT * FROM brc_opening WHERE bank_account_id = ? ORDER BY id DESC', [$id])->getResultArray() : [];
        $ledgers = $db->query('SELECT L_Code, PName FROM account_posting ORDER BY PName')->getResultArray();
        $cl = (string) ($db->query("SELECT v FROM tpd_meta WHERE k = 'bank_charge_ledger_code'")->getRowArray()['v'] ?? '');
        return $this->json(200, 'ok', ['corrections' => Corrections::list($db, $id, $this->request->getGet('status') ?: null), 'opening' => $opening, 'kinds' => Corrections::KINDS, 'ledgers' => $ledgers, 'charge_ledger' => $cl]);
    }

    public function api_days()
    {
        if ($r = $this->viewGuard('days')) {
            return $r;
        }
        $id = (int) $this->request->getGet('account');
        $rows = $this->db()->query('SELECT id, rec_date, currency, bank_closing, bank_closing_lkr, book_closing, difference_lkr, unexplained_lkr, status_code, status_label, prepared_by, prepared_at, prepare_note, review_status, reviewed_by, reviewed_at, review_note FROM brc_day WHERE bank_account_id = ? ORDER BY rec_date DESC, id DESC LIMIT 200', [$id])->getResultArray();
        return $this->json(200, 'ok', ['days' => $rows]);
    }

    public function api_audit()
    {
        if ($r = $this->viewGuard('audit')) {
            return $r;
        }
        $db = $this->db();
        $id = (int) $this->request->getGet('account');
        $a = $id ? Rec::account($db, $id) : null;
        $sql = "SELECT id, at, user, action, ref_type, ref_id, prev_value, new_value, reason, bank_account, ip FROM tpd_audit WHERE (action LIKE 'brc\\_%' OR ref_type LIKE 'brc%')";
        $p = [];
        if ($a) {
            $sql .= ' AND (bank_account = ? OR bank_account IS NULL)';
            $p[] = $a['label'];
        }
        $rows = $db->query($sql . ' ORDER BY id DESC LIMIT 300', $p)->getResultArray();
        return $this->json(200, 'ok', ['audit' => $rows]);
    }

    // ---------------------------------------------------------------- changes (POST)

    public function setup()
    {
        if (!Perms::isAdmin()) {
            return $this->refuse(403, 'Only the System Administrator sets up the reconciliation.', 'setup');
        }
        if ($r = $this->guard('view', 'setup')) {
            return $r;
        }
        $notes = Schema::ensure($this->db());
        Audit::log($this->db(), ['action' => 'brc_setup', 'ref_type' => 'brc_setup', 'ref_id' => 'v' . Schema::VERSION, 'new' => $notes]);
        return $this->json(200, implode(' ', $notes), ['notes' => $notes]);
    }

    public function mapping_propose()
    {
        if ($r = $this->guard('prepare', 'mapping')) {
            return $r;
        }
        return $this->out(Rec::proposeMap($this->db(), (int) $this->in('account'), (string) $this->in('rec_start'), (string) $this->in('note'), Access::user()));
    }

    public function mapping_decide()
    {
        if ($r = $this->guard('review', 'mapping')) {
            return $r;
        }
        return $this->out(Rec::decideMap($this->db(), (int) $this->in('map_id'), (string) $this->in('decision'), (string) $this->in('note'), Access::user()));
    }

    /** Reads an upload (CSV) or typed lines into statement lines; nothing is stored. */
    private function readStatement(int $accountId): array
    {
        $db = $this->db();
        $others = Statements::otherAccountNos($db, $accountId);
        $source = (string) $this->in('source', 'upload');
        if ($source === 'manual') {
            $rows = $this->in('lines', []);
            if (is_string($rows)) {
                $rows = json_decode($rows, true) ?: [];
            }
            return ['source' => 'manual', 'lines' => Statements::manualLines((array) $rows, $others), 'file' => null, 'sha' => null, 'headers' => [], 'map' => []];
        }
        $f = $this->request->getFile('file');
        if (!$f || !$f->isValid()) {
            return ['error' => 'Choose the statement file (CSV) to upload.'];
        }
        if ($f->getSize() > 2 * 1024 * 1024) {
            return ['error' => 'The file is larger than 2 MB.'];
        }
        $ext = strtolower($f->getClientExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return ['error' => 'Upload the statement as CSV (in Excel: File → Save As → CSV). Other formats are not read.'];
        }
        $text = (string) file_get_contents($f->getTempName());
        $rows = Statements::csvRows($text);
        $guess = Statements::guessMap($rows);
        $map = $guess['map'];
        $userMap = $this->in('map', '');
        if (is_string($userMap) && $userMap !== '') {
            $userMap = json_decode($userMap, true) ?: [];
        }
        if (is_array($userMap) && $userMap) {
            $map = array_filter($userMap, static fn ($v) => $v !== '' && $v !== null);
        }
        $hdr = $this->in('header_row', '') === '' ? $guess['header_row'] : (int) $this->in('header_row');
        $lines = Statements::normalise($rows, ['map' => $map, 'header_row' => $hdr, 'date_format' => $this->in('date_format') ?: null, 'amount_sign' => $this->in('amount_sign') ?: 'in_positive'], $others);
        return ['source' => 'upload', 'lines' => $lines, 'file' => $f->getClientName(), 'sha' => hash('sha256', $text), 'headers' => $hdr === null ? [] : ($rows[$hdr] ?? []), 'map' => $map, 'header_row' => $hdr,
            'first_rows' => array_slice($rows, 0, 8)];
    }

    private function statementHeader(): array
    {
        return ['opening_balance' => (string) $this->in('opening_balance'), 'closing_balance' => (string) $this->in('closing_balance'), 'period_from' => (string) $this->in('period_from'),
            'period_to' => (string) $this->in('period_to'), 'currency' => (string) $this->in('currency'), 'note' => (string) $this->in('note')];
    }

    public function statement_preview()
    {
        if ($r = $this->guard('prepare', 'statement')) {
            return $r;
        }
        $acct = (int) $this->in('account');
        if (!Rec::account($this->db(), $acct)) {
            return $this->json(404, 'Unknown bank account.');
        }
        $s = $this->readStatement($acct);
        if (isset($s['error'])) {
            return $this->json(400, $s['error']);
        }
        $chk = Statements::check($this->db(), $acct, $this->statementHeader(), $s['lines']);
        return $this->json(200, 'Checked. Nothing has been kept yet.', ['checks' => $chk, 'lines' => array_slice($s['lines'], 0, 400), 'headers' => $s['headers'], 'map' => $s['map'], 'header_row' => $s['header_row'] ?? null, 'first_rows' => $s['first_rows'] ?? [], 'file' => $s['file']]);
    }

    public function statement_save()
    {
        if ($r = $this->guard('prepare', 'statement')) {
            return $r;
        }
        $acct = (int) $this->in('account');
        if (!Rec::account($this->db(), $acct)) {
            return $this->json(404, 'Unknown bank account.');
        }
        $s = $this->readStatement($acct);
        if (isset($s['error'])) {
            return $this->json(400, $s['error']);
        }
        return $this->out(Statements::saveDraft($this->db(), $acct, $this->statementHeader(), $s['lines'], $s['source'], $s['file'], $s['sha'], Access::user()));
    }

    public function statement_accept()
    {
        if ($r = $this->guard('prepare', 'statement')) {
            return $r;
        }
        return $this->out(Statements::accept($this->db(), (int) $this->in('id'), Access::user()));
    }

    public function statement_void()
    {
        // a draft is voided by a preparer; an accepted statement only by a reviewer (checked in Statements::void)
        if ($r = $this->guard(Access::can('prepare') ? 'prepare' : 'review', 'statement')) {
            return $r;
        }
        $st = $this->db()->query('SELECT status FROM brc_statement WHERE id = ?', [(int) $this->in('id')])->getRowArray();
        if ($st && $st['status'] === 'draft' && !Access::can('prepare')) {
            return $this->refuse(403, 'A draft statement is voided by a preparer.', 'statement');
        }
        return $this->out(Statements::void($this->db(), (int) $this->in('id'), (string) $this->in('reason'), Access::user(), Access::can('review')));
    }

    public function line_edit()
    {
        if ($r = $this->guard('prepare', 'line')) {
            return $r;
        }
        $in = [];
        foreach (['txn_date', 'description', 'reference', 'money_in', 'money_out', 'balance'] as $k) {
            $v = $this->in($k, null);
            if ($v !== null) {
                $in[$k] = $v;
            }
        }
        return $this->out(Statements::editLine($this->db(), (int) $this->in('id'), $in, (string) $this->in('reason'), Access::user()));
    }

    public function line_category()
    {
        if ($r = $this->guard('prepare', 'line')) {
            return $r;
        }
        return $this->out(Statements::recategorise($this->db(), (int) $this->in('id'), (string) $this->in('category'), (string) $this->in('charge_type'), (string) $this->in('reason'), Access::user()));
    }

    public function rate_save()
    {
        if ($r = $this->guard('prepare', 'rate')) {
            return $r;
        }
        return $this->out(Rec::saveRate($this->db(), (string) $this->in('currency'), (string) $this->in('rate_date'), (string) $this->in('rate'), (string) $this->in('source'), (string) $this->in('note'), Access::user()));
    }

    private function listIn(string $k): array
    {
        $v = $this->in($k, []);
        if (is_string($v)) {
            $v = $v === '' ? [] : (json_decode($v, true) ?? explode(',', $v));
        }
        return array_values(array_filter((array) $v, static fn ($x) => $x !== '' && $x !== null));
    }

    public function match_create()
    {
        if ($r = $this->guard('prepare', 'match')) {
            return $r;
        }
        return $this->out(Matching::create($this->db(), (int) $this->in('account'), $this->listIn('line_ids'), $this->listIn('book_keys'), (string) $this->in('method', 'manual'), $this->in('diff_reason') ?: null, (string) $this->in('note'), Access::user()));
    }

    public function match_undo()
    {
        if ($r = $this->guard('prepare', 'match')) {
            return $r;
        }
        return $this->out(Matching::undo($this->db(), (int) $this->in('id'), (string) $this->in('reason'), Access::user()));
    }

    public function mark_create()
    {
        if ($r = $this->guard('prepare', 'mark')) {
            return $r;
        }
        return $this->out(Matching::mark($this->db(), (int) $this->in('account'), (string) $this->in('side'), $this->in('ref'), (string) $this->in('mark'), (string) $this->in('note'), $this->in('expected_date') ?: null, $this->in('related') ?: null, Access::user()));
    }

    public function mark_clear()
    {
        if ($r = $this->guard('prepare', 'mark')) {
            return $r;
        }
        return $this->out(Matching::unmark($this->db(), (int) $this->in('id'), (string) $this->in('reason'), Access::user()));
    }

    public function transfer_create()
    {
        if ($r = $this->guard('prepare', 'transfer')) {
            return $r;
        }
        return $this->out(Matching::createTransfer($this->db(), (int) $this->in('from_line'), (int) $this->in('to_line'), (string) $this->in('note'), Access::user()));
    }

    public function transfer_undo()
    {
        if ($r = $this->guard('prepare', 'transfer')) {
            return $r;
        }
        return $this->out(Matching::undoTransfer($this->db(), (int) $this->in('id'), (string) $this->in('reason'), Access::user()));
    }

    public function correction_propose()
    {
        if ($r = $this->guard('prepare', 'correction')) {
            return $r;
        }
        $lines = $this->in('lines', []);
        if (is_string($lines)) {
            $lines = json_decode($lines, true) ?: [];
        }
        return $this->out(Corrections::propose($this->db(), (int) $this->in('account'), (string) $this->in('kind'), (string) $this->in('description'), (string) $this->in('entry_date'), (array) $lines,
            ['line_ids' => $this->listIn('line_ids'), 'book_keys' => $this->listIn('book_keys')], Access::user()));
    }

    public function correction_charges()
    {
        if ($r = $this->guard('prepare', 'correction')) {
            return $r;
        }
        return $this->out(Corrections::proposeCharges($this->db(), (int) $this->in('account'), $this->listIn('line_ids'), Access::user(), $this->in('charge_ledger') ?: null));
    }

    public function correction_decide()
    {
        if ($r = $this->guard('review', 'correction')) {
            return $r;
        }
        return $this->out(Corrections::decide($this->db(), (int) $this->in('id'), (string) $this->in('decision'), (string) $this->in('note'), Access::user()));
    }

    public function correction_withdraw()
    {
        if ($r = $this->guard('prepare', 'correction')) {
            return $r;
        }
        return $this->out(Corrections::withdraw($this->db(), (int) $this->in('id'), (string) $this->in('reason'), Access::user(), Perms::isAdmin()));
    }

    public function correction_posted()
    {
        if ($r = $this->guard('prepare', 'correction')) {
            return $r;
        }
        return $this->out(Corrections::notePosted($this->db(), (int) $this->in('id'), (string) $this->in('ledger_id'), Access::user()));
    }

    public function opening_propose()
    {
        if ($r = $this->guard('prepare', 'opening')) {
            return $r;
        }
        return $this->out(Corrections::proposeOpening($this->db(), (int) $this->in('account'), (string) $this->in('description'), (string) $this->in('amount'), (string) $this->in('evidence'), Access::user()));
    }

    public function opening_decide()
    {
        $decision = (string) $this->in('decision');
        if ($r = $this->guard($decision === 'withdraw' ? 'prepare' : 'review', 'opening')) {
            return $r;
        }
        return $this->out(Corrections::decideOpening($this->db(), (int) $this->in('id'), $decision, (string) $this->in('note'), Access::user()));
    }

    public function day_prepare()
    {
        if ($r = $this->guard('prepare', 'day')) {
            return $r;
        }
        $date = (string) $this->in('date');
        if (!Rec::validDate($date)) {
            return $this->json(400, 'Give a valid date.');
        }
        return $this->out(Rec::prepareDay($this->db(), (int) $this->in('account'), $date, (string) $this->in('note'), Access::user()));
    }

    public function day_review()
    {
        if ($r = $this->guard('review', 'day')) {
            return $r;
        }
        return $this->out(Rec::reviewDay($this->db(), (int) $this->in('id'), (string) $this->in('decision'), (string) $this->in('note'), Access::user()));
    }
}
