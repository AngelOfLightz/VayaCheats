<?php
// Sayfanın önbelleğe alınmasını kesin olarak engelleyen header kodları
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config.php';
$error_msg = ""; $success_msg = "";

// Eğer session başlatılmadıysa otomatik başlat (Giriş yapabilmek için şarttır)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true); // BU KOMUT ESKİ OTURUMU ÖLDÜRÜR, YENİSİNİ AÇAR!
// 1. ADAMIN SENDEKİ LİSANS KEYİ İLE HESAP AÇMA MOTORU
if (isset($_POST['register_action'])) {
    $user = strip_tags(trim($_POST['reg_username']));
    $pass = trim($_POST['reg_password']);
    $key  = trim($_POST['reg_key']);

    if (!empty($user) && !empty($pass) && !empty($key)) {
        // Müşterinin yazdığı Key senin ürettiğin aktif Key'ler arasında var mı?
        $checkKey = $db->prepare("SELECT * FROM lisanslar WHERE lisans_key = ? AND durum = 'aktif'");
        $checkKey->execute([$key]);
        
        if ($checkKey->rowCount() > 0) {
            $checkUser = $db->prepare("SELECT * FROM kullanicilar WHERE username = ?");
            $checkUser->execute([$user]);
            
            if ($checkUser->rowCount() == 0) {
                $hashed_pass = md5($pass);                
                $keyData = $checkKey->fetch(PDO::FETCH_ASSOC);
                $key_day = (int)($keyData['sure_gun'] ?? 30);

                $default_sure = 0; // Süre girmek istemiyorsan 0 yap
                // HESAP AÇMA MOTORU - GÜNCELLENMİŞ SORGU
                // Sadece zorunlu alanları gönderiyoruz, diğerleri veritabanı default ayarlarını kullansın
                $insert = $db->prepare("INSERT INTO kullanicilar (username, password, role) VALUES (?, ?, 'vip')");
                $insert->execute([$user, $hashed_pass]);
                
                // Key artık kullanıldı, başkası giremez!
                $updateKey = $db->prepare("UPDATE lisanslar SET durum = 'kullanildi' WHERE lisans_key = ?");
                $updateKey->execute([$key]);
                
                $success_msg = "// KEY_VALIDATED: Hesap başarıyla enjekte edildi. Giriş yapabilirsiniz.";
            } else { $error_msg = "// ERROR: Bu kod adı siber ağda zaten kayıtlı."; }
        } else { $error_msg = "// ERROR: Geçersiz veya kullanılmış lisans anahtarı!"; }
    } else { $error_msg = "// WARNING: Boş alan bırakmayın."; }
}

// 2. GİRİŞ YAPMA MOTORU (BUG DÜZELTİLDİ 🌟)
if (isset($_POST['login_action'])) {
    $user = strip_tags(trim($_POST['login_username']));
    $pass = trim($_POST['login_password']);

    if (!empty($user) && !empty($pass)) {
        $query = $db->prepare("SELECT * FROM kullanicilar WHERE username = ?");
        $query->execute([$user]);
        $userData = $query->fetch(PDO::FETCH_ASSOC);

        if ($userData && md5($pass) === $userData['password']) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];

            // BENİ HATIRLA MOTORU:
            if (isset($_POST['remember_me'])) {
                setcookie('remember_user', $user, time() + (86400 * 30), "/"); // 30 Gün
            } else {
                setcookie('remember_user', '', time() - 3600, "/"); // Çerezi sil
            }
            // Siber yönlendirme istasyonu
            if ($userData['role'] === 'admin') {
                header("Location: admin.php");
                exit;
            } elseif ($userData['role'] === 'vip') {
                header("Location: user.php");
                exit;
            } else {
                echo "<script>alert('Hesabınız siber ağdan uzaklaştırılmıştır (BANNED)!'); window.location.href='auth.php';</script>";
                exit;
            }
        } else {
            $error_msg = "// ERROR: Geçersiz kullanıcı adı veya şifre.";
        }
    } else {
        $error_msg = "// WARNING: Boş alan bırakmayın.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title>VayaCheats // Terminal Access</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .auth-container { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        
        .auth-box { 
            background: var(--panel-blur-bg); 
            border: 1px solid var(--border-glow); 
            backdrop-filter: blur(30px); 
            -webkit-backdrop-filter: blur(30px);
            padding: 45px 50px; 
            border-radius: 24px; 
            width: 460px; 
            box-shadow: 0 40px 80px rgba(0,0,0,0.6); 
        }
        
        .auth-title { 
            font-family: monospace; 
            font-size: 12px; 
            font-weight: 800; 
            color: var(--neon-cyan); 
            letter-spacing: 2px; 
            margin-bottom: 25px; 
            border-bottom: 1px solid rgba(255,255,255,0.04);
            padding-bottom: 12px;
        }
        
        .msg-display { 
            font-family: monospace; 
            font-size: 11px; 
            margin-bottom: 20px; 
            font-weight: 700; 
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
        }
        .err-txt { color: #f87171; border: 1px solid rgba(248,113,113,0.1); } 
        .succ-txt { color: var(--neon-cyan); border: 1px solid rgba(0,255,204,0.1); }
        .cosmic-form {
            display: flex !important;
            flex-direction: column !important;
            gap: 18px !important; 
        }

        .cosmic-input-group input {
            color: #ffffff !important;
            font-weight: 600 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 16px 24px !important; 
            font-size: 14px !important;
            border-radius: 50px !important;
            width: 100% !important;
            outline: none !important;
            transition: all 0.3s ease !important;
        }
        .cosmic-input-group input::placeholder { color: #94a3b8 !important; opacity: 1 !important; }
        .cosmic-input-group input:focus { border-color: var(--neon-cyan) !important; box-shadow: 0 0 15px rgba(0, 255, 204, 0.2) !important; background: rgba(0,0,0,0.6) !important; }

        .btn-cosmic-submit {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 52px !important; 
            padding: 0 25px !important;
            font-size: 13px !important;
            font-weight: 900 !important;
            letter-spacing: 1px !important;
            text-transform: uppercase !important;
            border-radius: 50px !important;
            cursor: pointer !important;
            background: linear-gradient(135deg, var(--neon-purple), #7300cc) !important;
            color: #fff !important;
            border: none !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
            margin-top: 5px !important;
            box-shadow: 0 4px 15px rgba(144,0,255,0.2) !important;
        }
        .btn-cosmic-submit:hover { transform: translateY(-1px) !important; box-shadow: 0 6px 20px rgba(144,0,255,0.4) !important; }

        .toggle-link { 
            display: block; 
            text-align: center; 
            margin-top: 25px; 
            font-family: monospace; 
            font-size: 11px; 
            color: var(--text-dim); 
            text-decoration: none; 
            font-weight: 700; 
            transition: all 0.3s ease; 
            cursor: pointer; 
            letter-spacing: 0.5px;
        }
        .toggle-link:hover { color: var(--neon-cyan); text-shadow: 0 0 8px rgba(0,255,204,0.4); }
        .hidden-form { display: none; }
        
        a.btn-auth-back-portal, 
        a.btn-auth-back-portal:link, 
        a.btn-auth-back-portal:visited {
            display: inline-block !important;
            text-decoration: none !important;
            font-family: monospace !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            color: #cbd5e1 !important; 
            letter-spacing: 1px !important;
            margin-bottom: 20px !important;
            transition: all 0.3s ease !important;
            opacity: 0.8 !important;
            text-shadow: none !important;
        }

        a.btn-auth-back-portal:hover, 
        a.btn-auth-back-portal:active {
            color: #ffffff !important; 
            opacity: 1 !important;
            text-shadow: 0 0 10px rgba(144, 0, 255, 0.9) !important; 
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-box">
        <a href="/" class="btn-auth-back-portal">&lt; BACK_TO_PORTAL</a>
        
        <?php if(!empty($error_msg)): ?>
            <div class="msg-display err-txt"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($success_msg)): ?>
            <div class="msg-display succ-txt"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <!-- GİRİŞ FORMU -->
        <div id="login-form-block">
            <div class="auth-title">// SYS_AUTH_REQUEST (GİRİŞ)</div>
            <form action="" method="POST" class="cosmic-form">
                <div class="cosmic-input-group">
                    <input type="text" name="login_username" value="<?php echo $_COOKIE['remember_user'] ?? ''; ?>" placeholder="KOD ADI (USERNAME)" required>
                </div>
                <div class="cosmic-input-group" style="position: relative;">
                    <input type="password" name="login_password" id="login_pass" placeholder="ERİŞİM ŞİFRESİ (PASSWORD)" required>
<span id="eye-icon" onclick="togglePassword()" style="position: absolute; right: 12px;     /* Biraz daha içeride dursun */top: 50%;        /* Kutunun tam dikey ortasına yerleştir */transform: translateY(-50%); /* Kendi merkezine göre tam ortaya çek */padding: 10px;   /* Hitbox genişliği */cursor: pointer; color: var(--neon-cyan);display: flex; align-items: center;justify-content: center;">                        <svg id="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"></path>
                        </svg>
                        <svg id="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>

                <div style="margin: -5px 0 5px 10px; font-family: monospace; font-size: 11px; color: #aaa; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember_me" <?php if(isset($_COOKIE['remember_user'])) echo 'checked'; ?> style="width: auto;"> Beni Hatırla
                </div>
                
                <button type="submit" name="login_action" class="btn-cosmic-submit">AĞA BAĞLAN</button>
            </form>
            <div class="toggle-link" onclick="toggleForm()">[ SİBER AĞA KAYIT OL ]</div>
        </div>

        <!-- KAYIT FORMU -->
        <div id="register-form-block" class="hidden-form">
            <div class="auth-title">// SYS_REG_INITIALIZE (KAYIT)</div>
            <form action="" method="POST" class="cosmic-form">
                <div class="cosmic-input-group">
                    <input type="text" name="reg_username" placeholder="YENİ KOD ADI" required>
                </div>
                <div class="cosmic-input-group">
                    <input type="password" name="reg_password" placeholder="YENİ ŞİFRE" required>
                </div>
                <div class="cosmic-input-group">
                    <input type="text" name="reg_key" placeholder="LİSANS ANAHTARI (KEY)" required>
                </div>
                <button type="submit" name="register_action" class="btn-cosmic-submit">LİSANSI ENJEKTE ET</button>
            </form>
            <div class="toggle-link" onclick="toggleForm()">[ MEVCUT ERİŞİM ANAHTARI İLE GİRİŞ ]</div>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    var loginBlock = document.getElementById('login-form-block');
    var regBlock = document.getElementById('register-form-block');
    
    if(loginBlock.classList.contains('hidden-form')) {
        loginBlock.classList.remove('hidden-form');
        regBlock.classList.add('hidden-form');
    } else {
        loginBlock.classList.add('hidden-form');
        regBlock.classList.remove('hidden-form');
    }
}
function togglePassword() {
    var input = document.getElementById("login_pass");
    var closedIcon = document.getElementById("eye-closed"); // Üzerinde çizgi olan
    var openIcon = document.getElementById("eye-open");     // Normal göz

    if (input.type === "password") {
        // Şifreyi göster (Gözü aç)
        input.type = "text";
        closedIcon.style.display = "none"; // Kapalıyı gizle
        openIcon.style.display = "block";  // Açık gözü göster
    } else {
        // Şifreyi gizle (Gözü kapat)
        input.type = "password";
        closedIcon.style.display = "block"; // Kapalıyı göster
        openIcon.style.display = "none";    // Açık gözü gizle
    }
}
// ⚡ SAYFA YÜKLENDİĞİNDE GÖZÜN DURUMUNU EŞLE:
window.onload = function() {
    var input = document.getElementById("login_pass");
    var closedIcon = document.getElementById("eye-closed");
    var openIcon = document.getElementById("eye-open");

    // Eğer input hala şifre modundaysa, kapalı gözü göster
    if (input.type === "password") {
        closedIcon.style.display = "block";
        openIcon.style.display = "none";
    }
};
</script>
</body>
</html>
