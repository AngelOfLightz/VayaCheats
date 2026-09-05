<?php
/**
 * VayaCheats Database Connection & Migration System
 * Centralized database management with migration support
 */

// Session is started in security.php, no need to start again

// Database Configuration
define('DB_HOST', 'dpg-dae2ujad0e5s73euod6g-a.frankfurt-postgres.render.com');
define('DB_USER', 'vayacheats_user');
define('DB_PASS', '1fsWqtHXvq6gqRgXDt3BcJAt3aIUTfJV');
define('DB_NAME', 'vayacheats');

// Get PDO connection
function getDbConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME;
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            // Return JSON error for API calls
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            die("// CODE_RED: Core database bridge offline. " . $e->getMessage());
        }
    }
    
    return $db;
}

// Global database instance
$db = getDbConnection();

// Migration System
class MigrationSystem {
    private $db;
    private $migrationTable = 'migrations';
    
    public function __construct($db) {
        $this->db = $db;
        $this->ensureMigrationTable();
    }
    
    private function ensureMigrationTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
                id SERIAL PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    public function runMigration($migrationName, $sql) {
        try {
            // Check if migration already ran
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->migrationTable} WHERE migration_name = ?");
            $stmt->execute([$migrationName]);
            
            if ($stmt->fetchColumn() > 0) {
                return ['success' => true, 'message' => "Migration {$migrationName} already executed"];
            }
            
            // Execute migration
            $this->db->exec($sql);
            
            // Record migration
            $stmt = $this->db->prepare("INSERT INTO {$this->migrationTable} (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);
            
            return ['success' => true, 'message' => "Migration {$migrationName} executed successfully"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "Migration failed: " . $e->getMessage()];
        }
    }
    
    public function getExecutedMigrations() {
        $stmt = $this->db->query("SELECT migration_name, executed_at FROM {$this->migrationTable} ORDER BY executed_at ASC");
        return $stmt->fetchAll();
    }
}

// Initialize migration system (temporarily disabled for PostgreSQL migration)
// $migration = new MigrationSystem($db);

// Define all migrations (temporarily disabled)
$migrations = [
    'create_comments_table' => "
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            is_pinned BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES hileler(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            INDEX idx_product_id (product_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_product_details_table' => "
        CREATE TABLE IF NOT EXISTS product_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL UNIQUE,
            description TEXT,
            features TEXT,
            version VARCHAR(50) DEFAULT '1.0.0',
            last_update DATE,
            images JSON,
            FOREIGN KEY (product_id) REFERENCES hileler(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_changelog_table' => "
        CREATE TABLE IF NOT EXISTS changelog (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            version VARCHAR(50) NOT NULL,
            changes TEXT NOT NULL,
            release_date DATE NOT NULL,
            FOREIGN KEY (product_id) REFERENCES hileler(id) ON DELETE CASCADE,
            INDEX idx_product_id (product_id),
            INDEX idx_release_date (release_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_download_history_table' => "
        CREATE TABLE IF NOT EXISTS download_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES hileler(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_product_id (product_id),
            INDEX idx_downloaded_at (downloaded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_bans_table' => "
        CREATE TABLE IF NOT EXISTS bans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            banned_by INT NOT NULL,
            reason TEXT,
            ban_type ENUM('temporary', 'permanent') DEFAULT 'temporary',
            duration_hours INT,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (banned_by) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_mutes_table' => "
        CREATE TABLE IF NOT EXISTS mutes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            muted_by INT NOT NULL,
            reason TEXT,
            mute_type ENUM('temporary', 'permanent') DEFAULT 'temporary',
            duration_minutes INT,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (muted_by) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_reports_table' => "
        CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT NOT NULL,
            target_type ENUM('user', 'comment', 'product') NOT NULL,
            target_id INT NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
            reviewed_by INT NULL,
            reviewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporter_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_by) REFERENCES kullanicilar(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_target (target_type, target_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'upgrade_users_table' => "
        ALTER TABLE kullanicilar 
        ADD COLUMN IF NOT EXISTS hwid VARCHAR(255),
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
        ADD INDEX IF NOT EXISTS idx_role (role)
    ",
    
    'upgrade_products_table' => "
        ALTER TABLE hileler 
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS slug VARCHAR(255) UNIQUE,
        ADD INDEX IF NOT EXISTS idx_slug (slug),
        ADD INDEX IF NOT EXISTS idx_durum (durum)
    ",
    
    'create_orders_table' => "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(32) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            product_id INT NULL,
            plan_id INT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
            payment_gateway VARCHAR(50),
            payment_time TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES hileler(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_order_id (order_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_payments_table' => "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id VARCHAR(64) UNIQUE NOT NULL,
            order_id VARCHAR(32) NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            gateway VARCHAR(50) NOT NULL,
            gateway_transaction_id VARCHAR(255),
            status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            payment_time TIMESTAMP NULL,
            refund_time TIMESTAMP NULL,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_gateway (gateway),
            INDEX idx_payment_id (payment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_transactions_table' => "
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(64) UNIQUE NOT NULL,
            payment_id VARCHAR(64) NOT NULL,
            user_id INT NOT NULL,
            type ENUM('payment', 'refund', 'chargeback', 'adjustment') NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            description TEXT,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            transaction_time TIMESTAMP NULL,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            INDEX idx_payment_id (payment_id),
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_transaction_id (transaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_plans_table' => "
        CREATE TABLE IF NOT EXISTS plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            billing_cycle ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',
            features JSON,
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'upgrade_users_table_for_payments' => "
        ALTER TABLE kullanicilar 
        ADD COLUMN IF NOT EXISTS subscription_plan_id INT NULL,
        ADD COLUMN IF NOT EXISTS subscription_status ENUM('active', 'inactive', 'cancelled', 'expired') DEFAULT 'inactive',
        ADD COLUMN IF NOT EXISTS subscription_expires_at TIMESTAMP NULL,
        ADD INDEX IF NOT EXISTS idx_subscription_plan (subscription_plan_id)
    ",
    
    'create_subscription_types_table' => "
        CREATE TABLE IF NOT EXISTS subscription_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            slug VARCHAR(50) NOT NULL UNIQUE,
            level INT NOT NULL DEFAULT 0,
            description TEXT,
            price_monthly DECIMAL(10, 2) DEFAULT 0.00,
            price_yearly DECIMAL(10, 2) DEFAULT 0.00,
            features JSON,
            max_downloads INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_level (level),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_subscriptions_table' => "
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subscription_type_id INT NOT NULL,
            start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_date TIMESTAMP NULL,
            status ENUM('active', 'inactive', 'cancelled', 'expired', 'suspended') DEFAULT 'active',
            auto_renew BOOLEAN DEFAULT TRUE,
            payment_method VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (subscription_type_id) REFERENCES subscription_types(id) ON DELETE RESTRICT,
            INDEX idx_user_id (user_id),
            INDEX idx_subscription_type_id (subscription_type_id),
            INDEX idx_status (status),
            INDEX idx_end_date (end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_subscription_history_table' => "
        CREATE TABLE IF NOT EXISTS subscription_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subscription_id INT NOT NULL,
            user_id INT NOT NULL,
            action ENUM('created', 'upgraded', 'downgraded', 'renewed', 'cancelled', 'expired', 'suspended', 'reactivated', 'manually_granted', 'manually_removed', 'extended', 'shortened') NOT NULL,
            from_type_id INT NULL,
            to_type_id INT NULL,
            old_end_date TIMESTAMP NULL,
            new_end_date TIMESTAMP NULL,
            performed_by INT NULL,
            reason TEXT,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
            FOREIGN KEY (performed_by) REFERENCES kullanicilar(id) ON DELETE SET NULL,
            INDEX idx_subscription_id (subscription_id),
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'create_payment_logs_table' => "
        CREATE TABLE IF NOT EXISTS payment_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id VARCHAR(64) NOT NULL,
            gateway VARCHAR(50) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL,
            request_data JSON,
            response_data JSON,
            error_message TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_payment_id (payment_id),
            INDEX idx_gateway (gateway),
            INDEX idx_event_type (event_type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    
    'upgrade_products_table_for_subscription' => "
        ALTER TABLE hileler 
        ADD COLUMN IF NOT EXISTS required_subscription_level INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS required_subscription_id INT NULL,
        ADD INDEX IF NOT EXISTS idx_required_subscription (required_subscription_level, required_subscription_id)
    ",
    
    'insert_default_subscription_types' => "
        INSERT INTO subscription_types (name, slug, level, description, price_monthly, price_yearly, features, max_downloads, is_active) VALUES
        ('FREE', 'free', 0, 'Basic access with limited features', 0.00, 0.00, '[\"Access to free cheats\", \"Community support\"]', 5, TRUE),
        ('STARTER', 'starter', 1, 'Entry-level membership for casual users', 9.99, 99.99, '[\"Access to 3 basic cheats\", \"Standard support\", \"Weekly updates\", \"Basic anti-detection\", \"Community access\"]', 10, TRUE),
        ('PRO', 'pro', 2, 'Professional membership for serious users', 19.99, 199.99, '[\"Access to 10 cheats\", \"Priority support\", \"Daily updates\", \"Advanced anti-detection\", \"Private Discord access\", \"Custom configurations\"]', 25, TRUE),
        ('ULTIMATE', 'ultimate', 3, 'Ultimate membership with all features', 39.99, 399.99, '[\"Access to ALL cheats\", \"24/7 premium support\", \"Real-time updates\", \"Enterprise anti-detection\", \"VIP Discord role\", \"Beta access\", \"Custom builds\"]', 999, TRUE),
        ('OWNER', 'owner', 99, 'Owner level - full system access', 0.00, 0.00, '[\"Full system access\", \"Owner panel\", \"Manage all users\", \"Manage subscriptions\", \"System settings\"]', 9999, TRUE)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
    "
];

// Run migrations if requested (temporarily disabled)
/*
if (isset($_GET['run_migrations']) && isset($_SESSION['role']) && isAdmin()) {
    $results = [];
    foreach ($migrations as $name => $sql) {
        $result = $migration->runMigration($name, $sql);
        $results[] = ['name' => $name, 'result' => $result];
    }
    
    echo '<pre>';
    foreach ($results as $r) {
        echo $r['name'] . ': ' . $r['result']['message'] . "\n";
    }
    echo '</pre>';
    exit;
}
*/
?>
