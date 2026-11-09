<?php
/**
 * tf-clock-r1 (9 Nov 2026, Shantha) — the shared Japan / Sri Lanka clock strip, in the approved mockup design.
 * Used on Home (everyone) and on the Pilot Panel (Super Admin). Times come from the viewer's browser clock converted to
 * Asia/Tokyo and Asia/Colombo; temperatures from Open-Meteo (public, no key, nothing about the user is sent), refreshed
 * every 30 minutes and cached for the browser tab. If the weather service cannot be reached the strip shows times only.
 */
$wcId = 'wc' . substr(md5(uniqid('', true)), 0, 6);
// tf-clock-r3: $layout 'brand' places Tokyo on the left and Colombo on the right of the logo HTML passed in $logo
$wcBrand = ($layout ?? '') === 'brand';
?>
<style>
    .wc { display:grid; grid-template-columns:1fr 1fr; gap:28px; background:none; border:0; max-width:880px; margin:0 auto 12px; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    .wc-c { padding:2px 4px; min-width:0; }   /* tf-clock-r2: no frame, no divider */
    .wc-l { display:flex; align-items:center; gap:6px; color:#842484; font-weight:800; font-size:.8rem; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
    .wc-l svg { width:14px; height:14px; flex:none; }
    .wc-r { display:flex; align-items:center; gap:14px; margin-top:2px; }
    .wc-t { font-size:2.1rem; font-weight:800; color:#1999d6; line-height:1.05; font-variant-numeric:tabular-nums; letter-spacing:-.01em; }
    .wc-s { display:none; }
    .wc-d { color:#7d7683; font-size:.92rem; white-space:nowrap; }
    .wc-w { display:flex; align-items:center; gap:7px; font-weight:800; font-size:1.25rem; color:#1999d6; white-space:nowrap; }
    .wc-w svg * { fill:none !important; stroke:#8f8995 !important; }   /* the weather symbol in soft grey */
    .wc-w svg circle, .wc-w svg path[d^="M24 3.5"] { fill:#d6d2da !important; }
    .wc-w svg { width:34px; height:28px; flex:none; }
    .wc-w[hidden] { display:none; }
    @media (max-width:760px) { .wc-t { font-size:1.55rem; } .wc-r { gap:9px; } .wc-c { padding:8px 12px; } .wc-d { font-size:.8rem; } .wc-w { font-size:1rem; } .wc-w svg { width:28px; height:24px; } }
    @media (max-width:520px) { .wc { grid-template-columns:1fr; gap:6px; } }
    /* tf-clock-r3: either side of the logo, compact: place, time, then date and temperature on one line */
    .wc.brand { grid-template-columns:1fr auto 1fr; align-items:center; gap:26px; max-width:none; margin:0 0 6px; }
    .wc.brand .wc-mid, .wc.brand .wc-mid .hd-brand { margin:0 !important; }
    .wc.brand .wc-c[data-k=jp] { text-align:right; } .wc.brand .wc-c[data-k=jp] .wc-l, .wc.brand .wc-c[data-k=jp] .wc-r { justify-content:flex-end; }
    .wc.brand .wc-r { flex-wrap:wrap; gap:0 10px; margin-top:1px; }
    .wc.brand .wc-t { flex-basis:100%; font-size:1.9rem; }
    .wc.brand .wc-d { font-size:.86rem; } .wc.brand .wc-w { font-size:1.05rem; gap:5px; } .wc.brand .wc-w svg { width:26px; height:22px; }
    @media (max-width:760px) { .wc.brand { gap:12px; } .wc.brand .wc-t { font-size:1.4rem; } .wc.brand .wc-d { font-size:.74rem; } }
    @media (max-width:520px) { .wc.brand { grid-template-columns:1fr; } .wc.brand .wc-c, .wc.brand .wc-c[data-k=jp] { text-align:center; } .wc.brand .wc-l, .wc.brand .wc-r, .wc.brand .wc-c[data-k=jp] .wc-l, .wc.brand .wc-c[data-k=jp] .wc-r { justify-content:center; } .wc.brand .wc-mid { order:-1; } }
</style>
<div class="wc<?= $wcBrand ? ' brand' : '' ?>" id="<?= $wcId ?>" role="group" aria-label="Local time and weather in Japan and Sri Lanka">
    <?php foreach ([['jp', 'Japan · Tokyo', 'Asia/Tokyo'], ['lk', 'Sri Lanka · Colombo', 'Asia/Colombo']] as $wcI => [$k, $label, $tz]): ?>
    <?php if ($wcBrand && $wcI === 1): ?><div class="wc-mid"><?= $logo ?? '' ?></div><?php endif; ?>
    <div class="wc-c" data-k="<?= $k ?>" data-tz="<?= $tz ?>">
        <div class="wc-l"><svg viewBox="0 0 24 24" fill="#842484" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg><?= esc($label) ?></div>
        <div class="wc-r">
            <span class="wc-t">--:--</span><span class="wc-s"></span><span class="wc-d">&nbsp;</span>
            <span class="wc-s" hidden></span><span class="wc-w" hidden></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<script>
(function () {
    var root = document.getElementById('<?= $wcId ?>'); if (!root) return;
    var cells = root.querySelectorAll('.wc-c');
    var tick = function () {
        var now = new Date();
        cells.forEach(function (c) {
            var tz = c.dataset.tz;
            c.querySelector('.wc-t').textContent = new Intl.DateTimeFormat('en-GB', { timeZone: tz, hour: '2-digit', minute: '2-digit', hour12: false }).format(now);
            c.querySelector('.wc-d').textContent = new Intl.DateTimeFormat('en-GB', { timeZone: tz, weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }).format(now).replace(/,/g, '').replace(/^(\w+) /, '$1, ');
        });
    };
    tick(); setInterval(tick, 15000);

    // weather icons: sun, moon, cloud with sun/moon, cloud, rain, storm
    var SUN = '<g fill="#f2b632"><circle cx="21" cy="11" r="5"/><g stroke="#f2b632" stroke-width="2" stroke-linecap="round"><path d="M21 1.5v2.5M28 4.5l-1.8 1.8M30.5 11H28M14 4.5l1.8 1.8M12 11h0"/></g></g>';
    var MOON = '<path d="M24 3.5a7 7 0 1 0 6.5 9.6A5.6 5.6 0 0 1 24 3.5z" fill="#f2b632"/>';
    var CLOUD = '<path d="M9 26h17a6 6 0 0 0 .6-12 8 8 0 0 0-15.3 2.2A5 5 0 0 0 9 26z" fill="#fff" stroke="#4a4250" stroke-width="1.8" stroke-linejoin="round"/>';
    var RAIN = '<g stroke="#3b82c4" stroke-width="1.8" stroke-linecap="round"><path d="M12 28.5l-1 2.5M18 28.5l-1 2.5M24 28.5l-1 2.5"/></g>';
    var BOLT = '<path d="M19 26l-3 5h3l-2 4" fill="none" stroke="#f2b632" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>';
    var icon = function (code, day) {
        var c = Number(code), sky = day ? SUN : MOON, body;
        if (c === 0) body = day ? '<g transform="translate(-4 5)">' + SUN + '</g>' : '<g transform="translate(-6 5)">' + MOON + '</g>';
        else if (c <= 2) body = sky + CLOUD;
        else if (c === 3 || c === 45 || c === 48) body = CLOUD;
        else if (c >= 95) body = CLOUD + BOLT;
        else body = CLOUD + RAIN;
        return '<svg viewBox="0 0 34 34" aria-hidden="true">' + body + '</svg>';
    };
    var NAMES = { 0: 'Clear', 1: 'Mainly clear', 2: 'Partly cloudy', 3: 'Cloudy', 45: 'Fog', 48: 'Fog' };
    var paint = function (list) {
        ['jp', 'lk'].forEach(function (k, i) {
            var cur = list[i] && list[i].current; if (!cur || cur.temperature_2m == null) return;
            var c = root.querySelector('.wc-c[data-k="' + k + '"]'), w = c.querySelector('.wc-w');
            var code = cur.weather_code, label = NAMES[code] || (code >= 95 ? 'Thunderstorm' : (code >= 51 ? 'Rain' : 'Cloudy'));
            w.innerHTML = icon(code, cur.is_day) + Math.round(cur.temperature_2m) + '°C';
            w.title = label + ', ' + Math.round(cur.temperature_2m) + '°C';
            w.hidden = false; c.querySelectorAll('.wc-s')[1].hidden = false;
        });
    };
    var KEY = 'avh-wc-weather', TTL = 30 * 60 * 1000;
    var load = function () {
        try { var s = JSON.parse(sessionStorage.getItem(KEY) || 'null'); if (s && Date.now() - s.t < TTL) { paint(s.d); return; } } catch (e) {}
        fetch('https://api.open-meteo.com/v1/forecast?latitude=35.6895,6.9271&longitude=139.6917,79.8612&current=temperature_2m,weather_code,is_day&timezone=auto', { credentials: 'omit', referrerPolicy: 'no-referrer' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (!d) return; var list = Array.isArray(d) ? d : [d]; paint(list); try { sessionStorage.setItem(KEY, JSON.stringify({ t: Date.now(), d: list })); } catch (e) {} })
            .catch(function () { /* times only */ });
    };
    load(); setInterval(load, TTL);
})();
</script>
