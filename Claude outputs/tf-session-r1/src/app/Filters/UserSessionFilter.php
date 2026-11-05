<?php

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
