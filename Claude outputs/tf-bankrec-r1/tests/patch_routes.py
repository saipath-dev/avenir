"""tf-bankrec-r1: adds the Daily Bank Reconciliation routes to app/Config/Routes.php inside the existing `accounts` group,
just before its bank_unrealised_value group. Keeps the file's line endings. Idempotent (does nothing if already there)."""
import sys
p = sys.argv[1]
raw = open(p, 'rb').read()
crlf = b'\r\n' in raw
s = raw.decode('utf-8')
MARK = '// tf-bankrec-r1 — Daily Bank Reconciliation'
if MARK in s:
    print('already patched'); sys.exit(0)
anchor = "    $routes->group('bank_unrealised_value', static function ($routes) {"
assert s.count(anchor) == 1, 'anchor not found exactly once'
C = '\\\\App\\\\Controllers\\\\Accounts\\\\DailyBankRecController::'
C = '\\App\\Controllers\\Accounts\\DailyBankRecController::'
gets = [('/', 'index'), ('account/(:num)', 'account/$1'), ('fx', 'fx'), ('api/accounts', 'api_accounts'), ('api/day', 'api_day'), ('api/statements', 'api_statements'),
        ('api/statement', 'api_statement'), ('api/open', 'api_open'), ('api/suggest', 'api_suggest'), ('api/rates', 'api_rates'), ('api/corrections', 'api_corrections'),
        ('api/days', 'api_days'), ('api/audit', 'api_audit')]
posts = [('setup', 'setup'), ('mapping/propose', 'mapping_propose'), ('mapping/decide', 'mapping_decide'), ('statement/preview', 'statement_preview'), ('statement/save', 'statement_save'),
         ('statement/accept', 'statement_accept'), ('statement/void', 'statement_void'), ('line/edit', 'line_edit'), ('line/category', 'line_category'), ('rate/save', 'rate_save'),
         ('match/create', 'match_create'), ('match/undo', 'match_undo'), ('mark/create', 'mark_create'), ('mark/clear', 'mark_clear'), ('transfer/create', 'transfer_create'),
         ('transfer/undo', 'transfer_undo'), ('correction/propose', 'correction_propose'), ('correction/charges', 'correction_charges'), ('correction/decide', 'correction_decide'),
         ('correction/withdraw', 'correction_withdraw'), ('correction/posted', 'correction_posted'), ('opening/propose', 'opening_propose'), ('opening/decide', 'opening_decide'),
         ('day/prepare', 'day_prepare'), ('day/review', 'day_review')]
lines = ['    ' + MARK + ' (Step 2): read pages are GET; every change is a POST with the CSRF token (see the controller).',
         "    $routes->group('daily_bank_rec', static function ($routes) {"]
for u, m in gets:
    lines.append(f"        $routes->get('{u}', '{C}{m}');")
for u, m in posts:
    lines.append(f"        $routes->post('{u}', '{C}{m}');")
lines.append('    });')
lines.append('')
block = '\n'.join(lines) + '\n'
s = s.replace(anchor, block + anchor, 1)
if crlf:
    s = s.replace('\r\n', '\n').replace('\n', '\r\n')
open(p, 'wb').write(s.encode('utf-8'))
print('patched', 'CRLF' if crlf else 'LF')
