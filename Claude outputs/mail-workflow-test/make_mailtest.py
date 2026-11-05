#!/usr/bin/env python3
"""
make_mailtest.py — builds the separate TEST COPY of Avenir used for the one real e-mail workflow test.

  python3 make_mailtest.py <step> --src <live avenir folder> --dst <.../avenir_mailtest> [options]

  steps (run in this order; each can be run again and only finishes what is missing):
    check     what would be copied; refuses unless every guard below holds. Writes nothing.
    copy      the live CODE only (app, vendor, public without uploads, spark, preload.php, composer.json, .htaccess)
              into the test copy. Never the live .env, writable data, uploads, logs, backups or database dumps.
    overlay   the two reviewed release kits on top (tf-tour-booking-r1, then tf-mail-r1), each file checked against
              its kit's MANIFEST md5 before and after it is placed
    patch     the test copy's own settings: its address, its own cookie names, EVERY database connection forced to
              the database avenir_mailtest, a red "TEST COPY" bar on every page, the setup page and the dummy-data SQL
    manifest  an md5 of every code file of the test copy (writable/mailtest/MANIFEST.json), to compare with the build
              that was tested in the cloud

Guards: the destination folder must be named avenir_mailtest and must not be the live folder, inside it, or contain
it; nothing is ever opened for writing outside the destination. The live folder is only read.
Nothing here connects to a database, a mail server or the internet.
"""
import argparse
import base64
import hashlib
import json
import os
import re
import shutil
import sys
import time

TEST_DB = 'avenir_mailtest'
KITS = ['tf-tour-booking-r1', 'tf-mail-r1']
CODE_DIRS = ['app', 'vendor', 'public']
CODE_FILES = ['spark', 'preload.php', 'composer.json', '.htaccess']
PUBLIC_SKIP_DIRS = {'uploads', 'ci_sessions'}
PUBLIC_SKIP_FILES = {'sent_message_ids.txt', 'Thumbs.db', 'clear_cache.php'}
WRITABLE_DIRS = ['cache', 'logs', 'session', 'uploads', 'debugbar', 'mailtest']


def md5(p):
    h = hashlib.md5()
    with open(p, 'rb') as f:
        for b in iter(lambda: f.read(1 << 20), b''):
            h.update(b)
    return h.hexdigest()


class Builder:
    def __init__(self, a):
        self.src = os.path.realpath(a.src)
        self.dst = os.path.realpath(a.dst)
        self.public_src = os.path.realpath(a.public_src) if a.public_src else os.path.join(self.src, 'public')
        self.kits_root = os.path.realpath(a.kits_root) if a.kits_root else os.path.join(self.src, 'writable', 'tf_releases')
        self.overlay = os.path.realpath(a.overlay) if a.overlay else os.path.dirname(os.path.realpath(__file__))
        self.base_url = a.base_url
        self.db_host = a.db_host
        self.db_port = int(a.db_port)
        self.budget = time.time() + float(a.seconds)
        self.step = a.step
        self.guard()

    # ---- guards ------------------------------------------------------------------------------------------------
    def guard(self):
        bad = []
        if os.path.basename(self.dst) != TEST_DB:
            bad.append('the destination folder must be named ' + TEST_DB)
        for x, y in ((self.dst, self.src), (self.src, self.dst)):
            if x == y or x.startswith(y + os.sep):
                bad.append('the test copy and the live folder must be separate folders')
        if not os.path.isfile(os.path.join(self.src, 'app', 'Config', 'Database.php')):
            bad.append('the live folder given has no app/Config/Database.php')
        for k in KITS:
            if not os.path.isfile(os.path.join(self.kits_root, k, 'MANIFEST.json')):
                bad.append('kit not found: ' + k)
        if bad:
            sys.exit('REFUSED: ' + '; '.join(sorted(set(bad))))
        if self.step != 'check':
            os.makedirs(self.dst, exist_ok=True)

    def out(self, rel):
        p = os.path.realpath(os.path.join(self.dst, rel))
        if not (p == self.dst or p.startswith(self.dst + os.sep)):
            sys.exit('REFUSED: would write outside the test copy: ' + rel)
        return p

    def time_left(self):
        return time.time() < self.budget

    # ---- what is copied ----------------------------------------------------------------------------------------
    def plan(self):
        for d in CODE_DIRS:
            root = self.public_src if d == 'public' else os.path.join(self.src, d)
            for cur, dirs, files in os.walk(root):
                rel_dir = os.path.relpath(cur, root)
                if d == 'public' and rel_dir == '.':
                    dirs[:] = [x for x in dirs if x not in PUBLIC_SKIP_DIRS]
                dirs.sort()
                for f in sorted(files):
                    if d == 'public' and (f in PUBLIC_SKIP_FILES or f.endswith('.log')):
                        continue
                    rel = os.path.normpath(os.path.join(d, rel_dir, f))
                    yield os.path.join(cur, f), rel
        for f in CODE_FILES:
            p = os.path.join(self.src, f)
            if os.path.isfile(p):
                yield p, f

    def check(self):
        # read-only: what would be copied, and whether each kit file is intact and fits the live file it replaces
        n = size = 0
        for s, _ in self.plan():
            n += 1
            size += os.path.getsize(s)
        kits = {}
        final = {}
        for k in KITS:
            kd = os.path.join(self.kits_root, k)
            man = json.load(open(os.path.join(kd, 'MANIFEST.json')))
            ok = fits = 0
            for f in man['files']:
                s = os.path.join(kd, 'release', f['path'])
                ok += os.path.isfile(s) and md5(s) == f['release_md5']
                live = os.path.join(self.src, f['path'])
                lm = md5(live) if os.path.isfile(live) else None
                fits += lm == f['before_md5'] or (f['path'] in final and final[f['path']] == f['before_md5'])
                final[f['path']] = f['release_md5']
            kits[k] = {'files': len(man['files']), 'intact': ok, 'fits_live': fits}
        print(json.dumps({'step': 'check', 'from': self.src, 'to': self.dst, 'files': n, 'mb': round(size / 1048576, 1), 'kits': kits, 'writes': 'nothing',
                          'not_copied': ['.env', 'writable (data, logs, backups)', 'public/uploads', 'db.sql and other dumps', 'documents at the top of the live folder']}))

    def copy(self):
        done = skipped = 0
        for s, rel in self.plan():
            if not self.time_left():
                print(json.dumps({'step': 'copy', 'copied': done, 'already': skipped, 'finished': False}))
                return
            d = self.out(rel)
            st = os.stat(s)
            if os.path.isfile(d) and os.path.getsize(d) == st.st_size and int(os.path.getmtime(d)) == int(st.st_mtime):
                skipped += 1
                continue
            os.makedirs(os.path.dirname(d), exist_ok=True)
            shutil.copy2(s, d)
            done += 1
        wsrc = os.path.join(self.src, 'writable')
        for w in WRITABLE_DIRS:
            os.makedirs(self.out(os.path.join('writable', w)), exist_ok=True)
            idx = os.path.join(wsrc, 'index.html')
            if os.path.isfile(idx) and not os.path.isfile(self.out(os.path.join('writable', w, 'index.html'))):
                shutil.copy2(idx, self.out(os.path.join('writable', w, 'index.html')))
        for f in ('index.html', 'cacert.pem'):
            if os.path.isfile(os.path.join(wsrc, f)):
                shutil.copy2(os.path.join(wsrc, f), self.out(os.path.join('writable', f)))
        print(json.dumps({'step': 'copy', 'copied': done, 'already': skipped, 'finished': True}))

    # ---- the reviewed kits -------------------------------------------------------------------------------------
    def overlay_kits(self):
        report = {'step': 'overlay', 'kits': {}, 'problems': []}
        final = {}
        for k in KITS:
            kd = os.path.join(self.kits_root, k)
            man = json.load(open(os.path.join(kd, 'MANIFEST.json')))
            placed = live_as_expected = 0
            for f in man['files']:
                rel = f['path']
                s = os.path.join(kd, 'release', rel)
                if not os.path.isfile(s) or md5(s) != f['release_md5']:
                    report['problems'].append(k + ': kit file missing or changed: ' + rel)
                    continue
                live = os.path.join(self.src, rel)
                lm = md5(live) if os.path.isfile(live) else None
                if lm == f['before_md5'] or (lm is not None and lm == final.get(rel)):
                    live_as_expected += 1
                d = self.out(rel)
                os.makedirs(os.path.dirname(d), exist_ok=True)
                shutil.copyfile(s, d)
                if md5(d) != f['release_md5']:
                    report['problems'].append(k + ': placed file does not match: ' + rel)
                final[rel] = f['release_md5']
                placed += 1
            report['kits'][k] = {'files': len(man['files']), 'placed': placed, 'live_file_as_the_kit_expects': live_as_expected}
        report['distinct_files'] = len(final)
        json.dump(final, open(self.out('writable/mailtest/kit_files.json'), 'w'), indent=1)
        print(json.dumps(report))
        if report['problems']:
            sys.exit(3)

    # ---- the test copy's own settings --------------------------------------------------------------------------
    def sub_once(self, rel, pattern, repl, already):
        p = self.out(rel)
        s = open(p, encoding='utf-8').read()
        if already in s:
            return 'already'
        s2, n = re.subn(pattern, repl, s, count=1)
        if n != 1:
            sys.exit('REFUSED: could not patch ' + rel)
        open(p, 'w', encoding='utf-8', newline='').write(s2)
        return 'patched'

    def patch(self):
        r = {'step': 'patch'}
        r['App.php'] = self.sub_once('app/Config/App.php', r"public string \$baseURL = '[^']*';",
                                     "public string $baseURL = '" + self.base_url + "';   // TEST COPY", "// TEST COPY")
        r['Session.php'] = self.sub_once('app/Config/Session.php', r"public string \$cookieName = '[^']*';",
                                         "public string $cookieName = 'avenir_mailtest_session';   // TEST COPY: never the live session cookie", "avenir_mailtest_session")
        r['Security.php'] = self.sub_once('app/Config/Security.php', r"public string \$cookieName = '[^']*';",
                                          "public string $cookieName = 'avenir_mailtest_csrf';   // TEST COPY", "avenir_mailtest_csrf")
        guard = (
            "parent::__construct();\n\n"
            "        // ---- TEST COPY ONLY (avenir_mailtest) -----------------------------------------------------------------\n"
            "        // Every connection of this copy goes to the test database, whatever else is set: never avenir,\n"
            "        // inquiries or avenir_2024. Written here, not in .env, so nothing of it reaches the live site.\n"
            "        foreach (['default', 'secondary', 'secondDatabase'] as $g) {\n"
            "            if (isset($this->{$g}) && is_array($this->{$g})) {\n"
            "                $this->{$g}['database'] = '" + TEST_DB + "';\n"
            "                $this->{$g}['hostname'] = '" + self.db_host + "';\n"
            "                $this->{$g}['port'] = " + str(self.db_port) + ";\n"
            "            }\n"
            "        }\n"
            "        $this->defaultGroup = 'default';\n"
            "        // ---- end TEST COPY -----------------------------------------------------------------------------------\n")
        r['Database.php'] = self.sub_once('app/Config/Database.php', r"parent::__construct\(\);\n", lambda m: guard, "TEST COPY ONLY (avenir_mailtest)")
        # a red bar on every page of the test copy (CodeIgniter's own output buffer in app/Config/Events.php)
        inject = ("ob_start(static function ($buffer) {   // TEST COPY ONLY: the red bar, so this copy is never taken for the live system\n"
                  "            if (!is_string($buffer) || stripos($buffer, '<body') === false || strpos($buffer, 'mt-test-bar') !== false) {\n"
                  "                return $buffer;\n"
                  "            }\n"
                  "            return preg_replace('/<body[^>]*>/i', '$0<div id=\"mt-test-bar\" style=\"position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#b42318;color:#fff;font:600 13px/1.2 system-ui,sans-serif;padding:6px 12px;text-align:center;pointer-events:none\">TEST COPY (avenir_mailtest) — dummy data only — not the live Avenir system</div>', $buffer, 1);\n"
                  "        });")
        r['Events.php'] = self.sub_once('app/Config/Events.php', r"ob_start\(static fn \(\$buffer\) => \$buffer\);", lambda m: inject, "mt-test-bar")
        # the setup page, the dummy-data SQL and the notes
        for rel_src, rel_dst in (('mailtest_setup.php', 'public/mailtest_setup.php'), ('mailtest_evidence.php', 'public/mailtest_evidence.php'), ('mailtest.sql', 'writable/mailtest/mailtest.sql'),
                                 ('README-mailtest.txt', 'README-mailtest.txt')):
            s = os.path.join(self.overlay, rel_src)
            if not os.path.isfile(s):
                sys.exit('REFUSED: overlay file missing: ' + rel_src)
            shutil.copyfile(s, self.out(rel_dst))
        # its own key for sealing the mailbox password (the only line of its .env); never printed, never the live key
        env = self.out('.env')
        if not os.path.isfile(env):
            with open(env, 'w', newline='\n') as f:
                f.write('# TEST COPY ONLY (avenir_mailtest). Its own key for sealing the test mailbox password. Not the live key.\n')
                f.write('MAIL_SECRET_KEY = ' + base64.b64encode(os.urandom(32)).decode() + '\n')
            r['.env'] = 'written (its own new key; not shown)'
        else:
            r['.env'] = 'kept'
        print(json.dumps(r))

    def manifest(self):
        files = {}
        for top in CODE_DIRS + CODE_FILES + ['README-mailtest.txt']:
            p = self.out(top)
            if os.path.isfile(p):
                files[top] = md5(p)
                continue
            for cur, dirs, fs in os.walk(p):
                dirs.sort()
                for f in sorted(fs):
                    full = os.path.join(cur, f)
                    files[os.path.relpath(full, self.dst).replace(os.sep, '/')] = md5(full)
        json.dump(files, open(self.out('writable/mailtest/MANIFEST.json'), 'w'), indent=0, sort_keys=True)
        h = hashlib.md5(json.dumps(files, sort_keys=True).encode()).hexdigest()
        print(json.dumps({'step': 'manifest', 'files': len(files), 'digest': h}))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('step', choices=['check', 'copy', 'overlay', 'patch', 'manifest'])
    ap.add_argument('--src', required=True)
    ap.add_argument('--dst', required=True)
    ap.add_argument('--public-src')
    ap.add_argument('--kits-root')
    ap.add_argument('--overlay')
    ap.add_argument('--base-url', default='http://localhost/avenir_mailtest/')
    ap.add_argument('--db-host', default='localhost')
    ap.add_argument('--db-port', default='3306')
    ap.add_argument('--seconds', default='150')
    a = ap.parse_args()
    b = Builder(a)
    {'check': b.check, 'copy': b.copy, 'overlay': b.overlay_kits, 'patch': b.patch, 'manifest': b.manifest}[a.step]()


if __name__ == '__main__':
    main()
