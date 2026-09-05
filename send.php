<?php
require_once 'config.php';

header('Content-Type: application/json');

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

// 2. FORM VERİLERİNİ GÜVENLİCE YAKALAMA VE KAYDETME
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // HTML formundaki 'name' etiketleriyle birebir eşitlendi
    $kod_adi = sanitizeInput($_POST["kod_adi"]);
    $konu    = sanitizeInput($_POST["konu"]);
    $ileti   = sanitizeInput($_POST["ileti"]);

    if (!empty($kod_adi) && !empty($konu) && !empty($ileti)) {
        try {
            // SQL haritası ve yürütme motoru
            $query = $db->prepare("INSERT INTO mesajlar (kod_adi, konu, ileti) VALUES (?, ?, ?)");
            $query->execute([$kod_adi, $konu, $ileti]);
            
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error saving message']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
