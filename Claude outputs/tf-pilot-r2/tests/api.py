"""tf-paydesk-r1 — a signed-in HTTP client for the TEST COPY (127.0.0.1:8081, database avenir_test). Usage from other scripts:
    from api import Client; c = Client('admin'); c.get('tour/tour_payment_dashboard/desk', manual_id='AH 2218')
Accounts are the test copy's (tc/TEST_ACCOUNTS.txt) plus the ones this build adds for the payment roles."""
import os, sys, json, re, requests
HERE = os.path.dirname(os.path.abspath(__file__))
S = os.path.dirname(HERE)
BASE = os.environ.get('TC_HOST', 'http://127.0.0.1:8081') + '/avenir/'
ACC = {
    'admin': ('tcadmin', 'tc-admin-2026'), 'quote': ('tcquote', 'tc-desk-2026'), 'transport': ('tctransport', 'tc-desk-2026'), 'hotel': ('tchotel', 'tc-desk-2026'),
    'mgmt': ('tcmgmt', 'tc-desk-2026'),
    # payment roles (added by setup_testdata.py on the test copy only)
    'requester': ('tcpayreq', 'tc-pay-2026'), 'approver': ('tcpayappr', 'tc-pay-2026'), 'accountant': ('tcaccount', 'tc-pay-2026'), 'bank': ('tcbankappr', 'tc-pay-2026'), 'auditor': ('tcauditor', 'tc-pay-2026'), 'ops': ('tcops', 'tc-pay-2026'), 'transh': ('tctransh', 'tc-pay-2026'), 'hotelres': ('tchotelres', 'tc-pay-2026'),
}

class Client:
    def __init__(self, who='admin', base=None):
        self.who = who
        self.base = base or BASE   # tf-paydesk-r4: a second server (another port) for the concurrency test
        BASE_ = self.base
        self.s = requests.Session()
        u, p = ACC[who]
        r = self.s.get(BASE_)
        m = re.search(r'name="([^"]+)" value="([0-9a-f]{32})"', r.text)
        data = {'username': u, 'password': p}
        if m:
            data[m.group(1)] = m.group(2)
        import time as _t
        for attempt in range(8):
            r = self.s.post(BASE_ + 'login', data=data, headers={'X-Requested-With': 'XMLHttpRequest'})
            try:
                j = r.json()
            except Exception:
                raise RuntimeError('login failed for %s: %s' % (who, r.text[:200]))
            if j.get('status') == 429:   # the login rate limit (many test sign-ins in a row): wait and try again
                _t.sleep(float(j.get('retry_after') or 3) + 0.5); continue
            break
        if j.get('status') != 200:
            raise RuntimeError('login refused for %s: %s' % (who, j))
        self.csrf = None
    def _csrf(self):
        if self.csrf is None:
            r = self.s.get(self.base + 'tour/tour_payment_dashboard')
            m = re.search(r"formData.append\('([^']+)', '([0-9a-f]{32})'\)", r.text) or re.search(r'name="(csrf_[^"]+)" value="([0-9a-f]{32})"', r.text)
            self.csrf = (m.group(1), m.group(2)) if m else None
        return self.csrf
    def get(self, path, **params):
        r = self.s.get(self.base + path, params=params, headers={'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'})
        return self._j(r)
    def post(self, path, data=None, files=None, json_body=None):
        h = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
        if json_body is not None:
            r = self.s.post(self.base + path, json=json_body, headers=h)
        else:
            r = self.s.post(self.base + path, data=data or {}, files=files, headers=h)
        return self._j(r)
    def raw(self, path, **params):
        return self.s.get(self.base + path, params=params)
    def _j(self, r):
        try:
            j = r.json()
        except Exception:
            return {'status': r.status_code, 'raw': r.text[:1500]}
        return j if isinstance(j, dict) else {'status': r.status_code, 'raw': j}

if __name__ == '__main__':
    who = sys.argv[1] if len(sys.argv) > 1 else 'admin'
    c = Client(who)
    print(json.dumps(c.get(sys.argv[2], **dict(a.split('=', 1) for a in sys.argv[3:])), indent=1)[:4000])
