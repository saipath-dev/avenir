"""tf-session-r1: build the three changed files from the originals (r0, CRLF kept) into src/."""
import os, sys
K = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'kit', 'tf-session-r1')

def rw(rel, edits):
    src = open(os.path.join(K, 'r0', rel), 'rb').read().decode('utf-8')
    crlf = '\r\n' in src
    t = src.replace('\r\n', '\n')
    for old, new in edits:
        if t.count(old) != 1:
            sys.exit('%s: expected one match for %r, found %d' % (rel, old[:60], t.count(old)))
        t = t.replace(old, new)
    if crlf:
        t = t.replace('\n', '\r\n')
    os.makedirs(os.path.dirname(os.path.join(K, 'src', rel)), exist_ok=True)
    open(os.path.join(K, 'src', rel), 'wb').write(t.encode('utf-8'))

# 1. The sign-in page no longer signs out a session that is still valid
rw('app/Controllers/LoginController.php', [(
"""    public function index()
    {
        $session = \\Config\\Services::session();
        $session->destroy();
        return view('pages/login');
    }
""",
"""    public function index()
    {
        // tf-session-r1 (12 Oct 2026): opening the sign-in page no longer signs out a session that is still valid.
        // Before, any request that reached this page (for example a background check from an old tab, redirected here
        // after its own session had ended) destroyed the session shared by every tab, so a fresh sign-in vanished a few
        // seconds later. A valid session is sent to its start page; anything else is cleared as before.
        $session = \\Config\\Services::session();
        if ($this->sessionStillValid($session)) {
            return redirect()->to(base_url($this->startPage((int) $session->get('group_id'))));
        }
        $session->destroy();
        return view('pages/login');
    }

    /** tf-session-r1: the same test as UserSessionFilter (signed-in user in the session, token active in the registry). */
    private function sessionStillValid($session): bool
    {
        if (!$session->get('logged_in') || !$session->get('ID') || !$session->get('username') || !$session->get('group_id')) {
            return false;
        }
        $userId = $session->get('user_id');
        $token  = $session->get('sess_token');
        if (!$userId || !$token) {
            return false;
        }
        helper('usersession');
        try {
            return validate_user_session($userId, $token, session_id());
        } catch (\\Throwable $e) {
            return false;
        }
    }

    /** tf-session-r1: the start page by group, as after signing in (drivers, System Administrator, everyone else). */
    private function startPage(int $groupId): string
    {
        return $groupId == 24 ? 'driver_portal' : ($groupId == 1 ? 'dashboard' : 'dashboard2/tiles');
    }
""")])

# 2. Background requests whose session has ended: 401 JSON, no redirect, and no cookie sent back
FILTER = r"""<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class UserSessionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // request url
        $currentURL = current_url(true);
        // // replace index.php from the url
        $currentURL = str_replace('index.php/', '', $currentURL);
        // // replace base_url from the url
        $currentURL = str_replace(base_url(), '', $currentURL);

        // tf-session-r1 (12 Oct 2026): a background (AJAX / JSON) request without a session cookie is answered 401
        // before a session is started, so no cookie goes back that could replace a newer sign-in in another tab.
        $background = $this->isBackground($request);
        if ($background && (string) $request->getCookie(config('Session')->cookieName) === '') {
            return $this->endedQuietly();
        }

        $session = session();

        // Check if the user is logged in
        if (!$session->get('logged_in') || !$session->get('ID') || !$session->get('username') || !$session->get('group_id')) {
            if ($background) {
                return $this->endedQuietly();
            }
            // destroy session
            $session->destroy();
            // get the url user tried to access and set it in redirect= parameter
            // User is not logged in, redirect to login pagereturn 
            if (strpos($currentURL, '&') === false && strpos($currentURL, '403') === false) {
                return redirect()->to(base_url() . '?redirect=' . urlencode($currentURL));
            } else {
                return redirect()->to(base_url());
            }
        }

        // Validate the session token against the registry — kicks out revoked/expired devices.
        $userId    = $session->get('user_id');
        $sessToken = $session->get('sess_token');

        if ($userId && $sessToken) {
            helper('usersession');
            if (!validate_user_session($userId, $sessToken, session_id())) {
                if ($background) {
                    return $this->endedQuietly();
                }
                $session->destroy();
                return redirect()->to(base_url());
            }
        } else {
            if ($background) {
                return $this->endedQuietly();
            }
            // Pre-registry session (no token) — force re-login
            $session->destroy();
            return redirect()->to(base_url());
        }

        return $request;
    }

    /** tf-session-r1: an AJAX request, or one that asks for JSON (the same test as UserPermissionFilter). */
    private function isBackground(RequestInterface $request): bool
    {
        return $request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json');
    }

    /**
     * tf-session-r1 (12 Oct 2026): the answer to a background request whose session has ended. Before, such a request
     * was redirected to the sign-in page, and both answers told the browser to delete its session cookie: when one of
     * them arrived just after the user had signed in again (in this or another tab), the new sign-in was deleted, the
     * page received the sign-in page instead of its data and went blank. Now: 401 JSON, the session is closed without
     * writing, and no cookie of any kind is sent back. Page requests are redirected to the sign-in page as before.
     */
    private function endedQuietly(): ResponseInterface
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }
        if (!headers_sent()) {
            header_remove('Set-Cookie');
        }
        $msg      = 'Your session has ended. Please sign in again.';
        $response = new \CodeIgniter\HTTP\Response(config(\Config\App::class));
        return $response->setStatusCode(401)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['status' => 401, 'session_ended' => true, 'msg' => $msg, 'message' => $msg, 'login' => base_url()]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something after the route has executed
    }
}
"""
os.makedirs(os.path.join(K, 'src', 'app', 'Filters'), exist_ok=True)
open(os.path.join(K, 'src', 'app', 'Filters', 'UserSessionFilter.php'), 'wb').write(FILTER.replace('\n', '\r\n').encode('utf-8'))

# 3. plugins.js: no document.writeln after the page has loaded; one notice when the session has ended
rw('public/assets/js/plugins.js', [
("""(document.querySelectorAll("[toast-list]") ||""",
"""// tf-session-r1 (12 Oct 2026): document.writeln is only safe while the page is still being read. Run later (for example
// when a page's HTML was inserted by a script) it wipes the whole page, which is how the dashboard "disappeared".
document.readyState === 'loading' &&
(document.querySelectorAll("[toast-list]") ||"""),
("""console.log(base_url);""",
"""console.log(base_url);

// tf-session-r1: when a background request reports that the session has ended (401 from UserSessionFilter), show one
// notice with a link to sign in again. Nothing is reloaded or cleared automatically; the notice goes away by itself
// when a later background request succeeds (for example after signing in again in another tab). Works for every copy
// of jQuery on the page and for plain XMLHttpRequest / fetch, because it listens at the XMLHttpRequest / fetch level.
(function () {
  if (window.__tfSessionWatch) return;
  window.__tfSessionWatch = true;
  function notice(login) {
    if (!document.body || document.getElementById('tf-session-ended')) return;
    var here = location.href, back = '';
    if (login && here.indexOf(login) === 0) {
      var rel = here.slice(login.length).replace(/#.*$/, '');
      if (rel && rel.indexOf('&') < 0) back = '?redirect=' + encodeURIComponent(rel);
    }
    var d = document.createElement('div');
    d.id = 'tf-session-ended';
    d.setAttribute('role', 'alert');
    d.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:2147483000;background:#b42318;color:#fff;padding:10px 16px;font:600 14px/1.4 sans-serif;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.25)';
    d.appendChild(document.createTextNode('Your session has ended (you were signed out in this or another tab). '));
    var a = document.createElement('a');
    a.href = (login || '/') + back;
    a.textContent = 'Sign in again';
    a.style.cssText = 'color:#fff;text-decoration:underline';
    d.appendChild(a);
    document.body.appendChild(d);
  }
  function clear() { var n = document.getElementById('tf-session-ended'); if (n && n.parentNode) n.parentNode.removeChild(n); }
  function seen(status, text) {
    if (status === 401) {
      try { var j = JSON.parse(text || ''); if (j && j.session_ended) notice(j.login); } catch (e) {}
    } else if (status >= 200 && status < 300) {
      clear();
    }
  }
  var X = window.XMLHttpRequest && window.XMLHttpRequest.prototype;
  if (X && X.send) {
    var send = X.send;
    X.send = function () {
      var xhr = this;
      try { xhr.addEventListener('loadend', function () { var t = ''; try { t = (xhr.responseType === '' || xhr.responseType === 'text') ? xhr.responseText : ''; } catch (e) {} seen(xhr.status, t); }); } catch (e) {}
      return send.apply(this, arguments);
    };
  }
  if (window.fetch) {
    var f = window.fetch;
    window.fetch = function () {
      return f.apply(this, arguments).then(function (r) {
        try {
          if (r.status === 401) { r.clone().text().then(function (t) { seen(401, t); }, function () {}); }
          else if (r.ok) { clear(); }
        } catch (e) {}
        return r;
      });
    };
  }
})();"""),
])
print('built')
