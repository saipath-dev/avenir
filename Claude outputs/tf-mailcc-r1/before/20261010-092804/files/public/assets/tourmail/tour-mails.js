/*
 * tf-tourmail-r1 (10 Oct 2026) — the hotels' booking requests of a tour: the list before they go (Confirm Tour, and
 * "Email all hotels" on the Hotel Desk), what happened to each after OK, and a tour's mails as they went.
 * Needs SweetAlert2 (Swal), as every Tour Desk screen has. Nothing here sends: the caller posts the list it returns.
 */
(function () {
    'use strict';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = d => { if (!d) return '—'; const x = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }); };
    const isMail = v => /^[^\s@,;]+@[^\s@,;]+\.[^\s@,;]+$/.test(String(v || '').trim());
    const RED = '#b23a32', AMBER = '#b7791f', GREEN = '#1d6f47', MUTED = '#6b7a8c';
    const nights = n => n + ' night' + (Number(n) === 1 ? '' : 's');

    /**
     * The list before the hotels' requests go. plan: {sender, stays, rooms, crew}. Resolves to the person's list
     * {cc, stays:[{day_from, send, email, driver, guide}]} and the counts, or null on Cancel.
     */
    async function review(plan, opts) {
        opts = opts || {};
        const S = plan.stays || [], snd = plan.sender || {}, crew = plan.crew || {}, rooms = plan.rooms || {};
        const dReq = !!(crew.driver && crew.driver.required), gReq = !!(crew.guide && crew.guide.required);
        const open = S.filter(s => !s.already);
        if (!S.length) return { cc: '', stays: [], sending: 0, notSending: 0 };
        const row = (s, i) => {
            const dates = `${fmt(s.check_in)} – ${fmt(s.check_out)}<div style="font-size:11px;color:${MUTED}">${nights(s.nights)}${s.day_from ? ' · day ' + s.day_from + (s.day_to !== s.day_from ? '–' + s.day_to : '') : ''}</div>`;
            const roomsCell = `${esc(s.rooms_line || '—')}${s.rooms_was ? `<div style="font-size:11px;color:${GREEN}">corrected (was “${esc(s.rooms_was)}”)</div>` : ''}<div style="font-size:11px;color:${MUTED}">${esc(s.meal_plan || 'meal plan not stated')}</div>`;
            if (s.already) {
                return `<tr style="border-top:1px solid #eef1f5;color:${MUTED}"><td style="padding:6px">${dates}</td><td style="padding:6px"><b>${esc(s.hotel_name || '—')}</b></td><td style="padding:6px">${roomsCell}</td><td style="padding:6px" colspan="${(dReq || gReq) ? 2 : 1}">Requested already${s.last && s.last.to ? ' (' + esc(s.last.to) + ')' : ''} — <b>not sent again</b>${s.rooms_stale ? `<div style="color:${AMBER};font-size:11.5px">Its request said “${esc(s.rooms_note)}” — send it a corrected request from its own card (Send).</div>` : ''}</td><td></td></tr>`;
            }
            const blocked = s.needs_hotel || s.needs_meal;
            const crewCell = (dReq || gReq) ? `<td style="padding:6px;white-space:nowrap">${dReq ? `<label style="font-size:12px">Driver <input type="number" min="0" max="${s.nights}" value="${Number(s.driver || 0)}" data-crew="driver" data-i="${i}" style="width:48px"> n</label>` : ''}${gReq ? `<br><label style="font-size:12px">Guide <input type="number" min="0" max="${s.nights}" value="${Number(s.guide || 0)}" data-crew="guide" data-i="${i}" style="width:48px"> n</label>` : ''}</td>` : '';
            const mailCell = blocked
                ? `<span style="color:${RED}">${esc(s.blocked)}</span>`
                : `<input type="email" data-mail="${i}" value="${esc(s.email || '')}" placeholder="hotel e-mail address" style="width:100%;min-width:190px;padding:3px 6px;border:1px solid ${s.email ? '#cfd6df' : RED};border-radius:6px;font-size:12.5px">
                   <div data-mailnote="${i}" style="font-size:11px;color:${s.email ? MUTED : RED}">${s.email ? (s.email_source === 'master' ? 'Hotel Master' : s.email_source === 'supplier' ? 'supplier record' : s.email_source === 'stay' ? 'used on this stay before' : 'typed here') : 'No e-mail — will NOT be sent (type one to send)'}</div>`;
            return `<tr style="border-top:1px solid #eef1f5"><td style="padding:6px;vertical-align:top">${dates}</td><td style="padding:6px;vertical-align:top"><b>${esc(s.hotel_name || 'not named')}</b>${s.area ? `<div style="font-size:11px;color:${MUTED}">${esc(s.area)}</div>` : ''}</td><td style="padding:6px;vertical-align:top">${roomsCell}</td>${crewCell}<td style="padding:6px;vertical-align:top">${mailCell}</td>
                <td style="padding:6px;vertical-align:top;text-align:center"><input type="checkbox" data-send="${i}" ${blocked ? 'disabled' : 'checked'} title="Send this one"></td></tr>`;
        };
        const roomsNote = rooms.suggest
            ? `<div style="background:#e4f4ea;border:1px solid #bfe3cc;border-radius:8px;padding:7px 10px;margin:6px 0;color:${GREEN}"><b>Room type corrected on the letters to ${rooms.suggest === 'twin' ? 'TWN (twin beds)' : 'DBL (double bed)'}</b> — the agent asked for “${esc(rooms.agent_note)}”; the inquiry had the rooms as ${rooms.suggest === 'twin' ? 'DBL' : 'TWN'}. The change is noted on each stay; the quotation is not touched.</div>`
            : (rooms.agent_note ? `<div style="font-size:12px;color:${MUTED};margin:4px 0">The agent asked for: “${esc(rooms.agent_note)}”.</div>` : '');
        const crewNote = ['driver', 'guide'].filter(k => crew[k] && crew[k].choose).map(k => `<div style="background:#fdf3e1;border:1px solid #f0d9a8;border-radius:8px;padding:7px 10px;margin:6px 0;color:#7a4b00" data-crewwarn="${k}"><b>${k === 'driver' ? 'Driver' : 'Guide'} rooms were set by hand to ${crew[k].nights} night${crew[k].nights === 1 ? '' : 's'}</b> (the tour has ${crew[k].tour_nights}); the inquiry does not say which nights. Enter the nights at each hotel below — <span data-crewsum="${k}"></span>.</div>`).join('');
        const sender = snd.can_send
            ? `From <b>${esc(snd.email)}</b> (your mailbox).`
            : `<span style="color:${RED}"><b>Your mailbox is not set up to send from</b> — nothing can go; every request will be listed as NOT sent. Set it in My Profile → Mailbox.</span>`;
        let out = null;
        const r = await Swal.fire({
            title: opts.title || 'Hotel booking requests', width: 1100,
            html: `<div style="text-align:left;font-size:13px">
                <div style="margin-bottom:6px">${opts.intro || 'Each hotel below gets its booking request by e-mail when you press OK.'} ${sender} Rates are not shown on the letters (“As per agreed contract rates”).</div>
                ${roomsNote}${crewNote}
                <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:12.5px"><thead><tr style="font-size:11px;text-transform:uppercase;color:${MUTED}"><th style="text-align:left;padding:4px 6px">Nights</th><th style="text-align:left;padding:4px 6px">Hotel</th><th style="text-align:left;padding:4px 6px">Rooms on the letter</th>${(dReq || gReq) ? '<th style="text-align:left;padding:4px 6px">Crew rooms</th>' : ''}<th style="text-align:left;padding:4px 6px">Send to</th><th style="padding:4px 6px">Send</th></tr></thead><tbody>${S.map(row).join('')}</tbody></table></div>
                <div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap"><label style="font-size:12px">Copy (CC) every request to <input type="text" id="tm_cc" value="${esc(plan.cc || '')}" placeholder="optional, e.g. your own address" style="width:260px;padding:3px 6px;border:1px solid #cfd6df;border-radius:6px"></label></div>
                <div id="tm_sum" style="margin-top:8px;font-size:12.5px"></div></div>`,
            showCancelButton: true, confirmButtonText: opts.ok || 'OK', cancelButtonText: 'Cancel', confirmButtonColor: '#78b448', focusCancel: !!opts.focusCancel,
            didOpen: () => {
                const box = Swal.getHtmlContainer();
                const sum = () => {
                    let go = 0, no = 0;
                    S.forEach((s, i) => {
                        if (s.already) return;
                        const cb = box.querySelector(`[data-send="${i}"]`), m = box.querySelector(`[data-mail="${i}"]`);
                        const ok = cb && cb.checked && !cb.disabled && m && isMail(m.value) && snd.can_send;
                        if (m) {
                            const note = box.querySelector(`[data-mailnote="${i}"]`), v = m.value.trim(), good = isMail(v);
                            m.style.borderColor = good ? '#cfd6df' : RED;
                            if (note) {
                                note.style.color = good ? MUTED : RED;
                                note.textContent = !good ? (v ? 'Not an e-mail address — will NOT be sent' : 'No e-mail — will NOT be sent (type one to send)')
                                    : (v === String(s.email || '') ? (s.email_source === 'master' ? 'Hotel Master' : s.email_source === 'supplier' ? 'supplier record' : s.email_source === 'stay' ? 'used on this stay before' : 'typed here') : 'typed here — for this booking');
                            }
                        }
                        ok ? go++ : no++;
                    });
                    ['driver', 'guide'].forEach(k => { const el = box.querySelector(`[data-crewsum="${k}"]`); if (!el) return; let n = 0; box.querySelectorAll(`[data-crew="${k}"]`).forEach(x => n += Number(x.value || 0)); el.innerHTML = `<b>${n} of ${crew[k].nights}</b> entered` + (n !== Number(crew[k].nights) ? ' — not the same' : ' ✓'); });
                    box.querySelector('#tm_sum').innerHTML = `<b style="color:${GREEN}">${go} request${go === 1 ? '' : 's'} will be sent</b>${no ? ` · <b style="color:${RED}">${no} will NOT be sent</b> — ${no === 1 ? 'it stays' : 'they stay'} on “Mails not sent” until sent or recorded as sent another way` : ''}${S.length - open.length ? ` · ${S.length - open.length} requested already` : ''}.`;
                };
                box.querySelectorAll('input').forEach(x => x.addEventListener('input', sum));
                box.querySelectorAll('input[type=checkbox]').forEach(x => x.addEventListener('change', sum));
                sum();
            },
            preConfirm: () => {
                const box = Swal.getHtmlContainer();
                const cc = box.querySelector('#tm_cc').value.trim();
                if (cc && cc.split(/[;,\s]+/).filter(Boolean).some(x => !isMail(x))) { Swal.showValidationMessage('The CC box has something that is not an e-mail address.'); return false; }
                let go = 0, no = 0;
                const stays = S.map((s, i) => {
                    if (s.already) return { day_from: s.day_from, send: false, email: s.email || '', driver: Number(s.driver || 0), guide: Number(s.guide || 0) };
                    const cb = box.querySelector(`[data-send="${i}"]`), m = box.querySelector(`[data-mail="${i}"]`);
                    const d = box.querySelector(`[data-crew="driver"][data-i="${i}"]`), g = box.querySelector(`[data-crew="guide"][data-i="${i}"]`);
                    const email = m ? m.value.trim() : '';
                    const send = !!(cb && cb.checked && !cb.disabled);
                    (send && isMail(email) && snd.can_send) ? go++ : no++;
                    return { day_from: s.day_from, send, email, driver: d ? Math.max(0, Number(d.value || 0)) : Number(s.driver || 0), guide: g ? Math.max(0, Number(g.value || 0)) : Number(s.guide || 0) };
                });
                out = { cc, stays, sending: go, notSending: no };
                return true;
            },
        });
        return r.isConfirmed ? out : null;
    }

    const PILL = { sent: ['sent', GREEN], kept: ['requested already', MUTED], not_sent: ['NOT sent', RED], not_issued: ['NOT sent — no voucher', RED], other: ['sent another way', GREEN] };
    /** After OK: what happened to each hotel (and each restaurant), the ones that did not go in red with the reason. */
    function resultsHtml(hotels, meals) {
        const h = (hotels || []).map(r => `<tr style="border-top:1px solid #eef1f5"><td style="padding:4px 6px">Hotel</td><td style="padding:4px 6px"><b>${esc(r.hotel)}</b></td><td style="padding:4px 6px;color:${(PILL[r.status] || ['', '#333'])[1]}"><b>${esc((PILL[r.status] || [r.status])[0])}</b>${r.to && r.status === 'sent' ? ' to ' + esc(r.to) : ''}${r.why && r.status !== 'sent' ? `<div style="font-size:11.5px;color:${r.status === 'kept' ? MUTED : RED}">${esc(r.why)}</div>` : ''}</td></tr>`);
        const m = (meals || []).map(b => { const st = b.status === 'kept' ? 'kept' : b.status; const p = st === 'kept' ? ['already sent', GREEN] : (PILL[st] || [st, '#333']); return `<tr style="border-top:1px solid #eef1f5"><td style="padding:4px 6px">Restaurant</td><td style="padding:4px 6px"><b>${esc(b.restaurant)}</b>${b.voucher_no ? ' · ' + esc(b.voucher_no) : ''}</td><td style="padding:4px 6px;color:${p[1]}"><b>${esc(p[0])}</b>${b.to && ['sent', 'kept'].includes(b.status) ? ' to ' + esc(b.to) : ''}${b.why && !['sent', 'kept'].includes(b.status) ? `<div style="font-size:11.5px;color:${RED}">${esc(b.why)}</div>` : ''}</td></tr>`; });
        if (!h.length && !m.length) return '';
        const bad = (hotels || []).filter(r => r.status === 'not_sent').length + (meals || []).filter(b => !['sent', 'kept'].includes(b.status)).length;
        return `<table style="width:100%;border-collapse:collapse;font-size:12.5px;margin-top:4px" id="tm_results">${h.join('')}${m.join('')}</table>`
            + (bad ? `<div style="margin-top:8px;background:#fbe9e7;border:1px solid #f0b8b0;border-radius:8px;padding:7px 10px;color:#8a2a22"><b>${bad} mail${bad === 1 ? ' was' : 's were'} NOT sent.</b> ${bad === 1 ? 'It is' : 'They are'} on the red “Mails not sent” alert at the top of every screen until sent from the Hotel Desk / Activities desk, or recorded as sent another way.</div>` : '');
    }

    /** A tour's mails as they went (Hotel Desk → Mails), each with its copy. */
    async function showTourMails(base, tourId, title) {
        const r = await fetch(base + '/mails/data?tour_id=' + encodeURIComponent(tourId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        if (j.status !== 200) { Swal.fire({ icon: 'error', text: j.message || 'Could not read the mails.' }); return; }
        await Swal.fire({ title: 'Mails — ' + (title || ''), width: 1000, html: `<div style="text-align:left">${listHtml(j.data.mails, base, false)}<div style="margin-top:8px;font-size:12px"><a href="${base}/mails" target="_blank">Open Sent Mails (all tours)</a></div></div>`, confirmButtonText: 'Close' });
    }

    const STATE = { sent: ['Sent', GREEN], other: ['Sent another way', GREEN], not_sent: ['NOT sent', RED] };
    function listHtml(mails, base, withTour) {
        if (!mails || !mails.length) return `<div style="color:${MUTED};padding:10px 0">No hotel or restaurant mail on this tour yet.</div>`;
        return `<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:12.5px"><thead><tr style="font-size:11px;text-transform:uppercase;color:${MUTED}"><th style="text-align:left;padding:4px 6px">When</th>${withTour ? '<th style="text-align:left;padding:4px 6px">Tour</th>' : ''}<th style="text-align:left;padding:4px 6px">To whom</th><th style="text-align:left;padding:4px 6px">What</th><th style="text-align:left;padding:4px 6px">Address</th><th style="text-align:left;padding:4px 6px">State</th><th></th></tr></thead><tbody>${mails.map(m => {
            const [w, c] = STATE[m.state] || [m.state, '#333'];
            const fixed = m.state === 'not_sent' && !m.open;
            return `<tr style="border-top:1px solid #eef1f5${m.open ? ';background:#fff6f5' : ''}"><td style="padding:5px 6px;white-space:nowrap">${esc(String(m.at || '').slice(0, 16))}<div style="font-size:11px;color:${MUTED}">${esc(m.by || '')}</div></td>${withTour ? `<td style="padding:5px 6px;white-space:nowrap"><b>${esc(m.tour_no)}</b><div style="font-size:11px;color:${MUTED}">${esc(m.inquiry_no || '')}</div></td>` : ''}<td style="padding:5px 6px"><b>${esc(m.name || '—')}</b>${m.on ? `<div style="font-size:11px;color:${MUTED}">${fmt(m.on)}</div>` : ''}</td><td style="padding:5px 6px">${esc(m.what)}</td><td style="padding:5px 6px">${esc(m.to || '—')}${m.cc ? `<div style="font-size:11px;color:${MUTED}">cc ${esc(m.cc)}</div>` : ''}</td><td style="padding:5px 6px;color:${fixed ? MUTED : c}"><b>${esc(w)}</b>${fixed ? ' <span style="font-size:11px">(put right later)</span>' : ''}${m.error ? `<div style="font-size:11.5px;color:${fixed ? MUTED : (m.state === 'not_sent' ? RED : MUTED)}">${esc(m.error)}</div>` : ''}</td><td style="padding:5px 6px;white-space:nowrap">${m.has_copy || m.state === 'not_sent' ? `<a href="${base}/mails/copy/${m.type}/${m.id}" target="_blank">Open</a>` : ''}</td></tr>`;
        }).join('')}</tbody></table></div>`;
    }

    window.AvhTourMails = { review, resultsHtml, showTourMails, listHtml, esc };
})();
