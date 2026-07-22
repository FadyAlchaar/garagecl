<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/lang.php';
require_once __DIR__.'/../config/auth.php';
requireAuth();
if(!isPost()) jsonResponse(false,[],'POST only');
$id=(int)($_POST['image_id']??0);
$stmt=db()->prepare("SELECT * FROM report_images WHERE id=?");
$stmt->execute([$id]); $img=$stmt->fetch();
if(!$img) jsonResponse(false,[],'Image not found');
if(file_exists(APP_ROOT.'/'.$img['filename'])) unlink(APP_ROOT.'/'.$img['filename']);
db()->prepare("DELETE FROM report_images WHERE id=?")->execute([$id]);
auditLog($_SESSION['user_id']??null,'image_delete','report',$img['report_id'],"Deleted image #$id");
jsonResponse(true,[]);
