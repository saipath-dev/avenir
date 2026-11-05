<?php

namespace App\Libraries\Help;

/**
 * tf-help-r2 — a topic shaped for the person asking: steps in the exact screen and button names,
 * links to the screens they may open, and, where they lack the permission, who can do it instead.
 * Links are plain navigation; nothing here performs an action.
 */
class HelpAnswer
{
    public function __construct(private HelpManual $manual, private HelpAccess $access)
    {
    }

    public function topic(array $t): array
    {
        $needs = [];
        foreach ((array) ($t['needs'] ?? []) as $url) {
            $needs[] = $this->need((string) $url);
        }
        $ok = !array_filter($needs, static fn ($n) => !$n['allowed']);
        $note = '';
        if (!$ok) {
            $missing = array_values(array_filter($needs, static fn ($n) => !$n['allowed']));
            $note = HelpManual::md((string) ($t['needs_note'] ?? 'You do not have the permission this needs.'));
            $part = (array) ($t['needs_note_partial'] ?? []);
            if (!empty($part['if_allowed']) && $this->access->allowed((string) $part['if_allowed'])) {
                $note .= ' ' . HelpManual::md((string) ($part['text'] ?? ''));
            }
            $note .= ' <span class="avh-who">Who can do it: ' . htmlspecialchars(implode(', ', $missing[0]['holders']), ENT_QUOTES, 'UTF-8') . '.</span>';
        }
        $steps = [];
        foreach ((array) ($t['steps'] ?? []) as $s) {
            $step = ['html' => HelpManual::md((string) ($s['text'] ?? ''))];
            if (!empty($s['needs']) && !$this->access->allowed((string) $s['needs'])) {
                $n = $this->need((string) $s['needs']);
                $step['locked'] = ['name' => $n['name'], 'holders' => $n['holders']];
            }
            if (!empty($s['link']['path'])) {
                $step['link'] = $this->link((string) $s['link']['path'], (string) ($s['link']['label'] ?? 'Open'));
            }
            $steps[] = $step;
        }
        $images = [];
        foreach ((array) ($t['images'] ?? []) as $im) {
            if (!empty($im['file']) && is_file(APPPATH . 'Help/images/' . $im['file'])) {
                $images[] = ['src' => base_url('help/img/' . $im['file']), 'caption' => (string) ($im['caption'] ?? '')];
            }
        }
        $related = [];
        foreach ((array) ($t['related'] ?? []) as $rid) {
            if ($r = $this->manual->topic((string) $rid)) {
                $related[] = ['id' => $r['id'], 'title' => $r['title']];
            }
        }
        return [
            'id' => $t['id'],
            'title' => (string) $t['title'],
            'section' => (string) $t['section_title'],
            'section_id' => (string) $t['section'],
            'summary_html' => HelpManual::md((string) ($t['summary'] ?? '')),
            'audience' => (string) ($t['audience'] ?? ''),
            'access' => ['ok' => $ok, 'group' => $this->access->groupName(), 'needs' => $needs, 'note_html' => $note],
            'steps' => $steps,
            'notes' => array_map(static fn ($n) => HelpManual::md((string) $n), (array) ($t['notes'] ?? [])),
            'images' => $images,
            'related' => $related,
            'manual_ref' => (string) ($t['manual_ref'] ?? ''),
            'checked' => (string) ($this->manual->sections()[$t['section']]['checked'] ?? ''),
        ];
    }

    public function brief(array $t): array
    {
        return ['id' => $t['id'], 'title' => (string) $t['title'], 'section' => (string) $t['section_title'], 'section_id' => (string) $t['section']];
    }

    private function need(string $url): array
    {
        return [
            'url' => $url,
            'name' => $this->manual->menuName($url, $this->access),
            'allowed' => $this->access->allowed($url),
            'holders' => $this->access->holders($url),
        ];
    }

    private function link(string $path, string $label): array
    {
        $allowed = $this->access->allowed($path);
        $out = ['href' => base_url($path), 'label' => $label, 'allowed' => $allowed];
        if (!$allowed) {
            $menu = $this->access->menuFor($path);
            $out['title'] = 'You do not have access to ' . $this->manual->menuName($menu, $this->access) . '. Who can open it: ' . implode(', ', $this->access->holders($menu)) . '.';
        }
        return $out;
    }
}
