"""tf-ledger-guard-r1: apply the advance_delete hardening to a copy of TourDetailsSearchController.php and Routes.php.
usage: python3 patch.py <controller in> <controller out> <routes in> <routes out>"""
import sys
ci, co, ri, ro = sys.argv[1:5]

# ---------- controller (CRLF file: keep its line endings) ----------
s = open(ci, encoding='utf-8', newline='').read()
nl = '\r\n' if '\r\n' in s else '\n'
old_start = s.index('    public function advance_delete()')
old_end = s.index('\n    }', old_start) + len('\n    }')
if nl == '\r\n':
    old_end = s.index('\r\n    }', old_start) + len('\r\n    }')
new = r'''    // ---- tf-ledger-guard-r1 (11 Oct 2026) ---------------------------------------------------------------------------
    // Taking a tour advance out of the tour's advance list sets account_runing.aflag = 1 on every leg of its entry, as
    // before. It is now an Accounts action (the administrator, or a profile holding the "accounts" menu), done only by a
    // POST that carries CodeIgniter's CSRF token, the typed confirmation "FLAG <entry id>" and a reason, after a GET
    // "prepare" step that changes nothing. Only an advance entry (a leg on 6-11-107-10381, the account the advance list
    // reads) whose legs are all unflagged can be flagged. A GET to advance_delete never changes a row. Every change is
    // recorded in tpd_audit with the user, time, entry and the rows before and after; every refusal is recorded too.
    private const LEDGER_GUARD_ADVANCE_ACCOUNT = '6-11-107-10381';

    private function ledgerGuardAllowed(): bool
    {
        $md = (new MenuLoader())->loadMenuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
            if (trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), '/') === 'accounts') {
                return true;
            }
        }
        return false;
    }

    private function ledgerGuardAudit(string $action, $entryId, ?array $prev, ?array $new, string $reason): void
    {
        try {
            \App\Libraries\TourPayments\Audit::log(\Config\Database::connect(), ['action' => $action, 'ref_type' => 'ledger_entry', 'ref_id' => (string) $entryId, 'prev' => $prev, 'new' => $new, 'reason' => mb_substr($reason, 0, 250)]);
        } catch (\Throwable $e) {
            log_message('error', 'tf-ledger-guard-r1: audit not written: ' . $e->getMessage());
        }
    }

    private function ledgerGuardRefuse(int $http, string $action, $entryId, string $message, ?array $detail = null)
    {
        $this->ledgerGuardAudit($action, $entryId, null, ['method' => $this->request->getMethod(), 'detail' => $detail], $message);
        return $this->response->setStatusCode($http)->setJSON(['status' => $http, 'message' => $message . ' The attempt was recorded.']);
    }

    private function ledgerGuardRows(int $id): array
    {
        return \Config\Database::connect()->query('SELECT * FROM account_runing WHERE ID = ? ORDER BY L_Code, Dr, Cr', [$id])->getResultArray();
    }

    /** Why this entry may not be flagged, or null when it may. */
    private function ledgerGuardIneligible(array $rows): ?string
    {
        if (!$rows) {
            return 'No ledger entry has this number.';
        }
        $advance = false;
        foreach ($rows as $r) {
            if ((string) $r['L_Code'] === self::LEDGER_GUARD_ADVANCE_ACCOUNT) {
                $advance = true;
            }
            if ((int) $r['aflag'] !== 0) {
                return 'This entry is not an open advance (a leg is already flagged, or it belongs to a posted voucher: aflag ' . (int) $r['aflag'] . ').';
            }
        }
        return $advance ? null : 'Only a tour advance entry (a leg on ' . self::LEDGER_GUARD_ADVANCE_ACCOUNT . ') can be taken out of the advance list.';
    }

    // GET tour/tour_details_search/advance_delete — the old way in: refused, never changes a row
    public function advance_delete_get()
    {
        return $this->ledgerGuardRefuse(405, 'access_denied', $this->request->getGet('id') ?? '', 'A ledger entry is never changed by a GET request: prepare the change, then confirm it (POST).');
    }

    // GET tour/tour_details_search/advance_delete/prepare?id= — what would change, and the CSRF token; changes nothing
    public function advance_delete_prepare()
    {
        $id = (int) ($this->request->getGet('id') ?? 0);
        if (!$this->ledgerGuardAllowed()) {
            return $this->ledgerGuardRefuse(403, 'access_denied', $id, 'Taking an advance out of the ledger list is an Accounts action.');
        }
        $rows = $id > 0 ? $this->ledgerGuardRows($id) : [];
        $why = $this->ledgerGuardIneligible($rows);
        return $this->response->setJSON(['status' => 200, 'entry' => $id, 'rows' => $rows, 'eligible' => $why === null, 'why' => $why,
            'confirm_phrase' => 'FLAG ' . $id, 'csrf' => ['name' => csrf_token(), 'header' => csrf_header(), 'hash' => csrf_hash()]]);
    }

    // POST tour/tour_details_search/advance_delete {id, confirm: "FLAG <id>", reason, CSRF token}
    public function advance_delete()
    {
        $id = (int) ($this->request->getPost('id') ?? 0);
        if (!$this->ledgerGuardAllowed()) {
            return $this->ledgerGuardRefuse(403, 'access_denied', $id, 'Taking an advance out of the ledger list is an Accounts action.');
        }
        try {
            service('security')->verify($this->request);
        } catch (\CodeIgniter\Security\Exceptions\SecurityException $e) {
            return $this->ledgerGuardRefuse(403, 'access_denied', $id, 'The security token is missing or not valid; prepare the change again.');
        }
        $confirm = trim((string) ($this->request->getPost('confirm') ?? ''));
        $reason = trim((string) ($this->request->getPost('reason') ?? ''));
        if ($id <= 0 || $confirm !== 'FLAG ' . $id) {
            return $this->ledgerGuardRefuse(400, 'ledger_flag_refused', $id, 'Type the confirmation exactly: FLAG ' . max($id, 0) . '.', ['confirm' => mb_substr($confirm, 0, 40)]);
        }
        if (mb_strlen($reason) < 5) {
            return $this->ledgerGuardRefuse(400, 'ledger_flag_refused', $id, 'Give the reason for taking this advance out (at least 5 characters).');
        }
        $before = $this->ledgerGuardRows($id);
        if ($why = $this->ledgerGuardIneligible($before)) {
            return $this->ledgerGuardRefuse($before ? 409 : 404, 'ledger_flag_refused', $id, $why);
        }
        $db = \Config\Database::connect();
        $db->query('UPDATE account_runing SET aflag = 1 WHERE ID = ? AND aflag = 0', [$id]);
        $changed = $db->affectedRows();
        $after = $this->ledgerGuardRows($id);
        $this->ledgerGuardAudit('ledger_entry_flagged', $id, ['rows' => $before], ['rows' => $after, 'rows_changed' => $changed], $reason);
        return $this->response->setJSON(['status' => 200, 'message' => 'Successfully Updated: ' . $changed . ' ledger row(s) of entry ' . $id . ' taken out of the advance list (recorded).', 'rows' => $after]);
    }'''.replace('\n', nl)
s = s[:old_start] + new + s[old_end:]
open(co, 'w', encoding='utf-8', newline='').write(s)

# ---------- routes ----------
r = open(ri, encoding='utf-8', newline='').read()
rnl = '\r\n' if '\r\n' in r else '\n'
old = "        $routes->get('advance_delete', [TourDetailsSearchController::class, 'advance_delete']); // route : base_url/tour/tour_details/advance_details"
assert r.count(old) == 1, r.count(old)
new_r = rnl.join([
    "        // tf-ledger-guard-r1: a GET never changes a ledger row (refused and recorded); prepare (GET, changes nothing), then",
    "        // confirm by POST with the CSRF token, the typed confirmation and a reason — Accounts only, checked in the controller",
    "        $routes->get('advance_delete', [TourDetailsSearchController::class, 'advance_delete_get']);",
    "        $routes->get('advance_delete/prepare', [TourDetailsSearchController::class, 'advance_delete_prepare']);",
    "        $routes->post('advance_delete', [TourDetailsSearchController::class, 'advance_delete']);"])
open(ro, 'w', encoding='utf-8', newline='').write(r.replace(old, new_r))
print('patched', 'controller newline', repr(nl), 'routes newline', repr(rnl))
