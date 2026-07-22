<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/../../config/lang.php';
require_once __DIR__.'/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Token auth
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_GET['token'] ?? '');
$token = str_replace('Bearer ','',$token);
$apiUser = validateApiToken($token);
if(!$apiUser){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$path   = trim($_GET['path']??'','/');
$parts  = explode('/',$path);
$resource = $parts[0]??'';
$id       = isset($parts[1])?(int)$parts[1]:0;

switch($resource){
    case 'reports':
        if($method==='GET'){
            if($id){
                $s=$pdo->prepare("SELECT * FROM reports WHERE id=?");$s->execute([$id]);
                echo json_encode($s->fetch()?:['error'=>'Not found'],JSON_UNESCAPED_UNICODE);
            } else {
                $limit=(int)($_GET['limit']??50); $page=(int)($_GET['page']??1);
                $offset=($page-1)*$limit;
                $rows=$pdo->query("SELECT id,report_number,plate_number,brand,model,status,date_inspection FROM reports ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
                $total=(int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
                echo json_encode(['data'=>$rows,'total'=>$total,'page'=>$page,'limit'=>$limit],JSON_UNESCAPED_UNICODE);
            }
        } break;
    case 'vehicles':
        $plate=trim($_GET['plate']??'');
        if($plate){
            $s=$pdo->prepare("SELECT * FROM reports WHERE plate_number=? OR chassis_number=? ORDER BY date_inspection DESC");
            $s->execute([$plate,$plate]);
            echo json_encode(['data'=>$s->fetchAll()],JSON_UNESCAPED_UNICODE);
        } else { echo json_encode(['error'=>'plate parameter required']); }
        break;
    case 'stats':
        echo json_encode([
            'total'    =>(int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
            'complete' =>(int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='complete'")->fetchColumn(),
            'this_month'=>(int)$pdo->query("SELECT COUNT(*) FROM reports WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn(),
            'avg_perf' =>round((float)$pdo->query("SELECT AVG(performance_percent) FROM dyno_results")->fetchColumn(),1),
        ],JSON_UNESCAPED_UNICODE);
        break;
    default:
        echo json_encode(['endpoints'=>['GET /api/v1/?path=reports','GET /api/v1/?path=reports/{id}','GET /api/v1/?path=vehicles&plate=XXX','GET /api/v1/?path=stats']]);
}
