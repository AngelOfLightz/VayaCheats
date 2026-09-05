<?php
require_once 'config.php';

// Language detection
$lang = $_GET['lang'] ?? 'en';
if (!in_array($lang, ['en', 'tr'])) {
    $lang = 'en';
}

// Translations
$translations = [
    'en' => [
        'title' => 'CHOOSE YOUR PLAN',
        'subtitle' => 'Select the perfect plan for your needs. All plans include access to our premium cheat software with regular updates and 24/7 support.',
        'back_link' => '← RETURN TO CATALOG',
        'starter' => 'STARTER',
        'pro' => 'POPULAR',
        'ultimate' => 'BEST VALUE',
        'enterprise' => 'ENTERPRISE',
        'starter_name' => 'Starter',
        'pro_name' => 'Pro',
        'ultimate_name' => 'Ultimate',
        'enterprise_name' => 'Enterprise',
        'per_month' => 'per month',
        'purchase' => 'Purchase',
        'contact_sales' => 'Contact Sales',
        'footer' => 'All prices are in USD. Subscription auto-renews monthly. Cancel anytime.',
        'need_help' => 'Need help choosing?',
        'contact_support' => 'Contact our support team',
        'starter_features' => [
            'Access to 3 basic cheats',
            'Standard support',
            'Weekly updates',
            'Basic anti-detection',
            'Community access'
        ],
        'pro_features' => [
            'Access to 10 cheats',
            'Priority support',
            'Daily updates',
            'Advanced anti-detection',
            'Private Discord access',
            'Custom configurations'
        ],
        'ultimate_features' => [
            'Access to ALL cheats',
            '24/7 premium support',
            'Real-time updates',
            'Enterprise anti-detection',
            'VIP Discord role',
            'Beta access',
            'Custom builds'
        ],
        'enterprise_features' => [
            'Everything in Ultimate',
            'Dedicated support manager',
            'Custom cheat development',
            'White-label options',
            'API access',
            'SLA guarantee',
            'Team licenses',
            'Priority queue'
        ]
    ],
    'tr' => [
        'title' => 'PLANINİ SEÇ',
        'subtitle' => 'İhtiyaçlarınıza uygun planı seçin. Tüm planlar premium hile yazılımımıza erişim, düzenli güncellemeler ve 7/24 destek içerir.',
        'back_link' => '← KATALOĞA DÖN',
        'starter' => 'BAŞLANGIÇ',
        'pro' => 'POPÜLER',
        'ultimate' => 'EN İYİ DEĞER',
        'enterprise' => 'KURUMSAL',
        'starter_name' => 'Başlangıç',
        'pro_name' => 'Pro',
        'ultimate_name' => 'Ultimate',
        'enterprise_name' => 'Kurumsal',
        'per_month' => 'aylık',
        'purchase' => 'Satın Al',
        'contact_sales' => 'Satış İletişim',
        'footer' => 'Tüm fiyatlar USD cinsindendir. Abonelik aylık olarak otomatik yenilenir. İstediğiniz zaman iptal edebilirsiniz.',
        'need_help' => 'Seçim yaparken yardıma mı ihtiyacınız var?',
        'contact_support' => 'Destek ekibimizle iletişime geçin',
        'starter_features' => [
            '3 temel hileye erişim',
            'Standart destek',
            'Haftalık güncellemeler',
            'Temel anti-tespit',
            'Topluluk erişimi'
        ],
        'pro_features' => [
            '10 hileye erişim',
            'Öncelikli destek',
            'Günlük güncellemeler',
            'Gelişmiş anti-tespit',
            'Özel Discord erişimi',
            'Özel yapılandırmalar'
        ],
        'ultimate_features' => [
            'TÜM hilelere erişim',
            '7/24 premium destek',
            'Gerçek zamanlı güncellemeler',
            'Kurumsal anti-tespit',
            'VIP Discord rolü',
            'Beta erişimi',
            'Özel yapılar'
        ],
        'enterprise_features' => [
            'Ultimate\'deki her şey',
            'Özel destek yöneticisi',
            'Özel hile geliştirme',
            'White-label seçenekleri',
            'API erişimi',
            'SLA garantisi',
            'Takım lisansları',
            'Öncelikli kuyruk'
        ]
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRICING // VayaCheats</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --neon-cyan: #00ffcc;
            --neon-purple: #a855f7;
            --neon-blue: #3b82f6;
            --neon-gold: #f59e0b;
            --panel-blur-bg: rgba(6, 11, 23, 0.75);
        }
        
        body {
            background-color: #020306;
            color: #fff;
            min-height: 100vh;
            font-family: monospace;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        
        .pricing-container {
            max-width: min(95vw, 1300px);
            margin: 0 auto;
            padding: clamp(10px, 2vh, 20px) clamp(10px, 2vw, 20px);
            box-sizing: border-box;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .pricing-header {
            text-align: center;
            margin-bottom: clamp(10px, 2vh, 20px);
        }
        
        .pricing-title {
            font-size: clamp(16px, 2.5vw, 28px);
            font-weight: 900;
            color: #fff;
            letter-spacing: clamp(0.8px, 0.15vw, 1.5px);
            text-transform: uppercase;
            margin-bottom: clamp(4px, 0.8vh, 8px);
            text-shadow: 0 0 30px rgba(0, 255, 204, 0.3);
        }
        
        .pricing-subtitle {
            color: #64748b;
            font-size: clamp(10px, 0.9vw, 12px);
            max-width: clamp(300px, 50vw, 550px);
            margin: 0 auto;
            line-height: 1.3;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
            gap: clamp(8px, 1vw, 14px);
            margin-bottom: clamp(10px, 2vh, 20px);
            align-items: stretch;
            flex-grow: 1;
            max-height: 65vh;
        }
        
        .pricing-card {
            background: var(--panel-blur-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: clamp(6px, 1vw, 12px);
            padding: clamp(10px, 1.2vw, 16px) clamp(8px, 1vw, 14px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            min-height: 100%;
            max-height: 100%;
        }
        
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--card-color), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            border-color: var(--card-color);
            box-shadow: 0 20px 60px rgba(var(--card-rgb), 0.2);
        }
        
        .pricing-card:hover::before {
            opacity: 1;
        }
        
        .pricing-card.starter {
            --card-color: #3b82f6;
            --card-rgb: 59, 130, 246;
        }
        
        .pricing-card.pro {
            --card-color: #a855f7;
            --card-rgb: 168, 85, 247;
        }
        
        .pricing-card.ultimate {
            --card-color: #f59e0b;
            --card-rgb: 245, 158, 11;
        }
        
        .pricing-card.enterprise {
            --card-color: #00ffcc;
            --card-rgb: 0, 255, 204;
        }
        
        .card-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(var(--card-rgb), 0.1);
            color: var(--card-color);
            padding: 2px 6px;
            border-radius: 20px;
            font-size: clamp(7px, 0.8vw, 9px);
            font-weight: 900;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            border: 1px solid rgba(var(--card-rgb), 0.3);
        }
        
        .card-name {
            font-size: clamp(12px, 1.5vw, 18px);
            font-weight: 900;
            color: #fff;
            margin-bottom: clamp(2px, 0.5vh, 4px);
            text-transform: uppercase;
            letter-spacing: clamp(0.5px, 0.1vw, 1px);
        }
        
        .card-price {
            font-size: clamp(20px, 2.5vw, 32px);
            font-weight: 900;
            color: var(--card-color);
            margin-bottom: clamp(1px, 0.2vh, 2px);
            line-height: 1;
        }
        
        .card-period {
            color: #64748b;
            font-size: clamp(9px, 0.8vw, 11px);
            margin-bottom: clamp(8px, 1vh, 12px);
        }
        
        .card-features {
            list-style: none;
            padding: 0;
            margin-bottom: auto;
            flex-grow: 1;
            overflow-y: auto;
        }
        
        .card-features li {
            padding: clamp(3px, 0.6vh, 6px) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            font-size: clamp(8px, 0.9vw, 11px);
            display: flex;
            align-items: center;
            gap: clamp(4px, 0.6vw, 6px);
        }
        
        .card-features li::before {
            content: '✓';
            color: var(--card-color);
            font-weight: 900;
            font-size: clamp(8px, 1vw, 11px);
        }
        
        .card-features li:last-child {
            border-bottom: none;
        }
        
        .card-button {
            width: 100%;
            padding: clamp(6px, 1vw, 10px);
            border: 2px solid var(--card-color);
            background: transparent;
            color: var(--card-color);
            font-family: monospace;
            font-size: clamp(8px, 0.9vw, 11px);
            font-weight: 900;
            letter-spacing: clamp(0.5px, 0.1vw, 1px);
            text-transform: uppercase;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            margin-top: clamp(8px, 1vh, 12px);
            flex-shrink: 0;
        }
        
        .card-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(var(--card-rgb), 0.2), transparent);
            transition: left 0.5s;
        }
        
        .card-button:hover {
            background: var(--card-color);
            color: #000;
            box-shadow: 0 0 30px rgba(var(--card-rgb), 0.4);
        }
        
        .card-button:hover::before {
            left: 100%;
        }
        
        .pricing-footer {
            text-align: center;
            color: #64748b;
            font-size: clamp(8px, 0.8vw, 10px);
            padding-top: clamp(8px, 1.5vh, 15px);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            flex-shrink: 0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: clamp(3px, 0.6vw, 6px);
            color: var(--neon-cyan);
            text-decoration: none;
            font-family: monospace;
            font-size: clamp(9px, 0.9vw, 11px);
            margin-bottom: clamp(8px, 1.5vh, 15px);
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: #fff;
        }
        
        /* Responsive breakpoints for fine-tuning */
        @media (max-width: 1400px) {
            .pricing-container {
                max-width: 95vw;
            }
        }
        
        @media (max-width: 1100px) {
            .pricing-container {
                max-width: 95vw;
            }
        }
        
        @media (max-width: 768px) {
            .pricing-container {
                max-width: 100%;
                padding: clamp(8px, 2vh, 15px) clamp(6px, 1.5vw, 12px);
            }
            
            .pricing-grid {
                grid-template-columns: 1fr;
                gap: clamp(8px, 1.5vw, 12px);
                max-height: 60vh;
            }
            
            .card-badge {
                font-size: 6px;
                padding: 2px 5px;
            }
        }
        
        @media (max-width: 480px) {
            .pricing-card {
                padding: clamp(8px, 1.2vw, 12px) clamp(6px, 1vw, 10px);
            }
            
            .card-button {
                padding: clamp(5px, 1vw, 8px);
            }
        }
    </style>
</head>
<body>
    <div class="pricing-container">
        <a href="index.php" class="back-link"><?php echo $t['back_link']; ?></a>
        
        <div class="pricing-header">
            <h1 class="pricing-title"><?php echo $t['title']; ?></h1>
            <p class="pricing-subtitle"><?php echo $t['subtitle']; ?></p>
        </div>
        
        <div class="pricing-grid">
            <!-- Starter Plan -->
            <div class="pricing-card starter">
                <div class="card-badge"><?php echo $t['starter']; ?></div>
                <div class="card-name"><?php echo $t['starter_name']; ?></div>
                <div class="card-price">$9.99</div>
                <div class="card-period"><?php echo $t['per_month']; ?></div>
                <ul class="card-features">
                    <?php foreach ($t['starter_features'] as $feature): ?>
                    <li><?php echo $feature; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="card-button"><?php echo $t['purchase']; ?></button>
            </div>
            
            <!-- Pro Plan -->
            <div class="pricing-card pro">
                <div class="card-badge"><?php echo $t['pro']; ?></div>
                <div class="card-name"><?php echo $t['pro_name']; ?></div>
                <div class="card-price">$19.99</div>
                <div class="card-period"><?php echo $t['per_month']; ?></div>
                <ul class="card-features">
                    <?php foreach ($t['pro_features'] as $feature): ?>
                    <li><?php echo $feature; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="card-button"><?php echo $t['purchase']; ?></button>
            </div>
            
            <!-- Ultimate Plan -->
            <div class="pricing-card ultimate">
                <div class="card-badge"><?php echo $t['ultimate']; ?></div>
                <div class="card-name"><?php echo $t['ultimate_name']; ?></div>
                <div class="card-price">$39.99</div>
                <div class="card-period"><?php echo $t['per_month']; ?></div>
                <ul class="card-features">
                    <?php foreach ($t['ultimate_features'] as $feature): ?>
                    <li><?php echo $feature; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="card-button"><?php echo $t['purchase']; ?></button>
            </div>
            
            <!-- Enterprise Plan -->
            <div class="pricing-card enterprise">
                <div class="card-badge"><?php echo $t['enterprise']; ?></div>
                <div class="card-name"><?php echo $t['enterprise_name']; ?></div>
                <div class="card-price">$99.99</div>
                <div class="card-period"><?php echo $t['per_month']; ?></div>
                <ul class="card-features">
                    <?php foreach ($t['enterprise_features'] as $feature): ?>
                    <li><?php echo $feature; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="card-button"><?php echo $t['contact_sales']; ?></button>
            </div>
        </div>
        
        <div class="pricing-footer">
            <p><?php echo $t['footer']; ?></p>
            <p style="margin-top: 10px;"><?php echo $t['need_help']; ?> <a href="#" style="color: var(--neon-cyan);"><?php echo $t['contact_support']; ?></a></p>
        </div>
        
        <!-- Language Switcher -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="?lang=en" style="color: #64748b; text-decoration: none; margin: 0 10px; <?php echo $lang === 'en' ? 'color: var(--neon-cyan); font-weight: bold;' : ''; ?>">EN</a>
            <span style="color: #64748b;">|</span>
            <a href="?lang=tr" style="color: #64748b; text-decoration: none; margin: 0 10px; <?php echo $lang === 'tr' ? 'color: var(--neon-cyan); font-weight: bold;' : ''; ?>">TR</a>
        </div>
    </div>
</body>
</html>
