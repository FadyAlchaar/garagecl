<?php
// ============================================================
// DIAGRAM UPLOAD - Upload new diagrams
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/dictionaries.php';

requireAuth();

$pdo = db();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ar = trim($_POST['name_ar'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $key = trim($_POST['key'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Generate key if not provided
    if (empty($key)) {
        $key = strtolower(str_replace(' ', '_', $name_en));
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
    }

    $errors = [];
    if (empty($name_ar)) $errors[] = 'Arabic name is required.';
    if (empty($name_en)) $errors[] = 'English name is required.';
    if (empty($category)) $errors[] = 'Category is required.';

    // Check if key exists
    $check = $pdo->prepare("SELECT id FROM diagrams WHERE `key` = ?");
    $check->execute([$key]);
    if ($check->fetch()) {
        $errors[] = 'Key already exists. Please use a unique key.';
    }

    // Handle file upload
    $image_path = '';
    $thumbnail_path = '';

    if (isset($_FILES['diagram_image']) && $_FILES['diagram_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['diagram_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid file type. Allowed: SVG, PNG, JPG, GIF, WEBP';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File too large. Max 5MB.';
        } else {
            // Create directory if it doesn't exist
            $uploadDir = APP_ROOT . '/assets/diagrams/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filename = $key . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $image_path = 'assets/diagrams/' . $filename;
                
                // Generate thumbnail (only for non-SVG)
                if ($ext !== 'svg') {
                    $thumbPath = $uploadDir . 'thumb_' . $filename;
                    $thumbnail_path = 'assets/diagrams/thumb_' . $filename;
                    
                    // Simple thumbnail using GD
                    $src = imagecreatefromstring(file_get_contents($targetPath));
                    if ($src) {
                        list($width, $height) = getimagesize($targetPath);
                        $thumbWidth = 300;
                        $thumbHeight = ($height / $width) * $thumbWidth;
                        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
                        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                        
                        switch ($ext) {
                            case 'png': imagepng($thumb, $thumbPath); break;
                            case 'jpg':
                            case 'jpeg': imagejpeg($thumb, $thumbPath, 80); break;
                            case 'gif': imagegif($thumb, $thumbPath); break;
                            case 'webp': imagewebp($thumb, $thumbPath, 80); break;
                        }
                        imagedestroy($thumb);
                        imagedestroy($src);
                    } else {
                        // If thumbnail generation fails, use original
                        $thumbnail_path = $image_path;
                    }
                } else {
                    // SVG: use original as thumbnail
                    $thumbnail_path = $image_path;
                }
            } else {
                $errors[] = 'Failed to upload file.';
            }
        }
    } else {
        $errors[] = 'Please select an image to upload.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO diagrams (`key`, name_ar, name_en, category, image_path, thumbnail_path, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$key, $name_ar, $name_en, $category, $image_path, $thumbnail_path, $sort_order]);
        $message = uiText('diagram_saved');
        $messageType = 'success';
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Categories for dropdown
$categories = [
    'chassis' => ['ar' => 'هيكل السيارة', 'en' => 'Chassis'],
    'engine' => ['ar' => 'المحرك', 'en' => 'Engine'],
    'suspension' => ['ar' => 'نظام التعليق', 'en' => 'Suspension'],
    'interior' => ['ar' => 'المقصورة الداخلية', 'en' => 'Interior'],
    'electrical' => ['ar' => 'النظام الكهربائي', 'en' => 'Electrical'],
];

$pageTitle = uiText('upload_diagram');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar"><?= uiText('upload_diagram') ?></span>
        <span class="en"><?= uiText('upload_diagram') ?></span>
    </h1>
</div>

<div class="card" style="max-width:700px;margin:0 auto">
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="required"><?= uiText('customer_name_ar') ?></label>
            <input type="text" name="name_ar" value="<?= clean($_POST['name_ar'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label class="required"><?= uiText('customer_name_en') ?></label>
            <input type="text" name="name_en" value="<?= clean($_POST['name_en'] ?? '') ?>" style="direction:ltr" required>
        </div>
        
        <div class="form-group">
            <label><?= uiText('key') ?> (<?= uiText('optional') ?>)</label>
            <input type="text" name="key" value="<?= clean($_POST['key'] ?? '') ?>" style="direction:ltr" placeholder="e.g., chassis_full_view">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:3px">
                <?= uiText('leave_blank_to_auto_generate') ?>
            </div>
        </div>
        
        <div class="form-group">
            <label class="required"><?= uiText('category') ?></label>
            <select name="category" required>
                <option value="">— <?= uiText('select') ?> —</option>
                <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($_POST['category'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= $label['ar'] ?> / <?= $label['en'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="required"><?= uiText('diagram_image') ?></label>
            <input type="file" name="diagram_image" accept=".svg,.png,.jpg,.jpeg,.gif,.webp" required>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:3px">
                <?= uiText('supported_formats_svg_png_jpg') ?> — Max 5MB
            </div>
        </div>
        
        <div class="form-group">
            <label><?= uiText('sort_order') ?></label>
            <input type="number" name="sort_order" value="<?= clean($_POST['sort_order'] ?? 0) ?>" min="0">
        </div>
        
        <div style="display:flex;gap:1rem;margin-top:1rem">
            <button type="submit" class="btn btn-primary">💾 <?= uiText('save') ?></button>
            <a href="<?= APP_URL ?>/modules/diagram-library.php" class="btn btn-secondary">← <?= uiText('back') ?></a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>