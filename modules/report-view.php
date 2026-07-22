<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php'; // NEW
requireAuth();

$reportId = (int)($_GET['id'] ?? 0);
if (!$reportId) { redirect('modules/dashboard.php'); }

$pdo  = db();
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();
if (!$report) { redirect('modules/dashboard.php'); }

// Load all related data
$stmt = $pdo->prepare("SELECT * FROM checklist_items WHERE report_id = ?");
$stmt->execute([$reportId]);
$checklistRows = $stmt->fetchAll();
$checklist = [];
foreach ($checklistRows as $r) { $checklist[$r['item_key']] = $r['result']; }

$stmt = $pdo->prepare("SELECT * FROM dyno_results WHERE report_id = ?");
$stmt->execute([$reportId]);
$dyno = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM brake_results WHERE report_id = ?");
$stmt->execute([$reportId]);
$brakes = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM suspension_results WHERE report_id = ?");
$stmt->execute([$reportId]);
$suspension = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM inspection_checks WHERE report_id = ?");
$stmt->execute([$reportId]);
$icRows = $stmt->fetchAll();
$ic = [];
foreach ($icRows as $r) { $ic[$r['section']][$r['item_key']] = $r; }

$stmt = $pdo->prepare("SELECT * FROM body_panels WHERE report_id = ?");
$stmt->execute([$reportId]);
$panelRows = $stmt->fetchAll();
$panels = [];
foreach ($panelRows as $r) { $panels[$r['panel_key']] = $r; }

$stmt = $pdo->prepare("SELECT * FROM section_notes WHERE report_id = ?");
$stmt->execute([$reportId]);
$noteRows = $stmt->fetchAll();
$notes = [];
foreach ($noteRows as $r) { $notes[$r['section']] = $r['note_text']; }

$pageTitle = 'تقرير ' . $report['report_number'];

// Result label helper (now using central dict)
function resultBadge(string $result): string {
    $labels = CHECKLIST_STATUS; // or RESULT_OPTIONS depending on context
    // For inspection checks we use RESULT_OPTIONS
    // But the view uses CHECKLIST_STATUS only for the top-level checklist
    // We'll use a hybrid: for checklist items use CHECKLIST_STATUS, for others we can use RESULT_OPTIONS
    // For simplicity, we'll use the central RESULT_OPTIONS for all.
    $options = RESULT_OPTIONS;
    if (isset($options[$result])) {
        $color = match($result) {
            'good','none' => '#16a34a',
            'light'       => '#ca8a04',
            'medium'      => '#ea580c',
            'bad'         => '#dc2626',
            'not_checked' => '#6b7280',
            default       => '#6b7280',
        };
        return '<span style="color:' . $color . ';font-weight:700">'
             . $options[$result]['ar'] . ' / ' . $options[$result]['en'] . '</span>';
    }
    return '<span style="color:#6b7280;">—</span>';
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- The rest of report-view.php stays the same EXCEPT for the inspection check items and body panels -->
<div class="page-header">
    <h1>
        <span class="ar">تقرير الفحص</span>
        <span class="en">Inspection Report</span>
        <span style="color:var(--primary); font-size:1rem; display:block; margin-top:4px">
            <?= clean($report['report_number']) ?>
        </span>
    </h1>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/modules/report-new.php?id=<?= $reportId ?>"
           class="btn btn-outline">✏️ <span class="ar">تعديل</span>&nbsp;<span class="en">Edit</span></a>
        <a href="<?= APP_URL ?>/pdf/generate.php?id=<?= $reportId ?>"
           target="_blank" class="btn btn-primary btn-lg">
           🖨️ <span class="ar">طباعة PDF</span>&nbsp;<span class="en">Print PDF</span>
        </a>
        <a href="<?= APP_URL ?>/modules/notify.php?id=<?= $reportId ?>"
           class="btn btn-secondary">
           📨 <span class="ar">إرسال إشعار</span>&nbsp;<span class="en">Send Notification</span>
        </a>
        <a href="<?= APP_URL ?>/modules/dashboard.php" class="btn btn-secondary">
           ← <span class="ar">العودة</span>&nbsp;<span class="en">Back</span>
        </a>
    </div>
</div>

<!-- VEHICLE INFO -->
<div class="grid-2">
    <div class="card">
        <div class="card-header"><span class="ar">معلومات السيارة</span><span class="en">Vehicle Information</span></div>
        <table style="width:100%;border-collapse:collapse">
            <?php
            $vfields = [
                ['ar'=>'رقم اللوحة',    'en'=>'Plate',    'val'=>$report['plate_number']],
                ['ar'=>'رقم الشاسيه',   'en'=>'Chassis',  'val'=>$report['chassis_number']],
                ['ar'=>'الماركة',       'en'=>'Brand',    'val'=>$report['brand']],
                ['ar'=>'الموديل',       'en'=>'Model',    'val'=>$report['model']],
                ['ar'=>'سنة الصنع',     'en'=>'Year',     'val'=>$report['year']],
                ['ar'=>'نوع الوقود',    'en'=>'Fuel',     'val'=>$report['fuel_type']],
                ['ar'=>'الكيلومتر',     'en'=>'Mileage',  'val'=>$report['mileage'] ? number_format($report['mileage']) . ' KM' : ''],
            ];
            foreach ($vfields as $f): ?>
            <tr>
                <td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f0f0f0;color:var(--text-muted);width:50%">
                    <span class="ar"><?= $f['ar'] ?></span> / <span class="en"><?= $f['en'] ?></span>
                </td>
                <td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f0f0f0;font-weight:600">
                    <?= clean($f['val'] ?? '—') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <div class="card-header"><span class="ar">معلومات التقييم</span><span class="en">Assessment Info</span></div>
        <table style="width:100%;border-collapse:collapse">
            <?php
            $mfields = [
                ['ar'=>'رقم التقرير',       'en'=>'Report No',    'val'=>$report['report_number']],
                ['ar'=>'التاريخ',            'en'=>'Date',         'val'=>$report['date_inspection']],
                ['ar'=>'ساعة الدخول',       'en'=>'Time In',      'val'=>$report['time_in']],
                ['ar'=>'ساعة الخروج',       'en'=>'Time Out',     'val'=>$report['time_out']],
                ['ar'=>'نوع الباقة',         'en'=>'Package',      'val'=>$report['package_type']],
                ['ar'=>'هل رأيت الرخصة؟',  'en'=>'License Seen', 'val'=>$report['license_seen'] ? 'نعم / Yes' : 'لا / No'],
                ['ar'=>'اسم المشتري',        'en'=>'Customer',     'val'=>$report['customer_name']],
                ['ar'=>'اسم البائع',         'en'=>'Seller',       'val'=>$report['seller_name']],
            ];
            foreach ($mfields as $f): ?>
            <tr>
                <td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f0f0f0;color:var(--text-muted);width:50%">
                    <span class="ar"><?= $f['ar'] ?></span> / <span class="en"><?= $f['en'] ?></span>
                </td>
                <td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f0f0f0;font-weight:600">
                    <?= clean($f['val'] ?? '—') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- CHECKLIST -->
<div class="card">
    <div class="card-header"><span class="ar">الفحوصات الداخلة في التقييم</span><span class="en">Inspection Checklist</span></div>
    <div class="checklist-grid">
        <?php foreach (CHECKLIST_ITEMS as $item): ?>
        <?php $result = $checklist[$item['key']] ?? 'not_checked'; ?>
        <div class="checklist-item">
            <div class="item-name">
                <div class="ar"><?= $item['ar'] ?></div>
                <div class="en"><?= $item['en'] ?></div>
            </div>
            <div>
                <?php if ($result === 'pass'): ?>
                    <span style="font-size:1.3rem">✅</span>
                <?php elseif ($result === 'fail'): ?>
                    <span style="font-size:1.3rem">❌</span>
                <?php else: ?>
                    <span style="font-size:1.3rem;color:var(--text-muted)">—</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- DYNO -->
<?php if (!empty($dyno)): ?>
<div class="card">
    <div class="card-header"><span class="ar">نتيجة اختبار أداء المحرك</span><span class="en">Engine Performance</span></div>
    <div style="display:grid;grid-template-columns:1fr auto;gap:2rem;align-items:center">
        <table style="border-collapse:collapse;width:100%">
            <tr style="background:var(--accent);color:#fff">
                <th style="padding:0.5rem 1rem;text-align:right"></th>
                <th style="padding:0.5rem 1rem">KW</th>
                <th style="padding:0.5rem 1rem">HP</th>
            </tr>
            <tr>
                <td style="padding:0.5rem 1rem;border-bottom:1px solid #eee">
                    <span class="ar">القوة الأصلية</span> / <span class="en">Original Power</span>
                </td>
                <td style="padding:0.5rem 1rem;border-bottom:1px solid #eee;font-weight:700"><?= $dyno['original_kw'] ?? '—' ?></td>
                <td style="padding:0.5rem 1rem;border-bottom:1px solid #eee;font-weight:700"><?= $dyno['original_hp'] ?? '—' ?></td>
            </tr>
            <tr>
                <td style="padding:0.5rem 1rem">
                    <span class="ar">القوة المقاسة</span> / <span class="en">Measured Power</span>
                </td>
                <td style="padding:0.5rem 1rem;font-weight:700;color:var(--primary)"><?= $dyno['measured_kw'] ?? '—' ?></td>
                <td style="padding:0.5rem 1rem;font-weight:700;color:var(--primary)"><?= $dyno['measured_hp'] ?? '—' ?></td>
            </tr>
        </table>
        <div style="text-align:center;min-width:140px">
            <?php $pct = (float)($dyno['performance_percent'] ?? 0); ?>
            <div style="font-size:3rem;font-weight:800;color:<?= $pct >= 80 ? 'var(--good)' : ($pct >= 60 ? 'var(--warning)' : 'var(--danger)') ?>">
                <?= number_format($pct, 1) ?>%
            </div>
            <div><span class="ar">الأداء الآني</span> / <span class="en">Performance</span></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- BRAKES & SUSPENSION -->
<?php if (!empty($brakes) || !empty($suspension)): ?>
<div class="grid-2">
    <?php if (!empty($brakes)): ?>
    <div class="card">
        <div class="card-header"><span class="ar">نظام الفرامل</span><span class="en">Brake System</span></div>
        <?php
        $bw = [
            ['ar'=>'يسار أمامي','en'=>'Front Left', 's'=>'front_left'],
            ['ar'=>'يمين أمامي','en'=>'Front Right','s'=>'front_right'],
            ['ar'=>'يسار خلفي', 'en'=>'Rear Left',  's'=>'rear_left'],
            ['ar'=>'يمين خلفي', 'en'=>'Rear Right', 's'=>'rear_right'],
        ];
        ?>
        <div class="grid-2" style="gap:0.5rem">
        <?php foreach ($bw as $w): ?>
        <?php $st = $brakes[$w['s'].'_status'] ?? 'good'; ?>
        <div class="wheel-box <?= $st ?>">
            <div class="wlbl"><span class="ar"><?= $w['ar'] ?></span></div>
            <div class="wval"><?= $brakes[$w['s'].'_force'] ?? '—' ?>%</div>
            <div style="font-size:0.75rem;color:<?= $st==='good'?'var(--good)':($st==='warning'?'var(--warning)':'var(--danger)') ?>;font-weight:700">
                <?= $st === 'good' ? 'جيد' : ($st === 'warning' ? 'تحذير' : 'فشل') ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <table style="width:100%;border-collapse:collapse;margin-top:1rem;font-size:0.85rem">
            <?php
            $bExtra = [
                ['ar'=>'انحراف أمامي','en'=>'Front Dev','val'=>$brakes['front_deviation_pct'],'unit'=>'%'],
                ['ar'=>'انحراف خلفي', 'en'=>'Rear Dev', 'val'=>$brakes['rear_deviation_pct'], 'unit'=>'%'],
                ['ar'=>'فرام اليد',   'en'=>'Handbrake','val'=>$brakes['handbrake_deviation_pct'],'unit'=>'%'],
                ['ar'=>'انزلاق أمامي','en'=>'Slip Front','val'=>$brakes['slip_front_pct'],'unit'=>'%'],
                ['ar'=>'انزلاق خلفي', 'en'=>'Slip Rear', 'val'=>$brakes['slip_rear_pct'], 'unit'=>'%'],
            ];
            foreach ($bExtra as $be): ?>
            <tr>
                <td style="padding:0.3rem;border-bottom:1px solid #f0f0f0;color:var(--text-muted)">
                    <?= $be['ar'] ?> / <?= $be['en'] ?>
                </td>
                <td style="padding:0.3rem;border-bottom:1px solid #f0f0f0;font-weight:700">
                    <?= $be['val'] ? $be['val'] . $be['unit'] : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($suspension)): ?>
    <div class="card">
        <div class="card-header"><span class="ar">نظام التعليق</span><span class="en">Suspension System</span></div>
        <?php
        $sw = [
            ['ar'=>'يسار أمامي','en'=>'Front Left', 's'=>'front_left'],
            ['ar'=>'يمين أمامي','en'=>'Front Right','s'=>'front_right'],
            ['ar'=>'يسار خلفي', 'en'=>'Rear Left',  's'=>'rear_left'],
            ['ar'=>'يمين خلفي', 'en'=>'Rear Right', 's'=>'rear_right'],
        ];
        ?>
        <div class="grid-2" style="gap:0.5rem">
        <?php foreach ($sw as $w): ?>
        <?php $st = $suspension[$w['s'].'_status'] ?? 'good'; ?>
        <div class="wheel-box <?= $st ?>">
            <div class="wlbl"><span class="ar"><?= $w['ar'] ?></span></div>
            <div class="wval"><?= $suspension[$w['s'].'_pct'] ?? '—' ?>%</div>
            <div style="font-size:0.75rem;color:<?= $st==='good'?'var(--good)':($st==='warning'?'var(--warning)':'var(--danger)') ?>;font-weight:700">
                <?= $st === 'good' ? 'جيد' : ($st === 'warning' ? 'تحذير' : 'فشل') ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- INSPECTION CHECKS -->
<?php
$icSections = [
    'engine'      => ['ar'=>'فحوصات المحرك',                       'en'=>'Engine Checks'],
    'underbody'   => ['ar'=>'التنظيم الأمامي / الطقم السفلي',      'en'=>'Underbody / Alignment'],
    'electrical'  => ['ar'=>'النظام الكهربائي والإلكتروني',        'en'=>'Electrical & Electronic'],
    'airbag'      => ['ar'=>'نظام الوسادة الهوائية',               'en'=>'Airbag System'],
    'interior'    => ['ar'=>'الأقسام الداخلية والخارجية',          'en'=>'Interior & Exterior'],
    'accessories' => ['ar'=>'حزمة الإكسسوارات والراحة',            'en'=>'Accessories & Comfort'],
];
foreach ($icSections as $sKey => $sLabel):
    if (empty($ic[$sKey])) continue;
?>
<div class="card">
    <div class="card-header">
        <span class="ar"><?= $sLabel['ar'] ?></span>
        <span class="en"><?= $sLabel['en'] ?></span>
    </div>
    <div class="grid-2" style="gap:0">
    <?php foreach ($ic[$sKey] as $itemKey => $row): ?>
    <div style="display:flex;justify-content:space-between;padding:0.4rem 0.5rem;border-bottom:1px solid #f5f5f5;align-items:center">
        <?php // ---- USE CENTRAL DICTIONARY ---- ?>
        <span style="font-size:0.88rem;color:var(--text-muted)"><?= clean(getInspectionName($itemKey)) ?></span>
        <?= resultBadge($row['result']) ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php if (!empty($notes[$sKey])): ?>
    <div class="notes-section" style="margin-top:1rem">
        <h4><span class="ar">ملاحظات</span> <span class="en">Notes</span></h4>
        <p><?= clean($notes[$sKey]) ?></p>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- BODY PANELS -->
<?php if (!empty($panels)): ?>
<div class="card">
    <div class="card-header"><span class="ar">خريطة الهيكل الخارجي</span><span class="en">Body Panel Map</span></div>

    <div class="panel-legend" style="margin-bottom:1.5rem">
        <?php foreach (PANEL_STATUS as $key => $info): ?>
        <div class="legend-item">
            <div class="legend-swatch" style="background:<?= $info['color'] ?>"></div>
            <span class="ar"><?= $info['ar'] ?></span> / <span class="en"><?= $info['en'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid-2">
    <?php
    $panelGroups = [
        'يمين / Right' => ['right_front_fender','right_front_door','right_rear_door','right_rear_fender','right_sill','right_a_pillar','right_b_pillar','right_roof_rail','right_c_pillar','right_d_pillar','right_platform','right_chassis'],
        'يسار / Left'  => ['left_front_fender','left_front_door','left_rear_door','left_rear_fender','left_sill','left_a_pillar','left_b_pillar','left_roof_rail','left_c_pillar','left_d_pillar','left_platform','left_chassis'],
        'فوق / Top'    => ['hood','roof','trunk_top'],
        'أمام-خلف / Front-Rear' => ['trunk_door','rear_panel','trunk_floor','rear_bumper','front_bumper','front_panel'],
    ];
    $statusColors = array_column(PANEL_STATUS, 'color', null);
    $statusKeys   = array_keys(PANEL_STATUS);
    foreach ($panelGroups as $groupName => $groupKeys): ?>
    <div>
        <h4 style="margin-bottom:0.5rem;padding-bottom:0.3rem;border-bottom:2px solid var(--primary)"><?= $groupName ?></h4>
        <?php foreach ($groupKeys as $pk): ?>
        <?php if (!isset($panels[$pk])) continue; ?>
        <?php $p = $panels[$pk]; $pStatus = PANEL_STATUS[$p['status']] ?? PANEL_STATUS['original']; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.35rem 0.5rem;border-bottom:1px solid #f0f0f0">
            <?php // ---- USE CENTRAL DICTIONARY ---- ?>
            <span style="font-size:0.85rem;color:var(--text-muted)"><?= clean(getBodyName($pk)) ?></span>
            <div style="display:flex;align-items:center;gap:0.5rem">
                <span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:<?= $pStatus['color'] ?>;border:1px solid #ccc"></span>
                <span style="font-size:0.82rem;font-weight:600;color:<?= $pStatus['color'] === '#ffffff' ? '#333' : $pStatus['color'] ?>">
                    <?= $pStatus['ar'] ?>
                </span>
                <?php if ($p['has_disassembly']): ?><span class="badge badge-muted" style="font-size:0.68rem">ST</span><?php endif; ?>
                <?php if ($p['has_foil']): ?><span class="badge badge-muted" style="font-size:0.68rem">F</span><?php endif; ?>
                <?php if ($p['has_damage']): ?><span class="badge badge-danger" style="font-size:0.68rem">H</span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- GENERAL NOTES -->
<?php if (!empty($notes['general'])): ?>
<div class="card notes-section">
    <h4><span class="ar">الملاحظات العامة</span> <span class="en">General Notes</span></h4>
    <p><?= clean($notes['general']) ?></p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>