<?php
require_once 'config.php';

header('Content-Type: application/json');

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

// Check user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Klasör yoksa otomatik oluşturma kontrolü
$target_dir = "uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true); // Klasörü oluşturur
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    
    // Validate image upload
    $validation = validateImageUpload($_FILES['avatar']);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'message' => $validation['error']]);
        exit;
    }
    
    // Generate safe filename
    $extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $file_name = $_SESSION['user_id'] . "_" . time() . "." . $extension;
    $target_file = $target_dir . sanitizeFilename($file_name);

    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
        $sql = "UPDATE kullanicilar SET avatar_url = ?, avatar = NULL WHERE id = ?"; 
        $stmt = $db->prepare($sql);
        $stmt->execute([$target_file, $_SESSION['user_id']]);
        
        $_SESSION['user_avatar_url'] = $target_file;
        $_SESSION['user_avatar'] = null;

        echo json_encode(['success' => true, 'message' => 'Avatar uploaded successfully', 'avatar_url' => $target_file]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>