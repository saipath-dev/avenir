#!/bin/bash
# tf-paydesk-r5 final verification: the whole r4 suite (0–46 + 47 rollback is separate) and the r5 tests 48–62 on BOTH engines
P="$(cd "$(dirname "$0")" && pwd)"; cd "$P"
for eng in myisam innodb; do
  if [ $eng = innodb ]; then export CONVERT=1; else export CONVERT=0; fi
  echo "=================== $eng: r4 suite ==================="; bash run_all.sh 2>&1 | tail -3
  cp results.json results_r5_${eng}_suite.json; cp flow_run.log flow_run_${eng}.log
  echo "=================== $eng: bulk tests 48–62 ==================="; bash bulk_reset.sh 2>&1 | tail -2
  cp results_bulk.json results_bulk_${eng}.json; cp bulk_run.out bulk_run_${eng}.out
done
echo FINAL_RUNS_DONE
