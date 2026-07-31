<?php
session_start();
require_once 'config.php';

// Klasör yoksa otomatik oluşturma kontrolü
$target_dir = "uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true); // Klasörü oluşturur
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    
    $file_name = $_SESSION['user_id'] . "_" . time() . ".jpg";
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
        $sql = "UPDATE kullanicilar SET avatar_url = ?, avatar = NULL WHERE id = ?"; 
        $stmt = $db->prepare($sql);
        $stmt->execute([$target_file, $_SESSION['user_id']]);
        
        $_SESSION['user_avatar_url'] = $target_file;
        $_SESSION['user_avatar'] = null;

        header("Location: user.php?tab=tab-profile");
        exit;
    } else {
        echo "Dosya yüklenirken bir siber arıza oluştu.";
    }
}
?>