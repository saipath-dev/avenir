<?php

namespace App\Libraries\HotelRates;

/**
 * tf-hr-docs-r1 (12 Nov 2026, Shantha): the supporting documents of a hotel and its rate plans —
 * the rate sheet PDF a plan was typed from, a contract, a factsheet, an e-mail — kept with the plan
 * so the approver can open the original beside the figures.
 *
 *  - A document belongs to a hotel; with a contract_id it belongs to one rate plan, without one it is
 *    a hotel document shown under every plan of that hotel.
 *  - Files live outside the public folder (writable/…/hotel_rate_docs/<hotel id>/) under a random name
 *    and are only served through the panel (same privilege as Hotel Rates).
 *  - Nothing is ever hard-deleted: "remove" hides the file from the lists and keeps the row, the file
 *    and the reason in the audit trail. Removal: the uploader or a Hotel Rate approver; once the plan is
 *    approved, approvers only.
 *  - The same file (SHA-256) is attached once per hotel; a second copy is refused with a pointer to the first.
 */
class RateDocuments
{
    public const TABLE = 'hr2_document';
    public const MAX_MB = 20;
    /** extension => accepted MIME types (finfo may report a generic type for some Office files) */
    public const TYPES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'doc' => ['application/msword', 'application/octet-stream', 'application/CDFV2'],
        'eml' => ['message/rfc822', 'text/plain', 'application/octet-stream'],
        'msg' => ['application/vnd.ms-outlook', 'application/octet-stream', 'application/CDFV2'],
        'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv', 'application/csv'],
    ];
    /** shown inline in the browser; everything else downloads */
    public const INLINE = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt'];
    public const KINDS = ['rate_sheet' => 'Rate sheet', 'contract' => 'Contract / agreement', 'offer' => 'Offer / tactical', 'factsheet' => 'Factsheet', 'email' => 'E-mail', 'other' => 'Other'];

    private $db;
    private string $user;
    private ?string $baseDir;

    public function __construct($db, string $user = '', ?string $baseDir = null)
    {
        $this->db = $db;
        $this->user = $user !== '' ? $user : 'system';
        $this->baseDir = $baseDir;
    }

    public function installed(): bool
    {
        try {
            return (bool) $this->db->query("SHOW TABLES LIKE '" . self::TABLE . "'")->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Creates the table when it is missing (the same statement is in hotel_rates_v2_schema.sql). */
    public function ensure(): void
    {
        if ($this->installed()) {
            return;
        }
        $this->db->query(self::ddl());
    }

    public static function ddl(): string
    {
        return "CREATE TABLE IF NOT EXISTS `hr2_document` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id`        INT UNSIGNED NOT NULL,
  `contract_id`     INT UNSIGNED NULL,
  `kind`            VARCHAR(20) NOT NULL DEFAULT 'rate_sheet',
  `original_name`   VARCHAR(255) NOT NULL,
  `stored_name`     VARCHAR(120) NOT NULL,
  `ext`             VARCHAR(10) NOT NULL,
  `mime`            VARCHAR(120) NULL,
  `size_bytes`      INT UNSIGNED NOT NULL DEFAULT 0,
  `sha256`          CHAR(64) NOT NULL,
  `note`            VARCHAR(300) NULL,
  `uploaded_by`     VARCHAR(100) NULL,
  `uploaded_at`     DATETIME NOT NULL,
  `removed_at`      DATETIME NULL,
  `removed_by`      VARCHAR(100) NULL,
  `removed_reason`  VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_document_hotel` (`hotel_id`, `removed_at`),
  KEY `idx_hr2_document_contract` (`contract_id`, `removed_at`),
  KEY `idx_hr2_document_sha` (`sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    /** The folder the files go to: the first writable candidate (created when missing), or null. */
    public function storageRoot(): ?string
    {
        $cands = $this->baseDir !== null ? [$this->baseDir] : [];
        if (defined('WRITEPATH')) {
            $cands[] = WRITEPATH . 'hotel_rate_docs';
            $cands[] = WRITEPATH . 'uploads/hotel_rate_docs';
        }
        foreach ($cands as $dir) {
            $dir = rtrim($dir, '/\\');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }
        return null;
    }

    /** Active documents of a hotel: its own (hotel-wide) ones and those of each of its plans. */
    public function forHotel(int $hotelId): array
    {
        if (!$this->installed()) {
            return [];
        }
        $rows = $this->db->query(
            'SELECT d.*, c.name AS contract_name, c.status AS contract_status FROM ' . self::TABLE . ' d LEFT JOIN hr2_contract c ON c.id = d.contract_id
             WHERE d.hotel_id = ? AND d.removed_at IS NULL ORDER BY d.contract_id IS NOT NULL, d.uploaded_at DESC, d.id DESC',
            [$hotelId]
        )->getResultArray();
        return array_map([$this, 'decorate'], $rows);
    }

    /** Active documents shown with one plan: the plan's own first, then the hotel-wide ones. */
    public function forContract(int $contractId): array
    {
        if (!$this->installed()) {
            return [];
        }
        $c = $this->db->query('SELECT id, hotel_id FROM hr2_contract WHERE id = ?', [$contractId])->getRowArray();
        if (!$c) {
            return [];
        }
        $rows = $this->db->query(
            'SELECT d.*, c.name AS contract_name, c.status AS contract_status FROM ' . self::TABLE . ' d LEFT JOIN hr2_contract c ON c.id = d.contract_id
             WHERE d.removed_at IS NULL AND ((d.contract_id = ?) OR (d.hotel_id = ? AND d.contract_id IS NULL)) ORDER BY d.contract_id IS NULL, d.uploaded_at DESC, d.id DESC',
            [$contractId, (int) $c['hotel_id']]
        )->getResultArray();
        return array_map([$this, 'decorate'], $rows);
    }

    /** contract_id => number of active documents of the plan itself (for the paperclip counts). */
    public function countsForContracts(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids || !$this->installed()) {
            return [];
        }
        $out = [];
        foreach ($this->db->query('SELECT contract_id, COUNT(*) AS n FROM ' . self::TABLE . ' WHERE removed_at IS NULL AND contract_id IN (' . implode(',', $ids) . ') GROUP BY contract_id')->getResultArray() as $r) {
            $out[(int) $r['contract_id']] = (int) $r['n'];
        }
        return $out;
    }

    public function hotelWideCount(int $hotelId): int
    {
        if (!$this->installed()) {
            return 0;
        }
        return (int) ($this->db->query('SELECT COUNT(*) AS n FROM ' . self::TABLE . ' WHERE removed_at IS NULL AND contract_id IS NULL AND hotel_id = ?', [$hotelId])->getRowArray()['n'] ?? 0);
    }

    public function get(int $id): ?array
    {
        if (!$this->installed()) {
            return null;
        }
        $r = $this->db->query('SELECT d.*, c.name AS contract_name, c.status AS contract_status FROM ' . self::TABLE . ' d LEFT JOIN hr2_contract c ON c.id = d.contract_id WHERE d.id = ?', [$id])->getRowArray();
        return $r ? $this->decorate($r) : null;
    }

    /** Absolute path of the stored file, or null when it is missing on disk. */
    public function path(array $doc): ?string
    {
        $raw = $this->db->query('SELECT hotel_id, stored_name FROM ' . self::TABLE . ' WHERE id = ?', [(int) $doc['id']])->getRowArray();
        if (!$raw) {
            return null;
        }
        foreach ($this->candidateRoots() as $root) {
            $p = $root . '/' . (int) $raw['hotel_id'] . '/' . basename((string) $raw['stored_name']);
            if (is_file($p)) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Stores an uploaded file. $tmpPath is the PHP upload temp file (or any readable file in tests).
     * Returns the new document row.
     */
    public function add(int $hotelId, ?int $contractId, string $tmpPath, string $originalName, string $kind = 'rate_sheet', ?string $note = null): array
    {
        $this->ensure();
        $hotel = $this->db->query('SELECT id, name FROM hr2_hotel WHERE id = ?', [$hotelId])->getRowArray();
        if (!$hotel) {
            throw new \RuntimeException('Hotel not found.');
        }
        if ($contractId) {
            $c = $this->db->query('SELECT id, hotel_id FROM hr2_contract WHERE id = ?', [$contractId])->getRowArray();
            if (!$c || (int) $c['hotel_id'] !== $hotelId) {
                throw new \RuntimeException('That rate plan does not belong to this hotel.');
            }
        }
        if (!is_file($tmpPath) || !is_readable($tmpPath)) {
            throw new \RuntimeException('The uploaded file could not be read.');
        }
        $size = (int) filesize($tmpPath);
        if ($size <= 0) {
            throw new \RuntimeException('The file is empty.');
        }
        if ($size > self::MAX_MB * 1024 * 1024) {
            throw new \RuntimeException('The file is larger than ' . self::MAX_MB . ' MB.');
        }
        $originalName = trim(str_replace(["\0", '/', '\\'], ['', '_', '_'], $originalName)) ?: 'document';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            throw new \RuntimeException('Only PDF, pictures (JPG, PNG, WEBP), Excel, Word, e-mail (EML, MSG), TXT and CSV files can be attached.');
        }
        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $fi ? (finfo_file($fi, $tmpPath) ?: null) : null;
        }
        if ($mime !== null && !in_array($mime, self::TYPES[$ext], true) && !($ext === 'csv' || $ext === 'txt' || $ext === 'eml')) {
            throw new \RuntimeException("The file does not look like a .{$ext} file (it reads as {$mime}). Nothing was saved.");
        }
        if ($ext === 'pdf' && file_get_contents($tmpPath, false, null, 0, 5) !== '%PDF-') {
            throw new \RuntimeException('The file does not look like a PDF. Nothing was saved.');
        }
        $sha = hash_file('sha256', $tmpPath);
        $dup = $this->db->query('SELECT d.id, d.original_name, d.contract_id, c.name AS contract_name FROM ' . self::TABLE . ' d LEFT JOIN hr2_contract c ON c.id = d.contract_id WHERE d.hotel_id = ? AND d.sha256 = ? AND d.removed_at IS NULL LIMIT 1', [$hotelId, $sha])->getRowArray();
        if ($dup) {
            throw new \RuntimeException('This exact file is already attached to ' . ($dup['contract_id'] ? 'plan "' . $dup['contract_name'] . '"' : 'the hotel') . ' as "' . $dup['original_name'] . '". It was not attached twice.');
        }
        $root = $this->storageRoot();
        if ($root === null) {
            throw new \RuntimeException('The server has no writable folder for documents (writable/hotel_rate_docs). Ask the administrator to make it writable. Nothing was saved.');
        }
        $dir = $root . '/' . $hotelId;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new \RuntimeException('The documents folder for this hotel could not be created. Nothing was saved.');
        }
        $stored = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $stored;
        $ok = is_uploaded_file($tmpPath) ? @move_uploaded_file($tmpPath, $dest) : @copy($tmpPath, $dest);
        if (!$ok || !is_file($dest)) {
            throw new \RuntimeException('The file could not be stored on the server. Nothing was saved.');
        }
        $kind = isset(self::KINDS[$kind]) ? $kind : 'other';
        $note = $note !== null ? mb_substr(trim(strip_tags($note)), 0, 300) : null;
        $this->db->query(
            'INSERT INTO ' . self::TABLE . ' (hotel_id, contract_id, kind, original_name, stored_name, ext, mime, size_bytes, sha256, note, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$hotelId, $contractId ?: null, $kind, mb_substr($originalName, 0, 255), $stored, $ext, $mime, $size, $sha, $note ?: null, $this->user, date('Y-m-d H:i:s')]
        );
        $id = (int) $this->db->insertID();
        $this->audit($id, 'insert', null, ['hotel_id' => $hotelId, 'contract_id' => $contractId, 'file' => $originalName, 'size' => $size, 'sha256' => $sha, 'kind' => $kind]);
        return $this->get($id);
    }

    /** Hides a document (the row and the file are kept). $canApprove = the user holds Hotel Rate Approval. */
    public function remove(int $id, string $reason, bool $canApprove): array
    {
        $doc = $this->get($id);
        if (!$doc || $doc['removed_at']) {
            throw new \RuntimeException('Document not found or already removed.');
        }
        $reason = trim(strip_tags($reason));
        if ($reason === '') {
            throw new \RuntimeException('Please give a reason (it goes to the audit trail).');
        }
        if (!self::mayRemove($doc, $this->user, $canApprove)) {
            throw new \RuntimeException($doc['contract_status'] === 'approved'
                ? 'This plan is approved: only a user with the Hotel Rate Approval privilege can remove its documents.'
                : 'Only the person who attached this file or a Hotel Rate approver can remove it.');
        }
        $this->db->query('UPDATE ' . self::TABLE . ' SET removed_at = ?, removed_by = ?, removed_reason = ? WHERE id = ?', [date('Y-m-d H:i:s'), $this->user, mb_substr($reason, 0, 300), $id]);
        $this->audit($id, 'archive', ['file' => $doc['original_name']], ['reason' => $reason]);
        return $this->get($id);
    }

    public static function mayRemove(array $doc, string $user, bool $canApprove): bool
    {
        if ($canApprove) {
            return true;
        }
        if (($doc['contract_status'] ?? null) === 'approved') {
            return false;
        }
        return $user !== '' && strcasecmp((string) $doc['uploaded_by'], $user) === 0;
    }

    private function candidateRoots(): array
    {
        $out = [];
        if ($this->baseDir !== null) {
            $out[] = rtrim($this->baseDir, '/\\');
        }
        if (defined('WRITEPATH')) {
            $out[] = rtrim(WRITEPATH, '/\\') . '/hotel_rate_docs';
            $out[] = rtrim(WRITEPATH, '/\\') . '/uploads/hotel_rate_docs';
        }
        return $out;
    }

    private function decorate(array $r): array
    {
        $r['id'] = (int) $r['id'];
        $r['hotel_id'] = (int) $r['hotel_id'];
        $r['contract_id'] = $r['contract_id'] !== null ? (int) $r['contract_id'] : null;
        $r['size_bytes'] = (int) $r['size_bytes'];
        $r['kind_label'] = self::KINDS[$r['kind']] ?? $r['kind'];
        $r['inline'] = in_array($r['ext'], self::INLINE, true);
        $r['scope'] = $r['contract_id'] ? 'plan' : 'hotel';
        unset($r['stored_name']);
        return $r;
    }

    private function audit(int $id, string $action, $old, $new): void
    {
        try {
            (new HotelRateService($this->db, $this->user))->audit('document', $id, $action, $old, $new);
        } catch (\Throwable $e) {
            // the audit trail must never block the upload itself
        }
    }
}
