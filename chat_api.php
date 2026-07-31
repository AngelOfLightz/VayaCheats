<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Yetkisiz erişim.']);
    exit;
}

$uid = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// 1. MESAJLARI VERİTABANINDAN ÇEKME MOTORU
if ($action === 'fetch') {
    $query = $db->query("SELECT * FROM panel_sohbet ORDER BY id DESC LIMIT 40");
    $messages = $query->fetchAll(PDO::FETCH_ASSOC);
    $messages = array_reverse($messages);
    
    foreach ($messages as $msg) {
        // Veritabanındaki tarihten sadece Saat:Dakika kısmını alıyoruz
        $time_format = date('H:i', strtotime($msg['tarih']));
        
        // Kullanıcının rolüne göre siber etiket enjeksiyonu
        $user_tag = htmlspecialchars($msg['username']);
        if ($msg['username'] === 'VayaX') { 
            // Admin ismi senin görseldeki gibi kırmızı parlasın
            $user_tag = '<span style="color:#f87171; font-weight:800;">✦ [ADMIN] VayaX</span>';
        } else {
            // Kullanıcıların isimleri dinamik profil neon renginde parlasın
            $user_tag = '<span style="color:var(--user-neon); font-weight:700;">'.$user_tag.'</span>';
        }

        echo '<div class="chat-msg-row" style="margin-bottom:8px; font-family:monospace; font-size:11px;">';
        echo '  <span style="color:#64748b; margin-right:6px;">['.$time_format.']</span> ';
        echo '  ' . $user_tag . ': ';
        echo '  <span style="color:#cbd5e1; margin-left:4px; word-break:break-all;">'.htmlspecialchars($msg['mesaj']).'</span>';
        echo '</div>';
    }
    exit;
}

// 2. MESAJI VERİTABANINA YAZMA MOTORU
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg_txt = trim($_POST['message'] ?? '');
    
    if (!empty($msg_txt)) {
        $userQuery = $db->prepare("SELECT username FROM kullanicilar WHERE id = ?");
        $userQuery->execute([$uid]);
        $uData = $userQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($uData) {
            $insert = $db->prepare("INSERT INTO panel_sohbet (user_id, username, mesaj) VALUES (?, ?, ?)");
            $insert->execute([$uid, $uData['username'], $msg_txt]);
            echo json_encode(['status' => 'success']);
        }
    }
    exit;
}
?>
