<?php
// ============================================================
// CUSTOMER LIST (with Vehicle & Services)
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();

$pdo = db();

// Handle search
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE name_ar LIKE ? OR name_en LIKE ? OR phone LIKE ? OR email LIKE ? OR plate_number LIKE ? OR chassis_number LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like, $like, $like];
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Fetch records
$sql = "SELECT * FROM customers $where ORDER BY name_ar ASC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$customers = $stmt->fetchAll();

$pageTitle = uiText('customers');
require_once __DIR__ . '/../includes/header.php';

// Handle delete via GET (with confirmation)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        // Also delete related services (cascade will handle if FK with CASCADE)
        toastSuccess(uiText('customer_deleted'));
        header('Location: ' . APP_URL . '/modules/customers.php');
        exit;
    } else {
        // Show confirmation
        echo '<div class="alert alert-warning" style="margin: 1rem 0; padding: 1.5rem; text-align:center; border-radius:8px;">';
        echo '<p style="font-size:1.1rem;">⚠️ ' . uiText('confirm_delete_customer') . '</p>';
        echo '<a href="?delete=' . $id . '&confirm=yes" class="btn btn-danger" style="margin-right:10px;">' . uiText('confirm_delete') . '</a> ';
        echo '<a href="' . APP_URL . '/modules/customers.php" class="btn btn-secondary">' . uiText('cancel') . '</a>';
        echo '</div>';
    }
}
?>

<div class="page-header">
    <h1>
        <span class="ar"><?= uiText('customers') ?></span>
        <span class="en"><?= uiText('customers') ?></span>
    </h1>
    <a href="<?= APP_URL ?>/modules/customer-form.php" class="btn btn-primary btn-lg">
        + <span class="ar"><?= uiText('add_customer') ?></span>
        <span class="en"><?= uiText('add_customer') ?></span>
    </a>
</div>

<!-- Search Bar -->
<form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
    <input type="text" name="search" placeholder="<?= uiText('search') ?> <?= uiText('customers') ?>" value="<?= clean($search) ?>">
    <button type="submit" class="btn btn-secondary"><?= uiText('search') ?></button>
    <?php if ($search): ?>
        <a href="<?= APP_URL ?>/modules/customers.php" class="btn btn-outline"><?= uiText('clear') ?></a>
    <?php endif; ?>
</form>

<!-- Customers Table -->
<div class="card">
    <div class="card-header">
        <span class="ar">قائمة العملاء</span>
        <span class="en">Customer List</span>
        <span style="margin-left: auto; font-size: 0.85rem; color: var(--text-muted);">
            <?= $total ?> <?= uiText('customers') ?>
        </span>
    </div>

    <?php if (empty($customers)): ?>
        <div style="text-align:center; padding: 3rem; color: var(--text-muted);">
            <div style="font-size:3rem">👥</div>
            <p><?= uiText('no_customers') ?></p>
            <a href="<?= APP_URL ?>/modules/customer-form.php" class="btn btn-primary">
                <?= uiText('add_customer') ?>
            </a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= uiText('customer_name_ar') ?></th>
                        <th><?= uiText('customer_name_en') ?></th>
                        <th><?= uiText('phone') ?></th>
                        <th><?= uiText('email') ?></th>
                        <th><?= uiText('plate_number') ?></th>
                        <th><?= uiText('services') ?></th>
                        <th><?= uiText('status') ?></th>
                        <th><?= uiText('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $i => $c): ?>
                    <?php
                        // Fetch services for this customer
                        $stmt = $pdo->prepare("
                            SELECT s.name_ar, s.name_en 
                            FROM customer_services cs 
                            JOIN services s ON cs.service_id = s.id 
                            WHERE cs.customer_id = ?
                        ");
                        $stmt->execute([$c['id']]);
                        $svcs = $stmt->fetchAll();
                        $serviceNames = array_map(fn($s) => clean($s['name_ar']), $svcs);
                        $serviceList = !empty($serviceNames) ? implode(', ', $serviceNames) : '—';
                    ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><strong><?= clean($c['name_ar']) ?></strong></td>
                        <td><?= clean($c['name_en']) ?></td>
                        <td dir="ltr"><?= clean($c['phone']) ?></td>
                        <td dir="ltr"><?= clean($c['email']) ?></td>
                        <td dir="ltr"><?= clean($c['plate_number'] ?? '—') ?></td>
                        <td style="font-size:0.85rem;"><?= $serviceList ?></td>
                        <td>
                            <span class="badge badge-<?= $c['status'] === 'active' ? 'good' : 'warning' ?>">
                                <?= $c['status'] === 'active' ? uiText('active') : uiText('inactive') ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?= APP_URL ?>/modules/customer-form.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" title="<?= uiText('edit') ?>">✏️</a>
                                <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" title="<?= uiText('delete') ?>" onclick="return confirm('<?= uiText('confirm_delete_customer') ?>?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total > $limit): ?>
            <div style="display:flex; gap:0.5rem; justify-content:center; margin-top: 1rem; flex-wrap:wrap;">
                <?php
                $totalPages = ceil($total / $limit);
                for ($p = 1; $p <= $totalPages; $p++):
                    $active = $p === $page ? 'btn-primary' : 'btn-outline';
                ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                   class="btn btn-sm <?= $active ?>"
                   style="min-width:36px;">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>