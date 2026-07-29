<?php
// ============================================================
// API: Save New Service
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';

requireAuth();

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true);

$name_ar = trim($input['name_ar'] ?? '');
$name_en = trim($input['name_en'] ?? '');

if (empty($name_ar)) {
    echo json_encode(['success' => false, 'message' => 'Arabic name is required']);
    exit;
}

// Check duplicate
$check = $pdo->prepare("SELECT id FROM services WHERE name_ar = ? OR name_en = ?");
$check->execute([$name_ar, $name_en]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Service already exists']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO services (name_ar, name_en) VALUES (?, ?)");
$result = $stmt->execute([$name_ar, $name_en]);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Service created successfully',
        'data' => ['id' => $pdo->lastInsertId()]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}