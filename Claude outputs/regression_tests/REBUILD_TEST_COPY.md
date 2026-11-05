# Rebuilding the test copy (the recipe used on 6 October 2026)

A test copy is the real Avenir application running against a database named `avenir_test`, built from the live files
(read-only) and the backups. Nothing here touches live. Roughly an hour.

## 1. The files

On the live machine, from `C:\wamp64\www\avenir`, archive the program (never `.env`, never the backups folders):

    tar czf <scratch>/avenir_testcopy.tgz app vendor public/index.php public/.htaccess public/assets public/uploads public/favicon.ico \
        spark composer.json composer.lock preload.php .htaccess writable/hotel_rates_entry writable/uploads \
        writable/schema_dump.sql writable/schema_dump_data.json writable/schema_dump_suppliers.json writable/schema_map.json \
        writable/tf_backups/<latest tf-*.sql>

Unpack it where the copy will live (`<copy>`), and give the copy its own `.env` (nothing from live's):

    CI_ENVIRONMENT = production
    app.baseURL = 'http://127.0.0.1:8080/avenir/'
    database.default.hostname = 127.0.0.1
    database.default.database = avenir_test
    database.default.username = root
    database.default.password = ''
    database.default.DBDriver = MySQLi
    database.default.port = 3307
    (the same for database.secondary.* and database.secondDatabase.*)
    email.protocol = smtp
    email.SMTPHost = 127.0.0.1
    email.SMTPPort = 9              # no mail ever leaves the copy

Serve it with PHP's own server through a router that maps `/avenir/...` to `public/` (`router.php` in the workspace
notes: static files under public/ served with their type, everything else to `public/index.php` with SCRIPT_NAME
`/avenir/index.php`). `php -S 127.0.0.1:8080 router.php`.

## 2. The database (MariaDB 10.11, port 3307)

1. `writable/schema_dump.sql` (all tables; replace `utf8mb4_0900_ai_ci` with `utf8mb4_unicode_ci`; load the tables
   first, the views after, with `--force` until the dependent views settle; `job_role_assignments` without its foreign keys).
2. The 2024 legacy data (`app/2024-09-25-14-04-11.sql`) into a side database, then copied table by table on the columns
   both have (`INSERT IGNORE … SELECT`).
3. The 2026 master data: `writable/schema_dump_data.json` (hotel_rate, room_type, tour_quotations hotel columns) and
   `writable/schema_dump_suppliers.json` (supplier, account_posting hotels, consignee, daily_usd_rate), upserted.
4. The latest TourFlow copy (`writable/tf_backups/tf-*.sql`, collation replaced as above): the tf_ tables and the menu /
   privilege rows.
5. The Hotel Rates module: `app/Database/Sql/hotel_rates_v2_schema.sql` and `hotel_rates_v2_menu.sql`, then in the copy
   (signed in as the test administrator) `POST master_files/hotel_rate_approval/setup` with `{"step":"install"}`,
   `{"step":"migrate"}`, `{"step":"load_plans"}`, `{"step":"work_scan"}`.
6. TourFlow at the live schema: `POST tour/intake/setup` as the test administrator (Install now).
7. The groups live has beyond the 2024 data (`group_profile` 13 Operational, 15 Maintenance, 21 Management, 24 Driver,
   25 Admin, 26 Quotation Desk, 27 Hotel Desk, 28 Transport & Crew Guide Allocation Desk) and the desk grants exactly as
   live's Settings → Group Privileges show them (read them there; do not copy legacy grants).
8. The counters the legacy screens draw numbers from must match the 2026 data, not the 2024 dump:
   `UPDATE account_numbers SET PR_Code = (SELECT MAX(P_Code) FROM account_posting WHERE B_Code = 6 AND T_Code = 9);`
   (on 6 Oct 2026 the copy's counter was 10766 against accounts up to 11777, and a test supplier landed on an
   existing account — the release now guards against that, but the copy should start right). `invoice_no.hotel_code`
   is brought up by the Hotel Rates Install now (schema 8).
9. The test accounts (copy only), each with an `employee` row whose `emp_num` = its id:
   `tcadmin` (group 1, user_role 1), `tcquote` (26), `tctransport` (28), `tchotel` (27), `tcmgmt` (21); passwords in
   `TEST_ACCOUNTS.txt` of the battery's package, hashed with `password_hash`.

## 3. Check

    bash tests/newgen/run_battery.sh php        # should end OK
    bash tests/newgen/run_battery.sh screens    # every suite "n ok, 0 failed"

If the database is not named `*_test`, both halves refuse to run — that is the point.
