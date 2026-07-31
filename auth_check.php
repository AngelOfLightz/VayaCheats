<?php
function yetkiKontrol($gereken_seviyeler) {
    global $db; // Burası kesinlikle $db olmalı, çünkü projenin kalanı $db kullanıyor.

    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    $id = $_SESSION['user_id'];
    
    // Bağlantı değişkeni $db olduğu için burada da $db kullanıyoruz
    $sorgu = $db->prepare("SELECT role FROM kullanicilar WHERE id = ?");
    $sorgu->execute([$id]);
    $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

    // Admin her zaman girebilir
    if ($kullanici['role'] === 'admin') {
        return true; 
    }

    // Değilse rolleri kontrol et
    if (!in_array($kullanici['role'], $gereken_seviyeler)) {
        header("Location: index.php?hata=yetkisiz");
        exit();
    }
}
?>