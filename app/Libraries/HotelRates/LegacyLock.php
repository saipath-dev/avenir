<?php

namespace App\Libraries\HotelRates;

/**
 * Tells the OLD Hotel Rate screen (master_files/hotel_rate) which hotels it
 * may still edit. Once a hotel's rates are managed in the new panel, its
 * legacy hotel_rate rows are rebuilt from the approved plans by
 * LegacyProjector — a manual edit there would be overwritten at the next
 * projection, so the old screen is locked for those hotels.
 *
 * The rule comes from Settings (hr2_meta.legacy_screen):
 *   all       locked for every hotel once hotelrates:migrate has copied the hotels across (default)
 *   migrated  locked only for hotels that have an approved plan in the new panel
 *   open      no lock (not recommended)
 *
 * Everything degrades gracefully: when the hr2_ tables are not installed, or
 * the migration has not run yet (no hotels in the new panel), the old screen
 * behaves exactly as before.
 */
final class LegacyLock
{
    /** @var object */
    private $db;
    private ?bool $installed = null;
    private ?bool $hasHotels = null;
    private ?string $mode = null;
    private ?array $locked = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function installed(): bool
    {
        if ($this->installed === null) {
            try {
                $r = $this->db->query("SHOW TABLES LIKE 'hr2_contract'");
                $this->installed = ($r !== false && $r !== true) && count($r->getResultArray()) > 0;
            } catch (\Throwable $e) {
                $this->installed = false;
            }
        }
        return $this->installed;
    }

    /** True once hotelrates:migrate (or the panel) has put at least one hotel in the new panel. */
    public function hasHotels(): bool
    {
        if ($this->hasHotels === null) {
            $this->hasHotels = false;
            if ($this->installed()) {
                try {
                    $r = $this->db->query("SELECT COUNT(*) AS n FROM hr2_hotel WHERE status <> 'merged'");
                    $this->hasHotels = ($r !== false && $r !== true) && (int) ($r->getRowArray()['n'] ?? 0) > 0;
                } catch (\Throwable $e) {
                    $this->hasHotels = false;
                }
            }
        }
        return $this->hasHotels;
    }

    /** all | migrated | open */
    public function mode(): string
    {
        if ($this->mode === null) {
            $this->mode = 'open';
            if ($this->installed()) {
                try {
                    $this->mode = (new Settings($this->db))->get('legacy_screen') ?: 'all';
                } catch (\Throwable $e) {
                    $this->mode = 'all';
                }
            }
        }
        return $this->mode;
    }

    /** Whether the lock applies at all right now (tables installed, migration run, mode not open). */
    public function active(): bool
    {
        return $this->installed() && $this->mode() !== 'open' && $this->hasHotels();
    }

    /**
     * Supplier codes whose rates are managed in the new panel:
     * [sup_acc_no => ['hotel_id' => int, 'hotel' => name, 'plans' => n]]
     */
    public function lockedHotels(): array
    {
        if ($this->locked === null) {
            $this->locked = [];
            if ($this->installed() && $this->mode() !== 'open') {
                try {
                    $rows = $this->db->query(
                        "SELECT a.sup_acc_no, h.id AS hotel_id, h.name,
                                (SELECT COUNT(*) FROM hr2_contract c WHERE c.hotel_id = h.id AND c.status = 'approved') AS plans
                         FROM hr2_hotel_account a JOIN hr2_hotel h ON h.id = a.hotel_id
                         WHERE h.status <> 'merged'"
                    )->getResultArray();
                } catch (\Throwable $e) {
                    $rows = [];
                }
                foreach ($rows as $r) {
                    if ($this->mode() === 'all' || (int) $r['plans'] > 0) {
                        $this->locked[trim((string) $r['sup_acc_no'])] = ['hotel_id' => (int) $r['hotel_id'], 'hotel' => $r['name'], 'plans' => (int) $r['plans']];
                    }
                }
            }
        }
        return $this->locked;
    }

    public function isLocked(?string $supAccNo): bool
    {
        $code = trim((string) $supAccNo);
        if ($code === '') {
            return false;
        }
        if (!$this->active()) {
            return false;
        }
        if ($this->mode() === 'all') {
            return true; // every hotel, even one the new panel has not seen yet
        }
        return isset($this->lockedHotels()[$code]);
    }

    /** The message the old screen shows / the JSON error the old controller returns. */
    public function message(?string $supAccNo, ?string $hotelName = null): string
    {
        $info = $this->lockedHotels()[trim((string) $supAccNo)] ?? null;
        $name = $hotelName ?: ($info['hotel'] ?? 'this hotel');
        return "Rates for {$name} are managed in Master Files → Hotel Rates (new). Open that panel to add or change rates; the rows shown here are rebuilt from the approved plans automatically.";
    }

    /** What the old view needs, as one array (JSON-encoded there). */
    public function forView(string $panelUrl): array
    {
        return [
            'installed' => $this->installed(),
            'active' => $this->active(),
            'mode' => $this->mode(),
            'hotels' => $this->lockedHotels(),
            'panel_url' => $panelUrl,
        ];
    }
}
