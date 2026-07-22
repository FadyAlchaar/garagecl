<?php
// ============================================================
// 1. LOAD ALL CORE CONFIGURATIONS FIRST
// ============================================================
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

// ============================================================
// 2. SESSION (if not already started)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// 3. LANGUAGE TOGGLE
// ============================================================
if (isset($_GET['lang'])) {
    setLang($_GET['lang']);
    // Redirect without lang param
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    $qs = http_build_query($params);
    header('Location: ' . $url . ($qs ? '?' . $qs : ''));
    exit;
}

// ============================================================
// 4. WORKSHOP SETTINGS
// ============================================================
$settings = getSettings();
$currentLang = currentLang();
$user = getCurrentUser();

// ============================================================
// 5. START HTML OUTPUT
// ============================================================
?>
<!DOCTYPE html>
<html lang="<?= langAttr() ?>" dir="<?= langDir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#1a1a2e">
<title><?= isset($pageTitle) ? clean($pageTitle).' — ' : '' ?>نظام فحص السيارات</title>

<?php
// Dynamic @font-face using APP_URL so fonts load correctly on LAN and localhost
$fontBase = APP_URL . '/assets/fonts/';
$fontDir  = APP_ROOT . '/assets/fonts/';
$hasR = file_exists($fontDir . 'tajawal-Regular.ttf');
$hasB = file_exists($fontDir . 'tajawal-Bold.ttf');
?>
<style>
<?php if ($hasR): ?>
@font-face {
    font-family: 'tajawal';
    src: url('<?= $fontBase ?>tajawal-Regular.ttf') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
<?php if ($hasB): ?>
@font-face {
    font-family: 'tajawal';
    src: url('<?= $fontBase ?>tajawal-Bold.ttf') format('truetype');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
<?php else: ?>
@font-face {
    font-family: 'tajawal';
    src: url('<?= $fontBase ?>tajawal-Regular.ttf') format('truetype');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
<?php endif; ?>
<?php endif; ?>
:root {
    --font-ar: <?= $hasR ? "'tajawal', 'Arial', sans-serif" : "'Arial', sans-serif" ?>;
    --font-en: 'Segoe UI', 'Arial', sans-serif;
}
</style>

<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<script src="<?= APP_URL ?>/assets/js/chart.min.js"></script>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<script>
// Global toast function
function toast(message, type, duration) {
    type = type || 'info';
    duration = duration || 4000;
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

    const toastEl = document.createElement('div');
    toastEl.className = 'toast toast-' + type;
    toastEl.innerHTML = '<span class="toast-icon">' + (icons[type] || 'ℹ️') + '</span>' +
        '<span class="toast-body">' + message + '</span>' +
        '<button class="toast-close" onclick="this.closest(\'.toast\').remove()">✕</button>';

    container.appendChild(toastEl);

    const timer = setTimeout(function() {
        toastEl.classList.add('hide');
        setTimeout(function() { toastEl.remove(); }, 400);
    }, duration);

    toastEl.addEventListener('click', function() {
        clearTimeout(timer);
        toastEl.classList.add('hide');
        setTimeout(function() { toastEl.remove(); }, 400);
    });
}

function toastSuccess(msg) { toast(msg, 'success'); }
function toastError(msg) { toast(msg, 'error'); }
function toastWarning(msg) { toast(msg, 'warning'); }
function toastInfo(msg) { toast(msg, 'info'); }
</script>

</head>
<body>

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<nav class="navbar">
    <!-- Brand -->
    <div class="navbar-brand">
        <?php if (!empty($settings['logo_path']) && file_exists(APP_ROOT.'/'.$settings['logo_path'])): ?>
        <img src="<?= APP_URL.'/'.$settings['logo_path'] ?>" alt="Logo" class="nav-logo">
        <?php endif; ?>
        <div class="nav-title">
            <span class="nav-title-ar"><?= clean($settings['shop_name_ar'] ?? 'نظام الفحص') ?></span>
            <span class="nav-title-en"><?= clean($settings['shop_name_en'] ?? 'Inspection System') ?></span>
        </div>
    </div>

    <!-- Hamburger (mobile only) -->
    <button class="nav-hamburger" id="navHamburger" aria-label="Toggle menu" onclick="toggleNav()">
        <span></span><span></span><span></span>
    </button>

    <!-- Menu -->
    <ul class="navbar-menu" id="navMenu">
        <li><a href="<?= APP_URL ?>/modules/dashboard.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'dashboard')!==false?'active':'' ?>">
            <span class="ar">لوحة التحكم</span><span class="en">Dashboard</span>
        </a></li>
        <li><a href="<?= APP_URL ?>/modules/report-new.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'report-new')!==false?'active':'' ?>">
            <span class="ar">+ تقرير جديد</span><span class="en">New Report</span>
        </a></li>
        <li><a href="<?= APP_URL ?>/modules/search.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'search')!==false?'active':'' ?>">
            <span class="ar">بحث متقدم</span><span class="en">Search</span>
        </a></li>
        <li><a href="<?= APP_URL ?>/modules/vehicle-history.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'vehicle-history')!==false?'active':'' ?>">
            <span class="ar">سجل المركبات</span><span class="en">History</span>
        </a></li>
        <li><a href="<?= APP_URL ?>/modules/statistics.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'statistics')!==false?'active':'' ?>">
            <span class="ar">الإحصائيات</span><span class="en">Statistics</span>
        </a></li>
        <?php if (hasRole('admin')): ?>
        <li><a href="<?= APP_URL ?>/modules/users.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'users')!==false?'active':'' ?>">
            <span class="ar">المستخدمون</span><span class="en">Users</span>
        </a></li>
        <?php endif; ?>
        <li><a href="<?= APP_URL ?>/modules/customers.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'customers')!==false?'active':'' ?>">
            <span class="ar">العملاء</span><span class="en">Customers</span>
        </a></li>
        <li><a href="<?= APP_URL ?>/modules/settings.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'settings')!==false?'active':'' ?>">
            <span class="ar">الإعدادات</span><span class="en">Settings</span>
        </a></li>

        <!-- Divider -->
        <!-- <li style="border-top:1px solid rgba(255,255,255,.12);margin-top:.25rem;padding-top:.25rem">
            <a href="?lang=<?= $currentLang === 'ar' ? 'en' : 'ar' ?>" style="opacity:.8">
                <span style="font-size:1rem"><?= $currentLang === 'ar' ? '🇬🇧 English' : '🇸🇦 العربية' ?></span>
            </a>
        </li> -->

        <li>
            <a href="<?= APP_URL ?>/modules/logout.php" style="color:#fca5a5">
                <span class="ar">🚪 تسجيل الخروج</span><span class="en">Logout</span>
                <?php if ($user): ?>
                <span style="font-size:.72rem;opacity:.7;display:block">
                    <?= clean($user['full_name_ar'] ?? $user['username'] ?? '') ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
</nav>

<main class="main-content">

<!-- ============================================================
     MOBILE NAV TOGGLE SCRIPT
     ============================================================ -->
<script>
function toggleNav() {
    const menu = document.getElementById('navMenu');
    const hamburger = document.getElementById('navHamburger');
    if (menu) {
        menu.classList.toggle('active');
    }
    if (hamburger) {
        hamburger.classList.toggle('active');
    }
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('navMenu');
    const hamburger = document.getElementById('navHamburger');
    if (menu && hamburger) {
        const isClickInsideMenu = menu.contains(event.target);
        const isClickOnHamburger = hamburger.contains(event.target);
        if (!isClickInsideMenu && !isClickOnHamburger && menu.classList.contains('active')) {
            menu.classList.remove('active');
        }
    }
});
</script>

<style>
/* Mobile: Ensure hamburger is visible and menu toggles */
@media (max-width: 768px) {
    .navbar-menu {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #1a1a2e;
        padding: 10px 0;
        position: absolute;
        top: 60px;
        left: 0;
        right: 0;
        z-index: 999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .navbar-menu.active {
        display: flex;
    }
    .navbar-menu li {
        text-align: center;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .navbar-menu li:last-child {
        border-bottom: none;
    }
    .navbar-menu a {
        color: #fff;
        text-decoration: none;
        font-size: 16px;
        padding: 8px 16px;
        display: block;
    }
    .navbar-menu a:hover {
        background: rgba(255,255,255,0.1);
    }
    .nav-hamburger {
        display: flex !important;
        flex-direction: column;
        gap: 4px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px 12px;
    }
    .nav-hamburger span {
        display: block;
        width: 28px;
        height: 3px;
        background: white;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .nav-hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    .nav-hamburger.active span:nth-child(2) {
        opacity: 0;
    }
    .nav-hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }
}
@media (min-width: 769px) {
    .nav-hamburger {
        display: none !important;
    }
    .navbar-menu {
        display: flex !important;
        position: static !important;
        box-shadow: none !important;
        background: transparent !important;
        padding: 0 !important;
        border-top: none !important;
    }
    .navbar-menu li {
        border-bottom: none !important;
        padding: 0 !important;
    }
    .navbar-menu a {
        padding: 8px 16px !important;
    }
}
</style>