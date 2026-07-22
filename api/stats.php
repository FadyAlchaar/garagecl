<?php
// ============================================================
// API: Statistics for Dashboard Charts
// Returns JSON data for Chart.js
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';

// Authenticate (optional but recommended)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = db();

// ---- 1. Reports by Month (last 6 months) ----
$monthly = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        COUNT(*) AS count
    FROM reports
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll();

$months = [];
$counts = [];
foreach ($monthly as $row) {
    $months[] = $row['month'];
    $counts[] = (int)$row['count'];
}

// ---- 2. Status Distribution ----
$statusData = $pdo->query("
    SELECT status, COUNT(*) AS count 
    FROM reports 
    GROUP BY status
")->fetchAll();

$statusLabels = [];
$statusValues = [];
foreach ($statusData as $row) {
    $statusLabels[] = $row['status'] === 'complete' ? 'مكتمل / Complete' : 'مسودة / Draft';
    $statusValues[] = (int)$row['count'];
}

// ---- 3. Top Brands (top 5) ----
$brandData = $pdo->query("
    SELECT brand, COUNT(*) AS count
    FROM reports
    WHERE brand != ''
    GROUP BY brand
    ORDER BY count DESC
    LIMIT 5
")->fetchAll();

$brandLabels = [];
$brandValues = [];
foreach ($brandData as $row) {
    $brandLabels[] = $row['brand'];
    $brandValues[] = (int)$row['count'];
}

// ---- 4. Fuel Type Distribution ----
$fuelData = $pdo->query("
    SELECT fuel_type, COUNT(*) AS count
    FROM reports
    GROUP BY fuel_type
")->fetchAll();

$fuelLabels = [];
$fuelValues = [];
foreach ($fuelData as $row) {
    $fuelLabels[] = $row['fuel_type'] ?? 'غير محدد';
    $fuelValues[] = (int)$row['count'];
}

// ---- Return JSON ----
header('Content-Type: application/json');
echo json_encode([
    'months' => $months,
    'monthly_counts' => $counts,
    'status_labels' => $statusLabels,
    'status_counts' => $statusValues,
    'brand_labels' => $brandLabels,
    'brand_counts' => $brandValues,
    'fuel_labels' => $fuelLabels,
    'fuel_counts' => $fuelValues,
]);