<?php
require_once 'config.php';
require_once 'auth_check.php';

$oturum_gecerli = false;
$kullanici_adi = "";

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $checkUser = $db->prepare("SELECT bitis_tarihi, role FROM kullanicilar WHERE id = ? LIMIT 1");
    $checkUser->execute([$uid]);
    $user_data = $checkUser->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        // ADMIN KONTROLÜ: Admin ise tarih/süre umrumuzda değil, direkt içeride!
        if (isAdmin()) {
            $oturum_gecerli = true;
            $kullanici_adi = htmlspecialchars($_SESSION['username']);
        } 
        // KULLANICI KONTROLÜ: Sadece admin değilse süresine bak
        elseif ($user_data['role'] !== 'banned') {
            $simdi = time();
            $bitis_timestamp = $user_data['bitis_tarihi'] ? strtotime($user_data['bitis_tarihi']) : 0;
            
            if ($bitis_timestamp > $simdi) {
                $oturum_gecerli = true;
                $kullanici_adi = htmlspecialchars($_SESSION['username']);
            } else {
                session_unset();
                session_destroy();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VayaCheats // The Singularity</title>
    <link rel="stylesheet" href="style.css?v=1.0.1">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="VayaCheats ile siber ağın gücünü keşfedin. Güncel internal, external hileler ve yönetim paneli.">
    <meta name="keywords" content="VayaCheats, hile, cheat, internal, external, hack, siber ağ">
    <meta name="author" content="VayaCheats">
</head>
<body>

    <!-- Galaktik Kuantum Motoru -->
    <canvas id="cosmicCanvas"></canvas>
    <div class="nebula-overlay"></div>

    <!-- Fareyi Takip Eden Dinamik Kuantum Flare -->
    <div class="quantum-flare" id="quantumFlare"></div>

    <!-- 3D KATLANABİLİR YELPAZE KONSOL -->
    <main class="galaxy-wrapper">

<!-- SEKMELİ KART 01: MERKEZ -->
<section class="hologram-sheet active-sheet" id="sheet-home">
    <div class="sheet-tab">
        
        <!-- 🔥 AKILLI SİBER GEÇİŞ: OTURUM KONTROLÜNE BAĞLI PORTAL BUTTONU 🔥 -->
        <?php if (isset($oturum_gecerli) && $oturum_gecerli === true): ?>
            <!-- Kullanıcı giriş yapmış ve süresi aktifse: Direkt user.php'ye uçurur -->
            <a href="user.php" class="btn-sidebar-portal" data-hover="CORE PANEL" style="border-color: #10b981; box-shadow: 0 0 15px rgba(16, 185, 129, 0.25);">
                <span class="portal-icon" style="color: #10b981; text-shadow: 0 0 10px #10b981;">❖</span>
                <div class="portal-vertical-txt" style="color: #10b981;">
                    <span>P</span><span>A</span><span>N</span><span>E</span><span>L</span>
                </div>
            </a>
        <?php else: ?>
            <!-- Giriş yapmamışsa: Senin orijinal auth.php sayfasına giden LOGIN butonun -->
            <a href="auth.php" class="btn-sidebar-portal" data-hover="TERMINAL ACCESS">
                <span class="portal-icon">❖</span>
                <div class="portal-vertical-txt">
                    <span>L</span><span>O</span><span>G</span><span>I</span><span>N</span>
                </div>
            </a>
        <?php endif; ?>

        <span class="tab-num">01</span>
        <div class="tab-vertical-title">
            <span>M</span><span>E</span><span>R</span><span>K</span><span>E</span><span>Z</span>
        </div>
        <div class="tab-indicator-line"></div>
    </div>
    <div class="sheet-content">
        <!-- Buradan itibaren tüm eski ana merkez yazıların, arama kutun ve barların tıkır tıkır geri gelecek hacı! -->
        <div class="panel-section-title">[ 01 / ANA MERKEZ ]</div>


            <div class="panel-glitch-tag">// MATRIX_CORE_ACTIVE</div>

        <h1 class="quantum-text">Sınırların <br><span class="gradient-text">Ötesinde Bir Evren.</span></h1>

                <p class="panel-p">VayaCheats, standart yazılım enjeksiyonlarını reddeder. Turnuva korumalarını harici kuantum simülasyonları ile etkisiz bırakan izole bir dijital üs.</p>
                
                <div class="cosmic-search" style="margin-bottom: 25px;">
                    <!-- Input alanına ID tanımlandı -->
                    <input type="text" id="hile_adi_sorgu" placeholder="Siber ağları tara (Valorant, Apex)...">
                    <!-- Butona ID ve Tıklama Fonksiyonu Tanımlandı -->
                    <button class="btn-execute" id="btn-sorgula" onclick="
        const inputAlani = document.getElementById('hile_adi_sorgu');
        const terminal = document.getElementById('sorgu-terminal');
        const btn = document.getElementById('btn-sorgula');
        const hileInput = inputAlani ? inputAlani.value.trim() : '';

        if (!hileInput) {
            terminal.style.display = 'block';
            terminal.style.border = '1px solid #f43f5e';
            terminal.style.color = '#f43f5e';
            terminal.innerHTML = '>> [CORE_ERROR] Gadaşım, siber ağlarda aratmak için bir hile adı yazmalısın!';
        } else {
            btn.disabled = true;
            btn.innerText = 'TARANIYOR...';
            terminal.style.display = 'block';
            terminal.style.border = '1px solid #334155';
            terminal.style.color = '#38bdf8';
            terminal.style.background = '#020617';
            terminal.innerHTML = '>> [QUANTUM_SEARCH] Vayacheats siber veri tabanları taranıyor...<br>';

            setTimeout(() => {
                terminal.innerHTML += `>> [BYPASS_CHECK] &quot;${hileInput}&quot; için aktif imza durumu sorgulanıyor...<br>`;
            }, 600);

            setTimeout(() => {
                fetch('hile_mevcut_mu.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'arama_terimi=' + encodeURIComponent(hileInput)
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = 'SORGULA';
                    if (data.mevcut === true) {
                        terminal.style.border = '1px solid #10b981';
                        terminal.style.color = '#10b981';
                        let durumFisegi = data.durum === 'UNDETECTED' ? '<span style=&quot;color:#10b981; font-weight:bold;&quot;>[GÜNCEL / UNDETECTED]</span>' : '<span style=&quot;color:#eab308; font-weight:bold;&quot;>[BAKIMDA / DETECTED]</span>';
                        terminal.innerHTML = `>> [TARGET_FOUND] SİBER AĞDA HİLE TESPİT EDİLDİ!<br>> ==============================================================<br>> HİLE PROSESİ : <span style=&quot;color:#fff; font-weight:bold;&quot;>${data.hile_adi}</span><br>> GÜVENLİK     : ${durumFisegi}<br>> ENTEGRASYON  : ${data.koruma}<br>> ERİŞİM       : Yetkiniz var. Müşteri panelinden tek tıkla enjekte edebilirsiniz gadaşım.`;
                    } else {
                        terminal.style.border = '1px solid #f43f5e';
                        terminal.style.color = '#f43f5e';
                        terminal.innerHTML = `>> [SEARCH_FAILED] SİBER AĞ TARAMASI TAMAMLANDI<br>> ==============================================================<br>> SORGULANAN   : <span style=&quot;color:#fff; font-weight:bold;&quot;>&quot;${hileInput}&quot;</span><br>> DURUM        : Bu isimde aktif bir kuantum hilesi üssümüzde mevcut değil.<br>> TAVSİYE      : İsmi doğru yazdığınızdan emin olun veya Discord üzerinden gadaşlarıma bildirin!`;
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerText = 'SORGULA';
                    terminal.style.border = '1px solid #f43f5e';
                    terminal.style.color = '#f43f5e';
                    terminal.innerHTML = '>> [CONN_LOST] Sunucu çekirdeği yanıt vermedi. hile_mevcut_mu.php dosyasını kontrol et gadaşım!';
                });
            }, 1500);
        }
    ">SORGULA</button>
                </div>
                <div id="sorgu-terminal" style="display: none; max-width: 100%; margin: 0 auto 40px auto; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.9rem; text-align: left; line-height: 1.6; box-shadow: 0 0 20px rgba(0,0,0,0.5); transition: all 0.4s ease;">
                    <!-- Log akışı buraya canlanacak gadaşım -->
                </div>
                <?php
                // Ana sayfa için veritabanından anlık siber istatistikleri çekiyoruz gadaşım
                try {
                    $vanguard_ratio = "99.4%"; // Bunu statik veya ayarlardan çekebilirsin
                    $total_active_cheats = $db->query("SELECT COUNT(*) FROM hileler WHERE durum = 'UNDETECTED'")->fetchColumn();
                    $total_members_count = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE role = 'vip'")->fetchColumn();
                } catch (PDOException $e) {
                    $total_active_cheats = 0;
                    $total_members_count = 0;
                }
                ?>

                <!-- DİNAMİK REKABETÇİ VE KREATİF SİSTEM ANALİZ ŞERİTLERİ -->
                <div class="live-network-grid">
                    <div class="network-node">
                        <div class="node-header">
                            <span class="node-dot pulses-cyan"></span>
                            <span class="node-title">VANGUARD BYPASS RATIO</span>
                        </div>
                        <div class="node-value"><?php echo $vanguard_ratio; ?></div>
                        <p class="node-desc">AI tabanlı piksel tarama modülleri stabil.</p>
                    </div>

                    <div class="network-node">
                        <div class="node-header">
                            <span class="node-dot pulses-purple"></span>
                            <span class="node-title">SİBER KULLANICI AĞI</span>
                        </div>
                        <div class="node-value"><?php echo $total_members_count; ?> +</div>
                        <p class="node-desc">Şu anda ağımıza enjekte edilmiş aktif siber üye sayısı.</p>
                    </div>

                    <div class="network-node">
                        <div class="node-header">
                            <span class="node-dot pulses-cyan"></span>
                            <span class="node-title">AKTİF UNDETECTED HİLE</span>
                        </div>
                        <div class="node-value"><?php echo $total_active_cheats; ?> MODÜL</div>
                        <p class="node-desc">Çekirdek seviyesinde izole, yayında olan güvenli simülasyonlar.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEKMELİ KART 02: KATALOG -->
        <section class="hologram-sheet" id="sheet-catalog">
            <div class="sheet-tab">
                <span class="tab-num">02</span>
                <div class="tab-vertical-title">
                    <span>K</span><span>A</span><span>T</span><span>A</span><span>L</span><span>O</span><span>G</span>
                </div>
                <div class="tab-indicator-line"></div>
            </div>
            <div class="sheet-content">
                <div class="panel-section-title">[ 02 / PROJE KATALOĞU ]</div>
                <div class="panel-glitch-tag">// PROJE_KATALOGU</div>
                <h1 class="quantum-text" style="color: #fff; font-size: 28px; font-weight: 900; margin-bottom: 25px;">Aktif Modüller</h1>

                <div style="display: flex; gap: 30px; width: 100%; align-items: flex-start; flex-wrap: wrap;">
                    <?php
                    // Bulut ağındaki 'hileler' tablosundan tüm verileri çekiyoruz gadaşım
                    try {
                        $hileSorgu = $db->query("SELECT * FROM hileler ORDER BY id DESC");
                        $hileler = $hileSorgu->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $hileler = [];
                    }

                    if (count($hileler) > 0) {
                        foreach ($hileler as $hile) {
                            // Güvenlik durumuna göre siber neon renk ataması gadaşım
                            $neonColor = '#10b981'; // Varsayılan yeşil (UNDETECTED)
                            $shadowColor = 'rgba(16, 185, 129, 0.4)';
                            
                            if ($hile['durum'] === 'DETECTED') {
                                $neonColor = '#f43f5e'; // Kırmızı
                                $shadowColor = 'rgba(244, 63, 94, 0.4)';
                            } elseif ($hile['durum'] === 'BAKIMDA') {
                                $neonColor = '#eab308'; // Sarı
                                $shadowColor = 'rgba(234, 179, 8, 0.4)';
                            }
                    ?>
                            <!-- ⚡ DİNAMİK VAYACHEATS SİBER KART ⚡ -->
                            <div class="network-node" style="flex: 1; min-width: 320px; max-width: 400px; background: rgba(4, 7, 14, 0.45); border: 1px solid rgba(255,255,255,0.05); border-radius: 18px; padding: 30px; backdrop-filter: blur(30px); position: relative; font-family: monospace; cursor: pointer;" onclick="window.location.href='product.php?id=<?php echo $hile['id']; ?>'">
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <span style="font-size: 11px; color: #64748b; font-weight: 800; letter-spacing: 1px;">
                                        [ 042 / <?php echo strtoupper(htmlspecialchars($hile['aranacak_kelime'])); ?> ]
                                    </span>
                                    <span style="font-size: 10px; font-weight: 900; color: <?php echo $neonColor; ?>; border: 1px solid <?php echo $neonColor; ?>; padding: 2px 8px; border-radius: 4px; text-shadow: 0 0 5px <?php echo $shadowColor; ?>; background: rgba(0,0,0,0.2);">
                                        <?php echo htmlspecialchars($hile['durum']); ?>
                                    </span>
                                </div>

                                <h2 style="font-size: 20px; font-weight: 900; color: #fff; margin-bottom: 10px;">
                                    <?php echo htmlspecialchars($hile['hile_adi']); ?>
                                </h2>

                                <p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin-bottom: 20px; min-height: 50px;">
                                    Sürücü Altyapısı: <?php echo htmlspecialchars($hile['koruma']); ?>
                                </p>

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                                    <div style="display: flex; gap: 8px;">
                                        <span style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; font-size: 10px; padding: 4px 10px; border-radius: 4px;">WIN 10/11</span>
                                        <span style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; font-size: 10px; padding: 4px 10px; border-radius: 4px;">KERNEL</span>
                                    </div>

                                    <!-- View Product button instead of direct download -->
                                    <a href="product.php?id=<?php echo $hile['id']; ?>" class="btn-execute" style="background: linear-gradient(135deg, #00ffcc, #00b395); color: #000; font-weight: 900; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-size: 11px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,255,204,0.3); text-align: center;">
                                        VIEW DETAILS
                                    </a>
                                </div>

                            </div>
                    <?php
                        }
                    } else {
                        echo '<div style="font-family:monospace; color:#f87171; font-size:12px;">// BULUT AĞINDA AKTİF KATALOG MODÜLÜ BULUNAMADI. ADMIN PANELINDEN ENJEKTE EDIN.</div>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- SEKMELİ KART 03: VİZYON -->
        <section class="hologram-sheet" id="sheet-vision">
            <div class="sheet-tab">
                <span class="tab-num">03</span>
                <div class="tab-vertical-title">
                    <span>V</span><span>İ</span><span>Z</span><span>Y</span><span>O</span><span>N</span>
                </div>
                <div class="tab-indicator-line"></div>
            </div>
            <div class="sheet-content">
                <div class="panel-section-title">[ 03 / GELİŞTİRİCİ VİZYONU ]</div>
                <div class="panel-glitch-tag">// VİZYON_VE_FELSEFE</div>
                <div class="vision-wrapper">
                    <h2 class="quantum-text">Biz Kodun <br><span class="gradient-text">Görünmeyen Katmanıyız.</span></h2>
                    <p class="panel-p">VayaCheats, siber dünyanın en izole katmanlarında çalışarak kullanıcılarının rekabet gücünü maksimuma ulaştırmayı hedefler. Geleneksel yöntemleri yıkarak, tamamen harici donanım tabanlı simülasyon sistemleri üzerine Ar-Ge projeleri geliştiriyoruz.</p>
                    <div class="vision-metrics">
                        <div class="metric-box"><strong>%99</strong><span>Bypass Kararlılığı</span></div>
                        <div class="metric-box"><strong>&lt; 1ms</strong><span>Tepki Süresi</span></div>
                    </div>
                </div>
            </div>
        </section>
        
<!-- SEKMELİ KART 04: İLETİŞİM -->
<section class="hologram-sheet" id="sheet-connect">
    <div class="sheet-tab">
        <span class="tab-num">04</span>
        <div class="tab-vertical-title">
            <span>İ</span><span>L</span><span>E</span><span>T</span><span>İ</span><span>Ş</span><span>İ</span><span>M</span>
        </div>
        <div class="tab-indicator-line"></div>
    </div>
    <div class="sheet-content">
        <div class="panel-section-title">[ 04 / GÜVENLİ İLETİŞİM ]</div>
        <div class="panel-glitch-tag">// BAĞLANTI_İSTASYONU</div>
        
        <div class="contact-split-layout">
            
            <!-- FORM ALANI -->
            <div class="contact-form-wrapper">
                <h3>Güvenli Kanal Bağlantısı</h3>
                <p class="form-sub-txt">Operasyon ekibimize kriptolu veri paketi gönderin.</p>
                <form class="cosmic-form" action="send.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                    <div class="cosmic-input-group">
                        <input type="text" name="kod_adi" placeholder="E-posta veya Kod Adınız" required>
                    </div>
                    <div class="cosmic-input-group">
                        <input type="text" name="konu" placeholder="Konu Başlığı" required>
                    </div>
                    <div class="cosmic-input-group">
                        <textarea rows="4" name="ileti" placeholder="Şifreli iletinizi buraya yazın..." required></textarea>
                    </div>
                    <button type="submit" class="btn-cosmic-submit">VERİ PAKETİNİ FIRLAT</button>
                </form>
            </div>

            <!-- SOSYAL MEDYA PANELİ (YAZILARIN KORUMA ALTINA ALINDIĞI YENİ YAPI) -->
            <div class="social-networks-wrapper">
                
                <!-- DISCORD KUTUSU -->
                <a href="https://discord.com/users/543015383632969729" target="_blank" class="social-net-card discord-net">
                    <div class="social-net-header">
                        <span class="social-net-icon">❖</span>
                        <div class="social-text-block">
                            <span class="social-net-name">DISCORD HUB</span>
                            <p class="social-net-desc">Topluluğa Katıl ve Canlı Destek Al</p>
                        </div>
                    </div>
                </a>

                <!-- YOUTUBE KUTUSU -->
                <a href="https://youtube.com/@vayasssu" target="_blank" class="social-net-card youtube-net">
                    <div class="social-net-header">
                        <span class="social-net-icon">▶</span>
                        <div class="social-text-block">
                            <span class="social-net-name">YOUTUBE CHANNEL</span>
                            <p class="social-net-desc">Yazılım Test ve Kanıt Videoları</p>
                        </div>
                    </div>
                </a>

                <!-- INSTAGRAM KUTUSU -->
                <a href="https://instagram.com/vayassu" target="_blank" class="social-net-card instagram-net">
                    <div class="social-net-header">
                        <span class="social-net-icon">📷</span>
                        <div class="social-text-block">
                            <span class="social-net-name">INSTAGRAM MEDIA</span>
                            <p class="social-net-desc">Anlık Duyurular ve Güncel Paylaşımlar</p>
                        </div>
                    </div>
                </a>

                <!-- MADE BY VAYASSU İMZASI -->
                <div class="signature-credit-box">
                    <span class="sig-title">CORE ARCHITECTURE</span>
                    <div class="sig-name">Made by <span>Vayassu</span></div>
                </div>

            </div>

            <!-- EN SAĞ TARAF: BÜTÜNLÜĞÜ KORUYAN MASAÜSTÜ SİBER İSTASYON MATRİSİ -->
<div class="cyber-matrix-station">
    
    <!-- SYS_SEC_LOGS (ÜSTTEKİ MEVCUT BÜYÜK KUTU) -->
    <div class="security-logs-station">
        <div class="log-station-header">
            <span class="log-pulse-dot"></span>
            <span>SYS_SEC_LOGS (REAL-TIME)</span>
        </div>
        <div class="log-stream-box">
            <div class="log-line-item cyan-log">> [INFO] Kernel core isolated.</div>
            <div class="log-line-item purple-log">> [SYNC] KMBox hardware responsive.</div>
            <div class="log-line-item">> [BYPASS] Vanguard signature randomized.</div>
            <div class="log-line-item cyan-log">> [INFO] Anti-cheat trace successfully wiped.</div>
            <div class="log-line-item purple-log">> [SECURE] Memory pool heavily encrypted.</div>
            <div class="log-line-item">> [NETWORK] Secured connection established.</div>
        </div>
    </div>

    <!-- İKİ KÜÇÜK YAN YANA KUTU (YALANDAN ITEM PARÇA 1 & 2) -->
    <div class="cyber-small-row">
        <div class="cyber-mini-box">
            <div class="mini-box-tag">LATENCY ENGINE</div>
            <div class="mini-box-val pulses-cyan">14.2<span>ms</span></div>
        </div>
        <div class="cyber-mini-box">
            <div class="mini-box-tag">CRYPTO STREAM</div>
            <div class="mini-box-val pulses-purple">SSL_V3</div>
        </div>
    </div>

    <!-- ALTTAKİ BÜYÜK DETAY KUTUSU (YALANDAN ITEM PARÇA 3) -->
    <div class="cyber-big-status-box">
        <div class="big-box-header">ANTI-CHEAT HEATMAP ANALYSIS</div>
        <div class="heatmap-bar-wrapper">
            <div class="heatmap-bar-line"><div class="h-fill fill-90"></div></div>
            <div class="heatmap-bar-line"><div class="h-fill fill-40"></div></div>
            <div class="heatmap-bar-line"><div class="h-fill fill-75"></div></div>
        </div>
        <div class="big-box-footer">PACKETS INJECTED SECURELY</div>
    </div>

</div>


        </div>
    </div>
</section>



    </main>

    <footer>[VAYACHEATS CO. © 2026 // ALL SYSTEMS OPERATIONAL ] <span style="margin-left: 20px;">| <a href="pricing.php" style="color: #00ffcc; text-decoration: none;">PRICING</a></span></footer>

    <script src="script.js"></script>
</body>
</html>
