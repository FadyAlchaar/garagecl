<?php
// ============================================================
// APP CONFIGURATION
// *** UPDATE APP_URL to match your XAMPP folder name ***
// ============================================================
define('APP_ROOT',    dirname(__DIR__));
define('APP_FOLDER', 'garagecl'); // <-- change if your folder name differs
(function() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $protocol . '://' . $host . '/' . APP_FOLDER);
})();
define('APP_VERSION', '2.0.0');
define('UPLOAD_DIR',  APP_ROOT . '/assets/uploads/');

// Fuel types
define('FUEL_TYPES', [
    'Petrol'   => ['ar'=>'بنزين',   'en'=>'Petrol'],
    'Diesel'   => ['ar'=>'ديزل',    'en'=>'Diesel'],
    'Electric' => ['ar'=>'كهربائي', 'en'=>'Electric'],
    'Hybrid'   => ['ar'=>'هجين',    'en'=>'Hybrid'],
    'Gas'      => ['ar'=>'غاز',     'en'=>'Gas'],
]);

// Result labels
define('RESULT_LABELS', [
    'good'        => ['ar'=>'جيد',      'en'=>'Good',        'color'=>'#16a34a'],
    'none'        => ['ar'=>'لا يوجد',  'en'=>'None',        'color'=>'#16a34a'],
    'light'       => ['ar'=>'خفيف',     'en'=>'Light',       'color'=>'#ca8a04'],
    'medium'      => ['ar'=>'متوسط',    'en'=>'Medium',      'color'=>'#ea580c'],
    'bad'         => ['ar'=>'سيئ',      'en'=>'Bad',         'color'=>'#dc2626'],
    'not_checked' => ['ar'=>'لم يفحص',  'en'=>'Not Checked', 'color'=>'#6b7280'],
]);

// Panel status
define('PANEL_STATUS', [
    'original'   => ['ar'=>'أصلي',         'en'=>'Original',   'color'=>'#ffffff'],
    'painted'    => ['ar'=>'تم طلاؤه',     'en'=>'Painted',    'color'=>'#3b82f6'],
    'replaced'   => ['ar'=>'تم تبديله',    'en'=>'Replaced',   'color'=>'#ef4444'],
    'repaired'   => ['ar'=>'تم تعديله',    'en'=>'Repaired',   'color'=>'#a855f7'],
    'spot_paint' => ['ar'=>'طلاء موضعي',   'en'=>'Spot Paint', 'color'=>'#eab308'],
    'plastic'    => ['ar'=>'بلاستك',       'en'=>'Plastic',    'color'=>'#9ca3af'],
]);

// Top-level checklist (Page 1 of report)
define('CHECKLIST_ITEMS', [
    ['key'=>'exterior_body',   'ar'=>'فحص الهيكل الخارجي والطلاء',      'en'=>'Exterior Body & Paint'],
    ['key'=>'engine_bay',      'ar'=>'فحص قسم المحرك',                   'en'=>'Engine Bay Inspection'],
    ['key'=>'engine_perf',     'ar'=>'اختبار أداء المحرك',               'en'=>'Engine Performance Test'],
    ['key'=>'brake_system',    'ar'=>'اختبار نظام الفرامل',              'en'=>'Brake System Test'],
    ['key'=>'suspension',      'ar'=>'اختبار نظام التعليق',              'en'=>'Suspension System Test'],
    ['key'=>'lateral_slip',    'ar'=>'اختبار الانزلاق الجانبي',          'en'=>'Lateral Slip Test'],
    ['key'=>'underbody',       'ar'=>'فحص الهيكل السفلي',                'en'=>'Underbody Inspection'],
    ['key'=>'combustion_room', 'ar'=>'فحص غرفة اشتعال المحرك',           'en'=>'Combustion Chamber'],
    ['key'=>'electrical',      'ar'=>'فحص النظام الكهربائي والإلكتروني','en'=>'Electrical & Electronic'],
    ['key'=>'airbag',          'ar'=>'فحص نظام الوسادة الهوائية',        'en'=>'Airbag System'],
    ['key'=>'interior_ext',    'ar'=>'فحص الأقسام الداخلية والخارجية',  'en'=>'Interior & Exterior'],
    ['key'=>'accessories',     'ar'=>'فحص حزمة الإكسسوارات والراحة',    'en'=>'Accessories & Comfort'],
    ['key'=>'warranty',        'ar'=>'حزمة الضمان',                      'en'=>'Warranty Package'],
    ['key'=>'damage_record',   'ar'=>'استعلام سجل الأضرار',              'en'=>'Damage Record Inquiry'],
    ['key'=>'mileage_check',   'ar'=>'استعلام الكيلومتر',                'en'=>'Mileage Verification'],
    ['key'=>'road_test',       'ar'=>'اختبار الطريق',                    'en'=>'Road Test'],
]);

// ============================================================
// HELPERS
// ============================================================
function generateReportNumber(): string {
    $date  = date('Ymd');
    $count = (int)db()->query("SELECT COUNT(*) FROM reports WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    return 'GAR-'.$date.'-'.str_pad($count+1,4,'0',STR_PAD_LEFT);
}

function getSettings(): array {
    return db()->query("SELECT * FROM settings LIMIT 1")->fetch() ?: [];
}

function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(bool $success, array $data=[], string $error=''): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>$success,'data'=>$data,'error'=>$error], JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $path): void {
    $url = rtrim(APP_URL,'/').'/'.ltrim($path,'/');
    header('Location: '.$url);
    exit;
}

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Create upload directory if missing
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
