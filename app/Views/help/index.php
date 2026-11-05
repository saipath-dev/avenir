<?php /** tf-help-r1 — the User Manual as a page of its own (the same help, opened full width). */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Manual — Avenir Holidays</title>
<link rel="stylesheet" href="<?= htmlspecialchars($base . 'assets/help/help-widget.css?v=' . $version, ENT_QUOTES) ?>">
<style>body{margin:0;background:#f6f3f8;font-family:system-ui,"Segoe UI",Roboto,Arial,sans-serif;color:#241d2b}
.avh-pagebar{display:flex;align-items:center;gap:12px;padding:10px 18px;background:#fff;border-bottom:1px solid #e6dcef}
.avh-pagebar a{color:#4b2a5c;text-decoration:none;font-weight:600}</style>
</head>
<body>
<div class="avh-pagebar"><a href="<?= htmlspecialchars($base . 'dashboard', ENT_QUOTES) ?>">← Back to the system</a></div>
<script src="<?= htmlspecialchars($base . 'assets/help/help-widget.js?v=' . $version, ENT_QUOTES) ?>" data-base="<?= htmlspecialchars($base, ENT_QUOTES) ?>" data-path="help" data-mode="page" defer></script>
</body>
</html>
