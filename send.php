<?php
// 1. SİLİNDİRİK BAĞLANTI AYARLARI
$db_host = "sql213.infinityfree.com"; 
$db_user = "if0_42142730"; 
$db_pass = "28gHFFdTbBPL"; 
$db_name = "if0_42142730_vaya_db"; 

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("// CRITICAL_ERROR: Database connection failed.");
}

// 2. FORM VERİLERİNİ GÜVENLİCE YAKALAMA VE KAYDETME
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // HTML formundaki 'name' etiketleriyle birebir eşitlendi
    $kod_adi = strip_tags(trim($_POST["kod_adi"]));
    $konu    = strip_tags(trim($_POST["konu"]));
    $ileti   = strip_tags(trim($_POST["ileti"]));

    if (!empty($kod_adi) && !empty($konu) && !empty($ileti)) {
        try {
            // SQL haritası ve yürütme motoru
            $query = $db->prepare("INSERT INTO mesajlar (kod_adi, konu, ileti) VALUES (?, ?, ?)");
            $query->execute([$kod_adi, $konu, $ileti]);
            
            // Başarılı Siber Onay Ekranı
            echo "<body style='background:#020306; color:#00ffcc; font-family:monospace; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;'>";
            echo "<div style='border:1px dashed #00ffcc; padding:30px; border-radius:10px; text-align:center;'>";
            echo "// VERİ PAKETİ BAŞARIYLA FIRLATILDI!<br><br>";
            echo "<span style='color:#94a3b8;'>Ana merkeze dönülüyor...</span>";
            echo "</div>";
            echo "<script>setTimeout(function(){ window.location.href='index.html'; }, 2000);</script>";
            echo "</body>";
            
        } catch (PDOException $e) {
            echo "// ERROR: Mesaj veritabanına yazılamadı. Tablo adını kontrol edin.";
        }
    } else {
        echo "// WARNING: Eksik veri paketi algılandı.";
    }
} else {
    header("Location: index.html");
    exit;
}
?>
