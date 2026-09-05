<?php
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// Admin or owner can access
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

$action = $_POST['action'] ?? '';

// Reset password
if ($action === 'reset_password') {
    $target_user_id = validateId($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($target_user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    if (empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Password fields cannot be empty']);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }
    
    // Password validation
    if (strlen($new_password) < 2) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 2 characters']);
        exit;
    }
    
    if (strlen($new_password) > 128) {
        echo json_encode(['success' => false, 'message' => 'Password must be at most 128 characters']);
        exit;
    }
    
    // Get target user
    $targetUserQuery = $db->prepare("SELECT id, username, role FROM kullanicilar WHERE id = ?");
    $targetUserQuery->execute([$target_user_id]);
    $targetUser = $targetUserQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // User cannot reset their own password through admin panel
    if ($target_user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Use the profile page to change your own password']);
        exit;
    }
    
    // Admin cannot reset owner password
    if (isAdminOnly() && $targetUser['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'You cannot reset Owner password']);
        exit;
    }
    
    // Admin cannot reset admin password (unless it's themselves)
    if (isAdminOnly() && $targetUser['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'You cannot reset other Admin passwords']);
        exit;
    }
    
    // Hash new password
    $hashed_password = hashPassword($new_password);
    
    // Update password
    $updatePassword = $db->prepare("UPDATE kullanicilar SET password = ? WHERE id = ?");
    $updatePassword->execute([$hashed_password, $target_user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Password reset for ' . $targetUser['username']]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
