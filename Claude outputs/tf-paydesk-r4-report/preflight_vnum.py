#!/usr/bin/env python3
"""tf-paydesk-r4 — LIVE PREFLIGHT runner (READ-ONLY). Runs preflight_vnum.sql against the database named in the
application's .env (database.default.*), printing the findings. The password is read from .env by this script and
never printed, logged or written anywhere. Only SELECT / SHOW statements are sent.

    python3 preflight_vnum.py /path/to/avenir/.env [/path/to/preflight_vnum.sql]

Needs the mysql client on the PATH, or the PyMySQL module (pip install pymysql)."""
import os, re, sys, subprocess, shutil, json, datetime

env_path = sys.argv[1] if len(sys.argv) > 1 else '.env'
sql_path = sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(os.path.abspath(__file__)), 'preflight_vnum.sql')
cfg = {}
for line in open(env_path, encoding='utf-8', errors='ignore'):
    m = re.match(r"\s*database\.default\.(\w+)\s*=\s*(.*)$", line)
    if m:
        v = m.group(2).strip()
        if len(v) >= 2 and v[0] == v[-1] and v[0] in "'\"":
            v = v[1:-1]
        cfg[m.group(1)] = v
host, db, user, pw = cfg.get('hostname', '127.0.0.1'), cfg.get('database', ''), cfg.get('username', ''), cfg.get('password', '')
port = int(cfg.get('port') or 3306)
print('preflight', datetime.datetime.now().isoformat(timespec='seconds'), '| host', host, '| port', port, '| database', db, '| user', user, '| password: read from .env, not shown')
stmts = [s.strip() for s in re.sub(r'--[^\n]*', '', open(sql_path, encoding='utf-8').read()).split(';') if s.strip()]
for s in stmts:
    if not re.match(r'^(SELECT|SHOW)\b', s, re.I):
        raise SystemExit('refusing a non-read statement: ' + s[:80])
mysql = shutil.which('mysql')
rows_out = []
if mysql:
    env = dict(os.environ, MYSQL_PWD=pw)
    for s in stmts:
        r = subprocess.run([mysql, '-h', host, '-P', str(port), '-u', user, '--default-character-set=utf8mb4', '--batch', db, '-e', s], capture_output=True, text=True, env=env)
        print('\n>>', s[:160].replace('\n', ' '))
        print(r.stdout.strip() if r.returncode == 0 else 'ERROR ' + r.stderr.strip()[:300])
else:
    try:
        import pymysql
    except ImportError:
        raise SystemExit('neither the mysql client nor PyMySQL is available here')
    con = pymysql.connect(host=host, port=port, user=user, password=pw, database=db, charset='utf8mb4', cursorclass=pymysql.cursors.DictCursor, read_timeout=60)
    with con.cursor() as cur:
        for s in stmts:
            print('\n>>', s[:160].replace('\n', ' '))
            try:
                cur.execute(s)
                rows = cur.fetchall()
                if not rows:
                    print('(no rows)')
                else:
                    print('\t'.join(rows[0].keys()))
                    for r in rows[:60]:
                        print('\t'.join('' if v is None else str(v) for v in r.values()))
            except Exception as e:
                print('ERROR', str(e)[:300])
    con.close()
