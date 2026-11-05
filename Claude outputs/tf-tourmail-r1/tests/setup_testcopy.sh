#!/bin/bash
# TEST COPY ONLY — put the test tables back, then the test data: test hotel addresses (example.invalid), the driver's
# 2 nights set by hand, an issued quotation, the admin's mailbox = the local sink's test login
M="mysql --socket=/tmp/claude-0/-home-claude/1f177919-91eb-5b5f-a3ff-b7531d256531/scratchpad/mysql.sock -uroot avenir_test"
$M < /tmp/claude-0/-home-claude/1f177919-91eb-5b5f-a3ff-b7531d256531/scratchpad/tourmail/test/before_full.sql
$M -e "
UPDATE supplier SET email='kandalama-test@example.invalid' WHERE Sup_Acc_No='6-9-11-10581';
UPDATE supplier SET email='queens-test@example.invalid' WHERE Sup_Acc_No='6-9-11-10633';
UPDATE supplier SET email='lighthouse-test@example.invalid' WHERE Sup_Acc_No='6-9-11-10670';
UPDATE supplier SET email='kynrock-test@example.invalid' WHERE Sup_Acc_No='6-9-11-11690';
UPDATE tf_intake_accom SET required=1, nights=2, nights_by='override', detail_json='[{\"day_no\":1,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":2,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":3,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":4,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":5,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":6,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":7,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"},{\"day_no\":8,\"state\":\"charged\",\"hotel\":\"test\",\"why\":\"\"}]' WHERE intake_id=19 AND kind='driver';
INSERT INTO tf_quote (intake_id, version, status, currency, pax, sell_usd, per_person_usd, issued_at, issued_by, created_at, created_by) VALUES (19, 1, 'issued', 'USD', 2, 1000, 500, NOW(), 'tcadmin', NOW(), 'tcadmin');
UPDATE user_acc_tbl SET email='quotes-test@avenirholidays.com', host='mail.avenirholidays.com', email_password='right-test-pass' WHERE id=901 AND user='tcadmin';"
echo "test data ready"
