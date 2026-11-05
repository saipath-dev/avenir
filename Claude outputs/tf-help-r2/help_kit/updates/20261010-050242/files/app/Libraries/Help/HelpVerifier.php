<?php

namespace App\Libraries\Help;

/**
 * tf-help-r1 — checks every help topic against the system as it is now, so the manual is never
 * silently out of date after a change:
 *   view / file  the screen or library still shows each button / message name the steps quote
 *   route        the screen or action still exists (GET unless "method" says otherwise)
 *   menu         the menu entry (permission) still exists
 *   links        every link in a step opens an existing screen
 *   images       every picture the topic or guide shows is present
 * Runs from `php spark help:check` or the System Administrator's help/check page. Reads only;
 * the result is kept in writable/help/check.json for the panel's "checked" line.
 */
class HelpVerifier
{
    private array $routes = [];

    public function __construct(private HelpManual $manual, private $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $rc = service('routes');
        // CodeIgniter 4.5 keeps verbs in capitals, older versions in small letters: ask both
        $list = static fn ($v) => array_merge(array_keys($rc->getRoutes(strtoupper($v), false)), array_keys($rc->getRoutes(strtolower($v), false)));
        if (!$list('get')) {
            $rc->loadRoutes();
        }
        foreach (['get', 'post'] as $verb) {
            $this->routes[$verb] = array_values(array_unique(array_map(static fn ($k) => ltrim((string) $k, '/'), $list($verb))));
        }
    }

    public function run(): array
    {
        $out = [];
        $okCount = 0;
        foreach ($this->manual->topics() as $id => $t) {
            $problems = [];
            foreach ((array) ($t['verify'] ?? []) as $v) {
                $problems = array_merge($problems, $this->check($v));
            }
            foreach ((array) ($t['steps'] ?? []) as $i => $s) {
                if (!empty($s['link']['path']) && !$this->routeExists((string) parse_url($s['link']['path'], PHP_URL_PATH), 'get')) {
                    $problems[] = 'Step ' . ($i + 1) . ' links to a screen that no longer exists: ' . $s['link']['path'];
                }
            }
            foreach ((array) ($t['images'] ?? []) as $im) {
                if (!is_file(APPPATH . 'Help/images/' . ($im['file'] ?? ''))) {
                    $problems[] = 'Missing picture: ' . ($im['file'] ?? '');
                }
            }
            $ok = !$problems;
            $okCount += $ok ? 1 : 0;
            $out[] = ['id' => $id, 'section' => $t['section_title'], 'title' => $t['title'], 'ok' => $ok, 'problems' => $problems];
        }
        // the step-by-step guides' pictures
        $guide = [];
        foreach ($this->manual->sections() as $s) {
            foreach ((array) ($s['guide']['stages'] ?? []) as $st) {
                foreach ((array) ($st['steps'] ?? []) as $g) {
                    if (!empty($g['image']) && !is_file(APPPATH . 'Help/images/' . $g['image'])) {
                        $guide[] = 'Missing picture in ' . $s['title'] . ' guide: ' . $g['image'];
                    }
                }
            }
        }
        $result = [
            'checked_at' => date('Y-m-d H:i:s'),
            'version' => HelpManual::VERSION,
            'total' => count($out),
            'ok' => $okCount,
            'guide_problems' => $guide,
            'topics' => $out,
        ];
        $dir = WRITEPATH . 'help';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/check.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $result;
    }

    private function check(array $v): array
    {
        $p = [];
        if (!empty($v['view']) || !empty($v['file'])) {
            $rel = (string) ($v['view'] ?? $v['file']);
            $file = ROOTPATH . $rel;
            if (!is_file($file)) {
                return ['File not found: ' . $rel];
            }
            // the words as a person sees them: HTML entities decoded, JavaScript string escapes (\\' \\") undone
            $text = str_replace(["\\'", '\\"'], ["'", '"'], html_entity_decode((string) file_get_contents($file), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            foreach ((array) ($v['has'] ?? []) as $label) {
                if (!str_contains($text, (string) $label)) {
                    $p[] = '"' . $label . '" is no longer on ' . basename($rel);
                }
            }
        }
        if (!empty($v['route']) && !$this->routeExists((string) $v['route'], strtolower((string) ($v['method'] ?? 'get')))) {
            $p[] = 'Route not found: ' . strtoupper((string) ($v['method'] ?? 'get')) . ' ' . $v['route'];
        }
        if (!empty($v['menu'])) {
            try {
                $r = $this->db->query('SELECT id FROM menu_list WHERE url = ? AND del = 0 LIMIT 1', [(string) $v['menu']])->getRowArray();
                if (!$r) {
                    $p[] = 'Menu entry (permission) not found: ' . $v['menu'];
                }
            } catch (\Throwable $e) {
                $p[] = 'Could not read the menu table: ' . $e->getMessage();
            }
        }
        return $p;
    }

    /** Does a route exist for this path? Placeholders in $path ((:num), (:any)…) are tried as samples. */
    public function routeExists(string $path, string $verb = 'get'): bool
    {
        $path = trim($path, '/');
        $sample = str_replace(['(:num)', '(:any)', '(:segment)', '(:alpha)', '(:alphanum)', '(:hash)'], ['1', 'x', 'x', 'x', 'x1', 'x'], $path);
        foreach ($this->routes[$verb] ?? [] as $key) {
            if ($key === $path || $key === $sample) {
                return true;
            }
            if (@preg_match('#^' . $key . '$#u', $sample) === 1) {
                return true;
            }
        }
        return false;
    }
}
