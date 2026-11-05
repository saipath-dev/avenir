"""List the GET routes of the test copy by reading Config/Routes*.php (spark routes fails on this app)."""
import re, sys, glob, os, json
S = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
out = []
for f in sorted(glob.glob(S + '/tm/app/Config/Routes*.php')):
    src = open(f, encoding='utf-8', errors='replace').read()
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    src = '\n'.join(l.split('//')[0] if '://' not in l else l for l in src.split('\n'))
    stack = []   # (prefix, depth at which it closes)
    depth = 0
    i = 0
    tok = re.compile(r"\$routes->group\(\s*'([^']*)'|\$routes->(get|match)\(\s*(\[[^\]]*\]\s*,\s*)?'([^']*)'|[{}]")
    pending = None
    for m in tok.finditer(src):
        t = m.group(0)
        if t == '{':
            depth += 1
            if pending is not None:
                stack.append((pending, depth)); pending = None
        elif t == '}':
            if stack and stack[-1][1] == depth:
                stack.pop()
            depth -= 1
        elif m.group(1) is not None:
            pending = m.group(1).strip('/')
        else:
            meth = m.group(2)
            if meth == 'match' and 'get' not in (m.group(3) or ''):
                continue
            p = '/'.join([s for s, _ in stack if s] + ([m.group(4).strip('/')] if m.group(4).strip('/') else []))
            out.append(p)
seen = []
for p in out:
    if p not in seen:
        seen.append(p)
json.dump(seen, sys.stdout, indent=0)
