<?php
/**
 * VayaCheats API - Profile Endpoint
 * Returns JSON response for user profile data
 */
header('Content-Type: application/json');
require_once 'config.php';

// Handle CORS for launcher
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    SELECT t.user_id, t.expires_at, u.username, u.role 
    FROM api_tokens t 
    JOIN kullanicilar u ON t.user_id = u.id 
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
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get profile
        $query = $db->prepare("SELECT id, username, role, bitis_tarihi, profil_color, avatar, avatar_url, bio, discord_id FROM kullanicilar WHERE id = ?");
        $query->execute([$user_id]);
        $userData = $query->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'username' => $userData['username'],
                'role' => $userData['role'],
                'expiry_date' => $userData['bitis_tarihi'],
                'profile_color' => $userData['profil_color'] ?? '#00ffcc',
                'avatar' => $userData['avatar'] ?? '🥷',
                'avatar_url' => $userData['avatar_url'],
                'bio' => $userData['bio'] ?? '',
                'discord_id' => $userData['discord_id'] ?? ''
            ]
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update profile
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            exit;
        }

        $allowedFields = ['bio', 'discord_id', 'profil_color'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = sanitizeInput($input[$field]);
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            exit;
        }

        $params[] = $user_id;
        $sql = "UPDATE kullanicilar SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
