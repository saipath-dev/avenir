#!/usr/bin/env python3
"""tf-bankrec-r3 — the test data of the isolated instance before r3, and how to put it back.

  The r3 verification changes only TEST data on the test-bed database: the reconciliation tables (brc_*), the dummy ledger
  rows 99980000–99989999 (including the dummy posting entry 99980399 it adds), the TEST bank accounts / TEST ledger accounts,
  the reconciliation settings in tpd_meta, and it adds one TEST login (TestRecDual) with its TEST group.
  The audit trail (tpd_audit) is append-only and is kept; a row records the reset.

    python3 r3_db.py snapshot-sql <outdir on the MySQL server, e.g. c:/wamp64/tmp/brc_r3_backup_20261012> <columns.json>
        prints the SELECT … INTO DUMPFILE statements (one file d_<table>.sql per part) — run in phpMyAdmin
    python3 r3_db.py reset-sql <snapshot dir> <database name>
        prints reset-to-before-r3.sql built from those files (guarded: that database only)
  columns.json: {table: [column, …]} as on the target database (information_schema.COLUMNS, ORDINAL_POSITION order).
"""
import json, os, re, sys

PARTS = [  # (table, where, order) — what the r3 verification may change; nothing else is touched by the reset
    ('brc_account_map', '', 'id'), ('brc_statement', '', 'id'), ('brc_line', '', 'id'), ('brc_fx_rate', '', 'id'),
    ('brc_match', '', 'id'), ('brc_match_item', '', 'id'), ('brc_mark', '', 'id'), ('brc_mark_event', '', 'id'),
    ('brc_claim', '', 'item_key'), ('brc_transfer', '', 'id'), ('brc_opening', '', 'id'), ('brc_correction', '', 'id'),
    ('brc_day', '', 'id'),
    ('account_runing', 'WHERE ID BETWEEN 99980000 AND 99989999', 'ID, L_Code, Dr, Cr'),
    ('tpd_bank_account', "WHERE label LIKE 'TEST % account (dummy)'", 'id'),
    ('account_posting', "WHERE L_Code IN ('1-6-8-29901','1-6-8-29902','1-6-8-29903','1-6-8-29904','2-9-99-29909')", 'L_Code'),
    ('tpd_meta', "WHERE k LIKE 'brc%'", 'k'),
]
DUAL_GROUP = 'TEST Reconciliation preparer + reviewer (test)'
DUAL_USER = 'TestRecDual'

def row_expr(t, cols):
    return "CONCAT('INSERT INTO `%s` (%s) VALUES (', %s, ');')" % (t, ', '.join('`' + c + '`' for c in cols), ", ', ', ".join('QUOTE(`' + c + '`)' for c in cols))

def snapshot_query(t, cols, where, order, outfile=None):
    q = "SELECT COALESCE(GROUP_CONCAT(%s ORDER BY %s SEPARATOR '\\n'), '') FROM `%s` %s" % (row_expr(t, cols), order, t, where)
    return q + (" INTO DUMPFILE '%s'" % outfile if outfile else '')

def snapshot_sql(outdir, columns):
    out = ['-- tf-bankrec-r3: back up the reconciliation TEST data of the isolated test bed before r3 (one file per part, written by',
           '-- the MySQL server into ' + outdir + '/). Read only: nothing in the database is changed. The folder must exist and be empty.',
           'SET SESSION group_concat_max_len = 67108864;']
    for t, where, order in PARTS:
        out.append(snapshot_query(t, columns[t], where, order, outdir.rstrip('/') + '/d_' + t + '.sql') + ';')
    return '\n'.join(out) + '\n'

def reset_sql(snapdir, db):
    files = {t: open(os.path.join(snapdir, 'd_' + t + '.sql'), encoding='utf-8').read().strip() for t, _, _ in PARTS}
    import hashlib
    sums = ', '.join(t + ' ' + hashlib.md5(open(os.path.join(snapdir, 'd_' + t + '.sql'), 'rb').read()).hexdigest()[:12] for t, _, _ in PARTS)
    s = ['-- tf-bankrec-r3: put the reconciliation TEST data of the isolated test bed back exactly as it was before the r3 verification',
         '-- (snapshot sql/before-r3-snapshot/, taken from ' + db + ' before r3 was installed). ISOLATED TEST BED ONLY.',
         '-- Use together with: python apply.py rollback <avenir_rv root> (r3 made no schema change; schema stays at version 2).',
         '-- Replaces only: the brc_ tables\' rows, the dummy ledger rows 99980000-99989999 (the dummy posting entry included), the TEST bank',
         '-- accounts and TEST ledger accounts, the brc_ settings in tpd_meta; removes the TEST login ' + DUAL_USER + ' and its TEST group.',
         '-- The audit trail is kept (append-only); a row records this reset.',
         '-- Snapshot md5 (first 12): ' + sums, '',
         '-- Guard: stops (error 1048) unless this is the isolated test-bed database at schema version 2.',
         'DROP TEMPORARY TABLE IF EXISTS brc_r3_guard;', 'CREATE TEMPORARY TABLE brc_r3_guard (ok TINYINT NOT NULL);',
         "INSERT INTO brc_r3_guard (ok) VALUES (IF(DATABASE() = '%s', 1, NULL));" % db,
         "INSERT INTO brc_r3_guard (ok) VALUES (IF((SELECT v FROM tpd_meta WHERE k = 'brc_schema_version') = '2', 1, NULL));", '']
    for t, where, _ in PARTS:
        s.append('DELETE FROM `%s` %s;' % (t, where))
        if files[t]:
            s.append(files[t])
    s += ["-- the TEST login added for the dual-role test",
          "DELETE FROM user_privileges WHERE group_id IN (SELECT id FROM (SELECT id FROM group_profile WHERE type = '%s') g);" % DUAL_GROUP,
          "DELETE FROM user_acc_tbl WHERE user = '%s';" % DUAL_USER,
          "DELETE FROM employee WHERE Name = 'TEST Reconciliation dual-role tester (test)';",
          "DELETE FROM group_profile WHERE type = '%s';" % DUAL_GROUP,
          "INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, reason) SELECT DATE_FORMAT(NOW(3), '%Y-%m-%d %H:%i:%s.%f'), 'claude-technical (reset script run in phpMyAdmin; isolated test instance)', 'brc_setup', 'brc_setup', 'reset to before r3', 'tf-bankrec-r3 verification data put back on the isolated test instance' FROM DUAL;",
          'DROP TEMPORARY TABLE IF EXISTS brc_r3_guard;',
          "SELECT (SELECT COUNT(*) FROM brc_day) AS daily_records, (SELECT COUNT(*) FROM brc_correction) AS corrections, (SELECT COUNT(*) FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999) AS dummy_rows, (SELECT COUNT(*) FROM user_acc_tbl WHERE user = '%s') AS dual_login;" % DUAL_USER]
    return '\n'.join(s) + '\n'

if __name__ == '__main__':
    if len(sys.argv) == 4 and sys.argv[1] == 'snapshot-sql':
        print(snapshot_sql(sys.argv[2], json.load(open(sys.argv[3]))), end='')
    elif len(sys.argv) == 4 and sys.argv[1] == 'reset-sql':
        print(reset_sql(sys.argv[2], sys.argv[3]), end='')
    else:
        print(__doc__); sys.exit(2)
