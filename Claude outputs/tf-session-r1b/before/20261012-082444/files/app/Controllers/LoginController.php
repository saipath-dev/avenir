<?php

namespace App\Controllers;

use App\Models\UserModel;

class LoginController extends BaseController
{
    private const LOGIN_RATE_LIMIT_ATTEMPTS = 10;  // IP: max requests per window
    private const LOGIN_RATE_LIMIT_WINDOW   = 60;  // IP: window in seconds
    private const LOGIN_LOCKOUT_ATTEMPTS    = 5;   // account: fails before lock
    private const LOGIN_LOCKOUT_WINDOW      = 900; // account: rolling window (15 min)
    private const LOGIN_LOCKOUT_DURATION    = 900; // account: lock duration (15 min)

    public function index()
    {
        $session = \Config\Services::session();
        $session->destroy();
        return view('pages/login');
    }

    public function login()
    {
        try {
            $validation = \Config\Services::validation();

            $validation->setRules([
                'username' => 'required|strip_tags|alpha_numeric_space',
                'password' => 'required|strip_tags',
            ]);

            if ($validation->withRequest($this->request)->run()) {

                $username = $this->request->getPost('username');
                $password = $this->request->getPost('password');

                // Cache-based guard: IP throttle + account lockout (no DB write)
                if (($guard = $this->guardLoginAttempt($username)) !== null) {
                    return $this->response->setStatusCode(429)->setJSON($guard);
                }

                $userModel = new UserModel();
                $user = $userModel->where('user', $username)->first();

                if (empty($user)) {
                    $this->recordFailedLoginAttempt($username);
                    return $this->response->setStatusCode(401)->setJSON(["status" => 401, "msg" => "Unauthorized. Please check your username and password."]);
                }

                if ($user['access'] != 1) {
                    return $this->response->setStatusCode(401)->setJSON(["status" => 401, "msg" => "Forbidden. Your account is disabled."]);
                }

                if (!password_verify($password, $user['pass'])) {
                    $this->recordFailedLoginAttempt($username);
                    return $this->response->setStatusCode(401)->setJSON(["status" => 401, "msg" => "Unauthorized. Please check your username and password."]);
                }

                // Success — clear account counter
                $this->clearFailedLoginAttempts($username);

                $session = \Config\Services::session();
                $session->regenerate(true); // prevent session fixation
                $sessToken = bin2hex(random_bytes(32));
                $session->set([
                    'sess_token' => $sessToken,
                    'user_id'    => $user['id'],
                    'ID'         => $user['user'],
                    'username'   => $user['user'],
                    'group_id'   => $user['group_id'],
                    'emp_id'     => $user['emp_id'],
                    'acc_no'     => $user['acc_no'],
                    'agent'      => $user['agent'],
                    'logged_in'  => true,
                    'user_role'  => $user['user_role'],
                ]);
                helper('usersession');
                register_user_session(
                    $user['id'],
                    $sessToken,
                    session_id(),
                    $this->request->getIPAddress(),
                    (string) $this->request->getUserAgent()
                );
                $db = \Config\Database::connect();
                $db->table('a_log')->insert([
                    'Usr'   => session()->get('username'),
                    'Url'   => 'login',
                    'Panel' => 'Login',
                    'Date'  => date('Y-m-d H:i:s'),
                    'Tym'   => date('Y-m-d H:i:s'),
                    'ip'    => $this->request->getIPAddress(),
                ]);
                // do NOT close the shared DB connection - the DB session handler needs it at shutdown
                // tf-pilot-r1: every department lands on the common starting page (Pilot); drivers keep their portal, the
                // System Administrator keeps the administration dashboard
                $redirect = $user['group_id'] == 24 ? 'driver_portal' : ($user['group_id'] == 1 ? 'dashboard' : 'dashboard2/tiles');

                // Admin group (25): prompt for today's Daily USD Rate on login if it hasn't been set yet.
                $needsUsdRate = false;
                if ($user['group_id'] == 25) {
                    $hasTodayRate = $db->table('daily_usd_rate')
                        ->where('rate_date', date('Y-m-d'))
                        ->countAllResults();
                    $needsUsdRate = $hasTodayRate == 0;
                }

                return $this->response->setJSON(["status" => 200, "msg" => "Login Succeeded. Redirecting to dashboard...", "redirect" => $redirect, "needs_usd_rate" => $needsUsdRate]);

            } else {
                return $this->response->setStatusCode(400)->setJSON(["status" => 400, "msg" => "Please provide both username and password."]);
            }
        } catch (\Exception $e) {
            log_message('error', 'LoginController::login exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(["status" => 500, "msg" => "An internal error occurred. Please try again."]);
        }
    }

    public function logout()
    {
        $session = \Config\Services::session();
        helper('usersession');
        forget_user_session($session->get('sess_token'));
        $session->destroy();
        return $this->response->setJSON(['status' => 200, 'msg' => 'Logout Succeeded. Redirecting to login page...']);
    }

    public function forbidden()
    {
        return view('pages/403');
    }

    // -------------------------------------------------------------------------
    // Cache-based rate limiting helpers (no DB writes)
    // -------------------------------------------------------------------------

    protected function guardLoginAttempt(string $username): ?array
    {
        // 1. Account lockout (cache)
        $remaining = $this->getLockoutSecondsRemaining($username);
        if ($remaining > 0) {
            return [
                'status'      => 429,
                'msg'         => 'Account temporarily locked. Try again in ' . ceil($remaining / 60) . ' minute(s).',
                'retry_after' => $remaining,
            ];
        }

        // 2. IP throttle (token bucket)
        $throttler = \Config\Services::throttler();
        $ipKey = 'login_ip_' . hash('sha256', (string) $this->request->getIPAddress());
        if (!$throttler->check($ipKey, self::LOGIN_RATE_LIMIT_ATTEMPTS, self::LOGIN_RATE_LIMIT_WINDOW)) {
            return [
                'status'      => 429,
                'msg'         => 'Too many login attempts from your network. Try again shortly.',
                'retry_after' => $throttler->getTokenTime(),
            ];
        }

        return null;
    }

    protected function recordFailedLoginAttempt(string $username): void
    {
        $username = strtolower(trim($username));
        if ($username === '') return;

        $cache = \Config\Services::cache();
        $key   = 'login_user_' . hash('sha256', $username);
        $now   = time();
        $state = $cache->get($key) ?: ['count' => 0, 'window_started_at' => $now, 'locked_until' => 0];

        if (($state['locked_until'] ?? 0) > $now) return; // already locked

        if ($now - $state['window_started_at'] >= self::LOGIN_LOCKOUT_WINDOW) {
            $state = ['count' => 0, 'window_started_at' => $now, 'locked_until' => 0];
        }

        $state['count']++;
        $ttl = max(1, self::LOGIN_LOCKOUT_WINDOW - ($now - $state['window_started_at']));

        if ($state['count'] >= self::LOGIN_LOCKOUT_ATTEMPTS) {
            $state['locked_until'] = $now + self::LOGIN_LOCKOUT_DURATION;
            $ttl = self::LOGIN_LOCKOUT_DURATION;
            log_message('warning', 'Login lockout for username hash ' . substr(hash('sha256', $username), 0, 12));
        }

        $cache->save($key, $state, $ttl);
    }

    protected function clearFailedLoginAttempts(string $username): void
    {
        $username = strtolower(trim($username));
        if ($username !== '') {
            \Config\Services::cache()->delete('login_user_' . hash('sha256', $username));
        }
    }

    private function getLockoutSecondsRemaining(string $username): int
    {
        $username = strtolower(trim($username));
        if ($username === '') return 0;
        $state     = \Config\Services::cache()->get('login_user_' . hash('sha256', $username));
        $remaining = (int)($state['locked_until'] ?? 0) - time();
        return $remaining > 0 ? $remaining : 0;
    }
}
