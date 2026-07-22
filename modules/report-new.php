<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php'; // NEW
requireAuth();

$pageTitle = 'تقرير جديد | New Report';
$pdo       = db();
$reportId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$report    = [];
$checks    = [];
$dyno      = [];
$brakes    = [];
$suspension = [];
$bodyPanels = [];
$sectionNotes = [];

// Load existing report if editing
if ($reportId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch() ?: [];

    if ($report) {
        // Load checklist
        $stmt = $pdo->prepare("SELECT * FROM checklist_items WHERE report_id = ?");
        $stmt->execute([$reportId]);
        foreach ($stmt->fetchAll() as $row) {
            $checks[$row['item_key']] = $row['result'];
        }
        // Load dyno
        $stmt = $pdo->prepare("SELECT * FROM dyno_results WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $dyno = $stmt->fetch() ?: [];
        // Load brakes
        $stmt = $pdo->prepare("SELECT * FROM brake_results WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $brakes = $stmt->fetch() ?: [];
        // Load suspension
        $stmt = $pdo->prepare("SELECT * FROM suspension_results WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $suspension = $stmt->fetch() ?: [];
        // Load inspection checks
        $stmt = $pdo->prepare("SELECT * FROM inspection_checks WHERE report_id = ?");
        $stmt->execute([$reportId]);
        foreach ($stmt->fetchAll() as $row) {
            $checks[$row['section'] . '.' . $row['item_key']] = $row['result'];
        }
        // Load body panels
        $stmt = $pdo->prepare("SELECT * FROM body_panels WHERE report_id = ?");
        $stmt->execute([$reportId]);
        foreach ($stmt->fetchAll() as $row) {
            $bodyPanels[$row['panel_key']] = $row;
        }
        // Load notes
        $stmt = $pdo->prepare("SELECT * FROM section_notes WHERE report_id = ?");
        $stmt->execute([$reportId]);
        foreach ($stmt->fetchAll() as $row) {
            $sectionNotes[$row['section']] = $row['note_text'];
        }
    }
}

// Default report number for new report
$newReportNumber = empty($report) ? generateReportNumber() : $report['report_number'];

// ============================================================
// USE CENTRAL DICTIONARIES TO BUILD SECTIONS AND PANELS
// ============================================================
$inspectionSections = getInspectionSections(); // returns array of sections with 'key', 'ar', 'en', 'items'
$bodyPanelGroups = getBodyPanelGroups(); // returns array of groups with title and panels

$resultOptions = RESULT_OPTIONS;
$panelStatusOptions = PANEL_STATUS;

require_once __DIR__ . '/../includes/header.php';
?>

<!-- THE REST OF THE FILE (HTML + JS) REMAINS EXACTLY THE SAME -->
<!-- The only changes are that we removed the local array definitions and use the global ones -->

<div class="page-header">
    <h1>
        <span class="ar"><?= $reportId ? 'تعديل التقرير' : 'تقرير فحص جديد' ?></span>
        <span class="en"><?= $reportId ? 'Edit Report' : 'New Inspection Report' ?></span>
    </h1>
    <div>
        <span style="color:var(--text-muted);font-size:0.85rem">
            <span class="ar">رقم التقرير:</span>
            <span class="en">Report No:</span>
        </span>
        <strong><?= clean($newReportNumber) ?></strong>
    </div>
</div>

<!-- STEP WIZARD -->
<div class="wizard-steps">
    <?php
    $steps = [
        ['ar'=>'معلومات السيارة',  'en'=>'Vehicle Info'],
        ['ar'=>'نتائج الجهاز',    'en'=>'Dyno Results'],
        ['ar'=>'المحرك والهيكل',  'en'=>'Engine & Body'],
        ['ar'=>'الكهرباء والداخل','en'=>'Electrical & Interior'],
        ['ar'=>'خريطة الهيكل',    'en'=>'Body Map'],
    ];
    foreach ($steps as $i => $step):
    ?>
    <div class="wizard-step <?= $i === 0 ? 'active' : '' ?>" id="step-tab-<?= $i ?>">
        <div class="step-num"><?= $i + 1 ?></div>
        <span class="step-label-ar"><?= $step['ar'] ?></span>
        <span class="step-label-en"><?= $step['en'] ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div id="report-alert" class="alert hidden"></div>

<form id="reportForm" method="POST">
<input type="hidden" name="report_id" value="<?= $reportId ?>">
<input type="hidden" name="report_number" value="<?= clean($newReportNumber) ?>">

<!-- ================================================================
     STEP 1 — VEHICLE & CUSTOMER INFO + CHECKLIST
     ================================================================ -->
<div class="wizard-panel active" id="panel-0">

    <div class="grid-2">
        <!-- Vehicle Info -->
        <div class="card">
            <div class="card-header">
                <span class="ar">معلومات السيارة</span>
                <span class="en">Vehicle Information</span>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="required"><span class="ar">رقم اللوحة</span><span class="en">Plate Number</span></label>
                    <input type="text" name="plate_number" value="<?= clean($report['plate_number'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><span class="ar">رقم الشاسيه</span><span class="en">Chassis Number</span></label>
                    <input type="text" name="chassis_number" value="<?= clean($report['chassis_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="required"><span class="ar">الماركة</span><span class="en">Brand</span></label>
                    <input type="text" name="brand" value="<?= clean($report['brand'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="required"><span class="ar">الموديل</span><span class="en">Model</span></label>
                    <input type="text" name="model" value="<?= clean($report['model'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><span class="ar">سنة الصنع</span><span class="en">Year</span></label>
                    <input type="number" name="year" min="1980" max="2030" value="<?= clean($report['year'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><span class="ar">نوع الوقود</span><span class="en">Fuel Type</span></label>
                    <select name="fuel_type">
                        <?php foreach (FUEL_TYPES as $val => $names): ?>
                        <option value="<?= $val ?>" <?= ($report['fuel_type'] ?? '') === $val ? 'selected' : '' ?>>
                            <?= $names['ar'] ?> / <?= $names['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><span class="ar">الكيلومتر</span><span class="en">Mileage (KM)</span></label>
                    <input type="number" name="mileage" value="<?= clean($report['mileage'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><span class="ar">نوع الباقة</span><span class="en">Package Type</span></label>
                    <input type="text" name="package_type" value="<?= clean($report['package_type'] ?? 'GOLD PLUS PAKET') ?>">
                </div>
            </div>
        </div>

        <!-- Inspection Meta + Customer -->
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="ar">معلومات التقييم</span>
                    <span class="en">Assessment Information</span>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label><span class="ar">تاريخ الفحص</span><span class="en">Inspection Date</span></label>
                        <input type="date" name="date_inspection" value="<?= clean($report['date_inspection'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="ar">هل رأيت الرخصة؟</span><span class="en">License Seen?</span></label>
                        <select name="license_seen">
                            <option value="1" <?= ($report['license_seen'] ?? 0) == 1 ? 'selected' : '' ?>>نعم / Yes</option>
                            <option value="0" <?= ($report['license_seen'] ?? 0) == 0 ? 'selected' : '' ?>>لا / No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><span class="ar">ساعة الدخول</span><span class="en">Time In</span></label>
                        <input type="time" name="time_in" value="<?= clean($report['time_in'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="ar">ساعة الخروج</span><span class="en">Time Out</span></label>
                        <input type="time" name="time_out" value="<?= clean($report['time_out'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="ar">معلومات العميل والبائع</span>
                    <span class="en">Customer & Seller Info</span>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label><span class="ar">اسم المشتري</span><span class="en">Customer Name</span></label>
                        <input type="text" name="customer_name" value="<?= clean($report['customer_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="ar">هاتف المشتري</span><span class="en">Customer Phone</span></label>
                        <input type="tel" name="customer_phone" value="<?= clean($report['customer_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="ar">اسم البائع</span><span class="en">Seller Name</span></label>
                        <input type="text" name="seller_name" value="<?= clean($report['seller_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><span class="ar">هاتف البائع</span><span class="en">Seller Phone</span></label>
                        <input type="tel" name="seller_phone" value="<?= clean($report['seller_phone'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOP-LEVEL CHECKLIST -->
    <div class="card">
        <div class="card-header">
            <span class="ar">الفحوصات الداخلة في التقييم</span>
            <span class="en">Inspection Checklist</span>
        </div>
        <div class="checklist-grid">
            <?php foreach (CHECKLIST_ITEMS as $item): ?>
            <div class="checklist-item">
                <div class="item-name">
                    <div class="ar"><?= $item['ar'] ?></div>
                    <div class="en"><?= $item['en'] ?></div>
                </div>
                <div class="checklist-toggle">
                    <label>
                        <input type="radio" name="checklist[<?= $item['key'] ?>]" value="pass"
                            <?= ($checks[$item['key']] ?? 'pass') === 'pass' ? 'checked' : '' ?>>
                        <span class="pass">✅</span>
                    </label>
                    <label>
                        <input type="radio" name="checklist[<?= $item['key'] ?>]" value="fail"
                            <?= ($checks[$item['key']] ?? '') === 'fail' ? 'checked' : '' ?>>
                        <span class="fail">❌</span>
                    </label>
                    <label>
                        <input type="radio" name="checklist[<?= $item['key'] ?>]" value="not_checked"
                            <?= ($checks[$item['key']] ?? '') === 'not_checked' ? 'checked' : '' ?>>
                        <span>—</span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ================================================================
     STEP 2 — DYNO, BRAKE & SUSPENSION RESULTS
     ================================================================ -->
<div class="wizard-panel" id="panel-1">

    <!-- ENGINE PERFORMANCE -->
    <div class="card">
        <div class="card-header">
            <span class="ar">نتيجة اختبار أداء المحرك</span>
            <span class="en">Engine Performance Test Result</span>
        </div>
        <div class="dyno-section">
            <div class="dyno-values">
                <table>
                    <tr>
                        <td></td>
                        <td><strong>KW</strong></td>
                        <td><strong>HP</strong></td>
                    </tr>
                    <tr>
                        <td><span class="ar">القوة الأصلية</span><br><span class="en">Original Power</span></td>
                        <td><input type="number" name="dyno[original_kw]" style="width:80px" step="0.1"
                            value="<?= clean($dyno['original_kw'] ?? '') ?>" id="orig_kw" oninput="syncHP('orig')"></td>
                        <td><input type="number" name="dyno[original_hp]" style="width:80px" step="0.1"
                            value="<?= clean($dyno['original_hp'] ?? '') ?>" id="orig_hp" oninput="syncKW('orig')"></td>
                    </tr>
                    <tr>
                        <td><span class="ar">القوة المقاسة</span><br><span class="en">Measured Power</span></td>
                        <td><input type="number" name="dyno[measured_kw]" style="width:80px" step="0.1"
                            value="<?= clean($dyno['measured_kw'] ?? '') ?>" id="meas_kw" oninput="syncHP('meas');calcPerf()"></td>
                        <td><input type="number" name="dyno[measured_hp]" style="width:80px" step="0.1"
                            value="<?= clean($dyno['measured_hp'] ?? '') ?>" id="meas_hp" oninput="syncKW('meas');calcPerf()"></td>
                    </tr>
                </table>
                <div class="mt-2">
                    <label><span class="ar">الأداء %</span> <span class="en">Performance %</span></label>
                    <input type="number" name="dyno[performance_percent]" id="perf_pct" style="width:90px"
                        value="<?= clean($dyno['performance_percent'] ?? '') ?>" step="0.1" max="100" oninput="updateGauge()">
                </div>
            </div>
            <div class="gauge-container">
                <svg id="dynoGauge" width="200" height="120" viewBox="0 0 200 120">
                    <defs>
                        <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%"   stop-color="#dc2626"/>
                            <stop offset="50%"  stop-color="#ca8a04"/>
                            <stop offset="75%"  stop-color="#16a34a"/>
                        </linearGradient>
                    </defs>
                    <!-- Background arc -->
                    <path d="M20,110 A90,90 0 0,1 180,110" fill="none" stroke="#e5e7eb" stroke-width="18" stroke-linecap="round"/>
                    <!-- Colored arc -->
                    <path id="gaugeArc" d="M20,110 A90,90 0 0,1 180,110" fill="none" stroke="url(#gaugeGrad)" stroke-width="18" stroke-linecap="round" stroke-dasharray="0 283"/>
                    <!-- Needle -->
                    <line id="gaugeNeedle" x1="100" y1="110" x2="100" y2="30" stroke="#1a1a2e" stroke-width="3" stroke-linecap="round" transform="rotate(-90,100,110)"/>
                    <circle cx="100" cy="110" r="6" fill="#1a1a2e"/>
                    <!-- Labels -->
                    <text x="18"  y="125" font-size="10" fill="#888">0</text>
                    <text x="88"  y="20"  font-size="10" fill="#888">50</text>
                    <text x="175" y="125" font-size="10" fill="#888">100</text>
                </svg>
                <div class="gauge-label"><span id="gaugeText"><?= clean($dyno['performance_percent'] ?? '0') ?></span>%</div>
                <div style="font-size:0.8rem;color:var(--text-muted)">
                    <span class="ar">الأداء الآني</span> <span class="en">Live Performance</span>
                </div>
            </div>
        </div>
    </div>

    <!-- BRAKE SYSTEM -->
    <div class="card">
        <div class="card-header">
            <span class="ar">نتائج نظام الفرامل</span>
            <span class="en">Brake System Results</span>
        </div>
        <div class="grid-2">
            <?php
            $bWheels = [
                ['side'=>'front_left',  'ar'=>'يسار أمامي',   'en'=>'Front Left'],
                ['side'=>'front_right', 'ar'=>'يمين أمامي',   'en'=>'Front Right'],
                ['side'=>'rear_left',   'ar'=>'يسار خلفي',    'en'=>'Rear Left'],
                ['side'=>'rear_right',  'ar'=>'يمين خلفي',    'en'=>'Rear Right'],
            ];
            foreach ($bWheels as $w): ?>
            <div class="form-group">
                <label>
                    <span class="ar"><?= $w['ar'] ?></span>
                    <span class="en"><?= $w['en'] ?> — Force (%)</span>
                </label>
                <div class="flex gap-1">
                    <input type="number" name="brake[<?= $w['side'] ?>_force]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($brakes[$w['side'].'_force'] ?? '') ?>">
                    <select name="brake[<?= $w['side'] ?>_status]" style="width:120px">
                        <?php foreach (STATUS_GOOD_WARNING_FAIL as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($brakes[$w['side'].'_status'] ?? 'good') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr class="divider">

        <div class="grid-2">
            <div class="form-group">
                <label><span class="ar">انحراف الأمامي %</span> <span class="en">Front Deviation %</span></label>
                <div class="flex gap-1">
                    <input type="number" name="brake[front_deviation_pct]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($brakes['front_deviation_pct'] ?? '') ?>">
                    <select name="brake[front_deviation_status]" style="width:120px">
                        <?php foreach (DEVIATION_STATUS as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($brakes['front_deviation_status'] ?? 'pass') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label><span class="ar">انحراف الخلفي %</span> <span class="en">Rear Deviation %</span></label>
                <div class="flex gap-1">
                    <input type="number" name="brake[rear_deviation_pct]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($brakes['rear_deviation_pct'] ?? '') ?>">
                    <select name="brake[rear_deviation_status]" style="width:120px">
                        <?php foreach (DEVIATION_STATUS as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($brakes['rear_deviation_status'] ?? 'pass') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label><span class="ar">فرام اليد (انحراف %)</span> <span class="en">Handbrake Deviation %</span></label>
                <div class="flex gap-1">
                    <input type="number" name="brake[handbrake_deviation_pct]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($brakes['handbrake_deviation_pct'] ?? '') ?>">
                    <select name="brake[handbrake_status]" style="width:120px">
                        <?php foreach (STATUS_GOOD_WARNING_FAIL as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($brakes['handbrake_status'] ?? 'good') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <hr class="divider">
        <h4 style="margin-bottom:0.75rem"><span class="ar">اختبار الانزلاق الجانبي</span> <span class="en">Lateral Slip Test</span></h4>
        <div class="grid-2">
            <div class="form-group">
                <label><span class="ar">الانزلاق الأمامي (انحراف %)</span> <span class="en">Front Slip Deviation %</span></label>
                <div class="flex gap-1">
                    <input type="number" name="brake[slip_front_pct]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($brakes['slip_front_pct'] ?? '') ?>">
                    <select name="brake[slip_front_status]" style="width:120px">
                        <?php foreach (STATUS_GOOD_WARNING_FAIL as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($brakes['slip_front_status'] ?? 'good') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label><span class="ar">الانزلاق الخلفي (انحراف %)</span> <span class="en">Rear Slip Deviation %</span></label>
                <div class="flex gap-1">
                    <input type="number" name="brake[slip_rear_pct]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($brakes['slip_rear_pct'] ?? '') ?>">
                    <select name="brake[slip_rear_status]" style="width:120px">
                        <?php foreach (STATUS_GOOD_WARNING_FAIL as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($brakes['slip_rear_status'] ?? 'good') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- SUSPENSION -->
    <div class="card">
        <div class="card-header">
            <span class="ar">نتائج نظام التعليق</span>
            <span class="en">Suspension System Results</span>
        </div>
        <div class="grid-2">
            <?php
            $sWheels = [
                ['side'=>'front_left',  'ar'=>'يسار أمامي',  'en'=>'Front Left'],
                ['side'=>'front_right', 'ar'=>'يمين أمامي',  'en'=>'Front Right'],
                ['side'=>'rear_left',   'ar'=>'يسار خلفي',   'en'=>'Rear Left'],
                ['side'=>'rear_right',  'ar'=>'يمين خلفي',   'en'=>'Rear Right'],
            ];
            foreach ($sWheels as $w): ?>
            <div class="form-group">
                <label>
                    <span class="ar"><?= $w['ar'] ?></span>
                    <span class="en"><?= $w['en'] ?> — Value (%)</span>
                </label>
                <div class="flex gap-1">
                    <input type="number" name="suspension[<?= $w['side'] ?>_pct]" placeholder="%" step="0.1" style="flex:1"
                        value="<?= clean($suspension[$w['side'].'_pct'] ?? '') ?>">
                    <select name="suspension[<?= $w['side'] ?>_status]" style="width:120px">
                        <?php foreach (STATUS_GOOD_WARNING_FAIL as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($suspension[$w['side'].'_status'] ?? 'good') === $val ? 'selected' : '' ?>>
                            <?= $label['ar'] ?> / <?= $label['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DYNO NOTES -->
    <div class="card notes-section">
        <h4><span class="ar">ملاحظات</span> <span class="en">Notes</span></h4>
        <textarea name="notes[dyno]" rows="3"><?= clean($sectionNotes['dyno'] ?? '') ?></textarea>
    </div>
</div>

<!-- ================================================================
     STEP 3 — ENGINE, UNDERBODY CHECKS
     ================================================================ -->
<div class="wizard-panel" id="panel-2">
    <?php foreach ($inspectionSections as $section): ?>
        <?php if ($section['key'] !== 'engine' && $section['key'] !== 'underbody') continue; ?>
    <div class="card">
        <div class="card-header">
            <span class="ar"><?= $section['ar'] ?></span>
            <span class="en"><?= $section['en'] ?></span>
        </div>
        <table class="inspection-table">
            <thead>
                <tr>
                    <th style="width:55%"><span class="ar">البند</span> / <span class="en" style="color:#aab">Item</span></th>
                    <th><span class="ar">النتيجة</span> / <span class="en" style="color:#aab">Result</span></th>
                    <th><span class="ar">ملاحظة</span> / <span class="en" style="color:#aab">Note</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($section['items'] as $item): ?>
                <?php $savedResult = $checks[$section['key'] . '.' . $item['key']] ?? 'none'; ?>
                <tr>
                    <td>
                        <span class="ar"><?= $item['ar'] ?></span><br>
                        <span class="en"><?= $item['en'] ?></span>
                    </td>
                    <td>
                        <select name="ic[<?= $section['key'] ?>][<?= $item['key'] ?>][result]"
                                class="result-select"
                                onchange="colorSelect(this)">
                            <?php foreach (RESULT_OPTIONS as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $savedResult === $val ? 'selected' : '' ?>>
                                <?= $label['ar'] ?> / <?= $label['en'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="ic[<?= $section['key'] ?>][<?= $item['key'] ?>][notes]"
                               style="width:100%;min-width:120px"
                               placeholder="...">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card notes-section">
        <h4><span class="ar">ملاحظات <?= $section['ar'] ?></span> <span class="en"><?= $section['en'] ?> Notes</span></h4>
        <textarea name="notes[<?= $section['key'] ?>]" rows="3"><?= clean($sectionNotes[$section['key']] ?? '') ?></textarea>
    </div>
    <?php endforeach; ?>
</div>

<!-- ================================================================
     STEP 4 — ELECTRICAL, AIRBAG, INTERIOR, ACCESSORIES
     ================================================================ -->
<div class="wizard-panel" id="panel-3">
    <?php foreach ($inspectionSections as $section): ?>
        <?php if ($section['key'] === 'engine' || $section['key'] === 'underbody') continue; ?>
    <div class="card">
        <div class="card-header">
            <span class="ar"><?= $section['ar'] ?></span>
            <span class="en"><?= $section['en'] ?></span>
        </div>
        <table class="inspection-table">
            <thead>
                <tr>
                    <th style="width:55%"><span class="ar">البند</span> / <span class="en" style="color:#aab">Item</span></th>
                    <th><span class="ar">النتيجة</span> / <span class="en" style="color:#aab">Result</span></th>
                    <th><span class="ar">ملاحظة</span> / <span class="en" style="color:#aab">Note</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($section['items'] as $item): ?>
                <?php $savedResult = $checks[$section['key'] . '.' . $item['key']] ?? 'none'; ?>
                <tr>
                    <td>
                        <span class="ar"><?= $item['ar'] ?></span><br>
                        <span class="en"><?= $item['en'] ?></span>
                    </td>
                    <td>
                        <select name="ic[<?= $section['key'] ?>][<?= $item['key'] ?>][result]"
                                class="result-select"
                                onchange="colorSelect(this)">
                            <?php foreach (RESULT_OPTIONS as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $savedResult === $val ? 'selected' : '' ?>>
                                <?= $label['ar'] ?> / <?= $label['en'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="ic[<?= $section['key'] ?>][<?= $item['key'] ?>][notes]"
                               style="width:100%;min-width:120px"
                               placeholder="...">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card notes-section">
        <h4><span class="ar">ملاحظات</span> <span class="en"><?= $section['en'] ?> Notes</span></h4>
        <textarea name="notes[<?= $section['key'] ?>]" rows="2"><?= clean($sectionNotes[$section['key']] ?? '') ?></textarea>
    </div>
    <?php endforeach; ?>
</div>

<!-- ================================================================
     STEP 5 — BODY PANEL MAP
     ================================================================ -->
<div class="wizard-panel" id="panel-4">

    <!-- LEGEND -->
    <div class="card">
        <div class="card-header">
            <span class="ar">خريطة الهيكل الخارجي</span>
            <span class="en">External Body Map</span>
        </div>
        <div class="panel-legend" style="margin-bottom:1rem">
            <?php foreach (PANEL_STATUS as $key => $info): ?>
            <div class="legend-item">
                <div class="legend-swatch" style="background:<?= $info['color'] ?>"></div>
                <span class="ar"><?= $info['ar'] ?></span> / <span class="en"><?= $info['en'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PANEL STATUS TABLES -->
        <div class="panel-status-grid">
            <?php foreach ($bodyPanelGroups as $groupKey => $group): ?>
            <div class="panel-status-section">
                <h3><span class="ar"><?= $group['title_ar'] ?></span> / <span class="en"><?= $group['title_en'] ?></span></h3>
                <?php foreach ($group['panels'] as $panel): ?>
                <?php $savedPanel = $bodyPanels[$panel['key']] ?? []; ?>
                <div class="panel-row">
                    <div class="pname">
                        <span class="ar"><?= $panel['ar'] ?></span><br>
                        <span class="en"><?= $panel['en'] ?></span>
                    </div>
                    <select name="body[<?= $panel['key'] ?>][status]"
                            class="panel-status-select"
                            data-panel="<?= $panel['key'] ?>"
                            onchange="updatePanelColor(this)">
                        <?php foreach (PANEL_STATUS as $sKey => $sVal): ?>
                        <option value="<?= $sKey ?>"
                            style="background:<?= $sVal['color'] ?>"
                            <?= ($savedPanel['status'] ?? 'original') === $sKey ? 'selected' : '' ?>>
                            <?= $sVal['ar'] ?> / <?= $sVal['en'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="panel-icons">
                        <label class="panel-icon-cb" title="تفكيك وتركيب / Disassembled">
                            <input type="checkbox" name="body[<?= $panel['key'] ?>][has_disassembly]" value="1"
                                <?= !empty($savedPanel['has_disassembly']) ? 'checked' : '' ?>> ST
                        </label>
                        <label class="panel-icon-cb" title="فويل / Foil">
                            <input type="checkbox" name="body[<?= $panel['key'] ?>][has_foil]" value="1"
                                <?= !empty($savedPanel['has_foil']) ? 'checked' : '' ?>> F
                        </label>
                        <label class="panel-icon-cb" title="ضرر / Damage">
                            <input type="checkbox" name="body[<?= $panel['key'] ?>][has_damage]" value="1"
                                <?= !empty($savedPanel['has_damage']) ? 'checked' : '' ?>> H
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DETAILS SECTION -->
    <div class="card">
        <div class="card-header">
            <span class="ar">التفاصيل</span>
            <span class="en">Body Details</span>
        </div>
        <div class="grid-3">
            <?php
            $details = [
                ['key'=>'dents_scratches','ar'=>'الخدوش والصدمات',    'en'=>'Dents & Scratches'],
                ['key'=>'hail_damage',    'ar'=>'صدمات من البرد',     'en'=>'Hail Damage'],
                ['key'=>'retouch',        'ar'=>'تصبغات / بهوت / روتوش','en'=>'Discoloration / Fade / Retouch'],
            ];
            foreach ($details as $d): ?>
            <div class="form-group">
                <label><span class="ar"><?= $d['ar'] ?></span> <span class="en"><?= $d['en'] ?></span></label>
                <select name="ic[body_details][<?= $d['key'] ?>][result]" class="result-select" onchange="colorSelect(this)">
                    <?php foreach (RESULT_OPTIONS as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label['ar'] ?> / <?= $label['en'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FINAL NOTES -->
    <div class="card notes-section">
        <h4><span class="ar">الملاحظات العامة</span> <span class="en">General Notes</span></h4>
        <textarea name="notes[general]" rows="4"><?= clean($sectionNotes['general'] ?? '') ?></textarea>
    </div>

    <!-- STATUS -->
    <div class="card">
        <div class="card-header">
            <span class="ar">حالة التقرير</span>
            <span class="en">Report Status</span>
        </div>
        <div class="form-group">
            <label><span class="ar">الحالة</span> <span class="en">Status</span></label>
            <select name="status">
                <?php foreach (REPORT_STATUS as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($report['status'] ?? 'draft') === $val ? 'selected' : '' ?>>
                    <?= $label['ar'] ?> / <?= $label['en'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- NAVIGATION BUTTONS -->
<div class="wizard-nav">
    <button type="button" class="btn btn-outline" id="btnPrev" onclick="changeStep(-1)" style="display:none">
        &#8594; <span class="ar">السابق</span> <span class="en">Previous</span>
    </button>
    <div>
        <button type="button" class="btn btn-secondary" onclick="saveReport(false)">
            💾 <span class="ar">حفظ مسودة</span> <span class="en">Save Draft</span>
        </button>
    </div>
    <button type="button" class="btn btn-primary" id="btnNext" onclick="changeStep(1)">
        <span class="ar">التالي</span> <span class="en">Next</span> &#8592;
    </button>
    <button type="button" class="btn btn-success hidden" id="btnSave" onclick="saveReport(true)">
        ✅ <span class="ar">حفظ وإنهاء</span> <span class="en">Save & Finish</span>
    </button>
</div>

</form>

<script>
// ---- WIZARD NAVIGATION ----
let currentStep = 0;
const totalSteps = 5;

function changeStep(dir) {
    const panels = document.querySelectorAll('.wizard-panel');
    const tabs   = document.querySelectorAll('.wizard-step');
    tabs[currentStep].classList.remove('active');
    tabs[currentStep].classList.add('done');
    panels[currentStep].classList.remove('active');
    currentStep += dir;
    if (currentStep < 0) currentStep = 0;
    if (currentStep >= totalSteps) currentStep = totalSteps - 1;
    panels[currentStep].classList.add('active');
    tabs[currentStep].classList.add('active');
    tabs[currentStep].classList.remove('done');
    document.getElementById('btnPrev').style.display = currentStep === 0 ? 'none' : '';
    document.getElementById('btnNext').classList.toggle('hidden', currentStep === totalSteps - 1);
    document.getElementById('btnSave').classList.toggle('hidden', currentStep !== totalSteps - 1);
    window.scrollTo({top: 0, behavior: 'smooth'});
}

// ---- GAUGE ----
function updateGauge() {
    const pct = parseFloat(document.getElementById('perf_pct').value) || 0;
    document.getElementById('gaugeText').textContent = pct.toFixed(1);
    const arcLength = 283;
    const filled    = (pct / 100) * arcLength;
    document.getElementById('gaugeArc').setAttribute('stroke-dasharray', filled + ' ' + arcLength);
    const angle = -90 + (pct / 100) * 180;
    document.getElementById('gaugeNeedle').setAttribute('transform', `rotate(${angle},100,110)`);
}

function syncHP(prefix) {
    const kw = parseFloat(document.getElementById(prefix + '_kw').value);
    if (!isNaN(kw)) document.getElementById(prefix + '_hp').value = (kw * 1.34102).toFixed(1);
}
function syncKW(prefix) {
    const hp = parseFloat(document.getElementById(prefix + '_hp').value);
    if (!isNaN(hp)) document.getElementById(prefix + '_kw').value = (hp * 0.7457).toFixed(1);
}
function calcPerf() {
    const orig = parseFloat(document.getElementById('orig_kw').value);
    const meas = parseFloat(document.getElementById('meas_kw').value);
    if (orig > 0 && meas >= 0) {
        const pct = Math.min(100, (meas / orig) * 100);
        document.getElementById('perf_pct').value = pct.toFixed(1);
        updateGauge();
    }
}

// ---- RESULT SELECT COLOR ----
function colorSelect(sel) {
    const colors = {
        good:'#16a34a', none:'#16a34a', light:'#ca8a04',
        medium:'#ea580c', bad:'#dc2626', not_checked:'#6b7280'
    };
    sel.style.color = colors[sel.value] || '#1a1a2e';
    sel.style.fontWeight = '700';
}
document.querySelectorAll('.result-select').forEach(colorSelect);

// ---- PANEL COLOR ----
function updatePanelColor(sel) {
    const colors = {
        original:'#ffffff', painted:'#3b82f6', replaced:'#ef4444',
        repaired:'#a855f7', spot_paint:'#eab308', plastic:'#9ca3af'
    };
    sel.style.background = colors[sel.value] || '#fff';
    sel.style.color = ['original','spot_paint'].includes(sel.value) ? '#1a1a2e' : '#fff';
}
document.querySelectorAll('.panel-status-select').forEach(updatePanelColor);

// ---- SAVE REPORT ----
function saveReport(complete) {
    const form = document.getElementById('reportForm');
    const data = new FormData(form);
    if (complete) data.set('status', 'complete');

    const alert = document.getElementById('report-alert');
    alert.className = 'alert alert-info';
    alert.textContent = '... جاري الحفظ | Saving...';
    alert.classList.remove('hidden');

    fetch('<?= APP_URL ?>/api/save-report.php', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert.className = 'alert alert-success';
            alert.textContent = 'تم الحفظ بنجاح ✅ | Saved successfully';
            if (res.data.report_id) {
                document.querySelector('input[name=report_id]').value = res.data.report_id;
                const url = new URL(window.location);
                url.searchParams.set('id', res.data.report_id);
                window.history.replaceState({}, '', url);
            }
            if (complete) {
                setTimeout(() => { window.location = '<?= APP_URL ?>/modules/dashboard.php'; }, 1000);
            }
        } else {
            alert.className = 'alert alert-error';
            alert.textContent = 'خطأ: ' + res.error;
        }
        window.scrollTo({top: 0, behavior: 'smooth'});
    })
    .catch(e => {
        alert.className = 'alert alert-error';
        alert.textContent = 'فشل الاتصال بالخادم | Server connection failed';
    });
}

// Init gauge on page load
updateGauge();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>