<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/lang.php';
require_once __DIR__.'/../config/auth.php';
requireAuth();
if(!isPost()) jsonResponse(false,[],'POST only');

$reportId=(int)($_POST['report_id']??0);
$section=clean($_POST['section']??'general');
$caption=clean($_POST['caption']??'');
if(!$reportId) jsonResponse(false,[],'Invalid report ID');
if(empty($_FILES['image'])) jsonResponse(false,[],'No file uploaded');

$file=$_FILES['image'];
$allowed=['image/jpeg','image/png','image/gif','image/webp'];
$type=mime_content_type($file['tmp_name']);
if(!in_array($type,$allowed)) jsonResponse(false,[],'Invalid file type. Allowed: JPG, PNG, GIF, WEBP');
if($file['size']>5*1024*1024) jsonResponse(false,[],'File too large. Max 5MB');

$dir=UPLOAD_DIR.'reports/'.$reportId.'/';
if(!is_dir($dir)) mkdir($dir,0755,true);
$ext=pathinfo($file['name'],PATHINFO_EXTENSION);
$filename=uniqid('img_').'.'.$ext;
if(!move_uploaded_file($file['tmp_name'],$dir.$filename)) jsonResponse(false,[],'Upload failed');

$path='assets/uploads/reports/'.$reportId.'/'.$filename;
db()->prepare("INSERT INTO report_images (report_id,section,filename,original_name,mime_type,file_size,caption,uploaded_by) VALUES (?,?,?,?,?,?,?,?)")
   ->execute([$reportId,$section,$path,$file['name'],$type,$file['size'],$caption,$_SESSION['user_id']??null]);

auditLog($_SESSION['user_id']??null,'image_upload','report',$reportId,"Uploaded: $filename");
jsonResponse(true,['path'=>APP_URL.'/'.$path,'filename'=>$filename]);
