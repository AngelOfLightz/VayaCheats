# VayaCheats V3.1 - Configuration Center

## Overview

The Configuration Center provides a centralized, editable configuration service for all system settings. All settings are manageable from a single UI and stored in the database for dynamic updates without code changes.

---

## Configuration Categories

### 1. SMTP Configuration
- SMTP host
- SMTP port
- SMTP username
- SMTP password (encrypted)
- SMTP encryption (TLS/SSL)
- From email
- From name
- Reply-to email

### 2. Payment Configuration
- Stripe API keys (publishable, secret)
- PayTR merchant credentials
- Iyzico API keys
- PayPal API credentials
- Webhook URLs
- Currency settings
- Tax settings

### 3. Download Configuration
- Download speed limits
- Concurrent download limits
- File size limits
- Allowed file types
- Storage paths
- CDN settings
- Bandwidth throttling

### 4. Registration Configuration
- Registration enabled/disabled
- Email verification required
- Minimum password length
- Password complexity requirements
- Username restrictions
- Email domain blacklist
- IP whitelist/blacklist
- Registration rate limits

### 5. Security Configuration
- Session timeout
- Remember me duration
- 2FA requirements
- Password reset timeout
- Login attempt limits
- IP whitelist/blacklist
- CSRF token expiration
- API key expiration

### 6. Maintenance Configuration
- Maintenance mode enabled/disabled
- Maintenance message
- Maintenance start time
- Maintenance end time
- Allowed IPs during maintenance
- Bypass users during maintenance

### 7. Feature Flags Configuration
- Feature flag overrides
- Flag default values
- Flag rollout percentages
- Flag expiration dates

### 8. Analytics Configuration
- Analytics enabled/disabled
- Tracking code
- Custom dimensions
- Data retention period
- Anonymization settings
- Sampling rate

### 9. API Configuration
- API rate limits
- API versioning settings
- API key policies
- CORS settings
- Webhook timeouts
- API documentation URL

### 10. Launcher Configuration
- Launcher version
- Launcher download URL
- Launcher update server
- Launcher authentication settings
- Launcher API endpoints
- Launcher feature flags

### 11. Discord Configuration
- Discord bot token (encrypted)
- Discord guild ID
- Discord role mappings
- Discord webhook URLs
- Discord channel mappings

### 12. SEO Configuration
- Site title
- Meta description
- Meta keywords
- Open Graph settings
- Twitter Card settings
- Sitemap settings
- Robots.txt settings

### 13. Site Identity Configuration
- Site name
- Site tagline
- Site logo
- Site favicon
- Site colors
- Site theme
- Custom CSS
- Custom JavaScript

### 14. Uploads Configuration
- Max file size
- Allowed file types
- Storage path
- CDN settings
- Image processing settings
- Thumbnail generation
- File naming strategy

### 15. Rate Limits Configuration
- Global rate limits
- Per-endpoint rate limits
- Per-role rate limits
- Per-user rate limits
- Burst limits
- Sustained limits

### 16. System Limits Configuration
- Max users
- Max products
- Max licenses
- Max downloads per day
- Max API calls per day
- Storage quota
- Bandwidth quota

---

## Configuration Service

### Configuration Service Interface

```php
interface ConfigurationServiceInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value, ?string $type = null): void;
    public function has(string $key): bool;
    public function forget(string $key): void;
    public function getByCategory(string $category): array;
    public function all(): array;
    public function refresh(): void;
    public function cache(): void;
}
```

### Configuration Service Implementation

```php
class ConfigurationService implements ConfigurationServiceInterface
{
    private Database $db;
    private CacheInterface $cache;
    private array $config = [];
    private bool $loaded = false;
    
    public function __construct(Database $db, CacheInterface $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }
    
    public function get(string $key, $default = null)
    {
        $this->loadIfNeeded();
        
        if (!isset($this->config[$key])) {
            return $default;
        }
        
        return $this->config[$key];
    }
    
    public function set(string $key, $value, ?string $type = null): void
    {
        $this->loadIfNeeded();
        
        // Determine type if not provided
        if ($type === null) {
            $type = $this->determineType($value);
        }
        
        // Encrypt sensitive values
        if ($type === 'encrypted') {
            $value = $this->encrypt($value);
        }
        
        // Update database
        $this->db->query("
            INSERT INTO system_settings (key, value, type, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE value = ?, type = ?, updated_at = NOW()
        ", [$key, $value, $type, $value, $type]);
        
        // Update in-memory config
        $this->config[$key] = $value;
        
        // Clear cache
        $this->clearCache();
        
        // Dispatch event
        $this->events->dispatch(new ConfigurationUpdated($key, $value));
    }
    
    public function has(string $key): bool
    {
        $this->loadIfNeeded();
        return isset($this->config[$key]);
    }
    
    public function forget(string $key): void
    {
        $this->loadIfNeeded();
        
        // Delete from database
        $this->db->query("DELETE FROM system_settings WHERE key = ?", [$key]);
        
        // Remove from in-memory config
        unset($this->config[$key]);
        
        // Clear cache
        $this->clearCache();
        
        // Dispatch event
        $this->events->dispatch(new ConfigurationDeleted($key));
    }
    
    public function getByCategory(string $category): array
    {
        $this->loadIfNeeded();
        
        $categoryPrefix = $category . '.';
        
        return array_filter(
            $this->config,
            fn($key) => str_starts_with($key, $categoryPrefix),
            ARRAY_FILTER_USE_KEY
        );
    }
    
    public function all(): array
    {
        $this->loadIfNeeded();
        return $this->config;
    }
    
    public function refresh(): void
    {
        $this->loaded = false;
        $this->config = [];
        $this->clearCache();
    }
    
    public function cache(): void
    {
        $this->cache->set('system_config', $this->config, 3600);
    }
    
    private function loadIfNeeded(): void
    {
        if ($this->loaded) {
            return;
        }
        
        // Try cache first
        $cached = $this->cache->get('system_config');
        
        if ($cached !== null) {
            $this->config = $cached;
            $this->loaded = true;
            return;
        }
        
        // Load from database
        $result = $this->db->query("SELECT * FROM system_settings");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $value = $row['value'];
            
            // Decrypt if encrypted
            if ($row['type'] === 'encrypted') {
                $value = $this->decrypt($value);
            }
            
            // Cast to correct type
            $value = $this->castValue($value, $row['type']);
            
            $this->config[$row['key']] = $value;
        }
        
        // Cache the config
        $this->cache();
        
        $this->loaded = true;
    }
    
    private function determineType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_int($value)) {
            return 'integer';
        }
        
        if (is_float($value)) {
            return 'float';
        }
        
        if (is_array($value)) {
            return 'json';
        }
        
        if (str_starts_with($value, 'encrypted:')) {
            return 'encrypted';
        }
        
        return 'string';
    }
    
    private function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'encrypted' => $this->decrypt($value),
            default => $value
        };
    }
    
    private function encrypt(string $value): string
    {
        $key = getenv('ENCRYPTION_KEY');
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        
        return 'encrypted:' . base64_encode($iv . $encrypted);
    }
    
    private function decrypt(string $value): string
    {
        if (!str_starts_with($value, 'encrypted:')) {
            return $value;
        }
        
        $key = getenv('ENCRYPTION_KEY');
        $data = base64_decode(substr($value, 10));
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function clearCache(): void
    {
        $this->cache->delete('system_config');
    }
}
```

---

## Configuration Database Schema

### System Settings Table

```sql
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    type ENUM('string', 'integer', 'boolean', 'float', 'json', 'encrypted') DEFAULT 'string',
    category VARCHAR(50),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    is_sensitive BOOLEAN DEFAULT FALSE,
    validation_rule VARCHAR(255),
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_key (key),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public),
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Configuration History Table

```sql
CREATE TABLE configuration_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NULL,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_changed_by (changed_by),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (setting_key) REFERENCES system_settings(key) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Configuration Management UI

### Dashboard Structure

```
Configuration Center
├── Overview
│   ├── All settings summary
│   ├── Recent changes
│   └── Quick actions
├── Categories
│   ├── SMTP
│   ├── Payments
│   ├── Downloads
│   ├── Registration
│   ├── Security
│   ├── Maintenance
│   ├── Feature Flags
│   ├── Analytics
│   ├── API
│   ├── Launcher
│   ├── Discord
│   ├── SEO
│   ├── Site Identity
│   ├── Uploads
│   ├── Rate Limits
│   └── System Limits
├── History
│   ├── Change log
│   ├── Rollback
│   └── Export
└── Import/Export
    ├── Export configuration
    ├── Import configuration
    └── Validate configuration
```

### Setting Form

```
Setting Information
├── Key (auto-generated or custom)
├── Category (dropdown)
├── Type (dropdown: string, integer, boolean, json, encrypted)
├── Value (based on type)
├── Description
├── Validation rule
└── Public/Sensitive toggles

Advanced Options
├── Default value
├── Validation regex
├── Min/Max values (for numbers)
├── Allowed values (for enums)
└── Help text
```

---

## Configuration Validation

### Validation Rules

```php
class ConfigurationValidator
{
    private array $rules = [
        'smtp.host' => 'required|url',
        'smtp.port' => 'required|integer|min:1|max:65535',
        'smtp.username' => 'required|string',
        'smtp.password' => 'required|string',
        'registration.min_password_length' => 'required|integer|min:6|max:128',
        'registration.email_verification_required' => 'required|boolean',
        'security.session_timeout' => 'required|integer|min:60',
        'api.rate_limit_per_minute' => 'required|integer|min:1',
        'system.max_users' => 'required|integer|min:1',
    ];
    
    public function validate(string $key, $value): bool
    {
        if (!isset($this->rules[$key])) {
            return true;
        }
        
        $rule = $this->rules[$key];
        $rules = explode('|', $rule);
        
        foreach ($rules as $rule) {
            if (!$this->validateRule($rule, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function validateRule(string $rule, $value): bool
    {
        return match ($rule) {
            'required' => !empty($value),
            'string' => is_string($value),
            'integer' => is_int($value) || ctype_digit($value),
            'boolean' => is_bool($value) || in_array($value, ['true', 'false', '0', '1']),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            default => true
        };
    }
}
```

---

## Configuration Import/Export

### Export Configuration

```php
class ConfigurationExporter
{
    private ConfigurationService $config;
    
    public function export(bool $includeSensitive = false): array
    {
        $allConfig = $this->config->all();
        
        if (!$includeSensitive) {
            $allConfig = array_filter($allConfig, function($key) {
                return !$this->isSensitive($key);
            }, ARRAY_FILTER_USE_KEY);
        }
        
        return [
            'version' => '3.1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'settings' => $allConfig
        ];
    }
    
    public function exportToFile(string $filename, bool $includeSensitive = false): void
    {
        $data = $this->export($includeSensitive);
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    private function isSensitive(string $key): bool
    {
        $sensitiveKeys = [
            'smtp.password',
            'stripe.secret_key',
            'paytr.merchant_key',
            'discord.bot_token'
        ];
        
        foreach ($sensitiveKeys as $sensitive) {
            if (str_contains($key, $sensitive)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### Import Configuration

```php
class ConfigurationImporter
{
    private ConfigurationService $config;
    private ConfigurationValidator $validator;
    
    public function import(array $data, bool $overwrite = false): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($data['settings'] as $key => $value) {
            try {
                // Validate
                if (!$this->validator->validate($key, $value)) {
                    throw new ValidationException("Invalid value for {$key}");
                }
                
                // Check if exists
                if ($this->config->has($key) && !$overwrite) {
                    $results['failed']++;
                    $results['errors'][] = "{$key} already exists (skipped)";
                    continue;
                }
                
                // Set value
                $this->config->set($key, $value);
                $results['success']++;
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$key}: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    public function importFromFile(string $filename, bool $overwrite = false): array
    {
        $data = json_decode(file_get_contents($filename), true);
        return $this->import($data, $overwrite);
    }
}
```

---

## Configuration Rollback

### Rollback Service

```php
class ConfigurationRollbackService
{
    private Database $db;
    private ConfigurationService $config;
    
    public function rollback(string $key, ?int $historyId = null): void
    {
        if ($historyId) {
            // Rollback to specific history entry
            $history = $this->getHistoryEntry($historyId);
            
            if (!$history) {
                throw new HistoryNotFoundException($historyId);
            }
            
            $this->config->set($key, $history['old_value']);
            
        } else {
            // Rollback to previous value
            $previous = $this->getPreviousValue($key);
            
            if ($previous === null) {
                throw new NoPreviousValueException($key);
            }
            
            $this->config->set($key, $previous);
        }
    }
    
    public function rollbackAll(DateTime $toDateTime): void
    {
        $histories = $this->db->query("
            SELECT * FROM configuration_history
            WHERE created_at <= ?
            ORDER BY created_at DESC
        ", [$toDateTime->format('Y-m-d H:i:s')]);
        
        foreach ($histories->fetchAll(PDO::FETCH_ASSOC) as $history) {
            $this->config->set($history['setting_key'], $history['old_value']);
        }
    }
    
    private function getHistoryEntry(int $historyId): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM configuration_history WHERE id = ?",
            [$historyId]
        );
        
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getPreviousValue(string $key)
    {
        $result = $this->db->query("
            SELECT old_value FROM configuration_history
            WHERE setting_key = ?
            ORDER BY created_at DESC
            LIMIT 1
        ", [$key]);
        
        $row = $result->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $row['old_value'] : null;
    }
}
```

---

## Configuration Events

### Event Types

```php
class ConfigurationUpdated
{
    public function __construct(
        public string $key,
        public $value
    ) {}
}

class ConfigurationDeleted
{
    public function __construct(public string $key) {}
}

class ConfigurationRolledBack
{
    public function __construct(
        public string $key,
        public $oldValue,
        public $newValue
    ) {}
}
```

### Event Listeners

```php
class ConfigurationListener
{
    private CacheInterface $cache;
    private NotificationService $notificationService;
    
    public function onConfigurationUpdated(ConfigurationUpdated $event): void
    {
        // Clear relevant caches
        $this->clearRelatedCaches($event->key);
        
        // Notify admins for sensitive changes
        if ($this->isSensitive($event->key)) {
            $this->notificationService->notifyAdmins(
                'Configuration Changed',
                "Sensitive configuration '{$event->key}' has been updated"
            );
        }
    }
    
    private function clearRelatedCaches(string $key): void
    {
        $category = explode('.', $key)[0];
        
        match ($category) {
            'smtp' => $this->cache->delete('mail_config'),
            'payment' => $this->cache->delete('payment_config'),
            'api' => $this->cache->delete('api_config'),
            default => null
        };
    }
}
```

---

## Configuration API

### API Endpoints

```
GET    /api/v1/owner/config
GET    /api/v1/owner/config/{key}
PUT    /api/v1/owner/config/{key}
DELETE /api/v1/owner/config/{key}
GET    /api/v1/owner/config/category/{category}
GET    /api/v1/owner/config/history
GET    /api/v1/owner/config/history/{key}
POST   /api/v1/owner/config/export
POST   /api/v1/owner/config/import
POST   /api/v1/owner/config/rollback/{key}
POST   /api/v1/owner/config/refresh
```

---

## Configuration Best Practices

### Key Naming

- Use dot notation: `category.setting_name`
- Use lowercase with underscores
- Be descriptive but concise
- Examples: `smtp.host`, `payment.stripe.secret_key`, `security.session_timeout`

### Sensitive Data

- Always mark sensitive settings with `is_sensitive = true`
- Use `encrypted` type for passwords, API keys
- Never log sensitive values
- Restrict access to sensitive settings

### Validation

- Always validate configuration values
- Use appropriate data types
- Set min/max constraints
- Provide helpful error messages

### Version Control

- Export configuration to version control
- Track configuration changes
- Use configuration history for rollback
- Document configuration changes

---

## Summary

The Configuration Center provides:
- **Centralized configuration** management
- **Dynamic updates** without code deployment
- **Category-based organization** for settings
- **Type casting** (string, integer, boolean, json, encrypted)
- **Validation** for configuration values
- **Import/Export** functionality
- **Rollback** capability with history
- **Event-driven** configuration changes
- **UI management** for owners/admins
- **API endpoints** for programmatic access
- **Caching** for performance
- **Sensitive data protection** with encryption

Configuration categories cover:
- SMTP, Payments, Downloads, Registration
- Security, Maintenance, Feature Flags, Analytics
- API, Launcher, Discord, SEO
- Site Identity, Uploads, Rate Limits, System Limits
