<?php
// a quick render straight from the test database: job 1 (INQ 1001), the card as the full picture and the map alone
define('FCPATH', __DIR__ . '/../tm/public/');
require __DIR__ . '/../tm/app/Libraries/TourFlow/RouteMap.php';
$pdo = new PDO('mysql:unix_socket=' . __DIR__ . '/../mysql.sock;dbname=avenir_test;charset=utf8mb4', 'root', '');
$jobId = (int) ($argv[1] ?? 1);
$job = $pdo->query("SELECT j.*, t.tour_no, t.tour_name, t.agent_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.handler_name FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = $jobId")->fetch(PDO::FETCH_ASSOC);
$job['legs'] = $pdo->query("SELECT * FROM tf_transport_leg WHERE job_id = $jobId ORDER BY day_no, seq, id")->fetchAll(PDO::FETCH_ASSOC);
$drv = $pdo->query("SELECT * FROM tf_transport_crew WHERE job_id = $jobId AND kind = 'driver' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
$co = [];
foreach (['name', 'address', 'phone', 'email'] as $k) { $r = $pdo->query("SELECT v FROM tf_meta WHERE k = 'company_$k'")->fetch(PDO::FETCH_ASSOC); $co[$k] = $r['v'] ?? ''; }
$m = new App\Libraries\TourFlow\RouteMap($pdo);
$d = $m->build($job, ['pax_total' => $job['tour_pax']], $co, $drv);
echo count($d['legs']), ' legs, ', count($d['days']), ' days, ', count($d['stops']), " stops, unknown: ", implode(',', array_map(fn($s) => $s['name'], $d['unknown'])), "\n";
foreach ($d['stops'] as $s) echo $s['order'], ' ', $s['short'], ' days=', implode('/', $s['days']), ' nights=', count($s['nights']), "\n";
file_put_contents(__DIR__ . "/card_$jobId.png", $m->png($d, true));
file_put_contents(__DIR__ . "/map_$jobId.png", $m->png($d, false));
echo "ok\n";
