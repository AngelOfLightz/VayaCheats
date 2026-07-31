<?php
// indir.php - VAYACHEATS GÜVENLİ İNDİRME GEÇİDİ
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Güvenlik Duvarı: Sadece oturum açmış üyeler dosya çekebilsin gadaşım
if (!isset($_SESSION['role'])) {
    die(">> [ERR] Erişim izniniz yok. Siber ağa giriş yapın.");
}

if (isset($_GET['hile_id'])) {
    $hile_id = (int)$_GET['hile_id'];

    // Veritabanından hilenin dosya yolunu çekiyoruz
    $sorgu = $db->prepare("SELECT hile_adi, dosya_yolu FROM hileler WHERE id = ? LIMIT 1");
    $sorgu->execute([$hile_id]);
    $hile = $sorgu->fetch(PDO::FETCH_ASSOC);

    if ($hile && !empty($hile['dosya_yolu']) && file_exists($hile['dosya_yolu'])) {
        $gercek_dosya = $hile['dosya_yolu'];
        $dosya_adi = basename($gercek_dosya);

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
