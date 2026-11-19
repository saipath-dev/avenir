<?= view('layouts/head') ?>
<style>
    /* ---------- Confirmed Tour (tf-control-r2) — Overview and Passengers & Rooming; reads only; Avenir purple #8b1d40, green #78b848 ---------- */
    .oc { --p:#8b1d40; --g:#78b848; --ink:#1A1A1A; --line:#d9d3dd; --soft:#f6eef1; color:var(--ink); font-size:13px; }
    .oc .oc-back { display:inline-block; margin-bottom:4px; color:var(--p); font-weight:600; text-decoration:none; font-size:12px; }
    .oc .oc-top { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:8px; }
    .oc h2 { font-size:21px; font-weight:800; margin:0; color:var(--p); letter-spacing:-.2px; }
    .oc h2 .oc-who { color:#1A1A1A; font-weight:700; }
    .oc .oc-pill { display:inline-flex; align-items:center; gap:8px; background:var(--g); color:#fff; font-weight:700; font-size:13px; border-radius:5px; padding:4px 12px; }
    .oc .oc-pill.red { background:#c62828; }
    .oc .oc-facts { display:flex; flex-wrap:nowrap; overflow-x:auto; border:1px solid var(--line); border-radius:8px; background:#fff; padding:6px 2px; margin-bottom:10px; }
    .oc .oc-fact { display:flex; align-items:center; gap:8px; padding:1px 10px; border-right:1px solid var(--line); flex:1 1 auto; min-width:150px; }
    .oc .oc-fact.nw { flex:0 0 auto; } .oc .oc-fact.nw b { white-space:nowrap; }
    .oc .oc-fact:last-child { border-right:0; }
    .oc .oc-fact i { font-size:19px; color:var(--p); }
    .oc .oc-fact small { display:block; white-space:nowrap; font-size:11px; color:#1A1A1A; line-height:1.2; }
    .oc .oc-fact b { display:block; font-size:13px; line-height:1.2; }
    .oc .oc-fact b.pend { font-weight:500; }
    .oc .oc-tabs { display:flex; flex-wrap:wrap; border:1px solid var(--line); border-radius:7px; overflow:hidden; background:#fff; margin-bottom:12px; }
    .oc .oc-tab { flex:1 1 auto; text-align:center; padding:8px 10px; font-weight:600; font-size:13.5px; color:#1A1A1A; text-decoration:none; border-right:1px solid var(--line); white-space:nowrap; cursor:pointer; }
    .oc .oc-tab:last-child { border-right:0; } .oc .oc-tab.on { background:var(--p); color:#fff; }
    .oc .oc-pane { display:none; } .oc .oc-pane.on { display:block; }
    .oc .dot { width:11px; height:11px; border-radius:50%; display:inline-block; background:#b9b9b9; }
    .oc .dot.red { background:#e53935; } .oc .dot.amber { background:#f5a623; } .oc .dot.green { background:#3fae2a; }
    .oc .oc-ban { display:flex; align-items:center; gap:26px; background:#fbe6f1; border-radius:8px; padding:10px 24px 10px 70px; margin-bottom:12px; }
    .oc .oc-ban.ok { background:#eaf6df; }
    .oc .oc-ban .txt { border-left:2px solid var(--p); padding-left:26px; }
    .oc .oc-ban h3 { margin:0; font-size:22px; font-weight:800; color:var(--p); } .oc .oc-ban.ok h3 { color:#3d7d1c; }
    .oc .oc-ban p { margin:1px 0 0 0; font-size:13.5px; }
    .oc .tl { display:inline-flex; gap:7px; background:#2b2b2b; border-radius:9px; padding:6px 9px; box-shadow:0 2px 4px rgba(0,0,0,.3); }
    .oc .tl span { width:18px; height:18px; border-radius:50%; }
    .oc .tl.big { gap:12px; padding:9px 13px; border-radius:11px; } .oc .tl.big span { width:34px; height:34px; }
    .oc .tl span.r { background:#5a2a28; } .oc .tl span.a { background:#5f4a1c; } .oc .tl span.g { background:#254a25; }
    .oc .tl.red span.r { background:#ff3b30; box-shadow:0 0 9px #ff3b30; } .oc .tl.amber span.a { background:#ffcc00; box-shadow:0 0 9px #ffcc00; } .oc .tl.green span.g { background:#34e034; box-shadow:0 0 9px #34e034; }
    .oc .oc-cards { display:grid; grid-template-columns:repeat(5, 1fr); gap:10px; margin-bottom:12px; }
    .oc .oc-card { border:1px solid var(--line); border-radius:7px; background:#fff; padding:9px 12px; display:grid; grid-template-columns:auto 1fr; column-gap:14px; row-gap:4px; align-items:center; }
    .oc .oc-card > i { font-size:22px; color:var(--p); justify-self:center; }
    /* tf-control-r4 (19 Nov 2026, Shantha): the mock-up's own symbols — bed, car, fork and knife, tilted ticket, three people */
    .oc i.oci { display:inline-block; width:1.25em; height:1em; background:currentColor; vertical-align:-.125em; -webkit-mask:var(--m) center / contain no-repeat; mask:var(--m) center / contain no-repeat; }
    .oc i.oci::before { content:none; }
    .oc i.oci-bed { --m:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z'/%3E%3C/svg%3E"); } .oc i.oci-car { --m:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z'/%3E%3C/svg%3E"); } .oc i.oci-meal { --m:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z'/%3E%3C/svg%3E"); } .oc i.oci-group { --m:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 12.75c1.63 0 3.07.39 4.24.9 1.08.48 1.76 1.56 1.76 2.73V18H6v-1.61c0-1.18.68-2.26 1.76-2.73 1.17-.52 2.61-.91 4.24-.91zM4 13c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm1.13 1.1c-.37-.06-.74-.1-1.13-.1-.99 0-1.93.21-2.78.58C.48 14.9 0 15.62 0 16.43V18h4.5v-1.61c0-.83.23-1.61.63-2.29zM20 13c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm4 3.43c0-.81-.48-1.53-1.22-1.85-.85-.37-1.79-.58-2.78-.58-.39 0-.76.04-1.13.1.4.68.63 1.46.63 2.29V18H24v-1.57zM12 6c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z'/%3E%3C/svg%3E"); width:1.5em; }
    .oc i.oc-tilt { display:inline-block; transform:rotate(-35deg); }
    .oc .oc-card > i { font-size:30px; }
    .oc .oc-card h5 { margin:0; font-size:14.5px; font-weight:700; }
    .oc .oc-card .st b { display:block; font-size:14px; font-weight:800; line-height:1.1; } .oc .oc-card .st span { font-size:12.5px; }
    .oc .c-red { color:#e02b20; } .oc .c-amber { color:#ee8a00; } .oc .c-green { color:#3fa22a; } .oc .c-grey { color:#1A1A1A; }
    .oc .oc-box { border:1px solid var(--line); border-radius:7px; background:#fff; overflow:hidden; margin-bottom:12px; }
    /* tf-control-r5 (19 Nov 2026, Shantha): the whole Overview to the proportions of the approved mock-up — title, the fact strip
       starting with the tour number, a status dot on every tab, the large signal light, the five area cards, the work table */
    .oc { font-size:14px; }
    .oc .oc-top { margin-bottom:10px; } .oc h2.oc-title { font-size:28px; font-weight:800; color:#1A1A1A; letter-spacing:-.3px; }
    .oc .oc-top .oc-own { margin-left:auto; font-size:12.5px; } .oc .oc-top .oc-pill { font-size:13.5px; padding:6px 16px; border-radius:16px; }
    .oc .oc-facts { padding:8px 4px; margin-bottom:12px; border-radius:9px; }
    .oc .oc-facts { overflow:hidden; display:grid; grid-template-columns:max-content max-content minmax(0,1.2fr) max-content minmax(0,1.3fr) max-content minmax(0,1.25fr) minmax(0,1.25fr); }
    .oc .oc-fact, .oc .oc-fact.nw { gap:9px; padding:2px 10px; min-width:0; } .oc .oc-fact i { font-size:22px; flex:none; } .oc .oc-fact > div { min-width:0; } .oc .oc-fact:not(.nw) small { white-space:normal; }
    @media (max-width:640px) { .oc .oc-facts { grid-template-columns:repeat(4, minmax(0,1fr)); row-gap:8px; } }
    @media (max-width:640px) { .oc .oc-facts { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    .oc .oc-fact small { font-size:11.5px; } .oc .oc-fact b { font-size:13.5px; } .oc .oc-fact b.pend { font-weight:800; color:var(--p); }
    .oc .oc-tabs { margin-bottom:14px; border-radius:8px; }
    .oc .oc-tab { padding:10px 10px; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:9px; }
    .oc .oc-tab .dot { width:12px; height:12px; flex:none; } .oc .oc-tab.on .dot { background:#fff; }
    .oc .oc-ban { gap:34px; padding:14px 28px 14px 92px; margin-bottom:14px; border-radius:9px; }
    .oc .oc-ban .txt { padding-left:34px; } .oc .oc-ban h3 { font-size:29px; letter-spacing:-.2px; } .oc .oc-ban p { font-size:15.5px; margin-top:2px; }
    .oc .tl.big { gap:16px; padding:10px 17px; border-radius:14px; } .oc .tl.big span { width:44px; height:44px; }
    .oc .tl { gap:8px; padding:6px 9px; border-radius:10px; } .oc .tl span { width:19px; height:19px; }
    .oc .oc-cards { gap:12px; margin-bottom:14px; }
    .oc .oc-card { padding:10px 12px 11px; border-radius:9px; column-gap:12px; row-gap:6px; }
    .oc .oc-card > i { font-size:32px; } .oc .oc-card h5 { font-size:15.5px; line-height:1.2; }
    .oc .oc-card .st b { font-size:15px; white-space:nowrap; } .oc .oc-card .st span { font-size:12px; display:block; margin-top:2px; line-height:1.25; }
    .oc .oc-box { border-radius:9px; margin-bottom:14px; } .oc .oc-box > .h { font-size:19px; padding:5px 18px; }
    .oc table.ow th { font-size:14.5px; padding:7px 14px; } .oc table.ow td { font-size:15px; padding:6px 14px; }
    .oc table.ow td.area .dot { width:15px; height:15px; margin-right:14px; }
    .oc .gbtn { font-size:14px; padding:5px 16px; border-radius:6px; } .oc table.ow .gbtn.act { min-width:200px; }
    .oc .oc-row { padding:9px 16px; font-size:15px; gap:10px; } .oc .oc-row i.lead { font-size:22px; } .oc .oc-docs { padding:12px 16px; gap:14px; }
    .oc .oc-foot { font-size:11.5px; padding:9px 14px; gap:8px; }
    .oc .oc-ban { padding-top:9px; padding-bottom:9px; } .oc .tl.big { padding:9px 15px; gap:14px; } .oc .tl.big span { width:40px; height:40px; }
    .oc .oc-card { padding:8px 12px 9px; row-gap:4px; } .oc .oc-card > i { font-size:28px; }
    .oc table.ow th { padding:5px 14px; } .oc table.ow td { padding:4px 14px; } .oc .oc-row { padding:7px 16px; } .oc .oc-docs { padding:10px 16px; }
    .oc .oc-facts { padding:6px 4px; } .oc .oc-back { margin-bottom:2px; }
    /* tf-control-r6 (19 Nov 2026): the last differences from the mock-up — the title row carries only the title and one soft pill,
       the fact strip uses the handshake and the landing / take-off aircraft, the legend is one line with its divider */
    .oc .oc-top { justify-content:space-between; flex-wrap:nowrap; } .oc .oc-top .oc-pill { margin-left:auto; background:#f3dbe3; color:var(--p); font-weight:800; text-transform:uppercase; letter-spacing:.03em; font-size:13.5px; padding:6px 20px; border-radius:18px; white-space:nowrap; }
    .oc .oc-top .oc-pill.red { background:#fbdde3; color:#a3123a; }
    .oc .oc-fact i.mdi { font-size:26px; line-height:1; color:var(--p); }
    #oc .oc-foot { flex-wrap:nowrap; font-size:11.5px; } #oc .oc-foot > span:first-child { min-width:0; } #oc .oc-foot .lg { border-left:1px solid #b9a9af; padding-left:4px; white-space:nowrap; flex:none; } #oc .oc-foot .dot { margin:0 4px 0 10px; }
    #oc .oc-fact:not(.nw) small { white-space:nowrap; } #oc .oc-fact:not(.nw) b { overflow-wrap:anywhere; }
    @media (max-width:640px) { #oc .oc-foot { flex-wrap:wrap; } #oc .oc-foot .lg { border-left:0; padding-left:0; } .oc .oc-top { flex-wrap:wrap; } } .oc .oc-foot .dot { width:13px; height:13px; }
    .oc .oc-box > .h { background:var(--p); color:#fff; font-size:15.5px; font-weight:700; padding:5px 16px; }
    .oc .oc-box > .h2 { background:var(--soft); color:var(--p); font-size:15px; font-weight:700; padding:6px 14px; display:flex; align-items:center; gap:10px; }
    .oc table.ow { width:100%; border-collapse:collapse; }
    .oc table.ow th { background:var(--soft); text-align:left; padding:6px 12px; font-size:13px; font-weight:700; }
    .oc table.ow td { padding:5px 12px; font-size:13.5px; border-top:1px solid var(--line); vertical-align:middle; }
    .oc table.ow.grid th, .oc table.ow.grid td { border-right:1px solid var(--line); } .oc table.ow.grid th:last-child, .oc table.ow.grid td:last-child { border-right:0; }
    .oc table.ow td.area { font-weight:700; white-space:nowrap; } .oc table.ow td.area .dot { margin-right:12px; width:13px; height:13px; vertical-align:-2px; }
    .oc .gbtn { display:inline-flex; align-items:center; gap:8px; background:var(--g); color:#fff !important; font-weight:700; font-size:13px; border-radius:5px; padding:4px 14px; text-decoration:none; border:1px solid var(--g); white-space:nowrap; cursor:pointer; }
    .oc .gbtn:hover { background:#68a53d; } .oc .gbtn.off { background:#c9c9c9; border-color:#c9c9c9; pointer-events:none; }
    .oc .gbtn.line { background:#fff; color:#2f6d12 !important; } .oc .gbtn.line.off { color:#8a8a8a !important; border-color:#c9c9c9; }
    .oc .gbtn.lg { font-size:14.5px; padding:6px 26px; }
    .oc table.ow .gbtn.act { min-width:190px; }
    .oc .oc-two { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .oc .oc-row { display:flex; align-items:center; gap:10px; padding:6px 14px; border-top:1px solid var(--line); font-size:13.5px; }
    .oc .oc-row:first-of-type { border-top:0; } .oc .oc-row i.lead { font-size:18px; color:var(--p); } .oc .oc-row .grow { flex:1; }
    .oc .oc-docs { display:flex; gap:12px; flex-wrap:wrap; padding:9px 14px; }
    .oc .oc-foot { display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between; background:var(--soft); border-radius:6px; padding:7px 14px; font-size:12px; }
    .oc .oc-foot .dot { margin:0 5px 0 12px; vertical-align:-1px; }
    .oc .oc-msg { padding:14px; border:1px solid var(--line); border-radius:7px; background:#fff; }
    /* Passengers & Rooming */
    .oc .pr-head { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
    .oc .pr-head h3 { margin:0; font-size:20px; font-weight:800; color:var(--p); flex:1; display:flex; align-items:center; gap:12px; }
    .oc .pr-stats { display:flex; flex-wrap:wrap; align-items:center; background:var(--soft); border-radius:6px; padding:7px 14px; margin-bottom:8px; font-size:13.5px; }
    .oc .pr-stats > span { padding:0 18px; border-right:1px solid var(--line); } .oc .pr-stats > span:first-child { padding-left:0; } .oc .pr-stats > span:last-child { border-right:0; }
    .oc .pr-stats b { color:var(--p); } .oc .pr-stats .miss { color:var(--p); font-weight:700; }
    .oc .pr-stay { display:flex; flex-wrap:wrap; align-items:center; gap:14px; margin-bottom:8px; font-size:13px; }
    .oc .pr-stay select { border:1px solid var(--line); border-radius:5px; padding:4px 8px; font-size:13px; min-width:300px; background:#fff; }
    .oc .pr-stay .req { color:var(--p); font-weight:700; font-size:14px; background:var(--soft); border-radius:5px; padding:3px 10px; }
    .oc .pr-grid { display:grid; grid-template-columns:2.4fr 1fr; gap:14px; align-items:start; }
    .oc .ok-pill { display:inline-flex; align-items:center; gap:6px; background:#e4f4d6; color:#2f6d12; border-radius:12px; padding:1px 10px; font-size:12px; font-weight:600; }
    .oc .warn-pill { display:inline-block; background:#fbdde3; color:#a3123a; border-radius:12px; padding:1px 10px; font-size:12px; font-weight:600; }
    .oc .pr-note { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; font-size:12px; margin:-6px 2px 10px 2px; } .oc .pr-note i { color:var(--p); }
    .oc .pr-un .oc-row i { color:var(--p); }
    .oc .pr-det { display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap; border:1px solid var(--line); border-radius:7px; background:#fff; padding:8px 14px; margin-bottom:12px; }
    .oc .pr-det .ttl { color:var(--p); font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px; padding-bottom:4px; min-width:190px; }
    .oc .pr-det label { display:block; font-size:11.5px; margin:0 0 2px 0; } .oc .pr-det .f { flex:1 1 130px; }
    .oc .pr-det select, .oc .pr-det input { width:100%; border:1px solid var(--line); border-radius:5px; padding:4px 8px; font-size:13px; background:#fff; color:#1A1A1A; }
    .oc .pr-det select:disabled, .oc .pr-det input:disabled { background:#f7f7f7; }
    .oc .st-pill { display:inline-block; border-radius:12px; padding:1px 10px; font-size:12px; font-weight:600; white-space:nowrap; }
    .oc .st-pill.red { background:#fbdde3; color:#a3123a; } .oc .st-pill.amber { background:#fdeccb; color:#8a5200; } .oc .st-pill.green { background:#e4f4d6; color:#2f6d12; } .oc .st-pill.grey { background:#eee; color:#1A1A1A; }
    .oc .tp-empty { padding:12px 14px; }
    .oc .ver { display:inline-block; background:#f1f1f1; border-radius:10px; padding:1px 9px; font-size:11.5px; }
    /* a narrow window: the fields go onto tidy rows of their own, in the same order — never on top of each other */
    @media (max-width:640px) { .oc .oc-facts { flex-wrap:wrap; overflow-x:visible; row-gap:8px; } .oc .oc-fact, .oc .oc-fact.nw { flex:1 1 22%; min-width:170px; } .oc .oc-fact:nth-child(4n) { border-right:0; } }
    @media (max-width:640px) { .oc .oc-fact, .oc .oc-fact.nw { flex:1 1 45%; } .oc .oc-fact:nth-child(2n) { border-right:0; } }
    @media (max-width:640px) { .oc .oc-cards { grid-template-columns:repeat(2, 1fr); } .oc .oc-two, .oc .pr-grid { grid-template-columns:1fr; } .oc .oc-ban { padding-left:18px; flex-wrap:wrap; } .oc table.ow .gbtn.act { min-width:0; } .oc .oc-box { overflow-x:auto; } }
    /* tf-supdesk-r1: Supplier Emails & Vouchers */
    .oc .sm-t td { vertical-align:top; } .oc .sm-t td small { display:block; font-size:12px; color:#5f6470; margin-top:2px; } .oc .sm-mut { color:#5f6470; font-size:12.5px; }
    .oc .sm-a { white-space:nowrap; } .oc .sm-b { display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 11px; margin:0 4px 4px 0; border:1.5px solid #8b1d40; border-radius:7px; background:#fff; color:#8b1d40; font-size:12.5px; font-weight:700; text-decoration:none; cursor:pointer; }
    .oc .sm-b:hover { background:#f8f1f3; } .oc .sm-b.go { background:#78b848; border-color:#78b848; color:#fff; } .oc .sm-b.go.again { background:#8b1d40; border-color:#8b1d40; } .oc .sm-b:disabled { background:#c9c9c9; border-color:#c9c9c9; color:#fff; cursor:not-allowed; }
    .oc .sm-sub td { background:#fbf7f9; padding-top:6px; padding-bottom:8px; font-size:12.5px; } .oc .sm-sub .bad, .oc .sm-t .bad { color:#a3261d; font-weight:700; } .oc .sm-sub .warn { color:#8a5a00; font-weight:600; } .oc .sm-sub .ok { color:#3f7a17; font-weight:700; }
    .oc .sm-h { display:flex; gap:14px; flex-wrap:wrap; padding:3px 0; } .oc .sm-h a { color:#8b1d40; font-weight:700; } .oc .sm-h span:first-child { min-width:118px; }
    .oc .sm-c { border:1.5px solid #8b1d40; border-radius:9px; background:#fff; padding:10px 14px; margin:6px 0; display:grid; gap:7px; font-size:13.5px; max-width:720px; } .oc .sm-c label { display:flex; gap:8px; align-items:center; font-weight:600; cursor:pointer; margin:0; }
    .oc .sm-c input[type=checkbox] { width:17px; height:17px; accent-color:#8b1d40; } .oc .sm-c input[type=text] { height:34px; border:1.5px solid #d9d3dd; border-radius:7px; padding:0 10px; font-size:13.5px; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content oc" id="oc">
            <div class="container-fluid">
                <div id="oc_body"><div class="oc-msg">Loading&hellip;</div></div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>
<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const TOUR_ID = <?= (int) ($tour_id ?? 0) ?>;
    const PAGE = '<?= base_url($page_url ?? 'tour/intake/confirmed') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const HOTELS = '<?= base_url('tour/hotel_bookings') ?>', TRANSPORT = '<?= base_url('tour/transport_ops') ?>', BOARD = '<?= base_url('tour/board') ?>';
    const CAN = { hotel: <?= !empty($can_hotel) ? 'true' : 'false' ?>, transport: <?= !empty($can_transport) ? 'true' : 'false' ?>, board: <?= !empty($can_board) ? 'true' : 'false' ?> };
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const get = async u => (await fetch(u, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json();
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const dm = d => { const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(d || ''); return m ? Number(m[3]) + ' ' + MON[Number(m[2]) - 1] : ''; };
    const range = (a, b) => { const y = /^(\d{4})/.exec(b || a || ''); return a || b ? `${dm(a)} – ${dm(b)}${y ? ' ' + y[1] : ''}` : 'Not given'; };
    const WORD = { red: 'RED', amber: 'AMBER', green: 'GREEN', grey: 'NOT REQUIRED' };
    const light = (c, big) => `<span class="tl ${big ? 'big ' : ''}${c}"><span class="r"></span><span class="a"></span><span class="g"></span></span>`;
    const MEALS = ['lunch', 'dinner', 'breakfast', 'meal'];

    // one area's light, from what the desks have recorded — nothing is assumed
    function areas(c, it) {
        const s = c.strip || {}, H = c.hotels || [], T = c.transport || [], S = c.services || [];
        const out = {};
        // Hotels
        const hs = k => H.filter(h => h.status === k).length;
        if (!H.length) out.hotels = ['grey', 'No hotel on this tour'];
        else if (hs('problem')) out.hotels = ['red', hs('problem') + ' with a problem'];
        else if (hs('to_book')) out.hotels = ['red', hs('to_book') + ' not requested'];
        else if (hs('requested')) out.hotels = ['amber', 'Awaiting confirmation'];
        else if (hs('confirmed')) out.hotels = ['amber', 'Voucher to issue'];
        else out.hotels = ['green', 'Vouchers issued'];
        // Transport
        const drv = (s.driver || {}).state, veh = (s.vehicle || {}).state;
        if (!T.length) out.transport = ['red', 'No vehicle on the tour'];
        else if (veh === 'to_allocate') out.transport = ['red', 'Vehicle not assigned'];
        else if (drv === 'to_allocate') out.transport = ['red', 'Driver not assigned'];
        else if (s.transport_complete) out.transport = ['green', 'Complete'];
        else out.transport = ['amber', 'Awaiting completion'];
        // Meals, and Entrances & Activities — the service lines of the tour
        const svc = (rows, none, pend) => {
            if (!rows.length) return ['grey', none];
            const n = k => rows.filter(r => r.status === k).length;
            if (n('problem')) return ['red', n('problem') + ' with a problem'];
            if (n('to_book')) return ['red', n('to_book') + ' not booked'];
            if (n('requested')) return ['amber', pend];
            return ['green', 'Confirmed'];
        };
        const live = S.filter(r => r.status !== 'cancelled');
        out.meals = svc(live.filter(r => MEALS.includes(String(r.kind))), 'No meal to arrange', 'Awaiting confirmation');
        out.acts = svc(live.filter(r => !MEALS.includes(String(r.kind))), 'Nothing to arrange', 'Tickets pending');
        // Passengers & Rooming
        const names = (it.passengers || []).filter(p => String(p.full_name || '').trim() !== '').length, pax = Number(it.pax_total || 0);
        const rooms = Number((it.rooms || {}).rooms || 0);
        if (pax && names >= pax && rooms) out.pax = ['green', 'Complete'];
        else if (names || rooms) out.pax = ['amber', pax ? `${names} of ${pax} names` : 'Party size not given'];
        else out.pax = ['red', 'No names or rooms'];
        // Itinerary: the version the customer accepted
        out.itin = c.tour.quote_version ? ['green', 'Version ' + c.tour.quote_version] : ['red', 'No issued version'];
        // Documents: hotel vouchers
        const noV = H.filter(h => !h.voucher_no && h.status !== 'cancelled').length;
        out.docs = !H.length ? ['grey', 'Nothing to issue'] : (noV ? ['red', noV + ' voucher' + (noV === 1 ? '' : 's') + ' to issue'] : ['green', 'Issued']);
        // Flights
        out.flights = it.arrival_flight && it.departure_flight ? ['green', 'Given'] : ['red', 'Pending'];
        return out;
    }

    let TAB = 'over', STAY = 0;
    try { const h = (location.hash || '').replace('#', ''); if (['over', 'hotels', 'trans', 'meals', 'acts', 'pax', 'itin', 'mail', 'docs'].includes(h)) TAB = h; } catch (e) {}

    function draw(c, it) {
        const t = c.tour, A = areas(c, it), id = t.id, iid = t.intake_id;
        const toHotel = CAN.hotel ? `${HOTELS}?tour=${id}` : null, toTrans = CAN.transport ? `${TRANSPORT}?tour=${id}` : null;
        const toInq = `${INTAKE}?id=${iid}`, toDocs = `${INTAKE}/comms/${iid}`;
        const prog = CAN.board ? `${BOARD}/tour/${id}/programme` : (CAN.hotel ? `${HOTELS}/tour/${id}/programme` : (CAN.transport ? `${TRANSPORT}/tour/${id}/programme` : null));
        const quote = t.quote_id ? `${INTAKE}/intake/${iid}/quote/${t.quote_id}?view=client` : null;
        const btn = (href, label, icon, cls) => `<a class="gbtn ${cls || ''}${href ? '' : ' off'}" ${href ? `href="${href}" target="_blank"` : 'title="Not available yet"'}><i class="bi ${icon || 'bi-box-arrow-up-right'}"></i> ${label}</a>`;
        const W = {   // the work a red or amber area still needs: [area, work, responsible, link, button]
            hotels: s => ['Hotels', s === 'amber' ? (A.hotels[1] === 'Voucher to issue' ? 'Issue hotel voucher' : 'Record hotel confirmation') : (A.hotels[1].includes('problem') ? 'Resolve the hotel problem' : 'Request the hotel booking'), 'Reservations', toHotel, 'Open hotel booking'],
            transport: s => ['Transport', A.transport[1] === 'No vehicle on the tour' ? 'Add the vehicle for this tour' : (s === 'red' ? 'Assign driver and confirm acceptance' : 'Complete the transport arrangements'), 'Transport team', toTrans, 'Open Transport'],
            meals: s => ['Meals', s === 'amber' ? 'Record the restaurant confirmation' : 'Book the meals', 'Reservations', toHotel, 'Open meals'],
            acts: s => ['Entrances', s === 'amber' ? 'Allocate the tickets and record confirmation' : 'Book the entrances and activities', 'Operations', toHotel, 'Open entrances'],
            pax: () => ['Passengers', 'Complete the passenger names and rooming', 'Reservations', '#pax', 'Open passengers'],
            itin: () => ['Itinerary', 'Issue the quotation version the customer accepted', 'Reservations', toInq, 'Open inquiry'],
            docs: () => ['Documents', 'Issue hotel voucher', 'Reservations', toHotel, 'Open voucher'],
            flights: () => ['Flights', 'Complete arrival and departure details', 'Operations', toInq, 'Open flight details'],
        };
        const todo = Object.keys(W).filter(k => A[k][0] === 'red' || A[k][0] === 'amber').map(k => [A[k][0]].concat(W[k](A[k][0])));
        const worst = todo.some(r => r[0] === 'red') ? 'red' : (todo.length ? 'amber' : 'green');
        const fact = (icon, label, val, pend, wrap) => `<div class="oc-fact${wrap ? '' : ' nw'}"><i class="${/^mdi/.test(icon) ? icon : 'bi ' + icon}"></i><div><small>${label}</small><b${pend ? ' class="pend"' : ''}>${esc(val)}</b></div></div>`;
        const card = (icon, label, k) => `<div class="oc-card"><i class="bi ${icon}"></i><h5>${label}</h5>${light(A[k][0])}<div class="st"><b class="c-${A[k][0]}">${WORD[A[k][0]]}</b><span>${esc(A[k][1])}</span></div></div>`;
        const pax = Number(it.pax_total || 0), ad = Number(it.pax_adults || 0), ch = Number(it.pax_children || 0);
        const party = pax ? (ch ? `${ad} adults, ${ch} children` : `${ad || pax} adult${(ad || pax) === 1 ? '' : 's'}`) : 'party not given';
        const aref = String(((it.fields || {}).agent_reference || {}).value || '').trim();   // the agent's own reference, as kept on the inquiry
        const ver = t.quote_version ? 'Version ' + t.quote_version : 'No issued version';
        // the heading names the party as the e-mail did (the group / tour name), else the first passenger
        const lead = String(it.tour_name || '').trim() || String(((it.passengers || [])[0] || {}).full_name || '').trim() || 'Name not given';
        const dmy = d => { const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(d || ''); return m ? Number(m[3]) + ' ' + MON[Number(m[2]) - 1] + ' ' + m[1] : ''; };
        // arrival and departure: the date, the flight number and the time together, in one box
        const fly = (d, f) => String(f || '').trim() || 'Pending';   // tf-control-r6: as the mock-up — the flight and time, or Pending (the date is in the tour dates beside it)
        const cancelled = !!t.cancelled_at || t.stage === 'cancelled';
        const pill = cancelled ? '<span class="oc-pill red"><i class="bi bi-x-lg"></i> Tour cancelled</span>'
            : `<span class="oc-pill" title="Operations owner: ${esc(it.handler_name || 'Unassigned')}">Tour confirmed • ${worst === 'green' ? 'Arrangements complete' : 'Bookings in progress'}</span>`;
        // tf-control-r5: the tabs as the mock-up names them, each with the light of its own area
        const tabs = [['over', 'Overview', 'grey'], ['hotels', 'Hotels', A.hotels[0]], ['trans', 'Transport', A.transport[0]], ['meals', 'Meals', A.meals[0]], ['acts', 'Entrances &amp; Activities', A.acts[0]],
            ['pax', 'Passengers &amp; Rooming', A.pax[0]], ['itin', 'Itinerary', t.quote_version ? 'green' : 'grey'], ['mail', 'Supplier Emails &amp; Vouchers', 'grey'], ['docs', 'Documents &amp; History', 'grey']];
        const L = { toInq, toHotel, toTrans, toDocs, prog, quote, ver, btn };
        const more = morePanes(c, it, L);

        const overview = `
            <div class="oc-ban${worst === 'green' ? ' ok' : ''}">${light(worst, true)}
                <div class="txt"><h3>${worst === 'green' ? 'READY FOR ARRIVAL' : `NOT READY FOR ARRIVAL • ${todo.length} outstanding task${todo.length === 1 ? '' : 's'}`}</h3>
                <p>Green only when every required arrangement and document is complete.</p></div></div>
            <div class="oc-cards">
                ${card('oci oci-bed', 'Hotels', 'hotels')}${card('oci oci-car', 'Transport', 'transport')}${card('oci oci-meal', 'Meals', 'meals')}
                ${card('bi-ticket-perforated oc-tilt', 'Entrances &amp; Activities', 'acts')}${card('oci oci-group', 'Passengers &amp; Rooming', 'pax')}
            </div>
            <div class="oc-box"><div class="h">Outstanding work • Red &amp; yellow items</div>
                <table class="ow grid"><thead><tr><th>Area</th><th>Work still required</th><th>Responsible</th><th>Due</th><th>Action</th></tr></thead><tbody>
                ${todo.length ? todo.map(r => `<tr><td class="area"><span class="dot ${r[0]}"></span>${r[1]}</td><td>${esc(r[2])}</td><td>${r[3]}</td><td>Not set</td><td>${r[4] === '#pax' ? `<a class="gbtn act" data-octab="pax"><i class="bi bi-box-arrow-up-right"></i> ${r[5]}</a>` : btn(r[4], r[5], null, 'act')}</td></tr>`).join('')
                    : '<tr><td colspan="5">Nothing outstanding: every required arrangement and document is complete.</td></tr>'}
                </tbody></table></div>
            <div class="oc-two">
                <div class="oc-box"><div class="h">Itinerary • Preview &amp; Print</div>
                    <div class="oc-row"><i class="bi bi-file-text lead"></i><span class="grow">Client itinerary • ${ver}${t.quote_version ? ' • Final' : ''}</span>${btn(quote, 'Preview', 'bi-eye')}${btn(quote, 'Print', 'bi-printer')}${btn(quote ? quote + '&pdf=1' : null, 'PDF', 'bi-file-earmark-pdf')}</div>
                    <div class="oc-row"><i class="bi bi-file-text lead"></i><span class="grow">Driver / guide itinerary • ${ver}${t.quote_version ? ' • Final' : ''}</span>${btn(prog, 'Preview', 'bi-eye')}${btn(prog, 'Print', 'bi-printer')}${btn(null, 'PDF', 'bi-file-earmark-pdf')}</div></div>
                <div class="oc-box"><div class="h">Booking documents</div>
                    <div class="oc-docs">${btn(toHotel, 'Hotel request &amp; voucher', 'oci oci-bed')}${btn(toHotel, 'Meal / safari vouchers', 'oci oci-meal')}${btn(toHotel, 'Entrance tickets', 'bi-ticket-perforated oc-tilt')}</div>
                    <div class="oc-row"><i class="bi oci oci-car lead"></i><span>Driver agreement &amp; invoice &rarr; ${toTrans ? `<a href="${toTrans}" target="_blank" style="color:#8b1d40;font-weight:600">Open Transport</a>` : 'Open Transport'}</span></div></div>
            </div>
            <div class="oc-foot"><span>Hotel bookings, vouchers and entrances managed here • Transport tasks linked to Transport panel • One shared status, no duplicate entry</span>
                <span class="lg"><span class="dot red"></span><b>RED:</b> Action needed <span class="dot amber"></span><b>AMBER:</b> Awaiting completion <span class="dot green"></span><b>GREEN:</b> Complete</span></div>`;

        document.getElementById('oc_body').innerHTML = `
            <div class="oc-top"><h2 class="oc-title">Confirmed Tour • Operations Control</h2>${pill}</div>
            <div class="oc-facts">
                ${fact('bi-briefcase', 'Tour', t.tour_no || '—')}
                ${fact('bi-file-text', 'Inquiry', it.inquiry_no || '—').replace('<b>', `<b><a href="${toInq}" target="_blank" style="color:inherit" title="Open the confirmed inquiry">`).replace('</b>', '</a></b>')}
                ${fact('mdi mdi-handshake-outline', 'Agent:', it.agent_name || 'Not given', false, true)}
                ${fact('bi-bookmark', 'Agent booking ref:', aref || 'Not recorded', !aref)}
                ${fact('bi-people', 'Group:', (it.tour_name || lead || 'Not named') + ' • ' + party, false, true)}
                ${fact('bi-calendar3', '', range(it.arrival_date, it.departure_date)).replace('<small></small>', '')}
                ${fact('mdi mdi-airplane-landing', 'Arrival flight / time:', fly(it.arrival_date, it.arrival_flight), !String(it.arrival_flight || '').trim(), true)}
                ${fact('mdi mdi-airplane-takeoff', 'Departure flight / time:', fly(it.departure_date, it.departure_flight), !String(it.departure_flight || '').trim(), true)}
            </div>
            <div class="oc-tabs">${tabs.map(x => `<a class="oc-tab${TAB === x[0] ? ' on' : ''}" data-octab="${x[0]}"><span class="dot ${x[2] || 'grey'}"></span>${x[1]}</a>`).join('')}</div>
            <div class="oc-pane${TAB === 'over' ? ' on' : ''}" data-ocpane="over">${overview}</div>
            <div class="oc-pane${TAB === 'pax' ? ' on' : ''}" data-ocpane="pax">${paxPane(c, it, L)}</div>
            <div class="oc-pane${TAB === 'mail' ? ' on' : ''}" data-ocpane="mail"><div id="sm_box"></div></div>
            ${Object.keys(more).map(k => `<div class="oc-pane${TAB === k ? ' on' : ''}" data-ocpane="${k}">${more[k]}</div>`).join('')}`;
        drawMail();
        document.querySelectorAll('#oc_body [data-octab]').forEach(b => b.addEventListener('click', e => { e.preventDefault(); TAB = b.dataset.octab; try { history.replaceState(null, '', '#' + TAB); } catch (x) {} draw(c, it); }));
        fit();
        const sel = document.getElementById('pr_stay'); if (sel) sel.addEventListener('change', () => { STAY = Number(sel.value) || 0; draw(c, it); });
        document.querySelectorAll('#oc_body [data-prpax]').forEach(r => r.addEventListener('click', () => { PICK = r.dataset.prpax; draw(c, it); }));
    }

    // tf-control-r7 (19 Nov 2026, Shantha): the approved layout at every window width — on a window narrower than the design
    // (a side pane, a small laptop) the whole panel is scaled down to fit instead of being re-stacked; phones keep the stacked layout
    function fit() {
        const box = document.querySelector('#oc .container-fluid'); if (!box) return;
        box.style.zoom = '';
        const w = box.clientWidth, REF = 1240;
        if (window.innerWidth > 640 && w < REF) box.style.zoom = (w / REF).toFixed(4);
    }
    window.addEventListener('resize', fit);

    // ---------- tf-control-r3: the other tabs — each reads what its desk has recorded; the work itself is done on that desk ----------
    const HS = { to_book: ['red', 'Not requested'], requested: ['amber', 'Awaiting confirmation'], confirmed: ['amber', 'Confirmed • voucher to issue'], voucher_issued: ['green', 'Voucher issued'], problem: ['red', 'Problem'], cancelled: ['grey', 'Cancelled'] };
    const TS = { to_allocate: ['red', 'To allocate'], allocated: ['amber', 'Allocated'], scheduled: ['amber', 'Scheduled'], pack_issued: ['green', 'Driver pack issued'], running: ['green', 'On tour'], returned: ['green', 'Returned'], settled: ['green', 'Settled'], cancelled: ['grey', 'Cancelled'] };
    const SS = { to_book: ['red', 'Not booked'], requested: ['amber', 'Awaiting confirmation'], confirmed: ['green', 'Confirmed'], not_needed: ['grey', 'Not needed'], pay_at_gate: ['green', 'Pay at the gate'], problem: ['red', 'Problem'], cancelled: ['grey', 'Cancelled'] };
    const sp = (map, k) => { const x = map[k] || ['grey', k || '—']; return `<span class="st-pill ${x[0]}">${esc(x[1])}</span>`; };
    const when = d => { const m = /^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}:\d{2}))?/.exec(d || ''); return m ? `${Number(m[3])} ${MON[Number(m[2]) - 1]} ${m[1]}${m[4] ? ' ' + m[4] : ''}` : ''; };
    function morePanes(c, it, L) {
        const H = c.hotels || [], T = c.transport || [], S = (c.services || []), s = c.strip || {};
        const head = (icon, title, btns) => `<div class="pr-head"><h3><i class="bi ${icon}"></i> ${title}</h3>${btns || ''}</div>`;
        const stats = a => `<div class="pr-stats">${a.map(x => `<span><b>${x[0]}</b> ${x[1]}</span>`).join('')}</div>`;
        const table = (cols, rows, empty) => `<div class="oc-box"><table class="ow"><thead><tr>${cols.map(x => `<th>${x}</th>`).join('')}</tr></thead><tbody>${rows.length ? rows.join('') : `<tr><td colspan="${cols.length}">${empty}</td></tr>`}</tbody></table></div>`;
        const n = (rows, k) => rows.filter(r => r.status === k).length;
        const names = x => ((x || {}).names || []).filter(Boolean).join(', ');
        // Hotels
        const hotels = head('oci oci-bed', 'Hotels', L.btn(L.toHotel, 'Open Hotel Bookings', 'bi-box-arrow-up-right'))
            + stats([[H.length, 'hotel stays'], [n(H, 'requested'), 'awaiting confirmation'], [n(H, 'confirmed') + n(H, 'voucher_issued'), 'confirmed'], [n(H, 'voucher_issued'), 'vouchers issued'], [n(H, 'problem'), 'with a problem']])
            + table(['Hotel', 'Check-in', 'Check-out', 'Nights', 'Rooms', 'Meal plan', 'Confirmation ref', 'Voucher', 'Status'],
                H.map(h => `<tr><td><b>${esc(h.hotel_name || '—')}</b></td><td>${dm(h.check_in)}</td><td>${dm(h.check_out)}</td><td>${esc(h.nights ?? '—')}</td><td>${esc(h.rooms_note || '—')}</td><td>${esc(h.meal_plan || '—')}</td>
                    <td>${esc(h.confirmation_ref || 'Not recorded')}</td><td>${esc(h.voucher_no || 'Not issued')}</td><td>${sp(HS, h.status)}${h.problem_note ? `<div style="font-size:12px">${esc(h.problem_note)}</div>` : ''}</td></tr>`), 'No hotel stay on this tour.');
        // Transport & Guides
        const trans = head('oci oci-car', 'Transport &amp; Guides', L.btn(L.toTrans, 'Open Transport', 'bi-box-arrow-up-right'))
            + stats([[T.length, 'vehicle lines'], [names(s.driver) || 'Not assigned', 'driver'], [(s.guide || {}).state === 'not_required' ? 'Not required' : (names(s.guide) || 'Not assigned'), 'guide']])
            + table(['Vehicle type', 'Qty', 'Seats', 'Days', 'Kilometres', 'Vehicle number', 'Status'],
                T.map(t => `<tr><td><b>${esc(t.vehicle_type || '—')}</b></td><td>${esc(t.qty ?? '—')}</td><td>${esc(t.seats ?? '—')}</td><td>${esc(t.days ?? '—')}</td><td>${t.km_total != null ? Number(t.km_total).toLocaleString() + ' km' : '—'}</td><td>${esc(t.vehicle_reg || 'Not assigned')}</td><td>${sp(TS, t.status)}</td></tr>`),
                'No vehicle is on this tour yet. It is added on the Transport panel.');
        // Meals, and Activities & Entrances — the service lines of the tour
        const live = S.filter(r => r.status !== 'cancelled');
        const svcPane = (icon, title, rows, what, empty) => head(icon, title, L.btn(L.toHotel, 'Open Hotel Bookings', 'bi-box-arrow-up-right'))
            + stats([[rows.length, what], [n(rows, 'to_book'), 'not booked'], [n(rows, 'requested'), 'awaiting confirmation'], [rows.filter(r => ['confirmed', 'not_needed', 'pay_at_gate'].includes(r.status)).length, 'done']])
            + table(['Day', 'Date', 'Time', 'Service', 'Pax', 'Confirmation ref', 'Status'],
                rows.map(r => `<tr><td>${r.day_no ? 'Day ' + esc(r.day_no) : '—'}</td><td>${dm(r.on_date) || '—'}</td><td>${esc(String(r.on_time || '').slice(0, 5) || '—')}</td><td><b>${esc(r.title || '—')}</b></td><td>${esc(r.pax ?? '—')}</td><td>${esc(r.confirmation_ref || 'Not recorded')}</td><td>${sp(SS, r.status)}</td></tr>`), empty);
        const meals = svcPane('oci oci-meal', 'Meals', live.filter(r => MEALS.includes(String(r.kind))), 'meals to arrange', 'No meal outside the hotels to arrange on this tour.');
        const acts = svcPane('bi-ticket-perforated oc-tilt', 'Activities &amp; Entrances', live.filter(r => !MEALS.includes(String(r.kind))), 'activities and entrances', 'No activity or entrance to arrange on this tour.');
        // Itinerary: the days as the inquiry holds them
        const txt = ((it.itinerary || {}).days) || {};
        const days = (it.days || []).slice().sort((a, b) => Number(a.day_no) - Number(b.day_no));
        const dayRows = days.map(d => `<tr><td style="white-space:nowrap"><b>Day ${esc(d.day_no)}</b></td><td style="white-space:nowrap">${dm(d.day_date) || '—'}</td><td>${esc((txt[d.day_no] || {}).text || '—')}</td><td>${esc(d.hotel_name_raw || '—')}${d.meal_plan ? ' • ' + esc(d.meal_plan) : ''}</td></tr>`);
        const last = Object.keys(txt).map(Number).filter(k => !days.some(d => Number(d.day_no) === k)).sort((a, b) => a - b);
        last.forEach(k => dayRows.push(`<tr><td style="white-space:nowrap"><b>Day ${k}</b></td><td style="white-space:nowrap">${k === days.length + 1 ? dm(it.departure_date) : '—'}</td><td>${esc((txt[k] || {}).text || '—')}</td><td>Departure</td></tr>`));
        const itin = head('bi-map', 'Itinerary', L.btn(L.quote, 'Client itinerary', 'bi-eye') + L.btn(L.quote ? L.quote + '&pdf=1' : null, 'PDF', 'bi-file-earmark-pdf') + L.btn(L.prog, 'Driver / guide itinerary', 'bi-eye', 'line'))
            + stats([[days.length, 'nights'], [dayRows.length, 'days'], [esc(L.ver), 'accepted by the customer']])
            + table(['Day', 'Date', 'Programme', 'Overnight'], dayRows, 'No day is on this tour.');
        // Documents & History
        const E = c.events || [];
        const LANE = { tour: 'Tour', hotel: 'Hotels', transport: 'Transport', service: 'Activities' };
        const docs = head('bi-folder2-open', 'Documents &amp; History', L.btn(L.toDocs, 'Communications &amp; History', 'bi-chat-left-text'))
            + `<div class="oc-two"><div class="oc-box"><div class="h2"><i class="bi bi-file-text"></i> Documents</div>
                <div class="oc-row"><i class="bi bi-file-text lead"></i><span class="grow">Client itinerary • ${esc(L.ver)}</span>${L.btn(L.quote, 'Preview', 'bi-eye', 'line')}${L.btn(L.quote ? L.quote + '&pdf=1' : null, 'PDF', 'bi-file-earmark-pdf', 'line')}</div>
                <div class="oc-row"><i class="bi bi-file-text lead"></i><span class="grow">Driver / guide itinerary</span>${L.btn(L.prog, 'Preview', 'bi-eye', 'line')}</div>
                ${H.map(h => `<div class="oc-row"><i class="bi oci oci-bed lead"></i><span class="grow">Hotel voucher • ${esc(h.hotel_name || '')}</span><span class="ver">${esc(h.voucher_no || 'Not issued')}</span>${L.btn(L.toHotel, 'Open', 'bi-box-arrow-up-right', 'line')}</div>`).join('')}</div>
              <div class="oc-box"><div class="h2"><i class="bi bi-clock-history"></i> History (${E.length})</div>
                ${E.length ? E.map(e => `<div class="oc-row"><span style="white-space:nowrap;font-size:12px;min-width:118px">${when(e.at)}</span><span class="ver">${esc(LANE[e.lane] || e.lane || '')}</span><span class="grow">${esc(e.event || '')}${e.to_status ? ' → ' + esc(String(e.to_status).replace(/_/g, ' ')) : ''}${e.note ? ' • ' + esc(e.note) : ''}</span><span style="font-size:12px">${esc(e.by || '')}</span></div>`).join('') : '<div class="tp-empty">Nothing recorded yet.</div>'}</div></div>`;
        return { hotels, trans, meals, acts, itin, docs };
    }

    let PICK = null;
    const WORDS = { SGL: 'SINGLE', DBL: 'DOUBLE', TWN: 'TWIN', TPL: 'TRIPLE', QUAD: 'QUAD' };
    // Passengers & Rooming: the names on the inquiry, placed room by room exactly as the hotel's rooming list places them
    function paxPane(c, it, L) {
        const stays = c.rooming || [], st = stays[Math.min(STAY, Math.max(stays.length - 1, 0))] || null;
        const names = (it.passengers || []).map(p => String(p.full_name || '').trim()).filter(Boolean);
        const expected = Number(it.pax_total || 0), missing = Math.max(expected - names.length, 0);
        const rooms = st ? st.rooms : [], un = st ? st.unallocated : names;
        const assigned = rooms.reduce((n, r) => n + r.guests.length, 0);
        const beds = rooms.reduce((n, r) => n + Number(r.beds || 0), 0);
        const mix = {}; rooms.forEach(r => { mix[r.code] = (mix[r.code] || 0) + 1; });
        const req = rooms.length ? Object.keys(mix).map(k => `${mix[k]} ${WORDS[k] || k} room${mix[k] === 1 ? '' : 's'}`).join(' + ') + ` • ${expected || beds} guest${(expected || beds) === 1 ? '' : 's'}` : 'No room mix recorded';
        const g = n => n ? `<span data-prpax="${esc(n)}" style="cursor:pointer"><i class="bi bi-person" style="color:#8b1d40;margin-right:8px"></i>${esc(n)}</span>` : '<span>—</span>';
        const rows = rooms.map((r, i) => { const free = Number(r.beds || 0) - r.guests.length;
            return `<tr><td><b>R${String(i + 1).padStart(2, '0')}</b></td><td>${WORDS[r.code] || esc(r.code)}</td><td>${g(r.guests[0])}</td><td>${g(r.guests[1])}${r.guests.length > 2 ? '<br>' + r.guests.slice(2).map(g).join('<br>') : ''}</td>
                <td>${free <= 0 ? '<span class="ok-pill"><i class="bi bi-check-lg"></i> Assigned</span>' : `<span class="warn-pill">${free} bed${free === 1 ? '' : 's'} free</span>`}</td></tr>`; }).join('');
        const unRows = un.map(n => `<div class="oc-row" data-prpax="${esc(n)}" style="cursor:pointer"><i class="bi bi-person"></i><span>${esc(n)}</span></div>`).join('')
            + Array.from({ length: missing }, (x, i) => `<div class="oc-row"><i class="bi bi-person"></i><span>Passenger ${names.length + i + 1}</span><span class="warn-pill">Name missing</span></div>`).join('');
        const pick = (it.passengers || []).find(p => String(p.full_name || '').trim() === PICK) || (it.passengers || [])[0] || null;
        const nr = '<select disabled><option>Not recorded</option></select>';
        const ready = !missing && !un.length && rooms.length;
        return `
            <div class="pr-head"><h3><i class="bi bi-people"></i> Passengers &amp; Rooming</h3>
                ${L.btn(null, 'Import passenger list', 'bi-upload')}${L.btn(L.toInq + '&tab=pax', 'Add passenger', 'bi-plus-lg')}${L.btn(L.toHotel, 'Export rooming list', 'bi-download', 'line')}</div>
            <div class="pr-stats"><span><i class="bi bi-people" style="color:#8b1d40;margin-right:14px"></i><b>${expected || '—'}</b> expected</span><span><b>${names.length}</b> names entered</span>
                <span class="${missing ? 'miss' : ''}"><b>${missing}</b> names missing</span><span><b>${assigned}</b> assigned • <b>${un.length + missing}</b> unassigned</span></div>
            <div class="pr-stay"><span><i class="bi oci oci-bed" style="color:#8b1d40;margin-right:8px"></i>Hotel stay</span>
                <select id="pr_stay">${stays.length ? stays.map((s, i) => `<option value="${i}"${s === st ? ' selected' : ''}>${esc(s.hotel || 'Hotel')} • ${dm(s.check_in)} – ${dm(s.check_out)}</option>`).join('') : '<option>No hotel stay on this tour</option>'}</select>
                <span style="border-left:1px solid #d9d3dd;padding-left:16px"><i class="bi bi-door-closed" style="color:#8b1d40;margin-right:8px"></i>Rooming requirement</span><span class="req">${esc(req)}</span></div>
            <div class="pr-grid">
                <div class="oc-box"><table class="ow"><thead><tr><th>Room group <i class="bi bi-info-circle" title="Room groups are this list's own references; the hotel gives the room numbers"></i></th><th>Type</th><th>Guest 1</th><th>Guest 2</th><th>Status</th></tr></thead>
                    <tbody>${rows || '<tr><td colspan="5">No rooms to show for this stay.</td></tr>'}</tbody></table></div>
                <div class="oc-box pr-un"><div class="h2"><i class="bi bi-people"></i> Unassigned passengers (${un.length + missing})</div>${unRows || '<div class="oc-row">Everybody has a room.</div>'}</div>
            </div>
            <div class="pr-note"><span>Showing ${rooms.length} of ${rooms.length} room groups</span><span><i class="bi bi-info-circle"></i> <i style="color:#8b1d40">Room groups are internal references; hotel room numbers pending</i></span></div>
            <div class="pr-det"><div class="ttl"><i class="bi bi-person"></i> Passenger detail</div>
                <div class="f" style="flex:2 1 220px"><label>Name as passport</label><input disabled value="${esc(pick ? pick.full_name : 'No passenger entered')}"></div>
                <div class="f"><label>Adult / Child</label>${nr}</div><div class="f"><label>Nationality</label>${nr}</div><div class="f"><label>Dietary needs</label>${nr}</div>
                <div class="f"><label>Special assistance</label>${nr}</div><div class="f"><label>Flight group</label>${nr}</div></div>
            <div class="oc-two">
                <div class="oc-box"><div class="h2"><i class="bi bi-file-text"></i> Itinerary documents</div>
                    <div class="oc-row"><i class="bi bi-file-text lead"></i><span class="grow">Client itinerary</span><span class="ver">${esc(L.ver)}${c.tour.quote_version ? ' • Issued' : ''}</span>${L.btn(L.quote, 'Preview', 'bi-eye', 'line')}${L.btn(L.quote ? L.quote + '&pdf=1' : null, 'Export', 'bi-download', 'line')}</div>
                    <div class="oc-row"><i class="bi bi-file-text lead"></i><span class="grow">Guide / driver itinerary</span><span class="ver">${esc(L.ver)}</span>${L.btn(L.prog, 'Preview', 'bi-eye', 'line')}${L.btn(null, 'Export', 'bi-download', 'line')}</div></div>
                <div class="oc-box"><div class="h2"><i class="bi bi-check-circle"></i> Rooming validation</div>
                    <div style="padding:8px 14px 12px 14px"><div style="margin-bottom:8px">${ready ? 'Every name is entered and everybody has a room.' : 'Resolve missing names and unassigned guests before final rooming list.'}</div>
                    ${L.btn(L.toHotel, 'Preview hotel rooming list', 'oci oci-bed', 'lg')}</div></div>
            </div>
            <div class="oc-foot"><span><i class="bi bi-info-circle" style="color:#8b1d40;margin-right:8px"></i><i>Assign rooms per hotel stay • Keep amendment history</i></span></div>`;
    }

    // ---------- tf-supdesk-r1 (19 Nov 2026, Shantha): Supplier Emails & Vouchers — one entry per hotel reservation and per booked
    // service provider. Confirming the tour prepares the messages; nothing goes until a person has read the address, the e-mail and
    // the voucher and presses Send Email on that entry. "E-mail sent" and "booking confirmed by the supplier" are shown separately.
    let SM = null, SM_OPEN = null, SM_MSG = {};
    const SM_URL = `${PAGE}/${TOUR_ID}/supplier_mail`;
    async function loadMail() { try { const j = await get(SM_URL); SM = j.status === 200 ? j.data : { error: j.message || 'Could not be read.' }; } catch (e) { SM = { error: e.message }; } drawMail(); }
    function drawMail() {
        const box = document.getElementById('sm_box'); if (!box) return;
        if (!SM) { box.innerHTML = '<div class="oc-msg">Reading the supplier e-mails…</div>'; return; }
        if (SM.error) { box.innerHTML = `<div class="oc-msg">${esc(SM.error)}</div>`; return; }
        const E = SM.entries || [], k = e => e.type + ':' + e.id;
        const n = s => E.filter(e => e.state === s).length, held = E.filter(e => (e.flags || []).some(f => f.stop)).length;
        const pillOf = e => e.state === 'sent' ? (e.last_failed ? '<span class="st-pill red">Last attempt failed</span>' : `<span class="st-pill green">E-mail sent${e.times_sent > 1 ? ' ×' + e.times_sent : ''}</span>`)
            : (e.state === 'failed' ? '<span class="st-pill red">Send failed</span>' : '<span class="st-pill amber">Not sent</span>');
        const bk = e => !e.booking ? '<span class="sm-mut">Recorded on the Transport desk</span>'
            : `<span class="st-pill ${/^confirmed|voucher_issued|done$/.test(e.booking_state) ? 'green' : (e.booking_state === 'problem' || e.booking_state === 'to_book' ? 'red' : 'amber')}">${esc(e.booking)}</span>`;
        const rows = E.map(e => {
            const stop = (e.flags || []).filter(f => f.stop), key = k(e), open = SM_OPEN === key, sentOk = e.state === 'sent' && !e.last_failed;
            const pv = `${SM_URL}/preview/${e.type}/${e.id}`;
            const acts = !e.can ? '<span class="sm-mut">Your sign-in does not hold this desk</span>' :
                `<a class="sm-b" href="${pv}" target="_blank"><i class="bi bi-envelope-open"></i> Preview Email</a>`
                + (e.vouchers || []).map(v => `<a class="sm-b" href="${SM_URL}/voucher/${e.type}/${e.id}/${v.key}" target="_blank" title="${esc(v.label)}"><i class="bi bi-file-earmark-pdf"></i> ${(e.vouchers || []).length > 1 ? esc(v.label) : 'View Voucher'}</a>`).join('')
                + (stop.length || !e.mailbox ? `<button class="sm-b go" disabled title="${esc(stop.length ? stop[0].text : 'No mailbox is set up to send from')}"><i class="bi bi-send"></i> Send Email</button>`
                    : `<button class="sm-b go${sentOk ? ' again' : ''}" data-smopen="${key}"><i class="bi bi-send"></i> ${sentOk ? 'Send again' : (e.state === 'failed' || e.last_failed ? 'Retry Send' : 'Send Email')}</button>`);
            const hist = (e.history || []).map(h => `<div class="sm-h"><span>${when(h.at)}</span><span class="${h.result === 'sent' ? 'ok' : 'bad'}">${h.result === 'sent' ? 'Sent' : 'Failed'}${h.action === 'resend' ? ' • deliberate resend' : (h.action === 'retry' ? ' • retry' : '')}</span>
                <span>to ${esc(h.to_addr || '—')} • ${esc(h.by_name || '')}${h.reason ? ' • reason: ' + esc(h.reason) : ''}${h.error ? ' • ' + esc(h.error) : ''}</span>
                <a href="${SM_URL}/sent/${h.id}" target="_blank">View sent e-mail${(h.attachments || []).length ? ' + ' + h.attachments.length + ' attachment' + (h.attachments.length === 1 ? '' : 's') : ''}</a></div>`).join('')
                + (e.earlier ? `<div class="sm-h"><span>${when(e.earlier.at)}</span><span>${esc(e.earlier.state)} earlier from the desk (${esc(e.earlier.channel || '')})</span><span>${e.earlier.to ? 'to ' + esc(e.earlier.to) + ' • ' : ''}${esc(e.earlier.by || '')}</span><span></span></div>` : '');
            const confirm = !open ? '' : `<div class="sm-c"><b>${sentOk ? 'Send this e-mail AGAIN' : 'Send this e-mail'} to ${esc(e.provider)}</b>
                <div>Recipient: <b>${esc(e.recipient)}</b> <span class="sm-mut">(${esc(e.recipient_source || 'master')})</span></div>
                ${SM.redirect ? `<div class="bad" style="color:#8a5a00">TEST MODE: this system is still being developed. The e-mail will go to <b>${esc(SM.redirect)}</b> only \u2014 not to the supplier.</div>` : ''}
                <label><input type="checkbox" id="sm_ck"> I have checked the recipient address, the e-mail content and the voucher.</label>
                ${sentOk ? '<input type="text" id="sm_why" maxlength="300" placeholder="Why is it sent again? (kept with the resend)">' : ''}
                <div><button class="sm-b go" id="sm_go" disabled data-type="${e.type}" data-id="${e.id}" data-resend="${sentOk ? 1 : 0}"><i class="bi bi-send"></i> Send now</button> <button class="sm-b" id="sm_x">Cancel</button></div></div>`;
            return `<tr><td><b>${esc(e.provider)}</b><small>${esc(e.group)}</small></td><td>${esc(e.service)}</td>
                <td>${e.recipient ? `${esc(e.recipient)}<small>${esc(e.recipient_source || '')}</small>` : '<span class="bad">No address</span>'}</td>
                <td>${pillOf(e)}${e.sent ? `<small>${when(e.sent.at)} • ${esc(e.sent.by_name || '')}</small>` : ''}</td><td>${bk(e)}</td><td class="sm-a">${acts}</td></tr>`
                + ((e.flags || []).length || hist || confirm || SM_MSG[key] ? `<tr class="sm-sub"><td colspan="6">${(e.flags || []).map(f => `<div class="${f.stop ? 'bad' : 'warn'}"><i class="bi bi-exclamation-triangle-fill"></i> ${esc(f.text)}</div>`).join('')}
                    ${SM_MSG[key] ? `<div class="${SM_MSG[key].ok ? 'ok' : 'bad'}">${esc(SM_MSG[key].text)}</div>` : ''}${confirm}${hist}</td></tr>` : '');
        }).join('');
        box.innerHTML = `<div class="pr-head"><h3><i class="bi bi-envelope-paper"></i> Supplier Emails &amp; Vouchers</h3></div>
            <div class="pr-stats"><span><b>${E.length}</b> reservations</span><span><b>${n('sent')}</b> e-mail sent</span><span><b>${n('not_sent')}</b> not sent</span><span class="${n('failed') ? 'miss' : ''}"><b>${n('failed')}</b> failed</span><span class="${held ? 'miss' : ''}"><b>${held}</b> held — details missing</span></div>
            <div class="oc-box"><table class="ow sm-t"><thead><tr><th>Provider</th><th>Booked service</th><th>Recipient e-mail</th><th>E-mail status</th><th>Supplier booking</th><th>Check, then send</th></tr></thead>
                <tbody>${rows || '<tr><td colspan="6">No hotel reservation or booked service provider is on this tour yet.</td></tr>'}</tbody></table></div>
            <div class="oc-foot"><span><i class="bi bi-info-circle" style="color:#8b1d40;margin-right:8px"></i><i>Nothing is sent automatically. Read the address, the e-mail and the voucher, then press Send Email for that reservation. An e-mail sent is not a booking confirmed.</i>${SM.redirect ? ` <b style="color:#8a5a00">\u2022 TEST MODE: every e-mail from this system goes to ${esc(SM.redirect)} only, never to a supplier.</b>` : ''}${SM.sender_note ? ' • ' + esc(SM.sender_note) : ''}</span></div>`;
        box.querySelectorAll('[data-smopen]').forEach(b => b.addEventListener('click', () => { SM_OPEN = SM_OPEN === b.dataset.smopen ? null : b.dataset.smopen; drawMail(); }));
        const go = document.getElementById('sm_go'), ck = document.getElementById('sm_ck'), why = document.getElementById('sm_why'), x = document.getElementById('sm_x');
        if (x) x.addEventListener('click', () => { SM_OPEN = null; drawMail(); });
        if (go && ck) {
            const gate = () => { go.disabled = !(ck.checked && (!why || why.value.trim() !== '')); };
            ck.addEventListener('change', gate); if (why) why.addEventListener('input', gate);
            go.addEventListener('click', async () => {
                if (go.disabled || go.dataset.busy) return; go.dataset.busy = '1'; go.disabled = true; go.innerHTML = 'Sending…';
                const key = go.dataset.type + ':' + go.dataset.id; let j = null;
                try { j = await (await fetch(SM_URL + '/send', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ type: go.dataset.type, id: Number(go.dataset.id), resend: go.dataset.resend === '1', reason: why ? why.value.trim() : '' }) })).json(); } catch (e) { j = null; }
                SM_MSG[key] = { ok: !!(j && j.status === 200), text: (j && j.message) || 'It was not sent. Nothing was changed.' }; SM_OPEN = null; await loadMail();
            });
        }
    }

    async function load() {
        const box = document.getElementById('oc_body');
        try {
            const c = await get(`${PAGE}/${TOUR_ID}/control/data`);
            if (c.status !== 200) { box.innerHTML = `<div class="oc-msg">${esc(c.message || 'This tour could not be read.')}</div>`; return; }
            const i = await get(`${INTAKE}/intake/${c.data.tour.intake_id}`);
            draw(c.data, (i && i.data) || {});
            if (!SM_OPEN) loadMail();
        } catch (e) { box.innerHTML = `<div class="oc-msg">This tour could not be read: ${esc(e.message)}</div>`; }
    }
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') load(); });
    load();
})();
</script>
</body>
</html>
