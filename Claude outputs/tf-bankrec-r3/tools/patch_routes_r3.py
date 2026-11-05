"""tf-bankrec-r3: adds the one r3 route (correction/reverify) to app/Config/Routes.php inside the tf-bankrec-r1 route group,
after the r2 route match/explain. Keeps the file's line endings. Idempotent. Used to build src/app/Config/Routes.php from
the tf-bankrec-r2 file."""
import sys
p = sys.argv[1]
raw = open(p, 'rb').read(); crlf = b'\r\n' in raw
s = raw.decode('utf-8').replace('\r\n', '\n')
MARK = '// tf-bankrec-r3 (12 Oct 2026)'
if MARK in s:
    print('already patched'); sys.exit(0)
C = '\\App\\Controllers\\Accounts\\DailyBankRecController::'
anchor = f"        $routes->post('match/explain', '{C}match_explain');\n"
assert s.count(anchor) == 1, 'anchor (the r2 match/explain route) not found exactly once'
add = (f"        {MARK}: verify again a posted entry that changed in the ledger\n"
       f"        $routes->post('correction/reverify', '{C}correction_reverify');\n")
s = s.replace(anchor, anchor + add, 1)
if crlf:
    s = s.replace('\n', '\r\n')
open(p, 'wb').write(s.encode('utf-8'))
print('patched', 'CRLF' if crlf else 'LF')
