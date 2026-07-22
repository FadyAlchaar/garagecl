<?php
// ============================================================
// ADVANCED SEARCH
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();

$pdo       = db();
$pageTitle = uiText('search_title');
$results   = [];
$searched  = false;

// Load technicians for filter
$technicians = $pdo->query("SELECT id, full_name_ar, full_name_en FROM users WHERE is_active = 1 ORDER BY full_name_ar")->fetchAll();

if (isPost() || !empty($_GET)) {
    $searched   = true;
    $params     = [];
    $conditions = ['1=1'];

    // Plate
    if (!empty($_REQUEST['plate'])) {
        $conditions[] = 'r.plate_number LIKE :plate';
        $params[':plate'] = '%' . $_REQUEST['plate'] . '%';
    }
    // Chassis
    if (!empty($_REQUEST['chassis'])) {
        $conditions[] = 'r.chassis_number LIKE :chassis';
        $params[':chassis'] = '%' . $_REQUEST['chassis'] . '%';
    }
    // Customer
    if (!empty($_REQUEST['customer'])) {
        $conditions[] = 'r.customer_name LIKE :cust';
        $params[':cust'] = '%' . $_REQUEST['customer'] . '%';
    }
    // Brand
    if (!empty($_REQUEST['brand'])) {
        $conditions[] = 'r.brand LIKE :brand';
        $params[':brand'] = '%' . $_REQUEST['brand'] . '%';
    }
    // Date from
    if (!empty($_REQUEST['date_from'])) {
        $conditions[] = 'r.date_inspection >= :dfrom';
        $params[':dfrom'] = $_REQUEST['date_from'];
    }
    // Date to
    if (!empty($_REQUEST['date_to'])) {
        $conditions[] = 'r.date_inspection <= :dto';
        $params[':dto'] = $_REQUEST['date_to'];
    }
    // Status
    if (!empty($_REQUEST['status']) && in_array($_REQUEST['status'], ['draft','complete'])) {
        $conditions[] = 'r.status = :status';
        $params[':status'] = $_REQUEST['status'];
    }
    // Fuel type
    if (!empty($_REQUEST['fuel_type']) && in_array($_REQUEST['fuel_type'], ['Petrol','Diesel','Electric','Hybrid','Gas'])) {
        $conditions[] = 'r.fuel_type = :fuel';
        $params[':fuel'] = $_REQUEST['fuel_type'];
    }
    // Technician
    if (!empty($_REQUEST['technician_id'])) {
        $conditions[] = 'r.technician_id = :tech';
        $params[':tech'] = (int)$_REQUEST['technician_id'];
    }
    // Year
    if (!empty($_REQUEST['year'])) {
        $conditions[] = 'r.year = :year';
        $params[':year'] = (int)$_REQUEST['year'];
    }

    $where = implode(' AND ', $conditions);
    $sql   = "SELECT r.*, u.full_name_ar as tech_name
              FROM reports r
              LEFT JOIN users u ON r.technician_id = u.id
              WHERE $where
              ORDER BY r.created_at DESC
              LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar"><?= uiText('search_title') ?></span>
        <span class="en"><?= uiText('search_title') ?></span>
    </h1>
</div>

<!-- FILTER FORM -->
<div class="card">
    <div class="card-header">
        <span class="ar"><?= uiText('search_filters') ?></span>
        <span class="en"><?= uiText('search_filters') ?></span>
    </div>
    <form method="GET" action="">
        <div class="grid-3">
            <div class="form-group">
                <label><?= uiText('search_plate') ?></label>
                <input type="text" name="plate" value="<?= clean($_REQUEST['plate'] ?? '') ?>" placeholder="ABC 1234">
            </div>
            <div class="form-group">
                <label><?= uiText('search_chassis') ?></label>
                <input type="text" name="chassis" value="<?= clean($_REQUEST['chassis'] ?? '') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><?= uiText('search_customer') ?></label>
                <input type="text" name="customer" value="<?= clean($_REQUEST['customer'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= uiText('search_brand') ?></label>
                <input type="text" name="brand" value="<?= clean($_REQUEST['brand'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= uiText('search_date_from') ?></label>
                <input type="date" name="date_from" value="<?= clean($_REQUEST['date_from'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= uiText('search_date_to') ?></label>
                <input type="date" name="date_to" value="<?= clean($_REQUEST['date_to'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= uiText('search_status') ?></label>
                <select name="status">
                    <option value=""><?= uiText('all') ?></option>
                    <option value="complete" <?= ($_REQUEST['status'] ?? '')==='complete'?'selected':'' ?>>
                        ✅ <?= uiText('status_complete') ?>
                    </option>
                    <option value="draft" <?= ($_REQUEST['status'] ?? '')==='draft'?'selected':'' ?>>
                        📝 <?= uiText('status_draft') ?>
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label><?= uiText('search_fuel') ?></label>
                <select name="fuel_type">
                    <option value=""><?= uiText('all') ?></option>
                    <?php foreach (FUEL_TYPES as $val => $names): ?>
                    <option value="<?= $val ?>" <?= ($_REQUEST['fuel_type'] ?? '')===$val?'selected':'' ?>>
                        <?= $names['ar'] ?> / <?= $names['en'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= uiText('search_tech') ?></label>
                <select name="technician_id">
                    <option value=""><?= uiText('all') ?></option>
                    <?php foreach ($technicians as $tech): ?>
                    <option value="<?= $tech['id'] ?>" <?= ($_REQUEST['technician_id'] ?? '')==$tech['id']?'selected':'' ?>>
                        <?= clean($tech['full_name_ar']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= uiText('search_year') ?></label>
                <input type="number" name="year" min="1980" max="2030" value="<?= clean($_REQUEST['year'] ?? '') ?>" placeholder="e.g. 2020">
            </div>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.5rem">
            <button type="submit" class="btn btn-primary">
                🔍 <?= uiText('btn_search') ?>
            </button>
            <a href="<?= APP_URL ?>/modules/search.php" class="btn btn-outline">
                <?= uiText('btn_clear') ?>
            </a>
            <?php if ($searched && count($results)): ?>
            <a href="<?= APP_URL ?>/api/export.php?<?= http_build_query($_GET) ?>" class="btn btn-secondary">
                📊 <?= uiText('btn_export_csv') ?>
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- RESULTS -->
<?php if ($searched): ?>
<div class="card">
    <div class="card-header">
        <span class="ar"><?= uiText('search_results') ?></span>
        <span class="en"><?= uiText('search_results') ?></span>
        <span style="margin-left:auto; font-size:0.85rem; color:var(--text-muted);">
            <?= count($results) ?> <?= uiText('records') ?>
        </span>
    </div>

    <?php if (empty($results)): ?>
    <div style="text-align:center;padding:2rem;color:var(--text-muted)">
        <div style="font-size:2.5rem">🔍</div>
        <p class="ar"><?= uiText('no_results') ?></p>
        <p class="en"><?= uiText('no_results') ?></p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="reports-table">
        <thead>
            <tr>
                <th><?= uiText('report_number') ?></th>
                <th><?= uiText('plate_number') ?></th>
                <th><?= uiText('brand') ?> / <?= uiText('model') ?></th>
                <th><?= uiText('fuel') ?></th>
                <th><?= uiText('customer_name') ?></th>
                <th><?= uiText('technician') ?></th>
                <th><?= uiText('date_inspection') ?></th>
                <th><?= uiText('search_status') ?></th>
                <th><?= uiText('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><strong><?= clean($r['report_number']) ?></strong></td>
            <td><?= clean($r['plate_number'] ?? '—') ?></td>
            <td><?= clean($r['brand'] ?? '') ?> <?= clean($r['model'] ?? '') ?> <?= $r['year']?'('.$r['year'].')':'' ?></td>
            <td><?= clean($r['fuel_type'] ?? '—') ?></td>
            <td><?= clean($r['customer_name'] ?? '—') ?></td>
            <td><?= clean($r['tech_name'] ?? '—') ?></td>
            <td><?= $r['date_inspection'] ?? '—' ?></td>
            <td>
                <span class="badge <?= $r['status']==='complete'?'badge-good':'badge-warning' ?>">
                    <?= $r['status'] === 'complete' ? uiText('status_complete') : uiText('status_draft') ?>
                </span>
            </td>
            <td>
                <div style="display:flex;gap:.3rem">
                    <a href="<?= APP_URL ?>/modules/report-view.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm" title="<?= uiText('view') ?>">👁️</a>
                    <a href="<?= APP_URL ?>/pdf/generate.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-primary btn-sm" title="<?= uiText('print_pdf') ?>">🖨️</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>