<?php
declare(strict_types=1);

namespace NewGen\Php;

use App\Libraries\TourFlow\CrewPeople;
use App\Libraries\TourFlow\MileageAllowances;
use App\Libraries\TourFlow\MileageLookup;
use App\Libraries\TourFlow\TransportRates;
use App\Libraries\TourFlow\VehiclePlanner;
use NewGen\NewGenTestCase;

/**
 * Transport & Guide Services: the Driver & Guide Master (categories, standards, the rate and bata a person is paid,
 * the bank rule), the Transport Rates (general LKR/km by class), Mileage & Allowances, the mileage lookup.
 * Shantha's decisions of 5 Oct 2026 are the figures asserted here.
 */
final class A07_TransportAndGuideServicesTest extends NewGenTestCase
{
    private function crew(): CrewPeople
    {
        return new CrewPeople($this->db(), 'tcadmin', 901, 'Test Administrator');
    }

    public function testTheThreeCategoriesAndTheMasterCounts(): void
    {
        $cats = array_column(CrewPeople::categories(), 'code');
        $this->assertSame(['JSD', 'JSCG', 'JSNTG'], $cats);
        $cp = $this->crew();
        $this->assertTrue($cp->available() && $cp->masterReady());
        $all = $cp->all();
        $active = (int) $this->one("SELECT COUNT(*) FROM tf_crew_person WHERE status = 'active' AND archived_at IS NULL");
        $this->assertSame($active, count($all), 'the master lists every active person');
        foreach (['JSD', 'JSCG', 'JSNTG'] as $c) {
            $this->assertSame((int) $this->one("SELECT COUNT(*) FROM tf_crew_person WHERE status = 'active' AND archived_at IS NULL AND category = ?", [$c]), count($cp->all(null, true, null, $c)), "tab $c");
        }
        $toClassify = (int) $this->one("SELECT COUNT(*) FROM tf_crew_person WHERE status = 'active' AND archived_at IS NULL AND (category IS NULL OR category = '')");
        $this->assertSame($toClassify, count(array_filter($all, static fn ($p) => empty($p['category']))), 'the "To classify" tab');
        $this->assertSame('JSD', CrewPeople::lineCategory('JSD'));
        $this->assertSame('JSCG', CrewPeople::lineCategory('JSCG'));
        $this->assertSame('JSNTG', CrewPeople::lineCategory('GUIDE_JP'));
    }

    public function testTheStandardsAreShanthasFigures(): void
    {
        $cp = $this->crew();
        $s = [];
        foreach ($cp->standardsSummary() as $row) {
            $s[$row['item']] = $row;
        }
        $this->assertSame(4000.0, (float) $s['bata']['amount'], 'standard bata LKR 4,000 per day');
        $this->assertSame('2026-10-05', $s['bata']['effective_from']);
        $this->assertSame(7500.0, (float) $s['guide_fee']['amount'], 'standard guide fee LKR 7,500 per day');
        $this->assertSame('2026-10-05', $s['guide_fee']['effective_from']);
        $this->assertNull($s['overnight']['amount'], 'the overnight allowance stays unset until decided');
        $this->assertSame(['JSD', 'JSCG'], $s['bata']['for']);
        $this->assertSame(['JSNTG'], $s['guide_fee']['for']);
    }

    public function testWhatAPersonIsPaidFollowsStandardThenAgreed(): void
    {
        $cp = $this->crew();
        $jsd = $cp->all(null, true, null, 'JSD') ?: $cp->all();     // a classified driver when the copy has one, else any person on the master
        $this->assertNotSame([], $jsd);
        $p = $jsd[0];
        $bata = $cp->effectiveBata($p);
        foreach (['agreed', 'standard', 'bata', 'source', 'overnight', 'overnight_source'] as $k) {
            $this->assertArrayHasKey($k, $bata, "effectiveBata.$k");
        }
        if (!empty($p['category'])) {
            $this->assertNotNull($bata['bata'], 'a classified person has a bata: the standard, unless agreed');
            $this->assertContains($bata['source'], ['standard', 'agreed']);
            if ($bata['source'] === 'standard') {
                $this->assertSame((float) ($p['category'] === 'JSNTG' ? 7500 : 4000), (float) $bata['bata']);
            }
        } else {
            $this->assertNull($bata['bata'], 'an unclassified person has no bata figure yet — nothing is guessed');
        }
        $rate = $cp->effectiveRate($p);
        foreach (['general', 'special', 'rate_km', 'source', 'class'] as $k) {
            $this->assertArrayHasKey($k, $rate, "effectiveRate.$k");
        }
        if ($rate['special'] !== null) {
            $this->assertSame((float) $rate['special']['rate_km'], (float) $rate['rate_km'], 'a special rate overrides the general one for that person only');
        } elseif ($rate['general'] !== null) {
            $this->assertSame((float) $rate['general']['rate_km'], (float) $rate['rate_km'], 'without a special rate the class rate applies');
        } else {
            $this->assertNull($rate['rate_km'], 'no class, no rate — nothing is guessed');
        }
        $w = $cp->warningsFor($p);
        $this->assertIsArray($w);
    }

    public function testBankDetailsAreOnlyGivenWithThePrivilege(): void
    {
        $cp = $this->crew();
        $withBank = $cp->all(null, true, null, null, true);
        $without  = $cp->all(null, true, null, null, false);
        $this->assertSame(count($withBank), count($without));
        if ($without !== []) {
            $this->assertArrayNotHasKey('bank_account_no', $without[0], 'no bank column without the privilege');
        }
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM menu_list WHERE url = 'master_files/crew_people/bank' AND del = 0"), 'the bank privilege is a menu entry of its own');
        $this->assertSame(0, (int) $this->one("SELECT COUNT(*) FROM user_privileges up JOIN menu_list ml ON ml.id = up.menu_id WHERE ml.url = 'master_files/crew_people/bank' AND up.group_id <> 1"), 'held by the System Administrator only');
    }

    public function testTheGeneralRatesByVehicleClass(): void
    {
        $tr = new TransportRates($this->db(), 'tcadmin');
        $want = ['SEDAN' => 100, 'VAN' => 130, 'HIROOF' => 140, 'MINICOACH' => 275, 'COACH33' => 350, 'BUS45' => 425, 'BUS55' => 475];
        $board = [];
        foreach ($tr->board() as $row) {
            $code = $row['code'] ?? $row['class_code'] ?? ($row['class']['code'] ?? null);
            $cur  = $row['current'] ?? $row['rate'] ?? null;
            if ($code !== null) {
                $board[$code] = (float) ($cur['rate_km'] ?? 0);
            }
        }
        foreach ($want as $code => $rate) {
            $this->assertSame((float) $rate, $board[$code] ?? null, "$code LKR $rate per km (the live rates from 16 Sep 2026)");
            $cur = $tr->current($code);
            $this->assertNotNull($cur);
            $this->assertSame((float) $rate, (float) $cur['rate_km']);
            $this->assertSame('LKR', $cur['currency']);
        }
        $this->assertNull($tr->current('NOSUCH'));
        $this->assertSame([], $tr->staleOpen());
        $planner = new VehiclePlanner($this->db(), $tr);
        $this->assertTrue($planner->available());
        $this->assertCount(7, $planner->classes());
        $s = $planner->suggest(2);
        $this->assertIsArray($s);
    }

    public function testMileageAllowancesAndTheLookupNeverGuess(): void
    {
        $ma = new MileageAllowances($this->db(), 'tcadmin');
        $all = [];
        foreach ($ma->all() as $a) {
            $all[$a['code']] = (float) $a['km'];
        }
        $this->assertSame(80.0, $all['STAY_DAY'] ?? null, 'stay day 80 km');
        $this->assertSame(30.0, $all['GARAGE'] ?? null, 'garage 30 km');
        $this->assertSame(80.0, $ma->stayDayKm());
        $this->assertSame([30.0, 30.0], array_map('floatval', $ma->garageKm()));
        $ml = new MileageLookup($this->db());
        $this->assertTrue($ml->available());
        $r = $ml->between('Colombo', 'Kandy');
        $this->assertArrayHasKey('km', $r);
        $this->assertArrayHasKey('why', $r);
        if ($r['km'] === null) {
            $this->assertStringContainsString('no record', strtolower((string) $r['why']), 'with no record it says so instead of guessing');
        }
        $this->assertSame('Kandy', MileageLookup::townOf('Kandy'));
    }
}
