<?php
// hile_mevcut_mu.php - Vayacheats Canlı Veritabanı Hile Kontrol Sistemi
header('Content-Type: application/json; charset=utf-8');

// Veritabanı Bağlantısı (Varsa kendi baglan.php dosyanı require edebilirsin gadaşım)
$host = "sql213.infinityfree.com"; 
$db_user = "if0_42142730"; 
$db_pass = "28gHFFdTbBPL"; 
$db_name = "if0_42142730_vaya_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Veritabanı bağlantısı çekirdek seviyesinde koptu!']);
    exit;
}

if (isset($_POST['arama_terimi'])) {
    // SQL Injection kalkanı ve arama terimini temizleme
    $arama = mysqli_real_escape_string($conn, mb_strtolower(trim($_POST['arama_terimi']), 'UTF-8'));

    if (empty($arama)) {
        echo json_encode(['mevcut' => false]);
        exit;
    }

    // phpMyAdmin 'hileler' tablosunda arama yapıyoruz
    // LIKE ifadesi sayesinde kullanıcı "Apex oyna" yazsa bile içindeki "apex" kelimesini yakalar!
    $query = "SELECT * FROM hileler WHERE '$arama' LIKE CONCAT('%', aranacak_kelime, '%') OR aranacak_kelime LIKE '%$arama%' LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Veritabanından gelen siber veriyi JSON olarak fırlat
        echo json_encode([
            'mevcut' => true,
            'hile_adi' => $row['hile_adi'],
            'durum' => $row['durum'], // UNDETECTED, DETECTED veya BAKIMDA
            'koruma' => $row['koruma']
        ]);
    } else {
        // Veritabanında eşleşen hiçbir kayıt yoksa
        echo json_encode(['mevcut' => false]);
    }
} else {
    echo json_encode(['mevcut' => false, 'message' => 'Siber ağa yetkisiz veri paketi gönderildi!']);
}

$conn->close();
?>
