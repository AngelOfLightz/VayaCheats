<?php
require_once 'config.php';

header('Content-Type: application/json');

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = validateId($_POST['product_id'] ?? 0);
$comment = sanitizeInput($_POST['comment'] ?? '');

if ($product_id <= 0 || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or empty comment']);
    exit;
}

// Check if product exists
$productCheck = $db->prepare("SELECT id FROM hileler WHERE id = ?");
$productCheck->execute([$product_id]);
if (!$productCheck->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check for active mute
$muteCheck = $db->prepare("SELECT expires_at FROM mutes WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
$muteCheck->execute([$user_id]);
$activeMute = $muteCheck->fetch();

if ($activeMute) {
    echo json_encode(['success' => false, 'muted' => true, 'message' => 'You are currently muted.']);
    exit;
}

// Insert comment
$insertComment = $db->prepare("INSERT INTO comments (product_id, user_id, content) VALUES (?, ?, ?)");
$insertComment->execute([$product_id, $user_id, $comment]);

echo json_encode(['success' => true, 'message' => 'Comment posted successfully']);
exit;
?>
