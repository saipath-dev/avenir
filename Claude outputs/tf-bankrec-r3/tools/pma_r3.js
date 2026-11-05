// tf-bankrec-r3 — helpers run in a signed-in phpMyAdmin tab (same origin), for the ISOLATED TEST BED only.
// __sqlU(q, db) → {hdr, rows} | {ok} | {error};  __raw(q, db) → {success, text}
// __checksums(db) → {table: checksum} of every base table;  __copyDb(src, dst) → full copy of every base table's rows
window.__tok = () => (document.querySelector('input[name=token]') || {}).value || (window.CommonParams && CommonParams.get('token'));
window.__sqlU = async (q, db) => {
  const p = new URLSearchParams(); p.set('token', __tok()); p.set('db', db); p.set('sql_query', q); p.set('ajax_request', 'true'); p.set('ajax_page_request', 'true');
  const r = await fetch('index.php?route=/import', { method: 'POST', body: p, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  let j; try { j = await r.json(); } catch (e) { return { error: 'non-JSON response ' + r.status }; }
  if (j.redirect_flag) return { error: 'SESSION EXPIRED (redirect_flag)' };
  if (j.success === false) return { error: String(j.error || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 600) };
  const doc = new DOMParser().parseFromString(j.message || '', 'text/html');
  const t = doc.querySelector('table.table_results');
  if (!t) { const err = doc.querySelector('.alert-danger, .error'); return err ? { error: err.innerText.replace(/\s+/g, ' ').slice(0, 600) } : { ok: true, note: (doc.body.innerText || '').replace(/\s+/g, ' ').slice(0, 300) }; }
  const hdr = [...t.querySelectorAll('thead th.column_heading, thead th[data-column]')].map(th => (th.dataset.column || th.innerText).trim());
  const rows = [...t.querySelectorAll('tbody tr')].map(tr => [...tr.querySelectorAll('td.data')].map(td => td.innerText.trim())).filter(r => r.length);
  return { hdr, rows };
};
// phpMyAdmin shows at most 25 rows per page: page through with LIMIT for longer results
window.__all = async (q, db, page) => { const out = []; page = page || 25; for (let o = 0; ; o += page) { const r = await __sqlU(q + ' LIMIT ' + o + ', ' + page, db); if (r.error) return r; out.push(...(r.rows || [])); if (!r.rows || r.rows.length < page) return { rows: out }; } };
window.__raw = async (q, db) => { const p = new URLSearchParams(); p.set('token', __tok()); p.set('db', db); p.set('sql_query', q); p.set('ajax_request', 'true'); p.set('ajax_page_request', 'true'); const r = await fetch('index.php?route=/import', { method: 'POST', body: p, headers: { 'X-Requested-With': 'XMLHttpRequest' } }); const j = await r.json(); return { success: j.success, redirect: !!j.redirect_flag, text: (j.error || j.message || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 1500) }; };
window.__tables = async db => (await __all("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" + db + "' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", db, 25)).rows.map(r => r[0]);
window.__checksums = async db => {
  const ts = await __tables(db); const out = {};
  for (let i = 0; i < ts.length; i += 20) {
    const r = await __sqlU('CHECKSUM TABLE ' + ts.slice(i, i + 20).map(t => '`' + db + '`.`' + t + '`').join(', '), db);
    if (r.error) return { error: r.error, at: ts[i] };
    for (const [n, v] of r.rows) out[n.split('.').pop()] = v;
  }
  return out;
};
window.__copyDb = async (src, dst) => {
  const ts = await __tables(src); const log = [];
  let r = await __sqlU('CREATE DATABASE `' + dst + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', src); if (r.error) return { error: r.error };
  const gen = {}; for (const [t, c] of (await __all("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" + src + "' AND (EXTRA LIKE '%VIRTUAL GENERATED%' OR EXTRA LIKE '%STORED GENERATED%') ORDER BY TABLE_NAME, COLUMN_NAME", src)).rows) (gen[t] = gen[t] || []).push(c);
  for (const t of ts) {
    let cols = '*';
    if (gen[t]) { const all = (await __all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" + src + "' AND TABLE_NAME = '" + t + "' ORDER BY ORDINAL_POSITION", src)).rows.map(x => x[0]); cols = all.filter(c => !gen[t].includes(c)).map(c => '`' + c + '`').join(', '); }
    r = await __sqlU('CREATE TABLE `' + dst + '`.`' + t + '` LIKE `' + src + '`.`' + t + '`; INSERT INTO `' + dst + '`.`' + t + '` ' + (cols === '*' ? '' : '(' + cols + ') ') + 'SELECT ' + cols + ' FROM `' + src + '`.`' + t + '`', src);
    if (r.error) { log.push([t, r.error]); }
  }
  return { tables: ts.length, errors: log };
};
'pma r3 helpers loaded';
