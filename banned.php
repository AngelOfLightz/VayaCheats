<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch active ban
$activeBan = null;
try {
    $banQuery = $db->prepare("
        SELECT b.*, u.username as banned_username, m.username as moderator_username 
        FROM bans b 
        JOIN kullanicilar u ON b.user_id = u.id 
        LEFT JOIN kullanicilar m ON b.banned_by = m.id 
        WHERE b.user_id = ? AND (b.expires_at IS NULL OR b.expires_at > NOW()) 
        ORDER BY b.created_at DESC 
        LIMIT 1
    ");
    $banQuery->execute([$user_id]);
    $activeBan = $banQuery->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activeBan = null;
}

// If no active ban, redirect to index
if (!$activeBan) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACCOUNT BANNED // VayaCheats</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --neon-cyan: #00ffcc;
            --neon-red: #f43f5e;
            --panel-blur-bg: rgba(6, 11, 23, 0.75);
        }
        
        body {
            background-color: #020306;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: monospace;
        }
        
        .ban-container {
            max-width: 600px;
            width: 90%;
            padding: 40px;
        }
        
        .ban-panel {
            background: var(--panel-blur-bg);
            border: 2px solid rgba(244, 63, 94, 0.3);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 0 40px rgba(244, 63, 94, 0.2);
            backdrop-filter: blur(20px);
        }
        
        .ban-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .ban-icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .ban-title {
            font-size: 32px;
            font-weight: 900;
            color: var(--neon-red);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .ban-subtitle {
            color: #64748b;
            font-size: 14px;
        }
        
        .ban-info {
            margin-bottom: 30px;
        }
        
        .ban-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .ban-row:last-child {
            border-bottom: none;
        }
        
        .ban-label {
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .ban-value {
            color: #cbd5e1;
            font-size: 13px;
            text-align: right;
        }
        
        .ban-value.highlight {
            color: var(--neon-red);
            font-weight: 700;
        }
        
        .ban-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(244, 63, 94, 0.3), transparent);
            margin: 30px 0;
        }
        
        .timer-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .timer-label {
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        
        .countdown {
            display: flex;
            justify-content: center;
            gap: 15px;
            font-family: monospace;
        }
        
        .countdown-item {
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.3);
            border-radius: 12px;
            padding: 20px 15px;
            min-width: 70px;
            text-align: center;
        }
        
        .countdown-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--neon-red);
            line-height: 1;
        }
        
        .countdown-unit {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 8px;
            letter-spacing: 1px;
        }
        
        .permanent-badge {
            display: inline-block;
            background: rgba(244, 63, 94, 0.2);
            border: 2px solid var(--neon-red);
            color: var(--neon-red);
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .ban-footer {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            font-size: 11px;
        }
        
        .ban-footer a {
            color: var(--neon-cyan);
            text-decoration: none;
        }
        
        .ban-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .ban-panel {
                padding: 25px;
            }
            
            .countdown {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .countdown-item {
                min-width: 60px;
                padding: 15px 10px;
            }
            
            .countdown-value {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="ban-container">
        <div class="ban-panel">
            <div class="ban-header">
                <div class="ban-icon">🚫</div>
                <div class="ban-title">ACCOUNT BANNED</div>
                <div class="ban-subtitle">Your account has been suspended</div>
            </div>
            
            <div class="ban-info">
                <div class="ban-row">
                    <span class="ban-label">Username</span>
                    <span class="ban-value"><?php echo htmlspecialchars($activeBan['banned_username']); ?></span>
                </div>
                <div class="ban-row">
                    <span class="ban-label">Moderator</span>
                    <span class="ban-value"><?php echo htmlspecialchars($activeBan['moderator_username'] ?? 'System'); ?></span>
                </div>
                <div class="ban-row">
                    <span class="ban-label">Ban Date</span>
                    <span class="ban-value"><?php echo date('F j, Y H:i', strtotime($activeBan['created_at'])); ?> UTC</span>
                </div>
                <div class="ban-row">
                    <span class="ban-label">Ban Type</span>
                    <span class="ban-value highlight"><?php echo strtoupper($activeBan['ban_type']); ?></span>
                </div>
                <div class="ban-row">
                    <span class="ban-label">Reason</span>
                    <span class="ban-value" style="max-width: 300px; word-wrap: break-word;"><?php echo htmlspecialchars($activeBan['reason'] ?? 'No reason provided'); ?></span>
                </div>
            </div>
            
            <div class="ban-divider"></div>
            
            <?php if ($activeBan['expires_at']): ?>
                <div class="ban-row" style="margin-bottom: 15px;">
                    <span class="ban-label">Expiration Date</span>
                    <span class="ban-value"><?php echo date('F j, Y H:i', strtotime($activeBan['expires_at'])); ?> UTC</span>
                </div>
                
                <div class="timer-section">
                    <div class="timer-label">Remaining Time</div>
                    <div class="countdown" id="countdown">
                        <div class="countdown-item">
                            <div class="countdown-value" id="banDays">00</div>
                            <div class="countdown-unit">Days</div>
                        </div>
                        <div class="countdown-item">
                            <div class="countdown-value" id="banHours">00</div>
                            <div class="countdown-unit">Hours</div>
                        </div>
                        <div class="countdown-item">
                            <div class="countdown-value" id="banMinutes">00</div>
                            <div class="countdown-unit">Minutes</div>
                        </div>
                        <div class="countdown-item">
                            <div class="countdown-value" id="banSeconds">00</div>
                            <div class="countdown-unit">Seconds</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="timer-section">
                    <div class="permanent-badge">PERMANENT BAN</div>
                </div>
            <?php endif; ?>
            
            <div class="ban-footer">
                <p>If you believe this is an error, please <a href="#">contact support</a>.</p>
                <p style="margin-top: 10px;">Ban ID: #<?php echo $activeBan['id']; ?></p>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($activeBan['expires_at']): ?>
        const banExpiresAt = new Date('<?php echo $activeBan['expires_at']; ?>').getTime();
        
        function updateBanTimer() {
            const now = new Date().getTime();
            const distance = banExpiresAt - now;
            
            if (distance < 0) {
                // Ban expired, redirect to index
                window.location.href = 'index.php';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            const daysEl = document.getElementById('banDays');
            const hoursEl = document.getElementById('banHours');
            const minutesEl = document.getElementById('banMinutes');
            const secondsEl = document.getElementById('banSeconds');
            
            if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
            if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
            if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
            if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
        }
        
        // Update immediately and then every second
        updateBanTimer();
        setInterval(updateBanTimer, 1000);
        <?php endif; ?>
    });
    </script>
</body>
</html>
