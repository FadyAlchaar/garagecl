<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$pdo       = db();
$pageTitle = t('history_title');
$query     = clean(trim($_GET['q'] ?? ''));
$vehicle   = [];
$history   = [];

if ($query) {
    // Find the vehicle's reports
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name_ar as tech_name,
               d.performance_percent,
               d.measured_hp, d.original_hp
        FROM reports r
        LEFT JOIN users u ON r.technician_id = u.id
        LEFT JOIN dyno_results d ON r.id = d.report_id
        WHERE r.plate_number LIKE :q OR r.chassis_number LIKE :q
        ORDER BY r.date_inspection DESC, r.created_at DESC
    ");
    $stmt->execute([':q' => '%' . $query . '%']);
    $history = $stmt->fetchAll();

    if ($history) {
        $vehicle = [
            'plate'    => $history[0]['plate_number'],
            'chassis'  => $history[0]['chassis_number'],
            'brand'    => $history[0]['brand'],
            'model'    => $history[0]['model'],
            'fuel'     => $history[0]['fuel_type'],
            'count'    => count($history),
            'first'    => end($history)['date_inspection'],
            'last'     => $history[0]['date_inspection'],
        ];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><span class="ar"><?= t('history_title') ?></span><span class="en">Vehicle History</span></h1>
</div>

<!-- SEARCH BAR -->
<div class="card">
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="q" value="<?= clean($query) ?>"
                   placeholder="<?= t('history_search') ?> — e.g. ABC1234 or WF0JXXWPJJCK03283"
                   style="flex:1">
            <button type="submit" class="btn btn-primary">🔍 <?= t('btn_search') ?></button>
            <?php if ($query): ?>
            <a href="?" class="btn btn-outline"><?= t('btn_clear') ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($query && empty($history)): ?>
<div class="card" style="text-align:center;padding:2rem;color:var(--text-muted)">
    <div style="font-size:2.5rem">🚗</div>
    <p class="ar"><?= t('no_results') ?></p>
    <p class="en">No inspections found for: <strong><?= clean($query) ?></strong></p>
</div>
<?php endif; ?>

<?php if (!empty($vehicle)): ?>
<!-- VEHICLE SUMMARY -->
<div class="grid-4">
    <div class="card text-center">
        <div style="font-size:2rem;font-weight:800;color:var(--primary)"><?= $vehicle['count'] ?></div>
        <div class="ar">إجمالي الفحوصات</div>
        <div class="en"><?= t('history_count') ?></div>
    </div>
    <div class="card text-center">
        <div style="font-size:1.2rem;font-weight:700;color:var(--accent)"><?= clean($vehicle['plate']) ?></div>
        <div class="ar"><?= t('report_number') ?></div>
        <div class="en">Plate Number</div>
    </div>
    <div class="card text-center">
        <div style="font-size:1rem;font-weight:600"><?= $vehicle['first'] ?? '—' ?></div>
        <div class="ar"><?= t('first_inspection') ?></div>
        <div class="en">First Inspection</div>
    </div>
    <div class="card text-center">
        <div style="font-size:1rem;font-weight:600;color:var(--good)"><?= $vehicle['last'] ?? '—' ?></div>
        <div class="ar"><?= t('last_inspection') ?></div>
        <div class="en">Last Inspection</div>
    </div>
</div>

<!-- VEHICLE INFO -->
<div class="card">
    <div class="card-header">
        <span class="ar">معلومات المركبة</span><span class="en">Vehicle Information</span>
    </div>
    <div class="grid-3">
        <?php foreach ([
            ['ar'=>t('brand'),     'en'=>'Brand',   'val'=>$vehicle['brand']],
            ['ar'=>t('model'),     'en'=>'Model',   'val'=>$vehicle['model']],
            ['ar'=>t('fuel_type'), 'en'=>'Fuel',    'val'=>$vehicle['fuel']],
            ['ar'=>t('plate_number'), 'en'=>'Plate','val'=>$vehicle['plate']],
            ['ar'=>t('chassis_number'),'en'=>'Chassis','val'=>$vehicle['chassis']],
        ] as $f): ?>
        <div style="padding:.5rem;background:#f9fafb;border-radius:8px;border:1px solid var(--border)">
            <div style="font-size:.78rem;color:var(--text-muted)"><?= $f['ar'] ?> / <span style="font-family:var(--font-en)"><?= $f['en'] ?></span></div>
            <div style="font-weight:700;font-size:1rem"><?= clean($f['val'] ?? '—') ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- TIMELINE -->
<div class="card">
    <div class="card-header">
        <span class="ar">سجل الفحوصات</span><span class="en">Inspection Timeline</span>
    </div>
    <div style="position:relative;padding-right:2rem">
        <!-- Timeline line -->
        <div style="position:absolute;right:0;top:0;bottom:0;width:3px;background:linear-gradient(to bottom,var(--primary),#e5e7eb);border-radius:3px"></div>
        <?php foreach ($history as $i => $r): ?>
        <div style="position:relative;margin-bottom:1.5rem;padding-right:1.5rem">
            <!-- Timeline dot -->
            <div style="position:absolute;right:-1.25rem;width:16px;height:16px;border-radius:50%;background:<?= $r['status']==='complete'?'var(--good)':'var(--warning)' ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?= $r['status']==='complete'?'var(--good)':'var(--warning)' ?>;top:6px"></div>
            <div style="background:#f9fafb;border:1px solid var(--border);border-radius:10px;padding:1rem">
                <div class="flex-between">
                    <div>
                        <strong><?= clean($r['report_number']) ?></strong>
                        <span class="badge <?= $r['status']==='complete'?'badge-good':'badge-warning' ?>" style="margin-right:.5rem">
                            <?= t('status_' . $r['status']) ?>
                        </span>
                    </div>
                    <div style="font-size:.85rem;color:var(--text-muted)"><?= $r['date_inspection'] ?? $r['created_at'] ?></div>
                </div>
                <div class="grid-3 mt-1" style="font-size:.85rem">
                    <div><span style="color:var(--text-muted)">KM:</span> <strong><?= $r['mileage'] ? number_format($r['mileage']) : '—' ?></strong></div>
                    <?php if ($r['performance_percent']): ?>
                    <div><span style="color:var(--text-muted)">Performance:</span>
                        <strong style="color:<?= $r['performance_percent']>=80?'var(--good)':($r['performance_percent']>=60?'var(--warning)':'var(--danger)') ?>">
                            <?= number_format($r['performance_percent'],1) ?>%
                        </strong>
                    </div>
                    <?php endif; ?>
                    <div><span style="color:var(--text-muted)">Tech:</span> <strong><?= clean($r['tech_name'] ?? '—') ?></strong></div>
                </div>
                <div style="display:flex;gap:.4rem;margin-top:.75rem">
                    <a href="<?= APP_URL ?>/modules/report-view.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">👁️ <?= t('btn_view') ?></a>
                    <a href="<?= APP_URL ?>/pdf/generate.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-primary btn-sm">🖨️ PDF</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
