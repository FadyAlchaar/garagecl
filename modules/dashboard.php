<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$pageTitle = 'لوحة التحكم | Dashboard';

// Search/filter
$search = trim($_GET['search'] ?? '');
$pdo    = db();

if ($search !== '') {
    $s = '%' . $search . '%';
    $stmt = $pdo->prepare("
        SELECT * FROM reports
        WHERE plate_number   LIKE :s1
           OR chassis_number LIKE :s2
           OR report_number  LIKE :s3
           OR customer_name  LIKE :s4
           OR brand          LIKE :s5
        ORDER BY created_at DESC
        LIMIT 100
    ");
    $stmt->execute([':s1'=>$s,':s2'=>$s,':s3'=>$s,':s4'=>$s,':s5'=>$s]);
} else {
    $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 100");
}
$reports = $stmt->fetchAll();

// Stats
$total    = (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$today    = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$thisMonth = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())")->fetchColumn();
$avgPerf   = round((float)$pdo->query("SELECT AVG(performance_percent) FROM dyno_results WHERE performance_percent>0")->fetchColumn(),1);
$complete = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'complete'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar">لوحة التحكم</span>
        <span class="en">Dashboard</span>
    </h1>
    <a href="<?= APP_URL ?>/modules/report-new.php" class="btn btn-primary btn-lg">
        + <span class="ar">تقرير جديد</span>&nbsp;<span class="en">New Report</span>
    </a>
</div>

<!-- STATS -->
<div class="grid-4 mb-2">
    <div class="card text-center">
        <div style="font-size:2.5rem;font-weight:800;color:var(--primary)"><?= $total ?></div>
        <div class="ar">إجمالي التقارير</div>
        <div class="en">Total Reports</div>
    </div>
    <div class="card text-center">
        <div style="font-size:2.5rem;font-weight:800;color:var(--good)"><?= $today ?></div>
        <div class="ar">تقارير اليوم</div>
        <div class="en">Today's Reports</div>
    </div>
    <div class="card text-center">
        <div style="font-size:2.5rem;font-weight:800;color:#2563eb"><?= $complete ?></div>
        <div class="ar">تقارير مكتملة</div>
        <div class="en">Complete Reports</div>
    </div>
    <div class="card text-center">
        <div style="font-size:2rem;font-weight:800;color:var(--warning)"><?= $thisMonth ?></div>
        <div class="ar">هذا الشهر</div>
        <div class="en">This Month</div>
        <div class="en" style="font-size:.75rem;color:var(--text-muted);margin-top:2px">Avg perf: <?= $avgPerf ?>%</div>
    </div>
</div>

<!-- SEARCH -->
<form method="GET" action="">
    <div class="search-bar">
        <input type="text"
               name="search"
               placeholder="ابحث برقم اللوحة أو الشاسيه أو اسم العميل... | Search by plate, chassis, customer..."
               value="<?= clean($search) ?>">
        <button type="submit" class="btn btn-secondary">
            <span class="ar">بحث</span>&nbsp;<span class="en">Search</span>
        </button>
        <?php if ($search): ?>
            <a href="?" class="btn btn-outline btn-sm">
                <span class="ar">مسح</span>&nbsp;<span class="en">Clear</span>
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- REPORTS TABLE -->
<div class="card">
    <div class="card-header">
        <span class="ar">قائمة التقارير</span>
        <span class="en">Reports List</span>
    </div>

    <?php if (empty($reports)): ?>
        <div class="text-center" style="padding: 3rem; color: var(--text-muted)">
            <div style="font-size:3rem">📋</div>
            <p class="ar">لا توجد تقارير بعد</p>
            <p class="en">No reports yet</p>
            <a href="<?= APP_URL ?>/modules/report-new.php" class="btn btn-primary mt-2">
                <span class="ar">إنشاء أول تقرير</span>&nbsp;<span class="en">Create First Report</span>
            </a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th><span class="ar">رقم التقرير</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Report No.</span></th>
                        <th><span class="ar">رقم اللوحة</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Plate</span></th>
                        <th><span class="ar">الماركة / الموديل</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Brand / Model</span></th>
                        <th><span class="ar">العميل</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Customer</span></th>
                        <th><span class="ar">التاريخ</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Date</span></th>
                        <th><span class="ar">الحالة</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Status</span></th>
                        <th><span class="ar">إجراءات</span><br><span class="en" style="font-weight:400;font-size:0.72rem">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><strong><?= clean($r['report_number']) ?></strong></td>
                        <td><?= clean($r['plate_number'] ?? '—') ?></td>
                        <td><?= clean($r['brand'] ?? '') ?> <?= clean($r['model'] ?? '') ?> <?= $r['year'] ? '(' . $r['year'] . ')' : '' ?></td>
                        <td><?= clean($r['customer_name'] ?? '—') ?></td>
                        <td><?= $r['date_inspection'] ? date('Y-m-d', strtotime($r['date_inspection'])) : clean($r['created_at']) ?></td>
                        <td>
                            <?php if ($r['status'] === 'complete'): ?>
                                <span class="badge badge-good"><span class="ar">مكتمل</span></span>
                            <?php else: ?>
                                <span class="badge badge-warning"><span class="ar">مسودة</span></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?= APP_URL ?>/modules/report-new.php?id=<?= $r['id'] ?>"
                                   class="btn btn-outline btn-sm"
                                   title="تعديل | Edit">✏️</a>
                                <a href="<?= APP_URL ?>/modules/report-view.php?id=<?= $r['id'] ?>"
                                   class="btn btn-secondary btn-sm"
                                   title="عرض | View">👁️</a>
                                <a href="<?= APP_URL ?>/pdf/generate.php?id=<?= $r['id'] ?>"
                                   class="btn btn-primary btn-sm"
                                   target="_blank"
                                   title="طباعة PDF | Print PDF">🖨️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
