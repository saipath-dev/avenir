<?php
/**
 * Shared glass topbar for dashboard2 pages.
 *
 * Usage: <?= view('layouts/topbar') ?>
 * Options (all optional, pass in the view() data array):
 *   $topbar_back   string  Back button target URL. Pass 'close' to use window.close().
 *                          Default: base_url('dashboard')
 *   $topbar_home   bool    Show Home button. Default: true
 *   $topbar_logout bool    Show Logout button. Default: true
 *   $topbar_extra  string  Raw HTML rendered before the Back button (page-specific buttons).
 *   $topbar_class  string  Extra CSS classes on the bar (e.g. 'no-print').
 */
$topbar_back   = $topbar_back   ?? base_url('dashboard');
$topbar_home   = $topbar_home   ?? true;
$topbar_logout = $topbar_logout ?? true;
$topbar_extra  = $topbar_extra  ?? '';
$topbar_class  = $topbar_class  ?? '';
$topbar_back_action = $topbar_back === 'close'
    ? 'window.close()'
    : "window.location.href='" . $topbar_back . "'";
?>
<style>
    .app-topbar {
        background: linear-gradient(90deg,
            rgba(122, 188, 73, 0.22) 0%,
            rgba(255, 255, 255, 0.86) 28%,
            rgba(255, 255, 255, 0.86) 65%,
            rgba(100, 173, 212, 0.24) 100%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        backdrop-filter: blur(18px) saturate(180%);
        border-bottom: 1px solid rgba(44, 62, 80, 0.08);
        box-shadow: 0 2px 16px rgba(44, 62, 80, 0.15);
        padding: 0.5rem 1.5rem;
        z-index: 1030;
    }

    .app-topbar .tb-btn {
        border: none;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #fff;
        transition: filter 0.2s ease, transform 0.2s ease;
    }

    .app-topbar .tb-btn i {
        font-size: 0.8rem;
    }

    .app-topbar .tb-btn:hover {
        filter: brightness(0.92);
        transform: translateY(-1px);
        color: #fff;
    }

    .app-topbar .tb-back { background: #6c757d; }
    .app-topbar .tb-home { background: var(--mantis, #7ABC49); }
    .app-topbar .tb-logout { background: #dc3545; }

    @media (max-width: 767px) {
        .app-topbar { padding: 0.4rem 0.75rem; }

        .app-topbar .tb-btn {
            font-size: 0.7rem;
            padding: 6px 10px;
        }

        .app-topbar .tb-btn span { display: none; }

        .app-topbar .tb-btn i {
            font-size: 0.8rem;
            margin: 0;
        }

        .app-topbar img { width: 90px !important; }
    }
</style>

<style id="av-nav-btn-css">
/* tf-navbtn-r1 (22 Oct 2026): Back / Home / Dashboard - the Tour Inquiry button style (38px, 10px corners, soft shadow), solid colours */
.av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0;
  font-size:13.5px; font-weight:600; letter-spacing:.2px; line-height:1; white-space:nowrap; text-decoration:none; cursor:pointer;
  box-shadow:0 3px 10px rgba(0,0,0,.16); transition:filter .2s ease, box-shadow .2s ease, transform .2s ease; }
.av-nav-btn i { font-size:16px; line-height:1; }
.av-nav-btn:hover { filter:brightness(1.08); box-shadow:0 5px 14px rgba(0,0,0,.22); text-decoration:none; }
.av-nav-btn:active { transform:translateY(1px) scale(.98); }
.av-nav-btn:focus-visible { outline:3px solid rgba(224,165,38,.55); outline-offset:2px; }
.av-nav-back { background:#842484; color:#fff !important; }
.av-nav-home { background:#78b848; color:#2b2b33 !important; }
.av-nav-dash { background:#e0a526; color:#2b2108 !important; }
.av-nav-out  { background:#c62828; color:#fff !important; }
@media (max-width:767px) { .av-nav-btn { height:38px; padding:0 11px; font-size:12.5px; gap:5px; } }
@media (max-width:480px) { .av-nav-btn { width:42px; height:42px; padding:0; } .av-nav-btn i { font-size:18px; } .av-nav-btn span { display:none; } }
</style>
<div class="app-topbar d-flex justify-content-between align-items-center fixed-top <?= $topbar_class ?>">
    <div class="d-flex align-items-center"><?php /* tf-navbar-r1: the shared header bar; Back keeps this page's own target */ ?>
        <?= view('layouts/nav_bar', ['nb_back' => $topbar_back_action, 'nb_home' => $topbar_home, 'nb_dash' => true]) ?>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?= $topbar_extra ?>
        <?= view('layouts/page_search') ?><?php /* tf-search-r1: the shared "Search pages…" box */ ?>
        <?php if ($topbar_logout): ?>
            <button class="av-nav-btn av-nav-out" onclick="logout()" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($topbar_logout): ?>
<script>
    // Fallback logout — a page-level `function logout()` declared later overrides this.
    if (typeof window.logout !== 'function') {
        window.logout = function () {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to logout from the system?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?= base_url() ?>logout",
                        method: "GET",
                        success: function (response) {
                            if (response.status == 200) {
                                localStorage.removeItem('previousNotifications');
                                var is_admin = "<?= session()->get('is_admin') ?>";
                                window.location.href = is_admin == 1
                                    ? "<?= base_url() ?>admin"
                                    : "<?= base_url() ?>";
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            Swal.fire({ title: 'Error!', text: errorThrown, icon: 'error', confirmButtonColor: '#78b848' });
                        }
                    });
                }
            });
        };
    }
</script>
<?php endif; ?>
