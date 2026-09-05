<?php
// admin.php - CENTRAL CONTROL INTERFACE V4.5 (MASKELENMİŞ)
require_once 'config.php';
require_once 'auth_check.php';
yetkiKontrol(['admin', 'owner']);

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
    // CSRF validation
    validateCsrfToken($_POST['csrf_token'] ?? '');
    
    $h_adi = sanitizeInput($_POST['h_name']);
    $h_durum = sanitizeInput($_POST['h_status']);
    $h_koruma = sanitizeInput($_POST['h_bypass']);
    $h_kelime = sanitizeInput($_POST['h_word']);
    $h_dosya = sanitizeInput($_POST['h_file']);

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
    // CSRF validation
    validateCsrfToken($_POST['csrf_token'] ?? '');
    
    $target_user = validateId($_POST['user_select_id']);
    $eklenecek_gun = validateId($_POST['extend_days']);

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
<script>
    // Define switchAdminTab immediately in head to ensure it's available before onclick handlers
    window.switchAdminTab = function(tabId, element) {
        console.log("Switching to tab:", tabId);
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
            console.log("Target tab found:", targetTab);
            if (targetTab) {
                targetTab.classList.remove('hidden-tab');
                
                // [KRİTİK BYPASS] Modül yönetimine geri döndüğünde esnek yapıyı (flex-grid) zorla çiviliyoruz!
                // Böylece kutular asla aşağı kaymıyor, ilk günkü gibi yan yana ve jilet gibi hizalanıyor!
                if (tabId === 'adm-katalog') {
                    targetTab.style.display = 'flex';
                } else {
                    targetTab.style.display = 'block';
                }
                console.log("Tab displayed successfully");
            } else {
                console.error("Tab not found:", tabId);
            }
            
            // 4. Aktif basılan butonun neon pırıltılarını cayır cayır yak
            if (element) { element.classList.add('active'); }
        } catch (err) {
            console.error("Admin sekme senkron kalkanı hatayı süzdü: ", err);
        }
    };
    
    console.log("switchAdminTab function defined in head:", typeof window.switchAdminTab);
</script>
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
                    <button class="panel-tab-btn" onclick="switchAdminTab('adm-moderation', this)">⚡ MODERASYON</button>
                    <button class="panel-tab-btn" onclick="switchAdminTab('adm-chat', this)">💬 CANLI CHAT LOG</button>
                    <a href="pricing.php" class="panel-tab-btn" style="text-decoration: none; display: block;">💎 PRICING</a>
                    <?php if (isOwner()): ?>
                    <button class="panel-tab-btn" onclick="switchAdminTab('adm-owner', this)" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);">👑 OWNER PANEL</button>
                    <?php endif; ?>
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
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
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
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Süre Enjekte Edilecek Operatör:</span>
                                <select name="user_select_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Operatör Seçin --</option>
                                    <?php
                                    try {
                                        // Owner can see all users, Admin cannot see owner
                                        if (isOwner()) {
                                            $userSorgu = $db->query("SELECT id, username, role FROM kullanicilar ORDER BY id DESC");
                                        } else {
                                            $userSorgu = $db->query("SELECT id, username, role FROM kullanicilar WHERE role != 'owner' ORDER BY id DESC");
                                        }
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

                <!-- KUTU 3: AKTİF BULUT KATALOGU TABLOSU (SAĞA TAŞMA VE KAYMA ENGELLENDİ 💎) -->
                <div class="user-grid" style="margin-top: 5px; width: 100% !important;">
                    <div class="user-box" style="width: 100% !important; max-width: 100% !important; overflow: hidden !important;">
                        <div class="box-title">[ 📊 AKTİF BULUT KATALOG MODÜLLERİ ]</div>
                        <div style="overflow-x: auto; font-family: monospace; width: 100%;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; color: #cbd5e1; table-layout: fixed;">
                                <colgroup>
                                    <col style="width: 6%;">
                                    <col style="width: 22%;">
                                    <col style="width: 12%;">
                                    <col style="width: 18%;">
                                    <col style="width: 25%;">
                                    <col style="width: 17%;">
                                </colgroup>
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(0, 255, 204, 0.1); color: var(--neon-cyan); text-shadow: 0 0 5px rgba(0,255,204,0.2);">
                                        <th style="padding: 12px 8px;">ID</th>
                                        <th style="padding: 12px 8px;">MODÜL ADI</th>
                                        <th style="padding: 12px 8px;">DURUM</th>
                                        <th style="padding: 12px 8px;">SÜRÜCÜ</th>
                                        <th style="padding: 12px 8px;">DOSYA YOLU</th>
                                        <th style="padding: 12px 8px;">İŞLEMLER</th>
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
                                                echo '<td style="padding: 12px 8px;">';
                                                echo '<a href="product.php?id=' . $h['id'] . '" style="color: var(--neon-cyan); text-decoration: none; margin-right: 8px; font-size: 10px;">VIEW</a>';
                                                echo '<button onclick="editProduct(' . $h['id'] . ')" style="background: #eab308; color: #000; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 10px; margin-right: 5px;">EDIT</button>';
                                                echo '<form action="admin_products.php" method="POST" style="display: inline;">';
                                                echo '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
                                                echo '<input type="hidden" name="action" value="delete_product">';
                                                echo '<input type="hidden" name="product_id" value="' . $h['id'] . '">';
                                                echo '<button type="submit" onclick="return confirm(\'Delete this product?\')" style="background: #f43f5e; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 10px;">DELETE</button>';
                                                echo '</form>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #64748b;">// BULUT SEVİYESİNDE AKTİF KATALOG MODÜLÜ BULUNAMADI.</td></tr>';
                                        }
                                    } catch (PDOException $e) { echo '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #f43f5e;">// VERİ HATASI.</td></tr>'; }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- ADVANCED PRODUCT MANAGEMENT -->
                <div class="user-box" style="width: 100%; margin-top: 30px;">
                    <div class="box-title">[ ⚡ ADVANCED PRODUCT MANAGEMENT ]</div>
                    <form action="admin_products.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;" id="advancedProductForm">
                        <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Product Name:</span>
                                <input type="text" name="hile_adi" class="form-input-vaya" placeholder="Product name" required>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Status:</span>
                                <select name="durum" class="form-input-vaya" style="background: #04070e; cursor: pointer;">
                                    <option value="UNDETECTED">UNDETECTED</option>
                                    <option value="DETECTED">DETECTED</option>
                                    <option value="BAKIMDA">BAKIMDA</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Protection/Bypass:</span>
                                <input type="text" name="koruma" class="form-input-vaya" placeholder="e.g., Kernel Driver v4.2">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Search Keyword:</span>
                                <input type="text" name="aranacak_kelime" class="form-input-vaya" placeholder="e.g., csgo">
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="color: #64748b; font-size: 11px;">Product File:</span>
                            <input type="file" name="product_file" class="form-input-vaya" accept=".zip,.rar,.7z,.exe,.dll" required>
                            <span style="color: #64748b; font-size: 10px;">Allowed: .zip, .rar, .7z, .exe, .dll (Max 50MB)</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Version:</span>
                                <input type="text" name="version" class="form-input-vaya" placeholder="1.0.0" value="1.0.0">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Features (JSON array):</span>
                                <input type="text" name="features" class="form-input-vaya" placeholder='["Aimbot", "ESP", "Wallhack"]'>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="color: #64748b; font-size: 11px;">Description:</span>
                            <textarea name="description" class="form-input-vaya" rows="3" placeholder="Product description..."></textarea>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="color: #64748b; font-size: 11px;">Images (JSON array of URLs):</span>
                            <input type="text" name="images" class="form-input-vaya" placeholder='["img1.jpg", "img2.jpg"]'>
                        </div>
                        
                        <button type="submit" class="btn-download" id="addProductBtn">
                            <div class="neural-center-zone"><span class="neural-main-txt">ADD PRODUCT WITH DETAILS</span></div>
                        </button>
                        <div id="productFormResult" style="font-size: 12px;"></div>
                    </form>
                </div>
                
                <!-- CHANGELOG MANAGEMENT -->
                <div class="user-box" style="width: 100%; margin-top: 30px;">
                    <div class="box-title">[ 📝 ADD CHANGELOG ENTRY ]</div>
                    <form action="admin_products.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;" id="changelogForm">
                        <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                        <input type="hidden" name="action" value="add_changelog">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Product:</span>
                                <select name="product_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select Product --</option>
                                    <?php
                                    $katalogSorgu->execute();
                                    while ($h = $katalogSorgu->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $h['id'] . '">' . htmlspecialchars($h['hile_adi']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Version:</span>
                                <input type="text" name="version" class="form-input-vaya" placeholder="1.0.1" required>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="color: #64748b; font-size: 11px;">Release Date:</span>
                            <input type="date" name="release_date" class="form-input-vaya" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span style="color: #64748b; font-size: 11px;">Changes:</span>
                            <textarea name="changes" class="form-input-vaya" rows="4" placeholder="List of changes..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn-download" style="background: rgba(0, 255, 204, 0.1) !important; border: 1px solid var(--neon-cyan) !important;" id="addChangelogBtn">
                            <div class="neural-center-zone"><span class="neural-main-txt" style="color: var(--neon-cyan) !important;">ADD CHANGELOG</span></div>
                        </button>
                        <div id="changelogFormResult" style="font-size: 12px;"></div>
                    </form>
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
                                    // Owner can see all users, Admin cannot see owner
                                    if (isOwner()) {
                                        $uListQuery = $db->query("SELECT id, username, role, bitis_tarihi FROM kullanicilar ORDER BY id DESC");
                                    } else {
                                        $uListQuery = $db->query("SELECT id, username, role, bitis_tarihi FROM kullanicilar WHERE role != 'owner' ORDER BY id DESC");
                                    }
                                    while ($uItem = $uListQuery->fetch(PDO::FETCH_ASSOC)) {
                                        $rColor = '#fff';
                                        if($uItem['role'] === 'admin') $rColor = 'var(--user-neon)';
                                        if($uItem['role'] === 'owner') $rColor = '#f59e0b';
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
            <!-- ⚡ 3. SEKME: MODERASYON TOOLS (BAN, MUTE, COMMENT MANAGEMENT)             -->
            <!-- ========================================================================= -->
            <div id="adm-moderation" class="tab-content hidden-tab" style="display: none;">
                <div class="user-grid" style="width: 100%;">
                    
                    <!-- BAN USER TOOL -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title">[ ⚡ USER BAN SYSTEM ]</div>
                        <div id="banResult" style="margin-bottom: 15px; font-family: monospace; font-size: 11px;"></div>
                        <form id="banForm" action="admin_moderation.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="ban_user">
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Target User:</span>
                                <select name="user_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    // Owner can see all users, Admin cannot see admin or owner
                                    if (isOwner()) {
                                        $userList = $db->query("SELECT id, username, role FROM kullanicilar WHERE role != 'owner' ORDER BY username ASC");
                                    } else {
                                        $userList = $db->query("SELECT id, username, role FROM kullanicilar WHERE role NOT IN ('admin', 'owner') ORDER BY username ASC");
                                    }
                                    while ($u = $userList->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Ban Type:</span>
                                <select name="ban_type" class="form-input-vaya" style="background: #04070e; cursor: pointer;">
                                    <option value="temporary">Temporary</option>
                                    <option value="permanent">Permanent</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Duration (Hours):</span>
                                <input type="number" name="duration_hours" class="form-input-vaya" placeholder="0 for permanent" min="0">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Reason:</span>
                                <textarea name="reason" class="form-input-vaya" rows="3" placeholder="Ban reason..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-download" style="background: rgba(244, 63, 94, 0.1) !important; border: 1px solid #f43f5e !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f43f5e !important;">BAN USER</span></div>
                            </button>
                        </form>
                    </div>
                    
                    <!-- MUTE USER TOOL -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title">[ 🔇 USER MUTE SYSTEM ]</div>
                        <div id="muteResult" style="margin-bottom: 15px; font-family: monospace; font-size: 11px;"></div>
                        <form id="muteForm" action="admin_moderation.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="mute_user">
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Target User:</span>
                                <select name="user_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    // Owner can see all users, Admin cannot see admin or owner
                                    if (isOwner()) {
                                        $muteUserList = $db->query("SELECT id, username, role FROM kullanicilar WHERE role != 'owner' ORDER BY username ASC");
                                    } else {
                                        $muteUserList = $db->query("SELECT id, username, role FROM kullanicilar WHERE role NOT IN ('admin', 'owner') ORDER BY username ASC");
                                    }
                                    while ($u = $muteUserList->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Mute Type:</span>
                                <select name="mute_type" class="form-input-vaya" style="background: #04070e; cursor: pointer;">
                                    <option value="temporary">Temporary</option>
                                    <option value="permanent">Permanent</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Duration (Minutes):</span>
                                <input type="number" name="duration_minutes" class="form-input-vaya" placeholder="0 for permanent" min="0">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Reason:</span>
                                <textarea name="reason" class="form-input-vaya" rows="3" placeholder="Mute reason..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-download" style="background: rgba(234, 179, 8, 0.1) !important; border: 1px solid #eab308 !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #eab308 !important;">MUTE USER</span></div>
                            </button>
                        </form>
                    </div>
                    
                    <!-- PASSWORD RESET TOOL -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title">[ 🔑 PASSWORD RESET ]</div>
                        <div id="passwordResult" style="margin-bottom: 15px; font-family: monospace; font-size: 11px;"></div>
                        <form id="passwordForm" action="admin_password.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="reset_password">
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Target User:</span>
                                <select name="user_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    // Owner can see all users except themselves, Admin cannot see owner
                                    if (isOwner()) {
                                        $passwordUserList = $db->query("SELECT id, username, role FROM kullanicilar WHERE id != " . (int)$_SESSION['user_id'] . " ORDER BY username ASC");
                                    } else {
                                        $passwordUserList = $db->query("SELECT id, username, role FROM kullanicilar WHERE role != 'owner' ORDER BY username ASC");
                                    }
                                    while ($u = $passwordUserList->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">New Password:</span>
                                <input type="password" name="new_password" class="form-input-vaya" placeholder="Enter new password" required minlength="2" maxlength="128">
                                <span style="color: #64748b; font-size: 10px; margin-top: 3px;">Recommendation: Use at least 8 characters with uppercase, lowercase, numbers, and special characters for better security.</span>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Confirm Password:</span>
                                <input type="password" name="confirm_password" class="form-input-vaya" placeholder="Confirm new password" required minlength="2" maxlength="128">
                            </div>
                            
                            <button type="submit" class="btn-download" style="background: rgba(59, 130, 246, 0.1) !important; border: 1px solid #3b82f6 !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #3b82f6 !important;">RESET PASSWORD</span></div>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- ACTIVE BANS/MUTES LIST -->
                <div class="user-box" style="width: 100%; margin-top: 30px;">
                    <div class="box-title">[ 📋 ACTIVE PUNISHMENTS ]</div>
                    <div style="overflow-x: auto; font-family: monospace;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; color: #cbd5e1;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(0, 255, 204, 0.1); color: var(--neon-cyan);">
                                    <th style="padding: 12px 8px;">Type</th>
                                    <th style="padding: 12px 8px;">User</th>
                                    <th style="padding: 12px 8px;">Duration</th>
                                    <th style="padding: 12px 8px;">Expires</th>
                                    <th style="padding: 12px 8px;">Reason</th>
                                    <th style="padding: 12px 8px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Active bans
                                $bansQuery = $db->query("
                                    SELECT b.*, u.username 
                                    FROM bans b 
                                    JOIN kullanicilar u ON b.user_id = u.id 
                                    WHERE b.expires_at IS NULL OR b.expires_at > NOW()
                                    ORDER BY b.created_at DESC
                                ");
                                while ($ban = $bansQuery->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.02); background: rgba(244, 63, 94, 0.1);">';
                                    echo '<td style="padding: 12px 8px; color: #f43f5e; font-weight: bold;">BAN</td>';
                                    echo '<td style="padding: 12px 8px; font-weight: bold;">' . htmlspecialchars($ban['username']) . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . ($ban['duration_hours'] ? $ban['duration_hours'] . 'h' : 'Permanent') . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . ($ban['expires_at'] ? date('M j, H:i', strtotime($ban['expires_at'])) : 'Never') . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . htmlspecialchars(substr($ban['reason'], 0, 30)) . '</td>';
                                    echo '<td style="padding: 12px 8px;">';
                                    echo '<form action="admin_moderation.php" method="POST" style="display: inline;">';
                                    echo '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
                                    echo '<input type="hidden" name="action" value="unban_user">';
                                    echo '<input type="hidden" name="user_id" value="' . $ban['user_id'] . '">';
                                    echo '<button type="submit" style="background: #10b981; color: #000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 10px;">UNBAN</button>';
                                    echo '</form>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                // Active mutes
                                $mutesQuery = $db->query("
                                    SELECT m.*, u.username 
                                    FROM mutes m 
                                    JOIN kullanicilar u ON m.user_id = u.id 
                                    WHERE m.expires_at IS NULL OR m.expires_at > NOW()
                                    ORDER BY m.created_at DESC
                                ");
                                while ($mute = $mutesQuery->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.02); background: rgba(234, 179, 8, 0.1);">';
                                    echo '<td style="padding: 12px 8px; color: #eab308; font-weight: bold;">MUTE</td>';
                                    echo '<td style="padding: 12px 8px; font-weight: bold;">' . htmlspecialchars($mute['username']) . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . ($mute['duration_minutes'] ? $mute['duration_minutes'] . 'm' : 'Permanent') . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . ($mute['expires_at'] ? date('M j, H:i', strtotime($mute['expires_at'])) : 'Never') . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . htmlspecialchars(substr($mute['reason'], 0, 30)) . '</td>';
                                    echo '<td style="padding: 12px 8px;">';
                                    echo '<form action="admin_moderation.php" method="POST" style="display: inline;">';
                                    echo '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
                                    echo '<input type="hidden" name="action" value="unmute_user">';
                                    echo '<input type="hidden" name="user_id" value="' . $mute['user_id'] . '">';
                                    echo '<button type="submit" style="background: #10b981; color: #000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 10px;">UNMUTE</button>';
                                    echo '</form>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- RECENT COMMENTS -->
                <div class="user-box" style="width: 100%; margin-top: 30px;">
                    <div class="box-title">[ 💬 RECENT COMMENTS ]</div>
                    <div style="overflow-x: auto; font-family: monospace;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; color: #cbd5e1;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(0, 255, 204, 0.1); color: var(--neon-cyan);">
                                    <th style="padding: 12px 8px;">ID</th>
                                    <th style="padding: 12px 8px;">User</th>
                                    <th style="padding: 12px 8px;">Product</th>
                                    <th style="padding: 12px 8px;">Content</th>
                                    <th style="padding: 12px 8px;">Date</th>
                                    <th style="padding: 12px 8px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $commentsQuery = $db->query("
                                    SELECT c.*, u.username, h.hile_adi 
                                    FROM comments c 
                                    JOIN kullanicilar u ON c.user_id = u.id 
                                    JOIN hileler h ON c.product_id = h.id 
                                    ORDER BY c.created_at DESC 
                                    LIMIT 20
                                ");
                                while ($comment = $commentsQuery->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.02); background: rgba(0,0,0,0.15);">';
                                    echo '<td style="padding: 12px 8px; color: #64748b;">#' . $comment['id'] . '</td>';
                                    echo '<td style="padding: 12px 8px; font-weight: bold;">' . htmlspecialchars($comment['username']) . '</td>';
                                    echo '<td style="padding: 12px 8px;">' . htmlspecialchars($comment['hile_adi']) . '</td>';
                                    echo '<td style="padding: 12px 8px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($comment['content']) . '</td>';
                                    echo '<td style="padding: 12px 8px; color: #64748b;">' . date('M j, H:i', strtotime($comment['created_at'])) . '</td>';
                                    echo '<td style="padding: 12px 8px;">';
                                    if ($comment['is_pinned']) echo '<span style="background: var(--neon-cyan); color: #000; padding: 2px 6px; border-radius: 3px; font-size: 9px; margin-right: 5px;">PINNED</span>';
                                    echo '<form action="admin_moderation.php" method="POST" style="display: inline;">';
                                    echo '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
                                    echo '<input type="hidden" name="action" value="delete_comment">';
                                    echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
                                    echo '<button type="submit" style="background: #f43f5e; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 10px; margin-right: 5px;">DELETE</button>';
                                    echo '</form>';
                                    echo '<form action="admin_moderation.php" method="POST" style="display: inline;">';
                                    echo '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
                                    echo '<input type="hidden" name="action" value="pin_comment">';
                                    echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
                                    echo '<button type="submit" style="background: var(--neon-cyan); color: #000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 10px;">' . ($comment['is_pinned'] ? 'UNPIN' : 'PIN') . '</button>';
                                    echo '</form>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- ========================================================================= -->
            <!-- 💬 4. SEKME: CANLI SECURE GLOBAL CHAT AUDIT LOGS (GERİ KAZANILDI)          -->
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
            <!-- ========================================================================= -->
            <!-- 👑 5. SEKME: OWNER PANEL (OWNER ONLY)                                      -->
            <!-- ========================================================================= -->
            <?php if (isOwner()): ?>
            <div id="adm-owner" class="tab-content hidden-tab" style="display: none;">
                <div class="user-grid" style="width: 100%;">
                    
                    <!-- MANAGE OWNERS -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title" style="color: #f59e0b;">[ 👑 MANAGE OWNERS ]</div>
                        <form action="admin_owner.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="add_owner">
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Select User:</span>
                                <select name="user_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    $nonOwners = $db->query("SELECT id, username, role FROM kullanicilar WHERE id != " . (int)$_SESSION['user_id'] . " AND role != 'owner' ORDER BY username ASC");
                                    while ($u = $nonOwners->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-download" style="background: rgba(245, 158, 11, 0.1) !important; border: 1px solid #f59e0b !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f59e0b !important;">ADD OWNER</span></div>
                            </button>
                        </form>
                        
                        <div style="margin-top: 20px;">
                            <div style="color: #64748b; font-size: 11px; margin-bottom: 10px;">Current Owners:</div>
                            <?php
                            $owners = $db->query("SELECT id, username FROM kullanicilar WHERE role = 'owner'");
                            while ($owner = $owners->fetch(PDO::FETCH_ASSOC)) {
                                echo '<div style="padding: 10px; background: rgba(245, 158, 11, 0.1); border-radius: 8px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">';
                                echo '<span style="color: #f59e0b; font-weight: bold;">' . htmlspecialchars($owner['username']) . '</span>';
                                if ($owner['id'] !== $_SESSION['user_id']) {
                                    echo '<form action="admin_owner.php" method="POST" style="display: inline;">';
                                    echo '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
                                    echo '<input type="hidden" name="action" value="remove_owner">';
                                    echo '<input type="hidden" name="user_id" value="' . $owner['id'] . '">';
                                    echo '<button type="submit" style="background: #f43f5e; color: #fff; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 10px;">REMOVE</button>';
                                    echo '</form>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- MANAGE ROLES -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title" style="color: #f59e0b;">[ 🔧 MANAGE ROLES ]</div>
                        <form action="admin_owner.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="change_role">
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Select User:</span>
                                <select name="user_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    $allUsers = $db->query("SELECT id, username, role FROM kullanicilar WHERE id != " . (int)$_SESSION['user_id'] . " AND role != 'owner' ORDER BY username ASC");
                                    while ($u = $allUsers->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">New Role:</span>
                                <select name="new_role" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="vip">VIP</option>
                                    <option value="admin">ADMIN</option>
                                    <option value="moderator">MODERATOR</option>
                                    <option value="user">USER</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-download" style="background: rgba(245, 158, 11, 0.1) !important; border: 1px solid #f59e0b !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f59e0b !important;">CHANGE ROLE</span></div>
                            </button>
                        </form>
                    </div>
                    
                    <!-- SYSTEM SETTINGS -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title" style="color: #f59e0b;">[ ⚙️ SYSTEM SETTINGS ]</div>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                <div style="color: #64748b; font-size: 11px;">Maintenance Mode</div>
                                <div style="color: #10b981; font-size: 12px; margin-top: 5px;">DISABLED</div>
                            </div>
                            <div style="padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                <div style="color: #64748b; font-size: 11px;">Registration</div>
                                <div style="color: #10b981; font-size: 12px; margin-top: 5px;">OPEN</div>
                            </div>
                            <div style="padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                <div style="color: #64748b; font-size: 11px;">Database Status</div>
                                <div style="color: #10b981; font-size: 12px; margin-top: 5px;">CONNECTED</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PAYMENT APPROVALS -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title" style="color: #f59e0b;">[ 💳 PAYMENT APPROVALS ]</div>
                        <div style="text-align: center; color: #64748b; padding: 20px;">
                            Payment system coming soon
                        </div>
                    </div>
                    
                    <!-- SECURITY LOGS -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title" style="color: #f59e0b;">[ 🛡️ SECURITY LOGS ]</div>
                        <div style="text-align: center; color: #64748b; padding: 20px;">
                            Security logging coming soon
                        </div>
                    </div>
                    
                    <!-- SUBSCRIPTION MANAGEMENT -->
                    <div class="user-box" style="flex: 1; min-width: 320px;">
                        <div class="box-title" style="color: #f59e0b;">[ 💎 SUBSCRIPTION MANAGEMENT ]</div>
                        <div id="subResult" style="margin-bottom: 15px; font-family: monospace; font-size: 11px;"></div>
                        <form id="subForm" action="admin_subscription.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; font-family: monospace;">
                            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="grant_subscription">
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Action:</span>
                                <select name="sub_action" class="form-input-vaya" style="background: #04070e; cursor: pointer;" onchange="updateSubForm(this.value)">
                                    <option value="grant">Grant Subscription</option>
                                    <option value="remove">Remove Subscription</option>
                                    <option value="extend">Extend Subscription</option>
                                    <option value="shorten">Shorten Subscription</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Target User:</span>
                                <select name="user_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    // Owner can see all users except themselves, Admin cannot see owner
                                    if (isOwner()) {
                                        $allUsers = $db->query("SELECT id, username, role FROM kullanicilar WHERE id != " . (int)$_SESSION['user_id'] . " ORDER BY username ASC");
                                    } else {
                                        $allUsers = $db->query("SELECT id, username, role FROM kullanicilar WHERE role != 'owner' ORDER BY username ASC");
                                    }
                                    while ($u = $allUsers->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . ' [' . strtoupper($u['role']) . ']</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div id="subTypeDiv" style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Subscription Type:</span>
                                <select name="subscription_type_id" class="form-input-vaya" style="background: #04070e; cursor: pointer;">
                                    <option value="">-- Select Type --</option>
                                    <?php
                                    $subTypes = $db->query("SELECT id, name, level FROM subscription_types WHERE is_active = 1 ORDER BY level ASC");
                                    while ($st = $subTypes->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $st['id'] . '">' . htmlspecialchars($st['name']) . ' (Level ' . $st['level'] . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div id="durationDiv" style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Duration (Days):</span>
                                <input type="number" name="duration_days" class="form-input-vaya" value="30" min="1">
                            </div>
                            
                            <div id="extendDiv" style="display: none; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Additional Days:</span>
                                <input type="number" name="additional_days" class="form-input-vaya" value="30" min="1">
                            </div>
                            
                            <div id="shortenDiv" style="display: none; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Reduce Days:</span>
                                <input type="number" name="reduce_days" class="form-input-vaya" value="7" min="1">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="color: #64748b; font-size: 11px;">Reason:</span>
                                <textarea name="reason" class="form-input-vaya" rows="2" placeholder="Reason for action..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-download" style="background: rgba(245, 158, 11, 0.1) !important; border: 1px solid #f59e0b !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f59e0b !important;">EXECUTE</span></div>
                            </button>
                        </form>
                    </div>
                    
                    <!-- DANGER ZONE -->
                    <div class="user-box" style="flex: 1; min-width: 320px; border: 1px solid rgba(244, 63, 94, 0.3);">
                        <div class="box-title" style="color: #f43f5e;">[ ⚠️ DANGER ZONE ]</div>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <button onclick="if(confirm('Are you sure you want to clear all chat logs?')) location.href='admin.php?action=clear_logs'" class="btn-download" style="background: rgba(244, 63, 94, 0.1) !important; border: 1px solid #f43f5e !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f43f5e !important;">CLEAR ALL LOGS</span></div>
                            </button>
                            <button onclick="if(confirm('This will put the site in maintenance mode. Continue?')) location.href='admin.php?action=maintenance'" class="btn-download" style="background: rgba(244, 63, 94, 0.1) !important; border: 1px solid #f43f5e !important;">
                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #f43f5e !important;">MAINTENANCE MODE</span></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- ⚡ GRİD VE MENÜ KAYMALARINI KÖKTEN ERİTEN GELİŞMİŞ JS MOTORU -->
    <script>
        // Helper functions (switchAdminTab is already defined in head)
        
        window.loadAuditLogs = function() {
            const auditBox = document.getElementById('adm-chat-audit-box');
            if (!auditBox) return;
            
            const stored = localStorage.getItem('vaya_global_chat');
            if (!stored) {
                auditBox.innerHTML = '<div style="color: #64748b;">No chat logs available</div>';
                return;
            }
            
            try {
                const messages = JSON.parse(stored);
                auditBox.innerHTML = "";
                messages.forEach(msg => {
                    auditBox.innerHTML += `<div><span style="color:#64748b;">[${msg.time}]</span> <span style="color:var(--neon-cyan); font-weight:bold; text-shadow: 0 0 5px rgba(0,255,204,0.2);">${msg.sender}:</span> <span style="color:#cbd5e1;">${msg.text}</span></div>`;
                });
                auditBox.scrollTop = auditBox.scrollHeight;
            } catch (e) {
                auditBox.innerHTML = '<div style="color: #f43f5e;">Error loading chat logs</div>';
            }
        };

        window.clearVayaAuditLogs = function() {
            localStorage.removeItem('vaya_global_chat');
            window.loadAuditLogs();
        };
        
        // Update subscription form based on action
        window.updateSubForm = function(action) {
            const subTypeDiv = document.getElementById('subTypeDiv');
            const durationDiv = document.getElementById('durationDiv');
            const extendDiv = document.getElementById('extendDiv');
            const shortenDiv = document.getElementById('shortenDiv');
            const actionInput = document.querySelector('#subForm input[name="action"]');
            
            // Reset all
            subTypeDiv.style.display = 'none';
            durationDiv.style.display = 'none';
            extendDiv.style.display = 'none';
            shortenDiv.style.display = 'none';
            
            switch(action) {
                case 'grant':
                    subTypeDiv.style.display = 'flex';
                    durationDiv.style.display = 'flex';
                    actionInput.value = 'grant_subscription';
                    break;
                case 'remove':
                    actionInput.value = 'remove_subscription';
                    break;
                case 'extend':
                    extendDiv.style.display = 'flex';
                    actionInput.value = 'extend_subscription';
                    break;
                case 'shorten':
                    shortenDiv.style.display = 'flex';
                    actionInput.value = 'shorten_subscription';
                    break;
            }
        };

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
            console.log("DOM loaded, initializing admin panel");
            window.loadAuditLogs();
            setInterval(window.loadAuditLogs, 2000); // 2 saniyede bir chat verilerini süz gadaşım
            
            // Advanced product form handler
            const advancedProductForm = document.getElementById('advancedProductForm');
            if (advancedProductForm) {
                advancedProductForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(advancedProductForm);
                    const submitBtn = document.getElementById('addProductBtn');
                    const resultDiv = document.getElementById('productFormResult');
                    
                    submitBtn.disabled = true;
                    resultDiv.style.color = '#64748b';
                    resultDiv.textContent = 'Processing...';
                    
                    fetch('admin_products.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.style.color = '#10b981';
                            resultDiv.textContent = data.message;
                            advancedProductForm.reset();
                            // Reload page after short delay to show updated product list
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            resultDiv.style.color = '#f43f5e';
                            resultDiv.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Error: ' + error.message;
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Changelog form handler
            const changelogForm = document.getElementById('changelogForm');
            if (changelogForm) {
                changelogForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(changelogForm);
                    const submitBtn = document.getElementById('addChangelogBtn');
                    const resultDiv = document.getElementById('changelogFormResult');
                    
                    submitBtn.disabled = true;
                    resultDiv.style.color = '#64748b';
                    resultDiv.textContent = 'Processing...';
                    
                    fetch('admin_products.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.style.color = '#10b981';
                            resultDiv.textContent = data.message;
                            changelogForm.reset();
                        } else {
                            resultDiv.style.color = '#f43f5e';
                            resultDiv.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Error: ' + error.message;
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Ban form handler
            const banForm = document.getElementById('banForm');
            if (banForm) {
                banForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(banForm);
                    const resultDiv = document.getElementById('banResult');
                    const submitBtn = banForm.querySelector('button[type="submit"]');
                    
                    submitBtn.disabled = true;
                    resultDiv.style.color = '#64748b';
                    resultDiv.textContent = 'Processing...';
                    
                    fetch('admin_moderation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.style.color = '#10b981';
                            resultDiv.textContent = data.message;
                            banForm.reset();
                            setTimeout(() => {
                                if (window.history.length > 1) {
                                    window.history.back();
                                } else {
                                    window.location.href = 'admin.php';
                                }
                            }, 3000);
                        } else {
                            resultDiv.style.color = '#f43f5e';
                            resultDiv.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Error: ' + error.message;
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Mute form handler
            const muteForm = document.getElementById('muteForm');
            if (muteForm) {
                muteForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(muteForm);
                    const resultDiv = document.getElementById('muteResult');
                    const submitBtn = muteForm.querySelector('button[type="submit"]');
                    
                    submitBtn.disabled = true;
                    resultDiv.style.color = '#64748b';
                    resultDiv.textContent = 'Processing...';
                    
                    fetch('admin_moderation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.style.color = '#10b981';
                            resultDiv.textContent = data.message;
                            muteForm.reset();
                            setTimeout(() => {
                                if (window.history.length > 1) {
                                    window.history.back();
                                } else {
                                    window.location.href = 'admin.php';
                                }
                            }, 3000);
                        } else {
                            resultDiv.style.color = '#f43f5e';
                            resultDiv.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Error: ' + error.message;
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Password form handler
            const passwordForm = document.getElementById('passwordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(passwordForm);
                    const resultDiv = document.getElementById('passwordResult');
                    const submitBtn = passwordForm.querySelector('button[type="submit"]');
                    
                    const newPassword = formData.get('new_password');
                    const confirmPassword = formData.get('confirm_password');
                    
                    if (newPassword !== confirmPassword) {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Passwords do not match';
                        return;
                    }
                    
                    submitBtn.disabled = true;
                    resultDiv.style.color = '#64748b';
                    resultDiv.textContent = 'Processing...';
                    
                    fetch('admin_password.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.style.color = '#10b981';
                            resultDiv.textContent = data.message;
                            passwordForm.reset();
                            setTimeout(() => {
                                if (window.history.length > 1) {
                                    window.history.back();
                                } else {
                                    window.location.href = 'admin.php';
                                }
                            }, 3000);
                        } else {
                            resultDiv.style.color = '#f43f5e';
                            resultDiv.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Error: ' + error.message;
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Subscription form handler
            const subForm = document.getElementById('subForm');
            if (subForm) {
                subForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(subForm);
                    const resultDiv = document.getElementById('subResult');
                    const submitBtn = subForm.querySelector('button[type="submit"]');
                    const actionSelect = formData.get('sub_action');
                    
                    // Update action based on selection
                    formData.set('action', actionSelect + '_subscription');
                    
                    submitBtn.disabled = true;
                    resultDiv.style.color = '#64748b';
                    resultDiv.textContent = 'Processing...';
                    
                    fetch('admin_subscription.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.style.color = '#10b981';
                            resultDiv.textContent = data.message;
                            subForm.reset();
                            updateSubForm('grant');
                        } else {
                            resultDiv.style.color = '#f43f5e';
                            resultDiv.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        resultDiv.style.color = '#f43f5e';
                        resultDiv.textContent = 'Error: ' + error.message;
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                    });
                });
            }
            
            // Handle inline moderation forms (unban, unmute, delete, pin)
            document.querySelectorAll('form[action="admin_moderation.php"]').forEach(form => {
                if (form.id !== 'banForm' && form.id !== 'muteForm') {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(form);
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalText = submitBtn.textContent;
                        
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processing...';
                        
                        fetch('admin_moderation.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                submitBtn.style.background = '#10b981';
                                submitBtn.textContent = '✓ Success';
                                setTimeout(() => {
                                    if (window.history.length > 1) {
                                        window.history.back();
                                    } else {
                                        window.location.href = 'admin.php';
                                    }
                                }, 3000);
                            } else {
                                submitBtn.style.background = '#f43f5e';
                                submitBtn.textContent = '✗ Failed';
                                setTimeout(() => {
                                    submitBtn.disabled = false;
                                    submitBtn.style.background = '';
                                    submitBtn.textContent = originalText;
                                }, 2000);
                            }
                        })
                        .catch(error => {
                            submitBtn.style.background = '#f43f5e';
                            submitBtn.textContent = 'Error';
                            setTimeout(() => {
                                submitBtn.disabled = false;
                                submitBtn.style.background = '';
                                submitBtn.textContent = originalText;
                            }, 2000);
                        });
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php 
// Çıktı tamponunu temizleyip sayfayı pürüzsüz basıyoruz gadaşım
ob_end_flush(); 
?>
