#!/bin/bash
# tf-paydesk-r5 — restore the post-suite snapshot (db_after_suite.sql, written by run_all.sh) and run tests 48–61 (test copy only)
set -e
P="$(cd "$(dirname "$0")" && pwd)"; S="$(dirname "$P")"
M="mysql -uroot --socket=$S/mysql.sock"
$M -e "DROP DATABASE IF EXISTS avenir_test; CREATE DATABASE avenir_test"
$M avenir_test < "$P/db_after_suite.sql"
mkdir -p "$S/smtpsink/msgs_old"; mv "$S/smtpsink/msgs/"*.eml "$S/smtpsink/msgs_old/" 2>/dev/null || true
cd "$P" && python3 bulk_test.py 2>&1 | tee "$P/bulk_run.out" | grep -v "^  ok"; tail -1 "$P/bulk_run.out"
