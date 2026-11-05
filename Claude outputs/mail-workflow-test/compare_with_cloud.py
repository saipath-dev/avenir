#!/usr/bin/env python3
"""Read-only: would the laptop's test copy hold exactly the code rehearsed in the cloud? For each file the cloud build
holds, the md5 of what the laptop build would place there (the kit's release file, else the live file), compared;
the files only one side has, listed. Writes nothing.

    python3 compare_with_cloud.py <live avenir folder> <kits root> <MANIFEST-cloud-build.json> <kit_files-cloud.json>
"""
import hashlib, json, os, sys
live, kits_root, man_p, kits_p = sys.argv[1:5]
PATCHED = {'app/Config/App.php', 'app/Config/Session.php', 'app/Config/Security.php', 'app/Config/Database.php', 'app/Config/Events.php',
           'public/mailtest_setup.php', 'public/mailtest_evidence.php', 'README-mailtest.txt'}
def md5(p):
    h = hashlib.md5()
    with open(p, 'rb') as f:
        for b in iter(lambda: f.read(1 << 20), b''):
            h.update(b)
    return h.hexdigest()
cloud = json.load(open(man_p))
kit_final = {}
for k in ['tf-tour-booking-r1', 'tf-mail-r1']:
    for f in json.load(open(os.path.join(kits_root, k, 'MANIFEST.json')))['files']:
        kit_final[f['path']] = os.path.join(kits_root, k, 'release', f['path'])
same = differ = 0
diffs, missing = [], []
for rel, m in sorted(cloud.items()):
    if rel in PATCHED:
        continue
    src = kit_final.get(rel) or os.path.join(live, rel)
    if not os.path.isfile(src):
        missing.append(rel)
        continue
    if md5(src) == m:
        same += 1
    else:
        differ += 1
        diffs.append(rel)
# files the laptop build would copy that the cloud build does not have
SKIP_PUB_DIRS, SKIP_PUB_FILES = {'uploads', 'ci_sessions'}, {'sent_message_ids.txt', 'Thumbs.db', 'clear_cache.php'}
extra = []
for d in ['app', 'vendor', 'public']:
    root = os.path.join(live, d)
    for cur, dirs, files in os.walk(root):
        rd = os.path.relpath(cur, root)
        if d == 'public' and rd == '.':
            dirs[:] = [x for x in dirs if x not in SKIP_PUB_DIRS]
        for f in files:
            if d == 'public' and (f in SKIP_PUB_FILES or f.endswith('.log')):
                continue
            rel = os.path.normpath(os.path.join(d, rd, f)).replace(os.sep, '/')
            if rel not in cloud:
                extra.append(rel)
print(json.dumps({'same': same, 'differ': differ, 'only_in_cloud_build': len(missing), 'only_on_laptop': len(extra),
                  'differ_files': diffs[:40], 'only_in_cloud_files': missing[:40], 'only_on_laptop_files': extra[:40]}, indent=1))
