#!/usr/bin/env python3
"""tf-bankrec-r2 — Daily Bank Reconciliation r2 (D9, D10, two users at once, re-review flags, pending postings),
12 Oct 2026. APPROVED FOR THE ISOLATED TEST INSTANCE ONLY (http://localhost/avenir_rv/). Installs over tf-bankrec-r1.

   11 files changed (r1 → r2): app/Config/Routes.php (3 routes added to the r1 group), the controller, 5 libraries and
   4 views of accounts/daily_bank_rec. Access.php is unchanged. No file is added.
   Database: sql/upgrade-v2-test-bed.sql (phpMyAdmin, test-bed database selected) — 2 tables and columns added to the
   brc_ tables; no other table altered. Nothing writes to the ledger (account_runing) or its reconciliation flags.

    python3 apply.py check    <avenir_rv root>   what would change (nothing is written)
    python3 apply.py apply    <avenir_rv root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir_rv root>   put back the r1 files of the newest apply of that copy

   Guard: the target's app/Config/App.php must have the isolated instance's base URL (…/avenir_rv/). The development
   system (…/avenir/) and live are refused: installing there needs a separate approval and a separate kit decision.
   Every changed file must be exactly the r1 file it replaces (or already r2); anything else stops before writing.
"""
import hashlib, json, os, re, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
R1 = {   # the tf-bankrec-r1 files as installed (kit md5) — what r2 replaces
    'app/Config/Routes.php': '921c2ea6c5b994b70c3cf4d65ab33d37',
    'app/Controllers/Accounts/DailyBankRecController.php': '0d96e3038a50d9fbc4fcbe717319aab5',
    'app/Libraries/BankRec/Access.php': '660e4f1840873fcc1497bd10c0a3b0a4',
    'app/Libraries/BankRec/Corrections.php': 'ca42191c995b2045aecbb7da674f1e00',
    'app/Libraries/BankRec/Matching.php': 'f8d46c37e84c5db7164e285ad5cb13ce',
    'app/Libraries/BankRec/Rec.php': 'ed6df57705123a5a29cdd0365a47ad8a',
    'app/Libraries/BankRec/Schema.php': 'd321e7bba1c6bf61585211efc244f278',
    'app/Libraries/BankRec/Statements.php': '72c4017ce254c2d089d7a16bd26c9ff4',
    'app/Views/pages/accounts/bankrec/_common.php': 'bc00c3eccad472ce1c90527997eee521',
    'app/Views/pages/accounts/bankrec/account.php': '17996f0be6cb7319f60ad160ab83b14a',
    'app/Views/pages/accounts/bankrec/fx.php': '195f8a0f99e329df87373aef74e5fdf5',
    'app/Views/pages/accounts/bankrec/index.php': '320355c140da3604cb80ad2b3906c2f1',
}
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def files(): return sorted(os.path.relpath(os.path.join(d, f), SRC).replace('\\', '/') for d, _, fs in os.walk(SRC) for f in fs)
def copy_name(p): return os.path.basename(os.path.normpath(str(p).strip().replace('\\', '/'))).lower()
def is_r1(rel, h): return h == R1.get(rel)
def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback'):
        print(__doc__); sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    app = os.path.join(root, 'app', 'Config', 'App.php')
    if not os.path.isfile(os.path.join(root, 'app', 'Config', 'Routes.php')) or not os.path.isfile(app):
        raise SystemExit('Not an Avenir root: ' + root)
    m = re.search(r"public\s+string\s+\$baseURL\s*=\s*'([^']*)'", open(app, encoding='utf-8', errors='replace').read())
    base = m.group(1) if m else ''
    if '/avenir_rv/' not in base:
        raise SystemExit('Refused: ' + root + ' has the base URL "' + base + '", not the isolated test instance (…/avenir_rv/). '
                         'tf-bankrec-r2 is approved for the isolated test instance only.')
    stamp = time.strftime('%Y%m%d-%H%M%S'); rep = {'mode': mode, 'root': root, 'base_url': base, 'files': []}
    if mode == 'rollback':
        bdir = os.path.join(KIT, 'before')
        mine = sorted(b for b in (os.listdir(bdir) if os.path.isdir(bdir) else [])
                      if os.path.isfile(os.path.join(bdir, b, 'apply.json')) and os.path.isfile(os.path.join(bdir, b, 'root.txt'))
                      and copy_name(open(os.path.join(bdir, b, 'root.txt'), encoding='utf-8').read()) == copy_name(root))
        if not mine:
            raise SystemExit('No apply of ' + root + ' left to undo in ' + bdir)
        last = os.path.join(bdir, mine[-1]); info = json.load(open(os.path.join(last, 'apply.json'), encoding='utf-8'))
        for rel in info['changed']:
            shutil.copy2(os.path.join(last, 'files', rel), os.path.join(root, rel)); rep['files'].append(rel + ' restored (' + md5(os.path.join(root, rel)) + ')')
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]
        rep['note'] = ('The r1 files are back. The database stays at schema version 2 until sql/rollback-schema-v2-to-v1.sql (or '
                       'sql/reset-demo-to-r1-snapshot.sql) is run in phpMyAdmin on the test-bed database; r1 pages refuse nothing at version 2.')
        print(json.dumps(rep, indent=1)); return
    changed, added, same, drifted = [], [], [], []
    for rel in files():
        dst = os.path.join(root, rel)
        if not os.path.exists(dst):
            added.append(rel)
        elif md5(dst) == md5(os.path.join(SRC, rel)):
            same.append(rel)
        elif not is_r1(rel, md5(dst)):
            drifted.append(rel + ' (' + md5(dst) + ')')
        else:
            changed.append(rel)
    rep.update(changed=changed, added=added, same=same, not_r1=drifted)
    if drifted or added:
        rep['note'] = 'Stopped: these files are not the tf-bankrec-r1 files r2 replaces (or are missing); nothing was written.'
        print(json.dumps(rep, indent=1)); sys.exit(1)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    if not changed:
        rep['note'] = 'Already in; nothing to do.'; print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp)
    n = 1
    while os.path.exists(bdir):
        n += 1; bdir = os.path.join(KIT, 'before', stamp + '-' + str(n))
    os.makedirs(bdir)
    for rel in changed:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True)
        shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    for rel in changed:
        shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    bad = [rel for rel in changed if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    json.dump({'changed': changed, 'added': [], 'stamp': stamp, 'md5': {rel: md5(os.path.join(SRC, rel)) for rel in changed}}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root)
    rep['backup'] = bdir; rep['verified'] = not bad; rep['mismatch'] = bad
    rep['next'] = 'Run sql/upgrade-v2-test-bed.sql in phpMyAdmin with the test-bed database selected (the pages say "not set up" until then).'
    print(json.dumps(rep, indent=1))
    if bad:
        sys.exit(1)

if __name__ == '__main__':
    main()
