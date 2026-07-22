<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/lang.php';
require_once __DIR__.'/../config/auth.php';

requireAuth();
if (!isPost()) jsonResponse(false,[],'POST only');

$pdo      = db();
$reportId = (int)($_POST['report_id']??0);

try {
    $pdo->beginTransaction();

    // ---- MAIN REPORT ----
    $fields = [
        'report_number'   => $_POST['report_number']   ?? generateReportNumber(),
        'plate_number'    => $_POST['plate_number']    ?? '',
        'chassis_number'  => $_POST['chassis_number']  ?? '',
        'brand'           => $_POST['brand']           ?? '',
        'model'           => $_POST['model']           ?? '',
        'year'            => $_POST['year']            ?: null,
        'fuel_type'       => $_POST['fuel_type']       ?? 'Petrol',
        'mileage'         => $_POST['mileage']         ?: null,
        'package_type'    => $_POST['package_type']    ?? '',
        'date_inspection' => $_POST['date_inspection'] ?? date('Y-m-d'),
        'time_in'         => $_POST['time_in']         ?: null,
        'time_out'        => $_POST['time_out']        ?: null,
        'license_seen'    => isset($_POST['license_seen'])?(int)$_POST['license_seen']:0,
        'customer_name'   => $_POST['customer_name']   ?? '',
        'customer_phone'  => $_POST['customer_phone']  ?? '',
        'seller_name'     => $_POST['seller_name']     ?? '',
        'seller_phone'    => $_POST['seller_phone']    ?? '',
        'technician_id'   => $_POST['technician_id']   ?: null,
        'status'          => $_POST['status']          ?? 'draft',
    ];

    if ($reportId > 0) {
        $sets = implode(', ', array_map(fn($k)=>"`$k`=:$k", array_keys($fields)));
        $stmt = $pdo->prepare("UPDATE reports SET $sets WHERE id=:id");
        $fields['id'] = $reportId;
        $stmt->execute($fields);
    } else {
        $cols = implode(',', array_map(fn($k)=>"`$k`", array_keys($fields)));
        $vals = implode(',', array_map(fn($k)=>":$k", array_keys($fields)));
        $pdo->prepare("INSERT INTO reports ($cols) VALUES ($vals)")->execute($fields);
        $reportId = (int)$pdo->lastInsertId();
    }

    // ---- CHECKLIST ----
    if (!empty($_POST['checklist'])&&is_array($_POST['checklist'])) {
        $pdo->prepare("DELETE FROM checklist_items WHERE report_id=?")->execute([$reportId]);
        $stmt=$pdo->prepare("INSERT INTO checklist_items (report_id,item_key,result) VALUES (?,?,?)");
        foreach ($_POST['checklist'] as $k=>$v) {
            $stmt->execute([$reportId,$k,in_array($v,['pass','fail','not_checked'])?$v:'not_checked']);
        }
    }

    // ---- DYNO ----
    if (!empty($_POST['dyno'])&&is_array($_POST['dyno'])) {
        $d=$_POST['dyno'];
        $pdo->prepare("DELETE FROM dyno_results WHERE report_id=?")->execute([$reportId]);
        $pdo->prepare("INSERT INTO dyno_results (report_id,original_kw,original_hp,measured_kw,measured_hp,performance_percent) VALUES (?,?,?,?,?,?)")
            ->execute([$reportId,$d['original_kw']?:null,$d['original_hp']?:null,$d['measured_kw']?:null,$d['measured_hp']?:null,$d['performance_percent']?:null]);
    }

    // ---- BRAKES (19 columns, 19 values — bugfix applied) ----
    if (!empty($_POST['brake'])&&is_array($_POST['brake'])) {
        $b=$_POST['brake'];
        $pdo->prepare("DELETE FROM brake_results WHERE report_id=?")->execute([$reportId]);
        $pdo->prepare("INSERT INTO brake_results
            (report_id,
             front_left_force,front_left_status,front_right_force,front_right_status,
             rear_left_force,rear_left_status,rear_right_force,rear_right_status,
             front_deviation_pct,front_deviation_status,rear_deviation_pct,rear_deviation_status,
             handbrake_deviation_pct,handbrake_status,
             slip_front_pct,slip_front_status,slip_rear_pct,slip_rear_status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                $reportId,
                $b['front_left_force']?:null,     $b['front_left_status']??'good',
                $b['front_right_force']?:null,    $b['front_right_status']??'good',
                $b['rear_left_force']?:null,      $b['rear_left_status']??'good',
                $b['rear_right_force']?:null,     $b['rear_right_status']??'good',
                $b['front_deviation_pct']?:null,  $b['front_deviation_status']??'pass',
                $b['rear_deviation_pct']?:null,   $b['rear_deviation_status']??'pass',
                $b['handbrake_deviation_pct']?:null,$b['handbrake_status']??'good',
                $b['slip_front_pct']?:null,       $b['slip_front_status']??'good',
                $b['slip_rear_pct']?:null,        $b['slip_rear_status']??'good',
            ]);
    }

    // ---- SUSPENSION ----
    if (!empty($_POST['suspension'])&&is_array($_POST['suspension'])) {
        $s=$_POST['suspension'];
        $pdo->prepare("DELETE FROM suspension_results WHERE report_id=?")->execute([$reportId]);
        $pdo->prepare("INSERT INTO suspension_results (report_id,front_left_pct,front_left_status,front_right_pct,front_right_status,rear_left_pct,rear_left_status,rear_right_pct,rear_right_status) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$reportId,$s['front_left_pct']?:null,$s['front_left_status']??'good',$s['front_right_pct']?:null,$s['front_right_status']??'good',$s['rear_left_pct']?:null,$s['rear_left_status']??'good',$s['rear_right_pct']?:null,$s['rear_right_status']??'good']);
    }

    // ---- INSPECTION CHECKS ----
    if (!empty($_POST['ic'])&&is_array($_POST['ic'])) {
        $pdo->prepare("DELETE FROM inspection_checks WHERE report_id=?")->execute([$reportId]);
        $stmt=$pdo->prepare("INSERT INTO inspection_checks (report_id,section,item_key,result,notes) VALUES (?,?,?,?,?)");
        $valid=['good','none','light','medium','bad','not_checked'];
        foreach ($_POST['ic'] as $section=>$items) {
            if (!is_array($items)) continue;
            foreach ($items as $key=>$data) {
                $r=$data['result']??'none';
                $stmt->execute([$reportId,$section,$key,in_array($r,$valid)?$r:'none',$data['notes']??'']);
            }
        }
    }

    // ---- BODY PANELS ----
    if (!empty($_POST['body'])&&is_array($_POST['body'])) {
        $pdo->prepare("DELETE FROM body_panels WHERE report_id=?")->execute([$reportId]);
        $stmt=$pdo->prepare("INSERT INTO body_panels (report_id,panel_key,status,has_disassembly,has_foil,has_damage) VALUES (?,?,?,?,?,?)");
        $vs=['original','painted','replaced','repaired','spot_paint','plastic'];
        foreach ($_POST['body'] as $pk=>$data) {
            $stmt->execute([$reportId,$pk,in_array($data['status']??'',$vs)?$data['status']:'original',isset($data['has_disassembly'])?1:0,isset($data['has_foil'])?1:0,isset($data['has_damage'])?1:0]);
        }
    }

    // ---- NOTES ----
    if (!empty($_POST['notes'])&&is_array($_POST['notes'])) {
        $pdo->prepare("DELETE FROM section_notes WHERE report_id=?")->execute([$reportId]);
        $stmt=$pdo->prepare("INSERT INTO section_notes (report_id,section,note_text) VALUES (?,?,?)");
        foreach ($_POST['notes'] as $section=>$text) {
            if (trim($text)==='') continue;
            $stmt->execute([$reportId,$section,$text]);
        }
    }

    $pdo->commit();
    auditLog($_SESSION['user_id']??null,'report_save','report',$reportId,'Report saved');
    jsonResponse(true,['report_id'=>$reportId]);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false,[],'Database error: '.$e->getMessage());
}
