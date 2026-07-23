<?php
// api/save-report-diagrams.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$reportId = (int)($_POST['report_id'] ?? 0);
$diagrams = $_POST['diagrams'] ?? [];

if (!$reportId) {
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit;
}

$pdo = db();

// Delete existing
$pdo->prepare("DELETE FROM report_diagrams WHERE report_id = ?")->execute([$reportId]);

// Insert new
$stmt = $pdo->prepare("INSERT INTO report_diagrams (report_id, diagram_id) VALUES (?, ?)");
foreach ($diagrams as $did) {
    $stmt->execute([$reportId, (int)$did]);
}

echo json_encode(['success' => true, 'message' => 'Diagrams saved successfully']);
?>