#!/bin/bash
# tf-bankrec-r3: cloud rehearsal of apply.py on a skeleton copy of the isolated instance (r2 files, base URL …/avenir_rv/)
S=/tmp/claude-0/-home-claude/1f177919-91eb-5b5f-a3ff-b7531d256531/scratchpad; K=$S/bankrec/kit/tf-bankrec-r3; R=$S/bankrec/r3work/rehearse_apply
rm -rf $R $K/before; mkdir -p $R/app/Config
sed "s#public string \$baseURL = '[^']*'#public string \$baseURL = 'http://localhost/avenir_rv/'#" $S/tm/app/Config/App.php > $R/app/Config/App.php
(cd $S/bankrec/kit/tf-bankrec-r2/src && find . -type f | while read f; do mkdir -p $R/$(dirname $f); cp $f $R/$f; done)
for f in app/Controllers/Accounts/BankReconsillationController.php app/Views/pages/accounts/bank_reconcillation.php; do mkdir -p $R/$(dirname $f); cp $S/bankrec/r3work/r2_src/$f $R/$f; done
chmod -R u+w $R; cd $K
sig() { (cd $R && find app -type f ! -path 'app/Config/App.php' | sort | xargs md5sum | md5sum | cut -c1-12); }
echo "1. r2 skeleton: $(sig)"; R2SIG=$(sig)
echo "2. check:"; python3 apply.py check $R | python3 -c "import json,sys; d=json.load(sys.stdin); print('   changed', len(d['changed']), 'added', d['added'], 'not_r2', d['not_r2'])"
echo "3. apply:"; python3 apply.py apply $R | python3 -c "import json,sys; d=json.load(sys.stdin); print('   verified', d['verified'], 'backup', d['backup'].split('/')[-1])"
echo "   r3 files: $(cd $R && md5sum $(cd $K/src && find . -type f | sed 's#^./##') | md5sum | cut -c1-12) = kit src $(cd $K/src && md5sum $(find . -type f | sed 's#^./##') | md5sum | cut -c1-12)"
echo "4. rollback with deleting refused (as on the user's computer):"; python3 -c "
import os, sys, runpy, json, io, contextlib
def no(*a, **k): raise PermissionError(1, 'Operation not permitted')
os.remove = no; sys.argv = ['apply.py', 'rollback', '$R']; buf = io.StringIO()
with contextlib.redirect_stdout(buf):
    try: runpy.run_path('apply.py', run_name='__main__')
    except SystemExit as e: pass
d = json.loads(buf.getvalue()); print('   back_to_r2', d['back_to_r2'], '|', [f for f in d['files'] if 'Legacy' in f][0].split(' moved')[0], 'moved into before/…/added-removed/')"
echo "   files now: $(sig) (r2 was $R2SIG)"; [ "$(sig)" = "$R2SIG" ] && echo "   IDENTICAL to r2"
echo "5. apply again + normal rollback:"; python3 apply.py apply $R > /dev/null; python3 apply.py rollback $R | python3 -c "import json,sys; d=json.load(sys.stdin); print('   back_to_r2', d['back_to_r2'], [f for f in d['files'] if 'Legacy' in f])"; [ "$(sig)" = "$R2SIG" ] && echo "   IDENTICAL to r2"
D=$S/bankrec/r3work/rehearse_apply_dev; rm -rf $D; cp -r $R $D; sed -i 's#avenir_rv/#avenir/#' $D/app/Config/App.php
echo "6. development base URL:"; python3 apply.py check $D 2>&1 | sed 's/^/   /' | cut -c1-160
E=$S/bankrec/r3work/rehearse_apply_drift; rm -rf $E; cp -r $R $E; echo '// local edit' >> $E/app/Libraries/BankRec/Rec.php
echo "7. a file that is not the r2 file:"; python3 apply.py apply $E | python3 -c "import json,sys; d=json.load(sys.stdin); print('   not_r2', d['not_r2'], '|', d['note'])"; echo "   LegacyGuard.php written: $(ls $E/app/Libraries/BankRec/ | grep -c Legacy)"
rm -rf $K/before $D $E
