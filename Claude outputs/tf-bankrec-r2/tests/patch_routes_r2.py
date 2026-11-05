"""tf-bankrec-r2: adds the three r2 routes to app/Config/Routes.php inside the tf-bankrec-r1 route group (after
day/review). Keeps the file's line endings. Idempotent."""
import sys
p = sys.argv[1]
raw = open(p, 'rb').read(); crlf = b'\r\n' in raw
s = raw.decode('utf-8').replace('\r\n', '\n')
MARK = '// tf-bankrec-r2 (12 Oct 2026)'
if MARK in s:
    print('already patched'); sys.exit(0)
C = '\\App\\Controllers\\Accounts\\DailyBankRecController::'
anchor = f"        $routes->post('day/review', '{C}day_review');\n"
assert s.count(anchor) == 1, 'anchor (the r1 day/review route) not found exactly once'
add = (f"        {MARK}: opening outstanding items, the daily record with its snapshot, explained differences\n"
       f"        $routes->get('api/opening_candidates', '{C}api_opening_candidates');\n"
       f"        $routes->get('api/day_record', '{C}api_day_record');\n"
       f"        $routes->post('match/explain', '{C}match_explain');\n")
s = s.replace(anchor, anchor + add, 1)
if crlf:
    s = s.replace('\n', '\r\n')
open(p, 'wb').write(s.encode('utf-8'))
print('patched', 'CRLF' if crlf else 'LF')
