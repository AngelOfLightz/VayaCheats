<?php
// hile_mevcut_mu.php - Vayacheats Canlı Veritabanı Hile Kontrol Sistemi
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if (isset($_POST['arama_terimi'])) {
    // Sanitize input
    $arama = sanitizeInput($_POST['arama_terimi']);
    $arama = mb_strtolower($arama, 'UTF-8');

    if (empty($arama)) {
        echo json_encode(['mevcut' => false]);
        exit;
    }

    // Use PDO prepared statement to prevent SQL injection
    $query = "SELECT * FROM hileler WHERE ? LIKE CONCAT('%', aranacak_kelime, '%') OR aranacak_kelime LIKE ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$arama, '%' . $arama . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Veritabanından gelen siber veriyi JSON olarak fırlat
        echo json_encode([
            'mevcut' => true,
            'hile_adi' => $result['hile_adi'],
            'durum' => $result['durum'], // UNDETECTED, DETECTED veya BAKIMDA
            'koruma' => $result['koruma']
        ]);
    } else {
        // Veritabanında eşleşen hiçbir kayıt yoksa
        echo json_encode(['mevcut' => false]);
    }
} else {
    echo json_encode(['mevcut' => false, 'message' => 'Siber ağa yetkisiz veri paketi gönderildi!']);
}
?>
