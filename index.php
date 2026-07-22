<?php
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/app.php';
require_once __DIR__.'/config/lang.php';
require_once __DIR__.'/config/auth.php';
if(isLoggedIn()) header('Location: '.APP_URL.'/modules/dashboard.php');
else header('Location: '.APP_URL.'/modules/login.php');
exit;
