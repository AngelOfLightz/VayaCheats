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

// Ban user
if ($action === 'ban_user') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $ban_type = sanitizeInput($_POST['ban_type'] ?? 'temporary');
    $duration_hours = validateId($_POST['duration_hours'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Check if user exists
    $userCheck = $db->prepare("SELECT id, username, role FROM kullanicilar WHERE id = ?");
    $userCheck->execute([$user_id]);
    $targetRole = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetRole) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Admin cannot ban themselves
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot ban yourself']);
        exit;
    }
    
    // Admin cannot ban owners or admins
    if (isAdminOnly() && ($targetRole['role'] === 'owner' || $targetRole['role'] === 'admin')) {
        echo json_encode(['success' => false, 'message' => 'You cannot ban this user']);
        exit;
    }
    
    // Owner cannot ban other owners
    if (isOwner() && $targetRole['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'Use the Owner Management section to manage owners']);
        exit;
    }
    
    // Calculate expiry
    $expires_at = null;
    if ($ban_type === 'temporary' && $duration_hours > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration_hours hours"));
    }
    
    // Insert ban
    $insertBan = $db->prepare("INSERT INTO bans (user_id, reason, expires_at) VALUES (?, ?, ?)");
    $insertBan->execute([$user_id, $reason, $expires_at]);
    
    // Update user role to banned
    $updateRole = $db->prepare("UPDATE kullanicilar SET role = 'banned' WHERE id = ?");
    $updateRole->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => $targetRole['username'] . ' has been banned']);
    exit;
}

// Unban user
if ($action === 'unban_user') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Remove active bans
    $removeBans = $db->prepare("DELETE FROM bans WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $removeBans->execute([$user_id]);
    
    // Restore user role (default to user)
    $restoreRole = $db->prepare("UPDATE kullanicilar SET role = 'user' WHERE id = ?");
    $restoreRole->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => 'User has been unbanned']);
    exit;
}

// Mute user
if ($action === 'mute_user') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    $duration_hours = validateId($_POST['duration_hours'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Check if user exists
    $userCheck = $db->prepare("SELECT id, username, role FROM kullanicilar WHERE id = ?");
    $userCheck->execute([$user_id]);
    $targetRole = $userCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetRole) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Admin cannot mute themselves
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot mute yourself']);
        exit;
    }
    
    // Admin cannot mute owners or admins
    if (isAdminOnly() && ($targetRole['role'] === 'owner' || $targetRole['role'] === 'admin')) {
        echo json_encode(['success' => false, 'message' => 'You cannot mute this user']);
        exit;
    }
    
    // Owner cannot mute other owners
    if (isOwner() && $targetRole['role'] === 'owner') {
        echo json_encode(['success' => false, 'message' => 'You cannot mute other owners']);
        exit;
    }
    
    // Calculate expiry
    $expires_at = null;
    if ($duration_hours > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration_hours hours"));
    }
    
    // Insert mute
    $insertMute = $db->prepare("INSERT INTO mutes (user_id, reason, expires_at) VALUES (?, ?, ?)");
    $insertMute->execute([$user_id, $reason, $expires_at]);
    
    echo json_encode(['success' => true, 'message' => $targetRole['username'] . ' has been muted']);
    exit;
}

// Unmute user
if ($action === 'unmute_user') {
    $user_id = validateId($_POST['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Remove active mutes
    $removeMutes = $db->prepare("DELETE FROM mutes WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $removeMutes->execute([$user_id]);
    
    echo json_encode(['success' => true, 'message' => 'User has been unmuted']);
    exit;
}

// Delete comment
if ($action === 'delete_comment') {
    $comment_id = validateId($_POST['comment_id'] ?? 0);
    
    if ($comment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit;
    }
    
    $deleteComment = $db->prepare("DELETE FROM comments WHERE id = ?");
    $deleteComment->execute([$comment_id]);
    
    echo json_encode(['success' => true, 'message' => 'Comment deleted']);
    exit;
}

// Pin comment
if ($action === 'pin_comment') {
    $comment_id = validateId($_POST['comment_id'] ?? 0);
    
    if ($comment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit;
    }
    
    // Get current pin status
    $commentCheck = $db->prepare("SELECT is_pinned FROM comments WHERE id = ?");
    $commentCheck->execute([$comment_id]);
    $comment = $commentCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    
    // Toggle pin
    $newPinStatus = $comment['is_pinned'] ? 0 : 1;
    $updatePin = $db->prepare("UPDATE comments SET is_pinned = ? WHERE id = ?");
    $updatePin->execute([$newPinStatus, $comment_id]);
    
    echo json_encode(['success' => true, 'message' => $newPinStatus ? 'Comment pinned' : 'Comment unpinned']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
