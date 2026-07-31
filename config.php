<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 📌 TAMAMEN SENİN CANLI SUNUCU KOORDİNATLARIN KİLİTLENDİ
$db_host = "sql213.infinityfree.com"; 
$db_user = "if0_42142730"; 
$db_pass = "28gHFFdTbBPL"; 
$db_name = "if0_42142730_vaya_db";  // ⚠️ BURAYA DİKKAT: if0_... ile başlayan tam adı yazdık!

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("// CODE_RED: Core database bridge offline.");
}
?>
