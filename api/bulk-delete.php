<?php
// ============================================================
// API: Bulk Delete Reports
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';

requireAuth();

$pdo = db();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No reports selected']);
    exit;
}

// Validate all IDs are integers
$ids = array_map('intval', $ids);
$ids = array_filter($ids);

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid report IDs']);
    exit;
}

// Delete all related data first (cascade will handle if foreign keys with DELETE CASCADE)
// But we need to delete from all related tables manually if no CASCADE
try {
    $pdo->beginTransaction();
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Delete from child tables first (if CASCADE not set)
    $pdo->prepare("DELETE FROM checklist_items WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM dyno_results WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM brake_results WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM suspension_results WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM inspection_checks WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM body_panels WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM section_notes WHERE report_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM report_diagrams WHERE report_id IN ($placeholders)")->execute($ids);
    
    // Finally delete reports
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    $deleted = $stmt->rowCount();
    
    // Audit log
    auditLog($_SESSION['user_id'] ?? null, 'bulk_delete', 'report', null, "Deleted $deleted reports");
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Deleted $deleted reports"]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}