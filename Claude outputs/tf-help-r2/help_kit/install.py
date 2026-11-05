#!/usr/bin/env python3
"""tf-help-r2 — install / update / roll back the in-system User Manual on an Avenir copy.

    python3 install.py check       <avenir root>   report only: what would change, and whether it is already in
    python3 install.py apply       <avenir root>   copy the files, patch Routes.php and Filters.php (backups first).
                                                    Run again over an installed copy to update it: the files it
                                                    replaces are kept in updates/<time>/files/
    python3 install.py undo-update <avenir root>   go back to the version before the last update (r2 -> r1)
    python3 install.py rollback    <avenir root>   take the help out completely: the two patched files back and
                                                    every help file moved aside

New files (nothing existing is replaced):
  app/Help/…, app/Libraries/Help/…, app/Controllers/HelpController.php, app/Filters/HelpWidgetFilter.php,
  app/Commands/Help/Check.php, app/Config/Routes.help.php, app/Views/help/…, public/assets/help/…
Two existing files get small marked additions:
  app/Config/Routes.php   one line:  require __DIR__ . '/Routes.help.php';
  app/Config/Filters.php  the HelpWidgetFilter alias, the filter in globals 'after', and 'help', 'help/*' on
                          UserPermissionFilter's exception list (help is for everyone; it is read-only)
"""
import hashlib, json, os, shutil, sys, time

KIT = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(KIT, 'src')
MARK = 'tf-help-r1'
ROUTES_LINE = "require __DIR__ . '/Routes.help.php'; // " + MARK + ": the in-system User Manual\n"


def files():
    out = []
    for d, _, fs in os.walk(SRC):
        for f in fs:
            out.append(os.path.relpath(os.path.join(d, f), SRC))
    return sorted(out)


def same_copy(a, b):
    """Is a recorded root the same Avenir copy as b? Compared by folder name (avenir, avenir_mailtest…): the
    full path differs between the Windows side and each Linux session that mounts it."""
    name = lambda x: os.path.basename(os.path.normpath(str(x or '').replace('\\', '/'))).lower()
    return bool(a) and name(a) == name(b)


def md5(p):
    return hashlib.md5(open(p, 'rb').read()).hexdigest()


def patch_filters(text):
    """Return the patched Filters.php text (idempotent; keeps the file's line endings)."""
    if MARK in text:
        return text
    crlf = '\r\n' in text
    text = text.replace('\r\n', '\n')
    a = "'corsFilter' => \\App\\Filters\\CorsFilter::class,"
    if a not in text:
        raise SystemExit('Filters.php: alias anchor not found')
    text = text.replace(a, a + "\n        'HelpWidgetFilter' => \\App\\Filters\\HelpWidgetFilter::class, // " + MARK, 1)
    b = "'UserPermissionFilter' => ['except' => ['api/*', '/', 'login', 'logout', '403', "
    if b not in text:
        raise SystemExit('Filters.php: UserPermissionFilter anchor not found')
    text = text.replace(b, b + "'help', 'help/*', ", 1)
    c = "        'after' => [\n            'toolbar',"
    if c not in text:
        raise SystemExit("Filters.php: globals 'after' anchor not found")
    text = text.replace(c, c + "\n            'HelpWidgetFilter' => ['except' => ['api/*', 'help', 'help/*', 'login', 'logout', 'driver/*', 'driver_portal', 'driver_portal/*', 'tour/print', 'tour/print/*']], // " + MARK, 1)
    return text.replace('\n', '\r\n') if crlf else text


def patch_routes(text):
    if 'Routes.help.php' in text:
        return text
    line = ROUTES_LINE.replace('\n', '\r\n') if '\r\n' in text else ROUTES_LINE
    anchor = "require __DIR__ . '/Routes.hotel_rate_v2.php';"
    if anchor in text:
        i = text.index(anchor) + len(anchor)
        nl = text.find('\n', i)
        nl = len(text) if nl == -1 else nl + 1
        return text[:nl] + line + text[nl:]
    return text.rstrip('\r\n') + ('\r\n' if '\r\n' in text else '\n') + line


def main():
    if len(sys.argv) != 3 or sys.argv[1] not in ('check', 'apply', 'rollback', 'undo-update'):
        print(__doc__)
        sys.exit(2)
    mode, root = sys.argv[1], os.path.abspath(sys.argv[2])
    if not os.path.isfile(os.path.join(root, 'app', 'Config', 'Routes.php')):
        raise SystemExit('Not an Avenir root: ' + root)
    routes = os.path.join(root, 'app', 'Config', 'Routes.php')
    filters = os.path.join(root, 'app', 'Config', 'Filters.php')
    stamp = time.strftime('%Y%m%d-%H%M%S')
    report = {'mode': mode, 'root': root, 'at': stamp, 'new_files': [], 'patched': []}

    if mode == 'undo-update':
        ud = os.path.join(KIT, 'updates')
        cands = sorted(d for d in (os.listdir(ud) if os.path.isdir(ud) else []) if os.path.isfile(os.path.join(ud, d, 'update.json')))
        cands = [d for d in cands if same_copy(json.load(open(os.path.join(ud, d, 'update.json'), encoding='utf-8')).get('root'), root)]
        if not cands:
            raise SystemExit('No update of ' + root + ' recorded in ' + ud)
        last = os.path.join(ud, cands[-1])
        info = json.load(open(os.path.join(last, 'update.json'), encoding='utf-8'))
        aside = os.path.join(root, 'writable', 'help_undone_' + stamp)
        for rel in info.get('replaced', []):
            src = os.path.join(last, 'files', rel)
            if os.path.isfile(src):
                shutil.copy2(src, os.path.join(root, rel))
                report['new_files'].append(rel + ' restored from ' + src)
        for rel in info.get('added', []):
            p = os.path.join(root, rel)
            if os.path.isfile(p):
                os.makedirs(os.path.dirname(os.path.join(aside, rel)), exist_ok=True)
                shutil.move(p, os.path.join(aside, rel))
                report['new_files'].append(rel + ' (new in that update) moved to ' + aside)
        os.rename(os.path.join(last, 'update.json'), os.path.join(last, 'update.undone-' + stamp + '.json'))
        print(json.dumps(report, indent=2))
        return

    if mode == 'rollback':
        bk = sorted([d for d in os.listdir(os.path.join(KIT, 'before')) if os.path.isdir(os.path.join(KIT, 'before', d))]) if os.path.isdir(os.path.join(KIT, 'before')) else []
        if not bk:
            raise SystemExit('No backup found in ' + os.path.join(KIT, 'before'))
        # the newest backup of THIS copy taken before the help was in (each backup names its copy in root.txt)
        def root_of(b):
            f = os.path.join(KIT, 'before', b, 'root.txt')
            return open(f, encoding='utf-8').read().strip() if os.path.isfile(f) else None
        clean = [b for b in bk if MARK not in open(os.path.join(KIT, 'before', b, 'Filters.php'), encoding='utf-8').read()]
        mine = [b for b in clean if same_copy(root_of(b), root)]
        if not mine and any(root_of(b) for b in clean):
            raise SystemExit('No backup of ' + root + ' found in ' + os.path.join(KIT, 'before') + ' (the backups there are of other copies)')
        clean = mine or clean
        if not clean:
            raise SystemExit('No backup without the help found in ' + os.path.join(KIT, 'before'))
        last = os.path.join(KIT, 'before', clean[-1])
        for name, dst in (('Routes.php', routes), ('Filters.php', filters)):
            src = os.path.join(last, name)
            if os.path.isfile(src):
                shutil.copy2(src, dst)
                report['patched'].append(dst + ' restored from ' + src)
        aside = os.path.join(root, 'writable', 'help_rolled_back_' + stamp)
        for rel in files():
            p = os.path.join(root, rel)
            if os.path.isfile(p):
                os.makedirs(os.path.dirname(os.path.join(aside, rel)), exist_ok=True)
                shutil.move(p, os.path.join(aside, rel))
                report['new_files'].append(rel + ' moved to ' + aside)
        print(json.dumps(report, indent=2))
        return

    new_routes = patch_routes(open(routes, encoding='utf-8').read())
    new_filters = patch_filters(open(filters, encoding='utf-8').read())
    for rel in files():
        dst = os.path.join(root, rel)
        state = 'new' if not os.path.exists(dst) else ('same' if md5(dst) == md5(os.path.join(SRC, rel)) else 'differs')
        report['new_files'].append({'file': rel, 'state': state})
    report['patched'] = [
        {'file': 'app/Config/Routes.php', 'change': 'already in' if 'Routes.help.php' in open(routes, encoding='utf-8').read() else 'adds one require line'},
        {'file': 'app/Config/Filters.php', 'change': 'already in' if MARK in open(filters, encoding='utf-8').read() else 'adds alias, after-filter, help on the permission exception list'},
    ]
    if mode == 'check':
        print(json.dumps(report, indent=2))
        return

    already = MARK in open(filters, encoding='utf-8').read() and 'Routes.help.php' in open(routes, encoding='utf-8').read()
    # a re-run (an update of the help files): the two patched files are not backed up again
    bdir = os.path.join(KIT, 'before' if not already else 'updates', stamp)
    os.makedirs(bdir, exist_ok=True)
    if not already:
        shutil.copy2(routes, os.path.join(bdir, 'Routes.php'))
        shutil.copy2(filters, os.path.join(bdir, 'Filters.php'))
        open(os.path.join(bdir, 'root.txt'), 'w', encoding='utf-8').write(root + '\n')
    replaced, added = [], []
    for rel in files():
        dst = os.path.join(root, rel)
        if os.path.exists(dst):
            if md5(dst) != md5(os.path.join(SRC, rel)):
                replaced.append(rel)
            os.makedirs(os.path.dirname(os.path.join(bdir, 'files', rel)), exist_ok=True)
            shutil.copy2(dst, os.path.join(bdir, 'files', rel))
        else:
            added.append(rel)
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.copy2(os.path.join(SRC, rel), dst)
    if already:
        # an update: what undo-update needs to go back to the previous version
        json.dump({'root': root, 'at': stamp, 'replaced': replaced, 'added': added}, open(os.path.join(bdir, 'update.json'), 'w', encoding='utf-8'), indent=1)
    report['replaced'] = replaced
    report['added'] = added
    open(routes, 'w', encoding='utf-8', newline='').write(new_routes)
    open(filters, 'w', encoding='utf-8', newline='').write(new_filters)
    report['backup'] = bdir
    print(json.dumps(report, indent=2))


if __name__ == '__main__':
    main()
