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
<style>
<?php
// Dynamic @font-face using APP_URL so fonts load correctly on LAN and localhost
$fontBase = APP_URL . '/assets/fonts/';
$fontDir  = APP_ROOT . '/assets/fonts/';
$hasR = file_exists($fontDir . 'tajawal-Regular.ttf');
$hasB = file_exists($fontDir . 'tajawal-Bold.ttf');
if ($hasR): ?>
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
</head>
<body>

<?php
// Language toggle via GET
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
$settings = getSettings();
?>

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
        <li><a href="<?= APP_URL ?>/modules/settings.php"
               class="<?= strpos($_SERVER['PHP_SELF'],'settings')!==false?'active':'' ?>">
            <span class="ar">الإعدادات</span><span class="en">Settings</span>
        </a></li>

        <!-- Divider -->
        <li style="border-top:1px solid rgba(255,255,255,.12);margin-top:.25rem;padding-top:.25rem">
            <!-- Language toggle -->
            <a href="?lang=<?= currentLang()==='ar'?'en':'ar' ?>" style="opacity:.8">
                <span style="font-size:1rem"><?= currentLang()==='ar'?'🇬🇧 English':'🇸🇦 العربية' ?></span>
            </a>
        </li>

        <li>
            <a href="<?= APP_URL ?>/modules/logout.php" style="color:#fca5a5">
                <span class="ar">🚪 تسجيل الخروج</span><span class="en">Logout</span>
                <?php $u = getCurrentUser(); if($u): ?>
                <span style="font-size:.72rem;opacity:.7;display:block">
                    <?= clean($u['full_name_ar'] ?? $u['username'] ?? '') ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
</nav>

<main class="main-content">
