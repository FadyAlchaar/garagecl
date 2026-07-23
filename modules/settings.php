<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();
requireRole('admin');

$pdo     = db();
$msg     = '';
$msgType = '';

// ── SMTP SEND FUNCTION ────────────────────────────────────
function sendTestEmail(string $to, array $s): array {
    if (!file_exists(APP_ROOT.'/vendor/autoload.php'))
        return ['success'=>false,'error'=>'PHPMailer not installed. Run: composer require phpmailer/phpmailer'];
    require_once APP_ROOT.'/vendor/autoload.php';
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer'))
        return ['success'=>false,'error'=>'PHPMailer class not found'];
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $s['smtp_host']   ?? 'localhost';
        $mail->SMTPAuth   = !empty($s['smtp_user']);
        $mail->Username   = $s['smtp_user']   ?? '';
        $mail->Password   = $s['smtp_pass']   ?? '';
        $mail->SMTPSecure = $s['smtp_secure'] ?? 'tls';
        $mail->Port       = (int)($s['smtp_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($s['smtp_from'] ?? 'noreply@garage.local', $s['smtp_name'] ?? 'Garage');
        $mail->addAddress($to);
        $mail->Subject = 'Test — نظام فحص السيارات';
        $mail->Body    = "إذا وصلك هذا البريد فإن إعدادات SMTP تعمل بشكل صحيح.\n\nSMTP is configured correctly.";
        $mail->send();
        return ['success'=>true];
    } catch (Exception $e) {
        return ['success'=>false,'error'=>$e->getMessage()];
    }
}

// ── SMS SEND FUNCTION ─────────────────────────────────────
function sendSmsApi(string $phone, string $message, array $s): array {
    if (empty($s['sms_api_key']) || empty($s['sms_provider']))
        return ['success'=>false,'error'=>'SMS not configured'];
    $provider = strtolower($s['sms_provider']);
    $headers  = ['Content-Type: application/json'];

    if ($provider === 'twilio') {
        $parts = explode(':', $s['sms_api_key']);
        $sid   = $parts[0] ?? '';
        $token = $parts[1] ?? '';
        $url   = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['To'=>$phone,'From'=>$s['sms_sender'],'Body'=>$message]));
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch); curl_close($ch);
        $data = json_decode($resp, true);
        return isset($data['sid']) ? ['success'=>true] : ['success'=>false,'error'=>$data['message']??$resp];

    } elseif ($provider === 'vonage' || $provider === 'nexmo') {
        $parts = explode(':', $s['sms_api_key']);
        $payload = ['api_key'=>$parts[0]??'','api_secret'=>$parts[1]??'','to'=>$phone,'from'=>$s['sms_sender'],'text'=>$message];
        $ch = curl_init('https://rest.nexmo.com/sms/json');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch); curl_close($ch);
        $data = json_decode($resp, true);
        return ($data['messages'][0]['status']??'1')==='0' ? ['success'=>true] : ['success'=>false,'error'=>$data['messages'][0]['error-text']??$resp];

    } else {
        // Generic / Unifonic / Msegat / Custom
        $url = $s['sms_api_url'] ?? '';
        if (!$url) return ['success'=>false,'error'=>'API URL not set'];
        $payload = ['api_key'=>$s['sms_api_key'],'to'=>$phone,'from'=>$s['sms_sender'],'message'=>$message,'sender'=>$s['sms_sender']];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) return ['success'=>false,'error'=>$err];
        return ($code>=200&&$code<300) ? ['success'=>true] : ['success'=>false,'error'=>"HTTP $code — $resp"];
    }
}

// ── HANDLE DIAGRAM DELETE ───────────────────────────────
if (isset($_GET['delete_diagram']) && is_numeric($_GET['delete_diagram'])) {
    $id = (int)$_GET['delete_diagram'];
    // Get image path
    $stmt = $pdo->prepare("SELECT image_path, thumbnail_path FROM diagrams WHERE id = ?");
    $stmt->execute([$id]);
    $d = $stmt->fetch();
    if ($d) {
        if (!empty($d['image_path']) && file_exists(APP_ROOT . '/' . $d['image_path'])) {
            unlink(APP_ROOT . '/' . $d['image_path']);
        }
        if (!empty($d['thumbnail_path']) && file_exists(APP_ROOT . '/' . $d['thumbnail_path'])) {
            unlink(APP_ROOT . '/' . $d['thumbnail_path']);
        }
    }
    $pdo->prepare("DELETE FROM diagrams WHERE id = ?")->execute([$id]);
    $msg = '✅ تم حذف الرسم البياني'; $msgType = 'success';
    // Redirect to remove query param
    header('Location: ' . APP_URL . '/modules/settings.php?tab=diagrams');
    exit;
}

// ── HANDLE DIAGRAM UPLOAD ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_diagram'])) {
    $name_ar = trim($_POST['name_ar'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $key = trim($_POST['key'] ?? '');

    $errors = [];
    if (empty($name_ar)) $errors[] = 'Arabic name required';
    if (empty($category)) $errors[] = 'Category required';

    // ---- Generate a valid unique key ----
    if (empty($key)) {
        // Use name_en as base, fallback to name_ar
        $base = !empty($name_en) ? $name_en : $name_ar;
        $key = strtolower(str_replace(' ', '_', $base));
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        // If still empty, use a random string
        if (empty($key)) {
            $key = 'diagram_' . time() . '_' . rand(100, 999);
        }
    }

    // ---- Ensure key is unique ----
    $originalKey = $key;
    $suffix = 0;
    while (true) {
        $check = $pdo->prepare("SELECT id FROM diagrams WHERE `key` = ?");
        $check->execute([$key]);
        if (!$check->fetch()) {
            break; // key is unique
        }
        $suffix++;
        $key = $originalKey . '_' . $suffix;
    }

    // ---- Handle file upload ----
    if (!isset($_FILES['diagram_image']) || $_FILES['diagram_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select an image';
    } else {
        $file = $_FILES['diagram_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid file type. Allowed: SVG, PNG, JPG, GIF, WEBP';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File too large. Max 5MB.';
        } else {
            $uploadDir = APP_ROOT . '/assets/diagrams/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            // Use the final unique key for filename
            $filename = $key . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $image_path = 'assets/diagrams/' . $filename;
                $thumbnail_path = 'assets/diagrams/' . $filename; // fallback

                // Generate thumbnail for non-SVG
                if ($ext !== 'svg') {
                    $thumbPath = $uploadDir . 'thumb_' . $filename;
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
                        $thumbnail_path = 'assets/diagrams/thumb_' . $filename;
                    }
                } else {
                    $thumbnail_path = $image_path;
                }

                // Insert into database
                $stmt = $pdo->prepare("
                    INSERT INTO diagrams (`key`, name_ar, name_en, category, image_path, thumbnail_path)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$key, $name_ar, $name_en, $category, $image_path, $thumbnail_path]);
                if ($result) {
                    $msg = '✅ تم رفع الرسم البياني بنجاح';
                    $msgType = 'success';
                } else {
                    $errors[] = 'Database error';
                    // Clean up uploaded file
                    if (file_exists($targetPath)) unlink($targetPath);
                    if (isset($thumbPath) && file_exists($thumbPath)) unlink($thumbPath);
                }
            } else {
                $errors[] = 'Failed to move uploaded file';
            }
        }
    }

    if (!empty($errors)) {
        $msg = '❌ ' . implode('<br>', $errors);
        $msgType = 'error';
    }
}

// ── HANDLE POST (workshop, smtp, sms) ──────────────────
if (isPost()) {
    $section = $_POST['section'] ?? '';

    if ($section === 'workshop') {
        $logoPath = null;
        if (!empty($_FILES['logo']['name'])) {
            $ftype = mime_content_type($_FILES['logo']['tmp_name']);
            if (!in_array($ftype,['image/png','image/jpeg','image/jpg','image/gif','image/svg+xml'])) {
                $msg='نوع الملف غير مسموح'; $msgType='error';
            } elseif ($_FILES['logo']['size'] > 2*1024*1024) {
                $msg='الملف أكبر من 2MB'; $msgType='error';
            } else {
                $ext=pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION);
                $fn='logo_'.time().'.'.$ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'],APP_ROOT.'/assets/img/'.$fn))
                    $logoPath='assets/img/'.$fn;
            }
        }
        if ($msgType!=='error') {
            $cur=$pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
            if (!$logoPath && !empty($cur['logo_path'])) $logoPath=$cur['logo_path'];
            $pdo->prepare("UPDATE settings SET shop_name_ar=:ar,shop_name_en=:en,shop_address=:addr,shop_phone=:ph,shop_email=:em,logo_path=:lg WHERE id=:id")
                ->execute([':ar'=>$_POST['shop_name_ar']??'',':en'=>$_POST['shop_name_en']??'',':addr'=>$_POST['shop_address']??'',':ph'=>$_POST['shop_phone']??'',':em'=>$_POST['shop_email']??'',':lg'=>$logoPath,':id'=>$cur['id']]);
            auditLog($_SESSION['user_id']??null,'settings_workshop','settings');
            $msg='✅ تم حفظ معلومات الورشة'; $msgType='success';
        }
    }

    if ($section === 'smtp') {
        $pdo->prepare("UPDATE settings SET smtp_host=:h,smtp_port=:p,smtp_secure=:sec,smtp_user=:u,smtp_pass=:pw,smtp_from=:fr,smtp_name=:nm WHERE id=1")
            ->execute([':h'=>$_POST['smtp_host']??'localhost',':p'=>(int)($_POST['smtp_port']??587),':sec'=>$_POST['smtp_secure']??'tls',':u'=>$_POST['smtp_user']??'',':pw'=>(!empty($_POST['smtp_pass']) ? $_POST['smtp_pass'] : ($settings['smtp_pass'] ?? '')),':fr'=>$_POST['smtp_from']??'',':nm'=>$_POST['smtp_name']??'']);
        auditLog($_SESSION['user_id']??null,'settings_smtp','settings');
        $msg='✅ تم حفظ إعدادات البريد / SMTP settings saved'; $msgType='success';
    }

    if ($section === 'smtp_test') {
        $to=$_POST['test_email']??'';
        if (!filter_var($to,FILTER_VALIDATE_EMAIL)) { $msg='بريد غير صحيح'; $msgType='error'; }
        else {
            $s=$pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
            $r=sendTestEmail($to,$s);
            $msg=$r['success']?'✅ تم إرسال بريد الاختبار بنجاح!':'❌ '.$r['error'];
            $msgType=$r['success']?'success':'error';
        }
    }

    if ($section === 'sms') {
        $pdo->prepare("UPDATE settings SET sms_provider=:pv,sms_api_key=:k,sms_api_url=:u,sms_sender=:s WHERE id=1")
            ->execute([':pv'=>$_POST['sms_provider']??'',':k'=>$_POST['sms_api_key']??'',':u'=>$_POST['sms_api_url']??'',':s'=>$_POST['sms_sender']??'']);
        auditLog($_SESSION['user_id']??null,'settings_sms','settings');
        $msg='✅ تم حفظ إعدادات SMS'; $msgType='success';
    }

    if ($section === 'sms_test') {
        $phone=preg_replace('/[^0-9+]/','',trim($_POST['test_phone']??''));
        if (empty($phone)) { $msg='أدخل رقم هاتف'; $msgType='error'; }
        else {
            $s=$pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
            $r=sendSmsApi($phone,'رسالة اختبار من نظام فحص السيارات / Test from Garage System',$s);
            $msg=$r['success']?'✅ تم إرسال SMS الاختبار!':'❌ '.$r['error'];
            $msgType=$r['success']?'success':'error';
        }
    }
}

$settings  = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch() ?: [];
$activeTab = $_GET['tab'] ?? 'workshop';
$pageTitle = 'الإعدادات | Settings';

// Get diagrams for the diagrams tab
$diagrams = [];
try {
    $diagrams = $pdo->query("SELECT * FROM diagrams ORDER BY category, sort_order")->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><span class="ar">إعدادات النظام</span><span class="en">System Settings</span></h1>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= clean($msg) ?></div>
<?php endif; ?>

<!-- TABS -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:10px;border:1px solid var(--border);margin-bottom:1.5rem;flex-wrap:wrap">
    <?php foreach ([
        ['workshop','🏪 معلومات الورشة','Workshop Info'],
        ['smtp',    '📧 إعدادات البريد','Email / SMTP'],
        ['sms',     '📱 إعدادات SMS',   'SMS API'],
        ['diagrams','🖼️ الرسوم البيانية','Diagrams'],
    ] as [$id,$ar,$en]): ?>
    <a href="?tab=<?= $id ?>"
       style="padding:7px 18px;border-radius:7px;font-size:.9rem;font-weight:500;text-decoration:none;
              background:<?= $activeTab===$id?'#fff':'transparent' ?>;
              color:<?= $activeTab===$id?'var(--accent)':'var(--text-muted)' ?>;
              box-shadow:<?= $activeTab===$id?'0 1px 3px rgba(0,0,0,.08)':'none' ?>">
        <?= $ar ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<?php if ($activeTab === 'workshop'): ?>
<!-- WORKSHOP TAB -->
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="section" value="workshop">
<div class="grid-2">
    <div class="card">
        <div class="card-header"><span class="ar">معلومات الورشة</span><span class="en">Workshop Info</span></div>
        <div class="form-group">
            <label class="required"><span class="ar">اسم الورشة بالعربية</span></label>
            <input type="text" name="shop_name_ar" value="<?= clean($settings['shop_name_ar']??'') ?>" required>
        </div>
        <div class="form-group">
            <label class="required"><span class="ar">اسم الورشة بالإنجليزية</span></label>
            <input type="text" name="shop_name_en" value="<?= clean($settings['shop_name_en']??'') ?>" style="direction:ltr" required>
        </div>
        <div class="form-group">
            <label><span class="ar">العنوان</span></label>
            <textarea name="shop_address" rows="2"><?= clean($settings['shop_address']??'') ?></textarea>
        </div>
        <div class="form-group">
            <label><span class="ar">رقم الهاتف</span></label>
            <input type="tel" name="shop_phone" value="<?= clean($settings['shop_phone']??'') ?>" style="direction:ltr">
        </div>
        <div class="form-group">
            <label><span class="ar">البريد الإلكتروني</span></label>
            <input type="email" name="shop_email" value="<?= clean($settings['shop_email']??'') ?>" style="direction:ltr">
        </div>
        <button type="submit" class="btn btn-primary btn-lg">💾 <span class="ar">حفظ المعلومات</span></button>
    </div>
    <div class="card">
        <div class="card-header"><span class="ar">شعار الورشة</span><span class="en">Logo</span></div>
        <?php if (!empty($settings['logo_path']) && file_exists(APP_ROOT.'/'.$settings['logo_path'])): ?>
        <div style="text-align:center;margin-bottom:1.2rem;padding:1rem;background:#f9fafb;border-radius:8px">
            <img src="<?= APP_URL.'/'.$settings['logo_path'] ?>" alt="Logo" style="max-height:110px;max-width:100%;border-radius:6px">
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:2.5rem;background:#f9fafb;border-radius:8px;border:2px dashed var(--border);margin-bottom:1.2rem">
            <div style="font-size:3rem">🏪</div>
            <div style="color:var(--text-muted)">لا يوجد شعار / No logo</div>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label><span class="ar">رفع شعار جديد</span><span class="en">Upload new logo</span></label>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/svg+xml"
                   style="padding:.4rem;border:1px dashed var(--border);border-radius:8px;width:100%">
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px">PNG, JPG, SVG — Max 2MB. Transparent PNG recommended.</div>
        </div>
    </div>
</div>
</form>

<!-- ══════════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'smtp'): ?>
<!-- SMTP TAB -->
<div class="grid-2">
<div>
    <div class="card">
        <div class="card-header"><span class="ar">⚙️ إعدادات خادم البريد SMTP</span><span class="en">SMTP Server Settings</span></div>
        <form method="POST">
        <input type="hidden" name="section" value="smtp">
        <div class="form-group">
            <label><span class="ar">عنوان خادم SMTP</span><span class="en">SMTP Host</span></label>
            <input type="text" name="smtp_host" value="<?= clean($settings['smtp_host']??'smtp.gmail.com') ?>" style="direction:ltr" placeholder="smtp.gmail.com">
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label><span class="ar">المنفذ</span><span class="en">Port</span></label>
                <select name="smtp_port">
                    <option value="587" <?= ($settings['smtp_port']??587)==587?'selected':'' ?>>587 — TLS</option>
                    <option value="465" <?= ($settings['smtp_port']??0)==465?'selected':'' ?>>465 — SSL</option>
                    <option value="25"  <?= ($settings['smtp_port']??0)==25 ?'selected':'' ?>>25 — Plain</option>
                </select>
            </div>
            <div class="form-group">
                <label><span class="ar">التشفير</span><span class="en">Encryption</span></label>
                <select name="smtp_secure">
                    <option value="tls" <?= ($settings['smtp_secure']??'tls')==='tls'?'selected':'' ?>>TLS (recommended)</option>
                    <option value="ssl" <?= ($settings['smtp_secure']??'')==='ssl'?'selected':'' ?>>SSL</option>
                    <option value=""    <?= ($settings['smtp_secure']??'')=== '' ?'selected':'' ?>>None</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label><span class="ar">اسم المستخدم / البريد</span><span class="en">Username / Email</span></label>
            <input type="text" name="smtp_user" value="<?= clean($settings['smtp_user']??'') ?>" style="direction:ltr" placeholder="your@email.com">
        </div>
        <div class="form-group">
            <label><span class="ar">كلمة المرور</span><span class="en">Password / App Password</span></label>
            <input type="password" name="smtp_pass" value="<?= clean($settings['smtp_pass']??'') ?>" style="direction:ltr"
                   placeholder="<?= !empty($settings['smtp_pass'])?'●●●●●●●●●● (saved)':'' ?>">
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
                Gmail: use App Password → <strong>myaccount.google.com/apppasswords</strong>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label><span class="ar">بريد المُرسِل</span><span class="en">From Email</span></label>
                <input type="email" name="smtp_from" value="<?= clean($settings['smtp_from']??$settings['shop_email']??'') ?>" style="direction:ltr">
            </div>
            <div class="form-group">
                <label><span class="ar">اسم المُرسِل</span><span class="en">From Name</span></label>
                <input type="text" name="smtp_name" value="<?= clean($settings['smtp_name']??$settings['shop_name_ar']??'') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">💾 <span class="ar">حفظ إعدادات البريد</span></button>
        </form>
    </div>

    <!-- Provider reference -->
    <div class="card mt-2" style="background:#f0f9ff;border-color:#bae6fd">
        <div style="font-weight:700;color:#0369a1;margin-bottom:.75rem">📖 <span class="ar">إعدادات المزودين الشائعين</span></div>
        <table style="width:100%;font-size:.82rem;border-collapse:collapse">
            <tr style="background:#e0f2fe"><th style="padding:5px 8px;text-align:right">المزود</th><th style="padding:5px 8px;text-align:left">Host</th><th>Port</th><th>Security</th></tr>
            <?php foreach ([
                ['Gmail','smtp.gmail.com','587','TLS'],
                ['Outlook / 365','smtp.office365.com','587','TLS'],
                ['Yahoo','smtp.mail.yahoo.com','587','TLS'],
                ['cPanel host','mail.yourdomain.com','465','SSL'],
                ['Local (test)','localhost','25','None'],
            ] as $r): ?>
            <tr style="border-bottom:1px solid #bae6fd">
                <td style="padding:5px 8px;font-weight:600"><?= $r[0] ?></td>
                <td style="padding:5px 8px;font-family:monospace;direction:ltr"><?= $r[1] ?></td>
                <td style="padding:5px 8px;font-family:monospace"><?= $r[2] ?></td>
                <td style="padding:5px 8px"><?= $r[3] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- SMTP Test -->
<div class="card">
    <div class="card-header"><span class="ar">🧪 اختبار إعدادات البريد</span></div>
    <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem">
        <span class="ar">بعد حفظ الإعدادات، أرسل بريد اختبار للتأكد</span>
    </p>
    <form method="POST">
        <input type="hidden" name="section" value="smtp_test">
        <div class="form-group">
            <label><span class="ar">أرسل بريد اختبار إلى</span></label>
            <input type="email" name="test_email" style="direction:ltr" placeholder="test@email.com">
        </div>
        <button type="submit" class="btn btn-secondary">📧 <span class="ar">إرسال بريد اختبار</span></button>
    </form>
    <hr class="divider">
    <div style="font-size:.85rem">
        <strong><span class="ar">تثبيت PHPMailer (مطلوب):</span></strong>
        <div style="background:#1a1a2e;color:#a8f0a8;padding:.75rem;border-radius:8px;font-family:monospace;font-size:.82rem;margin-top:.5rem;direction:ltr">
            cd C:\xampp\htdocs\garagecl<br>
            composer require phpmailer/phpmailer
        </div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'sms'): ?>
<!-- SMS TAB -->
<div class="grid-2">
<div>
    <div class="card">
        <div class="card-header"><span class="ar">⚙️ إعدادات مزود SMS</span><span class="en">SMS Provider Settings</span></div>
        <form method="POST">
        <input type="hidden" name="section" value="sms">
        <div class="form-group">
            <label><span class="ar">مزود الخدمة</span><span class="en">Provider</span></label>
            <select name="sms_provider" id="smsProv" onchange="smsHints(this.value)">
                <option value=""        <?= empty($settings['sms_provider'])              ?'selected':'' ?>>— اختر المزود —</option>
                <option value="twilio"  <?= ($settings['sms_provider']??'')==='twilio'   ?'selected':'' ?>>Twilio</option>
                <option value="vonage"  <?= ($settings['sms_provider']??'')==='vonage'   ?'selected':'' ?>>Vonage (Nexmo)</option>
                <option value="unifonic"<?= ($settings['sms_provider']??'')==='unifonic' ?'selected':'' ?>>Unifonic</option>
                <option value="msegat"  <?= ($settings['sms_provider']??'')==='msegat'   ?'selected':'' ?>>Msegat</option>
                <option value="custom"  <?= ($settings['sms_provider']??'')==='custom'   ?'selected':'' ?>>مخصص / Custom</option>
            </select>
        </div>
        <div class="form-group">
            <label><span class="ar">مفتاح API</span><span class="en">API Key</span></label>
            <input type="text" name="sms_api_key" id="smsKey" value="<?= clean($settings['sms_api_key']??'') ?>" style="direction:ltr" autocomplete="off">
            <div id="smsKeyHint" style="font-size:.75rem;color:var(--text-muted);margin-top:3px"></div>
        </div>
        <div class="form-group">
            <label><span class="ar">رابط API</span><span class="en">API URL</span></label>
            <input type="url" name="sms_api_url" id="smsUrl" value="<?= clean($settings['sms_api_url']??'') ?>" style="direction:ltr" placeholder="https://api.provider.com/send">
            <div id="smsUrlHint" style="font-size:.75rem;color:var(--text-muted);margin-top:3px"></div>
        </div>
        <div class="form-group">
            <label><span class="ar">Sender ID / اسم المُرسِل</span></label>
            <input type="text" name="sms_sender" value="<?= clean($settings['sms_sender']??'') ?>" style="direction:ltr" placeholder="GARAGE">
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
                <span class="ar">يظهر للمستلم بدل الرقم — يجب تسجيله لدى المزود أولاً</span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">💾 <span class="ar">حفظ إعدادات SMS</span></button>
        </form>
    </div>

    <!-- Provider Reference -->
    <div class="card mt-2" style="background:#f0fdf4;border-color:#bbf7d0">
        <div style="font-weight:700;color:#166534;margin-bottom:.75rem">📖 <span class="ar">دليل المزودين</span></div>
        <?php foreach ([
            ['Twilio','twilio.com','AccountSID:AuthToken','Auto (no URL needed)'],
            ['Vonage','vonage.com','APIKey:APISecret','Auto (no URL needed)'],
            ['Unifonic','unifonic.com','App SID','https://api.unifonic.com/rest/Messages/Send'],
            ['Msegat','msegat.com','API Key','https://www.msegat.com/gw/sendsms.php'],
        ] as [$name,$site,$key,$url]): ?>
        <div style="border-bottom:1px solid #bbf7d0;padding:.6rem 0">
            <strong><?= $name ?></strong> — <a href="https://<?= $site ?>" target="_blank" style="color:#166534;font-size:.8rem"><?= $site ?></a><br>
            <span style="font-size:.78rem;color:var(--text-muted)">
                Key format: <code><?= $key ?></code><br>
                URL: <code><?= $url ?></code>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SMS Test -->
<div class="card">
    <div class="card-header"><span class="ar">🧪 اختبار إعدادات SMS</span></div>

    <!-- Current status -->
    <div style="background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:1rem;margin-bottom:1.2rem">
        <div style="font-size:.85rem;font-weight:600;margin-bottom:.5rem"><span class="ar">الإعدادات الحالية:</span></div>
        <table style="width:100%;font-size:.82rem">
            <?php foreach ([
                ['المزود',    $settings['sms_provider']??'—'],
                ['Sender ID', $settings['sms_sender']??'—'],
                ['API Key',   !empty($settings['sms_api_key'])?'✅ مُعيَّن':'❌ غير مُعيَّن'],
                ['API URL',   !empty($settings['sms_api_url'])?'✅ مُعيَّن':'❌ غير مُعيَّن'],
            ] as [$label,$val]): ?>
            <tr>
                <td style="color:var(--text-muted);width:40%;padding:3px 0"><?= $label ?></td>
                <td style="font-weight:500;padding:3px 0"><?= clean($val) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <form method="POST">
        <input type="hidden" name="section" value="sms_test">
        <div class="form-group">
            <label><span class="ar">رقم هاتف الاختبار (صيغة دولية)</span></label>
            <input type="tel" name="test_phone" style="direction:ltr" placeholder="+963912345678">
        </div>
        <button type="submit" class="btn btn-secondary">📱 <span class="ar">إرسال SMS اختبار</span></button>
    </form>

    <hr class="divider">

    <div class="notes-section">
        <h4><span class="ar">ملاحظة</span></h4>
        <p style="font-size:.82rem">
            <span class="ar">يتطلب إرسال SMS اتصالاً بالإنترنت للوصول إلى API المزود. واتساب لا يحتاج أي إعداد.</span>
        </p>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'diagrams'): ?>
<!-- DIAGRAMS TAB -->
<div class="card">
    <div class="card-header">
        <span class="ar">🖼️ رفع رسم بياني جديد</span>
        <span class="en">Upload New Diagram</span>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="upload_diagram" value="1">

        <div class="grid-2">
            <div class="form-group">
                <label class="required"><span class="ar">الاسم (عربي)</span><span class="en">Name (Arabic)</span></label>
                <input type="text" name="name_ar" required>
            </div>
            <div class="form-group">
                <label class="required"><span class="ar">الاسم (إنجليزي)</span><span class="en">Name (English)</span></label>
                <input type="text" name="name_en" style="direction:ltr">
            </div>
        </div>

        <div class="form-group">
            <label><span class="ar">المفتاح (اختياري)</span><span class="en">Key (optional)</span></label>
            <input type="text" name="key" style="direction:ltr" placeholder="e.g., chassis_full_view">
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
                <span class="ar">سيتم إنشاؤه تلقائياً إذا تركته فارغاً</span>
                <span class="en">Auto-generated if left empty</span>
            </div>
        </div>

        <div class="form-group">
            <label class="required"><span class="ar">التصنيف</span><span class="en">Category</span></label>
            <select name="category" required>
                <option value="">— <?= uiText('select') ?> —</option>
                <option value="chassis">هيكل / Chassis</option>
                <option value="engine">محرك / Engine</option>
                <option value="suspension">تعليق / Suspension</option>
                <option value="interior">داخلي / Interior</option>
                <option value="electrical">كهرباء / Electrical</option>
            </select>
        </div>

        <div class="form-group">
            <label class="required"><span class="ar">الصورة</span><span class="en">Image</span></label>
            <input type="file" name="diagram_image" accept=".svg,.png,.jpg,.jpeg,.gif,.webp" required>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
                SVG, PNG, JPG, GIF, WEBP — Max 5MB
            </div>
        </div>

        <button type="submit" class="btn btn-primary">💾 <span class="ar">رفع</span><span class="en">Upload</span></button>
    </form>
</div>

<!-- List existing diagrams -->
<div class="card">
    <div class="card-header">
        <span class="ar">الرسوم الموجودة</span>
        <span class="en">Existing Diagrams</span>
        <span style="margin-left:auto;font-size:.8rem;color:var(--text-muted);font-weight:normal">
            <?= count($diagrams) ?> <?= uiText('diagrams') ?>
        </span>
    </div>

    <?php if (empty($diagrams)): ?>
    <div style="text-align:center;padding:2rem;color:var(--text-muted)">
        <div style="font-size:3rem">🖼️</div>
        <p><?= uiText('no_diagrams') ?></p>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1.5rem">
        <?php foreach ($diagrams as $d): ?>
        <div style="border:1px solid var(--border);border-radius:8px;padding:1rem;text-align:center;background:var(--bg-card)">
            <?php if (!empty($d['thumbnail_path']) && file_exists(APP_ROOT . '/' . $d['thumbnail_path'])): ?>
            <img src="<?= APP_URL . '/' . $d['thumbnail_path'] ?>" alt="<?= $d['name_ar'] ?>" style="max-width:100%;max-height:100px;border-radius:4px">
            <?php else: ?>
            <div style="height:80px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:2rem">🖼️</div>
            <?php endif; ?>
            <div style="font-size:.85rem;font-weight:600;margin-top:.25rem">
                <?= $d['name_ar'] ?>
            </div>
            <div style="font-size:.7rem;color:var(--text-muted)">
                <?= $d['name_en'] ?>
            </div>
            <div style="font-size:.65rem;color:var(--text-muted);margin-top:2px">
                <span class="badge badge-muted"><?= ucfirst($d['category'] ?? 'Uncategorized') ?></span>
            </div>
            <div style="margin-top:.5rem">
                <a href="?tab=diagrams&delete_diagram=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= uiText('confirm_delete_diagram') ?>')">
                    🗑️ <?= uiText('delete') ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
const hints = {
    twilio:   {key:'Format: AccountSID:AuthToken (colon-separated)', url:'Leave blank — auto configured'},
    vonage:   {key:'Format: APIKey:APISecret (colon-separated)',     url:'Leave blank — auto configured'},
    unifonic: {key:'Enter your Unifonic App SID',                    url:'https://api.unifonic.com/rest/Messages/Send'},
    msegat:   {key:'Enter your Msegat API Key',                      url:'https://www.msegat.com/gw/sendsms.php'},
    custom:   {key:'Enter your API Key or Bearer token',             url:'Enter your full API endpoint URL'},
};
function smsHints(val) {
    const h = hints[val] || {key:'',url:''};
    const kh=document.getElementById('smsKeyHint');
    const uh=document.getElementById('smsUrlHint');
    if(kh) kh.textContent=h.key;
    if(uh) uh.textContent=h.url;
}
const sp=document.getElementById('smsProv');
if(sp) smsHints(sp.value);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>