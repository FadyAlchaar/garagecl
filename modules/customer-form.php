<?php
// ============================================================
// [DEBUG] Enable error reporting
// ============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================================
// CUSTOMER FORM (Add / Edit)
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';

requireAuth();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$customer = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        header('Location: ' . APP_URL . '/modules/customers.php');
        exit;
    }
}

$pageTitle = $id ? 'تعديل عميل | Edit Customer' : 'إضافة عميل | Add Customer';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar"><?= $id ? 'تعديل عميل' : 'إضافة عميل' ?></span>
        <span class="en"><?= $id ? 'Edit Customer' : 'Add Customer' ?></span>
    </h1>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <form method="POST" action="<?= APP_URL ?>/api/save-customer.php" id="customerForm">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- Name (Arabic) -->
        <div class="form-group">
            <label class="required"><span class="ar">الاسم (عربي)</span><span class="en">Name (Arabic)</span></label>
            <input type="text" name="name_ar" value="<?= clean($customer['name_ar'] ?? '') ?>" required>
        </div>

        <!-- Name (English) -->
        <div class="form-group">
            <label class="required"><span class="ar">الاسم (إنجليزي)</span><span class="en">Name (English)</span></label>
            <input type="text" name="name_en" value="<?= clean($customer['name_en'] ?? '') ?>" style="direction:ltr" required>
        </div>

        <!-- Phone -->
        <div class="form-group">
            <label><span class="ar">رقم الهاتف</span><span class="en">Phone Number</span></label>
            <input type="tel" name="phone" value="<?= clean($customer['phone'] ?? '') ?>" style="direction:ltr">
        </div>

        <!-- Email -->
        <div class="form-group">
            <label><span class="ar">البريد الإلكتروني</span><span class="en">Email Address</span></label>
            <input type="email" name="email" value="<?= clean($customer['email'] ?? '') ?>" style="direction:ltr">
        </div>

        <!-- Address -->
        <div class="form-group">
            <label><span class="ar">العنوان</span><span class="en">Address</span></label>
            <textarea name="address" rows="3"><?= clean($customer['address'] ?? '') ?></textarea>
        </div>

        <!-- ID Number -->
        <div class="form-group">
            <label><span class="ar">رقم الهوية / الإقامة</span><span class="en">ID / Iqama / Passport</span></label>
            <input type="text" name="id_number" value="<?= clean($customer['id_number'] ?? '') ?>" style="direction:ltr">
        </div>

        <!-- Notes -->
        <div class="form-group">
            <label><span class="ar">ملاحظات</span><span class="en">Notes</span></label>
            <textarea name="notes" rows="3"><?= clean($customer['notes'] ?? '') ?></textarea>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label><span class="ar">الحالة</span><span class="en">Status</span></label>
            <select name="status">
                <option value="active" <?= ($customer['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                    نشط / Active
                </option>
                <option value="inactive" <?= ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                    غير نشط / Inactive
                </option>
            </select>
        </div>

        <div style="display:flex; gap:1rem; margin-top:1rem;">
            <button type="submit" class="btn btn-primary btn-lg">💾 <span class="ar">حفظ</span><span class="en">Save</span></button>
            <a href="<?= APP_URL ?>/modules/customers.php" class="btn btn-secondary">← <span class="ar">رجوع</span><span class="en">Back</span></a>
        </div>
    </form>
</div>

<div id="formAlert" style="margin-top: 1rem;"></div>

<script>
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const alertDiv = document.getElementById('formAlert');

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alertDiv.innerHTML = '<div class="alert alert-success" style="padding:1rem;border-radius:8px;">✅ ' + res.message + '</div>';
            setTimeout(() => {
                window.location = '<?= APP_URL ?>/modules/customers.php';
            }, 1500);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-error" style="padding:1rem;border-radius:8px;">❌ ' + res.message + '</div>';
        }
    })
    .catch(() => {
        alertDiv.innerHTML = '<div class="alert alert-error" style="padding:1rem;border-radius:8px;">❌ فشل الاتصال / Connection failed</div>';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>