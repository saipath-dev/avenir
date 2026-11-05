"""TEST COPY ONLY. on: give each department test group exactly the menus the matching profile holds on the development system
today (read 11 Oct 2026 by URL; missing menu rows added as ZZSCAN entries); off: put every test group back."""
import sys, subprocess, os, json
S = os.path.dirname(os.path.dirname(os.path.abspath(__file__))); SOCK = S + '/mysql.sock'
def sql(q):
    r = subprocess.run(['mysql', '--socket=' + SOCK, '-uroot', '-N', 'avenir_test', '-e', q], capture_output=True, text=True)
    if r.returncode != 0: raise SystemExit('SQL failed: ' + r.stderr + '\n' + q[:300])
    return r.stdout.strip()
DEV = json.load(open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'dev_group_menus.json')))
MAP = {'906': '31', '907': '29', '908': '30', '909': '35', '910': '34', '911': '36', '912': '33', '913': '32'}
if sys.argv[1] == 'on':
    if not sql("SHOW TABLES LIKE 'zz_tg_bak'"):
        sql("CREATE TABLE zz_tg_bak AS SELECT * FROM user_privileges WHERE group_id BETWEEN 906 AND 913")
    cat = sql("SELECT cat_id FROM menu_list WHERE id = 253")
    for tg, dg in MAP.items():
        urls = []
        for u in DEV[dg]:
            if u.startswith('MISSING:'): continue
            if u.startswith('AMBIG:'): urls += u.split(':', 2)[2].split('|')
            else: urls.append(u)
        ids = []
        for u in dict.fromkeys(urls):
            i = sql(f"SELECT MIN(id) FROM menu_list WHERE url = '{u}'")
            if i in ('', 'NULL'):
                sql(f"INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES ({cat}, 'ZZSCAN {u}', 1, 0, '{u}', 999, 1)")
                i = sql(f"SELECT MAX(id) FROM menu_list WHERE url = '{u}'")
            ids.append(i)
        sql(f"DELETE FROM user_privileges WHERE group_id = {tg}")
        sql(f"INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES " + ','.join(f"({tg}, 1, {i})" for i in ids))
        print(f'test group {tg} mirrors dev group {dg}: {len(ids)} menus')
else:
    sql("DELETE FROM user_privileges WHERE group_id BETWEEN 906 AND 913")
    sql("INSERT INTO user_privileges SELECT * FROM zz_tg_bak")
    sql("DROP TABLE zz_tg_bak")
    sql("DELETE FROM menu_list WHERE name LIKE 'ZZSCAN %'")
    print('test groups restored:', sql("SELECT COUNT(*) FROM user_privileges WHERE group_id BETWEEN 906 AND 913"), 'grants')
