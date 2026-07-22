<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/dictionaries.php'; // NEW

$reportId = (int)($_GET['id'] ?? 0);
if (!$reportId) { die('Invalid report ID'); }

$pdo  = db();
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();
if (!$report) { die('Report not found'); }

// Load all data
$stmt = $pdo->prepare("SELECT * FROM checklist_items WHERE report_id = ?");
$stmt->execute([$reportId]);
$checklistRows = $stmt->fetchAll();
$checklist = [];
foreach ($checklistRows as $r) { $checklist[$r['item_key']] = $r['result']; }

$stmt = $pdo->prepare("SELECT * FROM dyno_results WHERE report_id = ?");
$stmt->execute([$reportId]); $dyno = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM brake_results WHERE report_id = ?");
$stmt->execute([$reportId]); $brakes = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM suspension_results WHERE report_id = ?");
$stmt->execute([$reportId]); $suspension = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM inspection_checks WHERE report_id = ?");
$stmt->execute([$reportId]);
$icRows = $stmt->fetchAll(); $ic = [];
foreach ($icRows as $r) { $ic[$r['section']][$r['item_key']] = $r; }

$stmt = $pdo->prepare("SELECT * FROM body_panels WHERE report_id = ?");
$stmt->execute([$reportId]);
$panelRows = $stmt->fetchAll(); $panels = [];
foreach ($panelRows as $r) { $panels[$r['panel_key']] = $r; }

$stmt = $pdo->prepare("SELECT * FROM section_notes WHERE report_id = ?");
$stmt->execute([$reportId]);
$noteRows = $stmt->fetchAll(); $notes = [];
foreach ($noteRows as $r) { $notes[$r['section']] = $r['note_text']; }

$settings = getSettings();

// mPDF config
$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4',
    'margin_top'    => 30,
    'margin_bottom' => 20,
    'margin_left'   => 12,
    'margin_right'  => 12,
    'default_font'  => 'dejavusans',
]);

// Add Amiri font if present
$amiriPath = APP_ROOT . '/assets/fonts/Amiri-Regular.ttf';
$amiriBold = APP_ROOT . '/assets/fonts/Amiri-Bold.ttf';
if (file_exists($amiriPath)) {
    $mpdf->fontdata['amiri'] = [
        'R' => $amiriPath,
        'B' => file_exists($amiriBold) ? $amiriBold : $amiriPath,
    ];
    $mpdf->default_font = 'amiri';
}

$mpdf->SetTitle('Inspection Report ' . $report['report_number']);
$mpdf->SetAuthor($settings['shop_name_en'] ?? 'Garage');
$mpdf->useAdobeCJK = true;
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont   = true;

// ============================================================
// HELPERS (status and panel display) - now using central dicts
// ============================================================
function pclean($v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function statusColor(string $s): string {
    return match($s) {
        'good','none' => '#16a34a',
        'light'       => '#ca8a04',
        'medium'      => '#ea580c',
        'bad'         => '#dc2626',
        default       => '#6b7280',
    };
}

function statusAr(string $s): string {
    return match($s) {
        'good'        => 'جيد',
        'none'        => 'لا يوجد',
        'light'       => 'خفيف',
        'medium'      => 'متوسط',
        'bad'         => 'سيئ',
        'not_checked' => 'لم يفحص',
        default       => $s,
    };
}

function statusEn(string $s): string {
    return match($s) {
        'good'        => 'Good',
        'none'        => 'None',
        'light'       => 'Light',
        'medium'      => 'Medium',
        'bad'         => 'Bad',
        'not_checked' => 'Not Checked',
        default       => $s,
    };
}

function panelColor(string $s): string {
    return match($s) {
        'original'   => '#ffffff',
        'painted'    => '#3b82f6',
        'replaced'   => '#ef4444',
        'repaired'   => '#a855f7',
        'spot_paint' => '#eab308',
        'plastic'    => '#9ca3af',
        default      => '#ffffff',
    };
}

function panelAr(string $s): string {
    return match($s) {
        'original'   => 'أصلي',
        'painted'    => 'تم طلاؤه',
        'replaced'   => 'تم تبديله',
        'repaired'   => 'تم تعديله',
        'spot_paint' => 'طلاء موضعي',
        'plastic'    => 'بلاستك',
        default      => $s,
    };
}

// Header/footer
$logoHtml = '';
if (!empty($settings['logo_path']) && file_exists(APP_ROOT . '/' . $settings['logo_path'])) {
    $logoHtml = '<img src="' . APP_ROOT . '/' . $settings['logo_path'] . '" style="height:50px">';
}

$headerHtml = '
<table width="100%" style="border-bottom:3px solid #c0392b; padding-bottom:8px; margin-bottom:0">
<tr>
    <td width="35%">' . $logoHtml . '<br>
        <span style="font-family:amiri; font-size:14pt; font-weight:bold; color:#1a1a2e">' . pclean($settings['shop_name_ar'] ?? '') . '</span><br>
        <span style="font-size:9pt; color:#555">' . pclean($settings['shop_name_en'] ?? '') . '</span>
    </td>
    <td width="30%" style="text-align:center">
        <span style="font-size:8pt; color:#777">' . pclean($settings['shop_address'] ?? '') . '</span><br>
        <span style="font-size:8pt; color:#777">' . pclean($settings['shop_phone'] ?? '') . '</span>
    </td>
    <td width="35%" style="text-align:left; font-size:9pt">
        <b>RAPOR NO:</b> ' . pclean($report['report_number']) . '<br>
        <b>DATE:</b> ' . pclean($report['date_inspection'] ?? '') . '<br>
        <b>PLATE:</b> ' . pclean($report['plate_number'] ?? '') . '<br>
        <b>KM:</b> ' . number_format((int)($report['mileage'] ?? 0))  . '
    </td>
</tr>
</table>';

$mpdf->SetHTMLHeader($headerHtml);
$mpdf->SetHTMLFooter('<table width="100%"><tr>
    <td style="font-size:8pt; color:#777">' . pclean($settings['shop_name_en'] ?? '') . '</td>
    <td style="text-align:center; font-size:8pt; color:#777">صفحة {PAGENO} من {nb}</td>
    <td style="text-align:left; font-size:8pt; color:#777">Page {PAGENO} of {nb}</td>
</tr></table>');

// ============================================================
// BUILD HTML CONTENT
// ============================================================
ob_start();
?>
<style>
    body         { font-family: amiri, dejavusans; font-size: 10pt; color: #1a1a2e; direction: rtl; }
    h2           { font-size: 11pt; font-weight: bold; color: #fff; background: #c0392b;
                   padding: 5px 10px; margin: 10px 0 6px; border-radius: 4px; }
    h3           { font-size: 10pt; font-weight: bold; color: #fff; background: #1a1a2e;
                   padding: 4px 8px; margin: 8px 0 4px; }
    table        { border-collapse: collapse; width: 100%; margin-bottom: 8px; }
    th           { background: #1a1a2e; color: #fff; padding: 4px 6px; font-size: 9pt; }
    td           { padding: 3px 6px; border-bottom: 1px solid #eee; font-size: 9pt; }
    .label       { color: #666; font-size: 8.5pt; }
    .val         { font-weight: bold; }
    .good        { color: #16a34a; font-weight: bold; }
    .warn        { color: #ca8a04; font-weight: bold; }
    .bad         { color: #dc2626; font-weight: bold; }
    .muted       { color: #6b7280; }
    .section-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 8px; margin-bottom:8px; }
</style>

<!-- PAGE 1: VEHICLE INFO + CHECKLIST -->
<table>
    <tr>
        <td width="50%">
            <h2>معلومات السيارة &nbsp;|&nbsp; Vehicle Information</h2>
            <table>
                <tr><td class="label">رقم اللوحة / Plate</td><td class="val"><?= pclean($report['plate_number'] ?? '—') ?></td></tr>
                <tr><td class="label">رقم الشاسيه / Chassis</td><td class="val"><?= pclean($report['chassis_number'] ?? '—') ?></td></tr>
                <tr><td class="label">الماركة / Brand</td><td class="val"><?= pclean($report['brand'] ?? '—') ?></td></tr>
                <tr><td class="label">الموديل / Model</td><td class="val"><?= pclean($report['model'] ?? '—') ?></td></tr>
                <tr><td class="label">السنة / Year</td><td class="val"><?= pclean($report['year'] ?? '—') ?></td></tr>
                <tr><td class="label">نوع الوقود / Fuel</td><td class="val"><?= pclean($report['fuel_type'] ?? '—') ?></td></tr>
                <tr><td class="label">الكيلومتر / Mileage</td><td class="val"><?= number_format((int)($report['mileage'] ?? 0)) ?> KM</td></tr>
            </table>
        </td>
        <td width="50%">
            <h2>معلومات التقييم &nbsp;|&nbsp; Assessment Info</h2>
            <table>
                <tr><td class="label">رقم التقرير / Report No</td><td class="val"><?= pclean($report['report_number']) ?></td></tr>
                <tr><td class="label">التاريخ / Date</td><td class="val"><?= pclean($report['date_inspection'] ?? '—') ?></td></tr>
                <tr><td class="label">ساعة الدخول / Time In</td><td class="val"><?= pclean($report['time_in'] ?? '—') ?></td></tr>
                <tr><td class="label">ساعة الخروج / Time Out</td><td class="val"><?= pclean($report['time_out'] ?? '—') ?></td></tr>
                <tr><td class="label">نوع الباقة / Package</td><td class="val"><?= pclean($report['package_type'] ?? '—') ?></td></tr>
                <tr><td class="label">هل رأيت الرخصة / License Seen</td><td class="val"><?= $report['license_seen'] ? 'نعم / Yes' : 'لا / No' ?></td></tr>
                <tr><td class="label">المشتري / Customer</td><td class="val"><?= pclean($report['customer_name'] ?? '—') ?></td></tr>
                <tr><td class="label">البائع / Seller</td><td class="val"><?= pclean($report['seller_name'] ?? '—') ?></td></tr>
            </table>
        </td>
    </tr>
</table>

<!-- CHECKLIST -->
<h2>الفحوصات الداخلة في التقييم &nbsp;|&nbsp; Inspection Checklist</h2>
<table>
    <tr>
    <?php
    $items = CHECKLIST_ITEMS;
    $half  = ceil(count($items) / 2);
    $col1  = array_slice($items, 0, $half);
    $col2  = array_slice($items, $half);
    $maxRows = max(count($col1), count($col2));
    for ($i = 0; $i < $maxRows; $i++):
        $a = $col1[$i] ?? null;
        $b = $col2[$i] ?? null;
    ?>
    </tr><tr>
        <td width="45%"><?= $a ? pclean($a['ar']) . ' / ' . pclean($a['en']) : '' ?></td>
        <td width="5%" style="text-align:center">
            <?php if ($a): $r = $checklist[$a['key']] ?? 'not_checked'; ?>
            <span style="color:<?= $r==='pass'?'#16a34a':($r==='fail'?'#dc2626':'#6b7280') ?>;font-weight:bold">
                <?= $r==='pass'?'✓':($r==='fail'?'✗':'—') ?>
            </span>
            <?php endif; ?>
        </td>
        <td width="45%"><?= $b ? pclean($b['ar']) . ' / ' . pclean($b['en']) : '' ?></td>
        <td width="5%" style="text-align:center">
            <?php if ($b): $r = $checklist[$b['key']] ?? 'not_checked'; ?>
            <span style="color:<?= $r==='pass'?'#16a34a':($r==='fail'?'#dc2626':'#6b7280') ?>;font-weight:bold">
                <?= $r==='pass'?'✓':($r==='fail'?'✗':'—') ?>
            </span>
            <?php endif; ?>
        </td>
    <?php endfor; ?>
    </tr>
</table>

<!-- PAGE 2: DYNO + BRAKE + SUSPENSION -->
<pagebreak/>

<!-- DYNO -->
<?php if (!empty($dyno)): ?>
<h2>نتيجة اختبار أداء المحرك &nbsp;|&nbsp; Engine Performance Test</h2>
<table>
    <tr>
        <td width="60%">
            <table>
                <tr><th></th><th>KW</th><th>HP</th></tr>
                <tr>
                    <td>القوة الأصلية / Original Power</td>
                    <td class="val"><?= pclean($dyno['original_kw'] ?? '—') ?></td>
                    <td class="val"><?= pclean($dyno['original_hp'] ?? '—') ?></td>
                </tr>
                <tr>
                    <td>القوة المقاسة / Measured Power</td>
                    <td class="val" style="color:#c0392b"><?= pclean($dyno['measured_kw'] ?? '—') ?></td>
                    <td class="val" style="color:#c0392b"><?= pclean($dyno['measured_hp'] ?? '—') ?></td>
                </tr>
            </table>
        </td>
        <td width="40%" style="text-align:center">
            <?php $pct = (float)($dyno['performance_percent'] ?? 0); ?>
            <span style="font-size:28pt; font-weight:bold; color:<?= $pct>=80?'#16a34a':($pct>=60?'#ca8a04':'#dc2626') ?>">
                <?= number_format($pct, 1) ?>%
            </span><br>
            <span style="font-size:9pt; color:#666">الأداء الآني / Live Performance</span>
        </td>
    </tr>
</table>
<?php endif; ?>

<!-- BRAKES -->
<?php if (!empty($brakes)): ?>
<h2>نظام الفرامل &nbsp;|&nbsp; Brake System</h2>
<table>
    <tr>
        <th>الموقع / Position</th>
        <th>القوة / Force (%)</th>
        <th>الحالة / Status</th>
        <th>الموقع / Position</th>
        <th>القوة / Force (%)</th>
        <th>الحالة / Status</th>
    </tr>
    <?php
    $brows = [
        ['يسار أمامي / Front Left','front_left','يمين أمامي / Front Right','front_right'],
        ['يسار خلفي / Rear Left',  'rear_left', 'يمين خلفي / Rear Right',  'rear_right'],
    ];
    foreach ($brows as $br): ?>
    <tr>
        <td><?= $br[0] ?></td>
        <td class="val"><?= $brakes[$br[1].'_force'] ?? '—' ?>%</td>
        <td><span style="color:<?= statusColor($brakes[$br[1].'_status'] ?? 'good') ?>;font-weight:bold">
            <?= statusAr($brakes[$br[1].'_status'] ?? 'good') ?></span></td>
        <td><?= $br[2] ?></td>
        <td class="val"><?= $brakes[$br[3].'_force'] ?? '—' ?>%</td>
        <td><span style="color:<?= statusColor($brakes[$br[3].'_status'] ?? 'good') ?>;font-weight:bold">
            <?= statusAr($brakes[$br[3].'_status'] ?? 'good') ?></span></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td>انحراف أمامي / Front Dev</td>
        <td class="val"><?= $brakes['front_deviation_pct'] ?? '—' ?>%</td>
        <td><span style="color:<?= ($brakes['front_deviation_status']??'pass')==='pass'?'#16a34a':'#dc2626' ?>;font-weight:bold">
            <?= ($brakes['front_deviation_status']??'pass')==='pass'?'نجح/Pass':'فشل/Fail' ?></span></td>
        <td>انحراف خلفي / Rear Dev</td>
        <td class="val"><?= $brakes['rear_deviation_pct'] ?? '—' ?>%</td>
        <td><span style="color:<?= ($brakes['rear_deviation_status']??'pass')==='pass'?'#16a34a':'#dc2626' ?>;font-weight:bold">
            <?= ($brakes['rear_deviation_status']??'pass')==='pass'?'نجح/Pass':'فشل/Fail' ?></span></td>
    </tr>
    <tr>
        <td colspan="2">فرام اليد / Handbrake</td>
        <td><?= $brakes['handbrake_deviation_pct'] ?? '—' ?>%</td>
        <td colspan="2">انزلاق أمامي / Front Slip</td>
        <td><?= $brakes['slip_front_pct'] ?? '—' ?>%</td>
    </tr>
    <tr>
        <td colspan="5">انزلاق خلفي / Rear Slip</td>
        <td><?= $brakes['slip_rear_pct'] ?? '—' ?>%</td>
    </tr>
</table>
<?php endif; ?>

<!-- SUSPENSION -->
<?php if (!empty($suspension)): ?>
<h2>نظام التعليق &nbsp;|&nbsp; Suspension System</h2>
<table>
    <tr><th>الموقع / Position</th><th>القيمة / Value (%)</th><th>الحالة / Status</th>
        <th>الموقع / Position</th><th>القيمة / Value (%)</th><th>الحالة / Status</th></tr>
    <?php
    $srows = [
        ['يسار أمامي / Front Left','front_left','يمين أمامي / Front Right','front_right'],
        ['يسار خلفي / Rear Left',  'rear_left', 'يمين خلفي / Rear Right',  'rear_right'],
    ];
    foreach ($srows as $sr): ?>
    <tr>
        <td><?= $sr[0] ?></td>
        <td class="val"><?= $suspension[$sr[1].'_pct'] ?? '—' ?>%</td>
        <td><span style="color:<?= statusColor($suspension[$sr[1].'_status']??'good') ?>;font-weight:bold">
            <?= statusAr($suspension[$sr[1].'_status'] ?? 'good') ?></span></td>
        <td><?= $sr[2] ?></td>
        <td class="val"><?= $suspension[$sr[3].'_pct'] ?? '—' ?>%</td>
        <td><span style="color:<?= statusColor($suspension[$sr[3].'_status']??'good') ?>;font-weight:bold">
            <?= statusAr($suspension[$sr[3].'_status'] ?? 'good') ?></span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<!-- PAGE 3: INSPECTION CHECKS -->
<pagebreak/>
<?php
$allSections = [
    'engine'      => ['ar'=>'فحوصات المحرك',                     'en'=>'Engine Checks'],
    'underbody'   => ['ar'=>'التنظيم الأمامي / الطقم السفلي',   'en'=>'Underbody / Alignment'],
    'electrical'  => ['ar'=>'النظام الكهربائي والإلكتروني',     'en'=>'Electrical & Electronic'],
    'airbag'      => ['ar'=>'نظام الوسادة الهوائية',            'en'=>'Airbag System'],
    'interior'    => ['ar'=>'الأقسام الداخلية والخارجية',       'en'=>'Interior & Exterior'],
    'accessories' => ['ar'=>'حزمة الإكسسوارات والراحة',         'en'=>'Accessories & Comfort'],
];
foreach ($allSections as $sKey => $sLabel):
    if (empty($ic[$sKey])) continue;
    $sItems = $ic[$sKey];
    $half   = ceil(count($sItems) / 2);
    $col1   = array_slice($sItems, 0, $half, true);
    $col2   = array_slice($sItems, $half, null, true);
    $keys1  = array_keys($col1);
    $keys2  = array_keys($col2);
?>
<h2><?= $sLabel['ar'] ?> &nbsp;|&nbsp; <?= $sLabel['en'] ?></h2>
<table>
    <tr><th width="38%">البند / Item</th><th width="12%">النتيجة / Result</th>
        <th width="38%">البند / Item</th><th width="12%">النتيجة / Result</th></tr>
    <?php
    $maxR = max(count($col1), count($col2));
    for ($i = 0; $i < $maxR; $i++):
        $aKey = $keys1[$i] ?? null;
        $bKey = $keys2[$i] ?? null;
        $a    = $aKey ? $col1[$aKey] : null;
        $b    = $bKey ? $col2[$bKey] : null;
        // ---- USE CENTRAL DICTIONARY ----
        $aLabel = $a ? getInspectionName($a['item_key']) : '';
        $bLabel = $b ? getInspectionName($b['item_key']) : '';
    ?>
    <tr>
        <td><?= pclean($aLabel) ?></td>
        <td><?php if ($a): ?>
            <span style="color:<?= statusColor($a['result']) ?>;font-weight:bold">
                <?= statusAr($a['result']) ?>
            </span>
        <?php endif; ?></td>
        <td><?= pclean($bLabel) ?></td>
        <td><?php if ($b): ?>
            <span style="color:<?= statusColor($b['result']) ?>;font-weight:bold">
                <?= statusAr($b['result']) ?>
            </span>
        <?php endif; ?></td>
    </tr>
    <?php endfor; ?>
</table>
<?php if (!empty($notes[$sKey])): ?>
<div class="section-box" style="background:#fffbeb; border-color:#fde68a">
    <b>ملاحظات / Notes:</b> <?= pclean($notes[$sKey]) ?>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- PAGE 4: BODY PANELS -->
<?php if (!empty($panels)): ?>
<pagebreak/>
<h2>خريطة الهيكل الخارجي &nbsp;|&nbsp; External Body Panel Map</h2>

<!-- Legend -->
<table style="margin-bottom:10px">
<tr>
<?php foreach (PANEL_STATUS as $key => $info): ?>
<td style="text-align:center;padding:3px 5px">
    <span style="display:inline-block;width:14px;height:14px;background:<?= $info['color'] ?>;border:1px solid #ccc;vertical-align:middle"></span>
    <span style="font-size:8pt"> <?= $info['ar'] ?> / <?= $info['en'] ?></span>
</td>
<?php endforeach; ?>
</tr>
</table>

<?php
$bodyPanelGroupsPdf = [
    ['title_ar'=>'يمين','title_en'=>'Right Side','keys'=>[
        'right_front_fender','right_front_door','right_rear_door','right_rear_fender',
        'right_sill','right_a_pillar','right_b_pillar','right_roof_rail',
        'right_c_pillar','right_d_pillar','right_platform','right_chassis',
    ]],
    ['title_ar'=>'يسار','title_en'=>'Left Side','keys'=>[
        'left_front_fender','left_front_door','left_rear_door','left_rear_fender',
        'left_sill','left_a_pillar','left_b_pillar','left_roof_rail',
        'left_c_pillar','left_d_pillar','left_platform','left_chassis',
    ]],
    ['title_ar'=>'فوق','title_en'=>'Top','keys'=>['hood','roof','trunk_top']],
    ['title_ar'=>'أمام / خلف','title_en'=>'Front / Rear','keys'=>[
        'trunk_door','rear_panel','trunk_floor','rear_bumper','front_bumper','front_panel',
    ]],
];
?>
<table>
<tr>
<?php foreach ($bodyPanelGroupsPdf as $grp): ?>
<td valign="top" width="25%" style="padding-right:8px">
    <table width="100%">
        <tr><th colspan="2"><?= $grp['title_ar'] ?> / <?= $grp['title_en'] ?></th></tr>
        <?php foreach ($grp['keys'] as $pk): ?>
        <?php $p = $panels[$pk] ?? null; ?>
        <tr>
            <?php 
            // ---- USE CENTRAL DICTIONARY ----
            $panelName = getBodyName($pk);
            ?>
            <td style="font-size:8pt;color:#555"><?= pclean($panelName) ?></td>
            <td style="font-size:8pt;text-align:center">
                <?php if ($p): ?>
                <span style="color:<?= panelColor($p['status'])==='#ffffff'?'#333':panelColor($p['status']) ?>;font-weight:bold">
                    <?= panelAr($p['status']) ?>
                </span>
                <?= $p['has_disassembly'] ? ' <span style="font-size:7pt;color:#555">[ST]</span>' : '' ?>
                <?= $p['has_foil']        ? ' <span style="font-size:7pt;color:#555">[F]</span>'  : '' ?>
                <?= $p['has_damage']      ? ' <span style="font-size:7pt;color:#dc2626">[H]</span>': '' ?>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</td>
<?php endforeach; ?>
</tr>
</table>
<?php endif; ?>

<!-- GENERAL NOTES -->
<?php if (!empty($notes['general'])): ?>
<h2>الملاحظات العامة &nbsp;|&nbsp; General Notes</h2>
<div class="section-box" style="background:#fffbeb; border-color:#fde68a; font-size:10pt">
    <?= pclean($notes['general']) ?>
</div>
<?php endif; ?>

<!-- IMPORTANT NOTES (static, like the original report) -->
<h2>توضيحات هامة &nbsp;|&nbsp; Important Notes</h2>
<div class="section-box" style="font-size:8.5pt; line-height:1.6">
    <p>نوصيك بعدم إجراء معاملة مبيعات كاتب عدل دون إظهار الأعطال والأضرار التي ذكرناها في تقرير التقييم حول السيارة إلى الخدمة المعتمدة أو مدير العلامة التجارية.</p>
    <p>We advise you not to make a notary sales transaction without showing the faults and damages mentioned in this evaluation report to the authorized service or brand manager.</p>
    <p>نوصي بإجراء فحوصات زيت محرك سيارتك، وسائل التبريد وغيرها من السوائل، وإجراء الصيانة الدورية للزيت والفلتر.</p>
    <p>شركتنا ليست مسؤولة عن سجلات تلف الترام أو السجلات ذات الصلة للمركبة أو أوجه القصور فيها.</p>
</div>

<!-- SIGNATURES -->
<br><br>
<table>
    <tr>
        <td width="33%" style="text-align:center; padding-top:30px; border-top:1px solid #333">
            <span class="ar">معلومات المشتري</span><br>
            <span style="font-size:8pt; color:#555">Customer Information</span><br>
            <b><?= pclean($report['customer_name'] ?? '—') ?></b><br>
            <span style="font-size:8pt"><?= pclean($report['customer_phone'] ?? '') ?></span>
        </td>
        <td width="33%" style="text-align:center; padding-top:30px; border-top:1px solid #333">
            <span class="ar">شركة التقييم الفني</span><br>
            <span style="font-size:8pt; color:#555">Technical Assessment Company</span><br>
            <b><?= pclean($settings['shop_name_ar'] ?? '') ?></b><br>
            <span style="font-size:8pt"><?= pclean($settings['shop_phone'] ?? '') ?></span>
        </td>
        <td width="33%" style="text-align:center; padding-top:30px; border-top:1px solid #333">
            <span class="ar">معلومات البائع</span><br>
            <span style="font-size:8pt; color:#555">Seller Information</span><br>
            <b><?= pclean($report['seller_name'] ?? '—') ?></b><br>
            <span style="font-size:8pt"><?= pclean($report['seller_phone'] ?? '') ?></span>
        </td>
    </tr>
</table>

<?php
$html = ob_get_clean();
$mpdf->WriteHTML($html);
$filename = 'report-' . $report['report_number'] . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);