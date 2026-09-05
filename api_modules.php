<?php
/**
 * VayaCheats API - Modules Endpoint
 * Returns JSON response for available modules/cheats
 */
header('Content-Type: application/json');
require_once 'config.php';

// Handle CORS for launcher
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Database-based token validation
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - No token provided']);
    exit;
}

// Validate token from database
$tokenQuery = $db->prepare("
    SELECT t.user_id, t.expires_at 
    FROM api_tokens t 
    WHERE t.token = ? AND t.is_active = 1 AND t.expires_at > NOW()
    LIMIT 1
");
$tokenQuery->execute([$token]);
$tokenData = $tokenQuery->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Invalid or expired token']);
    exit;
}

// Update last used timestamp
$updateLastUsed = $db->prepare("UPDATE api_tokens SET last_used = NOW() WHERE token = ?");
$updateLastUsed->execute([$token]);

$user_id = (int)$tokenData['user_id'];

try {
    // Get user's subscription level
    $user_level = 0;
    $subCheck = $db->prepare("
        SELECT st.level 
        FROM subscriptions s 
        JOIN subscription_types st ON s.subscription_type_id = st.id 
        WHERE s.user_id = ? AND s.status = 'active' AND (s.end_date IS NULL OR s.end_date > NOW())
        ORDER BY st.level DESC 
        LIMIT 1
    ");
    $subCheck->execute([$user_id]);
    $subResult = $subCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($subResult) {
        $user_level = $subResult['level'];
    }
    
    // Owner has level 99
    if (isOwner()) {
        $user_level = 99;
    }

    // Get available modules based on subscription level
    $query = $db->prepare("
        SELECT id, hile_adi, aciklama, oyun_adi, durum, required_subscription_level, 
               version, author, created_at, download_count, thumbnail
        FROM hileler 
        WHERE durum = 'UNDETECTED' 
        AND (required_subscription_level IS NULL OR required_subscription_level <= ?)
        ORDER BY created_at DESC
    ");
    $query->execute([$user_level]);
    $modules = $query->fetchAll(PDO::FETCH_ASSOC);

    $moduleList = [];
    foreach ($modules as $module) {
        $moduleList[] = [
            'id' => $module['id'],
            'name' => $module['hile_adi'],
            'description' => $module['aciklama'] ?? '',
            'game' => $module['oyun_adi'] ?? 'Unknown',
            'status' => $module['durum'],
            'required_level' => $module['required_subscription_level'] ?? 0,
            'version' => $module['version'] ?? '1.0',
            'author' => $module['author'] ?? 'VayaCheats',
            'created_at' => $module['created_at'],
            'download_count' => $module['download_count'] ?? 0,
            'thumbnail' => $module['thumbnail'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'modules' => $moduleList,
        'user_level' => $user_level
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
