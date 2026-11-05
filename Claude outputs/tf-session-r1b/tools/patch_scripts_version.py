"""tf-session-r1b: give plugins.js a version mark in app/Views/layouts/scripts.php, so that a browser fetches the file again
whenever it changes (the mark is the file's modification time) — no Ctrl+F5 needed after this or any later update of
plugins.js. Keeps the file's line endings. Usage: patch_scripts_version.py <app/Views/layouts/scripts.php>"""
import sys
p = sys.argv[1]
raw = open(p, 'rb').read(); crlf = b'\r\n' in raw
t = raw.decode('utf-8').replace('\r\n', '\n')
if 'tf-session-r1b' in t:
    sys.exit('already patched')
old = '    <script src="<?= base_url() ?>assets/js/plugins.js"></script>\n'
if t.count(old) != 1:
    sys.exit('expected the plugins.js line once, found %d' % t.count(old))
new = ('    <?php /* tf-session-r1b: version mark = the file\'s modification time, so browsers load a changed plugins.js at once */ ?>\n'
       '    <script src="<?= base_url() ?>assets/js/plugins.js?v=<?= @filemtime(FCPATH . \'assets/js/plugins.js\') ?: \'1\' ?>"></script>\n')
t = t.replace(old, new)
if crlf:
    t = t.replace('\n', '\r\n')
open(p, 'wb').write(t.encode('utf-8'))
print('patched', p)
