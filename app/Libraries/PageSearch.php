<?php

namespace App\Libraries;

/**
 * tf-search-r1 (22 Oct 2026) — the "Search pages…" list for the signed-in user.
 *
 * Grants nothing. The list is exactly what the user may already open:
 *   - every sidebar entry the user's group holds (Settings → Group Privileges; the same rows and the same
 *     show_in_menu_bar rule the sidebar uses, so action-only privileges are never offered as pages);
 *   - the Home desks' pages, already filtered by HomeDesks::forUser (the same rule as UserPermissionFilter);
 *   - Home and the Pilot task dashboard, open to every signed-in user.
 * Opening a result is an ordinary link: UserPermissionFilter checks it again on the server.
 */
final class PageSearch
{
    /** @return array<int, array{l:string,u:string,a:string}> label, url (relative), area */
    public static function forUser(array $menu): array
    {
        $out = [];
        $add = static function (string $url, string $label, string $area) use (&$out): void {
            $url = trim($url, '/ ');
            $label = trim(preg_replace('/\s+/', ' ', strip_tags($label)));
            if ($url === '' || $label === '' || isset($out[$url])) {
                return;
            }
            $out[$url] = ['l' => $label, 'u' => $url, 'a' => $area];
        };

        $add('home', 'Home — Avenir Holidays Handling System', 'Start');
        $add('dashboard2/tiles', 'Dashboard — my tasks (Pilot)', 'Start');
        $out['home']['k'] = 1;
        $out['dashboard2/tiles']['k'] = 1;

        $cats = [];
        foreach ((array) ($menu['allowed_categories'] ?? []) as $c) {
            $c = (array) $c;
            $cats[(string) ($c['cat_id'] ?? '')] = (string) ($c['category'] ?? '');
        }
        foreach ((array) ($menu['allowed_menu_list'] ?? []) as $m) {
            $m = (array) $m;
            if ((int) ($m['show_in_menu_bar'] ?? 0) === 1) {   // 1 = hidden from the sidebar: an action right, not a page
                continue;
            }
            $add((string) ($m['url'] ?? ''), (string) ($m['menu'] ?? ''), $cats[(string) ($m['cat_id'] ?? '')] ?? 'Menu');
        }

        $home = HomeDesks::forUser($menu);
        $walk = static function (array $items, string $area) use (&$walk, $add, &$out): void {
            foreach ($items as $it) {
                if (isset($it['items'])) {
                    $walk($it['items'], $area);
                } elseif (strpos((string) $it['url'], '?') === false) {   // the desks' "New inquiry" shortcuts are actions on a page, not pages
                    $add((string) $it['url'], (string) $it['label'], $area);
                    $ku = trim((string) $it['url'], '/ ');
                    if (isset($out[$ku])) { $out[$ku]['k'] = 1; }   // a desk's main page: ranked first among equal matches
                }
            }
        };
        foreach ($home['desks'] as $d) {
            $walk($d['items'], $d['name']);
        }
        foreach ($home['links'] as $l) {
            $add((string) $l['url'], (string) $l['label'], 'Start');
        }
        // the page this list is most often asked for by name
        if (isset($out['dashboard2/transport/driver_allow/pending_tours'])) {
            $out['dashboard2/transport/driver_allow/pending_tours']['l'] = 'Pending Driver Allocation';
        }
        return array_values($out);
    }
}
