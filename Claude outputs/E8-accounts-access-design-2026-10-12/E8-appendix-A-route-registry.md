# E8 — Appendix A: route registry draft (203 addresses under `accounts/`)

Development system, read from the code on 12 Oct 2026 (the cloud copy has the identical accounts routes, controllers, models and views; route list digest matched). **Draft for approval — nothing changed.**

**Class:**
- PAGE: a page that renders a screen;
- READ: returns data and changes nothing;
- WRITE: changes data;
- WRITE via GET: changes data on a plain link (to be converted to POST);
- dead: the route points at code that does not exist.

**Today:** the privilege that opens the address today. Every address is also opened by Manage Accounts (49), held by groups 1, 17, 25 and 30.

**Auditor:** whether the address is on the proposed read-only allowlist.


## chart of accounts (16)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `acc_running_records` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `(accounts)` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `title_accounts` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `heading_accounts` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `get_accounts_list` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `heading_acc_transactions` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `posting_acc_transactions` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| POST | `create/title_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `create/heading_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `create/posting_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `update/title_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `update/heading_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `update/posting_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `delete/title_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `delete/heading_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |
| POST | `delete/posting_account` | WRITE | Manage Accounts (49) |  | `act:chart.maintain` | no | POST + token + audit enforced |

## balance_sheet (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `balance_sheet` | PAGE | Balance Sheet (51) |  | view: Balance Sheet (51) | yes |  |

## outstanding_report (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `outstanding_report` | PAGE | Outstanding Report (143) |  | view: Outstanding Report (143) | yes |  |

## trial_balance (4)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `trial_balance` | PAGE | Trial Balance Report (100) |  | view: Trial Balance Report (100) | yes |  |
| GET | `trial_balance/report` | PAGE | Trial Balance Report - Account (141) |  | view: Trial Balance Report - Account (141) | yes |  |
| GET | `trial_balance/load_accountant_report` | PAGE | Trial Balance Report New (142) |  | view: Trial Balance Report New (142) | yes |  |
| GET | `trial_balance/trial_balance_details/(:any)` | PAGE | Trial Balance Report (100) |  | view: Trial Balance Report (100) | yes |  |

## profit_loss (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `profit_loss` | PAGE | Profit and Loss Report (103) |  | view: Profit and Loss Report (103) | yes |  |

## creditor_invoice (13)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `creditor_invoice` | PAGE | Payment Amendment (84) |  | view: Payment Amendment (84) | no |  |
| POST | `creditor_invoice/complete_payment` | WRITE | Payment Amendment (84) |  | `act:creditor_invoice.post` | no | POST + token + audit enforced |
| GET | `creditor_invoice/report` | PAGE | Creditor Invoice Report (91) |  | view: Creditor Invoice Report (91) | yes |  |
| GET | `creditor_invoice/voucher_authentication` | PAGE | Payment Release (96) |  | view: Payment Release (96) | yes |  |
| GET | `creditor_invoice/credited_report` | PAGE | Payment Amendment (84) |  | view: Payment Amendment (84) | yes |  |
| POST | `creditor_invoice/get_details` | READ | Payment Amendment (84) | Tour Management (76); Driver Expense (151) | view: Payment Amendment (84) | yes |  |
| POST | `creditor_invoice/get_tour_paymets` | READ | Payment Amendment (84) |  | view: Payment Amendment (84) | no |  |
| POST | `creditor_invoice/get_tour_details` | READ | Payment Amendment (84) |  | view: Payment Amendment (84) | yes |  |
| GET | `creditor_invoice/view_expences` | PAGE | Payment Amendment (84) |  | view: Payment Amendment (84) | no |  |
| GET | `creditor_invoice/expenses_delete` | WRITE via GET | Payment Amendment (84) |  | `act:records.delete` | no | convert to POST + token + audit; GET answers 405 (audited) |
| GET | `creditor_invoice/expenses_report` | PAGE | Operational PNL (95) |  | view: Operational PNL (95) | yes |  |
| GET | `creditor_invoice/view_operational_expences` | PAGE | Payment Amendment (84) |  | view: Payment Amendment (84) | no |  |
| POST | `creditor_invoice/search_voucher_number` | READ | Payment Amendment (84) |  | view: Payment Amendment (84) | no |  |

## new_creditor_invoice (11)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `new_creditor_invoice` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | no |  |
| POST | `new_creditor_invoice/complete_payment` | dead (handler missing) | Manage Accounts (49) |  | deny — remove route (handler missing) | no | remove route (handler does not exist) |
| GET | `new_creditor_invoice/report` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `new_creditor_invoice/credited_report` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| POST | `new_creditor_invoice/get_details` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| POST | `new_creditor_invoice/get_tour_details` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `new_creditor_invoice/view_expences` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | no |  |
| GET | `new_creditor_invoice/expenses_delete` | WRITE via GET | Manage Accounts (49) |  | `act:records.delete` | no | convert to POST + token + audit; GET answers 405 (audited) |
| GET | `new_creditor_invoice/expenses_report` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |
| GET | `new_creditor_invoice/view_operational_expences` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | no |  |
| GET | `new_creditor_invoice/get_transaction_history` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |

## creditor_payment (3)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `creditor_payment` | PAGE | Creditor Payment (56) |  | view: Creditor Payment (56) | no |  |
| POST | `creditor_payment/complete_payment` | WRITE | Creditor Payment (56) |  | `act:creditor_payment.post` | no | POST + token + audit enforced |
| GET | `creditor_payment/get_transaction_history` | READ | Creditor Payment (56) |  | view: Creditor Payment (56) | yes |  |

## usd_withdrawal (4)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `usd_withdrawal` | PAGE | USD Withdrawal (192) |  | view: USD Withdrawal (192) | no |  |
| POST | `usd_withdrawal/process` | WRITE | USD Withdrawal (192) |  | `act:usd_withdrawal.post` | no | POST + token + audit enforced |
| GET | `usd_withdrawal/print` | PAGE | USD Withdrawal (192) |  | view: USD Withdrawal (192) | yes |  |
| GET | `usd_withdrawal/history` | PAGE | USD Withdrawal History (193) |  | view: USD Withdrawal History (193) | yes |  |

## debtor_payment (4)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `debtor_payment` | PAGE | Debtor Payment (78) |  | view: Debtor Payment (78) | no |  |
| GET | `debtor_payment/date_filter` | READ | Debtor Payment (78) |  | view: Debtor Payment (78) | no |  |
| POST | `debtor_payment/complete_payment` | WRITE | Debtor Payment (78) |  | `act:debtor_payment.post` | no | POST + token + audit enforced |
| GET | `debtor_payment/report` | PAGE | Debtor Receipt Report (145) |  | view: Debtor Receipt Report (145) | yes |  |

## debtor_invoice (13)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `debtor_invoice` | PAGE | Debtor Invoice (86) |  | view: Debtor Invoice (86) | no |  |
| POST | `debtor_invoice/get_advance_payment` | READ | Debtor Invoice (86) |  | view: Debtor Invoice (86) | no |  |
| GET | `debtor_invoice/report` | PAGE | Debtor Invoice Report (92) |  | view: Debtor Invoice Report (92) | yes |  |
| GET | `debtor_invoice/cancellation_request` | WRITE via GET | Debtor Invoice (86) | Invoice Cancellation (187); Tour Inquiry / New Inquiry (79, 203) | `act:amendment.request` | no | convert to POST + token + audit; GET answers 405 (audited) |
| GET | `debtor_invoice/cancellation` | WRITE via GET | Debtor Invoice (86) |  | `act:amendment.decide` | no | convert to POST + token + audit; GET answers 405 (audited) |
| POST | `debtor_invoice/get` | READ | Debtor Invoice (86) |  | view: Debtor Invoice (86) | no |  |
| GET | `debtor_invoice/get_cancellation_request` | PAGE | Tour Amendment Approval (127) |  | view: Tour Amendment Approval (127) | yes |  |
| GET | `debtor_invoice/get_cancellation_request_final` | PAGE | Tour Amendment Finalize (147) |  | view: Tour Amendment Finalize (147) | yes |  |
| GET | `debtor_invoice/get_cancellation` | PAGE | Tour Amendment Report (128) |  | view: Tour Amendment Report (128) | yes |  |
| GET | `debtor_invoice/arrival_report` | PAGE | Arrival Report (138) |  | view: Arrival Report (138) | yes |  |
| GET | `debtor_invoice/arrival_report_print` | PAGE | Debtor Invoice (86) |  | view: Debtor Invoice (86) | yes |  |
| GET | `debtor_invoice/print_deptor_invoice_report` | PAGE | Debtor Invoice (86) |  | view: Debtor Invoice (86) | yes |  |
| GET | `debtor_invoice/cancellation_fee_print` | PAGE | Debtor Invoice (86) |  | view: Debtor Invoice (86) | yes |  |

## service_invoice (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `service_invoice` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | no |  |

## debtor_invoice_new (8)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `debtor_invoice_new` | PAGE | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | view: Manage Accounts (49) | no |  |
| POST | `debtor_invoice_new/complete_payment` | WRITE | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | `act:debtor_invoice.post` | no | POST + token + audit enforced |
| GET | `debtor_invoice_new/tour_details` | dead (handler missing) | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | deny — remove route (handler missing) | no | remove route (handler does not exist) |
| GET | `debtor_invoice_new/print` | PAGE | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | view: Manage Accounts (49) | yes |  |
| GET | `debtor_invoice_new/sent_mail` | READ | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | view: Manage Accounts (49) | no |  |
| GET | `debtor_invoice_new/print` | PAGE | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | view: Manage Accounts (49) | yes |  |
| POST | `debtor_invoice_new/send_receipt` | WRITE | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117); Debtor Invoice (86) | `act:debtor_invoice.post` | no | POST + token + audit enforced |
| GET | `debtor_invoice_new/additional_inv` | PAGE | Manage Accounts (49) | Tour Dashboard (110); Payment Forecast Report (132); Working Dashboard (117) | view: Manage Accounts (49) | no |  |

## debit_note (3)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `debit_note` | PAGE | Debit Note (115) |  | view: Debit Note (115) | no |  |
| GET | `debit_note/fetch/(:any)` | READ | Debit Note (115) |  | view: Debit Note (115) | no |  |
| POST | `debit_note/complete_payment` | WRITE | Debit Note (115) |  | `act:debit_note.post` | no | POST + token + audit enforced |

## credit_note (2)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `credit_note` | PAGE | Credit Note (139) |  | view: Credit Note (139) | no |  |
| POST | `credit_note/complete_payment` | WRITE | Credit Note (139) |  | `act:credit_note.post` | no | POST + token + audit enforced |

## bank_statement (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `bank_statement` | PAGE | Bank Statement (109) |  | view: Bank Statement (109) | yes |  |

## bank_reconcil (15)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `bank_reconcil` | PAGE | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | no |  |
| POST | `bank_reconcil/get_req_items` | READ | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | no |  |
| POST | `bank_reconcil/get_realised_sum` | READ | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | no |  |
| POST | `bank_reconcil/update` | WRITE | Bank Reconcillation (112) |  | `act:old_bankrec.change` | no | POST + token + audit enforced |
| POST | `bank_reconcil/post_unrealised_entries` | WRITE | Bank Reconcillation (112) |  | `act:old_bankrec.change` | no | POST + token + audit enforced |
| POST | `bank_reconcil/get_post_data` | READ | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | no |  |
| POST | `bank_reconcil/repost_unrealised_entries` | WRITE | Bank Reconcillation (112) |  | `act:old_bankrec.change` | no | POST + token + audit enforced |
| POST | `bank_reconcil/get_opening_balance` | READ | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | no |  |
| POST | `bank_reconcil/repost_single_entry` | WRITE | Bank Reconcillation (112) |  | `act:old_bankrec.change` | no | POST + token + audit enforced |
| GET | `bank_reconcil/load_printview` | PAGE | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | yes |  |
| GET | `bank_reconcil/load_details_print` | PAGE | Bank Reconcillation (112) |  | view: Bank Reconcillation (112) | yes |  |
| POST | `bank_reconcil/reconciliation` | WRITE | Bank Reconcillation (112) |  | `act:old_bankrec.change` | no | POST + token + audit enforced |
| GET | `bank_reconcil/summary` | PAGE | Bank Reconcillation Summary (123) |  | view: Bank Reconcillation Summary (123) | yes |  |
| GET | `bank_reconcil/details` | PAGE | Bank Reconcillation Details (122) |  | view: Bank Reconcillation Details (122) | yes |  |
| POST | `bank_reconcil/finalize_selected` | WRITE | Bank Reconcillation (112) |  | `act:old_bankrec.change` | no | POST + token + audit enforced |

## daily_bank_rec (42)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `daily_bank_rec` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/account/(:num)` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/fx` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/accounts` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/day` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/statements` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/statement` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/open` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/suggest` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/rates` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/corrections` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/days` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/audit` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| POST | `daily_bank_rec/setup` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/mapping/propose` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/mapping/decide` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/statement/preview` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/statement/save` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/statement/accept` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/statement/void` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/line/edit` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/line/category` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/rate/save` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/match/create` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/match/undo` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/mark/create` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/mark/clear` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/transfer/create` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/transfer/undo` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/correction/propose` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/correction/charges` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/correction/decide` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/correction/withdraw` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/correction/posted` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/opening/propose` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/opening/decide` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/day/prepare` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/day/review` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| GET | `daily_bank_rec/api/opening_candidates` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| GET | `daily_bank_rec/api/day_record` | READ | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation view (unchanged) | yes |  |
| POST | `daily_bank_rec/match/explain` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |
| POST | `daily_bank_rec/correction/reverify` | WRITE | Daily Bank Reconciliation (253) |  | Daily Bank Reconciliation privileges (unchanged) | no | POST + token + audit enforced |

## bank_unrealised_value (2)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `bank_unrealised_value` | PAGE | Bank Req Data Transfer (184) |  | view: Bank Req Data Transfer (184) — notice only | no |  |
| POST | `bank_unrealised_value/process` | WRITE | Bank Req Data Transfer (184) |  | deny — retired tool | no | POST + token + audit enforced |

## journal_entry (7)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `journal_entry` | PAGE | Journal Entry (81) |  | view: Journal Entry (81) | no |  |
| GET | `journal_entry/fetch/(:any)` | READ | Journal Entry (81) |  | view: Journal Entry (81) | no |  |
| POST | `journal_entry/save` | WRITE | Journal Entry (81) |  | `act:journal.post` | no | POST + token + audit enforced |
| GET | `journal_entry/report` | PAGE | Journal Entry Report (102) |  | view: Journal Entry Report (102) | yes |  |
| GET | `journal_entry/edit_report` | PAGE | Edit Journal Entry (120) |  | view: Edit Journal Entry (120) | yes |  |
| POST | `journal_entry/reverse` | WRITE | Journal Entry (81) |  | `act:journal.correct` | no | POST + token + audit enforced |
| POST | `journal_entry/edit_date` | WRITE | Journal Entry (81) |  | `act:journal.correct` | no | POST + token + audit enforced |

## voucher_payments (8)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `voucher_payments/(:num)` | PAGE | Voucher Payments (82) |  | view: Voucher Payments (82) | no |  |
| GET | `voucher_payments/voucher_cancel` | PAGE | Pending Voucher Report (119) |  | view: Pending Voucher Report (119) | yes |  |
| GET | `voucher_payments/voucher_reject` | PAGE | Voucher Payments (82) |  | view: Voucher Payments (82) | no |  |
| GET | `voucher_payments/fetch/(:num)/(:any)` | READ | Voucher Payments (82) |  | view: Voucher Payments (82) | no |  |
| POST | `voucher_payments/save` | WRITE | Voucher Payments (82) |  | `act:vouchers.create` | no | POST + token + audit enforced |
| GET | `voucher_payments/getSupplierDetails` | READ | Voucher Payments (82) |  | view: Voucher Payments (82) | yes |  |
| POST | `voucher_payments/voucher_delete` | dead (handler missing) | Voucher Payments (82) |  | deny — remove route (handler missing) | no | remove route (handler does not exist) |
| POST | `voucher_payments/get_pc_accumulated_balance` | READ | Voucher Payments (82) | voucher_auth (menu url accounts/voucher_auth); Bank Payment Check Report (189) | view: Voucher Payments (82) | no |  |

## voucher_auth (3)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `voucher_auth` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | no |  |
| POST | `voucher_auth/update` | WRITE | Manage Accounts (49) |  | `act:vouchers.finalise` | no | POST + token + audit enforced |
| GET | `voucher_auth/getVoucherDetails` | READ | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |

## voucher_audit (4)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `voucher_audit` | PAGE | Voucher Audit (124) |  | view: Voucher Audit (124) | yes |  |
| GET | `voucher_audit/auth` | PAGE | Voucher Audit (124) |  | view: Voucher Audit (124) | yes |  |
| POST | `voucher_audit/update` | WRITE | Voucher Audit (124) |  | `act:vouchers.audit_mark` | no | POST + token + audit enforced |
| POST | `voucher_audit/update_tour` | WRITE | Voucher Audit (124) |  | `act:vouchers.audit_mark` | no | POST + token + audit enforced |

## voucher_print (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `voucher_print/(:any)` | PAGE | Manage Accounts (49) |  | view: Manage Accounts (49) | yes |  |

## account_filter (2)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `account_filter` | PAGE | Ledger Accounts (108) |  | view: Ledger Accounts (108) | yes |  |
| GET | `account_filter/fetch/(:any)` | READ | Ledger Accounts (108) |  | view: Ledger Accounts (108) | yes |  |

## utility_bill (7)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `utility_bill` | PAGE | Utility Bill Payment (113) |  | view: Utility Bill Payment (113) | no |  |
| GET | `utility_bill/fetch/(:num)/(:any)` | READ | Utility Bill Payment (113) |  | view: Utility Bill Payment (113) | no |  |
| POST | `utility_bill/save` | WRITE | Utility Bill Payment (113) |  | `act:utility_bill.post` | no | POST + token + audit enforced |
| GET | `utility_bill/report` | PAGE | Utility Bills Report (114) |  | view: Utility Bills Report (114) | yes |  |
| GET | `utility_bill/delete` | WRITE via GET | Utility Bill Payment (113) |  | `act:records.delete` | no | convert to POST + token + audit; GET answers 405 (audited) |
| POST | `utility_bill/update_status` | WRITE | Utility Bill Payment (113) | Payment Approval V1 (185, no holders) | `act:utility_bill.post` | no | POST + token + audit enforced |
| POST | `utility_bill/pay` | WRITE | Utility Bill Payment (113) |  | `act:utility_bill.post` | no | POST + token + audit enforced |

## utility_bill_entertainment (7)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `utility_bill_entertainment` | PAGE | Utility Bill Entertainment (181) |  | view: Utility Bill Entertainment (181) | no |  |
| POST | `utility_bill_entertainment/save` | WRITE | Utility Bill Entertainment (181) |  | `act:entertainment.post` | no | POST + token + audit enforced |
| GET | `utility_bill_entertainment/report` | PAGE | Utility Bill Entertainment Report (182) |  | view: Utility Bill Entertainment Report (182) | yes |  |
| GET | `utility_bill_entertainment/delete` | dead (handler missing) | Utility Bill Entertainment (181) |  | deny — remove route (handler missing) | no | remove route (handler does not exist) |
| POST | `utility_bill_entertainment/upload_invoice` | WRITE | Utility Bill Entertainment (181) |  | `act:entertainment.post` | no | POST + token + audit enforced |
| POST | `utility_bill_entertainment/update_expense` | WRITE | Utility Bill Entertainment (181) |  | `act:entertainment.post` | no | POST + token + audit enforced |
| POST | `utility_bill_entertainment/delete_expense` | WRITE | Utility Bill Entertainment (181) |  | `act:records.delete` | no | POST + token + audit enforced |

## cash_book (2)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `cash_book` | PAGE | Cash In Hand (101) |  | view: Cash In Hand (101) | yes |  |
| GET | `cash_book/petty_cash` | PAGE | Cash In Hand (101) |  | view: Cash In Hand (101) | yes |  |

## age_analysis (2)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `age_analysis` | PAGE | Age Analysis Report (104) |  | view: Age Analysis Report (104) | yes |  |
| GET | `age_analysis/creditor` | PAGE | Creditor Age Analysis (105) |  | view: Creditor Age Analysis (105) | yes |  |

## liability (1)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `liability` | PAGE | Liability Report (88) |  | view: Liability Report (88) | yes |  |

## advance_payment (5)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `advance_payment` | PAGE | Advance Payments (90) |  | view: Advance Payments (90) | no |  |
| POST | `advance_payment/get` | READ | Advance Payments (90) |  | view: Advance Payments (90) | no |  |
| POST | `advance_payment/add` | WRITE | Advance Payments (90) |  | `act:advance_payment.post` | no | POST + token + audit enforced |
| POST | `advance_payment/payment_details` | READ | Advance Payments (90) |  | view: Advance Payments (90) | yes |  |
| GET | `advance_payment/report` | PAGE | Advance Payments Report (89) |  | view: Advance Payments Report (89) | yes |  |

## expenses_chart (2)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `expenses_chart` | PAGE | Expenses Chart (94) |  | view: Expenses Chart (94) | yes |  |
| GET | `expenses_chart/report` | PAGE | Expenses Report (180) |  | view: Expenses Report (180) | yes |  |

## other_invoice (4)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `other_invoice` | PAGE | Service Provider Invoice (172) |  | view: Service Provider Invoice (172) | no |  |
| POST | `other_invoice/add_invoice` | WRITE | Service Provider Invoice (172) |  | `act:other_invoice.post` | no | POST + token + audit enforced |
| GET | `other_invoice/report` | PAGE | Service Provider Invoice Report (174) |  | view: Service Provider Invoice Report (174) | yes |  |
| GET | `other_invoice/print` | PAGE | Service Provider Invoice (172) |  | view: Service Provider Invoice (172) | yes |  |

## service_provider_payment (3)

| Method | Address (after `accounts/`) | Class | Today | Also via (other modules) | Proposed requirement | Auditor | Change |
|---|---|---|---|---|---|---|---|
| GET | `service_provider_payment` | PAGE | Service Provider Payment (194) |  | view: Service Provider Payment (194) | no |  |
| POST | `service_provider_payment/complete_payment` | WRITE | Service Provider Payment (194) |  | `act:service_provider_payment.post` | no | POST + token + audit enforced |
| GET | `service_provider_payment/report` | PAGE | Service Provider Payment Receipt (195) |  | view: Service Provider Payment Receipt (195) | yes |  |
