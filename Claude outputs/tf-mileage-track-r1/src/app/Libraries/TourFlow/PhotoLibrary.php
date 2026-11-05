<?php

namespace App\Libraries\TourFlow;

/**
 * The tour photo library (schema 27): a presentation layer over the tour data.
 *
 * Tags are destinations, attractions and activities — Sigiriya, Kandy, Tea Country,
 * Safari, Beaches … — each with the words a day's programme uses for it (Admin edits
 * both; a new destination is a new tag, never a change to the code). A photo carries one
 * or more tags, a caption, and may be preferred so the best pictures come first. Files
 * live under public/<photo_folder> (a setting), resized on upload, with a thumbnail.
 *
 * For a client quotation each day's area, programme, visits and excursions are read for
 * the tags' words. The day's best tag gets a photo: preferred first, the least used
 * next, never the same picture twice on one itinerary, and the pick is kept on the
 * inquiry (tf_intake_day_photo) so the quotation is stable. Staff change any day's photo
 * by hand — or say "no photo" — and that stays; a day whose programme changes is picked
 * again; a photo taken out of the library is replaced on every open inquiry it was on.
 *
 * Nothing here is a cost, a rate or a price.
 */
final class PhotoLibrary
{
    public const KINDS = ['destination', 'attraction', 'activity', 'general'];

    private $db;
    private string $user;
    private ?bool $available = null;
    private ?array $tagCache = null;

    /** The public root the files live under (FCPATH on the server; the harness passes its own). */
    private string $publicRoot;
    /** The URL the public root is served at, with no trailing slash. */
    private string $publicUrl;

    public function __construct($db, string $user = '', ?string $publicRoot = null, ?string $publicUrl = null)
    {
        $this->db   = $db;
        $this->user = $user;
        $this->publicRoot = rtrim($publicRoot ?? (defined('FCPATH') ? FCPATH : sys_get_temp_dir() . '/'), '/\\') . '/';
        $this->publicUrl  = rtrim($publicUrl ?? (function_exists('base_url') ? (string) base_url() : ''), '/');
    }

    /** False until the installer has run schema 27. */
    public function available(): bool
    {
        if ($this->available === null) {
            try {
                $this->available = (bool) $this->db->query("SHOW TABLES LIKE 'tf_photo'")->getResultArray();
            } catch (\Throwable $e) {
                $this->available = false;
            }
        }
        return $this->available;
    }

    private function meta(string $k, ?string $default = null): ?string
    {
        try {
            $r = $this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
            return $r['v'] ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private static function cut($v, int $len): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, $len);
    }

    // =====================================================================
    //  Tags
    // =====================================================================

    /** @return array<int, array<string, mixed>> */
    public function tags(bool $all = false): array
    {
        if (!$this->available()) {
            return [];
        }
        $rows = $this->db->query('SELECT t.*, (SELECT COUNT(*) FROM tf_photo_tag_map m JOIN tf_photo p ON p.id = m.photo_id WHERE m.tag_code = t.code AND p.active = 1) AS photos FROM tf_photo_tag t' . ($all ? '' : ' WHERE t.active = 1') . ' ORDER BY t.sort_order, t.name')->getResultArray();
        foreach ($rows as &$r) {
            $r['photos'] = (int) $r['photos'];
            $r['active'] = (int) $r['active'];
            $r['keyword_list'] = self::keywordList($r['keywords'] ?? '');
        }
        unset($r);
        return $rows;
    }

    /** The words of a tag as the matcher reads them: lower case, one phrase each, the trade's spellings normalised. */
    public static function keywordList(?string $keywords): array
    {
        $out = [];
        foreach (preg_split('/[,\n;]+/', (string) $keywords) ?: [] as $w) {
            $w = ServiceLookup::words($w);
            if ($w !== '' && !in_array($w, $out, true)) {
                $out[] = $w;
            }
        }
        return $out;
    }

    /**
     * Add a tag, or change its name, kind, words, order or active flag. The code is the key:
     * letters, digits and underscores, upper case.
     *
     * @param array{code?:mixed, name?:mixed, kind?:mixed, keywords?:mixed, active?:mixed, sort_order?:mixed} $t
     */
    public function saveTag(array $t): array
    {
        $this->assertAvailable();
        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9_]+/', '_', (string) ($t['code'] ?? '')) ?? '', '_'));
        $name = self::cut($t['name'] ?? null, 120);
        if ($code === '' || $name === null) {
            throw new \InvalidArgumentException('A tag needs a short code and a name (Sigiriya, Tea Country, Safari …).');
        }
        $kind = in_array((string) ($t['kind'] ?? ''), self::KINDS, true) ? (string) $t['kind'] : 'destination';
        $words = self::cut($t['keywords'] ?? null, 1000);
        $sort  = isset($t['sort_order']) && $t['sort_order'] !== '' ? (int) $t['sort_order'] : null;
        $was   = $this->db->query('SELECT * FROM tf_photo_tag WHERE code = ?', [$code])->getRowArray();
        $active = array_key_exists('active', $t) ? (int) !empty($t['active']) : (int) ($was['active'] ?? 1);
        if ($was) {
            $this->db->query('UPDATE tf_photo_tag SET name = ?, kind = ?, keywords = ?, active = ?, sort_order = ?, updated_at = ?, updated_by = ? WHERE code = ?', [$name, $kind, $words, $active, $sort ?? (int) $was['sort_order'], $this->now(), $this->user, $code]);
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), 0) + 10 AS n FROM tf_photo_tag')->getRowArray()['n'] ?? 10);
            $this->db->query('INSERT INTO tf_photo_tag (code, name, kind, keywords, active, sort_order, updated_at, updated_by) VALUES (?,?,?,?,?,?,?,?)', [$code, $name, $kind, $words, $active, $sort ?? $next, $this->now(), $this->user]);
        }
        Activity::master($this->db, $this->user, 'tour_photo_tag', 0, 'Tour photo tag ' . $code . ($was ? ' changed' : ' added'), [
            ['what' => $code . ' name', 'from' => $was['name'] ?? null, 'to' => $name],
            ['what' => $code . ' words', 'from' => $was['keywords'] ?? null, 'to' => $words],
            ['what' => $code . ' kind', 'from' => $was['kind'] ?? null, 'to' => $kind],
            ['what' => $code . ' active', 'from' => isset($was['active']) ? (string) $was['active'] : null, 'to' => (string) $active],
        ]);
        $this->tagCache = null;
        return $this->db->query('SELECT * FROM tf_photo_tag WHERE code = ?', [$code])->getRowArray();
    }

    /** A tag off the list: taken off when no photo carries it, switched off otherwise (the photos keep their other tags). */
    public function deleteTag(string $code): string
    {
        $this->assertAvailable();
        $code = strtoupper(trim($code));
        $n = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_photo_tag_map WHERE tag_code = ?', [$code])->getRowArray()['n'] ?? 0);
        if ($n > 0) {
            $this->db->query('UPDATE tf_photo_tag SET active = 0, updated_at = ?, updated_by = ? WHERE code = ?', [$this->now(), $this->user, $code]);
            Activity::master($this->db, $this->user, 'tour_photo_tag', 0, 'Tour photo tag ' . $code . ' switched off (' . $n . ' photo(s) carry it)', [['what' => $code . ' active', 'from' => '1', 'to' => '0']]);
            $this->tagCache = null;
            return $n . ' photo(s) carry this tag, so it was switched off rather than removed; the photos keep it and their other tags.';
        }
        $this->db->query('DELETE FROM tf_photo_tag WHERE code = ?', [$code]);
        Activity::master($this->db, $this->user, 'tour_photo_tag', 0, 'Tour photo tag ' . $code . ' removed', [['what' => $code, 'from' => $code, 'to' => null]]);
        $this->tagCache = null;
        return 'Tag removed.';
    }

    // =====================================================================
    //  Photos and their files
    // =====================================================================

    public function folder(): string
    {
        return trim((string) ($this->meta('photo_folder') ?? 'uploads/tour_photos'), '/');
    }

    public function dir(): string
    {
        return $this->publicRoot . $this->folder() . '/';
    }

    public function url(?string $file): ?string
    {
        return $file ? $this->publicUrl . '/' . $this->folder() . '/' . rawurlencode($file) : null;
    }

    public function path(?string $file): ?string
    {
        return $file ? $this->dir() . $file : null;
    }

    /** @return array<int, array<string, mixed>> the photos, with their tags and URLs; active ones unless $all */
    public function photos(?string $tag = null, bool $all = false): array
    {
        if (!$this->available()) {
            return [];
        }
        $where = [];
        $bind  = [];
        if (!$all) {
            $where[] = 'p.active = 1';
        }
        if ($tag !== null && $tag !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM tf_photo_tag_map m WHERE m.photo_id = p.id AND m.tag_code = ?)';
            $bind[]  = strtoupper($tag);
        }
        $rows = $this->db->query('SELECT p.* FROM tf_photo p' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY p.active DESC, p.preferred DESC, p.sort_order, p.id', $bind)->getResultArray();
        return $this->hydrate($rows);
    }

    public function photo(int $id): ?array
    {
        if (!$this->available()) {
            return null;
        }
        $r = $this->db->query('SELECT * FROM tf_photo WHERE id = ?', [$id])->getRowArray();
        return $r ? $this->hydrate([$r])[0] : null;
    }

    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $ids  = array_map(static fn ($r) => (int) $r['id'], $rows);
        $tags = [];
        foreach ($this->db->query('SELECT m.photo_id, m.tag_code, t.name FROM tf_photo_tag_map m LEFT JOIN tf_photo_tag t ON t.code = m.tag_code WHERE m.photo_id IN (' . implode(',', $ids) . ') ORDER BY t.sort_order, m.tag_code')->getResultArray() as $m) {
            $tags[(int) $m['photo_id']][] = ['code' => $m['tag_code'], 'name' => $m['name'] ?? $m['tag_code']];
        }
        $used = [];
        try {
            foreach ($this->db->query('SELECT photo_id, COUNT(*) n FROM tf_intake_day_photo WHERE photo_id IN (' . implode(',', $ids) . ') GROUP BY photo_id')->getResultArray() as $u) {
                $used[(int) $u['photo_id']] = (int) $u['n'];
            }
        } catch (\Throwable $e) {
            $used = [];
        }
        foreach ($rows as &$r) {
            $r['id']        = (int) $r['id'];
            $r['preferred'] = (int) $r['preferred'];
            $r['active']    = (int) $r['active'];
            $r['uses']      = (int) $r['uses'];
            $r['tags']      = $tags[$r['id']] ?? [];
            $r['tag_codes'] = array_map(static fn ($t) => $t['code'], $r['tags']);
            $r['url']       = $this->url($r['file']);
            $r['thumb_url'] = $this->url($r['thumb'] ?: $r['file']);
            $r['on_days']   = $used[$r['id']] ?? 0;
            $r['exists']    = is_file($this->path($r['file']));
        }
        unset($r);
        return $rows;
    }

    /**
     * A photo into the library from an uploaded file: checked (an image, not too big),
     * resized to the setting's width, a thumbnail beside it, and the row with its tags.
     *
     * @param array{tmp:string, name?:string, size?:int} $file the file as uploaded (its temporary path)
     * @param array{tags?:mixed, caption?:mixed, credit?:mixed, preferred?:mixed} $meta
     */
    public function addPhoto(array $file, array $meta): array
    {
        $this->assertAvailable();
        $tags = $this->tagCodes($meta['tags'] ?? []);
        if ($tags === []) {
            throw new \InvalidArgumentException('Tag the photo to at least one destination or attraction.');
        }
        $img = $this->readImage($file);
        $id  = $this->nextFileId();
        $names = $this->writeFiles($img, $id);
        $this->db->query(
            'INSERT INTO tf_photo (file, thumb, original_name, mime, width, height, bytes, caption, credit, preferred, active, sort_order, uploaded_at, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,1,?,?,?)',
            [$names['file'], $names['thumb'], self::cut($file['name'] ?? null, 190), 'image/jpeg', $names['width'], $names['height'], $names['bytes'], self::cut($meta['caption'] ?? null, 190), self::cut($meta['credit'] ?? null, 120), (int) !empty($meta['preferred']), (int) ($meta['sort_order'] ?? 0), $this->now(), $this->user]
        );
        $photoId = (int) $this->db->insertID();
        foreach ($tags as $t) {
            $this->db->query('INSERT IGNORE INTO tf_photo_tag_map (photo_id, tag_code) VALUES (?, ?)', [$photoId, $t]);
        }
        Activity::master($this->db, $this->user, 'tour_photo', $photoId, 'Tour photo ' . $photoId . ' added: ' . implode(', ', $tags), [['what' => 'photo ' . $photoId, 'from' => null, 'to' => ($file['name'] ?? $names['file']) . ' · ' . implode(', ', $tags)]]);
        return $this->photo($photoId);
    }

    /** A new file for a photo already in the library: the same id, caption and tags; every quotation that carries it shows the new picture. */
    public function replacePhoto(int $id, array $file): array
    {
        $this->assertAvailable();
        $p = $this->photo($id);
        if (!$p) {
            throw new \RuntimeException('Photo not found.');
        }
        $img   = $this->readImage($file);
        $names = $this->writeFiles($img, $this->nextFileId());
        $old   = [$p['file'], $p['thumb']];
        $this->db->query('UPDATE tf_photo SET file = ?, thumb = ?, original_name = ?, mime = ?, width = ?, height = ?, bytes = ?, replaced_at = ?, replaced_by = ? WHERE id = ?', [$names['file'], $names['thumb'], self::cut($file['name'] ?? null, 190), 'image/jpeg', $names['width'], $names['height'], $names['bytes'], $this->now(), $this->user, $id]);
        foreach ($old as $f) {
            if ($f && is_file($this->path($f))) {
                @unlink($this->path($f));
            }
        }
        Activity::master($this->db, $this->user, 'tour_photo', $id, 'Tour photo ' . $id . ' replaced', [['what' => 'photo ' . $id . ' file', 'from' => $old[0], 'to' => $names['file']]]);
        return $this->photo($id);
    }

    /**
     * The caption, credit, preferred flag, order and tags of a photo.
     *
     * @param array{caption?:mixed, credit?:mixed, preferred?:mixed, sort_order?:mixed, tags?:mixed} $meta
     */
    public function savePhoto(int $id, array $meta): array
    {
        $this->assertAvailable();
        $p = $this->photo($id);
        if (!$p) {
            throw new \RuntimeException('Photo not found.');
        }
        $sets = [];
        $bind = [];
        $changes = [];
        foreach (['caption' => 190, 'credit' => 120] as $k => $len) {
            if (array_key_exists($k, $meta)) {
                $sets[] = "`{$k}` = ?";
                $bind[] = self::cut($meta[$k], $len);
                $changes[] = ['what' => 'photo ' . $id . ' ' . $k, 'from' => $p[$k], 'to' => self::cut($meta[$k], $len)];
            }
        }
        if (array_key_exists('preferred', $meta)) {
            $sets[] = 'preferred = ?';
            $bind[] = (int) !empty($meta['preferred']);
            $changes[] = ['what' => 'photo ' . $id . ' preferred', 'from' => (string) $p['preferred'], 'to' => (string) (int) !empty($meta['preferred'])];
        }
        if (array_key_exists('sort_order', $meta) && $meta['sort_order'] !== '') {
            $sets[] = 'sort_order = ?';
            $bind[] = (int) $meta['sort_order'];
        }
        if ($sets) {
            $bind[] = $id;
            $this->db->query('UPDATE tf_photo SET ' . implode(', ', $sets) . ' WHERE id = ?', $bind);
        }
        if (array_key_exists('tags', $meta)) {
            $tags = $this->tagCodes($meta['tags']);
            if ($tags === []) {
                throw new \InvalidArgumentException('A photo keeps at least one tag.');
            }
            $this->db->query('DELETE FROM tf_photo_tag_map WHERE photo_id = ?', [$id]);
            foreach ($tags as $t) {
                $this->db->query('INSERT IGNORE INTO tf_photo_tag_map (photo_id, tag_code) VALUES (?, ?)', [$id, $t]);
            }
            $changes[] = ['what' => 'photo ' . $id . ' tags', 'from' => implode(', ', $p['tag_codes']), 'to' => implode(', ', $tags)];
        }
        Activity::master($this->db, $this->user, 'tour_photo', $id, 'Tour photo ' . $id . ' changed', $changes);
        return $this->photo($id);
    }

    /** Out of the library: kept on disk and in the table, marked removed, off every open inquiry it was on (those days are picked again). */
    public function removePhoto(int $id): array
    {
        $this->assertAvailable();
        $p = $this->photo($id);
        if (!$p) {
            throw new \RuntimeException('Photo not found.');
        }
        $this->db->query('UPDATE tf_photo SET active = 0, removed_at = ?, removed_by = ? WHERE id = ?', [$this->now(), $this->user, $id]);
        // the days it was on choose again; a person's choice of it is let go too — the picture is gone
        $this->db->query('DELETE FROM tf_intake_day_photo WHERE photo_id = ?', [$id]);
        Activity::master($this->db, $this->user, 'tour_photo', $id, 'Tour photo ' . $id . ' removed from the library', [['what' => 'photo ' . $id . ' active', 'from' => '1', 'to' => '0']]);
        return $this->photo($id);
    }

    public function restorePhoto(int $id): array
    {
        $this->assertAvailable();
        $this->db->query('UPDATE tf_photo SET active = 1, removed_at = NULL, removed_by = NULL WHERE id = ?', [$id]);
        Activity::master($this->db, $this->user, 'tour_photo', $id, 'Tour photo ' . $id . ' back in the library', [['what' => 'photo ' . $id . ' active', 'from' => '0', 'to' => '1']]);
        return $this->photo($id);
    }

    private function tagCodes($tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,\s]+/', $tags) ?: [];
        }
        $known = array_column($this->tags(true), 'code');
        $out = [];
        foreach ((array) $tags as $t) {
            $t = strtoupper(trim((string) $t));
            if ($t !== '' && in_array($t, $known, true) && !in_array($t, $out, true)) {
                $out[] = $t;
            }
        }
        return $out;
    }

    private function nextFileId(): string
    {
        return date('Ymd') . '_' . bin2hex(random_bytes(5));
    }

    /** @return array{img:mixed, w:int, h:int, raw:?string} */
    private function readImage(array $file): array
    {
        $tmp = (string) ($file['tmp'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            throw new \InvalidArgumentException('No file came with the upload.');
        }
        $size = (int) filesize($tmp);
        if ($size > 12 * 1024 * 1024) {
            throw new \InvalidArgumentException('The file is over 12 MB; a photo for the quotation needs no more than a few megabytes.');
        }
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            throw new \InvalidArgumentException('Only a JPEG, PNG, WebP or GIF photo can go in the library.');
        }
        if (!function_exists('imagecreatefromstring')) {
            // no image library on this server: the file is kept as it came, unresized
            return ['img' => null, 'w' => (int) $info[0], 'h' => (int) $info[1], 'raw' => $tmp];
        }
        // a phone photo of 12 MB is 20–50 million pixels once decoded: make room for it first
        self::makeRoom((int) $info[0] * (int) $info[1]);
        $img = self::decode($tmp, (int) $info[2]);
        if (!$img) {
            throw new \InvalidArgumentException('The image could not be read.');
        }
        // the way up, as the phone recorded it (EXIF orientation); brought down to the working
        // size first so the turn is made on the small picture, not the 40-megapixel one
        $turn = $info[2] === IMAGETYPE_JPEG ? self::orientation($tmp) : 1;
        $img  = $this->workingSize($img, in_array($turn, [5, 6, 7, 8], true));
        $img  = self::upright($img, $turn);
        return ['img' => $img, 'w' => imagesx($img), 'h' => imagesy($img), 'raw' => null];
    }

    /** The picture decoded straight from its file (no copy of the file in memory), by type. */
    private static function decode(string $tmp, int $type)
    {
        $by = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp', IMAGETYPE_GIF => 'imagecreatefromgif'];
        $fn = $by[$type] ?? null;
        $img = $fn && function_exists($fn) ? @$fn($tmp) : false;
        return $img ?: @imagecreatefromstring((string) file_get_contents($tmp));
    }

    /** PHP's memory limit lifted for this request when the decoded picture would not fit under it. */
    private static function makeRoom(int $pixels): void
    {
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return;   // no limit
        }
        // the decoded picture (4 bytes a pixel and GD's rows), the working copy, the script itself
        $need = (int) ($pixels * 5.5) + 32 * 1048576 + memory_get_usage();
        if ($need > $limit) {
            @ini_set('memory_limit', (string) ((int) ceil($need / 1048576) + 16) . 'M');
        }
    }

    /** A php.ini size (128M, 2G, 512K, a plain number of bytes) in bytes; -1 and blank give 0. */
    public static function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '' || $v === '-1') {
            return 0;
        }
        $n = (float) $v;
        switch (strtolower(substr($v, -1))) {
            case 'g': $n *= 1073741824; break;
            case 'm': $n *= 1048576; break;
            case 'k': $n *= 1024; break;
        }
        return (int) $n;
    }

    /** The EXIF orientation of a JPEG (1 when there is none, or no EXIF support on the server). */
    private static function orientation(string $tmp): int
    {
        if (!function_exists('exif_read_data')) {
            return 1;
        }
        try {
            $ex = @exif_read_data($tmp);
            $o  = (int) ($ex['Orientation'] ?? 1);
            return $o >= 1 && $o <= 8 ? $o : 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * The decoded picture brought down to the setting's width (the side that becomes the width
     * once it is turned upright), so the rest of the work is done on a small picture; a picture
     * already small enough is kept as it is.
     */
    private function workingSize($img, bool $sideways)
    {
        $maxW = max(400, (int) ($this->meta('photo_max_width') ?? 1600));
        $w = imagesx($img);
        $h = imagesy($img);
        $side = $sideways ? $h : $w;   // the side that ends up as the width
        if ($side <= $maxW) {
            return $img;
        }
        $f  = $maxW / $side;
        $nw = max(1, (int) round($w * $f));
        $nh = max(1, (int) round($h * $f));
        $small = $this->scaled($img, $w, $h, $nw, $nh);
        imagedestroy($img);
        return $small;
    }

    /** The picture the right way up for its EXIF orientation. */
    private static function upright($img, int $o)
    {
        switch ($o) {
            case 2: imageflip($img, IMG_FLIP_HORIZONTAL); return $img;
            case 3: return imagerotate($img, 180, 0) ?: $img;
            case 4: imageflip($img, IMG_FLIP_VERTICAL); return $img;
            case 5: imageflip($img, IMG_FLIP_VERTICAL); return imagerotate($img, -90, 0) ?: $img;
            case 6: return imagerotate($img, -90, 0) ?: $img;
            case 7: imageflip($img, IMG_FLIP_HORIZONTAL); return imagerotate($img, -90, 0) ?: $img;
            case 8: return imagerotate($img, 90, 0) ?: $img;
            default: return $img;
        }
    }

    /** The photo written at the setting's width and a thumbnail beside it; returns the names and the size. */
    private function writeFiles(array $img, string $base): array
    {
        $dir = $this->dir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new \RuntimeException('The photo folder ' . $this->folder() . ' could not be created under the public root. Create it and make it writable.');
        }
        // a real write probe, not is_writable(): on Windows that function answered "no" for folders Apache wrote to
        // without trouble (the tf_backups fault of 5–6 Oct 2026, fixed the same way in TourFlow\Installer)
        try {
            Installer::assertWritableDir(rtrim($dir, '/\\'));
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('The photo folder ' . $this->folder() . ' is not writable (' . $e->getMessage() . ').');
        }
        $maxW   = max(400, (int) ($this->meta('photo_max_width') ?? 1600));
        $thumbW = max(120, (int) ($this->meta('photo_thumb_width') ?? 480));
        if ($img['img'] === null) {
            $name = $base . '.' . strtolower(pathinfo((string) $img['raw'], PATHINFO_EXTENSION) ?: 'jpg');
            copy($img['raw'], $dir . $name);
            return ['file' => $name, 'thumb' => null, 'width' => $img['w'], 'height' => $img['h'], 'bytes' => (int) filesize($dir . $name)];
        }
        $src = $img['img'];
        // the main picture at the setting's width at most; smaller pictures are kept as they are
        $w = $img['w'];
        $h = $img['h'];
        $main = $this->scaled($src, $w, $h, min($w, $maxW));
        $file = $base . '.jpg';
        imagejpeg($main, $dir . $file, 84);
        $thumb = $base . '_t.jpg';
        imagejpeg($this->scaled($src, $w, $h, min($w, $thumbW)), $dir . $thumb, 80);
        $out = ['file' => $file, 'thumb' => $thumb, 'width' => imagesx($main), 'height' => imagesy($main), 'bytes' => (int) filesize($dir . $file)];
        imagedestroy($main);
        imagedestroy($src);
        return $out;
    }

    private function scaled($src, int $w, int $h, int $toW, ?int $toH = null)
    {
        $toH = $toH ?? max(1, (int) round($h * $toW / max(1, $w)));
        $dst = imagecreatetruecolor($toW, $toH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);   // a transparent PNG lands on white, as it will on paper
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $toW, $toH, $w, $h);
        return $dst;
    }

    // =====================================================================
    //  Matching a day's programme to the tags, and the pick for each day
    // =====================================================================

    private function tagWords(): array
    {
        if ($this->tagCache === null) {
            $this->tagCache = [];
            foreach ($this->tags() as $t) {
                $this->tagCache[$t['code']] = ['name' => $t['name'], 'kind' => $t['kind'], 'words' => $t['keyword_list']];
            }
        }
        return $this->tagCache;
    }

    /**
     * The tags a piece of programme text points at, best first: a tag scores once per
     * word of its own found whole in the text, with the longest words counting a little
     * more, so "tea country" beats a lone "tea". The area of the day counts double.
     *
     * @return array<int, array{code:string, name:string, score:float, hits:array<int, string>}>
     */
    public function tagsFor(string $text, string $area = ''): array
    {
        $t = ' ' . ServiceLookup::words($text) . ' ';
        $a = ' ' . ServiceLookup::words($area) . ' ';
        $out = [];
        foreach ($this->tagWords() as $code => $tag) {
            $score  = 0.0;
            $hits   = [];
            $inArea = false;
            foreach ($tag['words'] as $w) {
                $len = 1 + min(2, substr_count($w, ' ')) * 0.5;
                if (str_contains($a, ' ' . $w . ' ')) {
                    $score += 2 * $len;
                    $hits[] = $w;
                    $inArea = true;
                } elseif (str_contains($t, ' ' . $w . ' ')) {
                    $score += $len;
                    $hits[] = $w;
                }
            }
            if ($score > 0) {
                $out[] = ['code' => $code, 'name' => $tag['name'], 'score' => $score, 'hits' => $hits, 'in_area' => $inArea];
            }
        }
        // the best score first; on a tie the tag with more of its words found, then the one the area names, then the code
        usort($out, static fn ($x, $y) => [$y['score'], count($y['hits']), (int) $y['in_area']] <=> [$x['score'], count($x['hits']), (int) $x['in_area']] ?: strcmp($x['code'], $y['code']));
        foreach ($out as &$o) {
            unset($o['in_area']);
        }
        unset($o);
        return $out;
    }

    /** The text a day's programme is read from: area, narrative, visits, excursions, the hotel's area. */
    public static function dayText(array $d): string
    {
        $bits = [(string) ($d['narrative'] ?? ''), (string) ($d['special_note'] ?? '')];
        foreach ($d['services'] ?? [] as $s) {
            $bits[] = (string) ($s['title'] ?? '');
        }
        foreach ($d['excursions'] ?? [] as $x) {
            $bits[] = (string) ($x['name'] ?? '');
        }
        return implode(' . ', array_filter($bits));
    }

    /**
     * The photo for each day of an inquiry, as the quotation shows them: a person's choice
     * where there is one, the kept automatic pick where the day still points at the same
     * tag, and a fresh pick otherwise — preferred first, least used next, never a picture
     * already on another day of this itinerary. The picks are written back so the next
     * read is the same. Keyed by day number; a day with nothing to show is absent.
     *
     * @param array<string, mixed> $it the inquiry as IntakeService::get() reads it (days with services and excursions)
     * @return array{days: array<int, array<string, mixed>>, tags: array<int, array<string, mixed>>, cover: array<int, array<string, mixed>>}
     */
    public function forIntake(array $it, bool $readOnly = false): array
    {
        // $readOnly (schema 32): a closed inquiry is shown with the picks it has, and nothing is written
        if (!$this->available()) {
            return ['days' => [], 'tags' => [], 'cover' => []];
        }
        $intakeId = (int) $it['id'];
        $kept = [];
        foreach ($this->db->query('SELECT * FROM tf_intake_day_photo WHERE intake_id = ?', [$intakeId])->getResultArray() as $k) {
            $kept[(int) $k['day_no']] = $k;
        }
        $active = [];
        foreach ($this->photos() as $p) {
            $active[$p['id']] = $p;
        }
        $taken = [];   // photo ids already on a day of this itinerary
        foreach ($kept as $k) {
            if (!empty($k['photo_id']) && isset($active[(int) $k['photo_id']])) {
                $taken[(int) $k['photo_id']] = true;
            }
        }
        $days   = [];
        $tagsBy = [];
        $bumped = [];
        foreach ($it['days'] ?? [] as $d) {
            $dayNo = (int) $d['day_no'];
            $found = $this->tagsFor(self::dayText($d), (string) ($d['area'] ?? ''));
            $tagsBy[$dayNo] = $found;
            $best = $found[0]['code'] ?? null;
            $row  = $kept[$dayNo] ?? null;
            $chosen = null;
            if ($row && (string) $row['chosen_by'] === 'human') {
                // a person's choice, or a person's "no photo": stays until they change it
                $chosen = $row['photo_id'] !== null && isset($active[(int) $row['photo_id']]) ? $active[(int) $row['photo_id']] : null;
                if ($row['photo_id'] !== null && $chosen === null) {
                    $row = null;   // the picture they chose has left the library: picked again below
                }
            }
            if ($row === null || (string) $row['chosen_by'] === 'auto') {
                // the kept pick stands while the day still reads as the same place and the picture is still in the library
                $stale = $row === null || (string) ($row['tag_code'] ?? '') !== (string) $best || ($row['photo_id'] !== null && !isset($active[(int) $row['photo_id']]));
                if (!$stale && $row['photo_id'] !== null) {
                    $chosen = $active[(int) $row['photo_id']];
                } elseif ($best !== null) {
                    if ($row && !empty($row['photo_id'])) {
                        unset($taken[(int) $row['photo_id']]);
                    }
                    // the best place first; when the library has no picture of it yet, the next place the day names
                    $chosen = null;
                    foreach ($found as $cand) {
                        $chosen = $this->pickFor($cand['code'], $taken, $active);
                        if ($chosen) {
                            break;
                        }
                    }
                    $same = $row && (string) ($row['tag_code'] ?? '') === (string) $best && ($row['photo_id'] === null ? $chosen === null : (int) $row['photo_id'] === ($chosen['id'] ?? 0));
                    if (!$same && !$readOnly) {
                        $this->db->query(
                            "INSERT INTO tf_intake_day_photo (intake_id, day_no, photo_id, tag_code, chosen_by, chosen_at, `by`) VALUES (?,?,?,?,'auto',?,?)
                             ON DUPLICATE KEY UPDATE photo_id = VALUES(photo_id), tag_code = VALUES(tag_code), chosen_by = 'auto', chosen_at = VALUES(chosen_at), `by` = VALUES(`by`)",
                            [$intakeId, $dayNo, $chosen['id'] ?? null, $best, $this->now(), $this->user ?: 'auto']
                        );
                        if ($chosen && (int) ($row['photo_id'] ?? 0) !== $chosen['id']) {
                            $bumped[] = $chosen['id'];   // a picture newly on this day counts as a use; the same one kept does not
                        }
                    }
                    $row = ['tag_code' => $best, 'chosen_by' => 'auto', 'by' => null, 'photo_id' => $chosen['id'] ?? null];
                } elseif ($row) {
                    // the day points at no tag any more: nothing to show
                    if (!$readOnly) {
                        $this->db->query('DELETE FROM tf_intake_day_photo WHERE intake_id = ? AND day_no = ?', [$intakeId, $dayNo]);
                    }
                    $chosen = null;
                }
            }
            if ($chosen) {
                $taken[$chosen['id']] = true;
                // the place the picture is shown for: the one of its own places the day names, else its first
                $shown = $chosen['tag_codes'][0] ?? $best;
                foreach ($found as $cand) {
                    if (in_array($cand['code'], $chosen['tag_codes'], true)) {
                        $shown = $cand['code'];
                        break;
                    }
                }
                $days[$dayNo] = [
                    'photo_id' => $chosen['id'], 'url' => $chosen['url'], 'thumb_url' => $chosen['thumb_url'], 'caption' => $chosen['caption'], 'credit' => $chosen['credit'],
                    'width' => $chosen['width'], 'height' => $chosen['height'], 'file' => $chosen['file'],
                    'tag' => $shown, 'tag_name' => $this->tagWords()[$shown]['name'] ?? null, 'read_as' => $best,
                    'chosen_by' => $row['chosen_by'] ?? 'auto', 'by' => $row['by'] ?? null,
                ];
            } elseif ($row && (string) $row['chosen_by'] === 'human') {
                $days[$dayNo] = ['photo_id' => null, 'chosen_by' => 'human', 'by' => $row['by'] ?? null, 'tag' => $best, 'tag_name' => $best ? ($this->tagWords()[$best]['name'] ?? null) : null];
            } elseif ($best !== null) {
                // the place has pictures, but every one is on another day of this itinerary already
                $has = array_filter($active, static fn ($p) => in_array($best, $p['tag_codes'], true));
                if ($has !== []) {
                    $days[$dayNo] = ['photo_id' => null, 'chosen_by' => 'auto', 'by' => null, 'tag' => $best, 'tag_name' => $this->tagWords()[$best]['name'] ?? null, 'note' => 'already on another day'];
                }
            }
        }
        if ($bumped && !$readOnly) {
            // a fresh automatic pick counts as a use, so the next itinerary leans to another picture: the mix
            $this->db->query('UPDATE tf_photo SET uses = uses + 1, last_used_at = ? WHERE id IN (' . implode(',', array_map('intval', array_unique($bumped))) . ')', [$this->now()]);
        }
        return ['days' => $days, 'tags' => $tagsBy, 'cover' => $this->cover($days, $active)];
    }

    /** The photo for a tag that is not yet on this itinerary: preferred first, least used next, then the library's order. */
    private function pickFor(string $tag, array $taken, array $active): ?array
    {
        $candidates = array_values(array_filter($active, static fn ($p) => in_array($tag, $p['tag_codes'], true)));
        if ($candidates === []) {
            return null;
        }
        // never the same picture twice on one itinerary: with every picture of the place already on
        // another day, the day goes without rather than repeating one
        $free = array_values(array_filter($candidates, static fn ($p) => !isset($taken[$p['id']])));
        if ($free === []) {
            return null;
        }
        usort($free, static fn ($a, $b) => [$b['preferred'], $a['uses'], $a['sort_order'], $a['id']] <=> [$a['preferred'], $b['uses'], $b['sort_order'], $b['id']]);
        return $free[0];
    }

    /**
     * tf-mileage-track-r1 (10 Oct 2026): a small picture for each station on the Mileage by sector railway view.
     * Read only — nothing is written and no use is counted, so the quotation's own picks are never moved by it.
     * The town of a place is its last part ("Good Wood Airport Hotel, …, Katunayake" → Katunayake). The picture is,
     * in order: a Photo Library photo tagged with the place whose name IS the town (the airport and Katunayake take the
     * Arrival & departure tag), preferred first; else the Places master's picture of a site named for the town
     * ("National Museum Colombo"), else of a site whose location ends in the town. A town with neither gets none —
     * the screen then draws a plain station (a house for the garage, a plane for the airport).
     *
     * @param array<int, string|null> $places the from and to places of the legs
     * @return array<string, array{url:string, caption:?string, source:string}> keyed by the place in lower case, as given
     */
    public function forStations(array $places): array
    {
        $out = [];
        $towns = [];
        foreach ($places as $p) {
            $p = trim((string) $p);
            if ($p === '') {
                continue;
            }
            $parts = array_values(array_filter(array_map('trim', explode(',', $p)), static fn ($x) => $x !== ''));
            $town = ServiceLookup::words((string) end($parts));
            if ($town === '' || $town === 'garage') {
                continue;
            }
            $towns[mb_strtolower($p)] = $town;
        }
        if ($towns === []) {
            return [];
        }
        // the library: the tag whose name is the town, and its best active photo
        $tagByName = [];
        $byTag = [];
        if ($this->available()) {
            try {
                foreach ($this->tags() as $t) {
                    if (!empty($t['active']) || !array_key_exists('active', $t)) {
                        $tagByName[ServiceLookup::words((string) $t['name'])] = (string) $t['code'];
                    }
                }
                foreach ($this->photos() as $ph) {
                    if (empty($ph['exists'])) {
                        continue;
                    }
                    foreach ($ph['tag_codes'] as $c) {
                        $byTag[$c][] = $ph;
                    }
                }
                foreach ($byTag as &$list) {
                    usort($list, static fn ($a, $b) => [$b['preferred'], $a['sort_order'], $a['id']] <=> [$a['preferred'], $b['sort_order'], $b['id']]);
                }
                unset($list);
            } catch (\Throwable $e) {
                $byTag = [];
            }
        }
        // the Places master: sites with a picture on disk
        $sites = [];
        try {
            if ($this->db->query("SHOW TABLES LIKE 'tf_place'")->getResultArray()) {
                foreach ($this->db->query("SELECT id, name, location, image FROM tf_place WHERE active = 1 AND merged_into IS NULL AND image IS NOT NULL AND image <> '' ORDER BY id")->getResultArray() as $r) {
                    $img = basename((string) $r['image']);
                    if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $img) || !is_file($this->publicRoot . 'uploads/places/' . $img)) {
                        continue;
                    }
                    $locParts = array_values(array_filter(array_map('trim', explode(',', (string) $r['location'])), static fn ($x) => $x !== ''));
                    $sites[] = ['name' => (string) $r['name'], 'nw' => ' ' . ServiceLookup::words((string) $r['name']) . ' ', 'town' => ServiceLookup::words((string) end($locParts)),
                        'url' => $this->publicUrl . '/uploads/places/' . rawurlencode($img)];
                }
            }
        } catch (\Throwable $e) {
            $sites = [];
        }
        foreach ($towns as $key => $town) {
            $code = $tagByName[$town] ?? (preg_match('/\b(airport|katunayake|bandaranaike)\b/', $town) ? 'ARRIVAL' : null);
            if ($code !== null && !empty($byTag[$code])) {
                $ph = $byTag[$code][0];
                $out[$key] = ['url' => (string) ($ph['thumb_url'] ?: $ph['url']), 'caption' => $ph['caption'] ?: ($this->tagWords()[$code]['name'] ?? null), 'source' => 'Photo Library'];
                continue;
            }
            $hit = null;
            foreach ($sites as $site) {
                if (str_contains($site['nw'], ' ' . $town . ' ')) {
                    $hit = $site;
                    break;
                }
            }
            if ($hit === null) {
                foreach ($sites as $site) {
                    if ($site['town'] === $town) {
                        $hit = $site;
                        break;
                    }
                }
            }
            if ($hit !== null) {
                $out[$key] = ['url' => $hit['url'], 'caption' => $hit['name'], 'source' => 'Places master'];
            }
        }
        return $out;
    }

    /** Up to three pictures for the top of the quotation: the days' photos, preferred first, one per tag, in day order. */
    private function cover(array $days, array $active): array
    {
        $seenTag = [];
        $out = [];
        $ordered = $days;
        uasort($ordered, static fn ($a, $b) => [(int) ($active[$b['photo_id'] ?? 0]['preferred'] ?? 0)] <=> [(int) ($active[$a['photo_id'] ?? 0]['preferred'] ?? 0)]);
        foreach ($ordered as $dayNo => $d) {
            if (empty($d['photo_id']) || isset($seenTag[$d['tag']])) {
                continue;
            }
            $seenTag[$d['tag']] = true;
            $out[] = ['day_no' => $dayNo] + $d;
            if (count($out) === 3) {
                break;
            }
        }
        usort($out, static fn ($a, $b) => $a['day_no'] <=> $b['day_no']);
        return $out;
    }

    /**
     * A person's choice for a day: a photo of the library, "no photo", or back to automatic.
     *
     * @param int|string|null $choice a photo id, 'none', or 'auto'
     */
    public function setDayPhoto(int $intakeId, int $dayNo, $choice): void
    {
        $this->assertAvailable();
        if ($choice === 'auto' || $choice === '' || $choice === null) {
            $this->db->query('DELETE FROM tf_intake_day_photo WHERE intake_id = ? AND day_no = ?', [$intakeId, $dayNo]);
            return;
        }
        if ($choice === 'none') {
            $this->db->query(
                "INSERT INTO tf_intake_day_photo (intake_id, day_no, photo_id, tag_code, chosen_by, chosen_at, `by`) VALUES (?,?,NULL,NULL,'human',?,?)
                 ON DUPLICATE KEY UPDATE photo_id = NULL, tag_code = NULL, chosen_by = 'human', chosen_at = VALUES(chosen_at), `by` = VALUES(`by`)",
                [$intakeId, $dayNo, $this->now(), $this->user]
            );
            return;
        }
        $p = $this->photo((int) $choice);
        if (!$p || !$p['active']) {
            throw new \RuntimeException('That photo is not in the library.');
        }
        $this->db->query(
            "INSERT INTO tf_intake_day_photo (intake_id, day_no, photo_id, tag_code, chosen_by, chosen_at, `by`) VALUES (?,?,?,?,'human',?,?)
             ON DUPLICATE KEY UPDATE photo_id = VALUES(photo_id), tag_code = VALUES(tag_code), chosen_by = 'human', chosen_at = VALUES(chosen_at), `by` = VALUES(`by`)",
            [$intakeId, $dayNo, $p['id'], $p['tag_codes'][0] ?? null, $this->now(), $this->user]
        );
    }

    private function assertAvailable(): void
    {
        if (!$this->available()) {
            throw new \RuntimeException('The photo library is not on this install yet. Open Tour Inquiry and press Install first.');
        }
    }
}
