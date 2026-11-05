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

        $session = session();

        // Check if the user is logged in
        if (!$session->get('logged_in') || !$session->get('ID') || !$session->get('username') || !$session->get('group_id')) {
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
                $session->destroy();
                return redirect()->to(base_url());
            }
        } else {
            // Pre-registry session (no token) — force re-login
            $session->destroy();
            return redirect()->to(base_url());
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something after the route has executed
    }
}
