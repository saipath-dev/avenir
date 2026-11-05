"""tf-bankrec-r3 (D7): the old Bank Reconciliation page shows the server's reason when a change is refused (409), instead
of "Something went wrong". The four handlers that called swal(...) use Swal.fire(...): the page loads SweetAlert2 11, where
swal(...) throws ("Class constructor ... cannot be invoked without 'new'") and no message appeared at all. Usage: patch_legacy_view_r3.py <app/Views/pages/accounts/bank_reconcillation.php>"""
import sys
p = sys.argv[1]
raw = open(p, 'rb').read().decode('utf-8')
crlf = '\r\n' in raw
t = raw.replace('\r\n', '\n')
if 'tf-bankrec-r3' in t:
    sys.exit('already patched')
n1 = t.count('error: function() {')
n2 = t.count("'Something went wrong! Please Try Again!'") + t.count('"Something went wrong! Please Try Again!"')
if (n1, n2) != (7, 7):
    sys.exit('unexpected page: %d handlers, %d messages' % (n1, n2))
t = t.replace('error: function() {', 'error: function(xhr) { // tf-bankrec-r3: show the reason given by the server')
t = t.replace("'Something went wrong! Please Try Again!'", "((xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong! Please Try Again!')")
t = t.replace('"Something went wrong! Please Try Again!"', '((xhr && xhr.responseJSON && xhr.responseJSON.message) || "Something went wrong! Please Try Again!")')
n3 = t.count('swal("", ((xhr && xhr.responseJSON')
t = t.replace('swal("", ((xhr && xhr.responseJSON', 'Swal.fire("", ((xhr && xhr.responseJSON')
if n3 != 4:
    sys.exit('unexpected page: %d swal error handlers' % n3)
if crlf:
    t = t.replace('\n', '\r\n')
open(p, 'wb').write(t.encode('utf-8'))
print('patched', p)
