<?php
// Migration Runner - Run this to set up the database
require_once 'config.php';
require_once 'auth_check.php';

// Check if user is admin
if (!isAdmin()) {
    die("Access denied. Admin only.");
}

// Run migrations
$results = [];
foreach ($migrations as $name => $sql) {
    $result = $migration->runMigration($name, $sql);
    $results[] = ['name' => $name, 'result' => $result];
}

// Display results
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VayaCheats // Database Migrations</title>
    <style>
        body {
            background: #020306;
            color: #00ffcc;
            font-family: monospace;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            border-bottom: 1px solid #00ffcc;
            padding-bottom: 20px;
        }
        .migration {
            background: rgba(0, 255, 204, 0.05);
            border: 1px solid rgba(0, 255, 204, 0.2);
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .success { color: #10b981; }
        .error { color: #f43f5e; }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #00ffcc;
            text-decoration: none;
            border: 1px solid #00ffcc;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .back-link:hover {
            background: rgba(0, 255, 204, 0.1);
        }
    </style>
</head>
<body>
    <h1>// DATABASE MIGRATION RESULTS</h1>
    
    <?php foreach ($results as $r): ?>
        <div class="migration">
            <strong><?php echo htmlspecialchars($r['name']); ?></strong><br>
            <span class="<?php echo $r['result']['success'] ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($r['result']['message']); ?>
            </span>
        </div>
    <?php endforeach; ?>
    
    <a href="admin.php" class="back-link">← RETURN TO ADMIN PANEL</a>
</body>
</html>
