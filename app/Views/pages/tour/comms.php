<?= view('layouts/head') ?>
<style>
    /* ---------- Communications & History (tf-comms-r1, 27 Oct 2026) — same tokens as the Tour Inquiry page ---------- */
    .tfc { --tf-navy: #1e3553; --tf-navy-2: #2c4a73; --tf-purple: #842484; --tf-purple-soft: #f3e7f3; --tf-green: #1f8a4c; --tf-green-soft: #e4f4ea; --tf-avgreen: #78b848; --tf-amber: #b7791f; --tf-amber-soft: #fdf3e1; --tf-red: #b23a32; --tf-red-soft: #fbe9e7; --tf-blue-soft: #e6eef8; --tf-ink: #1d2733; --tf-muted: #5f6f80; --tf-line: #dfe5ec; --tf-bg: #f4f6f9; --tf-card: #fff; --tf-radius: 10px; color: var(--tf-ink); }
    .tfc .tf-card { background: var(--tf-card); border: 1px solid var(--tf-line); border-radius: var(--tf-radius); box-shadow: 0 1px 2px rgba(16,24,40,.04); margin-bottom: 14px; }
    .tfc .tf-card-h { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid var(--tf-line); }
    .tfc .tf-card-h h6 { margin: 0; font-weight: 600; color: var(--tf-navy); font-size: 14px; }
    .tfc .tf-card-b { padding: 12px 14px; }
    .tfc .title { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 12px; }
    .tfc .title h4 { margin: 0; color: var(--tf-navy); font-weight: 600; }
    .tfc .title .sub { color: var(--tf-muted); font-size: 12.5px; }
    .tfc .toolbar { margin-left: auto; display: flex; flex-wrap: wrap; gap: 6px; }
    .tfc .btn { border-radius: 8px; font-size: 12.5px; padding: 6px 12px; font-weight: 500; white-space: nowrap; }
    .tfc .btn-sm { padding: 4px 9px; font-size: 12px; }
    .tfc .btn-primary { background: var(--tf-purple); border-color: var(--tf-purple); color: #fff; }
    .tfc .btn-primary:hover { background: #6a1d6a; border-color: #6a1d6a; }
    .tfc .btn-avgreen { background: var(--tf-avgreen); border-color: var(--tf-avgreen); color: #fff; }
    .tfc .btn-light { background: #fff; border: 1px solid var(--tf-line); color: var(--tf-ink); }
    .tfc .help { font-size: 12px; color: var(--tf-muted); }
    .tfc .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; line-height: 1.6; white-space: nowrap; }
    .tfc .pill-purple { background: var(--tf-purple-soft); color: var(--tf-purple); } .tfc .pill-green { background: var(--tf-green-soft); color: var(--tf-green); } .tfc .pill-amber { background: var(--tf-amber-soft); color: var(--tf-amber); } .tfc .pill-red { background: var(--tf-red-soft); color: var(--tf-red); } .tfc .pill-blue { background: var(--tf-blue-soft); color: var(--tf-navy); } .tfc .pill-grey { background: #eef1f5; color: var(--tf-muted); }
    .tfc .form-label { font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; color: var(--tf-muted); margin-bottom: 2px; }
    .tfc .form-control, .tfc .form-select { font-size: 12.5px; border-radius: 8px; }
    .tfc .filters { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .tfc .filters .chip { border: 1px solid var(--tf-line); background: #fff; border-radius: 999px; padding: 3px 10px; font-size: 12px; color: var(--tf-muted); cursor: pointer; }
    .tfc .filters .chip.on { background: var(--tf-purple-soft); color: var(--tf-purple); border-color: var(--tf-purple-soft); font-weight: 600; }
    /* the timeline */
    .tfc .tl { position: relative; margin: 0; padding: 0; list-style: none; }
    .tfc .tl li { position: relative; padding: 10px 12px 10px 44px; border-bottom: 1px solid #eef1f5; }
    .tfc .tl li:last-child { border-bottom: 0; }
    .tfc .tl .ico { position: absolute; left: 10px; top: 12px; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; background: #eef1f5; color: var(--tf-muted); }
    .tfc .tl .in .ico { background: var(--tf-blue-soft); color: var(--tf-navy); } .tfc .tl .out .ico { background: var(--tf-green-soft); color: var(--tf-green); } .tfc .tl .internal .ico { background: var(--tf-amber-soft); color: var(--tf-amber); } .tfc .tl .sys .ico { background: var(--tf-purple-soft); color: var(--tf-purple); } .tfc .tl .change .ico { background: var(--tf-red-soft); color: var(--tf-red); }
    .tfc .tl .when { font-family: Consolas, Menlo, monospace; font-size: 11.5px; color: var(--tf-muted); }
    .tfc .tl .head { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .tfc .tl .subj { font-weight: 600; color: var(--tf-ink); }
    .tfc .tl .addr { font-size: 12px; color: var(--tf-muted); }
    .tfc .tl .body { font-size: 12.5px; white-space: pre-wrap; margin-top: 4px; max-height: 120px; overflow: hidden; position: relative; }
    .tfc .tl .body.open { max-height: none; }
    .tfc .tl .body:not(.open)::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 28px; background: linear-gradient(transparent, #fff); }
    .tfc .tl .more { font-size: 12px; color: var(--tf-purple); cursor: pointer; }
    .tfc .tl .att a { display: inline-block; font-size: 12px; border: 1px solid var(--tf-line); border-radius: 6px; padding: 2px 8px; margin: 4px 4px 0 0; color: var(--tf-navy); text-decoration: none; }
    .tfc .tl .meta { font-size: 11.5px; color: var(--tf-muted); margin-top: 4px; display: flex; flex-wrap: wrap; gap: 10px; }
    .tfc .tl .acts { margin-left: auto; display: flex; gap: 4px; }
    .tfc .tl .acts button { border: 0; background: none; color: var(--tf-muted); font-size: 12px; padding: 0 4px; }
    .tfc .tl .acts button:hover { color: var(--tf-purple); }
    .tfc .tl table.chg { font-size: 11.5px; margin-top: 4px; border-collapse: collapse; } .tfc .tl table.chg td { padding: 1px 8px 1px 0; color: var(--tf-muted); } .tfc .tl table.chg td:first-child { color: var(--tf-ink); }
    .tfc .sum h6 { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--tf-muted); margin: 10px 0 4px; }
    .tfc .sum h6:first-child { margin-top: 0; }
    .tfc .sum .line { font-size: 12.5px; padding: 4px 0; border-bottom: 1px dashed #eef1f5; }
    .tfc .sum .line:last-child { border-bottom: 0; }
    .tfc .fu { display: flex; gap: 8px; align-items: flex-start; padding: 6px 0; border-bottom: 1px dashed #eef1f5; font-size: 12.5px; }
    .tfc .fu.overdue .due { color: var(--tf-red); font-weight: 600; }
    .tfc .fu.done { opacity: .6; text-decoration: line-through; }
    .tfc .fu .due { font-family: Consolas, Menlo, monospace; font-size: 11.5px; white-space: nowrap; }
    .tfc .empty { padding: 22px 14px; text-align: center; color: var(--tf-muted); }
    .tfc table.res { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .tfc table.res th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--tf-muted); padding: 8px 10px; border-bottom: 1px solid var(--tf-line); }
    .tfc table.res td { padding: 8px 10px; border-bottom: 1px solid #eef1f5; vertical-align: middle; }
    .tfc table.res tr:hover td { background: #fbfcfd; }
    .tfc .capture { background: var(--tf-purple-soft); border-radius: 8px; padding: 8px 12px; font-size: 12px; color: var(--tf-ink); }
    .tfc .capture b { color: var(--tf-purple); }
    .tfc .modal-content { border-radius: 12px; }
    .tfc .not-installed { padding: 14px 16px; border-radius: 8px; background: var(--tf-amber-soft); color: var(--tf-amber); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tfc" id="tfc">
            <div class="container-fluid">
                <?= view('layouts/tourdesk_bar') ?>

                <?php if (empty($installed)): ?>
                <div class="tf-card"><div class="tf-card-b not-installed"><b><i class="bi bi-exclamation-triangle"></i> Communications & History is not installed yet.</b> <span class="help">Open Tour Inquiry as System Administrator and press Install now (schema 45).</span></div></div>
                <?php elseif (empty($intake_id)): ?>

                <div class="title">
                    <div><h4>Communications & History — search</h4><div class="sub">Find any inquiry or tour by number, client / tour name, agent, travel dates or the text of a message, and see what is still open.</div></div>
                    <div class="toolbar"><a class="btn btn-light btn-sm" href="<?= base_url(($comms_url ?? 'tour/intake/comms') . '/inbox') ?>" id="inbox_link"><i class="bi bi-envelope-open"></i> Incoming e-mails <span class="pill pill-amber" id="inbox_count" style="display:none"></span></a><a class="btn btn-light btn-sm" href="<?= base_url($intake_url ?? 'tour/intake') ?>"><i class="bi bi-inbox"></i> Tour Inquiry</a><a class="btn btn-light btn-sm" href="<?= base_url($board_url ?? 'tour/board') ?>"><i class="bi bi-kanban"></i> Tour Board</a></div>
                </div>
                <div class="tf-card"><div class="tf-card-b">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4"><label class="form-label">Search</label><input class="form-control form-control-sm" id="s_q" placeholder="INQ 1037, AH 2252, Tsukamoto, Lion Royal, words from a message…"></div>
                        <div class="col-md-2"><label class="form-label">Travelling from</label><input type="date" class="form-control form-control-sm" id="s_from"></div>
                        <div class="col-md-2"><label class="form-label">to</label><input type="date" class="form-control form-control-sm" id="s_to"></div>
                        <div class="col-md-2"><label class="form-label">Handling agent</label><input class="form-control form-control-sm" id="s_handler" placeholder="name"></div>
                        <div class="col-md-2 d-flex gap-2 align-items-center"><label class="help"><input type="checkbox" id="s_open"> open follow-ups only</label><button class="btn btn-primary btn-sm" id="s_go"><i class="bi bi-search"></i> Search</button></div>
                    </div>
                </div>
                <div style="overflow-x:auto"><table class="res"><thead><tr><th>Inquiry / tour</th><th>Client / tour</th><th>Agent</th><th>Travel dates</th><th>Pax</th><th>Handling agent</th><th>Status</th><th class="text-end">Messages</th><th>Last message</th><th class="text-end">Open follow-ups</th><th>Next due</th><th></th></tr></thead><tbody id="s_rows"><tr><td colspan="12" class="empty">Type something and press Search — or search with nothing to list the latest.</td></tr></tbody></table></div>
                </div>
                <div class="tf-card"><div class="tf-card-h"><h6>Follow-ups due</h6><label class="help ms-2"><input type="checkbox" id="d_mine" checked> mine only</label></div><div class="tf-card-b" id="d_rows"><div class="empty">Loading…</div></div></div>

                <?php else: ?>

                <div class="title" id="hdr">
                    <div><h4 id="h_title">Communications & History</h4><div class="sub" id="h_sub">Loading…</div></div>
                    <div class="toolbar" id="h_tools"></div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="tf-card" id="sum_card"><div class="tf-card-h"><h6>Latest agreed arrangements & what needs action</h6></div><div class="tf-card-b sum" id="sum"><div class="empty">Loading…</div></div></div>
                        <div class="tf-card" id="fu_card"><div class="tf-card-h"><h6>Follow-ups</h6><span class="help" id="fu_count"></span><button class="btn btn-light btn-sm ms-auto" id="fu_add"><i class="bi bi-plus-lg"></i> Add</button></div><div class="tf-card-b" id="fu_list"><div class="empty">Loading…</div></div></div>
                    </div>
                    <div class="col-lg-8">
                        <div class="tf-card" id="add_card"><div class="tf-card-h"><h6>Record a communication</h6><span class="help">e-mail, WhatsApp, call, meeting, internal or handover note</span><button class="btn btn-avgreen btn-sm ms-auto" id="compose_btn"><i class="bi bi-envelope-paper"></i> Compose &amp; send e-mail</button></div><div class="tf-card-b">
                            <div class="capture mb-2"><b>Captured by the system automatically:</b> every e-mail composed and sent with the green button (with its attachments and whether the outgoing mail server accepted it), replies that arrive in the TourFlow mailbox and carry a certain reference to this record, the inquiry e-mail as pasted, quotation e-mails sent from here (with the PDF), restaurant vouchers e-mailed from the Meal Master, hotel booking letters sent from the Hotel Desk and hotel replies typed there. <b>Recorded by hand:</b> every e-mail that arrives in a mailbox, WhatsApp / SMS, telephone calls, meetings, handover notes — paste the text or upload the .eml / screenshot below. Nothing recorded here changes the itinerary or approves a request: changes go through the Edit Request.</div>
                            <div class="row g-2">
                                <div class="col-md-3"><label class="form-label">Direction</label><select class="form-select form-select-sm" id="a_dir"><option value="in">Received (from agent / client / hotel)</option><option value="out">Sent by us</option><option value="internal">Internal (note / handover)</option></select></div>
                                <div class="col-md-3"><label class="form-label">Channel</label><select class="form-select form-select-sm" id="a_channel"><?php foreach (($channels ?? []) as $k => $v): if ($k === 'system') continue; ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-3"><label class="form-label">Marks it as</label><select class="form-select form-select-sm" id="a_tag"><?php foreach (($tags ?? []) as $k => $v): ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-3"><label class="form-label">When it happened</label><input type="datetime-local" class="form-control form-control-sm" id="a_when"></div>
                                <div class="col-md-4"><label class="form-label">From</label><input class="form-control form-control-sm" id="a_from" placeholder="name or e-mail / number"></div>
                                <div class="col-md-4"><label class="form-label">To</label><input class="form-control form-control-sm" id="a_to"></div>
                                <div class="col-md-4"><label class="form-label">Cc</label><input class="form-control form-control-sm" id="a_cc"></div>
                                <div class="col-12"><label class="form-label">Subject</label><input class="form-control form-control-sm" id="a_subject"></div>
                                <div class="col-12"><label class="form-label">Message — the full text as received or sent</label><textarea class="form-control" id="a_body" rows="6" placeholder="Paste the whole message here. For a call or meeting: who spoke, what was said, what was agreed."></textarea></div>
                                <div class="col-md-6"><label class="form-label">Refers to</label><select class="form-select form-select-sm" id="a_link"><option value="">— nothing in particular —</option></select></div>
                                <div class="col-md-6"><label class="form-label">Attachments (.eml, .pdf, screenshots, documents — up to 15 MB each)</label><input type="file" class="form-control form-control-sm" id="a_files" multiple></div>
                                <div class="col-12 help" id="a_reply_note" style="display:none"></div><input type="hidden" id="a_reply_to">
                            </div>
                            <div class="mt-2 d-flex gap-2 align-items-center"><button class="btn btn-avgreen btn-sm" id="a_save"><i class="bi bi-save"></i> Record</button><span class="help">Recorded by <b><?= esc($actor_name ?? $user ?? '') ?></b> — the record keeps who, when and every later correction.</span></div>
                        </div></div>

                        <div class="tf-card" id="tl_card"><div class="tf-card-h"><h6>The record</h6><span class="help" id="tl_count"></span>
                            <div class="filters ms-auto"><span class="chip on" data-f="all">Everything</span><span class="chip" data-f="corr">Correspondence</span><span class="chip" data-f="internal">Internal notes</span><span class="chip" data-f="changes">Changes, approvals & versions</span><span class="chip" data-f="system">System events</span><input class="form-control form-control-sm" id="tl_q" placeholder="find in record…" style="width:160px"></div>
                        </div><ul class="tl" id="tl"><li class="empty">Loading…</li></ul></div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- tf-comms-r2: compose and send an e-mail -->
<div class="modal fade tfc" id="m_mail" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="m_mail_title">Compose e-mail</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="capture mb-2" id="mail_sender">…</div>
        <div id="mail_reply_note" class="help mb-2" style="display:none"></div>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label">To</label><input class="form-control form-control-sm" id="mail_to" placeholder="address, address"></div>
            <div class="col-md-3"><label class="form-label">Cc</label><input class="form-control form-control-sm" id="mail_cc"></div>
            <div class="col-md-3"><label class="form-label">Bcc</label><input class="form-control form-control-sm" id="mail_bcc"></div>
            <div class="col-md-9"><label class="form-label">Subject</label><input class="form-control form-control-sm" id="mail_subject" maxlength="300"></div>
            <div class="col-md-3"><label class="form-label">Marks it as</label><select class="form-select form-select-sm" id="mail_tag"><?php foreach (($tags ?? []) as $k => $v): ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" id="mail_body" rows="10"></textarea>
                <div class="help">The standard signature is added under the message:</div><pre class="help mono" id="mail_sig" style="white-space:pre-wrap;background:#f7f5fa;padding:6px 10px;border-radius:8px;margin:0"></pre></div>
            <div class="col-md-7"><label class="form-label">Attach documents of this inquiry / tour</label><div id="mail_docs" style="max-height:180px;overflow:auto;border:1px solid var(--tf-line);border-radius:8px;padding:6px 10px"><span class="help">None yet.</span></div></div>
            <div class="col-md-5"><label class="form-label">Attach files from your computer (up to 15 MB each)</label><input type="file" class="form-control form-control-sm" id="mail_files" multiple>
                <label class="form-label mt-2">Refers to</label><select class="form-select form-select-sm" id="mail_link"><option value="">— nothing in particular —</option></select></div>
        </div>
        <input type="hidden" id="mail_reply_to">
    </div>
    <div class="modal-footer"><span class="help me-auto">Sent by <b><?= esc($actor_name ?? $user ?? '') ?></b> — the e-mail, its attachments and the server's answer are kept on this record.</span><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-avgreen" id="mail_send"><i class="bi bi-send"></i> Send e-mail</button></div>
</div></div></div>

<!-- follow-up modal -->
<div class="modal fade tfc" id="m_fu" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="m_fu_title">Follow-up</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="form-label">What has to be done</label><input class="form-control form-control-sm" id="fu_title">
        <label class="form-label mt-2">Detail</label><textarea class="form-control" id="fu_detail" rows="2"></textarea>
        <div class="row g-2 mt-1">
            <div class="col-7"><label class="form-label">Responsible</label><select class="form-select form-select-sm" id="fu_owner"><?php foreach (($staff ?? []) as $s): ?><option value="<?= (int) $s['id'] ?>" data-name="<?= esc($s['name']) ?>"><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-5"><label class="form-label">Due</label><input type="date" class="form-control form-control-sm" id="fu_due"></div>
        </div>
        <input type="hidden" id="fu_id"><input type="hidden" id="fu_comm">
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="fu_ok">Save</button></div>
</div></div></div>

<!-- correct a line modal -->
<div class="modal fade tfc" id="m_edit" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Correct this line</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2">The version as it stands is kept inside the record and the correction is logged with your reason.</div>
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label">When it happened</label><input type="datetime-local" class="form-control form-control-sm" id="e_when"></div>
            <div class="col-md-4"><label class="form-label">Marks it as</label><select class="form-select form-select-sm" id="e_tag"><?php foreach (($tags ?? []) as $k => $v): ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">From</label><input class="form-control form-control-sm" id="e_from"></div>
            <div class="col-12"><label class="form-label">Subject</label><input class="form-control form-control-sm" id="e_subject"></div>
            <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" id="e_body" rows="6"></textarea></div>
            <div class="col-12"><label class="form-label">Why is it corrected?</label><input class="form-control form-control-sm" id="e_why" placeholder="required"></div>
        </div>
        <input type="hidden" id="e_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="e_ok">Save correction</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($comms_url ?? 'tour/intake/comms') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const BOARD = '<?= base_url($board_url ?? 'tour/board') ?>';
    const ID = <?= (int) ($intake_id ?? 0) ?>;
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const ME = { id: <?= (int) ($user_id ?? 0) ?>, name: <?= json_encode((string) ($actor_name ?? $user ?? ''), JSON_UNESCAPED_UNICODE) ?> };
    const CH = <?= json_encode($channels ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const TAGS = <?= json_encode($tags ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const showModal = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el, { focus: false }) : new bootstrap.Modal(el, { focus: false })).show(); };
    const hideModal = sel => { const el = document.querySelector(sel); const inst = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null; if (inst) inst.hide(); };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    async function api(path, opts) { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); if (r.status === 401 || r.redirected) { location.reload(); throw new Error('Signed out'); } return r.json(); }
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const pill = (cls, t) => `<span class="pill pill-${cls}">${esc(t)}</span>`;
    const dt = s => s ? String(s).slice(0, 16).replace('T', ' ') : '';
    const d = s => s ? String(s).slice(0, 10) : '';
    const today = () => new Date().toISOString().slice(0, 10);
    const nowLocal = () => { const n = new Date(); n.setMinutes(n.getMinutes() - n.getTimezoneOffset()); return n.toISOString().slice(0, 16); };
    if (!INSTALLED) return;
    // tf-comms-r3: the quiet mailbox check — runs only when the last one is older than five minutes; the scheduled task does the rest
    (async () => { try { const j = await post('/inbox/auto', {}); if (j.ran && j.data && j.data.new > 0 && typeof load === 'function') load(); const c = document.getElementById('inbox_count'); if (c && j.data && j.data.unmatched) { c.textContent = j.data.unmatched + ' waiting'; c.style.display = ''; } } catch (e) {} })();

    // ================= search page =================
    if (!ID) {
        const rows = document.getElementById('s_rows');
        async function run() {
            const p = new URLSearchParams({ q: document.getElementById('s_q').value, from: document.getElementById('s_from').value, to: document.getElementById('s_to').value, handler: document.getElementById('s_handler').value, open_only: document.getElementById('s_open').checked ? 1 : '' });
            rows.innerHTML = '<tr><td colspan="12" class="empty">Searching…</td></tr>';
            const j = await api('/search?' + p.toString()); if (j.status !== 200) { rows.innerHTML = `<tr><td colspan="12" class="empty">${esc(j.message)}</td></tr>`; return; }
            rows.innerHTML = j.data.map(r => `<tr><td><b>${esc(r.inquiry_no)}</b>${r.tour_no ? `<div class="help">${esc(r.tour_no)}</div>` : ''}</td><td>${esc(r.tour_name || '')}</td><td>${esc(r.agent_name || '')}</td><td class="text-nowrap">${d(r.arrival_date)}${r.departure_date ? ' → ' + d(r.departure_date) : ''}</td><td>${esc(r.pax_total ?? '')}</td><td>${esc(r.handler_name || '—')}</td><td>${r.archived_at ? pill('grey', 'archived') : (r.stage ? pill(r.stage === 'cancelled' ? 'red' : 'green', r.stage) : pill('blue', r.status))}</td><td class="text-end">${r.comms}</td><td class="text-nowrap help">${dt(r.last_comm)}</td><td class="text-end">${Number(r.open_followups) ? pill(r.next_due && r.next_due < today() ? 'red' : 'amber', r.open_followups) : '0'}</td><td class="text-nowrap help">${d(r.next_due)}</td><td class="text-end"><a class="btn btn-primary btn-sm" href="${BASE}/${r.id}"><i class="bi bi-chat-left-text"></i> Open record</a></td></tr>`).join('') || '<tr><td colspan="12" class="empty">Nothing matches.</td></tr>';
        }
        document.getElementById('s_go').addEventListener('click', run);
        document.getElementById('s_q').addEventListener('keydown', e => { if (e.key === 'Enter') run(); });
        async function due() {
            const mine = document.getElementById('d_mine').checked; const box = document.getElementById('d_rows');
            const j = await api('/due' + (mine ? '?mine=1' : '')); if (j.status !== 200) { box.innerHTML = `<div class="empty">${esc(j.message)}</div>`; return; }
            box.innerHTML = j.data.length ? j.data.map(f => `<div class="fu ${f.due_on && f.due_on < today() ? 'overdue' : ''}"><span class="due">${d(f.due_on) || 'no date'}</span><span><a href="${BASE}/${f.intake_id}"><b>${esc(f.inquiry_no)}</b>${f.tour_no ? ' · ' + esc(f.tour_no) : ''}</a> ${esc(f.tour_name || '')} — ${esc(f.title)}<div class="help">${esc(f.owner_name || '')}${f.detail ? ' · ' + esc(f.detail) : ''}</div></span></div>`).join('') : '<div class="empty">Nothing due.</div>';
        }
        document.getElementById('d_mine').addEventListener('change', due);
        run(); due();
        return;
    }

    // ================= one inquiry / tour =================
    const S = { data: null, filter: 'all', q: '' };
    const dirIcon = r => r.kind === 'comm' ? (r.direction === 'in' ? 'bi-envelope-arrow-down' : r.direction === 'out' ? 'bi-envelope-arrow-up' : (r.channel === 'handover' ? 'bi-people' : 'bi-journal-text')) : r.kind === 'edit_request' ? 'bi-pencil-square' : r.kind === 'quote' ? 'bi-file-earmark-text' : r.kind === 'event' ? 'bi-signpost' : 'bi-activity';
    const cls = r => r.kind === 'comm' ? (r.source === 'system' ? 'sys ' : '') + r.direction : (r.kind === 'edit_request' ? 'change' : 'sys');
    function matches(r) {
        if (S.filter === 'corr' && !(r.kind === 'comm' && r.direction !== 'internal')) return false;
        if (S.filter === 'internal' && !(r.kind === 'comm' && r.direction === 'internal')) return false;
        if (S.filter === 'changes' && !(r.kind === 'edit_request' || r.kind === 'quote' || (r.kind === 'comm' && ['agreement', 'approval', 'change', 'confirmation'].includes(r.tag)) || (r.kind === 'activity' && /confirm|amend|reassign|cancel|issue|quotation/i.test(r.action || '')))) return false;
        if (S.filter === 'system' && !(r.kind === 'activity' || r.kind === 'event')) return false;
        if (S.q) { const hay = JSON.stringify(r).toLowerCase(); if (!hay.includes(S.q.toLowerCase())) return false; }
        return true;
    }
    function renderTimeline() {
        const tl = document.getElementById('tl'); const rows = S.data.timeline.filter(matches);
        document.getElementById('tl_count').textContent = `${rows.length} of ${S.data.timeline.length} line(s)`;
        tl.innerHTML = rows.map(r => {
            if (r.kind === 'comm') {
                const who = r.direction === 'in' ? `from <b>${esc(r.from || '?')}</b>${r.to ? ' to ' + esc(r.to) : ''}` : r.direction === 'out' ? `to <b>${esc(r.to || '?')}</b>${r.cc ? ' cc ' + esc(r.cc) : ''}${r.from ? ' from ' + esc(r.from) : ''}` : `by <b>${esc(r.by || '')}</b>`;
                return `<li class="${cls(r)}" data-id="${r.id}"><div class="ico"><i class="bi ${dirIcon(r)}"></i></div>
                    <div class="head"><span class="when">${dt(r.at)}</span>${pill(r.direction === 'internal' ? 'amber' : (r.direction === 'in' ? 'blue' : 'green'), (r.direction === 'in' ? 'received' : r.direction === 'out' ? (r.send_state === 'failed' ? 'not sent' : 'sent') : 'internal') + ' · ' + (CH[r.channel] || r.channel))}${r.send_state === 'sent' ? pill('green', 'accepted by outgoing mail server') : r.send_state === 'failed' ? pill('red', 'NOT SENT') : ''}${r.ref && r.ref.startsWith('tf_mail_in#') ? pill('blue', 'collected from the mailbox') : ''}${r.source === 'system' ? pill('purple', 'captured by the system') : ''}${r.tag ? pill(r.tag === 'question' ? 'amber' : (r.tag === 'change' ? 'red' : 'green'), TAGS[r.tag] || r.tag) : ''}${r.versions > 1 ? pill('grey', 'corrected ×' + (r.versions - 1)) : ''}
                        <div class="acts">${r.channel === 'email' && r.direction !== 'internal' ? `<button data-reply="${r.id}" title="Reply by e-mail from here"><i class="bi bi-reply"></i></button>` : ''}${r.channel === 'email' && r.direction === 'out' && r.send_state !== 'failed' ? `<button data-recv="${r.id}" title="Record the reply that came back to this e-mail"><i class="bi bi-envelope-arrow-down"></i></button>` : ''}${r.source !== 'system' ? `<button data-edit="${r.id}" title="Correct"><i class="bi bi-pencil"></i></button><button data-remove="${r.id}" title="Remove from view (with reason)"><i class="bi bi-x-circle"></i></button>` : ''}<button data-attach="${r.id}" title="Add an attachment"><i class="bi bi-paperclip"></i></button><button data-fu="${r.id}" title="Follow-up from this"><i class="bi bi-flag"></i></button></div></div>
                    <div class="subj">${esc(r.subject || '(no subject)')}</div><div class="addr">${who}${r.bcc ? ' · bcc ' + esc(r.bcc) : ''}</div>
                    ${r.send_state === 'failed' ? `<div class="help" style="color:#b23a32"><i class="bi bi-exclamation-triangle"></i> ${esc(r.send_error || 'The mail server did not accept it.')}</div>` : ''}${r.in_reply_to ? `<div class="help"><i class="bi bi-reply"></i> reply to line #${r.in_reply_to}</div>` : ''}
                    ${r.body ? `<div class="body" id="b${r.id}">${esc(r.body)}</div><span class="more" data-more="${r.id}">show all</span>` : ''}
                    ${(r.attachments || []).length ? `<div class="att">${r.attachments.map(a => `<a href="${BASE}/attachment/${a.id}" target="_blank"><i class="bi bi-paperclip"></i> ${esc(a.original_name)} <span class="help">${a.bytes >= 1048576 ? (a.bytes / 1048576).toFixed(1) + ' MB' : a.bytes >= 1024 ? Math.round(a.bytes / 1024) + ' KB' : a.bytes + ' B'}</span></a>`).join('')}</div>` : ''}
                    <div class="meta">${(r.links || []).map(l => `<span><i class="bi bi-link-45deg"></i> ${esc(l.label || l.kind)}</span>`).join('')}<span>recorded by ${esc(r.by || '')} on ${dt(r.recorded_at)}</span></div></li>`;
            }
            if (r.kind === 'edit_request') {
                const st = { open: 'amber', waiting: 'amber', applied: 'green', cancelled: 'grey' }[r.status] || 'grey';
                return `<li class="change"><div class="ico"><i class="bi bi-pencil-square"></i></div><div class="head"><span class="when">${dt(r.at)}</span>${pill('red', 'Edit Request #' + r.seq)}${pill(st, r.status)}</div>
                    <div class="subj">Change requested by ${esc(r.by || '')}${r.reason ? ' — ' + esc(r.reason) : ''}</div>
                    <table class="chg"><tr><td>From</td><td>${esc(r.pasted || '')}</td></tr><tr><td>To</td><td>${esc(r.change_to || '')}</td></tr></table>
                    <div class="meta">${r.confirmed_at ? `<span><i class="bi bi-check2-circle"></i> approved and applied by ${esc(r.confirmed_by || '')} on ${dt(r.confirmed_at)}</span>` : r.cancelled_at ? `<span>cancelled ${dt(r.cancelled_at)} ${esc(r.cancel_note || '')}</span>` : `<span>${r.status === 'waiting' ? 'waiting for Management to amend the confirmed tour' : 'not yet checked / confirmed — the itinerary is unchanged until it is'}</span>`}</div></li>`;
            }
            if (r.kind === 'quote') {
                return `<li class="sys"><div class="ico"><i class="bi bi-file-earmark-text"></i></div><div class="head"><span class="when">${dt(r.at)}</span>${pill('purple', 'Quotation version ' + r.version)}${pill(r.status === 'issued' ? 'green' : (r.status === 'superseded' ? 'grey' : 'amber'), r.status)}</div>
                    <div class="subj">USD ${esc(r.sell_usd ?? '')} total · ${esc(r.per_person_usd ?? '')} per person · ${esc(r.pax ?? '')} pax${r.valid_until ? ' · valid until ' + d(r.valid_until) : ''}</div><div class="meta"><span>${esc(r.by || '')}${r.note ? ' · ' + esc(r.note) : ''}</span><span><a href="${INTAKE}/intake/${ID}/quote/${r.id}?view=client" target="_blank">open this version</a></span></div></li>`;
            }
            return `<li class="sys"><div class="ico"><i class="bi ${dirIcon(r)}"></i></div><div class="head"><span class="when">${dt(r.at)}</span>${pill('grey', (r.lane || r.entity || 'tour') + ' · ' + (r.action || ''))}</div><div class="subj" style="font-weight:500">${esc(r.summary || '')}</div>${(r.changes || []).length ? `<table class="chg">${r.changes.slice(0, 8).map(c => `<tr><td>${esc(c.what)}</td><td>${esc(c.from ?? '')} → ${esc(c.to ?? '')}</td></tr>`).join('')}</table>` : ''}<div class="meta"><span>${esc(r.by || '')}</span></div></li>`;
        }).join('') || '<li class="empty">Nothing in the record yet.</li>';
        tl.querySelectorAll('[data-more]').forEach(b => b.addEventListener('click', () => { const el = document.getElementById('b' + b.dataset.more); el.classList.toggle('open'); b.textContent = el.classList.contains('open') ? 'show less' : 'show all'; }));
        tl.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openEdit(Number(b.dataset.edit))));
        tl.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => removeLine(Number(b.dataset.remove))));
        tl.querySelectorAll('[data-attach]').forEach(b => b.addEventListener('click', () => attachTo(Number(b.dataset.attach))));
        tl.querySelectorAll('[data-reply]').forEach(b => b.addEventListener('click', () => openCompose(Number(b.dataset.reply))));
        tl.querySelectorAll('[data-recv]').forEach(b => b.addEventListener('click', () => recordReplyTo(Number(b.dataset.recv))));
        tl.querySelectorAll('[data-fu]').forEach(b => b.addEventListener('click', () => openFollowup(null, Number(b.dataset.fu))));
        tl.querySelectorAll('.body').forEach(el => { if (el.scrollHeight <= 124) { el.classList.add('open'); const m = tl.querySelector(`[data-more="${el.id.slice(1)}"]`); if (m) m.remove(); } });
    }
    function renderSummary() {
        const s = S.data.summary, i = s.inquiry, t = s.tour, q = s.quotation;
        document.getElementById('h_title').innerHTML = `Communications & History — ${esc(i.inquiry_no)}${t ? ' · ' + esc(t.tour_no) : ''}`;
        document.getElementById('h_sub').innerHTML = `${esc(i.tour_name || '')} · ${esc(i.agent_name || '')} · ${d(i.arrival_date)}${i.departure_date ? ' → ' + d(i.departure_date) : ''} · ${esc(i.pax_total ?? '')} pax · ${t ? pill(t.stage === 'cancelled' ? 'red' : 'green', 'tour ' + t.stage) : pill('blue', 'inquiry ' + i.status)}${i.archived_at ? ' ' + pill('grey', 'archived') : ''} · handling agent <b>${esc(t && t.handler_name ? t.handler_name : (i.handler_name || '—'))}</b>`;
        document.getElementById('h_tools').innerHTML = `<a class="btn btn-light btn-sm" href="${BASE}"><i class="bi bi-search"></i> Search all</a><a class="btn btn-light btn-sm" href="${INTAKE}?id=${i.id}"><i class="bi bi-inbox"></i> Open inquiry</a>${t ? `<a class="btn btn-light btn-sm" href="${BOARD}/tour/${t.id}/record" target="_blank"><i class="bi bi-journal-richtext"></i> Complete tour record</a>` : ''}<a class="btn btn-light btn-sm" href="${INTAKE}?id=${i.id}#act_card" title="Reassign the handling agent from the inquiry's ⋯ Manage menu (Management); the record stays with the tour"><i class="bi bi-person-gear"></i> Reassign</a><button class="btn btn-primary btn-sm" id="h_print"><i class="bi bi-printer"></i> Print handover</button>`;
        document.getElementById('h_print').addEventListener('click', () => window.print());
        document.getElementById('h_tools').insertAdjacentHTML('afterbegin', '<button class="btn btn-avgreen btn-sm" id="h_compose"><i class="bi bi-envelope-paper"></i> Compose &amp; send e-mail</button>');
        document.getElementById('h_compose').addEventListener('click', () => openCompose(null));
        const line = (html) => `<div class="line">${html}</div>`;
        let h = '';
        h += '<h6>Agreed so far</h6>';
        if (q.sold) h += line(`${pill('green', 'sold')} Quotation version ${q.sold.version} — USD ${esc(q.sold.sell_usd)} (${esc(q.sold.per_person_usd)} pp, ${esc(q.sold.pax)} pax)${t ? ` · confirmed ${dt(t.confirmed_at)} by ${esc(t.confirmed_by || '')}` : ''}`);
        if (q.issued && (!q.sold || q.issued.id !== q.sold.id)) h += line(`${pill('blue', 'issued')} Quotation version ${q.issued.version} — USD ${esc(q.issued.sell_usd)} (${esc(q.issued.per_person_usd)} pp) · ${dt(q.issued.issued_at)}`);
        if (!q.sold && !q.issued) h += line('<span class="help">No quotation issued yet.</span>');
        (s.changes_applied || []).forEach(e => h += line(`${pill('green', 'change applied')} #${e.seq}: ${esc(e.change_to || '')}<div class="help">requested by ${esc(e.requested_by || '')} ${dt(e.requested_at)} · approved by ${esc(e.confirmed_by || '')} ${dt(e.confirmed_at)}</div>`));
        (s.agreed || []).forEach(c => h += line(`${pill('green', TAGS[c.tag] || 'agreed')} ${esc(c.subject || (c.body || '').slice(0, 120))}<div class="help">${dt(c.occurred_at)} · ${CH[c.channel] || c.channel}${c.from_addr ? ' · ' + esc(c.from_addr) : ''}</div>`));
        if (s.hotels && s.hotels.length) { h += '<h6>Hotel bookings</h6>'; s.hotels.forEach(x => h += line(`${pill(x.status === 'confirmed' || x.status === 'voucher' ? 'green' : (x.status === 'requested' ? 'amber' : 'grey'), x.status)} ${esc(x.hotel_name)} · ${d(x.check_in)} · ${esc(x.nights)} night(s)${x.confirmation_ref ? ' · ref ' + esc(x.confirmation_ref) : ''}`)); }
        h += '<h6>Still open</h6>';
        let open = 0;
        (s.changes_waiting || []).forEach(e => { open++; h += line(`${pill('red', 'change not yet approved')} #${e.seq}: ${esc(e.change_to || '')}<div class="help">requested by ${esc(e.requested_by || '')} ${dt(e.requested_at)} — the itinerary is unchanged until it is checked and confirmed</div>`); });
        (s.questions || []).forEach(c => { open++; h += line(`${pill('amber', 'question')} ${esc(c.subject || (c.body || '').slice(0, 120))}<div class="help">${dt(c.occurred_at)} · ${esc(c.from_addr || c.recorded_name || '')}</div>`); });
        (s.open_requests || []).forEach(r => { open++; h += line(`${pill('amber', r.kind)} ${esc(r.title)}`); });
        (s.followups || []).forEach(f => { open++; h += line(`${pill(f.due_on && f.due_on < today() ? 'red' : 'amber', 'follow-up')} ${esc(f.title)} — ${esc(f.owner_name || '')}, due ${d(f.due_on)}`); });
        if (!open) h += line('<span class="help">Nothing waiting. </span>');
        h += `<h6>Last contact</h6>${line(`in: ${s.last_in ? dt(s.last_in.occurred_at) + ' · ' + esc(s.last_in.subject || '') + ' (' + esc(s.last_in.from_addr || '') + ')' : '—'}`)}${line(`out: ${s.last_out ? dt(s.last_out.occurred_at) + ' · ' + esc(s.last_out.subject || '') + ' (' + esc(s.last_out.to_addr || '') + ')' : '—'}`)}`;
        document.getElementById('sum').innerHTML = h;
        // links for the add form
        const sel = document.getElementById('a_link'); const opts = ['<option value="">— nothing in particular —</option>'];
        if (q.sold) opts.push(`<option value='${esc(JSON.stringify({ kind: 'quote', id: q.sold.id, label: 'quotation version ' + q.sold.version + ' (sold)' }))}'>Quotation version ${q.sold.version} (sold)</option>`);
        if (q.issued && (!q.sold || q.issued.id !== q.sold.id)) opts.push(`<option value='${esc(JSON.stringify({ kind: 'quote', id: q.issued.id, label: 'quotation version ' + q.issued.version }))}'>Quotation version ${q.issued.version}</option>`);
        (s.hotels || []).forEach(x => opts.push(`<option value='${esc(JSON.stringify({ kind: 'hotel_job', id: x.id, label: 'hotel booking ' + x.hotel_name }))}'>Hotel booking — ${esc(x.hotel_name)}</option>`));
        ['transport', 'passengers', 'special requests', 'payment'].forEach(k => opts.push(`<option value='${esc(JSON.stringify({ kind: k, id: 0, label: k }))}'>${k[0].toUpperCase() + k.slice(1)}</option>`));
        sel.innerHTML = opts.join('');
    }
    function renderFollowups() {
        const list = S.data.followups; const open = list.filter(f => f.status === 'open');
        document.getElementById('fu_count').textContent = `${open.length} open`;
        document.getElementById('fu_list').innerHTML = list.length ? list.map(f => `<div class="fu ${f.status !== 'open' ? 'done' : (f.due_on && f.due_on < today() ? 'overdue' : '')}"><span class="due">${d(f.due_on) || '—'}</span><span style="flex:1"><b>${esc(f.title)}</b>${f.detail ? `<div class="help">${esc(f.detail)}</div>` : ''}<div class="help">${esc(f.owner_name || '')}${f.status !== 'open' ? ' · ' + f.status + ' ' + dt(f.done_at) + (f.done_note ? ' — ' + esc(f.done_note) : '') : ''}</div></span>${f.status === 'open' ? `<span class="d-flex gap-1"><button class="btn btn-light btn-sm" data-fudone="${f.id}" title="Mark done"><i class="bi bi-check2"></i></button><button class="btn btn-light btn-sm" data-fuedit="${f.id}" title="Change owner / date"><i class="bi bi-pencil"></i></button></span>` : ''}</div>`).join('') : '<div class="empty">No follow-ups.</div>';
        document.querySelectorAll('[data-fudone]').forEach(b => b.addEventListener('click', async () => { const r = await Swal.fire({ title: 'Mark done', input: 'text', inputPlaceholder: 'what was done (optional)', showCancelButton: true, confirmButtonText: 'Done' }); if (!r.isConfirmed) return; const j = await post(`/${ID}/followup/${b.dataset.fudone}`, { status: 'done', done_note: r.value || '' }); toast(j.status === 200 ? 'success' : 'error', j.message); load(); }));
        document.querySelectorAll('[data-fuedit]').forEach(b => b.addEventListener('click', () => openFollowup(Number(b.dataset.fuedit))));
    }
    async function load() {
        const j = await api(`/${ID}/data`); if (j.status !== 200) { toast('error', j.message); return; }
        S.data = j.data; renderSummary(); renderFollowups(); renderTimeline();
    }
    document.querySelectorAll('.filters .chip').forEach(c => c.addEventListener('click', () => { document.querySelectorAll('.filters .chip').forEach(x => x.classList.remove('on')); c.classList.add('on'); S.filter = c.dataset.f; renderTimeline(); }));
    document.getElementById('tl_q').addEventListener('input', e => { S.q = e.target.value.trim(); renderTimeline(); });

    // record a communication
    document.getElementById('a_when').value = nowLocal();
    document.getElementById('a_dir').addEventListener('change', e => { const v = e.target.value; const ch = document.getElementById('a_channel'); if (v === 'internal' && !['note', 'handover', 'meeting', 'phone'].includes(ch.value)) ch.value = 'note'; if (v !== 'internal' && ['note', 'handover'].includes(ch.value)) ch.value = 'email'; });
    document.getElementById('a_save').addEventListener('click', async () => {
        const btn = document.getElementById('a_save'); if (btn.disabled) return;
        const fd = new FormData();
        fd.append('direction', document.getElementById('a_dir').value); fd.append('channel', document.getElementById('a_channel').value); fd.append('tag', document.getElementById('a_tag').value);
        fd.append('occurred_at', document.getElementById('a_when').value); fd.append('from_addr', document.getElementById('a_from').value); fd.append('to_addr', document.getElementById('a_to').value); fd.append('cc_addr', document.getElementById('a_cc').value);
        fd.append('subject', document.getElementById('a_subject').value); fd.append('body', document.getElementById('a_body').value);
        const link = document.getElementById('a_link').value; fd.append('links', JSON.stringify(link ? [JSON.parse(link)] : []));
        fd.append('in_reply_to', document.getElementById('a_reply_to').value);
        for (const f of document.getElementById('a_files').files) fd.append('files[]', f);
        if (!document.getElementById('a_body').value.trim() && !document.getElementById('a_subject').value.trim()) { toast('error', 'Type the message, or at least its subject.'); return; }
        btn.disabled = true;
        const r = await fetch(`${BASE}/${ID}/add`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(e => ({ status: 500, message: String(e) }));
        btn.disabled = false; toast(r.status === 200 ? 'success' : 'error', r.message);
        if (r.status === 200) { ['a_from', 'a_to', 'a_cc', 'a_subject', 'a_body'].forEach(id => document.getElementById(id).value = ''); document.getElementById('a_files').value = ''; document.getElementById('a_tag').value = ''; document.getElementById('a_when').value = nowLocal(); document.getElementById('a_reply_to').value = ''; document.getElementById('a_reply_note').style.display = 'none'; load(); }
    });
    // tf-comms-r2: a reply that came back to an e-mail sent from here is recorded on the form, in the same conversation
    function recordReplyTo(cid) {
        const r = S.data.timeline.find(x => x.kind === 'comm' && x.id === cid); if (!r) return;
        document.getElementById('a_dir').value = 'in'; document.getElementById('a_channel').value = 'email';
        document.getElementById('a_from').value = r.to || ''; document.getElementById('a_to').value = (r.sent_from || '');
        document.getElementById('a_subject').value = /^\s*re\s*:/i.test(r.subject || '') ? r.subject : 'Re: ' + (r.subject || '');
        document.getElementById('a_reply_to').value = cid;
        const n = document.getElementById('a_reply_note'); n.style.display = ''; n.innerHTML = `<i class="bi bi-reply"></i> Recording the reply to <b>${esc(r.subject || '')}</b> (sent ${dt(r.at)}) — paste the reply as received, or attach the .eml; it is filed in the same conversation.`;
        document.getElementById('add_card').scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => document.getElementById('a_body').focus(), 400);
    }

    // tf-comms-r2: compose and send an e-mail from this record (a reply when cid is given)
    async function openCompose(cid) {
        const j = await api(`/${ID}/compose${cid ? '?reply=' + cid : ''}`); if (j.status !== 200) { toast('error', j.message); return; }
        const d = j.data, sd = d.sender || {};
        document.getElementById('m_mail_title').textContent = cid ? 'Reply by e-mail' : 'Compose e-mail';
        document.getElementById('mail_sender').innerHTML = sd.kind === 'none' ? `<b style="color:#b23a32">Cannot send:</b> ${esc(sd.note)}` : `<b>From:</b> ${esc(sd.name || 'Avenir Holidays')} &lt;${esc(sd.email || '')}&gt; — ${esc(sd.note)}${(d.always_cc || []).length ? ` · <b>copied to</b> ${esc(d.always_cc.join(', '))} (company setting)` : ''}`;
        document.getElementById('mail_send').disabled = sd.kind === 'none';
        const rn = document.getElementById('mail_reply_note');
        if (d.reply) { rn.style.display = ''; rn.innerHTML = `<i class="bi bi-reply"></i> Replying to <b>${esc(d.reply.original.subject || '')}</b> (${esc(d.reply.original.from || '')}, ${dt(d.reply.original.at)}) — the reply is filed in the same conversation.`; } else { rn.style.display = 'none'; rn.innerHTML = ''; }
        document.getElementById('mail_reply_to').value = d.reply ? d.reply.in_reply_to : '';
        document.getElementById('mail_to').value = d.defaults.to || ''; document.getElementById('mail_cc').value = d.defaults.cc || ''; document.getElementById('mail_bcc').value = '';
        document.getElementById('mail_subject').value = d.defaults.subject || ''; document.getElementById('mail_tag').value = '';
        document.getElementById('mail_body').value = (d.defaults.greeting || '') + (d.defaults.quoted || '');
        document.getElementById('mail_sig').textContent = d.signature || '';
        document.getElementById('mail_files').value = '';
        const groups = {}; (d.documents || []).forEach(x => { (groups[x.group] = groups[x.group] || []).push(x); });
        document.getElementById('mail_docs').innerHTML = Object.keys(groups).length ? Object.keys(groups).map(g => `<div class="help" style="margin-top:4px"><b>${esc(g)}</b></div>` + groups[g].map(x => `<label style="display:block;font-size:12.5px"><input type="checkbox" class="mail_doc" value="${esc(x.key)}"> ${esc(x.label)}</label>`).join('')).join('') : '<span class="help">No quotation, letter or file on this record yet.</span>';
        const sel = document.getElementById('mail_link'); sel.innerHTML = document.getElementById('a_link').innerHTML;
        showModal('#m_mail');
        setTimeout(() => { const el = document.getElementById('mail_body'); el.focus(); el.setSelectionRange((d.defaults.greeting || '').length, (d.defaults.greeting || '').length); }, 300);
    }
    document.getElementById('compose_btn').addEventListener('click', () => openCompose(null));
    document.getElementById('mail_send').addEventListener('click', async () => {
        const btn = document.getElementById('mail_send'); if (btn.disabled) return;
        const to = document.getElementById('mail_to').value.trim(), subject = document.getElementById('mail_subject').value.trim(), body = document.getElementById('mail_body').value.trim();
        if (!to) { toast('error', 'Enter at least one e-mail address in To.'); return; }
        if (!subject) { toast('error', 'The e-mail needs a subject.'); return; }
        if (!body) { toast('error', 'Type the message.'); return; }
        const docs = [...document.querySelectorAll('.mail_doc:checked')].map(c => c.value); const files = document.getElementById('mail_files').files;
        const ok = await Swal.fire({ icon: 'question', title: 'Send this e-mail now?', html: `To <b>${esc(to)}</b>${document.getElementById('mail_cc').value.trim() ? ' · cc ' + esc(document.getElementById('mail_cc').value.trim()) : ''}<br>${esc(subject)}<br><span class="help">${docs.length + files.length} attachment(s)</span>`, showCancelButton: true, confirmButtonText: 'Send', confirmButtonColor: '#78b848' });
        if (!ok.isConfirmed) return;
        const fd = new FormData();
        fd.append('to', to); fd.append('cc', document.getElementById('mail_cc').value); fd.append('bcc', document.getElementById('mail_bcc').value); fd.append('subject', subject); fd.append('body', body);
        fd.append('tag', document.getElementById('mail_tag').value); fd.append('in_reply_to', document.getElementById('mail_reply_to').value); fd.append('docs', JSON.stringify(docs));
        const link = document.getElementById('mail_link').value; fd.append('links', JSON.stringify(link ? [JSON.parse(link)] : []));
        for (const f of files) fd.append('files[]', f);
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending…';
        const r = await fetch(`${BASE}/${ID}/send`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).catch(e => ({ status: 500, message: String(e) }));
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Send e-mail';
        if (r.status === 200) { hideModal('#m_mail'); toast('success', r.message); } else { Swal.fire({ icon: 'error', title: 'Not sent', text: r.message }); }
        load();
    });
    async function attachTo(cid) {
        const inp = document.createElement('input'); inp.type = 'file'; inp.multiple = true;
        inp.addEventListener('change', async () => { const fd = new FormData(); for (const f of inp.files) fd.append('files[]', f); const r = await fetch(`${BASE}/${ID}/message/${cid}/attach`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()); toast(r.status === 200 ? 'success' : 'error', r.message); load(); });
        inp.click();
    }
    function openEdit(cid) {
        const r = S.data.timeline.find(x => x.kind === 'comm' && x.id === cid); if (!r) return;
        document.getElementById('e_id').value = cid; document.getElementById('e_when').value = String(r.at).slice(0, 16).replace(' ', 'T'); document.getElementById('e_tag').value = r.tag || ''; document.getElementById('e_from').value = r.from || ''; document.getElementById('e_subject').value = r.subject || ''; document.getElementById('e_body').value = r.body || ''; document.getElementById('e_why').value = '';
        showModal('#m_edit');
    }
    document.getElementById('e_ok').addEventListener('click', async () => {
        const why = document.getElementById('e_why').value.trim(); if (why.length < 5) { toast('error', 'Say why it is corrected (at least five characters).'); return; }
        const j = await post(`/${ID}/message/${document.getElementById('e_id').value}/edit`, { occurred_at: document.getElementById('e_when').value, tag: document.getElementById('e_tag').value, from_addr: document.getElementById('e_from').value, subject: document.getElementById('e_subject').value, body: document.getElementById('e_body').value, why });
        toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) { hideModal('#m_edit'); load(); }
    });
    async function removeLine(cid) {
        const r = await Swal.fire({ icon: 'warning', title: 'Remove this line from view?', text: 'It is never deleted: it stays in the record with your reason, and the audit log says who removed it.', input: 'text', inputPlaceholder: 'reason (required)', inputValidator: v => (String(v || '').trim().length >= 5 ? null : 'Give the reason'), showCancelButton: true, confirmButtonText: 'Remove' }); if (!r.isConfirmed) return;
        const j = await post(`/${ID}/message/${cid}/remove`, { why: r.value }); toast(j.status === 200 ? 'success' : 'error', j.message); load();
    }
    function openFollowup(fid, cid) {
        const f = fid ? S.data.followups.find(x => Number(x.id) === fid) : null;
        document.getElementById('m_fu_title').textContent = fid ? 'Follow-up' : 'New follow-up';
        document.getElementById('fu_id').value = fid || ''; document.getElementById('fu_comm').value = cid || '';
        document.getElementById('fu_title').value = f ? f.title : ''; document.getElementById('fu_detail').value = f ? (f.detail || '') : '';
        const owner = document.getElementById('fu_owner'); owner.value = f && f.owner_id ? String(f.owner_id) : String(ME.id || ''); if (!owner.value && owner.options.length) owner.selectedIndex = 0;
        document.getElementById('fu_due').value = f ? (f.due_on || '') : '';
        showModal('#m_fu');
    }
    document.getElementById('fu_add').addEventListener('click', () => openFollowup(null, null));
    document.getElementById('fu_ok').addEventListener('click', async () => {
        const owner = document.getElementById('fu_owner'); const body = { title: document.getElementById('fu_title').value, detail: document.getElementById('fu_detail').value, owner_id: owner.value, owner_name: owner.options[owner.selectedIndex] ? owner.options[owner.selectedIndex].dataset.name : '', due_on: document.getElementById('fu_due').value, comm_id: document.getElementById('fu_comm').value || null };
        const fid = document.getElementById('fu_id').value; const j = await post(`/${ID}/followup${fid ? '/' + fid : ''}`, body);
        toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) { hideModal('#m_fu'); load(); }
    });
    load();
})();
</script>
</body>
</html>
