"""tf-bankrec-r3 (D7): add the guard calls to the old Bank Reconciliation controller. Usage: patch_legacy_r3.py <file>
Each write action of the old screen first asks LegacyGuard; a refusal is answered 409 JSON and nothing is changed."""
import sys
p = sys.argv[1]
raw = open(p, 'rb').read().decode('utf-8')
crlf = '\r\n' in raw
t = raw.replace('\r\n', '\n')
if 'LegacyGuard' in t:
    sys.exit('already patched')

def once(old, new):
    global t
    if t.count(old) != 1:
        sys.exit('expected one match for %r, found %d' % (old[:70], t.count(old)))
    t = t.replace(old, new)

once("use App\\Models\\BankReconcilModel;\n\nclass BankReconsillationController",
     "use App\\Models\\BankReconcilModel;\nuse App\\Libraries\\BankRec\\LegacyGuard; // tf-bankrec-r3 (D7)\n\nclass BankReconsillationController")
# a shared helper
once("    public function __construct()\n    {\n        date_default_timezone_set('Asia/Colombo');\n    }\n",
     "    public function __construct()\n    {\n        date_default_timezone_set('Asia/Colombo');\n    }\n\n"
     "    /** tf-bankrec-r3 (D7): the answer when the guard refuses a change in a period the Daily Bank Reconciliation covers. */\n"
     "    private function legacyRefused(array $r)\n    {\n"
     "        return $this->response->setStatusCode($r[0])->setJSON(['status' => $r[0], 'message' => $r[1], 'protected_period' => true]);\n    }\n")
# update(): one ledger entry
once("        $bank = strip_tags($this->request->getVar('bank')); // chethana\n",
     "        $bank = strip_tags($this->request->getVar('bank')); // chethana\n"
     "        if ($r = LegacyGuard::checkRows(\\Config\\Database::connect(), [$id], 'update')) { // tf-bankrec-r3 (D7)\n"
     "            return $this->legacyRefused($r);\n        }\n")
# finalize_selected(): several entries, all refused if any is protected
once("        $records = $this->request->getVar('records'); // Expecting an array of ['id' => ..., 'flag' => ..., 'bank' => ...]\n",
     "        $records = $this->request->getVar('records'); // Expecting an array of ['id' => ..., 'flag' => ..., 'bank' => ...]\n"
     "        if (is_array($records) && ($r = LegacyGuard::checkRows(\\Config\\Database::connect(), array_map(static fn ($x) => is_array($x) ? ($x['id'] ?? 0) : ($x->id ?? 0), $records), 'finalize_selected'))) { // tf-bankrec-r3 (D7)\n"
     "            return $this->legacyRefused($r);\n        }\n")
# reconciliation(): every bank_rec row of the account up to date2
once("        $date2 = strip_tags($this->request->getVar('date2'));\n        $bank = strip_tags($this->request->getVar('bank'));\n\n        $accRunningmodel = new AccountRunningModel();\n\n        $result = $accRunningmodel->select('ID')",
     "        $date2 = strip_tags($this->request->getVar('date2'));\n        $bank = strip_tags($this->request->getVar('bank'));\n"
     "        if ($r = LegacyGuard::checkRange(\\Config\\Database::connect(), (string) $bank, null, (string) $date2, 'reconciliation')) { // tf-bankrec-r3 (D7)\n"
     "            return $this->legacyRefused($r);\n        }\n\n        $accRunningmodel = new AccountRunningModel();\n\n        $result = $accRunningmodel->select('ID')")
# post_unrealised_entries(): rows moved to the unrealised account
once("        $depositArray = is_array($depositArray) ? array_unique($depositArray, SORT_REGULAR) : [];\n",
     "        $depositArray = is_array($depositArray) ? array_unique($depositArray, SORT_REGULAR) : [];\n"
     "        $ids = array_map(static fn ($x) => is_array($x) ? ($x['ID'] ?? 0) : ($x->ID ?? 0), array_merge($itemArray, $depositArray));\n"
     "        if ($r = LegacyGuard::checkRows(\\Config\\Database::connect(), $ids, 'post_unrealised_entries')) { // tf-bankrec-r3 (D7)\n"
     "            return $this->legacyRefused($r);\n        }\n")
# repost_unrealised_entries(): rows moved back, by account and date range
once("        $bank = strip_tags($this->request->getVar('bank'));\n        $output = \"error\"; // Default to error\n        $AccountRunningModel = new AccountRunningModel();\n",
     "        $bank = strip_tags($this->request->getVar('bank'));\n"
     "        if ($r = LegacyGuard::checkRange(\\Config\\Database::connect(), (string) $bank, (string) $date1, (string) $date2, 'repost_unrealised_entries')) { // tf-bankrec-r3 (D7)\n"
     "            return $this->legacyRefused($r);\n        }\n"
     "        $output = \"error\"; // Default to error\n        $AccountRunningModel = new AccountRunningModel();\n")
# repost_single_entry(): one row moved back
once("        $rowId = strip_tags($this->request->getVar('rowId'));\n",
     "        $rowId = strip_tags($this->request->getVar('rowId'));\n"
     "        if ($r = LegacyGuard::checkRows(\\Config\\Database::connect(), [$rowId], 'repost_single_entry')) { // tf-bankrec-r3 (D7)\n"
     "            return $this->legacyRefused($r);\n        }\n")
if crlf:
    t = t.replace('\n', '\r\n')
open(p, 'wb').write(t.encode('utf-8'))
print('patched', p)
