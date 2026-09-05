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

// Grant subscription
if ($action === 'grant_subscription') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $subscription_type_id = validateId($_POST['subscription_type_id'] ?? 0);
    $duration_days = validateId($_POST['duration_days'] ?? 30);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($user_id <= 0 || $subscription_type_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Check if user exists
    $userCheck = $db->prepare("SELECT id, username, role FROM kullanicilar WHERE id = ?");
    $userCheck->execute([$user_id]);
    $user = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Admin cannot modify owner subscriptions
    if (isAdminOnly() && $user['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'You cannot modify owner subscriptions']);
        exit;
    }
    
    // Check if subscription类型 exists
    $typeCheck = $db->prepare("SELECT id, name, level FROM subscription_types WHERE id = ?");
    $typeCheck->execute([$subscription_type_id]);
    $subType = $typeCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$subType) {
        echo json_encode(['success' => false, 'message' => 'Subscription type not found']);
        exit;
    }
    
    // Admin cannot grant Ultimate (level 3) or Owner (level 99) subscriptions
    if (isAdminOnly() && $subType['level'] >= 3) {
        echo json_encode(['success' => false, 'message' => 'You cannot grant this subscription level']);
        exit;
    }
    
    // Check if user already has active subscription
    $existingSub = $db->prepare("SELECT id, subscription_type_id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $existingSub->execute([$user_id]);
    $existing = $existingSub->fetch(PDO::FETCH_ASSOC);
    
    $end_date = null;
    if ($duration_days > 0) {
        $end_date = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
    }
    
    if ($existing) {
        // Update existing subscription
        $updateSub = $db->prepare("UPDATE subscriptions SET subscription_type_id = ?, end_date = ?, status = 'active' WHERE id = ?");
        $updateSub->execute([$subscription_type_id, $end_date, $existing['id']]);
        
        // Log history
        $logHistory = $db->prepare("INSERT INTO subscription_history (subscription_id, user_id, action, from_type_id, to_type_id, old_end_date, new_end_date, performed_by, reason) VALUES (?, ?, 'manually_granted', ?, ?, ?, ?, ?)");
        $logHistory->execute([$existing['id'], $user_id, $existing['subscription_type_id'], $subscription_type_id, $existing['end_date'], $end_date, $_SESSION['user_id'], $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription updated for ' . $user['username']]);
    } else {
        // Create new subscription
        $insertSub = $db->prepare("INSERT INTO subscriptions (user_id, subscription_type_id, end_date, status) VALUES (?, ?, ?, 'active')");
        $insertSub->execute([$user_id, $subscription_type_id, $end_date]);
        $sub_id = $db->lastInsertId();
        
        // Log history
        $logHistory = $db->prepare("INSERT INTO subscription_history (subscription_id, user_id, action, to_type_id, new_end_date, performed_by, reason) VALUES (?, ?, 'manually_granted', ?, ?, ?, ?)");
        $logHistory->execute([$sub_id, $user_id, $subscription_type_id, $end_date, $_SESSION['user_id'], $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription granted to ' . $user['username']]);
    }
    exit;
}

// Remove subscription
if ($action === 'remove_subscription') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Admin cannot remove owner subscriptions
    $targetRoleCheck = $db->prepare("SELECT role FROM kullanicilar WHERE id = ?");
    $targetRoleCheck->execute([$user_id]);
    $targetRole = $targetRoleCheck->fetch(PDO::FETCH_ASSOC);
    
    if (isAdminOnly() && $targetRole['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'You cannot modify owner subscriptions']);
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
    
    // Get active subscription
    $activeSub = $db->prepare("SELECT id, subscription_type_id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $activeSub->execute([$user_id]);
    $sub = $activeSub->fetch(PDO::FETCH_ASSOC);
    
    if (!$sub) {
        echo json_encode(['success' => false, 'message' => 'No active subscription found']);
        exit;
    }
    
    // Cancel subscription
    $cancelSub = $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
    $cancelSub->execute([$sub['id']]);
    
    // Log history
    $logHistory = $db->prepare("INSERT INTO subscription_history (subscription_id, user_id, action, from_type_id, old_end_date, performed_by, reason) VALUES (?, ?, 'manually_removed', ?, ?, ?, ?)");
    $logHistory->execute([$sub['id'], $user_id, $sub['subscription_type_id'], $sub['end_date'], $_SESSION['user_id'], $reason]);
    
    echo json_encode(['success' => true, 'message' => 'Subscription removed from ' . $user['username']]);
    exit;
}

// Extend subscription
if ($action === 'extend_subscription') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $additional_days = validateId($_POST['additional_days'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($user_id <= 0 || $additional_days <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Get active subscription
    $activeSub = $db->prepare("SELECT id, subscription_type_id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $activeSub->execute([$user_id]);
    $sub = $activeSub->fetch(PDO::FETCH_ASSOC);
    
    if (!$sub) {
        echo json_encode(['success' => false, 'message' => 'No active subscription found']);
        exit;
    }
    
    $old_end_date = $sub['end_date'];
    $base_date = $old_end_date ? $old_end_date : date('Y-m-d H:i:s');
    $new_end_date = date('Y-m-d H:i:s', strtotime("$base_date +$additional_days days"));
    
    // Update subscription
    $updateSub = $db->prepare("UPDATE subscriptions SET end_date = ? WHERE id = ?");
    $updateSub->execute([$new_end_date, $sub['id']]);
    
    // Log history
    $logHistory = $db->prepare("INSERT INTO subscription_history (subscription_id, user_id, action, old_end_date, new_end_date, performed_by, reason) VALUES (?, ?, 'extended', ?, ?, ?, ?)");
    $logHistory->execute([$sub['id'], $user_id, $old_end_date, $new_end_date, $_SESSION['user_id'], $reason]);
    
    echo json_encode(['success' => true, 'message' => 'Subscription extended by ' . $additional_days . ' days']);
    exit;
}

// Shorten subscription
if ($action === 'shorten_subscription') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $reduce_days = validateId($_POST['reduce_days'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($user_id <= 0 || $reduce_days <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Get active subscription
    $activeSub = $db->prepare("SELECT id, subscription_type_id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $activeSub->execute([$user_id]);
    $sub = $activeSub->fetch(PDO::FETCH_ASSOC);
    
    if (!$sub) {
        echo json_encode(['success' => false, 'message' => 'No active subscription found']);
        exit;
    }
    
    if (!$sub['end_date']) {
        echo json_encode(['success' => false, 'message' => 'Cannot shorten permanent subscription']);
        exit;
    }
    
    $old_end_date = $sub['end_date'];
    $new_end_date = date('Y-m-d H:i:s', strtotime("$old_end_date -$reduce_days days"));
    
    if (strtotime($new_end_date) < time()) {
        // Expire immediately
        $new_end_date = date('Y-m-d H:i:s');
        $updateSub = $db->prepare("UPDATE subscriptions SET end_date = ?, status = 'expired' WHERE id = ?");
        $updateSub->execute([$new_end_date, $sub['id']]);
        
        $logHistory = $db->prepare("INSERT INTO subscription_history (subscription_id, user_id, action, old_end_date, new_end_date, performed_by, reason) VALUES (?, ?, 'shortened', ?, ?, ?, ?)");
        $logHistory->execute([$sub['id'], $user_id, $old_end_date, $new_end_date, $_SESSION['user_id'], $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription expired immediately']);
    } else {
        $updateSub = $db->prepare("UPDATE subscriptions SET end_date = ? WHERE id = ?");
        $updateSub->execute([$new_end_date, $sub['id']]);
        
        $logHistory = $db->prepare("INSERT INTO subscription_history (subscription_id, user_id, action, old_end_date, new_end_date, performed_by, reason) VALUES (?, ?, 'shortened', ?, ?, ?, ?)");
        $logHistory->execute([$sub['id'], $user_id, $old_end_date, $new_end_date, $_SESSION['user_id'], $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription shortened by ' . $reduce_days . ' days']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
