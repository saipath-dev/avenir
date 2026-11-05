<?= view('layouts/head') ?>
<style>
    /* ---------- Tour Inquiry (same tokens as the Hotel Rates panel) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#b7791f; --tf-amber-soft:#fdf3e1;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
    .tf .tf-toolbar { display:flex; gap:8px; flex-wrap:wrap; }

    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-primary:hover { background:var(--tf-navy-2); border-color:var(--tf-navy-2); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .sugwrap { position:relative; }
    .tf .sugg { position:absolute; left:0; right:0; top:100%; z-index:1080; background:#fff; border:1px solid var(--tf-line); border-radius:8px; box-shadow:0 8px 24px -8px rgba(16,24,40,.25); max-height:280px; overflow-y:auto; margin-top:3px; }
    .tf .sugg .it { padding:7px 10px; cursor:pointer; font-size:12.5px; border-bottom:1px solid var(--tf-line); }
    .tf .sugg .it:last-child { border-bottom:0; }
    .tf .sugg .it:hover, .tf .sugg .it.on { background:var(--tf-teal-soft); }
    .tf .sugg .it b { display:block; }
    .tf .sugg .it .help { display:block; }
    .tf .sugg .none { padding:8px 10px; font-size:12px; color:var(--tf-muted); }
    .tf .pill { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }

    /* Day services: one chip per thing that happens that day. */
    .tf .svcs { display:flex; flex-wrap:wrap; gap:4px; align-items:center; }
    .tf .svc { display:inline-flex; align-items:center; gap:5px; max-width:100%; font-size:11px; font-weight:600;
               padding:2px 7px; border-radius:5px; background:#eef1f5; color:var(--tf-navy); border:1px solid transparent; }
    .tf .svc.priced { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .svc.offlist { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .svc.declined { background:#f6f7f9; color:var(--tf-muted); text-decoration:line-through; }
    .tf .svc.free { background:#eef7f0; color:var(--tf-green); border:1px dashed #b9dcc4; }
    .tf .svc .amt { font-weight:700; opacity:.85; }
    .tf .svc-add { font-size:11px; font-weight:600; color:var(--tf-teal); background:none; border:1px dashed #c9d3de;
                   border-radius:5px; padding:2px 7px; cursor:pointer; }
    .tf .svc-add:hover { border-color:var(--tf-teal); background:var(--tf-green-soft); }
    .tf .daytot { font-size:11px; color:var(--tf-muted); margin-top:3px; }
    /* schema 40: the day's lunch place, from the Meal Master */
    .tf .lp { display:flex; flex-wrap:wrap; align-items:center; gap:4px; margin-top:4px; font-size:11.5px; }
    .tf .lp-k { font-size:9.5px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; }
    .tf .lp-r { display:inline-flex; flex-wrap:wrap; align-items:center; gap:4px; }
    .tf .lp-amt { font-weight:700; color:var(--tf-green); }
    .tf .lp-auto { font-size:9.5px; font-weight:700; color:var(--tf-teal); border:1px solid #b9dcd6; border-radius:4px; padding:0 4px; }
    .tf .lp .btn { padding:0 6px; font-size:11px; line-height:18px; }
    .tf .notecell { font-size:11.5px; color:var(--tf-navy); cursor:pointer; }
    .tf .notecell.empty-note { color:#b6bfca; font-style:italic; }
    /* schema 32: the dates line, a line on the departure day, the departure day itself */
    .tf #dates_bar { margin: 0 0 10px; }
    .tf .datesline { font-size: 15px; font-weight: 600; color: var(--tf-navy); padding: 8px 12px; background: #fff; border: 1px solid #e3e8ef; border-radius: 8px; }
    .tf .datewarn { margin-top: 8px; padding: 9px 12px; border-radius: 8px; background: #fdecec; border: 1px solid #f3c2c2; font-size: 13px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .tf .datenote { margin-top: 6px; font-size: 12px; color: #5b6b7f; }
    .tf tr.deprow td { background: #fdf3f3; }
    .tf tr.depday td { background: #f4f8fd; }
    .tf .depwords { cursor: pointer; }
    /* Entrance & Activities: a line that stops the quotation is tinted until a person settles it */
    .tf tr.ea-stop td { background:#fff9ec; }
    .tf .svcline { display:flex; gap:8px; align-items:flex-start; padding:7px 0; border-bottom:1px solid #eef1f5; }
    .tf .svcline:last-child { border-bottom:0; }
    .tf .svcline .grow { flex:1; min-width:0; }
    /* the day programme from the two masters (schema 29) */
    .tf .mm-h { display:flex; gap:6px; align-items:center; margin-bottom:4px; }
    .tf .mm-list { max-height:260px; overflow:auto; border:1px solid var(--tf-line); border-radius:8px; }
    .tf .mmrow { display:flex; gap:8px; align-items:center; padding:6px 8px; border-bottom:1px solid #eef1f5; font-size:12.5px; }
    .tf .mmrow:last-child { border-bottom:0; }
    .tf .mmrow .grow { flex:1; min-width:0; }
    .tf .mmrow .dest { color:var(--tf-muted); font-size:11px; }
    .tf .mm-empty { padding:10px; color:var(--tf-muted); font-size:12px; }
    /* vehicle and crew: one line each, read across */
    .tf .crewrow { display:grid; grid-template-columns:2.2fr 1fr .7fr 1.1fr 1.2fr; gap:8px; align-items:center;
                   padding:7px 0; border-bottom:1px solid #eef1f5; font-size:12.5px; }
    .tf .crewrow:last-of-type { border-bottom:0; }
    .tf .crewrow.head { font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; color:var(--tf-muted); font-weight:700; border-bottom:1px solid #e3e8ee; }
    @media (max-width: 720px) { .tf .crewrow { grid-template-columns:1fr 1fr; } .tf .crewrow.head { display:none; } }
    .tf .accom { border:1px solid #e3e8ee; border-radius:8px; padding:11px 13px; height:100%; }
    .tf .accom.on { border-color:var(--tf-teal); background:var(--tf-green-soft); }
    .tf .accom .crewrow { grid-template-columns:1.1fr 1.4fr .5fr .9fr 1.6fr; font-size:11.5px; }
    .tf .dayedit { cursor:pointer; border-bottom:1px dashed var(--tf-line); }
    .tf .mealpick { padding:0 .4rem; font-size:.75rem; line-height:1.4; margin:1px 0; border:1px solid var(--tf-line); }
    .tf .mealpick:hover { border-color:var(--tf-navy); color:var(--tf-navy); }
    .tf .rateline.drift { margin-top:2px; }
    .tf .reprice { padding:0 .4rem; font-size:.75rem; line-height:1.4; border:1px solid var(--tf-line); }
    .tf .dayedit:hover { color:var(--tf-navy); border-bottom-color:var(--tf-navy); }
    .tf .rowdel { color:#b6bfca; padding:0 6px; line-height:1.1; font-size:15px; }
    .tf .rowdel:hover { color:var(--tf-red); background:var(--tf-red-soft); }
    .tf .tf-card-h .ms-auto { margin-left:auto; }
    /* the one banner that tells staff where they stand */
    .tf .bnr { display:flex; gap:10px; align-items:flex-start; padding:11px 14px; border-radius:8px; margin-bottom:12px; font-size:12.5px; }
    .tf .bnr b { display:block; margin-bottom:2px; }
    .tf .bnr .acts { margin-left:auto; display:flex; gap:6px; white-space:nowrap; }
    .tf .bnr-amber { background:var(--tf-amber-soft); color:var(--tf-amber); border:1px solid rgba(183,121,31,.25); }
    .tf .bnr-blue { background:var(--tf-blue-soft); color:var(--tf-navy); border:1px solid rgba(30,53,83,.15); }
    .tf .bnr-green { background:var(--tf-green-soft); color:var(--tf-green); border:1px solid rgba(31,138,76,.2); }
    /* a confirmed inquiry: everything is still readable, nothing is editable */
    .tf .locked .fld, .tf .locked .btn, .tf .locked input, .tf .locked select, .tf .locked textarea, .tf .locked [data-day], .tf .locked [data-mealpick], .tf .locked [data-reprice], .tf .locked [data-req], .tf .locked [data-svc], .tf .locked .rowdel { pointer-events:none; opacity:.55; }
    .tf .locked .tf-card { background:#fbfcfd; }

    /* steps */
    .tf .steps { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .tf .steps .st { flex:1 1 200px; display:flex; gap:10px; align-items:center; padding:9px 12px; border:1px solid var(--tf-line); border-radius:8px; background:var(--tf-card); color:var(--tf-muted); }
    .tf .steps .st.on { background:var(--tf-blue-soft); color:var(--tf-ink); border-color:transparent; }
    .tf .steps .st .n { width:22px; height:22px; flex:none; border-radius:50%; display:grid; place-items:center; font-size:11px; background:#eef1f5; color:var(--tf-muted); }
    .tf .steps .st.on .n { background:var(--tf-navy); color:#fff; }
    .tf .steps .st b { font-size:12.5px; font-weight:600; display:block; line-height:1.25; }
    .tf .steps .st span { font-size:11.5px; }
    /* the workflow bar on an inquiry: Inquiry → Quotation → Confirm Tour, each step with its state and its action */
    .tf .steps.flow .st { align-items:flex-start; padding:10px 12px; }
    .tf .steps.flow .st.done { background:var(--tf-green-soft); color:var(--tf-ink); border-color:transparent; }
    .tf .steps.flow .st.done .n { background:var(--tf-green); color:#fff; }
    .tf .steps.flow .st.go { background:#fff; border:2px solid var(--tf-green); color:var(--tf-ink); }
    .tf .steps.flow .st.go .n { background:var(--tf-green); color:#fff; }
    .tf .steps.flow .st .body { flex:1 1 auto; min-width:0; }
    .tf .steps.flow .st .acts { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
    .tf .steps.flow .st .acts .btn { font-size:12px; }
    .tf .steps.flow .st .sub { display:block; font-size:11.5px; color:var(--tf-muted); }
    .tf .steps.flow .st.done .sub, .tf .steps.flow .st.go .sub { color:var(--tf-ink); opacity:.8; }

    /* summary */
    .tf .summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:1px; background:var(--tf-line); border:1px solid var(--tf-line); border-radius:var(--tf-radius); overflow:hidden; margin-bottom:14px; }
    .tf .sm { background:var(--tf-card); padding:9px 12px; }
    .tf .sm .k { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:600; }
    .tf .sm .v { font-size:14.5px; font-weight:600; }
    .tf .sm .v small { display:block; font-weight:400; font-size:11.5px; color:var(--tf-muted); }
    .tf .sm.flag { background:var(--tf-amber-soft); } .tf .sm.flag .k, .tf .sm.flag .v { color:var(--tf-amber); }

    /* review grid */
    .tf .review { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(0,1fr); gap:14px; align-items:start; }
    @media (max-width:1100px){ .tf .review { grid-template-columns:minmax(0,1fr);} .tf .mailbox { position:static !important; max-height:420px !important; } .tf .sidecol { position:static !important; max-height:none !important; overflow:visible !important; } }
    @media (max-width:700px){ .tf .erwords { grid-template-columns:1fr; } }
    .tf .col { display:flex; flex-direction:column; gap:14px; min-width:0; }

    /* fields */
    .tf .fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:1px; background:var(--tf-line); }
    .tf .fld { background:var(--tf-card); border:0; border-left:3px solid var(--tf-teal); padding:9px 11px; text-align:left; cursor:pointer; min-width:0; }
    .tf .fld:hover { background:var(--tf-bg); }
    .tf .fld .k { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:600; display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .tf .fld .v { font-size:13px; font-weight:500; margin-top:2px; overflow-wrap:break-word; }
    .tf .fld.missing { border-left-color:var(--tf-amber); background:var(--tf-amber-soft); }
    .tf .fld.missing .v { color:var(--tf-amber); font-weight:600; }
    .tf .fld.unverified { border-left-color:var(--tf-red); }
    .tf .fld.derived { border-left-color:var(--tf-navy); }
    .tf .fld.human { border-left-color:var(--tf-navy); }
    .tf .tag { font-size:9.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; padding:1px 5px; border-radius:3px; }
    .tf .tag-src { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .tag-ask { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .tag-you { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .tag-bad { background:var(--tf-red-soft); color:var(--tf-red); }

    /* tables */
    .tf table.tbl { width:100%; font-size:12.5px; border-collapse:collapse; }
    .tf table.tbl th { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:600; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf table.tbl td { padding:8px 10px; border-bottom:1px solid #eef2f6; vertical-align:top; }
    .tf table.tbl tr:last-child td { border-bottom:0; }
    .tf table.tbl tbody tr:hover { background:var(--tf-bg); }
    /* schema 40: the Meals card's day table — its own class, so nothing that reads the itinerary's or the mileage's tables picks up its rows */
    .tf table.mtbl { width:100%; font-size:12.5px; border-collapse:collapse; }
    .tf table.mtbl th { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:600; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; text-align:left; }
    .tf table.mtbl td { padding:8px 10px; border-bottom:1px solid #eef2f6; vertical-align:top; }
    .tf table.mtbl tr:last-child td { border-bottom:0; }
    .tf table.mtbl tbody tr:hover { background:var(--tf-bg); }
    .tf .empty { text-align:center; color:var(--tf-muted); padding:22px; font-size:12.5px; }
    .tf .mono { font-family:ui-monospace,Consolas,monospace; font-size:11.5px; }

    /* email pane */
    .tf .mailbox { position:sticky; top:80px; max-height:calc(100vh - 110px); display:flex; flex-direction:column; }
    .tf .mailbox .tf-card-b { padding:0; overflow:auto; }
    /* schema 33: the email and the Edit Request below it, side by side with the inquiry */
    .tf .sidecol { display:flex; flex-direction:column; gap:14px; min-width:0; position:sticky; top:80px; max-height:calc(100vh - 110px); overflow:auto; }
    .tf .sidecol .mailbox { position:static; max-height:none; flex:0 1 auto; min-height:0; }
    .tf .sidecol .mailbox .tf-card-b { max-height:52vh; }
    .tf .ercard .tf-card-b { padding:12px 14px; }
    .tf .erlab { font-size:10.5px; letter-spacing:.06em; text-transform:uppercase; font-weight:700; color:var(--tf-navy); margin:0 0 4px; display:flex; gap:8px; align-items:center; }
    .tf .ercard textarea { font-family:ui-monospace,Consolas,monospace; font-size:12px; }
    .tf .erlist { margin-top:12px; border-top:1px solid var(--tf-line); }
    .tf .errow { display:grid; grid-template-columns:auto 1fr auto; gap:8px; padding:8px 0; border-bottom:1px solid #eef2f6; font-size:12px; align-items:start; }
    .tf .errow:last-child { border-bottom:0; }
    .tf .errow .words { color:var(--tf-muted); overflow-wrap:anywhere; }
    .tf .errow .words b { color:var(--tf-ink); font-weight:600; }
    .tf .ernote { background:var(--tf-bg); border-radius:7px; padding:8px 10px; font-size:12px; margin-bottom:10px; }
    .tf .ernote.warn { background:var(--tf-amber-soft); color:var(--tf-ink); }
    .tf .ernote.stop { background:var(--tf-red-soft); color:var(--tf-ink); }
    /* the check: Current → New, and everything that follows */
    .tf .erwords { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px; }
    .tf .erwords pre { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; font-size:11.5px; background:var(--tf-bg); border-radius:7px; padding:8px 10px; max-height:160px; overflow:auto; }
    .tf .ercmp { width:100%; font-size:12.5px; border-collapse:collapse; margin-bottom:12px; }
    .tf .ercmp th { font-size:10px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:600; padding:6px 8px; border-bottom:1px solid var(--tf-line); text-align:left; }
    .tf .ercmp td { padding:7px 8px; border-bottom:1px solid #eef2f6; vertical-align:top; overflow-wrap:anywhere; }
    .tf .ercmp td.from { color:var(--tf-muted); text-decoration:line-through; text-decoration-color:rgba(180,35,24,.45); }
    .tf .ercmp td.to { font-weight:600; }
    .tf .ercmp td.same { color:var(--tf-muted); font-style:italic; }
    .tf .ercmp .note { display:block; font-weight:400; color:var(--tf-muted); font-size:11.5px; margin-top:3px; font-style:normal; }
    .tf .ersec { font-size:11px; letter-spacing:.06em; text-transform:uppercase; font-weight:700; color:var(--tf-navy); margin:14px 0 6px; }
    .tf .erq { border:1px solid var(--tf-line); border-radius:8px; padding:10px 12px; margin-bottom:10px; }
    .tf .erq label { display:flex; gap:8px; align-items:flex-start; font-size:12.5px; padding:3px 0; cursor:pointer; }
    .tf pre.mail { margin:0; padding:12px 14px; font-family:ui-monospace,Consolas,monospace; font-size:11.5px; line-height:1.7; white-space:pre-wrap; overflow-wrap:break-word; color:var(--tf-muted); }
    .tf pre.mail mark { background:var(--tf-teal-soft); color:var(--tf-ink); padding:1px 2px; border-radius:2px; }

    .tf .reqrow { display:grid; grid-template-columns:auto 1fr auto; gap:10px; padding:9px 0; border-bottom:1px solid #eef2f6; align-items:start; }
    .tf .reqrow:last-child { border-bottom:0; }
    .tf .reqrow .dot { width:7px; height:7px; border-radius:50%; margin-top:6px; background:var(--tf-teal); }
    .tf .reqrow.q .dot { background:var(--tf-amber); }
    .tf .reqrow q { display:block; font-style:normal; color:var(--tf-muted); font-size:12px; margin-top:2px; }
    .tf .paxgrid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:7px; }
    /* the itinerary as the quotation says it */
    .tf .itinline { display:flex; gap:10px; align-items:flex-start; padding:6px 4px; border-bottom:1px solid var(--tf-line); font-size:12.5px; line-height:1.5; }
    .tf .itinline:last-child { border-bottom:0; }
    .tf .itinline.edit { cursor:pointer; }
    .tf .itinline.edit:hover { background:var(--tf-bg); }
    .tf .itinline .dayno { flex:0 0 52px; color:var(--tf-muted); font-size:11.5px; padding-top:2px; }
    .tf .itinline .txt { flex:1 1 auto; }
    /* the day pictures for the client quotation (Master Files → Tour Photos) */
    .tf .photostrip { display:flex; gap:8px; overflow-x:auto; padding-bottom:4px; }
    .tf .phtile { flex:0 0 132px; border:1px solid var(--tf-line); border-radius:8px; background:#fff; cursor:pointer; overflow:hidden; }
    .tf .phtile:hover { border-color:var(--tf-navy); box-shadow:0 1px 6px rgba(16,24,40,.12); }
    .tf .phtile .pic { width:132px; height:88px; object-fit:cover; display:block; background:var(--tf-bg); }
    .tf .phtile .nopic { width:132px; height:88px; display:flex; align-items:center; justify-content:center; color:var(--tf-muted); font-size:11.5px; background:var(--tf-bg); text-align:center; padding:6px; }
    .tf .phtile .cap { padding:5px 7px; font-size:11px; line-height:1.35; }
    .tf .phtile .cap b { display:block; font-size:11.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .tf .phgrid { display:grid; grid-template-columns:repeat(auto-fill,minmax(128px,1fr)); gap:8px; max-height:52vh; overflow:auto; padding:2px; }
    .tf .phpick { border:2px solid transparent; border-radius:8px; cursor:pointer; overflow:hidden; background:#fff; position:relative; }
    .tf .phpick:hover { border-color:var(--tf-navy-2); }
    .tf .phpick.on { border-color:var(--tf-teal); box-shadow:0 0 0 2px var(--tf-teal-soft); }
    .tf .phpick img { width:100%; height:86px; object-fit:cover; display:block; background:var(--tf-bg); }
    .tf .phpick .cap { font-size:10.5px; padding:4px 6px; color:var(--tf-ink); line-height:1.3; }
    .tf .phpick .star { position:absolute; top:4px; left:6px; color:#f0b429; font-size:13px; text-shadow:0 0 3px #fff; }
    .tf .chip { display:inline-block; font-size:11px; border-radius:20px; padding:2px 9px; border:1px solid var(--tf-line); background:#fff; cursor:pointer; margin:0 4px 4px 0; }
    .tf .chip.on { background:var(--tf-navy); color:#fff; border-color:var(--tf-navy); }
    .tf .paxgrid div { background:var(--tf-bg); border-radius:7px; padding:7px 9px; font-size:12.5px; display:flex; justify-content:space-between; gap:8px; }
    .tf .gaps div { display:flex; gap:9px; font-size:12.5px; padding:6px 0; border-bottom:1px solid #eef2f6; }
    .tf .gaps div:last-child { border-bottom:0; }
    /* the engine: vehicle lines, groups, legs by vehicle */
    .tf .crewrow.vrow { grid-template-columns:1.6fr 1fr .7fr 1.4fr 1fr 1.5fr; }
    .tf .crewrow.crow { grid-template-columns:1.8fr .5fr 1.1fr 1.1fr 1.6fr 1.6fr; }
    .tf .crewrow.grow { grid-template-columns:1.8fr .8fr .9fr 1.4fr .6fr 1fr; }
    .tf table.tbl tr.vhead td { background:var(--tf-blue-soft); color:var(--tf-navy); font-weight:600; font-size:11.5px; padding:5px 10px; }
    .tf table.tbl tr.released td { color:var(--tf-muted); background:#fbfcfd; }
    .tf table.tbl tr.waived td:nth-child(4), .tf table.tbl tr.waived td:nth-child(5) { color:var(--tf-muted); text-decoration:line-through; }
    .tf .paxgrid div.paxcard { flex-direction:column; align-items:stretch; gap:5px; }
    .tf .paxgrid div.paxcard .top { display:flex; justify-content:space-between; gap:8px; align-items:center; }
    .tf .goline { display:flex; gap:9px; font-size:12.5px; padding:6px 0; border-bottom:1px solid #eef2f6; align-items:flex-start; }
    .tf .goline:last-child { border-bottom:0; }
    /* the checklist: one line per thing to fix, a Fix button that jumps to its source */
    .tf .goline .fixb { margin-left:auto; flex:none; white-space:nowrap; }
    .tf .goline .fixhint { color:var(--tf-muted); font-size:11.5px; display:block; margin-top:2px; }
    .tf .goline.fixed span:nth-child(2) { text-decoration:line-through; color:var(--tf-muted); }
    .tf .chk-ready { padding:14px 16px; border-radius:10px; background:var(--tf-green-soft); color:var(--tf-green); font-size:14px; font-weight:600; display:flex; gap:10px; align-items:center; }
    .chk-target { outline:3px solid var(--tf-amber) !important; outline-offset:3px; border-radius:6px; transition:outline-color .6s; }
    #chk_pill { position:fixed; right:22px; bottom:22px; z-index:1055; background:var(--tf-navy); color:#fff; border:0; border-radius:24px; padding:9px 16px; font-size:13px; font-weight:600; box-shadow:0 4px 14px rgba(16,24,40,.25); display:none; }
    #chk_pill:hover { background:var(--tf-navy-2); }
    #chk_pill .n { background:#fff; color:var(--tf-navy); border-radius:12px; padding:1px 8px; margin-left:6px; }
    .tf .rateline { font-size:11.5px; color:var(--tf-navy); margin-top:3px; }
    .tf .rateline b { font-weight:600; }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    @media (max-width: 720px) { .tf .crewrow.vrow, .tf .crewrow.grow, .tf .crewrow.crow { grid-template-columns:1fr 1fr; } }
    /* ---------- tf-mileage-track-r1 (10 Oct 2026): Mileage by sector — the Railway view ---------- */
    .tf .mv-switch { display:inline-flex; border:1px solid var(--tf-line); border-radius:8px; overflow:hidden; background:#fff; }
    .tf .mv-switch > button, .tf .mv-switch > a { border:0; background:#fff; color:var(--tf-ink); font-size:12px; font-weight:600; padding:5px 11px;
        display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer; white-space:nowrap; }
    .tf .mv-switch > * + * { border-left:1px solid var(--tf-line); }
    .tf .mv-switch > .on { background:var(--tf-navy); color:#fff; }
    .tf .mv-switch > :not(.on):hover { background:var(--tf-bg); }
    .tf .trk-wrap { position:relative; --pich:46px; --ly:calc(42px + var(--pich)); }
    .tf .trk-veh { font-weight:600; font-size:12.5px; color:var(--tf-navy); margin:6px 0 2px; }
    .tf .trk-line { display:flex; flex-wrap:wrap; row-gap:14px; padding:6px 0 4px; }
    .tf .trk-grp { display:flex; flex:1 1 168px; min-width:166px; max-width:560px; }
    .tf .trk-grp.end { flex:0 0 auto; min-width:0; }
    /* a station: its day and date, its picture, the dot on the line, its name */
    .tf .trk-stop { position:relative; flex:0 0 104px; width:104px; display:flex; flex-direction:column; align-items:center; text-align:center; }
    .tf .trk-stop::before { content:''; position:absolute; top:calc(var(--ly) - 1.5px); left:0; right:0; height:3px; background:var(--tf-navy); z-index:0; }
    .tf .trk-stop.first::before { left:50%; }
    .tf .trk-stop.last::before { right:50%; }
    .tf .trk-stop.first.last::before { display:none; }
    .tf .trk-day { display:inline-flex; align-items:center; gap:5px; height:20px; margin-bottom:6px; padding:0 8px; border-radius:6px; background:var(--tf-blue-soft);
        color:var(--tf-navy); font-size:11px; font-weight:700; white-space:nowrap; }
    .tf .trk-day::before { content:''; width:7px; height:7px; border-radius:50%; background:var(--c, var(--tf-navy)); }
    .tf .trk-day small { font-weight:600; color:var(--tf-muted); font-size:10.5px; }
    .tf .trk-st { position:relative; z-index:1; background:none; border:0; padding:0; display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; }
    .tf .trk-pic { width:62px; height:var(--pich); border-radius:9px; background:#eef2f7 center/cover no-repeat; border:2px solid #fff;
        box-shadow:0 1px 4px rgba(16,24,40,.22); display:flex; align-items:center; justify-content:center; transition:transform .12s, box-shadow .12s; }
    .tf .trk-ic { background:#f3f6fa; }
    .tf .trk-ic i { font-size:21px; color:var(--tf-navy); }
    .tf .trk-dot { width:16px; height:16px; border-radius:50%; background:#fff; border:4px solid var(--c, var(--tf-navy)); box-shadow:0 0 0 2px #fff; }
    .tf .trk-st:hover .trk-pic, .tf .trk-st.on .trk-pic, .tf .trk-st:focus-visible .trk-pic { transform:translateY(-2px); box-shadow:0 5px 12px rgba(16,24,40,.28); }
    .tf .trk-st:focus-visible { outline:none; }
    .tf .trk-st.on .trk-dot { box-shadow:0 0 0 3px #fff, 0 0 0 5px var(--c, var(--tf-navy)); }
    .tf .trk-name { margin-top:6px; font-size:12.5px; font-weight:600; line-height:1.2; max-width:102px; overflow-wrap:anywhere; }
    .tf .trk-name small { display:block; font-size:10.5px; font-weight:500; color:var(--tf-muted); }
    .tf .trk-name .trk-nts { color:var(--tf-navy); font-weight:600; margin-top:1px; }
    .tf .trk-far { color:var(--tf-amber); font-size:10px; vertical-align:1px; }
    .tf .trk-legend { margin-top:10px; display:flex; gap:6px 16px; flex-wrap:wrap; font-size:11px; color:var(--tf-muted); }
    .tf .trk-legend > span { display:inline-flex; align-items:center; gap:6px; }
    .tf .trk-lg { display:inline-block; width:22px; height:3px; border-radius:2px; }
    .tf .trk-lg.dash { background:repeating-linear-gradient(90deg, var(--lc) 0 5px, transparent 5px 8px); }
    .tf .trk-loops { display:flex; flex-direction:column; align-items:center; gap:3px; margin-top:5px; }
    .tf .trk-loop { border:1px dashed #b9c6d6; background:#f7f9fc; border-radius:12px; font-size:10.5px; font-weight:600; padding:1px 8px; color:var(--tf-navy); cursor:pointer; white-space:nowrap; }
    .tf .trk-loop:hover, .tf .trk-loop.on { background:var(--tf-blue-soft); border-style:solid; }
    .tf .trk-loop.none { border-color:var(--tf-red); color:var(--tf-red); }
    .tf .trk-loop.off { opacity:.55; text-decoration:line-through; }
    /* a sector: the kilometres on the line between two stations */
    .tf .trk-seg { position:relative; flex:1 1 60px; min-width:64px; min-height:calc(var(--ly) + 24px); background:none; border:0; padding:0; cursor:pointer; }
    .tf .trk-rail { position:absolute; left:0; right:0; top:calc(var(--ly) - 1.5px); height:3px; background:var(--tf-navy); }
    .tf .trk-km { position:absolute; left:50%; top:calc(var(--ly) - 27px); transform:translateX(-50%); padding:1px 7px; border-radius:6px; background:#fff;
        font-size:12.5px; font-weight:700; color:var(--tf-ink); white-space:nowrap; transition:background .12s; }
    .tf .trk-seg:hover .trk-km, .tf .trk-seg.on .trk-km, .tf .trk-seg:focus-visible .trk-km { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .trk-seg:hover .trk-rail, .tf .trk-seg.on .trk-rail { height:5px; top:calc(var(--ly) - 2.5px); }
    .tf .trk-seg:focus-visible { outline:none; }
    .tf .trk-seg.garage .trk-rail { background:repeating-linear-gradient(90deg, var(--tf-muted) 0 7px, transparent 7px 11px); }
    .tf .trk-seg.none .trk-rail { background:repeating-linear-gradient(90deg, var(--tf-red) 0 7px, transparent 7px 11px); }
    .tf .trk-seg.none .trk-km { color:var(--tf-red); }
    .tf .trk-seg.waived .trk-rail { background:repeating-linear-gradient(90deg, var(--tf-green) 0 7px, transparent 7px 11px); }
    .tf .trk-seg.off .trk-rail { background:repeating-linear-gradient(90deg, #c3ccd6 0 3px, transparent 3px 7px); }
    .tf .trk-seg.off .trk-km { color:var(--tf-muted); font-weight:500; }
    .tf .trk-seg.gap { cursor:default; flex:0 0 46px; min-width:46px; }
    .tf .trk-seg.gap .trk-rail { background:repeating-linear-gradient(90deg, #c3ccd6 0 2px, transparent 2px 6px); }
    /* the window that opens on a station or a sector */
    .tf .trk-pop { position:absolute; z-index:1070; background:#fff; border:1px solid var(--tf-line); border-radius:12px; box-shadow:0 18px 44px -12px rgba(16,24,40,.38);
        max-height:72vh; overflow:auto; }
    .tf .trk-pop.sheet { position:fixed; left:8px !important; right:8px; bottom:8px; top:auto !important; width:auto !important; max-height:78vh; }
    .tf .trk-x { position:absolute; right:8px; top:6px; z-index:2; border:0; background:none; font-size:20px; line-height:1; color:var(--tf-muted); padding:4px 6px; }
    .tf .trk-x:hover { color:var(--tf-ink); }
    .tf .trk-pop:not(.pinned) .trk-x { display:none; }
    .tf .trk-pop .trk-tip { font-size:11px; color:var(--tf-muted); padding:0 14px 10px; }
    .tf .trk-pop.pinned .trk-tip { display:none; }
    .tf .trk-dh { display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:10px 40px 10px 14px; background:var(--tf-blue-soft); border-bottom:1px solid var(--tf-line); font-size:14px; }
    .tf .trk-dh .trk-day { margin:0; }
    .tf .trk-kv { width:100%; font-size:12.5px; border-collapse:collapse; }
    .tf .trk-kv th { width:112px; color:var(--tf-muted); font-weight:500; padding:7px 10px 7px 14px; vertical-align:top; white-space:nowrap; }
    .tf .trk-kv td { padding:7px 14px 7px 0; vertical-align:top; }
    .tf .trk-kv tr + tr > * { border-top:1px solid #eef1f5; }
    .tf .trk-act { display:flex; gap:6px; align-items:center; flex-wrap:wrap; padding:8px 14px 12px; border-top:1px solid #eef1f5; }
    .tf .trk-photo { display:block; width:100%; height:160px; object-fit:cover; }
    .tf .trk-cap { font-size:11px; color:var(--tf-muted); padding:5px 14px 0; }
    /* the days, one line each, opened for the sectors of that day */
    .tf .trk-days { margin-top:12px; display:flex; flex-direction:column; gap:6px; }
    .tf .trk-dayrow { border:1px solid var(--tf-line); border-radius:9px; background:#fff; }
    .tf .trk-dayrow > summary { list-style:none; display:flex; align-items:center; gap:12px; padding:8px 12px; cursor:pointer; font-size:13px; }
    .tf .trk-dayrow > summary::-webkit-details-marker { display:none; }
    .tf .trk-dayrow > summary > .bi-chevron-right { transition:transform .15s; color:var(--tf-muted); }
    .tf .trk-dayrow[open] > summary > .bi-chevron-right { transform:rotate(90deg); }
    .tf .trk-dayrow .trk-day { margin:0; }
    .tf .trk-route { flex:1; min-width:0; font-weight:600; }
    .tf .trk-dkm { min-width:84px; text-align:right; font-weight:700; font-variant-numeric:tabular-nums; }
    .tf .trk-cnt { font-size:11.5px; background:#eef1f5; color:var(--tf-muted); border-radius:14px; padding:2px 10px; white-space:nowrap; }
    .tf .trk-dbody { padding:2px 12px 10px 36px; display:flex; flex-direction:column; gap:2px; }
    .tf .trk-dline { display:flex; align-items:center; gap:8px; width:100%; border:0; background:none; text-align:left; padding:5px 8px; border-radius:6px; font-size:12.5px; }
    .tf button.trk-dline { cursor:pointer; }
    .tf button.trk-dline:hover, .tf .trk-dline.on { background:var(--tf-blue-soft); }
    .tf .trk-dline.off { opacity:.55; text-decoration:line-through; }
    .tf .trk-total { margin-top:12px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; padding:12px 16px; border-radius:10px; background:var(--tf-blue-soft); border:1px solid var(--tf-line); }
    .tf .trk-total > i { font-size:24px; color:var(--tf-navy); }
    .tf .trk-total b { font-size:15px; color:var(--tf-navy); }
    .tf .trk-total > div:nth-child(2) { flex:1 1 260px; min-width:0; }
    .tf .trk-tot-r { display:flex; flex-direction:column; align-items:flex-end; gap:4px; margin-left:auto; }
    .tf .trk-tkm { font-size:22px; font-weight:800; color:var(--tf-navy); font-variant-numeric:tabular-nums; line-height:1.1; white-space:nowrap; }
    .tf .trk-act .rowdel { font-size:12px; line-height:1.5; padding:.25rem .5rem; color:var(--tf-red); }
    .tf .trk-warn { margin-top:8px; display:flex; gap:6px; flex-wrap:wrap; }
    .tf .trk-more { margin-top:10px; border-top:1px dashed var(--tf-line); padding-top:6px; }
    .tf .trk-more > summary { cursor:pointer; font-size:12.5px; font-weight:600; color:var(--tf-navy); padding:4px 0; }
    @media (max-width: 575px) {
        .tf .trk-wrap { --pich:40px; }
        .tf .trk-line { row-gap:10px; }
        .tf .trk-grp { flex:1 1 46%; min-width:140px; max-width:50%; }
        .tf .trk-grp.end { flex:0 0 auto; max-width:none; }
        .tf .trk-stop { flex-basis:84px; width:84px; }
        .tf .trk-seg { min-width:50px; }
        .tf .trk-pic { width:52px; }
        .tf .trk-name { max-width:86px; font-size:12px; }
        .tf .trk-dayrow > summary { gap:8px; flex-wrap:wrap; }
        .tf .trk-route { flex-basis:100%; order:3; font-size:12px; }
        .tf .trk-dkm { min-width:0; margin-left:auto; }
        .tf .trk-tkm { font-size:19px; }
    }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_bar') ?>
                <?= view('layouts/tourdesk_menu') ?>

                <div class="tf-title">
                    <div>
                        <h4>Tour Inquiry <button class="btn btn-light btn-sm ms-2 align-middle" id="btn_list" type="button" style="font-weight:500"><i class="bi bi-inbox"></i> All inquiries</button></h4>
                        <div class="sub">Two ways in, one record either way. Paste what the agent sent and it is read for you — with the exact words each value came from, nothing the email does not say filled in with a guess, and the original kept alongside to check against. Or start it by hand and type the same fields yourself. Reading is there to save typing; it never has the last word, and it never has to work for you to get on.</div>
                    </div>

                </div>

                <?php if (empty($installed)): ?>
                <div class="tf-card"><div class="tf-card-b not-installed">
                    <b><i class="bi bi-exclamation-triangle"></i> The Tour Inquiry tables are not installed yet.</b>
                    <div class="help mt-1">
                        This creates four new tables (<span class="mono">tf_intake</span>, <span class="mono">tf_intake_day</span>, <span class="mono">tf_intake_pax</span>, <span class="mono">tf_intake_request</span>) and touches nothing that already exists — no tour, quotation or hotel-rate table is altered.<?= !empty($error) ? ' — ' . esc($error) : '' ?>
                    </div>
                    <?php if (!empty($is_sysadmin)): ?>
                        <button class="btn btn-primary btn-sm mt-2" id="btn_setup"><i class="bi bi-download"></i> Install now</button>
                    <?php else: ?>
                        <div class="help mt-2">Ask the System Administrator to open this page and press Install now.</div>
                    <?php endif; ?>
                </div></div>
                <?php else: ?>

                <?php if (empty($ai_ready)): ?>
                <div class="tf-card mb-3"><div class="tf-card-b" style="background:var(--tf-amber-soft);color:var(--tf-amber);font-size:12.5px;border-radius:var(--tf-radius)">
                    <b>No <?= esc($reader_label ?? 'API') ?> key is configured</b>, so emails cannot be read automatically yet. Everything else works — paste the email and fill the fields in by hand. Set <span class="mono"><?= esc($reader_key ?? 'ANTHROPIC_API_KEY') ?></span> in the project's <span class="mono">.env</span> to switch reading on.
                </div></div>
                <?php else: ?>
                <div class="help mb-2">Emails are read by <b><?= esc($reader_label ?? '') ?></b><?= !empty($reader_model) ? ' · ' . esc($reader_model) : '' ?>. Every value it returns is checked back against the email before it is shown.</div>
                <?php endif; ?>

                <!-- ============ LIST ============ -->
                <div id="view_list">
                    <div class="tf-card">
                        <div class="tf-card-h">
                            <h6>Inquiries</h6>
                            <div class="d-flex gap-2 align-items-center flex-wrap" id="filters">
                                <select class="form-select form-select-sm" id="f_status" style="width:auto" title="Status">
                                    <option value="open">Open</option>
                                    <option value="new">New</option>
                                    <option value="working">Working</option>
                                    <option value="quoted">Quoted</option>
                                    <option value="follow_up" id="f_follow">Follow-ups due</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="declined">Declined</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="all">All</option>
                                    <option value="archived">Archived</option>
                                </select>
                                <select class="form-select form-select-sm" id="f_handler" style="width:auto;max-width:200px" title="Handling agent — whose tour it is">
                                    <option value="">Any handling agent</option>
                                    <?php foreach ((array) ($handlers ?? []) as $h): ?>
                                    <option value="<?= esc($h['id'] !== null ? (string) $h['id'] : (string) $h['name']) ?>"><?= esc($h['name']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="none">No handling agent</option>
                                </select>
                                <input class="form-control form-control-sm" id="f_q" placeholder="Customer, group, number…" style="width:210px" title="Customer (agent), group or tour name, inquiry number">
                                <input type="date" class="form-control form-control-sm" id="f_from" style="width:auto" title="Arriving from">
                                <input type="date" class="form-control form-control-sm" id="f_to" style="width:auto" title="Arriving to">
                                <button class="btn btn-light btn-sm" id="f_clear" type="button" title="Clear every filter"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="tbl">
                                <thead><tr><th style="width:110px">Inquiry</th><th>Customer</th><th>Tour / group</th><th style="width:150px">Handling agent</th><th style="width:160px">Dates</th><th style="width:60px">Pax</th><th style="width:130px">Rooms</th><th style="width:180px">Needs attention</th><th style="width:120px"></th></tr></thead>
                                <tbody id="rows"><tr><td colspan="9" class="empty">Loading…</td></tr></tbody>
                            </table>
                        </div>
                        <div class="tf-card-b help" id="list_foot" style="padding-top:8px"></div>
                    </div>
                </div>

                <!-- ============ PASTE ============ -->
                <div id="view_paste" style="display:none">
                    <div class="steps">
                        <div class="st on"><span class="n">1</span><span><b>Paste and check the email</b><span>Correct the text before it is read</span></span></div>
                        <div class="st"><span class="n">2</span><span><b>Check what was read</b><span>Correct anything, fill the gaps</span></span></div>
                        <div class="st"><span class="n">3</span><span><b>Confirm it</b><span>When the agent says yes, the desks take over</span></span></div>
                    </div>
                    <div class="tf-card" id="p_step_paste">
                        <div class="tf-card-h"><h6>The agent's email</h6><span class="help">Kept with the inquiry exactly as pasted</span></div>
                        <div class="tf-card-b">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6"><label class="form-label help">Subject <span class="text-muted">(optional)</span></label><input class="form-control form-control-sm" id="p_subject" placeholder="01SEP Baiko Gakuin university Group"></div>
                                <div class="col-md-3"><label class="form-label help">From <span class="text-muted">(optional)</span></label><input class="form-control form-control-sm" id="p_from" placeholder="junko@agent.co.jp"></div>
                                <div class="col-md-3"><label class="form-label help">Email date <span class="text-muted">— dates before it are flagged</span></label><input type="date" class="form-control form-control-sm" id="p_received"></div>
                            </div>
                            <textarea class="form-control" id="p_text" rows="14" style="font-family:ui-monospace,Consolas,monospace;font-size:12px" placeholder="Paste the whole email here — signature, forward headers and all."></textarea>
                            <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap mt-2">
                                <span class="help">Nothing is priced and no supplier is contacted. The email is saved even if reading it fails.</span>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light btn-sm" id="p_cancel">Cancel</button>
                                    <button class="btn btn-light btn-sm" id="p_manual"><i class="bi bi-pencil"></i> Start by hand instead</button>
                                    <button class="btn btn-light btn-sm" id="p_save">Save without reading</button>
                                    <button class="btn btn-primary btn-sm" id="p_next"><i class="bi bi-check2-square"></i> Next: check the text</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- The review step: the text is checked by rule (dates, years) and can be corrected here,
                         before anything reads it. The paste itself is kept exactly as it arrived. -->
                    <div class="tf-card" id="p_step_review" style="display:none">
                        <div class="tf-card-h"><h6>Check the text before it is read</h6><span class="help">What you correct here is what the reader gets; the original is kept as pasted</span></div>
                        <div class="tf-card-b">
                            <div class="gaps mb-2" id="p_flags"></div>
                            <div class="help mb-2" id="p_dates"></div>
                            <div class="row g-2">
                                <div class="col-lg-7">
                                    <label class="form-label help">The text to read <span class="text-muted" id="p_edit_state">— as pasted</span></label>
                                    <textarea class="form-control" id="p_edit" rows="16" style="font-family:ui-monospace,Consolas,monospace;font-size:12px"></textarea>
                                </div>
                                <div class="col-lg-5">
                                    <label class="form-label help">The original, as pasted <span class="text-muted">— never changed</span></label>
                                    <pre class="mail" id="p_orig" style="max-height:340px;overflow:auto;background:#f6f7f9;border:1px solid #e3e8ee;border-radius:6px;padding:8px;font-size:12px;white-space:pre-wrap"></pre>
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap mt-2">
                                <span class="help">Dates are checked by rule: a day and month with no year, no date at all, a departure before the arrival, a date already gone. Nothing is guessed — a date without a year is not recorded.</span>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light btn-sm" id="p_back">Back</button>
                                    <button class="btn btn-light btn-sm" id="p_restore" style="display:none">Back to the original text</button>
                                    <button class="btn btn-light btn-sm" id="p_recheck"><i class="bi bi-arrow-repeat"></i> Check again</button>
                                    <button class="btn btn-light btn-sm" id="p_save2">Save without reading</button>
                                    <button class="btn btn-primary btn-sm" id="p_read" <?= empty($ai_ready) ? 'disabled title="No reading key is configured — use Save without reading, or Start by hand"' : '' ?>><i class="bi bi-magic"></i> Read this email</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ BY HAND ============ -->
                <div id="view_manual" style="display:none">
                    <div class="steps">
                        <div class="st on"><span class="n">1</span><span><b>Enter the details</b><span>Only what you actually have</span></span></div>
                        <div class="st"><span class="n">2</span><span><b>Itinerary &amp; the rest</b><span>Nights, names, requests</span></span></div>
                        <div class="st"><span class="n">3</span><span><b>Confirm it</b><span>When the agent says yes, the desks take over</span></span></div>
                    </div>
                    <div class="tf-card">
                        <div class="tf-card-h"><h6>Agent &amp; booking</h6><span class="help">Every field is optional — leave blank what you do not have</span></div>
                        <div class="tf-card-b"><div class="row g-2">
                            <div class="col-md-12"><label class="form-label help">Registered agent <span class="text-muted">— picking one fills in the code, email and country</span> <span class="text-muted" id="n_agent_scope"></span></label>
                                <select class="form-select form-select-sm" id="n_agent_pick"><option value="">Not in the list — I will type it below</option></select>
                                <div class="help mt-1" id="n_agent_hint"></div></div>
                            <div class="col-md-4"><label class="form-label help">Agent <span class="text-muted">— start typing to search the customer list</span></label>
                                <div class="sugwrap"><input class="form-control form-control-sm" id="n_agent_name" placeholder="Lion Royal Co., Ltd." autocomplete="off">
                                    <div class="sugg" id="n_agent_sugg" hidden></div></div></div>
                            <div class="col-md-4"><label class="form-label help">Agent email</label><input class="form-control form-control-sm" id="n_agent_email" placeholder="junko@lionroyal.com"></div>
                            <div class="col-md-4"><label class="form-label help">Country</label><input class="form-control form-control-sm" id="n_agent_country" placeholder="Japan"></div>
                            <div class="col-md-4"><label class="form-label help">Agent reference</label><input class="form-control form-control-sm" id="n_agent_reference" placeholder="Their own booking reference"></div>
                            <div class="col-md-5"><label class="form-label help">Group / tour name</label><input class="form-control form-control-sm" id="n_tour_name" placeholder="Baiko Gakuin university group"></div>
                            <div class="col-md-3"><label class="form-label help">Hotel class</label><input class="form-control form-control-sm" id="n_hotel_class" placeholder="Superior"></div>
                        </div></div>
                    </div>
                    <div class="tf-card">
                        <div class="tf-card-h"><h6>Dates &amp; party</h6><span class="help">Nights and dates decide which hotel rates apply</span></div>
                        <div class="tf-card-b"><div class="row g-2">
                            <div class="col-md-3"><label class="form-label help">Arrival</label><input type="date" class="form-control form-control-sm" id="n_arrival_date"></div>
                            <div class="col-md-3"><label class="form-label help">Departure</label><input type="date" class="form-control form-control-sm" id="n_departure_date"></div>
                            <div class="col-md-2"><label class="form-label help">Nights <span class="text-muted">— counted</span></label>
                                <input class="form-control form-control-sm" id="n_nights" readonly tabindex="-1" style="background:#f6f7f9" placeholder="—" title="Counted from the two dates; it is never typed."></div>
                            <div class="col-md-2"><label class="form-label help">Passengers</label><input type="number" min="0" class="form-control form-control-sm" id="n_pax_total"></div>
                            <div class="col-md-1"><label class="form-label help">Adults</label><input type="number" min="0" class="form-control form-control-sm" id="n_pax_adults"></div>
                            <div class="col-md-1"><label class="form-label help">Children</label><input type="number" min="0" class="form-control form-control-sm" id="n_pax_children"></div>
                            <div class="col-md-6"><label class="form-label help">Rooms</label><input class="form-control form-control-sm" id="n_rooms_note" placeholder="4TWN + 2SGL = 6 rooms"></div>
                            <div class="col-md-3"><label class="form-label help">Arrival flight</label><input class="form-control form-control-sm" id="n_arrival_flight" placeholder="UL 455 01SEP NRTCMB"></div>
                            <div class="col-md-3"><label class="form-label help">Departure flight</label><input class="form-control form-control-sm" id="n_departure_flight" placeholder="UL 454 07SEP CMBNRT"></div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="n_make_nights" checked>
                            <label class="form-check-label help" for="n_make_nights">
                                Start the itinerary with one blank night per night above, dated from the arrival date. On the next screen each night has a <b>Choose hotel</b> button: pick the area and the hotel from the Hotel Rates panel, the meal plan and the rooms.
                            </label>
                        </div>
                        <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap mt-3">
                            <span class="help">No inquiry number is used until you save. Nothing is guessed — whatever you leave blank is listed as still needed.</span>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light btn-sm" id="n_cancel">Cancel</button>
                                <button class="btn btn-light btn-sm" id="n_paste"><i class="bi bi-envelope"></i> I have the email instead</button>
                                <button class="btn btn-primary btn-sm" id="n_save"><i class="bi bi-check-lg"></i> Create this inquiry</button>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- ============ REVIEW ============ -->
                <div id="view_detail" style="display:none">
                    <!-- the workflow: Inquiry → Quotation → Confirm Tour. Each step says where this inquiry stands and carries its action. -->
                    <div class="steps flow" id="flow"></div>
                    <div id="d_banner"></div>
                    <!-- schema 32: arrival · departure · nights · days, and the email's dates when they differ -->
                    <div id="dates_bar"></div>
                    <div class="summary" id="summary"></div>
                    <div class="review">
                        <div class="col" id="detail_cols"></div>
                        <!-- the email, and below it the Edit Request (schema 33); the two scroll together beside the inquiry -->
                        <div class="sidecol">
                        <div class="tf-card mailbox">
                            <div class="tf-card-h"><h6 id="mail_head">Original email</h6><span class="help" id="mail_meta"></span></div>
                            <div class="tf-card-b">
                                <div class="d-flex gap-2 align-items-center flex-wrap mb-2" id="mail_tools" style="display:none">
                                    <span class="help" id="mail_edit_note"></span>
                                    <a href="#" class="help ms-auto" id="mail_toggle" style="display:none"></a>
                                    <button class="btn btn-light btn-sm" id="mail_edit"><i class="bi bi-pencil"></i> Correct the text</button>
                                </div>
                                <pre class="mail" id="mailpane"></pre>
                                <!-- the in-place correction of the working copy; the original above stays as pasted -->
                                <div id="mail_editor" style="display:none">
                                    <div class="gaps mb-2" id="me_flags"></div>
                                    <div class="help mb-2" id="me_dates"></div>
                                    <textarea class="form-control" id="me_text" rows="14" style="font-family:ui-monospace,Consolas,monospace;font-size:12px"></textarea>
                                    <input class="form-control form-control-sm mt-2" id="me_note" placeholder="What you corrected, in a few words (optional) — e.g. added the year 2027">
                                    <div class="d-flex gap-2 justify-content-end mt-2 flex-wrap">
                                        <button class="btn btn-light btn-sm" id="me_cancel">Cancel</button>
                                        <button class="btn btn-light btn-sm" id="me_recheck"><i class="bi bi-arrow-repeat"></i> Check again</button>
                                        <button class="btn btn-light btn-sm" id="me_save">Save the correction</button>
                                        <button class="btn btn-primary btn-sm" id="me_read" <?= empty($ai_ready) ? 'disabled title="No reading key is configured"' : '' ?>><i class="bi bi-magic"></i> Save and read again</button>
                                    </div>
                                </div>
                                <div id="mail_attach" style="display:none">
                                    <div class="help mb-2">This inquiry was typed in by hand. If the agent's email turns up later, paste it here — it joins this same inquiry, and nothing you have already entered is changed.</div>
                                    <textarea class="form-control" id="a_text" rows="8" style="font-family:ui-monospace,Consolas,monospace;font-size:12px" placeholder="Paste the agent's email here."></textarea>
                                    <div class="gaps mt-2" id="a_flags"></div>
                                    <div class="help" id="a_dates"></div>
                                    <div class="d-flex gap-2 justify-content-end mt-2">
                                        <button class="btn btn-light btn-sm" id="a_save">Attach it only</button>
                                        <button class="btn btn-primary btn-sm" id="a_read" <?= empty($ai_ready) ? 'disabled title="No reading key is configured"' : '' ?>><i class="bi bi-magic"></i> Attach and read</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Edit Request (schema 33): what needs to change, and what it should say now. The email above is never changed. -->
                        <div class="tf-card ercard" id="er_card" style="display:none">
                            <div class="tf-card-h"><h6><i class="bi bi-pencil-square"></i> Edit Request</h6><span class="help" id="er_meta"></span></div>
                            <div class="tf-card-b" id="er_body"></div>
                        </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- Edit Request (schema 33): the check — questions, or Current → New with everything that follows — and Confirm -->
<div class="modal fade tf" id="m_er" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mer_title">Edit Request</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="mer_body"></div>
    <div class="modal-footer" id="mer_foot"></div>
</div></div></div>

<!-- field edit -->
<div class="modal fade tf" id="m_field" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mf_title">Correct this</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="mf_quote"></div>
        <label class="form-label mt-2">Value</label>
        <input class="form-control form-control-sm" id="mf_value">
        <div class="help mt-2">Leaving it empty marks it as still needed. What you type is recorded as yours, and reading the email again will not overwrite it.</div>
        <input type="hidden" id="mf_key"><input type="hidden" id="mf_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mf_ok">Save</button></div>
</div></div></div>

<!-- the picture for one day of the quotation (Master Files → Tour Photos) -->
<div class="modal fade tf" id="m_photo" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mp_title">Picture for day</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="mp_now" style="margin-bottom:8px"></div>
        <div id="mp_chips" style="margin-bottom:6px"></div>
        <div class="d-flex gap-2 align-items-center flex-wrap" style="margin-bottom:8px">
            <select class="form-select form-select-sm" id="mp_tag" style="width:auto"></select>
            <input class="form-control form-control-sm" id="mp_q" placeholder="find by caption" style="width:180px">
            <span class="help ms-auto" id="mp_count"></span>
        </div>
        <div class="phgrid" id="mp_grid"></div>
        <div class="help mt-2">A preferred picture (★) is offered first; the library's own choice never repeats a picture on one itinerary. A picture chosen here stays until it is changed; <b>Automatic</b> hands the day back to the library.</div>
        <input type="hidden" id="mp_day">
    </div>
    <div class="modal-footer">
        <button class="btn btn-light btn-sm me-auto" id="mp_none" title="The quotation shows no picture on this day">No picture</button>
        <button class="btn btn-light btn-sm" id="mp_auto" title="Back to the library's own choice for this day">Automatic</button>
        <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
</div></div></div>

<!-- day edit -->
<div class="modal fade tf" id="m_day" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Night</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-12"><label class="form-label help">Hotel <span class="text-muted">— start typing to search the Hotel Rates panel</span></label>
                <div class="sugwrap"><input class="form-control" id="md_hotel" placeholder="e.g. Araliya, Cinnamon, Galle…" autocomplete="off">
                    <div class="sugg" id="md_hotel_sugg" hidden></div></div>
                <div class="help mt-1">Pick from the list to use the panel's own spelling, which is what makes the rate match; the area fills in by itself. A hotel that is not in the panel can still be typed here — it is kept as written and flagged, and its supplier record is made under Master Files → Supplier, never here.</div></div>
            <div class="col-md-6"><label class="form-label help">Date</label><input type="date" class="form-control form-control-sm" id="md_date"></div>
            <div class="col-md-6"><label class="form-label help">Area <span class="text-muted">— filled in from the hotel</span></label><input class="form-control form-control-sm" id="md_area"></div>
            <div class="col-md-6"><label class="form-label help">Meal plan</label>
                <select class="form-select form-select-sm" id="md_meal"><option value="">Not stated</option><option>RO</option><option>BB</option><option>HB</option><option>FB</option><option>AI</option></select></div>
            <div class="col-md-6"><label class="form-label help">Rooms <span class="text-muted">— as the agent wrote it</span></label><input type="number" min="0" class="form-control form-control-sm" id="md_rooms"></div>
            <div class="col-md-7" id="md_rtwrap" style="display:none"><label class="form-label help">Room type <span class="text-muted">— from the Hotel Rates panel, published rates only</span></label>
                <select class="form-select form-select-sm" id="md_rt"></select>
                <div class="help mt-1" id="md_rthint"></div></div>
            <div class="col-md-5" id="md_rttextwrap" style="display:none"><label class="form-label help">Wording on this booking <span class="text-muted">(optional)</span></label>
                <input class="form-control form-control-sm" id="md_rttext" maxlength="120" placeholder="as the master names it">
                <div class="help mt-1">Only when this booking calls the room something else (a category renamed, a view). The rate follows the room type chosen; the Hotel Master is not changed.</div></div>
            <div class="col-12"><label class="form-label help">Special note for this day</label>
                <textarea class="form-control form-control-sm" id="md_note" rows="2" placeholder="Anything about this day the operations team needs to know"></textarea></div>
            <div class="col-12"><label class="form-label help">Wording on the quotation <span class="text-muted">— leave blank for the standard wording</span></label>
                <textarea class="form-control form-control-sm" id="md_text" rows="3" maxlength="2000" placeholder="Your own words for this day, if the standard wording does not say it right"></textarea>
                <div class="help mt-1" id="md_text_std"></div></div>
        </div>
        <div class="help mt-2">Changing the hotel or the date re-checks it against the Hotel Rates panel straight away — published contract rates only. A tactical or special-offer rate is never used for a quotation, even where one covers the date.</div>
        <input type="hidden" id="md_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="md_ok">Save</button></div>
</div></div></div>

<!-- a special rate per kilometre for this job -->
<div class="modal fade tf" id="m_vrate" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Special rate per kilometre</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="mv_master"></div>
        <label class="form-label help mt-2">Rate per kilometre for this inquiry only</label>
        <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="mv_rate">
        <label class="form-label help mt-2">Why</label>
        <input class="form-control form-control-sm" id="mv_note" placeholder="e.g. agreed with the agent for this group">
        <input type="hidden" id="mv_id">
        <div class="help mt-2">The master rate stays printed beside the special one, with your name and reason. The master itself is untouched — a special rate belongs to this job alone.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mv_ok">Use this rate</button></div>
</div></div></div>

<!-- one leg of the driving -->
<div class="modal fade tf" id="m_leg" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Leg</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label help">Day</label><input type="number" min="0" class="form-control form-control-sm" id="ml_day"></div>
            <div class="col-md-8"><label class="form-label help">What kind of leg</label>
                <select class="form-select form-select-sm" id="ml_kind">
                    <option value="positioning">Running out to collect them</option>
                    <option value="arrival">Arrival transfer</option>
                    <option value="transfer">Transfer</option>
                    <option value="local">City tour, no transfer</option>
                    <option value="idle">Stay day at the hotel, nothing booked</option>
                    <option value="garage">Garage run</option>
                    <option value="departure">Departure transfer</option>
                </select></div>
            <div class="col-md-6"><label class="form-label help">From</label><input class="form-control form-control-sm" id="ml_from"></div>
            <div class="col-md-6"><label class="form-label help">To</label><input class="form-control form-control-sm" id="ml_to"></div>
            <div class="col-md-6"><label class="form-label help">Quoted km — typed over both figures</label>
                <input type="number" min="0" step="0.1" class="form-control form-control-sm" id="ml_km" placeholder="leave blank to quote by the setting">
                <div class="help mt-1" id="ml_hint"></div></div>
            <div class="col-md-6"><label class="form-label help">Extra km — site visits &amp; city tour</label>
                <input type="number" min="0" step="0.1" class="form-control form-control-sm" id="ml_extra" placeholder="blank = the town's allowance"></div>
            <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="ml_note"></div>
        </div>
        <div class="help mt-2">Correct the from and the to and both figures are looked up again — Google's road distance and the company's actual record. Leave the quoted kilometres blank and the setting decides which is quoted; type a figure and yours is quoted over both, with both kept beside it so the disagreement stays visible. The actual kilometres for a leg are typed in the box on its row, never here.</div>
        <input type="hidden" id="ml_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="ml_ok">Save</button></div>
</div></div></div>

<!-- one day's visits, activities and services -->
<div class="modal fade tf" id="m_svc" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="ms_title">Day programme</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="ms_list"></div>
        <div class="tf-card mt-3" id="ms_masters" style="display:none"><div class="tf-card-h"><h6>Add from the masters</h6>
            <span class="help">A free visit goes on the itinerary only and is never costed; a paid item pulls its rate into the costing</span></div>
            <div class="tf-card-b">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5"><label class="form-label help">Destination</label>
                        <select class="form-select form-select-sm" id="mm_dest"></select></div>
                    <div class="col-md-7"><label class="form-label help">Find</label>
                        <input class="form-control form-control-sm" id="mm_q" placeholder="Type to search every destination…"></div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6"><div class="mm-h"><span class="pill pill-green">Free visits</span><span class="help">itinerary only · never costed</span></div><div class="mm-list" id="mm_free"></div></div>
                    <div class="col-md-6"><div class="mm-h"><span class="pill pill-blue">Paid activities &amp; services</span><span class="help">costed at the master rate</span></div><div class="mm-list" id="mm_paid"></div></div>
                </div>
                <div class="help mt-2" id="mm_links"></div>
            </div></div>
        <div class="tf-card mt-3"><div class="tf-card-h"><h6 id="ms_formtitle">Add a service</h6>
            <span class="help">Or type it: priced from Master Files → Entrance Fee, in USD, converted at the daily rate</span></div>
            <div class="tf-card-b"><div class="row g-2">
                <div class="col-md-5"><label class="form-label help">What is it</label>
                    <input class="form-control form-control-sm" id="ms_title_in" list="ms_master" placeholder="Sigiriya rock, Habarana Jeep Safari…"></div>
                <div class="col-md-3"><label class="form-label help">Kind</label>
                    <select class="form-select form-select-sm" id="ms_kind">
                        <option value="">Work it out</option><option value="visit">Sightseeing (no ticket)</option><option value="entrance">Entrance (a ticket)</option>
                        <option value="safari">Safari</option><option value="train">Train</option><option value="ayurveda">Ayurveda</option>
                        <option value="lunch">Lunch</option><option value="dinner">Dinner</option><option value="guide">Guide</option>
                        <option value="transport">Transport</option><option value="other">Other</option></select></div>
                <div class="col-md-2"><label class="form-label help">How many</label>
                    <input type="number" min="0" class="form-control form-control-sm" id="ms_qty" placeholder="per person"></div>
                <div class="col-md-2"><label class="form-label help">Day</label>
                    <select class="form-select form-select-sm" id="ms_day"></select></div>
                <div class="col-md-5"><label class="form-label help">Who settles it</label>
                    <select class="form-select form-select-sm" id="ms_pay">
                        <option value="unknown">Not stated</option><option value="in_tour">Included in the tour price</option>
                        <option value="prepaid">Prepaid by the agent</option><option value="direct">Paid on the spot</option>
                        <option value="own_arrangement">Agent arranges it themselves</option><option value="not_arranged">Not to be arranged</option>
                        <option value="no_charge">No charge — a stop with nothing to pay</option></select></div>
                <div class="col-md-7"><label class="form-label help">Special note</label>
                    <input class="form-control form-control-sm" id="ms_note" placeholder="Anything the operations team needs to know"></div>
                <div class="col-12" id="ms_descwrap" style="display:none"><label class="form-label help">Description on the client quotation <span class="text-muted">— the Place to Visit master's words, copied here; edit them for this quotation only. Empty the box to take the master's text again.</span></label>
                    <textarea class="form-control form-control-sm" id="ms_desc" rows="3"></textarea></div>
            </div>
            <div class="help mt-2">Leave "how many" empty for one per person — at a safari park of the Place to Visit / Entrance Fee Master it is the number of jeeps (the entrance is for the party). A name that is not in the priced list is kept exactly as written and flagged, never swapped for something similar; an entrance or a safari that is not in the master is held until the place is picked or added there.</div>
            <datalist id="ms_master"></datalist>
            <input type="hidden" id="ms_id">
            </div></div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-light btn-sm me-auto" id="ms_clear" style="display:none">New line</button>
        <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary btn-sm" id="ms_ok">Save service</button></div>
</div></div></div>

<!-- a driver or a guide: type and language from the crew master, days, who is engaged. The bata is the master's and is only shown. -->
<div class="modal fade tf" id="m_crew" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mc_title">Add a driver</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="mc_kind"><input type="hidden" id="mc_from">
        <div class="row g-2">
            <div class="col-6"><label class="form-label help" id="mc_type_l">Driver type</label>
                <select class="form-select form-select-sm" id="mc_type"></select></div>
            <div class="col-6"><label class="form-label help">Language</label>
                <select class="form-select form-select-sm" id="mc_lang"></select></div>
            <div class="col-12"><div class="help" id="mc_bata"></div></div>
            <div class="col-4"><label class="form-label help">Days</label>
                <input type="number" min="0" class="form-control form-control-sm" id="mc_days"></div>
            <div class="col-8"><label class="form-label help">Person engaged</label>
                <select class="form-select form-select-sm" id="mc_who"><option value="">Nobody yet</option></select></div>
            <div class="col-12"><label class="form-label help">Note</label>
                <input class="form-control form-control-sm" id="mc_note" maxlength="255" placeholder="Anything the desk should know"></div>
        </div>
        <div class="help mt-2">The type and the language come from Master Files → Crew Bata, and so does the bata per day: it is shown here and changed only there, by Admin. Days follow the programme — every day with a transfer, a visit or the vehicle on duty, arrival and departure included; leave it empty for that, or type a number to set it by hand. Whoever is engaged must be on the supplier ledger (Master Files → Supplier); their own agreed bata is kept for the payment side and is not what is quoted.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="mc_ok">Save</button></div>
</div></div></div>

<!-- a passenger name, by hand -->
<div class="modal fade tf" id="m_pax" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mp_title">Add a passenger name</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label help">Name as the agent gave it</label><input class="form-control form-control-sm" id="mp_name" placeholder="Takao/Rio Ms"></div>
            <div class="col-md-5"><label class="form-label help">Room type</label>
                <select class="form-select form-select-sm" id="mp_room"><option value="">Not stated</option><option>SGL</option><option>TWN</option><option>DBL</option><option>TPL</option></select></div>
            <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="mp_note" placeholder="Professor, wheelchair, vegetarian…"></div>
            <div class="col-12" id="mp_groupwrap" style="display:none"><label class="form-label help">Group</label><select class="form-select form-select-sm" id="mp_group"></select></div>
        </div>
        <div class="help mt-2">Names only. No passport number and no date of birth — the agents do not send them and this system does not hold them.</div>
        <input type="hidden" id="mp_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mp_ok">Save</button></div>
</div></div></div>

<!-- a passenger group -->
<div class="modal fade tf" id="m_group" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mg_title">Group</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-8"><label class="form-label help">Name</label><input class="form-control form-control-sm" id="mg_name" placeholder="Main party, Early leavers…" maxlength="80"></div>
            <div class="col-md-4"><label class="form-label help">Passengers</label><input type="number" min="0" max="999" class="form-control form-control-sm" id="mg_pax"></div>
            <div class="col-md-6"><label class="form-label help">First night</label><input type="number" min="1" class="form-control form-control-sm" id="mg_from" placeholder="1"></div>
            <div class="col-md-6"><label class="form-label help">Last night</label><input type="number" min="1" class="form-control form-control-sm" id="mg_to" placeholder="last"></div>
            <div class="col-12"><div class="help" id="mg_nights"></div></div>
            <div class="col-3"><label class="form-label help">Single</label><input type="number" min="0" class="form-control form-control-sm" id="mg_sgl"></div>
            <div class="col-3"><label class="form-label help">Double</label><input type="number" min="0" class="form-control form-control-sm" id="mg_dbl"></div>
            <div class="col-3"><label class="form-label help">Twin</label><input type="number" min="0" class="form-control form-control-sm" id="mg_twn"></div>
            <div class="col-3"><label class="form-label help">Triple</label><input type="number" min="0" class="form-control form-control-sm" id="mg_tpl"></div>
            <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="mg_note" maxlength="255"></div>
        </div>
        <div class="help mt-2">The rooms are this group's own: a night is costed on the mix of every group present that night. Nothing is worked out from the passenger count.</div>
        <input type="hidden" id="mg_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mg_ok">Save</button></div>
</div></div></div>

<!-- a vehicle line -->
<div class="modal fade tf" id="m_vline" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mvl_title">Vehicle</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label help">Size, from the vehicle master</label><select class="form-select form-select-sm" id="mvl_class"></select></div>
            <div class="col-md-2"><label class="form-label help">How many</label><input type="number" min="1" max="9" class="form-control form-control-sm" id="mvl_qty" value="1"></div>
            <div class="col-md-3"><label class="form-label help">Label</label><input class="form-control form-control-sm" id="mvl_label" placeholder="Main van" maxlength="40"></div>
            <div class="col-md-6"><label class="form-label help">Carries</label><select class="form-select form-select-sm" id="mvl_group"></select></div>
            <div class="col-md-3"><label class="form-label help">From night</label><input type="number" min="1" class="form-control form-control-sm" id="mvl_from" placeholder="1"></div>
            <div class="col-md-3"><label class="form-label help">To night</label><input type="number" min="1" class="form-control form-control-sm" id="mvl_to" placeholder="last"></div>
        </div>
        <div class="help mt-2">Blank nights mean the vehicle serves its group for the group's whole stay (or the whole tour). Its legs are worked out for those days alone: a vehicle joining part-way runs out from base to the party, and one released before the end runs back. The rate per kilometre is copied from Transport Rates for the size.</div>
        <input type="hidden" id="mvl_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mvl_ok">Save</button></div>
</div></div></div>

<!-- an excursion on the quotation -->
<div class="modal fade tf" id="m_exc" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mx_title">Excursion</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label help">Day</label><select class="form-select form-select-sm" id="mx_day"></select></div>
            <div class="col-md-5"><label class="form-label help">For</label><select class="form-select form-select-sm" id="mx_group"></select></div>
            <div class="col-md-3"><label class="form-label help">Quantity</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" id="mx_qty"></div>
            <div class="col-12"><label class="form-label help">Description on the client quotation</label>
                <textarea class="form-control form-control-sm" id="mx_desc" rows="3" placeholder="What the clients will read: timings, what is included"></textarea></div>
            <div class="col-12"><label class="form-label help">Internal note</label><input class="form-control form-control-sm" id="mx_note" maxlength="255"></div>
        </div>
        <div class="help mt-2">The rate is the master's, copied when the excursion was added; it is not edited here. The quantity is: per person the people going, per group one, per vehicle one per vehicle serving them.</div>
        <input type="hidden" id="mx_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mx_ok">Save</button></div>
</div></div></div>

<!-- Go for Quotation: the checklist (every rate and figure still missing, each with a Fix that jumps to its source), or the version produced -->
<div class="modal fade tf" id="m_mealpick" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mm_title">Meal</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2" id="mm_day"></div>
        <div id="mm_conflict"></div>
        <div class="d-flex gap-2 align-items-center mb-2"><input class="form-control form-control-sm" id="mm_find" placeholder="Find a meal (restaurant, town, cuisine…)" style="max-width:320px"><span class="help" id="mm_n"></span></div>
        <div id="mm_list" style="max-height:300px;overflow-y:auto;border:1px solid var(--tf-line);border-radius:8px"></div>
        <!-- schema 40: a restaurant that is not in the list yet goes into the Meal Master from here (a Meal Master holder only) -->
        <div class="mt-2" id="mm_newwrap">
            <button type="button" class="btn btn-light btn-sm" id="mm_newbtn" hidden><i class="bi bi-plus-lg"></i> Add a new restaurant</button>
            <span class="help" id="mm_newno"></span>
            <div id="mm_new" class="mt-2 p-2" hidden style="border:1px solid var(--tf-line);border-radius:8px;background:var(--tf-bg)">
                <div class="help mb-2">The restaurant goes into the <b>Meal Master</b> (its Meal Code is given) and onto this day. Its supplier account can be linked later in the Meal Master — it is paid only once it is.</div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label help">Restaurant name</label><input class="form-control form-control-sm" id="mn_rest" maxlength="190" placeholder="e.g. Kadugannawa Rest House"></div>
                    <div class="col-md-6"><label class="form-label help">Supplier master record <span class="text-muted">(optional)</span></label><select class="form-select form-select-sm" id="mn_sup"></select></div>
                    <div class="col-md-6"><label class="form-label help">Location (town)</label><input class="form-control form-control-sm" id="mn_loc" maxlength="120"></div>
                    <div class="col-md-6"><label class="form-label help">Cuisine <span class="text-muted">(optional)</span></label><input class="form-control form-control-sm" id="mn_cui" maxlength="80"></div>
                    <div class="col-md-5"><label class="form-label help">Booking email <span class="text-muted">(the voucher goes here)</span></label><input class="form-control form-control-sm" id="mn_email" maxlength="190" placeholder="reservations@restaurant.lk"></div>
                    <div class="col-md-4"><label class="form-label help">Contact person</label><input class="form-control form-control-sm" id="mn_cname" maxlength="120"></div>
                    <div class="col-md-3"><label class="form-label help">Phone</label><input class="form-control form-control-sm" id="mn_tel" maxlength="60"></div>
                    <div class="col-md-3"><label class="form-label help">Quotation rate USD · adult</label><input class="form-control form-control-sm mono" id="mn_ar" inputmode="decimal"></div>
                    <div class="col-md-3"><label class="form-label help">USD · child</label><input class="form-control form-control-sm mono" id="mn_cr" inputmode="decimal" placeholder="blank: as adult"></div>
                    <div class="col-md-3"><label class="form-label help">Actual rate LKR · adult</label><input class="form-control form-control-sm mono" id="mn_alkr" inputmode="decimal" placeholder="on the voucher"></div>
                    <div class="col-md-3"><label class="form-label help">LKR · child</label><input class="form-control form-control-sm mono" id="mn_clkr" inputmode="decimal" placeholder="blank: as adult"></div>
                    <div class="col-md-3"><label class="form-label help">Taxes %</label><input class="form-control form-control-sm mono" id="mn_tax" inputmode="decimal" value="0"></div>
                    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" id="mn_taxin"><label class="form-check-label help" for="mn_taxin">in the rates</label></div></div>
                    <div class="col-md-12"><label class="form-label help">What the meal is <span class="text-muted">(optional)</span></label><input class="form-control form-control-sm" id="mn_desc" maxlength="1000" placeholder="Rice and curry buffet, soft drink"></div>
                    <div class="col-md-12" id="mn_legwrap"><div class="form-check"><input class="form-check-input" type="checkbox" id="mn_leg" checked><label class="form-check-label help" for="mn_leg" id="mn_leglabel">It is on the day's route</label></div></div>
                    <div class="col-md-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="mn_high"><label class="form-check-label help" for="mn_high">The amount is right (a USD rate above 500)</label></div></div>
                </div>
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-2"><label class="form-label help">Adults</label><input class="form-control form-control-sm mono" id="mm_a" inputmode="numeric"></div>
            <div class="col-md-2"><label class="form-label help">Children</label><input class="form-control form-control-sm mono" id="mm_c" inputmode="numeric"></div>
            <div class="col-md-2"><label class="form-label help">Meal time</label><input class="form-control form-control-sm mono" id="mm_t" placeholder="12:30"></div>
            <div class="col-md-6"><label class="form-label help">Note for the restaurant / the desk</label><input class="form-control form-control-sm" id="mm_note" maxlength="500" placeholder="Vegetarian for two, a table near the river…"></div>
        </div>
        <div class="help mt-1" id="mm_split"></div>
        <!-- schema 40: where the booking voucher goes at Confirm Tour — from the Meal Master, changeable for this booking -->
        <div class="row g-2 mt-1" id="mm_bookrow">
            <div class="col-md-5"><label class="form-label help">Booking email <span class="text-muted">(the voucher goes here on Confirm Tour)</span></label><input class="form-control form-control-sm" id="mm_bemail" maxlength="190"></div>
            <div class="col-md-4"><label class="form-label help">Contact person</label><input class="form-control form-control-sm" id="mm_bcontact" maxlength="120"></div>
            <div class="col-md-3"><label class="form-label help">Phone</label><input class="form-control form-control-sm" id="mm_bphone" maxlength="60"></div>
            <div class="col-12" id="mm_bsavewrap" hidden><div class="form-check"><input class="form-check-input" type="checkbox" id="mm_bsave"><label class="form-check-label help" for="mm_bsave">Also save them on the Meal Master, for the next booking</label></div></div>
        </div>
        <div class="mt-2" id="mm_cost"></div>
        <div class="mt-2" id="mm_outside" hidden>
            <div class="form-check"><input class="form-check-input" type="checkbox" id="mm_ok"><label class="form-check-label" for="mm_ok" style="font-size:12.5px" id="mm_oklabel">Arrange it outside as well</label></div>
            <input class="form-control form-control-sm mt-1" id="mm_reason" maxlength="200" placeholder="Why — e.g. the client asked for a dinner in town">
        </div>
        <input type="hidden" id="mm_sid"><input type="hidden" id="mm_dayno"><input type="hidden" id="mm_type">
    </div>
    <div class="modal-footer"><span class="help me-auto" id="mm_foot"></span><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mm_save"><i class="bi bi-check2"></i> Save</button></div>
</div></div></div>

<div class="modal fade tf" id="m_go" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mgo_title">Checklist</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="mgo_body"></div>
    <div class="modal-footer"><span class="help me-auto" id="mgo_foot"></span><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>
<!-- while the checklist is being worked through: the way back to it, from anywhere on the page -->
<button type="button" id="chk_pill" class="tf" title="Back to the checklist"><i class="bi bi-list-check"></i> Checklist<span class="n" id="chk_pill_n"></span></button>

<!-- Manage: reassign the handling agent -->
<div class="modal fade tf" id="m_reassign" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mra_title">Reassign handling agent</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="mra_now"></div>
        <label class="form-label help mt-2">Give this tour to</label>
        <select class="form-select form-select-sm" id="mra_user"></select>
        <label class="form-label help mt-2">Why <span class="text-muted">(optional — stays on the history)</span></label>
        <input class="form-control form-control-sm" id="mra_note" maxlength="200" placeholder="e.g. Mr. A on leave from Monday">
        <div class="help mt-2">Ownership moves to the person chosen and stays with the tour — on the inquiry, and on the tour and its cards once confirmed — until it is reassigned again. Who edits the tour is a separate matter and is always recorded as it happens.</div>
        <input type="hidden" id="mra_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mra_ok">Reassign</button></div>
</div></div></div>

<!-- Manage: archive an inquiry -->
<div class="modal fade tf" id="m_archive" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mar_title">Archive</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="mar_deps" class="help"></div>
        <label class="form-label help mt-2">Why <span class="text-muted">(optional — stays on the history)</span></label>
        <input class="form-control form-control-sm" id="mar_note" maxlength="400" placeholder="e.g. duplicate of INQ 1013, test record">
        <div class="help mt-2">Archiving hides the inquiry from every list and count and makes it read-only. Nothing is deleted: it can be restored from the Archived list at any time.</div>
        <input type="hidden" id="mar_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mar_ok">Archive</button></div>
</div></div></div>

<!-- Manage: remove an archived inquiry for good -->
<div class="modal fade tf" id="m_delete" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mdl_title">Remove for good</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="mdl_deps" class="help"></div>
        <div id="mdl_form">
            <label class="form-label help mt-2">Type the inquiry number to confirm</label>
            <input class="form-control form-control-sm" id="mdl_confirm" autocomplete="off">
            <div class="help mt-2" style="color:var(--tf-red)">This cannot be undone. The inquiry's nights, names, requests, services, vehicles and draft quotations go with it; its history is kept, ending with this removal.</div>
        </div>
        <input type="hidden" id="mdl_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger btn-sm" id="mdl_ok">Remove for good</button></div>
</div></div></div>

<!-- request answer -->
<div class="modal fade tf" id="m_req" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="mr_title">Request</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help" id="mr_quote"></div>
        <div class="row g-2 mt-1">
            <div class="col-md-8"><label class="form-label help">What was asked</label>
                <input class="form-control form-control-sm" id="mr_titlein" placeholder="Arrange a Japanese-speaking guide"></div>
            <div class="col-md-4"><label class="form-label help">Kind</label>
                <select class="form-select form-select-sm" id="mr_kind"><option value="request">Something to arrange</option><option value="question">A question to answer</option></select></div>
        </div>
        <div id="mr_answerwrap">
            <label class="form-label mt-2">Answer / note</label>
            <textarea class="form-control form-control-sm" id="mr_answer" rows="3"></textarea>
            <label class="form-label mt-2">Status</label>
            <select class="form-select form-select-sm" id="mr_status"><option value="open">Still open</option><option value="answered">Answered</option><option value="done">Done</option><option value="dropped">Not applicable</option></select>
        </div>
        <input type="hidden" id="mr_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="mr_ok">Save</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script src="<?= base_url('assets/tourmail/tour-mails.js') ?>?r1"></script>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const OPEN_ID = <?= (int) ($open_id ?? 0) ?>;
    const SEES_PRICING = <?= !empty($sees_pricing) ? 'true' : 'false' ?>;
    const CAN_MILEAGE = <?= !empty($can_mileage) ? 'true' : 'false' ?>;
    const SEES_EXC = <?= !empty($sees_excursions) ? 'true' : 'false' ?>;
    const SEES_CREW = <?= !empty($sees_crew) ? 'true' : 'false' ?>;   // holds Master Files → Crew Bata: a link, never an editor here
    const CREW_URL = '<?= base_url($crew_url ?? 'master_files/crew_bata') ?>';
    const SEES_PHOTOS = <?= !empty($sees_photos) ? 'true' : 'false' ?>;   // holds Master Files → Tour Photos: a link to the library, never an uploader here
    const PHOTOS_URL = '<?= base_url($photos_url ?? 'master_files/tour_photos') ?>';
    const EXC_URL = '<?= base_url($excursions_url ?? 'master_files/excursion_rates') ?>';
    // the two programme masters (schema 29): a holder gets a link to each from the day programme
    const SEES_FREE = <?= !empty($sees_free) ? 'true' : 'false' ?>;
    const FREE_URL = '<?= base_url($free_url ?? 'master_files/free_visits') ?>';
    const SEES_PAID = <?= !empty($sees_paid) ? 'true' : 'false' ?>;
    const PAID_URL = '<?= base_url($paid_url ?? 'master_files/paid_services') ?>';
    // schema 39: the Place to Visit / Entrance Fee Master — where a place's rate is set
    const SEES_MASTER = <?= !empty($sees_master) ? 'true' : 'false' ?>;
    const MASTER_URL = '<?= base_url($master_url ?? 'master_files/entrance_master') ?>';
    // schema 40: the Meal Master — where a meal is added or its rate changed; a holder gets a link from the Meals card
    const SEES_MEALS = <?= !empty($sees_meals) ? 'true' : 'false' ?>;
    const MEAL_URL = '<?= base_url($meal_url ?? 'master_files/meal_master') ?>';
    const PRICING_URL = '<?= base_url($pricing_url ?? 'tour/pricing') ?>';
    const HOTELS_URL = '<?= base_url('tour/hotel_bookings') ?>';
    const TRANSPORT_URL = '<?= base_url('tour/transport_ops') ?>';
    const BOARD_URL = '<?= base_url('tour/board') ?>';
    // which of the desks and the Board this sign-in may open: the rest are shown as labels, never as links
    const CAN = { hotel: <?= !empty($can_hotel) ? 'true' : 'false' ?>, transport: <?= !empty($can_transport) ? 'true' : 'false' ?>, board: <?= !empty($can_board) ? 'true' : 'false' ?> };
    const deskOpen = tid => ({ hotels: CAN.hotel ? `${HOTELS_URL}?tour=${tid}` : null, activities: CAN.hotel ? `${HOTELS_URL}?tour=${tid}` : null, vehicle: CAN.transport ? `${TRANSPORT_URL}?tour=${tid}` : null, driver: CAN.transport ? `${TRANSPORT_URL}?tour=${tid}` : null, guide: CAN.transport ? `${TRANSPORT_URL}?tour=${tid}` : null });
    // the red counts on the bar, re-read after Confirm Tour: Inquiries from this screen's own list, the
    // desks' counts from their screens where this group may open them (a refusal leaves the count as it was)
    function setBarBadge(url, n) {
        const tab = [...document.querySelectorAll('.tdbar a.td-item')].find(a => (a.getAttribute('href') || '').replace(/\/$/, '') === url);
        if (!tab || n === null || n === undefined) return;
        let b = tab.querySelector('.td-badge');
        if (Number(n) > 0) { if (!b) { b = document.createElement('span'); b.className = 'td-badge'; tab.appendChild(b); } b.textContent = String(n); }
        else if (b) { b.remove(); }
    }
    async function refreshBadges() {
        try { const j = await api('/list?status=open&limit=1'); if (j.status === 200) setBarBadge(BASE, (j.data.counts || {}).open); } catch (e) {}
        for (const u of [HOTELS_URL, TRANSPORT_URL]) {
            try { const r = await fetch(u + '/tours?which=pending', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }); if (r.ok) { const j = await r.json(); if (j.status === 200) setBarBadge(u, (j.data.counts || {}).pending); } } catch (e) {}
        }
    }
    // the ⋯ Manage menus post to the Manage privilege's own URL; the server asks the same question again
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const MANAGE_BASE = '<?= base_url($manage_url ?? 'tour/manage') ?>';
    const HANDLERS = <?= json_encode(array_values((array) ($handlers ?? [])), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const MY_ID = <?= (int) ($user_id ?? 0) ?>;
    // the *_by columns carry the sign-in name; the staff list turns it into the person's name where it can
    const staffName = u => { if (!u) return ''; const h = HANDLERS.find(x => x.username === u); return h ? h.name : u; };
    const NAMES = Object.fromEntries(HANDLERS.filter(h => h.username).map(h => [h.username, h.name]));
    const LABELS = {
        agent_name:'Agent', agent_email:'Agent email', agent_country:'Country', agent_reference:'Agent reference',
        tour_name:'Group / tour name', arrival_date:'Arrival', departure_date:'Departure', nights:'Nights',
        pax_total:'Passengers', pax_adults:'Adults', pax_children:'Children',
        rooms_note:'Rooms', hotel_class:'Hotel class', arrival_flight:'Arrival flight', departure_flight:'Departure flight',
        meal_basis:'Meal basis (whole tour)'
    };
    const GROUPS = [
        { title:'Agent & booking', keys:['agent_name','agent_email','agent_country','agent_reference','tour_name','hotel_class'] },
        { title:'Dates & party',   keys:['arrival_date','departure_date','nights','pax_total','pax_adults','pax_children','rooms_note','meal_basis','arrival_flight','departure_flight'] }
    ];
    const RATE = {
        rate:          ['pill-green','Published rate'],
        no_plan:       ['pill-red','Published rate missing'],
        no_supplier:   ['pill-red','No supplier record'],
        not_in_panel:  ['pill-red','Not in the panel'],
        nothing_named: ['pill-grey','No hotel named'],
        not_checked:   ['pill-grey','Not checked']
    };

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const show = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show(); };
    const hide = sel => { const i = bootstrap.Modal.getInstance(document.querySelector(sel)); if (i) i.hide(); };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2000 : undefined, showConfirmButton: icon !== 'success' });
    /**
     * Ask before doing something. Never window.confirm: a browser that has been told to stop
     * showing this page's dialogs returns false from it without a word, and the button then
     * looks broken. This is the panel's own dialog, which cannot be suppressed.
     */
    const ask = async (text, okWord) => {
        const r = await Swal.fire({ text, showCancelButton: true, confirmButtonText: okWord || 'Yes', cancelButtonText: 'Cancel', icon: 'question' });
        return !!r.isConfirmed;
    };
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    // One request per click: an identical POST pressed again while the first is still on its
    // way (a double click on Save or Add, a slow reply) rides on the first instead of going
    // out twice. The server would merge it anyway; this keeps the screen from asking.
    const INFLIGHT = new Map();
    const post = (path, body) => {
        const payload = JSON.stringify(body || {});
        const key = path + '\n' + payload;
        if (INFLIGHT.has(key)) return INFLIGHT.get(key);
        const p = api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: payload }).finally(() => INFLIGHT.delete(key));
        INFLIGHT.set(key, p);
        return p;
    };
    const fmtDate = d => { if (!d) return null; const t = new Date(d + 'T00:00:00'); return isNaN(t) ? d : t.toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }); };
    // the date n days after an ISO date, as an ISO date (schema 32: a night added moves the departure)
    const addDaysIso = (d, n) => { const t = new Date(d + 'T12:00:00'); t.setDate(t.getDate() + n); return t.toISOString().slice(0, 10); };

    let CURRENT = null;

    function setView(which) {
        ['view_list','view_paste','view_manual','view_detail'].forEach(v => { const el = document.getElementById(v); if (el) el.style.display = (v === which ? '' : 'none'); });
        window.scrollTo({ top:0, behavior:'smooth' });
    }

    // ---------- list ----------
    // Every filter combines with every other — status, handling agent, customer text, the
    // arrival window — so one colleague's quoted tours for one customer in March is one list.
    const filterValues = () => ({
        status: document.getElementById('f_status').value, q: document.getElementById('f_q').value.trim(),
        handler: document.getElementById('f_handler').value, from: document.getElementById('f_from').value, to: document.getElementById('f_to').value,
    });
    let LIST = [];
    async function loadList() {
        const f = filterValues();
        const j = await api('/list?' + new URLSearchParams(f).toString());
        const tb = document.getElementById('rows');
        if (j.status !== 200) { tb.innerHTML = `<tr><td colspan="9" class="empty">${esc(j.message)}</td></tr>`; return; }
        const items = LIST = j.data.items || [];
        const c = j.data.counts || {};
        const filtered = f.q || f.handler || f.from || f.to;
        document.getElementById('list_foot').innerHTML = `${items.length} shown · ${c.open ?? 0} open, ${c.confirmed ?? 0} confirmed, ${c.declined ?? 0} declined${c.archived ? `, ${c.archived} archived` : ''}${c.follow_up ? ` · <b style="color:var(--tf-amber)">${c.follow_up} follow-up${c.follow_up === 1 ? '' : 's'} due</b>` : ''}${filtered ? ' · <b>filtered</b>' : ''}`;
        const fo = document.getElementById('f_follow');
        if (fo) fo.textContent = 'Follow-ups due' + (c.follow_up ? ` (${c.follow_up})` : '');
        if (!items.length) {
            tb.innerHTML = `<tr><td colspan="9" class="empty">${filtered || f.status !== 'open' ? '<b>Nothing matches these filters.</b> Clear one of them, or press ✕ to clear them all.' : '<b>No inquiries yet.</b> Press <b>New quote</b> and paste an agent\'s email.'}</td></tr>`;
            return;
        }
        tb.innerHTML = items.map(it => {
            const needs = [];
            if (Number(it.has_email) === 0) needs.push('<span class="pill pill-grey"><i class="bi bi-pencil"></i> By hand</span>');
            else if (it.extract_error) needs.push('<span class="pill pill-amber">Could not be read</span>');
            else if (!it.extracted_at) needs.push('<span class="pill pill-blue">Not read yet</span>');
            if (it.open_days) needs.push(`<span class="pill pill-red">${it.open_days} hotel${it.open_days > 1 ? 's' : ''} unpriceable</span>`);
            if (it.open_questions) needs.push(`<span class="pill pill-amber">${it.open_questions} question${it.open_questions > 1 ? 's' : ''}</span>`);
            if (it.follow_up_on && ['new', 'working', 'quoted'].includes(it.status)) {
                const due = it.follow_up_on <= new Date().toISOString().slice(0, 10);
                needs.push(`<span class="pill ${due ? 'pill-red' : 'pill-blue'}" title="${esc(it.follow_up_note || '')}"><i class="bi bi-bell"></i> ${due ? 'Follow up' : 'Follow up ' + fmtDate(it.follow_up_on)}</span>`);
            }
            if (!needs.length) needs.push('<span class="pill pill-green">Nothing outstanding</span>');
            const dates = it.arrival_date ? (fmtDate(it.arrival_date) + (it.departure_date ? ' – ' + fmtDate(it.departure_date) : '')) : '<span class="pill pill-amber">No dates</span>';
            const mine = MY_ID && Number(it.handler_id) === MY_ID;
            return `<tr class="${it.archived_at ? 'released' : ''}" data-row="${it.id}">
                <td class="mono">${esc(it.inquiry_no)}<div class="help">${statusPill(it.status)}${it.archived_at ? ' <span class="pill pill-grey">archived</span>' : ''}</div></td>
                <td><b>${esc(it.agent_name || 'Agent not identified')}</b>${it.agent_acc ? `<div class="help mono">${esc(it.agent_acc)}</div>` : ''}</td>
                <td>${it.tour_name ? esc(it.tour_name) : '<span class="help">no group name</span>'}</td>
                <td>${it.handler_name ? `<b>${esc(it.handler_name)}</b>${mine ? ' <span class="pill pill-teal">you</span>' : ''}` : '<span class="pill pill-amber">nobody</span>'}${it.updated_by && staffName(it.updated_by) !== it.handler_name && it.updated_by !== it.handler_name ? `<div class="help" title="Last edited ${esc(it.updated_at || '')}">edited by ${esc(staffName(it.updated_by))}</div>` : ''}</td>
                <td>${dates}${it.nights ? `<div class="help">${it.nights} nights</div>` : ''}</td>
                <td>${it.pax_total ?? '<span class="pill pill-amber">?</span>'}</td>
                <td>${esc(it.rooms_note || '—')}</td>
                <td>${needs.join(' ')}</td>
                <td style="white-space:nowrap">${!it.archived_at && ['new', 'working', 'quoted'].includes(it.status) && it.issued_version ? `<button class="btn btn-success btn-sm" data-confirm="${it.id}" title="Quotation v${esc(it.issued_version)} was issued — the agent said yes"><i class="bi bi-check2-circle"></i> Confirm Tour</button> ` : ''}<button class="btn btn-light btn-sm" data-open="${it.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-manage="${it.id}" title="Manage" type="button">···</button>` : ''}</td></tr>`;
        }).join('');
        tb.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openIntake(Number(b.dataset.open))));
        tb.querySelectorAll('[data-confirm]').forEach(b => b.addEventListener('click', async () => { await openIntake(Number(b.dataset.confirm)); confirmTour(); }));
        tb.querySelectorAll('[data-manage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const it = LIST.find(x => Number(x.id) === Number(b.dataset.manage)); if (it) manageMenu(b, it); }));
    }

    // ---------- the ⋯ Manage menu (Management only; the server checks the privilege again) ----------
    const manage = (path, body) => fetch(MANAGE_BASE + path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) }).then(r => r.json());
    const manageGet = path => fetch(MANAGE_BASE + path, { headers: { 'X-Requested-With':'XMLHttpRequest' } }).then(r => r.json());
    function manageMenu(btn, it) {
        const archived = !!it.archived_at;
        const items = [{ title: it.inquiry_no + (it.tour_name ? ' · ' + it.tour_name : '') }];
        if (!archived) {
            items.push({ label: 'Reassign handling agent…', icon: 'bi-person-check', hint: it.handler_name ? 'Now ' + it.handler_name : 'Nobody handles it yet', onClick: () => openReassign(it) });
            items.push({ sep: true });
            items.push({ label: 'Archive…', icon: 'bi-archive', hint: 'Hidden from every list, kept, restorable', onClick: () => openArchive(it) });
        } else {
            items.push({ label: 'Restore', icon: 'bi-arrow-counterclockwise', hint: 'Back on the list as it was', onClick: async () => {
                const j = await manage('/intake/' + it.id + '/restore', {});
                if (j.status !== 200) { toast('error', j.message); return; }
                toast('success', j.message); afterManage(it.id, j.data);
            } });
            items.push({ sep: true });
            items.push({ label: 'Remove for good…', icon: 'bi-trash', danger: true, hint: 'Only with nothing attached; asks for the number', onClick: () => openDelete(it) });
        }
        items.push({ sep: true });
        items.push({ label: 'History', icon: 'bi-clock-history', hint: 'Every edit, who and when', onClick: () => openIntake(Number(it.id), true) });
        window.tdMenu(btn, items);
    }
    function afterManage(id, data) {
        if (CURRENT && Number(CURRENT.id) === Number(id) && data && data.id) { CURRENT = data; renderDetail(); }
        if (document.getElementById('view_list').style.display !== 'none') loadList();
    }
    function openReassign(it) {
        document.getElementById('mra_id').value = it.id;
        document.getElementById('mra_title').textContent = 'Reassign ' + it.inquiry_no;
        document.getElementById('mra_now').innerHTML = it.handler_name ? `Handled by <b>${esc(it.handler_name)}</b>${it.handler_set_at ? ` since ${esc(String(it.handler_set_at).slice(0, 10))}` : ''}${it.handler_set_by && it.handler_set_by !== 'installer' ? ` (set by ${esc(staffName(it.handler_set_by))})` : ''}.` : 'Nobody handles this tour yet.';
        const sel = document.getElementById('mra_user');
        sel.innerHTML = HANDLERS.filter(h => h.id).map(h => `<option value="${h.id}" ${Number(h.id) === Number(it.handler_id) ? 'selected' : ''}>${esc(h.name)}${h.group ? ' · ' + esc(h.group) : ''}</option>`).join('') || '<option value="">No user accounts hold a Tour Desk screen</option>';
        document.getElementById('mra_note').value = '';
        show('#m_reassign');
    }
    document.getElementById('mra_ok')?.addEventListener('click', async () => {
        const id = document.getElementById('mra_id').value;
        const j = await manage('/intake/' + id + '/reassign', { user_id: document.getElementById('mra_user').value, note: document.getElementById('mra_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_reassign'); toast('success', j.message); afterManage(id, j.data);
    });
    const depsText = d => {
        const lines = [];
        if (d.tour) lines.push(`Tour <b>${esc(d.tour.tour_no)}</b> (${esc(d.tour.stage)}): ${d.hotel_cards} hotel card(s), ${d.hotel_cards_moved} moved · ${d.transport_cards} transport card(s), ${d.transport_cards_moved} moved`);
        else lines.push('No tour on the desks');
        lines.push(`${d.quotes} quotation version(s), ${d.quotes_issued} issued · ${d.nights} night(s), ${d.passengers} name(s), ${d.services} service(s) · ${d.history} history line(s)`);
        if (d.legacy_tour_id) lines.push(`Linked to legacy tour <b>${esc(d.legacy_tour_id)}</b>`);
        return lines.join('<br>');
    };
    async function openArchive(it) {
        const j = await manageGet('/intake/' + it.id + '/dependencies');
        if (j.status !== 200) { toast('error', j.message); return; }
        const d = j.data;
        document.getElementById('mar_id').value = it.id;
        document.getElementById('mar_title').textContent = 'Archive ' + it.inquiry_no;
        document.getElementById('mar_deps').innerHTML = depsText(d) + (d.blocks_archive.length ? `<div class="mt-2" style="color:var(--tf-red)"><b>Cannot be archived:</b> ${d.blocks_archive.map(esc).join('; ')}.</div>` : '');
        document.getElementById('mar_ok').disabled = d.blocks_archive.length > 0;
        document.getElementById('mar_note').value = '';
        show('#m_archive');
    }
    document.getElementById('mar_ok')?.addEventListener('click', async () => {
        const id = document.getElementById('mar_id').value;
        const j = await manage('/intake/' + id + '/archive', { note: document.getElementById('mar_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_archive'); toast('success', j.message); afterManage(id, j.data);
    });
    async function openDelete(it) {
        const j = await manageGet('/intake/' + it.id + '/dependencies');
        if (j.status !== 200) { toast('error', j.message); return; }
        const d = j.data;
        document.getElementById('mdl_id').value = it.id;
        document.getElementById('mdl_title').textContent = 'Remove ' + it.inquiry_no + ' for good';
        document.getElementById('mdl_deps').innerHTML = depsText(d) + (d.blocks_delete.length ? `<div class="mt-2" style="color:var(--tf-red)"><b>Cannot be removed:</b> ${d.blocks_delete.map(esc).join('; ')}.</div>` : '');
        document.getElementById('mdl_form').style.display = d.blocks_delete.length ? 'none' : '';
        document.getElementById('mdl_ok').disabled = d.blocks_delete.length > 0;
        document.getElementById('mdl_confirm').value = '';
        show('#m_delete');
    }
    document.getElementById('mdl_ok')?.addEventListener('click', async () => {
        const id = document.getElementById('mdl_id').value;
        const j = await manage('/intake/' + id + '/delete', { confirm: document.getElementById('mdl_confirm').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_delete'); toast('success', j.message);
        if (CURRENT && Number(CURRENT.id) === Number(id)) { CURRENT = null; setView('view_list'); }
        loadList();
    });

    // ---------- detail ----------
    async function openIntake(id, toHistory) {
        const j = await api('/intake/' + id);
        if (j.status !== 200) { toast('error', j.message); return; }
        await loadCrew();   // the driver and guide sections name each line by its master type; read once, kept
        CHECK.armed = false; CHECK.items = []; CHECK.fixed = 0; showPill();
        CURRENT = j.data;
        renderDetail();
        setView('view_detail');
        if (toHistory) setTimeout(() => document.getElementById('act_card')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
    }

    // The tour's history: every edit (who, when, what moved) and every move on the desks, in one list.
    async function loadHistory() {
        const box = document.getElementById('act_body');
        if (!box || !CURRENT) return;
        const j = await api('/intake/' + CURRENT.id + '/activity');
        if (j.status !== 200) { box.innerHTML = `<div class="help">${esc(j.message)}</div>`; return; }
        box.innerHTML = window.tdHistory(j.data || [], { empty: 'Nothing recorded yet.', names: NAMES });
        const n = (j.data || []).length;
        const h = document.getElementById('act_count');
        if (h) h.textContent = n ? n + ' line' + (n === 1 ? '' : 's') : '';
    }

    function fieldCard(key) {
        const f = (CURRENT.fields || {})[key] || { value:null, state:'missing' };
        const cls = f.origin === 'human' ? 'human' : (f.state === 'missing' ? 'missing' : (f.state === 'unverified' ? 'unverified' : (f.state === 'derived' ? 'derived' : '')));
        let tag = '<span class="tag tag-src">Stated</span>';
        if (f.origin === 'human') tag = '<span class="tag tag-you">You</span>';
        else if (f.state === 'missing') tag = '<span class="tag tag-ask">Ask</span>';
        else if (f.state === 'unverified') tag = '<span class="tag tag-bad">Unverified</span>';
        else if (f.state === 'derived') tag = '<span class="tag tag-you">Worked out</span>';
        let v = f.value;
        if (v && (key === 'arrival_date' || key === 'departure_date')) v = fmtDate(v);
        return `<button class="fld ${cls}" data-field="${key}" data-quote="${esc(f.quote || '')}">
            <span class="k">${esc(LABELS[key] || key)} ${tag}</span>
            <span class="v">${v ? esc(v) : 'Not stated'}</span>
            ${f.basis ? `<span class="help">${esc(f.basis)}</span>` : ''}</button>`;
    }

    // why Google has no figure for a leg, in two words
    const GBY = { not_found: 'not found', no_key: 'no key', error: 'not reached', none: 'not asked' };

    /**
     * The workflow bar: Inquiry → Quotation → Confirm Tour. Each step shows where this inquiry
     * stands and carries its action, so the next thing to do is at the top of the screen, not
     * at the foot of a long page. Confirm Tour is offered only once a quotation version has
     * been issued — the version the agent accepted — and never on a read-only inquiry.
     */
    function renderFlow(it) {
        const box = document.getElementById('flow');
        if (!box) return;
        const gapCount = (it.gaps || []).length;
        const quotes = it.quotes || [];
        const issued = quotes.filter(q => q.status === 'issued').sort((a, b) => Number(b.version) - Number(a.version))[0] || null;
        const draft = quotes.find(q => q.status === 'draft') || null;
        const p = it.progress || null;
        const canConfirm = !it.locked && ['new', 'working', 'quoted'].includes(it.status);
        const confirmed = it.status === 'confirmed' || (it.confirmed_at && p);
        const stale = (it.edit_requests && it.edit_requests.stale_issue) || null;
        const fmtUsd = v => v != null ? 'USD ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '';
        // 1 — the inquiry
        const s1cls = confirmed || issued || it.status === 'quoted' ? 'done' : 'on';
        const came = (it.source_text || '').trim() ? 'from the agent\'s email' : 'entered by hand';
        const s1sub = came + ' · ' + (it.status === 'cancelled' ? 'cancelled' : (it.status === 'declined' ? 'declined' : (it.status === 'confirmed' ? 'confirmed — the record of the sale' : (gapCount ? `${gapCount} item${gapCount === 1 ? '' : 's'} still needed` : 'ready to quote'))));
        // 2 — the quotation
        const s2cls = issued ? 'done' : (draft || it.status === 'quoted' ? 'on' : (confirmed ? 'done' : ''));
        const s2sub = issued ? `v${issued.version} issued ${esc((issued.issued_at || '').slice(0, 10))}${issued.sell_usd != null ? ' · ' + fmtUsd(issued.sell_usd) : ''}`
            : (draft ? `v${draft.version} is a draft — issue it once it has gone to the agent` : (it.status === 'quoted' ? 'marked as quoted — no version issued' : 'no version yet'));
        const s2acts = it.locked ? '' : (draft ? `<button class="btn btn-success btn-sm" data-flow-issue="${draft.id}"><i class="bi bi-send"></i> Issue v${draft.version}</button>` : '')
            + `<button class="btn btn-light btn-sm" id="f_goquote"><i class="bi bi-arrow-down"></i> ${issued || draft ? 'Quotation' : 'Go for Quotation'}</button>`;
        // 3 — Confirm Tour
        let s3cls = '', s3sub = '', s3acts = '';
        if (confirmed) {
            s3cls = 'done';
            const no = p && p.tour_no ? p.tour_no : '';
            s3sub = `Confirmed ${esc((it.confirmed_at || '').slice(0, 10))} by ${esc(staffName(it.confirmed_by) || '')}${no ? ' · tour <b>' + esc(no) + '</b>' : ''}${p && p.quote_version ? ' · sold as v' + p.quote_version : ''}${it.confirmed_at && !it.locked ? ' · <span class="pill pill-amber">open for amendment</span>' : ''}`;
            const tid = p && p.tour_id ? p.tour_id : null;
            s3acts = tid ? `${CAN.hotel ? `<a class="btn btn-light btn-sm" href="${HOTELS_URL}?tour=${tid}"><i class="bi bi-door-open"></i> Hotel Bookings</a>` : ''}${CAN.transport ? `<a class="btn btn-light btn-sm" href="${TRANSPORT_URL}?tour=${tid}"><i class="bi bi-truck"></i> Transport</a>` : ''}` : '';
            if (it.confirmed_at && !it.locked && canConfirm) s3acts += stale
                ? `<button class="btn btn-light btn-sm" id="d_confirm_top" disabled title="Edit Request #${stale.seq} was applied after v${stale.version} was made: Issue a new version first"><i class="bi bi-check2-circle"></i> Confirm Tour again</button>`
                : `<button class="btn btn-success btn-sm" id="d_confirm_top"><i class="bi bi-check2-circle"></i> Confirm Tour again</button>`;
        } else if (it.status === 'cancelled' || it.status === 'declined') {
            s3sub = it.status === 'cancelled' ? 'cancelled' : 'declined — nothing goes to the desks';
        } else if (canConfirm && issued && stale) {
            // schema 33: an Edit Request applied after the issued version — a new version first
            s3sub = `Edit Request #${stale.seq} was applied after v${stale.version} was made. Go for Quotation and Issue a new version first: the version confirmed has to show the inquiry as it is now.`;
            s3acts = `<button class="btn btn-light btn-sm" id="d_confirm_top" disabled title="Issue a new quotation version first"><i class="bi bi-check2-circle"></i> Confirm Tour</button>`;
        } else if (canConfirm && issued) {
            s3cls = 'go';
            s3sub = 'The agent accepted v' + issued.version + '? One job goes to Hotel Bookings and one to Transport, and this inquiry becomes the record of the sale.';
            s3acts = `<button class="btn btn-success btn-sm" id="d_confirm_top"><i class="bi bi-check2-circle"></i> Confirm Tour</button>`;
        } else {
            s3sub = it.locked ? 'read-only' : 'Issue the quotation version the agent accepted first; Confirm Tour then puts one job on each desk.';
            s3acts = canConfirm ? `<button class="btn btn-light btn-sm" id="d_confirm_top" disabled title="Issue a quotation version first"><i class="bi bi-check2-circle"></i> Confirm Tour</button>` : '';
        }
        box.innerHTML = `
            <div class="st ${s1cls}"><span class="n">${s1cls === 'done' ? '<i class="bi bi-check"></i>' : '1'}</span><span class="body"><b>Inquiry</b><span class="sub">${esc(it.inquiry_no)} · ${statusWord(it.status)} · ${s1sub}</span></span></div>
            <div class="st ${s2cls}"><span class="n">${s2cls === 'done' ? '<i class="bi bi-check"></i>' : '2'}</span><span class="body"><b>Quotation</b><span class="sub">${s2sub}</span>${s2acts ? `<span class="acts">${s2acts}</span>` : ''}</span></div>
            <div class="st ${s3cls}"><span class="n">${s3cls === 'done' ? '<i class="bi bi-check"></i>' : '3'}</span><span class="body"><b>Confirm Tour</b><span class="sub">${s3sub}</span>${s3acts ? `<span class="acts">${s3acts}</span>` : ''}</span></div>`;
        document.getElementById('d_confirm_top')?.addEventListener('click', confirmTour);
        document.getElementById('f_goquote')?.addEventListener('click', () => { const q = document.getElementById('d_go') || document.querySelector('#detail_cols .tf-card[style*="tf-teal"]'); (q ? q.closest('.tf-card') || q : null)?.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
        box.querySelectorAll('[data-flow-issue]').forEach(b => b.addEventListener('click', () => document.querySelector(`[data-issue="${b.dataset.flowIssue}"]`)?.click()));
    }
    function statusWord(st) { return ({ new: 'new', working: 'being prepared', quoted: 'quoted', confirmed: 'confirmed', declined: 'declined', cancelled: 'cancelled' })[st] || st; }

    function renderDetail() {
        const it = CURRENT;
        const gapCount = (it.gaps || []).length;
        renderFlow(it);
        // schema 32: one line for the tour's shape — the dates decide everything below it
        (() => {
            const bar = document.getElementById('dates_bar'); if (!bar) return;
            const td = it.tour_dates || {}, dc = it.dates_check;
            let warn = '';
            if (dc && dc.differs && !dc.confirmed) {
                const one = (dc.open || []).length === 1 ? dc.open[0] : null;   // schema 33: the other date settled by an Edit Request
                warn = `<div class="datewarn" data-dates-warn><span class="pill pill-red">Dates differ from the email</span>
                    <span>The email says <b>${esc(dc.email_line)}</b>. This inquiry has <b>${esc(dc.line)}</b>${dc.by_hand ? ' (changed by hand)' : ''}.${dc.settled_line ? ' ' + esc(dc.settled_line) + ' Still to confirm — ' + esc(dc.open_line) + '.' : ' Confirm which is right'} — Go for Quotation waits until then.</span>
                    ${it.locked ? '' : `<button class="btn btn-primary btn-sm" id="d_datekeep">Keep these dates</button><button class="btn btn-light btn-sm" id="d_dateemail">Use the email's ${one ? esc(one) : 'dates'}</button>`}</div>`;
            } else if (dc && dc.differs && dc.confirmed) {
                warn = `<div class="datenote" data-dates-kept>The email says ${esc(dc.email_line)}. These dates were confirmed by ${esc(dc.confirmed_by || 'a colleague')}${dc.confirmed_at ? ' on ' + fmtDate(String(dc.confirmed_at).slice(0, 10)) : ''}.</div>`;
            }
            bar.innerHTML = `<div class="datesline" data-dates-line title="The arrival and departure dates decide the nights, the days and the departure day">${esc(td.line || '')}</div>${warn}`;
        })();
        document.getElementById('summary').innerHTML = [
            ['Inquiry', esc(it.inquiry_no), esc(it.status)],
            ['Agent', esc(it.agent_name || '—'), it.market_code ? 'market ' + esc(it.market_code) : 'market not resolved'],
            ['Group', esc(it.tour_name || '—'), ''],
            ['Arrival', it.arrival_date ? fmtDate(it.arrival_date) : '—', it.departure_date ? 'to ' + fmtDate(it.departure_date) : ''],
            ['Travellers', it.pax_total ?? '—', it.nights ? it.nights + ' nights' : ''],
            ['Rooms', roomsShort(it.rooms) || '<span class="pill pill-red">not set</span>', it.rooms && it.rooms.rooms ? 'sleeps ' + it.rooms.sleeps + (it.rooms.source === 'groups' ? ' · ' + (it.groups || []).length + ' groups' : '') : esc(it.rooms_note || '')],
            // ownership is one thing, editing another: whose tour it is, and who last touched it
            ['Handling agent', it.handler && it.handler.name ? esc(it.handler.name) : '<span class="pill pill-amber">nobody</span>', it.handler && it.handler.set_at ? 'since ' + esc(String(it.handler.set_at).slice(0, 10)) + (it.handler.set_by && it.handler.set_by !== 'installer' ? ' · by ' + esc(staffName(it.handler.set_by)) : '') : ''],
            ['Last edited', it.last_edit && it.last_edit.by ? esc(staffName(it.last_edit.by)) : '—', it.last_edit && it.last_edit.at ? esc(String(it.last_edit.at).slice(0, 16)) : '']
        ].map(([k, v, s]) => `<div class="sm"><div class="k">${k}</div><div class="v">${v}${s ? `<small>${s}</small>` : ''}</div></div>`).join('')
        + (it.status === 'confirmed'
            ? `<div class="sm"><div class="k">Still needed</div><div class="v">Nothing<small>confirmed — the record of the sale</small></div></div>`
            : `<div class="sm ${gapCount ? 'flag' : ''}"><div class="k">Still needed</div><div class="v">${gapCount || 'Nothing'}${gapCount ? '<small>listed below</small>' : '<small>ready to quote</small>'}</div></div>`);

        // One chip per thing happening that day. Colour says only what is true: green
        // when it has a price from the company's own list, red when it is not in that
        // list at all, struck through when the agent said not to arrange it.
        function servicesCell(d) {
            const list = d.services || [];
            // schema 40: a lunch place of the Meal Master is shown on its own line (Lunch place), not as a chip
            const lpOn = !!(CURRENT && CURRENT.meals && CURRENT.meals.available);
            const chips = list.filter(s => !(lpOn && s.meal_id && s.kind === 'lunch')).map(s => {
                const declined = s.pay_mode === 'own_arrangement' || s.pay_mode === 'not_arranged';
                // schema 39: an entrance or safari not in the Place to Visit / Entrance Fee Master, or one with no rate for the day, is held — never free
                const held = !declined && (s.match_state === 'not_in_master' || s.match_state === 'rate_missing');
                const free = !held && (s.pay_mode === 'no_charge' || s.match_state === 'no_charge');
                const cls = declined ? 'declined' : (free ? 'free' : (s.match_state === 'not_in_list' || held ? 'offlist'
                    : (s.amount_usd !== null && s.amount_usd !== undefined ? 'priced' : '')));
                const amt = (!declined && !free && s.amount_usd != null) ? `<span class="amt">$${Number(s.amount_usd).toFixed(0)}</span>` : (free ? '<span class="amt">free</span>' : (held ? '<span class="amt">held</span>' : ''));
                return `<span class="svc ${cls}" data-svc="${s.id}" title="${esc(s.free_visit_id ? 'Free visit from the master — on the itinerary only, never costed' : (s.match_note || s.source_quote || ''))}">${esc(s.title)}${amt}</span>`;
            }).join('');
            // the night's activities and excursions (Paid Activities & Services), costed at the master's rate
            const exc = d.excursions || [];
            const excChips = exc.map(x => `<span class="svc priced" data-dayexc="${x.id}" title="Activity, ${esc(x.currency)} ${Number(x.rate).toLocaleString()} ${esc(({ per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' })[x.unit] || x.unit)} × ${Number(x.qty)}">${esc(x.name)}<span class="amt">${x.currency === 'USD' ? '$' + Number(x.amount).toFixed(0) : esc(x.currency) + ' ' + Number(x.amount).toLocaleString()}</span></span>`).join('');
            const total = list.reduce((n, s) => n + (Number(s.amount_usd) || 0), 0) + exc.filter(x => x.currency === 'USD').reduce((n, x) => n + (Number(x.amount) || 0), 0);
            const lkr = exc.filter(x => x.currency !== 'USD').reduce((n, x) => n + (Number(x.amount) || 0), 0);
            const tot = total > 0 || lkr > 0 ? `<div class="daytot">${total > 0 ? '$' + total.toFixed(2) : ''}${lkr > 0 ? (total > 0 ? ' + ' : '') + 'LKR ' + lkr.toLocaleString() : ''} for the day${list.some(s => s.amount_usd == null && (!['own_arrangement', 'not_arranged', 'no_charge'].includes(s.pay_mode) || s.match_state === 'not_in_master') && s.match_state !== 'no_charge') ? ' + unpriced items' : ''}</div>` : '';
            return `<div class="svcs">${chips}${excChips}<button class="svc-add" data-addsvc="${d.day_no}">${list.length || exc.length ? '+' : '+ Add'}</button></div>${lunchPlace(d.day_no)}${tot}`;
        }

        // The rate for the night: the room type chosen (or to choose) and ONE published figure
        // per room type the agent asked for — a Deluxe DBL BB request shows the DBL rate and
        // nothing else. Nothing here is typed; a missing figure is said, never filled in.
        const fmtN = v => Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 });
        // the occupancies asked for on this night, from the room mix: [['dbl','DBL',2], ...]
        const asked = d => {
            const m = d.room_mix || {};
            return [['sgl', 'SGL'], ['dbl', 'DBL'], ['twn', 'TWN'], ['tpl', 'TPL']].filter(([k]) => Number(m[k]) > 0).map(([k, l]) => [k, l, Number(m[k])]);
        };
        const rateFor = (k, r) => k === 'sgl' ? r.sgl : (k === 'tpl' ? r.tpl : r.dbl);   // a twin is priced as a double
        // "USD 320 (DBL)" / "USD 320 ×2 (DBL), 283 (SGL)" / per unit — or null when nothing can be picked
        const figureFor = (d, r, cur) => {
            if (r.unit != null) return `${cur} ${fmtN(r.unit)} per unit${d.unit_pax_max ? ' (up to ' + d.unit_pax_max + ')' : ''}`;
            const want = asked(d);
            if (!want.length) return null;
            const parts = want.map(([k, l, n]) => { const v = rateFor(k, r); return (v == null ? 'no ' + l + ' rate' : fmtN(v)) + (n > 1 ? ' ×' + n : '') + ' <span class="help">' + l + '</span>'; });
            return `${cur} ${parts.join(', ')}`;
        };
        const hasFig = r => r && (r.unit != null || r.sgl != null || r.dbl != null || r.tpl != null);
        function nightRate(d) {
            const [cls, label] = RATE[d.rate_state] || RATE.not_checked;
            let line = '';
            if (d.rate_state === 'rate') {
                const priced = (d.room_types || []).filter(t => t.priced);
                if (d.room_type_id == null) {
                    line = priced.length > 1
                        ? `<div class="rateline"><button class="btn btn-primary btn-sm" data-day="${d.id}" title="Choose the room type from the panel">Choose room type</button> <span class="help">${priced.length} priced</span></div>`
                        : `<div class="rateline"><span class="pill pill-red">Published rate missing</span> <span class="help">no room type carries a published rate</span></div>`;
                } else {
                    const cur = esc(d.rate_currency || 'USD');
                    const kept = { sgl: d.rate_sgl, dbl: d.rate_dbl, tpl: d.rate_tpl, unit: d.rate_unit };
                    const want = asked(d);
                    const chosen = (d.room_types || []).find(t => Number(t.id) === Number(d.room_type_id));
                    const meals = chosen && chosen.meals ? Object.entries(chosen.meals) : [];
                    let body;
                    if (hasFig(kept)) {
                        const fig = figureFor(d, kept, cur);
                        body = fig !== null ? '· ' + (d.meal_plan ? esc(d.meal_plan) + ' · ' : '') + fig
                            : `· ${d.meal_plan ? esc(d.meal_plan) + ' · ' : ''}<span class="help">set the rooms (SGL / DBL / TWN / TPL) to see the rate for what was asked</span>`;
                    } else if (!d.meal_plan) {
                        // no meal plan on the night: one figure per meal plan, for the occupancy asked for, and the person picks
                        const chip = ([mp, m]) => {
                            const fig = want.length ? want.map(([k, l, n]) => { const v = rateFor(k, m); return (v == null ? '—' : fmtN(v)) + (want.length > 1 ? ' ' + l : ''); }).join(' · ') : '';
                            return `<button class="btn btn-light btn-sm mealpick" data-mealpick="${esc(mp)}" data-dayid="${d.id}" title="Set ${esc(mp)} on this night">${esc(mp)}${fig ? ' ' + fig : ''}</button>`;
                        };
                        body = meals.length
                            ? `<span class="pill pill-amber">Meal plan not stated</span> <span class="help">${want.length ? cur + (want.length === 1 ? ' per ' + want[0][1] + ' room' : '') + ' — pick one:' : 'pick one (set the rooms to see the figures):'}</span> ${meals.map(chip).join(' ')}`
                            : `<span class="pill pill-amber">Meal plan not stated</span> <span class="help">no meal plan on this night, and no published rate for ${esc(d.room_type_name || 'this room type')} either — fix it in Hotel Rates</span>`;
                    } else {
                        const others = meals.filter(([mp]) => mp !== d.meal_plan);
                        body = `<span class="pill pill-red">Published rate missing</span> <span class="help">no ${esc(d.meal_plan)} rate for ${esc(d.room_type_name || 'this room type')} on this date${others.length ? ' (the panel has ' + others.map(([mp]) => esc(mp)).join(', ') + ')' : ''} — fix it in Hotel Rates; nothing is typed in here</span>`;
                    }
                    const supp = (d.rate_supp || []).length ? `<div class="help">+ ${d.rate_supp.map(x => esc(x.name) + ' ' + fmtN(x.amount) + '/' + esc(x.per)).join(', ')}</div>` : '';
                    // the panel has moved away from the figure kept on the night: shown, never applied by itself
                    let drift = '';
                    if (d.rate_drift) {
                        const dr = d.rate_drift;
                        const now = dr.none ? `no published rate any more${dr.why ? ' — ' + esc(dr.why) : ''}` : `now ${figureFor(d, { sgl: dr.sgl, dbl: dr.dbl, tpl: dr.tpl, unit: dr.unit }, esc(dr.currency || cur)) || (esc(dr.currency || cur) + ' ' + [dr.sgl, dr.dbl, dr.tpl].map(v => v == null ? '—' : fmtN(v)).join(' / '))}${dr.plan_name ? ' <span class="help">(' + esc(dr.plan_name) + ')</span>' : ''}`;
                        drift = `<div class="rateline drift"><span class="pill pill-amber" title="The figure on the night is what was quoted${d.rate_frozen_at ? ', copied ' + fmtDate(String(d.rate_frozen_at).slice(0, 10)) : ''}. Hotel Rates has changed since.">Rate changed in Hotel Rates</span> <span class="help">${now}</span> <button class="btn btn-light btn-sm reprice" data-reprice="${d.id}" title="Replace the figure on this night with the panel's current published rate">Use the new rate</button></div>`;
                    }
                    // tf-walkfix-r1: the room type reads on the row, with whether the master's normal type filled it in or a person chose it; Edit changes it for this booking alone
                    const rtTag = Number(d.room_type_auto) === 1
                        ? ' <span class="pill pill-grey" style="font-size:9.5px" title="The hotel\'s normal room type (the first with a published rate in the Hotel Rates panel\'s own order) was filled in from the master. Edit the night to choose another for this booking; the master is never changed from here.">the hotel\'s normal type</span>'
                        : ' <span class="pill pill-grey" style="font-size:9.5px" title="Chosen on this night for this booking">chosen</span>';
                    const rtWord = d.room_type_text ? `${esc(d.room_type_text)} <span class="help" title="The wording this booking uses; the rate follows the master\'s ${esc(d.room_type_name || '')}">(master: ${esc(d.room_type_name || '')})</span>` : esc(d.room_type_name || '');
                    line = `<div class="rateline">Room type: <b class="dayedit" data-day="${d.id}" title="Change the room type for this booking">${rtWord}</b>${rtTag} ${body}${supp}</div>${drift}`;
                }
            }
            // for a priced night the plan's name sits on hover; the room line below it is what matters
            const title = (d.rate_note || '') + (d.rate_state === 'rate' && d.rate_frozen_at ? ' · copied ' + fmtDate(String(d.rate_frozen_at).slice(0, 10)) : '');
            return `<span class="pill ${cls}" title="${esc(title)}">${label}</span>${d.rate_note && d.rate_state !== 'rate' ? `<div class="help">${esc(d.rate_note)}</div>` : ''}${line}`;
        }
        const days = (it.days || []).map(d => {
            return `<tr>
                <td class="mono">${d.day_no}${d.day_date ? '<div class="help">' + fmtDate(d.day_date) + '</div>' : ''}</td>
                <td><span class="dayedit" data-day="${d.id}" title="Click to set the area">${esc(d.area || 'Area?')}</span></td>
                <td>${d.hotel_name_raw
                    ? `<b class="dayedit" data-day="${d.id}" title="Click to change the hotel">${esc(d.hotel_name_raw)} <i class="bi bi-pencil help"></i></b>`
                    : `<button class="btn btn-primary btn-sm" data-day="${d.id}"><i class="bi bi-building"></i> Choose hotel</button>`}${d.match_note ? `<div class="help">${esc(d.match_note)}</div>` : ''}</td>
                <td>${d.meal_plan ? esc(d.meal_plan) + (d.meal_source === 'tour' ? `<div class="help" title="${esc(d.meal_quote || '')}">from the terms${d.meal_quote ? ': “' + esc(d.meal_quote) + '”' : ''}</div>` : (d.meal_source === 'human' ? '<div class="help">set here</div>' : '')) : '<span class="pill pill-amber">Not stated</span>'}</td>
                <td>${roomsShort(d.room_mix) || (d.rooms ?? '—')}${d.rooms != null && d.room_mix && d.room_mix.rooms ? `<div class="help">agent: ${d.rooms}</div>` : ''}</td>
                <td>${nightRate(d)}</td>
                <td>${servicesCell(d)}</td>
                <td><div class="notecell ${d.special_note ? '' : 'empty-note'}" data-day="${d.id}">${d.special_note ? esc(d.special_note) : 'Add a note'}</div></td>
                <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-day="${d.id}">Edit</button>
                    <button class="btn btn-light btn-sm rowdel" data-delday="${d.id}" title="Remove this night">&times;</button></td></tr>`;
        }).join('');

        // schema 32: a line kept apart because it falls on the departure day — not a hotel night until a person says so
        const tdN = (it.tour_dates || {}).nights;
        const depRows = (it.departure_rows || []).map(d => `<tr class="deprow" data-deprow="${d.id}">
                <td class="mono">${d.day_no}${d.day_date ? '<div class="help">' + fmtDate(d.day_date) + '</div>' : ''}</td>
                <td colspan="5"><span class="pill pill-red">Falls on the departure day</span> <b>${esc(d.hotel_name_raw || d.area || '')}</b>
                    <div class="help">The dates give ${tdN} night${tdN === 1 ? '' : 's'}, so this line is not a hotel night and is not costed. If it is the day they fly out, make it the departure day: what is on it moves there. If the party really stays that night, keep it as an extra night: the departure moves a day later.</div>
                    ${it.locked ? '' : `<div class="mt-1"><button class="btn btn-primary btn-sm" data-depmake="${d.id}">Make it the departure day</button> <button class="btn btn-light btn-sm" data-depextra="${d.id}">It's an extra night</button></div>`}</td>
                <td>${servicesCell(d)}</td>
                <td>${d.special_note ? `<div class="notecell">${esc(d.special_note)}</div>` : ''}</td>
                <td></td></tr>`).join('');
        const missRow = (it.missing_nights || []).length ? `<tr class="deprow" data-missing><td colspan="9"><span class="pill pill-red">No line for night${it.missing_nights.length === 1 ? '' : 's'} ${esc(it.missing_nights.join(', '))}</span>
                <span class="help">The dates give ${tdN} nights; every night needs a line and a hotel.</span>
                ${it.locked ? '' : '<button class="btn btn-primary btn-sm" id="d_addmissing">Add the missing nights</button>'}</td></tr>` : '';
        const DD = it.departure_day;
        // a line above sits on the departure day's own number: its visits are shown there once, not twice
        const depAt = DD && (it.departure_rows || []).some(d => Number(d.day_no) === Number(DD.day_no));
        const depDayRow = DD ? `<tr class="depday" data-depday="${DD.day_no}">
                <td class="mono">${DD.day_no}<div class="help">${fmtDate(DD.date)}</div></td>
                <td colspan="5"><b>Departure day</b> · transfer from ${esc(DD.from || 'the last night\'s hotel')} to the airport${DD.flight ? ' · ' + esc(DD.flight) : ''}
                    <div class="help">Made from the dates, never a hotel night. A late check-out, a lunch or a visit on the way goes under Visits on this line.</div></td>
                <td>${depAt ? '<span class="help">On the line above until it is made the departure day.</span>' : servicesCell({ day_no: DD.day_no, services: DD.services || [], excursions: DD.excursions || [] })}</td>
                <td>${it.locked ? (DD.note ? esc(DD.note) : '') : `<div class="notecell ${DD.note ? '' : 'empty-note'}" data-depnote title="A note for the desks about the departure day">${DD.note ? esc(DD.note) : 'Add a note'}</div>`}</td>
                <td></td></tr>` : '';

        // The itinerary as the client quotation will say it: a paragraph a day, built from the
        // nights, visits, excursions and flights in the order above (Admin → Settings holds the
        // sentences); a night can carry a person's own wording instead.
        const itineraryCard = (() => {
            const I = it.itinerary; if (!I || (!(it.days || []).length)) return '';
            const byDay = I.days || {};
            const lines = (it.days || []).map(d => {
                const w = byDay[d.day_no] || byDay[String(d.day_no)];
                if (!w) return '';
                return `<div class="itinline ${it.locked ? '' : 'edit'}" ${it.locked ? '' : `data-day="${d.id}"`} title="${it.locked ? '' : 'Edit this night to write your own wording'}">
                    <span class="mono dayno">Day ${d.day_no}</span><span class="txt">${esc(w.text)}</span>${w.source === 'own' ? '<span class="pill pill-blue">own wording</span>' : ''}</div>`;
            }).join('');
            const dep = I.departure && !(it.days || []).some(d => Number(d.day_no) === Number(I.departure.day_no)) ? `<div class="itinline ${it.locked || !(it.departure_day && it.departure_day.editable) ? '' : 'edit depwords'}" ${it.locked || !(it.departure_day && it.departure_day.editable) ? '' : 'data-depwording'} title="${it.locked ? '' : 'Click to write your own wording for the departure day'}"><span class="mono dayno">Day ${I.departure.day_no}</span><span class="txt">${esc(I.departure.text)}</span>${I.departure.source === 'own' ? '<span class="pill pill-blue">own wording</span>' : ''}</div>` : '';
            return `<div class="tf-card" id="itin_card"><div class="tf-card-h"><h6>How the quotation reads</h6>
                <span class="help">Written from the nights, visits, excursions and flights above, in the order they stand — the arrival, each touring day, the departure. Click a night to put your own words on it.${CAN_MANAGE ? ` The sentences themselves are set under <a href="${MANAGE_BASE}?tab=settings">Admin → Settings</a>.` : ''}</span></div>
                <div class="tf-card-b">${lines}${dep}${I.error ? `<div class="help" style="color:var(--tf-red)">${esc(I.error)}</div>` : ''}</div></div>`;
        })();

        // The pictures the client quotation shows for each day — the library's own pick from the
        // day's programme, or the one a person chose. Only a presentation layer: nothing costed.
        const photosCard = (() => {
            if (!it.photos) return '';
            const P = it.photos, byDay = P.days || {}, tagsBy = P.tags || {};
            const anyLib = P.cover !== undefined;
            const tiles = (it.days || []).map(d => {
                const p = byDay[d.day_no] || byDay[String(d.day_no)];
                const t = (tagsBy[d.day_no] || tagsBy[String(d.day_no)] || [])[0];
                const who = p ? (p.chosen_by === 'human' ? `<span class="pill pill-blue" title="${esc('Chosen by ' + (p.by || 'staff'))}">chosen</span>` : '<span class="pill pill-grey">auto</span>') : '';
                const pic = p && p.photo_id ? `<img class="pic" src="${esc(p.thumb_url || p.url)}" alt="">`
                    : `<div class="nopic">${p && p.chosen_by === 'human' ? 'No picture' : (p && p.note ? esc((p.tag_name || '') + ' pictures ' + p.note) : (t ? 'No photo for ' + esc(t.name) + ' yet' : 'No picture yet'))}</div>`;
                return `<div class="phtile" ${it.locked ? 'style="cursor:default"' : `data-photoday="${d.day_no}"`} title="${esc('Day ' + d.day_no + (d.area ? ' · ' + d.area : '') + (it.locked ? '' : ' — click to change'))}">${pic}
                    <div class="cap"><b>Day ${d.day_no}${d.area ? ' · ' + esc(d.area) : ''}</b>${p && p.photo_id ? esc(p.tag_name || p.caption || '') + ' ' : (t ? esc(t.name) + ' ' : '')}${who}</div></div>`;
            }).join('');
            return `<div class="tf-card" id="photo_card"><div class="tf-card-h"><h6>Pictures on the quotation</h6>
                <span class="help">One for each day, from Master Files → Tour Photos: chosen from the day's programme, preferred pictures first, none repeated.${it.locked ? ' Read-only with the rest of the inquiry.' : ' Click a day to pick another, or none.'}${SEES_PHOTOS ? ` · <a href="${PHOTOS_URL}" target="_blank">open the library</a>` : ''}</span></div>
                <div class="tf-card-b">${tiles ? `<div class="photostrip">${tiles}</div>` : '<div class="help">The pictures follow the itinerary: add the nights first.</div>'}${P.error ? `<div class="help" style="color:var(--tf-red)">${esc(P.error)}</div>` : ''}</div></div>`;
        })();

        const hasMail = !!(it.source_text || '').trim();

        // One banner, saying plainly where this inquiry stands and what to do next. A
        // failed read is never a dead end: the work carries on by hand either way.
        const bn = document.getElementById('d_banner');
        if (it.archived) {
            bn.innerHTML = `<div class="bnr bnr-amber"><i class="bi bi-archive" style="font-size:15px"></i>
                <div><b>Archived on ${esc(String(it.archived_at || '').slice(0, 16))} by ${esc(staffName(it.archived_by))}.</b>${it.archive_note ? esc(it.archive_note) + ' ' : ''}Read-only and out of every list and count; nothing was deleted. ${CAN_MANAGE ? 'Restore it from the ⋯ Manage menu below.' : 'Management can restore it.'}</div></div>`;
        } else if (it.extract_error) {
            bn.innerHTML = `<div class="bnr bnr-amber"><i class="bi bi-exclamation-triangle" style="font-size:15px"></i>
                <div><b>The email could not be read automatically.</b>${esc(it.extract_error)}
                <div class="help" style="color:inherit;opacity:.85">The email itself is saved and shown beside you. Nothing was guessed — fill the fields in by hand, or try reading it again.</div></div>
                <div class="acts"><button class="btn btn-light btn-sm" id="b_manual">Continue by hand</button>
                <button class="btn btn-light btn-sm" id="b_retry"><i class="bi bi-arrow-repeat"></i> Try again</button></div></div>`;
        } else if (it.locked) {
            const p = it.progress || {};
            bn.innerHTML = `<div class="bnr bnr-green"><i class="bi bi-lock" style="font-size:15px"></i>
                <div><b>${it.status === 'cancelled' ? 'Cancelled.' : `Confirmed on ${esc((it.confirmed_at || '').slice(0, 16))} by ${esc(it.confirmed_by || '')}.`}</b>This inquiry is read-only: it is the record of what was sold. ${it.status === 'cancelled' ? 'Management can reopen the tour from the Tour Board.' : 'Its hotel stays are with the Hotel Desk and its vehicle with the Transport Desk; the strip below shows where they stand. Management amends or cancels it from the Tour Board.'}</div></div>`;
        } else if (it.confirmed_at && !it.locked) {
            bn.innerHTML = `<div class="bnr bnr-amber"><i class="bi bi-pencil-square" style="font-size:15px"></i>
                <div><b>Open for amendment.</b>Correct what changed and press Confirm again. Cards on the desks that have not moved are rebuilt; cards already in progress are kept and marked for their desk to check.</div></div>`;
        } else if (!hasMail) {
            bn.innerHTML = `<div class="bnr bnr-blue"><i class="bi bi-pencil" style="font-size:15px"></i>
                <div><b>Entered by hand.</b>Fill in whatever you have — every field is optional and nothing is filled in for you.
                <div class="help" style="color:inherit;opacity:.85">If the agent's email arrives later you can paste it on the right, and it joins this same inquiry.</div></div></div>`;
        } else {
            bn.innerHTML = '';
        }

        const reqs = (it.requests || []).map(r => `<div class="reqrow ${r.kind === 'question' ? 'q' : ''}" data-req="${r.id}" style="cursor:pointer">
            <span class="dot"></span>
            <div><b style="font-size:12.5px">${esc(r.title)}</b>${r.quote_text ? `<q>“${esc(r.quote_text)}”</q>` : ''}${r.answer ? `<q style="color:var(--tf-navy)">${esc(r.answer)}</q>` : ''}</div>
            <span class="pill ${r.status === 'open' ? (r.kind === 'question' ? 'pill-amber' : 'pill-grey') : 'pill-green'}">${esc(r.status)}</span>
            <button class="btn btn-light btn-sm rowdel" data-delreq="${r.id}" title="Remove">&times;</button></div>`).join('')
            || `<div class="empty">${hasMail ? 'Nothing was asked for in this email.' : 'Nothing recorded yet.'}</div>`;

        const pax = (it.passengers || []).map(p => `<div class="paxcard"><div class="top"><span data-editpax="${p.id}" style="cursor:pointer"><b>${esc(p.full_name)}</b><span class="help"> ${esc(p.room_type || '')}${p.note ? ' · ' + esc(p.note) : ''}</span></span>
            <button class="btn btn-light btn-sm rowdel" data-delpax="${p.id}" title="Remove">&times;</button></div>
            ${(it.groups || []).length ? `<select class="form-select form-select-sm" data-paxgroup="${p.id}" title="Which group"><option value="">no group</option>${(it.groups || []).map(g => `<option value="${g.id}" ${Number(p.group_id) === Number(g.id) ? 'selected' : ''}>${esc(g.code)} · ${esc(g.name)}</option>`).join('')}</select>` : ''}</div>`).join('')
            || `<div class="empty">${hasMail ? 'No names given.' : 'No names entered yet.'}</div>`;

        // Vehicles. One line per vehicle, each with the group it carries and the days it is
        // engaged for, its legs its own; the reasoning shown rather than hidden, and the rate
        // basis beside it so the costing has something real to work from.
        const vs = it.vehicles || [];
        const groupName = gid => { const g = (it.groups || []).find(x => Number(x.id) === Number(gid)); return g ? g.name : null; };
        const rateCell = v => {
            const cur = esc(v.currency || 'LKR');
            const fmt = x => cur + ' ' + Number(x).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (v.rate_by === 'manual') {
                return `<b>${fmt(v.km_rate)}</b> <span class="pill pill-amber">special rate</span>
                    <div class="help">Master: <b>${v.master_rate != null ? fmt(v.master_rate) : '—'}</b>${v.rate_set_by ? ' · set by ' + esc(v.rate_set_by) : ''}${v.rate_note ? ' · “' + esc(v.rate_note) + '”' : ''}</div>`;
            }
            if (v.rate_by === 'master' && v.km_rate != null) {
                const moved = v.master_now_id != null && Number(v.master_now_id) !== Number(v.rate_id);
                return `<b>${fmt(v.km_rate)}</b> <span class="pill pill-green">master rate</span>
                    ${moved ? `<div class="help">The master now says ${fmt(v.master_now)}; this inquiry keeps ${fmt(v.km_rate)} unless you re-price.</div>` : ''}`;
            }
            return `<span class="pill pill-red">no rate</span><div class="help">Set one under Master Files → Transport Rates.</div>`;
        };
        const byVeh = (it.mileage && it.mileage.by_vehicle) || [];
        const vehicleBlock = vs.length ? `
            <div class="crewrow head vrow"><span>Vehicle</span><span>Carries</span><span>Nights</span><span>Rate per kilometre</span><span>Driving</span><span></span></div>
            ${vs.map(v => {
                const bv = byVeh.find(b => Number(b.vehicle_seq) === Number(v.sort_order)) || {};
                return `<div class="crewrow vrow">
                <span><b>${esc(v.label || ('Vehicle ' + (Number(v.sort_order) + 1)))}</b><div class="help">${esc(v.type)}${Number(v.qty) > 1 ? ' × ' + v.qty : ''}${v.seats ? ' · ' + v.seats + ' seats' : ''} <span class="pill ${v.chosen === 'auto' ? 'pill-grey' : 'pill-blue'}">${v.chosen === 'auto' ? 'automatic' : 'yours'}</span></div></span>
                <span>${v.group_id ? `<span class="pill pill-blue">${esc(groupName(v.group_id) || 'group')}</span>` : '<span class="help">the whole party</span>'}</span>
                <span>${(() => { const g = v.group_id ? (it.groups || []).find(x => Number(x.id) === Number(v.group_id)) : null; const f = v.day_from || (g && g.day_from) || null, t = v.day_to || (g && g.day_to) || null; return f || t ? `${f || 1} – ${t || (it.nights || '')}${!v.day_from && !v.day_to ? ' <span class="help">(the group\'s)</span>' : ''}` : '<span class="help">whole tour</span>'; })()}</span>
                <span>${rateCell(v)}</span>
                <span>${bv.legs ? `<b>${Number(bv.charged_km).toLocaleString()} km</b>${Number(bv.waived_km) > 0 ? `<div class="help">${Number(bv.waived_km).toLocaleString()} km waived</div>` : ''}${bv.amount != null ? `<div class="help">${esc(bv.currency)} ${Number(bv.amount).toLocaleString()}</div>` : (bv.km_rate == null ? '<div class="help" style="color:var(--tf-red)">no rate</div>' : '')}` : '<span class="help">no legs yet</span>'}</span>
                <span style="white-space:nowrap"><button class="btn btn-light btn-sm" data-vline="${v.id}" title="Label, size, group and nights">Edit</button>
                    ${SEES_PRICING ? `<button class="btn btn-light btn-sm" data-vrate="${v.id}" title="A special rate per kilometre for this job (Management)">${v.rate_by === 'manual' ? 'Rate…' : 'Special'}</button>` : ''}
                    ${SEES_PRICING && v.rate_by === 'manual' ? `<button class="btn btn-light btn-sm" data-vmaster="${v.id}" title="Back to the master rate">Master</button>` : ''}
                    ${vs.length > 1 ? `<button class="btn btn-light btn-sm rowdel" data-delvline="${v.id}" title="Remove this vehicle line">&times;</button>` : ''}</span></div>`;
            }).join('')}
            <div class="help mt-2">${esc(vs[0].why || '')}</div>
            <div class="help">A second vehicle carries a group of its own, or joins for some nights only: press <b>Add a vehicle</b>. Each vehicle drives its own legs below, and the party's groups decide who is in which. The driver's bata is not on this line — it is the crew bata master's, shown under Driver allocation.</div>`
            : `<div class="empty">${(it.pax_total ? 'No vehicle held seats ' + it.pax_total + ' passenger(s). Add one by hand, or add a larger vehicle under the vehicle master.' : 'Enter the number of passengers and a vehicle is chosen automatically.')}</div>`;

        const vehPick = `<div class="d-flex gap-2 mt-2 flex-wrap">
                <button class="btn btn-light btn-sm" id="d_vehadd"><i class="bi bi-plus-lg"></i> Add a vehicle</button>
            </div>`;

        // Driver allocation and guide allocation: two sections, one per kind. Each line is a
        // type and a language from the crew master, the days, and whoever is engaged. The
        // bata per day is the master's for that type and language and is only shown here.
        const cr = it.crew || [];
        const roleOf = code => ((CREW && CREW.roles) || []).find(r => r.code === code) || null;
        const crewLines = kind => {
            const lines = cr.filter(c => c.kind === kind);
            if (!lines.length) {
                return `<div class="empty">No ${kind} on this inquiry yet. Press <b>Add a ${kind}</b> and pick the type and the language.</div>`;
            }
            return `<div class="crewrow head crow"><span>Type · language</span><span>Days</span><span>Bata / day</span><span>Amount</span><span>Person engaged</span><span></span></div>
            ${lines.map(c => {
                const role = roleOf(c.role_code);
                const own = c.person_bata != null && c.bata != null && Math.abs(Number(c.person_bata) - Number(c.bata)) > 0.005;
                return `<div class="crewrow crow" data-crewline="${esc(c.role_code)}">
                <span><b>${esc(role ? role.category : c.role_name)}</b> · ${esc(c.language || (role && role.language) || 'any language')}
                    <div class="help">${esc(c.role_name)} <span class="pill ${c.chosen === 'auto' ? 'pill-grey' : 'pill-blue'}">${c.chosen === 'auto' ? 'automatic' : 'yours'}</span></div></span>
                <span title="${esc(c.days_basis || '')}">${c.days ?? '—'}${c.days_by ? `<div class="help">${c.days_by === 'human' ? 'set by hand' : 'from the programme'}</div>` : ''}</span>
                <span>${c.bata != null ? esc(c.currency) + ' ' + Number(c.bata).toLocaleString() : '<span class="pill pill-red">no bata set</span>'}
                    <div class="help">${c.bata != null ? 'from the master' : 'Admin sets it under Crew Bata'}</div></span>
                <span>${c.amount != null ? '<b>' + esc(c.currency) + ' ' + Number(c.amount).toLocaleString() + '</b>' : '—'}${c.amount != null && c.days != null ? `<div class="help">${c.days} × ${Number(c.bata).toLocaleString()}</div>` : ''}</span>
                <span>${c.person_name ? `<b>${esc(c.person_name)}</b><div class="help">${esc(c.supplier_acc)}${own ? ' · own bata ' + Number(c.person_bata).toLocaleString() + ' (paid, not quoted)' : ''}</div>` : '<span class="help">nobody yet</span>'}</span>
                <span style="white-space:nowrap"><button class="btn btn-light btn-sm" data-crewchange="${esc(c.role_code)}" title="Type, language, days, person">Change</button>
                    ${c.person_name ? `<button class="btn btn-light btn-sm" data-crewrelease="${esc(c.role_code)}" title="Take ${esc(c.person_name)} off this line">Release</button>` : ''}
                    <button class="btn btn-light btn-sm rowdel" data-delcrew="${esc(c.role_code)}" title="Remove">&times;</button></span>
            </div>`; }).join('')}
            <div class="help mt-2">${esc(lines[0].why || '')}</div>`;
        };
        const crewCard = kind => `
            <div class="tf-card"><div class="tf-card-h"><h6>${kind === 'driver' ? 'Driver' : 'Guide'} allocation</h6>
                <span class="help">${kind === 'driver'
                    ? 'Type and language from the crew master; the bata per day is the master\'s for that type, times the days — shown here, maintained under Master Files → Crew Bata'
                    : 'A separate guide who does not drive: type and language from the crew master, bata per day from the master, times the days'}${SEES_CREW ? ` · <a href="${CREW_URL}" target="_blank">open the master</a>` : ''}</span>
                ${kind === 'driver' ? '<button class="btn btn-light btn-sm ms-auto" id="d_autocrew" title="Driver and guide from the agent\'s market again (both sections)"><i class="bi bi-magic"></i> Auto</button>' : '<span class="ms-auto"></span>'}
                <button class="btn btn-light btn-sm" data-crewadd="${kind}"><i class="bi bi-plus-lg"></i> Add a ${kind}</button></div>
                <div class="tf-card-b">${crewLines(kind)}
                    <div class="mt-3">${accomBlock(kind)}</div>
                </div></div>`;

        // Driver and guide accommodation. Two separate questions with separate answers, and
        // both start at "not required" — a hotel that gives a free guide room and one that
        // charges eighty a night look identical until somebody checks.
        function accomBlock(kind) {
            const a = (it.accom || {})[kind] || { required: 0 };
            const on = Number(a.required) === 1;
            const money = on && a.amount != null
                ? `<b>${esc(a.currency || 'USD')} ${Number(a.amount).toLocaleString()}</b>`
                : (on ? '<span class="pill pill-amber">not priced</span>' : '<span class="pill pill-grey">nothing</span>');
            return `<div class="accom ${on ? 'on' : ''}">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="ac_${kind}" data-accom="${kind}" ${on ? 'checked' : ''}>
                        <label class="form-check-label" for="ac_${kind}"><b>${kind === 'driver' ? 'Driver' : 'Guide'} room required</b></label>
                    </div>
                    <span class="ms-auto">${money}</span>
                </div>
                ${on ? `
                <div class="row g-2 mt-2 align-items-end">
                    <div class="col-md-3"><label class="form-label help">Nights ${a.nights_by === 'override' ? '(set by hand)' : '(counted)'}</label>
                        <input type="number" min="0" class="form-control form-control-sm" data-accomnights="${kind}" value="${a.nights ?? ''}" placeholder="counted"></div>
                    <div class="col-md-3"><label class="form-label help">Per night ${a.rate_by === 'manual' ? '(by hand)' : (a.rate_by === 'hotel_policy' ? '(contract)' : (a.rate_by === 'company_rate' ? '(company)' : ''))}</label>
                        <input type="number" min="0" step="0.01" class="form-control form-control-sm" data-accomrate="${kind}" value="${a.rate ?? ''}" placeholder="not found"></div>
                    <div class="col-md-4"><label class="form-label help">Note</label>
                        <input class="form-control form-control-sm" data-accomnote="${kind}" value="${esc(a.note || '')}"></div>
                    <div class="col-md-2"><button class="btn btn-light btn-sm w-100" data-accomsave="${kind}">Save</button></div>
                </div>
                <div class="help mt-2">${esc(a.why || '')}</div>
                ${Number(a.complimentary_nights) > 0 ? `<div class="help"><span class="pill pill-green">${a.complimentary_nights} complimentary</span> by contract, not by assumption.</div>` : ''}
                ${Number(a.unknown_nights) > 0 ? `<div class="help"><span class="pill pill-red">${a.unknown_nights} unknown</span> — nothing on file, so these are not treated as free.</div>` : ''}
                ${(a.lines || []).length ? `<details class="mt-2"><summary class="help" style="cursor:pointer">Night by night</summary>
                    <div class="mt-1">${(a.lines || []).map(l => `<div class="crewrow">
                        <span>Night ${l.day_no}${l.date ? ' · ' + fmtDate(l.date) : ''}</span>
                        <span>${esc(l.hotel || '—')}</span>
                        <span>${esc(l.meal || '—')}</span>
                        <span><span class="pill ${l.state === 'charged' ? 'pill-amber' : (l.state === 'complimentary' ? 'pill-green' : 'pill-red')}">${esc(l.state.replace('_',' '))}</span></span>
                        <span>${l.rate != null && l.rate > 0 ? esc(l.currency || 'USD') + ' ' + Number(l.rate).toLocaleString() : '—'}
                            <div class="help">${esc(l.why || '')}</div></span>
                    </div>`).join('')}</div></details>` : ''}
                ` : '<div class="help mt-1">Off by default. Many hotels give a complimentary room, many do not — nothing is costed and nothing is assumed free until this is switched on.</div>'}
            </div>`;
        }

        // The driving, leg by leg and vehicle by vehicle. Every distance says where it came
        // from and how many past journeys stand behind it, because "89.7 km" and "89.7 km,
        // from 417 journeys" are not the same claim and a quotation should not pretend they are.
        const KMBY = {
            google: ['pill-green', 'Google'], history: ['pill-green', 'past tours'], town_history: ['pill-green', 'past tours'], actual: ['pill-green', 'actual, typed'],
            city_allowance: ['pill-blue', 'allowance'], manual: ['pill-blue', 'by hand'], same_place: ['pill-blue', 'airport area'], none: ['pill-red', 'not known']
        };
        // Two figures on every driven leg — Google's road distance and the company's own
        // driven record — shown side by side, and the quoted one marked. The quotation
        // prefers Google's (it exists for a sector nobody has driven yet, and it tends to
        // sit above the record, which is the buffer wanted in the price); the driven
        // figure is kept in view so the true kilometres are never lost.
        const isDriven = s => !['garage', 'idle'].includes(s.kind) && !(s.kind === 'local' && String(s.from_place || '').trim().toLowerCase() === String(s.to_place || '').trim().toLowerCase());
        const kmCell = (v, quoted, title) => v != null
            ? `<span class="mono" title="${esc(title || '')}" style="${quoted ? 'font-weight:700' : ''}">${Number(v).toLocaleString()}</span>${quoted ? ' <span class="pill pill-green" style="font-size:9.5px;padding:0 5px">quoted</span>' : ''}`
            : '';
        const KIND = { positioning: 'to collect', arrival: 'arrival', transfer: 'transfer', local: 'city tour', idle: 'stay day', garage: 'garage run', departure: 'departure' };
        // A long place is shortened, but never to the town alone: an airport hotel in Katunayake
        // would then read "Katunayake → Katunayake" and you could not tell which leg you were
        // about to delete. The venue leads, the town follows, and the full name is in the tooltip.
        function place(p) {
            const s = String(p || '').trim();
            if (!s) return '—';
            if (s.length <= 34) return esc(s);
            const parts = s.split(',').map(x => x.trim()).filter(Boolean);
            const town = parts[parts.length - 1] || '';
            const venue = parts[0] && parts[0] !== town ? (parts[0].length > 24 ? parts[0].slice(0, 24) + '…' : parts[0]) : '';
            const shown = venue ? venue + ' · ' + town : (town || s.slice(0, 34));
            return `<span title="${esc(s)}" style="border-bottom:1px dotted #9aa5b1">${esc(shown)}</span>`;
        }
        const mil = it.mileage || { legs: 0, by_vehicle: [] };
        const multi = vs.length > 1;
        const vehLabel = seq => { const v = vs.find(x => Number(x.sort_order) === Number(seq)); return v ? (v.label || ('Vehicle ' + (Number(seq) + 1))) : ('Vehicle ' + (Number(seq) + 1)); };
        // released days: a vehicle sent home for a stay day has no leg at all, so the row is drawn from the decision itself
        const released = (it.vehicle_days || []).filter(x => Number(x.retained) === 0);
        const rows = [];
        (it.sectors || []).forEach(s => rows.push({ kind: 'leg', seq: Number(s.vehicle_seq || 0), day: Number(s.day_no), s }));
        released.forEach(r => rows.push({ kind: 'released', seq: Number(r.vehicle_seq), day: Number(r.day_no), r }));
        rows.sort((a, b) => (a.seq - b.seq) || (a.day - b.day) || ((a.s ? Number(a.s.seq) : -1) - (b.s ? Number(b.s.seq) : -1)));
        let lastSeq = null;
        const legs = rows.map(row => {
            let head = '';
            if (multi && row.seq !== lastSeq) {
                lastSeq = row.seq;
                head = `<tr class="vhead"><td colspan="8"><i class="bi bi-truck"></i> ${esc(vehLabel(row.seq))}</td></tr>`;
            }
            if (row.kind === 'released') {
                return head + `<tr class="released">
                    <td class="mono">${row.day}</td>
                    <td><span class="pill pill-grey">released</span></td>
                    <td colspan="3"><span class="help">The vehicle is not kept on this day — nothing is charged for it.${row.r.note ? ' “' + esc(row.r.note) + '”' : ''}</span></td>
                    <td colspan="2"><span class="pill pill-grey">not charged</span></td>
                    <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-keep="${row.seq}:${row.day}" title="Keep the vehicle on for this day; the stay-day figure is charged">Keep on</button></td></tr>`;
            }
            const s = row.s;
            const [kc, kl] = KMBY[s.km_by] || KMBY.none;
            const driven = isDriven(s);
            const kindLabel = s.kind === 'local' && String(s.from_place || '').trim().toLowerCase() !== String(s.to_place || '').trim().toLowerCase() ? 'day out' : (KIND[s.kind] || esc(s.kind));
            // a leg somebody took off: struck through, costed at nothing, with Restore
            if (s.removed_at) {
                row.cells = { kindLabel, removed: true };   // tf-mileage-track-r1: the Railway view's window for this leg
                return head + `<tr class="waived" style="opacity:.6">
                    <td class="mono">${s.day_no}</td>
                    <td><span class="pill pill-grey">${kindLabel}</span></td>
                    <td colspan="3"><s>${place(s.from_place)} → ${place(s.to_place)}</s> <span class="pill pill-grey">taken off</span>
                        <div class="help">Taken off by ${esc(s.removed_by || 'a colleague')} on ${esc(String(s.removed_at).slice(0, 10))}. It costs nothing and is not put back when the itinerary is re-planned.</div></td>
                    <td class="mono">—</td>
                    <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-restoreleg="${s.id}" title="Put this leg back on the mileage">Restore</button></td></tr>`;
            }
            const waived = s.waived_by ? `<div><span class="pill pill-green" title="An excursion that day includes its own transport, so this is shown and not charged">waived — ${esc(s.waived_by)}</span></div>` : '';
            const release = s.kind === 'idle' && s.origin === 'auto'
                ? `<button class="btn btn-light btn-sm" data-release="${row.seq}:${row.day}" title="Send the vehicle away for this day: the stay-day figure is not charged">Release</button>` : '';
            // Google's column: the figure, or why there is none — and a way to ask again
            let gcell = '';
            if (driven) {
                if (s.google_km != null) {
                    gcell = kmCell(s.google_km, s.km_by === 'google', s.google_why);
                } else {
                    const why = s.google_by === 'none' ? 'Google has not been asked about this leg yet.' : (s.google_why || '');
                    gcell = `<span class="pill ${s.google_by === 'no_key' ? 'pill-grey' : 'pill-amber'}" title="${esc(why)}">${GBY[s.google_by] || 'none'}</span>`
                        + (mil.google_key && s.google_by !== 'no_key' ? `<div><a href="#" class="help" data-gleg="${s.id}" title="${esc(why)}">ask Google</a></div>` : '');
                }
            } else {
                gcell = '<span class="help">—</span>';
            }
            // the actual column: the driven record with its journeys, or a figure typed by hand —
            // always with a box to type one, beside Google's figure and never over it
            let acell = '';
            if (driven) {
                const quotedHere = ['history', 'town_history', 'actual'].includes(s.km_by);
                let src = '';
                if (s.actual_by === 'manual') {
                    src = `typed${s.actual_why && s.actual_why.indexOf(' by ') > 0 ? ' by ' + esc(s.actual_why.split(' by ')[1].split(' on ')[0]) : ''}`;
                } else if (s.actual_km != null) {
                    src = `${s.samples} journey${Number(s.samples) === 1 ? '' : 's'}${Number(s.samples_post) > 0 ? ` · <span title="Entered on the Transport desk after those tours ran">${s.samples_post} after tours</span>` : ''}${Number(s.samples) > 0 && Number(s.samples) < 3 ? ' <span class="pill pill-amber">thin</span>' : ''}`;
                }
                const fig = s.actual_km != null
                    ? kmCell(s.actual_km, quotedHere, s.actual_why)
                    : `<span class="pill pill-grey" title="${esc(s.actual_why || 'The company has no record of driving this leg.')}">none on file</span>`;
                acell = `${fig}${src ? `<div class="help" style="font-size:10px">${src}</div>` : ''}`
                    + (CAN_MILEAGE
                        ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm mono" style="width:78px;margin-top:3px" data-actual="${s.id}" value="${s.actual_by === 'manual' && s.actual_km != null ? Number(s.actual_km) : ''}" placeholder="type km" title="Type the actual kilometres for this leg. Google's figure stays as it is; blank puts the record's figure back.">`
                        : '');
            } else {
                // not a journey between two places: the allowance IS the leg's figure, so it
                // sits here and can be typed. Blank puts the Mileage Allowances figure back.
                const asrc = s.km_by === 'manual' ? 'typed by hand' : (s.km_by === 'city_allowance' ? 'Mileage Allowances' : '');
                acell = `${s.km != null ? `<span class="mono" style="${s.km_by === 'manual' ? 'font-weight:700' : ''}">${Number(s.km).toLocaleString()}</span>` : '<span class="pill pill-red">none</span>'}`
                    + `${asrc ? `<div class="help" style="font-size:10px">${asrc}</div>` : ''}`
                    + (CAN_MILEAGE
                        ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm mono" style="width:78px;margin-top:3px" data-legkm="${s.id}" value="${s.km_by === 'manual' && s.km != null ? Number(s.km) : ''}" placeholder="type km" title="The kilometres for this allowance leg. Blank goes back to the figure in Master Files → Mileage Allowances.">`
                        : '');
            }
            // the extra running for site visits and the city tour: its own figure, where it came
            // from, and a box to set it. The driven figures are never touched by it.
            let extraCell;
            if (['garage', 'idle'].includes(s.kind) || (s.kind === 'local' && !driven)) {
                extraCell = `<span class="mono">${s.extra_km != null ? Number(s.extra_km).toLocaleString() : '—'}</span>`;
            } else {
                const EBY = { manual: 'typed', city_allowance: 'town allowance', history: 'from past tours', town_history: 'from past tours', none: '' };
                const src = s.extra_km != null ? (EBY[s.extra_by] || '') : 'none on file';
                extraCell = `${s.extra_km != null
                        ? `<span class="mono" style="${s.extra_by === 'manual' ? 'font-weight:700' : ''}">${Number(s.extra_km).toLocaleString()}</span>`
                        : '<span class="pill pill-amber" title="No figure for this town in Master Files → Extra Kilometers, and nothing logged on past journeys">none</span>'}
                    ${src ? `<div class="help" style="font-size:10px">${src}</div>` : ''}`
                    + (CAN_MILEAGE
                        ? `<input type="number" min="0" step="0.1" class="form-control form-control-sm mono" style="width:78px;margin-top:3px" data-extra="${s.id}" value="${s.extra_by === 'manual' && s.extra_km != null ? Number(s.extra_km) : ''}" placeholder="type km" title="The extra kilometres for the site visit or city tour on this leg. Blank goes back to the town's allowance.">`
                        : '');
            }
            // a quoted figure typed over both is said in the leg's own cell; otherwise the quoted one is marked in its column
            const override = driven && s.km_by === 'manual' && s.km != null
                ? `<div><span class="pill pill-blue" style="font-size:10px">quoted at ${Number(s.km).toLocaleString()} km, typed by hand</span></div>`
                : (s.km_by === 'same_place'
                    ? '<div><span class="pill pill-blue" style="font-size:10px" title="The airport area: the airport and garage running the plan already carries covers this leg, so 0 km is quoted and nothing more is needed. Only Katunayake is treated so; a hotel in any other town has its leg priced as usual.">airport area — 0 km, not a blocker</span></div>'
                    : (s.km == null ? '<div><span class="pill pill-red" style="font-size:10px">no distance — costs nothing</span></div>' : ''));
            row.cells = { gcell, acell, extraCell, override, release, kindLabel, waived, driven };   // tf-mileage-track-r1: the same cells, for the Railway view's window
            return head + `<tr class="${s.waived_by ? 'waived' : ''}"${driven && s.km == null ? ' data-unpriced="1"' : ''}>
                <td class="mono">${s.day_no}</td>
                <td><span class="pill pill-grey">${kindLabel}</span></td>
                <td>${place(s.from_place)} <span class="help">→</span> <b>${place(s.to_place)}</b>${override}
                    ${s.why ? `<div class="help">${esc(s.why)}</div>` : ''}
                    ${s.note ? `<div class="help">${esc(s.note)}</div>` : ''}${waived}</td>
                <td>${gcell}</td>
                <td>${acell}</td>
                <td>${extraCell}</td>
                <td style="white-space:nowrap">${release}<button class="btn btn-light btn-sm" data-leg="${s.id}" title="Correct the from, the to, the kilometres">Edit</button>
                    <button class="btn btn-light btn-sm rowdel" data-delleg="${s.id}" title="Take this leg off the mileage; the totals and the costing follow">Delete</button></td></tr>`;
        }).join('');

        const milTot = mil.legs > 0 ? `
            ${multi ? `<div class="crewrow head"><span>Vehicle</span><span>Charged km</span><span>Waived km</span><span>Per km</span><span>Trip total</span></div>
            ${(mil.by_vehicle || []).map(b => `<div class="crewrow">
                <span><b>${esc(b.label || ('Vehicle ' + (Number(b.vehicle_seq) + 1)))}</b> <span class="help">${esc(b.type || '')}${Number(b.qty) > 1 ? ' × ' + b.qty : ''}</span></span>
                <span><b>${Number(b.charged_km).toLocaleString()} km</b>${Number(b.unpriced) > 0 ? ` <span class="pill pill-red">${b.unpriced} unpriced</span>` : ''}</span>
                <span>${Number(b.waived_km) > 0 ? Number(b.waived_km).toLocaleString() + ' km' : '—'}</span>
                <span>${b.km_rate != null ? esc(b.currency) + ' ' + Number(b.km_rate).toLocaleString() : '<span class="pill pill-red">no rate</span>'}</span>
                <span>${b.amount != null ? '<b>' + esc(b.currency) + ' ' + Number(b.amount).toLocaleString() + '</b>' : '—'}</span></div>`).join('')}<hr class="my-2">` : ''}
            <div class="crewrow head"><span>Google km</span><span>Actual km</span><span>Difference</span><span>Quoted on</span><span></span></div>
            <div class="crewrow">
                <span><b>${Number(mil.google_km || 0).toLocaleString()} km</b><div class="help">${mil.google_legs || 0} leg${Number(mil.google_legs) === 1 ? '' : 's'}${Number(mil.google_missing) > 0 ? ` · <span class="pill pill-amber">${mil.google_missing} without a figure</span>` : ''}${Number(mil.google_unasked) > 0 ? ` · <span class="pill pill-amber">${mil.google_unasked} not asked yet</span>` : ''}</div></span>
                <span><b>${Number(mil.actual_km || 0).toLocaleString()} km</b><div class="help">${mil.actual_legs || 0} leg${Number(mil.actual_legs) === 1 ? '' : 's'} with a figure${Number(mil.actual_typed) > 0 ? `, ${mil.actual_typed} typed` : ''}${Number(mil.actual_learned) > 0 ? `, ${mil.actual_learned} learned from tours that ran` : ''}</div></span>
                <span>${mil.buffer_km != null ? `<b>${Number(mil.buffer_km) > 0 ? '+' : ''}${Number(mil.buffer_km).toLocaleString()} km</b><div class="help">Google against actual, over the ${mil.both_legs} leg${Number(mil.both_legs) === 1 ? '' : 's'} both know</div>` : '<span class="help">—</span>'}</span>
                <span>${mil.source === 'google' ? '<span class="pill pill-green">Google</span>' : '<span class="pill pill-blue">actual</span>'}<div class="help">${mil.quoted_google || 0} at Google's, ${mil.quoted_actual || 0} at the actual${Number(mil.quoted_manual) > 0 ? `, ${mil.quoted_manual} typed over` : ''}${Number(mil.removed) > 0 ? ` · ${mil.removed} taken off` : ''}</div></span>
                <span></span>
            </div>
            <div class="crewrow head"><span>Quoted km</span><span>Extra — site visits &amp; city tours</span><span>Charged</span><span>Waived</span><span>Trip total</span></div>
            <div class="crewrow">
                <span><b>${Number(mil.km).toLocaleString()} km</b></span>
                <span>${Number(mil.extra_km).toLocaleString()} km<div class="help">${Number(mil.extra_typed) > 0 ? mil.extra_typed + ' typed' : 'from the town allowances'}${Number(mil.extra_none) > 0 ? ` · <span class="pill pill-amber">${mil.extra_none} leg(s) with none</span>` : ''}</div></span>
                <span><b>${Number(mil.charged_km).toLocaleString()} km</b>${!multi && mil.km_rate != null ? `<div class="help">at LKR ${Number(mil.km_rate).toLocaleString()} per km</div>` : ''}</span>
                <span>${Number(mil.waived_km) > 0 ? Number(mil.waived_km).toLocaleString() + ' km' : '—'}</span>
                <span>${mil.amount != null ? '<b>LKR ' + Number(mil.amount).toLocaleString() + '</b>' : (mil.km_rate == null && vs.length ? '<span class="pill pill-red">no rate on file</span>' : '—')}</span>
            </div>
            <div class="help mt-2">Every driven leg carries two figures, always side by side: <b>Google</b> is the road distance Google gives for the two places, asked once and kept, never overwritten; <b>actual</b> is the kilometres actually driven — the company's own record from past tours (the middle journey, with the number behind it), and the actual runs entered on the Transport desk after each tour, which the record learns from first — or a figure typed in the box. The quotation is costed on ${mil.source === 'google' ? 'Google\'s figure by preference — it has an answer for a sector nobody has driven yet — and falls back to the actual where Google has none' : 'the actual figure, by the setting on Mileage Allowances, and takes Google\'s where there is none'}; a distance typed over in Edit stands over both. Delete takes a leg off for good (Restore brings it back); every edit and delete moves the totals and the costing at once. <b>Extra</b> is what the office gives the driver for the site visits and the city tour on that leg, on top of the journey itself: the destination town's figure from Master Files → Extra Kilometers, or what the drivers actually logged on that run — and you can type your own over it on the row, which is counted in the charged kilometres and paid at the same rate per km. A stay day is charged at the stay-day figure only while the vehicle is kept on; an excursion that brings its own transport waives that day's local running so nothing is charged twice. The <b>garage run</b>, the stay day and a city tour take their kilometres from Master Files → Mileage Allowances, and you can type over any of them on the row for this tour alone — blank puts the master's figure back. <b>Add a leg</b> below makes a garage run or any other kind.
            ${!mil.google_key ? `<br><span class="pill pill-grey">Google off</span> No Google Maps key is set — add <span class="mono">GOOGLE_MAPS_API_KEY</span> to the project's <span class="mono">.env</span>, then press <b>Ask Google</b> above.` : ''}
            ${Number(mil.unpriced) > 0 ? `<br><span class="pill pill-red">${mil.unpriced} leg(s) with no distance</span> These cost nothing at all until the kilometres are typed in${CAN_MILEAGE ? '' : ' — by somebody with the Mileage override privilege'}.` : ''}
            ${Number(mil.thin) > 0 ? `<br><span class="pill pill-amber">${mil.thin} thin</span> Quoted on one or two past journeys.` : ''}</div>`
            : `<div class="empty">Add the nights above and the driving is worked out from them.</div>`;

        const legTable = `${legs ? `<div style="overflow-x:auto"><table class="tbl">
                <thead><tr><th style="width:44px">Day</th><th style="width:86px">Leg</th><th>From → to</th>
                    <th style="width:96px" title="Google's road distance for the two places — asked once and kept, never overwritten">Google km</th><th style="width:118px" title="The kilometres actually driven: the company's record from past tours, or a figure typed here">Actual km</th><th style="width:112px" title="The extra kilometres the office gives the driver for site visits and a city tour on this leg — on top of the journey itself">Extra km</th><th style="width:124px"></th></tr></thead>
                <tbody>${legs}</tbody></table></div><hr class="my-3">` : ''}`;
        const addLegForm = `
            <div class="row g-2 mt-2 align-items-end">
                ${multi ? `<div class="col-md-2"><label class="form-label help">Vehicle</label>
                    <select class="form-select form-select-sm" id="d_legveh">${vs.map(v => `<option value="${v.sort_order}">${esc(v.label || ('Vehicle ' + (Number(v.sort_order) + 1)))}</option>`).join('')}</select></div>` : ''}
                <div class="col-md-1"><label class="form-label help">Day</label>
                    <input type="number" min="0" class="form-control form-control-sm" id="d_legday" placeholder="1"></div>
                <div class="col-md-${multi ? 2 : 3}"><label class="form-label help">From</label>
                    <input class="form-control form-control-sm" id="d_legfrom" placeholder="Kandy, or Garage"></div>
                <div class="col-md-2"><label class="form-label help">To</label>
                    <input class="form-control form-control-sm" id="d_legto" placeholder="Sigiriya"></div>
                <div class="col-md-2"><label class="form-label help">Leg</label>
                    <select class="form-select form-select-sm" id="d_legkind">
                        <option value="transfer">Transfer</option>
                        <option value="garage">Garage run</option>
                        <option value="local">City tour / day out</option>
                        <option value="idle">Stay day</option>
                        <option value="arrival">Arrival transfer</option>
                        <option value="departure">Departure transfer</option>
                        <option value="positioning">To collect / reposition</option>
                    </select></div>
                <div class="col-md-1"><label class="form-label help">km ${CAN_MILEAGE ? '(blank = look it up)' : '(looked up)'}</label>
                    <input type="number" min="0" step="0.1" class="form-control form-control-sm" id="d_legkm" ${CAN_MILEAGE ? '' : 'disabled title="Typing kilometres needs the Mileage override privilege"'}></div>
                <div class="col-md-2"><button class="btn btn-light btn-sm w-100" id="d_legadd">Add a leg</button></div>
            </div>`;
        const mileageBlock = `${legTable}${milTot}${addLegForm}`;

        // tf-mileage-track-r1 (10 Oct 2026): the Railway view. The same legs and the same figures as the list above, drawn as
        // the tour's journey — a station for each place (its day and date, and its picture where the system has one), only
        // the kilometres between stations on the line, and everything else (Google, actual, extra, the allowances, the garage
        // runs, the airport rule, the notes, the warnings, the boxes and buttons) in a window that opens when a station or a
        // sector is pointed at (hover) or tapped. Nothing is worked out here: every figure is the leg's own, as the list shows it.
        const MILVIEW = milView();
        const railStops = [];   // the places in order, for the Map view
        const railBlock = (() => {
            const legRows = rows.filter(r => r.kind === 'leg');
            if (!legRows.length) return `${milTot}${addLegForm}`;
            const photos = it.station_photos || {};
            // one colour for every day — the panel's own, so the line reads as part of the system (10 Oct 2026: Shantha, "colours uniform with the system")
            const dayColor = () => 'var(--tf-navy)';
            const norm = p => String(p || '').trim().toLowerCase();
            const parts = p => String(p || '').split(',').map(x => x.trim()).filter(Boolean);
            const townOf = p => { const x = parts(p); return x.length ? x[x.length - 1] : String(p || ''); };
            const isGarage = p => /\bgarage\b/i.test(String(p || ''));
            const isAirport = p => /\b(airport|katunayake|bandaranaike)\b/i.test(String(p || ''));
            const fmtKm = v => Number(v).toLocaleString(undefined, { maximumFractionDigits: 1 });
            const ServiceWords = t => String(t || '').toLowerCase().replace(/[^a-z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim();
            const cap = t => String(t || '').charAt(0).toUpperCase() + String(t || '').slice(1);
            // the date of a tour day: the itinerary's own date for it, else counted from the arrival date
            const dayDate = n => {
                const d = (it.days || []).find(x => Number(x.day_no) === Number(n) && x.day_date);
                if (d) return d.day_date;
                if (!it.arrival_date || !(Number(n) >= 1)) return null;
                const t = new Date(it.arrival_date + 'T00:00:00');
                if (isNaN(t)) return null;
                t.setDate(t.getDate() + Number(n) - 1);
                return t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
            };
            const shortDate = iso => { if (!iso) return ''; const t = new Date(iso + 'T00:00:00'); return isNaN(t) ? '' : t.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }); };
            const dayChip = (d, color) => `<span class="trk-day" style="--c:${color || dayColor(d)}">Day ${esc(d)}${dayDate(d) ? ` <small>${shortDate(dayDate(d))}</small>` : ''}</span>`;
            const dayFull = d => `Day ${esc(d)}${dayDate(d) ? ' · ' + fmtDate(dayDate(d)) : ''}`;
            const gDir = (a, b) => 'https://www.google.com/maps/dir/?api=1&travelmode=driving&origin=' + encodeURIComponent(a + ', Sri Lanka') + '&destination=' + encodeURIComponent(b + ', Sri Lanka');
            const gPlace = a => 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(a + ', Sri Lanka');
            const label = p => { const x = parts(p); const main = x.length > 1 ? x[0] : String(p || '').trim(); return { main: main.length > 24 ? main.slice(0, 23) + '…' : main, sub: x.length > 1 ? x[x.length - 1] : '' }; };
            const store = [];   // the detail windows' contents: in the page, so their boxes and buttons work; shown one at a time
            const made = {};
            // the nights the party sleeps at a station: the itinerary's nights whose area names the station's town
            // (an area can say "Sigiriya / Polonnaruwa"), counted from the day the vehicle arrives there
            const nightsAt = st => {
                const town = ServiceWords(townOf(st.name));
                if (!town || isGarage(st.name)) return null;
                const from = Number(st.day), upto = st.outLeg ? Number(st.outLeg.s.day_no) : 99999;
                const list = (it.days || []).filter(d => Number(d.day_no) >= from && Number(d.day_no) < Math.max(upto, from + 1)
                    && String(d.area || '').split('/').some(a => ServiceWords(a) === town));
                const n = list.reduce((a, d) => a + (Number(d.nights) || 1), 0);
                return n > 0 ? { n, hotel: list[0].hotel_name_raw || '' } : null;
            };

            // ---- a sector's window: every figure the list shows for the leg, with its boxes and buttons ----
            const legDetail = row => {
                const s = row.s, c = row.cells || {};
                const key = 'L' + s.id;
                if (made[key]) return key;
                made[key] = true;
                const same = norm(s.from_place) === norm(s.to_place);
                const title = same ? `${place(s.from_place)}` : `${place(s.from_place)} <span class="help">→</span> ${place(s.to_place)}`;
                const head = `<div class="trk-dh">${dayChip(s.day_no)}<b>${title}</b> <span class="pill pill-grey">${c.kindLabel || esc(s.kind)}</span>${s.removed_at ? ' <span class="pill pill-grey">taken off</span>' : ''}</div>`;
                if (c.removed) {
                    store.push(`<div class="trk-detail" data-trk-detail="${key}">${head}
                        <table class="trk-kv"><tr><th>Kilometres</th><td>${s.km != null ? `<s class="mono">${fmtKm(s.km)} km</s>` : '—'} <span class="help">not costed</span></td></tr>
                        <tr><th>Notes</th><td>Taken off by ${esc(s.removed_by || 'a colleague')} on ${esc(String(s.removed_at).slice(0, 10))}. It costs nothing and is not put back when the itinerary is re-planned.</td></tr></table>
                        <div class="trk-act"><button class="btn btn-light btn-sm" data-restoreleg="${s.id}" title="Put this leg back on the mileage">Restore</button></div></div>`);
                    return key;
                }
                const [kc, kl] = KMBY[s.km_by] || KMBY.none;
                const kv = [];
                kv.push(['Quoted', s.km != null
                    ? `<b class="mono">${fmtKm(s.km)} km</b> <span class="pill ${kc}">${kl}</span>${c.override || ''}`
                    : `<span class="pill pill-red">no distance — costs nothing</span>`]);
                if (c.driven) {
                    kv.push(['Google km', c.gcell]);
                    kv.push(['Actual km', c.acell]);
                } else {
                    kv.push([s.kind === 'garage' ? 'Garage run' : (s.kind === 'idle' ? 'Stay day' : 'Allowance'), c.acell]);
                }
                kv.push(['Extra km', c.extraCell]);
                const notes = [s.why ? esc(s.why) : '', s.note ? esc(s.note) : ''].filter(Boolean).join('<div style="height:4px"></div>');
                if (notes || c.waived) kv.push(['Notes', `${notes}${c.waived || ''}`]);
                const link = !same && !isGarage(s.from_place) && !isGarage(s.to_place)
                    ? `<a class="help ms-auto" href="${gDir(s.from_place, s.to_place)}" target="_blank" rel="noopener">View on Google Maps <i class="bi bi-box-arrow-up-right"></i></a>` : '';
                store.push(`<div class="trk-detail" data-trk-detail="${key}">${head}
                    <table class="trk-kv">${kv.map(([k, v]) => `<tr><th>${k}</th><td>${v}</td></tr>`).join('')}</table>
                    <div class="trk-act">${c.release || ''}<button class="btn btn-light btn-sm" data-leg="${s.id}" title="Correct the from, the to, the kilometres">Edit</button>
                        <button class="btn btn-light btn-sm rowdel" data-delleg="${s.id}" title="Take this leg off the mileage; the totals and the costing follow">Delete</button>${link}</div></div>`);
                return key;
            };

            // ---- the journey, vehicle by vehicle: a station for each place, a sector for each leg between two places;
            // a leg that stays in one place (a city tour, a stay day, the airport area) hangs under its station ----
            const tracks = [];
            let cur = null;
            legRows.forEach(row => {
                if (!cur || cur.seq !== row.seq) { cur = { seq: row.seq, items: [], stations: [] }; tracks.push(cur); }
                const s = row.s;
                let last = cur.stations[cur.stations.length - 1];
                if (!last || norm(last.name) !== norm(s.from_place)) {
                    if (last) cur.items.push({ t: 'gap' });
                    last = { name: s.from_place, day: s.day_no, inLeg: null, outLeg: null, loops: [] };
                    cur.stations.push(last);
                    cur.items.push({ t: 'stop', st: last });
                }
                if (norm(s.from_place) === norm(s.to_place)) { last.loops.push(row); return; }
                last.outLeg = row;
                const next = { name: s.to_place, day: s.day_no, inLeg: row, outLeg: null, loops: [] };
                cur.items.push({ t: 'seg', row });
                cur.stations.push(next);
                cur.items.push({ t: 'stop', st: next });
            });

            let stationNo = 0;
            const stationDetail = st => {
                const key = 'S' + (++stationNo);
                const ph = photos[norm(st.name)];
                const kv = [];
                if (st.inLeg) { const s = st.inLeg.s; kv.push(['Arrives', `${dayFull(s.day_no)} · from ${place(s.from_place)}${s.km != null ? ` · <b class="mono">${fmtKm(s.km)} km</b>` : ''}`]); }
                st.loops.forEach(r => { const s = r.s; kv.push([cap((r.cells || {}).kindLabel || s.kind), `${dayFull(s.day_no)} · ${s.km != null ? `<b class="mono">${fmtKm(s.km)} km</b>` : '<span class="pill pill-red">no distance</span>'}${s.removed_at ? ' <span class="pill pill-grey">taken off</span>' : ''}`]); });
                const ngd = nightsAt(st);
                if (ngd) kv.push(['Stay', `<b>${ngd.n} night${ngd.n === 1 ? '' : 's'}</b>${ngd.hotel ? ' · ' + esc(ngd.hotel) : ''}`]);
                if (st.outLeg) { const s = st.outLeg.s; kv.push(['Leaves', `${dayFull(s.day_no)} · to ${place(s.to_place)}${s.km != null ? ` · <b class="mono">${fmtKm(s.km)} km</b>` : ''}`]); }
                const pic = ph && ph.url
                    ? `<img class="trk-photo" src="${esc(ph.url)}" alt="${esc(townOf(st.name))}" loading="lazy"><div class="trk-cap">${esc(ph.caption || '')}${ph.caption ? ' · ' : ''}picture from the ${esc(ph.source || 'library')}</div>`
                    : (isGarage(st.name) ? '' : `<div class="trk-cap">No picture of ${esc(townOf(st.name))} in the system yet — one tagged with the place in Master Files → Tour Photos shows here.</div>`);
                store.push(`<div class="trk-detail" data-trk-detail="${key}">
                    <div class="trk-dh">${dayChip(st.day, isGarage(st.name) ? 'var(--tf-muted)' : null)}<b>${esc(st.name)}</b></div>${pic}
                    <table class="trk-kv">${kv.map(([k, v]) => `<tr><th>${k}</th><td>${v}</td></tr>`).join('')}</table>
                    ${isGarage(st.name) ? '' : `<div class="trk-act"><a class="help ms-auto" href="${gPlace(st.name)}" target="_blank" rel="noopener">View on Google Maps <i class="bi bi-box-arrow-up-right"></i></a></div>`}</div>`);
                return key;
            };
            const tile = p => {
                const ph = photos[norm(p)];
                if (ph && ph.url) return `<span class="trk-pic" style="background-image:url('${esc(ph.url)}')"></span>`;
                return `<span class="trk-pic trk-ic"><i class="bi ${isGarage(p) ? 'bi-house-door-fill' : (isAirport(p) ? 'bi-airplane-fill' : 'bi-geo-alt-fill')}"></i></span>`;
            };
            const stopHtml = (st, first, last) => {
                const key = stationDetail(st);
                const col = isGarage(st.name) ? 'var(--tf-muted)' : dayColor(st.day);
                const nm = label(st.name);
                const loops = st.loops.map(r => {
                    const s = r.s, k = legDetail(r);
                    const kind = (r.cells || {}).kindLabel || s.kind;
                    const km = s.km != null ? fmtKm(s.km) + ' km' : '? km';
                    const ic = s.kind === 'idle' ? 'bi-pause-circle' : (isAirport(s.to_place) ? 'bi-airplane' : 'bi-arrow-repeat');
                    return `<button type="button" class="trk-loop${s.removed_at ? ' off' : ''}${!s.removed_at && s.km == null ? ' none' : ''}" data-trk="${k}" aria-label="${esc(cap(kind) + ' ' + km)}"><i class="bi ${ic}"></i> ${kind} ${km}</button>`;
                }).join('');
                const ng = nightsAt(st);
                return `<div class="trk-stop${first ? ' first' : ''}${last ? ' last' : ''}" style="--c:${col}">
                    ${dayChip(st.day, col)}
                    <button type="button" class="trk-st" data-trk="${key}" aria-label="${esc(st.name + ', day ' + st.day + (ng ? ', ' + ng.n + ' night' + (ng.n === 1 ? '' : 's') : ''))}">${tile(st.name)}<span class="trk-dot"></span></button>
                    <span class="trk-name">${esc(nm.main)}${nm.sub ? `<small>${esc(nm.sub)}</small>` : ''}${ng ? `<small class="trk-nts" title="${esc(ng.hotel)}"><i class="bi bi-moon-stars"></i> ${ng.n} night${ng.n === 1 ? '' : 's'}</small>` : ''}</span>
                    ${loops ? `<span class="trk-loops">${loops}</span>` : ''}</div>`;
            };
            const maxKm = Math.max(1, ...legRows.filter(r => !r.s.removed_at && r.s.km != null).map(r => Number(r.s.km)));
            // the line's length follows the kilometres (a 224 km leg is drawn long, a 17 km one short), within limits so every label stays readable
            const growOf = s => s.km != null && !s.removed_at ? (0.6 + 2.4 * Math.sqrt(Number(s.km) / maxKm)).toFixed(2) : '1';
            const segHtml = row => {
                const s = row.s, k = legDetail(row);
                const cls = ['trk-seg'];
                // the driven record a fifth or more away from Google's figure: a small mark, the figures in the window
                const far = s.google_km != null && s.actual_km != null && Number(s.google_km) > 0 && Math.abs(Number(s.actual_km) - Number(s.google_km)) / Number(s.google_km) >= 0.2;
                let km;
                if (s.removed_at) { cls.push('off'); km = s.km != null ? `<s>${fmtKm(s.km)} km</s>` : 'taken off'; }
                else if (s.km == null) { cls.push('none'); km = '? km'; }
                else km = fmtKm(s.km) + ' km';
                if (s.kind === 'garage') cls.push('garage');
                if (s.waived_by && !s.removed_at) cls.push('waived');
                const unpriced = !s.removed_at && s.km == null && isDriven(s);
                return `<button type="button" class="${cls.join(' ')}" data-trk="${k}"${unpriced ? ' data-unpriced="1"' : ''} aria-label="${esc(townOf(s.from_place) + ' to ' + townOf(s.to_place) + ', ' + (s.km != null ? fmtKm(s.km) + ' km' : 'no distance'))}"><span class="trk-km">${km}${far ? ` <i class="bi bi-exclamation-triangle-fill trk-far" title="Past tours drove ${fmtKm(s.actual_km)} km on this leg — Google says ${fmtKm(s.google_km)} km"></i>` : ''}</span><span class="trk-rail"></span></button>`;
            };

            const trackHtml = tracks.map((tr, ti) => {
                const groups = [];
                let g = null;
                const nStops = tr.items.filter(x => x.t === 'stop').length;
                let stopIx = 0;
                tr.items.forEach(item => {
                    if (item.t === 'stop') {
                        g = { html: stopHtml(item.st, stopIx === 0, stopIx === nStops - 1), tail: false };
                        stopIx++;
                        groups.push(g);
                        if (ti === 0) railStops.push(item.st.name);
                    } else if (item.t === 'seg') { g.html += segHtml(item.row); g.tail = true; g.grow = growOf(item.row.s); }
                    else { g.html += `<span class="trk-seg gap" aria-hidden="true"><span class="trk-rail"></span></span>`; g.tail = true; }
                });
                return `${multi ? `<div class="trk-veh"><i class="bi bi-truck"></i> ${esc(vehLabel(tr.seq))}</div>` : ''}
                    <div class="trk-line">${groups.map(x => `<div class="trk-grp${x.tail ? '' : ' end'}"${x.grow ? ` style="flex-grow:${x.grow}"` : ''}>${x.html}</div>`).join('')}</div>`;
            }).join('');

            // ---- the days, one line each: the route, the day's kilometres, its sectors; opened for the legs ----
            const seqs = [...new Set(rows.map(r => r.seq))];
            const dayRows = seqs.map(seq => {
                const byDay = new Map();
                rows.filter(r => r.seq === seq).forEach(r => { if (!byDay.has(r.day)) byDay.set(r.day, []); byDay.get(r.day).push(r); });
                const lines = [...byDay.entries()].sort((a, b) => a[0] - b[0]).map(([d, list]) => {
                    const legsD = list.filter(r => r.kind === 'leg');
                    const live = legsD.filter(r => !r.s.removed_at);
                    const km = live.reduce((a, r) => a + (r.s.km != null ? Number(r.s.km) : 0), 0);
                    const route = [];
                    live.forEach(r => [r.s.from_place, r.s.to_place].forEach(p => { if (!route.length || norm(route[route.length - 1]) !== norm(p)) route.push(p); }));
                    const unpriced = live.filter(r => r.s.km == null && isDriven(r.s)).length;
                    const rel = list.filter(r => r.kind === 'released');
                    const body = legsD.map(r => {
                        const s = r.s, k = legDetail(r);
                        return `<button type="button" class="trk-dline${s.removed_at ? ' off' : ''}" data-trk="${k}"><span class="pill pill-grey">${(r.cells || {}).kindLabel || esc(s.kind)}</span>
                            <span>${place(s.from_place)} <span class="help">→</span> <b>${place(s.to_place)}</b></span>
                            <span class="mono ms-auto">${s.km != null ? fmtKm(s.km) + ' km' : '<span class="pill pill-red">no distance</span>'}</span></button>`;
                    }).join('') + rel.map(r => `<div class="trk-dline"><span class="pill pill-grey">released</span>
                            <span class="help">The vehicle is not kept on this day — nothing is charged for it.${r.r.note ? ' “' + esc(r.r.note) + '”' : ''}</span>
                            <button class="btn btn-light btn-sm ms-auto" data-keep="${r.seq}:${r.day}" title="Keep the vehicle on for this day; the stay-day figure is charged">Keep on</button></div>`).join('');
                    return `<details class="trk-dayrow"><summary><i class="bi bi-chevron-right"></i>${dayChip(d)}
                        <span class="trk-route">${route.length ? route.map(p => esc(label(p).main)).join(' – ') : (rel.length ? '<span class="help">vehicle released</span>' : '—')}</span>
                        ${unpriced ? `<span class="pill pill-red">${unpriced} without a distance</span>` : ''}
                        <span class="trk-dkm">${fmtKm(km)} km</span>
                        <span class="trk-cnt"><i class="bi bi-car-front"></i> ${live.length} sector${live.length === 1 ? '' : 's'}</span></summary>
                        <div class="trk-dbody">${body}</div></details>`;
                }).join('');
                return `${multi ? `<div class="trk-veh mt-2"><i class="bi bi-truck"></i> ${esc(vehLabel(seq))}</div>` : ''}${lines}`;
            }).join('');

            const sectors = legRows.filter(r => !r.s.removed_at).length;
            const warn = [
                Number(mil.unpriced) > 0 ? `<span class="pill pill-red">${mil.unpriced} leg(s) with no distance — they cost nothing until the kilometres are typed in</span>` : '',
                Number(mil.google_unasked) > 0 ? `<span class="pill pill-amber">${mil.google_unasked} not asked of Google yet</span>` : '',
                Number(mil.thin) > 0 ? `<span class="pill pill-amber">${mil.thin} quoted on one or two past journeys</span>` : ''
            ].filter(Boolean).join('');
            const diff = mil.buffer_km != null && Number(mil.both_legs) > 0 ? ` · Google against the driven record: <b>${Number(mil.buffer_km) > 0 ? '+' : ''}${fmtKm(mil.buffer_km)} km</b> over ${mil.both_legs} leg${Number(mil.both_legs) === 1 ? '' : 's'}` : '';
            const total = `<div class="trk-total"><i class="bi bi-signpost-split-fill"></i>
                <div><b>Total tour mileage</b><div class="help">Charged ${fmtKm(mil.charged_km || 0)} km — journey ${fmtKm(mil.km || 0)} km, extra for site visits &amp; city tours ${fmtKm(mil.extra_km || 0)} km${Number(mil.waived_km) > 0 ? `, waived ${fmtKm(mil.waived_km)} km` : ''}${!multi && mil.amount != null ? ` · <b style="font-size:12px">LKR ${Number(mil.amount).toLocaleString()}</b>` : ''}${diff}</div></div>
                <div class="trk-tot-r"><span class="trk-tkm">${fmtKm(mil.km || 0)} km</span>
                <span class="trk-cnt"><i class="bi bi-car-front"></i> ${sectors} sector${sectors === 1 ? '' : 's'}</span></div></div>`;

            return `<div class="trk-wrap">
                ${trackHtml}
                <div class="trk-days">${dayRows}</div>
                ${total}
                ${warn ? `<div class="trk-warn">${warn}</div>` : ''}
                <div class="trk-legend"><span><i class="trk-lg" style="background:var(--tf-navy)"></i> quoted sector</span><span><i class="trk-lg dash" style="--lc:var(--tf-muted)"></i> garage run</span><span><i class="trk-lg dash" style="--lc:var(--tf-green)"></i> waived (an excursion with its own transport)</span><span><i class="trk-lg dash" style="--lc:var(--tf-red)"></i> no distance</span><span><i class="bi bi-exclamation-triangle-fill trk-far"></i> past tours drove a fifth or more away from Google's figure</span><span><i class="bi bi-moon-stars" style="color:var(--tf-navy)"></i> nights at the place</span></div>
                <details class="trk-more"><summary>Totals, costing and how the mileage is worked out</summary>${milTot}</details>
                <details class="trk-more"><summary>Add a leg</summary>${addLegForm}</details>
                <div class="trk-pop" hidden role="dialog" aria-label="Details"><button type="button" class="trk-x" aria-label="Close">×</button><div class="trk-pop-b"></div>
                    <div class="trk-tip">Click or tap to keep this open and type a figure.</div></div>
                <div class="trk-store" hidden>${store.join('')}</div>
            </div>`;
        })();
        // the Map view: the tour's places, in order, on Google Maps (the garage is left out; Google takes up to ten stops)
        const MAPURL = (() => {
            const stops = [];
            railStops.forEach(p => { if (!/\bgarage\b/i.test(String(p || '')) && (!stops.length || stops[stops.length - 1].toLowerCase() !== String(p).trim().toLowerCase())) stops.push(String(p).trim()); });
            return stops.length >= 2 ? 'https://www.google.com/maps/dir/' + stops.slice(0, 10).map(p => encodeURIComponent(p + ', Sri Lanka')).join('/') : '';
        })();

        // The party and its rooms. The room mix is typed — singles, doubles, twins, triples —
        // never derived from the passenger count, because ten people could be five doubles
        // or ten singles and the difference is the whole hotel bill. A party that splits is
        // groups, each with its own nights, its own mix and its own names.
        const groups = it.groups || [];
        const rm = it.rooms || { source: 'none' };
        const mixInputs = (prefix, m) => ['sgl', 'dbl', 'twn', 'tpl'].map(k => `<div class="col-3 col-md-2"><label class="form-label help">${{ sgl: 'Single', dbl: 'Double', twn: 'Twin', tpl: 'Triple' }[k]}</label>
            <input type="number" min="0" max="999" class="form-control form-control-sm" id="${prefix}_${k}" value="${m && m[k] != null ? m[k] : 0}"></div>`).join('');
        const partyBlock = groups.length ? `
            <div class="crewrow head grow"><span>Group</span><span>Passengers</span><span>Nights</span><span>Rooms</span><span>Names</span><span></span></div>
            ${groups.map(g => {
                const named = (it.passengers || []).filter(p => Number(p.group_id) === Number(g.id)).length;
                const one = { sgl: Number(g.rooms_sgl), dbl: Number(g.rooms_dbl), twn: Number(g.rooms_twn), tpl: Number(g.rooms_tpl) };
                const sleeps = one.sgl + 2 * one.dbl + 2 * one.twn + 3 * one.tpl;
                return `<div class="crewrow grow">
                <span><span class="pill pill-blue">${esc(g.code)}</span> <b>${esc(g.name)}</b>${g.note ? `<div class="help">${esc(g.note)}</div>` : ''}</span>
                <span><b>${g.pax}</b></span>
                <span>${g.day_from || g.day_to ? `${g.day_from || 1} – ${g.day_to || (it.nights || '')}` : '<span class="help">whole tour</span>'}</span>
                <span>${roomsShort(one) || '<span class="pill pill-red">not set</span>'}${sleeps && Number(g.pax) && sleeps !== Number(g.pax) ? ` <span class="pill pill-amber" title="The mix sleeps ${sleeps}, the group is ${g.pax}">sleeps ${sleeps}</span>` : ''}</span>
                <span>${named}${named > Number(g.pax) ? ' <span class="pill pill-amber">too many</span>' : ''}</span>
                <span style="white-space:nowrap"><button class="btn btn-light btn-sm" data-group="${g.id}">Edit</button>
                    <button class="btn btn-light btn-sm rowdel" data-delgroup="${g.id}" title="Remove this group">&times;</button></span></div>`;
            }).join('')}
            <div class="help mt-2">Groups add up to <b>${groups.reduce((n, g) => n + Number(g.pax), 0)}</b> of ${it.pax_total ?? '—'} passengers. Put each name in its group under Passengers; a vehicle line can carry one group, and an excursion can be for one group.</div>
            <div class="d-flex gap-2 mt-2"><button class="btn btn-light btn-sm" id="d_addgroup"><i class="bi bi-plus-lg"></i> Add a group</button></div>`
            : `<div class="row g-2 align-items-end">${mixInputs('d_rm', rm)}
                <div class="col-6 col-md-2"><button class="btn btn-light btn-sm w-100" id="d_rmsave">Save</button></div>
                <div class="col-6 col-md-2"><button class="btn btn-light btn-sm w-100" id="d_split" title="The party splits: some leave early, or travel apart">Split into groups</button></div>
              </div>
              <div class="help mt-2">${rm.rooms ? `${rm.rooms} room${rm.rooms === 1 ? '' : 's'}, sleeping <b>${rm.sleeps}</b>${it.pax_total != null && Number(rm.sleeps) !== Number(it.pax_total) ? ` <span class="pill pill-amber">the party is ${it.pax_total}</span>` : (it.pax_total != null ? ' <span class="pill pill-green">matches the party</span>' : '')}` : '<span class="pill pill-red">Not set</span> Type the rooms the agent asked for — nothing is worked out from the passenger count.'}${it.rooms_note ? ` <span class="help">The agent wrote: “${esc(it.rooms_note)}”.</span>` : ''}</div>`;

        // Excursions: the rate copied from the master when the line is made; edited on the line
        // (quantity, words), never in the master from here.
        const exc = it.excursions || [];
        const UNITL = { per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' };
        const excursionBlock = `${exc.length ? `<div style="overflow-x:auto"><table class="tbl">
            <thead><tr><th style="width:52px">Day</th><th>Excursion</th><th style="width:110px">For</th><th style="width:90px">Charged</th><th style="width:60px">Qty</th><th style="width:90px">Rate</th><th style="width:100px">Amount</th><th style="width:92px"></th></tr></thead>
            <tbody>${exc.map(x => `<tr>
                <td class="mono">${x.day_no}</td>
                <td><b>${esc(x.name)}</b> <span class="help">${esc(x.code)}</span>${Number(x.includes_transport) === 1 ? ' <span class="pill pill-green" title="The rate brings its own vehicle: the local running that day is waived">transport included</span>' : ''}
                    ${x.description ? `<div class="help">${esc(x.description)}</div>` : ''}${x.note ? `<div class="help">${esc(x.note)}</div>` : ''}</td>
                <td>${x.group_id ? `<span class="pill pill-blue">${esc(groupName(x.group_id) || 'group')}</span>` : '<span class="help">everyone</span>'}</td>
                <td>${esc(UNITL[x.unit] || x.unit)}</td>
                <td class="mono">${Number(x.qty) > 0 ? Number(x.qty) : '<span class="pill pill-red">0</span>'}</td>
                <td class="mono">${esc(x.currency)} ${Number(x.rate).toLocaleString()}</td>
                <td class="mono"><b>${esc(x.currency)} ${Number(x.amount).toLocaleString()}</b></td>
                <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-exc="${x.id}">Edit</button>
                    <button class="btn btn-light btn-sm rowdel" data-delexc="${x.id}" title="Remove">&times;</button></td></tr>`).join('')}</tbody></table></div>` : '<div class="empty">No excursions on this quotation.</div>'}
            <div class="row g-2 mt-2 align-items-end">
                <div class="col-md-5"><label class="form-label help">Activity or excursion, from Master Files → Paid Activities &amp; Services</label>
                    <select class="form-select form-select-sm" id="d_excsel"><option value="">—</option></select></div>
                <div class="col-md-2"><label class="form-label help">Day</label>
                    <select class="form-select form-select-sm" id="d_excday">${(it.days || []).map(d => `<option value="${d.day_no}">Day ${d.day_no}${d.area ? ' · ' + esc(d.area) : ''}</option>`).join('')}<option value="${(it.days || []).length + 1}">Day ${(it.days || []).length + 1} (departure)</option></select></div>
                <div class="col-md-3"><label class="form-label help">For</label>
                    <select class="form-select form-select-sm" id="d_excgrp"><option value="">Everyone</option>${groups.map(g => `<option value="${g.id}">${esc(g.name)}</option>`).join('')}</select></div>
                <div class="col-md-2"><button class="btn btn-light btn-sm w-100" id="d_excadd">Add</button></div>
            </div>
            <div class="help mt-2">The quantity starts from the unit — the people going, one per group, or one per vehicle — and can be changed on the line. An excursion that is not in the master cannot be typed here${SEES_PAID ? `: add it under <a href="${PAID_URL}" target="_blank">Paid Activities &amp; Services</a> first` : (SEES_EXC ? `: add it under <a href="${EXC_URL}" target="_blank">Excursion Rates</a> first` : '')}. A night's programme also adds them, by destination, from its <b>+</b>.</div>`;

        // Entrance & Activities: the one place the entrance and activity cost is worked out —
        // every line across the days, what it costs and why, and one total the quotation takes as it is
        const ea = it.entrances || { lines: [], total_usd: 0, by_state: {}, stops: 0, mentions: [], recalc: [] };
        const EA_PILL = { priced: ['Priced', 'pill-green'], no_charge: ['No charge', 'pill-green'], declined: ['No cost', 'pill-grey'], direct: ['Paid direct', 'pill-blue'], unsure: ['Unsure', 'pill-amber'], no_amount: ['No amount', 'pill-amber'], rupees: ['Check figure', 'pill-red'], rate_missing: ['Rate missing — held', 'pill-red'], not_in_master: ['Not in the master — held', 'pill-red'] };
        const eaLines = ea.lines || [];
        const eaBy = ea.by_state || {};
        const entrancesBlock = `${eaLines.length ? `<div style="overflow-x:auto"><table class="tbl" id="ea_table">
            <thead><tr><th style="width:52px">Day</th><th>Entrance / activity</th><th style="width:120px">${ea.master ? 'From the master' : 'From the list'}</th><th style="width:70px">For</th><th style="width:90px">Rate USD</th><th style="width:100px">Amount USD</th><th style="width:170px"></th></tr></thead>
            <tbody>${eaLines.map(l => { const [pt, pc] = EA_PILL[l.state] || [l.state, 'pill-grey']; const dup = (ea.duplicates || []).find(x => x.ids.includes(l.id)); const sug = l.suggest_code && (l.state === 'unsure' || l.state === 'not_in_master') ? ((MASTER || []).find(m => String(m.code).toLowerCase() === String(l.suggest_code).toLowerCase()) || { code: l.suggest_code, name: l.suggest_code, usd: null }) : null;
                return `<tr data-ealine="${l.id}" class="${l.blocks || dup ? 'ea-stop' : ''}">
                <td class="mono">${l.day_no || 'tour'}${l.day_date ? `<div class="help">${fmtDate(l.day_date)}</div>` : ''}</td>
                <td><b>${esc(l.title)}</b> <span class="pill pill-grey">${esc(l.kind)}</span>${l.origin === 'human' ? ' <span class="pill pill-blue">yours</span>' : ''}${dup ? ` <span class="pill pill-red" title="The same thing ${dup.ids.length === 2 ? 'twice' : dup.ids.length + ' times'} on this day — costed ${dup.ids.length === 2 ? 'twice' : dup.ids.length + ' times'}; remove the extra line">on the day ${dup.ids.length === 2 ? 'twice' : dup.ids.length + ' times'}</span>` : ''}
                    ${l.note || l.why ? `<div class="help" style="color:${l.blocks ? 'var(--tf-amber)' : (l.state === 'no_charge' ? 'var(--tf-green)' : 'var(--tf-muted)')}">${esc(l.blocks || l.state === 'no_charge' ? (l.note || l.why) : (l.why || ''))}</div>` : ''}
                    ${l.special_note ? `<div class="help" style="color:var(--tf-navy)">${esc(l.special_note)}</div>` : ''}</td>
                <td>${l.meal_id ? `<span class="pill pill-green">Meal Master</span>${l.qty_adult != null ? `<div class="help">${Number(l.qty_adult)} adult${Number(l.qty_adult) === 1 ? '' : 's'}${Number(l.qty_child) ? ' · ' + Number(l.qty_child) + ' child' + (Number(l.qty_child) === 1 ? '' : 'ren') : ''}${l.meal_time ? ' · ' + esc(l.meal_time) : ''}</div>` : ''}` : (l.fee_code ? `<span class="mono">${esc(l.fee_code)}</span>${l.fee_name ? `<div class="help">${esc(l.fee_name)}</div>` : ''}` : '<span class="help">—</span>')}</td>
                <td class="mono">${l.count != null ? Number(l.count) + (l.per_person ? ' pax' : '') : '—'}${l.park ? `<div class="help">${l.jeeps != null ? Number(l.jeeps) + ' jeep' + (Number(l.jeeps) === 1 ? '' : 's') : 'jeeps?'}</div>` : ''}</td>
                <td class="mono">${l.unit_usd != null ? Number(l.unit_usd).toFixed(2) : '—'}${l.meal_id && (l.child_usd != null || (l.rate_currency && l.rate_currency !== 'USD')) ? `<div class="help">an adult, with taxes${l.child_usd != null ? ' · child ' + Number(l.child_usd).toFixed(2) : ''}${l.rate_currency && l.rate_currency !== 'USD' && l.amount_cur != null ? ' · ' + esc(l.rate_currency) + ' ' + Number(l.amount_cur).toLocaleString() + ' in all' : ''}</div>` : ''}${l.place_id && (l.child_usd != null || (l.rate_currency && l.rate_currency !== 'USD') || l.jeep_usd != null) ? `<div class="help">${l.unit_usd != null ? 'a head' : ''}${l.child_usd != null ? ' · child ' + Number(l.child_usd).toFixed(2) : ''}${l.rate_currency && l.rate_currency !== 'USD' ? ' · ' + esc(l.rate_currency) + ' ' + Number(l.rate_adult).toLocaleString() : ''}${l.jeep_usd != null ? '<br>jeep ' + Number(l.jeep_usd).toFixed(2) + ' per vehicle' : ''}</div>` : ''}</td>
                <td class="mono">${l.amount_usd != null ? '<b>' + Number(l.amount_usd).toFixed(2) + '</b>' : (l.would_cost_usd != null ? `<span class="help" title="What it would cost if it were taken on">(${Number(l.would_cost_usd).toFixed(2)})</span>` : '—')}</td>
                <td style="white-space:nowrap"><span class="pill ${pc}">${pt}</span>
                    ${it.locked ? '' : l.meal_id ? `<div class="mt-1 d-flex gap-1 flex-wrap"><button class="btn btn-light btn-sm" data-mealopen="${l.id}" title="Lunches and dinners are planned on the Meals card">${l.state === 'rate_missing' ? 'Pick another meal' : 'Open in Meals'}</button></div>` : `<div class="mt-1 d-flex gap-1 flex-wrap">${l.state === 'rate_missing' ? (SEES_MASTER ? `<a class="btn btn-light btn-sm" href="${MASTER_URL}" target="_blank" title="The rate is set in the master; nothing is typed here">Set the rate in the master</a>` : '<span class="help">The master needs the rate</span>') : ''}${l.state === 'not_in_master' ? `${sug ? `<button class="btn btn-light btn-sm" data-eaaccept="${l.id}" data-code="${esc(sug.code)}" style="white-space:normal;text-align:left" title="Price it from this place of the master">Use “${esc(sug.name)}”</button>` : ''}${SEES_MASTER ? `<a class="btn btn-light btn-sm" href="${MASTER_URL}" target="_blank" title="Add the place to the master, with its rate or as Free">Add it in the master</a>` : ''}` : ''}${l.blocks && l.state !== 'rate_missing' && l.state !== 'not_in_master' ? `<button class="btn btn-light btn-sm" data-eanocharge="${l.id}" title="A stop with nothing to pay: on the programme, nothing costed">No charge</button>${sug ? `<button class="btn btn-light btn-sm" data-eaaccept="${l.id}" data-code="${esc(sug.code)}" title="Price it from this entry in the list">Use “${esc(sug.name)}”${sug.usd ? ' $' + Number(sug.usd).toFixed(2) : ''}</button>` : ''}` : ''}<button class="btn btn-light btn-sm" data-eaedit="${l.id}" title="Open the line on its day">Open</button></div>`}</td></tr>`; }).join('')}</tbody>
            <tfoot><tr><td colspan="5" style="text-align:right;padding:8px 10px"><b>Entrance &amp; Activities total</b> <span class="help">${eaBy.priced || 0} priced · ${eaBy.no_charge || 0} no charge${eaBy.direct ? ' · ' + eaBy.direct + ' paid direct' : ''}${eaBy.declined ? ' · ' + eaBy.declined + ' no cost' : ''}${ea.stops ? ' · <span class="pill pill-amber">' + ea.stops + ' to settle</span>' : ''}</span></td>
                <td class="mono" style="padding:8px 10px"><b id="ea_total">USD ${Number(ea.total_usd || 0).toFixed(2)}</b></td><td></td></tr></tfoot></table></div>` : '<div class="empty">No entrance or activity on this inquiry yet — add one here, or on a night under Requested itinerary.</div>'}
            ${(ea.free_visits || []).length ? `<div class="mt-2 help" id="ea_free" style="color:var(--tf-green)"><i class="bi bi-signpost-2"></i> ${ea.free_visits.length} free visit${ea.free_visits.length === 1 ? '' : 's'} on the programme — in the itinerary only, never costed: ${ea.free_visits.map(f => esc(f.title) + (f.day_no ? ' (night ' + f.day_no + ')' : '')).join(', ')}.</div>` : ''}
            ${(ea.recalc || []).length ? `<div class="mt-2" id="ea_recalc" style="color:var(--tf-teal);font-size:12px"><i class="bi bi-arrow-repeat"></i> Worked out again just now — ${ea.recalc.map(esc).join('; ')}. The total above and the quotation follow.</div>` : ''}
            ${(ea.mentions || []).length ? `<div class="mt-2" id="ea_mentions">${ea.mentions.map(m => `<div class="help" style="color:var(--tf-amber)"><i class="bi bi-exclamation-triangle"></i> Day ${m.day_no} mentions “${esc(m.phrase)}” — the list has <b>${esc(m.name)}</b> (${esc(m.code)}) — but there is no line for it here. ${it.locked ? '' : `<button class="btn btn-light btn-sm" data-eaadd="${m.day_no}" data-title="${esc(m.name)}" data-code="${esc(m.code)}">Add it</button>`} Leave it if it is only a drive past.</div>`).join('')}</div>` : ''}
            <div class="help mt-2">${ea.master
                ? `The one place the entrance and activity cost is worked out, for the party of ${ea.pax || '—'}. Entrances and safaris are charged from the <b>Place to Visit / Entrance Fee Master</b> only: a place there with a valid rate for the day is charged (children at the child rate; at a safari park the jeep per vehicle too — How many on the line is the number of jeeps); one with no valid rate is held until the master has one; an entrance or safari that is not in the master is held — never free: pick the place, or add it there. Ordinary sightseeing has no entrance charge. Meals, guides, trains and other activities are priced from the old list or decided here, as before. Everything is worked out again whenever the party, a line or a rate changes; the quotation takes the total above as it is.`
                : `The one place the entrance and activity cost is worked out: each line is priced from Master Files → Entrance Fee for the party of ${ea.pax || '—'}, and worked out again on its own whenever the party, a line or a rate in the master changes. The quotation takes the total above as it is and never prices an entrance by itself. A line that cannot be priced stops Go for Quotation until it is priced or marked No charge.`}</div>`;

        // Go for Quotation: the validation is the engine's own blockers; on a pass a numbered
        // version is frozen and its two views open from here. Versions are history.
        const quotes = it.quotes || [];
        const redGaps = (it.gaps || []).filter(g => g.level === 'red').length;
        const QS = { draft: 'pill-amber', issued: 'pill-green', superseded: 'pill-grey' };
        const quoteBlock = `
            ${quotes.length ? `<div class="crewrow head"><span>Version</span><span>Price</span><span>Per person</span><span>Valid until</span><span></span></div>
            ${quotes.map(q => `<div class="crewrow">
                <span><b>v${q.version}</b> <span class="pill ${QS[q.status] || 'pill-grey'}">${esc(q.status)}</span><div class="help">${q.status === 'issued' ? 'issued ' + esc((q.issued_at || '').slice(0, 16)) + ' by ' + esc(q.issued_by || '') : 'made ' + esc((q.created_at || '').slice(0, 16)) + ' by ' + esc(q.created_by || '')}${q.note ? ' · ' + esc(q.note) : ''}</div></span>
                <span>${q.sell_usd != null ? '<b>USD ' + Number(q.sell_usd).toLocaleString(undefined, { minimumFractionDigits: 2 }) + '</b>' : '<span class="pill pill-amber">no price</span>'}</span>
                <span>${q.per_person_usd != null ? 'USD ' + Number(q.per_person_usd).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '—'}</span>
                <span>${q.valid_until ? fmtDate(q.valid_until) : '—'}</span>
                <span style="white-space:nowrap"><a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${it.id}/quote/${q.id}?view=internal">Costing</a>
                    <a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${it.id}/quote/${q.id}?view=client">Client</a>
                    <a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${it.id}/quote/${q.id}?view=client&pdf=1" title="Client quotation as PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                    ${q.status === 'draft' && !it.locked ? `<button class="btn btn-success btn-sm" data-issue="${q.id}" title="Sent to the agent: this version becomes the quotation on record">Issue</button>
                    <button class="btn btn-light btn-sm rowdel" data-discard="${q.id}" title="Throw the draft away">&times;</button>` : ''}</span></div>`).join('')}` : '<div class="empty">No quotation version yet.</div>'}
            ${it.locked ? '' : `<div class="d-flex gap-2 mt-3 flex-wrap align-items-center">
                <button class="btn btn-light btn-sm" id="d_checklist" title="Every rate and figure still missing, each with a Fix that takes you to it"><i class="bi bi-list-check"></i> Checklist</button>
                <button class="btn btn-primary btn-sm" id="d_go"><i class="bi bi-check2-square"></i> Go for Quotation</button>
                <a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${it.id}/preview?view=internal">Preview costing</a>
                <a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${it.id}/preview?view=client">Preview client quotation</a>
                <span class="help">${redGaps ? `<span class="pill pill-red">${redGaps} red item${redGaps === 1 ? '' : 's'}</span> listed under Still needed — the Checklist takes you to each one; Go for Quotation checks every rate and figure and stops on what is missing.` : 'The Checklist lists what is still missing, with a Fix for each; Go for Quotation checks every rate and figure and, on a pass, freezes a numbered version to preview, print and send.'}</span>
            </div>`}`;

        // Pricing: management only. The markup and the profit never reach the desk.
        const pricingBlock = !SEES_PRICING ? '' : `
            <div class="tf-card" style="border-left:3px solid var(--tf-amber)"><div class="tf-card-h"><h6>Pricing <span class="pill pill-amber">Management only</span></h6>
                <span class="help">Cost plus markup is the client's price; the desk sees the price, never the markup</span>
                <a class="btn btn-light btn-sm ms-auto" href="${PRICING_URL}"><i class="bi bi-percent"></i> Pricing settings</a></div>
                <div class="tf-card-b" id="d_pricing"><div class="help">Costing…</div></div></div>`;

        const gaps = (it.gaps || []).map(g => `<div><span class="pill ${g.level === 'red' ? 'pill-red' : (g.level === 'amber' ? 'pill-amber' : 'pill-blue')}">${g.level}</span><span>${esc(g.label)}</span></div>`).join('')
            || `<div class="empty">Nothing outstanding${hasMail ? ' — everything the email said is captured and every hotel can be priced.' : ' — every hotel named can be priced.'}</div>`;

        document.getElementById('detail_cols').innerHTML = `
            ${progressStrip(it)}
            <div class="${it.locked ? 'locked' : ''}">
            ${GROUPS.map(g => `<div class="tf-card"><div class="tf-card-h"><h6>${g.title}</h6><span class="help">Click a value to see where it came from, or to correct it</span></div>
                <div class="fields">${g.keys.map(fieldCard).join('')}</div></div>`).join('')}

            <div class="tf-card"><div class="tf-card-h"><h6>Party &amp; rooms</h6>
                <span class="help">The room mix every night is costed on — typed, never worked out from the passenger count; groups when the party splits</span></div>
                <div class="tf-card-b">${partyBlock}</div></div>

            <div class="tf-card">
                <div class="tf-card-h"><h6>Requested itinerary</h6>
                    <span class="help">Checked against the Hotel Rates panel${it.market_code ? ' for market ' + esc(it.market_code) : ''} — published rates only</span>
                    <button class="btn btn-light btn-sm ms-auto" id="d_addday"><i class="bi bi-plus-lg"></i> Add a night</button></div>
                <div style="overflow-x:auto"><table class="tbl">
                    <thead><tr><th style="width:64px">Night</th><th style="width:110px">Area</th><th style="width:180px">Hotel named by the agent</th><th style="width:76px">Meal</th><th style="width:96px">Rooms</th><th style="width:230px">Published rate</th><th style="width:250px">Visits, activities &amp; services</th><th style="width:150px">Special note</th><th style="width:64px"></th></tr></thead>
                    <tbody>${days || (depRows || depDayRow ? '' : '<tr><td colspan="9" class="empty">No itinerary was described.</td></tr>')}${depRows}${missRow}${depDayRow}</tbody></table></div>
                <div class="tf-card-b help" style="padding-top:8px">Press <b>Choose hotel</b> on a night to pick the hotel from the Hotel Rates panel and set the meal plan; the hotel's normal room type is filled in from the master and the published rate for that room and meal is copied beside the night — never typed, and a tactical or special-offer rate is never used. <b>Edit</b> the night to choose another room type for this booking, or to word it as the booking does; the master is never changed from here. Two nights at the same hotel become one stay when the tour is confirmed. <b>Add a night</b> puts another row at the end; a night past the dates moves the departure a day later, and says so first. The departure day is never a night: it follows the last night by itself.</div>
            </div>

            ${itineraryCard}

            ${photosCard}

            <div class="tf-card" id="ea_card"><div class="tf-card-h"><h6>Entrance &amp; Activities</h6>
                <span class="help">The single source of the entrance and activity cost — the quotation receives its total</span>
                ${it.locked ? '' : `<button class="btn btn-light btn-sm ms-auto" id="d_eaadd" title="Add an entrance or activity on a night"><i class="bi bi-plus-lg"></i> Add</button>`}</div>
                <div class="tf-card-b">${entrancesBlock}</div></div>

            ${mealsCard(it)}

            <div class="tf-card" id="mil_card"><div class="tf-card-h"><h6>Mileage by sector</h6>
                <span class="help">${MILVIEW === 'list' ? `Google's road distance and the company's own driven record, side by side — the quotation is costed on ${(it.mileage && it.mileage.source) === 'actual' ? 'the driven record' : 'Google\'s figure'}` : 'The whole tour at a glance — point at a place or a sector (tap it on a phone) to see its details'}</span>
                <span class="mv-switch ms-auto" role="group" aria-label="How the mileage is shown">
                    <button type="button" data-mview="rail" class="${MILVIEW === 'rail' ? 'on' : ''}" title="The tour as a railway line: places and the kilometres between them"><i class="bi bi-train-front"></i> Railway view</button>
                    <button type="button" data-mview="list" class="${MILVIEW === 'list' ? 'on' : ''}" title="Every leg with every figure, as a list"><i class="bi bi-list-ul"></i> List view</button>
                    ${MAPURL ? `<a href="${esc(MAPURL)}" target="_blank" rel="noopener" title="The tour's places in order on Google Maps (opens a new tab; Google shows up to ten stops)"><i class="bi bi-map"></i> Map view</a>` : ''}</span>
                ${it.mileage && it.mileage.google_key && (it.mileage.google_unasked > 0 || it.mileage.google_missing > 0) ? `<button class="btn btn-light btn-sm" id="d_google" title="Ask Google about every driven leg that has no figure yet; a distance typed by hand is never replaced"><i class="bi bi-geo-alt"></i> Ask Google</button>` : ''}
                <button class="btn btn-light btn-sm" id="d_autoleg" title="Work the whole itinerary's driving out again, losing any hand-typed legs"><i class="bi bi-magic"></i> Auto</button></div>
                <div class="tf-card-b">${MILVIEW === 'list' ? mileageBlock : railBlock}</div></div>

            <div class="tf-card"><div class="tf-card-h"><h6>Excursions</h6>
                <span class="help">Their own rates, from Master Files → Excursion Rates, copied onto the line when added; one that includes transport waives that day's local running</span></div>
                <div class="tf-card-b">${excursionBlock}</div></div>

            <div class="tf-card"><div class="tf-card-h"><h6>Vehicles</h6>
                <span class="help">Size from the passenger count; rate per kilometre from Master Files → Transport Rates, copied onto this inquiry when the vehicle is settled; several vehicles and drivers on a day when the party needs them</span>
                ${SEES_PRICING ? `<button class="btn btn-light btn-sm ms-auto" id="d_reprice" title="Copy today's master rate onto every line still on a master rate (Management)"><i class="bi bi-arrow-repeat"></i> Re-price</button>` : '<span class="ms-auto"></span>'}
                <button class="btn btn-light btn-sm" id="d_autoveh" title="Choose it from the passenger count again"><i class="bi bi-magic"></i> Auto</button></div>
                <div class="tf-card-b">${vehicleBlock}${vehPick}</div></div>

            ${crewCard('driver')}

            ${crewCard('guide')}

            <div class="tf-card"><div class="tf-card-h"><h6>Requests &amp; questions</h6><span class="help">Kept word for word</span>
                <button class="btn btn-light btn-sm ms-auto" id="d_addreq"><i class="bi bi-plus-lg"></i> Add one</button></div>
                <div class="tf-card-b">${reqs}</div></div>

            <div class="tf-card"><div class="tf-card-h"><h6>Passengers</h6><span class="help">Names only — no passport, no date of birth</span>
                <button class="btn btn-light btn-sm ms-auto" id="d_addpax"><i class="bi bi-plus-lg"></i> Add a name</button></div>
                <div class="tf-card-b"><div class="paxgrid">${pax}</div></div></div>

            <div class="tf-card" style="border-left:3px solid var(--tf-amber)"><div class="tf-card-h"><h6>${it.status === 'confirmed' ? 'Notes left on the inquiry' : 'Still needed before this can be quoted'}</h6><span class="help">${it.status === 'confirmed' ? 'the tour is confirmed; these stop nothing' : `${gapCount} item${gapCount === 1 ? '' : 's'}`}</span></div>
                <div class="tf-card-b gaps">${gaps}</div></div>

            ${pricingBlock}

            <div class="tf-card" style="border-left:3px solid var(--tf-teal)"><div class="tf-card-h"><h6>Quotation</h6>
                <span class="help">Go for Quotation checks every rate and figure; on a pass a numbered version is frozen — an internal costing and the client quotation on Avenir Holidays paper</span></div>
                <div class="tf-card-b">${quoteBlock}</div></div>

            </div>

            <div class="tf-card" id="act_card"><div class="tf-card-h"><h6>History</h6>
                <span class="help">Who changed what, and when — every edit here and every move on the desks. Ownership (the handling agent) is above; this is the editing.</span>
                <span class="help ms-auto" id="act_count"></span><button class="btn btn-light btn-sm" id="act_reload" type="button" title="Read the history again"><i class="bi bi-arrow-repeat"></i></button></div>
                <div class="tf-card-b" id="act_body"><div class="help">Loading…</div></div></div>

            <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    ${statusPill(it.status)}
                    ${it.locked ? '' : `
                    ${['new', 'working'].includes(it.status) ? '<button class="btn btn-light btn-sm" id="d_quoted" title="The quotation has gone to the agent">Mark as quoted</button>' : ''}
                    ${['working', 'quoted', 'new'].includes(it.status) ? '<button class="btn btn-success btn-sm" id="d_confirm" title="The agent said yes"><i class="bi bi-check2-circle"></i> Confirm Tour</button>' : ''}
                    ${['new', 'working', 'quoted'].includes(it.status) ? '<button class="btn btn-light btn-sm" id="d_decline">Decline</button>' : ''}
                    ${['quoted', 'declined'].includes(it.status) ? '<button class="btn btn-light btn-sm" id="d_reopen">Back to working</button>' : ''}
                    ${['new', 'working', 'quoted'].includes(it.status) && 'follow_up_on' in it ? `<span class="d-inline-flex gap-1 align-items-center ms-2" title="When to chase the agent. Nothing is sent; the inquiry shows under Follow-ups due when the day comes.">
                        <i class="bi bi-bell help"></i><input type="date" class="form-control form-control-sm" id="d_followup" style="width:auto" value="${esc(it.follow_up_on || '')}">
                        <input class="form-control form-control-sm" id="d_followup_note" style="width:170px" placeholder="follow up about…" maxlength="255" value="${esc(it.follow_up_note || '')}">
                        <button class="btn btn-light btn-sm" id="d_followup_ok" type="button">Set</button>${it.follow_up_on ? '<button class="btn btn-light btn-sm" id="d_followup_clear" type="button" title="Clear the follow-up">&times;</button>' : ''}</span>` : ''}`}
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-light btn-sm" id="d_back">Back to list</button>
                    ${it.locked ? '' : `<button class="btn btn-light btn-sm" id="d_recheck"><i class="bi bi-arrow-repeat"></i> Check hotels again</button>
                    ${(it.days || []).some(d => d.rate_drift) ? '<button class="btn btn-warning btn-sm" id="d_takenew" title="Every night Hotel Rates has moved away from takes the panel\'s figure; each one is a line in the history">Use all new rates</button>' : ''}
                    ${hasMail && !(it.edit_requests && it.edit_requests.read_blocked) ? '<button class="btn btn-light btn-sm" id="d_reread"><i class="bi bi-magic"></i> Read the email again</button>' : ''}`}
                    ${CAN_MANAGE ? '<button class="td-dots" id="d_manage" type="button" title="Manage">···</button>' : ''}
                </div>
            </div>`;
        loadHistory();
        document.getElementById('act_reload')?.addEventListener('click', loadHistory);
        document.getElementById('d_manage')?.addEventListener('click', e => { e.stopPropagation(); manageMenu(e.currentTarget, CURRENT); });


        // email pane, with the clicked field's quote highlighted
        renderMail(null);
        // schema 33: the Edit Request card below it
        renderEditCard();
        if (hasMail) {
            document.getElementById('mail_meta').textContent = it.extracted_at ? ('read ' + it.extracted_at + (it.extract_model ? ' · ' + it.extract_model : '')) : 'not read yet';
        }

        document.querySelectorAll('#detail_cols .fld').forEach(b => b.addEventListener('click', () => openField(b.dataset.field)));
        document.querySelectorAll('#detail_cols [data-day]').forEach(b => b.addEventListener('click', () => openDay(Number(b.dataset.day))));
        document.querySelectorAll('#detail_cols [data-photoday]').forEach(b => b.addEventListener('click', () => openPhotoPicker(Number(b.dataset.photoday))));
        document.querySelectorAll('#detail_cols [data-reprice]').forEach(b => b.addEventListener('click', async (ev) => {
            ev.stopPropagation();
            b.disabled = true;
            const j = await post('/intake/' + CURRENT.id + '/day/' + b.dataset.reprice + '/reprice', {});
            if (j.status !== 200) { b.disabled = false; toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); toast('success', j.message);
        }));
        document.querySelectorAll('#detail_cols [data-mealpick]').forEach(b => b.addEventListener('click', async (ev) => {
            ev.stopPropagation();
            b.disabled = true;
            const j = await post('/intake/' + CURRENT.id + '/day/' + b.dataset.dayid, { meal_plan: b.dataset.mealpick });
            if (j.status !== 200) { b.disabled = false; toast('error', j.message); return; }
            CURRENT = j.data; renderDetail();
        }));
        document.querySelectorAll('#detail_cols [data-req]').forEach(b => b.addEventListener('click', () => openReq(Number(b.dataset.req))));
        document.querySelectorAll('#detail_cols [data-addsvc]').forEach(b => b.addEventListener('click', () => openServices(Number(b.dataset.addsvc))));
        document.querySelectorAll('#detail_cols [data-dayexc]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); openExc(Number(b.dataset.dayexc)); }));
        document.querySelectorAll('#detail_cols [data-svc]').forEach(b => b.addEventListener('click', () => {
            const s = (CURRENT.services || []).find(x => Number(x.id) === Number(b.dataset.svc));
            if (s) openServices(Number(s.day_no), Number(s.id));
        }));

        // hand entry: add a row, remove a row. Same fields as the read produces.
        // the rate per kilometre on a vehicle line
        document.querySelectorAll('#detail_cols [data-vrate]').forEach(b => b.addEventListener('click', () => openVehicleRate(Number(b.dataset.vrate))));
        document.querySelectorAll('#detail_cols [data-vmaster]').forEach(b => b.addEventListener('click', () => rowPost('/vehicle', { line_id: Number(b.dataset.vmaster), rate: null }, 'Back on the master rate.')));
        document.getElementById('d_reprice')?.addEventListener('click', async () => {
            if (!await ask('Copy today\'s master rate onto every vehicle line still on a master rate? A special rate is left alone.', 'Re-price')) return;
            await rowPost('/vehicle', { reprice: 1 }, 'Re-priced.');
        });

        // mileage: edit a leg, add one, drop one, or hand the lot back to the history
        document.querySelectorAll('#detail_cols [data-leg]').forEach(b => b.addEventListener('click', () => openLeg(Number(b.dataset.leg))));
        document.querySelectorAll('#detail_cols [data-delleg]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await ask('Take this leg off the mileage? Its kilometres stop being costed, and the totals and the costing follow at once.', 'Delete the leg')) return;
            await rowPost('/sector/' + b.dataset.delleg, { delete: 1 }, 'Leg taken off.');
        }));
        document.querySelectorAll('#detail_cols [data-restoreleg]').forEach(b => b.addEventListener('click', () => rowPost('/sector/' + b.dataset.restoreleg, { restore: 1 }, 'Leg put back.')));
        // the actual kilometres typed on a leg: beside Google's figure, never over it
        // an allowance leg's own kilometres (garage run, stay day, city tour), typed on the row
        document.querySelectorAll('#detail_cols [data-legkm]').forEach(inp => {
            const save = async () => {
                const s = (CURRENT.sectors || []).find(x => Number(x.id) === Number(inp.dataset.legkm));
                if (!s) return;
                const was = s.km_by === 'manual' && s.km != null ? String(Number(s.km)) : '';
                if (String(inp.value) === was) return;
                await rowPost('/sector/' + inp.dataset.legkm, {
                    day_no: s.day_no, from_place: s.from_place, to_place: s.to_place, kind: s.kind,
                    km: inp.value, extra_km: s.extra_by === 'manual' ? s.extra_km : '', note: s.note
                }, inp.value === '' ? 'Back to the figure from Mileage Allowances.' : 'Kilometres saved.');
            };
            inp.addEventListener('change', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        });
        // the extra running for site visits and city tours, typed on the row
        document.querySelectorAll('#detail_cols [data-extra]').forEach(inp => {
            const save = async () => {
                const s = (CURRENT.sectors || []).find(x => Number(x.id) === Number(inp.dataset.extra));
                const was = s && s.extra_by === 'manual' && s.extra_km != null ? String(Number(s.extra_km)) : '';
                if (String(inp.value) === was) return;
                await rowPost('/sector/' + inp.dataset.extra + '/extra', { km: inp.value }, 'Extra running saved.');
            };
            inp.addEventListener('change', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        });
        document.querySelectorAll('#detail_cols [data-actual]').forEach(inp => {
            const save = async () => {
                const s = (CURRENT.sectors || []).find(x => Number(x.id) === Number(inp.dataset.actual));
                const was = s && s.actual_by === 'manual' && s.actual_km != null ? String(s.actual_km) : '';
                if (String(inp.value) === was) return;
                await rowPost('/sector/' + inp.dataset.actual + '/actual', { km: inp.value }, 'Actual kilometres kept.');
            };
            inp.addEventListener('change', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        });
        document.getElementById('d_legadd')?.addEventListener('click', () => rowPost('/sector', {
            vehicle_seq: document.getElementById('d_legveh')?.value || 0,
            day_no: document.getElementById('d_legday').value || 0,
            from_place: document.getElementById('d_legfrom').value,
            to_place: document.getElementById('d_legto').value,
            km: document.getElementById('d_legkm').value,
            kind: document.getElementById('d_legkind')?.value || 'transfer'
        }, 'Leg added.'));
        document.getElementById('d_autoleg')?.addEventListener('click', async () => {
            if (!await ask('Work the driving out again from the itinerary? Any leg you typed by hand is replaced, and legs you took off come back.', 'Work it out again')) return;
            await rowPost('/sector', { auto: 1 }, 'Mileage worked out again.');
        });
        // Google: every driven leg without its figure, or one leg asked again
        document.getElementById('d_google')?.addEventListener('click', () => rowPost('/google', {}, 'Google asked.'));
        document.querySelectorAll('#detail_cols [data-gleg]').forEach(a => a.addEventListener('click', async e => {
            e.preventDefault();
            await rowPost('/sector/' + a.dataset.gleg + '/google', { refresh: 1 }, 'Google asked.');
        }));
        trackInit();   // tf-mileage-track-r1: the Railway view's switch and its details window
        // ---------- the engine: groups, rooms, vehicles by day, excursions, the quotation ----------
        document.getElementById('d_rmsave')?.addEventListener('click', () => rowPost('/rooms', {
            rooms_sgl: document.getElementById('d_rm_sgl').value, rooms_dbl: document.getElementById('d_rm_dbl').value,
            rooms_twn: document.getElementById('d_rm_twn').value, rooms_tpl: document.getElementById('d_rm_tpl').value
        }, 'Room mix saved.'));
        document.getElementById('d_split')?.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Split the party into groups?', text: 'The first group takes the whole party and its room mix; then add a second group and move the passengers, rooms and nights that belong to it. Each vehicle and each excursion can then be for one group.', showCancelButton: true, confirmButtonText: 'Split' });
            if (r.isConfirmed) rowPost('/group', {}, 'Group A made from the whole party.');
        });
        document.getElementById('d_addgroup')?.addEventListener('click', () => openGroup(null));
        document.querySelectorAll('#detail_cols [data-group]').forEach(b => b.addEventListener('click', () => openGroup(Number(b.dataset.group))));
        document.querySelectorAll('#detail_cols [data-delgroup]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await ask('Remove this group? Its names, vehicles and excursions go back to the whole party.', 'Remove')) return;
            await rowPost('/group/' + b.dataset.delgroup, { delete: 1 }, 'Group removed.');
        }));
        document.querySelectorAll('#detail_cols [data-paxgroup]').forEach(sel => sel.addEventListener('change', () =>
            rowPost('/pax/' + sel.dataset.paxgroup + '/group', { group_id: sel.value }, 'Passenger assigned.')));

        document.getElementById('d_vehadd')?.addEventListener('click', () => openVline(null));
        document.querySelectorAll('#detail_cols [data-vline]').forEach(b => b.addEventListener('click', () => openVline(Number(b.dataset.vline))));
        document.querySelectorAll('#detail_cols [data-delvline]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await ask('Remove this vehicle line? Its legs go with it.', 'Remove')) return;
            const rows = (CURRENT.vehicles || []).filter(v => Number(v.id) !== Number(b.dataset.delvline)).map(vlineRow);
            await rowPost('/vehicle', { rows }, 'Vehicle line removed.');
        }));
        document.querySelectorAll('#detail_cols [data-release]').forEach(b => b.addEventListener('click', async () => {
            const [seq, day] = b.dataset.release.split(':');
            const r = await Swal.fire({ title: 'Release the vehicle for day ' + day + '?', input: 'text', inputPlaceholder: 'Why (optional) — e.g. sent back to Colombo for the day', text: 'Nothing is charged for that day: the stay-day figure comes off. Say why, for the driver\'s pack.', showCancelButton: true, confirmButtonText: 'Release' });
            if (!r.isConfirmed) return;
            await rowPost('/vehicle_day', { vehicle_seq: seq, day_no: day, retained: 0, note: r.value || '' }, 'Released.');
        }));
        document.querySelectorAll('#detail_cols [data-keep]').forEach(b => b.addEventListener('click', async () => {
            const [seq, day] = b.dataset.keep.split(':');
            await rowPost('/vehicle_day', { vehicle_seq: seq, day_no: day, retained: 1 }, 'Kept on.');
        }));

        loadExcursions().then(list => {
            const sel = document.getElementById('d_excsel');
            if (!sel) return;
            sel.innerHTML = '<option value="">—</option>' + list.map(x => `<option value="${esc(x.code)}">${esc(x.name)} · ${esc(x.currency)} ${Number(x.rate).toLocaleString()} ${esc(UNITL[x.unit] || x.unit)}${Number(x.includes_transport) === 1 ? ' · transport included' : ''}</option>`).join('');
            if (!list.length) { sel.innerHTML = '<option value="">The Excursion Rates master is empty</option>'; sel.disabled = true; }
        });
        document.getElementById('d_excadd')?.addEventListener('click', () => {
            const code = document.getElementById('d_excsel').value;
            if (!code) { toast('error', 'Choose an excursion from the master first.'); return; }
            rowPost('/excursion', { code, day_no: document.getElementById('d_excday').value, group_id: document.getElementById('d_excgrp').value || null }, 'Excursion added.');
        });
        // Entrance & Activities: open a line on its day, settle an unsure one in one click, add a place the day names
        document.getElementById('d_eaadd')?.addEventListener('click', () => openServices((it.days || []).length ? Number(it.days[0].day_no) : 0));
        document.querySelectorAll('#detail_cols [data-eaedit]').forEach(b => b.addEventListener('click', () => { const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(b.dataset.eaedit)); if (sv) openServices(Number(sv.day_no), Number(sv.id)); }));
        wireMeals();   // schema 40: the Meals card
        document.querySelectorAll('#detail_cols [data-eanocharge]').forEach(b => b.addEventListener('click', () => svcQuick(Number(b.dataset.eanocharge), { pay_mode: 'no_charge' })));
        document.querySelectorAll('#detail_cols [data-eaaccept]').forEach(b => b.addEventListener('click', () => svcQuick(Number(b.dataset.eaaccept), { fee_code: b.dataset.code })));
        document.querySelectorAll('#detail_cols [data-eaadd]').forEach(b => b.addEventListener('click', () => rowPost('/service', { day_no: b.dataset.eaadd, title: b.dataset.title, kind: '', pay_mode: 'unknown', fee_code: b.dataset.code }, 'Added.')));
        // the "Use <entry>" offers name the entry once the list is here (until then they carry its code): drawn again then
        if (MASTER === null) loadMaster().then(() => { if (document.querySelector('#detail_cols [data-eaaccept]')) renderDetail(); });
        document.querySelectorAll('#detail_cols [data-exc]').forEach(b => b.addEventListener('click', () => openExc(Number(b.dataset.exc))));
        document.querySelectorAll('#detail_cols [data-delexc]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await ask('Remove this excursion from the quotation?', 'Remove')) return;
            await rowPost('/excursion/' + b.dataset.delexc, { delete: 1 }, 'Excursion removed.');
        }));

        document.getElementById('d_go')?.addEventListener('click', goForQuotation);
        document.querySelectorAll('#detail_cols [data-issue]').forEach(b => b.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Issue this version?', input: 'text', inputPlaceholder: 'Note (optional) — e.g. sent by email', text: 'It becomes the quotation on record and the inquiry is marked quoted. An earlier issued version is kept as history.', showCancelButton: true, confirmButtonText: 'Issue' });
            if (!r.isConfirmed) return;
            await rowPost('/quote/' + b.dataset.issue + '/issue', { note: r.value || '' }, 'Issued.');
            loadList();
        }));
        document.querySelectorAll('#detail_cols [data-discard]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await ask('Throw this draft away? Go for Quotation makes a new one whenever you like.', 'Throw it away')) return;
            await rowPost('/quote/' + b.dataset.discard + '/discard', {}, 'Draft discarded.');
        }));
        if (SEES_PRICING) loadPricing();

        document.getElementById('d_addday')?.addEventListener('click', async () => {
            const td = CURRENT.tour_dates || {};
            const nos = (CURRENT.days || []).concat(CURRENT.departure_rows || []).map(d => Number(d.day_no));
            const next = (nos.length ? Math.max(...nos) : 0) + 1;
            if (td.nights != null && next > td.nights) {
                // schema 32: the dates decide the nights — a night past them moves the departure, said first
                if (!await ask(`The dates give ${td.nights} night${td.nights === 1 ? '' : 's'}, so a night ${next} would fall on ${fmtDate(td.departure)}, the departure day. Adding it moves the departure to ${fmtDate(addDaysIso(td.arrival, next))}. Add the night?`, 'Add the night')) return;
                return rowPost('/day', { extend: 1 }, 'Night added; the departure moved a day later.');
            }
            rowPost('/day', {}, 'Night added.');
        });
        // schema 32: the departure day and the dates
        document.querySelectorAll('#detail_cols [data-depmake]').forEach(b => b.addEventListener('click', async () => {
            if (!await ask('Make this line the departure day? Its visits and notes move to the departure day, and the line itself goes — nothing on it is lost, and the history keeps it.', 'Make it the departure day')) return;
            rowPost('/day/' + b.dataset.depmake + '/departure', {}, 'It is the departure day now.');
        }));
        document.querySelectorAll('#detail_cols [data-depextra]').forEach(b => b.addEventListener('click', async () => {
            const row = (CURRENT.departure_rows || []).find(d => String(d.id) === String(b.dataset.depextra));
            const td = CURRENT.tour_dates || {};
            const to = row && td.arrival ? fmtDate(addDaysIso(td.arrival, Number(row.day_no))) : 'the next day';
            if (!await ask(`Keep night ${row ? row.day_no : ''} as a night? The departure moves to ${to}, and the hotels and legs follow.`, 'Keep it as a night')) return;
            rowPost('/day/' + b.dataset.depextra + '/extra_night', {}, 'Kept as a night; the departure moved.');
        }));
        document.getElementById('d_addmissing')?.addEventListener('click', () => rowPost('/nights_missing', {}, 'The missing nights are on the itinerary.'));
        document.getElementById('d_datekeep')?.addEventListener('click', async () => {
            if (!await ask('Keep the dates on this inquiry, although the email says otherwise? Your name goes on the confirmation, and it is asked again if either date changes.', 'Keep these dates')) return;
            rowPost('/dates', { action: 'keep' }, 'These dates are confirmed.');
        });
        document.getElementById('d_dateemail')?.addEventListener('click', async () => {
            // schema 33: a date an Edit Request settled stays; only the one still in question is taken
            const dc = CURRENT.dates_check || {}, one = (dc.open || []).length === 1 ? dc.open[0] : null;
            if (!await ask(one ? `Put the email's ${one} date on this inquiry? The ${one === 'arrival' ? 'departure' : 'arrival'} stays as the Edit Request settled it. The nights move with the dates and every hotel is checked again.` : 'Put the email\'s dates on this inquiry? The nights move with the arrival and every hotel is checked again on the new dates.', one ? `Use the email's ${one}` : 'Use the email\'s dates')) return;
            rowPost('/dates', { action: 'email' }, one ? `The email's ${one} date is on the inquiry.` : 'The email\'s dates are on the inquiry.');
        });
        document.querySelector('#detail_cols [data-depnote]')?.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Departure day note', input: 'textarea', inputValue: (CURRENT.departure_day && CURRENT.departure_day.note) || '', inputPlaceholder: 'For the desks — e.g. late check-out until 18:00, lunch at the hotel', showCancelButton: true, confirmButtonText: 'Save' });
            if (!r.isConfirmed) return;
            rowPost('/departure', { note: r.value || '' }, 'Departure day saved.');
        });
        document.querySelector('#detail_cols [data-depwording]')?.addEventListener('click', async () => {
            const cur = (CURRENT.departure_day && CURRENT.departure_day.text) || '';
            const std = CURRENT.itinerary && CURRENT.itinerary.departure ? CURRENT.itinerary.departure.standard : '';
            const r = await Swal.fire({ title: 'The departure day, in your own words', input: 'textarea', inputValue: cur || std, inputPlaceholder: std, text: 'Leave it empty to use the standard sentence.', showCancelButton: true, confirmButtonText: 'Save' });
            if (!r.isConfirmed) return;
            const v = (r.value || '').trim();
            rowPost('/departure', { text: v === std ? '' : v }, 'Departure day saved.');
        });
        document.getElementById('d_addpax')?.addEventListener('click', () => openPax(null));
        document.getElementById('d_addreq')?.addEventListener('click', () => openReq(null));
        document.querySelectorAll('#detail_cols [data-editpax]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); openPax(Number(b.dataset.editpax)); }));
        [['delday','/day/','this night, and the services on it'], ['delpax','/pax/','this name'], ['delreq','/request/','this request']]
            .forEach(([attr, path, what]) => document.querySelectorAll(`#detail_cols [data-${attr}]`).forEach(b => b.addEventListener('click', async e => {
                e.stopPropagation();
                if (!await ask('Remove ' + what + '?', 'Remove')) return;
                await rowPost(path + b.dataset[attr], { delete: 1 }, 'Removed.');
            })));

        // ---------- vehicle and crew ----------
        document.getElementById('d_autoveh')?.addEventListener('click', () => rowPost('/vehicle', { auto: 1 }));

        // accommodation: the switch is the decision, the rest only appears once it is on
        document.querySelectorAll('#detail_cols [data-accom]').forEach(b => b.addEventListener('change', () =>
            rowPost('/accom', { kind: b.dataset.accom, required: b.checked ? 1 : 0 })));
        document.querySelectorAll('#detail_cols [data-accomsave]').forEach(b => b.addEventListener('click', () => {
            const k = b.dataset.accomsave;
            rowPost('/accom', {
                kind: k,
                nights: document.querySelector(`#detail_cols [data-accomnights="${k}"]`).value,
                rate: document.querySelector(`#detail_cols [data-accomrate="${k}"]`).value,
                note: document.querySelector(`#detail_cols [data-accomnote="${k}"]`).value
            });
        }));

        // driver allocation and guide allocation: add, change (type, language, days, person),
        // release the person, remove the line; Auto re-plans both sections from the agent's market
        document.getElementById('d_autocrew')?.addEventListener('click', () => rowPost('/crew', { auto: 1 }));
        document.querySelectorAll('#detail_cols [data-crewadd]').forEach(b => b.addEventListener('click', () => openCrew(b.dataset.crewadd, null)));
        document.querySelectorAll('#detail_cols [data-crewchange]').forEach(b => b.addEventListener('click', () => {
            const line = (CURRENT.crew || []).find(c => c.role_code === b.dataset.crewchange);
            if (line) openCrew(line.kind, line);
        }));
        document.querySelectorAll('#detail_cols [data-crewrelease]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            const line = (CURRENT.crew || []).find(c => c.role_code === b.dataset.crewrelease);
            if (!line) return;
            if (!await ask('Take ' + (line.person_name || 'this person') + ' off the line? The type, language and days stay; the bata stays the master\'s.', 'Release')) return;
            await rowPost('/crew', { code: line.role_code, supplier_acc: '', days: line.days_by === 'auto' ? '' : (line.days ?? ''), note: line.note || '' }, 'Released.');
        }));
        document.querySelectorAll('#detail_cols [data-delcrew]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await ask('Are you sure you want to delete this record? The line comes off the quotation and its bata off the costing. The planner will not put it back; Auto starts afresh.', 'Remove')) return;
            await rowPost('/crew', { code: b.dataset.delcrew, delete: 1 });
        }));

        // A failed read is a message and two buttons, never a dead end.
        document.getElementById('b_manual')?.addEventListener('click', () => {
            document.getElementById('d_banner').innerHTML = '';
            toast('success', 'Carry on by hand — every field below is yours to fill in.');
        });
        document.getElementById('b_retry')?.addEventListener('click', () => document.getElementById('d_reread')?.click());
        document.getElementById('d_back').addEventListener('click', () => { setView('view_list'); loadList(); });
        document.getElementById('d_quoted')?.addEventListener('click', () => setStatus('quoted'));
        document.getElementById('d_followup_ok')?.addEventListener('click', () => rowPost('/follow_up', { on: document.getElementById('d_followup').value, note: document.getElementById('d_followup_note').value }, 'Follow-up set.'));
        document.getElementById('d_followup_clear')?.addEventListener('click', () => rowPost('/follow_up', { on: '' }, 'Follow-up cleared.'));
        document.getElementById('d_reopen')?.addEventListener('click', () => setStatus('working'));
        document.getElementById('d_decline')?.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'Decline this inquiry?', text: 'It leaves the open list and keeps everything on it. It can be brought back to working later.', showCancelButton: true, confirmButtonText: 'Decline' });
            if (r.isConfirmed) setStatus('declined');
        });
        document.getElementById('d_confirm')?.addEventListener('click', confirmTour);
        document.getElementById('d_recheck')?.addEventListener('click', async () => {
            const j = await post('/intake/' + CURRENT.id + '/recheck', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); toast('success', j.message);
        });
        document.getElementById('d_takenew')?.addEventListener('click', async () => {
            const n = (CURRENT.days || []).filter(d => d.rate_drift).length;
            const ok = await Swal.fire({ icon: 'question', title: 'Use the new rates?', text: n + ' night(s) will take the published rate as Hotel Rates gives it today. The figures quoted so far are replaced; the change is written to the history.', showCancelButton: true, confirmButtonText: 'Use the new rates' });
            if (!ok.isConfirmed) return;
            const j = await post('/intake/' + CURRENT.id + '/recheck', { take_new_rates: true });
            if (j.status !== 200) { toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); toast('success', j.message);
        });
        document.getElementById('d_reread')?.addEventListener('click', async () => {
            const j = await post('/intake/' + CURRENT.id + '/read', {});
            if (j.status !== 200) { toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); toast('success', j.message);
        });
        document.getElementById('d_checklist')?.addEventListener('click', () => { CHECK.fixed = 0; openChecklist(); });
        // a save re-renders: if the checklist is being worked through, it comes back by itself
        checklistAfterRender();
    }

    // =====================================================================
    //  Edit Request (schema 33) — below the original email. The words that
    //  need to change, and what they should say now: TourFlow finds the working
    //  values, shows Current → New with everything that follows, and changes
    //  them only when the colleague confirms. The email itself never changes.
    // =====================================================================
    const EDIT_AUTHORITY = <?= !empty($edit_authority) ? 'true' : 'false' ?>;
    let ER = { requests: [], authority: EDIT_AUTHORITY, standing: 'open', why: '', loadedFor: null, error: null };
    const ER_STATUS = { open: ['pill-amber', 'being checked'], waiting: ['pill-blue', 'waiting for amendment'], applied: ['pill-green', 'applied'], cancelled: ['pill-grey', 'withdrawn'] };
    const erCut = (s, n) => { s = String(s || '').replace(/\s+/g, ' ').trim(); return s.length > n ? s.slice(0, n - 1) + '…' : s; };
    const erWhen = s => s ? String(s).slice(0, 16) : '';

    // where the inquiry stands for Edit Requests: the server's word, read with the inquiry
    function erStanding() {
        const s = (CURRENT && CURRENT.edit_requests) || null;
        return s ? { state: s.standing, why: s.standing_why } : { state: ER.standing, why: ER.why };
    }

    async function loadEditRequests() {
        if (!CURRENT) return;
        const id = CURRENT.id;
        const j = await api('/intake/' + id + '/edit_requests');
        if (!CURRENT || CURRENT.id !== id) return;
        if (j.status !== 200) { ER.error = j.message; ER.loadedFor = id; renderEditCard(); return; }
        erTake(j.data);
        renderEditCard();
    }
    function erTake(d) {
        ER.requests = d.requests || ER.requests;
        if ('authority' in d) ER.authority = !!d.authority;
        if (d.standing) { ER.standing = d.standing; ER.why = d.why || ''; }
        ER.loadedFor = CURRENT ? CURRENT.id : null;
        ER.error = null;
        if (CURRENT && d.summary) CURRENT.edit_requests = d.summary;
    }

    function renderEditCard() {
        const card = document.getElementById('er_card');
        if (!card || !CURRENT) return;
        card.style.display = '';
        if (ER.loadedFor !== CURRENT.id) {
            ER.requests = []; ER.error = null;
            document.getElementById('er_body').innerHTML = '<div class="help">Loading…</div>';
            loadEditRequests();
            return;
        }
        const body = document.getElementById('er_body');
        const meta = document.getElementById('er_meta');
        if (ER.error) { body.innerHTML = `<div class="help">${esc(ER.error)}</div>`; meta.textContent = ''; return; }
        const { state, why } = erStanding();
        const keep = id => document.getElementById(id)?.value || '';
        const draft = { p: keep('er_pasted'), c: keep('er_change'), r: keep('er_reason') };
        let top = '';
        if (state === 'closed') {
            top = `<div class="ernote">${esc(why)}</div>`;
        } else if (!ER.authority) {
            top = `<div class="ernote warn" data-er-noauth><i class="bi bi-lock"></i> Edit Requests need <b>Edit Request Authority</b>, which Management turns on for each person under Tour Desk Admin → Edit Requests. You can see the requests on this inquiry below, but not make or confirm one.</div>`;
        } else {
            const locked = state === 'locked';
            top = `${locked ? `<div class="ernote warn" data-er-locked><i class="bi bi-lock"></i> ${esc(why)}</div>` : ''}
                <div class="erlab">Copy / paste what needs to be edited <a href="#" class="help ms-auto" id="er_take" style="text-transform:none;letter-spacing:0;font-weight:400" title="Select the words in the email above, then press this">take the words selected in the email</a></div>
                <textarea class="form-control" id="er_pasted" rows="3" maxlength="4000" placeholder="The words as they stand — from the email, or as the inquiry shows them.">${esc(draft.p)}</textarea>
                <div class="erlab mt-2">Change to</div>
                <textarea class="form-control" id="er_change" rows="3" maxlength="4000" placeholder="The same words as they should read now. Leave it empty to take them out.">${esc(draft.c)}</textarea>
                <input class="form-control form-control-sm mt-2" id="er_reason" maxlength="1000" placeholder="${locked ? 'Why it is needed — required: Management sees it on the Tour Board' : 'Why (optional) — the customer’s message, say'}" value="${esc(draft.r)}">
                <div class="d-flex gap-2 mt-2 align-items-center flex-wrap">
                    <button class="btn ${locked ? 'btn-light' : 'btn-primary'} btn-sm" id="er_submit">${locked ? '<i class="bi bi-hourglass-split"></i> Save as Waiting for Amendment' : '<i class="bi bi-search"></i> Check the change'}</button>
                    <span class="help">${locked ? 'Nothing on the confirmed tour changes.' : 'Nothing is changed until you confirm.'} The email is never changed.</span>
                </div>`;
        }
        const reqs = ER.requests || [];
        const rows = reqs.map(r => {
            const [cls, word] = ER_STATUS[r.status] || ['pill-grey', r.status];
            const who = r.status === 'applied' ? `confirmed by ${esc(r.confirmed_by || '')} · ${erWhen(r.confirmed_at)}`
                : (r.status === 'cancelled' ? `withdrawn by ${esc(r.cancelled_by || '')} · ${erWhen(r.cancelled_at)}` : `asked by ${esc(r.requested_by || '')} · ${erWhen(r.requested_at)}`);
            const live = ['open', 'waiting'].includes(r.status);
            const acts = [];
            if (live && ER.authority && state === 'open') acts.push(`<button class="btn btn-primary btn-sm py-0" data-er-open="${r.id}">${r.status === 'waiting' ? 'Check it now' : 'Continue'}</button>`);
            else acts.push(`<button class="btn btn-light btn-sm py-0" data-er-view="${r.id}">View</button>`);
            if (live && ER.authority && state !== 'closed') acts.push(`<button class="btn btn-light btn-sm py-0" data-er-cancel="${r.id}" title="Withdraw it: nothing is changed">Withdraw</button>`);
            return `<div class="errow" data-er-row="${r.id}"><b>#${r.seq}</b>
                <div><span class="pill ${cls}">${word}</span> <span class="words"><b>“${esc(erCut(r.pasted_text, 90))}”</b> → “${esc(erCut(r.change_to, 90)) || '<i>taken out</i>'}”</span>
                    ${r.reason ? `<div class="help">Why: ${esc(r.reason)}</div>` : ''}<div class="help">${who}</div></div>
                <div class="d-flex gap-1 flex-wrap justify-content-end">${acts.join('')}</div></div>`;
        }).join('');
        const sum = CURRENT.edit_requests || {};
        const counts = sum.counts || {};
        meta.textContent = reqs.length ? `${reqs.length} request${reqs.length === 1 ? '' : 's'}${counts.applied ? ' · ' + counts.applied + ' applied' : ''}${counts.waiting ? ' · ' + counts.waiting + ' waiting' : ''}` : '';
        body.innerHTML = top + (reqs.length ? `<div class="erlist">${rows}</div>` : (state === 'closed' || !ER.authority ? '<div class="help">No Edit Request on this inquiry.</div>' : ''));

        document.getElementById('er_take')?.addEventListener('click', e => {
            e.preventDefault();
            const sel = window.getSelection ? String(window.getSelection()) : '';
            const pane = document.getElementById('mailpane');
            const inPane = sel && pane && window.getSelection().anchorNode && pane.contains(window.getSelection().anchorNode);
            if (!sel.trim() || !inPane) { toast('info', 'Select the words in the email above first, then press this.'); return; }
            document.getElementById('er_pasted').value = sel.trim();
            const ch = document.getElementById('er_change');
            if (!ch.value.trim()) ch.value = sel.trim();
            ch.focus();
        });
        document.getElementById('er_submit')?.addEventListener('click', submitEditRequest);
        body.querySelectorAll('[data-er-open]').forEach(b => b.addEventListener('click', () => checkEditRequest(Number(b.dataset.erOpen), {})));
        body.querySelectorAll('[data-er-view]').forEach(b => b.addEventListener('click', () => { const r = (ER.requests || []).find(x => x.id === Number(b.dataset.erView)); if (r) { renderEditModal(r, ''); show('#m_er'); } }));
        body.querySelectorAll('[data-er-cancel]').forEach(b => b.addEventListener('click', () => cancelEditRequest(Number(b.dataset.erCancel))));
    }

    async function submitEditRequest() {
        const pasted = document.getElementById('er_pasted').value, change = document.getElementById('er_change').value, reason = document.getElementById('er_reason').value;
        if (!pasted.trim()) { toast('error', 'Paste the words that need to change into the first box.'); return; }
        const btn = document.getElementById('er_submit');
        const was = btn.innerHTML; btn.disabled = true; btn.innerHTML = 'Checking…';
        const j = await post('/intake/' + CURRENT.id + '/edit_request', { pasted, change_to: change, reason });
        btn.disabled = false; btn.innerHTML = was;
        if (j.status !== 200) { if (j.no_authority) { ER.authority = false; renderEditCard(); } toast('error', j.message); return; }
        document.getElementById('er_pasted').value = ''; document.getElementById('er_change').value = ''; document.getElementById('er_reason').value = '';
        erTake(j.data);
        renderEditCard();
        if (j.data.request && j.data.request.status === 'waiting') { toast('success', j.message); return; }
        renderEditModal(j.data.request, j.message);
        show('#m_er');
    }

    async function checkEditRequest(reqId, answers) {
        const j = await post('/intake/' + CURRENT.id + '/edit_request/' + reqId + '/check', { answers });
        if (j.status !== 200) { if (j.no_authority) { ER.authority = false; renderEditCard(); } toast('error', j.message); return; }
        erTake(j.data);
        renderEditCard();
        renderEditModal(j.data.request, j.message);
        show('#m_er');
    }

    async function cancelEditRequest(reqId) {
        const r = (ER.requests || []).find(x => x.id === reqId);
        const res = await Swal.fire({ icon: 'question', title: 'Withdraw Edit Request' + (r ? ' #' + r.seq : '') + '?', text: 'Nothing on the inquiry is changed. It stays in the list, and in the history, as withdrawn.', input: 'text', inputPlaceholder: 'Why (optional)', showCancelButton: true, confirmButtonText: 'Withdraw', cancelButtonText: 'Keep it' });
        if (!res.isConfirmed) return;
        const j = await post('/intake/' + CURRENT.id + '/edit_request/' + reqId + '/cancel', { note: res.value || '' });
        if (j.status !== 200) { toast('error', j.message); return; }
        erTake(j.data);
        hide('#m_er');
        renderEditCard();
        loadHistory();
        toast('success', j.message);
    }

    async function confirmEditRequest(r) {
        const btn = document.getElementById('mer_confirm');
        if (btn) { btn.disabled = true; btn.innerHTML = 'Applying…'; }
        const j = await post('/intake/' + CURRENT.id + '/edit_request/' + r.id + '/confirm', { fingerprint: r.fingerprint || '' });
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirm and apply'; }
        if (j.status !== 200) { if (j.no_authority) { ER.authority = false; renderEditCard(); } toast('error', j.message); return; }
        erTake(j.data);
        if (j.data.stale || !j.data.applied) {
            // the inquiry moved since the check: the change as it stands now, to be looked at again
            renderEditCard();
            renderEditModal(j.data.request, j.message, true);
            return;
        }
        hide('#m_er');
        if (j.data.inquiry) CURRENT = j.data.inquiry;
        renderDetail();
        toast('success', j.message);
    }

    // one row of Current → New
    function erRow(i) {
        const same = !!i.same;
        return `<tr><td>${esc(i.scope || '')}</td><td>${esc(i.label || '')}</td>
            <td class="${same ? '' : 'from'}">${esc(i.from ?? '—')}</td>
            <td class="${same ? 'same' : 'to'}">${same ? 'already so — nothing changes; recorded' : esc(i.to ?? '—')}${i.note ? `<span class="note">${esc(i.note)}</span>` : ''}</td></tr>`;
    }
    function erConnected(list) {
        if (!(list || []).length) return '<div class="help">Nothing else moves.</div>';
        const groups = {};
        list.forEach(c => { (groups[c.group] = groups[c.group] || []).push(c); });
        return Object.entries(groups).map(([g, cs]) => `<table class="ercmp"><thead><tr><th style="width:34%">${esc(g)}</th><th>Current</th><th>New</th></tr></thead><tbody>
            ${cs.map(c => `<tr><td>${esc(c.what)}</td><td class="from">${esc(c.from)}</td><td class="to">${esc(c.to)}</td></tr>`).join('')}</tbody></table>`).join('');
    }
    const erMoney = v => v === null || v === undefined ? '—' : 'USD ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function renderEditModal(r, message, stale) {
        if (!r) return;
        const [cls, word] = ER_STATUS[r.status] || ['pill-grey', r.status];
        document.getElementById('mer_title').innerHTML = `Edit Request #${r.seq} <span class="pill ${cls}">${word}</span> <span class="help">· ${esc(CURRENT.inquiry_no || '')}</span>`;
        const { state } = erStanding();
        const words = `<div class="erwords"><div><div class="erlab">Copy / paste what needs to be edited</div><pre>${esc(r.pasted_text)}</pre></div>
            <div><div class="erlab">Change to</div><pre>${r.change_to ? esc(r.change_to) : '<i>(empty — taken out)</i>'}</pre></div></div>
            ${r.reason ? `<div class="help mb-2">Why: ${esc(r.reason)}</div>` : ''}
            <div class="help mb-2">Asked by ${esc(r.requested_by || '')} · ${erWhen(r.requested_at)}${r.was_locked == 1 ? ' · saved while the tour was confirmed and locked' : ''}</div>`;
        let html = (message ? `<div class="ernote ${stale ? 'warn' : ''}" id="mer_msg">${esc(message)}</div>` : '') + words;
        const foot = [];
        const can = ER.authority && state === 'open';
        if (r.status === 'applied') {
            const res = r.result || {};
            html += `<div class="ernote" style="background:var(--tf-green-soft, #e7f6ee)"><i class="bi bi-check2-circle"></i> Applied — confirmed by <b>${esc(r.confirmed_by || '')}</b> on ${erWhen(r.confirmed_at)}. The history of the inquiry has it, with every value that moved.</div>`;
            html += `<div class="ersec">What was changed</div><table class="ercmp"><thead><tr><th>Where</th><th>What</th><th>Was</th><th>Now</th></tr></thead><tbody>${(res.intents || []).map(erRow).join('')}</tbody></table>`;
            html += `<div class="ersec">What followed from it</div>${erConnected(res.connected)}`;
            if ((res.follow_ups || []).length) html += `<div class="ersec">Left to do after it</div>${res.follow_ups.map(x => `<div class="ernote warn">${esc(x)}</div>`).join('')}`;
            if ((res.cleared || []).length) html += `<div class="ersec">Settled by it</div>${res.cleared.map(x => `<div class="ernote">${esc(x)}</div>`).join('')}`;
        } else if (r.status === 'cancelled') {
            html += `<div class="ernote">Withdrawn by ${esc(r.cancelled_by || '')} on ${erWhen(r.cancelled_at)}${r.cancel_note ? ' — ' + esc(r.cancel_note) : ''}. Nothing was changed.</div>`;
        } else if (r.status === 'waiting' && state === 'locked') {
            html += `<div class="ernote warn"><i class="bi bi-hourglass-split"></i> Waiting for amendment. It changes nothing on the confirmed tour. Management sees it, with its reason, on the Tour Board; once the tour is opened with <b>Amend</b>, it is checked here and confirmed like any other.</div>`;
        } else {
            const p = r.proposal || null;
            if (!p) {
                html += `<div class="help">Not checked yet.</div>`;
                if (can) foot.push(`<button class="btn btn-primary btn-sm" id="mer_recheck">Check it now</button>`);
            } else if (p.outcome === 'question') {
                const qs = p.questions || [];
                const words = qs.filter(q => !(q.options || []).length);   // these need the words put right, not an answer
                html += `<div class="ersec">${words.length ? 'To put right first' : 'To answer first'} — nothing is changed until then</div>` + qs.map((q, n) => (q.options || []).length
                    ? `<div class="erq" data-q="${esc(q.id)}"><div style="font-size:12.5px;font-weight:600;margin-bottom:4px">${esc(q.text)}</div>
                        ${q.options.map((o, k) => `<label><input type="radio" name="erq_${n}" value="${esc(o.value)}" ${q.options.length === 1 ? 'checked' : ''}> <span>${esc(o.label)}</span></label>`).join('')}</div>`
                    : `<div class="ernote warn" data-q-words="${esc(q.id)}">${esc(q.text)}</div>`).join('');
                if ((p.intents || []).length) html += `<div class="ersec">Already understood</div><table class="ercmp"><thead><tr><th>Where</th><th>What</th><th>Current</th><th>New</th></tr></thead><tbody>${p.intents.map(erRow).join('')}</tbody></table>`;
                if (can && words.length) foot.push(`<button class="btn btn-primary btn-sm" id="mer_fix" title="This request is withdrawn and its words go back into the boxes below the email, to be put right and checked again"><i class="bi bi-pencil"></i> Put the words right</button>`);
                else if (can) foot.push(`<button class="btn btn-primary btn-sm" id="mer_answer"><i class="bi bi-arrow-repeat"></i> Check again with these answers</button>`);
            } else if (p.outcome === 'refused') {
                html += `<div class="ernote stop">Nothing here can be changed by this Edit Request.</div>`;
                if (can) foot.push(`<button class="btn btn-light btn-sm" id="mer_fix" title="This request is withdrawn and its words go back into the boxes below the email"><i class="bi bi-pencil"></i> Put the words right</button>`);
            } else {
                const changes = (p.intents || []).filter(i => !i.same);
                const where = p.located === 'email' ? 'Found in the email; the working values those words stand for are below.' : (p.located === 'values' ? 'Matched against the inquiry’s own values.' : '');
                if (where) html += `<div class="help mb-2">${where}</div>`;
                html += `<div class="ersec">What changes${changes.length ? '' : ' — nothing: the inquiry already says this'}</div><table class="ercmp"><thead><tr><th style="width:16%">Where</th><th style="width:16%">What</th><th>Current</th><th>New</th></tr></thead><tbody>${(p.intents || []).map(erRow).join('')}</tbody></table>`;
                if ((p.errors || []).length) html += p.errors.map(x => `<div class="ernote stop"><i class="bi bi-x-octagon"></i> ${esc(x)}</div>`).join('');
                html += `<div class="ersec">What follows from it — worked out by the same rules as a change by hand</div>${erConnected(p.connected)}`;
                if ((p.follow_ups || []).length) html += `<div class="ersec">Still to do after it</div>${p.follow_ups.map(x => `<div class="ernote warn">${esc(x)}</div>`).join('')}`;
                if ((p.cleared || []).length) html += `<div class="ersec">Settled by it</div>${p.cleared.map(x => `<div class="ernote">${esc(x)}</div>`).join('')}`;
                if (p.cost) html += `<div class="help mt-2">Cost of the tour: <b>${erMoney(p.cost.before)}</b> → <b>${erMoney(p.cost.after)}</b> · items that stop the quotation: ${p.cost.blockers_before} → ${p.cost.blockers_after}</div>`;
                const issued = (CURRENT.quotes || []).filter(q => q.status === 'issued').sort((a, b) => b.version - a.version)[0];
                if (issued) html += `<div class="ernote warn mt-2"><i class="bi bi-info-circle"></i> Quotation version ${issued.version} was issued on ${erWhen(issued.issued_at)}. Once this is applied, Confirm Tour waits for a new version made and issued after it — whether or not the price moves.</div>`;
                if (can && !(p.errors || []).length && p.fingerprint) foot.push(`<button class="btn btn-success btn-sm" id="mer_confirm"><i class="bi bi-check2-circle"></i> Confirm and apply</button>`);
            }
            if (p && (p.refusals || []).length) html += `<div class="ersec">Not changed by an Edit Request</div>${p.refusals.map(x => `<div class="ernote stop">${esc(x)}</div>`).join('')}`;
            if (p && (p.notes || []).length) html += `<div class="ersec">Notes</div>${p.notes.map(x => `<div class="ernote">${esc(x)}</div>`).join('')}`;
            if (ER.authority && state !== 'closed') foot.push(`<button class="btn btn-light btn-sm" id="mer_cancel">Withdraw the request</button>`);
        }
        foot.push(`<button class="btn btn-light btn-sm" data-bs-dismiss="modal" id="mer_close">Close</button>`);
        document.getElementById('mer_body').innerHTML = html;
        document.getElementById('mer_foot').innerHTML = foot.join('');
        document.getElementById('mer_close')?.addEventListener('click', () => hide('#m_er'));
        document.getElementById('mer_confirm')?.addEventListener('click', () => confirmEditRequest(r));
        document.getElementById('mer_cancel')?.addEventListener('click', () => cancelEditRequest(r.id));
        document.getElementById('mer_recheck')?.addEventListener('click', () => checkEditRequest(r.id, {}));
        document.getElementById('mer_fix')?.addEventListener('click', async () => {
            // the request is withdrawn (it stays in the list and the history) and its words come back to be put right
            const j = await post('/intake/' + CURRENT.id + '/edit_request/' + r.id + '/cancel', { note: 'Withdrawn to put the words right; made again as a new request.' });
            if (j.status !== 200) { toast('error', j.message); return; }
            erTake(j.data);
            hide('#m_er');
            renderEditCard();
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
            set('er_pasted', r.pasted_text); set('er_change', r.change_to); set('er_reason', r.reason);
            document.getElementById('er_change')?.focus();
            document.getElementById('er_card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        document.getElementById('mer_answer')?.addEventListener('click', () => {
            const answers = {};
            let missing = 0;
            document.querySelectorAll('#mer_body .erq').forEach((box, n) => {
                const v = box.querySelector('input[type=radio]:checked');
                if (v) answers[box.dataset.q] = v.value; else missing++;
            });
            if (missing) { toast('error', 'Answer every question first.'); return; }
            checkEditRequest(r.id, answers);
        });
    }

    // Which copy of the email the pane shows: the working copy (what the reader gets) or,
    // once corrected, the original as pasted on request.
    let MAIL_SHOW_ORIGINAL = false;
    function renderMail(quote) {
        const working  = CURRENT.source_text || '';
        const original = CURRENT.source_text_original || '';
        const edited   = !!CURRENT.source_edited && original.trim() !== '' && original !== working;
        const text     = (edited && MAIL_SHOW_ORIGINAL) ? original : working;
        const pane = document.getElementById('mailpane');
        const box  = document.getElementById('mail_attach');
        const tools = document.getElementById('mail_tools');
        const editor = document.getElementById('mail_editor');

        // An inquiry typed in by hand has no email, and that is a normal state, not a
        // failure: the pane offers to take one later instead of sitting there empty.
        if (!working.trim()) {
            document.getElementById('mail_head').textContent = 'No email on this inquiry';
            document.getElementById('mail_meta').textContent = 'entered by hand';
            pane.style.display = 'none';
            tools.style.display = 'none';
            editor.style.display = 'none';
            box.style.display = '';
            return;
        }
        document.getElementById('mail_head').textContent = edited ? (MAIL_SHOW_ORIGINAL ? 'Original email, as pasted' : 'Email — corrected copy') : 'Original email';
        pane.style.display = '';
        box.style.display = 'none';
        editor.style.display = 'none';
        tools.style.display = '';
        const note = document.getElementById('mail_edit_note');
        const tog  = document.getElementById('mail_toggle');
        if (edited) {
            note.textContent = 'Corrected' + (CURRENT.source_edited_by ? ' by ' + staffName(CURRENT.source_edited_by) : '') + (CURRENT.source_edited_at ? ' on ' + String(CURRENT.source_edited_at).slice(0, 16) : '') + '. The original is kept as pasted; the reader gets the corrected copy.';
            tog.style.display = '';
            tog.textContent = MAIL_SHOW_ORIGINAL ? 'Show the corrected copy' : 'Show the original';
        } else {
            note.textContent = 'Exactly as pasted.';
            tog.style.display = 'none';
        }
        // schema 33: once an Edit Request is applied, a new reading could undo it — it is switched off
        const er = CURRENT.edit_requests || null;
        if (er && er.read_blocked && er.last_applied) {
            note.textContent += ' Reading it again is switched off: Edit Request #' + er.last_applied.seq + ' was applied' + (er.last_applied.by ? ' by ' + er.last_applied.by : '') + '. Further changes go through an Edit Request below.';
        }
        const rb = !!(er && er.read_blocked);
        ['me_read', 'a_read'].forEach(id => { const b = document.getElementById(id); if (b) b.style.display = rb ? 'none' : ''; });
        document.getElementById('mail_edit').style.display = CURRENT.locked ? 'none' : '';

        if (!quote) { pane.textContent = text; return; }
        const norm = s => s.replace(/[‘’]/g, "'").replace(/[“”]/g, '"').replace(/\s+/g, ' ').toLowerCase();
        const i = norm(text).indexOf(norm(quote));
        if (i < 0) { pane.textContent = text; return; }
        // walk the original text counting normalised characters, so the highlight lands on the real span
        let seen = 0, start = -1, end = -1, prevSpace = false;
        for (let p = 0; p < text.length; p++) {
            const isSpace = /\s/.test(text[p]);
            if (isSpace && prevSpace) { continue; }
            if (seen === i && start < 0) start = p;
            seen++;
            prevSpace = isSpace;
            if (seen === i + norm(quote).length) { end = p + 1; break; }
        }
        if (start < 0) { pane.textContent = text; return; }
        if (end < 0) end = text.length;
        pane.innerHTML = esc(text.slice(0, start)) + '<mark>' + esc(text.slice(start, end)) + '</mark>' + esc(text.slice(end));
        const m = pane.querySelector('mark');
        if (m) { const box = pane.parentElement; box.scrollTo({ top: m.offsetTop - box.clientHeight / 2 + 30, behavior:'smooth' }); }
    }

    // The email pane's own controls: the original / corrected toggle and the in-place
    // correction of the working copy, checked by the same rules as the paste step.
    document.getElementById('mail_toggle')?.addEventListener('click', e => { e.preventDefault(); MAIL_SHOW_ORIGINAL = !MAIL_SHOW_ORIGINAL; renderMail(null); });
    let ME_TIMER = null;
    async function openMailEditor() {
        if (!CURRENT || CURRENT.locked) return;
        document.getElementById('mailpane').style.display = 'none';
        document.getElementById('mail_tools').style.display = 'none';
        const ed = document.getElementById('mail_editor');
        ed.style.display = '';
        const ta = document.getElementById('me_text');
        ta.value = CURRENT.source_text || '';
        document.getElementById('me_note').value = '';
        await checkText(ta.value, document.getElementById('me_flags'), document.getElementById('me_dates'), CURRENT.source_received_at || '');
        ta.focus();
    }
    document.getElementById('mail_edit')?.addEventListener('click', openMailEditor);
    document.getElementById('me_cancel')?.addEventListener('click', () => renderMail(null));
    document.getElementById('me_recheck')?.addEventListener('click', () => checkText(document.getElementById('me_text').value, document.getElementById('me_flags'), document.getElementById('me_dates'), CURRENT.source_received_at || ''));
    document.getElementById('me_text')?.addEventListener('input', () => {
        clearTimeout(ME_TIMER);
        ME_TIMER = setTimeout(() => checkText(document.getElementById('me_text').value, document.getElementById('me_flags'), document.getElementById('me_dates'), CURRENT.source_received_at || ''), 700);
    });
    async function saveMailEdit(read) {
        const text = document.getElementById('me_text').value;
        if (!text.trim()) { toast('error', 'The text cannot be emptied.'); return; }
        const btn = document.getElementById(read ? 'me_read' : 'me_save');
        const was = btn.innerHTML; btn.disabled = true; btn.innerHTML = read ? 'Reading…' : 'Saving…';
        const j = await post('/intake/' + CURRENT.id + '/email_edit', { source_text: text, note: document.getElementById('me_note').value, read: !!read });
        btn.disabled = false; btn.innerHTML = was;
        if (j.status !== 200) { toast('error', j.message); return; }
        MAIL_SHOW_ORIGINAL = false;
        CURRENT = j.data; renderDetail(); toast('success', j.message);
    }
    document.getElementById('me_save')?.addEventListener('click', () => saveMailEdit(false));
    document.getElementById('me_read')?.addEventListener('click', () => saveMailEdit(true));

    // ---------- editors ----------
    // ---------- the room mix in three words ----------
    function roomsShort(m) {
        if (!m) return '';
        const p = [];
        [['sgl', 'SGL'], ['dbl', 'DBL'], ['twn', 'TWN'], ['tpl', 'TPL']].forEach(([k, l]) => { if (Number(m[k]) > 0) p.push(Number(m[k]) + ' ' + l); });
        return p.join(' · ');
    }

    // ---------- a passenger group ----------
    function openGroup(id) {
        const g = id ? (CURRENT.groups || []).find(x => Number(x.id) === id) : null;
        document.getElementById('mg_id').value = g ? g.id : '';
        document.getElementById('mg_title').textContent = g ? 'Group ' + g.code + ' — ' + g.name : 'Add a group';
        document.getElementById('mg_name').value = g ? g.name : '';
        document.getElementById('mg_pax').value = g ? g.pax : '';
        document.getElementById('mg_from').value = g && g.day_from ? g.day_from : '';
        document.getElementById('mg_to').value = g && g.day_to ? g.day_to : '';
        ['sgl', 'dbl', 'twn', 'tpl'].forEach(k => document.getElementById('mg_' + k).value = g ? g['rooms_' + k] : 0);
        document.getElementById('mg_note').value = g ? (g.note || '') : '';
        document.getElementById('mg_nights').textContent = CURRENT.nights ? 'Blank at both ends means the whole tour of ' + CURRENT.nights + ' nights. A group that leaves after night 4 has last night 4.' : 'Blank at both ends means the whole tour.';
        show('#m_group');
    }
    document.getElementById('mg_ok').addEventListener('click', async () => {
        const id = document.getElementById('mg_id').value;
        const ok = await rowPost('/group' + (id ? '/' + id : ''), {
            name: document.getElementById('mg_name').value, pax: document.getElementById('mg_pax').value,
            day_from: document.getElementById('mg_from').value, day_to: document.getElementById('mg_to').value,
            rooms_sgl: document.getElementById('mg_sgl').value, rooms_dbl: document.getElementById('mg_dbl').value,
            rooms_twn: document.getElementById('mg_twn').value, rooms_tpl: document.getElementById('mg_tpl').value,
            note: document.getElementById('mg_note').value
        });
        if (ok) hide('#m_group');
    });

    // ---------- a vehicle line: label, size, how many, which group, which nights ----------
    const vlineRow = v => ({ class_code: v.class_code, qty: v.qty, label: v.label, group_id: v.group_id, day_from: v.day_from, day_to: v.day_to });
    function openVline(id) {
        const v = id ? (CURRENT.vehicles || []).find(x => Number(x.id) === id) : null;
        loadCrew().then(c => {
            const sel = document.getElementById('mvl_class');
            sel.innerHTML = (c.classes || []).map(k => `<option value="${esc(k.code)}">${esc(k.name)} — up to ${k.max_pax} pax${k.seats ? ' · ' + k.seats + ' seats' : ''}</option>`).join('');
            if (v) sel.value = v.class_code;
        });
        const gsel = document.getElementById('mvl_group');
        gsel.innerHTML = '<option value="">The whole party</option>' + (CURRENT.groups || []).map(g => `<option value="${g.id}">${esc(g.name)} (${g.pax})</option>`).join('');
        gsel.value = v && v.group_id ? String(v.group_id) : '';
        document.getElementById('mvl_id').value = v ? v.id : '';
        document.getElementById('mvl_title').textContent = v ? 'Vehicle line — ' + (v.label || v.type) : 'Add a vehicle';
        document.getElementById('mvl_label').value = v ? (v.label || '') : '';
        document.getElementById('mvl_qty').value = v ? v.qty : 1;
        document.getElementById('mvl_from').value = v && v.day_from ? v.day_from : '';
        document.getElementById('mvl_to').value = v && v.day_to ? v.day_to : '';
        show('#m_vline');
    }
    document.getElementById('mvl_ok').addEventListener('click', async () => {
        const id = Number(document.getElementById('mvl_id').value || 0);
        const line = {
            class_code: document.getElementById('mvl_class').value, qty: document.getElementById('mvl_qty').value || 1,
            label: document.getElementById('mvl_label').value, group_id: document.getElementById('mvl_group').value || null,
            day_from: document.getElementById('mvl_from').value || null, day_to: document.getElementById('mvl_to').value || null
        };
        if (!line.class_code) { toast('error', 'Choose a vehicle size.'); return; }
        // every line is sent together: the lines are one decision, saved as a whole
        const rows = (CURRENT.vehicles || []).map(v => Number(v.id) === id ? line : vlineRow(v));
        if (!id) rows.push(line);
        const ok = await rowPost('/vehicle', { rows }, id ? 'Vehicle line updated.' : 'Vehicle added.');
        if (ok) hide('#m_vline');
    });

    // ---------- an excursion line: day, group, quantity, the quotation's own words ----------
    function openExc(id) {
        const x = (CURRENT.excursions || []).find(e => Number(e.id) === id);
        if (!x) return;
        document.getElementById('mx_id').value = x.id;
        document.getElementById('mx_title').textContent = x.name + ' — ' + x.currency + ' ' + Number(x.rate).toLocaleString() + ' ' + ({ per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' }[x.unit] || x.unit);
        const dsel = document.getElementById('mx_day');
        dsel.innerHTML = (CURRENT.days || []).map(d => `<option value="${d.day_no}">Day ${d.day_no}${d.area ? ' · ' + esc(d.area) : ''}</option>`).join('') + `<option value="${(CURRENT.days || []).length + 1}">Day ${(CURRENT.days || []).length + 1} (departure)</option>`;
        dsel.value = String(x.day_no);
        const gsel = document.getElementById('mx_group');
        gsel.innerHTML = '<option value="">Everyone</option>' + (CURRENT.groups || []).map(g => `<option value="${g.id}">${esc(g.name)} (${g.pax})</option>`).join('');
        gsel.value = x.group_id ? String(x.group_id) : '';
        document.getElementById('mx_qty').value = x.qty;
        document.getElementById('mx_desc').value = x.description || '';
        document.getElementById('mx_note').value = x.note || '';
        show('#m_exc');
    }
    document.getElementById('mx_ok').addEventListener('click', async () => {
        const ok = await rowPost('/excursion/' + document.getElementById('mx_id').value, {
            day_no: document.getElementById('mx_day').value, group_id: document.getElementById('mx_group').value || null,
            qty: document.getElementById('mx_qty').value, description: document.getElementById('mx_desc').value, note: document.getElementById('mx_note').value
        });
        if (ok) hide('#m_exc');
    });
    let EXCURSIONS = null;
    async function loadExcursions() {
        if (EXCURSIONS !== null) return EXCURSIONS;
        try { const j = await api('/excursions'); EXCURSIONS = j.data || []; } catch (e) { EXCURSIONS = []; }
        return EXCURSIONS;
    }

    // ---------- Go for Quotation, and the checklist in front of it ----------
    // The checklist is the human QC step: every rate and figure still missing, one line each,
    // with a Fix that takes the person straight to its source on this screen (the night, the
    // line, the field). After they save, the screen comes back to the checklist with the count
    // reduced; at nought it says Ready to Issue Quotation and offers Go for Quotation, which
    // freezes the numbered version. Nothing here fixes anything by itself.
    const CHECK = { armed: false, items: [], fixed: 0, timer: null, polls: 0, jumping: false };
    const goLine = (b, cls) => `<div class="goline"><span class="pill ${cls}">${cls === 'pill-red' ? 'fix' : 'check'}</span><span>${esc(b.label)}</span></div>`;

    async function goForQuotation() {
        const j = await post('/intake/' + CURRENT.id + '/go', {});
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; renderDetail();
        if (!j.ok) { renderChecklist(j.blockers || [], j.warnings || []); return; }
        // the version is produced: the checklist is done with
        CHECK.armed = false; showPill();
        document.getElementById('mgo_title').textContent = 'Quotation version ' + j.quote.version + ' is ready';
        document.getElementById('mgo_foot').textContent = '';
        document.getElementById('mgo_body').innerHTML = `<div class="help mb-2">Every rate and figure was found. The costing is frozen into version ${j.quote.version}. Open the client quotation to check it and email it to the agent from there — sending issues the version. If it goes another way (printed, or sent from your own mail program), press Issue on the version once it has gone.</div>
               ${j.warnings.length ? `<div class="help mb-1"><b>Worth an eye</b></div>${j.warnings.map(w => goLine(w, 'pill-amber')).join('')}` : ''}
               <div class="d-flex gap-2 mt-3 flex-wrap">
                    <a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${CURRENT.id}/quote/${j.quote.id}?view=internal"><i class="bi bi-calculator"></i> Internal costing</a>
                    <a class="btn btn-primary btn-sm" target="_blank" href="${BASE}/intake/${CURRENT.id}/quote/${j.quote.id}?view=client"><i class="bi bi-file-earmark-text"></i> Client quotation</a>
                    <a class="btn btn-light btn-sm" target="_blank" href="${BASE}/intake/${CURRENT.id}/quote/${j.quote.id}?view=client&pdf=1"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
               </div>`;
        show('#m_go');
    }

    // the checklist from the costing check alone: no version is produced by looking
    async function openChecklist() {
        if (!CURRENT || !CURRENT.id) return;
        const j = await api('/intake/' + CURRENT.id + '/costing');
        if (j.status !== 200) { toast('error', j.message); return; }
        renderChecklist(j.data.blockers || [], j.data.warnings || []);
    }

    function renderChecklist(blockers, warnings) {
        clearTimeout(CHECK.timer);
        const n = blockers.length;
        const was = CHECK.items.length;
        if (CHECK.armed && was > 0 && n < was) CHECK.fixed += was - n;
        CHECK.items = blockers;
        CHECK.armed = n > 0;
        showPill();
        document.getElementById('mgo_title').textContent = n ? `Checklist — ${n} thing${n === 1 ? '' : 's'} to fix` : 'Ready to Issue Quotation';
        document.getElementById('mgo_foot').textContent = n
            ? (CHECK.fixed ? `${CHECK.fixed} fixed so far · ` : '') + 'Fix takes you to the source; save there and this list comes back with the count down.'
            : (CHECK.fixed ? `${CHECK.fixed} thing${CHECK.fixed === 1 ? '' : 's'} fixed in this pass.` : '');
        if (!n) {
            document.getElementById('mgo_body').innerHTML = `<div class="chk-ready"><i class="bi bi-check-circle-fill"></i> Every rate and figure is in place. Go for Quotation freezes the numbered version to preview, print and send.</div>
                ${warnings.length ? `<div class="help mt-3 mb-1"><b>Worth an eye</b></div>${warnings.map(w => goLine(w, 'pill-amber')).join('')}` : ''}
                <div class="d-flex gap-2 mt-3"><button class="btn btn-primary btn-sm" id="mgo_go"><i class="bi bi-check2-square"></i> Go for Quotation</button></div>`;
            document.getElementById('mgo_go').addEventListener('click', goForQuotation);
            if (!document.querySelector('#m_go').classList.contains('show')) show('#m_go');
            return;
        }
        document.getElementById('mgo_body').innerHTML = `<div class="help mb-2">Nothing is produced until every line below is fixed — a quotation with a hole in it is worse than none. Each line says what is missing; nothing is guessed or typed in for you.</div>
            ${blockers.map((b, i) => { const f = fixFor(b); return `<div class="goline"><span class="pill pill-red">${i + 1}</span><span>${esc(b.label)}${f.hint ? `<span class="fixhint">${esc(f.hint)}</span>` : ''}</span><span class="fixb d-flex gap-1">${f.alt ? `<button class="btn btn-light btn-sm" data-fixalt="${i}">${esc(f.alt.text)}</button>` : ''}${f.run ? `<button class="btn btn-primary btn-sm" data-fix="${i}">${esc(f.text || 'Fix')}</button>` : (f.text ? `<span class="pill pill-grey">${esc(f.text)}</span>` : '')}</span></div>`; }).join('')}
            ${warnings.length ? `<div class="help mt-3 mb-1"><b>Also worth an eye</b> — these do not stop the quotation</div>${warnings.map(w => goLine(w, 'pill-amber')).join('')}` : ''}`;
        document.querySelectorAll('#mgo_body [data-fix]').forEach(btn => btn.addEventListener('click', () => {
            const f = fixFor(blockers[Number(btn.dataset.fix)]);
            CHECK.jumping = true;   // hidden by a Fix, not closed: the pass goes on
            hide('#m_go');
            setTimeout(() => { try { f.run(); } catch (e) { toast('error', 'That could not be opened: ' + e.message); } }, 250);
        }));
        // the one-click alternative on a line (No charge): done from the list itself, which then re-reads
        document.querySelectorAll('#mgo_body [data-fixalt]').forEach(btn => btn.addEventListener('click', async () => {
            const f = fixFor(blockers[Number(btn.dataset.fixalt)]);
            if (!f.alt) return;
            btn.disabled = true;
            try { await f.alt.run(); } finally { btn.disabled = false; }
            openChecklist();   // the list re-read at once, the count down
        }));
        if (!document.querySelector('#m_go').classList.contains('show')) show('#m_go');
    }
    // Close or X ends the pass (the Checklist button starts it again); a Fix only hides the list
    document.getElementById('m_go')?.addEventListener('hidden.bs.modal', () => {
        if (CHECK.jumping) { CHECK.jumping = false; showPill(); return; }
        CHECK.armed = false; clearTimeout(CHECK.timer); showPill();
    });

    // where each thing to fix lives on this screen, by the code the costing check gives it
    function fixFor(b) {
        const code = String(b.code || '');
        let m;
        const day = id => ({ run: () => openDay(id) });
        if (code === 'pax') return { run: () => openField('pax_total') };
        if (code === 'arrival') return { run: () => openField('arrival_date') };
        if (code === 'no_nights') return { run: () => jumpTo('#d_addday', 'Add a night for each hotel stay') };
        if (code === 'dates_unconfirmed' || code === 'dates_email') return { text: 'Show', run: () => jumpTo('#dates_bar', 'Keep these dates, or use the email\'s') };
        if (code === 'nights_missing') return { run: () => jumpTo('#d_addmissing', 'Add the missing nights, then name the hotels') };
        if ((m = code.match(/^departure_row_(\d+)$/))) return { run: () => jumpTo(`#detail_cols [data-deprow="${m[1]}"]`, 'Make it the departure day, or keep it as an extra night') };
        if (code === 'rooms_mix') return { run: () => jumpTo('#d_rm_sgl', 'Type the room mix and save', true) };
        if ((m = code.match(/^group_rooms_(\d+)$/))) return { run: () => openGroup(Number(m[1])) };
        if ((m = code.match(/^(?:hotel_none|hotel_date|hotel_meal|hotel_cur|hotel|roomtype|rate|mix)_(\d+)$/))) return day(Number(m[1]));
        if ((m = code.match(/^reread_(\d+)$/))) return { text: 'Check hotels again', run: () => { const b2 = document.getElementById('d_recheck'); if (b2) b2.click(); else jumpTo('#detail_cols', ''); } };
        if (code === 'no_vehicle') return { run: () => jumpTo('#d_vehadd', 'Add a vehicle') };
        if (code === 'no_legs') return { run: () => jumpTo('#d_autoleg', 'Press Auto to read the legs from the itinerary, or add them by hand') };
        if ((m = code.match(/^km_unpriced_(\d+)$/))) return { run: () => {
                const seg = document.querySelector('#detail_cols .trk-wrap [data-unpriced]');   // tf-mileage-track-r1: the Railway view
                if (seg) { jumpTo(seg, CAN_MILEAGE ? 'This leg has no distance on record: type the kilometres in its window, or press Edit to correct the from and the to' : 'This leg has no distance on record. Management types the kilometres; or correct the from and the to so the record can price it'); setTimeout(() => { if (TRK.openKey) TRK.openKey(seg.dataset.trk); }, 500); return; }
                const row = document.querySelector('#detail_cols tr[data-unpriced]');
                if (row) { jumpTo(row.querySelector('[data-leg]') || row, CAN_MILEAGE ? 'This leg has no distance on record: press Edit and type the kilometres, or correct the from and the to' : 'This leg has no distance on record. Management types the kilometres (Edit on the leg needs the Mileage override); or correct the from and the to so the record can price it'); return; }
                const inp = [...document.querySelectorAll('#detail_cols [data-legkm]')].find(x => !x.value); jumpTo(inp || '#d_legadd', 'Type the kilometres on the leg, or let the history price it', !!inp); } };
        if ((m = code.match(/^km_norate_(\d+)$/))) return { run: () => jumpTo('#detail_cols [data-vline]', 'The rate per kilometre comes from Master Files → Transport Rates for this size'), hint: 'Set under Master Files → Transport Rates (Management); the line takes it on Re-price' };
        if ((m = code.match(/^crew_rate_(.+)$/))) return { run: () => jumpTo(`#detail_cols [data-crewline="${m[1]}"]`, ''), hint: 'Admin sets the bata under Master Files → Crew Bata; the line takes it at once' };
        if ((m = code.match(/^crew_days_(.+)$/))) return { run: () => { const line = (CURRENT.crew || []).find(c => c.role_code === m[1]); if (line) openCrew(line.kind, line); else jumpTo('#detail_cols', ''); } };
        if ((m = code.match(/^accom(?:_cur)?_(driver|guide)$/))) return { run: () => jumpTo(`#detail_cols [data-accomrate="${m[1]}"]`, 'Type the nightly figure and save, or switch the room off', true, `#ac_${m[1]}`) };
        if ((m = code.match(/^svc_(\d+)$/))) {
            // unsure: never forced to a price — No charge in one click, or open the line to pick the priced entry
            const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(m[1]));
            return { run: () => { if (sv) openServices(Number(sv.day_no), Number(sv.id)); else jumpTo('#detail_cols', ''); },
                     alt: sv ? { text: 'No charge', run: () => svcQuick(Number(sv.id), { pay_mode: 'no_charge' }) } : null,
                     hint: sv && sv.suggest_code ? 'Closest entry in the priced list: ' + sv.suggest_code + ' — Fix opens the line to take it' : null };
        }
        // schema 39: an entrance or safari not in the Place to Visit / Entrance Fee Master — the place picked (or added there), never "No charge"
        if ((m = code.match(/^svc_master_(\d+)$/))) return { text: 'Pick the place', run: () => { const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(m[1])); if (sv) openServices(Number(sv.day_no), Number(sv.id)); else jumpTo('#ea_card', ''); },
                     alt: SEES_MASTER ? { text: 'Add it in the master', run: () => window.open(MASTER_URL, '_blank') } : null, hint: 'Pick the place from the master on the line, or add it there with its rate (or as Free) — it is never taken as free' };
        // schema 39: a place of the master with no rate for the day — the rate is set in the master, nothing is typed here
        if ((m = code.match(/^svc_rate_(\d+)$/))) {
            const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(m[1]));
            if (sv && sv.meal_id) return { text: 'Pick another meal', run: () => openMealPick(Number(sv.day_no), sv.kind, Number(sv.id), null), hint: 'A meal switched off, out of its dates, or whose supplier is unavailable is never re-priced from a guess' };
        }
        // schema 40: the meals of a Half Board tour
        if ((m = code.match(/^meal_(?:dup|hotel)_(\d+)_(lunch|dinner)$/))) return { text: 'Open Meals', run: () => jumpTo(`#meals_card tr[data-mealday="${m[1]}"]`, code.startsWith('meal_dup') ? 'Remove the extra line, or lower the adults and children' : 'Switch the night\'s HB meal, remove the outside meal, or confirm it with a reason') };
        if ((m = code.match(/^meal_day_(\d+)$/))) return { text: 'Open Meals', run: () => jumpTo('#meals_card', 'Move the meal to a day of the tour, or remove it') };
        if ((m = code.match(/^svc_rate_(\d+)$/))) return SEES_MASTER ? { text: 'Set the rate in the master', run: () => window.open(MASTER_URL, '_blank'), hint: 'The line takes the rate on its next read' } : { text: 'The master', hint: 'The rate is set in the Place to Visit / Entrance Fee Master' };
        if ((m = code.match(/^svc_dup_(\d+)$/))) return { text: 'Remove the extra', run: () => { const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(m[1])); if (sv) openServices(Number(sv.day_no), Number(sv.id)); else jumpTo('#ea_card', ''); }, hint: 'The same thing twice on one day is costed twice — Remove one of the two lines' };
        if ((m = code.match(/^svc_rupees_(\d+)$/))) return { run: () => { const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(m[1])); if (sv) openServices(Number(sv.day_no), Number(sv.id)); else jumpTo('#detail_cols', ''); } };
        if ((m = code.match(/^exc_(?:qty|cur)_(\d+)$/))) return { run: () => openExc(Number(m[1])) };
        if (code === 'groups_pax') return { run: () => jumpTo('#d_addgroup', 'Make the groups add up to the party') };
        if (code === 'pricing') return SEES_PRICING ? { run: () => jumpTo('#d_mkpct', 'Set the markup for this inquiry, or the default under Pricing', true) } : { text: 'Management', hint: 'The markup is set by Management under Pricing' };
        if (code === 'no_usd_rate') return { text: 'Open Daily USD Rate', run: () => window.open(`${BASE.replace(/\/tour\/intake$/, '')}/master_files/daily_usd_rate`, '_blank'), hint: 'Enter today\'s rate there, then Check hotels again here' };
        return {};
    }

    // scroll to the element, light it up for a moment, focus it when it takes typing

    // ---- tf-mileage-track-r1 (10 Oct 2026): the Mileage by sector Railway view — which view, and its details window ----
    // A mouse pointing at a station or a sector opens its window; moving away closes it. A click or a tap keeps it open
    // (a phone has no hover), and so does a click inside it, so the boxes in it can be typed in; × or Esc or a click
    // elsewhere closes it. After a save the screen is drawn again and the window that was kept open comes back.
    const TRK = { pin: null, key: null, hideT: 0, showT: 0, bound: false, close: null, pos: null };
    function milView() {
        try { return localStorage.getItem('avh_mileage_view') === 'list' ? 'list' : 'rail'; } catch (e) { return 'rail'; }
    }
    function trackInit() {
        if (TRK.intake !== (CURRENT && CURRENT.id)) { TRK.pin = null; TRK.intake = CURRENT && CURRENT.id; }   // another inquiry: nothing kept open
        document.querySelectorAll('#detail_cols [data-mview]').forEach(b => b.addEventListener('click', () => {
            try { localStorage.setItem('avh_mileage_view', b.dataset.mview); } catch (e) { /* the choice is only a convenience */ }
            const y = window.scrollY;
            TRK.pin = null;
            renderDetail();
            window.scrollTo(0, y);
        }));
        const wrap = document.querySelector('#detail_cols .trk-wrap');
        if (!wrap) { TRK.close = null; TRK.pos = null; return; }
        const pop = wrap.querySelector('.trk-pop'), body = pop.querySelector('.trk-pop-b'), store = wrap.querySelector('.trk-store');
        let anchorEl = null;
        const park = () => { [...body.children].forEach(ch => store.appendChild(ch)); };
        const close = () => {
            clearTimeout(TRK.showT); clearTimeout(TRK.hideT);
            park(); pop.hidden = true; pop.classList.remove('pinned');
            TRK.pin = null; TRK.key = null; anchorEl = null;
            wrap.querySelectorAll('[data-trk].on').forEach(x => x.classList.remove('on'));
        };
        const position = () => {
            if (!anchorEl || pop.hidden) return;
            if (window.matchMedia('(max-width: 575px)').matches) { pop.classList.add('sheet'); return; }
            pop.classList.remove('sheet');
            const wr = wrap.getBoundingClientRect(), ar = anchorEl.getBoundingClientRect();
            const w = Math.min(560, wr.width - 16);
            pop.style.width = w + 'px';
            const left = Math.max(8, Math.min(ar.left + ar.width / 2 - wr.left - w / 2, wr.width - w - 8));
            pop.style.left = left + 'px';
            pop.style.top = (ar.bottom - wr.top + 6) + 'px';
            const ph = pop.offsetHeight;
            if (ar.bottom + ph + 12 > window.innerHeight && ar.top - ph - 12 > 0) pop.style.top = (ar.top - wr.top - ph - 6) + 'px';
        };
        const open = (a, pinned) => {
            const key = a.dataset.trk;
            const block = body.querySelector(`[data-trk-detail="${key}"]`) || store.querySelector(`[data-trk-detail="${key}"]`);
            if (!block) return;
            clearTimeout(TRK.hideT); clearTimeout(TRK.showT);
            if (!body.contains(block)) { park(); body.appendChild(block); }
            wrap.querySelectorAll('[data-trk].on').forEach(x => x.classList.remove('on'));
            a.classList.add('on');
            anchorEl = a;
            TRK.key = key;
            TRK.pin = pinned ? key : null;
            pop.classList.toggle('pinned', !!pinned);
            pop.hidden = false;
            position();
        };
        wrap.querySelectorAll('[data-trk]').forEach(a => {
            a.addEventListener('pointerenter', e => {
                if (e.pointerType !== 'mouse' || TRK.pin) return;
                clearTimeout(TRK.hideT); clearTimeout(TRK.showT);
                TRK.showT = setTimeout(() => open(a, false), 130);
            });
            a.addEventListener('pointerleave', e => {
                if (e.pointerType !== 'mouse') return;
                clearTimeout(TRK.showT);
                if (!TRK.pin) TRK.hideT = setTimeout(close, 260);
            });
            a.addEventListener('click', () => { if (TRK.pin === a.dataset.trk) { close(); return; } open(a, true); });
            a.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
        });
        pop.addEventListener('pointerenter', e => { if (e.pointerType === 'mouse') clearTimeout(TRK.hideT); });
        pop.addEventListener('pointerleave', e => { if (e.pointerType === 'mouse' && !TRK.pin) TRK.hideT = setTimeout(close, 260); });
        // working inside the window keeps it open
        pop.addEventListener('pointerdown', () => { if (!TRK.pin && TRK.key) { TRK.pin = TRK.key; pop.classList.add('pinned'); } });
        pop.querySelector('.trk-x').addEventListener('click', close);
        TRK.close = close;
        TRK.pos = position;
        TRK.openKey = key => { const a = wrap.querySelector(`[data-trk="${key}"]`); if (a) open(a, true); };
        if (!TRK.bound) {
            TRK.bound = true;
            document.addEventListener('pointerdown', e => {
                const w = document.querySelector('#detail_cols .trk-wrap');
                const p = w && w.querySelector('.trk-pop');
                if (!p || p.hidden || !TRK.close) return;
                if (p.contains(e.target) || (e.target.closest && e.target.closest('#detail_cols [data-trk]'))) return;
                if (e.target.closest && e.target.closest('.modal, .swal2-container')) return;   // a question, a message or an editor opened from the window
                TRK.close();
            });
            document.addEventListener('keydown', e => { if (e.key === 'Escape' && TRK.close && !document.querySelector('.modal.show, .swal2-container')) TRK.close(); });
            window.addEventListener('resize', () => { if (TRK.pos) TRK.pos(); });
        }
        // drawn again after a save: the window that was kept open comes back
        if (TRK.pin) { const k = TRK.pin; TRK.pin = null; TRK.openKey(k); }
    }

    function jumpTo(target, hint, focus, highlightSel) {
        const el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) { toast('error', 'That part of the screen is not open — scroll to the card and fix it there.'); return; }
        const box = highlightSel ? (document.querySelector(highlightSel) || el) : (el.closest('.crewrow, tr, .accom, .tf-card') || el);
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        box.classList.add('chk-target');
        setTimeout(() => box.classList.remove('chk-target'), 3500);
        if (focus && typeof el.focus === 'function') setTimeout(() => el.focus(), 400);
        if (hint) toast('success', hint);
    }

    function showPill() {
        const pill = document.getElementById('chk_pill');
        if (!pill) return;
        const n = CHECK.items.length;
        const listOpen = document.querySelector('#m_go') && document.querySelector('#m_go').classList.contains('show');
        pill.style.display = CHECK.armed && n > 0 && !listOpen ? 'inline-block' : 'none';
        document.getElementById('chk_pill_n').textContent = n;
    }
    document.getElementById('chk_pill')?.addEventListener('click', openChecklist);
    document.getElementById('m_go')?.addEventListener('shown.bs.modal', showPill);

    // after a save while the checklist is being worked through: once every window has closed,
    // read the check again and come back to the list with the count down
    function checklistAfterRender() {
        if (!CHECK.armed) return;
        clearTimeout(CHECK.timer);
        CHECK.polls = 0;
        const tick = () => {
            if (!CHECK.armed) return;
            if (document.querySelector('.modal.show')) { if (++CHECK.polls < 40) CHECK.timer = setTimeout(tick, 500); return; }
            openChecklist();
        };
        CHECK.timer = setTimeout(tick, 600);
    }

    // ---------- pricing, management only ----------
    async function loadPricing() {
        const box = document.getElementById('d_pricing');
        if (!box) return;
        const j = await api('/intake/' + CURRENT.id + '/costing');
        if (!box.isConnected) return;   // the screen was drawn again while the costing was being read; that draw asks again
        if (j.status !== 200) { box.innerHTML = `<div class="help">${esc(j.message)}</div>`; return; }
        const c = j.data, m = c.markup || {};
        const usd = v => v == null ? '—' : 'USD ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        box.innerHTML = `
            <div class="crewrow head"><span>Cost</span><span>Markup</span><span>Price</span><span>Per person</span><span>Profit</span></div>
            <div class="crewrow">
                <span><b>${usd(c.cost_usd)}</b>${c.cost_lkr != null ? `<div class="help">LKR ${Number(c.cost_lkr).toLocaleString()} at ${c.usd_rate}</div>` : ''}</span>
                <span>${m.pct != null ? `<b>${Number(m.pct).toFixed(2)}%</b> <span class="pill ${m.source === 'inquiry' ? 'pill-blue' : 'pill-grey'}">${m.source === 'inquiry' ? 'this inquiry' : 'default'}</span>${m.set_by ? `<div class="help">${esc(m.set_by)} · ${esc((m.set_at || '').slice(0, 16))}</div>` : ''}${m.note ? `<div class="help" title="Management's note, kept with the markup; the desks never see it">“${esc(m.note)}”</div>` : ''}` : '<span class="pill pill-red">not set</span><div class="help">no default under Pricing either</div>'}</span>
                <span><b>${usd(m.sell_usd)}</b></span>
                <span>${usd(m.per_person_usd)}</span>
                <span style="color:var(--tf-green)"><b>${usd(m.profit_usd)}</b></span>
            </div>
            <div class="row g-2 mt-2 align-items-end">
                <div class="col-md-3"><label class="form-label help">Markup for this inquiry, %</label>
                    <input type="number" min="0" max="500" step="0.01" class="form-control form-control-sm" id="d_mkpct" value="${m.source === 'inquiry' && m.pct != null ? m.pct : ''}" placeholder="use the default"></div>
                <div class="col-md-6"><label class="form-label help">Note</label>
                    <input class="form-control form-control-sm" id="d_mknote" value="${esc(m.note || '')}" placeholder="Why this one differs from the default — kept with the markup, seen under Pricing only"></div>
                <div class="col-md-3"><button class="btn btn-light btn-sm w-100" id="d_mkset">Set markup</button></div>
            </div>
            <div class="help mt-2">Cost is every published rate, kilometre and bata on this inquiry, the Entrance &amp; Activities total as that section works it out, and every excursion, added up — see the internal costing. ${c.blockers && c.blockers.length ? `<a href="#" id="d_pr_chk"><span class="pill pill-red">Checklist — ${c.blockers.length} to fix</span></a> before it can be quoted.` : '<span class="pill pill-green">ready to issue</span>'}</div>`;
        document.getElementById('d_pr_chk')?.addEventListener('click', e => { e.preventDefault(); openChecklist(); });
        document.getElementById('d_mkset').addEventListener('click', async () => {
            const r = await post('/intake/' + CURRENT.id + '/markup', { pct: document.getElementById('d_mkpct').value, note: document.getElementById('d_mknote').value });
            if (r.status !== 200) { toast('error', r.message); return; }
            toast('success', r.message); loadPricing();
            checklistAfterRender();   // a markup set is a fix: back to the checklist if it is being worked through
        });
    }

    function openField(key) {
        const f = (CURRENT.fields || {})[key] || {};
        document.getElementById('mf_title').textContent = LABELS[key] || key;
        document.getElementById('mf_key').value = key;
        document.getElementById('mf_value').value = f.value ?? '';
        // Nights are counted, never typed. The box still opens so the reasoning is visible,
        // but what goes in it is only ever recorded as a claim to be checked.
        const nightsField = key === 'nights';
        document.getElementById('mf_value').readOnly = nightsField;
        document.getElementById('mf_value').style.background = nightsField ? '#f6f7f9' : '';
        document.getElementById('mf_quote').innerHTML = f.quote
            ? 'From the email: “' + esc(f.quote) + '”'
            : (f.basis ? esc(f.basis)
                : ((CURRENT.source_text || '').trim() ? 'The email does not state this.' : 'Type in whatever you have. Nothing is filled in for you.'));
        renderMail(f.quote || null);
        show('#m_field');
    }
    document.getElementById('mf_ok').addEventListener('click', async () => {
        const j = await post('/intake/' + CURRENT.id + '/field', { key: document.getElementById('mf_key').value, value: document.getElementById('mf_value').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; hide('#m_field'); renderDetail();
    });

    // ---- the picture for one day of the quotation ----
    let PHOTO_LIB = null;   // the library as last read: { library, all_tags, days, tags }
    async function openPhotoPicker(dayNo) {
        if (!CURRENT) return;
        const j = await api('/intake/' + CURRENT.id + '/photos');
        if (j.status !== 200) { toast('error', j.message); return; }
        PHOTO_LIB = j.data || {};
        if (PHOTO_LIB.available === false) { toast('info', 'The photo library is not installed yet.'); return; }
        const d = (CURRENT.days || []).find(x => Number(x.day_no) === Number(dayNo)) || {};
        document.getElementById('mp_day').value = dayNo;
        document.getElementById('mp_title').textContent = 'Picture for day ' + dayNo + (d.area ? ' · ' + d.area : '');
        const byDay = PHOTO_LIB.days || {}, now = byDay[dayNo] || byDay[String(dayNo)];
        const sugg = (PHOTO_LIB.tags || {})[dayNo] || (PHOTO_LIB.tags || {})[String(dayNo)] || [];
        document.getElementById('mp_now').innerHTML = now && now.photo_id
            ? `Now: <b>${esc(now.caption || now.tag_name || 'photo ' + now.photo_id)}</b> — ${now.chosen_by === 'human' ? 'chosen by ' + esc(now.by || 'staff') : 'the library\'s own pick'}`
            : (now && now.chosen_by === 'human' ? 'Now: <b>no picture</b>, by choice' : (sugg.length ? 'Now: no picture — the library has none for ' + esc(sugg[0].name) + ' yet' : 'Now: no picture — the day\'s programme names no place the library knows'));
        // the places this day's programme points at, best first, as chips that filter the grid
        document.getElementById('mp_chips').innerHTML = sugg.length
            ? '<span class="help">This day reads as:</span> ' + sugg.slice(0, 5).map((t, i) => `<span class="chip ${i === 0 ? 'on' : ''}" data-chip="${esc(t.code)}" title="${esc(t.hits.join(', '))}">${esc(t.name)}</span>`).join('')
            : '<span class="help">No destination or attraction recognised in this day\'s programme — browse the library below, or add the place\'s words to a tag in Master Files → Tour Photos.</span>';
        const sel = document.getElementById('mp_tag');
        sel.innerHTML = '<option value="">All places</option>' + (PHOTO_LIB.all_tags || []).map(t => `<option value="${esc(t.code)}">${esc(t.name)} (${t.photos})</option>`).join('');
        sel.value = sugg.length ? sugg[0].code : '';
        document.getElementById('mp_q').value = '';
        renderPhotoGrid();
        document.querySelectorAll('#mp_chips [data-chip]').forEach(c => c.addEventListener('click', () => {
            document.querySelectorAll('#mp_chips [data-chip]').forEach(x => x.classList.toggle('on', x === c));
            sel.value = c.dataset.chip; renderPhotoGrid();
        }));
        show('#m_photo');
    }
    function renderPhotoGrid() {
        const dayNo = Number(document.getElementById('mp_day').value);
        const tag = document.getElementById('mp_tag').value, q = document.getElementById('mp_q').value.trim().toLowerCase();
        const byDay = (PHOTO_LIB && PHOTO_LIB.days) || {}, now = byDay[dayNo] || byDay[String(dayNo)];
        const onOther = new Set(Object.entries(byDay).filter(([k, v]) => Number(k) !== dayNo && v && v.photo_id).map(([k, v]) => Number(v.photo_id)));
        let list = (PHOTO_LIB && PHOTO_LIB.library) || [];
        if (tag) list = list.filter(p => (p.tag_codes || []).includes(tag));
        if (q) list = list.filter(p => (p.caption || '').toLowerCase().includes(q) || (p.tags || []).some(t => (t.name || '').toLowerCase().includes(q)));
        document.getElementById('mp_count').textContent = list.length ? list.length + ' picture' + (list.length === 1 ? '' : 's') : '';
        document.getElementById('mp_grid').innerHTML = list.length ? list.map(p => `<div class="phpick ${now && Number(now.photo_id) === Number(p.id) ? 'on' : ''}" data-pick="${p.id}" title="${esc((p.caption || '') + (onOther.has(Number(p.id)) ? ' — already on another day of this itinerary' : ''))}">
                ${Number(p.preferred) === 1 ? '<span class="star">&#9733;</span>' : ''}<img src="${esc(p.thumb_url || p.url)}" alt="">
                <div class="cap">${esc(p.caption || (p.tags || []).map(t => t.name).join(', '))}${onOther.has(Number(p.id)) ? ' <span class="pill pill-amber">on another day</span>' : ''}</div></div>`).join('')
            : `<div class="help" style="grid-column:1/-1;padding:14px 0">${tag ? 'No picture carries this tag yet.' : 'The library is empty.'}${SEES_PHOTOS ? ` <a href="${PHOTOS_URL}" target="_blank">Upload some under Master Files → Tour Photos.</a>` : ' Ask Admin to add some under Master Files → Tour Photos.'}</div>`;
        document.querySelectorAll('#mp_grid [data-pick]').forEach(el => el.addEventListener('click', () => setDayPhoto(dayNo, Number(el.dataset.pick))));
    }
    async function setDayPhoto(dayNo, choice) {
        const j = await post('/intake/' + CURRENT.id + '/photo/' + dayNo, { photo_id: choice });
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; hide('#m_photo'); renderDetail(); toast('success', j.message);
    }
    document.getElementById('mp_tag').addEventListener('change', renderPhotoGrid);
    document.getElementById('mp_q').addEventListener('input', renderPhotoGrid);
    document.getElementById('mp_none').addEventListener('click', () => setDayPhoto(Number(document.getElementById('mp_day').value), 'none'));
    document.getElementById('mp_auto').addEventListener('click', () => setDayPhoto(Number(document.getElementById('mp_day').value), 'auto'));

    function openDay(id) {
        const d = (CURRENT.days || []).find(x => Number(x.id) === id);
        if (!d) return;
        document.getElementById('md_id').value = id;
        document.getElementById('md_date').value = d.day_date || '';
        document.getElementById('md_area').value = d.area || '';
        document.getElementById('md_hotel').value = d.hotel_name_raw || '';
        document.getElementById('md_meal').value = d.meal_plan || '';
        document.getElementById('md_rooms').value = d.rooms ?? '';
        document.getElementById('md_note').value = d.special_note || '';
        document.getElementById('md_text').value = d.client_text || '';
        const stdText = ((CURRENT.itinerary || {}).days || {})[d.day_no] || ((CURRENT.itinerary || {}).days || {})[String(d.day_no)];
        document.getElementById('md_text_std').innerHTML = stdText ? 'Standard wording: <i>' + esc(stdText.standard) + '</i>' : '';
        // the room types the panel prices for this hotel and date; the chosen one is what the night is costed on
        const rts = d.room_types || [];
        const wrap = document.getElementById('md_rtwrap');
        const sel = document.getElementById('md_rt');
        const textWrap = document.getElementById('md_rttextwrap');
        if (d.rate_state === 'rate' && rts.length) {
            wrap.style.display = '';
            textWrap.style.display = '';
            const normal = rts.find(t => t.priced);   // the hotel's normal room type: the first with a published rate, in the panel's own order
            sel.innerHTML = '<option value="">Not chosen — the hotel\'s normal type</option>' + rts.map(t => `<option value="${t.id}" ${!t.priced ? 'disabled' : ''}>${esc(t.name)}${normal && t.id === normal.id ? ' (the hotel\'s normal type)' : ''}${t.priced ? '' : ' — no published rate'}${t.per_unit ? ' (per unit' + (t.unit_pax_max ? ', up to ' + t.unit_pax_max : '') + ')' : ''}</option>`).join('');
            sel.value = d.room_type_id != null ? String(d.room_type_id) : '';
            document.getElementById('md_rthint').textContent = (Number(d.room_type_auto) === 1 ? 'Filled in from the master: the hotel\'s normal room type. Choose another here for this booking alone. ' : '') + 'The published rate for the room type and the meal plan is copied beside the night when you save. Nothing is typed.';
            document.getElementById('md_rttext').value = d.room_type_text || '';
        } else {
            wrap.style.display = 'none';
            textWrap.style.display = 'none';
            sel.innerHTML = '';
            document.getElementById('md_rttext').value = '';
        }

        loadMasters();   // so the hotel search answers at once

        renderMail(d.source_quote || null);
        show('#m_day');
        setTimeout(() => document.getElementById('md_hotel')?.focus(), 350);
    }
    document.getElementById('md_ok').addEventListener('click', async () => {
        const id = document.getElementById('md_id').value;
        const j = await post('/intake/' + CURRENT.id + '/day/' + id, {
            day_date: document.getElementById('md_date').value, area: document.getElementById('md_area').value,
            hotel_name_raw: document.getElementById('md_hotel').value, meal_plan: document.getElementById('md_meal').value,
            rooms: document.getElementById('md_rooms').value,
            special_note: document.getElementById('md_note').value,
            client_text: document.getElementById('md_text').value,
            ...(document.getElementById('md_rtwrap').style.display !== 'none' ? { room_type_id: document.getElementById('md_rt').value, room_type_text: document.getElementById('md_rttext').value } : {})
        });
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; hide('#m_day'); renderDetail();
        // tf-walkfix-r1: one trip — the hotel's normal room type is filled in from the master on Save, and the row says so
        const saved = (CURRENT.days || []).find(x => Number(x.id) === Number(id));
        if (saved && saved.rate_state === 'rate' && Number(saved.room_type_auto) === 1 && saved.room_type_name) {
            toast('success', 'Saved. Room type ' + saved.room_type_name + ' — the hotel\'s normal type — was filled in from the master; Edit the night to choose another for this booking.');
        }
    });

    // ---------- a special rate per kilometre ----------
    function openVehicleRate(id) {
        const v = (CURRENT.vehicles || []).find(x => Number(x.id) === id);
        if (!v) return;
        const cur = v.currency || 'LKR';
        document.getElementById('mv_id').value = id;
        document.getElementById('mv_rate').value = v.rate_by === 'manual' ? (v.km_rate ?? '') : '';
        document.getElementById('mv_note').value = v.rate_note || '';
        document.getElementById('mv_master').textContent = v.master_rate != null
            ? `${v.type}: the master rate is ${cur} ${Number(v.master_rate).toFixed(2)} per kilometre.`
            : `${v.type}: no master rate is set for this size yet.`;
        show('#m_vrate');
    }
    document.getElementById('mv_ok').addEventListener('click', async () => {
        const j = await post('/intake/' + CURRENT.id + '/vehicle', { line_id: Number(document.getElementById('mv_id').value), rate: document.getElementById('mv_rate').value, note: document.getElementById('mv_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; hide('#m_vrate'); renderDetail(); toast('success', j.message);
    });

    // ---------- one leg of the driving ----------
    function openLeg(id) {
        const s = (CURRENT.sectors || []).find(x => Number(x.id) === id);
        if (!s) return;
        document.getElementById('ml_id').value = id;
        document.getElementById('ml_day').value = s.day_no;
        document.getElementById('ml_kind').value = s.kind || 'transfer';
        document.getElementById('ml_from').value = s.from_place || '';
        document.getElementById('ml_to').value = s.to_place || '';
        document.getElementById('ml_km').value = s.km_by === 'manual' ? (s.km ?? '') : '';
        document.getElementById('ml_extra').value = s.extra_by === 'manual' ? (s.extra_km ?? '') : '';
        document.getElementById('ml_note').value = s.note || '';
        const both = [s.google_km != null ? 'Google: ' + Number(s.google_km).toLocaleString() + ' km' : (s.google_by && s.google_by !== 'none' ? 'Google: ' + (GBY[s.google_by] || s.google_by) : ''),
                      s.actual_km != null ? 'Past tours: ' + Number(s.actual_km).toLocaleString() + ' km over ' + s.samples + ' journey' + (Number(s.samples) === 1 ? '' : 's') : ''].filter(Boolean).join(' · ');
        document.getElementById('ml_hint').textContent = (CAN_MILEAGE ? 'A figure typed here is quoted over both Google\'s and the actual; leave it blank to quote by the setting. The actual kilometres go in the box on the row. ' : 'Typing kilometres over the figures needs the Mileage override privilege (Management). ') + (both ? both + ' — ' : '') + (s.why || '');
        document.getElementById('ml_km').disabled = !CAN_MILEAGE;
        document.getElementById('ml_extra').disabled = !CAN_MILEAGE;
        show('#m_leg');
    }
    document.getElementById('ml_ok').addEventListener('click', async () => {
        const id = document.getElementById('ml_id').value;
        const j = await post('/intake/' + CURRENT.id + '/sector/' + id, {
            day_no: document.getElementById('ml_day').value, kind: document.getElementById('ml_kind').value,
            from_place: document.getElementById('ml_from').value, to_place: document.getElementById('ml_to').value,
            km: document.getElementById('ml_km').value, extra_km: document.getElementById('ml_extra').value,
            note: document.getElementById('ml_note').value
        });
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; hide('#m_leg'); renderDetail();
    });

    // ---------- day services ----------
    const PAY_LABEL = {
        in_tour: 'Included in the tour price', prepaid: 'Prepaid by the agent', direct: 'Paid on the spot',
        own_arrangement: 'The agent arranges it themselves', not_arranged: 'Not to be arranged', unknown: 'Who settles it is not stated',
        no_charge: 'No charge — nothing to pay'
    };

    function svcDayOptions(sel) {
        const DD = CURRENT.departure_day;
        const opts = ['<option value="0">Applies to the whole tour</option>']
            .concat((CURRENT.days || []).map(d => `<option value="${d.day_no}">Night ${d.day_no}${d.day_date ? ' · ' + fmtDate(d.day_date) : ''}${d.area ? ' · ' + esc(d.area) : ''}</option>`))
            .concat((CURRENT.departure_rows || []).filter(d => !DD || Number(d.day_no) !== Number(DD.day_no)).map(d => `<option value="${d.day_no}">Line ${d.day_no} · on the departure day</option>`))
            .concat(DD ? [`<option value="${DD.day_no}">Departure day ${DD.day_no} · ${fmtDate(DD.date)}</option>`] : []);
        const el = document.getElementById('ms_day');
        el.innerHTML = opts.join('');
        el.value = String(sel ?? 0);
    }

    function renderSvcList(dayNo) {
        const all = (CURRENT.services || []).filter(s => Number(s.day_no) === Number(dayNo));
        const dayExc = (CURRENT.excursions || []).filter(x => Number(x.day_no) === Number(dayNo));
        const UNITW = { per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' };
        // the day's activities and excursions (Paid Activities & Services): costed at the rate copied from the master
        const excHtml = dayExc.map(x => `<div class="svcline" data-dayexc="${x.id}">
                <div class="grow"><b style="font-size:12.5px">${esc(x.name)}</b> <span class="pill pill-blue" style="margin-left:6px">activity</span>${Number(x.includes_transport) === 1 ? ' <span class="pill pill-green">transport included</span>' : ''}
                    <div class="help">${esc(x.currency)} ${Number(x.rate).toLocaleString()} ${esc(UNITW[x.unit] || x.unit)} × ${Number(x.qty)}${x.group_id ? ' · ' + esc(groupName(x.group_id) || 'group') : ''}</div>
                    ${x.description ? `<div class="help" style="margin-top:3px;font-style:italic">“${esc(x.description.length > 160 ? x.description.slice(0, 160) + '…' : x.description)}”</div>` : ''}</div>
                <div style="text-align:right;white-space:nowrap"><span class="pill pill-green">${esc(x.currency)} ${Number(x.amount).toLocaleString()}</span>
                    <div class="mt-1"><button class="btn btn-light btn-sm" data-editexc="${x.id}">Edit</button>
                    <button class="btn btn-light btn-sm" data-delexc2="${x.id}">Remove</button></div></div></div>`).join('');
        document.getElementById('ms_list').innerHTML = (all.length ? all.map(s => {
            if (s.free_visit_id) {
                // a free visit from the master: on the itinerary, never costed
                return `<div class="svcline" data-freeline="${s.id}">
                <div class="grow"><b style="font-size:12.5px">${esc(s.title)}</b> <span class="pill pill-green" style="margin-left:6px">free visit</span>${s.free_destination ? ` <span class="help">${esc(s.free_destination)}</span>` : ''}
                    <div class="help" style="color:var(--tf-green)">On the itinerary only — never costed.</div>
                    ${s.special_note ? `<div class="help" style="color:var(--tf-navy)">${esc(s.special_note)}</div>` : ''}
                    ${s.description ? `<div class="help" style="margin-top:3px;font-style:italic">“${esc(s.description.length > 160 ? s.description.slice(0, 160) + '…' : s.description)}”</div>` : ''}</div>
                <div style="text-align:right;white-space:nowrap"><span class="pill pill-grey">Not costed</span>
                    <div class="mt-1"><button class="btn btn-light btn-sm" data-editsvc="${s.id}">Edit</button>
                    <button class="btn btn-light btn-sm" data-delsvc="${s.id}">Remove</button></div></div></div>`;
            }
            const declined = s.pay_mode === 'own_arrangement' || s.pay_mode === 'not_arranged';
            // schema 39: an entrance or safari not in the Place to Visit / Entrance Fee Master, or with no rate for the day, is held — never free
            const notIn = !declined && s.match_state === 'not_in_master' && s.pay_mode !== 'direct';
            const free = !notIn && (s.pay_mode === 'no_charge' || s.match_state === 'no_charge');
            const held = !declined && !free && (s.match_state === 'rate_missing' || notIn);
            const unsure = !declined && !free && !held && (s.match_state === 'not_in_list' || s.amount_usd == null);
            const sug = s.suggest_code ? (MASTER || []).find(m => String(m.code).toLowerCase() === String(s.suggest_code).toLowerCase()) : null;
            const money = declined ? '<span class="pill pill-grey">No cost</span>'
                : (free ? '<span class="pill pill-green">No charge</span>'
                : (s.amount_usd != null
                    ? `<span class="pill pill-green">$${Number(s.amount_usd).toFixed(2)}</span>`
                      + (s.amount_lkr != null ? `<div class="help">LKR ${Number(s.amount_lkr).toLocaleString()} at ${Number(s.usd_rate).toFixed(2)}${s.rate_date ? ' of ' + fmtDate(s.rate_date) : ''}</div>` : '<div class="help">No USD rate entered yet</div>')
                    : (held ? (notIn ? '<span class="pill pill-red">Not in the master — held</span>' : '<span class="pill pill-red">Rate missing — held</span>' + (SEES_MASTER ? `<div class="mt-1"><a class="btn btn-light btn-sm" href="${MASTER_URL}" target="_blank">Set the rate in the master</a></div>` : '')) : '<span class="pill pill-amber">Unsure</span>')));
            // an unsure line is never forced to a price: No charge, the closest entry in the list, or the master
            const offer = unsure ? `<div class="mt-1 d-flex gap-1 flex-wrap">
                    <button class="btn btn-light btn-sm" data-nocharge="${s.id}" title="A stop with nothing to pay: on the programme, nothing costed">No charge</button>
                    ${sug ? `<button class="btn btn-light btn-sm" data-accept="${s.id}" data-code="${esc(sug.code)}" title="Price it from this entry in the list">Use “${esc(sug.name)}”${sug.usd ? ' $' + Number(sug.usd).toFixed(2) : ''}</button>` : ''}
                    <button class="btn btn-light btn-sm" data-editsvc="${s.id}" title="Pick the entry from the priced list by name">Pick from the list…</button></div>`
                // schema 39: an entrance or safari not in the master — held: the place picked from the master (one click for the closest spelling), or added there
                : (notIn ? `<div class="mt-1 d-flex gap-1 flex-wrap">${sug ? `<button class="btn btn-light btn-sm" data-accept="${s.id}" data-code="${esc(sug.code)}" title="Price it from this place of the master">Use “${esc(sug.name)}”</button>` : ''}
                    <button class="btn btn-light btn-sm" data-editsvc="${s.id}" title="Pick the place from the master by name">Pick the place…</button>${SEES_MASTER ? `<a class="btn btn-light btn-sm" href="${MASTER_URL}" target="_blank" title="Add the place to the master, with its rate or as Free">Add it in the master</a>` : ''}</div>` : '');
            return `<div class="svcline">
                <div class="grow">
                    <b style="font-size:12.5px">${esc(s.title)}</b>
                    <span class="pill pill-grey" style="margin-left:6px">${esc(s.kind)}</span>
                    ${s.origin === 'human' ? '<span class="pill pill-blue" style="margin-left:4px">yours</span>' : ''}
                    <div class="help">${esc(free ? PAY_LABEL.no_charge : (PAY_LABEL[s.pay_mode] || s.pay_mode))}${free ? '' : (s.qty ? ' · ' + s.qty + ' of them' : ' · one per person')}${s.unit_usd != null && !free ? ' · $' + Number(s.unit_usd).toFixed(2) + ' each' : ''}</div>
                    ${s.match_note ? `<div class="help" style="color:${free ? 'var(--tf-green)' : (unsure ? 'var(--tf-amber)' : 'var(--tf-red)')}">${esc(s.match_note)}</div>` : ''}
                    ${offer}
                    ${s.source_quote ? `<q>“${esc(s.source_quote)}”</q>` : ''}
                    ${s.special_note ? `<div class="help" style="color:var(--tf-navy)">${esc(s.special_note)}</div>` : ''}
                    ${s.description ? `<div class="help" style="margin-top:3px;font-style:italic">“${esc(s.description.length > 160 ? s.description.slice(0, 160) + '…' : s.description)}”</div>` : ''}
                </div>
                <div style="text-align:right;white-space:nowrap">${money}
                    <div class="mt-1"><button class="btn btn-light btn-sm" data-editsvc="${s.id}">Edit</button>
                    <button class="btn btn-light btn-sm" data-delsvc="${s.id}">Remove</button></div></div>
            </div>`;
        }).join('') : '') + excHtml || '<div class="empty">Nothing recorded for this day yet.</div>';
        document.querySelectorAll('#ms_list [data-editexc]').forEach(b => b.addEventListener('click', () => { hide('#m_svc'); openExc(Number(b.dataset.editexc)); }));
        document.querySelectorAll('#ms_list [data-delexc2]').forEach(b => b.addEventListener('click', async () => {
            if (!await ask('Are you sure you want to delete this record? The activity comes off the day and off the costing.', 'Remove')) return;
            const j = await post('/intake/' + CURRENT.id + '/excursion/' + b.dataset.delexc2, { delete: 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); renderSvcList(document.getElementById('ms_day').value); renderProgramme();
        }));

        document.querySelectorAll('#ms_list [data-editsvc]').forEach(b => b.addEventListener('click', () => fillSvcForm(Number(b.dataset.editsvc))));
        document.querySelectorAll('#ms_list [data-nocharge]').forEach(b => b.addEventListener('click', () => svcQuick(Number(b.dataset.nocharge), { pay_mode: 'no_charge' })));
        document.querySelectorAll('#ms_list [data-accept]').forEach(b => b.addEventListener('click', () => svcQuick(Number(b.dataset.accept), { fee_code: b.dataset.code })));
        document.querySelectorAll('#ms_list [data-delsvc]').forEach(b => b.addEventListener('click', async () => {
            if (!await ask('Are you sure you want to delete this record? The service line comes off the day and off the costing.', 'Remove')) return;
            const j = await post('/intake/' + CURRENT.id + '/service/' + b.dataset.delsvc, { delete: 1 });
            if (j.status !== 200) { toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); renderSvcList(document.getElementById('ms_day').value); renderProgramme();
        }));
    }

    // ---------- the day programme from the two masters (schema 29) ----------
    // Free Visits: a destination's sightseeing and en-route stops, never costed. Paid Activities &
    // Services: the priced entrances and tickets (by the entry's id) and the activities and
    // excursions (by code), each costed at the master's rate. Read once per page.
    let PROG = null;
    let PROG_DAY = 0;
    async function loadProgramme() {
        if (PROG !== null) return PROG;
        try { const j = await api('/programme_masters'); PROG = (j && j.data) || null; } catch (e) { PROG = null; }
        if (!PROG) PROG = { available: false, destinations: [], free: [], entrances: [], activities: [] };
        return PROG;
    }
    // The list opens on the night's area when a destination is called exactly that — nothing is
    // worked out: "Kandy" opens Kandy; "Sigiriya / Dambulla / Kandalama" opens the ones of those
    // three the masters have; an area no destination is called opens All destinations.
    function programmeDest(dayNo) {
        const P = PROG || { destinations: [] };
        const sel = document.getElementById('mm_dest');
        const d = (CURRENT.days || []).find(x => Number(x.day_no) === Number(dayNo));
        const dests = P.destinations || [];
        const known = t => dests.find(x => x.toLowerCase() === String(t || '').trim().toLowerCase());
        const parts = d ? [...new Set(String(d.area || '').split('/').map(known).filter(Boolean))] : [];
        sel.innerHTML = (parts.length > 1 ? `<option value="any:${esc(parts.join('|'))}">${esc(parts.join(' / '))}</option>` : '')
            + '<option value="">All destinations</option>' + dests.map(x => `<option value="${esc(x)}">${esc(x)}</option>`).join('');
        sel.value = parts.length > 1 ? 'any:' + parts.join('|') : (parts[0] || '');
        document.getElementById('mm_q').value = '';
    }
    function renderProgramme() {
        const P = PROG;
        const wrap = document.getElementById('ms_masters');
        if (!wrap) return;
        if (!P || !P.available || !CURRENT || CURRENT.locked) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';
        const dayNo = Number(PROG_DAY);
        const dsel = document.getElementById('mm_dest');
        const dest = dsel.value.toLowerCase();
        const destSet = dest.startsWith('any:') ? dest.slice(4).split('|') : (dest ? [dest] : []);
        const destWord = dsel.selectedIndex >= 0 ? dsel.options[dsel.selectedIndex].text : '';
        const q = document.getElementById('mm_q').value.trim().toLowerCase();
        const inScope = x => q ? (String(x.name) + ' ' + String(x.destination || '')).toLowerCase().includes(q) : (!destSet.length || destSet.includes(String(x.destination || '').toLowerCase()));
        const shown = (rows, n) => rows.length > n ? rows.slice(0, n) : rows;
        const onDaySvc = (CURRENT.services || []).filter(s => Number(s.day_no) === dayNo);
        const onDayExc = (CURRENT.excursions || []).filter(x => Number(x.day_no) === dayNo);
        const showDest = !!q || destSet.length !== 1;
        const there = '<span class="pill pill-grey">on the day</span>';

        const free = (P.free || []).filter(inScope);
        document.getElementById('mm_free').innerHTML = free.length ? shown(free, 80).map(f => `<div class="mmrow" data-mmfree="${f.id}">
                <div class="grow"><b>${esc(f.name)}</b> <span class="help">${esc(f.kind_word || '')}</span>${showDest ? `<div class="dest">${f.destination ? esc(f.destination) : 'not placed yet'}</div>` : ''}</div>
                ${onDaySvc.some(s => Number(s.free_visit_id) === Number(f.id)) ? there : `<button class="btn btn-light btn-sm" data-addfree="${f.id}">Add</button>`}</div>`).join('')
                + (free.length > 80 ? `<div class="mm-empty">${free.length - 80} more — type to narrow.</div>` : '')
            : `<div class="mm-empty">${q ? 'No free visit matches.' : (destSet.length ? 'No free visits under ' + esc(destWord) + ' yet.' : 'The Free Visits master is empty.')}${SEES_FREE ? ` <a href="${FREE_URL}" target="_blank">Add them under Free Visits</a>.` : ''}</div>`;

        const UNITW = { per_person: 'per person', per_group: 'per group', per_vehicle: 'per vehicle' };
        const ents = (P.entrances || []).filter(inScope).map(e => Object.assign({ t: 'e' }, e));
        const acts = (P.activities || []).filter(inScope).map(a => Object.assign({ t: 'a' }, a));
        const paid = ents.concat(acts).sort((a, b) => String(a.name).localeCompare(String(b.name)));
        const rateOf = x => x.t === 'a'
            ? `${esc(x.currency)} ${Number(x.rate).toLocaleString()} ${esc(UNITW[x.unit] || x.unit)}${Number(x.includes_transport) === 1 ? ' · transport included' : ''}${x.valid_to ? ' · to ' + esc(x.valid_to) : ''}`
            : x.master ? (x.rate_state === 'priced' ? `${esc(x.rate_text)}${x.pay_mode === 'prepaid' ? ' · prepaid' : (x.pay_mode === 'direct' ? ' · paid direct' : '')}` : (x.rate_state === 'free' ? '<span class="pill pill-green">free / no entrance fee</span>' : '<span class="pill pill-red">no rate — held</span>'))
            : (x.rate_state === 'priced' ? `USD ${Number(x.usd).toFixed(2)} a head${x.pay_mode === 'prepaid' ? ' · prepaid' : (x.pay_mode === 'direct' ? ' · paid direct' : '')}`
               : (x.rate_state === 'rupees' ? '<span class="pill pill-red">check the figure</span>' : '<span class="pill pill-amber">no amount yet</span>'));
        const isThere = x => x.t === 'a' ? onDayExc.some(e => String(e.code) === String(x.code)) : onDaySvc.some(s => x.master ? Number(s.place_id) === Number(x.place_id) : Number(s.fee_id) === Number(x.id));
        document.getElementById('mm_paid').innerHTML = paid.length ? shown(paid, 80).map(x => `<div class="mmrow" data-mmpaid="${x.t}:${esc(x.t === 'a' ? x.code : (x.master ? 'p' + x.place_id : x.id))}">
                <div class="grow"><b>${esc(x.name)}</b> <span class="pill ${x.t === 'a' ? 'pill-blue' : 'pill-grey'}">${x.t === 'a' ? 'activity' : (x.master ? (x.jeep ? 'safari park' : 'entrance') : 'entrance / ticket')}</span>
                    <div class="help">${rateOf(x)}</div>${showDest ? `<div class="dest">${x.destination ? esc(x.destination) : 'not placed yet'}</div>` : ''}</div>
                ${isThere(x) ? there : (x.t === 'a' ? `<button class="btn btn-light btn-sm" data-addact="${esc(x.code)}">Add</button>` : `<button class="btn btn-light btn-sm" data-addent="${x.master ? 'p' + x.place_id : x.id}">Add</button>`)}</div>`).join('')
                + (paid.length > 80 ? `<div class="mm-empty">${paid.length - 80} more — type to narrow.</div>` : '')
            : `<div class="mm-empty">${q ? 'Nothing paid matches.' : (destSet.length ? 'Nothing paid under ' + esc(destWord) + ' yet.' : 'The paid lists are empty.')}${SEES_PAID ? ` <a href="${PAID_URL}" target="_blank">See Paid Activities &amp; Services</a>.` : ''}</div>`;

        document.getElementById('mm_links').innerHTML = [SEES_FREE ? `<a href="${FREE_URL}" target="_blank">Free Visits master</a>` : '', SEES_PAID ? `<a href="${PAID_URL}" target="_blank">Paid Activities &amp; Services master</a>` : ''].filter(Boolean).join(' · ');

        const after = (j) => {
            if (j.status !== 200) { toast('error', j.message); return; }
            CURRENT = j.data; renderDetail(); renderSvcList(dayNo); renderProgramme(); toast('success', j.message);
        };
        document.querySelectorAll('#mm_free [data-addfree]').forEach(b => b.addEventListener('click', async () => {
            b.disabled = true;
            after(await post('/intake/' + CURRENT.id + '/free_visit', { day_no: dayNo, free_visit_id: Number(b.dataset.addfree) }));
        }));
        document.querySelectorAll('#mm_paid [data-addent]').forEach(b => b.addEventListener('click', async () => {
            const key = String(b.dataset.addent);
            const e = (P.entrances || []).find(x => (x.master ? 'p' + x.place_id : String(x.id)) === key);
            if (!e) return;
            b.disabled = true;
            after(await post('/intake/' + CURRENT.id + '/service', Object.assign({ day_no: dayNo, title: e.name, kind: '', pay_mode: 'unknown' }, e.master ? { place_id: e.place_id, fee_id: e.id || null } : { fee_id: e.id })));
        }));
        document.querySelectorAll('#mm_paid [data-addact]').forEach(b => b.addEventListener('click', async () => {
            if (dayNo < 1) { toast('error', 'An activity sits on a night of the tour — open the night it happens on and add it there.'); return; }
            b.disabled = true;
            after(await post('/intake/' + CURRENT.id + '/excursion', { code: b.dataset.addact, day_no: dayNo, group_id: null }));
        }));
    }
    document.getElementById('mm_dest').addEventListener('change', () => renderProgramme());
    document.getElementById('mm_q').addEventListener('input', () => renderProgramme());

    function clearSvcForm(dayNo) {
        document.getElementById('ms_id').value = '';
        document.getElementById('ms_title_in').value = '';
        document.getElementById('ms_kind').value = '';
        document.getElementById('ms_qty').value = '';
        document.getElementById('ms_pay').value = 'unknown';
        document.getElementById('ms_note').value = '';
        document.getElementById('ms_desc').value = '';
        document.getElementById('ms_descwrap').style.display = 'none';
        document.getElementById('ms_formtitle').textContent = 'Add a service';
        document.getElementById('ms_clear').style.display = 'none';
        svcDayOptions(dayNo);
    }

    // one click on an unsure line: No charge, or the priced entry offered — the line's own words stay
    async function svcQuick(id, change) {
        const s = (CURRENT.services || []).find(x => Number(x.id) === id);
        if (!s) return;
        const body = Object.assign({ day_no: s.day_no, title: s.title, kind: s.kind, qty: s.qty ?? '', pay_mode: s.pay_mode, special_note: s.special_note || '', description: s.description || '' }, change);
        const j = await post('/intake/' + CURRENT.id + '/service/' + id, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; renderDetail();
        const open = document.querySelector('#m_svc') && document.querySelector('#m_svc').classList.contains('show');
        if (open) renderSvcList(document.getElementById('ms_day').value);
        toast('success', j.message);
    }

    function fillSvcForm(id) {
        const s = (CURRENT.services || []).find(x => Number(x.id) === id);
        if (!s) return;
        document.getElementById('ms_id').value = s.id;
        document.getElementById('ms_title_in').value = s.title || '';
        document.getElementById('ms_kind').value = s.kind || '';
        document.getElementById('ms_qty').value = s.qty ?? '';
        document.getElementById('ms_pay').value = s.pay_mode || 'unknown';
        document.getElementById('ms_note').value = s.special_note || '';
        document.getElementById('ms_desc').value = s.description || '';
        document.getElementById('ms_descwrap').style.display = '';
        document.getElementById('ms_formtitle').textContent = 'Change this service';
        document.getElementById('ms_clear').style.display = '';
        svcDayOptions(s.day_no);
        renderMail(s.source_quote || null);
    }

    // The priced list is fetched once and offered as you type, so a name that exists is
    // spelled the way the list spells it and matches instead of landing as "not in list".
    let MASTER = null;
    async function loadMaster() {
        if (MASTER !== null) return;
        try {
            const j = await api('/services');
            MASTER = (j.data && j.data.items) || [];
        } catch (e) { MASTER = []; }
        document.getElementById('ms_master').innerHTML = MASTER
            .map(m => `<option value="${esc(m.name)}">${esc(m.location || '')}${m.usd ? ' · $' + Number(m.usd).toFixed(2) : ''}</option>`).join('');
    }

    function openServices(dayNo, editId) {
        // the "Use <entry>" offer on an unsure line needs the list; the first open draws the list again once it is here
        const first = MASTER === null;
        loadMaster().then(() => { if (first && document.querySelector('#m_svc').classList.contains('show')) renderSvcList(document.getElementById('ms_day').value); });
        const d = (CURRENT.days || []).find(x => Number(x.day_no) === Number(dayNo));
        const DDs = CURRENT.departure_day;
        document.getElementById('ms_title').textContent = Number(dayNo) === 0
            ? 'Services for the whole tour'
            : (DDs && Number(dayNo) === Number(DDs.day_no) ? `Departure day · ${fmtDate(DDs.date)}`
            : `Night ${dayNo}${d && d.day_date ? ' · ' + fmtDate(d.day_date) : ''}${d && d.area ? ' · ' + d.area : ''}`);
        clearSvcForm(dayNo);
        renderSvcList(dayNo);
        if (editId) fillSvcForm(editId);
        PROG_DAY = Number(dayNo);
        loadProgramme().then(() => { programmeDest(dayNo); renderProgramme(); });
        show('#m_svc');
    }

    document.getElementById('ms_clear').addEventListener('click', () => clearSvcForm(document.getElementById('ms_day').value));
    document.getElementById('ms_ok').addEventListener('click', async () => {
        const btn = document.getElementById('ms_ok');
        if (btn.disabled) return;   // pressed again before the first save answered
        const id = document.getElementById('ms_id').value;
        const body = {
            day_no: document.getElementById('ms_day').value,
            title: document.getElementById('ms_title_in').value,
            kind: document.getElementById('ms_kind').value,
            qty: document.getElementById('ms_qty').value,
            pay_mode: document.getElementById('ms_pay').value,
            special_note: document.getElementById('ms_note').value,
            ...(id ? { description: document.getElementById('ms_desc').value } : {})
        };
        btn.disabled = true;
        let j;
        try { j = await post('/intake/' + CURRENT.id + '/service' + (id ? '/' + id : ''), body); }
        finally { btn.disabled = false; }
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data;
        renderDetail();
        const day = body.day_no;
        clearSvcForm(day);
        renderSvcList(day);
        if (Number(day) !== Number(PROG_DAY) && PROG) { PROG_DAY = Number(day); programmeDest(day); }
        renderProgramme();
        toast('success', j.message);
    });

    const STATUS_PILL = { new: ['New', 'pill-blue'], working: ['Working', 'pill-blue'], quoted: ['Quoted', 'pill-amber'], confirmed: ['Confirmed', 'pill-green'], declined: ['Declined', 'pill-grey'], cancelled: ['Cancelled', 'pill-grey'], converted: ['Converted', 'pill-grey'] };
    function statusPill(st) { const [l, c] = STATUS_PILL[st] || [st, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`; }

    /** The read-only strip on a confirmed inquiry: where each desk stands. Counts, never the other desks' money. */
    function progressStrip(it) {
        const p = it.progress;
        if (!p) return '';
        const h = p.hotel || {}, t = p.transport || {};
        const TL = { to_allocate:'to allocate', allocated:'allocated', scheduled:'scheduled', pack_issued:'pack issued', running:'running', returned:'returned', settled:'settled', cancelled:'cancelled' };
        const HL = { to_book:'to book', requested:'requested', confirmed:'confirmed', voucher_issued:'voucher issued', problem:'problem', cancelled:'cancelled' };
        const cards = (p.hotel_cards || []).map(c => `<span class="pill ${c.status === 'problem' ? 'pill-red' : (c.status === 'voucher_issued' ? 'pill-green' : (c.status === 'confirmed' ? 'pill-teal' : 'pill-grey'))}" title="${esc(c.problem_note || '')}">${esc(c.hotel_name || (c.area ? c.area + ': no hotel named' : 'no hotel named'))} · ${esc(HL[c.status] || c.status)}</span>`).join(' ');
        const AL = { to_book:'to book', requested:'requested', confirmed:'confirmed', not_needed:'nothing to arrange', problem:'problem', cancelled:'cancelled', pay_at_gate:'pay at gate' };
        const acts = (p.service_cards || []).map(c => `<span class="pill ${c.status === 'problem' ? 'pill-red' : (c.status === 'confirmed' ? 'pill-green' : (c.status === 'not_needed' ? 'pill-teal' : (c.status === 'pay_at_gate' ? 'pill-amber' : 'pill-grey')))}" title="${esc(c.problem_note || '')}">${esc(c.title)} · ${esc(AL[c.status] || c.status)}</span>`).join(' ');
        return `<div class="tf-card" style="border-left:3px solid var(--tf-green)"><div class="tf-card-h"><h6>On the desks — tour ${esc(p.tour_no)}</h6><span class="help">${esc(p.stage)}${p.amending ? ' · being amended' : ''}${p.quote_version ? ` · sold as version ${p.quote_version}` : ''}</span></div>
            <div class="tf-card-b">
                ${p.strip ? `<div class="mb-2">${window.tdStrip(p.strip, { open: deskOpen(p.tour_id) })}</div>` : ''}
                ${CAN.board ? `<div class="mb-2"><a class="btn btn-light btn-sm" href="${BOARD_URL}/tour/${p.tour_id}/record"><i class="bi bi-journal-text"></i> Complete record of the tour</a></div>` : `<div class="help mb-2">The complete record of the tour is on the Tour Board, which this sign-in does not hold.</div>`}
                <div class="crewrow head"><span>Hotels</span><span>Vehicle</span></div>
                <div class="crewrow"><span><b>${h.booked ?? 0} of ${h.total ?? 0}</b> confirmed · ${h.voucher_issued ?? 0} voucher(s)${h.problem ? ` · <span class="pill pill-red">${h.problem} problem</span>` : ''}${h.flags ? ` · <span class="pill pill-amber">${h.flags} waiting on this desk</span>` : ''}</span>
                    <span>${t.total ? `<b>${esc(TL[t.status] || t.status)}</b>` : 'no vehicle line'}${t.amended ? ' · <span class="pill pill-amber">check after amendment</span>' : ''}</span></div>
                <div class="mt-2" style="display:flex;gap:6px;flex-wrap:wrap">${cards}</div>
                ${acts ? `<div class="crewrow head mt-2"><span>Activities &amp; tickets</span></div><div class="mt-1" style="display:flex;gap:6px;flex-wrap:wrap">${acts}</div>` : ''}
                <div class="help mt-2">Counts only. A problem raised by the Hotel Reservations &amp; Activities desk is the agent's to answer: offer an alternative and ask Management to amend.</div>
            </div></div>`;
    }

    async function setStatus(status) {
        const j = await post('/intake/' + CURRENT.id + '/status', { status });
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; renderDetail(); toast('success', j.message);
    }

    /**
     * The agent said yes. Three steps, in this order: the signed-in person's password (checked on the server; a wrong
     * one stops here and nothing changes), then the final warning with Cancel / Confirm Tour, and only then the
     * confirmation itself — one transaction: the tour, the hotel cards, the transport cards — or nothing. Who
     * confirmed, and when, goes on the tour, the inquiry and the Activity log.
     */
    async function confirmTour() {
        const it = CURRENT;
        const nights = (it.days || []).length, vehicles = (it.vehicles || []).length;
        // the stays as Confirm Tour makes them: nights in a row at the same hotel become one stay
        const stays = (() => { let n = 0, prev = null; (it.days || []).slice().sort((a, b) => Number(a.day_no) - Number(b.day_no)).forEach(d => { const acc = String(d.hotel_acc || '').trim(), hid = Number(d.hotel_id || 0), nm = String(d.hotel_name_raw || '').trim(); const key = acc ? 'acc:' + acc : (hid ? 'hid:' + hid : (nm ? 'name:' + nm.toLowerCase() : 'none:' + d.day_no)); if (key !== prev) n++; prev = key; }); return n; })();
        const unnamed = (it.days || []).filter(d => !(d.hotel_name_raw || '').trim() && !d.hotel_id).length;
        const issued = (it.quotes || []).find(q => q.status === 'issued');
        if (!issued) {
            await Swal.fire({ icon: 'info', title: 'No quotation issued yet', text: 'The agent accepts a quotation that was sent. Press Go for Quotation, then Issue the version you sent; Confirm records which version was sold.' });
            return;
        }
        // 0 — schema 40: the lunch places, day by day, from the Meal Master — change one or add one before the tour goes to the desks
        if (!(await lunchReview(it))) return;
        // 0b — tf-tourmail-r1: every hotel's booking request, with the address it goes to — sent when Confirm Tour is pressed
        let mail = null;
        try {
            const mp = await api('/intake/' + CURRENT.id + '/mail_preview');
            if (mp.status !== 200) { await Swal.fire({ icon: 'error', title: 'Hotel e-mails', text: mp.message || 'Could not read the hotels\' addresses. Nothing was changed.' }); return; }
            if (window.AvhTourMails && (mp.data.stays || []).length) {
                mail = await window.AvhTourMails.review(mp.data, { title: 'Hotel booking requests — ' + it.inquiry_no, intro: 'When you confirm, <b>each hotel below gets its booking request by e-mail at once</b>, as listed. Check the addresses; untick one to leave it out.', ok: 'Continue to confirm' });
                if (!mail) return;
            }
        } catch (e) { await Swal.fire({ icon: 'error', title: 'Hotel e-mails', text: 'Could not read the hotels\' addresses. Nothing was changed.' }); return; }
        // 1 — the password
        const pw = await Swal.fire({
            title: 'Confirm ' + it.inquiry_no + ' — your password',
            html: `<div style="text-align:left;font-size:13.5px">Confirming a tour releases it to Hotel Bookings and Transport. Enter <b>your own</b> sign-in password to go on; it is checked and not kept.</div>`,
            input: 'password', inputPlaceholder: 'Your password', inputAttributes: { autocomplete: 'current-password' },
            showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#78b448',
            inputValidator: v => (v ? undefined : 'Enter your password.'),
        });
        if (!pw.isConfirmed) return;
        const auth = await post('/intake/' + CURRENT.id + '/reauth', { password: pw.value || '' });
        if (auth.status !== 200 || !auth.ok) {
            await Swal.fire({ icon: 'error', title: 'Not confirmed', text: auth.message || 'Password not recognised. Nothing was changed.' });
            return;
        }
        // 2 — the final warning
        const arranged = (it.services || []).filter(x => !['guide', 'transport'].includes(x.kind) && !['own_arrangement', 'not_arranged', 'no_charge'].includes(x.pay_mode) && x.match_state !== 'no_charge').length + (it.excursions || []).length;
        const r = await Swal.fire({
            icon: 'warning',
            title: 'Confirm ' + it.inquiry_no + '?',
            html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fdf3e1;border:1px solid #f0d9a8;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#7a4b00"><b>This cannot be undone from here.</b> The tour is marked <b>Confirmed</b>, given its number, and released to Hotel Bookings and Transport, who start work on it. Only Management can cancel it afterwards, with a reason.</div>
                The agent has accepted <b>quotation version ${issued.version}</b>${issued.sell_usd ? ` (USD ${Number(issued.sell_usd).toLocaleString()})` : ''}. Confirm Tour takes this inquiry off the Open list as <b>confirmed</b>, and puts <b>one job on Hotel Bookings</b> (${nights} night${nights === 1 ? '' : 's'} in ${stays} hotel stay${stays === 1 ? '' : 's'}, ${arranged} activity or ticket line${arranged === 1 ? '' : 's'}) and <b>one job on Transport</b> (${vehicles} vehicle${vehicles === 1 ? '' : 's'}); both red counts go up by one. This inquiry becomes read-only, the record of what was sold.
                ${unnamed ? `<br><br><span style="color:#b7791f">${unnamed} night${unnamed === 1 ? ' has' : 's have'} no hotel named. ${unnamed === 1 ? 'It' : 'They'} will be put on the desk flagged, not filled in.</span>` : ''}
                <br><br>${mail ? `<b>E-mails, the moment you press Confirm Tour:</b> ${mail.sending} hotel booking request${mail.sending === 1 ? '' : 's'}${mail.notSending ? ` (<span style="color:#b23a32">${mail.notSending} will NOT be sent — listed after</span>)` : ''} and each restaurant's booking voucher as listed before, from your mailbox. ` : ''}No invoice is raised and nothing is written to the old tour tables. Your name and the time go on the tour and the Activity log.</div>`,
            showCancelButton: true, cancelButtonText: 'Cancel', confirmButtonText: 'Confirm Tour', confirmButtonColor: '#78b448', focusCancel: true,
        });
        if (!r.isConfirmed) return;
        // 3 — the confirmation, the password checked once more with it
        Swal.fire({ title: 'Confirming ' + it.inquiry_no + '…', html: 'Sending the hotels\' and restaurants\' e-mails — this can take a minute. Please keep this page open.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let j;
        try { j = await post('/intake/' + CURRENT.id + '/confirm', mail ? { password: pw.value || '', mail } : { password: pw.value || '' }); }
        catch (e) {
            // the server did not answer properly: the tour may or may not be confirmed — say so, and where to look
            await Swal.fire({ icon: 'error', title: 'No clear answer from the server', html: 'The page did not get an answer it could read. <b>Do not confirm again yet</b>: open the inquiry list (it shows <b>Confirmed</b> if it went through) and <b>Sent Mails</b> on Hotel Bookings to see which e-mails went.' });
            loadList(); return;
        }
        Swal.close();
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; renderDetail(); loadList();
        const p = CURRENT.progress || {};
        const SB = { sent: ['emailed', '#1d6f47'], kept: ['already sent', '#1d6f47'], not_sent: ['not sent', '#b7791f'], not_issued: ['no voucher', '#b23a32'] };
        const mb = (j.meal_bookings || []).map(b => `<tr><td style="padding:3px 6px">${esc(b.restaurant)}</td><td style="padding:3px 6px">${esc(b.voucher_no || '—')}</td><td style="padding:3px 6px;color:${(SB[b.status] || ['', '#333'])[1]}">${esc((SB[b.status] || [b.status])[0])}${b.to && ['sent', 'kept'].includes(b.status) ? ' to ' + esc(b.to) : ''}${b.why && !['sent', 'kept'].includes(b.status) ? '<div style="font-size:11.5px;color:#6b7a8c">' + esc(b.why) + '</div>' : ''}</td></tr>`).join('');
        // tf-tourmail-r1: what happened to every hotel's and restaurant's mail — the ones that did not go in red, with why
        const tm = window.AvhTourMails && (j.hotel_mails || []).length ? window.AvhTourMails.resultsHtml(j.hotel_mails, j.meal_bookings) : '';
        const tmBad = (j.hotel_mails || []).some(r => r.status === 'not_sent') || (j.meal_bookings || []).some(b => !['sent', 'kept'].includes(b.status));
        await Swal.fire({ icon: tmBad ? 'warning' : 'success', title: 'Tour ' + (p.tour_no || '') + ' confirmed', width: (mb || tm) ? 860 : undefined, html: `<div style="text-align:left;font-size:13.5px">${esc(String(j.message).split(' Restaurants:')[0].split(' Hotels:')[0])}<br><br>The inquiry has left the Open list and shows under <b>Confirmed</b>. The Hotel Desk and the Transport Desk each have one new job; the red counts on the bar show it.
            ${tm ? `<div style="margin-top:10px"><b>E-mails</b>${tm}<div style="font-size:12px;margin-top:6px"><a href="${HOTELS_URL}/mails?tour_id=${j.tour_id || ''}" target="_blank">See the mails as they went (Sent Mails)</a></div></div>`
                 : (mb ? `<div style="margin-top:10px"><b>Restaurants booked</b><table style="width:100%;border-collapse:collapse;font-size:12.5px;margin-top:4px" id="conf_meals">${mb}</table><div style="font-size:12px;color:#6b7a8c;margin-top:4px">What could not go is on the Activities desk, to send from there.</div></div>` : '')}</div>`, confirmButtonText: 'Done' });
        if (window.AvhMailAlert) window.AvhMailAlert();
        refreshBadges();
    }

    /**
     * Before Confirm Tour (schema 40): the lunch place of every day as the desks will get it — the restaurant from the Meal
     * Master and what it costs (in-house), + to add one, Change to pick another or add a new restaurant. The price sold is
     * the quotation version the agent accepted; a lunch changed since it was issued changes the cost, not the price.
     */
    async function lunchReview(it) {
        const M = it.meals;
        if (!M || !M.available || M.locked || !(M.days || []).length) return true;
        const n2 = v => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const btnCss = 'margin:0;padding:2px 10px;font-size:12px;background:#eef1f5;color:#1d2733';
        // where each restaurant's booking voucher will go, and what it states (the Meal Master's LKR rates)
        const book = l => {
            const lk = l.lkr != null ? `LKR ${n2(l.lkr)}` : '<span style="color:#b7791f">no LKR rate — no voucher</span>';
            const to = l.book_email ? esc(l.book_email) : '<span style="color:#b7791f">no email — not sent</span>';
            return `${lk}<div style="font-size:11px;color:#6b7a8c">${to}${l.book_contact ? ' · ' + esc(l.book_contact) : ''}${l.book_phone ? ' · ' + esc(l.book_phone) : ''}</div>`;
        };
        let nVouchers = 0, nShort = 0;
        const rows = M.days.map(r => {
            const c = r.meals.lunch, mine = c.lines.filter(l => l.meal_id), old = c.lines.filter(l => !l.meal_id && l.ours), notOurs = c.lines.filter(l => !l.ours);
            const dinners = r.meals.dinner.lines.filter(l => l.meal_id);
            let cell, vcell = '', btn = '';
            if (c.by_hotel && !mine.length) cell = `<span style="color:#6b7a8c">lunch at the hotel (${esc(r.meal_plan)})</span>`;
            else if (mine.length) {
                cell = mine.map(l => `<b>${esc(l.restaurant || l.title)}</b>${l.location ? ' · ' + esc(l.location) : ''}${l.blocks ? ' <span style="color:#b23a32">held</span>' : (l.amount_usd != null ? ` · USD ${Number(l.amount_usd).toFixed(2)}` : '')}${l.auto ? ' <span style="color:#2a8c82;font-size:11px">auto</span>' : ''}`).join('<br>');
                vcell = mine.map(book).join('');
                btn = `<button type="button" class="swal2-styled" style="${btnCss}" data-lprev="${r.day_no}|${mine[0].id}">Change</button>`;
            } else if (old.length) {
                cell = `<span style="color:#b7791f">“${esc(old[0].title)}” — no restaurant</span>`;
                btn = `<button type="button" class="swal2-styled" style="${btnCss}" data-lprev="${r.day_no}|${old[0].id}">+</button>`;
            } else if (notOurs.length) cell = `<span style="color:#6b7a8c">${esc(notOurs[0].title)} — not ours</span>`;
            else {
                cell = `<span style="color:#6b7a8c">no lunch${c.skipped ? ' (taken off)' : ''}</span>`;
                btn = `<button type="button" class="swal2-styled" style="${btnCss}" data-lprev="${r.day_no}|">+</button>`;
            }
            if (dinners.length) {
                cell += dinners.map(l => `<div style="margin-top:3px">Dinner: <b>${esc(l.restaurant || l.title)}</b></div>`).join('');
                vcell += dinners.map(book).join('');
            }
            [...mine, ...dinners].forEach(l => { nVouchers++; if (l.lkr == null || !l.book_email) nShort++; });
            return `<tr style="border-top:1px solid #eef1f5"><td style="padding:5px 6px;white-space:nowrap;vertical-align:top">${r.departure ? 'Dep.' : 'Day ' + r.day_no}${r.date ? '<div style="font-size:11px;color:#6b7a8c">' + fmtDate(r.date) + '</div>' : ''}</td><td style="padding:5px 6px;font-size:12px;vertical-align:top">${esc(r.route || '—')}</td><td style="padding:5px 6px;vertical-align:top">${cell}</td><td style="padding:5px 6px;font-size:12px;vertical-align:top">${vcell}</td><td style="padding:5px 6px;text-align:right;vertical-align:top">${btn}</td></tr>`;
        }).join('');
        const iss = M.issued, moved = iss && Math.abs(Number(iss.meals_usd) - Number(M.meals_usd)) > 0.004;
        let pick = null;
        const r = await Swal.fire({
            title: 'Restaurants — ' + it.inquiry_no, width: 980,
            html: `<div style="text-align:left;font-size:13px"><div style="margin-bottom:8px">When you confirm, <b>each restaurant below is booked at once</b>: its booking voucher (the amount in LKR from the Meal Master) is emailed to the booking email shown, from your mailbox. Change or add a lunch place here first if needed.</div>
                <table style="width:100%;border-collapse:collapse" id="lprev_tbl"><thead><tr style="font-size:11px;text-transform:uppercase;color:#6b7a8c"><th style="text-align:left;padding:4px 6px">Day</th><th style="text-align:left;padding:4px 6px">Route</th><th style="text-align:left;padding:4px 6px">Lunch place (in-house USD)</th><th style="text-align:left;padding:4px 6px">Booking voucher</th><th></th></tr></thead><tbody>${rows}</tbody></table>
                <div style="margin-top:8px;font-size:12px">${nVouchers} booking voucher${nVouchers === 1 ? '' : 's'} to send${nShort ? ` — <span style="color:#b7791f">${nShort} cannot go yet (no LKR rate or no email); the Activities desk finishes ${nShort === 1 ? 'it' : 'them'}</span>` : ''}. Meals now <b>USD ${Number(M.meals_usd || 0).toFixed(2)}</b>${iss ? ` · quotation version ${iss.version} carries <b>USD ${Number(iss.meals_usd).toFixed(2)}</b>${moved ? ' — <span style="color:#b7791f">changed since it was issued: the price sold stays as version ' + iss.version + '; only the cost moves</span>' : ''}` : ''}</div></div>`,
            showCancelButton: true, confirmButtonText: 'Continue to confirm', cancelButtonText: 'Cancel', confirmButtonColor: '#78b448',
            didOpen: () => { Swal.getHtmlContainer().querySelectorAll('[data-lprev]').forEach(b => b.addEventListener('click', () => { pick = b.dataset.lprev; Swal.close(); })); },
        });
        if (pick !== null) {
            const [day, sid] = pick.split('|');
            openMealPick(Number(day), 'lunch', sid ? Number(sid) : null, null);   // then Confirm Tour again
            return false;
        }
        return !!r.isConfirmed;
    }

    /** One place every hand edit goes through, so they all behave the same way. */
    // ---------------------------------------------------------------- schema 40: Meals (Half Board), from the Meal Master
    const MEAL_STATE = { hotel: ['By the hotel', 'pill-green'], arranged: ['Arranged', 'pill-green'], held: ['Held', 'pill-red'], conflict: ['Hotel gives it too', 'pill-red'],
                         duplicate: ['Twice for the same people', 'pill-red'], old: ['Old line', 'pill-amber'], declined: ['Not ours to arrange', 'pill-grey'], open: ['', ''] };
    function mealCell(r, type, locked) {
        const c = r.meals[type];
        const [pt, pc] = MEAL_STATE[c.state] || [c.state, 'pill-grey'];
        const lines = c.lines.map(l => `<div class="mb-1" data-mealline="${l.id}">
            <b>${esc(l.restaurant || l.title)}</b>${l.code ? ` <span class="mono help">${esc(l.code)}</span>` : ''}${l.auto ? ' <span class="lp-auto" title="Picked by the system from the route">auto</span>' : ''}
            <div class="help">${l.meal_id ? `${l.qty_adult != null ? Number(l.qty_adult) + 'A' : ''}${Number(l.qty_child) ? ' + ' + Number(l.qty_child) + 'C' : ''}${l.meal_time ? ' · ' + esc(l.meal_time) : ''}${l.amount_cur != null ? ' · ' + esc(l.currency) + ' ' + Number(l.amount_cur).toLocaleString(undefined, { minimumFractionDigits: 2 }) : ''}${l.currency && l.currency !== 'USD' && l.amount_usd != null ? ' (USD ' + Number(l.amount_usd).toFixed(2) + ')' : ''}` : esc(l.title) + (l.amount_usd != null ? ' · USD ' + Number(l.amount_usd).toFixed(2) : '') + (l.ours ? '' : ' · ' + esc(String(l.pay_mode).replace('_', ' ')))}</div>
            ${l.blocks ? `<div class="help" style="color:var(--tf-amber)">${esc(l.note || l.why || '')}</div>` : ''}
            ${l.ok_note ? `<div class="help" style="color:var(--tf-navy)">Outside although the hotel gives it: ${esc(l.ok_note)}</div>` : ''}
            ${locked ? '' : `<div class="d-flex gap-1 flex-wrap mt-1">${l.meal_id ? `<button class="btn btn-light btn-sm" data-medit="${l.id}">Edit</button>` : (l.ours ? `<button class="btn btn-light btn-sm" data-mconv="${l.id}" title="Price this line from the Meal Master — its words are kept as where it came from">Price from the Meal Master</button>` : '')}<button class="btn btn-light btn-sm rowdel" data-mdel="${l.id}" title="Remove the line">&times;</button></div>`}</div>`).join('');
        const sugg = (c.suggestions || []).map(o => `<button class="btn btn-light btn-sm mb-1" style="white-space:normal;text-align:left" data-madd="${r.day_no}|${type}|${o.id}" title="${esc(o.why)}">${esc(o.restaurant)} <span class="help">· ${esc(o.location)} · ${esc(o.currency)} ${Number(o.adult_rate).toFixed(2)}</span><div class="help">${esc(o.why)}</div></button>`).join('');
        return `${pt ? `<span class="pill ${pc}">${pt}</span>` : ''}${c.by_hotel && c.state === 'hotel' ? `<div class="help">${esc(r.meal_plan)} plan</div>` : ''}${lines}
            ${c.problems.length ? `<div class="help" style="color:var(--tf-red)">${esc(c.problems[0].label)}</div>` : ''}
            ${locked ? '' : `${sugg ? `<div class="mt-1">${sugg}</div>` : ''}<button class="btn btn-light btn-sm mt-1" data-madd="${r.day_no}|${type}|"><i class="bi bi-plus-lg"></i> ${c.lines.length || c.by_hotel ? 'Add' : 'Add ' + type}</button>`}`;
    }
    /**
     * The lunch place of a day of the itinerary (schema 40): the restaurant from the Meal Master and what it costs (in-house —
     * the agent's itinerary names the restaurant, never a price), picked by the system from the route or by a person;
     * + adds one (pick from the master, or a new restaurant), Change picks another, × takes it off the day.
     */
    function lunchPlace(dayNo) {
        const M = CURRENT && CURRENT.meals;
        if (!M || !M.available || !M.days) return '';
        const r = M.days.find(x => Number(x.day_no) === Number(dayNo));
        if (!r) return '';
        const c = r.meals.lunch, lock = M.locked;
        const k = '<span class="lp-k">Lunch place</span>';
        const mine = c.lines.filter(l => l.meal_id), old = c.lines.filter(l => !l.meal_id && l.ours), notOurs = c.lines.filter(l => !l.ours);
        if (c.by_hotel && !mine.length) return `<div class="lp" data-lp="${r.day_no}">${k} <span class="help">at the hotel (${esc(r.meal_plan)} plan)</span></div>`;
        let body;
        if (mine.length) {
            body = mine.map(l => `<span class="lp-r" data-lpline="${l.id}" title="${esc(l.note || '')}"><b>${esc(l.restaurant || l.title)}</b>${l.location && !String(l.restaurant || '').toLowerCase().includes(String(l.location).toLowerCase()) ? '<span class="help">, ' + esc(l.location) + '</span>' : ''}
                ${l.blocks ? '<span class="pill pill-red">held</span>' : (l.amount_usd != null ? `<span class="lp-amt">$${Number(l.amount_usd).toFixed(2)}</span>` : '')}${l.auto ? '<span class="lp-auto" title="Picked by the system from the route: a restaurant on the day\'s leg first, then the cheapest. Change it and it is yours.">auto</span>' : ''}
                ${lock ? '' : `<button class="btn btn-light" data-lpadd="${r.day_no}|${l.id}" title="Pick another restaurant, or add a new one">Change</button><button class="btn btn-light rowdel" data-lpdel="${l.id}" title="No lunch place on this day">&times;</button>`}</span>`).join(' ');
        } else if (old.length) {
            body = `<span class="help">“${esc(old[0].title)}” — no restaurant yet</span>${lock ? '' : ` <button class="btn btn-light" data-lpadd="${r.day_no}|${old[0].id}" title="Pick the restaurant from the Meal Master">+</button>`}`;
        } else if (notOurs.length) {
            body = `<span class="help">${esc(notOurs[0].title)} — not ours to arrange</span>`;
        } else {
            body = `<span class="help">${c.skipped ? 'none (taken off)' : 'none'}</span>${lock ? '' : ` <button class="btn btn-light" data-lpadd="${r.day_no}|" title="Add the lunch restaurant from the Meal Master">+</button>`}`;
        }
        return `<div class="lp" data-lp="${r.day_no}">${k} ${body}</div>`;
    }
    function mealsCard(it) {
        const M = it.meals;
        if (!M || !M.available) return '';
        const n = M.count || {};
        const head = `<div class="tf-card-h"><h6>Meals (Half Board)</h6>
            <span class="help">Lunches and dinners outside the hotel, from the Meal Master — the hotel's plan gives the rest. An HB hotel gives breakfast and ${esc(M.hb_default)} unless a night says lunch.</span>
            <span class="ms-auto d-flex gap-1">${M.locked ? '' : `<button class="btn btn-light btn-sm" data-lpfill title="The system picks the lunch place of every day from the route: a restaurant on the day's leg first, then in a town on the route, then where the party sleeps — the cheapest of those. A person's pick, and a day a person took lunch off, are left alone.">Pick lunch places</button>`}
            ${SEES_MEALS ? `<a class="btn btn-light btn-sm" href="${MEAL_URL}" target="_blank">Meal Master</a>` : ''}</span></div>`;
        if (M.error) return `<div class="tf-card" id="meals_card">${head}<div class="tf-card-b help" style="color:var(--tf-red)">${esc(M.error)}</div></div>`;
        const rows = (M.days || []).map(r => `<tr data-mealday="${r.day_no}">
            <td class="mono">${r.departure ? 'Dep.' : r.day_no}${r.date ? `<div class="help">${fmtDate(r.date)}</div>` : ''}</td>
            <td>${r.route ? esc(r.route) : '<span class="help">no driving that day</span>'}<div class="help">${r.party != null ? r.party + ' pax' : ''}</div></td>
            <td>${r.departure ? '<span class="help">departure day — no hotel meal</span>' : `${esc(r.hotel || r.area || '—')} ${r.meal_plan ? `<span class="pill pill-blue">${esc(r.meal_plan)}</span>` : '<span class="pill pill-amber">no meal plan</span>'}
                <div class="help">${r.hotel_gives.length ? 'Hotel gives breakfast, ' + r.hotel_gives.join(' and ') : (['RO'].includes(r.meal_plan) ? 'Room only' : (r.meal_plan === 'BB' ? 'Breakfast only' : ''))}${r.meal_plan === 'HB' && !r.hb_meal_set ? ' <span class="help">(default)</span>' : ''}</div>
                ${r.hb_switchable ? `<button class="btn btn-light btn-sm mt-1" data-hb="${r.day_id}" data-hbmeal="${r.hb_meal === 'dinner' ? 'lunch' : 'dinner'}" title="Which meal the hotel gives besides breakfast on this night">Hotel gives ${r.hb_meal === 'dinner' ? 'lunch' : 'dinner'} instead</button>` : ''}`}</td>
            <td>${mealCell(r, 'lunch', M.locked)}</td>
            <td>${mealCell(r, 'dinner', M.locked)}</td></tr>`).join('');
        const probs = (M.problems || []).length ? `<div class="mt-2" style="color:var(--tf-red);font-size:12.5px"><b>${M.problems.length} to settle before the quotation:</b><ul class="mb-0">${M.problems.map(p => `<li>${esc(p.label)}</li>`).join('')}</ul></div>` : '';
        return `<div class="tf-card" id="meals_card">${head}<div class="tf-card-b">
            <div class="help mb-2">${n.hb_nights || 0} HB night${n.hb_nights === 1 ? '' : 's'} · ${n.arranged || 0} meal${n.arranged === 1 ? '' : 's'} arranged from the Meal Master · ${n.hotel || 0} by the hotel${n.old ? ' · ' + n.old + ' old line' + (n.old === 1 ? '' : 's') + ' to price from the Meal Master' : ''}${n.held ? ' · <span class="pill pill-red">' + n.held + ' held</span>' : ''}</div>
            <div class="help mb-2">${M.auto_lunch ? 'The lunch place of each day is picked by the system once the route is set (<b>auto</b>): a restaurant on the day\'s leg first, then the cheapest. Change any of them on the itinerary or here; a person\'s pick is never replaced.' : 'Automatic lunch places are switched off in the Meal Master settings — Pick lunch places runs it once.'}
                ${M.issued ? ` Quotation version ${M.issued.version} carries meals of <b>USD ${Number(M.issued.meals_usd).toFixed(2)}</b>; now <b>USD ${Number(M.meals_usd).toFixed(2)}</b>${Math.abs(Number(M.issued.meals_usd) - Number(M.meals_usd)) > 0.004 ? ' <span class="pill pill-amber">changed since it was issued</span>' : ''} (in-house costing; the agent's itinerary names the restaurants, never a price).` : ''}</div>
            ${(M.days || []).length ? `<div style="overflow-x:auto"><table class="mtbl" id="meals_table"><thead><tr><th style="width:70px">Day</th><th style="width:200px">Route</th><th style="width:220px">Hotel &amp; plan</th><th>Lunch</th><th>Dinner</th></tr></thead><tbody>${rows}</tbody></table></div>` : '<div class="empty">No nights on this inquiry yet.</div>'}
            ${probs}
            <div class="help mt-2">Suggestions come from the itinerary: a lunch on the route the day drives, a dinner where the party stays that night. Only meals in use, within their dates, with an available supplier are offered. The cost is adults × adult rate + children × child rate + taxes, in the meal's currency, and goes into Entrance &amp; Activities and the costing. The same people are never given the same meal twice on a day; a meal the hotel's plan gives is arranged outside only with a reason.</div>
        </div></div>`;
    }

    let MM = null;   // what the picker is showing: the options for the day and the meal type
    async function openMealPick(dayNo, type, sid, pre) {
        const line = sid ? (CURRENT.services || []).find(x => Number(x.id) === Number(sid)) : null;
        if (line) { dayNo = Number(line.day_no); if (['lunch', 'dinner'].includes(line.kind)) type = line.kind; }
        const j = await api('/intake/' + CURRENT.id + '/meals/options?day=' + encodeURIComponent(dayNo) + '&type=' + encodeURIComponent(type));
        if (j.status !== 200) { toast('error', j.message); return; }
        MM = j.data;
        const d = MM.day;
        document.getElementById('mm_title').textContent = (line && line.meal_id ? 'Change the ' : (line ? 'Price from the Meal Master: ' : 'Add ')) + type + ' — ' + d.label + (d.date ? ', ' + fmtDate(d.date) : '');
        document.getElementById('mm_day').innerHTML = `${d.route ? 'Route: <b>' + esc(d.route) + '</b> · ' : ''}${d.departure ? 'departure day' : esc(d.hotel || d.area || '') + (d.meal_plan ? ' (' + esc(d.meal_plan) + ')' : '')}${d.party != null ? ' · party ' + d.party : ''}${line && !line.meal_id ? `<br>The line: “${esc(line.title)}” — its words are kept as where it came from.` : ''}`;
        document.getElementById('mm_conflict').innerHTML = MM.hotel_gives ? `<div class="help mb-2" style="color:var(--tf-amber)">The hotel's ${esc(d.meal_plan)} plan gives ${esc(type)} on this night — an outside ${esc(type)} needs a reason${d.hb_switchable ? ', or switch the night\'s HB meal' : ''}.</div>` : '';
        document.getElementById('mm_sid').value = sid || '';
        document.getElementById('mm_dayno').value = d.day_no;
        document.getElementById('mm_type').value = type;
        document.getElementById('mm_find').value = '';
        const keepA = line && line.meal_id && line.qty_adult != null ? line.qty_adult : MM.split.adults;
        const keepC = line && line.meal_id && line.qty_child != null ? line.qty_child : MM.split.children;
        document.getElementById('mm_a').value = keepA;
        document.getElementById('mm_c').value = keepC;
        document.getElementById('mm_t').value = line && line.meal_time ? line.meal_time : MM.time;
        document.getElementById('mm_note').value = line ? (line.special_note || '') : '';
        document.getElementById('mm_split').textContent = MM.split.known || (line && line.meal_id) ? '' : MM.split.note;
        document.getElementById('mm_ok').checked = false;
        document.getElementById('mm_reason').value = '';
        document.getElementById('mm_outside').hidden = !MM.hotel_gives;
        document.getElementById('mm_oklabel').textContent = 'Arrange the ' + type + ' outside as well, although the hotel gives it';
        MM.pick = pre || (line && line.meal_id ? Number(line.meal_id) : (MM.suggestions[0] ? MM.suggestions[0].id : null));
        MM.line = line;
        mealNewReset();
        document.getElementById('mm_bookrow').hidden = false;
        document.getElementById('mm_bsavewrap').hidden = !MM.can_add;
        document.getElementById('mm_bsave').checked = false;
        MB_DIRTY = false;
        mealBookFill(true);
        renderMealList();
        show('#m_mealpick');
    }
    // schema 40: "+ Add a new restaurant" — for a holder of the Meal Master; the others pick from the list
    function mealNewReset() {
        const d = MM.day, leg = (d.legs || [])[0];
        document.getElementById('mm_new').hidden = true;
        document.getElementById('mm_newbtn').hidden = !MM.can_add;
        document.getElementById('mm_newno').innerHTML = MM.can_add ? '' : 'A restaurant not in the list is added in the Meal Master by its holder.';
        ['mn_rest', 'mn_cui', 'mn_tel', 'mn_ar', 'mn_cr', 'mn_desc', 'mn_email', 'mn_cname', 'mn_alkr', 'mn_clkr'].forEach(k => { document.getElementById(k).value = ''; });
        document.getElementById('mn_tax').value = '0';
        document.getElementById('mn_taxin').checked = false;
        document.getElementById('mn_high').checked = false;
        document.getElementById('mn_loc').value = leg ? leg.to : (d.area || '');
        document.getElementById('mn_sup').innerHTML = '<option value="">— not in the Supplier master yet —</option>' + (MM.suppliers || []).filter(x => x.available).map(x => `<option value="${x.id}">${esc(x.name)}${x.acc ? ' (' + esc(x.acc) + ')' : ''}</option>`).join('');
        document.getElementById('mn_legwrap').hidden = !leg;
        document.getElementById('mn_leg').checked = !!leg;
        document.getElementById('mn_leglabel').textContent = leg ? 'It serves the ' + leg.from + ' → ' + leg.to + ' route (so it is offered on that leg on other tours too)' : '';
    }
    function mealNewOpen() { return !document.getElementById('mm_new').hidden; }
    document.getElementById('mm_newbtn')?.addEventListener('click', () => {
        const box = document.getElementById('mm_new');
        box.hidden = !box.hidden;
        document.getElementById('mm_newbtn').innerHTML = box.hidden ? '<i class="bi bi-plus-lg"></i> Add a new restaurant' : 'Pick from the list instead';
        document.getElementById('mm_bookrow').hidden = !box.hidden;   // a new restaurant's contact is typed in its own form
        mealCost();
    });
    // the booking email and contact follow the restaurant picked, until a person types their own
    let MB_DIRTY = false;
    function mealBookFill(force) {
        if (MB_DIRTY && !force) return;
        const line = MM.line;
        const o = [...MM.suggestions, ...MM.others].find(x => Number(x.id) === Number(MM.pick));
        const same = line && line.meal_id && Number(line.meal_id) === Number(MM.pick);
        document.getElementById('mm_bemail').value = same ? (line.meal_email || '') : (o ? (o.contact_email || '') : '');
        document.getElementById('mm_bcontact').value = same ? (line.meal_contact || '') : (o ? (o.contact_name || '') : '');
        document.getElementById('mm_bphone').value = same ? (line.meal_phone || '') : (o ? (o.contact_phone || '') : '');
    }
    ['mm_bemail', 'mm_bcontact', 'mm_bphone'].forEach(k => document.getElementById(k)?.addEventListener('input', () => { MB_DIRTY = true; }));
    document.getElementById('mn_sup')?.addEventListener('change', e => { const x = (MM.suppliers || []).find(y => String(y.id) === e.target.value); if (x && !document.getElementById('mn_rest').value.trim()) document.getElementById('mn_rest').value = x.name; });
    ['mn_ar', 'mn_cr', 'mn_alkr', 'mn_clkr', 'mn_tax', 'mn_taxin'].forEach(k => document.getElementById(k)?.addEventListener('input', () => mealCost()));
    ['mn_taxin'].forEach(k => document.getElementById(k)?.addEventListener('change', () => mealCost()));
    function mealOpt(o, sugg) {
        return `<label class="d-flex gap-2 align-items-start p-2" style="border-bottom:1px solid var(--tf-line);cursor:pointer" data-mopt="${o.id}">
            <input type="radio" name="mm_pick" value="${o.id}" ${Number(MM.pick) === Number(o.id) ? 'checked' : ''} style="margin-top:3px">
            <span><b>${esc(o.restaurant)}</b> <span class="mono help">${esc(o.code)}</span> · ${esc(o.location)}${o.cuisine ? ' · ' + esc(o.cuisine) : ''}
            <div class="help">${esc(o.rate_text)} · ${esc(o.tax_text)}${o.currency !== 'USD' && o.adult_usd != null ? ' · ≈ USD ' + Number(o.adult_usd).toFixed(2) + ' an adult' : ''}${o.route_text ? ' · legs: ' + esc(o.route_text) : ''}</div>
            <div class="help">On the booking voucher: ${o.needs_lkr ? '<span style="color:var(--tf-amber)">LKR rate to enter in the Meal Master</span>' : esc(o.lkr_text)}${o.contact_email ? ' · ' + esc(o.contact_email) : ' · <span style="color:var(--tf-amber)">no booking email</span>'}</div>
            ${o.needs_supplier ? '<div class="help" style="color:var(--tf-amber)">not linked to a supplier account yet</div>' : ''}
            ${sugg ? `<div class="help" style="color:var(--tf-green)">${esc(o.why)}</div>` : ''}${o.description ? `<div class="help">${esc(o.description)}</div>` : ''}</span></label>`;
    }
    function renderMealList() {
        const q = document.getElementById('mm_find').value.trim().toLowerCase();
        const hit = o => !q || [o.code, o.restaurant, o.location, o.cuisine, o.description, o.route_text].join(' ').toLowerCase().includes(q);
        const s = MM.suggestions.filter(hit), o = MM.others.filter(hit);
        document.getElementById('mm_n').textContent = (MM.suggestions.length + MM.others.length) + ' meal(s) may be booked that day';
        document.getElementById('mm_list').innerHTML = (s.length ? `<div class="help px-2 pt-2"><b>Suits the day</b></div>` + s.map(x => mealOpt(x, true)).join('') : '')
            + (o.length ? `<div class="help px-2 pt-2"><b>${s.length ? 'Other meals that may be booked that day' : 'Meals that may be booked that day'}</b></div>` + o.map(x => mealOpt(x, false)).join('') : '')
            || `<div class="empty p-2">${MM.suggestions.length + MM.others.length ? 'Nothing matches.' : 'No ' + esc(MM.type) + ' in the Meal Master may be booked on this day (in use, within its dates, with an available supplier).'}${SEES_MEALS ? ` <a href="${MEAL_URL}" target="_blank">Add one in the Meal Master</a>` : ''}</div>`;
        document.querySelectorAll('#mm_list [name="mm_pick"]').forEach(r => r.addEventListener('change', () => { MM.pick = Number(r.value); mealBookFill(false); mealCost(); }));
        mealCost();
    }
    function mealCost() {
        const o = mealNewOpen()
            ? { currency: 'USD', adult_rate: document.getElementById('mn_ar').value || 0, child_rate: document.getElementById('mn_cr').value === '' ? null : document.getElementById('mn_cr').value,
                adult_lkr: document.getElementById('mn_alkr').value === '' ? null : document.getElementById('mn_alkr').value, child_lkr: document.getElementById('mn_clkr').value === '' ? null : document.getElementById('mn_clkr').value,
                tax_pct: document.getElementById('mn_tax').value || 0, tax_included: document.getElementById('mn_taxin').checked }
            : [...MM.suggestions, ...MM.others].find(x => Number(x.id) === Number(MM.pick));
        const box = document.getElementById('mm_cost');
        if (!o) { box.innerHTML = ''; return; }
        const a = parseInt(document.getElementById('mm_a').value, 10) || 0, c = parseInt(document.getElementById('mm_c').value, 10) || 0;
        const ra = Number(o.adult_rate), rc = o.child_rate == null ? ra : Number(o.child_rate);
        const sub = Math.round((a * ra + c * rc) * 100) / 100, tax = o.tax_included ? 0 : Math.round(sub * Number(o.tax_pct)) / 100;
        // the restaurant's own figure in LKR (the Meal Master's actual rates): what the booking voucher states — never converted
        let lkr = '';
        if (o.adult_lkr != null && o.adult_lkr !== '') {
            const la = Number(o.adult_lkr), lc = o.child_lkr == null || o.child_lkr === '' ? la : Number(o.child_lkr);
            const ls = Math.round((a * la + c * lc) * 100) / 100, lt = o.tax_included ? 0 : Math.round(ls * Number(o.tax_pct)) / 100;
            lkr = `<br>Booking voucher to the restaurant: <b>LKR ${(ls + lt).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b> <span class="help">(the Meal Master's LKR rates)</span>`;
        } else {
            lkr = '<br><span style="color:var(--tf-amber)">No LKR rate in the Meal Master yet — no booking voucher until it is entered.</span>';
        }
        box.innerHTML = `<div class="help" style="background:var(--tf-bg);border-radius:8px;padding:8px 10px">Quotation: ${a} × ${esc(o.currency || 'USD')} ${ra.toFixed(2)}${c ? ' + ' + c + ' × ' + rc.toFixed(2) : ''}${tax ? ' + taxes ' + Number(o.tax_pct) + '% ' + tax.toFixed(2) : (o.tax_included && Number(o.tax_pct) ? ' (taxes included)' : '')} = <b>${esc(o.currency || 'USD')} ${(sub + tax).toFixed(2)}</b>${MM.day.party != null && a + c > MM.day.party ? ` <span class="pill pill-red">more than the party of ${MM.day.party}</span>` : ''}${lkr}</div>`;
    }
    async function saveMeal(extra) {
        const sid = document.getElementById('mm_sid').value;
        const body = Object.assign({ day_no: Number(document.getElementById('mm_dayno').value), meal_type: document.getElementById('mm_type').value, meal_id: MM.pick,
            qty_adult: document.getElementById('mm_a').value, qty_child: document.getElementById('mm_c').value, meal_time: document.getElementById('mm_t').value,
            special_note: document.getElementById('mm_note').value, confirm_outside: document.getElementById('mm_ok').checked ? 1 : 0, outside_reason: document.getElementById('mm_reason').value,
            book_email: document.getElementById('mm_bemail').value, book_contact: document.getElementById('mm_bcontact').value, book_phone: document.getElementById('mm_bphone').value,
            save_master: document.getElementById('mm_bsave').checked ? 1 : 0 }, extra || {});
        const fresh = mealNewOpen();
        if (fresh) {
            ['book_email', 'book_contact', 'book_phone', 'save_master'].forEach(k => { delete body[k]; });
            const leg = (MM.day.legs || [])[0];
            Object.assign(body, { meal_id: null, service_id: sid || null, restaurant: document.getElementById('mn_rest').value, supplier_id: document.getElementById('mn_sup').value || null,
                location: document.getElementById('mn_loc').value, cuisine: document.getElementById('mn_cui').value, contact_phone: document.getElementById('mn_tel').value,
                contact_email: document.getElementById('mn_email').value, contact_name: document.getElementById('mn_cname').value,
                adult_rate: document.getElementById('mn_ar').value, child_rate: document.getElementById('mn_cr').value, currency: 'USD',
                adult_lkr: document.getElementById('mn_alkr').value, child_lkr: document.getElementById('mn_clkr').value,
                tax_pct: document.getElementById('mn_tax').value, tax_included: document.getElementById('mn_taxin').checked ? 1 : 0, description: document.getElementById('mn_desc').value,
                route_from: leg && document.getElementById('mn_leg').checked ? leg.from : '', route_to: leg && document.getElementById('mn_leg').checked ? leg.to : '',
                confirm_high: document.getElementById('mn_high').checked ? 1 : 0 });
        } else if (!body.meal_id) { toast('error', 'Pick the meal from the list.'); return; }
        const j = await post('/intake/' + CURRENT.id + (fresh ? '/meal_new' : '/meal' + (sid ? '/' + sid : '')), body);
        if (j.status === 409 && j.conflict === 'hotel_gives') {
            document.getElementById('mm_outside').hidden = false;
            const d = MM.day;
            document.getElementById('mm_conflict').innerHTML = `<div class="p-2 mb-2" style="background:var(--tf-amber-soft);color:var(--tf-amber);border-radius:8px;font-size:12.5px">${esc(j.message)}
                <div class="d-flex gap-2 mt-2 flex-wrap">${j.can_switch && d.day_id ? `<button class="btn btn-light btn-sm" id="mm_switch">The hotel gives ${d.hb_meal === 'dinner' ? 'lunch' : 'dinner'} on this night</button>` : ''}<span class="help">or tick "Arrange it outside as well" below and give the reason.</span></div></div>`;
            document.getElementById('mm_switch')?.addEventListener('click', async () => {
                const k = await post('/intake/' + CURRENT.id + '/day/' + d.day_id + '/hb_meal', { hb_meal: d.hb_meal === 'dinner' ? 'lunch' : 'dinner' });
                if (k.status !== 200) { toast('error', k.message); return; }
                CURRENT = k.data; renderDetail();
                saveMeal();
            });
            return;
        }
        if (j.status !== 200) { toast('error', j.message); return; }
        hide('#m_mealpick');
        CURRENT = j.data; renderDetail(); toast('success', j.message);
    }
    document.getElementById('mm_save')?.addEventListener('click', async () => { const b = document.getElementById('mm_save'); if (b.disabled) return; b.disabled = true; try { await saveMeal(); } finally { b.disabled = false; } });
    document.getElementById('mm_find')?.addEventListener('input', renderMealList);
    ['mm_a', 'mm_c'].forEach(k => document.getElementById(k)?.addEventListener('input', mealCost));
    function wireMeals() {
        document.querySelectorAll('#meals_card [data-madd]').forEach(b => b.addEventListener('click', () => { const [day, type, mid] = b.dataset.madd.split('|'); openMealPick(Number(day), type, null, mid ? Number(mid) : null); }));
        document.querySelectorAll('#meals_card [data-medit], #meals_card [data-mconv]').forEach(b => b.addEventListener('click', () => {
            const sid = Number(b.dataset.medit || b.dataset.mconv); const sv = (CURRENT.services || []).find(x => Number(x.id) === sid); if (sv) openMealPick(Number(sv.day_no), sv.kind, sid, null); }));
        document.querySelectorAll('#meals_card [data-mdel]').forEach(b => b.addEventListener('click', async () => {
            const sid = Number(b.dataset.mdel); const sv = (CURRENT.services || []).find(x => Number(x.id) === sid);
            if (!sv || !(await ask('Remove “' + sv.title + '” from day ' + sv.day_no + '?', 'Remove'))) return;
            rowPost('/meal/' + sid, { delete: 1 }, 'Meal removed.');
        }));
        document.querySelectorAll('#meals_card [data-hb]').forEach(b => b.addEventListener('click', () => rowPost('/day/' + b.dataset.hb + '/hb_meal', { hb_meal: b.dataset.hbmeal }, 'Saved.')));
        document.querySelectorAll('#detail_cols [data-mealopen]').forEach(b => b.addEventListener('click', () => { const sv = (CURRENT.services || []).find(x => Number(x.id) === Number(b.dataset.mealopen)); if (sv) openMealPick(Number(sv.day_no), sv.kind, Number(sv.id), null); }));
        // the itinerary's Lunch place: + / Change opens the picker (pick, or add a new restaurant), × takes it off the day
        document.querySelectorAll('#detail_cols [data-lpadd]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const [day, sid] = b.dataset.lpadd.split('|'); openMealPick(Number(day), 'lunch', sid ? Number(sid) : null, null); }));
        document.querySelectorAll('#detail_cols [data-lpdel]').forEach(b => b.addEventListener('click', async e => {
            e.stopPropagation();
            const sid = Number(b.dataset.lpdel); const sv = (CURRENT.services || []).find(x => Number(x.id) === sid);
            if (!sv || !(await ask('Take the lunch place “' + sv.title + '” off day ' + sv.day_no + '? The system will not put one back on that day; + adds one again.', 'Remove'))) return;
            rowPost('/meal/' + sid, { delete: 1 }, 'Lunch place removed.');
        }));
        document.querySelectorAll('#meals_card [data-lpfill]').forEach(b => b.addEventListener('click', () => rowPost('/meals/fill', {}, 'Done.')));
    }

    async function rowPost(path, body, okMsg) {
        const j = await post('/intake/' + CURRENT.id + path, body || {});
        if (j.status !== 200) { toast('error', j.message); return false; }
        CURRENT = j.data; renderDetail(); toast('success', j.message || okMsg || 'Saved.');
        return true;
    }

    function openPax(id) {
        const p = id ? (CURRENT.passengers || []).find(x => Number(x.id) === id) : null;
        document.getElementById('mp_id').value = p ? p.id : '';
        document.getElementById('mp_name').value = p ? (p.full_name || '') : '';
        document.getElementById('mp_room').value = p ? (p.room_type || '') : '';
        document.getElementById('mp_note').value = p ? (p.note || '') : '';
        document.getElementById('mp_title').textContent = p ? 'Change this name' : 'Add a passenger name';
        const gs = (CURRENT.groups || []);
        document.getElementById('mp_groupwrap').style.display = gs.length ? '' : 'none';
        document.getElementById('mp_group').innerHTML = '<option value="">No group</option>' + gs.map(g => `<option value="${g.id}">${esc(g.code)} · ${esc(g.name)}</option>`).join('');
        document.getElementById('mp_group').value = p && p.group_id ? String(p.group_id) : '';
        show('#m_pax');
    }
    document.getElementById('mp_ok').addEventListener('click', async () => {
        const id = document.getElementById('mp_id').value;
        const ok = await rowPost('/pax' + (id ? '/' + id : ''), {
            full_name: document.getElementById('mp_name').value,
            room_type: document.getElementById('mp_room').value,
            note: document.getElementById('mp_note').value,
            ...((CURRENT.groups || []).length ? { group_id: document.getElementById('mp_group').value || null } : {})
        });
        if (ok) hide('#m_pax');
    });

    // ---------- a driver or a guide line: type and language from the master, days, person ----------
    // The bata never travels from here: the server takes the master's figure for the role.
    function crewRolesOf(kind) { return ((CREW && CREW.roles) || []).filter(r => r.kind === kind); }
    function fillCrewTypes(kind, code) {
        const roles = crewRolesOf(kind);
        const cats  = [...new Set(roles.map(r => r.category))];
        const cur   = code ? roles.find(r => r.code === code) : null;
        const type  = document.getElementById('mc_type');
        type.innerHTML = cats.length ? cats.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join('') : '<option value="">No ' + kind + ' types in the master</option>';
        type.disabled = !cats.length;
        if (cur) type.value = cur.category;
        fillCrewLangs(kind, code);
    }
    function fillCrewLangs(kind, code) {
        const cat   = document.getElementById('mc_type').value;
        const roles = crewRolesOf(kind).filter(r => r.category === cat);
        const lang  = document.getElementById('mc_lang');
        const already = new Set((CURRENT.crew || []).map(c => c.role_code));
        lang.innerHTML = roles.length ? roles.map(r =>
            `<option value="${esc(r.code)}"${already.has(r.code) && r.code !== code ? ' disabled' : ''}>${esc(r.language || 'Any language')}${roles.filter(x => (x.language || '') === (r.language || '')).length > 1 ? ' — ' + esc(r.name) : ''}${already.has(r.code) && r.code !== code ? ' (already on this inquiry)' : ''}</option>`).join('')
            : '<option value="">—</option>';
        lang.disabled = !roles.length;
        if (code && roles.some(r => r.code === code)) lang.value = code;
        else {
            // a new line starts on the language the inquiry already speaks — the other section's
            // line, else the agent's market — and otherwise on the first the master lists
            const spoken = ((CURRENT.crew || []).find(c => c.language) || {}).language || (String(CURRENT.market_code || '').toUpperCase() === 'JP' ? 'Japanese' : null);
            const pick = roles.find(r => !already.has(r.code) && spoken && String(r.language || '').toLowerCase() === String(spoken).toLowerCase()) || roles.find(r => !already.has(r.code));
            if (pick) lang.value = pick.code;
        }
        showCrewBata();
    }
    function showCrewBata() {
        const r = ((CREW && CREW.roles) || []).find(x => x.code === document.getElementById('mc_lang').value);
        const days = Number(document.getElementById('mc_days').value || CURRENT.programme_day_count || 0);
        document.getElementById('mc_bata').innerHTML = !r ? ''
            : (r.bata != null
                ? `Bata per day <b>${esc(r.currency)} ${Number(r.bata).toLocaleString()}</b> from the master${days ? ` · ${days} day(s) = <b>${esc(r.currency)} ${(days * Number(r.bata)).toLocaleString()}</b>` : ''}`
                : `<span class="pill pill-red">no bata set</span> for ${esc(r.name)} — Admin sets it under Master Files → Crew Bata; Go for Quotation stops until then.`);
    }
    function openCrew(kind, line) {
        const who = document.getElementById('mc_who');
        const people = ((CREW && CREW.freelancers) || []).filter(f => kind === 'guide' ? f.is_guide : f.is_driver);
        who.innerHTML = '<option value="">Nobody yet</option>' + people.map(f =>
            `<option value="${esc(f.acc)}">${esc(f.name)}${f.language ? ' · ' + esc(f.language) : ''}${f.bata != null ? ' · own bata ' + Number(f.bata).toLocaleString() : ''}</option>`).join('');
        document.getElementById('mc_kind').value = kind;
        document.getElementById('mc_from').value = line ? line.role_code : '';
        document.getElementById('mc_title').textContent = (line ? 'Change the ' : 'Add a ') + kind;
        document.getElementById('mc_type_l').textContent = kind === 'driver' ? 'Driver type' : 'Guide type';
        // schema 32: empty means the programme's figure; a number typed is the person's and stays
        document.getElementById('mc_days').value = line && line.days_by !== 'auto' ? (line.days ?? '') : '';
        document.getElementById('mc_days').placeholder = CURRENT.programme_day_count != null ? CURRENT.programme_day_count + ' (programme)' : '';
        document.getElementById('mc_note').value = line ? (line.note || '') : '';
        fillCrewTypes(kind, line ? line.role_code : null);
        who.value = line && line.supplier_acc ? line.supplier_acc : '';
        if (line && line.supplier_acc && who.value !== line.supplier_acc) {
            who.insertAdjacentHTML('beforeend', `<option value="${esc(line.supplier_acc)}" selected>${esc(line.person_name || line.supplier_acc)} · not offered for this kind any more</option>`);
        }
        show('#m_crew');
    }
    document.getElementById('mc_type').addEventListener('change', () => fillCrewLangs(document.getElementById('mc_kind').value, null));
    document.getElementById('mc_lang').addEventListener('change', showCrewBata);
    document.getElementById('mc_days').addEventListener('input', showCrewBata);
    document.getElementById('mc_ok').addEventListener('click', async () => {
        const code = document.getElementById('mc_lang').value;
        const from = document.getElementById('mc_from').value;
        if (!code) { toast('error', 'Pick the type and the language first.'); return; }
        const body = { code, days: document.getElementById('mc_days').value, supplier_acc: document.getElementById('mc_who').value, note: document.getElementById('mc_note').value };
        if (from && from !== code) body.change_from = from;
        const j = await post('/intake/' + CURRENT.id + '/crew', body);
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; hide('#m_crew'); renderDetail(); toast('success', j.message);
    });

    function openReq(id) {
        const r = id ? (CURRENT.requests || []).find(x => Number(x.id) === id) : null;
        document.getElementById('mr_id').value = r ? r.id : '';
        document.getElementById('mr_title').textContent = r ? r.title : 'Add a request or question';
        document.getElementById('mr_titlein').value = r ? (r.title || '') : '';
        document.getElementById('mr_kind').value = r ? (r.kind || 'request') : 'request';
        document.getElementById('mr_quote').innerHTML = r && r.quote_text ? 'The agent wrote: “' + esc(r.quote_text) + '”' : '';
        document.getElementById('mr_answer').value = r ? (r.answer || '') : '';
        document.getElementById('mr_status').value = r ? (r.status || 'open') : 'open';
        document.getElementById('mr_answerwrap').style.display = r ? '' : 'none';
        if (r) renderMail(r.quote_text || null);
        show('#m_req');
    }
    document.getElementById('mr_ok').addEventListener('click', async () => {
        const id = document.getElementById('mr_id').value;
        const ok = await rowPost('/request' + (id ? '/' + id : ''), {
            title: document.getElementById('mr_titlein').value,
            kind: document.getElementById('mr_kind').value,
            status: document.getElementById('mr_status').value,
            answer: document.getElementById('mr_answer').value
        });
        if (ok) hide('#m_req');
    });

    // ---------- paste ----------
    function openPaste() {
        ['p_subject','p_from','p_text','p_edit'].forEach(i => document.getElementById(i).value = '');
        document.getElementById('p_received').value = new Date().toISOString().slice(0, 10);
        pasteStep('paste');
        setView('view_paste');
    }
    // Two steps in one view: the paste, then the check of the text before it is read.
    function pasteStep(which) {
        document.getElementById('p_step_paste').style.display = which === 'paste' ? '' : 'none';
        document.getElementById('p_step_review').style.display = which === 'review' ? '' : 'none';
    }
    // The text the reader will get: the corrected copy once the review step has been opened,
    // the paste itself before that. The paste is always sent along as the original.
    function pasteTexts() {
        const original = document.getElementById('p_text').value;
        const reviewed = document.getElementById('p_step_review').style.display !== 'none';
        const edited = reviewed ? document.getElementById('p_edit').value : original;
        return { original, text: edited };
    }
    let CHECK_TIMER = null;
    // The date check, by rule on the server (TextChecks): the same answer every time, no model.
    async function checkText(text, into, datesInto, receivedAt) {
        let j;
        try { j = await post('/check_text', { source_text: text, source_received_at: receivedAt !== undefined ? receivedAt : document.getElementById('p_received').value }); }
        catch (e) { j = { status: 500 }; }
        const flags = (j.status === 200 && j.data) ? (j.data.flags || []) : [];
        const dates = (j.status === 200 && j.data) ? (j.data.dates || []) : [];
        into.innerHTML = flags.length
            ? flags.map(f => `<div><span class="pill ${f.level === 'red' ? 'pill-red' : 'pill-amber'}">${f.level}</span><span>${esc(f.label)}</span></div>`).join('')
            : (j.status === 200 ? '<div><span class="pill pill-green">ok</span><span>The dates in the text carry a year and are in order. Correct anything else you can see, then read it.</span></div>' : '<div><span class="pill pill-amber">?</span><span>The text could not be checked (are you still logged in?). You can still read it.</span></div>');
        if (datesInto) {
            // each mention once, however many times the agent wrote it
            const seen = new Set();
            const uniq = dates.filter(d => { const k = d.text.toLowerCase() + '|' + (d.year || ''); if (seen.has(k)) return false; seen.add(k); return true; });
            const shown = uniq.slice(0, 8).map(d => esc(d.text) + (d.year ? '' : ' <span class="text-danger">(no year)</span>'));
            datesInto.innerHTML = uniq.length ? 'Dates found in the text: ' + shown.join(' · ') + (uniq.length > 8 ? ' · and ' + (uniq.length - 8) + ' more' : '') : '';
        }
        return flags;
    }
    let P_FLAGS = [];
    async function openReview() {
        const text = document.getElementById('p_text').value;
        if (!text.trim()) { toast('error', 'Paste the email first.'); return; }
        const edit = document.getElementById('p_edit');
        // keep a correction already typed when coming back from the paste step with the same paste
        if (edit.dataset.from !== text) { edit.value = text; edit.dataset.from = text; }
        document.getElementById('p_orig').textContent = text;
        pasteStep('review');
        reviewState();
        P_FLAGS = await checkText(edit.value, document.getElementById('p_flags'), document.getElementById('p_dates'));
        readLabel();
    }
    function reviewState() {
        const same = document.getElementById('p_edit').value === document.getElementById('p_text').value;
        document.getElementById('p_edit_state').textContent = same ? '— as pasted' : '— corrected; the original is kept as pasted';
        document.getElementById('p_restore').style.display = same ? 'none' : '';
    }
    function readLabel() {
        const btn = document.getElementById('p_read');
        if (btn.disabled) return;
        const red = P_FLAGS.some(f => f.level === 'red');
        btn.className = 'btn btn-sm ' + (red ? 'btn-warning' : 'btn-primary');
        btn.innerHTML = red ? '<i class="bi bi-magic"></i> Read anyway — the flags stay on the inquiry' : '<i class="bi bi-magic"></i> Read this email';
    }
    document.getElementById('p_next')?.addEventListener('click', openReview);
    document.getElementById('p_back')?.addEventListener('click', () => pasteStep('paste'));
    document.getElementById('p_restore')?.addEventListener('click', async () => {
        document.getElementById('p_edit').value = document.getElementById('p_text').value;
        reviewState();
        P_FLAGS = await checkText(document.getElementById('p_edit').value, document.getElementById('p_flags'), document.getElementById('p_dates'));
        readLabel();
    });
    document.getElementById('p_recheck')?.addEventListener('click', async () => {
        P_FLAGS = await checkText(document.getElementById('p_edit').value, document.getElementById('p_flags'), document.getElementById('p_dates'));
        readLabel();
    });
    document.getElementById('p_edit')?.addEventListener('input', () => {
        reviewState();
        clearTimeout(CHECK_TIMER);
        CHECK_TIMER = setTimeout(async () => { P_FLAGS = await checkText(document.getElementById('p_edit').value, document.getElementById('p_flags'), document.getElementById('p_dates')); readLabel(); }, 700);
    });
    document.getElementById('btn_new')?.addEventListener('click', openPaste);
    // The Tour Desk bar's New quote on THIS page opens the view in place; on any other
    // panel the same items are plain links here with ?start=, picked up at boot below.
    document.querySelectorAll('.tdbar [data-start]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        if (a.dataset.start === 'manual') { openManual(); } else { openPaste(); }
    }));
    document.getElementById('btn_list')?.addEventListener('click', () => { setView('view_list'); loadList(); });
    document.getElementById('p_cancel')?.addEventListener('click', () => { setView('view_list'); loadList(); });

    async function submitPaste(read, btnId) {
        const t = pasteTexts();
        if (!t.text.trim()) { toast('error', t.original.trim() ? 'The text to read is empty — press "Back to the original text" or type it again.' : 'Paste the email first.'); return; }
        const btn = document.getElementById(btnId || (read ? 'p_read' : 'p_save'));
        const was = btn.innerHTML; btn.disabled = true; btn.innerHTML = read ? 'Reading…' : 'Saving…';
        const j = await post('/intake', {
            source_text: t.text, source_text_original: t.original, source_subject: document.getElementById('p_subject').value,
            source_from: document.getElementById('p_from').value, source_received_at: document.getElementById('p_received').value, read: !!read
        });
        btn.disabled = false; btn.innerHTML = was;
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; renderDetail(); setView('view_detail'); toast('success', j.message);
    }
    document.getElementById('p_read')?.addEventListener('click', () => submitPaste(true, 'p_read'));
    document.getElementById('p_save')?.addEventListener('click', () => submitPaste(false, 'p_save'));
    document.getElementById('p_save2')?.addEventListener('click', () => submitPaste(false, 'p_save2'));

    // ---------- by hand ----------
    // The same fields the reader produces, as a form to type into. Nothing is created
    // until Save, so changing your mind costs nothing and no inquiry number is used up.
    const MANUAL_FIELDS = ['agent_name','agent_email','agent_country','agent_reference','tour_name','hotel_class',
        'arrival_date','departure_date','nights','pax_total','pax_adults','pax_children','rooms_note','arrival_flight','departure_flight'];

    // ---------- the lists the panel picks from ----------
    let MASTERS = null, CREW = null;
    async function loadCrew() {
        if (CREW !== null) return CREW;
        try { CREW = (await api('/crew')).data || { roles:[], fleet:[], classes:[] }; }
        catch (e) { CREW = { roles:[], fleet:[], classes:[] }; }
        return CREW;
    }
    async function loadMasters() {
        if (MASTERS !== null) return MASTERS;
        try {
            const j = await api('/masters');
            MASTERS = j.data || { agents:[], areas:[], hotels:{} };
            if (!MASTERS.agents) MASTERS.agents = [];
            if (j.status && j.status !== 200) MASTERS.error = j.message || ('HTTP ' + j.status);
        } catch (e) {
            // a login that has timed out, or a server error, comes back as a page, not JSON
            MASTERS = { agents:[], areas:[], hotels:{}, error: 'the server did not answer with the list (are you still logged in?)' };
        }

        const sel = document.getElementById('n_agent_pick');
        if (sel && MASTERS.agents.length) {
            fillAgentPick(false);
        } else if (sel) {
            const hint = document.getElementById('n_agent_hint');
            if (hint && !hint.textContent) hint.textContent = 'The customer list could not be read' + (MASTERS.error ? ' — ' + MASTERS.error : '') + '. You can still type the agent in.';
        }
        // one flat list of every hotel the panel holds, with its area, for the search box
        if (!MASTERS.flat) {
            const areaName = {};
            (MASTERS.areas || []).forEach(a => { areaName[a.id] = a.name; });
            MASTERS.flat = Object.entries(MASTERS.hotels || {}).flatMap(([aid, list]) => (list || []).map(h => Object.assign({ area: areaName[aid] || '' }, h)));
        }
        return MASTERS;
    }

    // The pick list is the customers marked as agents under Master Files → Consignees.
    // The whole ledger (agents and ticketing customers alike) sits behind a link for the
    // one that is not marked yet; until the first customer is marked the list is everyone.
    let AGENTS_ALL = false;
    function agentList() {
        return (AGENTS_ALL && (MASTERS?.customers || []).length) ? MASTERS.customers : (MASTERS?.agents || []);
    }
    // Select a booking code on the pick list; a code the list shown does not carry (a customer
    // not marked as an agent) is added from the ledger rather than silently dropped.
    function setAgentPick(acc) {
        const sel = document.getElementById('n_agent_pick');
        if (!sel) return;
        if (acc && ![...sel.options].some(o => o.value === acc)) {
            const a = (MASTERS?.customers || []).find(x => x.acc === acc) || (MASTERS?.agents || []).find(x => x.acc === acc);
            if (a) sel.insertAdjacentHTML('beforeend', `<option value="${esc(a.acc)}">${esc(a.long || a.name)} — ${esc(a.acc)}${a.is_agent === false ? ' · not marked as an agent' : ''}</option>`);
        }
        sel.value = acc || '';
    }
    function fillAgentPick(all) {
        AGENTS_ALL = !!all;
        const sel = document.getElementById('n_agent_pick');
        const scope = document.getElementById('n_agent_scope');
        if (!sel) return;
        const keep = sel.value;
        const list = agentList();
        sel.innerHTML = '<option value="">Not in the list — I will type it below</option>'
            + list.map(a => `<option value="${esc(a.acc)}">${esc(a.long || a.name)} — ${esc(a.acc)}${a.country ? ' · ' + esc(a.country) : ''}${a.is_agent === false && AGENTS_ALL ? ' · not marked as an agent' : ''}</option>`).join('');
        // a choice already made (a draft, or a customer picked from the full list) stays on the list
        setAgentPick(keep || '');
        if (!scope) return;
        const marked = Number(MASTERS?.agents_marked || 0);
        if (!marked) {
            scope.innerHTML = '— no customer is marked as an agent yet, so the whole customer list is shown. Mark the agents under Master Files → Consignees.';
        } else if (AGENTS_ALL) {
            scope.innerHTML = `— every customer (${list.length}), the ones not marked as agents included. <a href="#" id="n_agent_scope_link">Back to the ${marked} marked agents</a>`;
        } else {
            scope.innerHTML = `— the ${marked} customer${marked === 1 ? '' : 's'} marked as agents. <a href="#" id="n_agent_scope_link">Not here? Show all customers</a>`;
        }
        document.getElementById('n_agent_scope_link')?.addEventListener('click', e => { e.preventDefault(); fillAgentPick(!AGENTS_ALL); });
    }

    // Choosing a registered agent fills in what the ledger already holds, rather than
    // asking somebody to retype a name, a code and a country the company owns. The same
    // thing happens whether the agent is picked from the list or typed and chosen from
    // the suggestions under the name field.
    function applyAgentPick(a) {
        const hint = document.getElementById('n_agent_hint');
        if (!a) { hint.textContent = ''; return; }
        const sel = document.getElementById('n_agent_pick');
        // picked from the suggestions while not on the list shown: put it on the list first
        if (![...sel.options].some(o => o.value === a.acc)) {
            sel.insertAdjacentHTML('beforeend', `<option value="${esc(a.acc)}">${esc(a.long || a.name)} — ${esc(a.acc)}${a.is_agent === false ? ' · not marked as an agent' : ''}</option>`);
        }
        sel.value = a.acc;
        document.getElementById('n_agent_name').value = a.long || a.name;
        if (a.email) document.getElementById('n_agent_email').value = a.email;
        if (a.country) document.getElementById('n_agent_country').value = a.country;
        hint.textContent = 'Booking code ' + a.acc + (a.margin !== null ? ' · standing margin ' + a.margin + '%' : '') + '. You can still change any of it.'
            + (a.is_agent === false && Number(MASTERS?.agents_marked || 0) > 0 ? ' This customer is not marked as an agent — if it is one, mark it under Master Files → Consignees.' : '');
        saveDraft();
    }
    document.getElementById('n_agent_pick')?.addEventListener('change', function () {
        const v = this.value;
        applyAgentPick((MASTERS?.customers || []).find(x => x.acc === v) || (MASTERS?.agents || []).find(x => x.acc === v));
    });

    // Type-ahead on the agent name: matches on name, long name, email and booking code,
    // so "lionroyal" finds every Lion Royal record however the name was typed in.
    (function () {
        const inp = document.getElementById('n_agent_name');
        const box = document.getElementById('n_agent_sugg');
        if (!inp || !box) return;
        let items = [], cur = -1;
        const close = () => { box.hidden = true; box.innerHTML = ''; items = []; cur = -1; };
        const paint = () => [...box.querySelectorAll('.it')].forEach((el, i) => el.classList.toggle('on', i === cur));
        const pick = i => { if (items[i]) { applyAgentPick(items[i]); } close(); };
        inp.addEventListener('input', async () => {
            const q = inp.value.trim().toLowerCase();
            if (q.length < 2) { close(); return; }
            await loadMasters();
            const all = MASTERS?.agents || [];
            if (!all.length) {
                box.innerHTML = '<div class="none">The customer list could not be read' + (MASTERS?.error ? ': ' + esc(MASTERS.error) : '') + '. Type the agent in — it is still saved.</div>';
                box.hidden = false; return;
            }
            const hit = a => [a.name, a.long, a.email, a.acc, a.country].some(v => v && String(v).toLowerCase().includes(q));
            // the marked agents first; then the rest of the ledger, each saying it is not marked
            items = all.filter(hit).slice(0, 10);
            if (items.length < 10 && (MASTERS?.customers || []).length) {
                items = items.concat(MASTERS.customers.filter(a => !a.is_agent && hit(a)).slice(0, 10 - items.length));
            }
            cur = -1;
            box.innerHTML = items.length
                ? items.map(a => `<div class="it"><b>${esc(a.long || a.name)}</b><span class="help">${esc(a.acc)}${a.email ? ' · ' + esc(a.email) : ''}${a.country ? ' · ' + esc(a.country) : ''}${a.is_agent === false && Number(MASTERS?.agents_marked || 0) > 0 ? ' · <span class="text-warning">not marked as an agent</span>' : ''}</span></div>`).join('')
                : '<div class="none">Nobody on the customer list matches. Keep typing — a name not on the list is saved as typed.</div>';
            box.hidden = false;
            [...box.querySelectorAll('.it')].forEach((el, i) => el.addEventListener('mousedown', e => { e.preventDefault(); pick(i); }));
        });
        inp.addEventListener('keydown', e => {
            if (box.hidden) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); cur = Math.min(cur + 1, items.length - 1); paint(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); cur = Math.max(cur - 1, 0); paint(); }
            else if (e.key === 'Enter') { if (cur >= 0) { e.preventDefault(); pick(cur); } }
            else if (e.key === 'Escape') { close(); }
        });
        inp.addEventListener('blur', () => setTimeout(close, 150));
    })();

    // Hotel search in the Night window: type part of the name (or the town) and pick from
    // what the Hotel Rates panel holds. Picking fills in the panel's own spelling — which is
    // what makes the rate look-up find anything — and the hotel's area. Nothing stops a name
    // the panel does not have from being typed; it is kept as written and flagged.
    (function () {
        const inp = document.getElementById('md_hotel');
        const box = document.getElementById('md_hotel_sugg');
        if (!inp || !box) return;
        let items = [], cur = -1;
        const close = () => { box.hidden = true; box.innerHTML = ''; items = []; cur = -1; };
        const paint = () => [...box.querySelectorAll('.it')].forEach((el, i) => el.classList.toggle('on', i === cur));
        const pick = i => {
            const h = items[i];
            if (h) { inp.value = h.name; if (h.area) document.getElementById('md_area').value = h.area; }
            close();
        };
        inp.addEventListener('input', async () => {
            const q = inp.value.trim().toLowerCase();
            if (q.length < 2) { close(); return; }
            await loadMasters();
            const all = MASTERS?.flat || [];
            if (!all.length) {
                box.innerHTML = '<div class="none">The hotel list could not be read' + (MASTERS?.error ? ': ' + esc(MASTERS.error) : '') + '. Type the hotel in — it is still saved.</div>';
                box.hidden = false; return;
            }
            const words = q.split(/\s+/).filter(Boolean);
            const codeNo = (q.match(/^(?:htl[\s-]*|h)?0*(\d{1,6})$/i) || [])[1];   // the hotel code: "HTL-017", "htl17" or "17"
            items = all.filter(h => { if (codeNo && h.code && parseInt(String(h.code).replace(/\D/g, ''), 10) === parseInt(codeNo, 10)) return true; const t = (h.name + ' ' + (h.area || '') + ' ' + (h.code || '')).toLowerCase(); return words.every(w => t.includes(w)); })
                .sort((a, b) => (a.name.toLowerCase().startsWith(q) ? 0 : 1) - (b.name.toLowerCase().startsWith(q) ? 0 : 1) || a.name.localeCompare(b.name))
                .slice(0, 12);
            cur = -1;
            box.innerHTML = items.length
                ? items.map(h => `<div class="it"><b>${esc(h.name)}</b><span class="help">${h.code ? '<span class="mono">' + esc(h.code) + '</span> · ' : ''}${esc(h.area || 'area not set')}${h.star ? ' · ' + esc(h.star) : ''}${h.has_supplier ? '' : ' · no supplier account'}${h.rates_pending ? ' · <b style="color:var(--tf-red,#b3261e)">Incomplete — Rates Pending</b>' : ''}</span></div>`).join('')
                : '<div class="none">No hotel in the panel matches. Keep typing — a name not in the panel is saved as written and flagged.</div>';
            box.hidden = false;
            [...box.querySelectorAll('.it')].forEach((el, i) => el.addEventListener('mousedown', e => { e.preventDefault(); pick(i); }));
        });
        inp.addEventListener('keydown', e => {
            if (box.hidden) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); cur = Math.min(cur + 1, items.length - 1); paint(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); cur = Math.max(cur - 1, 0); paint(); }
            else if (e.key === 'Enter') { if (cur >= 0) { e.preventDefault(); pick(cur); } }
            else if (e.key === 'Escape') { close(); }
        });
        inp.addEventListener('blur', () => setTimeout(close, 150));
    })();

    // ---------- draft protection ----------
    // Typing into the by-hand form is kept in this browser until the inquiry is created,
    // so stepping back to the list, or a reload, never costs somebody their work.
    const DRAFT_KEY = 'tf_manual_draft';
    function saveDraft() {
        try {
            const d = {};
            MANUAL_FIELDS.forEach(k => { d[k] = document.getElementById('n_' + k)?.value || ''; });
            d._pick = document.getElementById('n_agent_pick')?.value || '';
            d._nights = document.getElementById('n_make_nights')?.checked ? 1 : 0;
            localStorage.setItem(DRAFT_KEY, JSON.stringify(d));
        } catch (e) { /* private window, or storage blocked: the form still works */ }
    }
    function readDraft() {
        try { return JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { return null; }
    }
    function clearDraft() { try { localStorage.removeItem(DRAFT_KEY); } catch (e) {} }

    function openManual() {
        MANUAL_FIELDS.forEach(k => { const el = document.getElementById('n_' + k); if (el) el.value = ''; });
        document.getElementById('n_agent_pick').value = '';
        document.getElementById('n_agent_hint').textContent = '';
        document.getElementById('n_make_nights').checked = true;
        loadMasters();

        const d = readDraft();
        const hadSomething = d && MANUAL_FIELDS.some(k => (d[k] || '').trim() !== '');
        if (hadSomething) {
            MANUAL_FIELDS.forEach(k => { const el = document.getElementById('n_' + k); if (el && d[k]) el.value = d[k]; });
            setAgentPick(d._pick || '');
            document.getElementById('n_make_nights').checked = d._nights !== 0;
            countNights();
            toast('success', 'Picked up where you left off. Clear the fields if you meant to start fresh.');
        }
        setView('view_manual');
        document.getElementById('n_agent_name').focus();
    }

    // Nights are never typed. They are the gap between the two dates, shown as you set them.
    function countNights() {
        const a = document.getElementById('n_arrival_date').value;
        const b = document.getElementById('n_departure_date').value;
        const out = document.getElementById('n_nights');
        if (!a || !b) { out.value = ''; return; }
        const n = Math.round((new Date(b) - new Date(a)) / 86400000);
        out.value = n >= 0 ? n : '';
        out.title = n < 0 ? 'The departure is before the arrival.' : `${n} night(s), counted from the two dates.`;
    }
    ['n_arrival_date','n_departure_date'].forEach(id =>
        document.getElementById(id)?.addEventListener('change', () => { countNights(); saveDraft(); }));
    MANUAL_FIELDS.concat(['make_nights']).forEach(k => {
        const el = document.getElementById('n_' + k);
        el?.addEventListener('input', saveDraft);
        el?.addEventListener('change', saveDraft);
    });
    document.getElementById('btn_manual')?.addEventListener('click', openManual);
    document.getElementById('p_manual')?.addEventListener('click', openManual);
    document.getElementById('n_cancel')?.addEventListener('click', () => { setView('view_list'); loadList(); });
    document.getElementById('n_paste')?.addEventListener('click', openPaste);

    document.getElementById('n_save')?.addEventListener('click', async function () {
        const fields = {};
        MANUAL_FIELDS.forEach(k => {
            const v = (document.getElementById('n_' + k)?.value || '').trim();
            if (v !== '') fields[k] = v;
        });
        const was = this.innerHTML; this.disabled = true; this.innerHTML = 'Creating…';
        const j = await post('/intake', {
            fields,
            agent_acc: document.getElementById('n_agent_pick').value || '',
            make_nights: document.getElementById('n_make_nights').checked ? (parseInt(fields.nights, 10) || 0) : 0
        });
        this.disabled = false; this.innerHTML = was;
        if (j.status !== 200) { toast('error', j.message); return; }
        clearDraft();
        CURRENT = j.data; renderDetail(); setView('view_detail'); toast('success', j.message);
    });

    // An email arriving after the fact joins the inquiry that already exists, rather
    // than starting a second one for the same tour.
    async function attachEmail(read) {
        const text = document.getElementById('a_text').value;
        if (!text.trim()) { toast('error', 'Paste the email first.'); return; }
        const btn = document.getElementById(read ? 'a_read' : 'a_save');
        const was = btn.innerHTML; btn.disabled = true; btn.innerHTML = read ? 'Reading…' : 'Saving…';
        const j = await post('/intake/' + CURRENT.id + '/email', { source_text: text, read: !!read });
        btn.disabled = false; btn.innerHTML = was;
        if (j.status !== 200) { toast('error', j.message); return; }
        CURRENT = j.data; renderDetail(); toast('success', j.message);
    }
    document.getElementById('a_read')?.addEventListener('click', () => attachEmail(true));
    document.getElementById('a_save')?.addEventListener('click', () => attachEmail(false));
    // the same date check as the paste step, as the text is typed or pasted here
    let A_TIMER = null;
    document.getElementById('a_text')?.addEventListener('input', () => {
        clearTimeout(A_TIMER);
        const text = document.getElementById('a_text').value;
        if (!text.trim()) { document.getElementById('a_flags').innerHTML = ''; document.getElementById('a_dates').innerHTML = ''; return; }
        A_TIMER = setTimeout(() => checkText(text, document.getElementById('a_flags'), document.getElementById('a_dates'), (CURRENT && CURRENT.source_received_at) || ''), 700);
    });

    // ---------- install ----------
    document.getElementById('btn_setup')?.addEventListener('click', async function () {
        this.disabled = true; this.textContent = 'Installing…';
        const j = await post('/setup', {});
        if (j.status !== 200) { toast('error', j.message); this.disabled = false; this.textContent = 'Install now'; return; }
        Swal.fire({ icon:'success', title:'Installed', text: j.message }).then(() => location.reload());
    });

    // The quotation outputs — Internal costing, Client quotation, PDF, the two previews — open in a new tab. A browser
    // that refuses new tabs (the Claude desktop app's browser pane, a kiosk) would do nothing on the click, so where
    // the new tab is refused the same tab goes there instead; every output page has "Back to the inquiry".
    document.addEventListener('click', e => {
        const a = e.target.closest('a[target="_blank"]');
        if (!a || !a.href || !/\/intake\/\d+\/(quote\/\d+|preview)(\?|$)/.test(a.href)) return;
        if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        let w = null;
        try { w = window.open(a.href, '_blank'); } catch (err) { w = null; }
        if (!w) location.href = a.href;
    });

    // ---------- go ----------
    if (INSTALLED) {
        ['f_status', 'f_handler', 'f_from', 'f_to'].forEach(id => document.getElementById(id).addEventListener('change', loadList));
        let t = null;
        document.getElementById('f_q').addEventListener('input', () => { clearTimeout(t); t = setTimeout(loadList, 250); });
        document.getElementById('f_clear').addEventListener('click', () => {
            document.getElementById('f_status').value = 'open';
            ['f_handler', 'f_q', 'f_from', 'f_to'].forEach(id => document.getElementById(id).value = '');
            loadList();
        });
        // ?handler=ID opens the list already filtered to one colleague (a link from another desk)
        const H0 = new URLSearchParams(location.search).get('handler');
        if (H0) document.getElementById('f_handler').value = H0;
        const START = new URLSearchParams(location.search).get('start');
        if (OPEN_ID) { openIntake(OPEN_ID); }
        else if (START === 'paste') { openPaste(); }
        else if (START === 'manual') { openManual(); }
        else { loadList(); }
    }
})();
</script>
</body>
</html>
