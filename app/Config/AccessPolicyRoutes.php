<?php

namespace Config;

/**
 * tf-e8-access-r1 — the Accounts route registry: every address under accounts/ exactly once (method + full path; placeholders
 * only where Routes.php has them). Read by App\Filters\AccessPolicyFilter. Generated from the reviewed registry; edit with care:
 * the registry test (T1) fails if a route in Routes.php is missing here or an entry here has no route.
 *
 *   c      p = page, r = read, w = write (POST only), t = tombstone of a link converted to POST (always 405)
 *   priv   any one of these menu addresses (or act: action privileges) lets a group through; System Administrator bypasses
 *   ro     on the Auditor read-only allowlist
 *   csrf   filter = the filter checks the security token and audits; controller = the controller checks it and audits itself
 *   reason a reason of at least 5 characters is required (deletes and corrections)
 */
final class AccessPolicyRoutes
{
    public const ROUTES = [
        // ---- (root)
        'GET accounts' => ['c' => 'p', 'priv' => ['accounts', 'act:chart.maintain'], 'ro' => true],
        // ---- acc_running_records
        'GET accounts/acc_running_records' => ['c' => 'p', 'priv' => ['accounts'], 'ro' => true],
        // ---- account_filter
        'GET accounts/account_filter' => ['c' => 'p', 'priv' => ['accounts/account_filter'], 'ro' => true],
        'GET accounts/account_filter/fetch/(:any)' => ['c' => 'r', 'priv' => ['accounts/account_filter'], 'ro' => true],
        // ---- advance_payment
        'GET accounts/advance_payment' => ['c' => 'p', 'priv' => ['accounts/advance_payment']],
        'POST accounts/advance_payment/add' => ['c' => 'w', 'priv' => ['act:advance_payment.post'], 'csrf' => 'filter'],
        'POST accounts/advance_payment/get' => ['c' => 'r', 'priv' => ['accounts/advance_payment']],
        'POST accounts/advance_payment/payment_details' => ['c' => 'r', 'priv' => ['accounts/advance_payment'], 'ro' => true],
        'GET accounts/advance_payment/report' => ['c' => 'p', 'priv' => ['accounts/advance_payment/report', 'accounts/advance_payment'], 'ro' => true],
        // ---- age_analysis
        'GET accounts/age_analysis' => ['c' => 'p', 'priv' => ['accounts/age_analysis'], 'ro' => true],
        'GET accounts/age_analysis/creditor' => ['c' => 'p', 'priv' => ['accounts/age_analysis/creditor', 'accounts/age_analysis'], 'ro' => true],
        // ---- balance_sheet
        'GET accounts/balance_sheet' => ['c' => 'p', 'priv' => ['accounts/balance_sheet'], 'ro' => true],
        // ---- bank_reconcil
        'GET accounts/bank_reconcil' => ['c' => 'p', 'priv' => ['accounts/bank_reconcil']],
        'GET accounts/bank_reconcil/details' => ['c' => 'p', 'priv' => ['accounts/bank_reconcil/details', 'accounts/bank_reconcil'], 'ro' => true],
        'POST accounts/bank_reconcil/finalize_selected' => ['c' => 'w', 'priv' => ['act:old_bankrec.change'], 'csrf' => 'filter'],
        'POST accounts/bank_reconcil/get_opening_balance' => ['c' => 'r', 'priv' => ['accounts/bank_reconcil']],
        'POST accounts/bank_reconcil/get_post_data' => ['c' => 'r', 'priv' => ['accounts/bank_reconcil']],
        'POST accounts/bank_reconcil/get_realised_sum' => ['c' => 'r', 'priv' => ['accounts/bank_reconcil']],
        'POST accounts/bank_reconcil/get_req_items' => ['c' => 'r', 'priv' => ['accounts/bank_reconcil']],
        'GET accounts/bank_reconcil/load_details_print' => ['c' => 'p', 'priv' => ['accounts/bank_reconcil'], 'ro' => true],
        'GET accounts/bank_reconcil/load_printview' => ['c' => 'p', 'priv' => ['accounts/bank_reconcil'], 'ro' => true],
        'POST accounts/bank_reconcil/post_unrealised_entries' => ['c' => 'w', 'priv' => ['act:old_bankrec.change'], 'csrf' => 'filter'],
        'POST accounts/bank_reconcil/reconciliation' => ['c' => 'w', 'priv' => ['act:old_bankrec.change'], 'csrf' => 'filter'],
        'POST accounts/bank_reconcil/repost_single_entry' => ['c' => 'w', 'priv' => ['act:old_bankrec.change'], 'csrf' => 'filter'],
        'POST accounts/bank_reconcil/repost_unrealised_entries' => ['c' => 'w', 'priv' => ['act:old_bankrec.change'], 'csrf' => 'filter'],
        'GET accounts/bank_reconcil/summary' => ['c' => 'p', 'priv' => ['accounts/bank_reconcil/summary', 'accounts/bank_reconcil'], 'ro' => true],
        'POST accounts/bank_reconcil/update' => ['c' => 'w', 'priv' => ['act:old_bankrec.change'], 'csrf' => 'filter'],
        // ---- bank_statement
        'GET accounts/bank_statement' => ['c' => 'p', 'priv' => ['accounts/bank_statement'], 'ro' => true],
        // ---- bank_unrealised_value
        'GET accounts/bank_unrealised_value' => ['c' => 'p', 'priv' => ['accounts/bank_unrealised_value']],
        'POST accounts/bank_unrealised_value/process' => ['c' => 'w', 'priv' => ['accounts/bank_unrealised_value'], 'csrf' => 'controller'],
        // ---- cash_book
        'GET accounts/cash_book' => ['c' => 'p', 'priv' => ['accounts/cash_book'], 'ro' => true],
        'GET accounts/cash_book/petty_cash' => ['c' => 'p', 'priv' => ['accounts/cash_book'], 'ro' => true],
        // ---- create
        'POST accounts/create/heading_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter'],
        'POST accounts/create/posting_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter'],
        'POST accounts/create/title_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter'],
        // ---- credit_note
        'GET accounts/credit_note' => ['c' => 'p', 'priv' => ['accounts/credit_note']],
        'POST accounts/credit_note/complete_payment' => ['c' => 'w', 'priv' => ['act:credit_note.post'], 'csrf' => 'filter'],
        // ---- creditor_invoice
        'GET accounts/creditor_invoice' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice', 'act:records.delete']],
        'POST accounts/creditor_invoice/complete_payment' => ['c' => 'w', 'priv' => ['act:creditor_invoice.post'], 'csrf' => 'filter'],
        'GET accounts/creditor_invoice/credited_report' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice'], 'ro' => true],
        'GET accounts/creditor_invoice/expenses_delete' => ['c' => 't'],
        'POST accounts/creditor_invoice/expenses_delete' => ['c' => 'w', 'priv' => ['act:records.delete'], 'csrf' => 'filter', 'reason' => true],
        'GET accounts/creditor_invoice/expenses_report' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice/expenses_report', 'accounts/creditor_invoice'], 'ro' => true],
        'POST accounts/creditor_invoice/get_details' => ['c' => 'r', 'priv' => ['accounts/creditor_invoice', 'tour/booking_confirm', 'dashboard2/transport/driver_expense', 'act:records.delete'], 'ro' => true],
        'POST accounts/creditor_invoice/get_tour_details' => ['c' => 'r', 'priv' => ['accounts/creditor_invoice', 'act:records.delete'], 'ro' => true],
        'POST accounts/creditor_invoice/get_tour_paymets' => ['c' => 'r', 'priv' => ['accounts/creditor_invoice', 'act:records.delete']],
        'GET accounts/creditor_invoice/report' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice/report', 'accounts/creditor_invoice'], 'ro' => true],
        'POST accounts/creditor_invoice/search_voucher_number' => ['c' => 'r', 'priv' => ['accounts/creditor_invoice']],
        'GET accounts/creditor_invoice/view_expences' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice']],
        'GET accounts/creditor_invoice/view_operational_expences' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice']],
        'GET accounts/creditor_invoice/voucher_authentication' => ['c' => 'p', 'priv' => ['accounts/creditor_invoice/voucher_authentication', 'accounts/creditor_invoice'], 'ro' => true],
        // ---- creditor_payment
        'GET accounts/creditor_payment' => ['c' => 'p', 'priv' => ['accounts/creditor_payment']],
        'POST accounts/creditor_payment/complete_payment' => ['c' => 'w', 'priv' => ['act:creditor_payment.post'], 'csrf' => 'filter'],
        'GET accounts/creditor_payment/get_transaction_history' => ['c' => 'r', 'priv' => ['accounts/creditor_payment'], 'ro' => true],
        // ---- daily_bank_rec
        'GET accounts/daily_bank_rec' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/account/(:num)' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/accounts' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/audit' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/corrections' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/day' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/day_record' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/days' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/open' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/opening_candidates' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/rates' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/statement' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/statements' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'GET accounts/daily_bank_rec/api/suggest' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'POST accounts/daily_bank_rec/correction/charges' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/correction/decide' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/correction/posted' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/correction/propose' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/correction/reverify' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/correction/withdraw' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/day/prepare' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/day/review' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'GET accounts/daily_bank_rec/fx' => ['c' => 'r', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'ro' => true],
        'POST accounts/daily_bank_rec/line/category' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/line/edit' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/mapping/decide' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/mapping/propose' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/mark/clear' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/mark/create' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/match/create' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/match/explain' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/match/undo' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/opening/decide' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/opening/propose' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/rate/save' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/setup' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/statement/accept' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/statement/preview' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/statement/save' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/statement/void' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/transfer/create' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        'POST accounts/daily_bank_rec/transfer/undo' => ['c' => 'w', 'priv' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'], 'csrf' => 'controller'],
        // ---- debit_note
        'GET accounts/debit_note' => ['c' => 'p', 'priv' => ['accounts/debit_note']],
        'POST accounts/debit_note/complete_payment' => ['c' => 'w', 'priv' => ['act:debit_note.post'], 'csrf' => 'filter'],
        'GET accounts/debit_note/fetch/(:any)' => ['c' => 'r', 'priv' => ['accounts/debit_note']],
        // ---- debtor_invoice
        'GET accounts/debtor_invoice' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice']],
        'GET accounts/debtor_invoice/arrival_report' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice/arrival_report', 'accounts/debtor_invoice'], 'ro' => true],
        'GET accounts/debtor_invoice/arrival_report_print' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice'], 'ro' => true],
        'GET accounts/debtor_invoice/cancellation' => ['c' => 't'],
        'POST accounts/debtor_invoice/cancellation' => ['c' => 'w', 'priv' => ['act:amendment.decide'], 'csrf' => 'filter'],
        'GET accounts/debtor_invoice/cancellation_fee_print' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice'], 'ro' => true],
        'GET accounts/debtor_invoice/cancellation_request' => ['c' => 't'],
        'POST accounts/debtor_invoice/cancellation_request' => ['c' => 'w', 'priv' => ['act:amendment.request'], 'csrf' => 'filter'],
        'POST accounts/debtor_invoice/get' => ['c' => 'r', 'priv' => ['accounts/debtor_invoice']],
        'POST accounts/debtor_invoice/get_advance_payment' => ['c' => 'r', 'priv' => ['accounts/debtor_invoice']],
        'GET accounts/debtor_invoice/get_cancellation' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice/get_cancellation', 'accounts/debtor_invoice'], 'ro' => true],
        'GET accounts/debtor_invoice/get_cancellation_request' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice/get_cancellation_request', 'accounts/debtor_invoice', 'act:amendment.decide'], 'ro' => true],
        'GET accounts/debtor_invoice/get_cancellation_request_final' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice/get_cancellation_request_final', 'accounts/debtor_invoice', 'act:amendment.decide'], 'ro' => true],
        'GET accounts/debtor_invoice/print_deptor_invoice_report' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice'], 'ro' => true],
        'GET accounts/debtor_invoice/report' => ['c' => 'p', 'priv' => ['accounts/debtor_invoice/report', 'accounts/debtor_invoice'], 'ro' => true],
        // ---- debtor_invoice_new
        'GET accounts/debtor_invoice_new' => ['c' => 'p', 'priv' => ['accounts', 'tour/tour_dashboard', 'tour/tour_accounts', 'tour/tour_details']],
        'GET accounts/debtor_invoice_new/additional_inv' => ['c' => 'p', 'priv' => ['accounts', 'tour/tour_dashboard', 'tour/tour_accounts', 'tour/tour_details']],
        'POST accounts/debtor_invoice_new/complete_payment' => ['c' => 'w', 'priv' => ['act:debtor_invoice.post'], 'csrf' => 'filter'],
        'GET accounts/debtor_invoice_new/print' => ['c' => 'p', 'priv' => ['accounts', 'tour/tour_dashboard', 'tour/tour_accounts', 'tour/tour_details'], 'ro' => true],
        'POST accounts/debtor_invoice_new/send_receipt' => ['c' => 'w', 'priv' => ['act:debtor_invoice.post'], 'csrf' => 'filter'],
        'GET accounts/debtor_invoice_new/sent_mail' => ['c' => 'r', 'priv' => ['accounts', 'tour/tour_dashboard', 'tour/tour_accounts', 'tour/tour_details']],
        // ---- debtor_payment
        'GET accounts/debtor_payment' => ['c' => 'p', 'priv' => ['accounts/debtor_payment']],
        'POST accounts/debtor_payment/complete_payment' => ['c' => 'w', 'priv' => ['act:debtor_payment.post'], 'csrf' => 'filter'],
        'GET accounts/debtor_payment/date_filter' => ['c' => 'r', 'priv' => ['accounts/debtor_payment']],
        'GET accounts/debtor_payment/report' => ['c' => 'p', 'priv' => ['accounts/debtor_payment/report', 'accounts/debtor_payment'], 'ro' => true],
        // ---- delete
        'POST accounts/delete/heading_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter', 'reason' => true],
        'POST accounts/delete/posting_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter', 'reason' => true],
        'POST accounts/delete/title_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter', 'reason' => true],
        // ---- expenses_chart
        'GET accounts/expenses_chart' => ['c' => 'p', 'priv' => ['accounts/expenses_chart'], 'ro' => true],
        'GET accounts/expenses_chart/report' => ['c' => 'p', 'priv' => ['accounts/expenses_chart/report', 'accounts/expenses_chart'], 'ro' => true],
        // ---- get_accounts_list
        'GET accounts/get_accounts_list' => ['c' => 'r', 'priv' => ['accounts'], 'ro' => true],
        // ---- heading_acc_transactions
        'GET accounts/heading_acc_transactions' => ['c' => 'r', 'priv' => ['accounts'], 'ro' => true],
        // ---- heading_accounts
        'GET accounts/heading_accounts' => ['c' => 'p', 'priv' => ['accounts', 'act:chart.maintain'], 'ro' => true],
        // ---- journal_entry
        'GET accounts/journal_entry' => ['c' => 'p', 'priv' => ['accounts/journal_entry']],
        'POST accounts/journal_entry/edit_date' => ['c' => 'w', 'priv' => ['act:journal.correct'], 'csrf' => 'filter', 'reason' => true],
        'GET accounts/journal_entry/edit_report' => ['c' => 'p', 'priv' => ['accounts/journal_entry/edit_report', 'accounts/journal_entry', 'act:journal.correct'], 'ro' => true],
        'GET accounts/journal_entry/fetch/(:any)' => ['c' => 'r', 'priv' => ['accounts/journal_entry']],
        'GET accounts/journal_entry/report' => ['c' => 'p', 'priv' => ['accounts/journal_entry/report', 'accounts/journal_entry', 'act:journal.correct'], 'ro' => true],
        'POST accounts/journal_entry/reverse' => ['c' => 'w', 'priv' => ['act:journal.correct'], 'csrf' => 'filter', 'reason' => true],
        'POST accounts/journal_entry/save' => ['c' => 'w', 'priv' => ['act:journal.post'], 'csrf' => 'filter'],
        // ---- liability
        'GET accounts/liability' => ['c' => 'p', 'priv' => ['accounts/liability'], 'ro' => true],
        // ---- new_creditor_invoice
        'GET accounts/new_creditor_invoice' => ['c' => 'p', 'priv' => ['accounts', 'act:records.delete']],
        'GET accounts/new_creditor_invoice/credited_report' => ['c' => 'p', 'priv' => ['accounts'], 'ro' => true],
        'GET accounts/new_creditor_invoice/expenses_delete' => ['c' => 't'],
        'POST accounts/new_creditor_invoice/expenses_delete' => ['c' => 'w', 'priv' => ['act:records.delete'], 'csrf' => 'filter', 'reason' => true],
        'GET accounts/new_creditor_invoice/expenses_report' => ['c' => 'p', 'priv' => ['accounts'], 'ro' => true],
        'POST accounts/new_creditor_invoice/get_details' => ['c' => 'r', 'priv' => ['accounts', 'act:records.delete'], 'ro' => true],
        'POST accounts/new_creditor_invoice/get_tour_details' => ['c' => 'r', 'priv' => ['accounts', 'act:records.delete'], 'ro' => true],
        'GET accounts/new_creditor_invoice/get_transaction_history' => ['c' => 'r', 'priv' => ['accounts', 'act:records.delete'], 'ro' => true],
        'GET accounts/new_creditor_invoice/report' => ['c' => 'p', 'priv' => ['accounts'], 'ro' => true],
        'GET accounts/new_creditor_invoice/view_expences' => ['c' => 'p', 'priv' => ['accounts']],
        'GET accounts/new_creditor_invoice/view_operational_expences' => ['c' => 'p', 'priv' => ['accounts']],
        // ---- other_invoice
        'GET accounts/other_invoice' => ['c' => 'p', 'priv' => ['accounts/other_invoice']],
        'POST accounts/other_invoice/add_invoice' => ['c' => 'w', 'priv' => ['act:other_invoice.post'], 'csrf' => 'filter'],
        'GET accounts/other_invoice/print' => ['c' => 'p', 'priv' => ['accounts/other_invoice'], 'ro' => true],
        'GET accounts/other_invoice/report' => ['c' => 'p', 'priv' => ['accounts/other_invoice/report', 'accounts/other_invoice'], 'ro' => true],
        // ---- outstanding_report
        'GET accounts/outstanding_report' => ['c' => 'p', 'priv' => ['accounts/outstanding_report'], 'ro' => true],
        // ---- posting_acc_transactions
        'GET accounts/posting_acc_transactions' => ['c' => 'r', 'priv' => ['accounts'], 'ro' => true],
        // ---- profit_loss
        'GET accounts/profit_loss' => ['c' => 'p', 'priv' => ['accounts/profit_loss'], 'ro' => true],
        // ---- service_invoice
        'GET accounts/service_invoice' => ['c' => 'p', 'priv' => ['accounts']],
        // ---- service_provider_payment
        'GET accounts/service_provider_payment' => ['c' => 'p', 'priv' => ['accounts/service_provider_payment']],
        'POST accounts/service_provider_payment/complete_payment' => ['c' => 'w', 'priv' => ['act:service_provider_payment.post'], 'csrf' => 'filter'],
        'GET accounts/service_provider_payment/report' => ['c' => 'p', 'priv' => ['accounts/service_provider_payment/report', 'accounts/service_provider_payment'], 'ro' => true],
        // ---- title_accounts
        'GET accounts/title_accounts' => ['c' => 'p', 'priv' => ['accounts', 'act:chart.maintain'], 'ro' => true],
        // ---- trial_balance
        'GET accounts/trial_balance' => ['c' => 'p', 'priv' => ['accounts/trial_balance'], 'ro' => true],
        'GET accounts/trial_balance/load_accountant_report' => ['c' => 'p', 'priv' => ['accounts/trial_balance/load_accountant_report', 'accounts/trial_balance'], 'ro' => true],
        'GET accounts/trial_balance/report' => ['c' => 'p', 'priv' => ['accounts/trial_balance/report', 'accounts/trial_balance'], 'ro' => true],
        'GET accounts/trial_balance/trial_balance_details/(:any)' => ['c' => 'p', 'priv' => ['accounts/trial_balance'], 'ro' => true],
        // ---- update
        'POST accounts/update/heading_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter'],
        'POST accounts/update/posting_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter'],
        'POST accounts/update/title_account' => ['c' => 'w', 'priv' => ['act:chart.maintain'], 'csrf' => 'filter'],
        // ---- usd_withdrawal
        'GET accounts/usd_withdrawal' => ['c' => 'p', 'priv' => ['accounts/usd_withdrawal']],
        'GET accounts/usd_withdrawal/history' => ['c' => 'p', 'priv' => ['accounts/usd_withdrawal/history', 'accounts/usd_withdrawal'], 'ro' => true],
        'GET accounts/usd_withdrawal/print' => ['c' => 'p', 'priv' => ['accounts/usd_withdrawal'], 'ro' => true],
        'POST accounts/usd_withdrawal/process' => ['c' => 'w', 'priv' => ['act:usd_withdrawal.post'], 'csrf' => 'filter'],
        // ---- utility_bill
        'GET accounts/utility_bill' => ['c' => 'p', 'priv' => ['accounts/utility_bill']],
        'GET accounts/utility_bill/delete' => ['c' => 't'],
        'POST accounts/utility_bill/delete' => ['c' => 'w', 'priv' => ['act:records.delete'], 'csrf' => 'filter', 'reason' => true],
        'GET accounts/utility_bill/fetch/(:num)/(:any)' => ['c' => 'r', 'priv' => ['accounts/utility_bill']],
        'POST accounts/utility_bill/pay' => ['c' => 'w', 'priv' => ['act:utility_bill.post'], 'csrf' => 'filter'],
        'GET accounts/utility_bill/report' => ['c' => 'p', 'priv' => ['accounts/utility_bill/report', 'accounts/utility_bill', 'act:records.delete'], 'ro' => true],
        'POST accounts/utility_bill/save' => ['c' => 'w', 'priv' => ['act:utility_bill.post'], 'csrf' => 'filter'],
        'POST accounts/utility_bill/update_status' => ['c' => 'w', 'priv' => ['act:utility_bill.post'], 'csrf' => 'filter'],
        // ---- utility_bill_entertainment
        'GET accounts/utility_bill_entertainment' => ['c' => 'p', 'priv' => ['accounts/utility_bill_entertainment', 'act:records.delete']],
        'POST accounts/utility_bill_entertainment/delete_expense' => ['c' => 'w', 'priv' => ['act:records.delete'], 'csrf' => 'filter', 'reason' => true],
        'GET accounts/utility_bill_entertainment/report' => ['c' => 'p', 'priv' => ['accounts/utility_bill_entertainment/report', 'accounts/utility_bill_entertainment', 'act:records.delete'], 'ro' => true],
        'POST accounts/utility_bill_entertainment/save' => ['c' => 'w', 'priv' => ['act:entertainment.post'], 'csrf' => 'filter'],
        'POST accounts/utility_bill_entertainment/update_expense' => ['c' => 'w', 'priv' => ['act:entertainment.post'], 'csrf' => 'filter'],
        'POST accounts/utility_bill_entertainment/upload_invoice' => ['c' => 'w', 'priv' => ['act:entertainment.post'], 'csrf' => 'filter'],
        // ---- voucher_audit
        'GET accounts/voucher_audit' => ['c' => 'p', 'priv' => ['accounts/voucher_audit', 'act:vouchers.audit_mark'], 'ro' => true],
        'GET accounts/voucher_audit/auth' => ['c' => 'p', 'priv' => ['accounts/voucher_audit', 'act:vouchers.audit_mark'], 'ro' => true],
        'POST accounts/voucher_audit/update' => ['c' => 'w', 'priv' => ['act:vouchers.audit_mark'], 'csrf' => 'filter'],
        'POST accounts/voucher_audit/update_tour' => ['c' => 'w', 'priv' => ['act:vouchers.audit_mark'], 'csrf' => 'filter'],
        // ---- voucher_auth
        'GET accounts/voucher_auth' => ['c' => 'p', 'priv' => ['accounts']],
        'GET accounts/voucher_auth/getVoucherDetails' => ['c' => 'r', 'priv' => ['accounts'], 'ro' => true],
        'POST accounts/voucher_auth/update' => ['c' => 'w', 'priv' => ['act:vouchers.finalise'], 'csrf' => 'filter'],
        // ---- voucher_payments
        'GET accounts/voucher_payments/(:num)' => ['c' => 'p', 'priv' => ['accounts/voucher_payments/0']],
        'GET accounts/voucher_payments/fetch/(:num)/(:any)' => ['c' => 'r', 'priv' => ['accounts/voucher_payments/0']],
        'GET accounts/voucher_payments/getSupplierDetails' => ['c' => 'r', 'priv' => ['accounts/voucher_payments/0'], 'ro' => true],
        'POST accounts/voucher_payments/get_pc_accumulated_balance' => ['c' => 'r', 'priv' => ['accounts/voucher_payments/0', 'report/bank_payment_check', 'accounts/voucher_auth']],
        'POST accounts/voucher_payments/save' => ['c' => 'w', 'priv' => ['act:vouchers.create'], 'csrf' => 'filter'],
        'GET accounts/voucher_payments/voucher_cancel' => ['c' => 'p', 'priv' => ['accounts/voucher_payments/voucher_cancel'], 'ro' => true],
        'GET accounts/voucher_payments/voucher_reject' => ['c' => 'p', 'priv' => ['accounts/voucher_payments/0']],
        // ---- voucher_print
        'GET accounts/voucher_print/(:any)' => ['c' => 'p', 'priv' => ['accounts', 'act:vouchers.finalise'], 'ro' => true],
    ];
}
