<?php
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// Only owner can access
if (!isOwner()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

$action = $_POST['action'] ?? '';

// Add owner
if ($action === 'add_owner') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Check if user exists
    $userCheck = $db->prepare("SELECT id, username FROM kullanicilar WHERE id = ?");
    $userCheck->execute([$user_id]);
    $user = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if user is already owner
    if ($user['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'User is already an owner']);
        exit;
    }
    
    // Promote to owner
    $updateRole = $db->prepare("UPDATE kullanicilar SET role = 'owner' WHERE id = ?");
    $updateRole->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => $user['username'] . ' promoted to Owner']);
    exit;
}

// Remove owner
if ($action === 'remove_owner') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Owner cannot remove themselves
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot remove yourself as owner']);
        exit;
    }
    
    // Check if user exists and is owner
    $userCheck = $db->prepare("SELECT id, username, role FROM kullanicilar WHERE id = ?");
    $userCheck->execute([$user_id]);
    $user = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if ($user['role'] !== 'owner') {
        echo json_encode(['success' => false, 'message' => 'User is not an owner']);
        exit;
    }
    
    // Demote to admin
    $updateRole = $db->prepare("UPDATE kullanicilar SET role = 'admin' WHERE id = ?");
    $updateRole->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => $user['username'] . ' demoted to Admin']);
    exit;
}

// Change user role
if ($action === 'change_role') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $new_role = sanitizeInput($_POST['new_role'] ?? '');
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    if (!in_array($new_role, ['user', 'vip', 'moderator', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    // Owner cannot change their own role
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot change your own role']);
        exit;
    }
    
    // Check if user exists
    $userCheck = $db->prepare("SELECT id, username, role FROM kullanicilar WHERE id = ?");
    $userCheck->execute([$user_id]);
    $targetUser = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Owner cannot demote other owners
    if ($targetUser['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'Use the Owner Management section to manage owners']);
        exit;
    }
    
    // Update role
    $updateRole = $db->prepare("UPDATE kullanicilar SET role = ? WHERE id = ?");
    $updateRole->execute([$new_role, $user_id]);
    
    echo json_encode(['success' => true, 'message' => $targetUser['username'] . ' role changed to ' . strtoupper($new_role)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
