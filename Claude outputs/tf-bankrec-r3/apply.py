#!/usr/bin/env python3
"""tf-bankrec-r3 — Daily Bank Reconciliation r3 (D7 guard on the old screen, pending amounts per day, verify again),
12 Oct 2026. FOR THE ISOLATED TEST INSTANCE ONLY (http://localhost/avenir_rv/). Installs over tf-bankrec-r2.

   8 files changed (r2 → r3) and 1 added:
     app/Config/Routes.php                                      +1 route (correction/reverify) in the r1 group
     app/Controllers/Accounts/DailyBankRecController.php        verify again; old-screen flag count on the overview
     app/Controllers/Accounts/BankReconsillationController.php  the OLD screen: 6 write actions ask the guard first
     app/Libraries/BankRec/Rec.php                              pending amounts up to the day; old-screen flag warning
     app/Libraries/BankRec/Corrections.php                      verify again (reverify)
     app/Views/pages/accounts/bankrec/account.php, index.php    banners, "Verify again", "withdrawn by"
     app/Views/pages/accounts/bank_reconcillation.php           the OLD screen shows the server's reason
     app/Libraries/BankRec/LegacyGuard.php                      (added) the D7 guard — refuses only, never changes a flag
   No database change (schema stays at version 2). Nothing writes to the ledger (account_runing) or its flags.

    python3 apply.py check    <avenir_rv root>   what would change (nothing is written)
    python3 apply.py apply    <avenir_rv root>   back up every file it changes to before/<time>/, then copy the files
    python3 apply.py rollback <avenir_rv root>   put back the r2 files of the newest apply of that copy and take the
                                                 added LegacyGuard.php out (deleted, or moved into before/<time>/ when
                                                 deleting is not allowed, or renamed *.r3-removed as a last resort)

   Guard: the target's app/Config/App.php must have the isolated instance's base URL (…/avenir_rv/). The development
   system (…/avenir/) and live are refused: installing there needs a separate approval and a separate kit decision.
   Every changed file must be exactly the r2 file it replaces (or already r3), and the r2 files r3 relies on must be
   present unchanged; anything else stops before writing.
"""
import hashlib, json, os, re, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
R2 = {   # the files as they are with tf-bankrec-r2 installed on the isolated instance (md5) — what r3 replaces
    'app/Config/Routes.php': 'baaa8be478019abcff872213e554de7e',
    'app/Controllers/Accounts/DailyBankRecController.php': 'cbf33c97614279f166c06bddefe41979',
    'app/Controllers/Accounts/BankReconsillationController.php': '0fdfc868c73756ca9e91a6fff9c7779b',
    'app/Libraries/BankRec/Rec.php': 'ff231e0fd7caf135487409eac8a3827e',
    'app/Libraries/BankRec/Corrections.php': 'a459c553c078cfc7d30528d925f0cc05',
    'app/Views/pages/accounts/bankrec/account.php': '1c70554f2100736b3d5934bba9b2ba1e',
    'app/Views/pages/accounts/bankrec/index.php': '2b56270e81eb4273375740880a99f886',
    'app/Views/pages/accounts/bank_reconcillation.php': 'b60efa2f9f2b3ff2a5271e57528711c0',
}
ADDED = ['app/Libraries/BankRec/LegacyGuard.php']
REQUIRES = {   # r2 files r3 relies on and does not change
    'app/Libraries/BankRec/Access.php': '660e4f1840873fcc1497bd10c0a3b0a4',
    'app/Libraries/BankRec/Matching.php': '9f481901a738eacb7c526bf863716576',
    'app/Libraries/BankRec/Schema.php': '1e6fc0535e01ed4f949169d77391059b',
    'app/Libraries/BankRec/Statements.php': '31b0ac8c6431873b14322499195ce3fb',
    'app/Views/pages/accounts/bankrec/_common.php': 'bbc382935ff7aec0fd5896cb40756ef1',
    'app/Views/pages/accounts/bankrec/fx.php': '259b86839fd9b07036bd0a7b5f517479',
}
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def files(): return sorted(os.path.relpath(os.path.join(d, f), SRC).replace('\\', '/') for d, _, fs in os.walk(SRC) for f in fs)
def copy_name(p): return os.path.basename(os.path.normpath(str(p).strip().replace('\\', '/'))).lower()

def take_out(path, keep_dir, stamp):
    """Remove an added file: delete it; if deleting is not allowed, move it into the backup folder; last resort: rename it
    so that it is never loaded (not *.php)."""
    try:
        os.remove(path); return 'deleted'
    except OSError:
        pass
    try:
        os.makedirs(keep_dir, exist_ok=True)
        os.rename(path, os.path.join(keep_dir, os.path.basename(path))); return 'moved to ' + keep_dir
    except OSError:
        pass
    alt = path + '.r3-removed-' + stamp
    os.rename(path, alt); return 'renamed to ' + os.path.basename(alt) + ' (never loaded)'

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
                         'tf-bankrec-r3 is for the isolated test instance only.')
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
            shutil.copy2(os.path.join(last, 'files', rel), os.path.join(root, rel))
            rep['files'].append(rel + ' restored (' + md5(os.path.join(root, rel)) + (', the r2 file' if md5(os.path.join(root, rel)) == R2.get(rel) else ', NOT the r2 file') + ')')
        for rel in info.get('added', []):
            p = os.path.join(root, rel)
            if os.path.exists(p):
                rep['files'].append(rel + ' ' + take_out(p, os.path.join(last, 'added-removed', os.path.dirname(rel)), stamp))
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]
        bad = [rel for rel in R2 if md5(os.path.join(root, rel)) != R2[rel]] + [rel for rel in ADDED if os.path.exists(os.path.join(root, rel))]
        rep['back_to_r2'] = not bad
        rep['note'] = ('The r2 files are back. r3 made no database change, so none is needed for the code; the test data of the r3 '
                       'verification is put back separately with sql/reset-to-before-r3.sql (phpMyAdmin, test-bed database).')
        print(json.dumps(rep, indent=1)); sys.exit(1 if bad else 0)
    changed, added, same, drifted, missing_req = [], [], [], [], []
    for rel, h in REQUIRES.items():
        p = os.path.join(root, rel)
        if not os.path.isfile(p) or md5(p) != h:
            missing_req.append(rel + (' (' + md5(p) + ')' if os.path.isfile(p) else ' (missing)'))
    for rel in files():
        dst = os.path.join(root, rel)
        if not os.path.exists(dst):
            (added if rel in ADDED else drifted).append(rel if rel in ADDED else rel + ' (missing)')
        elif md5(dst) == md5(os.path.join(SRC, rel)):
            same.append(rel)
        elif rel in ADDED or md5(dst) != R2.get(rel):
            drifted.append(rel + ' (' + md5(dst) + ')')
        else:
            changed.append(rel)
    rep.update(changed=changed, added=added, same=same, not_r2=drifted, r2_files_required_but_different=missing_req)
    if drifted or missing_req:
        rep['note'] = 'Stopped: the target is not tf-bankrec-r2 as installed on the isolated instance; nothing was written.'
        print(json.dumps(rep, indent=1)); sys.exit(1)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    if not changed and not added:
        rep['note'] = 'Already in; nothing to do.'; print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp)
    n = 1
    while os.path.exists(bdir):
        n += 1; bdir = os.path.join(KIT, 'before', stamp + '-' + str(n))
    os.makedirs(bdir)
    for rel in changed:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True)
        shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    json.dump({'changed': changed, 'added': added, 'stamp': stamp, 'state': 'copying'}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root)
    for rel in changed + added:
        os.makedirs(os.path.dirname(os.path.join(root, rel)), exist_ok=True)
        shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    bad = [rel for rel in changed + added if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    json.dump({'changed': changed, 'added': added, 'stamp': stamp, 'md5': {rel: md5(os.path.join(SRC, rel)) for rel in changed + added}}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    rep['backup'] = bdir; rep['verified'] = not bad; rep['mismatch'] = bad
    rep['next'] = 'No database step. Open Accounts → Daily Bank Reconciliation and the old Bank Reconciliation screen; run tests/rv_test_r3.js.'
    print(json.dumps(rep, indent=1))
    if bad:
        sys.exit(1)

if __name__ == '__main__':
    main()
