<?php
/**
 * One process = one new hotel, for A13's concurrency check: twenty of these are started at the same instant and each
 * must come back with a different code. Test copy only (the bootstrap's database guard applies).
 */
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';
$db = \Config\Database::connect();
$name = (string) ($db->query('SELECT DATABASE() AS d')->getRowArray()['d'] ?? '');
if (!preg_match('/_test$/', $name)) {
    fwrite(STDERR, "REFUSED: '{$name}' is not a test database\n");
    exit(99);
}
$i = (int) ($argv[1] ?? 0);
usleep(random_int(0, 20000));
$svc = new \App\Libraries\HotelRates\HotelRateService($db, "probe{$i}");
$id = $svc->createHotel("Battery probe hotel P{$i}", '6-9-11-97' . str_pad((string) (100 + $i), 3, '0', STR_PAD_LEFT), null);
echo $id . ' ' . $svc->codes()->codeOf($id) . PHP_EOL;
