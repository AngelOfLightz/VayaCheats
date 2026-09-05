<?php
/**
 * API Test Script
 * Test login endpoint directly
 */
header('Content-Type: application/json');
require_once 'config.php';

// Test data
$testUsername = 'test_user';
$testPassword = 'test_password';

echo "=== API Login Test ===\n\n";

// Test 1: Check if api_tokens table exists
echo "Test 1: Checking api_tokens table...\n";
try {
    $checkTable = $db->query("SHOW TABLES LIKE 'api_tokens'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ api_tokens table exists\n";
    } else {
        echo "✗ api_tokens table does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking table: " . $e->getMessage() . "\n";
}

// Test 2: Check if user exists
echo "\nTest 2: Checking if user exists...\n";
try {
    $checkUser = $db->prepare("SELECT id, username FROM kullanicilar WHERE username = ? LIMIT 1");
    $checkUser->execute([$testUsername]);
    $user = $checkUser->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "✓ User found: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
    } else {
        echo "✗ User not found. Creating test user...\n";
        $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
        $insertUser = $db->prepare("INSERT INTO kullanicilar (username, password, role, bitis_tarihi) VALUES (?, ?, 'vip', DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $insertUser->execute([$testUsername, $hashedPassword]);
        echo "✓ Test user created\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Test login directly
echo "\nTest 3: Testing login endpoint logic...\n";
try {
    $query = $db->prepare("SELECT id, username, password, role, bitis_tarihi FROM kullanicilar WHERE username = ?");
    $query->execute([$testUsername]);
    $userData = $query->fetch(PDO::FETCH_ASSOC);

    if ($userData && password_verify($testPassword, $userData['password'])) {
        echo "✓ Password verification successful\n";
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        echo "✓ Token generated: " . substr($token, 0, 16) . "...\n";
        
        // Delete old tokens
        $deleteOldTokens = $db->prepare("DELETE FROM api_tokens WHERE user_id = ?");
        $deleteOldTokens->execute([$userData['id']]);
        
        // Insert new token
        $insertToken = $db->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $insertToken->execute([$userData['id'], $token, $expiresAt]);
        
        echo "✓ Token stored in database\n";
        echo "✓ Token expires at: " . $expiresAt . "\n";
        
        // Verify token was stored
        $verifyToken = $db->prepare("SELECT * FROM api_tokens WHERE token = ? LIMIT 1");
        $verifyToken->execute([$token]);
        $storedToken = $verifyToken->fetch(PDO::FETCH_ASSOC);
        
        if ($storedToken) {
            echo "✓ Token verification successful\n";
        } else {
            echo "✗ Token verification failed\n";
        }
    } else {
        echo "✗ Password verification failed\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Check API endpoint accessibility
echo "\nTest 4: Checking API endpoint file...\n";
$apiFile = __DIR__ . '/api_login.php';
if (file_exists($apiFile)) {
    echo "✓ api_login.php exists\n";
} else {
    echo "✗ api_login.php not found\n";
}

echo "\n=== Test Complete ===\n";
?>
