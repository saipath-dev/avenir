<?php
/**
 * User session registry helper (DB-backed).
 * Works with MySQL (MySQLi) — dialect auto-detected.
 * Immediate kill deletes rows from the ci_sessions table (DB session handler).
 */
if (!function_exists('_user_sessions_db')) {
    function _user_sessions_db()
    {
        return \Config\Database::connect();
    }
}
if (!function_exists('_sess_now_sql')) {
    function _sess_now_sql(): string
    {
        $driver = _user_sessions_db()->DBDriver;
        return (stripos($driver, 'mysql') !== false) ? 'NOW()' : 'GETDATE()';
    }
}
if (!function_exists('_sess_cutoff_sql')) {
    function _sess_cutoff_sql(int $seconds): string
    {
        $driver = _user_sessions_db()->DBDriver;
        if (stripos($driver, 'mysql') !== false) {
            return "DATE_SUB(NOW(), INTERVAL {$seconds} SECOND)";
        }
        return "DATEADD(SECOND, -{$seconds}, GETDATE())";
    }
}
if (!function_exists('register_user_session')) {
    function register_user_session($uid, string $token, string $sessionId, ?string $ip = null, ?string $ua = null): string
    {
        $db  = _user_sessions_db();
        $uid = (int) $uid;

        // Global cleanup of expired/idle active rows (>2h)
        try {
            $now    = _sess_now_sql();
            $cutoff = _sess_cutoff_sql(7200);
            $db->query(
                "UPDATE user_sessions
                    SET is_active = 0, revoke_reason = 'expired', revoked_at = {$now}
                  WHERE is_active = 1
                    AND COALESCE(last_activity, created_at) < {$cutoff}"
            );
        } catch (\Throwable $e) {
            log_message('error', 'register_user_session expire-flag failed: ' . $e->getMessage());
        }

        $db->table('user_sessions')
            ->set('uid', $uid)
            ->set('token', $token)
            ->set('session_id', $sessionId)
            ->set('ip_address', $ip !== null ? substr($ip, 0, 45) : null)
            ->set('user_agent', $ua !== null ? substr($ua, 0, 255) : null)
            ->set('is_active', 1)
            ->set('created_at', _sess_now_sql(), false)
            ->set('last_activity', _sess_now_sql(), false)
            ->insert();

        return $token;
    }
}
if (!function_exists('validate_user_session')) {
    function validate_user_session($uid, ?string $token, string $currentSessionId): bool
    {
        if (empty($token)) {
            return false;
        }
        $db  = _user_sessions_db();
        $uid = (int) $uid;

        $row = $db->table('user_sessions')
            ->select('id, session_id')
            ->where('uid', $uid)
            ->where('token', $token)
            ->where('is_active', 1)
            ->get()->getRowArray();

        if (!$row) {
            return false;
        }

        $builder = $db->table('user_sessions')
            ->where('id', $row['id'])
            ->set('last_activity', _sess_now_sql(), false);
        if ($row['session_id'] !== $currentSessionId) {
            $builder->set('session_id', $currentSessionId);
        }
        $builder->update();

        return true;
    }
}
if (!function_exists('forget_user_session')) {
    function forget_user_session(?string $token): void
    {
        if (empty($token)) {
            return;
        }
        try {
            _user_sessions_db()->table('user_sessions')
                ->where('token', $token)
                ->where('is_active', 1)
                ->set('is_active', 0)
                ->set('revoke_reason', 'logout')
                ->set('revoked_at', _sess_now_sql(), false)
                ->update();
        } catch (\Throwable $e) {
            log_message('error', 'forget_user_session failed: ' . $e->getMessage());
        }
    }
}
if (!function_exists('kill_user_sessions')) {
    function kill_user_sessions($userId, bool $keepCurrent = true, string $reason = 'password_change'): int   // tf-stage10: the caller names the reason (default unchanged for the existing callers)
    {
        $reason = preg_replace('/[^a-z0-9_]/', '', strtolower($reason)) ?: 'password_change';
        $db         = _user_sessions_db();
        $userId     = (int) $userId;
        $curToken   = session()->get('sess_token');
        $sessPrefix = config('Session')->cookieName . ':';

        $rows = $db->table('user_sessions')
            ->where('uid', $userId)
            ->where('is_active', 1)
            ->get()->getResultArray();

        $idsToRevoke = [];
        $killed      = 0;

        foreach ($rows as $row) {
            if ($keepCurrent && $curToken && $row['token'] === $curToken) {
                continue;
            }
            if (!empty($row['session_id'])) {
                try {
                    $db->table('ci_sessions')
                        ->where('id', $sessPrefix . $row['session_id'])
                        ->delete();
                } catch (\Throwable $e) {
                    log_message('error', 'kill_user_sessions ci_sessions delete failed: ' . $e->getMessage());
                }
            }
            $idsToRevoke[] = $row['id'];
            $killed++;
        }

        if (!empty($idsToRevoke)) {
            $db->table('user_sessions')->whereIn('id', $idsToRevoke)
                ->set('is_active', 0)
                ->set('revoke_reason', substr($reason, 0, 40))
                ->set('revoked_at', _sess_now_sql(), false)
                ->update();
        }

        log_message('info', "kill_user_sessions: uid={$userId} revoked={$killed} keepCurrent=" . ($keepCurrent ? '1' : '0'));
        return $killed;
    }
}
