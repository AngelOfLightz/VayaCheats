<?php
// Load config which handles session initialization properly
require_once 'config.php';

// Tüm oturum değişkenlerini hafızadan temizle
$_SESSION = array();

// Eğer oturum çerezleri varsa onları tarayıcıdan sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu sunucu tarafında tamamen yok et
session_destroy();

// Kullanıcıyı jilet gibi giriş kapısına geri fırlat
header("Location: index.php");
exit;
?>
