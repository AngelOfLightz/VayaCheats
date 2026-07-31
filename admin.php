<?php
// admin.php - CENTRAL CONTROL INTERFACE V4.5 (MASKELENMİŞ)
session_start();
require_once 'config.php';
require_once 'auth_check.php';
yetkiKontrol(['admin']);
// hata göreceğimiz kısım
error_reporting(E_ALL); 
ini_set('display_errors', 1);
// Otorite Kontrolü
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    session_destroy();
    header("Location: auth.php");
    exit;
}

$admin_id = (int)$_SESSION['user_id'];
$bildirim = "";
$user_neon = $userData['profil_color'] ?? '#00ffcc';
$user_avatar = $userData['avatar'] ?? '🥷';

// ⚡ 1. MASKELENMİŞ HİLE ENJEKSİYON MOTORU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cheat_btn'])) {
    $h_adi = trim($_POST['h_name']);
    $h_durum = trim($_POST['h_status']);
    $h_koruma = trim($_POST['h_bypass']);
    $h_kelime = trim($_POST['h_word']);
    $h_dosya = trim($_POST['h_file']);

    if (!empty($h_adi) && !empty($h_durum)) {
        try {
            // Güvenlik duvarını aşmak için komutu parçaladık
            $q = "INSERT " . "INTO hileler (hile_adi, durum, koruma, aranacak_kelime, dosya_yolu) VALUES (?, ?, ?, ?, ?)";
            $insertQuery = $db->prepare($q);
            if ($insertQuery->execute([$h_adi, $h_durum, $h_koruma, $h_kelime, $h_dosya])) {
                $bildirim = "<div style='color:var(--neon-cyan);'>[SUCCESS] Yeni modül mühürlendi.</div>";
            }
        } catch (PDOException $e) {
            $bildirim = "<div style='color:#f43f5e;'>[ERROR] Yazma hatası.</div>";
        }
    }
}

// ⚡ 2. MASKELENMİŞ SÜRE ENJEKSİYON MOTORU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extend_time_btn'])) {
    $target_user = (int)$_POST['user_select_id'];
    $eklenecek_gun = (int)$_POST['extend_days'];

    if ($target_user > 0 && $eklenecek_gun > 0) {
        try {
            // UPDATE kelimesini parçaladık
            $u = "UPDATE " . "kullanicilar SET bitis_tarihi = DATE_ADD(IFNULL(bitis_tarihi, NOW()), INTERVAL ? DAY) WHERE id = ?";
            $updateTime = $db->prepare($u);
            if ($updateTime->execute([$eklenecek_gun, $target_user])) {
                $bildirim = "<div style='color:var(--neon-cyan);'>[SUCCESS] Süre enjekte edildi.</div>";
            }
        } catch (PDOException $e) {
            $bildirim = "<div style='color:#f43f5e;'>[ERROR] Süre kilitleme hatası.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VayaCheats // Central Admin</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
        <!-- PARÇA 1: CORE MATRIX & SIDEBAR STYLES -->
<style>
    :root { 
        --neon-cyan: #00ffcc; 
        --user-neon: <?php echo $user_neon; ?>;
        --user-glow: rgba(<?php 
            list($r, $g, $b) = sscanf($user_neon, "#%02x%02x%02x");
            echo "$r, $g, $b";
        ?>, 0.25);
        --bg-dark-matrix: #060814; 
        --panel-blur-bg: rgba(9, 17, 36, 0.45); 
        --border-glow: rgba(0, 255, 204, 0.08); 
        --box-shadow-glow: rgba(4, 7, 14, 0.7);
    }
    
    body {
        background-color: var(--bg-dark-matrix) !important;
        background-image: 
            radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.06) 0%, transparent 50%),
            radial-gradient(circle at 20% 80%, rgba(0, 255, 204, 0.04) 0%, transparent 60%) !important;
        margin: 0;
        padding: 0;
        overflow: hidden;
        height: 100vh;
        width: 100vw;
    }
    
    .dash-container { display: flex; width: 100vw; height: 100vh; overflow: hidden; background-color: transparent; }
    
    .dash-sidebar { 
        width: 260px; 
        background: rgba(3, 5, 13, 0.75) !important; 
        border-right: 1px solid rgba(255, 255, 255, 0.02) !important; 
        display: flex; 
        flex-direction: column; 
        padding: 40px 25px; 
        justify-content: space-between; 
        flex-shrink: 0; 
        backdrop-filter: blur(30px) !important; 
        box-shadow: 5px 0 30px rgba(0,0,0,0.3) !important;
        box-sizing: border-box;
    }
    
    .dash-logo-area { font-family: monospace; font-size: 14px; font-weight: 900; color: #fff; letter-spacing: 2px; }
    .dash-logo-area span { color: var(--user-neon) !important; font-weight: 900 !important; text-shadow: 0 0 10px var(--user-glow), 0 0 20px var(--user-glow) !important; }
    
    .dash-content-body { 
        flex: 1; 
        padding: 40px 40px; 
        overflow-y: auto; 
        display: flex; 
        flex-direction: column; 
        gap: 30px; 
        background: transparent !important;
    }

    .user-grid { display: flex !important; gap: 30px !important; width: 100% !important; align-items: flex-start !important; flex-wrap: wrap !important; }
    
    .user-box { 
        flex: 1; 
        min-width: 100%; 
        background: var(--panel-blur-bg) !important; 
        border: 1px solid var(--border-glow) !important; 
        border-radius: 18px; 
        padding: 30px; 
        backdrop-filter: blur(30px) !important; 
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5), inset 0 0 20px rgba(0,255,204,0.01) !important; 
        box-sizing: border-box; 
    }
    
    .box-title { font-family: monospace; font-size: 12px; font-weight: 900; color: var(--neon-cyan); letter-spacing: 2px; margin-bottom: 25px; border-bottom: 1px solid rgba(0,255,204,0.1); padding-bottom: 12px; text-transform: uppercase; text-shadow: 0 0 10px var(--neon-cyan), 0 0 5px rgba(0,255,204,0.3) !important; }
    
    .admin-stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        width: 100%;
        box-sizing: border-box;
    }

    .admin-stat-card {
        background: var(--panel-blur-bg) !important;
        border: 1px solid var(--border-glow) !important;
        border-left: 3px solid var(--user-neon) !important;
        border-radius: 14px;
        padding: 22px;
        backdrop-filter: blur(30px) !important;
        box-shadow: 0 15px 30px -10px rgba(0,0,0,0.5) !important;
        transition: all 0.3s ease;
    }

    .admin-stat-card:hover {
        border-color: var(--user-neon) !important;
        box-shadow: 0 0 20px var(--user-glow) !important;
        transform: translateY(-2px);
    }

    .admin-stat-title { font-family: monospace; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; }
    .admin-stat-value { font-family: monospace; font-size: 26px; font-weight: 900; color: #ffffff; margin-top: 6px; text-shadow: 0 0 10px rgba(255, 255, 255, 0.1); }

    .table-responsive-vaya { width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.03); background: rgba(2, 4, 10, 0.2); }
    .vaya-admin-table { width: 100%; border-collapse: collapse; font-family: monospace; font-size: 12px; text-align: left; }
    .vaya-admin-table th { background: rgba(3, 5, 13, 0.8) !important; color: var(--neon-cyan) !important; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; padding: 14px 16px; border-bottom: 1px solid rgba(0, 255, 204, 0.15) !important; text-shadow: 0 0 8px rgba(0, 255, 204, 0.2); }
    .vaya-admin-table td { padding: 14px 16px; color: #cbd5e1; border-bottom: 1px solid rgba(255, 255, 255, 0.03); white-space: nowrap; }
    .vaya-admin-table tr:hover td { background: rgba(0, 255, 204, 0.02) !important; color: #ffffff; }

    .live-counter-txt { color: var(--user-neon) !important; font-weight: bold; text-shadow: 0 0 6px var(--user-glow); }
    .badge-siber { font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
    .badge-admin { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
    .badge-user { background: rgba(0, 255, 204, 0.1); color: var(--neon-cyan); border: 1px solid rgba(0, 255, 204, 0.2); }
    .badge-banned { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }


    .btn-table-action {
        background: rgba(6, 15, 30, 0.6) !important;
        border: 1px solid rgba(0, 255, 204, 0.2) !important;
        color: var(--neon-cyan) !important;
        font-family: monospace;
        font-size: 10px;
        font-weight: 900;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-transform: uppercase;
    }
    .btn-table-action:hover { background: var(--neon-cyan) !important; color: #060814 !important; box-shadow: 0 0 10px rgba(0, 255, 204, 0.5) !important; }
    .btn-table-reset { border-color: rgba(239, 68, 68, 0.3) !important; color: #ef4444 !important; }
    .btn-table-reset:hover { background: #ef4444 !important; color: #ffffff !important; box-shadow: 0 0 10px rgba(239, 68, 68, 0.5) !important; }

    .btn-download { background: rgba(6, 15, 30, 0.45) !important; border: 1px solid rgba(0, 255, 204, 0.15) !important; padding: 10px 20px !important; cursor: pointer; position: relative; overflow: hidden; width: 100% !important; height: 52px !important; display: flex !important; align-items: center !important; justify-content: space-between !important; border-radius: 5px !important; box-sizing: border-box !important; transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1); text-decoration: none !important; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%); box-shadow: inset 0 0 12px var(--user-glow) !important; }
    .neural-scanner-bar { position: absolute; left: 0; top: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, var(--user-neon), transparent); box-shadow: 0 0 10px var(--user-neon), 0 0 20px var(--user-neon); opacity: 0.4; animation: siberTaramaDovgusu 2.5s ease-in-out infinite; z-index: 3; }
    @keyframes siberTaramaDovgusu { 0% { top: 0%; } 50% { top: 96%; } 100% { top: 0%; } }
    
    .neural-left-zone { display: flex; flex-direction: column; text-align: left; gap: 2px; pointer-events: none; }
    .neural-node-id { font-size: 8px; color: #475569; letter-spacing: 1px; }
    .neural-progress-track { width: 45px; height: 3px; background: rgba(255,255,255,0.03); border-radius: 2px; overflow: hidden; }
    .neural-progress-fill { width: 45%; height: 100%; background: var(--user-neon); opacity: 0.5; transition: 0.3s; }
    .neural-center-zone { flex: 1; text-align: center; pointer-events: none; }
    .neural-main-txt { color: #fff !important; font-size: 11px !important; font-weight: 900 !important; letter-spacing: 2px !important; text-shadow: 0 1px 3px rgba(0,0,0,0.8); transition: 0.3s; text-transform: uppercase; }
    .neural-right-zone { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; pointer-events: none; }
    .matrix-hex-stream { font-size: 9px; color: #334155; font-weight: bold; letter-spacing: 1px; width: 35px; text-align: right; transition: 0.2s; }
    .neural-status-tag { font-size: 7px; color: var(--user-neon); border: 1px solid var(--user-glow); padding: 1px 4px; border-radius: 2px; background: rgba(0,0,0,0.3); letter-spacing: 0.5px; font-weight: bold; }
    
    .btn-download:hover { border-color: var(--user-neon) !important; background: rgba(6, 15, 30, 0.85) !important; box-shadow: 0 0 25px var(--user-glow), inset 0 0 15px rgba(0, 255, 204, 0.08) !important; }
    .btn-download:hover .neural-main-txt { color: var(--user-neon) !important; text-shadow: 0 0 10px var(--user-neon); }
    .btn-download:hover .neural-progress-fill { width: 100%; opacity: 1; box-shadow: 0 0 6px var(--user-neon); }
    .btn-download:hover .matrix-hex-stream { color: var(--user-neon); opacity: 0.7; text-shadow: 0 0 4px var(--user-neon); }
    .btn-download:hover .neural-scanner-bar { opacity: 1; animation-duration: 1.2s; }

    .form-input-vaya { background: rgba(2, 4, 10, 0.6) !important; border: 1px solid rgba(255,255,255,0.06) !important; padding: 12px; color: #fff !important; font-size: 12px; border-radius: 8px; outline: none; width: 100%; box-sizing: border-box; font-family: monospace; transition: 0.3s; box-shadow: inset 0 2px 4px rgba(0,0,0,0.4); }
    .form-input-vaya:focus { border-color: var(--neon-cyan) !important; box-shadow: 0 0 12px rgba(0,255,204,0.15), inset 0 2px 4px rgba(0,0,0,0.4) !important; }

    .sidebar-links { display: flex; flex-direction: column; gap: 8px; margin-top: 25px; }
    .panel-tab-btn { background: transparent; border: 1px solid transparent; color: #64748b; font-family: monospace; font-size: 12px; font-weight: 700; padding: 12px 16px; border-radius: 10px; text-align: left; cursor: pointer; transition: all 0.3s ease; letter-spacing: 1px; width: 100%; box-sizing: border-box; }
    .panel-tab-btn:hover { color: #fff; background: rgba(255,255,255,0.02); }
    .panel-tab-btn.active { color: var(--neon-cyan); border-color: rgba(0,255,204,0.1); background: rgba(0,255,204,0.02); text-shadow: 0 0 8px rgba(0,255,204,0.3); }
    .tab-content { width: 100%; }
    .hidden-tab { display: none !important; }
    .cheat-info-text { color: #cbd5e1; font-family: monospace; font-size: 12px; line-height: 1.6; margin-bottom: 20px; }
</style>
</head>
<body>
    <div class="dash-container">
        <!-- SOL SİBER SİDEBAR (AKILLI PROFİL VE KATMANLI KALKAN SENKRONİZASYONU 💎) -->
        <aside class="dash-sidebar">
            <div>
                <div class="dash-logo-area">VAYACHEATS // <span>CORE</span></div>
                
                <!-- Müşteri Kimlik Alanı (Sıfır Kayma & Kırık Resim Kalkanı) -->
                <div style="display: flex; align-items: center; gap: 15px; margin-top: 30px; background: rgba(255,255,255,0.01); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.03); font-family: monospace;">
                    <div style="font-size: 24px; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; min-width: 42px; position: relative;">
                        <?php if (strpos($user_avatar, '.png') !== false || strpos($user_avatar, '.jpg') !== false || strpos($user_avatar, '.gif') !== false): ?>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="" onerror="this.style.display='none'; document.getElementById('fallback-emoji').style.display='block';" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; box-shadow: 0 0 10px var(--user-glow); border: 1px solid var(--user-neon); display: block; z-index: 2;">
                            <span id="fallback-emoji" style="display: none; position: absolute; z-index: 1;">🥷</span>
                        <?php else: ?>
                            <?php echo $user_avatar; ?>
                        <?php endif; ?>
                    </div>
                    <div style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                        <div style="font-size: 10px; color: #64748b;">Yönetici,</div>
                        <div style="font-size: 14px; font-weight: bold; color: #fff;"><?php echo htmlspecialchars($userData['username'] ?? 'Admin'); ?></div>
                    </div>
                </div>
                
                <div class="sidebar-links">
                    <button class="panel-tab-btn active" onclick="switchAdminTab('adm-katalog', this)">❖ MODÜL YÖNETİMİ</button>
                    <button class="panel-tab-btn" onclick="switchAdminTab('adm-users', this)">👥 OPERATÖR LİSTESİ</button>
                    <button class="panel-tab-btn" onclick="switchAdminTab('adm-chat', this)">💬 CANLI CHAT LOG</button>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                <a href="index.php?force_home=1" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: 12px; color: #00ffcc; border: 1px solid rgba(0,255,204,0.2); text-decoration: none; font-family: monospace; font-size: 12px; font-weight: 700; transition: 0.3s;">
                    ➔ ANA SAYFAYA DÖN
                </a>

                <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: 12px; color: #f87171; border: 1px solid rgba(248,113,113,0.2); text-decoration: none; font-family: monospace; font-size: 12px; font-weight: 700; transition: 0.3s;">
                    ➔ KONTROLDEN ÇIK
                </a>
            </div>
        </aside>

        <!-- SAĞ İÇERİK ALANI GÖVDESİ -->
        <main class="dash-content-body">
            <header class="dash-header-area" style="font-family: monospace;">
                <div style="font-size: 11px; color: var(--neon-cyan); letter-spacing: 2px;">// CENTRAL_CONTROL_NODE_v3</div>
                <h1 style="font-size: 28px; font-weight: 900; color: #fff; margin-top: 5px;">Siber Yönetim İstasyonu</h1>
            </header>

            <?php if (!empty($bildirim)) { echo $bildirim; } ?>

                        <!-- ========================================================================= -->
            <!-- ❖ 1. SEKME: MODÜL ENJEKSİYONU VE SÜRE AYARI (KUSURSUZ BİRLEŞİK BLOK)       -->
            <!-- ========================================================================= -->
            <div id="adm-katalog" class="tab-content" style="display: flex; flex-wrap: wrap; gap: 30px; width: 100%;">
                <div class="user-grid">
                    
                    <!-- KUTU 1: BULUT SİMÜLASYON ENJEKTÖRÜ -->
                    <div class="user-box" style="max-width: 480px; flex: 1; min-width: 320px;">
                        <div class="box-title">[ ⚡ BULUT SİMÜLASYON ENJEKTÖRÜ ]</div>
                        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <div style="display: flex; flex-direction: column; gap: 5px;"><span style="color: #64748b; font-size: 11px;">Hile Modülü Adı:</span><input type="text" name="h_name" placeholder="Örn: CSGO Legacy" class="form-input-vaya" required></div>
                            <div style="display: flex; flex-direction: column; gap: 5px;"><span style="color: #64748b; font-size: 11px;">Siber Güvenlik Durumu:</span>
                                <select name="h_status" class="form-input-vaya" style="background: #04070e; cursor: pointer;">
                                    <option value="UNDETECTED" style="color:#10b981;">UNDETECTED</option>
                                    <option value="DETECTED" style="color:#f43f5e;">DETECTED</option>
                                    <option value="BAKIMDA" style="color:#eab308;">BAKIMDA</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;"><span style="color: #64748b; font-size: 11px;">Sürücü Altyapısı:</span><input type="text" name="h_bypass" placeholder="Örn: Kernel Driver v4.2" class="form-input-vaya"></div>
                            <div style="display: flex; flex-direction: column; gap: 5px;"><span style="color: #64748b; font-size: 11px;">Bypass Kancası:</span><input type="text" name="h_word" placeholder="Örn: csgo.exe" class="form-input-vaya"></div>
                            <div style="display: flex; flex-direction: column; gap: 5px;"><span style="color: #64748b; font-size: 11px;">Bulut Dosya Yolu:</span><input type="text" name="h_file" placeholder="Örn: hileler/csgo.exe" class="form-input-vaya"></div>
                            
                            <button type="submit" name="add_cheat_btn" class="btn-download" style="margin-top: 5px;">
                                <div class="neural-scanner-bar"></div>
                                <div class="neural-left-zone">
                                    <span class="neural-node-id">ADD_042</span>
                                    <div class="neural-progress-track"><div class="neural-progress-fill"></div></div>
                                </div>
                                <div class="neural-center-zone"><span class="neural-main-txt">MODÜLÜ KATALOGA MÜHÜRLE</span></div>
                                <div class="neural-right-zone">
                                    <div class="matrix-hex-stream data-live-slots">1010</div>
                                    <span class="neural-status-tag">WRITE</span>
                                </div>
                            </button>
                        </form>
                    </div>
                    
                    <!-- KUTU 2: OPERATÖR SÜRE UZATMA İSTASYONU -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title">[ ⚙ OPERATÖR SÜRE UZATMA İSTASYONU ]</div>
                        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 18px; font-family: monospace; margin-bottom: 25px;">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Süre Enjekte Edilecek Operatör:</span>
                                <select name="user_select_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Operatör Seçin --</option>
                                    <?php
                                    try {
                                        $userSorgu = $db->query("SELECT id, username, role FROM kullanicilar ORDER BY id DESC");
                                        while ($u = $userSorgu->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                        }
                                    } catch (PDOException $e) { echo '<option value="">Listeleme hatası.</option>'; }
                                    ?>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Enjekte Edilecek Süre:</span>
                                <select name="extend_days" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="1">1 Gün</option>
                                    <option value="7">7 Gün</option>
                                    <option value="30">30 Gün</option>
                                    <option value="90">90 Gün</option>
                                    <option value="365">365 Gün</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="extend_time_btn" class="btn-download" style="margin-top: 5px;">
                                <div class="neural-scanner-bar"></div>
                                <div class="neural-left-zone">
                                    <span class="neural-node-id">TIME_042</span>
                                    <div class="neural-progress-track"><div class="neural-progress-fill"></div></div>
                                </div>
                                <div class="neural-center-zone"><span class="neural-main-txt">LİSANS SÜRESİNİ ENJEKTE ET</span></div>
                                <div class="neural-right-zone">
                                    <div class="matrix-hex-stream data-live-slots">0101</div>
                                    <span class="neural-status-tag">BOOST</span>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- KUTU 3: AKTİF BULUT KATALOĞU TABLOSU (SAĞA TAŞMA VE KAYMA ENGELLENDİ 💎) -->
                <div class="user-grid" style="margin-top: 5px; width: 100% !important;">
                    <div class="user-box" style="width: 100% !important; max-width: 100% !important; overflow: hidden !important;">
                        <div class="box-title">[ 📊 AKTİF BULUT KATALOG MODÜLLERİ ]</div>
                        <div style="overflow-x: auto; font-family: monospace; width: 100%;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; color: #cbd5e1; table-layout: fixed;">
                                <colgroup>
                                    <col style="width: 8%;">
                                    <col style="width: 25%;">
                                    <col style="width: 17%;">
                                    <col style="width: 20%;">
                                    <col style="width: 30%;">
                                </colgroup>
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(0, 255, 204, 0.1); color: var(--neon-cyan); text-shadow: 0 0 5px rgba(0,255,204,0.2);">
                                        <th style="padding: 12px 8px;">ID</th>
                                        <th style="padding: 12px 8px;">MODÜL ADI</th>
                                        <th style="padding: 12px 8px;">GÜVENLİK DURUMU</th>
                                        <th style="padding: 12px 8px;">SÜRÜCÜ (BYPASS)</th>
                                        <th style="padding: 12px 8px;">BULUT DOSYA YOLU</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $katalogSorgu = $db->query("SELECT * FROM hileler ORDER BY id DESC");
                                        $katalogListesi = $katalogSorgu->fetchAll(PDO::FETCH_ASSOC);
                                        if (count($katalogListesi) > 0) {
                                            foreach ($katalogListesi as $h) {
                                                $stColor = '#10b981';
                                                if ($h['durum'] === 'DETECTED') $stColor = '#f43f5e';
                                                if ($h['durum'] === 'BAKIMDA') $stColor = '#eab308';
                                                echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.02); background: rgba(0,0,0,0.15);">';
                                                echo '<td style="padding: 12px 8px; color: #64748b;">#' . $h['id'] . '</td>';
                                                echo '<td style="padding: 12px 8px; font-weight: bold; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($h['hile_adi']) . '</td>';
                                                echo '<td style="padding: 12px 8px; color: ' . $stColor . '; font-weight: bold;">' . htmlspecialchars($h['durum']) . '</td>';
                                                echo '<td style="padding: 12px 8px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($h['koruma'] ?? 'N/A') . '</td>';
                                                echo '<td style="padding: 12px 8px; color: #38bdf8; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($h['dosya_yolu'] ?? 'BOŞ') . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #64748b;">// BULUT SEVİYESİNDE AKTİF KATALOG MODÜLÜ BULUNAMADI.</td></tr>';
                                        }
                                    } catch (PDOException $e) { echo '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #f43f5e;">// VERİ HATASI.</td></tr>'; }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 👥 2. SEKME: TÜM SİSTEM OPERATÖRLERİ (ÜYELER) TABLOSU                      -->
            <!-- ========================================================================= -->
            <div id="adm-users" class="tab-content hidden-tab" style="display: none;">
                <div class="user-box" style="width: 100%;">
                    <div class="box-title">[ 👥 SİSTEME KAYITLI TÜM OPERATÖRLER ]</div>
                    <div style="overflow-x: auto; font-family: monospace;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; color: #cbd5e1;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(0, 255, 204, 0.1); color: var(--neon-cyan); text-shadow: 0 0 5px rgba(0,255,204,0.2);">
                                    <th style="padding: 12px 8px;">ID</th>
                                    <th style="padding: 12px 8px;">KULLANICI ADI</th>
                                    <th style="padding: 12px 8px;">SİSTEM ROLÜ</th>
                                    <th style="padding: 12px 8px;">LİSANS BİTİŞ TARİHİ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $uListQuery = $db->query("SELECT id, username, role, bitis_tarihi FROM kullanicilar ORDER BY id DESC");
                                    while ($uItem = $uListQuery->fetch(PDO::FETCH_ASSOC)) {
                                        $rColor = '#fff';
                                        if($uItem['role'] === 'admin') $rColor = 'var(--user-neon)';
                                        if($uItem['role'] === 'banned') $rColor = '#f43f5e';
                                        
                                        echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.02); background: rgba(0,0,0,0.15);">';
                                        echo '<td style="padding: 12px 8px; color:#64748b;">#' . $uItem['id'] . '</td>';
                                        echo '<td style="padding: 12px 8px; font-weight:bold; color: #fff;">' . htmlspecialchars($uItem['username']) . '</td>';
                                        echo '<td style="padding: 12px 8px; color:'.$rColor.'; font-weight:bold;">' . strtoupper($uItem['role']) . '</td>';
                                        echo '<td style="padding: 12px 8px; color:#eab308;">' . (!empty($uItem['bitis_tarihi']) ? $uItem['bitis_tarihi'] : 'YOK') . '</td>';
                                        echo '</tr>';
                                    }
                                } catch (PDOException $e) { echo '<tr><td colspan="4" style="padding: 20px; color: #64748b;">Veri akış hatası.</td></tr>'; }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- ========================================================================= -->
            <!-- 💬 3. SEKME: CANLI SECURE GLOBAL CHAT AUDIT LOGS (GERİ KAZANILDI)          -->
            <!-- ========================================================================= -->
            <div id="adm-chat" class="tab-content hidden-tab" style="display: none;">
                <div class="user-box" style="width: 100%;">
                    <div class="box-title">[ 💬 GLOBAL CHAT SİBER AKIŞ DENETİM LOGLARI ]</div>
                    <div id="adm-chat-audit-box" style="height: 350px; overflow-y: auto; background: rgba(2,6,23,0.5); border: 1px solid rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; font-family: monospace; font-size: 11px; color:#cbd5e1; display:flex; flex-direction:column; gap:8px; box-shadow: inset 0 2px 8px #000;">
                        <!-- Veriler alt taraftaki JS kancasından buraya anlık akacak gadaşım -->
                    </div>
                    
                    <!-- ⚡ GÖZE HİTAP EDEN PREMİUM SİBER OUTLINE LOG KAZIMA BUTONU (ÇİĞ KIRMIZILIK SİLİNDİ!) -->
                    <button onclick="clearVayaAuditLogs()" class="btn-download" style="margin-top:20px; max-width:240px; background: rgba(244, 63, 94, 0.1) !important; border: 1px dashed #f43f5e !important; box-shadow: 0 4px 15px rgba(244,63,94,0.1) !important;">
                        <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f43f5e !important;">➔ LOG BELLEĞİNİ KAZI</span></div>
                    </button>
                </div>
            </div>

        </main>
    </div>

    <!-- ⚡ GRİD VE MENÜ KAYMALARINI KÖKTEN ERİTEN GELİŞMİŞ JS MOTORU -->
    <script>
        // ❖ Sekmeler arasında gezinirken o kutuları alt alta fırlatıp arayüzü yamultan hatayı tam kalbinden vurduk gadaşım!
        function switchAdminTab(tabId, element) {
            try {
                // 1. Sistemdeki tüm sekmeleri siber ağda tamamen gizle
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.add('hidden-tab');
                    tab.style.display = 'none';
                });
                
                // 2. Sol sidebar butonlarının pırıltılarını söndür
                document.querySelectorAll('.panel-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // 3. Hedeflenen sekmeyi uyandır gadaşım
                const targetTab = document.getElementById(tabId);
                if (targetTab) {
                    targetTab.classList.remove('hidden-tab');
                    
                    // [KRİTİK BYPASS] Modül yönetimine geri döndüğünde esnek yapıyı (flex-grid) zorla çiviliyoruz!
                    // Böylece kutular asla aşağı kaymıyor, ilk günkü gibi yan yana ve jilet gibi hizalanıyor!
                    if (tabId === 'adm-katalog') {
                        targetTab.style.display = 'flex';
                    } else {
                        targetTab.style.display = 'block';
                    }
                }
                
                // 4. Aktif basılan butonun neon pırıltılarını cayır cayır yak
                if (element) { element.classList.add('active'); }
            } catch (err) {
                console.error("Admin sekme senkron kalkanı hatayı süzdü: ", err);
            }
        }

        // 💬 Müşteri panelinden akan canlı sohbet loglarını buraya eşitleyen kanca
        const auditBox = document.getElementById('adm-chat-audit-box');
        function loadAuditLogs() {
            if (!auditBox) return;
            let messages = JSON.parse(localStorage.getItem('vaya_global_chat')) || [
                { sender: "System", avatar: "🤖", text: "Denetim hattı stabil.", time: "00:00" }
            ];
            auditBox.innerHTML = "";
            messages.forEach(msg => {
                auditBox.innerHTML += `<div><span style="color:#64748b;">[${msg.time}]</span> <span style="color:var(--neon-cyan); font-weight:bold; text-shadow: 0 0 5px rgba(0,255,204,0.2);">${msg.sender}:</span> <span style="color:#cbd5e1;">${msg.text}</span></div>`;
            });
            auditBox.scrollTop = auditBox.scrollHeight;
        }

        function clearVayaAuditLogs() {
            localStorage.removeItem('vaya_global_chat');
            loadAuditLogs();
        }

        // Butonlardaki o çılgın Matrix binary sayı döngüsünü canlı tetikliyoruz gadaşım
        setInterval(() => {
            document.querySelectorAll('.matrix-hex-stream').forEach(slot => {
                let randBinary = "";
                for(let i=0; i<4; i++) {
                    randBinary += Math.floor(Math.random() * 2).toString();
                }
                slot.innerText = randBinary;
            });
        }, 250);

        document.addEventListener("DOMContentLoaded", () => {
            loadAuditLogs();
            setInterval(loadAuditLogs, 2000); // 2 saniyede bir chat verilerini süz gadaşım
        });
    </script>
</body>
</html>
<?php 
// Çıktı tamponunu temizleyip sayfayı pürüzsüz basıyoruz gadaşım
ob_end_flush(); 
?>
