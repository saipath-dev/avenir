var base_url = window.location.origin + '/avenir/';
// tf-session-r1 (12 Oct 2026): document.writeln is only safe while the page is still being read. Run later (for example
// when a page's HTML was inserted by a script) it wipes the whole page, which is how the dashboard "disappeared".
document.readyState === 'loading' &&
(document.querySelectorAll("[toast-list]") ||
  document.querySelectorAll("[data-choices]") ||
  document.querySelectorAll("[data-provider]")) &&
  (document.writeln(
    "<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/toastify-js'></script>"
  ),
  document.writeln(
    "<script type='text/javascript' src='assets/libs/choices.js/public/assets/scripts/choices.min.js'></script>"
  ),
  document.writeln(
    "<script type='text/javascript' src='"+ base_url +"assets/libs/flatpickr/flatpickr.min.js'></script>"
  ));
console.log(base_url);

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
})();