<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/modules/dashboard.php');
    exit;
}

// Language toggle
if (isset($_GET['lang'])) {
    setLang($_GET['lang']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$error    = '';
$redirect = clean($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user     = attemptLogin($username, $password);

    if ($user) {
        $dest = $redirect ?: APP_URL . '/modules/dashboard.php';
        header('Location: ' . $dest);
        exit;
    } else {
        $error = t('login_error');
    }
}

$settings = getSettings();
$lang     = currentLang();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= langDir() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('login_title') ?> — <?= clean($settings['shop_name_ar'] ?? 'نظام الفحص') ?></title>
    <style>
        @font-face { font-family:'tajawal'; src:url('<?= APP_URL ?>/assets/fonts/tajawal-Regular.ttf') format('truetype'); }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'tajawal','Segoe UI',sans-serif; background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; direction:<?= langDir() ?>; }
        .card { background:#fff; border-radius:16px; padding:2.5rem 2rem; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.4); }
        .logo-wrap { text-align:center; margin-bottom:1.5rem; }
        .logo-wrap img { max-height:80px; max-width:180px; object-fit:contain; }
        .shop-name { font-size:1.3rem; font-weight:700; color:#1a1a2e; margin-top:.5rem; }
        .shop-name-en { font-size:.85rem; color:#6b7280; margin-top:.2rem; }
        h2 { font-size:1.1rem; color:#374151; text-align:center; margin-bottom:1.5rem; }
        .form-group { margin-bottom:1.1rem; }
        .form-group label { display:block; margin-bottom:.4rem; font-size:.95rem; font-weight:600; color:#374151; }
        input[type=text], input[type=password] { width:100%; padding:.65rem .9rem; border:1px solid #d1d5db; border-radius:8px; font-family:inherit; font-size:1rem; direction:ltr; transition:border-color .15s, box-shadow .15s; }
        input:focus { outline:none; border-color:#c0392b; box-shadow:0 0 0 3px rgba(192,57,43,.12); }
        .btn-login { width:100%; padding:.75rem; background:#c0392b; color:#fff; border:none; border-radius:8px; font-family:inherit; font-size:1.05rem; font-weight:700; cursor:pointer; transition:background .15s; margin-top:.5rem; }
        .btn-login:hover { background:#a93226; }
        .error { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.9rem; text-align:center; }
        .lang-switch { text-align:center; margin-top:1.5rem; font-size:.85rem; }
        .lang-switch a { color:#6b7280; text-decoration:none; margin:0 .5rem; padding:.2rem .6rem; border-radius:4px; border:1px solid #e5e7eb; }
        .lang-switch a.active { background:#1a1a2e; color:#fff; border-color:#1a1a2e; }
        .default-hint { background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:.6rem .9rem; margin-top:1rem; font-size:.8rem; color:#0369a1; text-align:center; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-wrap">
        <?php if (!empty($settings['logo_path']) && file_exists(APP_ROOT . '/' . $settings['logo_path'])): ?>
            <img src="<?= APP_URL . '/' . $settings['logo_path'] ?>" alt="Logo">
        <?php else: ?>
            <div style="font-size:3rem">🔧</div>
        <?php endif; ?>
        <div class="shop-name"><?= clean($settings['shop_name_ar'] ?? 'نظام فحص السيارات') ?></div>
        <div class="shop-name-en"><?= clean($settings['shop_name_en'] ?? 'Car Inspection System') ?></div>
    </div>

    <h2><?= t('login_title') ?></h2>

    <?php if ($error): ?>
    <div class="error"><?= clean($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="username"><?= t('login_username') ?></label>
            <input type="text" id="username" name="username"
                   value="<?= clean($_POST['username'] ?? '') ?>"
                   autocomplete="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password"><?= t('login_password') ?></label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-login"><?= t('login_btn') ?></button>
    </form>

    <!-- <div class="default-hint">
        Default: <strong>admin</strong> / <strong>Admin@1234</strong> — change after login
    </div>
 -->
    <div class="lang-switch">
        <a href="?lang=ar" class="<?= $lang==='ar'?'active':'' ?>">العربية</a>
        <a href="?lang=en" class="<?= $lang==='en'?'active':'' ?>">English</a>
    </div>
</div>
</body>
</html>
