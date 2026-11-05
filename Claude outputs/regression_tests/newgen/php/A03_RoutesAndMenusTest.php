<?php
declare(strict_types=1);

namespace NewGen\Php;

use Config\Services;
use NewGen\NewGenTestCase;

/**
 * Every route of Tour Operations and Hotel Rates points at a controller method that exists; every Tour Desk menu URL
 * has one live menu entry; the privilege view the sidebar and the route filter read agrees with the tables under it.
 */
final class A03_RoutesAndMenusTest extends NewGenTestCase
{
    /** @return array<int, array{0:string,1:string,2:string}> [method, from, handler] */
    private function tourRoutes(): array
    {
        $routes = Services::routes();
        $out = [];
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $verb) {
            foreach ($routes->getRoutes($verb) as $from => $to) {
                // the new-generation routes only (Routes.tour_flow.php and Routes.hotel_rate_v2.php); the legacy tour/* module has its own, older routes
                if (preg_match('#^(tour/(intake|hotel_bookings|transport_ops|board|ticket_stock|manage|pricing)|master_files/(hotel_rate_v2|hotel_rate_approval|hotel_work|entrance_master|meal_master|free_visits|paid_services|excursion_rates|tour_photos|offer_selection|transport_rates|mileage_allowances|crew_people|crew_bata))(/|$)#', (string) $from)) {
                    $out[] = [$verb, (string) $from, is_string($to) ? $to : json_encode($to)];
                }
            }
        }
        return $out;
    }

    public function testThereAreTourOperationsRoutes(): void
    {
        $this->assertGreaterThan(200, count($this->tourRoutes()), 'the Tour Operations and masters route table');
    }

    public function testEveryTourOperationsRoutePointsAtARealControllerMethod(): void
    {
        $missing = [];
        foreach ($this->tourRoutes() as [$verb, $from, $to]) {
            if (!preg_match('#^\\\\?([A-Za-z0-9_\\\\]+)::([A-Za-z0-9_]+)#', $to, $m)) {
                continue;   // a closure
            }
            $class = '\\' . ltrim($m[1], '\\');
            if (!class_exists($class)) {
                $missing[] = "$verb $from -> $to (no class)";
                continue;
            }
            if (!method_exists($class, $m[2])) {
                $missing[] = "$verb $from -> $to (no method)";
            }
        }
        $this->assertSame([], $missing, 'routes with no controller behind them');
    }

    public function testEveryGuardedRouteSitsUnderAMenuUrlOrAnAlias(): void
    {
        // the route filter opens a path when the signed-in group holds a menu URL that is a prefix of it (or an alias in the filter)
        $urls = array_map(static fn ($r) => $r['url'], $this->rows('SELECT url FROM menu_list WHERE del = 0'));
        $ref  = new \ReflectionClass(\App\Filters\UserPermissionFilter::class);
        $aliases = $ref->hasConstant('MENU_ROUTE_ALIASES') ? $ref->getConstant('MENU_ROUTE_ALIASES') : [];
        $covered = $urls;
        foreach ($aliases as $menu => $paths) {
            foreach ((array) $paths as $p) {
                $covered[] = $p;
            }
        }
        $orphans = [];
        foreach ($this->tourRoutes() as [$verb, $from, $to]) {
            $path = preg_replace('#/\(.*$#', '', $from);                       // up to the first placeholder
            $path = rtrim($path, '/');
            $ok = false;
            foreach ($covered as $u) {
                $u = trim($u, '/');
                if ($u !== '' && ($path === $u || str_starts_with($path, $u . '/'))) { $ok = true; break; }
            }
            if (!$ok) {
                $orphans[] = "$verb $from";
            }
        }
        $this->assertSame([], $orphans, 'routes no menu privilege could ever open (the filter would refuse everyone)');
    }

    public function testTheTourDeskMenuUrlsAreSingleAndLive(): void
    {
        foreach (['tour/intake', 'tour/hotel_bookings', 'tour/transport_ops', 'tour/board', 'tour/ticket_stock', 'tour/manage', 'tour/pricing', 'tour/intake/mileage', 'tour/transport_ops/crew_change', 'tour/transport_ops/driver_advance',
                  'master_files/hotel_rate_v2', 'master_files/hotel_rate_approval', 'master_files/hotel_work', 'master_files/entrance_master', 'master_files/meal_master', 'master_files/free_visits', 'master_files/paid_services', 'master_files/excursion_rates', 'master_files/tour_photos', 'master_files/offer_selection',
                  'master_files/transport_rates', 'master_files/mileage_allowances', 'master_files/crew_people', 'master_files/crew_bata', 'master_files/crew_people/bank'] as $u) {
            $this->assertSame(1, (int) $this->one('SELECT COUNT(*) FROM menu_list WHERE url = ? AND del = 0', [$u]), "$u: one live menu entry");
        }
    }

    public function testThePrivilegeViewAgreesWithTheTables(): void
    {
        foreach ([901 => 1, 902 => 26, 903 => 28, 904 => 27] as $uid => $gid) {
            $viaView  = (int) $this->one('SELECT COUNT(DISTINCT menu_id) FROM user_priv_q WHERE user_id = ?', [$uid]);
            $viaTable = (int) $this->one('SELECT COUNT(DISTINCT up.menu_id) FROM user_privileges up JOIN menu_list ml ON ml.id = up.menu_id AND ml.del = 0 JOIN menu_category mc ON mc.id = ml.cat_id AND mc.del = 0 WHERE up.group_id = ?', [$gid]);
            $this->assertSame($viaTable, $viaView, "user $uid (group $gid): the view and the tables name the same screens");
        }
    }

    public function testTheMenuHeadingsAreAsInstalled(): void
    {
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM menu_category WHERE category = 'Transport & Guide Services' AND del = 0"), 'the Transport & Guide Services heading');
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM menu_category WHERE category = 'Master Files' AND del = 0"));
        $this->assertSame(1, (int) $this->one("SELECT COUNT(*) FROM menu_category WHERE category = 'Tour' AND del = 0"));
        $under = array_map(static fn ($r) => $r['url'], $this->rows("SELECT ml.url FROM menu_list ml JOIN menu_category mc ON mc.id = ml.cat_id WHERE mc.category = 'Transport & Guide Services' AND ml.del = 0 ORDER BY ml.order_id"));
        sort($under);
        $this->assertSame(['master_files/crew_bata', 'master_files/crew_people', 'master_files/crew_people/bank', 'master_files/mileage_allowances', 'master_files/transport_rates'], $under, 'the five entries under the heading');
    }
}
