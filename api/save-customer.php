<?php
// ============================================================
// API: Save or Delete Customer (with Vehicle & Services)
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();
$pdo = db();

// Determine action: DELETE or SAVE
if (isset($_POST['_method']) && strtoupper($_POST['_method']) === 'DELETE') {
    // DELETE
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => uiText('customer_deleted')]);
    exit;
}

// Otherwise SAVE (POST)
$id = (int)($_POST['id'] ?? 0);
$name_ar   = trim($_POST['name_ar'] ?? '');
$name_en   = trim($_POST['name_en'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$address   = trim($_POST['address'] ?? '');
$id_number = trim($_POST['id_number'] ?? '');
$notes     = trim($_POST['notes'] ?? '');
$status    = in_array($_POST['status'] ?? 'active', ['active','inactive']) ? $_POST['status'] : 'active';

// --- NEW VEHICLE FIELDS ---
$plate_number   = trim($_POST['plate_number'] ?? '');
$chassis_number = trim($_POST['chassis_number'] ?? '');

// --- SERVICES (comma-separated IDs from hidden input) ---
$servicesInput = $_POST['services'] ?? '';
$serviceIds = array_filter(array_map('intval', explode(',', $servicesInput)));

// Validation
$errors = [];
if (empty($name_ar)) $errors[] = 'Arabic name is required.';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if (empty($errors) && !empty($phone)) {
    // Check unique phone (except self)
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id != ?");
    $stmt->execute([$phone, $id]);
    if ($stmt->fetch()) $errors[] = 'Phone number already exists.';
}
if (empty($errors) && !empty($email)) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) $errors[] = 'Email already exists.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Save customer data (including new fields)
if ($id > 0) {
    // UPDATE
    $stmt = $pdo->prepare("
        UPDATE customers
        SET name_ar = ?, name_en = ?, phone = ?, email = ?,
            address = ?, id_number = ?, notes = ?, status = ?,
            plate_number = ?, chassis_number = ?
        WHERE id = ?
    ");
    $result = $stmt->execute([$name_ar, $name_en, $phone, $email, $address, $id_number, $notes, $status, $plate_number, $chassis_number, $id]);
} else {
    // INSERT
    $stmt = $pdo->prepare("
        INSERT INTO customers (name_ar, name_en, phone, email, address, id_number, notes, status, plate_number, chassis_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([$name_ar, $name_en, $phone, $email, $address, $id_number, $notes, $status, $plate_number, $chassis_number]);
    $id = $pdo->lastInsertId();
}

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error while saving customer.']);
    exit;
}

// --- SAVE SERVICES ---
// Delete existing services for this customer
$pdo->prepare("DELETE FROM customer_services WHERE customer_id = ?")->execute([$id]);

// Insert new services
if (!empty($serviceIds)) {
    $stmt = $pdo->prepare("INSERT INTO customer_services (customer_id, service_id) VALUES (?, ?)");
    foreach ($serviceIds as $sid) {
        $stmt->execute([$id, $sid]);
    }
}

// Log and respond
auditLog($_SESSION['user_id'] ?? null, 'customer_save', 'customer', $id, 'Customer saved with services');
echo json_encode([
    'success' => true,
    'message' => uiText('customer_saved'),
    'data' => ['id' => $id]
]);