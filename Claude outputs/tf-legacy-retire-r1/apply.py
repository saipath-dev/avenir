#!/usr/bin/env python3
"""tf-legacy-retire-r1 — retires two old one-off tools, 12 Oct 2026. Approved by Shantha for the DEVELOPMENT system only
(tested first on the cloud copy and the isolated test instance). Live is refused.

   "Bank Not Reconciliation Record Insert"  (Settings → Manual Update; POST settings/manual_update/bank_recon_record_insert)
   "Bank Req Data Transfer"                 (Accounts → Bank Req Data Transfer; POST accounts/bank_unrealised_value/process)

   5 files changed, no database or schema change, no route or menu change:
     app/Libraries/BankRec/LegacyGuard.php                         + refuseRetiredTool(): security token first (403 when
                                                                     missing or wrong), then the retirement (409); every
                                                                     attempt audited (tpd_audit, legacy_tool_refused)
     app/Controllers/Settings/ManualUpdateController.php           bank_recon_record_insert() refuses before anything else
     app/Controllers/Accounts/BankUnrealisedValueController.php    process_unrealised_value_change() refuses likewise
     app/Views/pages/settings/manual_update.php                    that one button → a grey "Retired" notice
     app/Views/pages/accounts/bank_unrealised_value_change.php     the Process button → a grey "Retired" notice
   All other Manual Update buttons, their routes and their code are unchanged.

    python3 apply.py check    <avenir root>   what would change (nothing is written)
    python3 apply.py apply    <avenir root>   back up the 5 files to before/<time>/files/, copy the new ones, verify by md5
    python3 apply.py rollback <avenir root>   put back the 5 files of the newest apply and verify them against the originals

   Guard: the target's app/Config/App.php must have the base URL http://localhost/avenir/ (development) or
   http://localhost/avenir_rv/ (the isolated test instance); anything else, live included, is refused. Each of the 5 files
   must be exactly the original (or already this kit's file); anything else stops before writing.
"""
import hashlib, json, os, re, shutil, sys, time
KIT = os.path.dirname(os.path.abspath(__file__)); SRC = os.path.join(KIT, 'src')
ORIGINAL = {   # the files this kit replaces, as installed on development and the isolated instance on 12 Oct 2026 (md5)
    'app/Libraries/BankRec/LegacyGuard.php': '1d4cca93bf1f0ade3aafb99a793a421d',
    'app/Controllers/Settings/ManualUpdateController.php': 'd67ac130c88044ce95534b547fff2115',
    'app/Controllers/Accounts/BankUnrealisedValueController.php': '280eb40fa6eae1137f4150514bd72d2d',
    'app/Views/pages/settings/manual_update.php': '4f52f9b384b3df1155e974eb17738a8c',
    'app/Views/pages/accounts/bank_unrealised_value_change.php': '3b8085088696425f803adcc664fe3cd8',
}
REQUIRES = {   # unchanged files this kit relies on (md5)
    'app/Libraries/TourPayments/Audit.php': 'f136ad5cd36208a7ce1a63a0fecbf2fd',
}
ROUTES = ["'bank_recon_record_insert', [ManualUpdateController::class, 'bank_recon_record_insert']",
          "'process', [BankUnrealisedValueController::class, 'process_unrealised_value_change']"]
BASES = {'http://localhost/avenir/': 'development', 'http://localhost/avenir_rv/': 'isolated test instance'}
def md5(p): return hashlib.md5(open(p, 'rb').read()).hexdigest()
def copy_name(p): return os.path.normcase(os.path.normpath(str(p).strip().replace('\\', '/')))

def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback'):
        print(__doc__); sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    app = os.path.join(root, 'app', 'Config', 'App.php'); routes = os.path.join(root, 'app', 'Config', 'Routes.php')
    if not os.path.isfile(routes) or not os.path.isfile(app):
        raise SystemExit('Not an Avenir root: ' + root)
    m = re.search(r"public\s+string\s+\$baseURL\s*=\s*'([^']*)'", open(app, encoding='utf-8', errors='replace').read())
    base = m.group(1) if m else ''
    if base not in BASES:
        raise SystemExit('Refused: ' + root + ' has the base URL "' + base + '". This kit is approved for development '
                         '(http://localhost/avenir/) and the isolated test instance (http://localhost/avenir_rv/) only.')
    stamp = time.strftime('%Y%m%d-%H%M%S'); rep = {'mode': mode, 'root': root, 'target': BASES[base], 'files': []}
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
        bad = []
        for rel in ORIGINAL:
            h = md5(os.path.join(root, rel)); ok = h == ORIGINAL[rel]
            rep['files'].append(rel + ' ' + h + (' = original' if ok else ' NOT the original'))
            if not ok:
                bad.append(rel)
        os.rename(os.path.join(last, 'apply.json'), os.path.join(last, 'apply.rolled-back-' + stamp + '.json'))
        rep['undone'] = mine[-1]; rep['all_five_original'] = not bad
        print(json.dumps(rep, indent=1)); sys.exit(1 if bad else 0)
    changed, same, drifted, missing = [], [], [], []
    for rel, h in REQUIRES.items():
        p = os.path.join(root, rel)
        if not os.path.isfile(p) or md5(p) != h:
            missing.append(rel + (' (' + md5(p) + ')' if os.path.isfile(p) else ' (missing)'))
    rtext = open(routes, encoding='utf-8', errors='replace').read()
    missing += ['route ' + r for r in ROUTES if r not in rtext]
    for rel in ORIGINAL:
        dst = os.path.join(root, rel)
        if not os.path.isfile(dst):
            drifted.append(rel + ' (missing)')
        elif md5(dst) == md5(os.path.join(SRC, rel)):
            same.append(rel)
        elif md5(dst) != ORIGINAL[rel]:
            drifted.append(rel + ' (' + md5(dst) + ')')
        else:
            changed.append(rel)
    rep.update(changed=changed, already_in=same, not_original=drifted, requirements_missing=missing)
    if drifted or missing:
        rep['note'] = 'Stopped: the target does not have the expected files; nothing was written.'
        print(json.dumps(rep, indent=1)); sys.exit(1)
    if mode == 'check':
        print(json.dumps(rep, indent=1)); return
    if not changed:
        rep['note'] = 'Already in; nothing to do.'; print(json.dumps(rep, indent=1)); return
    bdir = os.path.join(KIT, 'before', stamp); n = 1
    while os.path.exists(bdir):
        n += 1; bdir = os.path.join(KIT, 'before', stamp + '-' + str(n))
    os.makedirs(bdir)
    for rel in changed:
        os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True)
        shutil.copy2(os.path.join(root, rel), os.path.join(bdir, 'files', rel))
    bad_backup = [rel for rel in changed if md5(os.path.join(bdir, 'files', rel)) != ORIGINAL[rel]]
    if bad_backup:
        raise SystemExit('Backup copy does not match the original: ' + ', '.join(bad_backup) + ' — nothing was changed.')
    open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root)
    json.dump({'changed': changed, 'stamp': stamp, 'state': 'copying'}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    for rel in changed:
        shutil.copy2(os.path.join(SRC, rel), os.path.join(root, rel))
    bad = [rel for rel in changed if md5(os.path.join(root, rel)) != md5(os.path.join(SRC, rel))]
    json.dump({'changed': changed, 'stamp': stamp, 'md5_new': {rel: md5(os.path.join(SRC, rel)) for rel in changed},
               'md5_original': {rel: ORIGINAL[rel] for rel in changed}}, open(os.path.join(bdir, 'apply.json'), 'w', encoding='utf-8'), indent=1)
    rep['backup'] = bdir; rep['verified'] = not bad; rep['mismatch'] = bad
    print(json.dumps(rep, indent=1))
    if bad:
        sys.exit(1)

if __name__ == '__main__':
    main()
