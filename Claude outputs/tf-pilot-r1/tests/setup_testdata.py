"""tf-paydesk-r1 — TEST COPY ONLY (database avenir_test). Builds the demonstration tour and the four payment-role accounts.
Never run against the live database. Idempotent: running it twice changes nothing more.

  Tour AH 2218 (tour_id 2325) becomes the NEMOTO / REIKO MS test tour, 11–16 Oct 2026, 1 pax, agent STW:
    hotels as already quoted (COVENRO VEYANGODA · Kassapa Lions Rock ×2 · Cassamara Hotel · MORVEN COLOMBO),
    meals and entrances as activities, a driver allocated with an advance request, a guide.
  Accounts (password tc-pay-2026): tcpayreq (requester) · tcpayappr (management approver) · tcaccount (accountant) · tcbankappr (bank approver)
"""
import subprocess, os, sys
S = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + S + '/mysql.sock', '-uroot', '-N', os.environ.get('TC_DB', 'avenir_test'), '-e', q], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()

PW = '$2y$10$5oNelaTpdeOjWqMV4seKJui79bZaJwKq4TNjZGAFDzTpSKiLouFgy'   # tc-pay-2026
TOUR = '2325'

# ---- the tour ----
sql("""UPDATE consignee SET consignee='NEMOTO / REIKO MS', verify=1, tour_cancel=0, guide='6-9-11-10675', guide_name='Sajith M T (guide)',
       arrival_flight='UL455', arrival_flight_time='14:05', departure_flight='UL454', departure_flight_time='08:35'
       WHERE tour_id='2325' AND manual_id='AH 2218'""")
acts = [
    ('2026-10-12', 'En-route lunch — Gimanhala', '6-9-11-11590', 'GIMANHALA LUNCH VOUCHER', 2500),
    ('2026-10-12', 'Sigiriya entrance tickets', '6-9-11-11617', 'ENTRANCE FEES - Fright Less Jeep Safari Minneriya  - (TISSA)', 12500),
    ('2026-10-13', 'Minneriya safari jeep', '6-9-23-11075', 'Fright Less Jeep Safari Minneriya ', 8000),
    ('2026-10-14', 'Spice garden lunch — Regence', '6-9-11-11595', 'REGENCE  SPICE GARDEN LUNCH VOUCHER', 1800),
    ('2026-10-15', 'Colombo city tour tuk-tuk transfer', '6-9-23-10826', 'Food edge Family restaurant', 4500),
]
for i, (d, name, acc, sname, fee) in enumerate(acts, 1):
    if sql("SELECT COUNT(*) FROM tour_activity WHERE tour_id=%s AND name='%s'" % (TOUR, name.replace("'", "''"))) == '0':
        sql("INSERT INTO tour_activity (tour_id, tour_row, tour_date, name, code, fee, ref_no, supplier_id, supplier_name, payment_request, lkr_fee, is_prepaid) VALUES (%s, %d, '%s', '%s', NULL, %d, 0, '%s', '%s', 0, %d, 0)"
            % (TOUR, i, d, name.replace("'", "''"), fee, acc, sname.replace("'", "''"), fee))
if sql("SELECT COUNT(*) FROM driver_allocation WHERE tour_id=%s" % TOUR) == '0':
    sql("INSERT INTO driver_allocation (tour_id, manual_id, driver, driver_name, allow_date, allow_user, payment_settlement, request_allocation) VALUES (%s, 'AH 2218', '6-9-11-10639', 'MR. UDAYA KUMARA', NOW(), 'tcadmin', 0, 0)" % TOUR)
if sql("SELECT COUNT(*) FROM driver_advance_request WHERE tour_id=%s" % TOUR) == '0':
    sql("INSERT INTO driver_advance_request (tour_id, manual_id, driver, driver_name, amount, username, flag, date) VALUES (%s, 'AH 2218', '6-9-11-10639', 'MR. UDAYA KUMARA', 20000.00, 'tctransport', 0, NOW())" % TOUR)
# tf-paydesk-r2: a second tour with a Cassamara night, so one supplier batch can carry two tours (AH 2290, tour_id 2390, 20–22 Oct 2026)
if sql("SELECT COUNT(*) FROM consignee WHERE tour_id='2390'") == '0':
    sql("CREATE TEMPORARY TABLE tc_c AS SELECT * FROM consignee WHERE tour_id='2325'; UPDATE tc_c SET ID=0, tour_id='2390', manual_id='AH 2290', consignee='TANAKA / YUKI MS', start_date='2026-10-20', end_date='2026-10-22', passenger_count=2, transport_agent=NULL, transport_agent_name=NULL; INSERT INTO consignee SELECT * FROM tc_c")
    sql("CREATE TEMPORARY TABLE tc_q AS SELECT * FROM tour_quotations WHERE quotation_id='2325' AND hotel_acc='6-9-11-11047'; UPDATE tc_q SET id=0, quotation_id='2390', tour_date='2026-10-20', hotel_dollar_rate=0, payment_request=0, booking_confirmed_flag=0, hotel_payment_flag=0, hotel_advance=0; INSERT INTO tour_quotations SELECT * FROM tc_q")
    sql("CREATE TEMPORARY TABLE tc_q2 AS SELECT * FROM tour_quotations WHERE quotation_id='2325' AND hotel_acc='6-9-11-11047'; UPDATE tc_q2 SET id=0, quotation_id='2390', tour_date='2026-10-21', hotel_acc='6-9-11-10626', hotel_name='COVENRO VEYANGODA', sgl=55.00, meal_code='bb', room_type=1, hotel_dollar_rate=0, payment_request=0, booking_confirmed_flag=0, hotel_payment_flag=0, hotel_advance=0; INSERT INTO tour_quotations SELECT * FROM tc_q2")
    sql("CREATE TEMPORARY TABLE tc_q3 AS SELECT * FROM tour_quotations WHERE quotation_id='2325' AND tour_date='2026-10-16'; UPDATE tc_q3 SET id=0, quotation_id='2390', tour_date='2026-10-22'; INSERT INTO tour_quotations SELECT * FROM tc_q3")
# tf-paydesk-r3: two TEST ledger accounts for the exchange gain / loss mapping (test copy only — on live Accounts creates or
# approves the real accounts in the chart of accounts and the administrator maps them in Settings)
for pcode, name, lcode in [(31901, 'TEST Realised Exchange Gain', '4-8-109-31901'), (31902, 'TEST Realised Exchange Loss', '4-8-109-31902'), (31903, 'TEST Bank Charges', '4-8-109-31903')]:
    if sql("SELECT COUNT(*) FROM account_posting WHERE L_Code='%s'" % lcode) == '0':
        sql("INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code, Link, Pay, acp, deact, grn, type) VALUES (%d, '%s', 4, 8, 109, '%s', NULL, NULL, NULL, 0, NULL, 0)" % (pcode, name, lcode))
# a TourFlow tour linked to AH 2218, so the Complete Tour Record shows the tour's supplier payments (test copy only)
if sql("SELECT COUNT(*) FROM tf_tour WHERE legacy_tour_id='2325'") == '0' and sql("SELECT COUNT(*) FROM tf_tour WHERE id=4") == '1':
    sql("UPDATE tf_tour SET legacy_tour_id='2325' WHERE id=4")
# supplier e-mail addresses for the remittance advices (test addresses; the test mail sink keeps every message)
for acc, mail in [('6-9-11-11496', 'kassapa-test@example.invalid'), ('6-9-11-11047', 'cassamara-test@example.invalid'), ('6-9-11-10626', 'covenro-test@example.invalid'), ('6-9-11-11392', 'morven-test@example.invalid'), ('6-9-11-11590', 'gimanhala-test@example.invalid')]:
    sql("UPDATE supplier SET email='%s' WHERE Sup_Acc_No='%s' AND (email IS NULL OR email='')" % (mail, acc))

# ---- the menu rows the live system has and this copy lacks ----
menus = [('tour/tour_payment_dashboard', 'Tour Payment Dashboard', 22, 0), ('tour/tour_accounts/payment_approval_v2', 'Payment approval', 22, 0),
         ('report/bank_payment_check', 'Bank Payment Check Report', 23, 0), ('report/bank_payment_check/finalized_records', 'Finalized Bank Check Payment', 23, 0), ('master_files/daily_usd_rate', 'Daily USD Rate', 4, 0),
         ('master_files/supplier', 'Supplier', 4, 0)]
for url, name, cat, hidden in menus:
    if sql("SELECT COUNT(*) FROM menu_list WHERE url='%s' AND del=0" % url) == '0':
        sql("INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (%d, '%s', 1, 0, '%s', 50, %d)" % (cat, name, url, hidden))
        mid = sql("SELECT id FROM menu_list WHERE url='%s' AND del=0 ORDER BY id DESC LIMIT 1" % url)
        sql("INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (1, 1, %s)" % mid)

# ---- the four payment roles ----
roles = {
    906: ('tcpayreq', 'TPD Requester (test)', 'Payment requester tester', ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/request']),   # r5: making requests is its own privilege
    907: ('tcpayappr', 'TPD Approver (test)', 'Management approver tester', ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/approve', 'tour/tour_payment_dashboard/payables', 'tour/tour_payment_dashboard/batch', 'tour/tour_accounts/payment_approval_v2', 'master_files/daily_usd_rate', 'master_files/offer_selection']),
    908: ('tcaccount', 'TPD Accountant (test)', 'Accountant tester', ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/payables', 'report/bank_payment_check', 'report/bank_payment_check/initiate', 'report/bank_payment_check/finalized_records', 'master_files/bank_accounts', 'master_files/supplier_bank', 'master_files/supplier']),
    909: ('tcbankappr', 'TPD Bank Approver (test)', 'Bank approver tester', ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/payables', 'report/bank_payment_check', 'report/bank_payment_check/approve', 'report/bank_payment_check/finalized_records', 'master_files/supplier_bank/verify', 'master_files/supplier_bank']),
    # tf-pilot-r1: a read-only auditor (report pages only; the profile name carries "read-only", like profile 34 on the development system)
    910: ('tcauditor', 'TPD Auditor (read-only) (test)', 'Auditor tester', ['tour/tour_payment_dashboard/payables', 'report/bank_payment_check/finalized_records', 'report/voucher_report', 'accounts/voucher_audit', 'master_files/supplier/all']),
}
for gid, (user, gname, ename, urls) in roles.items():
    if sql("SELECT COUNT(*) FROM group_profile WHERE id=%d" % gid) == '0':
        sql("INSERT INTO group_profile (id, type, user, del) VALUES (%d, '%s', 1, 0)" % (gid, gname))
    if sql("SELECT COUNT(*) FROM employee WHERE emp_num=%d" % gid) == '0':
        sql("INSERT INTO employee (emp_num, Acc_No, Name) VALUES (%d, '4-8-9-90%d', '%s')" % (gid, gid, ename))
    if sql("SELECT COUNT(*) FROM user_acc_tbl WHERE user='%s'" % user) == '0':
        sql("INSERT INTO user_acc_tbl (id, emp_id, user, pass, acc_no, group_id, access, login_type, user_role, login_attempts) VALUES (%d, %d, '%s', '%s', '4-8-9-90%d', %d, 1, 1, 0, 0)" % (gid, gid, user, PW, gid, gid))
    for url in urls:
        mid = sql("SELECT id FROM menu_list WHERE url='%s' AND del=0 ORDER BY id LIMIT 1" % url)
        if not mid:
            print('  menu missing:', url); continue
        if sql("SELECT COUNT(*) FROM user_privileges WHERE group_id=%d AND menu_id=%s" % (gid, mid)) == '0':
            sql("INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (%d, 1, %s)" % (gid, mid))
print('tour:', sql("SELECT CONCAT(manual_id,' | ',consignee,' | verify=',verify,' | ',start_date,'..',end_date) FROM consignee WHERE tour_id='2325'"))
print('tour 2:', sql("SELECT CONCAT(manual_id,' | ',consignee,' | ',start_date,'..',end_date, ' | nights=', (SELECT COUNT(*) FROM tour_quotations WHERE quotation_id='2390' AND hotel_acc<>'')) FROM consignee WHERE tour_id='2390'"))
print('activities:', sql("SELECT COUNT(*) FROM tour_activity WHERE tour_id=2325"), '| driver:', sql("SELECT driver_name FROM driver_allocation WHERE tour_id=2325"), '| advance req:', sql("SELECT amount FROM driver_advance_request WHERE tour_id=2325"))
print('users:', sql("SELECT GROUP_CONCAT(CONCAT(user,'(',group_id,')')) FROM user_acc_tbl WHERE id BETWEEN 906 AND 910"))
