<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

// ── SMS API FUNCTION ──────────────────────────────────────
function sendSmsViaApi(string $phone, string $message, array $cfg): array {
    if (empty($cfg['sms_api_key']) || empty($cfg['sms_provider']))
        return ['success'=>false, 'error'=>'SMS API not configured in Settings'];

    $provider = strtolower($cfg['sms_provider']);
    $headers  = ['Content-Type: application/json'];

    if ($provider === 'twilio') {
        $parts = explode(':', $cfg['sms_api_key']);
        $sid   = $parts[0] ?? ''; $token = $parts[1] ?? '';
        $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['To'=>$phone,'From'=>$cfg['sms_sender'],'Body'=>$message]),
            CURLOPT_USERPWD => "$sid:$token",
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15
        ]);
        $resp = curl_exec($ch); curl_close($ch);
        $data = json_decode($resp, true);
        return isset($data['sid']) ? ['success'=>true] : ['success'=>false,'error'=>$data['message']??$resp];
    } elseif (in_array($provider, ['vonage','nexmo'])) {
        $parts = explode(':', $cfg['sms_api_key']);
        $ch = curl_init('https://rest.nexmo.com/sms/json');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['api_key'=>$parts[0]??'','api_secret'=>$parts[1]??'','to'=>$phone,'from'=>$cfg['sms_sender'],'text'=>$message]),
            CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15
        ]);
        $resp = curl_exec($ch); curl_close($ch);
        $data = json_decode($resp, true);
        return ($data['messages'][0]['status']??'1')==='0' ? ['success'=>true] : ['success'=>false,'error'=>$data['messages'][0]['error-text']??$resp];
    } else {
        $url = $cfg['sms_api_url'] ?? '';
        if (!$url) return ['success'=>false,'error'=>'API URL not configured'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['api_key'=>$cfg['sms_api_key'],'to'=>$phone,'from'=>$cfg['sms_sender'],'message'=>$message]),
            CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15
        ]);
        $resp = curl_exec($ch); $err = curl_error($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) return ['success'=>false,'error'=>$err];
        return ($code>=200&&$code<300) ? ['success'=>true] : ['success'=>false,'error'=>"HTTP $code: $resp"];
    }
}


$pdo      = db();
$reportId = (int)($_GET['id'] ?? 0);
$msg      = '';
$msgType  = '';

// Load report
if (!$reportId) redirect('modules/dashboard.php');
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name_ar as tech_name
    FROM reports r
    LEFT JOIN users u ON r.technician_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$reportId]);
$report = $stmt->fetch();
if (!$report) redirect('modules/dashboard.php');

$settings = getSettings();

// Load past notifications for this report
$past = $pdo->prepare("SELECT * FROM notifications_log WHERE report_id = ? ORDER BY created_at DESC");
$past->execute([$reportId]);
$pastNotifs = $past->fetchAll();

// ── Default message templates ──────────────────────────────
$shopName = currentLang() === 'ar'
    ? ($settings['shop_name_ar'] ?? 'الورشة')
    : ($settings['shop_name_en'] ?? 'Workshop');

$defaultEmail = trim($report['customer_name'] ?? '') . "\n\n" .
    "تقرير فحص سيارتكم جاهز / Your vehicle inspection report is ready.\n\n" .
    "رقم التقرير / Report No: " . $report['report_number'] . "\n" .
    "المركبة / Vehicle: " . $report['brand'] . " " . $report['model'] . " " . $report['year'] . "\n" .
    "رقم اللوحة / Plate: " . $report['plate_number'] . "\n" .
    "تاريخ الفحص / Inspection Date: " . $report['date_inspection'] . "\n\n" .
    "شكراً لثقتكم / Thank you for your trust.\n" .
    $shopName . "\n" .
    ($settings['shop_phone'] ?? '');

$defaultSMS =
    "تقرير فحص سيارتكم جاهز ✅\n" .
    "رقم التقرير: " . $report['report_number'] . "\n" .
    $report['brand'] . " " . $report['model'] . "\n" .
    $shopName;

$whatsappMsg = urlencode(
    "تقرير فحص سيارتكم جاهز ✅\n" .
    "رقم التقرير: " . $report['report_number'] . "\n" .
    "المركبة: " . $report['brand'] . " " . $report['model'] . " (" . $report['year'] . ")\n" .
    "رقم اللوحة: " . $report['plate_number'] . "\n" .
    "تاريخ الفحص: " . $report['date_inspection'] . "\n" .
    "شكراً لثقتكم — " . $shopName
);

// ── Handle form submissions ────────────────────────────────
if (isPost()) {
    $action = $_POST['action'] ?? '';

    // ── EMAIL ──
    if ($action === 'send_email') {
        $to      = trim($_POST['email_to']   ?? '');
        $subject = trim($_POST['email_subj'] ?? 'تقرير فحص السيارة - Vehicle Inspection Report');
        $body    = trim($_POST['email_body'] ?? '');

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $msg = 'البريد الإلكتروني غير صحيح / Invalid email address';
            $msgType = 'error';
        } elseif (empty($body)) {
            $msg = 'الرسالة فارغة / Message is empty';
            $msgType = 'error';
        } else {
            // Try PHPMailer if available, else use PHP mail()
            $sent  = false;
            $error = '';

            if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
                require_once APP_ROOT . '/vendor/autoload.php';
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host       = $settings['smtp_host']     ?? 'localhost';
                        $mail->SMTPAuth   = !empty($settings['smtp_user']);
                        $mail->Username   = $settings['smtp_user']     ?? '';
                        $mail->Password   = $settings['smtp_pass']     ?? '';
                        $mail->SMTPSecure = $settings['smtp_secure']   ?? 'tls';
                        $mail->Port       = (int)($settings['smtp_port'] ?? 587);
                        $mail->CharSet    = 'UTF-8';
                        $mail->setFrom(
                            $settings['smtp_from'] ?? ($settings['shop_email'] ?? 'noreply@garage.local'),
                            $shopName
                        );
                        $mail->addAddress($to);
                        $mail->Subject = $subject;
                        $mail->Body    = $body;
                        $mail->send();
                        $sent = true;
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }

            // Fallback: PHP built-in mail()
            if (!$sent && empty($error)) {
                $headers  = "From: $shopName <noreply@garage.local>\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $sent = mail($to, $subject, $body, $headers);
                if (!$sent) $error = 'PHP mail() failed — configure SMTP in Settings';
            }

            // Log it
            $status = $sent ? 'sent' : 'failed';
            $pdo->prepare("
                INSERT INTO notifications_log (report_id,type,recipient,subject,message,status,error_message,sent_at)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $reportId, 'email', $to, $subject, $body,
                $status, $error ?: null, $sent ? date('Y-m-d H:i:s') : null
            ]);

            auditLog($_SESSION['user_id'] ?? null, 'send_email', 'report', $reportId, "Email to $to — $status");

            if ($sent) {
                $msg = '✅ تم إرسال البريد الإلكتروني بنجاح / Email sent successfully';
                $msgType = 'success';
            } else {
                $msg = '❌ فشل الإرسال: ' . $error . ' / Send failed';
                $msgType = 'error';
            }
        }
    }

    // ── SMS (log only — requires external API) ──
    if ($action === 'log_sms') {
        $phone = trim($_POST['sms_phone'] ?? '');
        $body  = trim($_POST['sms_body']  ?? '');
        $pdo->prepare("
            INSERT INTO notifications_log (report_id,type,recipient,message,status,sent_at)
            VALUES (?,?,?,?,'sent',NOW())
        ")->execute([$reportId, 'sms', $phone, $body]);
        auditLog($_SESSION['user_id'] ?? null, 'log_sms', 'report', $reportId, "SMS logged to $phone");
        $msg = '✅ تم تسجيل الرسالة / SMS logged successfully';
        $msgType = 'success';
    }

    // Reload past notifs
    $past->execute([$reportId]);
    $pastNotifs = $past->fetchAll();
}

$pageTitle = 'إرسال إشعار | Send Notification';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>
        <span class="ar">إرسال إشعار للعميل</span>
        <span class="en">Send Customer Notification</span>
    </h1>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/modules/report-view.php?id=<?= $reportId ?>" class="btn btn-outline">
            ← <span class="ar">العودة للتقرير</span>
        </a>
        <a href="<?= APP_URL ?>/pdf/generate.php?id=<?= $reportId ?>" target="_blank" class="btn btn-primary">
            🖨️ <span class="ar">طباعة PDF</span>
        </a>
    </div>
</div>

<!-- REPORT SUMMARY BADGE -->
<div class="card" style="background:linear-gradient(135deg,var(--accent),#2d2d5e);color:#fff;margin-bottom:1.5rem">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem">
        <div>
            <div style="font-size:.75rem;opacity:.7">رقم التقرير / Report No</div>
            <div style="font-size:1.1rem;font-weight:700"><?= clean($report['report_number']) ?></div>
        </div>
        <div>
            <div style="font-size:.75rem;opacity:.7">المركبة / Vehicle</div>
            <div style="font-weight:600"><?= clean($report['brand'].' '.$report['model'].' ('.$report['year'].')') ?></div>
        </div>
        <div>
            <div style="font-size:.75rem;opacity:.7">رقم اللوحة / Plate</div>
            <div style="font-weight:600"><?= clean($report['plate_number'] ?? '—') ?></div>
        </div>
        <div>
            <div style="font-size:.75rem;opacity:.7">العميل / Customer</div>
            <div style="font-weight:600"><?= clean($report['customer_name'] ?? '—') ?></div>
        </div>
        <div>
            <div style="font-size:.75rem;opacity:.7">الهاتف / Phone</div>
            <div style="font-weight:600"><?= clean($report['customer_phone'] ?? '—') ?></div>
        </div>
    </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= clean($msg) ?></div>
<?php endif; ?>

<div class="grid-2">

<!-- ── EMAIL ──────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <span class="ar">📧 إرسال بريد إلكتروني</span>
        <span class="en">Send Email</span>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="send_email">
        <div class="form-group">
            <label class="required">
                <span class="ar">البريد الإلكتروني للعميل</span>
                <span class="en">Customer Email</span>
            </label>
            <input type="email" name="email_to"
                   value="<?= clean($_POST['email_to'] ?? '') ?>"
                   placeholder="customer@email.com" style="direction:ltr" required>
        </div>
        <div class="form-group">
            <label>
                <span class="ar">الموضوع</span>
                <span class="en">Subject</span>
            </label>
            <input type="text" name="email_subj"
                   value="<?= clean($_POST['email_subj'] ?? 'تقرير فحص السيارة - Vehicle Inspection Report') ?>">
        </div>
        <div class="form-group">
            <label>
                <span class="ar">نص الرسالة</span>
                <span class="en">Message</span>
            </label>
            <textarea name="email_body" rows="8"><?= clean($_POST['email_body'] ?? $defaultEmail) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">
            📧 <span class="ar">إرسال البريد</span>&nbsp;<span class="en">Send Email</span>
        </button>
        <div style="margin-top:.75rem;padding:.6rem;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.8rem;color:#92400e">
            <span class="ar">⚠️ لإرسال البريد، يجب ضبط إعدادات SMTP في صفحة الإعدادات أو تفعيل PHP mail().</span><br>
            <span class="en">⚠️ Configure SMTP settings in Settings page, or enable PHP mail() on your server.</span>
        </div>
    </form>
</div>

<!-- ── WHATSAPP + SMS ──────────────────────────────── -->
<div>
    <!-- WhatsApp -->
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header">
            <span class="ar">💬 إرسال عبر واتساب</span>
            <span class="en">Send via WhatsApp</span>
        </div>
        <p style="font-size:.9rem;color:var(--text-muted);margin-bottom:1rem">
            <span class="ar">انقر الزر لفتح واتساب مع الرسالة جاهزة للإرسال للعميل مباشرةً</span><br>
            <span class="en">Click to open WhatsApp with the message pre-filled for the customer</span>
        </p>

        <?php
        $phone = preg_replace('/[^0-9]/', '', $report['customer_phone'] ?? '');
        // Convert Syrian local format 09xx to international 963xx
        if (str_starts_with($phone, '0')) $phone = '963' . substr($phone, 1);
        $waUrl = 'https://wa.me/' . $phone . '?text=' . $whatsappMsg;
        ?>

        <?php if ($phone): ?>
        <a href="<?= $waUrl ?>" target="_blank" class="btn btn-success btn-lg" style="width:100%;text-align:center;background:#25d366;display:block">
            <span style="font-size:1.2rem">📱</span>
            <span class="ar"> فتح واتساب — <?= clean($report['customer_phone']) ?></span>
        </a>
        <?php else: ?>
        <div class="alert alert-error">
            <span class="ar">رقم هاتف العميل غير مسجل في التقرير</span>
            <span class="en">No customer phone number in this report</span>
        </div>
        <?php endif; ?>

        <div style="margin-top:1rem">
            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">
                <span class="ar">نص الرسالة (للمراجعة)</span>
                <span class="en">Message preview</span>
            </label>
            <textarea rows="6" readonly style="width:100%;background:#f9fafb;font-size:.82rem;direction:rtl"><?= clean(urldecode($whatsappMsg)) ?></textarea>
        </div>
    </div>

    <!-- SMS Log -->
    <div class="card">
        <div class="card-header">
            <span class="ar">📱 تسجيل رسالة SMS</span>
            <span class="en">Log SMS</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="log_sms">
            <div class="form-group">
                <label><span class="ar">رقم الهاتف</span><span class="en">Phone Number</span></label>
                <input type="tel" name="sms_phone"
                       value="<?= clean($report['customer_phone'] ?? '') ?>"
                       style="direction:ltr">
            </div>
            <div class="form-group">
                <label><span class="ar">نص الرسالة</span><span class="en">Message</span></label>
                <textarea name="sms_body" rows="4"><?= clean($defaultSMS) ?></textarea>
            </div>
            <button type="submit" class="btn btn-secondary">
                📱 <span class="ar">تسجيل الرسالة</span>&nbsp;<span class="en">Log SMS</span>
            </button>
            <div style="margin-top:.5rem;font-size:.78rem;color:var(--text-muted)">
                <span class="ar">يسجل الرسالة في السجل — للإرسال الفعلي اربط مزود SMS خارجي</span>
            </div>
        </form>
    </div>
</div>

</div>

<!-- ── NOTIFICATION HISTORY ───────────────────────── -->
<div class="card">
    <div class="card-header">
        <span class="ar">📋 سجل الإشعارات المرسلة</span>
        <span class="en">Notification History</span>
    </div>

    <?php if (empty($pastNotifs)): ?>
    <div style="text-align:center;padding:1.5rem;color:var(--text-muted)">
        <span class="ar">لم يتم إرسال أي إشعارات لهذا التقرير بعد</span>
        <span class="en"> — No notifications sent yet for this report</span>
    </div>
    <?php else: ?>
    <table class="reports-table" style="font-size:.85rem">
        <thead>
            <tr>
                <th><span class="ar">النوع</span></th>
                <th><span class="ar">المستلم</span></th>
                <th><span class="ar">الموضوع</span></th>
                <th><span class="ar">الحالة</span></th>
                <th><span class="ar">التاريخ</span></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pastNotifs as $n): ?>
        <tr>
            <td>
                <?php if ($n['type']==='email'): ?>
                    <span class="badge badge-muted">📧 Email</span>
                <?php else: ?>
                    <span class="badge badge-muted">📱 SMS</span>
                <?php endif; ?>
            </td>
            <td style="direction:ltr"><?= clean($n['recipient']) ?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= clean($n['subject'] ?? $n['message'] ?? '—') ?>
            </td>
            <td>
                <?php if ($n['status']==='sent'): ?>
                    <span class="badge badge-good">✅ أُرسل</span>
                <?php elseif ($n['status']==='failed'): ?>
                    <span class="badge badge-danger" title="<?= clean($n['error_message']??'') ?>">❌ فشل</span>
                <?php else: ?>
                    <span class="badge badge-warning">⏳ معلق</span>
                <?php endif; ?>
            </td>
            <td style="font-size:.78rem;white-space:nowrap"><?= $n['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
