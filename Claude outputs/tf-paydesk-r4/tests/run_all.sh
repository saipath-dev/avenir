#!/bin/bash
# tf-paydesk — reset the TEST database to its pre-flow snapshot and run the whole suite (test copy only: avenir_test on the
# scratch MariaDB, the PHP test server on 8081 (+8082 for the concurrency test), the mail sink on 2465).
set -e
P="$(cd "$(dirname "$0")" && pwd)"; S="$(dirname "$P")"
M="mysql -uroot --socket=$S/mysql.sock avenir_test"
bash "$S/svc.sh"
$M -e "DROP TRIGGER IF EXISTS tpd_voucher_vnum_guard; DROP TABLE IF EXISTS tpd_batch"
$M < "$P/db_before_flow.sql"
python3 "$P/setup_testdata.py" > /dev/null
# the administrator's Setup: tables, columns, privileges, the voucher-number guard (schema version 6)
python3 - <<EOF
import sys; sys.path.insert(0, '$P')
from api import Client
j = Client('admin').post('tour/tour_payment_dashboard/setup', data={})
print('setup:', j.get('status'), '|', ' | '.join((j.get('notes') or [])[:12]))
EOF
python3 "$P/setup_testdata.py" > /dev/null
mkdir -p "$S/smtpsink/msgs_old"; mv "$S/smtpsink/msgs/"*.eml "$S/smtpsink/msgs_old/" 2>/dev/null || true
cd "$P" && python3 flow_test.py 2>&1 | tee "$P/flow_run.log" | grep -v "^  ok" ; tail -1 "$P/flow_run.log"
