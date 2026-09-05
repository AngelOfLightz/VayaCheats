<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config.php';
    
    // Check if test_user exists
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE username = ?");
    $stmt->execute(['test_user']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'test_user not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
