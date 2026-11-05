#!/bin/bash
# The new-generation regression battery, in one go: the PHP side (PHPUnit over the real libraries, test database only),
# then every screen suite (Playwright over the real application), then the survey. One log per run under _work/.
#
#   bash tests/newgen/run_battery.sh            everything
#   bash tests/newgen/run_battery.sh php        the PHP side only
#   bash tests/newgen/run_battery.sh screens    the screen suites only
#
# Environment (defaults suit the workspace copy): NEWGEN_HOST=http://127.0.0.1:8080  NEWGEN_DB_SOCKET=<mysql socket> or
# NEWGEN_DB_HOST/PORT/USER  NEWGEN_DB_NAME=avenir_test (must end in _test — both sides refuse any other name).
set -u
HERE="$(cd "$(dirname "$0")" && pwd)"; ROOT="$(cd "$HERE/../.." && pwd)"
WORK="${NEWGEN_WORK:-$HERE/_work}"; mkdir -p "$WORK"
STAMP=$(date +%Y%m%d-%H%M%S); LOG="$WORK/battery-$STAMP.log"; SUM="$WORK/battery-$STAMP.summary.txt"
WHAT="${1:-all}"
echo "New-generation regression battery — $(date '+%Y-%m-%d %H:%M:%S') — $ROOT" | tee "$LOG" "$SUM"
fails=0
if [ "$WHAT" = "all" ] || [ "$WHAT" = "php" ]; then
  echo "== PHP side (PHPUnit)" | tee -a "$LOG" "$SUM"
  (cd "$ROOT" && timeout 1500 vendor/bin/phpunit -c tests/newgen/phpunit.xml --testdox) >> "$LOG" 2>&1; rc=$?
  line=$(grep -E "^(OK|Tests:|ERRORS|FAILURES)" "$LOG" | tail -1)
  echo "   phpunit: ${line:-no summary} (exit $rc)" | tee -a "$SUM"
  [ $rc -ne 0 ] && fails=$((fails+1))
fi
if [ "$WHAT" = "all" ] || [ "$WHAT" = "screens" ]; then
  echo "== Screen suites (Playwright)" | tee -a "$LOG" "$SUM"
  for s in ui_1c ui_hotel_work_flow ui_desks ui_masters ui_tour_operations ui_hotel_rates ui_hotel_codes ui_rates_pending ui_security sweep_http; do
    echo "-- $s" >> "$LOG"
    out=$(cd "$HERE/screens" && timeout 900 python3 "$s.py" 2>&1); rc=$?
    echo "$out" >> "$LOG"
    line=$(echo "$out" | grep -E "^[0-9]+ ok, [0-9]+ failed" | tail -1)
    fl=$(echo "$out" | grep -E "^  FAIL" | head -5)
    echo "   $s: ${line:-NO SUMMARY (exit $rc)}" | tee -a "$SUM"
    [ -n "$fl" ] && echo "$fl" | tee -a "$SUM"
    { [ $rc -ne 0 ] || [ -z "$line" ]; } && fails=$((fails+1))
  done
  echo "-- survey" >> "$LOG"
  out=$(cd "$HERE/screens" && timeout 900 python3 survey.py "$WORK/shots-$STAMP" 2>&1); rc=$?; echo "$out" >> "$LOG"
  echo "   survey: $(echo "$out" | grep -E "^screens:" | tail -1)" | tee -a "$SUM"
  [ $rc -ne 0 ] && fails=$((fails+1))
fi
echo "== $( [ $fails -eq 0 ] && echo "ALL GREEN" || echo "$fails suite(s) with a problem" ) — log $LOG" | tee -a "$SUM"
exit $fails
