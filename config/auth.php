<?php
if (session_status()===PHP_SESSION_NONE) session_start();
define('ROLE_LEVELS',['viewer'=>1,'technician'=>2,'manager'=>3,'admin'=>4]);

function attemptLogin(string $username, string $password): array|false {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND is_active=1 LIMIT 1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password,$user['password_hash'])) {
        auditLog(null,'login_failed','auth',null,"Failed login: $username");
        return false;
    }
    $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['lang']     = $user['lang'] ?? 'ar';
    session_regenerate_id(true);
    auditLog($user['id'],'login','auth',null,'Successful login');
    unset($user['password_hash']);
    return $user;
}
function logout(): void {
    auditLog($_SESSION['user_id']??null,'logout','auth',null,'Logged out');
    session_unset(); session_destroy();
}
function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }
function getCurrentUser(): array {
    if (!isLoggedIn()) return [];
    static $u=null;
    if ($u!==null) return $u;
    $stmt=db()->prepare("SELECT id,username,full_name_ar,full_name_en,email,phone,role,lang FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $u=$stmt->fetch()?:[];
    return $u;
}
function currentUserName(): string {
    $u=getCurrentUser();
    if (!$u) return '';
    return currentLang()==='ar'?($u['full_name_ar']??$u['username']):($u['full_name_en']??$u['username']);
}
function currentRole(): string { return $_SESSION['role']??'viewer'; }
function hasRole(string $minRole): bool {
    return (ROLE_LEVELS[currentRole()]??0)>=(ROLE_LEVELS[$minRole]??99);
}
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: '.APP_URL.'/modules/login.php?redirect='.urlencode($_SERVER['REQUEST_URI']??''));
        exit;
    }
}
function requireRole(string $minRole): void {
    requireAuth();
    if (!hasRole($minRole)) { http_response_code(403); die('<h2>403 — Access Denied</h2><a href="'.APP_URL.'/modules/dashboard.php">Back</a>'); }
}
function generateApiToken(int $userId): string {
    $token=bin2hex(random_bytes(32));
    db()->prepare("UPDATE users SET api_token=? WHERE id=?")->execute([$token,$userId]);
    return $token;
}
function validateApiToken(string $token): array|false {
    if (empty($token)) return false;
    $stmt=db()->prepare("SELECT * FROM users WHERE api_token=? AND is_active=1 LIMIT 1");
    $stmt->execute([$token]);
    $u=$stmt->fetch();
    if (!$u) return false;
    unset($u['password_hash']); return $u;
}
function hashPassword(string $p): string { return password_hash($p,PASSWORD_BCRYPT,['cost'=>12]); }
function auditLog(?int $userId, string $action, string $targetType='', ?int $targetId=null, string $details=''): void {
    try {
        db()->prepare("INSERT INTO audit_logs (user_id,username,action,target_type,target_id,details,ip_address) VALUES (?,?,?,?,?,?,?)")
           ->execute([$userId,$_SESSION['username']??null,$action,$targetType,$targetId,$details,$_SERVER['REMOTE_ADDR']??'']);
    } catch(Exception $e){}
}
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrfField(): string { return '<input type="hidden" name="csrf_token" value="'.csrfToken().'">'; }
function validateCsrf(): bool { return hash_equals(csrfToken(),$_POST['csrf_token']??''); }
function requireCsrf(): void { if(!validateCsrf()){http_response_code(403);die(json_encode(['success'=>false,'error'=>'Invalid CSRF token']));} }
