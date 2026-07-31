<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'auth_check.php';

// Yetki ve Giriş Kontrolleri
yetkiKontrol(['vip', 'admin']); 

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 🔥 TEK VE GÜÇLÜ SORGULU VERİ ÇEKME MOTORU
$userQuery = $db->prepare("SELECT username, role, bitis_tarihi, profil_color, avatar, avatar_url FROM kullanicilar WHERE id = ?");
$userQuery->execute([$user_id]);
$userData = $userQuery->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    session_destroy();
    header("Location: auth.php");
    exit;
}

// Süre kontrolü
if ($userData['role'] !== 'admin') {
    // Veritabanındaki tarih değerini kontrol edelim
    $tarih_degeri = $userData['bitis_tarihi'];
    
    // Eğer tarih alanı boşsa veya hatalıysa lisansı durdur
    if (empty($tarih_degeri)) {
        session_destroy();
        header("Location: auth.php?error=no_license");
        exit;
    }

    $bitis = strtotime($tarih_degeri);
    if ($bitis < time()) {
        session_destroy();
        header("Location: auth.php?error=expired");
        exit;
    }
}
// Global değişkenleri burada mühürlüyoruz (Artık tek yerden geliyor)
$username    = $userData['username'];
$user_role   = $userData['role'];
$user_neon   = $userData['profil_color'] ?? '#00ffcc';
$user_avatar = $userData['avatar'] ?? '🥷';
$user_photo  = $userData['avatar_url'];

// ... diğer değişkenler ...
$bitis_tarihi = $userData['bitis_tarihi'];
$bitis_timestamp = strtotime($bitis_tarihi);
$simdi = time();
$kalan_saniye = $bitis_timestamp - $simdi;

// Kalan süreyi okunabilir formata çevir (Gün ve Saat olarak)
$gun = floor($kalan_saniye / 86400);
$saat = floor(($kalan_saniye % 86400) / 3600);
$kalan_sure_metin = ($gun > 0) ? "$gun Gün, $saat Saat" : "$saat Saat";
$kalan_saniye = strtotime($userData['bitis_tarihi']) - time();

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'tab-main';


?>
<script>
    let remainingSeconds = <?php echo max(0, $kalan_saniye); ?>;
</script>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VayaCheats // Terminal</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
    :root {
        --neon-cyan: #00ffcc;
        --user-neon: <?php echo $user_neon; ?>;
        --user-glow: rgba(<?php 
            list($r, $g, $b) = sscanf($user_neon, "#%02x%02x%02x");
            echo "$r, $g, $b";
        ?>, 0.25);
        --bg-dark-matrix: #060814;
        --panel-blur-bg: rgba(6, 11, 23, 0.55); 
        --border-glow: rgba(0, 255, 204, 0.08);
        --box-shadow-glow: rgba(4, 7, 14, 0.7);
    }

    body {
        background-color: var(--bg-dark-matrix) !important;
        background-image: 
            /* Opaklıkları 0.015 ve 0.01 yaparak ışığı iyice dağıtıp arka plana gömdük */
            radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.015) 0%, transparent 50%),
            radial-gradient(circle at 20% 80%, rgba(0, 255, 204, 0.01) 0%, transparent 60%) !important;
        margin: 0;
        padding: 0;
        overflow: hidden;
        height: 100vh;
        width: 100vw;
    }

    .dash-container { display: flex; width: 100vw; height: 100vh; overflow: hidden; }
    .dash-sidebar { 
        width: 260px; 
        background: rgba(4, 7, 14, 0.85); 
        /* Beyaz çizgiyi kaldırdık */
        border-right: none !important; 
        display: flex; 
        flex-direction: column; 
        padding: 40px 25px; 
        justify-content: space-between; 
        flex-shrink: 0; 
        backdrop-filter: blur(20px);
        /* Gölge sabit */
        box-shadow: 5px 0 30px rgba(0,0,0,0.3) !important;
    }  
    .dash-logo-area { font-family: monospace; font-size: 14px; font-weight: 900; color: #fff; letter-spacing: 2px; }
    .dash-logo-area span { color: var(--user-neon) !important; font-weight: 900 !important; text-shadow: 0 0 10px var(--user-glow), 0 0 20px var(--user-glow), 0 0 30px rgba(0, 255, 204, 0.2) !important; }
    .dash-content-body { flex: 1; padding: 40px 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 35px; }

    .user-grid { display: flex; gap: 30px; width: 100%; align-items: flex-start; flex-wrap: wrap; }
    
    /* ADMİN PANELİNDEKİ DERİNLİK EKLENDİ */
    .user-box { 
        flex: 1; min-width: 320px; 
        background: var(--panel-blur-bg) !important; 
        border: 1px solid var(--border-glow) !important; 
        border-radius: 18px; padding: 30px; 
        backdrop-filter: blur(25px) !important; 
        box-sizing: border-box; 
        box-shadow: 0 15px 35px -5px var(--box-shadow-glow), 0 5px 15px -5px rgba(0, 0, 0, 0.5) !important; 
    }
    
    .box-title { font-family: monospace; font-size: 13px; font-weight: 900 !important; color: var(--neon-cyan); letter-spacing: 1.5px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 10px; text-transform: uppercase; text-shadow: 0 0 10px var(--neon-cyan), 0 0 5px rgba(0,255,204,0.3) !important; }
    
    .sidebar-links { display: flex; flex-direction: column; gap: 8px; margin-top: 30px; }
    .panel-tab-btn { background: transparent; border: 1px solid transparent; color: #64748b; font-family: monospace; font-size: 12px; font-weight: 700; padding: 12px 16px; border-radius: 10px; text-align: left; cursor: pointer; transition: all 0.3s ease; letter-spacing: 1px; width: 100%; box-sizing: border-box; }
    .panel-tab-btn:hover { color: #fff; background: rgba(255,255,255,0.02); }
    .panel-tab-btn.active { color: var(--user-neon); border-color: var(--user-glow); background: rgba(0,255,204,0.02); text-shadow: 0 0 8px var(--user-glow); }

    /* BİRLEŞTİRİLMİŞ NEURAL INTERFUSE MİMARİSİ */
    .btn-download {
        background: rgba(6, 15, 30, 0.45) !important;
        border: 1px solid rgba(0, 255, 204, 0.15) !important;
        padding: 10px 20px !important;
        cursor: pointer; position: relative; overflow: hidden;
        width: 100% !important; height: 52px !important;
        display: flex !important; align-items: center !important; justify-content: space-between !important;
        border-radius: 5px !important; box-sizing: border-box !important;
        transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        text-decoration: none !important;
        clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%);
        box-shadow: inset 0 0 12px var(--user-glow) !important;
    }

    .neural-scanner-bar { position: absolute; left: 0; top: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, var(--user-neon), transparent); box-shadow: 0 0 10px var(--user-neon), 0 0 20px var(--user-neon); opacity: 0.4; animation: siberTaramaDovgusu 2.5s ease-in-out infinite; z-index: 3; }
    @keyframes siberTaramaDovgusu { 0% { top: 0%; } 50% { top: 96%; } 100% { top: 0%; } }
    
    .neural-left-zone { display: flex; flex-direction: column; text-align: left; gap: 2px; pointer-events: none; }
    .neural-node-id { font-size: 8px; color: #475569; letter-spacing: 1px; font-family: monospace; }
    .neural-progress-track { width: 45px; height: 3px; background: rgba(255,255,255,0.03); border-radius: 2px; overflow: hidden; }
    .neural-progress-fill { width: 45%; height: 100%; background: var(--user-neon); opacity: 0.5; transition: 0.3s; }
    .neural-center-zone { flex: 1; text-align: center; pointer-events: none; }
    .neural-main-txt { color: #fff !important; font-size: 11px !important; font-weight: 900 !important; letter-spacing: 2px !important; font-family: monospace; text-shadow: 0 1px 3px rgba(0,0,0,0.8); transition: 0.3s; text-transform: uppercase; }
    .neural-right-zone { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; pointer-events: none; }
    .matrix-hex-stream { font-size: 9px; color: #334155; font-weight: bold; letter-spacing: 1px; width: 35px; text-align: right; transition: 0.2s; font-family: monospace; }
    .neural-status-tag { font-size: 7px; color: var(--user-neon); border: 1px solid var(--user-glow); padding: 1px 4px; border-radius: 2px; background: rgba(0,0,0,0.3); letter-spacing: 0.5px; font-family: monospace; font-weight: bold; }

    /* HOVER EFEKTLERİ */
    .btn-download:hover:not([disabled]) { border-color: var(--user-neon) !important; background: rgba(6, 15, 30, 0.85) !important; box-shadow: 0 0 25px var(--user-glow), inset 0 0 15px rgba(0, 255, 204, 0.08) !important; }
    .btn-download:hover:not([disabled]) .neural-main-txt { color: var(--user-neon) !important; text-shadow: 0 0 10px var(--user-neon); }
    .btn-download:hover:not([disabled]) .neural-progress-fill { width: 100%; opacity: 1; box-shadow: 0 0 6px var(--user-neon); }
    .btn-download:hover:not([disabled]) .matrix-hex-stream { color: var(--user-neon); opacity: 0.7; text-shadow: 0 0 4px var(--user-neon); }
    .btn-download:hover:not([disabled]) .neural-scanner-bar { opacity: 1; animation-duration: 1.2s; }
    .btn-download:active:not([disabled]) { transform: scale(0.98); }

    /* KİLİT MEKANİZMASI */
    .btn-download[disabled] { background: rgba(15, 23, 42, 0.2) !important; border: 1px solid rgba(255, 255, 255, 0.03) !important; box-shadow: none !important; text-shadow: none !important; cursor: not-allowed !important; opacity: 0.4 !important; transform: none !important; clip-path: none !important; }
    .btn-download[disabled] .neural-progress-fill, .btn-download[disabled] .neural-scanner-bar { display: none !important; }
    .btn-download[disabled] .neural-main-txt { color: #475569 !important; }
    .btn-download[disabled] .neural-status-tag { color: #475569 !important; border-color: rgba(255,255,255,0.05); }

    /* Profil Formu Modernizasyonu */
    .vaya-form-group { display: flex; flex-direction: column; gap: 6px; }
    .vaya-form-label { color: #64748b; font-size: 11px; letter-spacing: 0.5px; font-family: monospace; }
    .vaya-form-box { display: flex; gap: 10px; align-items: center; background: rgba(2, 6, 23, 0.4); border: 1px solid rgba(255,255,255,0.05); padding: 10px; border-radius: 10px; }

    /* Renk Seçici Düzenlemesi */
    .vaya-color-input { background: transparent; border: none; padding: 0; height: 32px; width: 50px; cursor: pointer; border-radius: 4px; }

    /* Select Menü Düzenlemesi */
    .vaya-select-input { background: rgba(2, 6, 23, 0.5); border: 1px solid rgba(255,255,255,0.05); padding: 12px; color: #fff; font-size: 12px; border-radius: 10px; outline: none; cursor: pointer; width: 100%; box-sizing: border-box; font-family: monospace; box-shadow: inset 0 2px 4px rgba(0,0,0,0.3); }
    .vaya-select-input option { background: #04070e; color: #fff; }

    .cheat-info-text { color: #cbd5e1; font-family: monospace; font-size: 12px; line-height: 1.6; margin-bottom: 20px; }
    .tab-content { display: none !important; width: 100%; }
    .tab-active {
    display: block !important;
    }
    .hidden-tab { display: none !important; }
    #status-monitor {
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.2);
    backdrop-filter: blur(5px);
    }
    /* Başlık Stilleri - Çizgisiz ve Sadece Neon */
    .neon-title {
    /* Yazı rengi: Profil rengin */
    color: var(--user-neon) !important;
    
    text-shadow: none !important; /* Bloom (yayılma) bitti */
    
    /* Animasyonu çok daha yavaş ve yumuşak yapalım (dikkat dağıtmasın) */
    animation: neon-pulse 4s infinite alternate;
    
    /* Yazının netliği için karakter aralığını biraz açalım */
    letter-spacing: 2px;
}

/* Animasyonu da yumuşatalım */
@keyframes neon-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
.user-card {
    border: 1px solid var(--user-neon) !important;
    border-radius: 10px; /* Köşeleri biraz yuvarlatırsan daha şık durur */
    background: rgba(255, 255, 255, 0.02); /* İçine hafif bir saydamlık verelim ki border kendini belli etsin */
    padding: 15px;
    transition: border 0.3s ease; /* Renk değişirse yumuşak geçiş yapsın */
}
/* Profil avatarını bir çerçeveye hapseden CSS */
.user-avatar-img {
    width: 150px;       /* İstediğin boyuta çekebilirsin */
    height: 150px;      /* Kare olması için width ile aynı yap */
    object-fit: cover;  /* Resim uzamasın, ortalayarak sığdırsın */
    border-radius: 50%; /* Yuvarlak profil için */
    border: 3px solid #00ffcc; /* Siber neon çerçeve */
    display: block;
    margin: 10px auto;  /* Ortala */
}




.btn-tab {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-glow);
    color: #fff;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 8px;
    font-family: monospace;
}
.btn-tab.active {
    background: var(--user-neon);
    color: #000;
    font-weight: bold;
}
.tab-content-item {
    background: var(--panel-blur-bg);
    padding: 30px;
    border-radius: 18px;
    border: 1px solid var(--border-glow);
}

</style>
</head>
<body>
    <canvas id="cosmicCanvas"></canvas>
    <div class="nebula-overlay"></div>

    <div class="dash-container">
        <!-- SOL SİBER SİDEBAR -->
        <aside class="dash-sidebar">
            <div>
                <div class="dash-logo-area">VAYACHEATS // <span>CLIENT</span></div>
                
                <!-- Müşteri Kimlik Alanı (Sıfır Kayma & Kırık Resim Kalkanı) -->
                <div class="user-card" style="display: flex; align-items: center; gap: 15px; margin-top: 30px; background: rgba(255,255,255,0.01); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.03); font-family: monospace;">
    
                    <div style="font-size: 24px; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; min-width: 42px; position: relative;">
                        
                        <?php if (!empty($user_photo)): ?>
                            <img src="<?php echo htmlspecialchars($user_photo); ?>" 
                                style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; box-shadow: 0 0 10px var(--user-glow); border: 1px solid var(--user-neon);">
                        <?php else: ?>
                            <span style="font-size: 24px;"><?php echo htmlspecialchars($user_avatar); ?></span>
                        <?php endif; ?>
                        
                    </div>

                    <div style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                        <div style="font-size: 10px; color: #64748b;">Hoş geldin,</div>
                        <div style="font-size: 14px; font-weight: bold; color: #fff;"><?php echo htmlspecialchars($username); ?></div>
                    </div>
                </div>

                <div class="sidebar-links">
                    <button class="panel-tab-btn" onclick="if(this.classList.contains('active')) return; switchUserTab('tab-main', this);">ANA MERKEZ</button>
                    <button class="panel-tab-btn" onclick="switchUserTab('tab-cheats', this)">⚡ CHEATS & SPOOFER</button>
                    <button class="panel-tab-btn" onclick="showSection('tab-profile', this)">⚙ PROFILE SYSTEM</button>
                </div>
            </div>
            <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: 12px; color: #f87171; border: 1px solid rgba(248,113,113,0.1); text-decoration: none; font-family: monospace; font-size: 12px; font-weight: 700;">➔ SİSTEMDEN ÇIK</a>
        </aside>

        <!-- SAĞ İÇERİK ALANI GÖVDESİ -->
        <main class="dash-content-body">
            <header class="dash-header-area" style="font-family: monospace; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
    
                <div>
                    <div style="font-size: 11px; color: var(--user-neon); letter-spacing: 2px;">// CLIENT_SECURE_INTERFACE_NODE</div>
                    <h1 style="font-size: 28px; font-weight: 900; color: #fff; margin-top: 5px; margin-bottom: 0;">Müşteri Komuta Merkezi</h1>
                </div>

                <div id="status-monitor" style="background: rgba(var(--neon-rgb), 0.1); border: 1px solid var(--user-neon); padding: 8px 15px; border-radius: 6px; text-align: right; color: var(--user-neon);">
                    <div style="font-size: 9px; opacity: 0.8; letter-spacing: 1px;">LİSANS AKTİF SÜRE</div>
                    <div id="live-timer" style="font-size: 14px; font-weight: bold; color: #fff; margin-top: 2px;">HESAPLANIYOR...</div>
                </div>
                
            </header>

            <!-- 1. TAB: ANA MERKEZ (NET VE KESKİN ARKA PLAN) -->
            <div id="tab-main" class="tab-content">
                <div class="user-box" style="max-width: 100%; width: 100%;">
                    <div class="box-title neon-title">[ ❖ VAYACHEATS CORESYNC TERMINAL ]</div>
                    <p class="cheat-info-text" style="font-size: 13px;">
                        Siber ağ bağlantınız başarıyla mühürlendi. Kuantum kalkanlarımız ve Ring 0 enjeksiyon modüllerimiz, sisteminizin oyun turnuva korumalarını harici simülasyonlar ile etkisiz bırakması için aktif durumda nöbet tutuyor.<br><br>
                        > LİSANS SAHİBİ : <span style="color:#fff; font-weight:bold;"><?php echo htmlspecialchars($username); ?></span><br>
                        > ERİŞİM ROLÜ   : <span style="color:#fff; font-weight:bold;"><?php echo htmlspecialchars($user_role); ?></span><br>
                        > AKILLI DURUM  : <span style="color:#10b981; font-weight:bold;">AĞA BAĞLI (CONNECTED)</span><br>
                    </p>
                </div>
            </div>
                        <!-- 2. TAB: CHEATS & KERNEL SPOOFER (NEURAL INTERFUSE PARÇA - 1) -->
            <div id="tab-cheats" class="tab-content hidden-tab" style="display: none;">
                <div class="user-grid">
                    <?php
                    try {
                        $hileSorgu = $db->query("SELECT * FROM hileler ORDER BY id DESC");
                        $hileler = $hileSorgu->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) { $hileler = []; }

                    if (count($hileler) > 0) {
                        foreach ($hileler as $hile) {
                            $neonColor = '#10b981';
                            if ($hile['durum'] === 'DETECTED') $neonColor = '#f43f5e';
                            if ($hile['durum'] === 'BAKIMDA') $neonColor = '#eab308';
                    ?>
                            <!-- ⚡ BİREBİR KESKİN ARKA PLANLI HİLE KUTUSU -->
                            <div class="user-box" style="max-width: 380px; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-family: monospace;">
                                        <span style="font-weight: bold; color: #fff; font-size: 13px;"><?php echo htmlspecialchars($hile['hile_adi']); ?></span>
                                        <span style="font-size: 10px; font-weight: 900; color: <?php echo $neonColor; ?>; border: 1px solid <?php echo $neonColor; ?>; padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.3);"><?php echo htmlspecialchars($hile['durum']); ?></span>
                                    </div>
                                    <p class="cheat-info-text">
                                        Sürücü Altyapısı: <?php echo htmlspecialchars($hile['koruma']); ?><br>
                                        // Modül kuantum ağımız üzerinden enjeksiyona hazırdır gadaşım.
                                    </p>
                                </div>
                                
                                <!-- ⚡ UNIFORM NEURAL INTERFUSE BUTON YAPISI -->
                                <div style="width: 100%;">
                                    <?php if (!empty($userData['bitis_tarihi']) && strtotime($userData['bitis_tarihi']) >= time()) { 
                                        if ($hile['durum'] === 'UNDETECTED' && !empty($hile['dosya_yolu'])) { ?>
                                            
                                            <!-- ⚡ KUSURSUZ NEURAL INTERFUSE HİLE ENJEKSİYON GEÇİDİ -->
                                            <a href="indir.php?hile_id=<?php echo $hile['id']; ?>" class="btn-download" onclick="if(typeof startInjectionCore === 'function') { startInjectionCore('<?php echo htmlspecialchars($hile['aranacak_kelime']); ?>'); }">
                                                <div class="neural-scanner-bar"></div>
                                                <div class="neural-left-zone">
                                                    <span class="neural-node-id">SYNC_042</span>
                                                    <div class="neural-progress-track"><div class="neural-progress-fill"></div></div>
                                                </div>
                                                <div class="neural-center-zone">
                                                    <span class="neural-main-txt">AĞA ENJEKTE ET</span>
                                                </div>
                                                <div class="neural-right-zone">
                                                    <div class="matrix-hex-stream data-live-slots">1010</div>
                                                    <span class="neural-status-tag">READY</span>
                                                </div>
                                            </a>

                                        <?php } else { ?>
                                            <!-- Kilitli Durum Hücresi -->
                                            <button class="btn-download" disabled style="opacity: 0.4; cursor: not-allowed;">
                                                <div class="neural-center-zone"><span class="neural-main-txt" style="color: #475569 !important;">ERİŞİLEMEZ</span></div>
                                                <div class="neural-right-zone"><span class="neural-status-tag" style="color: #475569 !important; border-color: rgba(255,255,255,0.05);">LOCKED</span></div>
                                            </button>
                                        <?php } 
                                    } else { ?>
                                        <!-- Süre Yetersiz Hücresi -->
                                        <button class="btn-download" disabled style="opacity: 0.4; cursor: not-allowed;">
                                            <div class="neural-center-zone"><span class="neural-main-txt" style="color: #475569 !important;">SÜRE YETERSİZ</span></div>
                                            <div class="neural-right-zone"><span class="neural-status-tag" style="color: #475569 !important; border-color: rgba(255,255,255,0.05);">LOCKED</span></div>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                    <?php 
                        } 
                    } else {
                        echo '<div class="user-box" style="width:100%;"><p class="cheat-info-text">// BULUT SEVİYESİNDE AKTİF MODÜL BULUNAMADI.</p></div>';
                    } 
                    ?>
                    <!-- KERNEL HARDWARE SPOOFER (GİZLİ SİBER RULET KAMUFLAJI BURAYA GELDİ GADAŞIM 🎰) -->
                    <div class="user-box" style="max-width: 380px; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px; position: relative !important;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-family: monospace;">
                                <span style="font-weight: bold; color: #fff; font-size: 13px;">KERNEL // HARDWARE SPOOFER</span>
                                <span style="font-size: 10px; font-weight: 900; color: #cbd5e1; border: 1px solid rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.3);">KERNEL LEVEL</span>
                            </div>
                            <p class="cheat-info-text">Anakart, Disk (VolumeID, Serial), MAC Adresi ve SMBIOS numaralarını tamamen sahteleriyle değiştirerek cihaz banını (HWID) tek tıkla ortadan kaldırır.</p>
                        </div>
                        <div style="width: 100%;">
                            <?php if (!empty($userData['bitis_tarihi']) && strtotime($userData['bitis_tarihi']) >= time()): ?>
                                
                                <!-- Bu spoofer butonu normalde düz neural interfuse gibi duracak ama 5'li komboda çıldıracak gadaşım! -->
                                <button id="stealthDecryptorBtn" class="btn-download" style="border-color: #f43f5e !important;">
                                    <div class="decryptor-scan-line" id="vayaScanLine"></div>
                                    <div class="neural-left-zone">
                                        <span class="neural-node-id">HWID_042</span>
                                        <div class="neural-progress-track"><div class="neural-progress-fill" style="background:#f43f5e !important;"></div></div>
                                    </div>
                                    <div class="neural-center-zone">
                                        <span class="neural-main-txt" id="vayaDecryptorTxt" style="color:#f43f5e !important;">TEMİZLİĞİ BAŞLAT</span>
                                    </div>
                                    <div class="decryptor-bit-matrix" style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px; pointer-events: none;">
                                        <div class="matrix-slots" style="display: flex; gap: 2px;">
                                            <span class="slot" id="slot1" style="font-size: 8px; width: 10px; height: 14px; background:#000; border:1px solid rgba(244,63,94,0.2); color:#f43f5e;">0</span>
                                            <span class="slot" id="slot2" style="font-size: 8px; width: 10px; height: 14px; background:#000; border:1px solid rgba(244,63,94,0.2); color:#f43f5e;">0</span>
                                            <span class="slot" id="slot3" style="font-size: 8px; width: 10px; height: 14px; background:#000; border:1px solid rgba(244,63,94,0.2); color:#f43f5e;">0</span>
                                        </div>
                                        <span class="matrix-tag" id="vayaMatrixTag" style="font-size: 6px; color: #475569;">STANDBY</span>
                                    </div>
                                </button>

                            <?php else: ?>
                                <button class="btn-download" disabled>
                                    <div class="neural-center-zone"><span class="neural-main-txt">SÜRE YETERSİZ</span></div>
                                    <div class="neural-right-zone"><span class="neural-status-tag">LOCKED</span></div>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 🔥 JACKPOT VURULDUĞUNDA SİNEMA EFEKTİYLE SAKLI SIZACAK SENSE SEÇİM PANELİ -->
                <div id="gamblerChoiceStation" style="display: none; margin: 20px auto 0 auto; font-family: monospace; width: 100%; max-width: 380px; box-sizing: border-box;">
                    <div style="font-size: 10px; color: #00ffcc; text-shadow: 0 0 8px rgba(0,255,204,0.4); margin-bottom: 12px; text-align: left; letter-spacing: 1px;">
                        >> JACKPOT_DETECTED // SKOR: <span id="jackpotScoreTrack" style="font-weight: bold; color: #fff;">1</span>
                    </div>
                    <div style="display: flex; gap: 15px; width: 100%;">
                        <button onclick="claimSiberAccess()" class="choice-btn claim-btn" style="flex: 1; height: 38px; background: rgba(6, 15, 30, 0.6); color: #fff; border: 1px solid rgba(0,255,204,0.2); font-family: monospace; font-size: 10px; font-weight: 900; cursor: pointer; border-radius: 4px;">> LİSANSI AKTİF ET</button>
                        <button onclick="gambleAgain()" class="choice-btn retry-btn" style="flex: 1; height: 38px; background: rgba(6, 15, 30, 0.6); color: #fff; border: 1px solid rgba(168,85,247,0.2); font-family: monospace; font-size: 10px; font-weight: 900; cursor: pointer; border-radius: 4px;">🎰 ŞANSINI TEKRAR DENE</button>
                    </div>
                </div>
            </div>

                        <div id="tab-profile" class="tab-content" style="display: none;">
                            
                            <div class="vaya-tab-nav" style="display: flex; gap: 15px; margin-bottom: 25px;">
                                <button onclick="switchTab('genel')" class="btn-tab active">GENEL</button>
                                <button onclick="switchTab('istatistik')" class="btn-tab">İSTATİSTİK</button>
                                <button onclick="switchTab('ayarlar')" class="btn-tab">AYARLAR</button>
                            </div>

                            <div id="genel" class="tab-content-item">
                                <div style="display: flex; gap: 40px; align-items: center;">
                                    <div style="font-size: 80px;"><?php echo $user_avatar; ?></div>
                                    <div>
                                        <h2 style="color: var(--user-neon);">OPERATÖR: <?php echo $username; ?></h2>
                                        <p>Kayıtlı HWID: <?php echo $user_hwid; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            </div>
        </main>
    </div>
<script>
function switchTab(tabId) {
    // Profil içi alt sekmeleri yönetir
    document.querySelectorAll('.tab-content-item').forEach(item => item.style.display = 'none');
    document.querySelectorAll('.btn-tab').forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabId).style.display = 'block';
    // Aktif olan butonu bul ve renklendir (basit bir yaklaşım)
    event.currentTarget.classList.add('active');
}
</script>
    <!-- ⚡ VAYACHEATS TIKIR TIKIR ÇALIŞAN GÜVENLİ JS MOTORU -->
    <script>
// ⚡ BYPASS MOTORU: SEKME ÇAKIŞMALARINI KÖKTEN ERİTEN SİBER KALKAN ⚡
function switchUserTab(tabId, element) {
    try {
        const targetTab = document.getElementById(tabId);
        if (!targetTab) {
            console.warn("Siber Kalkan: Hedef sekme bulunamadı (" + tabId + ")");
            return; // Hata verme, sadece dur.
        }

        // 1. Tüm içerik sekmelerini gizle
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.setProperty('display', 'none', 'important');
        });

        // 2. Butonların aktifliğini resetle
        document.querySelectorAll('.panel-tab-btn').forEach(btn => btn.classList.remove('active'));
        
        // 3. Hedef sekmeyi görünür yap
        targetTab.style.setProperty('display', targetTab.classList.contains('user-grid') ? 'flex' : 'block', 'important');
        
        // 4. Aktif buton ışığını yak
        if (element) element.classList.add('active');

    } catch (err) {
        console.error("VayaCheats Kalkanı Çöktü: ", err);
    }
}
document.addEventListener("DOMContentLoaded", function() {
    // Önce ana merkez butonu var mı kontrol et
    const mainBtn = document.querySelector('.panel-tab-btn'); 
    const mainTab = document.getElementById('tab-main');

    if (mainBtn && mainTab) {
        switchUserTab('tab-main', mainBtn);
    } else {
        console.error("Kritik: Ana sekme veya buton DOM'da bulunamadı!");
    }
});

// Sayfa yüklenirken parlamayı önle
document.write('<style>.tab-content { display: none !important; }</style>');

        function startSpooferCore() {
            const terminal = document.getElementById('spoofer-terminal');
            const btn = document.getElementById('vaya-spoofer-btn');
            if (!terminal || !btn) return;
            btn.disabled = true; btn.innerText = "SİSTEMLER SIFIRLANIYOR..."; btn.style.opacity = "0.5";
            terminal.style.display = "block"; terminal.innerHTML = ""; 
            const randSerial = () => Math.random().toString(36).substring(2, 10).toUpperCase() + "-" + Math.random().toString(36).substring(2, 6).toUpperCase();
            const logs = [
                { text: ">> [SYSTEM] Vayacheats Kuantum Çekirdek Sürücüsü başlatılıyor...", delay: 0, color: "#38bdf8" },
                { text: ">> [KERNEL] Ring 0 katmanına sızılıyor... Başarılı.", delay: 500, color: "#10b981" },
                { text: `>> [SPOOF] Anakart yeni seri enjekte edildi: VAYA-${randSerial()}`, delay: 1200, color: "#10b981" },
                { text: `>> [SPOOF] Disk Serial Shuffled! -> [ Serial: WDS-${randSerial()} ]`, delay: 2000, color: "#10b981" },
                { text: ">> [NETWORK] MAC adresi spoofed successfully.", delay: 2800, color: "#10b981" },
                { text: ">> [SUCCESS] DONANIM BANINIZ (HWID) BAŞARIYLA ORTADAN KALDIRILDI!", delay: 3500, color: "#10b981" },
                { text: ">> [ALERT] Lütfen BİLGİSAYARINIZI YENİDEN BAŞLATIN gadaşım.", delay: 4000, color: "#fff" }
            ];
            logs.forEach(log => {
                setTimeout(() => {
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = audioCtx.createOscillator(); osc.type = "sine"; osc.frequency.setValueAtTime(600, audioCtx.currentTime);
                        osc.connect(audioCtx.destination); osc.start(); osc.stop(audioCtx.currentTime + 0.02);
                    } catch(e){}
                    terminal.innerHTML += `<div style="color: ${log.color};">${log.text}</div>`;
                    terminal.scrollTop = terminal.scrollHeight;
                    if (log === logs[logs.length - 1]) {
                        btn.innerText = "TEMİZLİK BAŞARILI ✓"; btn.style.background = "linear-gradient(135deg, #10b981, #059669) !important"; btn.style.opacity = "1";
                    }
                }, log.delay);
            });
        }

        const chatBox = document.getElementById('vaya-chat-box');
        const chatInput = document.getElementById('vaya-chat-input');
        const currentOperator = "<?php echo $_SESSION['username']; ?>";
        const currentAvatar = "<?php echo $user_avatar; ?>";

        function loadVayaChat() {
            if (!chatBox) return;
            let messages = JSON.parse(localStorage.getItem('vaya_global_chat')) || [
                { sender: "VayaBot", avatar: "🤖", text: "Siber ağa hoş geldiniz. Kanala güvenli veri akışı sağlandı.", time: "11:58" }
            ];
            chatBox.innerHTML = "";
            messages.forEach(msg => {
                chatBox.innerHTML += `<div><span style="color:#64748b;">[${msg.time}]</span> ${msg.avatar} <strong style="color:var(--user-neon);">${msg.sender}:</strong> <span style="color:#cbd5e1;">${msg.text}</span></div>`;
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        // ⚡ BUTTON BİMARY DATA STREAM GENERATOR
        setInterval(() => {
            document.querySelectorAll('.data-live-slots').forEach(slot => {
                let randBinary = "";
                for(let i=0; i<4; i++) {
                    randBinary += Math.floor(Math.random() * 2).toString();
                }
                slot.innerText = randBinary;
            });
        }, 250); // Her 250 milisaniyede bir hile butonlarındaki kodlar aksın gadaşım!


        function sendVayaGlobalMessage() {
            if (!chatInput || !chatInput.value.trim()) return;
            let messages = JSON.parse(localStorage.getItem('vaya_global_chat')) || [];
            let now = new Date(); let timeStr = now.getHours().toString().padStart(2, '0') + ":" + now.getMinutes().toString().padStart(2, '0');
            messages.push({ sender: currentOperator, avatar: currentAvatar, text: chatInput.value.trim(), time: timeStr });
            if(messages.length > 50) messages.shift();
            localStorage.setItem('vaya_global_chat', JSON.stringify(messages));
            chatInput.value = ""; loadVayaChat();
        }

        if(chatInput) { chatInput.addEventListener("keypress", (e) => { if (e.key === "Enter") sendVayaGlobalMessage(); }); }
        document.addEventListener("DOMContentLoaded", () => { loadVayaChat(); setInterval(loadVayaChat, 2000); });



let secretClicks = 0;
let jackpotChain = 0;

const btn = document.getElementById('stealthDecryptorBtn');

if (btn) {

    btn.addEventListener('click', unlockSecretMode);

    function unlockSecretMode() {

        secretClicks++;

        const txt = document.getElementById('vayaDecryptorTxt');
        const tag = document.getElementById('vayaMatrixTag');

        if (secretClicks < 5) {

            txt.textContent = `SCAN ${secretClicks}/5`;
            tag.textContent = 'STANDBY';

            setTimeout(() => {
                txt.textContent = 'TEMİZLİĞİ BAŞLAT';
            }, 300);

            return;
        }

        btn.removeEventListener('click', unlockSecretMode);

        txt.textContent = 'JACKPOT MODE';
        tag.textContent = 'UNLOCKED';

        setTimeout(() => {
            startSpooferSlots();
        }, 600);
    }

    function startSpooferSlots() {

        const slot1 = document.getElementById('slot1');
        const slot2 = document.getElementById('slot2');
        const slot3 = document.getElementById('slot3');

        const txt = document.getElementById('vayaDecryptorTxt');
        const tag = document.getElementById('vayaMatrixTag');

        btn.disabled = true;

        txt.textContent = 'SPINNING';
        tag.textContent = 'JACKPOT MODE';

        let count = 0;

        const interval = setInterval(() => {

            slot1.textContent = Math.floor(Math.random() * 10);
            slot2.textContent = Math.floor(Math.random() * 10);
            slot3.textContent = Math.floor(Math.random() * 10);

            count++;

            if (count >= 30) {

                clearInterval(interval);

                const jackpot = Math.random() < 0.20;

                if (jackpot) {

                    slot1.textContent = '7';
                    slot2.textContent = '7';
                    slot3.textContent = '7';

                    txt.textContent = 'JACKPOT';
                    tag.textContent = 'ACCESS GRANTED';

                    jackpotChain++;

                    const score =
                        document.getElementById('jackpotScoreTrack');

                    if (score) {
                        score.textContent = jackpotChain;
                    }

                    document.getElementById(
                        'gamblerChoiceStation'
                    ).style.display = 'block';

                } else {

                    slot1.textContent = '0';
                    slot2.textContent = '0';
                    slot3.textContent = '0';

                    txt.textContent = 'TRY AGAIN';
                    tag.textContent = 'FAILED';

                    btn.disabled = false;
                }
            }

        }, 100);
    }

    window.gambleAgain = function () {

        document.getElementById(
            'gamblerChoiceStation'
        ).style.display = 'none';

        btn.disabled = false;

        startSpooferSlots();
    };

    window.claimSiberAccess = function () {

        jackpotChain = 0;

        document.getElementById(
            'gamblerChoiceStation'
        ).style.display = 'none';

        const txt = document.getElementById('vayaDecryptorTxt');
        const tag = document.getElementById('vayaMatrixTag');

        txt.textContent = 'SYSTEM READY';
        tag.textContent = 'LICENSE ACTIVE';

        alert('HWID temizleme simülasyonu başlatıldı.');

        btn.disabled = false;
    };

}
function updateTimer() {
    if (remainingSeconds <= 0) {
        document.getElementById('live-timer').innerText = "LİSANS SÜRESİ DOLDU!";
        return;
    }

    let d = Math.floor(remainingSeconds / 86400);
    let h = Math.floor((remainingSeconds % 86400) / 3600);
    let m = Math.floor((remainingSeconds % 3600) / 60);
    let s = remainingSeconds % 60;

    document.getElementById('live-timer').innerText = 
        `${d} Gün, ${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;

    remainingSeconds--;
}

// Her saniye motoru tetikle
setInterval(updateTimer, 1000);
// İlk açılışta hemen başlat
updateTimer();
    </script>
</body>
</html>
