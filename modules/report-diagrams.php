<?php
// ============================================================
// REPORT DIAGRAMS - Select diagrams for a report
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();

$pdo = db();
$reportId = (int)($_GET['report_id'] ?? 0);

if (!$reportId) {
    header('Location: ' . APP_URL . '/modules/dashboard.php');
    exit;
}

// Get report info
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();
if (!$report) {
    header('Location: ' . APP_URL . '/modules/dashboard.php');
    exit;
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagrams = $_POST['diagrams'] ?? [];
    
    // Delete existing
    $pdo->prepare("DELETE FROM report_diagrams WHERE report_id = ?")->execute([$reportId]);
    
    // Insert new
    if (!empty($diagrams)) {
        $stmt = $pdo->prepare("INSERT INTO report_diagrams (report_id, diagram_id) VALUES (?, ?)");
        foreach ($diagrams as $did) {
            $stmt->execute([$reportId, (int)$did]);
        }
    }
    
    // Set flash message
    $_SESSION['flash_message'] = uiText('diagrams_saved');
    $_SESSION['flash_type'] = 'success';
    
    header('Location: ' . APP_URL . '/modules/report-view.php?id=' . $reportId);
    exit;
}

// Get all available diagrams
$allDiagrams = $pdo->query("SELECT * FROM diagrams WHERE is_active = 1 ORDER BY category, sort_order, name_ar")->fetchAll();

// Get currently selected diagrams
$stmt = $pdo->prepare("SELECT diagram_id FROM report_diagrams WHERE report_id = ?");
$stmt->execute([$reportId]);
$selectedDiagrams = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = uiText('select_diagrams');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar"><?= uiText('select_diagrams') ?></span>
        <span class="en"><?= uiText('select_diagrams') ?></span>
    </h1>
    <div>
        <span style="color:var(--text-muted);font-size:0.85rem">
            <?= uiText('report') ?> #<?= clean($report['report_number']) ?>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="ar">🖼️ <?= uiText('select_diagrams') ?></span>
        <span class="en">🖼️ <?= uiText('select_diagrams') ?></span>
        <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted);font-weight:normal">
            <?= count($selectedDiagrams) ?> <?= uiText('selected') ?>
        </span>
    </div>

    <form method="POST">
        <p style="color:var(--text-muted);margin-bottom:1rem">
            <span class="ar">✅ اختر الرسوم البيانية التي تريد إضافتها إلى هذا التقرير</span>
            <span class="en">Select the diagrams you want to include in this report</span>
        </p>

        <?php if (empty($allDiagrams)): ?>
        <div style="text-align:center;padding:2rem;color:var(--text-muted)">
            <p><?= uiText('no_diagrams') ?></p>
            <a href="<?= APP_URL ?>/modules/diagram-upload.php" class="btn btn-primary">
                <?= uiText('upload_diagram') ?>
            </a>
        </div>
        <?php else: ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem">
            <?php 
            $currentCategory = '';
            foreach ($allDiagrams as $d): 
                if ($currentCategory !== $d['category']) {
                    $currentCategory = $d['category'];
                    $catLabel = !empty($currentCategory) ? (uiText('category_' . $currentCategory) ?? ucfirst($currentCategory)) : 'Other';
                }
                $checked = in_array($d['id'], $selectedDiagrams);
            ?>
            <label class="diagram-select-item" style="border:2px solid <?= $checked ? 'var(--primary)' : 'var(--border)' ?>;border-radius:8px;padding:0.75rem;text-align:center;cursor:pointer;transition:all 0.2s;background:var(--bg-card)">
                <input type="checkbox" name="diagrams[]" value="<?= $d['id'] ?>" <?= $checked ? 'checked' : '' ?>
                       style="display:none" onchange="this.parentElement.style.borderColor=this.checked?'var(--primary)':'var(--border)'">
                
                <?php if (!empty($d['thumbnail_path']) && file_exists(APP_ROOT . '/' . $d['thumbnail_path'])): ?>
                <img src="<?= APP_URL . '/' . $d['thumbnail_path'] ?>" 
                     alt="<?= $d['name_ar'] ?>" 
                     style="max-width:100%;max-height:80px;border-radius:4px">
                <?php else: ?>
                <div style="height:80px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:2rem;color:#ccc">
                    🖼️
                </div>
                <?php endif; ?>
                
                <div style="font-size:0.85rem;font-weight:600;margin-top:.25rem">
                    <span class="ar"><?= $d['name_ar'] ?></span>
                </div>
                <div style="font-size:0.7rem;color:var(--text-muted)">
                    <span class="en"><?= $d['name_en'] ?></span>
                </div>
                <?php if (!empty($d['category'])): ?>
                <div style="font-size:0.6rem;color:var(--text-muted);margin-top:2px">
                    <span class="badge badge-muted"><?= $catLabel ?></span>
                </div>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>

        <div style="display:flex;gap:1rem;margin-top:1.5rem">
            <button type="submit" class="btn btn-primary">💾 <?= uiText('save') ?></button>
            <a href="<?= APP_URL ?>/modules/report-view.php?id=<?= $reportId ?>" class="btn btn-secondary">← <?= uiText('back') ?></a>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>