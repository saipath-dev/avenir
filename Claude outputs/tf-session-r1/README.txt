tf-session-r1 — "the dashboard disappears after signing in", 12 October 2026. NOT INSTALLED on development or live.
Tested in the cloud test copy and on the isolated test instance (avenir_rv, then rolled back). Installing it on the
development system needs your approval; apply.py refuses any base URL other than …/avenir_rv/ until then.

The problem (reproduced in the cloud test copy, 14 checks)
  1. A page left open (dashboard, any page with the top bar) keeps asking the server in the background: stock
     notifications every 30 s, tour notifications every 20 s.
  2. After a sign-out, those background requests have no session; the server sends them to the sign-in page, and the
     sign-in page ENDS the session it finds and sends a "delete the session cookie" answer.
  3. If such an answer arrives just after someone signs in again (in another tab, or after typing the password), it deletes
     the new sign-in. The dashboard's own requests then receive the sign-in page as HTML, which an old jQuery plugin
     writes into the document with document.writeln after the page has loaded — the page goes blank.

The fix (3 files, no database change)
  app/Controllers/LoginController.php  the sign-in page no longer ends a session that is still valid (it opens the user's
                                       start page instead); a stale session is still ended as before
  app/Filters/UserSessionFilter.php    background requests (AJAX / JSON) without a valid session get a quiet answer:
                                       401 JSON {session_ended: true} and NO cookie; page requests are sent to the
                                       sign-in page as before (with ?redirect=); permissions (403) unchanged
  public/assets/js/plugins.js          no document.writeln once the page has loaded (no more blank page); a red notice
                                       "Your session has ended … Sign in again" when a background request is answered 401;
                                       the notice goes away when requests succeed again

Install / roll back (isolated instance only in this stage)
  python3 apply.py check|apply|rollback <avenir_rv root>   (backs up the 3 files to before/<time>/ first)
Results    results/cloud/ (before 14/14 problems reproduced; after 18/18), results/isolated-instance/ (before 5/5, after 6/6, rolled back)
Files      src/ (md5 in SOURCE-MD5.txt); tests/test_session.py (cloud, Playwright, two tabs, held answers)
