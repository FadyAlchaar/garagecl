<?php
// ============================================================
// CUSTOMER FORM (Add / Edit) with Vehicle Fields & Services
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$customer = [];
$customerServices = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        header('Location: ' . APP_URL . '/modules/customers.php');
        exit;
    }
    
    // Load customer's services
    $stmt = $pdo->prepare("
        SELECT cs.*, s.name_ar, s.name_en 
        FROM customer_services cs
        JOIN services s ON cs.service_id = s.id
        WHERE cs.customer_id = ?
    ");
    $stmt->execute([$id]);
    $customerServices = $stmt->fetchAll();
}

// Get all available services for dropdown
$allServices = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, name_ar")->fetchAll();

$pageTitle = $id ? uiText('edit_customer') : uiText('add_customer');
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .service-tag {
        display: inline-block;
        background: #eef2ff;
        color: #4338ca;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 3px 4px;
        border: 1px solid #c7d2fe;
    }
    .service-tag .remove-service {
        cursor: pointer;
        margin-left: 6px;
        color: #ef4444;
        font-weight: bold;
    }
    .service-tag .remove-service:hover {
        color: #dc2626;
    }
    .service-select-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .service-select-wrapper select {
        flex: 1;
        min-width: 200px;
    }
    .service-select-wrapper .btn {
        white-space: nowrap;
    }
    .service-list {
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    .add-service-form {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 10px;
        padding: 10px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px dashed #d1d5db;
    }
    .add-service-form input {
        flex: 1;
        min-width: 150px;
    }
    .add-service-form .btn {
        white-space: nowrap;
    }
</style>

<div class="page-header">
    <h1>
        <span class="ar"><?= $id ? uiText('edit_customer') : uiText('add_customer') ?></span>
        <span class="en"><?= $id ? uiText('edit_customer') : uiText('add_customer') ?></span>
    </h1>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form method="POST" action="<?= APP_URL ?>/api/save-customer.php" id="customerForm">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="services" id="servicesInput" value="">

        <!-- ===== CUSTOMER INFO ===== -->
        <h3 style="border-bottom: 2px solid var(--primary); padding-bottom: 8px; margin-bottom: 16px;">
            <span class="ar">معلومات العميل</span>
            <span class="en">Customer Information</span>
        </h3>

        <div class="grid-2">
            <div class="form-group">
                <label class="required"><?= uiText('customer_name_ar') ?></label>
                <input type="text" name="name_ar" value="<?= clean($customer['name_ar'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label><?= uiText('customer_name_en') ?></label>
                <input type="text" name="name_en" value="<?= clean($customer['name_en'] ?? '') ?>" style="direction:ltr">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label><?= uiText('phone') ?></label>
                <input type="tel" name="phone" value="<?= clean($customer['phone'] ?? '') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><?= uiText('email') ?></label>
                <input type="email" name="email" value="<?= clean($customer['email'] ?? '') ?>" style="direction:ltr">
            </div>
        </div>

        <div class="form-group">
            <label><?= uiText('address') ?></label>
            <textarea name="address" rows="2"><?= clean($customer['address'] ?? '') ?></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label><?= uiText('id_number') ?></label>
                <input type="text" name="id_number" value="<?= clean($customer['id_number'] ?? '') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><?= uiText('status') ?></label>
                <select name="status">
                    <option value="active" <?= ($customer['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                        <?= uiText('active') ?>
                    </option>
                    <option value="inactive" <?= ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                        <?= uiText('inactive') ?>
                    </option>
                </select>
            </div>
        </div>

        <!-- ===== VEHICLE INFO ===== -->
        <h3 style="border-bottom: 2px solid var(--primary); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px;">
            <span class="ar">🚗 معلومات المركبة</span>
            <span class="en">Vehicle Information</span>
        </h3>

        <div class="grid-2">
            <div class="form-group">
                <label><?= uiText('plate_number') ?></label>
                <input type="text" name="plate_number" value="<?= clean($customer['plate_number'] ?? '') ?>" placeholder="ABC 1234">
            </div>
            <div class="form-group">
                <label><?= uiText('chassis_number') ?></label>
                <input type="text" name="chassis_number" value="<?= clean($customer['chassis_number'] ?? '') ?>" style="direction:ltr" placeholder="VIN / Chassis Number">
            </div>
        </div>

        <!-- ===== SERVICES ===== -->
        <h3 style="border-bottom: 2px solid var(--primary); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px;">
            <span class="ar">🔧 الخدمات المطلوبة</span>
            <span class="en">Required Services</span>
        </h3>

        <!-- Add Service Dropdown -->
        <div class="service-select-wrapper">
            <select id="serviceSelect" class="form-control">
                <option value="">— <?= uiText('select_service') ?> —</option>
                <?php foreach ($allServices as $s): ?>
                <option value="<?= $s['id'] ?>"><?= clean($s['name_ar']) ?> / <?= clean($s['name_en']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-secondary" onclick="addService()">
                + <span class="ar">إضافة</span><span class="en">Add</span>
            </button>
            <button type="button" class="btn btn-outline" onclick="showAddServiceForm()">
                ✏️ <span class="ar">خدمة جديدة</span><span class="en">New Service</span>
            </button>
        </div>

        <!-- Add New Service Form (hidden by default) -->
        <div id="addServiceForm" class="add-service-form" style="display:none;">
            <input type="text" id="newServiceAr" placeholder="الاسم بالعربية / Arabic Name">
            <input type="text" id="newServiceEn" placeholder="الاسم بالإنجليزية / English Name" style="direction:ltr">
            <button type="button" class="btn btn-primary" onclick="saveNewService()">
                💾 <span class="ar">حفظ</span><span class="en">Save</span>
            </button>
            <button type="button" class="btn btn-outline" onclick="hideAddServiceForm()">
                ✕ <span class="ar">إلغاء</span><span class="en">Cancel</span>
            </button>
        </div>

        <!-- Selected Services List -->
        <div id="selectedServices" class="service-list">
            <?php foreach ($customerServices as $cs): ?>
            <span class="service-tag" data-service-id="<?= $cs['service_id'] ?>">
                <?= clean($cs['name_ar']) ?> / <?= clean($cs['name_en']) ?>
                <span class="remove-service" onclick="removeService(this)">✕</span>
            </span>
            <?php endforeach; ?>
        </div>

        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:8px;">
            <span class="ar">💡 اختر الخدمات المطلوبة من القائمة أو أضف خدمة جديدة</span>
            <span class="en">💡 Select required services from the list or add a new one</span>
        </div>

        <!-- ===== NOTES ===== -->
        <div class="form-group" style="margin-top: 20px;">
            <label><?= uiText('notes') ?></label>
            <textarea name="notes" rows="3"><?= clean($customer['notes'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; gap:1rem; margin-top:1rem;">
            <button type="submit" class="btn btn-primary btn-lg">💾 <?= uiText('save') ?></button>
            <a href="<?= APP_URL ?>/modules/customers.php" class="btn btn-secondary">← <?= uiText('back') ?></a>
        </div>
    </form>
</div>

<div id="formAlert" style="margin-top: 1rem;"></div>

<script>
// ============================================================
// SERVICES MANAGEMENT
// ============================================================

let selectedServices = [];

// Initialize from existing services
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#selectedServices .service-tag').forEach(el => {
        selectedServices.push(parseInt(el.dataset.serviceId));
    });
    updateServicesInput();
});

function addService() {
    const select = document.getElementById('serviceSelect');
    const serviceId = parseInt(select.value);
    
    if (!serviceId) {
        toastWarning('<?= uiText('select_service_first') ?>');
        return;
    }
    
    if (selectedServices.includes(serviceId)) {
        toastWarning('<?= uiText('service_already_added') ?>');
        return;
    }
    
    selectedServices.push(serviceId);
    const text = select.options[select.selectedIndex].text;
    addServiceTag(serviceId, text);
    select.value = '';
    updateServicesInput();
    toastSuccess('<?= uiText('service_added') ?>');
}

function addServiceTag(id, text) {
    const container = document.getElementById('selectedServices');
    const tag = document.createElement('span');
    tag.className = 'service-tag';
    tag.dataset.serviceId = id;
    tag.innerHTML = text + ' <span class="remove-service" onclick="removeService(this)">✕</span>';
    container.appendChild(tag);
}

function removeService(element) {
    const tag = element.closest('.service-tag');
    const id = parseInt(tag.dataset.serviceId);
    selectedServices = selectedServices.filter(s => s !== id);
    tag.remove();
    updateServicesInput();
}

function updateServicesInput() {
    document.getElementById('servicesInput').value = selectedServices.join(',');
}

// ============================================================
// ADD NEW SERVICE (AJAX)
// ============================================================

function showAddServiceForm() {
    document.getElementById('addServiceForm').style.display = 'flex';
}

function hideAddServiceForm() {
    document.getElementById('addServiceForm').style.display = 'none';
    document.getElementById('newServiceAr').value = '';
    document.getElementById('newServiceEn').value = '';
}

function saveNewService() {
    const nameAr = document.getElementById('newServiceAr').value.trim();
    const nameEn = document.getElementById('newServiceEn').value.trim();
    
    if (!nameAr) {
        toastWarning('<?= uiText('please_enter_service_name_arabic') ?>');
        return;
    }
    
    fetch('<?= APP_URL ?>/api/save-service.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name_ar: nameAr, name_en: nameEn })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Add to dropdown
            const select = document.getElementById('serviceSelect');
            const option = document.createElement('option');
            option.value = res.data.id;
            option.text = nameAr + ' / ' + (nameEn || nameAr);
            select.appendChild(option);
            
            // Auto-select the new service
            select.value = res.data.id;
            
            // Clear form and hide
            hideAddServiceForm();
            
            // Add to selected
            selectedServices.push(parseInt(res.data.id));
            addServiceTag(res.data.id, option.text);
            updateServicesInput();
            
            toastSuccess('<?= uiText('service_created') ?>');
        } else {
            toastError(res.message || '<?= uiText('save_failed') ?>');
        }
    })
    .catch(() => toastError('<?= uiText('save_failed') ?>'));
}

// ============================================================
// FORM SUBMISSION
// ============================================================

document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const alertDiv = document.getElementById('formAlert');

    // Ensure services are included
    updateServicesInput();

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
        alertDiv.innerHTML = '<div class="alert alert-error" style="padding:1rem;border-radius:8px;">❌ <?= uiText('save_failed') ?></div>';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>