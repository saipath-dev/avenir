<?php

namespace App\Libraries\Help;

/**
 * tf-help-r1 — what the signed-in person may open, read the same way UserPermissionFilter decides it
 * (the menu entries granted to the person, through the user_priv_q view; a path is allowed when it
 * equals or sits under a granted menu URL; group 1, System Administrator, may open everything).
 * Read-only: SELECTs only.
 */
class HelpAccess
{
    /** Paths every signed-in person may open (UserPermissionFilter's own exceptions, and this help). */
    private const FREE = ['help', 'dashboard', 'my_profile', 'navigation', 'message-center', 'notification', '403'];

    private int $group;
    /** @var string[] */
    private array $urls = [];
    private string $groupName = '';
    private $db;
    private array $holderCache = [];
    private array $nameCache = [];

    public function __construct($db = null, ?array $sessionData = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $s = $sessionData ?? [
            'group_id' => session()->get('group_id'),
            'emp_id'   => session()->get('emp_id'),
            'user_id'  => session()->get('user_id'),
        ];
        $this->group = (int) ($s['group_id'] ?? 0);
        try {
            $rows = $this->db->query('SELECT DISTINCT url, type FROM user_priv_q WHERE emp_id = ? AND user_id = ?', [$s['emp_id'] ?? '', $s['user_id'] ?? 0])->getResultArray();
            foreach ($rows as $r) {
                if ((string) $r['url'] !== '') {
                    $this->urls[] = trim((string) $r['url'], '/');
                }
                $this->groupName = (string) ($r['type'] ?? $this->groupName);
            }
            if ($this->groupName === '' && $this->group) {
                $g = $this->db->query('SELECT type FROM group_profile WHERE id = ?', [$this->group])->getRowArray();
                $this->groupName = (string) ($g['type'] ?? '');
            }
        } catch (\Throwable $e) {
            // no menu tables (a bare test database): only the free paths and System Administrator
        }
    }

    public function isAdmin(): bool
    {
        return $this->group === 1;
    }

    public function groupName(): string
    {
        return $this->groupName;
    }

    /** May this person open $path (a route path, query string allowed)? */
    public function allowed(string $path): bool
    {
        $path = trim((string) parse_url($path, PHP_URL_PATH), '/');
        if ($this->isAdmin() || $path === '') {
            return true;
        }
        foreach (self::FREE as $f) {
            if ($path === $f || str_starts_with($path, $f . '/')) {
                return true;
            }
        }
        // the group-privilege page carries its id in the URL (as UserPermissionFilter treats it)
        if (str_starts_with($path, 'settings/group_privilege')) {
            $path = 'settings/group_privilege/0';
        }
        foreach ($this->urls as $u) {
            if ($path === $u || str_starts_with($path, rtrim($u, '/') . '/')) {
                return true;
            }
        }
        return false;
    }

    /** The user groups that hold a menu URL — who to ask. */
    public function holders(string $url): array
    {
        $url = trim($url, '/');
        if (!isset($this->holderCache[$url])) {
            $names = [];
            try {
                foreach ($this->db->query('SELECT DISTINCT type FROM user_priv_q WHERE url = ? ORDER BY type', [$url])->getResultArray() as $r) {
                    if ((string) $r['type'] !== '') {
                        $names[] = (string) $r['type'];
                    }
                }
            } catch (\Throwable $e) {
            }
            if (!in_array('System Administrator', $names, true)) {
                $names[] = 'System Administrator';
            }
            $this->holderCache[$url] = $names;
        }
        return $this->holderCache[$url];
    }

    /** The menu URL a path belongs to (the longest granted-style prefix in the menu table), or the path itself. */
    public function menuFor(string $path): string
    {
        $path = trim((string) parse_url($path, PHP_URL_PATH), '/');
        try {
            $r = $this->db->query("SELECT url FROM menu_list WHERE del = 0 AND url <> '' AND (url = ? OR ? LIKE CONCAT(url, '/%')) ORDER BY LENGTH(url) DESC LIMIT 1", [$path, $path])->getRowArray();
            if ($r) {
                return trim((string) $r['url'], '/');
            }
        } catch (\Throwable $e) {
        }
        return $path;
    }

    /** The menu entry's own name for a URL, from the menu table. */
    public function menuName(string $url): ?string
    {
        $url = trim($url, '/');
        if (!array_key_exists($url, $this->nameCache)) {
            $this->nameCache[$url] = null;
            try {
                $r = $this->db->query('SELECT name FROM menu_list WHERE url = ? AND del = 0 ORDER BY id LIMIT 1', [$url])->getRowArray();
                $this->nameCache[$url] = $r ? (string) $r['name'] : null;
            } catch (\Throwable $e) {
            }
        }
        return $this->nameCache[$url];
    }
}
