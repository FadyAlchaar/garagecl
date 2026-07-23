<?php
// ============================================================
// DIAGRAM LIBRARY - Browse, upload, and manage diagrams
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();

$pdo = db();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        // Get image path to delete file
        $stmt = $pdo->prepare("SELECT image_path, thumbnail_path FROM diagrams WHERE id = ?");
        $stmt->execute([$id]);
        $diagram = $stmt->fetch();
        if ($diagram) {
            // Delete files
            if (!empty($diagram['image_path']) && file_exists(APP_ROOT . '/' . $diagram['image_path'])) {
                unlink(APP_ROOT . '/' . $diagram['image_path']);
            }
            if (!empty($diagram['thumbnail_path']) && file_exists(APP_ROOT . '/' . $diagram['thumbnail_path'])) {
                unlink(APP_ROOT . '/' . $diagram['thumbnail_path']);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM diagrams WHERE id = ?");
        $stmt->execute([$id]);
        toastSuccess(uiText('diagram_deleted'));
        header('Location: ' . APP_URL . '/modules/diagram-library.php');
        exit;
    } else {
        // Show confirmation
        echo '<div class="alert alert-warning" style="margin: 1rem 0; padding: 1.5rem; text-align:center; border-radius:8px;">';
        echo '<p style="font-size:1.1rem;">⚠️ ' . uiText('confirm_delete_diagram') . '</p>';
        echo '<a href="?delete=' . $id . '&confirm=yes" class="btn btn-danger" style="margin-right:10px;">' . uiText('confirm_delete') . '</a> ';
        echo '<a href="' . APP_URL . '/modules/diagram-library.php" class="btn btn-secondary">' . uiText('cancel') . '</a>';
        echo '</div>';
    }
}

// Get all diagrams
$category = $_GET['category'] ?? 'all';
$sql = "SELECT * FROM diagrams WHERE is_active = 1";
$params = [];
if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY category, sort_order, name_ar";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$diagrams = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM diagrams WHERE is_active = 1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = uiText('diagram_library');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar"><?= uiText('diagram_library') ?></span>
        <span class="en"><?= uiText('diagram_library') ?></span>
    </h1>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/modules/diagram-upload.php" class="btn btn-primary btn-lg">
            + <span class="ar"><?= uiText('upload_diagram') ?></span>
            <span class="en"><?= uiText('upload_diagram') ?></span>
        </a>
    </div>
</div>

<!-- Category Filter -->
<div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem">
    <a href="?category=all" class="btn btn-sm <?= $category === 'all' ? 'btn-primary' : 'btn-outline' ?>">
        <?= uiText('all') ?>
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="?category=<?= $cat ?>" class="btn btn-sm <?= $category === $cat ? 'btn-primary' : 'btn-outline' ?>">
        <?= uiText('category_' . $cat) ?? ucfirst($cat) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Diagram Grid -->
<?php if (empty($diagrams)): ?>
<div style="text-align:center;padding:3rem;color:var(--text-muted)">
    <div style="font-size:3rem">🖼️</div>
    <p><?= uiText('no_diagrams') ?></p>
    <a href="<?= APP_URL ?>/modules/diagram-upload.php" class="btn btn-primary">
        <?= uiText('upload_diagram') ?>
    </a>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.5rem">
    <?php foreach ($diagrams as $d): ?>
    <div class="card" style="padding:1rem;text-align:center;transition:all 0.2s">
        <?php if (!empty($d['thumbnail_path']) && file_exists(APP_ROOT . '/' . $d['thumbnail_path'])): ?>
        <img src="<?= APP_URL . '/' . $d['thumbnail_path'] ?>" 
             alt="<?= $d['name_ar'] ?>" 
             style="max-width:100%;max-height:150px;border-radius:8px;border:1px solid var(--border)">
        <?php elseif (!empty($d['image_path']) && file_exists(APP_ROOT . '/' . $d['image_path'])): ?>
        <img src="<?= APP_URL . '/' . $d['image_path'] ?>" 
             alt="<?= $d['name_ar'] ?>" 
             style="max-width:100%;max-height:150px;border-radius:8px;border:1px solid var(--border)">
        <?php else: ?>
        <div style="height:150px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:3rem;color:#ccc">
            🖼️
        </div>
        <?php endif; ?>
        
        <div style="font-weight:600;margin-top:.5rem">
            <span class="ar"><?= $d['name_ar'] ?></span><br>
            <span class="en" style="font-size:0.8rem;color:var(--text-muted)"><?= $d['name_en'] ?></span>
        </div>
        
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:.25rem">
            <?php if (!empty($d['category'])): ?>
            <span class="badge badge-muted"><?= uiText('category_' . $d['category']) ?? ucfirst($d['category']) ?></span>
            <?php endif; ?>
        </div>
        
        <div style="display:flex;gap:0.5rem;justify-content:center;margin-top:.5rem">
            <a href="<?= APP_URL . '/' . $d['image_path'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="<?= uiText('view') ?>">👁️</a>
            <a href="?delete=<?= $d['id'] ?>" class="btn btn-danger btn-sm" title="<?= uiText('delete') ?>">🗑️</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>