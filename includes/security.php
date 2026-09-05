<?php
/**
 * VayaCheats Security Layer
 * Provides CSRF protection, input sanitization, and security utilities
 */

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Return JSON error for API calls
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
            exit;
        }
        die('CSRF validation failed. Please refresh the page and try again.');
    }
    return true;
}

function getCsrfToken() {
    return generateCsrfToken();
}

// Input Sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitizeFilename($filename) {
    // Remove dangerous characters and preserve safe ones
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

// Password Hashing (Upgraded from MD5)
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    // Check if it's a legacy MD5 hash
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        return md5($password) === $hash;
    }
    // Use modern verification
    return password_verify($password, $hash);
}

// Session Security
function secureSession() {
    // Only set secure cookie flags if using HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically (only if session is active)
    if (isset($_SESSION['created'])) {
        if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    } else {
        $_SESSION['created'] = time();
    }
}

// XSS Protection
function escapeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// SQL Injection Prevention (use with PDO prepared statements)
function validateId($id) {
    return filter_var($id, FILTER_VALIDATE_INT);
}

// File Upload Security
function validateImageUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large. Maximum 5MB allowed.'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
    }
    
    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Invalid file extension.'];
    }
    
    // Validate it's actually an image
    if (!getimagesize($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'File is not a valid image.'];
    }
    
    return ['valid' => true];
}

// Rate Limiting
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['attempts']++;
    return true;
}

// Initialize security
secureSession();
?>
