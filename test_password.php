<?php
require_once 'config.php';

// Get the user from database
$stmt = $conn->prepare("SELECT username, password FROM users WHERE username = ?");
$username = 'demo';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<h2>Password Debug Test</h2>";
echo "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
echo "<p><strong>Stored Hash:</strong> " . htmlspecialchars($user['password']) . "</p>";
echo "<p><strong>Hash Length:</strong> " . strlen($user['password']) . "</p>";

// Test different passwords
$passwords = ['demo123', 'password123', 'demo', 'test'];

echo "<h3>Testing Passwords:</h3>";
foreach ($passwords as $pass) {
    $verify = password_verify($pass, $user['password']);
    echo "<p>Password '<strong>$pass</strong>': " . ($verify ? "✅ MATCH!" : "❌ No match") . "</p>";
}

// Generate a fresh hash for demo123
$new_hash = password_hash('demo123', PASSWORD_DEFAULT);
echo "<h3>Fresh Hash for 'demo123':</h3>";
echo "<p>" . $new_hash . "</p>";
?>
