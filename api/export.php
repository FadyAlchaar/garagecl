<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/lang.php';
require_once __DIR__.'/../config/auth.php';
requireAuth();

$pdo=db();
$conditions=['1=1']; $params=[];
if(!empty($_GET['plate'])){$conditions[]='r.plate_number LIKE :plate';$params[':plate']='%'.$_GET['plate'].'%';}
if(!empty($_GET['chassis'])){$conditions[]='r.chassis_number LIKE :chassis';$params[':chassis']='%'.$_GET['chassis'].'%';}
if(!empty($_GET['customer'])){$conditions[]='r.customer_name LIKE :cust';$params[':cust']='%'.$_GET['customer'].'%';}
if(!empty($_GET['brand'])){$conditions[]='r.brand LIKE :brand';$params[':brand']='%'.$_GET['brand'].'%';}
if(!empty($_GET['date_from'])){$conditions[]='r.date_inspection>=:dfrom';$params[':dfrom']=$_GET['date_from'];}
if(!empty($_GET['date_to'])){$conditions[]='r.date_inspection<=:dto';$params[':dto']=$_GET['date_to'];}
if(!empty($_GET['status'])&&in_array($_GET['status'],['draft','complete'])){$conditions[]='r.status=:status';$params[':status']=$_GET['status'];}
if(!empty($_GET['fuel_type'])){$conditions[]='r.fuel_type=:fuel';$params[':fuel']=$_GET['fuel_type'];}

$where=implode(' AND ',$conditions);
$stmt=$pdo->prepare("SELECT r.report_number,r.plate_number,r.chassis_number,r.brand,r.model,r.year,r.fuel_type,r.mileage,r.customer_name,r.customer_phone,r.seller_name,r.date_inspection,r.status,d.original_hp,d.measured_hp,d.performance_percent,u.full_name_ar as technician FROM reports r LEFT JOIN dyno_results d ON r.id=d.report_id LEFT JOIN users u ON r.technician_id=u.id WHERE $where ORDER BY r.created_at DESC");
$stmt->execute($params);
$rows=$stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reports_'.date('Ymd').'.csv"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel Arabic support
$out=fopen('php://output','w');
fputcsv($out,['Report No','Plate','Chassis','Brand','Model','Year','Fuel','Mileage','Customer','Customer Phone','Seller','Date','Status','Original HP','Measured HP','Performance %','Technician']);
foreach($rows as $r) fputcsv($out,array_values($r));
fclose($out);
auditLog($_SESSION['user_id']??null,'export_csv','reports',null,'Exported '.count($rows).' records');
