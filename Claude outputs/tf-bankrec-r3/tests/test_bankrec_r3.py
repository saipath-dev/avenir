r"""tf-bankrec-r3 — cloud test of the r3 changes, after the whole r2 suite (DUMMY data only, cloud test copy).

  1. D7: the old Bank Reconciliation screen refuses changes for a mapped account on or after its first day; earlier dates
     stay available; flags already set in the protected period are listed by the new workflow and left unchanged.
  2. Pending corrections per day: only the amount attributable up to the day, the full correction total separately,
     each correction counted once.
  3. A verified posting whose entry changes must be verified again; a user holding both the preparer and the reviewer
     privilege (not the administrator) is refused the approval / review of their own work; two DIFFERENT users at once.
The r2 suite (108 checks) runs first and re-checks partial clearing, undo, opening items, changes after review,
exchange-rate explanations and pending vs posted. The real ledger is compared before and after.
"""
import os, sys, json, subprocess, time
HERE = os.path.dirname(os.path.abspath(__file__)); S = os.path.dirname(HERE)
sys.path.insert(0, S + '/paydesk'); sys.path.insert(0, HERE)
import api
import test_bankrec_r2 as R2
from test_bankrec_r2 import check, sql, LEDGER_SIG, BR, MW, race, day, RES

AJ = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
api.ACC['dual'] = ('tcdual', 'tc-pay-2026')

def ensure_dual():
    """A TEST login holding BOTH the preparer and the reviewer privilege (not an administrator), test copy only."""
    gid = 919
    h = subprocess.run(['php', '-r', 'echo password_hash("tc-pay-2026", PASSWORD_DEFAULT);'], capture_output=True, text=True).stdout
    if sql(f"SELECT COUNT(*) FROM group_profile WHERE id = {gid}") == '0':
        sql(f"INSERT INTO group_profile (id, type, user, del) VALUES ({gid}, 'TEST Reconciliation preparer + reviewer (test)', 1, 0)")
    if sql(f"SELECT COUNT(*) FROM employee WHERE emp_num = {gid}") == '0':
        sql(f"INSERT INTO employee (emp_num, Acc_No, Name) VALUES ({gid}, '4-8-9-90{gid}', 'Dual role tester')")
    if sql("SELECT COUNT(*) FROM user_acc_tbl WHERE user = 'tcdual'") == '0':
        sql(f"INSERT INTO user_acc_tbl (id, emp_id, user, pass, group_id, access, login_type, user_role) VALUES ({gid}, {gid}, 'tcdual', '{h}', {gid}, 1, 1, 0)")
    for u in ('accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'):
        mid = sql(f"SELECT id FROM menu_list WHERE url = '{u}' AND del = 0 LIMIT 1")
        if mid and sql(f"SELECT COUNT(*) FROM user_privileges WHERE group_id = {gid} AND menu_id = {mid}") == '0':
            sql(f"INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES ({gid}, 1, {mid})")

def old(adm, path, data):
    """A call to the old Bank Reconciliation screen (accounts/bank_reconcil/...), as its page makes it."""
    r = adm.c.s.post(adm.c.base + 'accounts/bank_reconcil/' + path, data=data, headers=AJ)
    try:
        return r.status_code, r.json()
    except Exception:
        return r.status_code, {'raw': r.text[:200]}

def flags(ids):
    return sql("SELECT GROUP_CONCAT(CONCAT(ID, ':', L_Code, ':', COALESCE(New_L_Code, ''), ':', COALESCE(bank_rec, 0), ':', COALESCE(bankst_flag, 0)) ORDER BY ID, L_Code) FROM account_runing WHERE ID IN (%s)" % ','.join(str(i) for i in ids))

def part_d7(adm, prep, rev, view, ids, b):
    V, L = b['V'], ids['LKR']
    vcode = '1-6-8-29904'
    ids_all = [99980302, 99980304, 99980305, 99980306, 99980307, 99980003]
    f0 = flags(ids_all)
    a0 = int(sql("SELECT COALESCE(MAX(id),0) FROM tpd_audit"))
    c, j = old(adm, 'update', {'id': 99980305, 'flag': 0, 'bank': 0})
    check('D7 old screen "update" on a row of the mapped verification account dated 2026-09-02 (≥ first day 2026-09-01) → refused 409, message names the first day; nothing changed',
          c == 409 and j.get('protected_period') is True and '2026-09-01' in j.get('message', '') and flags(ids_all) == f0, (c, j, flags(ids_all)))
    c, j = old(adm, 'update', {'id': 99980302, 'flag': 0, 'bank': 0})
    set1 = sql("SELECT COALESCE(bank_rec, 0) FROM account_runing WHERE ID = 99980302")
    c2, j2 = old(adm, 'update', {'id': 99980302, 'flag': 0, 'bank': 1})
    check('D7 earlier dates stay available: "update" on the 2026-08-28 cheque row (before the first day) works (bank_rec 1), and unticking it puts it back (0)',
          c == 200 and set1 == '1' and c2 == 200 and flags(ids_all) == f0, (c, j, set1, c2, flags(ids_all)))
    c, j = old(adm, 'finalize_selected', {'records[0][id]': 99980302, 'records[0][bank]': 0, 'records[1][id]': 99980306, 'records[1][bank]': 0})
    check('D7 "finalize selected" with one row before and one row after the first day → all refused (409); neither row changed', c == 409 and flags(ids_all) == f0, (c, j, flags(ids_all)))
    c, j = old(adm, 'reconciliation', {'date2': '2026-09-05', 'bank': vcode})
    c2, j2 = old(adm, 'reconciliation', {'date2': '2026-08-31', 'bank': vcode})
    check('D7 "reconcile up to" 2026-09-05 → refused (409); up to 2026-08-31 (before the first day) → allowed (200); no flag changed (no ticked rows before the first day)',
          c == 409 and c2 == 200 and flags(ids_all) == f0, (c, j.get('message'), c2, j2, flags(ids_all)))
    c, j = old(adm, 'post_unrealised_entries', {'itemArray[0][lcode]': vcode, 'itemArray[0][ID]': 99980306})
    c2, j2 = old(adm, 'repost_single_entry', {'rowId': 99980304})
    c3, j3 = old(adm, 'repost_unrealised_entries', {'date1': '2026-08-15', 'date2': '2026-09-10', 'bank': vcode})
    c4, j4 = old(adm, 'update', {'id': 99980003, 'flag': 0, 'bank': 0})
    check('D7 moving a row to the unrealised account (409), moving one back (409), reposting a range that reaches the first day (409), and a row of the TEST LKR account (409) are all refused; nothing changed',
          (c, c2, c3, c4) == (409, 409, 409, 409) and flags(ids_all) == f0, (c, c2, c3, c4, flags(ids_all)))
    n = int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id > {a0} AND action = 'brc_legacy_refused'"))
    check(f'D7 every refusal is audited ({n} "brc_legacy_refused" rows, by the signed-in user)', n == 7 and sql(f"SELECT GROUP_CONCAT(DISTINCT user) FROM tpd_audit WHERE id > {a0} AND action = 'brc_legacy_refused'") == 'tcadmin', n)
    # flags set before the guard existed (simulated on dummy rows by SQL): listed, never changed by the new workflow
    nl0 = sql("SELECT COALESCE(New_L_Code, 'NULL') FROM account_runing WHERE ID = 99980307")
    before_day = day(view, V, '2026-09-05')
    sql("UPDATE account_runing SET bank_rec = 1 WHERE ID = 99980306")
    sql(f"UPDATE account_runing SET New_L_Code = L_Code, L_Code = '1-6-112-10782' WHERE ID = 99980307 AND L_Code = '{vcode}'")
    d = day(view, V, '2026-09-05')
    lf = d.get('legacy_flags') or {}
    rows = {r['id']: r['flags'] for r in lf.get('rows', [])}
    c, ja = view.get('api/accounts')
    ov = next((a for a in ja.get('accounts', []) if a['id'] == V), {})
    check('D7 the new workflow warns about old-screen flags in the protected period: 2 rows (bank_rec on 99980306; 99980307 moved to the unrealised account), in the day and on the overview',
          lf.get('count') == 2 and rows.get(99980306) == ['bank_rec'] and 'moved to the unrealised account 1-6-112-10782' in rows.get(99980307, []) and any('old Bank Reconciliation screen' in w for w in d.get('warnings', [])) and (ov.get('day') or {}).get('legacy_flags') == 2,
          (lf, d.get('warnings'), (ov.get('day') or {}).get('legacy_flags')))
    check('D7 the flags are left as they are by the new workflow (still set after the day and the overview were computed)',
          sql("SELECT CONCAT(bank_rec, '|', L_Code) FROM account_runing WHERE ID IN (99980306, 99980307) ORDER BY ID") == '1|1-6-8-29904\n0|1-6-112-10782', sql("SELECT CONCAT(bank_rec, '|', L_Code) FROM account_runing WHERE ID IN (99980306, 99980307) ORDER BY ID"))
    sql("UPDATE account_runing SET bank_rec = 0 WHERE ID = 99980306")
    sql(f"UPDATE account_runing SET L_Code = New_L_Code, New_L_Code = {'NULL' if nl0 == 'NULL' else repr(nl0)} WHERE ID = 99980307 AND L_Code = '1-6-112-10782'")
    d2 = day(view, V, '2026-09-05')
    check('D7 the test rows put back: no warning, the day as before', (d2.get('legacy_flags') or {}).get('count') == 0 and flags(ids_all) == f0 and d2.get('status_code') == before_day.get('status_code'),
          (d2.get('legacy_flags'), flags(ids_all), d2.get('status_code'), before_day.get('status_code')))

def part_pending(prep, rev, view, ids, a):
    L = ids['LKR']
    exp = {'2026-09-01': ('25.00', '4550.00'), '2026-09-02': ('50.00', '4550.00'), '2026-09-03': ('1050.00', '4550.00'), '2026-09-04': ('236050.00', '239550.00'), '2026-09-05': ('239550.00', '239550.00')}
    got = {}
    ok = True
    for dd, (tot, full) in exp.items():
        d = day(view, L, dd); p = d.get('pending_postings') or {}
        ap = p.get('approved') or {}
        ids_ = [c['id'] for c in p.get('list', [])]
        got[dd] = (ap.get('total'), ap.get('full_total'), p.get('summary'), d.get('status_code'), (d.get('reconciliation') or {}).get('unexplained_lkr'))
        suffix_ok = ('up to this day; correction total' in p.get('summary', '')) == (tot != full)
        ok = ok and ap.get('total') == tot and ap.get('full_total') == full and suffix_ok and len(ids_) == len(set(ids_)) and (d.get('reconciliation') or {}).get('unexplained_lkr') == '0.00'
    check('P2 pending corrections per day (TEST LKR): approved, not posted = 25.00 (1 Sep), 50.00 (2 Sep), 1,050.00 (3 Sep), 236,050.00 (4 Sep), 239,550.00 (5 Sep); the full total (4,550.00 / 239,550.00) named separately when it differs; each correction once; unexplained 0.00',
          ok, got)
    d1 = day(view, L, '2026-09-01')
    ch = next((c for c in d1['pending_postings']['list'] if c['id'] == a['cC']), {})
    check('P2 1 Sep: "1 approved but not posted (25.00 up to this day; correction total 4,550.00)" — the 4,550.00 charges correction covers 1–5 Sep',
          '1 approved but not posted (25.00 up to this day; correction total 4,550.00)' in d1['status_label'] and ch.get('attributable') == '25.00' and ch.get('full_amount') == '4550.00', (d1['status_label'], ch))
    c, j = prep.post('day/prepare', {'account': L, 'date': '2026-09-03', 'note': 'r3: attributable amounts'})
    rid = j.get('id')
    c2, j2 = view.get('api/day_record', id=rid)
    rec = (j2.get('record') or {})
    pj = json.loads(sql(f"SELECT pending_json FROM brc_day WHERE id = {int(rid)}") or '{}') if rid else {}
    check('P2 a daily record prepared for 3 Sep keeps "1,050.00 up to this day; correction total 4,550.00" in its snapshot',
          c == 200 and 'up to this day; correction total' in (pj.get('summary') or '') and (pj.get('approved') or {}).get('total') == '1050.00', (c, j.get('message'), pj.get('summary')))
    c, j = rev.post('day/review', {'id': rid, 'decision': 'reviewed', 'note': 'r3'})
    d3 = day(view, L, '2026-09-03')
    check('P2 reviewed: "Reviewed — ledger not yet corrected — 1 approved but not posted (1,050.00 up to this day; correction total 4,550.00)"',
          c == 200 and d3['status_code'] == 'reviewed_pending' and '1,050.00 up to this day; correction total 4,550.00' in d3['status_label'], d3['status_label'])

def part_reverify(prep, rev, view, b):
    V, cV = b['V'], b['cV']
    st = lambda: next((c for c in view.get('api/corrections', account=V)[1].get('corrections', []) if c['id'] == cV), {})
    sig = lambda: sql("SELECT GROUP_CONCAT(CONCAT(L_Code, '|', Dr, '|', Cr, '|', Discription) ORDER BY L_Code) FROM account_runing WHERE ID = 99980399")
    s0 = sig(); a0 = st()
    check('R the V charge correction is posted and verified with dummy entry 99980399 (from the r2 suite)', a0.get('state') == 'posted', a0.get('state_label'))
    desc0 = sql("SELECT Discription FROM account_runing WHERE ID = 99980399 LIMIT 1")
    sql("UPDATE account_runing SET Discription = 'BANK CHARGES 01-09 (dummy posting, wording changed)' WHERE ID = 99980399")
    a1 = st(); d1 = day(view, V, '2026-09-05')
    check('R the posted entry changes in the ledger (wording) → "Posted entry changed in the ledger — verify again"; the charge is no longer explained',
          a1.get('state') == 'posted_changed' and d1['status_code'] not in ('explained', 'reviewed'), (a1.get('state_label'), d1['status_label']))
    c, j = rev.post('correction/reverify', {'id': cV})
    check('R a reviewer without the preparer privilege cannot verify again (403)', c == 403, c)
    c, j = prep.post('correction/reverify', {'id': cV})
    a2 = st()
    check('R the preparer verifies again: the entry still matches the approved lines → posted and verified (its new state); audited',
          c == 200 and a2.get('state') == 'posted' and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_correction_posting_reverified' AND ref_id = '{cV}'") != '0', (c, j.get('message'), a2.get('state')))
    c, j = prep.post('correction/reverify', {'id': cV})
    check('R verifying again when nothing changed is refused (409)', c == 409, (c, j.get('message')))
    sql("UPDATE account_runing SET Dr = IF(Dr > 0, 30, 0), Cr = IF(Cr > 0, 30, 0) WHERE ID = 99980399")
    c, j = prep.post('correction/reverify', {'id': cV})
    a3 = st()
    check('R the entry\'s amounts change (25 → 30): verifying again is refused (409, names the mismatch) and it stays "verify again"; audited',
          c == 409 and 'does not match' in j.get('message', '') and a3.get('state') == 'posted_changed' and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE action = 'brc_correction_reverify_refused' AND ref_id = '{cV}'") != '0', (c, j.get('message'), a3.get('state')))
    sql("UPDATE account_runing SET Dr = IF(Dr > 0, 25, 0), Cr = IF(Cr > 0, 25, 0) WHERE ID = 99980399")
    a4 = st()
    sql("UPDATE account_runing SET Discription = %s WHERE ID = 99980399" % repr(desc0))
    a5 = st()
    c, j = prep.post('correction/reverify', {'id': cV})
    a6 = st()
    check('R amounts put back → posted again; wording put back → "verify again" → verified again; the dummy entry is exactly as before',
          a4.get('state') == 'posted' and a5.get('state') == 'posted_changed' and c == 200 and a6.get('state') == 'posted' and sig() == s0, (a4.get('state'), a5.get('state'), c, a6.get('state'), sig() == s0))

def part_dual(prep, rev, view, b):
    V = b['V']
    dual = BR('dual')
    c, j = dual.post('opening/propose', {'account': V, 'book_key': '99980301.1', 'evidence': 'dual-role test (dummy)'})
    oid = j.get('id')
    c2, j2 = dual.post('opening/decide', {'id': oid, 'decision': 'accept', 'note': 'own'})
    c3, j3 = dual.post('opening/decide', {'id': oid, 'decision': 'withdraw', 'note': 'dual-role test withdrawn'})
    check('U a user holding BOTH the preparer and the reviewer privilege (tcdual, not an administrator) proposes an opening item (200) and cannot accept it (403); withdraws it (200)',
          (c, c2, c3) == (200, 403, 200), (c, c2, c3, j2.get('message')))
    l3 = int(b.get('l3') or sql(f"SELECT l.id FROM brc_line l JOIN brc_statement s ON s.id = l.statement_id AND s.status = 'accepted' WHERE l.bank_account_id = {V} AND l.description LIKE 'TEST INTEREST CREDIT%%' ORDER BY l.id DESC LIMIT 1") or 0)
    c, j = dual.post('correction/propose', {'account': V, 'kind': 'unbooked_receipt', 'description': 'TEST interest credit 6 Sep not in the ledger (dual-role test, dummy)', 'entry_date': '2026-09-06',
                                            'lines': json.dumps([{'ledger_code': '1-6-8-29904', 'dr': '1000', 'cr': ''}, {'ledger_code': '2-9-99-29909', 'dr': '', 'cr': '1000'}]), 'line_ids': json.dumps([l3])})
    cid = j.get('id')
    c2, j2 = dual.post('correction/decide', {'id': cid, 'decision': 'approve', 'note': 'own'})
    c3, j3 = dual.post('correction/withdraw', {'id': cid, 'reason': 'dual-role test withdrawn'})
    check('U the same user proposes a correction (200), cannot approve it (403), withdraws it (200)', (c, c2, c3) == (200, 403, 200), (c, j.get('message'), c2, j2.get('message'), c3, j3.get('message')))
    c, j = dual.post('day/prepare', {'account': V, 'date': '2026-09-05', 'note': 'dual-role test'})
    rid = j.get('id')
    c2, j2 = dual.post('day/review', {'id': rid, 'decision': 'reviewed', 'note': 'own'})
    c3, j3 = rev.post('day/review', {'id': rid, 'decision': 'reviewed', 'note': 'reviewed by the reviewer'})
    check('U the same user prepares a daily record (200), cannot review it (403); another reviewer reviews it (200)', (c, c2, c3) == (200, 403, 200), (c, c2, j2.get('message'), c3))
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-04', 'note': 'prepared by the preparer'})
    c2, j2 = dual.post('day/review', {'id': j.get('id'), 'decision': 'reviewed', 'note': 'dual-role user reviews someone else\'s record'})
    check('U the same user may review a record someone else prepared (200)', c == 200 and c2 == 200, (c, c2, j2.get('message')))
    return dual, l3

def part_two_users(prep, rev, dual, b, l3):
    V = b['V']
    p2, d2, r2 = BR('accountant', base=MW), BR('dual', base=MW), BR('approver', base=MW)
    out = race([(p2, 'mark/create', {'account': V, 'side': 'bank', 'ref': l3, 'mark': 'timing', 'note': 'race: preparer', 'expected_date': '2026-09-08'}),
                (d2, 'mark/create', {'account': V, 'side': 'bank', 'ref': l3, 'mark': 'excluded', 'note': 'race: dual-role user'})])
    codes = sorted(o[0] for o in out)
    win = [o[1].get('id') for o in out if o[0] == 200]
    check('T two DIFFERENT users classify the same statement line at the same instant: exactly one succeeds (200), the other is refused (409)', codes == [200, 409], [(o[0], o[1].get('message')) for o in out])
    if win:
        owner = p2 if out[0][0] == 200 else d2
        owner.post('mark/clear', {'id': win[0], 'reason': 'race test cleared'})
    c, j = prep.post('day/prepare', {'account': V, 'date': '2026-09-03', 'note': 'race: two reviewers'})
    rid = j.get('id')
    out = race([(r2, 'day/review', {'id': rid, 'decision': 'reviewed', 'note': 'race: reviewer'}), (d2, 'day/review', {'id': rid, 'decision': 'reviewed', 'note': 'race: dual-role user'})])
    codes = sorted(o[0] for o in out)
    check('T two DIFFERENT reviewers review the same daily record at the same instant: one 200, one 409; reviewed once (one audit row)',
          codes == [200, 409] and sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id > {R2.A0} AND action = 'brc_day_reviewed' AND ref_id = '{rid}'") == '1', [(o[0], o[1].get('message')) for o in out])
    check('T in both races the two users\' requests were in the server at the same time (overlapping)', all(r['first_requests_overlapped'] for r in R2.RACES[-2:]) and all(len(r['users']) == 2 for r in R2.RACES[-2:]), R2.RACES[-2:])

def main():
    sig_real0 = sql(LEDGER_SIG)
    R2.start_mw(); R2.ensure_users(); ensure_dual()
    # ---- the whole r2 suite on the r3 code
    adm = BR('admin'); adm.page()
    prep, rev, view = BR('accountant'), BR('approver'), BR('bank')
    a0 = int(sql("SELECT COALESCE(MAX(id),0) FROM tpd_audit"))
    ids, a = R2.part_a(adm, prep, rev, view, a0)
    b = R2.part_b(adm, prep, rev, view, ids)
    n_r2 = len(RES)
    dummy0 = sql("SELECT CONCAT(COUNT(*),'|',SUM(Dr),'|',SUM(Cr),'|',SUM(CRC32(CONCAT_WS('|', ID, L_Code, Dr, Cr, Discription, Date, aflag, bank_rec, bankst_flag, BankFlg, Recpt_No, Chk_No, COALESCE(New_L_Code,''))))) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999")
    # ---- r3
    part_d7(adm, prep, rev, view, ids, b)
    part_pending(prep, rev, view, ids, a)
    part_reverify(prep, rev, view, b)
    dual, l3 = part_dual(prep, rev, view, b)
    part_two_users(prep, rev, dual, b, l3)
    dummy1 = sql("SELECT CONCAT(COUNT(*),'|',SUM(Dr),'|',SUM(Cr),'|',SUM(CRC32(CONCAT_WS('|', ID, L_Code, Dr, Cr, Discription, Date, aflag, bank_rec, bankst_flag, BankFlg, Recpt_No, Chk_No, COALESCE(New_L_Code,''))))) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999")
    check('dummy ledger rows exactly as before the r3 part (every test change to a dummy row was put back; flags and unrealised moves included)', dummy1 == dummy0, (dummy0, dummy1))
    check('the real ledger is unchanged: rows, sums and every row\'s reconciliation flags', sql(LEDGER_SIG) == sig_real0, (sig_real0, sql(LEDGER_SIG)))
    n = int(sql(f"SELECT COUNT(*) FROM tpd_audit WHERE id > {a0} AND (action LIKE 'brc%%' OR ref_type LIKE 'brc%%') AND user NOT IN ('tcaccount','tcaccount2','tcpayappr','tcpayappr2','tcbankappr','tcadmin','tcdual','tcpayreq','tcops','tctransh','tchotelres','tcquote','tchotel','tctransport','tcmgmt')"))
    check('every audited action is attributed to an identifiable test login', n == 0, n)
    json.dump({'ids': ids, 'a': a, 'b': b, 'r2_checks': n_r2, 'races': R2.RACES}, open(HERE + '/test_ids_r3.json', 'w'), indent=1)

if __name__ == '__main__':
    try:
        main()
    finally:
        json.dump(RES, open(os.environ.get('BRC_RESULTS', HERE + '/results_bankrec_r3.json'), 'w'), indent=1)
        print('%d/%d passed' % (sum(r['ok'] for r in RES), len(RES)))
