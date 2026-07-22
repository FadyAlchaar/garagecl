<?php
// ============================================================
// [DEBUG] Enable error reporting to see what's wrong
// ============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================================
// CUSTOMER LIST
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';

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
    $where = "WHERE name_ar LIKE ? OR name_en LIKE ? OR phone LIKE ? OR email LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
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

$pageTitle = 'العملاء | Customers';
require_once __DIR__ . '/../includes/header.php';

// Handle delete via GET (with confirmation)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        // Use simple message instead of toast if function doesn't exist
        echo '<div class="alert alert-success" style="margin: 1rem 0; padding: 1rem; text-align:center; border-radius:8px;">✅ تم حذف العميل بنجاح / Customer deleted successfully</div>';
        // Redirect after 2 seconds
        echo '<meta http-equiv="refresh" content="2;url=' . APP_URL . '/modules/customers.php">';
        exit;
    } else {
        // Show confirmation
        echo '<div class="alert alert-warning" style="margin: 1rem 0; padding: 1.5rem; text-align:center; border-radius:8px;">';
        echo '<p style="font-size:1.1rem;">⚠️ هل أنت متأكد من حذف هذا العميل؟ / Are you sure you want to delete this customer?</p>';
        echo '<a href="?delete=' . $id . '&confirm=yes" class="btn btn-danger" style="margin-right:10px;">نعم / Yes</a> ';
        echo '<a href="' . APP_URL . '/modules/customers.php" class="btn btn-secondary">إلغاء / Cancel</a>';
        echo '</div>';
    }
}
?>

<div class="page-header">
    <h1>
        <span class="ar">العملاء</span>
        <span class="en">Customers</span>
    </h1>
    <a href="<?= APP_URL ?>/modules/customer-form.php" class="btn btn-primary btn-lg">
        + <span class="ar">إضافة عميل</span>
        <span class="en">Add Customer</span>
    </a>
</div>

<!-- Search Bar -->
<form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
    <input type="text" name="search" placeholder="ابحث بالاسم أو الهاتف أو البريد... | Search by name, phone, email..." value="<?= clean($search) ?>">
    <button type="submit" class="btn btn-secondary">بحث / Search</button>
    <?php if ($search): ?>
        <a href="<?= APP_URL ?>/modules/customers.php" class="btn btn-outline">مسح / Clear</a>
    <?php endif; ?>
</form>

<!-- Customers Table -->
<div class="card">
    <div class="card-header">
        <span class="ar">قائمة العملاء</span>
        <span class="en">Customer List</span>
        <span style="margin-left: auto; font-size: 0.85rem; color: var(--text-muted);">
            <?= $total ?> عميل / Customer<?= $total > 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($customers)): ?>
        <div style="text-align:center; padding: 3rem; color: var(--text-muted);">
            <div style="font-size:3rem">👥</div>
            <p style="margin: 1rem 0;">
                <span class="ar">لا يوجد عملاء</span>
                <span class="en">No customers found</span>
            </p>
            <a href="<?= APP_URL ?>/modules/customer-form.php" class="btn btn-primary">
                <span class="ar">إضافة عميل</span>
                <span class="en">Add Customer</span>
            </a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><span class="ar">الاسم (عربي)</span><span class="en">Name (Arabic)</span></th>
                        <th><span class="ar">الاسم (إنجليزي)</span><span class="en">Name (English)</span></th>
                        <th><span class="ar">الهاتف</span><span class="en">Phone</span></th>
                        <th><span class="ar">البريد</span><span class="en">Email</span></th>
                        <th><span class="ar">الحالة</span><span class="en">Status</span></th>
                        <th><span class="ar">الإجراءات</span><span class="en">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $i => $c): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><strong><?= clean($c['name_ar']) ?></strong></td>
                        <td><?= clean($c['name_en']) ?></td>
                        <td dir="ltr"><?= clean($c['phone']) ?></td>
                        <td dir="ltr"><?= clean($c['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $c['status'] === 'active' ? 'good' : 'warning' ?>">
                                <?= $c['status'] === 'active' ? 'نشط / Active' : 'غير نشط / Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?= APP_URL ?>/modules/customer-form.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" title="تعديل / Edit">✏️</a>
                                <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" title="حذف / Delete">🗑️</a>
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