#!/bin/bash
# reset the rollback-test environment: the live-equivalent copy back to the live files, its database back to "never installed"
P="$(cd "$(dirname "$0")" && pwd)"; S="$(dirname "$P")"; K="$S/kit/tf-paydesk-r4"
for f in $(cd $S/paydesk/before && find app -type f); do cp $S/paydesk/before/$f $S/tl/$f; done
for f in $(cd $K/src && find . -type f | sed 's#^\./##'); do grep -q "'$f'" $K/apply.py || rm -f $S/tl/$f; done
rm -rf $K/before $S/tl/writable/paydesk_rolled_back_*
M="mysql -uroot --socket=$S/mysql.sock"
$M -e "DROP DATABASE IF EXISTS avenir_rb; CREATE DATABASE avenir_rb CHARACTER SET utf8mb4"
$M avenir_rb < $S/paydesk/db_before_flow.sql
$M avenir_rb -e "DROP TRIGGER IF EXISTS tpd_voucher_vnum_guard; DROP TABLE IF EXISTS tpd_meta, tpd_bank_account, tpd_supplier_bank, tpd_request_ext, tpd_bank_initiation, tpd_bank_approval, tpd_remittance, tpd_document, tpd_expense, tpd_batch, tpd_audit"
TC_DB=avenir_rb python3 $P/setup_testdata.py > /dev/null
curl -s -o /dev/null http://127.0.0.1:8083/ || (cd $S && setsid nohup php -S 127.0.0.1:8083 $S/router_tl.php >> $S/php8083.log 2>&1 < /dev/null & sleep 2)
echo "rb environment reset"
