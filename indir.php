<?php
// indir.php - VAYACHEATS GÜVENLİ İNDİRME GEÇİDİ
require_once 'config.php';

// Güvenlik Duvarı: Sadece oturum açmış üyeler dosya çekebilsin gadaşım
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if (isset($_GET['hile_id'])) {
    $hile_id = validateId($_GET['hile_id']);

    // Veritabanından hile bilgilerini çekiyoruz (subscription requirement dahil)
    $sorgu = $db->prepare("SELECT hile_adi, dosya_yolu, durum, required_subscription_level FROM hileler WHERE id = ? LIMIT 1");
    $sorgu->execute([$hile_id]);
    $hile = $sorgu->fetch(PDO::FETCH_ASSOC);

    if (!$hile) {
        die(">> [ERR] Product not found.");
    }

    // Check if product status allows download
    if ($hile['durum'] !== 'UNDETECTED') {
        die(">> [ERR] This product is currently not available for download. Status: " . strtoupper($hile['durum']));
    }
    
    // Check subscription level requirement
    $required_level = $hile['required_subscription_level'] ?? 0;
    $user_level = 0;
    
    // Get user's subscription level
    $subCheck = $db->prepare("
        SELECT st.level 
        FROM subscriptions s 
        JOIN subscription_types st ON s.subscription_type_id = st.id 
        WHERE s.user_id = ? AND s.status = 'active' AND (s.end_date IS NULL OR s.end_date > NOW())
        ORDER BY st.level DESC 
        LIMIT 1
    ");
    $subCheck->execute([$_SESSION['user_id']]);
    $subResult = $subCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($subResult) {
        $user_level = $subResult['level'];
    }
    
    // Owner has level 99, always allow
    if (isOwner()) {
        $user_level = 99;
    }
    
    // Check if user has sufficient subscription level
    if ($user_level < $required_level) {
        // Get required subscription type name
        $requiredTypeName = 'FREE';
        if ($required_level > 0) {
            $typeCheck = $db->prepare("SELECT name FROM subscription_types WHERE level = ?");
            $typeCheck->execute([$required_level]);
            $typeResult = $typeCheck->fetch(PDO::FETCH_ASSOC);
            if ($typeResult) {
                $requiredTypeName = $typeResult['name'];
            }
        }
        
        // Return JSON error for modal display
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'insufficient_subscription',
            'message' => "You need {$requiredTypeName} Membership to download this product.",
            'required_level' => $required_level,
            'user_level' => $user_level
        ]);
        exit;
    }

    if (!empty($hile['dosya_yolu']) && file_exists($hile['dosya_yolu'])) {
        $gercek_dosya = $hile['dosya_yolu'];
        $dosya_adi = basename($gercek_dosya);

        // Log download to history
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logDownload = $db->prepare("INSERT INTO download_history (user_id, product_id, ip_address) VALUES (?, ?, ?)");
        $logDownload->execute([$_SESSION['user_id'], $hile_id, $ip_address]);

        // ⚡ SUNUCU DUVARINI YIKAN KRİTİK SİBER HEADER AYARLARI ⚡
        // Dosyayı sunucuya çaktırmadan binary veri paketi olarak maskeliyoruz gadaşım
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); 
        header('Content-Disposition: attachment; filename="' . $dosya_adi . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($gercek_dosya));
        
        // Önbelleği temizle ve siber akışı başlat
        flush();
        readfile($gercek_dosya);
        exit;
    } else {
        die(">> [ERR] Hile dosyası bulut sunucusunda fiziksel olarak mevcut değil gadaşım!");
    }
} else {
    die(">> [ERR] Geçersiz siber istek!");
}
?>
