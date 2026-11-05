tf-session-r1b — sign-out fix + version mark on plugins.js, 12 October 2026. Approved for DEVELOPMENT
(http://localhost/avenir/); also accepted on the isolated test instance. Live is refused.
  = tf-session-r1 (LoginController, UserSessionFilter, plugins.js: see that kit's README) + app/Views/layouts/scripts.php:
    plugins.js?v=<modification time of plugins.js>, so browsers load a changed plugins.js at once — after this install
    and after any later update of plugins.js (no Ctrl+F5).
  python3 apply.py check|apply|rollback <root>   (backs up the 4 files to before/<time>/ first). No database change.
Files: src/ (SOURCE-MD5.txt); tools/patch_scripts_version.py builds scripts.php from the original.
