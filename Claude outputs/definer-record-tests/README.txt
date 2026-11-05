Tests of the guarded definer records, 11 Oct 2026. NONE of these ran on the development server.
They ran on a separate scratch MySQL 8.0.46 server in Claude's cloud workspace, on a database named `avenir` restored from the
independent export. For tests T2-T8 the copies were built with that scratch server's identity (and, where stated, an approval
reference "TEST ONLY - scratch server"); the shipped files carry the development server's identity and approval NULL.

 T1  shipped rollback record, unmodified           -> stopped at check 1 (approval), error 1048; nothing changed
 T2  approval set, development identity            -> stopped at check 2 (server); nothing changed
 T3  approval + scratch identity, objects not in the repaired state -> stopped at check 3a; nothing changed
 T4  forward recovery file                          -> six objects under root@localhost, bodies/sql_mode as recorded; all post-checks 1
 T5  complete rollback run                          -> all checks 1; six objects under dbmasteruser@%
 T5b reading a view afterwards                      -> ERROR 1449 (as on development before the repair)
 T5c saving an allocation afterwards                -> ERROR 1449, yet the MyISAM row was written (630 rows) with no audit row (1786)
 T6  forward recovery after T5                      -> all post-checks 1; Q2 lists allocation 807 (the row written in T5c)
 T7  rollback stopping between DROP and CREATE      -> views under dbmasteruser@%, DELETE trigger missing
 T8  forward recovery after T7                      -> 3 triggers, all root@localhost; views readable (acc_selct 1,174 rows)

Development server, read-only (11 Oct 2026, evening): check 2 = 1, 3a = 1, 3b = 1, 3c = 1; 629 allocations, 1,786 audit rows,
Q2 = 0 allocations without an INSERT audit row. So on development the approval line is what stops the record.
build.py generates both files from the recorded definitions.
