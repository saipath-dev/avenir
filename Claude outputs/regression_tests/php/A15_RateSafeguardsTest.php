<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\HotelRates\QuoteReadiness;
use App\Libraries\HotelRates\RateLookup;
use NewGen\NewGenTestCase;

/**
 * The quotation safeguards, proved on a hotel of the test's own (Shantha, 6 Oct 2026: "Keep safeguards that prevent
 * missing, unapproved or expired rates from being used in quotations … test using separate test data"). No real hotel,
 * rate or quotation is read or touched: the test hotel, its plans and every row they make are created here and removed.
 *
 *   missing   — a hotel with no plan prices nothing, and readiness names it "Incomplete — Rates Pending"
 *   unapproved — a draft plan prices nothing for a quotation, however complete it is
 *   approved  — the same plan, once approved, prices its own dates and room
 *   expired   — after its last date it prices nothing; before its first, nothing
 *   offers    — a tactical plan covering a date is never used by the quotation types; only a contract rate is
 */
final class A15_RateSafeguardsTest extends NewGenTestCase
{
    private const ACC = '6-9-11-97951';
    private const NAME = 'Battery safeguard test hotel';

    private function svc(): HotelRateService
    {
        return new HotelRateService($this->db(), 'battery');
    }

    /** A complete one-season sheet for the test hotel, as a rate sheet would be entered. */
    private function sheet(int $hotelId, string $name, string $from, string $to, string $type = 'contract'): array
    {
        return [
            'hotel' => ['id' => $hotelId, 'name' => self::NAME, 'sup_acc_no' => self::ACC],
            'contract' => ['name' => $name, 'plan_type' => $type, 'contract_type' => $type === 'contract' ? 'Travel Agent' : 'Tactical rate',
                'valid_from' => $from, 'valid_to' => $to, 'currency' => 'USD', 'pricing_mode' => 'per_room', 'taxes_included' => 1,
                'source_type' => 'manual', 'notes' => 'battery test data'],
            'room_types' => [['name' => 'Battery Room', 'priced_as' => 'base', 'room_count' => 1, 'max_adults' => 2]],
            'seasons' => [['name' => $name . ' season', 'kind' => 'normal', 'dates' => [['from' => $from, 'to' => $to]],
                'rates' => [['room_type' => 'Battery Room', 'meal_plan' => 'BB', 'sgl' => 80, 'dbl' => 90, 'tpl' => null, 'quad' => null, 'unit' => null]]]],
        ];
    }

    private function quoteOpts(): array
    {
        return ['plan_types' => RateLookup::quotationPlanTypes($this->db())];
    }

    private function cleanUp(): void
    {
        foreach ($this->rows('SELECT id FROM hr2_hotel WHERE primary_sup_acc_no = ?', [self::ACC]) as $h) {
            $hid = (int) $h['id'];
            $contracts = array_map(static fn ($r) => (int) $r['id'], $this->rows('SELECT id FROM hr2_contract WHERE hotel_id = ?', [$hid]));
            if ($contracts) {
                $in = implode(',', $contracts);
                $seasons = array_map(static fn ($r) => (int) $r['id'], $this->rows("SELECT id FROM hr2_season WHERE contract_id IN ({$in})"));
                foreach ($this->rows("SELECT table_name AS t, column_name AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name LIKE 'hr2\\_%' AND column_name IN ('season_id', 'contract_id') AND table_name NOT IN ('hr2_season', 'hr2_contract')") as $tc) {
                    $ids = $tc['c'] === 'season_id' ? $seasons : $contracts;
                    if ($ids) {
                        $this->db()->query("DELETE FROM `{$tc['t']}` WHERE `{$tc['c']}` IN (" . implode(',', $ids) . ')');
                    }
                }
                if ($seasons) {
                    $this->db()->query('DELETE FROM hr2_season WHERE id IN (' . implode(',', $seasons) . ')');
                }
                $this->db()->query("DELETE FROM hr2_contract WHERE id IN ({$in})");
                $this->db()->query("DELETE FROM hr2_audit WHERE entity = 'contract' AND entity_id IN ({$in})");
            }
            $this->db()->query('DELETE FROM hr2_room_type WHERE hotel_id = ?', [$hid]);
            $this->db()->query('DELETE FROM hr2_hotel_account WHERE hotel_id = ?', [$hid]);
            $this->db()->query('DELETE FROM hr2_hotel_code WHERE hotel_id = ?', [$hid]);
            $this->db()->query("DELETE FROM hr2_audit WHERE entity = 'hotel' AND entity_id = ?", [$hid]);
            $this->db()->query("DELETE FROM hr2_work_item WHERE hotel_id = ?", [$hid]);
            $this->db()->query('DELETE FROM hr2_hotel WHERE id = ?', [$hid]);
        }
        $this->db()->query('DELETE FROM hotel_rate WHERE hotel = ?', [self::ACC]);   // the old-screen rows an approval projects
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }

    public function testMissingUnapprovedExpiredAndOfferRatesAreNeverUsedForAQuotation(): void
    {
        $svc = $this->svc();
        $hid = $svc->createHotel(self::NAME, self::ACC, null);
        $y = (int) date('Y') + 1;                                  // dates in the future, so "today" never interferes
        $inside = "{$y}-06-15";
        $after = "{$y}-08-01";
        $before = "{$y}-05-31";

        // missing: no plan at all
        $engine = new RateLookup($this->db());
        $this->assertNull($engine->price($hid, $inside, 'Battery Room', 'BB', $this->quoteOpts()), 'no plan, no price');
        $q = new QuoteReadiness($this->db());
        $h = array_values(array_filter($q->hotels(), static fn ($x) => (int) $x['id'] === $hid))[0];
        $st = $q->hotelStatus($h, $inside);
        $this->assertSame('none', $st['ready']);
        $this->assertSame('Incomplete — Rates Pending', $st['reason']['text']);

        // unapproved: a complete draft
        $res = $svc->saveContract($this->sheet($hid, 'Battery contract', "{$y}-06-01", "{$y}-07-31"), ['acknowledge_warnings' => true]);
        $this->assertNotEmpty($res['saved'], json_encode($res['issues'] ?? []));
        $cid = (int) $res['contract_id'];
        $this->assertSame('draft', (string) $this->one('SELECT status FROM hr2_contract WHERE id = ?', [$cid]));
        $engine = new RateLookup($this->db());
        $this->assertNull($engine->price($hid, $inside, 'Battery Room', 'BB', $this->quoteOpts()), 'a draft is never priced for a quotation');

        // approved: the same plan prices its own date and room
        $ap = $svc->approveContract($cid, null, true);
        $this->assertTrue($ap['approved'], json_encode($ap['issues'] ?? []));
        $engine = new RateLookup($this->db());
        $p = $engine->price($hid, $inside, 'Battery Room', 'BB', $this->quoteOpts());
        $this->assertNotNull($p, 'an approved contract prices its dates');
        $this->assertSame($cid, (int) ($p['plan']['id'] ?? $p['plan_id'] ?? 0), 'priced from the approved plan');

        // expired / not started: outside its dates, nothing
        $this->assertNull($engine->price($hid, $after, 'Battery Room', 'BB', $this->quoteOpts()), 'after the last date: no price');
        $this->assertNull($engine->price($hid, $before, 'Battery Room', 'BB', $this->quoteOpts()), 'before the first date: no price');

        // offers: an approved tactical plan covering a date the contract does not cover is not used by quotations
        $res2 = $svc->saveContract($this->sheet($hid, 'Battery tactical', "{$y}-09-01", "{$y}-09-30", 'tactical'), ['acknowledge_warnings' => true]);
        $this->assertNotEmpty($res2['saved'], json_encode($res2['issues'] ?? []));
        $tid = (int) $res2['contract_id'];
        $ap2 = $svc->approveContract($tid, null, true);
        $this->assertTrue($ap2['approved'], json_encode($ap2['issues'] ?? []));
        $engine = new RateLookup($this->db());
        $this->assertNull($engine->price($hid, "{$y}-09-15", 'Battery Room', 'BB', $this->quoteOpts()), 'a tactical offer is never used for a quotation by itself');
        $this->assertNotContains('tactical', RateLookup::quotationPlanTypes($this->db()));
    }
}
