<?php

namespace App\Libraries\Help;

/**
 * tf-help-r1 — the in-system User Manual.
 *
 * One source for everything: the JSON files in app/Help/sections/ (one per section of the system).
 * The help panel, the "Help for this screen" list and the printable PDF are all built from them, so
 * they can never disagree. Each topic carries the exact screen and button names, the link to the
 * screen, the permission (menu URL) the action needs, and "verify" checks that HelpVerifier runs
 * against the code (php spark help:check) so a system change that alters a screen is caught.
 *
 * Reading only: nothing here writes to the database. Questions are matched against the checked
 * topics by meaning words (a small, fixed vocabulary) — no guessing: when no topic fits well
 * enough the answer says so and offers the nearest topics and the printable manual.
 */
class HelpManual
{
    public const VERSION = 'tf-help-r1';

    /** @var array<string, array> section id => section */
    private array $sections = [];
    /** @var array<string, array> topic id => topic (with 'section' and 'section_title') */
    private array $topics = [];
    /** @var array<string, int> concept => number of topics using it */
    private array $df = [];

    // ---- the vocabulary: phrases first, then words, both mapped to meaning words (concepts) ----
    private const PHRASES = [
        'check hotels again' => 'refresh', 'check hotel again' => 'refresh', 'check the hotels again' => 'refresh',
        'not showing' => 'missing', 'not show' => 'missing', 'not shown' => 'missing', 'not appearing' => 'missing',
        'not appear' => 'missing', 'not visible' => 'missing', 'not there' => 'missing', 'not coming' => 'missing',
        'not pulling' => 'missing', 'no rate' => 'missing', 'no rates' => 'missing', 'no price' => 'missing',
        'no prices' => 'missing', 'cannot see' => 'missing', 'can not see' => 'missing', 'do not see' => 'missing',
        'does not show' => 'missing', 'did not show' => 'missing', 'still has no' => 'missing', 'is missing' => 'missing',
        'are missing' => 'missing', 'rate missing' => 'missing', 'unpriceable' => 'missing', 'not priced' => 'missing',
        'user manual' => 'manualdoc', 'printable' => 'manualdoc', 'the manual' => 'manualdoc', 'pdf manual' => 'manualdoc',
        'manual pdf' => 'manualdoc', 'handbook' => 'manualdoc', 'manual as a pdf' => 'manualdoc',
        'try a quote' => 'pricecheck', 'rate on a date' => 'pricecheck', 'how much' => 'pricecheck', 'price for' => 'pricecheck',
        'price on' => 'pricecheck', 'cost on' => 'pricecheck', 'for a date' => 'pricecheck', 'certain night' => 'pricecheck',
        'room type' => 'roomtype', 'room types' => 'roomtype',
        'who can' => 'permission', 'who is allowed' => 'permission', 'not allowed' => 'permission', 'no access' => 'permission',
        'no permission' => 'permission', 'access denied' => 'permission', 'forbidden' => 'permission', '403' => 'permission',
        'do not have access' => 'permission', 'cannot open' => 'permission', 'approve button' => 'permission approve',
        'sign off' => 'approve', 'signoff' => 'approve', 'approval queue' => 'approve',
        'next year' => 'nextyear', 'last year' => 'nextyear', 'same rates' => 'nextyear', 'same rate' => 'nextyear',
        'carried forward' => 'nextyear', 'carry forward' => 'nextyear', 'not sent' => 'nextyear', 'next season' => 'nextyear',
        'old screen' => 'oldscreen', 'old hotel rate' => 'oldscreen', 'old page' => 'oldscreen',
        'by hand' => 'manualentry', 'type in' => 'manualentry', 'key in' => 'manualentry', 'without a pdf' => 'manualentry',
        'two times' => 'duplicate', 'two copies' => 'duplicate', 'same sheet' => 'duplicate', 'same rate sheet' => 'duplicate', 'two names' => 'duplicate',
        'nothing happened' => 'warning', 'nothing happens' => 'warning', 'does nothing' => 'warning', 'cannot approve yet' => 'error approve',
        'rate sheet' => 'sheet', 'rate sheets' => 'sheet', 'tariff sheet' => 'sheet',
        'excel sheet' => 'excel', 'excel file' => 'excel',
        'ending soon' => 'expiry', 'run out' => 'expiry', 'running out' => 'expiry', 'ends soon' => 'expiry',
        'every morning' => 'expiry', 'morning check' => 'expiry',
        'which plan' => 'market pricecheck', 'japanese client' => 'market',
    ];

    private const WORDS = [
        'approve' => ['approve', 'approval', 'approvals', 'approving', 'approver', 'authorise', 'authorize', 'authorised', 'authorized', 'authorisation', 'authorization', 'publish'],
        'upload' => ['upload', 'uploads', 'uploading', 'uploaded', 'import', 'importing', 'imported', 'read', 'load', 'loading', 'attach', 'scan', 'put'],
        'approvedstate' => ['approved'],
        'choose' => ['choose', 'choosing', 'pick', 'picking', 'select', 'selecting'],
        'pdf' => ['pdf', 'pdfs'],
        'excel' => ['excel', 'xlsx', 'xls', 'spreadsheet', 'workbook', 'csv', 'template'],
        'sheet' => ['sheet', 'sheets', 'tariff', 'tariffs', 'contract', 'contracts', 'file', 'files', 'document'],
        'manualentry' => ['manually', 'hand', 'type', 'typing', 'key', 'keying', 'enter', 'entering', 'input'],
        'new' => ['new', 'create', 'creating', 'add', 'adding'],
        'correct' => ['correct', 'correcting', 'correction', 'fix', 'fixing', 'fixed', 'edit', 'editing', 'change', 'changing', 'changed', 'amend', 'amending', 'amendment', 'update', 'updating', 'modify', 'wrong', 'mistake', 'incorrect'],
        'duplicate' => ['duplicate', 'duplicates', 'duplicated', 'twice', 'double'],
        'retire' => ['supersede', 'superseded', 'superseding', 'retire', 'retiring', 'old', 'replace', 'replaced', 'outdated', 'archive'],
        'delete' => ['delete', 'deleting', 'remove', 'removing', 'discard'],
        'draft' => ['draft', 'drafts', 'pending', 'waiting'],
        'review' => ['review', 'reviewing', 'check', 'checking', 'verify', 'compare', 'inspect'],
        'inquiry' => ['inquiry', 'inquiries', 'enquiry', 'enquiries', 'inq', 'quotation', 'quotations', 'quote', 'quotes', 'tour', 'tours'],
        'missing' => ['missing', 'blank', 'empty', 'unpriced'],
        'refresh' => ['refresh', 'recheck', 'reload', 'rerun', 'again'],
        'roomtype' => ['room', 'rooms', 'roomtype', 'deluxe', 'superior', 'suite', 'standard'],
        'market' => ['market', 'markets', 'country', 'countries', 'japan', 'japanese', 'jp', 'china', 'chinese', 'europe', 'european', 'asia', 'asian', 'nationality'],
        'season' => ['season', 'seasons', 'winter', 'summer', 'peak', 'festive', 'period', 'date', 'dates', 'night', 'nights'],
        'price' => ['rate', 'rates', 'price', 'prices', 'cost', 'costs', 'amount', 'figure'],
        'hotel' => ['hotel', 'hotels', 'resort', 'property'],
        'warning' => ['warning', 'warnings', 'popup', 'alert', 'w09'],
        'error' => ['error', 'errors', 'blocked', 'fail', 'failed'],
        'expiry' => ['expire', 'expires', 'expiry', 'expired', 'expiring', 'ending', 'renew', 'renewal', 'morning'],
        'nextyear' => ['carried'],
        'permission' => ['permission', 'permissions', 'access', 'privilege', 'privileges', 'allowed', 'rights', 'role'],
        'help' => ['help', 'guide', 'use'],
        'manualdoc' => ['print', 'printing', 'download', 'booklet', 'book'],
        'pricecheck' => ['coverage'],
        'oldscreen' => ['legacy', 'locked'],
    ];

    private const STOP = ['where', 'do', 'does', 'did', 'i', 'we', 'you', 'go', 'goes', 'to', 'how', 'the', 'a', 'an', 'is', 'are', 'am', 'was', 'were', 'can',
        'could', 'should', 'my', 'our', 'in', 'on', 'at', 'for', 'of', 'what', 'why', 'when', 'it', 'its', 'this', 'that', 'these', 'those', 'me', 'please',
        'be', 'been', 'and', 'or', 'with', 'from', 'into', 'there', 'here', 'which', 'get', 'got', 'need', 'want', 'have', 'has', 'will', 'would',
        'about', 'any', 'some', 'up', 'so', 'as', 'by', 'still', 'yet', 'now', 'then', 'one', 'also', 'just', 'all', 'not', 'no', 'but', 'if', 'own',
        'see', 'find', 'open', 'screen', 'page', 'system', 'avenir', 'mean', 'means', 'say', 'says', 'said', 'should', 'click', 'button', 'way', 'tell',
        'colleague', 'someone', 'something', 'thing', 'things', 'same', 'certain', 'between', 'before', 'after', 'they', 'them', 'their', 'us', 'he', 'she'];

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function __construct(?string $dir = null)
    {
        $dir = $dir ?? (APPPATH . 'Help/sections');
        $files = glob(rtrim($dir, '/\\') . '/*.json') ?: [];
        foreach ($files as $f) {
            $s = json_decode((string) file_get_contents($f), true);
            if (!is_array($s) || empty($s['id'])) {
                continue;
            }
            $s['_file'] = $f;
            $this->sections[$s['id']] = $s;
        }
        uasort($this->sections, static fn ($a, $b) => ((int) ($a['order'] ?? 50)) <=> ((int) ($b['order'] ?? 50)));
        foreach ($this->sections as $sid => $s) {
            foreach ((array) ($s['topics'] ?? []) as $i => $t) {
                if (empty($t['id'])) {
                    continue;
                }
                $t['section'] = $sid;
                $t['section_title'] = (string) ($s['title'] ?? $sid);
                $t['_order'] = $i;
                $t['_text'] = $this->concepts(implode(' ', array_merge([(string) ($t['title'] ?? '')], (array) ($t['questions'] ?? []))))['concepts'];
                $t['_qsets'] = array_map(fn ($q) => $this->conceptKey($this->concepts((string) $q)['concepts']), (array) ($t['questions'] ?? []));
                $this->topics[$t['id']] = $t;
                foreach (array_unique(array_merge(array_keys((array) ($t['keywords'] ?? [])), $t['_text'])) as $c) {
                    $this->df[$c] = ($this->df[$c] ?? 0) + 1;
                }
            }
        }
    }

    public function sections(): array
    {
        return $this->sections;
    }

    public function topics(): array
    {
        return $this->topics;
    }

    public function topic(string $id): ?array
    {
        return $this->topics[$id] ?? null;
    }

    /** The newest "checked" date across sections — shown in the panel and on the PDF. */
    public function checkedDate(): string
    {
        $d = '';
        foreach ($this->sections as $s) {
            $d = max($d, (string) ($s['checked'] ?? ''));
        }
        return $d;
    }

    /** A fingerprint of the manual's sources (sections + images + this library) — the PDF cache key. */
    public function fingerprint(): string
    {
        $parts = [self::VERSION];
        foreach ($this->sections as $s) {
            $parts[] = md5_file($s['_file']);
        }
        foreach (glob(APPPATH . 'Help/images/*/*') ?: [] as $f) {
            $parts[] = basename($f) . ':' . filesize($f);
        }
        return substr(md5(implode('|', $parts)), 0, 16);
    }

    // ------------------------------------------------------------------
    //  Understanding a question
    // ------------------------------------------------------------------

    /** @return array{concepts: string[], unknown: string[]} */
    public function concepts(string $text): array
    {
        $t = mb_strtolower($text);
        $t = str_replace(['’', '‘', '`'], "'", $t);
        $t = preg_replace(["/can't/", "/cannot/", "/won't/", "/n't\\b/"], ['cannot', 'cannot', 'will not', ' not'], $t);
        $t = ' ' . preg_replace('/[^a-z0-9]+/', ' ', $t) . ' ';
        $found = [];
        // phrases, longest first
        $phrases = self::PHRASES;
        uksort($phrases, static fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($phrases as $p => $c) {
            if (str_contains($t, ' ' . $p . ' ')) {
                foreach (explode(' ', $c) as $cc) {
                    $found[] = $cc;
                }
                $t = str_replace(' ' . $p . ' ', ' ', $t);
            }
        }
        $tokens = array_values(array_filter(explode(' ', trim($t)), static fn ($w) => $w !== ''));
        // "manual" alone: the printable manual, unless the question is about entering rates
        $entryWords = ['enter', 'type', 'typing', 'key', 'input', 'create', 'add', 'rate', 'rates', 'price', 'prices', 'plan'];
        $unknown = [];
        foreach ($tokens as $w) {
            if ($w === 'manual') {
                $found[] = array_intersect($tokens, $entryWords) && !array_intersect($tokens, ['user', 'print', 'pdf', 'download']) ? 'manualentry' : 'manualdoc';
                continue;
            }
            if (in_array($w, self::STOP, true)) {
                continue;
            }
            $c = $this->wordConcept($w);
            if ($c === null) {
                foreach (['ing', 'ed', 'es', 's'] as $suf) {
                    if (strlen($w) > strlen($suf) + 2 && str_ends_with($w, $suf)) {
                        $c = $this->wordConcept(substr($w, 0, -strlen($suf)));
                        if ($c !== null) {
                            break;
                        }
                    }
                }
            }
            if ($c === null) {
                if (strlen($w) > 2 && !ctype_digit($w)) {
                    $unknown[] = $w;
                }
                continue;
            }
            $found[] = $c;
        }
        return ['concepts' => array_values(array_unique($found)), 'unknown' => array_values(array_unique($unknown))];
    }

    /** Where in the question the first word for any of these concepts stands (for two-part questions). */
    private function firstPos(string $q, array $concepts): int
    {
        $t = ' ' . preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($q)) . ' ';
        $best = PHP_INT_MAX;
        foreach ($concepts as $c) {
            foreach (self::WORDS[$c] ?? [] as $w) {
                $p = strpos($t, ' ' . $w . ' ');
                if ($p !== false && $p < $best) {
                    $best = $p;
                }
            }
            foreach (self::PHRASES as $ph => $pc) {
                if (in_array($c, explode(' ', $pc), true)) {
                    $p = strpos($t, ' ' . $ph . ' ');
                    if ($p !== false && $p < $best) {
                        $best = $p;
                    }
                }
            }
        }
        return $best;
    }

    private function wordConcept(string $w): ?string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (self::WORDS as $c => $ws) {
                foreach ($ws as $x) {
                    $map[$x] = $c;
                }
            }
        }
        return $map[$w] ?? null;
    }

    private function conceptKey(array $c): string
    {
        sort($c);
        return implode(' ', $c);
    }

    /**
     * Match a question against the checked topics.
     * @return array{status: string, ranked: array<int, array{id: string, score: float}>, concepts: string[], unknown: string[]}
     *   status: answer (one topic fits clearly) | unsure (several could) | none (nothing checked fits)
     */
    public function search(string $q, string $path = ''): array
    {
        $an = $this->concepts($q);
        $Q = $an['concepts'];
        $n = max(1, count($this->topics));
        $key = $this->conceptKey($Q);
        $ranked = [];
        foreach ($this->topics as $id => $t) {
            $kw = (array) ($t['keywords'] ?? []);
            $s = 0.0;
            $hit = 0;
            $strong = [];
            foreach ($Q as $c) {
                $idf = log(1 + $n / (1 + ($this->df[$c] ?? 0)));
                if (isset($kw[$c])) {
                    $s += (float) $kw[$c] * $idf;
                    $hit++;
                    if ((float) $kw[$c] >= 3) {
                        $strong[] = $c;   // a word the topic is mainly about
                    }
                } elseif (in_array($c, $t['_text'], true)) {
                    $s += 0.6 * $idf;
                    $hit++;
                }
            }
            if ($s <= 0) {
                continue;
            }
            $coverage = $hit / max(1, count($Q) + 0.5 * count($an['unknown']));
            $score = $s * (0.4 + 0.6 * $coverage);
            if ($key !== '' && in_array($key, $t['_qsets'], true)) {
                $score += 3.0;   // the question is one of the topic's own example questions (by meaning)
            }
            if ($path !== '' && $this->onPage($t, $path)) {
                $score *= 1.12;  // a tie between two topics goes to the screen the person is on
            }
            $ranked[] = ['id' => $id, 'score' => round($score, 3), 'strong' => $strong];
        }
        usort($ranked, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $best = $ranked[0] ?? null;
        $second = $ranked[1] ?? null;
        $bs = $best['score'] ?? 0;
        $ss = $second['score'] ?? 0;
        $also = null;
        if (!$best || !$best['strong']) {
            // nothing the manual is mainly about was asked: never answer from a passing word
            $status = ($best && $bs >= 1.6 && !$an['unknown']) ? 'unsure' : 'none';
        } elseif ($bs >= 3.0 && $ss < 0.8 * $bs) {
            $status = 'answer';
        } elseif ($bs >= 1.6) {
            $status = 'unsure';
        } else {
            $status = 'none';
        }
        // a question in two parts ("… manually or correct a rate?"): answer both when each part
        // clearly matches its own topic
        if ($second && $second['strong'] && $best['strong'] && in_array($status, ['answer', 'unsure'], true)
            && preg_match('/\b(or|and|also|then)\b|,/i', $q)
            && !array_intersect($best['strong'], $second['strong']) && $ss >= 0.45 * $bs) {
            $status = 'answer';
            $also = $second['id'];
            // answer the parts in the order they were asked
            if ($this->firstPos($q, $second['strong']) < $this->firstPos($q, $best['strong'])) {
                [$ranked[0], $ranked[1]] = [$ranked[1], $ranked[0]];
                $also = $best['id'];
            }
        }
        return ['status' => $status, 'ranked' => array_slice($ranked, 0, 5), 'also' => $also, 'concepts' => $Q, 'unknown' => $an['unknown']];
    }

    // ------------------------------------------------------------------
    //  Help for this screen
    // ------------------------------------------------------------------

    public function onPage(array $t, string $path): bool
    {
        $path = trim($path, '/');
        foreach ((array) ($t['pages'] ?? []) as $p) {
            $p = trim((string) $p, '/');
            if ($p === '*' ) {
                continue;
            }
            if ($path === $p || str_starts_with($path, $p . '/')) {
                return true;
            }
        }
        return false;
    }

    /** Topics for the screen at $path (most specific section first), then nothing else. */
    public function pageTopics(string $path): array
    {
        $out = [];
        foreach ($this->topics as $id => $t) {
            if ($this->onPage($t, $path)) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /** The section a screen belongs to, if any (for the panel's heading). */
    public function pageSection(string $path): ?array
    {
        $path = trim($path, '/');
        foreach ($this->sections as $s) {
            // the screens that belong to the section (its own), not the ones it only helps with
            foreach ((array) ($s['home_pages'] ?? $s['pages'] ?? []) as $p) {
                $p = trim((string) $p, '/');
                if ($p !== '*' && ($path === $p || str_starts_with($path, $p . '/'))) {
                    return $s;
                }
            }
        }
        return null;
    }

    // ------------------------------------------------------------------
    //  Text
    // ------------------------------------------------------------------

    /** The small mark-up used in the topics: **bold**, *italic*; everything else is escaped. */
    public static function md(string $s): string
    {
        $h = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $h = preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $h);
        $h = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<i>$1</i>', $h);
        return $h;
    }

    public static function plain(string $s): string
    {
        return str_replace(['**', '*'], '', $s);
    }

    /** A menu name for a URL: the section's own list first, then the menu table. */
    public function menuName(string $url, ?HelpAccess $access = null): string
    {
        foreach ($this->sections as $s) {
            if (isset($s['menus'][$url])) {
                return (string) $s['menus'][$url];
            }
        }
        return $access ? ($access->menuName($url) ?? $url) : $url;
    }
}
