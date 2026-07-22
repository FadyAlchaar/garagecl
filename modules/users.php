<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('admin');

$pdo    = db();
$msg    = '';
$msgType= '';
$editUser = null;

// ---- HANDLE ACTIONS ----
if (isPost()) {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $uid      = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $nameAr   = trim($_POST['full_name_ar'] ?? '');
        $nameEn   = trim($_POST['full_name_en'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = $_POST['role'] ?? 'technician';
        $lang     = $_POST['lang'] ?? 'ar';
        $active   = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        if (!in_array($role, ['admin','manager','technician','viewer'])) $role = 'technician';
        if (!in_array($lang, ['ar','en'])) $lang = 'ar';

        if ($action === 'create') {
            if (empty($password)) {
                $msg = t('required_field') . ': ' . t('user_password');
                $msgType = 'error';
            } else {
                try {
                    $pdo->prepare("
                        INSERT INTO users (username, password_hash, full_name_ar, full_name_en, email, phone, role, lang, is_active)
                        VALUES (?,?,?,?,?,?,?,?,?)
                    ")->execute([$username, hashPassword($password), $nameAr, $nameEn, $email, $phone, $role, $lang, $active]);
                    auditLog($_SESSION['user_id'], 'user_create', 'user', (int)$pdo->lastInsertId(), "Created user: $username");
                    $msg = t('user_created'); $msgType = 'success';
                } catch (Exception $e) {
                    $msg = 'Error: ' . $e->getMessage(); $msgType = 'error';
                }
            }
        } else {
            try {
                $sets  = "username=:u, full_name_ar=:ar, full_name_en=:en, email=:e, phone=:ph, role=:r, lang=:lg, is_active=:a";
                $params = [':u'=>$username,':ar'=>$nameAr,':en'=>$nameEn,':e'=>$email,':ph'=>$phone,':r'=>$role,':lg'=>$lang,':a'=>$active,':id'=>$uid];
                if (!empty($password)) {
                    $sets .= ", password_hash=:pw";
                    $params[':pw'] = hashPassword($password);
                }
                $pdo->prepare("UPDATE users SET $sets WHERE id = :id")->execute($params);
                auditLog($_SESSION['user_id'], 'user_update', 'user', $uid, "Updated user: $username");
                $msg = t('user_updated'); $msgType = 'success';
            } catch (Exception $e) {
                $msg = 'Error: ' . $e->getMessage(); $msgType = 'error';
            }
        }
    }

    if ($action === 'delete') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === (int)$_SESSION['user_id']) {
            $msg = 'Cannot delete yourself'; $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->execute([$uid]);
            auditLog($_SESSION['user_id'], 'user_delete', 'user', $uid, "Deactivated user #$uid");
            $msg = t('user_deleted'); $msgType = 'success';
        }
    }

    if ($action === 'gen_token') {
        $uid   = (int)($_POST['user_id'] ?? 0);
        $token = generateApiToken($uid);
        $msg   = 'API Token: ' . $token; $msgType = 'success';
    }
}

// Load for edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch();
    unset($editUser['password_hash']);
}

// Load all users
$users = $pdo->query("SELECT id, username, full_name_ar, full_name_en, email, phone, role, lang, is_active, last_login, created_at FROM users ORDER BY role, full_name_ar")->fetchAll();

// Recent audit log
$auditRows = $pdo->query("SELECT a.*, u.full_name_ar FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 30")->fetchAll();

$pageTitle = t('users_title');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><span class="ar"><?= t('users_title') ?></span><span class="en">User Management</span></h1>
    <a href="?new=1" class="btn btn-primary">+ <?= t('btn_new_user') ?></a>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= clean($msg) ?></div>
<?php endif; ?>

<div class="grid-2">
<!-- ===== USER FORM ===== -->
<div class="card">
    <div class="card-header">
        <span class="ar"><?= $editUser ? 'تعديل مستخدم' : 'مستخدم جديد' ?></span>
        <span class="en"><?= $editUser ? 'Edit User' : 'New User' ?></span>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
        <?php if ($editUser): ?>
        <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
        <?php endif; ?>

        <div class="grid-2">
            <div class="form-group">
                <label class="required"><span class="ar"><?= t('user_username') ?></span></label>
                <input type="text" name="username" value="<?= clean($editUser['username'] ?? '') ?>" required
                       style="direction:ltr" autocomplete="off">
            </div>
            <div class="form-group">
                <label><span class="ar"><?= t('user_password') ?></span>
                    <?php if ($editUser): ?><span class="en" style="font-weight:400">(leave blank = no change)</span><?php endif; ?>
                </label>
                <input type="password" name="password" style="direction:ltr" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="required"><span class="ar"><?= t('user_fullname_ar') ?></span></label>
                <input type="text" name="full_name_ar" value="<?= clean($editUser['full_name_ar'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label><span class="ar"><?= t('user_fullname_en') ?></span></label>
                <input type="text" name="full_name_en" value="<?= clean($editUser['full_name_en'] ?? '') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><span class="ar"><?= t('user_email') ?></span></label>
                <input type="email" name="email" value="<?= clean($editUser['email'] ?? '') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><span class="ar"><?= t('user_phone') ?></span></label>
                <input type="tel" name="phone" value="<?= clean($editUser['phone'] ?? '') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><span class="ar"><?= t('user_role') ?></span></label>
                <select name="role">
                    <?php foreach (['admin','manager','technician','viewer'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($editUser['role'] ?? 'technician') === $r ? 'selected' : '' ?>>
                        <?= t("role_$r") ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><span class="ar"><?= t('user_lang') ?></span></label>
                <select name="lang">
                    <option value="ar" <?= ($editUser['lang'] ?? 'ar') === 'ar' ? 'selected' : '' ?>>العربية / Arabic</option>
                    <option value="en" <?= ($editUser['lang'] ?? '') === 'en' ? 'selected' : '' ?>>English / الإنجليزية</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" <?= ($editUser['is_active'] ?? 1) ? 'checked' : '' ?>>
                <span class="ar"><?= t('user_active') ?></span>
            </label>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary">💾 <?= t('btn_save') ?></button>
            <?php if ($editUser): ?>
            <a href="<?= APP_URL ?>/modules/users.php" class="btn btn-outline"><?= t('btn_cancel') ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ===== USER LIST ===== -->
<div class="card">
    <div class="card-header">
        <span class="ar">المستخدمون</span><span class="en">Users</span>
    </div>
    <div style="overflow-x:auto">
    <table class="reports-table" style="font-size:.88rem">
        <thead>
            <tr>
                <th><?= t('user_fullname_ar') ?></th>
                <th><?= t('user_role') ?></th>
                <th><?= t('user_status') ?></th>
                <th><?= t('audit_time') ?></th>
                <th><?= t('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td>
                <strong><?= clean($u['full_name_ar']) ?></strong><br>
                <span style="font-size:.78rem;color:var(--text-muted)"><?= clean($u['username']) ?></span>
            </td>
            <td>
                <span class="badge <?= $u['role']==='admin'?'badge-danger':($u['role']==='manager'?'badge-warning':'badge-muted') ?>">
                    <?= t('role_' . $u['role']) ?>
                </span>
            </td>
            <td>
                <span class="badge <?= $u['is_active'] ? 'badge-good' : 'badge-danger' ?>">
                    <?= $u['is_active'] ? t('user_active') : t('user_inactive') ?>
                </span>
            </td>
            <td style="font-size:.78rem"><?= $u['last_login'] ? date('Y-m-d', strtotime($u['last_login'])) : '—' ?></td>
            <td>
                <div style="display:flex;gap:.3rem">
                    <a href="?edit=<?= $u['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('confirm_delete') ?>')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="gen_token">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Generate API Token">🔑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</div>

<!-- ===== AUDIT LOG ===== -->
<div class="card">
    <div class="card-header">
        <span class="ar"><?= t('audit_title') ?></span><span class="en">Audit Log</span>
    </div>
    <div style="overflow-x:auto">
    <table class="reports-table" style="font-size:.82rem">
        <thead>
            <tr>
                <th><?= t('audit_user') ?></th>
                <th><?= t('audit_action') ?></th>
                <th><?= t('audit_target') ?></th>
                <th>Details</th>
                <th><?= t('audit_ip') ?></th>
                <th><?= t('audit_time') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($auditRows as $a): ?>
        <tr>
            <td><?= clean($a['username'] ?? '—') ?></td>
            <td><code style="font-size:.78rem;background:#f3f4f6;padding:1px 5px;border-radius:3px"><?= clean($a['action']) ?></code></td>
            <td><?= clean($a['target_type'] ?? '') ?> <?= $a['target_id'] ? '#'.$a['target_id'] : '' ?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= clean($a['details'] ?? '') ?></td>
            <td style="font-size:.75rem;color:var(--text-muted)"><?= clean($a['ip_address'] ?? '') ?></td>
            <td style="font-size:.75rem;white-space:nowrap"><?= $a['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
