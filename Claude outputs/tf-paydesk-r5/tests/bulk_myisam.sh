#!/bin/bash
# tf-paydesk-r5 — tests 48–62 with the four voucher tables on MyISAM (the development system's engine): restore the snapshot, convert back, run
set -e
P="$(cd "$(dirname "$0")" && pwd)"; S="$(dirname "$P")"; M="mysql -uroot --socket=$S/mysql.sock"
$M -e "DROP DATABASE IF EXISTS avenir_test; CREATE DATABASE avenir_test"; $M avenir_test < "$P/db_after_suite.sql"
for t in voucher v_inv invoice_no payment_request; do $M avenir_test -e "ALTER TABLE $t ENGINE=MyISAM"; done
$M -N avenir_test -e "SELECT CONCAT('engine check: ', GROUP_CONCAT(CONCAT(TABLE_NAME,'=',ENGINE))) FROM information_schema.TABLES WHERE TABLE_SCHEMA='avenir_test' AND TABLE_NAME IN ('voucher','v_inv','invoice_no','payment_request')"
mkdir -p "$S/smtpsink/msgs_old"; mv "$S/smtpsink/msgs/"*.eml "$S/smtpsink/msgs_old/" 2>/dev/null || true
cd "$P" && python3 bulk_test.py 2>&1 | tee "$P/bulk_run_myisam.out" | grep -v "^  ok"; tail -1 "$P/bulk_run_myisam.out"; cp "$P/results_bulk.json" "$P/results_bulk_myisam.json"
