<?php
require_once 'config.php';

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$profil_color = sanitizeInput($_POST['profil_color'] ?? '');
$avatar = sanitizeInput($_POST['avatar'] ?? '');

// Validate color format (hex)
if (!empty($profil_color) && !preg_match('/^#[0-9a-fA-F]{6}$/', $profil_color)) {
    $profil_color = '#00ffcc'; // Default
}

// Update profile
$updateQuery = $db->prepare("UPDATE kullanicilar SET profil_color = ?, avatar = ? WHERE id = ?");
$updateQuery->execute([$profil_color, $avatar, $user_id]);

// Update session
$_SESSION['profil_color'] = $profil_color;

header("Location: user.php?tab=tab-profile");
exit;
?>
