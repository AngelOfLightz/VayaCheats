<?php
require_once 'config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch product data
$productQuery = $db->prepare("SELECT * FROM hileler WHERE id = ?");
$productQuery->execute([$product_id]);
$product = $productQuery->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

// Fetch product details if available (check if table exists first)
try {
    $detailsQuery = $db->prepare("SELECT * FROM product_details WHERE product_id = ?");
    $detailsQuery->execute([$product_id]);
    $details = $detailsQuery->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $details = null; // Table doesn't exist yet
}

// Fetch changelog (check if table exists first)
try {
    $changelogQuery = $db->prepare("SELECT * FROM changelog WHERE product_id = ? ORDER BY release_date DESC LIMIT 10");
    $changelogQuery->execute([$product_id]);
    $changelog = $changelogQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $changelog = []; // Table doesn't exist yet
}

// Fetch comments (check if table exists first)
try {
    $commentsQuery = $db->prepare("
        SELECT c.*, u.username, u.avatar, u.avatar_url, u.profil_color 
        FROM comments c 
        JOIN kullanicilar u ON c.user_id = u.id 
        WHERE c.product_id = ? 
        ORDER BY c.is_pinned DESC, c.created_at DESC 
        LIMIT 20
    ");
    $commentsQuery->execute([$product_id]);
    $comments = $commentsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $comments = []; // Table doesn't exist yet
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Check if user is muted
$user_mute = null;
if ($is_logged_in) {
    try {
        $muteQuery = $db->prepare("SELECT * FROM mutes WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC LIMIT 1");
        $muteQuery->execute([$user_id]);
        $user_mute = $muteQuery->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $user_mute = null;
    }
}

// Determine status color
$status_color = '#10b981';
if ($product['durum'] === 'DETECTED') $status_color = '#f43f5e';
elseif ($product['durum'] === 'BAKIMDA') $status_color = '#eab308';

// Parse features if available
$features = $details ? json_decode($details['features'] ?? '[]', true) : [];
$images = $details ? json_decode($details['images'] ?? '[]', true) : [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['hile_adi']); ?> // VayaCheats</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --neon-cyan: #00ffcc;
            --status-color: <?php echo $status_color; ?>;
            --panel-blur-bg: rgba(6, 11, 23, 0.75);
        }
        
        body {
            background-color: #020306;
            color: #fff;
            min-height: 100vh;
        }
        
        .product-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .product-title-section h1 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 10px;
            font-family: monospace;
        }
        
        .product-status {
            display: inline-block;
            padding: 8px 20px;
            border: 2px solid var(--status-color);
            color: var(--status-color);
            font-weight: 900;
            font-size: 14px;
            border-radius: 50px;
            text-shadow: 0 0 10px var(--status-color);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .product-main {
            background: var(--panel-blur-bg);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 18px;
            padding: 30px;
            backdrop-filter: blur(30px);
        }
        
        .product-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-box {
            background: var(--panel-blur-bg);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 18px;
            padding: 25px;
            backdrop-filter: blur(30px);
        }
        
        .info-box-title {
            font-family: monospace;
            font-size: 12px;
            font-weight: 900;
            color: var(--neon-cyan);
            letter-spacing: 2px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0,255,204,0.1);
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-family: monospace;
            font-size: 13px;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #64748b;
        }
        
        .info-value {
            color: #fff;
            font-weight: 600;
        }
        
        .download-btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--neon-cyan), #00b395);
            color: #000;
            font-weight: 900;
            font-size: 16px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0,255,204,0.3);
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(0,255,204,0.5);
        }
        
        .download-btn:disabled {
            background: #1e293b;
            color: #64748b;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .features-list li:before {
            content: "❖";
            color: var(--neon-cyan);
            font-size: 12px;
        }
        
        .changelog-item {
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid var(--neon-cyan);
        }
        
        .changelog-version {
            font-family: monospace;
            font-weight: 900;
            color: var(--neon-cyan);
            margin-bottom: 5px;
        }
        
        .changelog-date {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 10px;
        }
        
        .comments-section {
            margin-top: 40px;
        }
        
        .comment-item {
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 2px solid var(--neon-cyan);
        }
        
        .comment-username {
            font-weight: 700;
            color: #fff;
        }
        
        .comment-date {
            font-size: 12px;
            color: #64748b;
            margin-left: auto;
        }
        
        .comment-content {
            color: #cbd5e1;
            line-height: 1.6;
        }
        
        .pinned-badge {
            background: var(--neon-cyan);
            color: #000;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 900;
            margin-left: 8px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--neon-cyan);
            text-decoration: none;
            font-family: monospace;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: #fff;
        }
        
        .comment-form {
            background: var(--panel-blur-bg);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .comment-form textarea {
            width: 100%;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            color: #fff;
            font-family: monospace;
            resize: vertical;
            min-height: 100px;
        }
        
        .comment-form textarea:focus {
            outline: none;
            border-color: var(--neon-cyan);
        }
        
        .submit-comment {
            background: var(--neon-cyan);
            color: #000;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 900;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
        }
        
        .submit-comment:hover {
            box-shadow: 0 0 20px rgba(0,255,204,0.5);
        }
        
        .mute-panel {
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.3);
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.1);
        }
        
        .mute-header {
            color: #f43f5e;
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 20px;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .mute-content {
            font-family: monospace;
        }
        
        .mute-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .mute-label {
            color: #64748b;
            font-size: 12px;
        }
        
        .mute-value {
            color: #cbd5e1;
            font-size: 12px;
        }
        
        .mute-divider {
            height: 1px;
            background: rgba(244, 63, 94, 0.2);
            margin: 15px 0;
        }
        
        .mute-timer {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 14px;
            color: #f43f5e;
        }
        
        .mute-timer span {
            background: rgba(244, 63, 94, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(244, 63, 94, 0.3);
            min-width: 50px;
            text-align: center;
            font-weight: 900;
        }
        
        /* Product Slider */
        .product-slider {
            position: relative;
        }
        
        .slider-main {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            aspect-ratio: 16/9;
            background: rgba(0,0,0,0.3);
        }
        
        .slider-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
            height: 100%;
        }
        
        .slider-slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .slider-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .slider-image:hover {
            transform: scale(1.05);
        }
        
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.7);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            z-index: 10;
        }
        
        .slider-btn:hover {
            background: var(--neon-cyan);
            color: #000;
            border-color: var(--neon-cyan);
        }
        
        .slider-prev {
            left: 10px;
        }
        
        .slider-next {
            right: 10px;
        }
        
        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
        }
        
        .slider-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .slider-dot.active {
            background: var(--neon-cyan);
            box-shadow: 0 0 10px rgba(0,255,204,0.5);
        }
        
        .slider-thumbnails {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        
        .slider-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.5;
            transition: all 0.3s;
            flex-shrink: 0;
            border: 2px solid transparent;
        }
        
        .slider-thumbnail.active {
            opacity: 1;
            border-color: var(--neon-cyan);
        }
        
        .slider-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .slider-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            cursor: pointer;
        }
        
        .slider-fullscreen.active {
            display: flex;
        }
        
        .slider-fullscreen img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .slider-fullscreen-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #fff;
            font-size: 30px;
            cursor: pointer;
            z-index: 1001;
        }
        
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .product-header {
                flex-direction: column;
            }
            
            .mute-timer {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .slider-btn {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
            
            .slider-thumbnail {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="product-container">
        <a href="index.php" class="back-link">← RETURN TO CATALOG</a>
        
        <div class="product-header">
            <div class="product-title-section">
                <h1><?php echo htmlspecialchars($product['hile_adi']); ?></h1>
                <span class="product-status"><?php echo htmlspecialchars($product['durum']); ?></span>
            </div>
        </div>
        
        <div class="product-grid">
            <div class="product-main">
                <div class="info-box-title">// PRODUCT DESCRIPTION</div>
                <p style="color: #cbd5e1; line-height: 1.8; margin-bottom: 30px;">
                    <?php echo $details ? nl2br(htmlspecialchars($details['description'])) : 'Sürücü Altyapısı: ' . htmlspecialchars($product['koruma']); ?>
                </p>
                
                <?php if (!empty($features)): ?>
                    <div class="info-box-title">// FEATURES</div>
                    <ul class="features-list">
                        <?php foreach ($features as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($changelog)): ?>
                    <div class="info-box-title" style="margin-top: 40px;">// CHANGELOG</div>
                    <?php foreach ($changelog as $entry): ?>
                        <div class="changelog-item">
                            <div class="changelog-version">v<?php echo htmlspecialchars($entry['version']); ?></div>
                            <div class="changelog-date"><?php echo date('F j, Y', strtotime($entry['release_date'])); ?></div>
                            <div style="color: #cbd5e1; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($entry['changes'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="comments-section">
                    <div class="info-box-title">// COMMENTS</div>
                    
                    <?php if ($is_logged_in): ?>
                        <?php if ($user_mute): ?>
                            <div class="mute-panel" id="mutePanel">
                                <div class="mute-header">YOU ARE MUTED</div>
                                <div class="mute-content">
                                    <div class="mute-row">
                                        <span class="mute-label">Reason:</span>
                                        <span class="mute-value"><?php echo htmlspecialchars($user_mute['reason'] ?? 'No reason provided'); ?></span>
                                    </div>
                                    <div class="mute-divider"></div>
                                    <div class="mute-row">
                                        <span class="mute-label">Remaining Time:</span>
                                    </div>
                                    <div class="mute-timer" id="muteTimer">
                                        <span id="muteDays">00</span> Days
                                        <span id="muteHours">00</span> Hours
                                        <span id="muteMinutes">00</span> Minutes
                                        <span id="muteSeconds">00</span> Seconds
                                    </div>
                                    <?php if ($user_mute['expires_at']): ?>
                                        <div class="mute-row" style="margin-top: 15px;">
                                            <span class="mute-label">Muted Until:</span>
                                            <span class="mute-value"><?php echo date('F j, Y H:i', strtotime($user_mute['expires_at'])); ?> UTC</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="mute-row" style="margin-top: 15px;">
                                            <span class="mute-value" style="color: #f43f5e;">PERMANENT MUTE</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <form class="comment-form" id="commentForm" method="POST" action="post_comment.php" style="display: none;">
                                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <textarea name="comment" id="commentText" placeholder="Write your comment..." required></textarea>
                                <button type="submit" class="submit-comment" id="commentSubmitBtn">POST COMMENT</button>
                            </form>
                        <?php else: ?>
                            <form class="comment-form" id="commentForm" method="POST" action="post_comment.php">
                                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <textarea name="comment" id="commentText" placeholder="Write your comment..." required></textarea>
                                <button type="submit" class="submit-comment" id="commentSubmitBtn">POST COMMENT</button>
                            </form>
                        <?php endif; ?>
                        <div id="commentResult" style="margin-top: 10px; font-size: 12px;"></div>
                    <?php else: ?>
                        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                            <a href="auth.php" style="color: var(--neon-cyan); text-decoration: none; font-weight: 700;">Login to post comments</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-avatar" style="border-color: <?php echo htmlspecialchars($comment['profil_color'] ?? '#00ffcc'); ?>;">
                                    <?php if (!empty($comment['avatar_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($comment['avatar_url']); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($comment['avatar'] ?? '🥷'); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="comment-username" style="color: <?php echo htmlspecialchars($comment['profil_color'] ?? '#00ffcc'); ?>;">
                                        <?php echo htmlspecialchars($comment['username']); ?>
                                    </span>
                                    <?php if ($comment['is_pinned']): ?>
                                        <span class="pinned-badge">PINNED</span>
                                    <?php endif; ?>
                                </div>
                                <span class="comment-date"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($comments)): ?>
                        <div style="text-align: center; color: #64748b; padding: 40px;">
                            No comments yet. Be the first to comment!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-sidebar">
                <div class="info-box">
                    <div class="info-box-title">// PRODUCT INFO</div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value" style="color: <?php echo $status_color; ?>;"><?php echo htmlspecialchars($product['durum']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Version</span>
                        <span class="info-value"><?php echo $details ? htmlspecialchars($details['version']) : '1.0.0'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Protection</span>
                        <span class="info-value"><?php echo htmlspecialchars($product['koruma']); ?></span>
                    </div>
                    <?php if ($details && $details['last_update']): ?>
                        <div class="info-row">
                            <span class="info-label">Last Update</span>
                            <span class="info-value"><?php echo date('M j, Y', strtotime($details['last_update'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-box">
                    <div class="info-box-title">// DOWNLOAD</div>
                    <?php if ($is_logged_in): ?>
                        <?php if ($product['durum'] === 'UNDETECTED' && !empty($product['dosya_yolu'])): ?>
                            <a href="indir.php?hile_id=<?php echo $product_id; ?>" class="download-btn">
                                DOWNLOAD NOW
                            </a>
                        <?php else: ?>
                            <button class="download-btn" disabled>
                                <?php echo $product['durum'] === 'UNDETECTED' ? 'FILE NOT AVAILABLE' : 'STATUS: ' . strtoupper($product['durum']); ?>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="auth.php" class="download-btn" style="background: linear-gradient(135deg, #9000ff, #7300cc);">
                            LOGIN TO DOWNLOAD
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($images)): ?>
                    <div class="info-box">
                        <div class="info-box-title">// SCREENSHOTS</div>
                        <div class="product-slider">
                            <div class="slider-main">
                                <div class="slider-track" id="sliderTrack">
                                    <?php foreach ($images as $img): ?>
                                        <div class="slider-slide">
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Screenshot" class="slider-image">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="slider-btn slider-prev" id="sliderPrev">❮</button>
                                <button class="slider-btn slider-next" id="sliderNext">❯</button>
                            </div>
                            <div class="slider-dots" id="sliderDots"></div>
                            <div class="slider-thumbnails" id="sliderThumbnails"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="slider-fullscreen" id="sliderFullscreen">
        <span class="slider-fullscreen-close" id="sliderFullscreenClose">×</span>
        <img src="" alt="Fullscreen" id="sliderFullscreenImage">
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(commentForm);
                const submitBtn = document.getElementById('commentSubmitBtn');
                const resultDiv = document.getElementById('commentResult');
                
                submitBtn.disabled = true;
                resultDiv.style.color = '#64748b';
                resultDiv.textContent = 'Posting...';
                
                fetch('post_comment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.style.color = '#10b981';
                        resultDiv.textContent = data.message;
                        commentForm.reset();
                        // Reload page after short delay to show new comment
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
        
        // Mute timer countdown
        <?php if ($user_mute && $user_mute['expires_at']): ?>
        const muteExpiresAt = new Date('<?php echo $user_mute['expires_at']; ?>').getTime();
        const mutePanel = document.getElementById('mutePanel');
        const muteTimer = document.getElementById('muteTimer');
        const commentForm = document.getElementById('commentForm');
        
        function updateMuteTimer() {
            const now = new Date().getTime();
            const distance = muteExpiresAt - now;
            
            if (distance < 0) {
                // Mute expired
                if (mutePanel) mutePanel.style.display = 'none';
                if (commentForm) commentForm.style.display = 'block';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            const daysEl = document.getElementById('muteDays');
            const hoursEl = document.getElementById('muteHours');
            const minutesEl = document.getElementById('muteMinutes');
            const secondsEl = document.getElementById('muteSeconds');
            
            if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
            if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
            if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
            if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
        }
        
        // Update immediately and then every second
        updateMuteTimer();
        setInterval(updateMuteTimer, 1000);
        <?php endif; ?>
        
        // Product Slider
        const sliderTrack = document.getElementById('sliderTrack');
        const sliderPrev = document.getElementById('sliderPrev');
        const sliderNext = document.getElementById('sliderNext');
        const sliderDots = document.getElementById('sliderDots');
        const sliderThumbnails = document.getElementById('sliderThumbnails');
        const sliderFullscreen = document.getElementById('sliderFullscreen');
        const sliderFullscreenImage = document.getElementById('sliderFullscreenImage');
        const sliderFullscreenClose = document.getElementById('sliderFullscreenClose');
        
        if (sliderTrack && sliderTrack.children.length > 0) {
            const slides = sliderTrack.children;
            const totalSlides = slides.length;
            let currentIndex = 0;
            
            // Create dots
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('div');
                dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
                dot.addEventListener('click', () => goToSlide(i));
                sliderDots.appendChild(dot);
            }
            
            // Create thumbnails
            for (let i = 0; i < totalSlides; i++) {
                const img = slides[i].querySelector('img');
                if (img) {
                    const thumb = document.createElement('div');
                    thumb.className = 'slider-thumbnail' + (i === 0 ? ' active' : '');
                    thumb.innerHTML = '<img src="' + img.src + '" alt="Thumbnail">';
                    thumb.addEventListener('click', () => goToSlide(i));
                    sliderThumbnails.appendChild(thumb);
                }
            }
            
            function updateSlider() {
                sliderTrack.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
                
                // Update dots
                document.querySelectorAll('.slider-dot').forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentIndex);
                });
                
                // Update thumbnails
                document.querySelectorAll('.slider-thumbnail').forEach((thumb, index) => {
                    thumb.classList.toggle('active', index === currentIndex);
                });
            }
            
            function goToSlide(index) {
                currentIndex = index;
                updateSlider();
            }
            
            function nextSlide() {
                currentIndex = (currentIndex + 1) % totalSlides;
                updateSlider();
            }
            
            function prevSlide() {
                currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
                updateSlider();
            }
            
            if (sliderPrev) {
                sliderPrev.addEventListener('click', prevSlide);
            }
            
            if (sliderNext) {
                sliderNext.addEventListener('click', nextSlide);
            }
            
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') prevSlide();
                if (e.key === 'ArrowRight') nextSlide();
            });
            
            // Touch swipe
            let touchStartX = 0;
            let touchEndX = 0;
            
            sliderTrack.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            sliderTrack.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) nextSlide();
                if (touchEndX - touchStartX > 50) prevSlide();
            });
            
            // Fullscreen
            document.querySelectorAll('.slider-image').forEach(img => {
                img.addEventListener('click', () => {
                    sliderFullscreenImage.src = img.src;
                    sliderFullscreen.classList.add('active');
                });
            });
            
            if (sliderFullscreenClose) {
                sliderFullscreenClose.addEventListener('click', () => {
                    sliderFullscreen.classList.remove('active');
                });
            }
            
            if (sliderFullscreen) {
                sliderFullscreen.addEventListener('click', (e) => {
                    if (e.target === sliderFullscreen) {
                        sliderFullscreen.classList.remove('active');
                    }
                });
            }
        }
    });
    </script>
</body>
</html>
