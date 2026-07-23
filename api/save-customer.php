<?php
// ============================================================
// API: Save Customer
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';

requireAuth();
$pdo = db();

$id = (int)($_POST['id'] ?? 0);
$name_ar   = trim($_POST['name_ar'] ?? '');
$name_en   = trim($_POST['name_en'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$address   = trim($_POST['address'] ?? '');
$id_number = trim($_POST['id_number'] ?? '');
$notes     = trim($_POST['notes'] ?? '');
$status    = in_array($_POST['status'] ?? 'active', ['active','inactive']) ? $_POST['status'] : 'active';

// Validation
$errors = [];
if (empty($name_ar)) $errors[] = 'Arabic name is required.';
// if (empty($name_en)) $errors[] = 'English name is required.';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if (empty($errors) && !empty($phone)) {
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

// Save
if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE customers
        SET name_ar = ?, name_en = ?, phone = ?, email = ?,
            address = ?, id_number = ?, notes = ?, status = ?
        WHERE id = ?
    ");
    $result = $stmt->execute([$name_ar, $name_en, $phone, $email, $address, $id_number, $notes, $status, $id]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO customers (name_ar, name_en, phone, email, address, id_number, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([$name_ar, $name_en, $phone, $email, $address, $id_number, $notes, $status]);
    $id = $pdo->lastInsertId();
}

if ($result) {
    echo json_encode(['success' => true, 'message' => 'تم حفظ العميل بنجاح / Customer saved successfully', 'data' => ['id' => $id]]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}