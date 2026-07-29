<?php
require_once 'config/db.php';

$pdo = db();
$username = 'admin';
$password = 'admin123';

// Fetch the user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

echo "<h2>🔍 Login Debug</h2>";

if ($user) {
    echo "<p>✅ User found: <strong>" . $user['username'] . "</strong></p>";
    echo "<p>Full name: " . ($user['full_name_ar'] ?? $user['full_name_en']) . "</p>";
    echo "<p>Role: " . $user['role'] . "</p>";
    echo "<p>is_active: " . ($user['is_active'] ? 'Yes ✅' : 'No ❌') . "</p>";
    echo "<p>Hash in DB: <code>" . $user['password_hash'] . "</code></p>";
    echo "<p>Hash length: " . strlen($user['password_hash']) . " (should be 60)</p>";
    
    // Test password_verify
    if (password_verify($password, $user['password_hash'])) {
        echo "<p style='color:green;font-weight:bold;'>✅ Password matches! Login should work.</p>";
    } else {
        echo "<p style='color:red;font-weight:bold;'>❌ Password does NOT match.</p>";
        
        // Generate a new hash
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "<p>New hash for '<strong>$password</strong>':</p>";
        echo "<code>$newHash</code>";
        echo "<p>Run this SQL to fix it:</p>";
        echo "<pre style='background:#1a1a2e;color:#a8f0a8;padding:10px;border-radius:6px;'>";
        echo "UPDATE users SET password_hash = '$newHash' WHERE username = '$username';";
        echo "</pre>";
        
        // Also display the hash from our known working hash
        echo "<p><strong>Or use the known working hash:</strong></p>";
        echo "<pre style='background:#1a1a2e;color:#a8f0a8;padding:10px;border-radius:6px;'>";
        echo "UPDATE users SET password_hash = '\$2y\$10\$Yh/9T1NlF4BdVzHfy6E6xuY/KvXHd0K6XWbMwxU7Gz8Q9RaP6qW5a' WHERE username = '$username';";
        echo "</pre>";
    }
} else {
    echo "<p style='color:red;font-weight:bold;'>❌ User not found!</p>";
}
?>