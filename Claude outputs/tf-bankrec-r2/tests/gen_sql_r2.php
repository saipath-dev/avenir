<?php
/**
 * tf-bankrec-r2 — writes the test-bed SQL from the Schema class itself, so the scripts and the "Set up" button can never
 * differ:  php gen_sql_r2.php <path to Schema.php> <test-bed database name> <out dir>
 *   upgrade-v2-test-bed.sql          version 1 → 2 (guarded: only that database, only at version 1)
 *   rollback-schema-v2-to-v1.sql     version 2 → 1 (guarded: only that database, only at version 2) — removes the
 *                                    version-2 columns and tables and what was recorded in them
 */
[$_, $schemaFile, $dbName, $out] = $argv + [null, null, null, null];
if (!$schemaFile || !$dbName || !$out) {
    fwrite(STDERR, "usage: php gen_sql_r2.php Schema.php <db> <outdir>\n");
    exit(2);
}
require $schemaFile;
use App\Libraries\BankRec\Schema;

$tables = (new ReflectionMethod(Schema::class, 'tables'));
$tables->setAccessible(true);
$all = $tables->invoke(null, 'utf8mb4_unicode_ci');
$one = static fn (string $sql) => preg_replace('/\s+/', ' ', trim($sql));
$who = "'claude-technical (upgrade script run in phpMyAdmin; isolated test instance)'";
$guard = static function (string $version) use ($dbName) {
    return "-- Guard: stops (error 1048) unless this is the isolated test-bed database at schema version {$version}.\n"
        . "DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;\nCREATE TEMPORARY TABLE brc_r2_guard (ok TINYINT NOT NULL);\n"
        . "INSERT INTO brc_r2_guard (ok) VALUES (IF(DATABASE() = '{$dbName}', 1, NULL));\n"
        . "INSERT INTO brc_r2_guard (ok) VALUES (IF((SELECT v FROM tpd_meta WHERE k = 'brc_schema_version') = '{$version}', 1, NULL));\n"
        . "INSERT INTO brc_r2_guard (ok) VALUES (IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brc_account_map') = 1, 1, NULL));\n";
};

$u = "-- tf-bankrec-r2: schema version 1 → 2 of the Daily Bank Reconciliation on the ISOLATED TEST BED ({$dbName}) only.\n"
    . "-- Generated from app/Libraries/BankRec/Schema.php (the same steps as the \"Set up\" button). Adds two tables and columns to the brc_ tables;\n"
    . "-- no other table is altered. Run with the test-bed database selected. Never on the development or live database.\n\n" . $guard('1') . "\n";
foreach (['brc_claim', 'brc_mark_event'] as $t) {
    $u .= $one($all[$t]) . ";\n";
}
foreach (Schema::V2_COLUMNS as $t => $cols) {
    $u .= 'ALTER TABLE `' . $t . '` ' . implode(', ', array_map(static fn ($c, $d) => "ADD COLUMN `{$c}` {$d}", array_keys($cols), $cols)) . ";\n";
}
$u .= "UPDATE brc_transfer SET active_from = from_line_id, active_to = to_line_id WHERE status = 'active' AND active_from IS NULL;\n";
foreach (Schema::V2_INDEXES as $t => $idx) {
    $u .= 'ALTER TABLE `' . $t . '` ' . implode(', ', array_map(static fn ($d) => 'ADD ' . $d, $idx)) . ";\n";
}
$u .= "-- backfill (as Schema::backfillV2): a claim for every matched or classified item; existing explanations of differences\n";
$u .= "INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at) SELECT CONCAT('L', i.line_id), m.bank_account_id, 'match', m.id, m.created_by, m.created_at FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' WHERE i.side = 'bank';\n";
$u .= "INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at) SELECT CONCAT('B', m.bank_account_id, ':', i.book_key), m.bank_account_id, 'match', m.id, m.created_by, m.created_at FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' WHERE i.side = 'book';\n";
$u .= "INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at) SELECT IF(k.side = 'bank', CONCAT('L', k.line_id), CONCAT('B', k.bank_account_id, ':', k.book_key)), k.bank_account_id, 'mark', k.id, k.created_by, k.created_at FROM brc_mark k WHERE k.status = 'active';\n";
$u .= "UPDATE brc_match SET diff_note = note, diff_noted_by = created_by, diff_noted_at = created_at WHERE diff_note IS NULL AND difference_lkr <> 0 AND diff_reason IN ('exchange rate', 'other') AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) >= 5;\n";
$u .= "UPDATE tpd_meta SET v = '2' WHERE k = 'brc_schema_version';\n";
$u .= "INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, new_value, reason) SELECT DATE_FORMAT(NOW(3), '%Y-%m-%d %H:%i:%s.%f'), {$who}, 'brc_setup', 'brc_setup', 'v2',\n"
    . "  CONCAT('{\"from\":1,\"to\":2,\"claims\":', (SELECT COUNT(*) FROM brc_claim), ',\"fx_differences_to_explain\":', (SELECT COUNT(*) FROM brc_match WHERE status = 'active' AND difference_lkr <> 0 AND diff_reason = 'exchange rate' AND diff_note IS NULL), '}'), 'tf-bankrec-r2 schema upgrade on the isolated test instance' FROM DUAL;\n";
$u .= "-- check: the claims recorded must equal the matched + classified items\n";
$u .= "SELECT (SELECT COUNT(*) FROM brc_claim) AS claims, (SELECT COUNT(*) FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active') + (SELECT COUNT(*) FROM brc_mark WHERE status = 'active') AS items_matched_or_classified, (SELECT v FROM tpd_meta WHERE k = 'brc_schema_version') AS schema_version;\n";
$u .= "DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;\n";
file_put_contents($out . '/upgrade-v2-test-bed.sql', $u);

$r = "-- tf-bankrec-r2: schema version 2 → 1 on the ISOLATED TEST BED ({$dbName}) only — the rollback of the r2 database change.\n"
    . "-- Removes the two version-2 tables and the version-2 columns, with everything recorded in them (claims, timing history, partial-match links,\n"
    . "-- difference explanations, posting verification, record fingerprints). Use together with: python apply.py rollback <avenir_rv root>.\n"
    . "-- To return to the demonstration data exactly as it was before r2, use reset-demo-to-r1-snapshot.sql instead.\n\n" . $guard('2') . "\n";
$r .= "DROP TABLE IF EXISTS brc_claim;\nDROP TABLE IF EXISTS brc_mark_event;\n";
foreach (Schema::V2_INDEXES as $t => $idx) {
    $r .= 'ALTER TABLE `' . $t . '` ' . implode(', ', array_map(static fn ($n) => "DROP INDEX `{$n}`", array_keys($idx))) . ";\n";
}
foreach (Schema::V2_COLUMNS as $t => $cols) {
    $r .= 'ALTER TABLE `' . $t . '` ' . implode(', ', array_map(static fn ($c) => "DROP COLUMN `{$c}`", array_keys($cols))) . ";\n";
}
$r .= "UPDATE brc_match SET status = 'undone', undone_by = 'schema rollback', undone_at = NOW(), undo_reason = 'superseded partial match (r2) removed by the schema rollback' WHERE status = 'superseded';\n";
$r .= "UPDATE brc_mark SET status = 'cleared' WHERE status = 'matched';\n";
$r .= "UPDATE tpd_meta SET v = '1' WHERE k = 'brc_schema_version';\n";
$r .= "INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, reason) SELECT DATE_FORMAT(NOW(3), '%Y-%m-%d %H:%i:%s.%f'), {$who}, 'brc_setup', 'brc_setup', 'v1 (rollback)', 'tf-bankrec-r2 schema rolled back on the isolated test instance' FROM DUAL;\n";
$r .= "DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;\n";
file_put_contents($out . '/rollback-schema-v2-to-v1.sql', $r);
echo "written\n";
