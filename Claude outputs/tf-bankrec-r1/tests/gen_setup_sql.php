<?php
// Prints the set-up SQL of tf-bankrec-r1 exactly as BankRec\Schema::ensure() would run it (tables), plus the privilege rows.
namespace CodeIgniter { }
namespace {
require $argv[1] . '/app/Libraries/BankRec/Schema.php';
$coll = $argv[2];
$r = new ReflectionMethod(\App\Libraries\BankRec\Schema::class, 'tables');
$r->setAccessible(true);
$out = "-- tf-bankrec-r1: set-up of the Daily Bank Reconciliation on a TEST BED database (the isolated restore copy).\n"
     . "-- The same statements BankRec\\Schema::ensure() runs (CREATE TABLE IF NOT EXISTS; no existing table altered), plus the\n"
     . "-- privilege rows and the department grants used for the tests. Run with the test-bed database selected.\n"
     . "-- On the development system the System Administrator uses the page's Set up button instead.\n\n";
foreach ($r->invoke(null, $coll) as $name => $sql) {
    $out .= preg_replace('/\s+/', ' ', trim($sql)) . ";\n";
}
$out .= "\nSET @cat = (SELECT id FROM menu_category WHERE category = 'Accounts' AND del = 0 ORDER BY id LIMIT 1);\n";
foreach (\App\Libraries\BankRec\Schema::MENUS as $url => [$name, $shown]) {
    $n = addslashes($name);
    $out .= "INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) SELECT @cat, '{$n}', 1, 0, '{$url}', (SELECT COALESCE(MAX(m.order_id), 0) + 1 FROM menu_list m WHERE m.cat_id = @cat), {$shown} FROM DUAL WHERE @cat IS NOT NULL AND NOT EXISTS (SELECT 1 FROM menu_list x WHERE x.url = '{$url}' AND x.del = 0);\n";
}
$grants = ['1' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review'],
    '30' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare'], '29' => ['accounts/daily_bank_rec', 'accounts/daily_bank_rec/review'],
    '35' => ['accounts/daily_bank_rec'], '34' => ['accounts/daily_bank_rec']];
$out .= "-- grants: System Administrator (1) all; Accounts (30) view + prepare; Management Approver (29) view + review; Bank Authoriser (35) and Auditor (34) view\n";
foreach ($grants as $g => $urls) {
    foreach ($urls as $u) {
        $out .= "INSERT INTO user_privileges (group_id, user_id, menu_id) SELECT {$g}, COALESCE((SELECT MIN(p.user_id) FROM user_privileges p WHERE p.group_id = {$g}), 1), m.id FROM menu_list m WHERE m.url = '{$u}' AND m.del = 0 AND NOT EXISTS (SELECT 1 FROM user_privileges q WHERE q.group_id = {$g} AND q.menu_id = m.id);\n";
    }
}
$out .= "INSERT INTO tpd_meta (k, v) VALUES ('brc_schema_version', '1') ON DUPLICATE KEY UPDATE v = VALUES(v);\n";
echo $out;
}
