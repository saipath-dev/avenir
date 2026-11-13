<?= view('layouts/head') ?>
<style>
    /* ---------- Hotel Rates design tokens (scoped) ---------- */
    .hr2 { --hr-navy: #1e3553; --hr-navy-2: #2c4a73; --hr-teal: #0f8b7e; --hr-teal-soft: #e3f4f1; --hr-amber: #1a1a1a; --hr-amber-soft: #ffffff; --hr-red: #b23a32; --hr-red-soft: #fbe9e7; --hr-green: #1f8a4c; --hr-green-soft: #e4f4ea; --hr-blue-soft: #e6eef8; --hr-ink: #1d2733; --hr-muted: #5f6f80; --hr-line: #dfe5ec; --hr-bg: #f4f6f9; --hr-card: #ffffff; --hr-radius: 10px; color: var(--hr-ink); }
    .hr2 .hr-shell { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; align-items: start; }
    @media (max-width: 1100px) { .hr2 .hr-shell { grid-template-columns: 1fr; } }
    .hr2 .hr-card { background: var(--hr-card); border: 1px solid var(--hr-line); border-radius: var(--hr-radius); box-shadow: 0 1px 2px rgba(16,24,40,.04); }
    .hr2 .hr-card + .hr-card { margin-top: 14px; }
    .hr2 .hr-card-h { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--hr-line); }
    .hr2 .hr-card-h h6 { margin: 0; font-size: 13.5px; font-weight: 600; letter-spacing: .01em; }
    .hr2 .hr-card-b { padding: 14px 16px; }
    .hr2 .hr-title { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 14px; }
    .hr2 .hr-title h4 { margin: 0; font-weight: 600; color: var(--hr-navy); }
    .hr2 .hr-title .sub { color: var(--hr-muted); font-size: 12.5px; }
    .hr2 .hr-toolbar { margin-left: auto; display: flex; flex-wrap: wrap; gap: 6px; }
    /* Hotel rate management buttons — the same button language as the Tour Desk bar: 38px, 10px corners, HK Grotesk,
       the logo's purple on icons and its green for the one action that adds rates. Every id is unchanged. */
    .hr2 .hr-actions { --hr-purple:#842484; --hr-tint:rgba(132,36,132,.09); --hr-pl:rgba(132,36,132,.18); margin-left:auto; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .hr2 .hr-actions .grp { display:flex; align-items:center; gap:4px; flex-wrap:wrap; padding:4px; background:#fff; border:1px solid var(--hr-pl); border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.06); }
    .hr2 .hr-actions .act { display:inline-flex; align-items:center; gap:8px; height:36px; padding:0 14px; border-radius:9px; border:1px solid transparent; background:transparent;
        color:#2c3e50; font-size:13px; font-weight:500; letter-spacing:.2px; line-height:1; white-space:nowrap; cursor:pointer; transition:background-color .2s ease, transform .2s ease, box-shadow .2s ease; }
    .hr2 .hr-actions .act i { font-size:15px; color:var(--hr-purple); }
    .hr2 .hr-actions .act:hover { background:var(--hr-tint); }
    .hr2 .hr-actions .act:active { transform:translateY(1px) scale(.98); transition-duration:.1s; }
    .hr2 .hr-actions .act:focus-visible { outline:none; box-shadow:0 0 0 .2rem rgba(132,36,132,.22); }
    .hr2 .hr-actions .act .pill { margin-left:2px; }
    .hr2 .hr-actions .act-primary { display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 14px 0 16px; border-radius:10px; border:0; color:#fff; font-size:13.5px; font-weight:600; letter-spacing:.2px; line-height:1; white-space:nowrap; cursor:pointer;
        background:linear-gradient(135deg, #84C155 0%, #78b448 100%); box-shadow:0 3px 10px rgba(120,180,72,.35); transition:box-shadow .3s ease, transform .3s ease; }
    .hr2 .hr-actions .act-primary i { font-size:15px; } .hr2 .hr-actions .act-primary .chev { font-size:11px; opacity:.9; }
    .hr2 .hr-actions .act-primary:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(120,180,72,.4); }
    .hr2 .hr-actions .act-primary:focus-visible, .hr2 .hr-actions .act-primary[aria-expanded="true"] { outline:none; box-shadow:0 0 0 .2rem rgba(120,180,72,.25), 0 3px 10px rgba(120,180,72,.35); }
    .hr2 .hr-actions .dropdown-menu { border:1px solid var(--hr-pl); border-radius:12px; padding:6px; min-width:320px; margin-top:8px !important; box-shadow:0 8px 32px rgba(0,0,0,.15); }
    .hr2 .hr-actions .dropdown-item { display:flex; gap:12px; align-items:flex-start; padding:10px 12px; border-radius:10px; white-space:normal; color:#2c3e50; background:transparent; border:0; width:100%; text-align:left; }
    .hr2 .hr-actions .dropdown-item .ic { width:36px; height:36px; border-radius:50%; display:grid; place-items:center; flex:none; background:#fff; color:var(--hr-purple); font-size:15px; box-shadow:0 4px 12px rgba(0,0,0,.1); }
    .hr2 .hr-actions .dropdown-item b { display:block; font-size:13.5px; font-weight:600; line-height:1.25; }
    .hr2 .hr-actions .dropdown-item span.d { display:block; font-size:11.5px; color:#6c757d; line-height:1.35; margin-top:2px; }
    .hr2 .hr-actions .dropdown-item:hover, .hr2 .hr-actions .dropdown-item:focus { background:var(--hr-tint); color:#2c3e50; }
    @media (max-width: 1100px) { .hr2 .hr-actions .act > span:not(.pill) { display:none; } .hr2 .hr-actions .act { padding:0 11px; } }
    /* the row of buttons on the chosen hotel: same language, one size down */
    .hr2 .hotel-actions .btn { border-radius:9px; height:34px; display:inline-flex; align-items:center; gap:7px; font-size:12.75px; letter-spacing:.2px; }
    .hr2 .hotel-actions .btn-light { border-color:rgba(132,36,132,.18); } .hr2 .hotel-actions .btn-light i { color:#842484; }
    .hr2 .hotel-actions .btn-light:hover { background:rgba(132,36,132,.09); }
    .hr2 .hotel-actions .btn-primary { background:linear-gradient(135deg, #84C155 0%, #78b448 100%); border-color:transparent; box-shadow:0 3px 10px rgba(120,180,72,.35); font-weight:600; }
    .hr2 .hotel-actions .btn-primary:hover { background:linear-gradient(135deg, #8fca60 0%, #78b448 100%); border-color:transparent; }
    .hr2 .hotel-actions .btn-group > .btn-primary:first-child { border-top-right-radius:0; border-bottom-right-radius:0; } .hr2 .hotel-actions .btn-group > .dropdown-toggle-split { border-top-left-radius:0; border-bottom-left-radius:0; padding-left:8px; padding-right:8px; }
    /* buttons */
    .hr2 .btn { white-space: nowrap; border-radius: 8px; font-weight: 500; font-size: 12.5px; padding: 6px 12px; line-height: 1.4; }
    .hr2 .btn-sm { padding: 4px 9px; font-size: 12px; }
    .hr2 .btn-primary { background: var(--hr-navy); border-color: var(--hr-navy); color: #fff; }
    .hr2 .btn-primary:hover { background: var(--hr-navy-2); border-color: var(--hr-navy-2); }
    .hr2 .btn-success { background: var(--hr-teal); border-color: var(--hr-teal); color: #fff; }
    .hr2 .btn-success:hover { background: #0c7268; border-color: #0c7268; }
    .hr2 .btn-light, .hr2 .btn-outline-secondary { background: #fff; border: 1px solid var(--hr-line); color: var(--hr-ink); }
    .hr2 .btn-light:hover { background: var(--hr-bg); }
    .hr2 .btn-ghost { background: transparent; border: 1px solid transparent; color: var(--hr-navy); }
    .hr2 .btn-ghost:hover { background: var(--hr-bg); }
    .hr2 .btn-danger-soft { background: var(--hr-red-soft); border-color: transparent; color: var(--hr-red); }
    .hr2 .btn-warn-soft { background: var(--hr-amber-soft); border-color: transparent; color: var(--hr-amber); }
    /* pills */
    .hr2 .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; letter-spacing: .02em; line-height: 1.6; white-space: nowrap; }
    .hr2 .pill-green { background: var(--hr-green-soft); color: var(--hr-green); }
    .hr2 .pill-amber { background: var(--hr-amber-soft); border:1px solid #d9d3dd; color: var(--hr-amber); }
    .hr2 .pill-red { background: var(--hr-red-soft); color: var(--hr-red); }
    .hr2 .pill-blue { background: var(--hr-blue-soft); color: var(--hr-navy); }
    .hr2 .pill-teal { background: var(--hr-teal-soft); color: var(--hr-teal); }
    .hr2 .pill-grey { background: #eef1f5; color: var(--hr-muted); }
    /* hotel rail */
    .hr2 .rail-search { padding: 12px; border-bottom: 1px solid var(--hr-line); }
    .hr2 .rail-search input { border-radius: 8px; }
    .hr2 .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .hr2 .chip { font-size: 11.5px; padding: 3px 9px; border-radius: 999px; border: 1px solid var(--hr-line); background: #fff; cursor: pointer; color: var(--hr-muted); user-select: none; }
    .hr2 .chip.on { background: var(--hr-navy); color: #fff; border-color: var(--hr-navy); }
    .hr2 .hotel-list { max-height: calc(100vh - 300px); overflow-y: auto; }
    .hr2 .hotel-item { display: block; padding: 9px 12px; border-left: 3px solid transparent; border-bottom: 1px solid #eef1f5; cursor: pointer; color: inherit; text-decoration: none; }
    .hr2 .hotel-item:hover { background: var(--hr-bg); }
    .hr2 .hotel-item.active { background: var(--hr-blue-soft); border-left-color: var(--hr-navy); }
    .hr2 .hotel-item .nm { font-weight: 600; font-size: 13px; }
    .hr2 .hotel-item .meta { font-size: 11.5px; color: var(--hr-muted); }
    .hr2 .hcode { display: inline-block; font-family: Consolas, Menlo, monospace; font-size: 11px; font-weight: 700; letter-spacing: .03em; padding: 0 6px; border-radius: 6px; background: var(--hr-blue-soft); color: var(--hr-navy); white-space: nowrap; vertical-align: middle; }
    .hr2 h5 .hcode { font-size: 12.5px; padding: 1px 8px; margin-left: 8px; }
    .hr2 .rail-foot { padding: 8px 12px; font-size: 11.5px; color: var(--hr-muted); border-top: 1px solid var(--hr-line); }
    /* Frequently Used: a star on each hotel of the list, a tick on the hotel header, a tab above the list */
    .hr2 .fav-star { flex: 0 0 auto; font-size: 17px; line-height: 1; color: #aab4c2; cursor: pointer; padding: 0 3px 2px; border-radius: 4px; }
    .hr2 .fav-star:hover, .hr2 .fav-star:focus { color: #e0a100; outline: none; background: #fff7e0; }
    .hr2 .fav-star.on { color: #e0a100; }
    .hr2 .fav-ico { color: #e0a100; }
    .hr2 .chip.chip-fav.on .fav-ico { color: #ffd257; }
    .hr2 .fav-check { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; font-weight: 500; color: var(--hr-muted); margin: 0 0 0 10px; cursor: pointer; user-select: none; vertical-align: middle; }
    .hr2 .fav-check input { margin: 0; cursor: pointer; }
    .hr2 .fav-check.on { color: #8a6200; }
    /* tf-offers-r1: the three areas of a hotel — Published rates · Tactical rates · Early Birds */
    .hr2 .areas { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .hr2 .area { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; border: 1px solid var(--hr-line); border-radius: 8px; padding: 3px 10px; background: #fff; cursor: pointer; color: var(--hr-ink, #1d2733); }
    .hr2 .area:hover { background: var(--hr-bg); }
    .hr2 .area b { font-weight: 600; }
    .hr2 .area.ok { border-color: #bfe3cc; background: #f3faf6; }
    .hr2 .area.none { border-color: #f1cfca; background: #fdf6f5; color: #9b3029; }
    .hr2 .area.warn { border-color: #f2dfb8; background: #ffffff; }
    .hr2 .offer-intro { font-size: 12px; color: var(--hr-muted); max-width: 760px; }
    /* hotel header */
    .hr2 .hotel-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; }
    .hr2 .hotel-head h5 { margin: 0 0 2px; font-weight: 600; color: var(--hr-navy); }
    .hr2 .hotel-head .meta { font-size: 12.5px; color: var(--hr-muted); }
    .hr2 .mono { font-family: Consolas, Menlo, monospace; font-size: 12px; }
    /* tabs */
    .hr2 .hr-tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--hr-line); margin: 14px 0 12px; flex-wrap: wrap; }
    .hr2 .hr-tab { padding: 8px 14px; font-size: 13px; font-weight: 500; color: var(--hr-muted); border-bottom: 2px solid transparent; cursor: pointer; background: none; border-top: 0; border-left: 0; border-right: 0; }
    .hr2 .hr-tab.on { color: var(--hr-navy); border-bottom-color: var(--hr-navy); }
    .hr2 .hr-tab .cnt { margin-left: 6px; font-size: 11px; background: #eef1f5; border-radius: 999px; padding: 1px 6px; color: var(--hr-muted); }
    /* tables */
    .hr2 table.hr-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .hr2 table.hr-table th { text-align: left; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--hr-muted); padding: 8px 10px; border-bottom: 1px solid var(--hr-line); background: #fafbfc; white-space: nowrap; }
    .hr2 table.hr-table td { padding: 7px 10px; border-bottom: 1px solid #eef1f5; vertical-align: middle; }
    .hr2 table.hr-table tr:hover td { background: #fbfcfd; }
    .hr2 table.hr-table td.num, .hr2 table.hr-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
    /* tf-hr-queue-r2 (10 Nov 2026, Shantha): the approval queue uses the width the screen has, can open full screen,
       and keeps the Edit / Approve buttons in view (sticky right column) while the table scrolls sideways */
    #m_queue .modal-dialog:not(.modal-fullscreen) { max-width: min(1500px, 96vw); }
    #m_queue .modal-header .aq-full { margin-left: auto; margin-right: 10px; white-space: nowrap; }
    #m_queue .aq-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 1px solid var(--hr-line); border-radius: 8px; }
    #m_queue .modal-fullscreen .aq-scroll { max-height: calc(100vh - 260px); overflow-y: auto; }
    #m_queue #aq_table { min-width: 900px; }
    #m_queue #aq_table thead th { position: sticky; top: 0; z-index: 2; }
    #m_queue #aq_table td.aq-hotel { min-width: 140px; }
    #m_queue #aq_table td.aq-plan { min-width: 210px; max-width: 420px; }
    #m_queue #aq_table td.aq-hotel, #m_queue #aq_table td.aq-plan { white-space: normal; }
    #m_queue #aq_table td.aq-dates { min-width: 118px; }
    #m_queue #aq_table td.aq-by { min-width: 110px; white-space: normal; }
    #m_queue #aq_table th.aq-act, #m_queue #aq_table td.aq-act { position: sticky; right: 0; background: #fff; box-shadow: -6px 0 8px -6px rgba(16,24,40,.18); z-index: 1; }
    #m_queue #aq_table th.aq-act { background: #fafbfc; z-index: 3; }
    #m_queue #aq_table tr:hover td.aq-act { background: #fbfcfd; }
    /* tf-hr-queue-r3 (10 Nov 2026, Shantha): the site theme turns every button light green; in the queue Edit stays navy and
       Approve teal with white text, as on the approved test page */
    #m_queue #aq_table td.aq-act .btn-primary { background: #1e3553 !important; border-color: #1e3553 !important; color: #fff !important; }
    #m_queue #aq_table td.aq-act .btn-primary:hover, #m_queue #aq_table td.aq-act .btn-primary:focus { background: #16283f !important; border-color: #16283f !important; color: #fff !important; }
    #m_queue #aq_table td.aq-act [data-qapprove] { background: #0f8b7e !important; border-color: #0f8b7e !important; color: #fff !important; }
    #m_queue #aq_table td.aq-act [data-qapprove]:hover, #m_queue #aq_table td.aq-act [data-qapprove]:focus { background: #0b6f65 !important; border-color: #0b6f65 !important; color: #fff !important; }
    .hr2 table.grid td, .hr2 table.grid th { padding: 3px 4px; }
    .hr2 table.grid input, .hr2 table.grid select { font-size: 12.5px; padding: 3px 6px; height: 28px; border-radius: 6px; border: 1px solid var(--hr-line); background: #fff; width: 100%; min-width: 64px; }
    .hr2 table.grid input[type=number] { text-align: right; }
    .hr2 table.grid input:focus, .hr2 table.grid select:focus, .hr2 .form-control:focus, .hr2 .form-select:focus { border-color: var(--hr-teal); box-shadow: 0 0 0 3px rgba(15,139,126,.12); outline: 0; }
    .hr2 table.grid input.wide { min-width: 160px; }
    .hr2 table.grid input.narrow { min-width: 54px; width: 64px; }
    .hr2 table.grid input.date { min-width: 128px; }
    .hr2 table.grid .del { color: var(--hr-red); background: none; border: 0; font-size: 16px; line-height: 1; padding: 0 4px; }
    .hr2 .form-label { font-size: 11.5px; font-weight: 600; color: var(--hr-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
    .hr2 .form-control, .hr2 .form-select { font-size: 13px; border-radius: 8px; border-color: var(--hr-line); }
    .hr2 .form-control-sm, .hr2 .form-select-sm { font-size: 12.5px; padding: 4px 8px; }
    .hr2 .help { font-size: 11.5px; color: var(--hr-muted); }
    /* editor */
    .hr2 .editor { display: grid; grid-template-columns: 210px minmax(0, 1fr); gap: 0; }
    @media (max-width: 900px) { .hr2 .editor { grid-template-columns: 1fr; } }
    .hr2 .ed-nav { border-right: 1px solid var(--hr-line); padding: 10px 0; background: #fafbfc; border-radius: var(--hr-radius) 0 0 var(--hr-radius); }
    .hr2 .ed-nav a { display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 12.5px; color: var(--hr-ink); text-decoration: none; border-left: 3px solid transparent; }
    .hr2 .ed-nav a:hover { background: #f1f4f8; }
    .hr2 .ed-nav a.on { background: #fff; border-left-color: var(--hr-teal); font-weight: 600; }
    .hr2 .ed-nav .n { margin-left: auto; font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: #eef1f5; color: var(--hr-muted); }
    .hr2 .ed-nav .n.err { background: var(--hr-red-soft); color: var(--hr-red); }
    .hr2 .ed-nav .n.warn { background: var(--hr-amber-soft); border:1px solid #d9d3dd; color: var(--hr-amber); }
    .hr2 .ed-body { padding: 16px 18px; min-height: 420px; }
    .hr2 .ed-section { display: none; }
    .hr2 .ed-section.on { display: block; }
    .hr2 .ed-section h6 { font-weight: 600; margin: 0 0 4px; color: var(--hr-navy); }
    .hr2 .ed-lead { font-size: 12.5px; color: var(--hr-muted); margin-bottom: 12px; }
    .hr2 .fieldset { border: 1px solid var(--hr-line); border-radius: 8px; padding: 12px 14px 6px; margin-bottom: 12px; }
    .hr2 .fieldset > .legend { font-size: 11.5px; font-weight: 700; color: var(--hr-navy); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
    .hr2 .season { border: 1px solid var(--hr-line); border-radius: 8px; margin-bottom: 12px; overflow: hidden; }
    .hr2 .season-h { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; padding: 10px 12px; background: #fafbfc; border-bottom: 1px solid var(--hr-line); }
    .hr2 .season-h .f { display: flex; flex-direction: column; }
    .hr2 .season-h label { font-size: 10.5px; color: var(--hr-muted); text-transform: uppercase; font-weight: 600; letter-spacing: .04em; }
    .hr2 .season-h input, .hr2 .season-h select { height: 28px; font-size: 12.5px; padding: 2px 6px; border: 1px solid var(--hr-line); border-radius: 6px; }
    .hr2 .date-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; padding: 8px 12px; border-bottom: 1px dashed var(--hr-line); font-size: 12px; }
    .hr2 .date-chip { display: inline-flex; gap: 4px; align-items: center; background: var(--hr-blue-soft); border-radius: 6px; padding: 2px 6px; }
    .hr2 .date-chip input { width: 118px; height: 24px; font-size: 12px; border: 0; background: transparent; }
    .hr2 .date-chip button { border: 0; background: none; color: var(--hr-red); padding: 0 2px; }
    .hr2 .sticky-actions { position: sticky; bottom: 0; background: #fff; padding: 10px 16px; border-top: 1px solid var(--hr-line); display: flex; gap: 8px; align-items: center; flex-wrap: wrap; border-radius: 0 0 var(--hr-radius) var(--hr-radius); z-index: 5; }
    .hr2 .issue { padding: 6px 10px; border-radius: 6px; font-size: 12.5px; margin-bottom: 4px; cursor: pointer; }
    .hr2 .issue.error { background: var(--hr-red-soft); color: #7f2a24; }
    .hr2 .issue.warning { background: var(--hr-amber-soft); border:1px solid #d9d3dd; color: #7a5011; }
    .hr2 .issue b { font-family: Consolas, Menlo, monospace; margin-right: 6px; }
    .hr2 .readonly input, .hr2 .readonly select, .hr2 .readonly textarea { background: #f8f9fb; }
    /* coverage */
    .hr2 .cov { display: grid; grid-template-columns: 180px repeat(12, 1fr); gap: 3px; align-items: center; font-size: 12px; }
    .hr2 .cov .lab { font-weight: 600; padding-right: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hr2 .cov .m { height: 22px; border-radius: 4px; background: #eef1f5; position: relative; overflow: hidden; }
    .hr2 .cov .m .seg { position: absolute; top: 0; bottom: 0; }
    .hr2 .cov .mh { font-size: 10.5px; color: var(--hr-muted); text-align: center; }
    .hr2 .legend { display: flex; flex-wrap: wrap; gap: 10px; font-size: 12px; margin-top: 10px; }
    .hr2 .legend i { display: inline-block; width: 14px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    /* quote */
    .hr2 .kpi { border: 1px solid var(--hr-line); border-radius: 8px; padding: 10px 14px; }
    .hr2 .kpi .v { font-size: 20px; font-weight: 700; color: var(--hr-navy); }
    .hr2 .kpi .l { font-size: 11px; color: var(--hr-muted); text-transform: uppercase; letter-spacing: .04em; }
    .hr2 .empty { padding: 28px; text-align: center; color: var(--hr-muted); font-size: 13px; }
    .hr2 .absorbed { font-size: 12px; color: var(--hr-muted); margin-top: 4px; }
    .hr2 .absorbed b { color: var(--hr-ink); }
    /* rate on a date */
    .hr2 .rate-on { border-color: #c9d9e6; }
    .hr2 .rate-on .hr-card-h { background: linear-gradient(90deg, #f1f6fb, #fff); }
    .hr2 .rate-on .hr-card-b { padding: 10px 16px 12px; }
    .hr2 .ro-table th.meal { text-align: center; }
    .hr2 .ro-table td.cell { white-space: nowrap; text-align: center; font-family: Consolas, Menlo, monospace; font-size: 12.5px; }
    .hr2 .ro-table td.cell .occ { display: inline-block; min-width: 30px; }
    .hr2 .ro-table td.cell .sep { color: #b8c2cc; padding: 0 2px; }
    .hr2 .ro-table td.cell.derived { color: var(--hr-navy-2); }
    .hr2 .ro-table td.cell .dv { display: block; font-family: inherit; font-size: 10.5px; color: var(--hr-muted); }
    .hr2 .ro-table td.cell .dv.adhoc { color: var(--hr-green); font-weight: 600; font-family: Consolas, Menlo, monospace; font-size: 11.5px; }
    .hr2 .ro-table td.cell.none { color: #c2ccd6; }
    .hr2 .ro-table td.rt { white-space: nowrap; }
    .hr2 .ro-lines { display: flex; flex-direction: column; gap: 4px; margin-top: 10px; font-size: 12.5px; }
    .hr2 .ro-lines b { font-weight: 600; color: var(--hr-muted); font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; margin-right: 4px; }
    .hr2 .ro-none { padding: 14px 16px; border-radius: 8px; background: var(--hr-red-soft); color: var(--hr-red); font-size: 13px; }
    .hr2 .ro-flags { border: 1px solid #f1d9a6; background: #fff9ec; border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; font-size: 12.5px; }
    .hr2 .ro-flags.ok { border-color: #cfe8d8; background: #f2faf5; color: var(--hr-green); }
    .hr2 .ro-flags-h { font-weight: 600; color: var(--hr-amber); margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
    .hr2 .ro-flag { display: flex; gap: 8px; align-items: baseline; padding: 2px 0; flex-wrap: wrap; }
    .hr2 .ro-flag .when { font-family: Consolas, Menlo, monospace; font-size: 12px; min-width: 150px; color: var(--hr-ink); }
    .hr2 .ro-seg { margin-top: 10px; }
    .hr2 .ro-seg-h { font-size: 12.5px; margin-bottom: 4px; }
    .hr2 .ro-none .help { color: #8a4a45; }
    .hr2 textarea.form-control { font-size: 12.5px; }
    .hr2 .modal-content { border-radius: 12px; }
    .hr2 .drop { border: 2px dashed var(--hr-line); border-radius: 10px; padding: 18px; text-align: center; color: var(--hr-muted); font-size: 12.5px; }
    .hr2 .wf { max-width: 100%; }
/* tf-hr-docs-r1: supporting documents */
.doc-drop{border:2px dashed #cdbfe2;border-radius:12px;padding:16px;text-align:center;background:#faf8fd;color:#4b3b66;cursor:pointer;transition:background .15s,border-color .15s}
.doc-drop:hover,.doc-drop:focus{border-color:#6b4c9a;outline:none}
.doc-drop.over{border-color:#78b848;background:#f3faee}
.doc-drop i{font-size:26px;color:#6b4c9a;display:block;margin-bottom:2px}
.doc-up{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:10px}
.doc-list .doc-ico{font-size:20px;width:30px;text-align:center;color:#6b4c9a}
.doc-list .doc-pdf{color:#c0392b}.doc-list .doc-xls{color:#1e7e45}.doc-list .doc-doc{color:#2b5797}
.doc-clip{display:inline-flex;align-items:center;gap:2px;font-size:11.5px;font-weight:600;color:#5b3f8a;background:#efe9f8;border-radius:10px;padding:1px 7px;margin-left:6px;border:0;cursor:pointer;vertical-align:middle}
.doc-clip.none{color:#9a6b00;background:#fff4dc;cursor:default}
.doc-group{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#5b3f8a;margin:16px 0 6px}
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content hr2" id="hr2">
            <div class="container-fluid">

                <?php $tourdesk_badges = ['master_files/hotel_work' => $work_open ?? null]; ?>
                <?= view('layouts/tourdesk_bar') ?>

                <div class="hr-title">
                    <div>
                        <h4>Hotel Rates</h4>
                        <div class="sub">Contracts, offers, seasons, supplements and policies — exactly as the hotels write them. Approved plans feed quotations and the old Hotel Rate screen automatically.</div>
                    </div>
                    <div class="hr-actions">
                        <div class="grp">
                            <button class="act" id="btn_queue" title="Rate sheets read in and waiting to be approved"><i class="bi bi-inbox"></i><span>Approval queue</span> <span class="pill pill-blue" id="queue_count" style="display:none"></span></button>
                            <button class="act" id="btn_dups" title="Hotels that may be on file twice"><i class="bi bi-intersect"></i><span>Duplicates</span></button>
                            <button class="act" id="btn_check" title="Tours with no rate, plans about to expire, offers whose booking window has closed, drafts, flagged rows"><i class="bi bi-shield-check"></i><span>Health check</span></button>
                            <a class="act" id="btn_readiness" style="text-decoration:none" href="<?= base_url(($panel_url ?? 'master_files/hotel_rate_v2') . '/readiness') ?>" title="Which hotels the quotation engine can price by itself, which it cannot and why, and the rate-expiry warnings"><i class="bi bi-clipboard2-check"></i><span>Quote readiness</span> <span class="pill pill-red" id="rate_alert_count" style="display:none"></span></a>
                        </div>
                        <div class="grp">
                            <a class="act" id="btn_offers" style="text-decoration:none" href="<?= base_url($offers_url ?? 'master_files/hotel_rate_v2/offers') ?>" title="Tactical rates and Early Bird offers across hotels — active, deadline close, expired — and the hotels with none"><i class="bi bi-tags"></i><span>Tacticals &amp; Early Birds</span></a>
                            <button class="act" id="btn_quote" title="Price a stay at any hotel on any dates"><i class="bi bi-calculator"></i><span>Try a quote</span></button>
                            <button class="act" id="btn_settings" title="<?= !empty($can_approve) ? 'Markets, tiers, gala nights, check thresholds, country → market' : 'View the settings (changing them needs the Hotel Rate Approval privilege)' ?>"><i class="bi bi-sliders"></i><span>Settings</span></button>
                        </div>
                        <div class="dropdown">
                            <button class="act-primary" id="btn_add_rates" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false"><i class="bi bi-plus-lg"></i><span>Add rates</span><i class="bi bi-chevron-down chev"></i></button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <button class="dropdown-item" id="btn_import" type="button"><span class="ic"><i class="bi bi-file-earmark-spreadsheet"></i></span><div><b>Import an Excel sheet</b><span class="d">The hotel's rate sheet, checked line by line before anything is saved.</span></div></button>
                                <?php if (!empty($ai_enabled)): ?><button class="dropdown-item" id="btn_ai" type="button"><span class="ic"><i class="bi bi-stars"></i></span><div><b>Read a PDF</b><span class="d">A PDF rate sheet read for you into a draft, for you to check and approve.</span></div></button><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="rate_alerts" class="hr-card" style="display:none;border-color:var(--hr-red);margin-bottom:14px"></div>

                <?php if (!empty($not_installed)): ?>
                <div class="hr-card" style="border-color:var(--hr-amber);margin-bottom:14px"><div class="hr-card-b">
                    <b><i class="bi bi-exclamation-triangle"></i> The Hotel Rates tables are not installed<?= isset($schema_version) && $schema_version ? " (schema {$schema_version} found; this panel needs schema " . \App\Libraries\HotelRates\Installer::SCHEMA_VERSION . ' — the install upgrades it in place)' : '' ?>.</b>
                    <?php if (!empty($is_sysadmin)): ?>
                    <div class="help mt-1">Step 1 of 2 — create the tables, the settings and the two menu entries. Nothing in the existing system is changed.</div>
                    <div class="mt-2"><button class="btn btn-primary btn-sm" id="setup_install"><i class="bi bi-download"></i> Install now</button> <span class="help ms-2">same as <span class="mono">php spark hotelrates:install --menu</span></span></div>
                    <?php else: ?>
                    <div class="help mt-1">Ask the System Administrator to open this page and press Install, or run in <span class="mono">C:\wamp64\www\avenir</span>:</div>
                    <pre class="mono mt-2 mb-0" style="font-size:12px">php spark hotelrates:install --menu
php spark hotelrates:migrate</pre>
                    <?php endif; ?>
                </div></div>
                <?php elseif (!empty($needs_migration)): ?>
                <div class="hr-card" style="border-color:var(--hr-amber);margin-bottom:14px"><div class="hr-card-b">
                    <b><i class="bi bi-exclamation-triangle"></i> The tables are installed but empty — the existing hotels and rates have not been copied across yet.</b>
                    <?php if (!empty($is_sysadmin)): ?>
                    <div class="help mt-1">Step 2 of 2 — every hotel in Master Files → Supplier becomes a hotel here, and every row of the old Hotel Rate screen becomes a "migrated" plan (flagged where the old data looks wrong). The old table is not touched. Run the dry run first if you want to see the numbers.</div>
                    <div class="mt-2"><button class="btn btn-light btn-sm" id="setup_dry"><i class="bi bi-eye"></i> Dry run</button> <button class="btn btn-primary btn-sm" id="setup_migrate"><i class="bi bi-arrow-right-circle"></i> Copy hotels &amp; rates across</button> <span class="help ms-2">same as <span class="mono">php spark hotelrates:migrate</span></span></div>
                    <?php else: ?>
                    <div class="help mt-1">Ask the System Administrator to open this page and run the migration, or run <span class="mono">php spark hotelrates:migrate</span>.</div>
                    <?php endif; ?>
                    <div id="setup_out" class="mt-2"></div>
                </div></div>
                <?php endif; ?>
                <div class="hr-shell">
                    <!-- ---------------- hotel rail ---------------- -->
                    <div class="hr-card">
                        <div class="rail-search">
                            <input type="text" class="form-control form-control-sm" id="hotel_search" placeholder="Search hotel, location, chain or code…">
                            <div class="chips">
                                <span class="chip chip-fav" data-flt="fav" id="chip_fav" style="display:none" title="Show only the hotels marked Frequently Used (the full list is unchanged — click again to see every hotel)"><span class="fav-ico">&#9733;</span> Frequently Used Hotels</span>
                                <span class="chip" data-flt="norate">No rate today</span>
                                <span class="chip" data-flt="draft">Has drafts</span>
                                <span class="chip" data-flt="used">Used last year</span>
                                <span class="chip" data-flt="expiring">Ends in <?= (int) ($settings['expiry_warning_days'] ?? 90) ?> days</span>
                            </div>
                        </div>
                        <div class="hotel-list" id="hotel_list"><div class="empty">Loading…</div></div>
                        <div class="rail-foot" id="hotel_count"></div>
                    </div>

                    <!-- ---------------- detail ---------------- -->
                    <div>
                        <div id="detail_empty" class="hr-card"><div class="empty"><i class="bi bi-building" style="font-size:28px"></i><br>Pick a hotel on the left.<br><span class="help">Hotels with <span class="pill pill-red">no rate</span> have nothing approved for today; <span class="pill pill-amber">ends …</span> shows when the last approved plan runs out.</span></div></div>
                        <div id="detail" style="display:none">
                            <div class="hr-card"><div class="hr-card-b hotel-head">
                                <div style="flex:1 1 300px">
                                    <h5><span id="h_name"></span><span class="hcode" id="h_code" style="display:none" title="Hotel code — permanent, given by the system; it stays if the hotel is renamed or archived"></span><label class="fav-check" id="h_fav_wrap" style="display:none" title="Tick for a hotel we use regularly: it then shows under the Frequently Used Hotels tab. The hotel stays in the full list; its rates are not touched."><input type="checkbox" id="h_fav"> <span class="fav-ico">&#9733;</span> Frequently Used</label></h5>
                                    <div class="meta"><span id="h_loc"></span> · <span class="mono" id="h_acc"></span> · <span id="h_status"></span></div>
                                    <div id="h_absorbed"></div>
                                    <div class="areas" id="h_areas" style="display:none"></div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap hotel-actions">
                                    <button class="btn btn-light btn-sm" id="btn_rate_on" title="Rate on a date — check what this hotel costs on any night"><i class="bi bi-calendar-check"></i> Rate on a date</button>
                                    <button class="btn btn-light btn-sm" id="btn_hotel_quote"><i class="bi bi-calculator"></i> Quote</button>
                                    <button class="btn btn-light btn-sm" id="btn_project" title="Rebuild the rows the old Hotel Rate screen shows for this hotel"><i class="bi bi-arrow-repeat"></i> Refresh old screen</button>
                                    <?php if (!empty($can_manage)): ?><button class="td-dots" id="btn_manage" type="button" title="Manage — archive or restore this hotel, with what depends on it">···</button><?php endif; ?>
                                    <div class="btn-group">
                                        <button class="btn btn-primary btn-sm" id="btn_new_contract"><i class="bi bi-plus-lg"></i> New plan</button>
                                        <button class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" data-new="contract">Contract (season rates)</a></li>
                                            <li><a class="dropdown-item" href="#" data-new="tactical">Tactical / special offer</a></li>
                                            <li><a class="dropdown-item" href="#" data-new="addendum">Addendum to an existing plan</a></li>
                                            <li><a class="dropdown-item" href="#" data-new="adhoc" title="A rate the hotel agreed for paying — the customer quotation keeps the contract rate">Ad hoc rate (payment only)</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" data-new="import">Import from Excel…</a></li>
                                            <?php if (!empty($ai_enabled)): ?><li><a class="dropdown-item" href="#" data-new="ai">Read from a PDF…</a></li><?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div></div>

                            <!-- rate on a date: what the hotel costs tonight (or any night), straight from the approved plans -->
                            <div class="hr-card rate-on" id="rates_on_card">
                                <div class="hr-card-h flex-wrap">
                                    <h6><i class="bi bi-calendar-check"></i> Rate on <span id="ro_date_lbl"></span> <span id="ro_state"></span></h6>
                                    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                                        <div class="btn-group">
                                            <button class="btn btn-light btn-sm" id="ro_prev" title="Previous day"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-light btn-sm" id="ro_today" title="Back to today">Today</button>
                                            <button class="btn btn-light btn-sm" id="ro_next" title="Next day"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                        <input type="date" class="form-control form-control-sm" id="ro_date" style="width:150px" title="First night">
                                        <span class="help">to</span>
                                        <input type="date" class="form-control form-control-sm" id="ro_to" style="width:150px" title="Last night — leave empty for one night">
                                        <label class="help mb-0">Nights</label><input type="number" class="form-control form-control-sm" id="ro_nights" value="1" min="1" max="62" style="width:64px" title="Number of nights (sets the last night)">
                                        <label class="help mb-0">Market</label><select class="form-select form-select-sm" id="ro_market" style="width:auto"></select>
                                        <label class="help mb-0">Rooms</label><input type="number" class="form-control form-control-sm" id="ro_rooms" value="1" min="1" style="width:64px" title="Number of rooms — decides FIT / group plans">
                                    </div>
                                </div>
                                <div class="hr-card-b" id="rates_on_box"><div class="empty">Looking up…</div></div>
                            </div>

                            <div class="hr-tabs" id="tabs">
                                <button class="hr-tab on" data-tab="plans"><span id="lbl_plans"><?= !empty($offers_ready) ? 'Published rates' : 'Rate plans' ?></span> <span class="cnt" id="cnt_plans"></span></button>
                                <?php if (!empty($offers_ready)): ?>
                                <button class="hr-tab" data-tab="tactical">Tactical rates <span class="cnt" id="cnt_tactical"></span></button>
                                <button class="hr-tab" data-tab="early_bird">Early Birds <span class="cnt" id="cnt_early_bird"></span></button>
                                <?php endif; ?>
                                <button class="hr-tab" data-tab="coverage">Coverage</button>
                                <button class="hr-tab" data-tab="rooms">Room types <span class="cnt" id="cnt_rooms"></span></button>
                                <button class="hr-tab" data-tab="docs" title="Rate sheets, contracts, factsheets and e-mails kept with this hotel and its plans"><i class="bi bi-paperclip"></i> Documents <span class="cnt" id="cnt_docs"></span></button>
                                <button class="hr-tab" data-tab="hotel">Hotel details</button>
                                <button class="hr-tab" data-tab="history">History</button>
                            </div>
                            <div id="tab_plans" class="tabpane">
                                <div class="hr-card"><div class="table-responsive"><table class="hr-table" id="contract_table">
                                    <thead><tr><th>Plan</th><th>Type</th><th>Markets · tier</th><th>Stay dates</th><th>Book by</th><th>Status</th><th class="num">Seasons</th><th class="num">Rows</th><th></th></tr></thead>
                                    <tbody></tbody></table></div></div>
                            </div>
                            <?php if (!empty($offers_ready)): ?>
                            <div id="tab_tactical" class="tabpane" style="display:none"><div class="hr-card">
                                <div class="hr-card-h flex-wrap"><h6>Tactical rates</h6><span class="offer-intro">Special rates this hotel gives us selectively (production, flash sales, special offers, packages). Kept apart from the published rates: never used for an inquiry, quotation or booking. At hotel payment, the stays they fit are flagged to an authorized person, who applies one or keeps the published rate. Active as soon as it is saved.</span>
                                    <div class="ms-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-primary btn-sm" data-offer-new="tactical"><i class="bi bi-plus-lg"></i> Add tactical</button>
                                        <?php if (!empty($ai_enabled)): ?><button class="btn btn-light btn-sm" data-offer-upload="tactical" data-ai="1"><i class="bi bi-stars"></i> Read a PDF…</button><?php endif; ?>
                                        <button class="btn btn-light btn-sm" data-offer-upload="tactical" data-ai="0"><i class="bi bi-file-earmark-spreadsheet"></i> Import Excel…</button>
                                    </div></div>
                                <div class="table-responsive"><table class="hr-table" id="offer_table_tactical">
                                    <thead><tr><th>Offer</th><th>Status</th><th>Stay dates</th><th>Book by</th><th>Rooms · meal plans</th><th>Markets · conditions</th><th class="num">From</th><th></th></tr></thead>
                                    <tbody></tbody></table></div></div>
                            </div>
                            <div id="tab_early_bird" class="tabpane" style="display:none"><div class="hr-card">
                                <div class="hr-card-h flex-wrap"><h6>Early Bird offers</h6><span class="offer-intro">Rates for booking ahead. Kept apart from the published rates and from tacticals: never used for an inquiry, quotation or booking. At hotel payment, a tour booked by the deadline is flagged to an authorized person, who applies it or keeps the published rate. Active as soon as it is saved.</span>
                                    <div class="ms-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-primary btn-sm" data-offer-new="early_bird"><i class="bi bi-plus-lg"></i> Add early bird</button>
                                        <?php if (!empty($ai_enabled)): ?><button class="btn btn-light btn-sm" data-offer-upload="early_bird" data-ai="1"><i class="bi bi-stars"></i> Read a PDF…</button><?php endif; ?>
                                        <button class="btn btn-light btn-sm" data-offer-upload="early_bird" data-ai="0"><i class="bi bi-file-earmark-spreadsheet"></i> Import Excel…</button>
                                    </div></div>
                                <div class="table-responsive"><table class="hr-table" id="offer_table_early_bird">
                                    <thead><tr><th>Offer</th><th>Status</th><th>Stay dates</th><th>Book by</th><th>Rooms · meal plans</th><th>Markets · conditions</th><th class="num">From</th><th></th></tr></thead>
                                    <tbody></tbody></table></div></div>
                            </div>
                            <?php endif; ?>
                            <div id="tab_coverage" class="tabpane" style="display:none"><div class="hr-card"><div class="hr-card-h"><h6>Which plan prices each night</h6>
                                <div class="ms-auto d-flex gap-2 align-items-center">
                                    <label class="help mb-0">Market</label><select class="form-select form-select-sm" id="cov_market" style="width:auto"></select>
                                    <label class="help mb-0">Rooms</label><input type="number" class="form-control form-control-sm" id="cov_rooms" value="1" min="1" style="width:70px">
                                    <button class="btn btn-light btn-sm" id="cov_refresh">Refresh</button>
                                </div></div><div class="hr-card-b" id="coverage_box"></div></div></div>
                            <div id="tab_rooms" class="tabpane" style="display:none"><div class="hr-card"><div class="hr-card-b" id="rooms_box"></div></div></div>
                            <div id="tab_docs" class="tabpane" style="display:none"><div class="hr-card"><div class="hr-card-b" id="docs_box"></div></div></div>
                            <div id="tab_hotel" class="tabpane" style="display:none"><div class="hr-card"><div class="hr-card-b" id="hotel_box"></div></div></div>
                            <div id="tab_history" class="tabpane" style="display:none"><div class="hr-card"><div class="hr-card-b" id="history_box"></div></div></div>

                            <div id="editor" class="hr-card" style="display:none"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- ---------------- modals ---------------- -->
<div class="modal fade hr2" id="m_import" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="m_import_title">Import rate sheet</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="imp_step1">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="fieldset"><div class="legend">Excel file</div>
                        <input type="file" class="form-control" id="imp_file" accept=".xlsx,.xls,.csv">
                        <div class="help mt-2">Three layouts are understood: the workbook from <a href="#" id="imp_template">Download template</a> (one sheet per topic: Plan, Rates, Supplements, Children…), a long list (Room type · Meal plan · From · To · SGL · DBL · TPL · QUAD…), or the old wide upload (SRO … TFB). Blank cells mean "not offered" — never type 0.</div>
                    </div>
                    <div class="fieldset" id="imp_ai_box" style="display:none"><div class="legend">Contract PDF / image</div>
                        <div class="d-flex gap-2"><input type="file" class="form-control" id="ai_file" accept=".pdf,.jpg,.jpeg,.png"><button class="btn btn-primary" id="ai_extract_btn">Read</button></div>
                        <div class="help mt-2">The reader transcribes the whole document — seasons, base + supplement rooms, meal deltas, gala dinners, child rules, guide and FOC policy, cancellation and payment terms — into a draft. Every number still goes through the preview and the checks, and you can edit the draft before approving.</div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="fieldset"><div class="legend">Defaults for this file</div>
                        <div class="mb-2"><label class="form-label">Hotel</label><select class="form-select form-select-sm" id="imp_hotel"><option value="">— as named in the file —</option></select></div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">Plan type</label><select class="form-select form-select-sm" id="imp_plan_type"><option value="contract">Contract</option><option value="tactical">Tactical offer</option><option value="special_offer">Special offer</option><option value="early_bird">Early bird</option><option value="addendum">Addendum</option></select></div>
                            <div class="col-6"><label class="form-label">Markets</label><input class="form-control form-control-sm" id="imp_market" placeholder="ALL or JP,CN"></div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-6"><label class="form-label">Pricing</label><select class="form-select form-select-sm" id="imp_pricing"><option value="per_room">Per room</option><option value="per_person">Per person</option><option value="per_unit">Per unit / villa</option></select></div>
                            <div class="col-6"><label class="form-label">Currency</label><input class="form-control form-control-sm" id="imp_currency" value="<?= esc($settings['default_currency'] ?? 'USD') ?>" maxlength="3"></div>
                        </div>
                        <button class="btn btn-primary w-100 mt-3" id="imp_preview_btn">Preview</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="imp_step2" style="display:none"></div>
    </div>
    <div class="modal-footer">
        <label class="me-auto help" id="imp_ack_box" style="display:none"><input type="checkbox" id="imp_ack"> I have read the warnings</label>
        <label class="help me-3" id="imp_sup_box" style="display:none"><input type="checkbox" id="imp_supersede"> retire approved plans of the same type that overlap</label>
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-light" id="imp_draft_btn" style="display:none">Save as drafts</button>
        <button class="btn btn-success" id="imp_commit_btn" style="display:none">Save &amp; approve</button>
        <button class="btn btn-success" id="imp_offer_btn" style="display:none">Save — active now</button>
        <span class="help" id="imp_no_approve" style="display:none">Plans are saved as drafts; a user with the Hotel Rate Approval privilege approves them from the queue.</span>
    </div>
</div></div></div>

<div class="modal fade hr2" id="m_quote" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Try a quote</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Hotel</label><select class="form-select form-select-sm" id="q_hotel"></select></div>
            <div class="col-md-2"><label class="form-label">Room type</label><select class="form-select form-select-sm" id="q_room"></select></div>
            <div class="col-md-1"><label class="form-label">Meal</label><select class="form-select form-select-sm" id="q_meal"><option>BB</option><option selected>HB</option><option>FB</option><option>AI</option><option>RO</option></select></div>
            <div class="col-md-2"><label class="form-label">Check-in</label><input type="date" class="form-control form-control-sm" id="q_in"></div>
            <div class="col-md-1"><label class="form-label">Nights</label><input type="number" class="form-control form-control-sm" id="q_nights" value="2" min="1"></div>
            <div class="col-md-1"><label class="form-label">Adults</label><input type="number" class="form-control form-control-sm" id="q_adults" value="2" min="1" max="4"></div>
            <div class="col-md-1"><label class="form-label">Rooms</label><input type="number" class="form-control form-control-sm" id="q_rooms" value="1" min="1"></div>
            <div class="col-md-1"><label class="form-label">Market</label><select class="form-select form-select-sm" id="q_market"></select></div>
        </div>
        <div class="d-flex gap-2 mt-2 align-items-end">
            <div><label class="form-label">Booking date</label><input type="date" class="form-control form-control-sm" id="q_booking"></div>
            <button class="btn btn-primary btn-sm" id="q_run">Price it</button>
            <span class="help">Uses exactly the rules a quotation will use: markets, booking windows, tiers, base + supplement rooms, meal deltas, compulsory supplements.</span>
        </div>
        <div id="q_out" class="mt-3"></div>
    </div>
</div></div></div>

<div class="modal fade hr2" id="m_dups" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Possible duplicate hotels</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="dups_body"></div>
</div></div></div>

<div class="modal fade hr2" id="m_check" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Rate health check</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="check_body"></div>
</div></div></div>

<div class="modal fade hr2" id="m_settings" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Hotel Rates settings</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-3">Every rule the checks and the pricing engine use lives here (table <span class="mono">hr2_meta</span>); nothing is fixed in the code. <?= !empty($can_approve) ? 'Changes apply immediately to new checks and lookups.' : '<b>Read-only:</b> changing them needs the Hotel Rate Approval privilege.' ?></div>
        <div id="settings_body"><div class="empty">Loading…</div></div>
    </div>
    <div class="modal-footer"><span class="help me-auto" id="settings_msg"></span><?php if (!empty($is_sysadmin)): ?><button class="btn btn-light" id="settings_reinstall" title="Re-runs the installer: creates missing tables, upgrades the layout, matches the database collation, re-adds the menu entries. Safe to repeat; plans are untouched."><i class="bi bi-arrow-repeat"></i> Re-run install</button> <button class="btn btn-light" id="settings_cost_backfill" title="Prices every confirmed tour with hotel nights from today on from the Hotel Rates panel (ad hoc → contract → quoted flagged) for the Tour Payment Dashboard. Never touches quotations. Safe to repeat."><i class="bi bi-cash-coin"></i> Cost confirmed tours</button><?php endif; ?><button class="btn btn-light" data-bs-dismiss="modal">Close</button><?php if (!empty($can_approve)): ?><button class="btn btn-primary" id="settings_save"><i class="bi bi-save"></i> Save settings</button><?php endif; ?></div>
</div></div></div>

<div class="modal fade hr2" id="m_queue" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Approval queue</h5><button type="button" class="btn btn-sm btn-outline-primary aq-full" id="aq_full" title="Use the whole browser window for the queue"><i class="bi bi-arrows-fullscreen"></i> <span>Full screen</span></button><button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="queue_body"><div class="empty">Loading…</div></div>
</div></div></div>

<!-- tf-hr-queue-r1: correct a draft from the queue (hotel, plan name, dates) before approving it -->
<div class="modal fade hr2" id="m_qedit" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Correct this draft</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2" id="qe_head"></div>
        <label class="form-label">Hotel <span class="help">— type to search the Hotel Rates master; the draft moves to the hotel you pick</span></label>
        <input class="form-control form-control-sm" id="qe_hotel_q" placeholder="Search hotel…" autocomplete="off">
        <select class="form-select form-select-sm mt-1" id="qe_hotel" size="6"></select>
        <div class="help mt-1" id="qe_hotel_note"></div>
        <label class="form-label mt-2">Plan name</label><input class="form-control form-control-sm" id="qe_name">
        <div class="row g-2 mt-0"><div class="col-6"><label class="form-label">Valid from</label><input type="date" class="form-control form-control-sm" id="qe_from"></div><div class="col-6"><label class="form-label">Valid to</label><input type="date" class="form-control form-control-sm" id="qe_to"></div></div>
        <label class="form-label mt-2">Note <span class="help">— why it was corrected (kept on the plan)</span></label><input class="form-control form-control-sm" id="qe_note" placeholder="e.g. wrong hotel picked by the reader">
        <div class="text-danger small mt-2" id="qe_err"></div>
    </div>
    <div class="modal-footer"><span class="help me-auto">Still a draft after saving — approve it from the queue when it is right.</span><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="qe_save"><i class="bi bi-check2"></i> Save correction</button></div>
</div></div></div>

<div class="modal fade hr2" id="m_paste" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Paste rates from Excel</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2">Copy the cells from the hotel's sheet and paste them here. Columns: <b>Room type · Meal plan · SGL · DBL · TPL · QUAD · UNIT · Child with bed · Child no bed</b> (tab separated, one row per line). Missing columns are left blank; "-" means not offered.</div>
        <textarea class="form-control" id="paste_text" rows="10" placeholder="Deluxe	BB	110	120	165&#10;Deluxe	HB	134	168	237"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="paste_apply">Add rows</button></div>
</div></div></div>

<?= view('layouts/tourdesk_menu') ?>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($panel_url ?? 'master_files/hotel_rate_v2') ?>';
    const APPROVE_BASE = '<?= base_url($approval_url ?? 'master_files/hotel_rate_approval') ?>';
    const CAN_APPROVE = <?= !empty($can_approve) ? 'true' : 'false' ?>;
    // tf-offers-r1: Published rates · Tactical rates · Early Bird offers kept apart
    const OFFERS_READY = <?= !empty($offers_ready) ? 'true' : 'false' ?>;
    const PRESELECT_TAB = <?= json_encode((string) ($preselect_tab ?? '')) ?>;
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const MANAGE_BASE = '<?= base_url($manage_url ?? 'tour/manage') ?>';
    const MASTER_BASE = '<?= base_url('master_files/hotel_master') ?>';
    const APPROVAL_MODE = <?= !empty($approval_mode) ? 'true' : 'false' ?>;
    const PRESELECT_ACC = <?= json_encode((string) ($preselect_acc ?? '')) ?>;
    const PRESELECT_CONTRACT = <?= (int) ($preselect_contract ?? 0) ?>;
    const FROM_QUEUE = <?= !empty($preselect_from) ? 'true' : 'false' ?>;
    const CAN_RENAME = <?= !empty($can_rename) ? 'true' : 'false' ?>;
    const PRESELECT_DATE = <?= json_encode((string) ($preselect_date ?? '')) ?>;
    const PRESELECT_TO = <?= json_encode((string) ($preselect_to ?? '')) ?>;
    const TODAY = '<?= $today ?>';
    const MARKETS = <?= json_encode($markets ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const SETTINGS = <?= json_encode($settings ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const DEFAULT_MARKET = '<?= $default_market ?? 'JP' ?>';
    const EXPIRY_DAYS = Number(SETTINGS.expiry_warning_days || 90);
    const GALA_DATES = String(SETTINGS.gala_dates || '12-24,12-31').split(/[,;\s]+/).filter(Boolean);
    const GALA_CHILD_PCT = SETTINGS.gala_child_pct === undefined ? 50 : Number(SETTINGS.gala_child_pct);
    const DEFAULT_CURRENCY = String(SETTINGS.default_currency || 'USD');
    const MEALS = ['RO', 'BB', 'HB', 'FB', 'AI'];
    const OPT = {
        plan_type: { contract: 'Contract', special_offer: 'Special offer', tactical: 'Tactical offer', early_bird: 'Early bird', addendum: 'Addendum', series: 'Series', migrated: 'Migrated' },
        applies_to: { both: 'Quotations & hotel payments', quotation: 'Quotations only', payment: 'Payments only — ad hoc rate' },
        tier: { all: 'All bookings', fit: 'FIT', group: 'Group', wedding: 'Wedding', long_stay: 'Long stay' },
        pricing_mode: { per_room: 'Per room per night', per_person: 'Per person per night', per_unit: 'Per unit (villa / tent)' },
        billing_currency: { same: 'Same currency', LKR: 'LKR at the day rate' },
        credit_facility: { unknown: 'Unknown', yes: 'Yes', no: 'No' },
        season_kind: { normal: 'Normal', peak: 'Peak', festive: 'Festive', low: 'Low', other: 'Other' },
        sup_type: { gala: 'Gala dinner', peak: 'Peak surcharge', festival: 'Festival (Perahera…)', extra_adult: 'Extra adult / 4th pax', early_checkin: 'Early check-in', late_checkout: 'Late check-out', other: 'Other' },
        sup_per: { person: 'per person', adult: 'per adult', child: 'per child', room: 'per room', night: 'per night', booking: 'per booking' },
        bed: { any: 'any', with_bed: 'with extra bed', no_bed: 'no extra bed' },
        pct_basis: { '': '—', adult_rate: 'of adult rate', meal_rate: 'of meal rate', meal_supplement: 'of meal supplement', room_rate: 'of room rate' },
        role: { guide: 'Guide', driver: 'Driver', tour_leader: 'Tour leader', any: 'Any' },
        accommodation: { unknown: '—', hotel_room: 'Hotel room', guide_quarters: 'Guide quarters', nearby: 'Nearby' },
        pct_of: { '': '—', sgl: '% of single rate', dbl: '% of double rate', room: '% of room rate' },
        foc_basis: { pax: 'paying pax', rooms: 'paid rooms' },
        foc_as: { any: 'any', half_double: 'half a double', single: 'single', guide_room: 'guide room' },
        offer_kind: { early_booking: 'Early booking', long_stay: 'Long stay', honeymoon: 'Honeymoon', group_reduction: 'Group reduction', free_night: 'Free night', other: 'Other' },
        offer_per: { room: 'per room', person: 'per person', booking: 'per booking', night: 'per night' },
        cancel_tier: { all: 'All', fit: 'FIT', group: 'Group' },
        pay_applies: { all: 'All', credit: 'Credit agents', non_credit: 'Non-credit agents', fit: 'FIT', group: 'Groups', peak: 'Peak dates' },
        pay_kind: { prepay: 'Full payment', advance: 'Advance %', interest: 'Late interest', surcharge: 'Surcharge', credit_period: 'Credit period', other: 'Other' },
        restriction: { min_stay: 'Minimum stay', closed_to_arrival: 'No check-in', closed_to_departure: 'No check-out', meal_plan_required: 'Meal plan required', stop_sale: 'Stop sale', other: 'Other' },
        extra_cat: { excursion: 'Excursion', transfer: 'Transfer', mice: 'MICE', wellness: 'Wellness', other: 'Other' },
        extra_per: { person: 'per person', room: 'per room', booking: 'per booking', group: 'per group', hour: 'per hour' },
        meal: { breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner', other: 'Other' },
        rs_per: { room: 'per room', person: 'per person' },
    };
    const SECTIONS = [
        ['overview', 'Overview', 'bi-card-heading'], ['seasons', 'Seasons & rates', 'bi-calendar3'], ['rooms', 'Room supplements & meal deltas', 'bi-door-open'],
        ['supplements', 'Supplements & meals', 'bi-plus-square'], ['children', 'Children & extra beds', 'bi-people'], ['guide', 'Guide, driver & FOC', 'bi-person-badge'],
        ['offers', 'Offers & restrictions', 'bi-tag'], ['cancel', 'Cancellation & payment', 'bi-receipt'], ['extras', 'Extras', 'bi-bicycle'], ['documents', 'Documents', 'bi-paperclip'], ['issues', 'Checks', 'bi-shield-check'],
    ];
    const S = { hotels: [], hotel: null, detail: null, sheet: null, ro: false, section: 'overview', filters: {}, importId: null, importSheets: [], covMarket: DEFAULT_MARKET, rateDate: (PRESELECT_DATE || TODAY), rateTo: (PRESELECT_TO || null), roMarket: DEFAULT_MARKET };

    // ---------------- helpers ----------------
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = v => (v === null || v === undefined || v === '') ? '' : Number(v).toFixed(2).replace(/\.00$/, '');
    const money = v => (v === null || v === undefined || v === '') ? '—' : Number(v).toLocaleString('en', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    const val = v => (v === null || v === undefined) ? '' : v;
    // hotel-dup-r1: focus:false so a SweetAlert box opened above a modal (Approve anyway, typed confirmations) keeps the keyboard
    const showModal = sel => { const el = document.querySelector(sel); if (window.bootstrap && bootstrap.Modal) { (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el, { focus: false }) : new bootstrap.Modal(el, { focus: false })).show(); } else { $(el).modal('show'); } };
    const hideModal = sel => { const el = document.querySelector(sel); const inst = window.bootstrap && bootstrap.Modal && bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null; if (inst) { inst.hide(); } else { $(el).modal('hide'); } };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 1800 : undefined, showConfirmButton: icon !== 'success' });
    const confirmBox = (title, text, btn) => Swal.fire({ icon: 'question', title, text, showCancelButton: true, confirmButtonText: btn || 'Yes' }).then(r => r.isConfirmed);
    async function api(path, opts, base) { const r = await fetch((base || BASE) + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); if (r.status === 403) { let j = null; try { j = await r.json(); } catch (e) {} return { status: 403, message: (j && (j.message || j.msg)) || 'Your user group is not allowed to do this (Hotel Rate Approval privilege needed).' }; } return r.json(); }
    const post = (path, body, base) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) }, base);
    // approvals, supersede, merge, settings go through the second route group so the group-privilege screen governs them
    const postA = (path, body) => post(path, body, APPROVE_BASE);
    const sel = (opts, current, attrs, allowBlank) => `<select ${attrs}>${allowBlank ? '<option value="">—</option>' : ''}${Object.entries(opts).map(([k, v]) => `<option value="${esc(k)}" ${String(current ?? '') === String(k) ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select>`;
    const mealSel = (current, attrs, allowBlank) => `<select ${attrs}>${allowBlank ? '<option value="">any</option>' : ''}${MEALS.map(m => `<option ${current === m ? 'selected' : ''}>${m}</option>`).join('')}</select>`;
    const pill = (cls, t) => `<span class="pill pill-${cls}">${esc(t)}</span>`;
    const statusPill = s => pill({ approved: 'green', draft: 'blue', superseded: 'grey', cancelled: 'grey' }[s] || 'grey', s);
    const typePill = t => pill({ contract: 'teal', migrated: 'grey', addendum: 'blue' }[t] || 'amber', OPT.plan_type[t] || t);
    // tf-offers-r1: which area a plan belongs to — null = Published rates, else 'tactical' | 'early_bird'
    // as OfferRates::kindOf: a payment-only special offer is the older ad hoc payment rate, not a tactical
    const offerKind = c => !c ? null : (c.plan_type === 'early_bird' ? 'early_bird' : ((c.plan_type === 'tactical' || (c.plan_type === 'special_offer' && c.applies_to !== 'payment')) ? 'tactical' : null));
    const OFFER_STATUS = { active: ['green', 'Active'], deadline_soon: ['amber', 'Booking deadline soon'], upcoming: ['blue', 'Booking opens later'], booking_closed: ['grey', 'Booking closed'], expired: ['grey', 'Expired'], withdrawn: ['grey', 'Withdrawn'], draft: ['blue', 'Draft'] };
    const OFFER_ACTIVE = ['active', 'deadline_soon', 'upcoming'];
    const KIND_LABEL = { tactical: 'Tactical rate', early_bird: 'Early Bird offer' };
    const marketsText = c => { const inc = (c.markets || []).filter(m => m.mode === 'include').map(m => m.code); const exc = (c.markets || []).filter(m => m.mode === 'exclude').map(m => '−' + m.code); return (inc.length ? inc.join(', ') : 'All markets') + (exc.length ? ' ' + exc.join(' ') : ''); };

    // ---------------- hotel rail ----------------
    async function loadHotels() {
        const j = await api('/hotels');
        if (j.status !== 200) { return toast('error', j.message); }
        S.hotels = j.data;
        S.favReady = !!j.fav_ready;
        document.getElementById('chip_fav').style.display = S.favReady ? '' : 'none';
        renderHotels();
        const opts = S.hotels.map(h => `<option value="${h.id}">${esc(h.name)}${h.location_name ? ' · ' + esc(h.location_name) : ''}</option>`).join('');
        document.getElementById('imp_hotel').innerHTML = '<option value="">— as named in the file —</option>' + opts;
        document.getElementById('q_hotel').innerHTML = opts;
    }
    function renderHotels() {
        const q = document.getElementById('hotel_search').value.trim().toLowerCase();
        const f = S.filters;
        const in90 = new Date(TODAY); in90.setDate(in90.getDate() + EXPIRY_DAYS); const in90s = in90.toISOString().slice(0, 10);
        const codeNo = (q.match(/^(?:htl[\s-]*|h)?0*(\d{1,6})$/i) || [])[1];   // "HTL-017", "htl17", "H017" or "17" is the hotel code
        const list = S.hotels.filter(h => {
            if (q && !(`${h.name} ${h.location_name || ''} ${h.chain || ''} ${h.primary_sup_acc_no} ${h.hotel_code || ''}`.toLowerCase().includes(q))
                  && !(codeNo && h.hotel_code && parseInt(String(h.hotel_code).replace(/\D/g, ''), 10) === parseInt(codeNo, 10))) return false;
            if (f.fav && !Number(h.frequently_used)) return false;
            if (f.norate && Number(h.seasons_today) > 0) return false;
            if (f.draft && Number(h.draft_plans) === 0) return false;
            if (f.used && Number(h.nights_last_year) === 0) return false;
            if (f.expiring && !(h.latest_valid_to && h.latest_valid_to >= TODAY && h.latest_valid_to <= in90s)) return false;
            return true;
        });
        const box = document.getElementById('hotel_list');
        box.innerHTML = list.map(h => {
            const ok = Number(h.seasons_today) > 0;
            let badge;
            if (ok) badge = pill('green', 'rate today');
            else if (Number(h.rates_pending)) badge = `<span class="pill pill-red" title="No valid published contract rate yet: the hotel is on the master for identification and preparation; it gives no quotation price until its rates are entered and approved.">Incomplete — Rates Pending</span>`;
            else if (!h.latest_valid_to) badge = pill('red', 'no rate');
            else if (h.latest_valid_to < TODAY) badge = pill('amber', 'expired ' + h.latest_valid_to);
            else if (h.next_valid_from) badge = pill('amber', 'from ' + h.next_valid_from);
            else badge = pill('amber', 'gap today');
            if (Number(h.draft_plans) > 0) badge += ' ' + pill('blue', 'draft');
            if (h.status === 'inactive') badge += ' ' + pill('grey', 'inactive');
            const fav = Number(h.frequently_used) > 0;
            const star = S.favReady ? `<span class="fav-star ${fav ? 'on' : ''}" role="button" tabindex="0" data-fav="${h.id}" aria-pressed="${fav}" aria-label="Frequently Used" title="${fav ? 'Frequently Used — click to untick' : 'Frequently Used — click to tick'}">${fav ? '&#9733;' : '&#9734;'}</span>` : '';
            return `<a class="hotel-item ${S.hotel && S.hotel.id == h.id ? 'active' : ''}" data-id="${h.id}">
                <div class="d-flex justify-content-between align-items-start gap-2">${star}<div style="min-width:0;flex:1 1 auto"><div class="nm">${esc(h.name)}</div><div class="meta">${h.hotel_code ? `<span class="hcode">${esc(h.hotel_code)}</span> ` : ''}${esc([h.location_name, h.chain].filter(Boolean).join(' · '))}${Number(h.nights_last_year) ? ` · ${h.nights_last_year} nights/yr` : ''}</div></div><div class="text-end">${badge}</div></div></a>`;
        }).join('') || `<div class="empty">${f.fav && !S.hotels.some(h => Number(h.frequently_used)) ? 'No hotel is marked Frequently Used yet — tick the star beside a hotel (or "Frequently Used" on its page).' : 'No hotels match.'}</div>`;
        const favN = S.hotels.filter(h => Number(h.frequently_used) > 0).length;
        document.getElementById('hotel_count').textContent = `${list.length} of ${S.hotels.length} hotels · ${S.hotels.filter(h => Number(h.seasons_today) > 0).length} priced for today${S.favReady ? ` · ${favN} frequently used` : ''}`;
        box.querySelectorAll('.hotel-item').forEach(el => el.addEventListener('click', () => selectHotel(Number(el.dataset.id))));
        box.querySelectorAll('.fav-star').forEach(el => {
            const go = e => { e.preventDefault(); e.stopPropagation(); const h = S.hotels.find(x => x.id == el.dataset.fav); if (h) setFrequent(h.id, !Number(h.frequently_used)); };
            el.addEventListener('click', go);
            el.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') go(e); });
        });
    }
    // Frequently Used: one flag on the hotel record, saved the moment it is ticked; the hotel stays in the full list
    async function setFrequent(id, on) {
        const j = await post('/hotel/' + id + '/frequent', { on: !!on });
        if (j.status !== 200) { toast('error', j.message); return renderFavHead(); }
        const v = Number(j.data.frequently_used) ? 1 : 0;
        const h = S.hotels.find(x => x.id == id); if (h) h.frequently_used = v;
        if (S.hotel && S.hotel.id == id) { S.hotel.frequently_used = v; renderFavHead(); loadHistory(); }
        renderHotels();
        // a small corner note rather than the usual pop-up, so a list of hotels can be starred one after another
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: j.message, timer: 1800, showConfirmButton: false });
    }
    function renderFavHead() {
        const wrap = document.getElementById('h_fav_wrap');
        if (!S.favReady || !S.hotel) { wrap.style.display = 'none'; return; }
        const on = Number(S.hotel.frequently_used) > 0;
        wrap.style.display = '';
        wrap.classList.toggle('on', on);
        document.getElementById('h_fav').checked = on;
    }
    document.getElementById('h_fav').addEventListener('change', e => { if (S.hotel) setFrequent(S.hotel.id, e.target.checked); });
    document.getElementById('hotel_search').addEventListener('input', renderHotels);
    document.querySelectorAll('.chip[data-flt]').forEach(c => c.addEventListener('click', () => { S.filters[c.dataset.flt] = !S.filters[c.dataset.flt]; c.classList.toggle('on', !!S.filters[c.dataset.flt]); renderHotels(); }));

    // ---------------- hotel detail ----------------
    async function selectHotel(id, keepEditor) {
        const j = await api('/hotel/' + id + '?market=' + encodeURIComponent(S.covMarket));
        if (j.status !== 200) { return toast('error', j.message); }
        S.hotel = j.data.hotel; S.detail = j.data;
        if (!keepEditor) { closeEditor(); }
        // tf-refresh-r1: the hotel is in the address (?hotel=<supplier account>) — a refresh opens it again, read fresh
        if (window.TfView) { const acc = S.hotel.primary_sup_acc_no || ((S.hotel.accounts || [])[0] || {}).sup_acc_no || ''; if (acc) TfView.setParams({ hotel: acc }); }
        document.getElementById('detail_empty').style.display = 'none';
        document.getElementById('detail').style.display = '';
        document.getElementById('h_name').textContent = S.hotel.name;
        const hc = document.getElementById('h_code'); hc.textContent = S.hotel.hotel_code || ''; hc.style.display = S.hotel.hotel_code ? '' : 'none';
        renderFavHead();
        document.getElementById('h_loc').textContent = [S.hotel.location_name || 'no location', S.hotel.chain].filter(Boolean).join(' · ');
        document.getElementById('h_acc').textContent = S.hotel.accounts.map(a => a.sup_acc_no).join(', ');
        const ratesPending = !(S.detail.contracts || []).some(c => c.status === 'approved' && c.applies_to !== 'payment');
        document.getElementById('h_status').innerHTML = `${S.hotel.status === 'inactive' ? pill('amber', 'inactive — archived') : esc(S.hotel.status)}${ratesPending ? ' · <span class="pill pill-red" title="No valid published contract rate yet: visible for identification and preparation; no quotation price until its rates are entered and approved.">Incomplete — Rates Pending</span>' : ''} · ${esc(S.hotel.currency)} · taxes ${esc(S.hotel.tax_treatment)}`;
        renderAbsorbed();
        document.getElementById('cnt_plans').textContent = OFFERS_READY ? S.detail.contracts.filter(c => !offerKind(c)).length : S.detail.contracts.length;
        document.getElementById('cnt_rooms').textContent = S.detail.room_types.length;
        setDocCount();   // tf-hr-docs-r1
        renderContracts(); renderCoverage(S.detail.coverage); renderRooms(); renderHotelForm(); renderHotels();
        loadOffers();
        loadHistory();
        loadRatesOn();
    }

    // ---------------- tf-hr-docs-r1: supporting documents (rate sheet PDF, contract, factsheet, e-mail) ----------------
    // A document belongs to one rate plan, or to the whole hotel (then it shows under every plan of the hotel).
    // Files are kept on the server outside the public folder and opened through this panel; "Remove" hides a file
    // but keeps it, with the reason, in the audit trail (uploader or a Hotel Rate approver; approved plans: approvers only).
    const DOC_ICON = { pdf: 'bi-file-earmark-pdf doc-pdf', jpg: 'bi-file-earmark-image', jpeg: 'bi-file-earmark-image', png: 'bi-file-earmark-image', webp: 'bi-file-earmark-image', xlsx: 'bi-file-earmark-excel doc-xls', xls: 'bi-file-earmark-excel doc-xls', docx: 'bi-file-earmark-word doc-doc', doc: 'bi-file-earmark-word doc-doc', eml: 'bi-envelope', msg: 'bi-envelope', txt: 'bi-file-earmark-text', csv: 'bi-file-earmark-spreadsheet' };
    const DOC_ACCEPT = '.pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.docx,.doc,.eml,.msg,.txt,.csv';
    const docSize = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    const docUrl = (id, dl) => BASE + '/document/' + id + (dl ? '?download=1' : '');
    function docCountsTotal() { const dc = (S.detail && S.detail.doc_counts) || {}; return Object.values(dc).reduce((a, n) => a + Number(n), 0) + Number((S.detail && S.detail.doc_hotel) || 0); }
    function setDocCount() { const el = document.getElementById('cnt_docs'); if (el) el.textContent = docCountsTotal() || ''; }
    const mayRemoveDoc = (doc, info) => info.can_approve || (doc.contract_status !== 'approved' && String(doc.uploaded_by || '').toLowerCase() === String(info.user || '').toLowerCase());
    function docTable(list, info, showPlan) {
        if (!list.length) return '<div class="empty">No documents attached yet.</div>';
        return `<div class="table-responsive"><table class="hr-table doc-list"><thead><tr><th></th><th>Document</th><th>Type</th>${showPlan ? '<th>Belongs to</th>' : ''}<th class="num">Size</th><th>Attached</th><th></th></tr></thead><tbody>
            ${list.map(doc => `<tr>
                <td class="doc-ico"><i class="bi ${DOC_ICON[doc.ext] || 'bi-file-earmark'}"></i></td>
                <td><a href="${docUrl(doc.id)}" target="_blank" rel="noopener" class="fw-semibold">${esc(doc.original_name)}</a>${doc.note ? `<div class="help">${esc(doc.note)}</div>` : ''}</td>
                <td>${pill('grey', doc.kind_label)}</td>
                ${showPlan ? `<td>${doc.contract_id ? `${esc(doc.contract_name || 'plan #' + doc.contract_id)} ${doc.contract_status ? statusPill(doc.contract_status) : ''}` : '<span class="help">Whole hotel — shown under every plan</span>'}</td>` : ''}
                <td class="num">${docSize(doc.size_bytes)}</td>
                <td class="help">${esc(doc.uploaded_by || '')}<br>${esc(String(doc.uploaded_at || '').slice(0, 16))}</td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-light" href="${docUrl(doc.id)}" target="_blank" rel="noopener" title="${doc.inline ? 'Open in a new browser tab' : 'Download'}"><i class="bi ${doc.inline ? 'bi-eye' : 'bi-download'}"></i> ${doc.inline ? 'Open' : 'Download'}</a>
                    ${doc.inline ? `<a class="btn btn-sm btn-light" href="${docUrl(doc.id, 1)}" title="Download"><i class="bi bi-download"></i></a>` : ''}
                    ${mayRemoveDoc(doc, info) ? `<button class="btn btn-sm btn-light text-danger" data-doc-remove="${doc.id}" title="Remove — the file stays in the audit trail"><i class="bi bi-x-lg"></i></button>` : ''}
                </td></tr>`).join('')}
            </tbody></table></div>`;
    }
    // the upload area; contractId undefined = the hotel tab (choose where the file belongs), else fixed to that plan
    function docUploader(contractId, info) {
        const plans = (S.detail.contracts || []).filter(c => c.status !== 'cancelled');
        const target = contractId === undefined
            ? `<div><label class="form-label">Attach to</label><select class="form-select form-select-sm" data-doc-target style="min-width:260px"><option value="">Choose where it belongs…</option><option value="0">Whole hotel (shown under every plan)</option>${plans.map(c => `<option value="${c.id}">Plan: ${esc(c.name)} (${esc(c.status)})</option>`).join('')}</select></div>`
            : '';
        return `<div class="doc-drop" data-doc-drop tabindex="0" role="button" aria-label="Attach documents"><i class="bi bi-cloud-arrow-up"></i><b>Drop files here</b> or click to choose<div class="help">PDF, pictures, Excel, Word, e-mail (EML / MSG), TXT or CSV — up to ${info.max_mb || 20} MB each</div><input type="file" multiple accept="${DOC_ACCEPT}" data-doc-file hidden></div>
            <div class="doc-up">${target}
                <div><label class="form-label">Type</label>${sel(info.kinds || {}, 'rate_sheet', 'class="form-select form-select-sm" data-doc-kind')}</div>
                <div style="flex:1 1 220px"><label class="form-label">Note (optional)</label><input class="form-control form-control-sm" data-doc-note maxlength="300" placeholder="e.g. received from the hotel by e-mail on 12 Nov"></div>
            </div>
            <div class="help mt-1" data-doc-status></div>`;
    }
    async function uploadDocs(box, files, contractId, info, reload) {
        const st = box.querySelector('[data-doc-status]');
        let target = contractId;
        if (target === undefined) {
            const v = box.querySelector('[data-doc-target]').value;
            if (v === '') { toast('warning', 'Choose where the document belongs: the whole hotel or one rate plan.'); return; }
            target = Number(v) || null;
        }
        const kind = box.querySelector('[data-doc-kind]').value, note = box.querySelector('[data-doc-note]').value;
        const done = [], failed = [];
        for (const f of files) {
            if (st) st.textContent = `Attaching ${f.name}…`;
            const fd = new FormData(); fd.append('document', f); fd.append('hotel_id', S.hotel.id); if (target) fd.append('contract_id', target); fd.append('kind', kind); if (note) fd.append('note', note);
            let j; try { j = await fetch(BASE + '/documents/upload', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json()); } catch (e) { j = { status: 500, message: 'The upload failed: ' + e.message }; }
            (j.status === 200 ? done : failed).push(j.status === 200 ? f.name : f.name + ': ' + (j.message || 'not attached'));
        }
        if (st) st.textContent = '';
        if (failed.length) Swal.fire({ icon: done.length ? 'warning' : 'error', title: done.length ? `${done.length} attached, ${failed.length} not` : 'Not attached', html: failed.map(esc).join('<br>') });
        else toast('success', done.length === 1 ? `"${done[0]}" attached.` : `${done.length} documents attached.`);
        await reload();
    }
    function bindDocs(box, contractId, info, reload) {
        const drop = box.querySelector('[data-doc-drop]'), input = box.querySelector('[data-doc-file]');
        if (drop && input) {
            drop.addEventListener('click', () => input.click());
            drop.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
            input.addEventListener('change', () => { if (input.files.length) uploadDocs(box, [...input.files], contractId, info, reload); input.value = ''; });
            ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
            ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
            drop.addEventListener('drop', e => { const fl = [...(e.dataTransfer.files || [])]; if (fl.length) uploadDocs(box, fl, contractId, info, reload); });
        }
        box.querySelectorAll('[data-doc-remove]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ icon: 'question', title: 'Remove this document?', text: 'It disappears from the lists but stays on the server and in the audit trail.', input: 'text', inputLabel: 'Reason (required)', inputPlaceholder: 'e.g. wrong file, replaced by the revised sheet', showCancelButton: true, confirmButtonText: 'Remove', inputValidator: v => (!v || !v.trim()) ? 'Please give a reason.' : undefined });
            if (!r.isConfirmed) return;
            const j = await post('/document/' + b.dataset.docRemove + '/remove', { reason: r.value });
            if (j.status !== 200) return toast('error', j.message);
            toast('success', j.message); await reload();
        }));
    }
    function docCountsFrom(list) {
        const dc = {}; let hw = 0;
        list.forEach(doc => { if (doc.contract_id) dc[doc.contract_id] = (dc[doc.contract_id] || 0) + 1; else hw++; });
        S.detail.doc_counts = dc; S.detail.doc_hotel = hw; setDocCount();
    }
    async function loadHotelDocs() {
        const box = document.getElementById('docs_box'); if (!box || !S.hotel) return;
        box.innerHTML = '<div class="empty">Loading…</div>';
        const j = await api('/hotel/' + S.hotel.id + '/documents');
        if (j.status !== 200) { box.innerHTML = `<div class="issue error">${esc(j.message)}</div>`; return; }
        const info = j.data, list = info.documents;
        docCountsFrom(list); renderContracts();
        const hotelWide = list.filter(d => !d.contract_id), perPlan = list.filter(d => d.contract_id);
        box.innerHTML = `<div class="ed-lead">Keep the original rate sheet, contract, factsheet or e-mail with the hotel, so whoever approves a plan can open the source beside the figures. A document attached to the <b>whole hotel</b> shows under every plan.</div>
            ${info.storage_ok ? docUploader(undefined, info) : '<div class="issue error">The server has no writable folder for documents yet (writable/hotel_rate_docs) — ask the administrator. Nothing can be attached until then.</div>'}
            <div class="doc-group">Whole hotel <span class="help">(${hotelWide.length})</span></div>${docTable(hotelWide, info, false)}
            <div class="doc-group">Rate plans <span class="help">(${perPlan.length})</span></div>${docTable(perPlan, info, true)}`;
        bindDocs(box, undefined, info, loadHotelDocs);
    }
    async function loadPlanDocs() {
        const box = document.getElementById('ed_docs'); if (!box || !S.sheet) return;
        const c = S.sheet.contract;
        if (!c.id) { box.innerHTML = '<div class="empty">Save the draft first — then attach the rate sheet here.</div>'; return; }
        const j = await api('/contract/' + c.id + '/documents');
        if (j.status !== 200) { box.innerHTML = `<div class="issue error">${esc(j.message)}</div>`; return; }
        const info = j.data, list = info.documents;
        const own = list.filter(d => d.contract_id), hotelWide = list.filter(d => !d.contract_id);
        S.detail.doc_counts = S.detail.doc_counts || {}; S.detail.doc_counts[c.id] = own.length; S.detail.doc_hotel = hotelWide.length; setDocCount(); renderContracts();
        const canAdd = info.storage_ok;   // evidence can be added to an approved plan too: it changes no rate
        box.innerHTML = `${canAdd ? docUploader(c.id, info) : ''}
            <div class="doc-group">This plan <span class="help">(${own.length})</span></div>${docTable(own, info, false)}
            <div class="doc-group">Whole hotel <span class="help">(${hotelWide.length}) — attach or remove these under the hotel's Documents tab</span></div>${docTable(hotelWide, info, false)}`;
        box.querySelectorAll('[data-doc-remove]').forEach(b => { if (hotelWide.some(d => String(d.id) === b.dataset.docRemove)) b.remove(); });
        bindDocs(box, c.id, info, loadPlanDocs);
    }
    document.addEventListener('click', e => { const b = e.target.closest('[data-docs-open]'); if (!b) return; e.preventDefault(); showTab('docs'); });

    // ---------------- rate on a date / a stay ----------------
    // The first thing shown for a hotel: what it costs tonight (or any night, or every night of a
    // stay), per room type and meal plan, from the approved plans — the same answer a quotation gets —
    // with every gala, peak, festival or festive-season night of the chosen dates flagged from the data.
    const OCC_LABEL = { sgl: 'SGL', dbl: 'DBL', tpl: 'TPL', quad: 'QUAD' };
    const RESTRICTION_LABEL = { min_stay: 'Minimum stay', closed_to_arrival: 'Closed to arrival', closed_to_departure: 'Closed to departure', meal_plan_required: 'Meal plan required', stop_sale: 'Stop sale', other: 'Rule' };
    const FLAG_LABEL = { gala: 'gala dinner', peak: 'peak surcharge', festival: 'festival', season: 'season', supplement: 'supplement', restriction: 'rule', no_rate: 'no rate', gala_unrecorded: 'check', adhoc: 'ad hoc rate' };
    const shiftDate = (d, n) => { const x = new Date(d + 'T00:00:00'); x.setDate(x.getDate() + n); return x.toISOString().slice(0, 10); };
    const nightsBetween = (a, b) => Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000) + 1;
    const niceDate = d => new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
    const shortDate = d => new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    const rangeText = (a, b) => a === b ? shortDate(a) + ' ' + a.slice(0, 4) : (a.slice(0, 7) === b.slice(0, 7) ? shortDate(a).slice(0, 2) + ' – ' + shortDate(b) : shortDate(a) + ' – ' + shortDate(b)) + ' ' + b.slice(0, 4);
    let roSeq = 0;
    function syncRoControls() {
        document.getElementById('ro_date').value = S.rateDate;
        document.getElementById('ro_to').value = S.rateTo || '';
        document.getElementById('ro_nights').value = S.rateTo ? nightsBetween(S.rateDate, S.rateTo) : 1;
        document.getElementById('ro_date_lbl').textContent = S.rateTo ? rangeText(S.rateDate, S.rateTo) + ` (${nightsBetween(S.rateDate, S.rateTo)} nights)` : niceDate(S.rateDate) + (S.rateDate === TODAY ? ' (today)' : '');
    }
    async function loadRatesOn() {
        if (!S.hotel) return;
        const mk = document.getElementById('ro_market');
        if (!mk.options.length) { mk.innerHTML = Object.entries(MARKETS).map(([k, v]) => `<option value="${k}" ${k === S.roMarket ? 'selected' : ''}>${esc(v)}</option>`).join(''); }
        if (S.rateTo && S.rateTo <= S.rateDate) S.rateTo = null;
        syncRoControls();
        const box = document.getElementById('rates_on_box');
        box.innerHTML = '<div class="empty">Looking up…</div>';
        const seq = ++roSeq;
        const rooms = Math.max(1, Number(document.getElementById('ro_rooms').value) || 1);
        const j = await api(`/rates_on?hotel_id=${S.hotel.id}&date=${S.rateDate}${S.rateTo ? '&to=' + S.rateTo : ''}&market=${encodeURIComponent(mk.value || S.roMarket)}&rooms=${rooms}`);
        if (seq !== roSeq) return; // a newer request has taken over
        if (j.status !== 200) { box.innerHTML = `<div class="ro-none">${esc(j.message)}</div>`; return; }
        renderRatesOn(j.data);
    }
    const compText = c => c.kind === 'rate' ? `${c.meal} row of ${c.room}` : c.kind === 'room_supplement' ? `${c.base} + ${money(c.amount)} per ${c.per}` : c.kind === 'meal_delta' ? `${c.from} → ${c.to} ${Number(c.adult) >= 0 ? '+' : ''}${money(c.adult)} per person` : (c.label || c.note || '');
    // the room type x meal plan table for one night or one segment of identical nights
    function gridHtml(d) {
        const planById = {}; (d.plans || []).forEach(p => planById[p.id] = p);
        const meals = MEALS.filter(m => d.grid.some(r => r.rates[m]));
        const perPerson = d.pricing_mode === 'per_person';
        const cell = (rt, m) => {
            const r = rt.rates[m];
            if (!r) return '<td class="cell none">—</td>';
            const mode = r.pricing_mode || d.pricing_mode;
            let inner;
            if (mode === 'per_unit' || (r.unit !== null && r.sgl === null && r.dbl === null)) {
                inner = `<span class="occ">${money(r.unit)}</span> <span class="help">/ unit${rt.unit_pax_max ? ' · max ' + rt.unit_pax_max + ' pax' : ''}</span>`;
            } else {
                inner = ['sgl', 'dbl', 'tpl'].map(o => `<span class="occ" title="${OCC_LABEL[o]}">${r[o] === null ? '·' : money(r[o])}</span>`).join('<span class="sep">/</span>');
                if (r.quad !== null) inner += `<span class="sep">/</span><span class="occ" title="QUAD">${money(r.quad)}</span>`;
            }
            const derived = r.derived && r.derived !== 'direct';
            const why = derived ? (r.components || []).map(compText).filter(Boolean).join(' · ') : '';
            const planTag = d.plans.length > 1 ? `<span class="dv">${esc(planById[r.plan_id] ? planById[r.plan_id].name : '')}</span>` : '';
            const flags = String(r.quality_flags || '').split(',').filter(f => f && f !== 'all_zero');
            const flagTag = flags.length ? ` <span class="text-danger" title="Check this row: ${esc(flags.join(', ').replace(/_/g, ' '))}"><i class="bi bi-exclamation-triangle-fill"></i></span>` : '';
            const adhoc = r.adhoc ? `<span class="dv adhoc" title="Ad hoc payment rate from ${esc(r.adhoc.plan)} — the hotel is paid this; the customer price stays as above">pay ${r.adhoc.unit !== null && r.adhoc.sgl === null ? money(r.adhoc.unit) : ['sgl', 'dbl', 'tpl'].map(o => r.adhoc[o] === null ? '·' : money(r.adhoc[o])).join(' / ')}</span>` : '';
            return `<td class="cell ${derived ? 'derived' : ''}" title="${esc(m)} · ${esc(r.season || '')}${derived ? ' · derived: ' + esc(r.derived.replace(/_/g, ' ')) + (why ? ' (' + esc(why) + ')' : '') : ''}${flags.length ? ' · flagged: ' + esc(flags.join(', ')) : ''}">${inner}${flagTag}${derived ? `<span class="dv">${esc(r.derived.replace(/_/g, ' '))}</span>` : ''}${planTag}${adhoc}</td>`;
        };
        let html = `<div class="table-responsive"><table class="hr-table ro-table"><thead><tr><th>Room type</th>${meals.map(m => `<th class="meal">${m} <span class="help">${perPerson ? 'per person' : 'SGL / DBL / TPL'}</span></th>`).join('')}</tr></thead><tbody>`;
        html += d.grid.map(rt => `<tr><td class="rt"><div class="fw-semibold">${esc(rt.name)}</div>${rt.priced_as === 'supplement' && rt.base_room_type ? `<div class="help">${esc(rt.base_room_type)} + supplement</div>` : ''}${rt.max_adults ? `<div class="help">max ${rt.max_adults} adults</div>` : ''}</td>${meals.map(m => cell(rt, m)).join('')}</tr>`).join('');
        return html + '</tbody></table></div>';
    }
    const openPlan = id => `<a href="#" data-ro-open="${id}">open</a>`;
    const seasonText = p => (p.seasons && p.seasons.length ? p.seasons : [{ name: p.season, date_from: p.season_from, date_to: p.season_to, kind: p.season_kind, min_stay: p.min_stay }]).map(s => `${esc(s.name || '')} ${s.date_from} → ${s.date_to}${s.kind && s.kind !== 'normal' ? ' (' + esc(s.kind) + ')' : ''}${s.min_stay ? ' · min stay ' + s.min_stay + ' nights' : ''}`).join(', ');
    const planLine = plans => `<div><b>Plan</b>${plans.map(p => `${typePill(p.plan_type)} ${esc(p.name)} <span class="help">${p.seasons || p.season ? '· season ' + seasonText(p) + ' ' : ''}· valid ${p.valid_from} → ${p.valid_to}${p.book_to ? ' · book by ' + p.book_to : ''}${p.rate_code ? ' · code ' + esc(p.rate_code) : ''}</span> ${openPlan(p.id)}`).join('<br>')}</div>`;
    const basisLine = d => `<div><b>Basis</b>${esc(d.currency || '')} · ${d.pricing_mode === 'per_person' ? 'per person per night' : d.pricing_mode === 'per_unit' ? 'per unit per night' : 'per room per night'} · ${d.taxes_included ? 'taxes included' : 'taxes excluded' + (d.tax_pct ? ' (' + d.tax_pct + ' %)' : '')} · market ${esc(MARKETS[d.market] || d.market)}${d.booking_date ? ' · booked ' + esc(d.booking_date) : ''}</div>`;
    const draftLine = d => (d.drafts || []).length ? `<div><b>Draft</b>${d.drafts.map(x => `${esc(x.name)} (${esc(OPT.plan_type[x.plan_type] || x.plan_type)}, ${x.valid_from} → ${x.valid_to}) — awaiting approval · ${openPlan(x.id)}`).join('; ')}</div>` : '';
    const otherLine = d => (d.other_market_plans || []).length ? `<div><b>Not used</b>${d.other_market_plans.map(x => `${esc(x.name)} <span class="help">(${esc(x.why)})</span> ${openPlan(x.id)}`).join('; ')}</div>` : '';
    const noneHtml = (d, from, to) => {
        const n = d.nearest || {};
        const near = [];
        if (n.previous) near.push(`the last approved season ended <b>${n.previous.date}</b> (${esc(n.previous.plan)})`);
        if (n.next) near.push(`the next starts <b>${n.next.date}</b> (${esc(n.next.plan)})`);
        return `<div class="ro-none"><b>No approved rate ${from === to ? 'on ' + esc(niceDate(from)) : 'for ' + esc(rangeText(from, to))}</b> for market ${esc(MARKETS[d.market] || d.market)}.${near.length ? `<div class="help mt-1">${near.join(' · ')}.</div>` : `<div class="help mt-1">This hotel has no approved plan at all yet.</div>`}</div>`;
    };
    // the "watch out" list: every gala / peak / festival / festive-season / rule / missing night, with its dates
    function flagsHtml(flags, single) {
        if (!flags || !flags.length) return `<div class="ro-flags ok"><i class="bi bi-check2-circle"></i> No gala dinners, peak or festival supplements, festive seasons or booking rules on ${single ? 'this night' : 'these nights'}.</div>`;
        const cls = { red: 'red', amber: 'amber', blue: 'grey', green: 'green' };
        return `<div class="ro-flags"><div class="ro-flags-h"><i class="bi bi-exclamation-diamond"></i> Watch out</div>` + flags.map(f => {
            const when = f.from ? (f.from === f.to ? shortDate(f.from) + ' ' + f.from.slice(0, 4) : rangeText(f.from, f.to) + ` · ${f.nights} night${f.nights > 1 ? 's' : ''}`) : '';
            const label = f.kind === 'supplement' ? (f.compulsory ? 'compulsory' : 'optional') : (f.kind === 'season' ? f.name.split(':')[0].toLowerCase() : FLAG_LABEL[f.kind] || f.kind);
            return `<div class="ro-flag"><span class="when">${esc(when)}</span>${pill(cls[f.level] || 'grey', label)} <b>${esc(f.kind === 'season' ? f.name.replace(/^[^:]+:\s*/, '') : f.name)}</b>${f.detail ? ` <span class="help">— ${esc(f.detail)}</span>` : ''}</div>`;
        }).join('') + '</div>';
    }
    function renderRatesOn(d) {
        const box = document.getElementById('rates_on_box');
        const state = document.getElementById('ro_state');
        let html = '';
        if (d.mode === 'range') {
            const missing = (d.missing_nights || []).length;
            state.innerHTML = missing === 0 ? pill('green', 'rate available') : missing === d.nights ? pill('red', 'no rate') : pill('amber', `${missing} of ${d.nights} nights unpriced`);
            html += flagsHtml(d.flags, false);
            d.segments.forEach(seg => {
                html += `<div class="ro-seg"><div class="ro-seg-h"><b>${esc(rangeText(seg.from, seg.to))}</b> <span class="help">· ${seg.nights} night${seg.nights > 1 ? 's' : ''}${seg.available && seg.plans.length ? ' · ' + seg.plans.map(p => esc(p.name) + (p.seasons && p.seasons.length ? ' — ' + p.seasons.map(x => esc(x.name)).join(', ') : '')).join('; ') : ''}</span></div>`;
                html += seg.available ? gridHtml(seg) : noneHtml(seg, seg.from, seg.to);
                html += '</div>';
            });
            if ((d.totals || []).length) {
                const meals = MEALS.filter(m => d.totals.some(t => t.meal_plan === m));
                const rooms = []; d.totals.forEach(t => { if (!rooms.includes(t.room_type)) rooms.push(t.room_type); });
                const tot = (room, m) => { const t = d.totals.find(x => x.room_type === room && x.meal_plan === m); if (!t) return '<td class="cell none">—</td>'; if (!t.complete) return `<td class="cell none" title="priced on ${t.nights} of ${d.nights} nights only">${t.nights}/${d.nights} nights</td>`; return `<td class="cell">${t.unit !== null && t.sgl === null && t.dbl === null ? `<span class="occ">${money(t.unit)}</span> <span class="help">/ unit</span>` : ['sgl', 'dbl', 'tpl'].map(o => `<span class="occ" title="${OCC_LABEL[o]}">${t[o] === null ? '·' : money(t[o])}</span>`).join('<span class="sep">/</span>')}</td>`; };
                html += `<div class="ro-seg"><div class="ro-seg-h"><b>Stay total</b> <span class="help">· ${d.nights} nights · room rates only — add the compulsory supplements above${d.pricing_mode === 'per_person' ? ' · per person' : ''}</span></div><div class="table-responsive"><table class="hr-table ro-table"><thead><tr><th>Room type</th>${meals.map(m => `<th class="meal">${m} <span class="help">${d.pricing_mode === 'per_person' ? 'per person' : 'SGL / DBL / TPL'}</span></th>`).join('')}</tr></thead><tbody>${rooms.map(r => `<tr><td class="rt"><div class="fw-semibold">${esc(r)}</div></td>${meals.map(m => tot(r, m)).join('')}</tr>`).join('')}</tbody></table></div></div>`;
            }
            const lines = [];
            if ((d.plans || []).length) lines.push(planLine(d.plans));
            if (d.currency) lines.push(basisLine(d));
            html += `<div class="ro-lines">${lines.join('')}</div>`;
            box.innerHTML = html;
            bindRoLinks(box);
            return;
        }
        if (!d.available) {
            state.innerHTML = pill('red', 'no rate');
            html += flagsHtml(d.flags, true) + noneHtml(d, d.date, d.date) + `<div class="ro-lines">${draftLine(d)}${otherLine(d)}</div>`;
            box.innerHTML = html;
            bindRoLinks(box);
            return;
        }
        state.innerHTML = pill('green', 'rate available');
        html += flagsHtml(d.flags, true) + gridHtml(d);
        const lines = [planLine(d.plans), basisLine(d)];
        if ((d.adhoc || []).length) lines.push(`<div><b>Ad hoc</b>${d.adhoc.map(a => `${esc(a.room_type)} ${esc(a.meal_plan)}: hotel paid <b>${a.pay.unit !== null && a.pay.sgl === null ? money(a.pay.unit) : ['sgl', 'dbl', 'tpl'].map(o => a.pay[o] === null ? '·' : money(a.pay[o])).join(' / ')}</b>${a.quote ? ` (quotation ${['sgl', 'dbl', 'tpl'].map(o => a.quote[o] === null ? '·' : money(a.quote[o])).join(' / ')}${a.margin && a.margin.dbl !== null ? ' · margin ' + money(a.margin.dbl) + ' on a double' : ''})` : ' (no quotation rate — customer price unchanged)'} <span class="help">· ${esc(a.plan)}</span>`).join('<br>')}</div>`);
        if ((d.meal_prices || []).length) lines.push(`<div><b>Meals</b>${d.meal_prices.map(m => `${esc(m.name || m.meal)} ${money(m.adult)}${m.child !== null ? ' (child ' + money(m.child) + ')' : ''}`).join(' · ')}</div>`);
        if ((d.child_rates || []).length) lines.push(`<div><b>Children</b>${d.child_rates.slice(0, 6).map(c => `${fmt(c.age_from)}–${fmt(c.age_to)} yrs${c.bed && c.bed !== 'any' ? ' ' + c.bed.replace('_', ' ') : ''}${c.meal_plan ? ' ' + esc(c.meal_plan) : ''}: ${c.amount !== null ? (Number(c.amount) === 0 ? 'free' : money(c.amount)) : c.pct + ' % of ' + esc((c.pct_basis || '').replace(/_/g, ' '))}`).join(' · ')}${d.child_rates.length > 6 ? ' · …' : ''}</div>`);
        if ((d.unpriced_rooms || []).length) lines.push(`<div><b>No rate</b><span class="help">${d.unpriced_rooms.map(esc).join(', ')} — not priced on this date</span></div>`);
        const dl = draftLine(d), ol = otherLine(d);
        if (dl) lines.push(dl);
        if (ol) lines.push(ol);
        html += `<div class="ro-lines">${lines.join('')}</div>`;
        box.innerHTML = html;
        bindRoLinks(box);
    }
    function bindRoLinks(box) {
        box.querySelectorAll('[data-ro-open]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); showTab('plans'); openContract(Number(a.dataset.roOpen)); }));
    }
    document.getElementById('ro_date').addEventListener('change', e => { if (e.target.value) { const n = S.rateTo ? nightsBetween(S.rateDate, S.rateTo) : 1; S.rateDate = e.target.value; if (S.rateTo) S.rateTo = shiftDate(S.rateDate, n - 1); loadRatesOn(); } });
    document.getElementById('ro_to').addEventListener('change', e => { S.rateTo = e.target.value && e.target.value > S.rateDate ? e.target.value : null; loadRatesOn(); });
    document.getElementById('ro_nights').addEventListener('change', e => { const n = Math.max(1, Math.min(62, Number(e.target.value) || 1)); S.rateTo = n > 1 ? shiftDate(S.rateDate, n - 1) : null; loadRatesOn(); });
    document.getElementById('ro_prev').addEventListener('click', () => { const n = S.rateTo ? nightsBetween(S.rateDate, S.rateTo) : 1; S.rateDate = shiftDate(S.rateDate, -n); if (S.rateTo) S.rateTo = shiftDate(S.rateDate, n - 1); loadRatesOn(); });
    document.getElementById('ro_next').addEventListener('click', () => { const n = S.rateTo ? nightsBetween(S.rateDate, S.rateTo) : 1; S.rateDate = shiftDate(S.rateDate, n); if (S.rateTo) S.rateTo = shiftDate(S.rateDate, n - 1); loadRatesOn(); });
    document.getElementById('ro_today').addEventListener('click', () => { S.rateDate = TODAY; S.rateTo = null; loadRatesOn(); });
    document.getElementById('ro_market').addEventListener('change', e => { S.roMarket = e.target.value; loadRatesOn(); });
    document.getElementById('ro_rooms').addEventListener('change', () => loadRatesOn());
    document.getElementById('btn_rate_on').addEventListener('click', () => { const el = document.getElementById('rates_on_card'); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); const inp = document.getElementById('ro_date'); inp.focus(); if (inp.showPicker) { try { inp.showPicker(); } catch (e) {} } });
    // hotels folded into this one by a merge, each with an undo
    function renderAbsorbed() {
        const box = document.getElementById('h_absorbed');
        const list = (S.hotel && S.hotel.absorbed) || [];
        box.innerHTML = list.length ? list.map(a => `<div class="absorbed"><i class="bi bi-sign-merge-left"></i> absorbed <b>${esc(a.name)}</b>${a.hotel_code ? ` <span class="hcode">${esc(a.hotel_code)}</span>` : ''} <span class="help">${esc(a.location_name || '')} · <span class="mono">${esc(a.primary_sup_acc_no || '')}</span> · merged ${esc(String(a.updated_at || '').slice(0, 16))} by ${esc(a.updated_by || '')}</span>${CAN_APPROVE ? ` <button class="btn btn-sm btn-outline-danger py-0" data-unmerge="${a.id}"><i class="bi bi-arrow-counterclockwise"></i> Undo merge</button>` : ''}</div>`).join('') : '';
        box.querySelectorAll('[data-unmerge]').forEach(b => b.addEventListener('click', () => undoMerge(Number(b.dataset.unmerge))));
    }
    async function undoMerge(id) {
        const j = await api(`/hotel/${id}/unmerge_preview`);
        if (j.status !== 200) return toast('error', j.message);
        const p = j.data;
        const html = `<div class="text-start small"><p><b>${esc(p.hotel.name)}</b> <span class="help">(${esc(p.hotel.location_name || '')} · ${esc(p.hotel.primary_sup_acc_no || '')})</span> becomes a hotel again, taken out of <b>${esc(p.kept.name)}</b>.</p>
            <div><b>Supplier code(s) back:</b> ${p.accounts.length ? p.accounts.map(esc).join(', ') : '<span class="text-danger">none found</span>'}</div>
            <div><b>Plans back:</b> ${p.contracts.length ? p.contracts.map(c => `${esc(c.name)} <span class="help">(${esc(OPT.plan_type[c.plan_type] || c.plan_type)}, ${esc(c.status)}, ${c.valid_from} → ${c.valid_to})</span>`).join('; ') : 'none'}</div>
            <div><b>Room types:</b> ${p.room_types.length ? p.room_types.map(r => `${esc(r.name)}${r.action === 'copy' ? ' <span class="help">(copied — the kept hotel uses it too)</span>' : ''}`).join(', ') : 'none'}</div>
            ${p.warning ? `<div class="alert alert-warning mt-2 mb-0">${esc(p.warning)}</div>` : ''}
            <div class="help mt-2">The old Hotel Rate screen rows of both hotels are rebuilt afterwards.</div></div>`;
        const ok = await Swal.fire({ icon: 'question', title: 'Undo this merge?', html, width: 700, showCancelButton: true, confirmButtonText: 'Undo merge' });
        if (!ok.isConfirmed) return;
        const r = await postA('/unmerge', { hotel_id: id });
        toast(r.status === 200 ? 'success' : 'error', r.message);
        if (r.status === 200) { await loadHotels(); selectHotel(S.hotel.id); }
    }
    document.querySelectorAll('.hr-tab').forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));
    function showTab(name) {
        if (window.TfView && S.hotel) TfView.setParams({ tab: name !== 'plans' ? name : null });   // tf-refresh-r1
        document.querySelectorAll('.hr-tab').forEach(t => t.classList.toggle('on', t.dataset.tab === name));
        document.querySelectorAll('.tabpane').forEach(p => p.style.display = p.id === 'tab_' + name ? '' : 'none');
        if (name === 'docs') loadHotelDocs();   // tf-hr-docs-r1
        if (!['plans', 'tactical', 'early_bird'].includes(name)) closeEditor();
    }

    function renderContracts() {
        const tb = document.querySelector('#contract_table tbody');
        // with the three areas, this tab holds the published rates only; tacticals and early birds have their own tabs
        const list = OFFERS_READY ? S.detail.contracts.filter(c => !offerKind(c)) : S.detail.contracts;
        const dcount = (S.detail && S.detail.doc_counts) || {};   // tf-hr-docs-r1
        tb.innerHTML = list.map(c => {
            const closed = c.book_to && c.book_to < TODAY;
            return `<tr>
            <td><div class="fw-semibold">${esc(c.name)}${dcount[c.id] ? `<button class="doc-clip" data-docs-open="${c.id}" title="${dcount[c.id]} document(s) attached to this plan — open the Documents tab"><i class="bi bi-paperclip"></i>${dcount[c.id]}</button>` : ''}</div><div class="help">${c.rate_code ? '<span class="mono">' + esc(c.rate_code) + '</span> · ' : ''}${esc(c.source_type)}${c.source_file ? ' · ' + esc(c.source_file) : ''}${c.parent_name ? ' · addendum to <i>' + esc(c.parent_name) + '</i>' : ''}</div></td>
            <td>${typePill(c.plan_type)}${c.applies_to === 'payment' ? ' ' + pill('green', 'payment only') : c.applies_to === 'quotation' ? ' ' + pill('grey', 'quotation only') : ''}</td>
            <td><div>${esc(c.market_list ? c.market_list.replace(/,/g, ', ') : 'All markets')}</div><div class="help">${c.tier !== 'all' ? esc(OPT.tier[c.tier] || c.tier) + (c.tier_min_rooms || c.tier_max_rooms ? ` (${c.tier_min_rooms || 1}–${c.tier_max_rooms || '∞'} rooms)` : '') : ''}</div></td>
            <td class="mono">${c.valid_from} → ${c.valid_to}</td>
            <td class="mono">${c.book_to ? `<span class="${closed ? 'text-danger' : ''}">${c.book_to}${closed ? ' (closed)' : ''}</span>` : ''}</td>
            <td>${statusPill(c.status)}</td>
            <td class="num">${c.seasons}</td><td class="num">${c.rate_rows}</td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-light" data-act="open" data-id="${c.id}">${c.status === 'draft' ? 'Edit' : 'View'}</button>
                ${c.status === 'draft' && CAN_APPROVE ? `<button class="btn btn-sm btn-success" data-act="approve" data-id="${c.id}">Approve</button>` : ''}
                <div class="btn-group"><button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">More</button><ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-act="copy" data-id="${c.id}">Copy to a new draft</a></li>
                    <li><a class="dropdown-item" href="#" data-act="copy_next" data-id="${c.id}">Copy for next year (+1 year)</a></li>
                    ${c.status === 'approved' && CAN_APPROVE ? `<li><a class="dropdown-item" href="#" data-act="edit_approved" data-id="${c.id}">Correct the approved plan</a></li><li><a class="dropdown-item" href="#" data-act="supersede" data-id="${c.id}">Mark as superseded</a></li>` : ''}
                    ${c.status === 'draft' ? `<li><a class="dropdown-item text-danger" href="#" data-act="delete" data-id="${c.id}">Delete draft</a></li>` : ''}
                    <li><a class="dropdown-item" href="#" data-act="audit" data-id="${c.id}">Who changed it</a></li>
                </ul></div>
            </td></tr>`;
        }).join('') || '<tr><td colspan="9"><div class="empty">No rate plans yet. Click <b>New plan</b>, import a sheet or read a PDF.</div></td></tr>';
        tb.querySelectorAll('[data-act]').forEach(b => b.addEventListener('click', e => { e.preventDefault(); contractAction(b.dataset.act, Number(b.dataset.id)); }));
    }
    async function contractAction(act, id) {
        if (act === 'open') return openContract(id);
        if (act === 'edit_approved') return openContract(id, true);
        if (act === 'approve') return approveContract(id);
        if (act === 'copy' || act === 'copy_next') {
            const j = await post(`/contract/${id}/copy`, { shift_years: act === 'copy_next' ? 1 : 0 });
            if (j.status !== 200) return toast('error', j.message);
            await selectHotel(S.hotel.id, true); return openContract(j.data.contract_id);
        }
        if (act === 'supersede') {
            if (!await confirmBox('Supersede this plan?', 'Its rates stop being used for quotations and the old screen is rebuilt without it. The plan stays for history.', 'Supersede')) return;
            const j = await postA(`/contract/${id}/supersede`); toast(j.status === 200 ? 'success' : 'error', j.message); selectHotel(S.hotel.id);
        }
        if (act === 'delete') {
            if (!await confirmBox('Delete this draft?', 'This cannot be undone.', 'Delete')) return;
            const j = await post(`/contract/${id}/delete`); toast(j.status === 200 ? 'success' : 'error', j.message); selectHotel(S.hotel.id);
        }
        if (act === 'audit') {
            const j = await api(`/contract/${id}/audit`);
            Swal.fire({ title: 'History', html: '<div class="text-start small">' + (j.data || []).map(a => `<div><span class="mono">${a.created_at}</span> ${esc(a.user)} — ${a.action}</div>`).join('') + '</div>', width: 600 });
        }
    }
    async function approveContract(id, ack) {
        const j = await postA(`/contract/${id}/approve`, { acknowledge_warnings: !!ack });
        if (j.status === 200) { toast('success', j.message); if (S.hotel) selectHotel(S.hotel.id); loadQueueCount(); return; }
        if (j.status === 403) { toast('error', j.message); return; }
        if (j.data && j.data.needs_acknowledgement) {
            const ok = await Swal.fire({ title: 'Warnings to confirm', html: issuesHtml(j.data.issues), width: 760, showCancelButton: true, confirmButtonText: 'Approve anyway' });
            if (ok.isConfirmed) return approveContract(id, true);
            return;
        }
        Swal.fire({ title: 'Cannot approve yet', html: issuesHtml(j.data ? j.data.issues : []), width: 760 });
    }
    const issuesHtml = issues => issues && issues.length ? '<div class="text-start">' + issues.map(i => `<div class="issue ${i.level}"><b>${i.code}</b>${esc(i.message)}</div>`).join('') + '</div>' : '<div>No issues.</div>';

    // ---------------- Published rates · Tactical rates · Early Birds (tf-offers-r1) ----------------
    async function loadOffers() {
        const strip = document.getElementById('h_areas');
        if (!OFFERS_READY || !S.hotel) { strip.style.display = 'none'; return; }
        const hid = S.hotel.id;
        const j = await api('/hotel/' + hid + '/offers');
        if (!S.hotel || S.hotel.id !== hid) return;
        if (j.status !== 200 || !j.data) { strip.style.display = 'none'; return; }
        S.areas = j.data;
        renderAreas(); renderOfferTable('tactical'); renderOfferTable('early_bird');
    }
    function renderAreas() {
        const a = S.areas, strip = document.getElementById('h_areas');
        const pubTxt = a.published.current > 0 ? `<b>Published rates:</b> Available${a.published.until ? ' until ' + esc(a.published.until) : ''}` : `<b>Published rates:</b> none current${a.published.drafts ? ' (' + a.published.drafts + ' draft' + (a.published.drafts === 1 ? '' : 's') + ')' : ''}`;
        const n = (k, one, none) => a[k + '_active'] > 0 ? `<b>${one}:</b> ${a[k + '_active']} Active` : `<b>${one}:</b> 0 — ${none}`;
        strip.innerHTML = `<span class="area ${a.published.current > 0 ? 'ok' : 'none'}" data-area="plans" title="Seasonal contract rates — the basis of every quotation">${pubTxt}</span>`
            + `<span class="area ${a.tactical_active > 0 ? 'ok' : 'none'}" data-area="tactical" title="Special rates the hotel gives us selectively — at hotel payment only, applied by an authorized person">${n('tactical', 'Tacticals', 'No Tactical Rates')}</span>`
            + `<span class="area ${a.early_bird_active > 0 ? 'ok' : 'none'}" data-area="early_bird" title="Rates for booking ahead — at hotel payment only, for tours booked by the deadline, applied by an authorized person">${n('early_bird', 'Early Birds', 'No Early Bird Offers')}</span>`;
        strip.style.display = '';
        strip.querySelectorAll('[data-area]').forEach(e => e.addEventListener('click', () => showTab(e.dataset.area)));
        document.getElementById('cnt_tactical').textContent = a.tactical_active;
        document.getElementById('cnt_early_bird').textContent = a.early_bird_active;
    }
    function renderOfferTable(kind) {
        const tb = document.querySelector(`#offer_table_${kind} tbody`); if (!tb) return;
        const list = (S.areas && S.areas[kind]) || [];
        tb.innerHTML = list.map(o => {
            const [lvl, t] = OFFER_STATUS[o.offer_status] || ['grey', o.offer_status];
            const days = o.days_to_deadline !== null && OFFER_ACTIVE.includes(o.offer_status) ? `<div class="help">${o.days_to_deadline === 0 ? 'last booking day today' : o.days_to_deadline + ' day' + (o.days_to_deadline === 1 ? '' : 's') + ' to book'}</div>` : '';
            const editable = o.status === 'draft' || o.status === 'approved';
            return `<tr>
            <td><div class="fw-semibold">${esc(o.name)}</div><div class="help">${o.rate_code ? '<span class="mono">' + esc(o.rate_code) + '</span> · ' : ''}${esc(OPT.plan_type[o.plan_type] || o.plan_type)}${o.payment_only ? ' · payment only' : ''}${o.source_file ? ' · ' + esc(o.source_file) : ''}</div><div class="help">${o.approved_by ? 'saved active by ' + esc(o.approved_by) + ' on ' + esc(String(o.approved_at || '').slice(0, 10)) : (o.entered_by ? 'entered by ' + esc(o.entered_by) : '')}</div></td>
            <td>${pill(lvl, t)}${days}</td>
            <td class="mono">${esc(o.valid_from)} → ${esc(o.valid_to)}</td>
            <td class="mono">${o.book_to ? esc(o.book_to) : '<span class="help">—</span>'}</td>
            <td>${esc(o.rooms.slice(0, 4).join(', '))}${o.rooms.length > 4 ? ' +' + (o.rooms.length - 4) : ''}<div class="help">${esc(o.meal_plans.join(' · '))}</div></td>
            <td>${o.markets.length ? esc(o.markets.join(', ')) : '<span class="help">all markets</span>'}${o.min_stay ? `<div class="help">min ${o.min_stay} nights</div>` : ''}</td>
            <td class="num">${o.lowest ? `${esc(o.currency)} ${money(o.lowest.amount)}<div class="help">${esc(o.lowest.room)} ${esc(o.lowest.meal_plan)}</div>` : '—'}</td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-light" data-oact="open" data-id="${o.id}">${editable ? 'Edit' : 'View'}</button>
                ${o.status === 'approved' ? `<button class="btn btn-sm btn-light text-danger" data-oact="withdraw" data-id="${o.id}">Withdraw</button>` : ''}
                ${o.status === 'draft' ? `<button class="btn btn-sm btn-success" data-oact="activate" data-id="${o.id}">Make active</button> <button class="btn btn-sm btn-light text-danger" data-oact="delete" data-id="${o.id}">Delete</button>` : ''}
                <button class="btn btn-sm btn-light" data-oact="audit" data-id="${o.id}" title="Who changed it">History</button>
            </td></tr>`;
        }).join('') || `<tr><td colspan="8"><div class="empty">${kind === 'early_bird' ? 'Early Birds: 0 — No Early Bird Offers for this hotel.' : 'Tacticals: 0 — No Tactical Rates for this hotel.'} Add one with the button above, or read the hotel's offer from a PDF.</div></td></tr>`;
        tb.querySelectorAll('[data-oact]').forEach(b => b.addEventListener('click', e => { e.preventDefault(); offerAction(b.dataset.oact, Number(b.dataset.id)); }));
    }
    async function offerAction(act, id) {
        const o = ((S.areas && [...S.areas.tactical, ...S.areas.early_bird]) || []).find(x => x.id === id);
        if (act === 'open') return openContract(id, !!(o && o.status === 'approved'));
        if (act === 'audit') return contractAction('audit', id);
        if (act === 'delete') return contractAction('delete', id);
        if (act === 'withdraw') {
            if (!await confirmBox('Withdraw this offer?', 'It stops counting for hotel payments from now on; confirmed tours are re-costed. It stays in the list as withdrawn.', 'Withdraw')) return;
            const j = await post(`/contract/${id}/withdraw_offer`); toast(j.status === 200 ? 'success' : 'error', j.message); await selectHotel(S.hotel.id); if (o) showTab(o.kind); return;
        }
        if (act === 'activate') { await openContract(id); return save(false, false, true); }
    }
    document.querySelectorAll('[data-offer-new]').forEach(b => b.addEventListener('click', () => {
        if (!S.hotel) return;
        S.sheet = blankSheet(b.dataset.offerNew); S.ro = false; S.editApproved = false; S.section = 'overview'; renderEditor();
    }));
    document.querySelectorAll('[data-offer-upload]').forEach(b => b.addEventListener('click', () => {
        if (!S.hotel) return;
        resetImport(b.dataset.ai === '1', b.dataset.offerUpload); showModal('#m_import');
    }));

    // ---------------- coverage ----------------
    const PLAN_COLORS = ['#0f8b7e', '#1e3553', '#b7791f', '#7c3aed', '#c2410c', '#0369a1', '#4d7c0f', '#9f1239'];
    function renderCoverage(cov) {
        const box = document.getElementById('coverage_box');
        const mk = document.getElementById('cov_market');
        if (!mk.options.length) { mk.innerHTML = Object.entries(MARKETS).map(([k, v]) => `<option value="${k}" ${k === S.covMarket ? 'selected' : ''}>${esc(v)}</option>`).join(''); }
        const rooms = Object.keys(cov.rooms);
        if (!rooms.length) { box.innerHTML = `<div class="empty">No approved rates between ${cov.from} and ${cov.to} for market ${esc(cov.market)}.</div>`; return; }
        const colorOf = {}; (cov.plans || []).forEach((p, i) => colorOf[p.id] = PLAN_COLORS[i % PLAN_COLORS.length]);
        const start = new Date(cov.from); const months = [];
        for (let i = 0; i < 12; i++) months.push(new Date(start.getFullYear(), start.getMonth() + i, 1));
        const dayMs = 86400000;
        let html = `<div class="cov"><div></div>${months.map(m => `<div class="mh">${m.toLocaleString('en', { month: 'short' })} ${String(m.getFullYear()).slice(2)}</div>`).join('')}`;
        rooms.forEach(name => {
            const r = cov.rooms[name];
            html += `<div class="lab" title="${esc(name)}">${esc(name)}${r.priced_as === 'supplement' ? ' <span class="help">+supp.</span>' : ''}</div>`;
            months.forEach(m => {
                const mStart = m, mEnd = new Date(m.getFullYear(), m.getMonth() + 1, 0); const days = (mEnd - mStart) / dayMs + 1;
                let segs = '';
                r.segments.forEach(s => {
                    const f = new Date(s.date_from), t = new Date(s.date_to);
                    if (t < mStart || f > mEnd) return;
                    const a = Math.max(0, (Math.max(f, mStart) - mStart) / dayMs), b = Math.min(days, (Math.min(t, mEnd) - mStart) / dayMs + 1);
                    segs += `<span class="seg" style="left:${a / days * 100}%;width:${(b - a) / days * 100}%;background:${colorOf[s.plan_id] || '#0f8b7e'}" title="${esc(s.plan)} · ${s.date_from} → ${s.date_to} · ${esc(s.meal_plans)} · DBL ${money(s.dbl)}${s.derived !== 'direct' ? ' (' + s.derived.replace(/_/g, ' ') + ')' : ''}"></span>`;
                });
                html += `<div class="m">${segs}</div>`;
            });
        });
        html += '</div>';
        html += `<div class="legend">${(cov.plans || []).map(p => `<span><i style="background:${colorOf[p.id]}"></i>${esc(p.name)} <span class="help">${p.valid_from} → ${p.valid_to}${p.book_to ? ' · book by ' + p.book_to : ''}</span></span>`).join('')}<span><i style="background:#eef1f5"></i>no rate</span></div>`;
        const gaps = rooms.filter(n => cov.rooms[n].gaps.length);
        if (gaps.length) html += '<div class="mt-3"><b class="help">Gaps</b>' + gaps.map(n => `<div class="small"><b>${esc(n)}</b>: ${cov.rooms[n].gaps.map(g => g[0] + ' → ' + g[1]).join(', ')}</div>`).join('') + '</div>';
        box.innerHTML = html;
    }
    document.getElementById('cov_refresh').addEventListener('click', async () => {
        S.covMarket = document.getElementById('cov_market').value;
        const j = await api(`/coverage/${S.hotel.id}?market=${encodeURIComponent(S.covMarket)}&rooms=${encodeURIComponent(document.getElementById('cov_rooms').value || 1)}`);
        if (j.status === 200) renderCoverage(j.data); else toast('error', j.message);
    });

    // ---------------- room types ----------------
    function renderRooms() {
        const box = document.getElementById('rooms_box');
        const rooms = S.detail.room_types;
        const baseOpts = r => `<option value="">—</option>` + rooms.filter(x => x.id !== r.id).map(x => `<option value="${x.id}" ${String(r.base_room_type_id) === String(x.id) ? 'selected' : ''}>${esc(x.name)}</option>`).join('');
        box.innerHTML = `<div class="d-flex gap-2 mb-3 flex-wrap align-items-end"><div><label class="form-label">Add a room type</label><input class="form-control form-control-sm" id="rt_new" placeholder="Exactly as the hotel writes it" style="min-width:260px"></div><button class="btn btn-sm btn-primary" id="rt_add">Add</button><span class="help ms-2">Rooms priced as "base + supplement" (Sheraton, Radisson, Amaya…) get a base room here and their supplement inside each plan.</span></div>
            <div class="table-responsive"><table class="hr-table grid"><thead><tr><th>Name</th><th>Code</th><th>Priced as</th><th>Base room</th><th>Rooms</th><th>m²</th><th>Beds</th><th>View</th><th>Max ad.</th><th>Max ch.</th><th>Extra bed</th><th>Unit pax</th><th>Status</th><th>Legacy id</th><th></th></tr></thead><tbody>` +
            rooms.map(r => `<tr data-id="${r.id}"><td><input class="wide" value="${esc(r.name)}" data-f="name"></td><td><input class="narrow" value="${esc(val(r.code))}" data-f="code"></td>
                <td>${sel({ base: 'base rate', supplement: 'base + suppl.' }, r.priced_as, 'data-f="priced_as"')}</td><td><select data-f="base_room_type_id">${baseOpts(r)}</select></td>
                <td><input type="number" class="narrow" value="${val(r.room_count)}" data-f="room_count"></td><td><input type="number" class="narrow" value="${val(r.size_sqm)}" data-f="size_sqm"></td>
                <td><input value="${esc(val(r.bed_config))}" data-f="bed_config"></td><td><input class="narrow" value="${esc(val(r.view))}" data-f="view"></td>
                <td><input type="number" class="narrow" value="${val(r.max_adults)}" data-f="max_adults"></td><td><input type="number" class="narrow" value="${val(r.max_children)}" data-f="max_children"></td>
                <td>${sel({ 1: 'yes', 0: 'no' }, r.extra_bed_allowed, 'data-f="extra_bed_allowed"')}</td><td><input class="narrow" value="${val(r.unit_pax_max)}" data-f="unit_pax_max" placeholder="max"></td>
                <td>${sel({ active: 'active', inactive: 'inactive' }, r.status, 'data-f="status"')}</td><td class="mono">${r.legacy_room_type_id || ''}</td>
                <td><button class="btn btn-sm btn-light" data-save="${r.id}">Save</button></td></tr>`).join('') + '</tbody></table></div>';
        box.querySelector('#rt_add').addEventListener('click', async () => {
            const name = box.querySelector('#rt_new').value.trim(); if (!name) return;
            const j = await post(`/hotel/${S.hotel.id}/room_type`, { name }); toast(j.status === 200 ? 'success' : 'error', j.message); selectHotel(S.hotel.id, true);
        });
        box.querySelectorAll('[data-save]').forEach(b => b.addEventListener('click', async () => {
            const tr = b.closest('tr'); const body = { id: tr.dataset.id };
            tr.querySelectorAll('[data-f]').forEach(i => body[i.dataset.f] = i.value);
            const j = await post(`/hotel/${S.hotel.id}/room_type`, body); toast(j.status === 200 ? 'success' : 'error', j.message); selectHotel(S.hotel.id, true);
        }));
    }

    // ---------------- hotel form ----------------
    function renderHotelForm() {
        const h = S.hotel; const box = document.getElementById('hotel_box');
        const f = (label, key, type, extra) => `<div class="col-md-4"><label class="form-label">${label}</label><input type="${type || 'text'}" class="form-control form-control-sm" data-h="${key}" value="${esc(val(h[key]))}" ${extra || ''}></div>`;
        // tf-hotelgrade-r1: the star rating and the hotel's grade are chosen from a list, not typed. A word
        // already on the record that is not on the list is kept on it, marked, so a save never loses it.
        const pick = (label, key, list) => {
            const cur = val(h[key]); const all = list.slice();
            if (cur !== '' && !all.includes(cur)) { all.push(cur); }
            return `<div class="col-md-4"><label class="form-label">${label}</label>`
                 + `<select class="form-select form-select-sm" data-h="${key}"><option value="">— not set —</option>`
                 + all.map(v => `<option value="${esc(v)}"${v === cur ? ' selected' : ''}>${esc(v)}${list.includes(v) ? '' : ' (as it was typed)'}</option>`).join('')
                 + '</select></div>';
        };
        box.innerHTML = `<div class="row g-2">
            <div class="col-md-4"><label class="form-label">Hotel code</label><div class="form-control form-control-sm" style="background:#f8f9fb">${h.hotel_code ? `<span class="hcode">${esc(h.hotel_code)}</span> <span class="help">permanent — given by the system, kept through renames and archiving</span>` : '<span class="help">given by the system when the record is saved</span>'}</div></div>
            ${f('Hotel name', 'name', 'text', CAN_RENAME ? '' : 'disabled title="Renamed by an approver or Management"')}<div class="col-md-4"><label class="form-label">Location</label><input class="form-control form-control-sm" data-h="location_name" list="dl_locations" value="${esc(val(h.location_name))}"><datalist id="dl_locations">${(S.detail.locations || []).map(l => `<option value="${esc(l.name)}"></option>`).join('')}</datalist></div>${f('Chain / management company', 'chain')}
            ${pick('Star category', 'star_category', ['1 star','2 star','3 star','4 star','5 star','Unrated'])}${pick('Hotel grade', 'grade', ['Standard','Superior','Deluxe','Luxury'])}${f('Address', 'address')}
            ${f('Website', 'website')}
            ${f('Reservations e-mail', 'reservations_email')}${f('Reservations phone', 'reservations_phone')}${f('Sales contact', 'sales_contact')}
            ${f('Accounts e-mail', 'accounts_email')}${f('Check-in time', 'check_in_time', 'text', 'placeholder="14:00"')}${f('Check-out time', 'check_out_time', 'text', 'placeholder="12:00"')}
            ${f('Currency', 'currency', 'text', 'maxlength="3"')}<div class="col-md-4"><label class="form-label">Taxes in rates</label>${sel({ unknown: 'unknown', inclusive: 'included', exclusive: 'added on top' }, h.tax_treatment, 'class="form-select form-select-sm" data-h="tax_treatment"')}</div>${f('Tax note', 'tax_note', 'text', 'placeholder="SC 10%, VAT 18%, SSCL 2.5%"')}
            <div class="col-md-4"><label class="form-label">Status</label>${sel({ active: 'active', inactive: 'inactive' }, h.status, 'class="form-select form-select-sm" data-h="status"' + (CAN_MANAGE ? '' : ' disabled title="Management archives or restores a hotel from the ⋯ Manage menu"'))}${CAN_MANAGE ? '' : '<div class="help">Archived or restored by Management (⋯ Manage)</div>'}</div>
            <div class="col-md-8"><label class="form-label">Supplier accounts <span class="help">(ledger, 6-9-11-…)</span></label><div class="form-control form-control-sm mono" style="background:#f8f9fb">${h.accounts.map(a => a.sup_acc_no + (a.is_primary == 1 ? ' (primary)' : '') + (a.legacy_name ? ' · ' + esc(a.legacy_name) : '')).join('<br>')}</div></div>
            <div class="col-md-6"><label class="form-label">Bank details</label><textarea class="form-control" rows="3" data-h="bank_details">${esc(val(h.bank_details))}</textarea></div>
            <div class="col-md-6"><label class="form-label">Notes</label><textarea class="form-control" rows="3" data-h="notes">${esc(val(h.notes))}</textarea></div>
            ${S.detail.transfer_fee_on ? (() => { const tf = S.detail.transfer_fee || {}; const tv = k => tf[k] == null ? '' : tf[k]; return `
            <div class="col-12 mt-2"><div style="border-top:1px solid #eef0f3;padding-top:8px"><b>Airport pick-up &amp; drop by the hotel</b> <span class="help">— charged on this hotel's bill when the hotel takes the clients from or to the airport instead of our car. Used by the inquiry when Arrival or Departure is set to "Hotel transfer".</span></div></div>
            <div class="col-md-2"><label class="form-label">Airport pick-up fee</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-h="tf_pickup_amount" value="${esc(tv('pickup_amount'))}" placeholder="not set"></div>
            <div class="col-md-2"><label class="form-label">Airport drop fee</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-h="tf_drop_amount" value="${esc(tv('drop_amount'))}" placeholder="not set"></div>
            <div class="col-md-2"><label class="form-label">Currency</label>${sel({ USD: 'USD', LKR: 'LKR' }, tf.currency || 'USD', 'class="form-select form-select-sm" data-h="tf_currency"')}</div>
            <div class="col-md-2"><label class="form-label">Charged</label>${sel({ vehicle: 'per vehicle', person: 'per person' }, tf.basis || 'vehicle', 'class="form-select form-select-sm" data-h="tf_basis"')}</div>
            <div class="col-md-2"><label class="form-label">People per vehicle</label><input type="number" min="1" max="60" class="form-control form-control-sm" data-h="tf_pax_per_vehicle" value="${esc(tv('pax_per_vehicle'))}" placeholder="all in one"></div>
            <div class="col-md-2"><label class="form-label">Transfer note</label><input class="form-control form-control-sm" data-h="tf_note" value="${esc(tv('note'))}" placeholder="e.g. book 24 h ahead"></div>`; })() : ''}
            </div><div class="mt-3"><button class="btn btn-primary btn-sm" id="h_save">Save hotel</button></div>`;
        box.querySelector('#h_save').addEventListener('click', async () => {
            const body = {}; box.querySelectorAll('[data-h]').forEach(i => body[i.dataset.h] = i.value);
            const j = await post(`/hotel/${S.hotel.id}/update`, body); toast(j.status === 200 ? 'success' : 'error', j.message); await loadHotels(); selectHotel(S.hotel.id, true);
        });
    }
    // ⋯ Manage on the hotel: archive (set inactive) or restore, with what depends on it shown first.
    // Nothing is deleted; the rate plans and every night on file keep their figures.
    const manageGet = path => fetch(MANAGE_BASE + path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
    const managePost = (path, body) => fetch(MANAGE_BASE + path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) }).then(r => r.json());
    document.getElementById('btn_manage')?.addEventListener('click', async e => {
        e.stopPropagation();
        const btn = e.currentTarget;
        if (!S.hotel) return;
        const j = await manageGet(`/hotel/${S.hotel.id}/dependencies`);
        if (j.status !== 200) { toast('error', j.message); return; }
        const d = j.data;
        const deps = `${d.plans_approved} approved plan(s), ${d.plans_draft} draft(s) · ${d.open_inquiry_nights} night(s) on open inquiries · ${d.desk_cards_open} open card(s) on the Hotel Desk · ${d.legacy_nights_year} tour night(s) in the last year`;
        const items = [{ title: d.name }, { label: 'Depends on it', icon: 'bi-diagram-3', hint: deps, disabled: true }];
        // hotel-dup-r1 (27 Oct 2026, Shantha): Management's three master actions — rename, remove a duplicate, delete an unused entry
        if (d.status !== 'merged') {
            items.push({ label: 'Edit hotel name…', icon: 'bi-pencil', hint: 'Corrects the name on the Hotel Master and its supplier record; logged', onClick: async () => {
                const r = await Swal.fire({ title: 'Hotel name', input: 'text', inputValue: S.hotel.name, showCancelButton: true, confirmButtonText: 'Save name', inputValidator: v => (String(v).trim() ? null : 'Type the name') });
                if (!r.isConfirmed) return; const name = String(r.value).trim().replace(/\s+/g, ' '); if (name === S.hotel.name) return;
                const sim = await api('/similar?name=' + encodeURIComponent(name) + '&exclude=' + S.hotel.id); const matches = (sim.data || []).filter(m => m.status !== 'merged');
                if (matches.length) { const ok = await Swal.fire({ icon: 'warning', title: 'A similar hotel is already on file', html: `<div style="text-align:left;font-size:13px">${similarListHtml(matches)}<br>If one of these is the same hotel, use <b>Remove duplicate</b> instead of renaming.</div>`, width: 640, showCancelButton: true, confirmButtonText: 'Rename anyway', cancelButtonText: 'Cancel' }); if (!ok.isConfirmed) return; }
                await renameHotelTo(name); } });
            items.push({ label: 'Hotel Master — remove a duplicate, archive or delete…', icon: 'bi-building-gear', hint: 'Opens the Hotel Master page for this hotel; merge, archive and delete live there with the dependency review', onClick: () => { window.location.href = `${MASTER_BASE}?hotel=${d.id}`; } });
        }
        if (d.status === 'inactive') {
            items.push({ label: 'Restore (set active)', icon: 'bi-arrow-counterclockwise', hint: 'Back in the inquiry pickers', onClick: async () => {
                const r = await managePost(`/hotel/${d.id}/restore`, {}); toast(r.status === 200 ? 'success' : 'error', r.message); await loadHotels(); selectHotel(d.id, true); } });
        } else {
            const blocked = (d.blocks_archive || []).length > 0;
            items.push({ label: 'Archive (set inactive)…', icon: 'bi-archive', danger: false, disabled: blocked, hint: blocked ? d.blocks_archive.join('; ') : 'Leaves the inquiry pickers; rates and nights on file are untouched; restorable', onClick: async () => {
                const r = await Swal.fire({ icon: 'question', title: 'Archive ' + d.name + '?', html: `<div style="text-align:left;font-size:13px">${esc(deps)}.<br><br>The hotel is set inactive: it no longer appears when a hotel is chosen on an inquiry. Its rate plans and every night already on file keep their figures. Nothing is deleted, and it can be restored.</div>`, input: 'text', inputPlaceholder: 'Why (optional, stays on the history)', showCancelButton: true, confirmButtonText: 'Archive' });
                if (!r.isConfirmed) return;
                const r2 = await managePost(`/hotel/${d.id}/archive`, { note: r.value || '' }); toast(r2.status === 200 ? 'success' : 'error', r2.message); await loadHotels(); selectHotel(d.id, true); } });
        }
        window.tdMenu(btn, items);
    });
    async function loadHistory() {
        const box = document.getElementById('history_box');
        const j = await api(`/hotel/${S.hotel.id}/audit`);
        const what = a => a.flag ? (a.flag.indexOf(':1') > 0 ? 'ticked Frequently Used' : 'unticked Frequently Used') : esc(a.action);
        const rows = (j.data || []).map(a => `<tr><td class="mono">${a.created_at}</td><td>${esc(a.user)}</td><td>${what(a)}</td></tr>`).join('');
        box.innerHTML = rows ? `<table class="hr-table"><thead><tr><th>When</th><th>Who</th><th>What</th></tr></thead><tbody>${rows}</tbody></table>` : '<div class="empty">No changes recorded yet.</div>';
    }

    // ---------------- plan editor ----------------
    function blankSheet(type) {
        const y = new Date().getFullYear();
        const winter = [{ from: `${y}-11-01`, to: `${y + 1}-04-30` }];
        const sheet = { hotel: { id: S.hotel.id, name: S.hotel.name }, contract: { id: null, name: '', plan_type: type || 'contract', rate_code: '', tier: 'all', tier_min_rooms: '', tier_max_rooms: '', tier_min_pax: '', tier_max_pax: '', priority: 0, applies_to: 'both', parent_contract_id: '', markets: [], valid_from: `${y}-11-01`, valid_to: `${y + 1}-04-30`, book_from: '', book_to: '', currency: S.hotel.currency || DEFAULT_CURRENCY, billing_currency: 'same', pricing_mode: 'per_room', default_meal_plan: '', taxes_included: 1, tax_pct: '', tax_note: S.hotel.tax_note || '', received_at: TODAY, contract_date: '', contract_type: '', hotel_reference: '', credit_facility: 'unknown', min_stay: '', max_rooms_per_booking: '', release_days: '', tentative_hold_hours: '', extra_bed_adult: '', max_children_free: '', child_policy_text: '', guide_policy_text: '', foc_policy_text: '', cancellation_text: '', payment_text: '', terms_text: '', voucher_note: '', notes: '', source_type: 'manual', status: 'draft' },
            room_types: [], seasons: [], room_supplements: [], meal_deltas: [], meal_prices: [], supplements: [], child_rates: [], guide_policies: [], foc_rules: [], offers: [], cancellation_rules: [], payment_rules: [], restrictions: [], extras: [], meta: { status: 'draft' } };
        if (type === 'tactical' || type === 'special_offer') { sheet.contract.book_to = `${y}-10-31`; }
        if (OFFERS_READY && (type === 'tactical' || type === 'special_offer' || type === 'early_bird')) {
            // tf-offers-r1: an offer of its own area — named as such, booking deadline for an early bird
            sheet.contract.priority = 10;
            sheet.contract.applies_to = 'payment';   // never a quotation rate — what the hotel is paid only
            sheet.contract.name = type === 'early_bird' ? `Early Bird offer ${y}/${String(y + 1).slice(2)}` : `Tactical rate ${y}/${String(y + 1).slice(2)}`;
            if (type === 'early_bird') { sheet.contract.book_to = `${y}-10-31`; }
            if (type === 'tactical') { sheet.contract.book_to = ''; }
        }
        // an offer carries its own rates only: gala dinners and other supplements stay those of the published contract
        if (OFFERS_READY && (type === 'tactical' || type === 'special_offer' || type === 'early_bird')) { sheet.__offer = true; }
        if (type === 'adhoc') { sheet.contract.plan_type = 'special_offer'; sheet.contract.applies_to = 'payment'; sheet.contract.priority = 10; sheet.contract.name = 'Ad hoc payment rate'; sheet.contract.notes = 'Payment-only rate agreed with the hotel: pays the hotel, never prices the customer. The margin against the contract rate stays with us.'; sheet.supplements = []; sheet.child_rates = []; }
        if (type !== 'addendum') {
            sheet.seasons = [{ id: null, name: type === 'adhoc' ? 'Ad hoc' : `Winter ${y}/${String(y + 1).slice(2)}`, kind: 'normal', min_stay: '', max_rooms: '', notes: '', dates: winter, rates: [] }];
        }
        if (type !== 'addendum' && type !== 'adhoc' && !sheet.__offer) {
            // one gala line per night listed under Settings › Gala nights, dated inside the plan's validity
            const galaName = md => ({ '12-24': 'Christmas Eve Gala Dinner', '12-25': 'Christmas Day Gala Dinner', '12-31': "New Year's Eve Gala Dinner" }[md] || `Gala dinner ${md}`);
            sheet.supplements = GALA_DATES.map(md => { const yy = `${y}-${md}` >= sheet.contract.valid_from ? y : y + 1; const dd = `${yy}-${md}`; return { type: 'gala', name: galaName(md), date_from: dd, date_to: dd, season: '', meal_plans: '', room_type: '', per: 'person', amount: '', amount_sgl: '', amount_dbl: '', amount_tpl: '', child_amount: '', child_pct: GALA_CHILD_PCT, pct_of_rate: '', compulsory: 1, note: '' }; });
            sheet.child_rates = [{ age_from: 0, age_to: 5.99, bed: 'any', meal_plan: '', amount: 0, pct: '', pct_basis: '', max_children: '', room_type: '', note: 'Sharing with parents' }];
        }
        delete sheet.__offer;
        return sheet;
    }
    async function openContract(id, editApproved) {
        if (id === null) { S.sheet = blankSheet('contract'); S.ro = false; S.section = 'overview'; renderEditor(); return; }
        const j = await api('/contract/' + id);
        if (j.status !== 200) { return toast('error', j.message); }
        S.sheet = j.data; S.sheet.issues = j.issues; S.editApproved = !!editApproved; S.moveHotel = null; S.pendingHotelName = null;
        if (window.TfView) TfView.setParams({ contract: id });   // tf-refresh-r1: the plan open in the editor
        S.ro = (S.sheet.meta.status === 'approved' && !editApproved) || S.sheet.meta.status === 'superseded' || S.sheet.meta.status === 'cancelled';
        S.section = 'overview'; renderEditor();
    }
    function closeEditor() { document.getElementById('editor').style.display = 'none'; S.sheet = null; S.moveHotel = null; S.pendingHotelName = null; if (window.TfView && TfView.param('contract')) TfView.setParams({ contract: null }); }
    document.getElementById('btn_new_contract').addEventListener('click', () => openContract(null));
    document.querySelectorAll('[data-new]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault(); const t = a.dataset.new;
        if (t === 'import') { resetImport(false); showModal('#m_import'); return; }
        if (t === 'ai') { resetImport(true); showModal('#m_import'); return; }
        S.sheet = blankSheet(t); S.ro = false; S.section = 'overview'; renderEditor();
    }));

    function renderEditor() {
        const sh = S.sheet, c = sh.contract, ro = S.ro;
        const ed = document.getElementById('editor');
        ed.style.display = '';
        const okind = OFFERS_READY ? offerKind(c) : null;
        showTab(okind || 'plans');
        const counts = issueCounts(sh.issues || []);
        const nav = SECTIONS.map(([k, label, icon]) => { const n = counts[k]; return `<a href="#" data-sec="${k}" class="${S.section === k ? 'on' : ''}"><i class="bi ${icon}"></i>${label}${n ? `<span class="n ${n.err ? 'err' : 'warn'}">${n.err || n.warn}</span>` : ''}</a>`; }).join('');
        ed.innerHTML = `<div class="hr-card-h"><h6>${c.id ? (ro ? 'Rate plan' : (S.editApproved ? 'Correcting approved plan' : 'Edit draft')) : 'New rate plan'} — ${esc(S.hotel.name)}</h6>${sh.meta && sh.meta.status ? statusPill(sh.meta.status) : ''}${sh.meta && sh.meta.parent_name ? pill('blue', 'addendum to ' + sh.meta.parent_name) : ''}
                <span class="help ms-2">${sh.meta && sh.meta.approved_by ? 'approved by ' + esc(sh.meta.approved_by) + ' on ' + (sh.meta.approved_at || '').slice(0, 10) : (sh.meta && sh.meta.entered_by ? 'entered by ' + esc(sh.meta.entered_by) : '')}</span>
                ${(CAN_APPROVE || FROM_QUEUE) && c.id && sh.meta && sh.meta.status === 'draft' ? `<a class="btn btn-sm btn-light ms-auto" href="${APPROVE_BASE}" id="ed_queue" title="Back to the approval queue"><i class="bi bi-list-check"></i> Back to approval queue</a> ${CAN_APPROVE && !ro ? `<button class="btn btn-sm btn-light" id="ed_move" title="Only when this draft was read against the wrong hotel: put it under another hotel record"><i class="bi bi-box-arrow-right"></i> Move draft…</button> ` : ''}` : ''}<button class="btn btn-sm btn-light ${(CAN_APPROVE || FROM_QUEUE) && c.id && sh.meta && sh.meta.status === 'draft' ? '' : 'ms-auto'}" id="ed_close"><i class="bi bi-x-lg"></i> Close</button></div>
            <div class="editor ${ro ? 'readonly' : ''}"><nav class="ed-nav">${nav}</nav><div class="ed-body" id="ed_body">${renderSection(S.section)}</div></div>
            ${ro ? '' : (okind ? `<div class="sticky-actions"><button class="btn btn-light" id="ed_check"><i class="bi bi-shield-check"></i> Check</button>${S.editApproved ? '<button class="btn btn-success" id="ed_save"><i class="bi bi-save"></i> Save correction — active now</button>' : '<button class="btn btn-light" id="ed_save"><i class="bi bi-save"></i> Save draft</button><button class="btn btn-success" id="ed_offer"><i class="bi bi-lightning-charge"></i> Save — active now</button>'}<span class="help ms-auto">${esc(KIND_LABEL[okind])}: active as soon as it is saved — at hotel payment an authorized person decides whether to apply it; never used for an inquiry, quotation or booking. The type of plan decides the area — Tactical or Early Bird.</span></div>`
              : `<div class="sticky-actions"><button class="btn btn-light" id="ed_check"><i class="bi bi-shield-check"></i> Check</button><button class="btn btn-primary" id="ed_save"><i class="bi bi-save"></i> ${S.editApproved ? 'Save correction' : 'Save draft'}</button>${CAN_APPROVE && !S.editApproved ? '<button class="btn btn-success" id="ed_approve"><i class="bi bi-check2-circle"></i> Save &amp; approve</button>' : ''}<span class="help ms-auto">${CAN_APPROVE ? 'Approving publishes the rates to quotations and rebuilds the old Hotel Rate rows for this hotel.' : 'Saved drafts wait in the approval queue for a user with the Hotel Rate Approval privilege.'}</span></div>`)}`;
        ed.querySelector('#ed_close').addEventListener('click', closeEditor);
        const mv = ed.querySelector('#ed_move'); if (mv) mv.addEventListener('click', () => openQueueEdit(S.hotel.id, c.id));
        ed.querySelectorAll('[data-sec]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); collect(); S.section = a.dataset.sec; renderEditor(); }));
        if (!ro) {
            ed.querySelector('#ed_check').addEventListener('click', () => checkOnly());
            ed.querySelector('#ed_save').addEventListener('click', () => save(false));
            const apBtn = ed.querySelector('#ed_approve'); if (apBtn) apBtn.addEventListener('click', () => save(true));
            const ofBtn = ed.querySelector('#ed_offer'); if (ofBtn) ofBtn.addEventListener('click', () => save(false, false, true));
        }
        bindSection();
        if (S.section === 'documents') loadPlanDocs();   // tf-hr-docs-r1
        ed.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function issueCounts(issues) {
        const map = { header: 'overview', room_types: 'overview', seasons: 'seasons', rates: 'seasons', room_supplements: 'rooms', meal_deltas: 'rooms', meal_prices: 'supplements', supplements: 'supplements', child_rates: 'children', guide_policies: 'guide', foc_rules: 'guide', offers: 'offers', restrictions: 'offers', cancellation_rules: 'cancel', payment_rules: 'cancel', extras: 'extras' };
        const out = {}; issues.forEach(i => { const k = map[i.section] || 'overview'; out[k] = out[k] || { err: 0, warn: 0 }; if (i.level === 'error') out[k].err++; else out[k].warn++; });
        const all = { err: issues.filter(i => i.level === 'error').length, warn: issues.filter(i => i.level === 'warning').length }; if (all.err || all.warn) out.issues = all;
        return out;
    }
    const roomList = () => { const names = new Set((S.detail.room_types || []).map(r => r.name)); (S.sheet.room_types || []).forEach(r => names.add(r.name)); S.sheet.seasons.forEach(s => s.rates.forEach(r => r.room_type && names.add(r.room_type))); return [...names]; };
    const roomDatalist = () => `<datalist id="dl_rooms">${roomList().map(n => `<option value="${esc(n)}"></option>`).join('')}</datalist>`;
    const seasonOpts = current => `<option value="">whole plan</option>` + S.sheet.seasons.map((s, i) => `<option value="${esc(s.name || String(i))}" ${String(current ?? '') === String(s.name || i) ? 'selected' : ''}>${esc(s.name || 'Season ' + (i + 1))}</option>`).join('');
    const d = (ro) => ro ? 'disabled' : '';
    const inp = (k, v, cls, attrs) => `<input ${attrs || ''} class="${cls || ''}" data-k="${k}" value="${esc(val(v))}" ${d(S.ro)}>`;
    const num = (k, v, cls) => `<input type="number" step="0.01" class="${cls || 'narrow'}" data-k="${k}" value="${esc(val(v))}" ${d(S.ro)}>`;
    const dateI = (k, v) => `<input type="date" class="date" data-k="${k}" value="${esc(val(v))}" ${d(S.ro)}>`;
    const rowDel = (list, i) => S.ro ? '' : `<button class="del" data-del="${list}:${i}" title="Remove">&times;</button>`;
    const addBtn = (list, label) => S.ro ? '' : `<button class="btn btn-sm btn-light mt-2" data-add="${list}"><i class="bi bi-plus"></i> ${label}</button>`;
    const table = (head, body, cls) => `<div class="table-responsive"><table class="hr-table grid ${cls || ''}"><thead><tr>${head.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${body}</tbody></table></div>`;

    function renderSection(sec) {
        const sh = S.sheet, c = sh.contract, ro = S.ro;
        const f = (label, key, type, extra, col) => `<div class="${col || 'col-md-3 col-6'}"><label class="form-label">${label}</label><input type="${type || 'text'}" class="form-control form-control-sm" data-c="${key}" value="${esc(val(c[key]))}" ${extra || ''} ${d(ro)}></div>`;
        const fs = (label, key, opts, col, blank) => `<div class="${col || 'col-md-3 col-6'}"><label class="form-label">${label}</label>${sel(opts, c[key], `class="form-select form-select-sm" data-c="${key}" ${d(ro)}`, blank)}</div>`;
        const ta = (label, key, rows, col) => `<div class="${col || 'col-md-6'}"><label class="form-label">${label}</label><textarea class="form-control" rows="${rows || 2}" data-c="${key}" ${d(ro)}>${esc(val(c[key]))}</textarea></div>`;
        if (sec === 'documents') {   // tf-hr-docs-r1
            return `<h6>Documents</h6><div class="ed-lead">The original rate sheet, contract or e-mail this plan was entered from — the approver opens it beside the figures. Attach it once; it stays with the plan.</div><div id="ed_docs"><div class="empty">Loading…</div></div>`;
        }
        if (sec === 'overview') {
            const parents = (S.detail.contracts || []).filter(p => p.id !== c.id && p.status !== 'cancelled').map(p => `<option value="${p.id}" ${String(c.parent_contract_id || '') === String(p.id) ? 'selected' : ''}>${esc(p.name)} (${p.status})</option>`).join('');
            const marketChips = Object.entries(MARKETS).filter(([k]) => k !== 'ALL').map(([k, v]) => { const m = (c.markets || []).find(x => x.code === k); const cls = m ? (m.mode === 'exclude' ? 'on' : 'on') : ''; return `<span class="chip ${cls}" data-mk="${k}" style="${m && m.mode === 'exclude' ? 'background:#b23a32;border-color:#b23a32' : ''}" title="${esc(v)} — click: include, click again: exclude, third click: off">${k}${m && m.mode === 'exclude' ? ' ✕' : ''}</span>`; }).join('');
            return `<h6>Overview</h6><div class="ed-lead">Who this plan is for and when it applies. Several plans may cover the same dates: markets, tier, booking window and priority decide which one a quotation uses.</div>
                <div class="fieldset"><div class="legend">Identity</div><div class="row g-2">
                    ${!ro ? `<div class="col-md-6"><label class="form-label">Hotel name <span class="help">${CAN_RENAME ? '— the name on the Hotel Master; a spelling mistake corrected here is corrected everywhere' : '— corrected by Management or an approver'}</span></label><input class="form-control form-control-sm" id="ed_hotel_name" value="${esc(S.pendingHotelName != null ? S.pendingHotelName : S.hotel.name)}" ${CAN_RENAME ? '' : 'disabled'}></div><div class="col-md-6"><label class="form-label">Hotel code</label><div class="form-control form-control-sm" style="background:#f8f9fb">${esc(S.hotel.hotel_code || '')} <span class="help">${esc(S.hotel.location_name || '')}</span></div></div>` : ''}
                    ${f('Plan name', 'name', 'text', 'placeholder="e.g. Winter 2026/27 travel agent rates"', 'col-md-6')}${fs('Plan type', 'plan_type', OPT.plan_type)}${f('Rate code (from the sheet)', 'rate_code', 'text', 'placeholder="HA/W26_27/AME/01"')}
                    <div class="col-md-6"><label class="form-label">Parent plan (addendum / offer inherits everything it does not define)</label><select class="form-select form-select-sm" data-c="parent_contract_id" ${d(ro)}><option value="">— none —</option>${parents}</select></div>
                    ${f('Priority (higher wins on the same night)', 'priority', 'number')}${fs('Source', 'source_type', { manual: 'Typed in', excel: 'Excel', ai: 'PDF reader', email: 'E-mail', migration: 'Migrated' })}
                    ${fs('Use for', 'applies_to', OPT.applies_to)}<div class="col-md-6"><div class="help mt-4">${(c.applies_to || 'both') === 'payment' ? '<b>Payments only:</b> this rate pays the hotel and is never shown to or used for the customer. The quotation keeps the contract rate; the difference is our margin.' : (c.applies_to || 'both') === 'quotation' ? 'Prices quotations only; hotel payments fall back to the other plans.' : 'Prices quotations and, unless an ad hoc rate exists, the hotel payment.'}</div></div>
                </div></div>
                <div class="fieldset"><div class="legend">Stay dates & booking window</div><div class="row g-2">
                    ${f('Valid from (stay)', 'valid_from', 'date')}${f('Valid to (stay)', 'valid_to', 'date')}${f('Book from', 'book_from', 'date')}${f('Book before / until', 'book_to', 'date')}
                    ${f('Minimum stay (whole plan)', 'min_stay', 'number')}${f('Max rooms per booking', 'max_rooms_per_booking', 'number', 'placeholder="above → contact hotel"')}${f('Release days', 'release_days', 'number')}${f('Tentative hold (hours)', 'tentative_hold_hours', 'number')}
                </div></div>
                <div class="fieldset"><div class="legend">Markets & tier</div>
                    <div class="help mb-1">No market selected = all markets. Click a market once to include it, twice to exclude it ("Asia excluding China / Japan"), a third time to clear.</div>
                    <div class="chips mb-2" id="market_chips">${marketChips}</div>
                    <div class="row g-2">${fs('Tier', 'tier', OPT.tier)}${f('Min rooms', 'tier_min_rooms', 'number')}${f('Max rooms', 'tier_max_rooms', 'number')}${f('Min pax', 'tier_min_pax', 'number')}${f('Max pax', 'tier_max_pax', 'number')}</div>
                </div>
                <div class="fieldset"><div class="legend">Pricing & tax</div><div class="row g-2">
                    ${fs('Rates are', 'pricing_mode', OPT.pricing_mode)}${f('Currency', 'currency', 'text', 'maxlength="3"')}${fs('Billing', 'billing_currency', OPT.billing_currency)}<div class="col-md-3 col-6"><label class="form-label">Default meal plan (if the sheet gives none)</label>${mealSel(c.default_meal_plan, `class="form-select form-select-sm" data-c="default_meal_plan" ${d(ro)}`, true)}</div>
                    ${fs('Taxes', 'taxes_included', { 1: 'included in rates', 0: 'added on top' })}${f('Tax %', 'tax_pct', 'number', 'placeholder="18 / 34.34"')}${f('Tax note', 'tax_note', 'text', 'placeholder="SC 10%, VAT 18%, SSCL 2.5%"', 'col-md-6')}
                </div></div>
                <div class="fieldset"><div class="legend">Contract details</div><div class="row g-2">
                    ${f('Contract / issue date', 'contract_date', 'date')}${f('Received on', 'received_at', 'date')}${f('Contract type', 'contract_type', 'text', 'placeholder="DMC, LTA, wholesale…"')}${f("Hotel's reference / VAT no.", 'hotel_reference')}${fs('Credit facility', 'credit_facility', OPT.credit_facility)}${f('Source file', 'source_file', 'text', '', 'col-md-6')}
                </div></div>
                <div class="fieldset"><div class="legend">Policies as written (kept for reference; the structured rules are on the other pages)</div><div class="row g-2">
                    ${ta('Child policy', 'child_policy_text')}${ta('Guide / driver policy', 'guide_policy_text')}${ta('FOC policy', 'foc_policy_text')}${ta('Cancellation', 'cancellation_text')}${ta('Payment terms', 'payment_text')}${ta('Voucher must mention', 'voucher_note')}${ta('Other terms & conditions', 'terms_text', 3, 'col-12')}${ta('Notes / things to clarify with the hotel', 'notes', 2, 'col-12')}
                </div></div>`;
        }
        if (sec === 'seasons') {
            const seasonHtml = (s, si) => `<div class="season" data-si="${si}">
                <div class="season-h">
                    <div class="f"><label>Season name</label><input data-s="name" value="${esc(val(s.name))}" style="width:220px" ${d(ro)}></div>
                    <div class="f"><label>Kind</label>${sel(OPT.season_kind, s.kind, `data-s="kind" ${d(ro)}`)}</div>
                    <div class="f"><label>Min stay</label><input type="number" data-s="min_stay" value="${val(s.min_stay)}" style="width:70px" ${d(ro)}></div>
                    <div class="f"><label>Max rooms</label><input type="number" data-s="max_rooms" value="${val(s.max_rooms)}" style="width:80px" ${d(ro)} title="Tactical offers often cap rooms per period"></div>
                    <div class="f" style="flex:1"><label>Notes</label><input data-s="notes" value="${esc(val(s.notes))}" ${d(ro)}></div>
                    ${ro ? '' : `<div class="d-flex gap-1"><button class="btn btn-sm btn-light" data-addrow="${si}">+ row</button><button class="btn btn-sm btn-light" data-fill="${si}" title="One row per room type and meal plan">Fill grid</button><button class="btn btn-sm btn-light" data-paste="${si}">Paste</button><button class="btn btn-sm btn-light" data-copys="${si}">Copy season</button><button class="btn btn-sm btn-danger-soft" data-dels="${si}">Remove</button></div>`}
                </div>
                <div class="date-chips"><span class="help">Dates:</span>${s.dates.map((dt, di) => `<span class="date-chip" data-di="${di}"><input type="date" data-d="from" value="${esc(val(dt.from))}" ${d(ro)}> → <input type="date" data-d="to" value="${esc(val(dt.to))}" ${d(ro)}>${ro ? '' : `<button data-deld="${si}:${di}" title="Remove this range">&times;</button>`}</span>`).join('')}${ro ? '' : `<button class="btn btn-sm btn-ghost" data-addd="${si}">+ date range</button><span class="help">a season can have several ranges (e.g. 01 Nov–20 Dec and 01–30 Apr)</span>`}</div>
                ${table(['Room type', 'Meal', 'SGL', 'DBL', 'TPL', 'QUAD', 'UNIT', 'Child + bed', 'Child no bed', 'Note', ''], s.rates.map((r, ri) => `<tr data-ri="${ri}">
                    <td><input class="wide" list="dl_rooms" data-r="room_type" value="${esc(val(r.room_type))}" ${d(ro)}></td>
                    <td>${mealSel(r.meal_plan, `data-r="meal_plan" ${d(ro)}`)}</td>
                    ${['sgl', 'dbl', 'tpl', 'quad', 'unit', 'child_with_bed', 'child_no_bed'].map(k => `<td><input type="number" step="0.01" class="narrow" data-r="${k}" value="${esc(val(r[k]))}" ${d(ro)}></td>`).join('')}
                    <td><input data-r="note" value="${esc(val(r.note))}" ${d(ro)}></td>
                    <td>${r.quality_flags ? pill('amber', r.quality_flags) : ''}${ro ? '' : `<button class="del" data-delrow="${si}:${ri}">&times;</button>`}</td></tr>`).join('') || `<tr><td colspan="11" class="help">No rate rows yet — add rows, fill the grid or paste from Excel.</td></tr>`)}
            </div>`;
            return `<h6>Seasons & rates</h6><div class="ed-lead">One block per season. Blank = not offered (never 0). Rooms priced as base + supplement do <b>not</b> get rows here — enter the base room and put the supplement on the next page. ${c.pricing_mode === 'per_person' ? '<b>This plan is per person:</b> SGL = single, DBL = per person sharing.' : c.pricing_mode === 'per_unit' ? '<b>This plan is per unit:</b> fill the UNIT column.' : ''}</div>
                ${roomDatalist()}<div id="seasons">${sh.seasons.map(seasonHtml).join('')}</div>${addBtn('seasons', 'Add season')}`;
        }
        if (sec === 'rooms') {
            return `<h6>Room supplements & meal deltas</h6><div class="ed-lead">"Deluxe to Club Suite USD 40 per room per night" and "BB reduction USD 5 per person on HB" style rules. The engine derives every combination the sheet does not print directly.</div>
                ${roomDatalist()}
                <div class="fieldset"><div class="legend">Room supplements (added to the base room's rate)</div>
                ${table(['Room type', 'Season', 'Amount (may be negative)', 'Per', 'Note', ''], sh.room_supplements.map((r, i) => `<tr data-i="${i}"><td><input class="wide" list="dl_rooms" data-k="room_type" value="${esc(val(r.room_type))}" ${d(ro)}></td><td><select data-k="season" ${d(ro)}>${seasonOpts(r.season)}</select></td><td>${num('amount', r.amount)}</td><td>${sel(OPT.rs_per, r.per, `data-k="per" ${d(ro)}`)}</td><td>${inp('note', r.note)}</td><td>${rowDel('room_supplements', i)}</td></tr>`).join(''))}
                ${addBtn('room_supplements', 'Add room supplement')}
                <div class="help mt-2">The base room of each supplement room is set on the Room types tab (it is filled automatically the first time you save).</div></div>
                <div class="fieldset"><div class="legend">Meal deltas (per person per night; reductions negative)</div>
                ${table(['From', 'To', 'Adult', 'Child', 'Season', 'Note', ''], sh.meal_deltas.map((r, i) => `<tr data-i="${i}"><td>${mealSel(r.from, `data-k="from" ${d(ro)}`)}</td><td>${mealSel(r.to, `data-k="to" ${d(ro)}`)}</td><td>${num('adult', r.adult)}</td><td>${num('child', r.child)}</td><td><select data-k="season" ${d(ro)}>${seasonOpts(r.season)}</select></td><td>${inp('note', r.note)}</td><td>${rowDel('meal_deltas', i)}</td></tr>`).join(''))}
                ${addBtn('meal_deltas', 'Add meal delta')}
                <div class="help mt-2">Examples: HB → BB −5 / child −2.5 (BB reduction), HB → FB +25, BB → AI +100 (all-inclusive package on BB), BB → HB +25 (half-board add-on).</div></div>`;
        }
        if (sec === 'supplements') {
            return `<h6>Supplements & meal prices</h6><div class="ed-lead">Gala dinners by basis, peak / August / Perahera surcharges, extra adult or 4th person, early check-in and late check-out. Compulsory supplements are added to quotations automatically for the nights they cover.</div>
                ${roomDatalist()}
                ${table(['Type', 'Name', 'From', 'To', 'or season', 'Meal plans', 'Room type', 'Per', 'Amount', 'SGL', 'DBL', 'TPL', 'Child amt', 'Child %', '% of rate', 'Comp.', 'Note', ''], sh.supplements.map((r, i) => `<tr data-i="${i}">
                    <td>${sel(OPT.sup_type, r.type, `data-k="type" ${d(ro)}`)}</td><td>${inp('name', r.name, 'wide')}</td><td>${dateI('date_from', r.date_from)}</td><td>${dateI('date_to', r.date_to)}</td><td><select data-k="season" ${d(ro)}>${seasonOpts(r.season)}</select></td>
                    <td>${inp('meal_plans', r.meal_plans, 'narrow', 'placeholder="all"')}</td><td><input list="dl_rooms" data-k="room_type" value="${esc(val(r.room_type))}" placeholder="all" ${d(ro)}></td><td>${sel(OPT.sup_per, r.per, `data-k="per" ${d(ro)}`)}</td>
                    <td>${num('amount', r.amount)}</td><td>${num('amount_sgl', r.amount_sgl)}</td><td>${num('amount_dbl', r.amount_dbl)}</td><td>${num('amount_tpl', r.amount_tpl)}</td><td>${num('child_amount', r.child_amount)}</td><td>${num('child_pct', r.child_pct)}</td><td>${num('pct_of_rate', r.pct_of_rate)}</td>
                    <td><input type="checkbox" data-k="compulsory" ${Number(r.compulsory) ? 'checked' : ''} ${d(ro)}></td><td>${inp('note', r.note)}</td><td>${rowDel('supplements', i)}</td></tr>`).join(''))}
                ${addBtn('supplements', 'Add supplement')}
                <div class="fieldset mt-3"><div class="legend">Extra meal prices (per person)</div>
                ${table(['Meal', 'Name', 'Adult', 'Child', 'Note', ''], sh.meal_prices.map((r, i) => `<tr data-i="${i}"><td>${sel(OPT.meal, r.meal, `data-k="meal" ${d(ro)}`)}</td><td>${inp('name', r.name)}</td><td>${num('adult', r.adult)}</td><td>${num('child', r.child)}</td><td>${inp('note', r.note)}</td><td>${rowDel('meal_prices', i)}</td></tr>`).join(''))}
                ${addBtn('meal_prices', 'Add meal price')}</div>`;
        }
        if (sec === 'children') {
            return `<h6>Children & extra beds</h6><div class="ed-lead">One rule per age band, with or without an extra bed, per meal plan when the sheet differs. Amount 0 = free of charge. "50% of the meal rate" → % 50 with basis meal rate. Agents ask for both child-with-bed and child-without-bed prices.</div>
                ${roomDatalist()}
                <div class="row g-2 mb-3">${f('Extra bed (adult) per night', 'extra_bed_adult', 'number')}${f('Max free children per room', 'max_children_free', 'number')}</div>
                ${table(['Age from', 'Age to', 'Bed', 'Meal plan', 'Amount', '%', 'of', 'Max children', 'Room type', 'Note', ''], sh.child_rates.map((r, i) => `<tr data-i="${i}"><td>${num('age_from', r.age_from)}</td><td>${num('age_to', r.age_to)}</td><td>${sel(OPT.bed, r.bed, `data-k="bed" ${d(ro)}`)}</td><td>${mealSel(r.meal_plan, `data-k="meal_plan" ${d(ro)}`, true)}</td><td>${num('amount', r.amount)}</td><td>${num('pct', r.pct)}</td><td>${sel(OPT.pct_basis, r.pct_basis, `data-k="pct_basis" ${d(ro)}`)}</td><td>${num('max_children', r.max_children)}</td><td><input list="dl_rooms" data-k="room_type" value="${esc(val(r.room_type))}" placeholder="all" ${d(ro)}></td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('child_rates', i)}</td></tr>`).join(''))}
                ${addBtn('child_rates', 'Add child rule')}`;
        }
        if (sec === 'guide') {
            return `<h6>Guide, driver & FOC</h6><div class="ed-lead">Guide / driver / tour-leader room rules and free-of-charge thresholds.</div>
                <div class="fieldset"><div class="legend">Guide & driver rooms</div>
                ${table(['Role', 'Meal plan', 'Amount', 'Currency', '% of', '%', 'Per', 'Free from pax', 'Min rooms', 'Accommodation', 'Note', ''], sh.guide_policies.map((r, i) => `<tr data-i="${i}"><td>${sel(OPT.role, r.role, `data-k="role" ${d(ro)}`)}</td><td>${mealSel(r.meal_plan, `data-k="meal_plan" ${d(ro)}`, true)}</td><td>${num('amount', r.amount)}</td><td>${inp('currency', r.currency, 'narrow', 'maxlength="3" placeholder="USD"')}</td><td>${sel(OPT.pct_of, r.pct_of, `data-k="pct_of" ${d(ro)}`)}</td><td>${num('pct', r.pct)}</td><td>${sel({ room: 'per room', person: 'per person' }, r.per, `data-k="per" ${d(ro)}`)}</td><td>${num('free_from_pax', r.free_from_pax)}</td><td>${num('min_rooms', r.min_rooms)}</td><td>${sel(OPT.accommodation, r.accommodation, `data-k="accommodation" ${d(ro)}`)}</td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('guide_policies', i)}</td></tr>`).join(''))}
                ${addBtn('guide_policies', 'Add guide rule')}</div>
                <div class="fieldset"><div class="legend">FOC rules</div>
                ${table(['Every N', 'basis', 'Free', 'Discount %', 'Given as', 'Note', ''], sh.foc_rules.map((r, i) => `<tr data-i="${i}"><td>${num('every_n', r.every_n)}</td><td>${sel(OPT.foc_basis, r.basis, `data-k="basis" ${d(ro)}`)}</td><td>${num('free_n', r.free_n)}</td><td>${num('discount_pct', r.discount_pct)}</td><td>${sel(OPT.foc_as, r.as_what, `data-k="as_what" ${d(ro)}`)}</td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('foc_rules', i)}</td></tr>`).join(''))}
                ${addBtn('foc_rules', 'Add FOC rule')}
                <div class="help mt-2">"Every 15 paying pax the 16th is free" → every 15 paying pax, free 1, 100%. "Every 7th paid room the 8th at 50%" → every 7 paid rooms, free 1, 50%.</div></div>`;
        }
        if (sec === 'offers') {
            return `<h6>Offers & restrictions</h6><div class="ed-lead">Early-booking, long-stay, honeymoon and group reductions (informational for now), and date-bound restrictions the quotation must respect.</div>
                <div class="fieldset"><div class="legend">Offers</div>
                ${table(['Kind', 'Name', 'Book from', 'Book to', 'Stay from', 'Stay to', 'Min nights', 'Min pax', 'Min rooms', 'Value %', 'Amount', 'Free nights', 'Per', 'Combinable', 'Excl. peak', 'Conditions', ''], sh.offers.map((r, i) => `<tr data-i="${i}"><td>${sel(OPT.offer_kind, r.kind, `data-k="kind" ${d(ro)}`)}</td><td>${inp('name', r.name, 'wide')}</td><td>${dateI('book_from', r.book_from)}</td><td>${dateI('book_to', r.book_to)}</td><td>${dateI('stay_from', r.stay_from)}</td><td>${dateI('stay_to', r.stay_to)}</td><td>${num('min_nights', r.min_nights)}</td><td>${num('min_pax', r.min_pax)}</td><td>${num('min_rooms', r.min_rooms)}</td><td>${num('value_pct', r.value_pct)}</td><td>${num('value_amount', r.value_amount)}</td><td>${num('free_nights', r.free_nights)}</td><td>${sel(OPT.offer_per, r.per, `data-k="per" ${d(ro)}`)}</td><td><input type="checkbox" data-k="combinable" ${Number(r.combinable) ? 'checked' : ''} ${d(ro)}></td><td><input type="checkbox" data-k="excludes_peak" ${Number(r.excludes_peak) ? 'checked' : ''} ${d(ro)}></td><td>${inp('conditions', r.conditions, 'wide')}</td><td>${rowDel('offers', i)}</td></tr>`).join(''))}
                ${addBtn('offers', 'Add offer')}</div>
                <div class="fieldset"><div class="legend">Restrictions</div>
                ${table(['Kind', 'From', 'To', 'Value', 'Note', ''], sh.restrictions.map((r, i) => `<tr data-i="${i}"><td>${sel(OPT.restriction, r.kind, `data-k="kind" ${d(ro)}`)}</td><td>${dateI('date_from', r.date_from)}</td><td>${dateI('date_to', r.date_to)}</td><td>${inp('value', r.value, '', 'placeholder="2 nights / HB,FB,AI"')}</td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('restrictions', i)}</td></tr>`).join(''))}
                ${addBtn('restrictions', 'Add restriction')}
                <div class="help mt-2">Minimum stay → value = nights. Meal plan required (24 & 31 Dec only HB/FB/AI) → value = HB,FB,AI. No check-out → the date.</div></div>`;
        }
        if (sec === 'cancel') {
            return `<h6>Cancellation & payment</h6><div class="ed-lead">Enter the matrix exactly as the sheet prints it: one row per band, per stay period and per FIT / group (or room / pax limits). "Days from" is the larger number of days before arrival.</div>
                <div class="fieldset"><div class="legend">Cancellation rules</div>
                ${table(['Stay from', 'Stay to', 'Tier', 'Min rooms', 'Max rooms', 'Min pax', 'Max pax', 'Days from', 'Days to', 'Charge %', 'or nights', 'Note', ''], sh.cancellation_rules.map((r, i) => `<tr data-i="${i}"><td>${dateI('date_from', r.date_from)}</td><td>${dateI('date_to', r.date_to)}</td><td>${sel(OPT.cancel_tier, r.tier, `data-k="tier" ${d(ro)}`)}</td><td>${num('min_rooms', r.min_rooms)}</td><td>${num('max_rooms', r.max_rooms)}</td><td>${num('min_pax', r.min_pax)}</td><td>${num('max_pax', r.max_pax)}</td><td>${num('days_from', r.days_from)}</td><td>${num('days_to', r.days_to)}</td><td>${num('charge_pct', r.charge_pct)}</td><td>${num('charge_nights', r.charge_nights)}</td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('cancellation_rules', i)}</td></tr>`).join(''))}
                ${addBtn('cancellation_rules', 'Add band')} ${ro ? '' : `<button class="btn btn-sm btn-light mt-2" data-tpl="cancel3">Add the usual 3 bands</button>`}</div>
                <div class="fieldset"><div class="legend">Payment rules</div>
                ${table(['Applies to', 'Kind', 'Days before', '%', 'From', 'To', 'Note', ''], sh.payment_rules.map((r, i) => `<tr data-i="${i}"><td>${sel(OPT.pay_applies, r.applies, `data-k="applies" ${d(ro)}`)}</td><td>${sel(OPT.pay_kind, r.kind, `data-k="kind" ${d(ro)}`)}</td><td>${num('days_before', r.days_before)}</td><td>${num('pct', r.pct)}</td><td>${dateI('date_from', r.date_from)}</td><td>${dateI('date_to', r.date_to)}</td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('payment_rules', i)}</td></tr>`).join(''))}
                ${addBtn('payment_rules', 'Add payment rule')}</div>`;
        }
        if (sec === 'extras') {
            return `<h6>Extras</h6><div class="ed-lead">Excursions, transfers, MICE and wellness prices printed on the sheet. Reference only — they do not enter room pricing.</div>
                ${table(['Category', 'Name', 'Per', 'Adult', 'Child', 'Currency', 'Note', ''], sh.extras.map((r, i) => `<tr data-i="${i}"><td>${sel(OPT.extra_cat, r.category, `data-k="category" ${d(ro)}`)}</td><td>${inp('name', r.name, 'wide')}</td><td>${sel(OPT.extra_per, r.per, `data-k="per" ${d(ro)}`)}</td><td>${num('adult', r.adult)}</td><td>${num('child', r.child)}</td><td>${inp('currency', r.currency, 'narrow', 'maxlength="3"')}</td><td>${inp('note', r.note, 'wide')}</td><td>${rowDel('extras', i)}</td></tr>`).join(''))}
                ${addBtn('extras', 'Add extra')}`;
        }
        if (sec === 'issues') {
            const issues = sh.issues || [];
            const cnt = { error: 0, warning: 0 }; issues.forEach(i => cnt[i.level]++);
            return `<h6>Checks</h6><div class="ed-lead">${issues.length ? `<b>${cnt.error} error(s)</b> block approval; <b>${cnt.warning} warning(s)</b> must be confirmed. Click a line to jump to it.` : 'Press <b>Check</b> to validate the plan against the sheet rules and the other plans of this hotel.'}</div>
                <div id="issue_list">${issues.map(i => `<div class="issue ${i.level}" data-goto="${i.section || 'header'}"><b>${i.code}</b>${esc(i.message)}</div>`).join('') || (sh.issues ? '<div class="issue" style="background:var(--hr-green-soft);color:var(--hr-green)">No issues found.</div>' : '')}</div>`;
        }
        return '';
    }

    const blankRow = {
        seasons: () => ({ id: null, name: '', kind: 'normal', min_stay: '', max_rooms: '', notes: '', dates: [{ from: '', to: '' }], rates: [] }),
        room_supplements: () => ({ room_type: '', season: '', amount: '', per: 'room', note: '' }),
        meal_deltas: () => ({ from: 'HB', to: 'BB', adult: '', child: '', season: '', note: '' }),
        meal_prices: () => ({ meal: 'lunch', name: '', adult: '', child: '', note: '' }),
        supplements: () => ({ type: 'gala', name: '', date_from: '', date_to: '', season: '', meal_plans: '', room_type: '', per: 'person', amount: '', amount_sgl: '', amount_dbl: '', amount_tpl: '', child_amount: '', child_pct: '', pct_of_rate: '', compulsory: 1, note: '' }),
        child_rates: () => ({ age_from: '', age_to: '', bed: 'any', meal_plan: '', amount: '', pct: '', pct_basis: '', max_children: '', room_type: '', note: '' }),
        guide_policies: () => ({ role: 'guide', meal_plan: '', amount: '', currency: (S.sheet && S.sheet.contract.currency) || DEFAULT_CURRENCY, pct_of: '', pct: '', per: 'room', free_from_pax: '', min_rooms: '', accommodation: 'unknown', note: '' }),
        foc_rules: () => ({ basis: 'pax', every_n: 15, free_n: 1, discount_pct: 100, as_what: 'any', note: '' }),
        offers: () => ({ kind: 'other', name: '', book_from: '', book_to: '', stay_from: '', stay_to: '', min_nights: '', min_pax: '', min_rooms: '', value_pct: '', value_amount: '', free_nights: '', per: 'room', combinable: 0, excludes_peak: 0, conditions: '' }),
        cancellation_rules: () => ({ date_from: '', date_to: '', tier: 'all', min_rooms: '', max_rooms: '', min_pax: '', max_pax: '', days_from: '', days_to: 0, charge_pct: '', charge_nights: '', note: '' }),
        payment_rules: () => ({ applies: 'all', kind: 'prepay', days_before: '', pct: '', date_from: '', date_to: '', note: '' }),
        restrictions: () => ({ kind: 'min_stay', date_from: '', date_to: '', value: '', note: '' }),
        extras: () => ({ category: 'excursion', name: '', per: 'person', adult: '', child: '', currency: '', note: '' }),
    };
    function bindSection() {
        const body = document.getElementById('ed_body'); if (!body) return;
        body.querySelectorAll('[data-add]').forEach(b => b.addEventListener('click', () => { collect(); S.sheet[b.dataset.add].push(blankRow[b.dataset.add]()); renderEditor(); }));
        body.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => { collect(); const [l, i] = b.dataset.del.split(':'); S.sheet[l].splice(Number(i), 1); renderEditor(); }));
        body.querySelectorAll('[data-tpl]').forEach(b => b.addEventListener('click', () => { collect(); S.sheet.cancellation_rules.push({ ...blankRow.cancellation_rules(), days_from: '', days_to: 30, charge_pct: 0, note: 'free cancellation' }, { ...blankRow.cancellation_rules(), days_from: 29, days_to: 15, charge_pct: 50 }, { ...blankRow.cancellation_rules(), days_from: 14, days_to: 0, charge_pct: 100 }); renderEditor(); }));
        body.querySelectorAll('[data-mk]').forEach(ch => ch.addEventListener('click', () => {
            if (S.ro) return; collect(); const code = ch.dataset.mk; const list = S.sheet.contract.markets || []; const idx = list.findIndex(m => m.code === code);
            if (idx < 0) list.push({ code, mode: 'include' }); else if (list[idx].mode === 'include') list[idx].mode = 'exclude'; else list.splice(idx, 1);
            S.sheet.contract.markets = list; renderEditor();
        }));
        body.querySelectorAll('[data-goto]').forEach(el => el.addEventListener('click', () => { const map = { header: 'overview', room_types: 'overview', seasons: 'seasons', rates: 'seasons', room_supplements: 'rooms', meal_deltas: 'rooms', meal_prices: 'supplements', supplements: 'supplements', child_rates: 'children', guide_policies: 'guide', foc_rules: 'guide', offers: 'offers', restrictions: 'offers', cancellation_rules: 'cancel', payment_rules: 'cancel', extras: 'extras' }; S.section = map[el.dataset.goto] || 'overview'; renderEditor(); }));
        // seasons
        body.querySelectorAll('[data-addrow]').forEach(b => b.addEventListener('click', () => { collect(); const s = S.sheet.seasons[b.dataset.addrow]; const last = s.rates[s.rates.length - 1]; s.rates.push({ room_type: last ? last.room_type : '', meal_plan: last ? MEALS[Math.min(MEALS.indexOf(last.meal_plan) + 1, 3)] : 'BB', sgl: '', dbl: '', tpl: '', quad: '', unit: '', child_with_bed: '', child_no_bed: '', note: '' }); renderEditor(); }));
        body.querySelectorAll('[data-fill]').forEach(b => b.addEventListener('click', async () => {
            collect(); const s = S.sheet.seasons[b.dataset.fill];
            const rooms = (S.detail.room_types || []).filter(r => r.priced_as !== 'supplement' && r.status === 'active').map(r => r.name);
            const res = await Swal.fire({ title: 'Fill the grid', html: `<div class="text-start small"><label>Room types (one per line)</label><textarea id="fg_rooms" class="form-control" rows="4">${esc(rooms.join('\n'))}</textarea><label class="mt-2">Meal plans</label><input id="fg_meals" class="form-control" value="BB,HB"></div>`, showCancelButton: true, confirmButtonText: 'Add rows', preConfirm: () => [document.getElementById('fg_rooms').value, document.getElementById('fg_meals').value] });
            if (!res.isConfirmed) return;
            const [rt, mp] = res.value; const meals = mp.split(/[,\s]+/).map(m => m.trim().toUpperCase()).filter(m => MEALS.includes(m));
            rt.split('\n').map(x => x.trim()).filter(Boolean).forEach(room => meals.forEach(m => { if (!s.rates.some(r => r.room_type === room && r.meal_plan === m)) s.rates.push({ room_type: room, meal_plan: m, sgl: '', dbl: '', tpl: '', quad: '', unit: '', child_with_bed: '', child_no_bed: '', note: '' }); }));
            renderEditor();
        }));
        body.querySelectorAll('[data-paste]').forEach(b => b.addEventListener('click', () => { collect(); S.pasteSeason = Number(b.dataset.paste); document.getElementById('paste_text').value = ''; showModal('#m_paste'); }));
        body.querySelectorAll('[data-copys]').forEach(b => b.addEventListener('click', () => { collect(); const s = S.sheet.seasons[b.dataset.copys]; S.sheet.seasons.push({ id: null, name: '', kind: s.kind, min_stay: s.min_stay, max_rooms: s.max_rooms, notes: '', dates: [{ from: '', to: '' }], rates: s.rates.map(r => Object.assign({}, r)) }); renderEditor(); }));
        body.querySelectorAll('[data-dels]').forEach(b => b.addEventListener('click', () => { collect(); S.sheet.seasons.splice(Number(b.dataset.dels), 1); renderEditor(); }));
        body.querySelectorAll('[data-delrow]').forEach(b => b.addEventListener('click', () => { collect(); const [si, ri] = b.dataset.delrow.split(':').map(Number); S.sheet.seasons[si].rates.splice(ri, 1); renderEditor(); }));
        body.querySelectorAll('[data-addd]').forEach(b => b.addEventListener('click', () => { collect(); S.sheet.seasons[b.dataset.addd].dates.push({ from: '', to: '' }); renderEditor(); }));
        body.querySelectorAll('[data-deld]').forEach(b => b.addEventListener('click', () => { collect(); const [si, di] = b.dataset.deld.split(':').map(Number); S.sheet.seasons[si].dates.splice(di, 1); renderEditor(); }));
    }
    document.getElementById('paste_apply').addEventListener('click', () => {
        const s = S.sheet && S.sheet.seasons[S.pasteSeason]; if (!s) return;
        const lines = document.getElementById('paste_text').value.split(/\r?\n/).map(l => l.split(/\t|\s{2,}/).map(x => x.trim())).filter(l => l.length >= 2 && l[0]);
        let added = 0;
        lines.forEach(l => { const meal = (l[1] || '').toUpperCase().replace(/[^A-Z]/g, ''); const m = { ROOMONLY: 'RO', BEDANDBREAKFAST: 'BB', HALFBOARD: 'HB', FULLBOARD: 'FB', ALLINCLUSIVE: 'AI' }[meal] || meal; if (!MEALS.includes(m)) return; const n = k => (k === undefined || k === '' || k === '-' || k === 'x' || k === 'X') ? '' : k.replace(/[^0-9.\-]/g, ''); const row = { room_type: l[0], meal_plan: m, sgl: n(l[2]), dbl: n(l[3]), tpl: n(l[4]), quad: n(l[5]), unit: n(l[6]), child_with_bed: n(l[7]), child_no_bed: n(l[8]), note: '' }; if (!(row.sgl || row.dbl || row.tpl || row.quad || row.unit)) return; s.rates.push(row); added++; });
        hideModal('#m_paste'); toast(added ? 'success' : 'error', added ? `${added} row(s) added.` : 'No rows recognised — check the column order.'); renderEditor();
    });
    function collect() {
        const body = document.getElementById('ed_body'); if (!body || !S.sheet) return;
        const sh = S.sheet;
        const hn = document.getElementById('ed_hotel_name'); if (hn && !hn.disabled) { S.pendingHotelName = hn.value; }   // hotel-dup-r1: the typed name survives a page change
        body.querySelectorAll('[data-c]').forEach(i => sh.contract[i.dataset.c] = i.value);
        body.querySelectorAll('.season').forEach(se => {
            const s = sh.seasons[Number(se.dataset.si)];
            se.querySelectorAll('[data-s]').forEach(i => s[i.dataset.s] = i.value);
            se.querySelectorAll('.date-chip').forEach(ch => { const dt = s.dates[Number(ch.dataset.di)]; ch.querySelectorAll('[data-d]').forEach(i => dt[i.dataset.d] = i.value); });
            se.querySelectorAll('tr[data-ri]').forEach(tr => { const r = s.rates[Number(tr.dataset.ri)]; tr.querySelectorAll('[data-r]').forEach(i => r[i.dataset.r] = i.value); });
        });
        // generic lists: the grids of a section appear in a fixed order
        const listFor = { rooms: ['room_supplements', 'meal_deltas'], supplements: ['supplements', 'meal_prices'], children: ['child_rates'], guide: ['guide_policies', 'foc_rules'], offers: ['offers', 'restrictions'], cancel: ['cancellation_rules', 'payment_rules'], extras: ['extras'] }[S.section] || [];
        const tables = body.querySelectorAll('table.grid');
        listFor.forEach((list, ti) => {
            const t = tables[ti]; if (!t) return;
            t.querySelectorAll('tr[data-i]').forEach(tr => { const r = sh[list][Number(tr.dataset.i)]; if (!r) return; tr.querySelectorAll('[data-k]').forEach(i => r[i.dataset.k] = i.type === 'checkbox' ? (i.checked ? 1 : 0) : i.value); });
        });
    }
    function sheetPayload() {
        collect();
        const sh = S.sheet; const out = {};
        ['hotel', 'contract', 'room_types', 'seasons', 'room_supplements', 'meal_deltas', 'meal_prices', 'supplements', 'child_rates', 'guide_policies', 'foc_rules', 'offers', 'cancellation_rules', 'payment_rules', 'restrictions', 'extras'].forEach(k => out[k] = sh[k]);
        out.hotel = { id: S.hotel.id, name: S.hotel.name };
        return out;
    }
    async function checkOnly() {
        const j = await post('/contract/validate', { sheet: sheetPayload() });
        if (j.status !== 200) return toast('error', j.message);
        S.sheet.issues = j.data.issues; S.section = 'issues'; renderEditor();
    }
    // hotel-dup-r1: the hotel name typed on the Overview page is saved to the Hotel Master before the sheet
    // (a near-duplicate name is shown for review first; an exact duplicate is refused by the server)
    async function renameHotelIfChanged() {
        collect(); if (!CAN_RENAME || !S.hotel || S.pendingHotelName == null) return true;
        const name = String(S.pendingHotelName).trim().replace(/\s+/g, ' '); if (!name || name === S.hotel.name) { S.pendingHotelName = null; return true; }
        const inp = { value: name };
        const sim = await api('/similar?name=' + encodeURIComponent(name) + '&exclude=' + S.hotel.id);
        const matches = (sim.data || []).filter(m => m.status !== 'merged');
        if (matches.length) {
            const ok = await Swal.fire({ icon: 'warning', title: 'A similar hotel is already on file', html: `<div style="text-align:left;font-size:13px">${similarListHtml(matches)}<br>If one of these is the same hotel, do not rename — ask Management to <b>remove the duplicate</b> (⋯ Manage) so the records are joined. Rename only if <b>${esc(name)}</b> is really a different hotel.</div>`, width: 640, showCancelButton: true, confirmButtonText: 'Rename anyway', cancelButtonText: 'Keep the current name' });
            if (!ok.isConfirmed) { S.pendingHotelName = null; const el = document.getElementById('ed_hotel_name'); if (el) el.value = S.hotel.name; return true; }
        }
        return renameHotelTo(name);
    }
    async function renameHotelTo(name) {
        const r = await post(`/hotel/${S.hotel.id}/update`, { name });
        if (r.status !== 200) { toast('error', r.message); return false; }
        toast('success', r.message); S.hotel.name = name; S.pendingHotelName = null;
        await loadHotels(); const j = await api('/hotel/' + S.hotel.id + '?market=' + encodeURIComponent(S.covMarket)); if (j.status === 200) { S.hotel = j.data.hotel; S.detail = j.data; document.getElementById('h_name').textContent = S.hotel.name; renderHotelForm(); }
        const hn = document.getElementById('ed_hotel_name'); if (hn) hn.value = name;
        return true;
    }
    const depsText = d => `${d.plans_approved} approved plan(s), ${d.plans_draft} draft(s), ${d.room_types} room type(s) · ${d.quotation_nights} night(s) on ${d.tours} tour(s) / quotation(s) · ${d.inquiry_nights} night(s) on inquiries · ${d.desk_cards} Hotel Desk card(s) · ${d.payment_requests} payment request(s), ${d.vouchers} voucher(s) · supplier account(s) ${(d.accounts || []).join(', ') || '—'}`;
    const hotelPickerHtml = (exceptId) => `<input class="form-control form-control-sm mb-1" id="mp_q" placeholder="Type part of the hotel name…" autocomplete="off"><select class="form-select form-select-sm" id="mp_sel" size="7"></select><div class="help mt-1">Pick the record to keep — the one with the history (tours, payments, approved rates).</div>`;
    function hotelPickerBind(exceptId) {
        const q = document.getElementById('mp_q'), sel = document.getElementById('mp_sel');
        const fill = () => { const w = q.value.trim().toLowerCase(); const list = S.hotels.filter(h => Number(h.id) !== Number(exceptId) && (!w || `${h.name} ${h.location_name || ''} ${h.hotel_code || ''} ${h.primary_sup_acc_no || ''}`.toLowerCase().includes(w))).slice(0, 60); sel.innerHTML = list.map(h => `<option value="${h.id}">${esc(h.hotel_code || '')} ${esc(h.name)}${h.location_name ? ' · ' + esc(h.location_name) : ''} · ${h.nights_last_year || 0} nights/yr</option>`).join(''); };
        q.addEventListener('input', fill); fill(); setTimeout(() => q.focus(), 200);
    }
    const similarListHtml = list => `<table class="hr-table" style="width:100%"><thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Status</th><th class="num">Nights / yr</th><th class="num">Plans</th></tr></thead><tbody>${list.map(m => `<tr><td><span class="hcode">${esc(m.hotel_code || '')}</span></td><td><b>${esc(m.name)}</b><div class="help">${esc(m.why || '')}</div></td><td>${esc(m.location_name || '')}</td><td>${esc(m.status)}</td><td class="num">${m.nights_last_year}</td><td class="num">${Number(m.plans_approved) + Number(m.plans_draft)}</td></tr>`).join('')}</tbody></table>`;

    async function save(approve, ack, activate) {
        if (!ack) { if (!(await renameHotelIfChanged())) return; }
        const sheet = sheetPayload();
        // tf-offers-r1: a tactical / early bird is saved through the panel itself — active at once ("Save — active now"),
        // or as a correction of an active one; a published plan keeps the approval route
        const isOffer = OFFERS_READY && !!offerKind(sheet.contract);
        const needsApproval = !isOffer && (approve || !!S.editApproved);
        const j = await post('/contract/save', { sheet, approve: isOffer ? false : approve, activate_offer: isOffer && (!!activate || !!S.editApproved), acknowledge_warnings: !!ack, edit_approved: !!S.editApproved }, needsApproval ? APPROVE_BASE : BASE);
        if (j.status === 403) { toast('error', j.message); return; }
        if (j.data && j.data.contract_id) { S.sheet.contract.id = j.data.contract_id; }
        if (j.data && j.data.needs_acknowledgement) {
            const ok = await Swal.fire({ title: 'Warnings to confirm', html: issuesHtml(j.data.issues), width: 760, showCancelButton: true, confirmButtonText: 'Approve anyway' });
            if (ok.isConfirmed) return save(approve, true, activate);
            S.sheet.issues = j.data.issues; S.section = 'issues'; renderEditor(); return;
        }
        if (j.status === 200) {
            toast('success', j.message);
            loadQueueCount();
            const hid = S.hotel.id;
            if (approve || S.editApproved || activate) { const k = isOffer ? offerKind(sheet.contract) : null; closeEditor(); await selectHotel(hid); if (k) showTab(k); if (FROM_QUEUE && approve) { location.href = APPROVE_BASE; return; } }
            else { await selectHotel(hid, true); await openContract(j.data.contract_id); S.sheet.issues = j.data.issues; if (j.data.issues.length) { S.section = 'issues'; renderEditor(); }
                if (FROM_QUEUE) { const back = await Swal.fire({ icon: 'success', title: 'Saved — still a draft', text: 'Nothing is approved yet. Go back to the approval queue, or stay and keep editing.', showCancelButton: true, confirmButtonText: 'Back to approval queue', cancelButtonText: 'Stay here' }); if (back.isConfirmed) { location.href = APPROVE_BASE; return; } } }
        } else {
            if (j.data && j.data.issues) { S.sheet.issues = j.data.issues; S.section = 'issues'; renderEditor(); }
            toast('error', j.message);
        }
    }

    // ---------------- quote ----------------
    function openQuote(hotelId) {
        const mk = document.getElementById('q_market'); if (!mk.options.length) mk.innerHTML = Object.entries(MARKETS).map(([k, v]) => `<option value="${k}" ${k === DEFAULT_MARKET ? 'selected' : ''}>${esc(v)}</option>`).join('');
        if (hotelId) document.getElementById('q_hotel').value = hotelId;
        document.getElementById('q_in').value = document.getElementById('q_in').value || TODAY;
        document.getElementById('q_booking').value = document.getElementById('q_booking').value || TODAY;
        loadQuoteRooms(); document.getElementById('q_out').innerHTML = ''; showModal('#m_quote');
    }
    async function loadQuoteRooms() {
        const id = document.getElementById('q_hotel').value; if (!id) return;
        const j = await api('/hotel/' + id); if (j.status !== 200) return;
        document.getElementById('q_room').innerHTML = (j.data.room_types || []).map(r => `<option value="hr2:${r.id}">${esc(r.name)}${r.priced_as === 'supplement' ? ' (+suppl.)' : ''}</option>`).join('');
    }
    document.getElementById('q_hotel').addEventListener('change', loadQuoteRooms);
    document.getElementById('btn_quote').addEventListener('click', () => openQuote(S.hotel ? S.hotel.id : null));
    document.getElementById('btn_hotel_quote').addEventListener('click', () => openQuote(S.hotel.id));
    document.getElementById('q_run').addEventListener('click', async () => {
        const g = id => document.getElementById(id).value;
        const j = await api(`/quote?hotel_id=${g('q_hotel')}&room=${encodeURIComponent(g('q_room'))}&meal=${g('q_meal')}&check_in=${g('q_in')}&nights=${g('q_nights')}&adults=${g('q_adults')}&rooms=${g('q_rooms')}&market=${g('q_market')}&booking_date=${g('q_booking')}`);
        const out = document.getElementById('q_out');
        if (j.status !== 200) { out.innerHTML = `<div class="issue error">${esc(j.message)}</div>`; return; }
        const q = j.data;
        out.innerHTML = `<div class="d-flex gap-3 flex-wrap mb-3"><div class="kpi"><div class="v">${money(q.room_total)}</div><div class="l">rooms (${q.occupancy.toUpperCase()}, ${q.nights} nights)</div></div><div class="kpi"><div class="v">${money(q.supplement_total)}</div><div class="l">compulsory supplements</div></div><div class="kpi"><div class="v">${money(q.grand_total)}</div><div class="l">total for ${q.adults} adults</div></div>${q.missing_nights ? `<div class="kpi" style="border-color:var(--hr-red)"><div class="v text-danger">${q.missing_nights}</div><div class="l">nights without a rate</div></div>` : ''}</div>
            <table class="hr-table"><thead><tr><th>Night</th><th class="num">Room</th><th>Supplements</th><th>Plan · season</th><th>How</th><th>Rules</th></tr></thead><tbody>${q.nights_detail.map(n => n.missing ? `<tr><td class="mono">${n.date}</td><td colspan="5" class="text-danger">no approved rate</td></tr>` : `<tr><td class="mono">${n.date}</td><td class="num">${money(n.room)}</td><td>${n.supplements.map(s => `${esc(s.name)} ${money(s.amount)} ${esc(OPT.sup_per[s.per] || s.per)} = <b>${money(s.line)}</b>`).join('<br>') || '—'}</td><td>${esc(n.plan)}<div class="help">${esc(n.season || '')}</div></td><td class="help">${n.derived === 'direct' ? 'printed rate' : n.components.map(cp => cp.kind === 'rate' ? `${esc(cp.meal)} ${money(cp.dbl ?? cp.unit)}` : cp.kind === 'meal_delta' ? `${cp.from}→${cp.to} ${cp.adult > 0 ? '+' : ''}${money(cp.adult)} pp` : `+${money(cp.amount)} ${esc(cp.room)}`).join(' · ')}</td><td class="help">${n.restrictions.map(r => `${esc(OPT.restriction[r.kind] || r.kind)}${r.value ? ' ' + esc(r.value) : ''}${r.violated ? ' <span class="text-danger">✗</span>' : ''}`).join('<br>')}</td></tr>`).join('')}</tbody></table>`;
    });

    // ---------------- project ----------------
    document.getElementById('btn_project').addEventListener('click', async () => { const j = await post(`/hotel/${S.hotel.id}/project`); toast(j.status === 200 ? 'success' : 'error', j.message + (j.data && j.data.skipped && j.data.skipped.length ? ' Skipped: ' + j.data.skipped.join('; ') : '')); });

    // ---------------- import ----------------
    function resetImport(ai, offerKindForImport) {
        S.importId = null; S.importSheets = []; S.importKind = offerKindForImport || null;
        document.getElementById('imp_step1').style.display = ''; document.getElementById('imp_step2').style.display = 'none'; document.getElementById('imp_step2').innerHTML = '';
        ['imp_commit_btn', 'imp_draft_btn', 'imp_ack_box', 'imp_sup_box', 'imp_no_approve', 'imp_offer_btn'].forEach(id => document.getElementById(id).style.display = 'none');
        document.getElementById('imp_ai_box').style.display = ai ? '' : 'none';
        document.getElementById('m_import_title').textContent = S.importKind ? (ai ? 'Read a ' + (S.importKind === 'early_bird' ? 'early bird offer' : 'tactical rate') + ' from a PDF' : 'Import a ' + (S.importKind === 'early_bird' ? 'early bird offer' : 'tactical rate') + ' from Excel') : (ai ? 'Read a rate sheet PDF' : 'Import a rate sheet from Excel');
        if (S.hotel) document.getElementById('imp_hotel').value = S.hotel.id;
        if (S.importKind) document.getElementById('imp_plan_type').value = S.importKind;
    }
    document.getElementById('btn_import').addEventListener('click', () => { resetImport(false); showModal('#m_import'); });
    const aiBtn = document.getElementById('btn_ai'); if (aiBtn) aiBtn.addEventListener('click', () => { resetImport(true); showModal('#m_import'); });
    function readWorkbook(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => { try { const wb = XLSX.read(e.target.result, { type: 'array', cellDates: false }); const out = {}; wb.SheetNames.forEach(n => out[n] = XLSX.utils.sheet_to_json(wb.Sheets[n], { defval: '' })); resolve(out); } catch (err) { reject(err); } };
            reader.onerror = reject; reader.readAsArrayBuffer(file);
        });
    }
    document.getElementById('imp_preview_btn').addEventListener('click', async () => {
        const file = document.getElementById('imp_file').files[0]; if (!file) return toast('error', 'Choose an Excel file first.');
        let wb; try { wb = await readWorkbook(file); } catch (e) { return toast('error', 'Could not read the file: ' + e.message); }
        const defaults = { source_type: 'excel', source_file: file.name, market: document.getElementById('imp_market').value || 'ALL', plan_type: document.getElementById('imp_plan_type').value, pricing_mode: document.getElementById('imp_pricing').value, currency: document.getElementById('imp_currency').value || DEFAULT_CURRENCY };
        const hid = document.getElementById('imp_hotel').value; if (hid) defaults.hotel_id = Number(hid);
        const names = Object.keys(wb);
        const body = names.length > 1 || /^(plan|rates)$/i.test(names[0] || '') ? { workbook: wb, defaults } : { rows: wb[names[0]] || [], defaults };
        const j = await post('/import/preview', body);
        if (j.status !== 200) return toast('error', j.message);
        renderPreview(j.data);
    });
    const aiExtract = document.getElementById('ai_extract_btn'); if (aiExtract) aiExtract.addEventListener('click', async () => {
        const file = document.getElementById('ai_file').files[0]; const hid = document.getElementById('imp_hotel').value;
        if (!file) return toast('error', 'Choose a PDF or image.');
        const fd = new FormData(); fd.append('contract_file', file); if (hid) fd.append('hotel_id', hid);
        aiExtract.disabled = true; aiExtract.textContent = 'Reading… (30–90 s)';
        try { const r = await fetch(BASE + '/import/extract', { method: 'POST', body: fd }); const j = await r.json(); if (j.status !== 200) return toast('error', j.message); renderPreview(j.data); }
        finally { aiExtract.disabled = false; aiExtract.textContent = 'Read'; }
    });
    function renderPreview(data) {
        S.importId = data.import_id; S.importSheets = data.sheets;
        document.getElementById('imp_step1').style.display = 'none';
        const box = document.getElementById('imp_step2'); box.style.display = '';
        let anyWarn = false, anyErr = false;
        box.innerHTML = `<div class="help mb-2">Preview #${data.import_id}: ${data.sheets.length} plan(s) · ${data.totals.ok} clean · ${data.totals.warning} with warnings · ${data.totals.error} with errors. Nothing has been saved yet.</div>` + data.sheets.map((s, i) => {
            const errs = s.issues.filter(x => x.level === 'error'), warns = s.issues.filter(x => x.level === 'warning'); if (warns.length) anyWarn = true; if (errs.length) anyErr = true;
            const picker = !s.hotel.id ? `<div class="issue error">Hotel "${esc(s.hotel.name)}" not found. Pick the right hotel: <select class="form-select form-select-sm d-inline-block w-auto ms-1" data-override="${i}"><option value="">—</option>${(s.suggestions || []).map(h => `<option value="${h.id}">${esc(h.name)}${h.location_name ? ' · ' + esc(h.location_name) : ''} (${h.score}%)</option>`).join('')}<option value="__all">… any hotel</option></select></div>` : '';
            const seasons = s.seasons.map(p => `<div class="mono">${p.dates.map(dd => (dd.from || '?') + '→' + (dd.to || '?')).join(' & ')}: ${[...new Set(p.rates.map(r => r.room_type))].join(', ')} (${p.rates.length} rows)</div>`).join('');
            return `<div class="fieldset"><div class="d-flex align-items-center gap-2 flex-wrap"><b>${esc(s.hotel.name || '(no hotel)')}</b> ${typePill(s.contract.plan_type)} <span class="help">${esc(s.contract.name)} · ${marketsText(s.contract)} · ${s.contract.valid_from || '?'} → ${s.contract.valid_to || '?'} · ${s.seasons.length} season(s) · ${s.supplements.length} supplement(s) · ${s.child_rates.length} child rule(s) · ${s.cancellation_rules.length} cancellation band(s)</span></div>${picker}
                <div class="small mt-1">${seasons}</div>
                ${errs.length ? '<div class="mt-1">' + errs.map(x => `<div class="issue error"><b>${x.code}</b>${esc(x.message)}</div>`).join('') + '</div>' : ''}
                ${warns.length ? '<div class="mt-1">' + warns.map(x => `<div class="issue warning"><b>${x.code}</b>${esc(x.message)}</div>`).join('') + '</div>' : ''}</div>`;
        }).join('');
        box.querySelectorAll('[data-override]').forEach(el => el.addEventListener('change', () => { if (el.value === '__all') { el.innerHTML = '<option value="">—</option>' + S.hotels.map(h => `<option value="${h.id}">${esc(h.name)}</option>`).join(''); } }));
        if (S.importKind) {
            // tf-offers-r1: an upload from the Tactical / Early Bird tab is saved as that kind — active at once, or as a draft
            box.insertAdjacentHTML('afterbegin', `<div class="help mb-2">Saved as <b>${esc(KIND_LABEL[S.importKind])}${data.sheets.length === 1 ? '' : 's'}</b> of this hotel — kept apart from the published rates, never used for a quotation.</div>`);
            document.getElementById('imp_offer_btn').style.display = ''; document.getElementById('imp_draft_btn').style.display = ''; document.getElementById('imp_ack_box').style.display = anyWarn ? '' : 'none';
            return;
        }
        document.getElementById('imp_commit_btn').style.display = CAN_APPROVE ? '' : 'none'; document.getElementById('imp_draft_btn').style.display = ''; document.getElementById('imp_ack_box').style.display = anyWarn ? '' : 'none'; document.getElementById('imp_sup_box').style.display = CAN_APPROVE ? '' : 'none'; document.getElementById('imp_no_approve').style.display = CAN_APPROVE ? 'none' : '';
    }
    async function commitImport(approve, activateOffer) {
        const overrides = {}; document.querySelectorAll('#imp_step2 [data-override]').forEach(s => { if (s.value && s.value !== '__all') overrides[s.dataset.override] = Number(s.value); });
        const kind = S.importKind;
        const supersede = !kind && CAN_APPROVE && document.getElementById('imp_supersede').checked;
        const body = { hotel_overrides: overrides, acknowledge_warnings: document.getElementById('imp_ack').checked, approve: kind ? false : approve, supersede };
        if (kind) {
            // every plan of the upload goes in as this kind of offer (a published plan never slips through this way)
            body.sheets = S.importSheets.map((sh, i) => { const c = Object.assign({}, sh.contract, { plan_type: kind, applies_to: 'payment', priority: Math.max(10, Number(sh.contract.priority || 0)) }); if (overrides[i]) { sh = Object.assign({}, sh, { hotel: { id: overrides[i] } }); } return Object.assign({}, sh, { contract: c }); });
            body.activate_offer = !!activateOffer;
        }
        const j = await post(`/import/${S.importId}/commit`, body, (!kind && (approve || supersede)) ? APPROVE_BASE : BASE);
        if (j.status === 403) { toast('error', j.message); return; }
        const res = j.data ? j.data.results : [];
        const html = res.map(r => `<div class="${r.saved ? 'text-success' : 'text-danger'}"><b>${esc(r.hotel || '(no hotel)')}</b> — ${esc(r.plan)}: ${r.approved ? 'approved' : (r.saved ? 'saved as draft' : 'not saved')}${r.needs_acknowledgement ? ' — tick "I have read the warnings"' : ''}${r.issues.filter(i => i.level === 'error').map(i => `<div class="issue error"><b>${i.code}</b>${esc(i.message)}</div>`).join('')}</div>`).join('');
        await Swal.fire({ icon: j.status === 200 ? 'success' : 'warning', title: j.message, html: '<div class="text-start small">' + html + '</div>', width: 720 });
        if (j.status === 200) { hideModal('#m_import'); await loadHotels(); loadQueueCount(); const first = res.find(r => r.saved); if (first && first.hotel_id) { await selectHotel(first.hotel_id); if (kind) { showTab(kind); if (!activateOffer && first.contract_id) openContract(first.contract_id); } else if (!approve && first.contract_id) openContract(first.contract_id); } }
    }
    document.getElementById('imp_commit_btn').addEventListener('click', () => { if (!CAN_APPROVE) return toast('error', 'Approving needs the Hotel Rate Approval privilege.'); commitImport(true); });
    document.getElementById('imp_draft_btn').addEventListener('click', () => commitImport(false));
    document.getElementById('imp_offer_btn').addEventListener('click', () => commitImport(false, true));
    document.getElementById('imp_template').addEventListener('click', e => {
        e.preventDefault();
        const wb = XLSX.utils.book_new();
        const add = (name, rows) => XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(rows), name);
        add('Plan', [['Field', 'Value'], ['Hotel', S.hotel ? S.hotel.name : ''], ['Plan name', 'Winter 2026/27 travel agent rates'], ['Plan type', 'contract'], ['Rate code', ''], ['Tier', 'all'], ['Markets', 'ALL'], ['Valid from', '2026-11-01'], ['Valid to', '2027-04-30'], ['Book to', ''], ['Currency', DEFAULT_CURRENCY], ['Pricing mode', 'per_room'], ['Taxes included', 1], ['Tax pct', ''], ['Contract date', ''], ['Credit facility', 'unknown'], ['Extra bed adult', ''], ['Child policy', ''], ['Cancellation policy', ''], ['Payment terms', ''], ['Notes', '']]);
        add('Rates', [['Season', 'From', 'To', 'Room type', 'Meal plan', 'SGL', 'DBL', 'TPL', 'QUAD', 'UNIT', 'Child with bed', 'Child no bed', 'Note'], ['Winter', '2026-11-01', '2026-12-19', 'Deluxe', 'BB', 110, 120, 165, '', '', '', '', ''], ['Winter', '2026-11-01', '2026-12-19', 'Deluxe', 'HB', 134, 168, 237, '', '', '', '', ''], ['Festive', '2026-12-20', '2027-01-10', 'Deluxe', 'BB', 166, 180, 236, '', '', '', '', '']]);
        add('Room types', [['Room type', 'Base room', 'Rooms', 'Size sqm', 'Beds', 'Max adults', 'Max children'], ['Deluxe', '', 44, 51, '1 queen', 3, 1], ['Suite', 'Deluxe', 2, 129, 'king + queen', 4, 3]]);
        add('Room supplements', [['Room type', 'Season', 'Amount', 'Per', 'Note'], ['Suite', '', 260, 'room', '']]);
        add('Meal deltas', [['From', 'To', 'Adult', 'Child', 'Season', 'Note'], ['HB', 'BB', -5, -2.5, '', 'BB reduction'], ['HB', 'FB', 25, 12.5, '', 'FB supplement']]);
        add('Meal prices', [['Meal', 'Adult', 'Child'], ['breakfast', 17, ''], ['lunch', 20, ''], ['dinner', 24, '']]);
        const g1 = `2026-${GALA_DATES[0] || '12-24'}`;
        add('Supplements', [['Type', 'Name', 'From', 'To', 'Season', 'Meal plans', 'Room type', 'Per', 'Amount', 'SGL', 'DBL', 'TPL', 'Child amount', 'Child pct', 'Pct of rate', 'Compulsory', 'Note'], ['gala', 'Gala dinner (BB guests)', g1, g1, '', 'BB', '', 'person', 119, '', '', '', '', GALA_CHILD_PCT, '', 1, ''], ['gala', 'Gala dinner (HB/FB guests)', g1, g1, '', 'HB,FB', '', 'person', 95, '', '', '', '', GALA_CHILD_PCT, '', 1, ''], ['peak', 'Peak supplement', '2026-12-20', '2027-01-10', '', '', '', 'room', 10, '', '', '', '', '', '', 1, '']]);
        add('Children', [['Age from', 'Age to', 'Bed', 'Meal plan', 'Amount', 'Pct', 'Pct basis', 'Max children', 'Note'], [0, 5.99, 'any', '', 0, '', '', 2, 'sharing parents'], [6, 11.99, 'with_bed', 'BB', 35, '', '', '', ''], [6, 11.99, 'no_bed', '', '', 50, 'meal_rate', '', '']]);
        add('Guide', [['Role', 'Meal plan', 'Amount', 'Currency', 'Pct of', 'Pct', 'Per', 'Free from pax', 'Min rooms', 'Accommodation', 'Note'], ['guide', 'HB', 55, DEFAULT_CURRENCY, '', '', 'person', 16, '', 'guide_quarters', '']]);
        add('FOC', [['Basis', 'Every', 'Free', 'Discount pct', 'As', 'Note'], ['pax', 15, 1, 100, 'any', '16th pax free']]);
        add('Offers', [['Kind', 'Name', 'Book from', 'Book to', 'Stay from', 'Stay to', 'Min nights', 'Min pax', 'Min rooms', 'Value pct', 'Value amount', 'Free nights', 'Per', 'Combinable', 'Excludes peak', 'Conditions']]);
        add('Cancellation', [['Stay from', 'Stay to', 'Tier', 'Min rooms', 'Max rooms', 'Min pax', 'Max pax', 'Days from', 'Days to', 'Charge pct', 'Charge nights', 'Note'], ['', '', 'fit', '', '', '', '', '', 30, 0, '', 'free'], ['', '', 'fit', '', '', '', '', 29, 15, 50, '', ''], ['', '', 'fit', '', '', '', '', 14, 0, 100, '', '']]);
        add('Payment', [['Applies', 'Kind', 'Days before', 'Pct', 'From', 'To', 'Note'], ['non_credit', 'prepay', 30, 100, '', '', '']]);
        add('Restrictions', [['Kind', 'From', 'To', 'Value', 'Note'], ['meal_plan_required', g1, g1, 'HB,FB,AI', '']]);
        add('Extras', [['Category', 'Name', 'Per', 'Adult', 'Child', 'Currency', 'Note']]);
        XLSX.writeFile(wb, 'hotel-rate-plan-template.xlsx');
    });

    // ---------------- duplicates ----------------
    // Same name in two towns is usually two properties of one brand (Wild Glamping Gal Oya / Knuckles,
    // Anantaya Chilaw / Passikudah): those groups carry a warning and merging them needs a typed confirmation.
    document.getElementById('btn_dups').addEventListener('click', async () => {
        const j = await api('/duplicates'); if (j.status !== 200) return toast('error', j.message);
        const body = document.getElementById('dups_body');
        body.innerHTML = j.data.length ? j.data.map((g, gi) => `<div class="fieldset ${g.location_mismatch ? 'border-warning' : ''}"><div class="help mb-1">${esc(g.reason)}${g.location_mismatch ? ` &nbsp;${pill('amber', 'different locations')} <span class="text-warning">probably two properties of one brand (${esc((g.locations || []).join(' / '))}) — merge only if you are sure they are the same place</span>` : ''}</div>` + g.hotels.map(h => `<div class="d-flex align-items-center gap-2 mb-1"><span class="flex-grow-1">${esc(h.name)} <span class="help">${esc(h.location_name || 'no location')} · <span class="mono">${esc(h.primary_sup_acc_no || '')}</span> · ${h.contracts} plan(s) · ${h.timeline_rows} tour nights</span></span>${CAN_MANAGE ? `<button class="btn btn-sm btn-light" data-keep="${h.id}" data-group="${gi}">Keep this one</button>` : ''}</div>`).join('') + '</div>').join('') + `<div class="help mt-2">A merge can be undone: open the kept hotel and use <b>Undo merge</b> next to the hotel it absorbed.</div>` : '<div class="empty">No likely duplicates found.</div>';
        body.querySelectorAll('[data-keep]').forEach(b => b.addEventListener('click', async () => {
            const g = j.data[Number(b.dataset.group)]; const keep = Number(b.dataset.keep); const others = g.hotels.filter(h => Number(h.id) !== keep);
            const keepH = g.hotels.find(h => Number(h.id) === keep);
            const line = h => `${esc(h.name)} <span class="help">(${esc(h.location_name || 'no location')} · ${esc(h.primary_sup_acc_no || '')})</span>`;
            const html = `Keep <b>${line(keepH)}</b> and fold in: ${others.map(line).join(', ')}.<br><small>Plans, room types and the supplier code move across; the duplicate is hidden from dropdowns. It can be undone from the kept hotel (<b>Undo merge</b>).</small>` + (g.location_mismatch ? `<div class="alert alert-warning mt-2 mb-0 small"><b>These hotels are in different locations.</b> If they are two properties of one brand, do not merge them. Type <b>MERGE</b> to continue.</div>` : '');
            const ok = await Swal.fire(Object.assign({ icon: 'warning', title: 'Merge hotels?', html, showCancelButton: true }, g.location_mismatch ? { input: 'text', inputPlaceholder: 'MERGE', inputValidator: v => (String(v).trim().toUpperCase() === 'MERGE' ? null : 'Type MERGE to confirm, or cancel.') } : {}));
            if (!ok.isConfirmed) return;
            for (const o of others) { const r = await postA('/merge', { keep_id: keep, merge_id: o.id }); if (r.status !== 200) { toast('error', r.message); return; } }
            toast('success', 'Merged.'); hideModal('#m_dups'); loadHotels(); if (S.hotel) selectHotel(S.hotel.id);
        }));
        showModal('#m_dups');
    });

    // ---------------- check ----------------
    document.getElementById('btn_check').addEventListener('click', async () => {
        const body = document.getElementById('check_body'); body.innerHTML = '<div class="empty">Checking…</div>'; showModal('#m_check');
        const j = await api('/check'); if (j.status !== 200) { body.innerHTML = esc(j.message); return; }
        const dd = j.data; const byHotel = {};
        dd.missing.forEach(m => { const k = m.hotel_acc; byHotel[k] = byHotel[k] || { hotel: m.hotel, acc: k, nights: 0, tours: new Set() }; byHotel[k].nights++; byHotel[k].tours.add(m.manual_id); });
        const list = Object.values(byHotel).sort((a, b) => b.nights - a.nights);
        body.innerHTML = `<div class="d-flex gap-3 flex-wrap mb-3"><div class="kpi"><div class="v">${dd.nights_checked}</div><div class="l">hotel nights on tours ${dd.from} → ${dd.to}</div></div><div class="kpi" style="${dd.missing.length ? 'border-color:var(--hr-red)' : ''}"><div class="v ${dd.missing.length ? 'text-danger' : ''}">${dd.missing.length}</div><div class="l">without an approved rate</div></div><div class="kpi"><div class="v">${dd.expiring.length}</div><div class="l">plans ending within ${dd.expiry_days} days</div></div><div class="kpi"><div class="v">${dd.drafts.length}</div><div class="l">drafts waiting</div></div></div>
            <h6>Tours without a rate</h6><table class="hr-table"><thead><tr><th>Hotel</th><th class="num">Nights</th><th>Tours</th></tr></thead><tbody>${list.map(h => `<tr><td>${esc(h.hotel)} <span class="mono help">${h.acc}</span></td><td class="num">${h.nights}</td><td class="help">${[...h.tours].slice(0, 8).join(', ')}${h.tours.size > 8 ? ' …' : ''}</td></tr>`).join('') || '<tr><td colspan="3" class="text-success">Every night has a rate.</td></tr>'}</tbody></table>
            <h6 class="mt-3">Plans ending within ${dd.expiry_days} days with no successor (Settings › expiry warning)</h6>${dd.expiring.length ? '<ul class="small">' + dd.expiring.map(e => `<li>${e.valid_to} — ${esc(e.hotel)} (${esc(e.name)})</li>`).join('') + '</ul>' : '<div class="help">none</div>'}
            <h6 class="mt-3">Offers whose booking window has closed (${dd.closed_windows.length})</h6>${dd.closed_windows.length ? '<ul class="small">' + dd.closed_windows.map(e => `<li>closed ${e.book_to} — ${esc(e.hotel)} (${esc(e.name)})</li>`).join('') + '</ul>' : '<div class="help">none</div>'}
            <h6 class="mt-3">Drafts waiting for approval (${dd.drafts.length})</h6>${dd.drafts.length ? '<ul class="small">' + dd.drafts.map(e => `<li>${esc(e.hotel)} — ${esc(e.name)} <span class="help">(${esc(e.entered_by || '')}, ${(e.updated_at || '').slice(0, 10)})</span></li>`).join('') + '</ul>' : '<div class="help">none</div>'}
            <h6 class="mt-3">Approved rate rows with quality flags (${dd.flagged.length})</h6>${dd.flagged.length ? '<div class="table-responsive"><table class="hr-table"><thead><tr><th>Hotel</th><th>Plan</th><th>Season</th><th>Room</th><th>Meal</th><th class="num">SGL</th><th class="num">DBL</th><th class="num">TPL</th><th>Flags</th></tr></thead><tbody>' + dd.flagged.slice(0, 200).map(f => `<tr><td>${esc(f.hotel)}</td><td class="help">${esc(f.plan)}</td><td>${esc(f.season || '')}</td><td>${esc(f.room_type)}</td><td>${f.meal_plan}</td><td class="num">${fmt(f.sgl)}</td><td class="num">${fmt(f.dbl)}</td><td class="num">${fmt(f.tpl)}</td><td>${pill('amber', f.quality_flags)}</td></tr>`).join('') + '</tbody></table></div>' : '<div class="help">none</div>'}`;
    });

    // ---------------- rate-expiry warnings (tf-hr-ready-r1) — read-only, from readiness/alerts ----------------
    async function loadRateAlerts() {
        const box = document.getElementById('rate_alerts'); if (!box) return;
        let j; try { j = await api('/readiness/alerts'); } catch (e) { return; }
        if (!j || j.status !== 200 || !j.data || !j.data.alerts || !j.data.alerts.length) { box.style.display = 'none'; return; }
        const list = j.data.alerts, cnt = document.getElementById('rate_alert_count');
        if (cnt) { cnt.textContent = list.length; cnt.style.display = ''; }
        const tone = a => a.state === 'expired' ? 'red' : 'amber';
        const repTone = r => r.key === 'required' ? 'red' : (r.key === 'gap' ? 'amber' : 'blue');
        box.innerHTML = `<div class="hr-card-b"><div class="d-flex flex-wrap align-items-center gap-2 mb-2"><b style="color:var(--hr-red)"><i class="bi bi-exclamation-octagon"></i> RATE EXPIRY WARNING</b><span class="help">${list.length} hotel${list.length === 1 ? '' : 's'} — the warning starts ${j.data.alert_days} days before an approved contract rate ends and clears when an approved rate covers the night after.</span><a class="ms-auto btn btn-light btn-sm" href="${BASE}/readiness"><i class="bi bi-clipboard2-check"></i> Open quotation readiness</a></div>
            <ul class="small mb-0" style="padding-left:18px">${list.slice(0, 6).map(a => `<li class="mb-1">${pill(tone(a), a.state_text)} <a href="${BASE}?hotel=${encodeURIComponent(a.acc || '')}"><b>${esc(a.hotel)}</b></a> — ${a.state === 'expiring' ? 'current contract expires' : 'contract expired'} <b>${esc(a.ends_text || a.ends)}</b> — ${pill(repTone(a.replacement), a.replacement.text)}</li>`).join('')}${list.length > 6 ? `<li class="help">and ${list.length - 6} more — open quotation readiness</li>` : ''}</ul></div>`;
        box.style.display = '';
    }

    // ---------------- approval queue ----------------
    async function loadQueueCount() {
        try { const j = await api('/summary'); const el = document.getElementById('queue_count'); if (j.status === 200) { el.textContent = j.data.drafts; el.style.display = j.data.drafts > 0 ? '' : 'none'; } } catch (e) { /* ignore */ }
    }
    async function openQueue() {
        const body = document.getElementById('queue_body'); body.innerHTML = '<div class="empty">Loading…</div>'; showModal('#m_queue');
        const j = await api('/queue'); if (j.status !== 200) { body.innerHTML = `<div class="issue error">${esc(j.message)}</div>`; return; }
        const d = j.data;
        // tf-hr-queue-r1 (27 Oct 2026, Shantha): a search box and quick filters over the queue — by hotel, plan, type,
        // source, entered by, or the check result — with a count, so a long queue can be worked hotel by hotel
        const types = [...new Set(d.drafts.map(c => c.plan_type).filter(Boolean))].sort();
        body.innerHTML = `<div class="help mb-2">${d.drafts.length} draft(s) waiting. ${CAN_APPROVE ? 'Open a draft to review every page, then approve it here or from the editor.' : 'A user with the Hotel Rate Approval privilege approves them.'}</div>
            <div class="d-flex gap-2 align-items-center flex-wrap mb-2" id="aq_bar">
                <input class="form-control form-control-sm" id="aq_q" placeholder="Search hotel, plan, entered by…" style="max-width:300px" autocomplete="off">
                <select class="form-select form-select-sm" id="aq_type" style="max-width:170px"><option value="">All types</option>${types.map(t => `<option value="${esc(t)}">${esc(t.replace('_', ' '))}</option>`).join('')}</select>
                <select class="form-select form-select-sm" id="aq_check" style="max-width:190px"><option value="">Any check result</option><option value="clean">Clean — ready to approve</option><option value="warn">Warnings only</option><option value="err">Has errors</option></select>
                <select class="form-select form-select-sm" id="aq_sort" style="max-width:190px"><option value="hotel">Sort: hotel A–Z</option><option value="newest">Sort: newest first</option><option value="oldest">Sort: oldest first</option><option value="start">Sort: stay start</option></select>
                <span class="help" id="aq_count"></span>
                <button class="btn btn-sm btn-light ms-auto" id="aq_clear" title="Clear the search and filters">&times; Clear</button>
            </div>
            <div class="aq-scroll"><table class="hr-table" id="aq_table"><thead><tr><th>Hotel</th><th>Plan</th><th>Type</th><th>Stay dates</th><th class="num">Seasons</th><th class="num">Rows</th><th>Checks</th><th>Entered by</th><th class="aq-act text-end">Actions</th></tr></thead><tbody>
            ${d.drafts.map(c => `<tr data-aq="${esc([c.hotel, c.name, c.source_type, c.entered_by, c.plan_type].join(' ').toLowerCase())}" data-aqtype="${esc(c.plan_type || '')}" data-aqcheck="${Number(c.errors) ? 'err' : (Number(c.warnings) ? 'warn' : 'clean')}" data-aqhotel="${esc((c.hotel || '').toLowerCase())}" data-aqat="${esc(c.updated_at || '')}" data-aqfrom="${esc(c.valid_from || '')}"><td class="fw-semibold aq-hotel">${esc(c.hotel)}</td><td class="aq-plan">${esc(c.name)}${c.docs === undefined ? '' : (Number(c.docs) ? `<span class="doc-clip" title="${c.docs} supporting document(s) — open the draft, Documents page"><i class="bi bi-paperclip"></i>${c.docs}</span>` : '<span class="doc-clip none" title="No rate sheet or contract attached yet">no document</span>')}<div class="help">${esc(c.source_type)}</div></td><td>${typePill(c.plan_type)}</td><td class="mono aq-dates">${c.valid_from} → ${c.valid_to}</td><td class="num">${c.seasons}</td><td class="num">${c.rate_rows}</td>
                <td>${Number(c.errors) ? pill('red', c.errors + ' error(s)') : ''} ${Number(c.warnings) ? pill('amber', c.warnings + ' warning(s)') : ''}${!Number(c.errors) && !Number(c.warnings) ? pill('green', 'clean') : ''}</td>
                <td class="help aq-by">${esc(c.entered_by || '')}<br>${(c.updated_at || '').slice(0, 16)}</td>
                <td class="text-nowrap text-end aq-act"><a class="btn btn-sm btn-primary" href="${BASE}?contract=${c.id}&from=queue" title="Open this draft where the rates were entered: correct the hotel name, rates, dates, seasons and supplements, save (still a draft) and come back here to approve"><i class="bi bi-pencil-square"></i> Edit</a> ${CAN_APPROVE && !Number(c.errors) ? `<button class="btn btn-sm btn-success" data-qapprove="${c.hotel_id}:${c.id}">Approve</button>` : ''}</td></tr>`).join('') || '<tr><td colspan="9"><div class="empty">Nothing waiting for approval.</div></td></tr>'}
            </tbody></table></div>
            <h6 class="mt-3">Recently approved</h6>${d.recently_approved.length ? '<ul class="small">' + d.recently_approved.map(r => `<li>${(r.approved_at || '').slice(0, 16)} — ${esc(r.hotel)}: ${esc(r.name)} <span class="help">by ${esc(r.approved_by || '')}</span></li>`).join('') + '</ul>' : '<div class="help">none yet</div>'}`;
        // tf-hr-queue-r1: filter, sort and count; the search is remembered while the page is open so Approve → reopen keeps it
        const aqState = window.__aqState = window.__aqState || { q: '', type: '', check: '', sort: 'hotel' };
        const aqEls = { q: document.getElementById('aq_q'), type: document.getElementById('aq_type'), check: document.getElementById('aq_check'), sort: document.getElementById('aq_sort') };
        Object.entries(aqEls).forEach(([k, el]) => { if (el) el.value = aqState[k]; });
        const aqApply = () => {
            Object.entries(aqEls).forEach(([k, el]) => { if (el) aqState[k] = el.value; });
            const q = aqState.q.trim().toLowerCase(), words = q.split(/\s+/).filter(Boolean);
            const tb = body.querySelector('#aq_table tbody'); if (!tb) return;
            const rows = [...tb.querySelectorAll('tr[data-aq]')];
            let n = 0;
            rows.forEach(tr => {
                const on = words.every(w => tr.dataset.aq.includes(w)) && (!aqState.type || tr.dataset.aqtype === aqState.type) && (!aqState.check || tr.dataset.aqcheck === aqState.check);
                tr.style.display = on ? '' : 'none'; if (on) n++;
            });
            const key = { hotel: tr => tr.dataset.aqhotel, newest: tr => tr.dataset.aqat, oldest: tr => tr.dataset.aqat, start: tr => tr.dataset.aqfrom }[aqState.sort] || (tr => tr.dataset.aqhotel);
            const dir = aqState.sort === 'newest' ? -1 : 1;
            rows.sort((x, y) => (key(x) < key(y) ? -1 : key(x) > key(y) ? 1 : 0) * dir).forEach(tr => tb.appendChild(tr));
            const cnt = document.getElementById('aq_count'); if (cnt) cnt.textContent = n === rows.length ? `${n} draft(s)` : `${n} of ${rows.length} draft(s) match`;
            let none = tb.querySelector('tr[data-aqnone]');
            if (!n) { if (!none) { none = document.createElement('tr'); none.dataset.aqnone = '1'; none.innerHTML = '<td colspan="9"><div class="empty">No draft matches — clear the search or a filter.</div></td>'; tb.appendChild(none); } } else if (none) { none.remove(); }
        };
        if (aqEls.q) { aqEls.q.addEventListener('input', aqApply); ['type', 'check', 'sort'].forEach(k => aqEls[k].addEventListener('change', aqApply)); document.getElementById('aq_clear').addEventListener('click', () => { Object.assign(aqState, { q: '', type: '', check: '', sort: 'hotel' }); Object.entries(aqEls).forEach(([k, el]) => { el.value = aqState[k]; }); aqApply(); aqEls.q.focus(); }); aqApply(); setTimeout(() => aqEls.q.focus(), 300); }
        body.querySelectorAll('[data-qedit]').forEach(b => b.addEventListener('click', () => { const [h, c] = b.dataset.qedit.split(':').map(Number); openQueueEdit(h, c); }));
        body.querySelectorAll('[data-qopen]').forEach(b => b.addEventListener('click', async () => { const [h, c] = b.dataset.qopen.split(':').map(Number); hideModal('#m_queue'); await selectHotel(h); openContract(c); }));
        body.querySelectorAll('[data-qapprove]').forEach(b => b.addEventListener('click', async () => { const [h, c] = b.dataset.qapprove.split(':').map(Number); if (!S.hotel || S.hotel.id !== h) { await selectHotel(h); } await approveContract(c); openQueue(); }));
    }
    document.getElementById('btn_queue').addEventListener('click', openQueue);
    // tf-hr-queue-r2: Full screen / Exit full screen for the approval queue; the choice is remembered on this computer
    (function () {
        const btn = document.getElementById('aq_full'); if (!btn) return;
        const dlg = document.querySelector('#m_queue .modal-dialog');
        const set = on => { dlg.classList.toggle('modal-fullscreen', on); dlg.classList.toggle('modal-xl', !on); btn.querySelector('i').className = on ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen'; btn.querySelector('span').textContent = on ? 'Exit full screen' : 'Full screen'; btn.title = on ? 'Back to the normal window size' : 'Use the whole browser window for the queue'; };
        let on = false; try { on = localStorage.getItem('hr2_aq_full') === '1'; } catch (e) { /* storage off */ }
        set(on);
        btn.addEventListener('click', () => { on = !dlg.classList.contains('modal-fullscreen'); set(on); try { localStorage.setItem('hr2_aq_full', on ? '1' : '0'); } catch (e) { /* storage off */ } });
    })();

    // tf-hr-queue-r1: the correction dialog — the draft is read in full, only the hotel / name / dates / note change,
    // and it is saved back as a draft through the same save the editor uses (every check runs again)
    let QE = null;
    function qeHotelList(q) {
        const sel = document.getElementById('qe_hotel'); const w = (q || '').trim().toLowerCase();
        const list = (S.hotels || []).filter(h => h.status !== 'merged' && (!w || (h.name + ' ' + (h.location_name || '')).toLowerCase().includes(w))).slice(0, 60);
        sel.innerHTML = list.map(h => `<option value="${h.id}" ${QE && Number(h.id) === Number(QE.hotelId) ? 'selected' : ''}>${esc(h.name)}${h.location_name ? ' · ' + esc(h.location_name) : ''}${Number(h.approved_plans) ? ' · ' + h.approved_plans + ' approved' : ''}</option>`).join('') || '<option disabled>No hotel matches</option>';
    }
    async function openQueueEdit(hotelId, contractId) {
        const j = await api('/contract/' + contractId); if (j.status !== 200) { toast('error', j.message); return; }
        const sheet = j.data; QE = { contractId, hotelId: Number(sheet.hotel && sheet.hotel.id || hotelId), sheet };
        document.getElementById('qe_head').innerHTML = `Draft <b>${esc(sheet.contract.name || '')}</b> — now under <b>${esc(sheet.hotel && sheet.hotel.name || '')}</b>. ${sheet.seasons ? sheet.seasons.length + ' season(s)' : ''}${sheet.contract.source_file ? ' · from <span class="mono">' + esc(sheet.contract.source_file) + '</span>' : ''}`;
        document.getElementById('qe_hotel_q').value = ''; qeHotelList('');
        document.getElementById('qe_hotel_note').textContent = 'Pick a different hotel only when the draft was read against the wrong record; the rates themselves are not changed here — use Open for those.';
        document.getElementById('qe_name').value = sheet.contract.name || '';
        document.getElementById('qe_from').value = sheet.contract.valid_from || ''; document.getElementById('qe_to').value = sheet.contract.valid_to || '';
        document.getElementById('qe_note').value = ''; document.getElementById('qe_err').textContent = '';
        showModal('#m_qedit'); setTimeout(() => document.getElementById('qe_hotel_q').focus(), 300);
    }
    document.getElementById('qe_hotel_q').addEventListener('input', e => qeHotelList(e.target.value));
    document.getElementById('qe_save').addEventListener('click', async () => {
        if (!QE) return; const btn = document.getElementById('qe_save'); if (btn.disabled) return;
        const err = document.getElementById('qe_err'); err.textContent = '';
        const sel = document.getElementById('qe_hotel'); const hid = Number(sel.value || 0);
        const name = document.getElementById('qe_name').value.trim(); const from = document.getElementById('qe_from').value, to = document.getElementById('qe_to').value;
        if (!hid) { err.textContent = 'Pick the hotel.'; return; }
        if (!name) { err.textContent = 'The plan needs a name.'; return; }
        if (from && to && from > to) { err.textContent = 'Valid from is after valid to.'; return; }
        const sheet = JSON.parse(JSON.stringify(QE.sheet));
        const h = (S.hotels || []).find(x => Number(x.id) === hid);
        const moved = hid !== QE.hotelId;
        sheet.hotel = { id: hid, name: h ? h.name : (sheet.hotel && sheet.hotel.name) };
        sheet.contract.name = name; if (from) sheet.contract.valid_from = from; if (to) sheet.contract.valid_to = to;
        const note = document.getElementById('qe_note').value.trim();
        const stamp = `Corrected in the approval queue on ${new Date().toISOString().slice(0, 10)}${moved ? ' — moved from "' + (QE.sheet.hotel && QE.sheet.hotel.name || '') + '"' : ''}${note ? ': ' + note : ''}`;
        sheet.contract.notes = ((sheet.contract.notes || '') + ' | ' + stamp).replace(/^\s*\|\s*/, '');
        if (moved) { (sheet.room_types || []).forEach(rt => { delete rt.id; delete rt.hotel_id; }); }   // room types belong to the hotel: re-created under the new one by name
        btn.disabled = true; btn.innerHTML = 'Saving…';
        const r = await api('/contract/save', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ sheet, acknowledge_warnings: true }) });
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2"></i> Save correction';
        if (r.status !== 200 || !(r.data && r.data.saved)) { err.textContent = r.message || 'Not saved.'; const issues = (r.data && r.data.issues || []).filter(i => i.level === 'error').map(i => i.message).slice(0, 3); if (issues.length) err.textContent += ' ' + issues.join(' '); return; }
        hideModal('#m_qedit'); toast('success', moved ? `Draft moved to ${h ? h.name : 'the chosen hotel'} and saved — still a draft.` : 'Draft corrected — still a draft.');
        if (S.hotel && (S.hotel.id === QE.hotelId || S.hotel.id === hid)) { selectHotel(S.hotel.id, true); }
        openQueue(); loadQueueCount();
    });

    // ---------------- settings ----------------
    async function openSettings() {
        const body = document.getElementById('settings_body'); body.innerHTML = '<div class="empty">Loading…</div>'; document.getElementById('settings_msg').textContent = ''; showModal('#m_settings');
        const j = await api('/settings'); if (j.status !== 200) { body.innerHTML = `<div class="issue error">${esc(j.message)}</div>`; return; }
        const d = j.data; const ro = !d.can_edit; S.settingsData = d;
        const groups = {}; d.settings.forEach(x => { (groups[x.group] = groups[x.group] || []).push(x); });
        const field = x => {
            const attrs = `data-set="${x.key}" ${ro ? 'disabled' : ''}`;
            if (x.type === 'market') return `<select class="form-select form-select-sm" ${attrs}>${Object.entries(x.choices || {}).map(([k, v]) => `<option value="${k}" ${k === x.value ? 'selected' : ''}>${k} — ${esc(v)}</option>`).join('')}</select>`;
            if (x.type === 'choice') return `<select class="form-select form-select-sm" ${attrs}>${Object.entries(x.choices || {}).map(([k, v]) => `<option value="${k}" ${k === x.value ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select>`;
            if (x.type === 'int' || x.type === 'number') return `<input type="number" step="${x.type === 'int' ? 1 : 0.01}" ${x.min !== undefined ? `min="${x.min}"` : ''} ${x.max !== undefined ? `max="${x.max}"` : ''} class="form-control form-control-sm" value="${esc(x.value)}" ${attrs}>`;
            return `<input class="form-control form-control-sm" value="${esc(x.value)}" ${attrs}>`;
        };
        const mkOpts = cur => Object.entries(d.markets).filter(([k]) => k !== 'ALL').map(([k, v]) => `<option value="${k}" ${k === cur ? 'selected' : ''}>${k} — ${esc(v)}</option>`).join('');
        body.innerHTML = Object.entries(groups).map(([g, items]) => `<div class="fieldset"><div class="legend">${esc(g)}</div><div class="row g-2">${items.map(x => `<div class="col-md-4"><label class="form-label">${esc(x.label)}</label>${field(x)}<div class="help">${esc(x.help || '')}</div><div class="text-danger small" data-err="${x.key}"></div></div>`).join('')}</div></div>`).join('') +
            `<div class="fieldset"><div class="legend">Country → market</div>
            <div class="help mb-2">The quotation form stores the agent's country (ISO code). Each country maps to one of the markets the hotels price for; unmapped countries use the default market above. ${ro ? '' : 'Change the market in the dropdown; a note explains special cases (e.g. a Singapore agent selling to Japanese guests).'}</div>
            <div class="d-flex gap-2 align-items-center mb-2"><input class="form-control form-control-sm" id="mc_filter" placeholder="Filter countries…" style="max-width:260px"><span class="help" id="mc_count"></span>${ro ? '' : '<button class="btn btn-sm btn-light ms-auto" id="mc_add">+ Add a country</button>'}</div>
            <div class="table-responsive" style="max-height:360px;overflow:auto"><table class="hr-table grid" id="mc_table"><thead><tr><th>Code</th><th>Country</th><th>Market</th><th>Note</th></tr></thead><tbody>
            ${d.countries.map(c => `<tr data-iso="${esc(c.country_iso)}" data-name="${esc((c.country_name || '').toLowerCase())}"><td class="mono">${esc(c.country_iso)}</td><td>${esc(c.country_name)}</td><td><select data-mc="market_code" ${ro ? 'disabled' : ''}>${mkOpts(c.market_code)}</select></td><td><input data-mc="note" value="${esc(c.note || '')}" ${ro ? 'disabled' : ''}></td></tr>`).join('')}
            </tbody></table></div>${d.countries.length ? '' : '<div class="help mt-2">No countries yet — run <span class="mono">php spark hotelrates:install</span> again to seed them from the country table, or add them here.</div>'}</div>`;
        const filt = () => { const q = document.getElementById('mc_filter').value.trim().toLowerCase(); let n = 0; body.querySelectorAll('#mc_table tbody tr').forEach(tr => { const on = !q || tr.dataset.iso.toLowerCase().includes(q) || tr.dataset.name.includes(q) || (tr.querySelector('[data-mc="market_code"]').value.toLowerCase() === q); tr.style.display = on ? '' : 'none'; if (on) n++; }); document.getElementById('mc_count').textContent = `${n} countries`; };
        document.getElementById('mc_filter').addEventListener('input', filt); filt();
        const addBtn = document.getElementById('mc_add'); if (addBtn) addBtn.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Add a country', html: '<div class="text-start small"><label>ISO code (2 letters, as the quotation form stores it)</label><input id="mc_iso" class="form-control" maxlength="2"><label class="mt-2">Country name</label><input id="mc_name" class="form-control"></div>', showCancelButton: true, preConfirm: () => [document.getElementById('mc_iso').value.trim().toUpperCase(), document.getElementById('mc_name').value.trim()] });
            if (!r.isConfirmed || !/^[A-Z]{2}$/.test(r.value[0])) return;
            const tb = body.querySelector('#mc_table tbody'); tb.insertAdjacentHTML('afterbegin', `<tr data-iso="${r.value[0]}" data-name="${esc(r.value[1].toLowerCase())}" data-new="1"><td class="mono">${r.value[0]}</td><td>${esc(r.value[1] || r.value[0])}<input type="hidden" data-mc="country_name" value="${esc(r.value[1])}"></td><td><select data-mc="market_code">${mkOpts(DEFAULT_MARKET)}</select></td><td><input data-mc="note"></td></tr>`); filt();
        });
    }
    document.getElementById('btn_settings').addEventListener('click', openSettings);
    const setSave = document.getElementById('settings_save'); if (setSave) setSave.addEventListener('click', async () => {
        const body = document.getElementById('settings_body'); const values = {}; body.querySelectorAll('[data-set]').forEach(i => values[i.dataset.set] = i.value);
        const countries = []; body.querySelectorAll('#mc_table tbody tr').forEach(tr => { const row = { country_iso: tr.dataset.iso }; tr.querySelectorAll('[data-mc]').forEach(i => row[i.dataset.mc] = i.value); const orig = (S.settingsData.countries || []).find(c => c.country_iso === tr.dataset.iso); if (!orig || orig.market_code !== row.market_code || (orig.note || '') !== (row.note || '') || tr.dataset.new) countries.push(row); });
        const j = await postA('/settings', { values, countries });
        body.querySelectorAll('[data-err]').forEach(e => e.textContent = '');
        if (j.data && j.data.errors) Object.entries(j.data.errors).forEach(([k, m]) => { const e = body.querySelector(`[data-err="${k}"]`); if (e) e.textContent = m; });
        document.getElementById('settings_msg').textContent = (j.message || '') + (j.data && j.data.country_errors && j.data.country_errors.length ? ' ' + j.data.country_errors.join(' ') : '');
        toast(j.status === 200 ? 'success' : 'error', j.message);
        if (j.status === 200) { Object.assign(SETTINGS, j.data.values || {}); S.settingsData.countries = j.data.countries || S.settingsData.countries; }
    });

    // ---------------- setup (System Administrator, without spark) ----------------
    async function setupStep(step, btn) {
        const all = document.querySelectorAll('#setup_install, #setup_dry, #setup_migrate'); all.forEach(b => b.disabled = true);
        const label = btn.innerHTML; btn.innerHTML = 'Working…';
        try {
            const j = await postA('/setup', { step });
            if (j.status !== 200) { toast('error', j.message); return; }
            if (step === 'dry_run') {
                const d = j.data; const out = document.getElementById('setup_out');
                out.innerHTML = `<div class="issue" style="background:var(--hr-blue-soft);color:var(--hr-navy)">${esc(j.message)}</div>` + (d.skipped && d.skipped.length ? `<div class="help mt-1">Rows that cannot be read (left in the old table): ${d.skipped.map(s => 'legacy #' + esc(s[0]) + ' — ' + esc(s[1])).join('; ')}${d.skipped_total > d.skipped.length ? ' …' : ''}</div>` : '') + `<div class="help mt-1">Flags: ${esc(JSON.stringify(d.flags))}</div>`;
                return;
            }
            await Swal.fire({ icon: 'success', title: step === 'install' ? 'Installed' : 'Copied across', text: j.message });
            location.reload();
        } finally { btn.innerHTML = label; all.forEach(b => b.disabled = false); }
    }
    ['setup_install:install', 'setup_dry:dry_run', 'setup_migrate:migrate'].forEach(x => { const [id, step] = x.split(':'); const b = document.getElementById(id); if (b) b.addEventListener('click', () => setupStep(step, b)); });
    const reinst = document.getElementById('settings_reinstall'); if (reinst) reinst.addEventListener('click', async () => {
        if (!await confirmBox('Re-run the installer?', 'Creates missing tables, upgrades the layout and matches the database collation. Existing plans are untouched.', 'Run')) return;
        reinst.disabled = true; const j = await postA('/setup', { step: 'install' }); reinst.disabled = false;
        document.getElementById('settings_msg').textContent = j.message + (j.data && j.data.notes && j.data.notes.length ? ' ' + j.data.notes.join(' ') : '');
        toast(j.status === 200 ? 'success' : 'error', j.message);
    });

    const costBf = document.getElementById('settings_cost_backfill'); if (costBf) costBf.addEventListener('click', async () => {
        if (!await confirmBox('Cost the confirmed tours?', 'Every confirmed tour with hotel nights from today on is priced from this panel (ad hoc rate → contract rate → quoted rate flagged) and handed to the Tour Payment Dashboard. Quotations are not changed. Safe to repeat.', 'Run')) return;
        costBf.disabled = true; const j = await postA('/setup', { step: 'cost_backfill' }); costBf.disabled = false;
        document.getElementById('settings_msg').textContent = j.message + (j.data && j.data.failed && j.data.failed.length ? ' Failed: ' + j.data.failed.join('; ') : '');
        toast(j.status === 200 ? 'success' : 'error', j.message);
    });

    // ---------------- start ----------------
    (async () => {
        await loadHotels();
        loadQueueCount();
        loadRateAlerts();
        if (PRESELECT_ACC) { const h = S.hotels.find(x => String(x.primary_sup_acc_no) === PRESELECT_ACC || String(x.sup_acc_nos || '').split(',').includes(PRESELECT_ACC)); if (h) { await selectHotel(h.id); if (PRESELECT_TAB && OFFERS_READY) showTab(PRESELECT_TAB); } else toast('error', `No hotel with the supplier account ${PRESELECT_ACC} on the Hotel Master yet.`); }
        if (PRESELECT_CONTRACT) { const j = await api('/contract/' + PRESELECT_CONTRACT); if (j.status === 200 && j.data.hotel && j.data.hotel.id) { await selectHotel(j.data.hotel.id); await openContract(PRESELECT_CONTRACT); } else toast('error', j.message || 'That rate plan was not found.'); }
        else if (APPROVAL_MODE) openQueue();
        // tf-refresh-r1: any tab named in the address (Coverage, Room types, Hotel details, History too), and the place this tab was at
        const T0 = new URLSearchParams(location.search).get('tab');
        if (S.hotel && T0 && T0 !== PRESELECT_TAB && document.querySelector('.hr-tab[data-tab="' + T0 + '"]')) showTab(T0);
        if (window.TfView) TfView.restoreScroll();
    })();
})();
</script>
</body>
</html>
