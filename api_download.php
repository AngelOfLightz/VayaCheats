<?php
/**
 * VayaCheats API - Module Download Endpoint
 * Returns JSON response with download URL or direct file
 */
header('Content-Type: application/json');
require_once 'config.php';

// Handle CORS for launcher
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

$user_id = (int)$tokenData['user_id'];

if (!isset($_GET['module_id'])) {
    echo json_encode(['success' => false, 'message' => 'Module ID required']);
    exit;
}

$module_id = validateId($_GET['module_id']);

try {
    // Get module info
    $query = $db->prepare("SELECT hile_adi, dosya_yolu, durum, required_subscription_level FROM hileler WHERE id = ? LIMIT 1");
    $query->execute([$module_id]);
    $module = $query->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit;
    }

    // Check module status
    if ($module['durum'] !== 'UNDETECTED') {
        echo json_encode(['success' => false, 'message' => 'Module is currently not available']);
        exit;
    }

    // Check subscription level
    $required_level = $module['required_subscription_level'] ?? 0;
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
    
    if (isOwner()) {
        $user_level = 99;
    }
    
    if ($user_level < $required_level) {
        echo json_encode(['success' => false, 'message' => 'Insufficient subscription level']);
        exit;
    }

    // Check if file exists
    if (empty($module['dosya_yolu']) || !file_exists($module['dosya_yolu'])) {
        echo json_encode(['success' => false, 'message' => 'Module file not found on server']);
        exit;
    }

    // Log download
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logDownload = $db->prepare("INSERT INTO download_history (user_id, product_id, ip_address) VALUES (?, ?, ?)");
    $logDownload->execute([$user_id, $module_id, $ip_address]);

    // Return download URL (launcher will download from this URL)
    $downloadUrl = "https://vayacheats.freedev.app/indir.php?hile_id=" . $module_id;
    
    echo json_encode([
        'success' => true,
        'download_url' => $downloadUrl,
        'filename' => basename($module['dosya_yolu']),
        'size' => filesize($module['dosya_yolu'])
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
