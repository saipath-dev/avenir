# "Manage Accounts" permission — holders and scope (development, read-only, 12 Oct 2026) — CORRECTED

**Correction (12 Oct 2026, later the same day):** the first version listed `journal_entry/fetch` as changing data. It only reads: it is the account search box on the journal page. There are **5** GET addresses that change data, not 6. The types below were re-derived with a corrected code reading. 4 routes point at code that does not exist (dead).

Read from the development system on 12 Oct 2026. Nothing was changed. **Live not verified.**

## Current holders of Manage Accounts (menu 49, address `accounts`)

| Group | Users (login) | Note |
|---|---|---|
| 1 System Administrator | super admin | Holds every privilege anyway |
| 17 Auditor | nethanjana, audit firm, Nipuna (active); Niroshan (disabled) | Read-only on development only (name rule) |
| 25 Admin | — (no users) | |
| 30 Accounts | TestAccounts (TEST login) | No real staff yet |

## What it grants: 203 addresses

| Type | Count |
|---|---:|
| page | 82 |
| read (GET) | 29 |
| read (POST) | 15 |
| GET that changes data | 5 |
| action (POST, changes data) | 68 |
| dead (handler missing) | 4 |

**GET addresses that change data:**

- `accounts/creditor_invoice/expenses_delete` (CreditorInvoiceController::expenses_delete): read-only Auditor on development: **allowed**
- `accounts/new_creditor_invoice/expenses_delete` (NewCreditorInvoiceController::expenses_delete): read-only Auditor on development: **allowed**
- `accounts/debtor_invoice/cancellation_request` (DebtorInvoiceController::debtor_cancellation_request): read-only Auditor on development: **allowed**
- `accounts/debtor_invoice/cancellation` (DebtorInvoiceController::debtor_cancellation): read-only Auditor on development: **allowed**
- `accounts/utility_bill/delete` (UtilityBillPaymentController::delete): read-only Auditor on development: **refused**

## Every address

| Method | Address | Handler | Type | Own check | Read-only Auditor (dev) |
|---|---|---|---|---|---|
| GET | `accounts/acc_running_records` | AccRunningController::index | page | no | allowed |
| GET | `accounts` | ChartOfAccountsController::index | page | no | allowed |
| GET | `accounts/title_accounts` | ChartOfAccountsController::title_accounts | page | no | allowed |
| GET | `accounts/heading_accounts` | ChartOfAccountsController::heading_accounts | page | no | allowed |
| GET | `accounts/get_accounts_list` | ChartOfAccountsController::getChartOfAccounts | read (GET) | no | allowed |
| GET | `accounts/heading_acc_transactions` | ChartOfAccountsController::heading_acc_transactions | read (GET) | no | allowed |
| GET | `accounts/posting_acc_transactions` | ChartOfAccountsController::posting_acc_transactions | read (GET) | no | allowed |
| POST | `accounts/create/title_account` | ChartOfAccountsController::createTitleAccount | action (POST, changes data) | no | refused |
| POST | `accounts/create/heading_account` | ChartOfAccountsController::createHeadingAccount | action (POST, changes data) | no | refused |
| POST | `accounts/create/posting_account` | ChartOfAccountsController::createPostingAccount | action (POST, changes data) | no | refused |
| POST | `accounts/update/title_account` | ChartOfAccountsController::updateTitleAccount | action (POST, changes data) | no | refused |
| POST | `accounts/update/heading_account` | ChartOfAccountsController::updateHeadingAccount | action (POST, changes data) | no | refused |
| POST | `accounts/update/posting_account` | ChartOfAccountsController::updatePostingAccount | action (POST, changes data) | no | refused |
| POST | `accounts/delete/title_account` | ChartOfAccountsController::deleteTitleAccount | action (POST, changes data) | no | refused |
| POST | `accounts/delete/heading_account` | ChartOfAccountsController::deleteHeadingAccount | action (POST, changes data) | no | refused |
| POST | `accounts/delete/posting_account` | ChartOfAccountsController::deletePostingAccount | action (POST, changes data) | no | refused |
| GET | `accounts/balance_sheet` | BalanceSheetController::index | page | no | allowed |
| GET | `accounts/outstanding_report` | OutstandingController::index | page | no | allowed |
| GET | `accounts/trial_balance` | TrialBalanceController::index | page | no | allowed |
| GET | `accounts/trial_balance/report` | TrialBalanceController::load_report | page | no | allowed |
| GET | `accounts/trial_balance/load_accountant_report` | TrialBalanceController::load_accountant_report | page | no | allowed |
| GET | `accounts/trial_balance/trial_balance_details/(:any)` | TrialBalanceController::trial_balance_details | page | no | allowed |
| GET | `accounts/profit_loss` | ProfitAndLossController::index | page | no | allowed |
| GET | `accounts/creditor_invoice` | CreditorInvoiceController::index | page | no | allowed |
| POST | `accounts/creditor_invoice/complete_payment` | CreditorInvoiceController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/creditor_invoice/report` | CreditorInvoiceController::credit_invoice_report | page | no | allowed |
| GET | `accounts/creditor_invoice/voucher_authentication` | CreditorInvoiceController::voucher_authentication | page | no | allowed |
| GET | `accounts/creditor_invoice/credited_report` | CreditorInvoiceController::credited_invoice_report | page | no | allowed |
| POST | `accounts/creditor_invoice/get_details` | CreditorInvoiceController::get_details | read (POST) | no | refused |
| POST | `accounts/creditor_invoice/get_tour_paymets` | CreditorInvoiceController::get_tour_paymets | read (POST) | no | refused |
| POST | `accounts/creditor_invoice/get_tour_details` | CreditorInvoiceController::get_tour_details | read (POST) | no | refused |
| GET | `accounts/creditor_invoice/view_expences` | CreditorInvoiceController::get_expences | page | no | allowed |
| GET | `accounts/creditor_invoice/expenses_delete` | CreditorInvoiceController::expenses_delete | GET that changes data | no | allowed |
| GET | `accounts/creditor_invoice/expenses_report` | CreditorInvoiceController::expenses_report | page | no | allowed |
| GET | `accounts/creditor_invoice/view_operational_expences` | CreditorInvoiceController::view_operational_expences | page | no | allowed |
| POST | `accounts/creditor_invoice/search_voucher_number` | CreditorInvoiceController::search_voucher_number | read (POST) | no | refused |
| GET | `accounts/new_creditor_invoice` | NewCreditorInvoiceController::index | page | no | allowed |
| POST | `accounts/new_creditor_invoice/complete_payment` | NewCreditorInvoiceController::complete_payment | dead (handler missing) | no | refused |
| GET | `accounts/new_creditor_invoice/report` | NewCreditorInvoiceController::credit_invoice_report | page | no | allowed |
| GET | `accounts/new_creditor_invoice/credited_report` | NewCreditorInvoiceController::credited_invoice_report | page | no | allowed |
| POST | `accounts/new_creditor_invoice/get_details` | NewCreditorInvoiceController::get_details | read (POST) | no | refused |
| POST | `accounts/new_creditor_invoice/get_tour_details` | NewCreditorInvoiceController::get_tour_details | read (POST) | no | refused |
| GET | `accounts/new_creditor_invoice/view_expences` | NewCreditorInvoiceController::get_expences | page | no | allowed |
| GET | `accounts/new_creditor_invoice/expenses_delete` | NewCreditorInvoiceController::expenses_delete | GET that changes data | no | allowed |
| GET | `accounts/new_creditor_invoice/expenses_report` | NewCreditorInvoiceController::expenses_report | page | no | allowed |
| GET | `accounts/new_creditor_invoice/view_operational_expences` | NewCreditorInvoiceController::view_operational_expences | page | no | allowed |
| GET | `accounts/new_creditor_invoice/get_transaction_history` | NewCreditorInvoiceController::get_transaction_history | read (GET) | no | allowed |
| GET | `accounts/creditor_payment` | CreditorPaymentController::index | page | no | allowed |
| POST | `accounts/creditor_payment/complete_payment` | CreditorPaymentController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/creditor_payment/get_transaction_history` | CreditorPaymentController::get_transaction_history | read (GET) | no | allowed |
| GET | `accounts/usd_withdrawal` | UsdWithdrawalController::index | page | no | allowed |
| POST | `accounts/usd_withdrawal/process` | UsdWithdrawalController::process | action (POST, changes data) | no | refused |
| GET | `accounts/usd_withdrawal/print` | UsdWithdrawalController::print | page | no | allowed |
| GET | `accounts/usd_withdrawal/history` | UsdWithdrawalController::history | page | no | allowed |
| GET | `accounts/debtor_payment` | DebtorPaymentController::index | page | no | allowed |
| GET | `accounts/debtor_payment/date_filter` | DebtorPaymentController::date_filter | read (GET) | no | allowed |
| POST | `accounts/debtor_payment/complete_payment` | DebtorPaymentController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/debtor_payment/report` | DebtorPaymentController::debtor_receipt_report | page | no | allowed |
| GET | `accounts/debtor_invoice` | DebtorInvoiceController::index | page | no | allowed |
| POST | `accounts/debtor_invoice/get_advance_payment` | DebtorInvoiceController::get_advance_payment | read (POST) | no | refused |
| GET | `accounts/debtor_invoice/report` | DebtorInvoiceController::debtor_invoice_report | page | no | allowed |
| GET | `accounts/debtor_invoice/cancellation_request` | DebtorInvoiceController::debtor_cancellation_request | GET that changes data | no | allowed |
| GET | `accounts/debtor_invoice/cancellation` | DebtorInvoiceController::debtor_cancellation | GET that changes data | no | allowed |
| POST | `accounts/debtor_invoice/get` | DebtorInvoiceController::get_details | read (POST) | no | refused |
| GET | `accounts/debtor_invoice/get_cancellation_request` | DebtorInvoiceController::cancellation_request_report | page | no | allowed |
| GET | `accounts/debtor_invoice/get_cancellation_request_final` | DebtorInvoiceController::cancellation_request_report_final | page | no | allowed |
| GET | `accounts/debtor_invoice/get_cancellation` | DebtorInvoiceController::cancellation_report | page | no | allowed |
| GET | `accounts/debtor_invoice/arrival_report` | DebtorInvoiceController::arrival_report | page | no | allowed |
| GET | `accounts/debtor_invoice/arrival_report_print` | DebtorInvoiceController::debtor_invoice_report_print | page | no | allowed |
| GET | `accounts/debtor_invoice/print_deptor_invoice_report` | DebtorInvoiceController::debtor_invoice_report_print_n | page | no | allowed |
| GET | `accounts/debtor_invoice/cancellation_fee_print` | DebtorInvoiceController::cancellation_fee_print | page | no | allowed |
| GET | `accounts/service_invoice` | ServiceInvoiceController::index | page | no | allowed |
| GET | `accounts/debtor_invoice_new` | DebtorInvoiceNewController::index | page | no | allowed |
| POST | `accounts/debtor_invoice_new/complete_payment` | DebtorInvoiceNewController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/debtor_invoice_new/tour_details` | DebtorInvoiceNewController::get_tour_details | dead (handler missing) | no | allowed |
| GET | `accounts/debtor_invoice_new/print` | DebtorInvoiceNewController::print | page | no | allowed |
| GET | `accounts/debtor_invoice_new/sent_mail` | DebtorInvoiceNewController::sent | read (GET) | no | allowed |
| GET | `accounts/debtor_invoice_new/print` | DebtorInvoiceNewController::print | page | no | allowed |
| POST | `accounts/debtor_invoice_new/send_receipt` | DebtorInvoiceNewController::send_receipt | action (POST, changes data) | no | refused |
| GET | `accounts/debtor_invoice_new/additional_inv` | DebtorInvoiceNewController::additional_inv | page | no | allowed |
| GET | `accounts/debit_note` | DebitNoteController::index | page | no | allowed |
| GET | `accounts/debit_note/fetch/(:any)` | DebitNoteController::fetch | read (GET) | no | allowed |
| POST | `accounts/debit_note/complete_payment` | DebitNoteController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/credit_note` | CreditNoteController::index | page | no | allowed |
| POST | `accounts/credit_note/complete_payment` | CreditNoteController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/bank_statement` | BankStatementController::index | page | no | allowed |
| GET | `accounts/bank_reconcil` | BankReconsillationController::index | page | no | allowed |
| POST | `accounts/bank_reconcil/get_req_items` | BankReconsillationController::get_req_items | read (POST) | no | refused |
| POST | `accounts/bank_reconcil/get_realised_sum` | BankReconsillationController::get_realised_sum | read (POST) | no | refused |
| POST | `accounts/bank_reconcil/update` | BankReconsillationController::update | action (POST, changes data) | no | refused |
| POST | `accounts/bank_reconcil/post_unrealised_entries` | BankReconsillationController::post_unrealised_entries | action (POST, changes data) | no | refused |
| POST | `accounts/bank_reconcil/get_post_data` | BankReconsillationController::get_post_data | read (POST) | no | refused |
| POST | `accounts/bank_reconcil/repost_unrealised_entries` | BankReconsillationController::repost_unrealised_entries | action (POST, changes data) | no | refused |
| POST | `accounts/bank_reconcil/get_opening_balance` | BankReconsillationController::get_opening_balance | read (POST) | no | refused |
| POST | `accounts/bank_reconcil/repost_single_entry` | BankReconsillationController::repost_single_entry | action (POST, changes data) | no | refused |
| GET | `accounts/bank_reconcil/load_printview` | BankReconsillationController::load_printview | page | no | allowed |
| GET | `accounts/bank_reconcil/load_details_print` | BankReconsillationController::load_details_print | page | no | allowed |
| POST | `accounts/bank_reconcil/reconciliation` | BankReconsillationController::reconciliation | action (POST, changes data) | no | refused |
| GET | `accounts/bank_reconcil/summary` | BankReconsillationController::load_summary | page | no | allowed |
| GET | `accounts/bank_reconcil/details` | BankReconsillationController::load_details | page | no | allowed |
| POST | `accounts/bank_reconcil/finalize_selected` | BankReconsillationController::finalize_selected | action (POST, changes data) | no | refused |
| GET | `accounts/daily_bank_rec` | DailyBankRecController::index | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/account/(:num)` | '\App\Controllers\Accounts\DailyBankRecController::account/$ | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/fx` | DailyBankRecController::fx | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/accounts` | DailyBankRecController::api_accounts | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/day` | DailyBankRecController::api_day | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/statements` | DailyBankRecController::api_statements | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/statement` | DailyBankRecController::api_statement | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/open` | DailyBankRecController::api_open | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/suggest` | DailyBankRecController::api_suggest | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/rates` | DailyBankRecController::api_rates | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/corrections` | DailyBankRecController::api_corrections | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/days` | DailyBankRecController::api_days | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/audit` | DailyBankRecController::api_audit | read (GET) | yes | allowed |
| POST | `accounts/daily_bank_rec/setup` | DailyBankRecController::setup | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/mapping/propose` | DailyBankRecController::mapping_propose | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/mapping/decide` | DailyBankRecController::mapping_decide | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/statement/preview` | DailyBankRecController::statement_preview | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/statement/save` | DailyBankRecController::statement_save | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/statement/accept` | DailyBankRecController::statement_accept | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/statement/void` | DailyBankRecController::statement_void | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/line/edit` | DailyBankRecController::line_edit | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/line/category` | DailyBankRecController::line_category | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/rate/save` | DailyBankRecController::rate_save | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/match/create` | DailyBankRecController::match_create | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/match/undo` | DailyBankRecController::match_undo | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/mark/create` | DailyBankRecController::mark_create | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/mark/clear` | DailyBankRecController::mark_clear | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/transfer/create` | DailyBankRecController::transfer_create | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/transfer/undo` | DailyBankRecController::transfer_undo | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/correction/propose` | DailyBankRecController::correction_propose | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/correction/charges` | DailyBankRecController::correction_charges | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/correction/decide` | DailyBankRecController::correction_decide | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/correction/withdraw` | DailyBankRecController::correction_withdraw | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/correction/posted` | DailyBankRecController::correction_posted | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/opening/propose` | DailyBankRecController::opening_propose | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/opening/decide` | DailyBankRecController::opening_decide | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/day/prepare` | DailyBankRecController::day_prepare | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/day/review` | DailyBankRecController::day_review | action (POST, changes data) | yes | refused |
| GET | `accounts/daily_bank_rec/api/opening_candidates` | DailyBankRecController::api_opening_candidates | read (GET) | yes | allowed |
| GET | `accounts/daily_bank_rec/api/day_record` | DailyBankRecController::api_day_record | read (GET) | yes | allowed |
| POST | `accounts/daily_bank_rec/match/explain` | DailyBankRecController::match_explain | action (POST, changes data) | yes | refused |
| POST | `accounts/daily_bank_rec/correction/reverify` | DailyBankRecController::correction_reverify | action (POST, changes data) | yes | refused |
| GET | `accounts/bank_unrealised_value` | BankUnrealisedValueController::index | page | no | allowed |
| POST | `accounts/bank_unrealised_value/process` | BankUnrealisedValueController::process_unrealised_value_change | action (POST, changes data) | retired (refuses everyone) | refused |
| GET | `accounts/journal_entry` | JournalEntryController::index | page | no | allowed |
| GET | `accounts/journal_entry/fetch/(:any)` | JournalEntryController::fetch | read (GET) | no | allowed |
| POST | `accounts/journal_entry/save` | JournalEntryController::save | action (POST, changes data) | no | refused |
| GET | `accounts/journal_entry/report` | JournalEntryController::report | page | no | allowed |
| GET | `accounts/journal_entry/edit_report` | JournalEntryController::edit_report | page | no | allowed |
| POST | `accounts/journal_entry/reverse` | JournalEntryController::reverse | action (POST, changes data) | no | refused |
| POST | `accounts/journal_entry/edit_date` | JournalEntryController::edit_date | action (POST, changes data) | no | refused |
| GET | `accounts/voucher_payments/(:num)` | VoucherPaymentController::index | page | no | allowed |
| GET | `accounts/voucher_payments/voucher_cancel` | VoucherPaymentController::voucher_cancel | page | no | allowed |
| GET | `accounts/voucher_payments/voucher_reject` | VoucherPaymentController::voucher_reject | page | no | allowed |
| GET | `accounts/voucher_payments/fetch/(:num)/(:any)` | VoucherPaymentController::fetch | read (GET) | no | allowed |
| POST | `accounts/voucher_payments/save` | VoucherPaymentController::save | action (POST, changes data) | no | refused |
| GET | `accounts/voucher_payments/getSupplierDetails` | VoucherPaymentController::getSupplierDetails | read (GET) | no | allowed |
| POST | `accounts/voucher_payments/voucher_delete` | VoucherPaymentController::voucher_delete | dead (handler missing) | no | refused |
| POST | `accounts/voucher_payments/get_pc_accumulated_balance` | VoucherPaymentController::pcaccumulatedbalance | read (POST) | no | refused |
| GET | `accounts/voucher_auth` | VoucherPaymentController::auth | page | no | allowed |
| POST | `accounts/voucher_auth/update` | VoucherPaymentController::update | action (POST, changes data) | no | refused |
| GET | `accounts/voucher_auth/getVoucherDetails` | VoucherPaymentController::getVoucherDetails | read (GET) | no | allowed |
| GET | `accounts/voucher_audit` | VoucherAuditController::index | page | no | allowed |
| GET | `accounts/voucher_audit/auth` | VoucherAuditController::auth | page | no | allowed |
| POST | `accounts/voucher_audit/update` | VoucherAuditController::update | action (POST, changes data) | no | refused |
| POST | `accounts/voucher_audit/update_tour` | VoucherAuditController::update_tour | action (POST, changes data) | no | refused |
| GET | `accounts/voucher_print/(:any)` | VoucherPaymentController::print | page | no | allowed |
| GET | `accounts/account_filter` | AccountFilterController::index | page | no | allowed |
| GET | `accounts/account_filter/fetch/(:any)` | AccountFilterController::fetch | read (GET) | no | allowed |
| GET | `accounts/utility_bill` | UtilityBillPaymentController::index | page | no | allowed |
| GET | `accounts/utility_bill/fetch/(:num)/(:any)` | UtilityBillPaymentController::fetch | read (GET) | no | allowed |
| POST | `accounts/utility_bill/save` | UtilityBillPaymentController::save | action (POST, changes data) | no | refused |
| GET | `accounts/utility_bill/report` | UtilityBillPaymentController::report | page | no | allowed |
| GET | `accounts/utility_bill/delete` | UtilityBillPaymentController::delete | GET that changes data | no | refused |
| POST | `accounts/utility_bill/update_status` | UtilityBillPaymentController::update_status | action (POST, changes data) | no | refused |
| POST | `accounts/utility_bill/pay` | UtilityBillPaymentController::pay | action (POST, changes data) | no | refused |
| GET | `accounts/utility_bill_entertainment` | UtilityBillEntertainmentController::index | page | no | allowed |
| POST | `accounts/utility_bill_entertainment/save` | UtilityBillEntertainmentController::save | action (POST, changes data) | no | refused |
| GET | `accounts/utility_bill_entertainment/report` | UtilityBillEntertainmentController::report | page | no | allowed |
| GET | `accounts/utility_bill_entertainment/delete` | UtilityBillEntertainmentController::delete | dead (handler missing) | no | refused |
| POST | `accounts/utility_bill_entertainment/upload_invoice` | UtilityBillEntertainmentController::upload_invoice | action (POST, changes data) | no | refused |
| POST | `accounts/utility_bill_entertainment/update_expense` | UtilityBillEntertainmentController::update_expense | action (POST, changes data) | no | refused |
| POST | `accounts/utility_bill_entertainment/delete_expense` | UtilityBillEntertainmentController::delete_expense | action (POST, changes data) | no | refused |
| GET | `accounts/cash_book` | CashBookController::index | page | no | allowed |
| GET | `accounts/cash_book/petty_cash` | CashBookController::petty_cash | page | no | allowed |
| GET | `accounts/age_analysis` | AgeAnalysisController::index | page | no | allowed |
| GET | `accounts/age_analysis/creditor` | AgeAnalysisController::creditor_analysis | page | no | allowed |
| GET | `accounts/liability` | LiabilityController::index | page | no | allowed |
| GET | `accounts/advance_payment` | PaymentController::index | page | no | allowed |
| POST | `accounts/advance_payment/get` | PaymentController::get_details | read (POST) | no | refused |
| POST | `accounts/advance_payment/add` | PaymentController::add_amount | action (POST, changes data) | no | refused |
| POST | `accounts/advance_payment/payment_details` | PaymentController::get_payment_details | read (POST) | no | refused |
| GET | `accounts/advance_payment/report` | PaymentController::advance_payment_report | page | no | allowed |
| GET | `accounts/expenses_chart` | ExpensesChartController::index | page | no | allowed |
| GET | `accounts/expenses_chart/report` | ExpensesChartController::report | page | no | allowed |
| GET | `accounts/other_invoice` | OtherInvoiceController::index | page | no | allowed |
| POST | `accounts/other_invoice/add_invoice` | OtherInvoiceController::add_invoice | action (POST, changes data) | no | refused |
| GET | `accounts/other_invoice/report` | OtherInvoiceController::report | page | no | allowed |
| GET | `accounts/other_invoice/print` | OtherInvoiceController::print | page | no | allowed |
| GET | `accounts/service_provider_payment` | ServiceProviderPaymentController::index | page | no | allowed |
| POST | `accounts/service_provider_payment/complete_payment` | ServiceProviderPaymentController::complete_payment | action (POST, changes data) | no | refused |
| GET | `accounts/service_provider_payment/report` | ServiceProviderPaymentController::report | page | no | allowed |
