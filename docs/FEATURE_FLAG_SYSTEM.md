# VayaCheats V3.1 - Feature Flag System

## Overview

The Feature Flag System enables dynamic enabling/disabling of features without code deployment. Features can be toggled globally, per user, per role, per membership, or by percentage rollout.

---

## Flag Types

### 1. Global Flags
Enabled/disabled for all users

**Use Cases**:
- Maintenance mode
- New payment flow
- System-wide features

### 2. User Flags
Enabled/disabled for specific users

**Use Cases**:
- Beta testing for specific users
- A/B testing
- Feature previews

### 3. Role Flags
Enabled/disabled for specific roles

**Use Cases**:
- Admin-only features
- Moderator tools
- Owner dashboard features

### 4. Membership Flags
Enabled/disabled for specific membership levels

**Use Cases**:
- Premium features for Ultimate members
- Early access for Pro members
- Special features for Lifetime members

### 5. Percentage Rollout
Enabled for a percentage of users

**Use Cases**:
- Gradual feature rollout
- A/B testing
- Load testing

### 6. Time-Based Flags
Enabled/disabled based on date/time

**Use Cases**:
- Scheduled feature launches
- Limited-time features
- Event-based features

---

## Flag Structure

### Flag Definition

```php
class FeatureFlag
{
    public string $key;
    public string $name;
    public string $description;
    public string $type; // global, user, role, membership, percentage, time
    public bool $enabled;
    public ?array $conditions;
    public ?int $percentage;
    public ?DateTime $startDate;
    public ?DateTime $endDate;
    public ?string $createdBy;
    public DateTime $createdAt;
    public DateTime $updatedAt;
}
```

### Flag Conditions

```php
class FlagConditions
{
    // User conditions
    public ?array $userIds;
    
    // Role conditions
    public ?array $roleIds;
    public ?array $roleSlugs;
    
    // Membership conditions
    public ?array $membershipIds;
    public ?array $membershipSlugs;
    
    // Percentage conditions
    public ?int $percentage;
    public ?string $percentageBasedOn; // user_id, random
    
    // Time conditions
    public ?DateTime $startDate;
    public ?DateTime $endDate;
    public ?string $timezone;
    
    // Custom conditions
    public ?array $custom;
}
```

---

## Flag Service

### Flag Service Interface

```php
interface FeatureFlagServiceInterface
{
    public function isEnabled(string $key, ?int $userId = null): bool;
    public function enable(string $key): void;
    public function disable(string $key): void;
    public function create(string $key, array $data): FeatureFlag;
    public function update(string $key, array $data): FeatureFlag;
    public function delete(string $key): void;
    public function get(string $key): ?FeatureFlag;
    public function all(): array;
    public function getByType(string $type): array;
}
```

### Flag Service Implementation

```php
class FeatureFlagService implements FeatureFlagServiceInterface
{
    private Database $db;
    private CacheInterface $cache;
    private EventDispatcher $events;
    
    public function __construct(Database $db, CacheInterface $cache, EventDispatcher $events)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->events = $events;
    }
    
    public function isEnabled(string $key, ?int $userId = null): bool
    {
        // Try cache first
        $cacheKey = $this->getCacheKey($key, $userId);
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Get flag
        $flag = $this->get($key);
        
        if (!$flag) {
            return false;
        }
        
        // Check if globally disabled
        if (!$flag->enabled) {
            $this->cache->set($cacheKey, false, 300);
            return false;
        }
        
        // Check time-based conditions
        if (!$this->checkTimeConditions($flag)) {
            $this->cache->set($cacheKey, false, 300);
            return false;
        }
        
        // Check type-specific conditions
        $result = match ($flag->type) {
            'global' => $this->checkGlobal($flag),
            'user' => $this->checkUser($flag, $userId),
            'role' => $this->checkRole($flag, $userId),
            'membership' => $this->checkMembership($flag, $userId),
            'percentage' => $this->checkPercentage($flag, $userId),
            'time' => $this->checkTimeConditions($flag),
            default => false
        };
        
        // Cache result
        $this->cache->set($cacheKey, $result, 300);
        
        return $result;
    }
    
    private function checkGlobal(FeatureFlag $flag): bool
    {
        return $flag->enabled;
    }
    
    private function checkUser(FeatureFlag $flag, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        $conditions = $flag->conditions;
        
        if (isset($conditions['userIds']) && in_array($userId, $conditions['userIds'])) {
            return true;
        }
        
        return false;
    }
    
    private function checkRole(FeatureFlag $flag, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        $conditions = $flag->conditions;
        
        // Get user role
        $user = $this->getUser($userId);
        
        if (!$user) {
            return false;
        }
        
        // Check by role IDs
        if (isset($conditions['roleIds']) && in_array($user->role_id, $conditions['roleIds'])) {
            return true;
        }
        
        // Check by role slugs
        if (isset($conditions['roleSlugs']) && in_array($user->role->slug, $conditions['roleSlugs'])) {
            return true;
        }
        
        return false;
    }
    
    private function checkMembership(FeatureFlag $flag, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        $conditions = $flag->conditions;
        
        // Get user membership
        $membership = $this->getUserMembership($userId);
        
        if (!$membership) {
            return false;
        }
        
        // Check by membership IDs
        if (isset($conditions['membershipIds']) && in_array($membership->plan_id, $conditions['membershipIds'])) {
            return true;
        }
        
        // Check by membership slugs
        if (isset($conditions['membershipSlugs']) && in_array($membership->plan->slug, $conditions['membershipSlugs'])) {
            return true;
        }
        
        return false;
    }
    
    private function checkPercentage(FeatureFlag $flag, ?int $userId): bool
    {
        $conditions = $flag->conditions;
        $percentage = $conditions['percentage'] ?? 0;
        
        if ($percentage >= 100) {
            return true;
        }
        
        if ($percentage <= 0) {
            return false;
        }
        
        $basedOn = $conditions['percentageBasedOn'] ?? 'user_id';
        
        if ($basedOn === 'user_id' && $userId) {
            // Deterministic based on user ID
            $hash = crc32($flag->key . $userId);
            $value = abs($hash % 100);
            
            return $value < $percentage;
        }
        
        // Random
        return random_int(0, 99) < $percentage;
    }
    
    private function checkTimeConditions(FeatureFlag $flag): bool
    {
        $conditions = $flag->conditions;
        
        $now = new DateTime();
        
        // Check start date
        if (isset($conditions['startDate']) && $now < $conditions['startDate']) {
            return false;
        }
        
        // Check end date
        if (isset($conditions['endDate']) && $now > $conditions['endDate']) {
            return false;
        }
        
        return true;
    }
    
    public function enable(string $key): void
    {
        $flag = $this->get($key);
        
        if (!$flag) {
            throw new FeatureFlagNotFoundException($key);
        }
        
        $this->db->query("
            UPDATE feature_flags 
            SET enabled = true, updated_at = NOW() 
            WHERE key = ?
        ", [$key]);
        
        // Clear cache
        $this->clearCache($key);
        
        // Dispatch event
        $this->events->dispatch(new FeatureFlagEnabled($key));
    }
    
    public function disable(string $key): void
    {
        $flag = $this->get($key);
        
        if (!$flag) {
            throw new FeatureFlagNotFoundException($key);
        }
        
        $this->db->query("
            UPDATE feature_flags 
            SET enabled = false, updated_at = NOW() 
            WHERE key = ?
        ", [$key]);
        
        // Clear cache
        $this->clearCache($key);
        
        // Dispatch event
        $this->events->dispatch(new FeatureFlagDisabled($key));
    }
    
    public function create(string $key, array $data): FeatureFlag
    {
        $this->db->insert('feature_flags', [
            'key' => $key,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'enabled' => $data['enabled'] ?? false,
            'conditions' => json_encode($data['conditions'] ?? []),
            'percentage' => $data['percentage'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Dispatch event
        $this->events->dispatch(new FeatureFlagCreated($key));
        
        return $this->get($key);
    }
    
    public function update(string $key, array $data): FeatureFlag
    {
        $flag = $this->get($key);
        
        if (!$flag) {
            throw new FeatureFlagNotFoundException($key);
        }
        
        $updateData = [];
        
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['type'])) $updateData['type'] = $data['type'];
        if (isset($data['enabled'])) $updateData['enabled'] = $data['enabled'];
        if (isset($data['conditions'])) $updateData['conditions'] = json_encode($data['conditions']);
        if (isset($data['percentage'])) $updateData['percentage'] = $data['percentage'];
        if (isset($data['start_date'])) $updateData['start_date'] = $data['start_date'];
        if (isset($data['end_date'])) $updateData['end_date'] = $data['end_date'];
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->update('feature_flags', $updateData, ['key' => $key]);
        
        // Clear cache
        $this->clearCache($key);
        
        // Dispatch event
        $this->events->dispatch(new FeatureFlagUpdated($key));
        
        return $this->get($key);
    }
    
    public function delete(string $key): void
    {
        $flag = $this->get($key);
        
        if (!$flag) {
            throw new FeatureFlagNotFoundException($key);
        }
        
        $this->db->query("DELETE FROM feature_flags WHERE key = ?", [$key]);
        
        // Clear cache
        $this->clearCache($key);
        
        // Dispatch event
        $this->events->dispatch(new FeatureFlagDeleted($key));
    }
    
    public function get(string $key): ?FeatureFlag
    {
        $result = $this->db->query("SELECT * FROM feature_flags WHERE key = ?", [$key]);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapToFlag($row);
    }
    
    public function all(): array
    {
        $result = $this->db->query("SELECT * FROM feature_flags ORDER BY created_at DESC");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'mapToFlag'], $rows);
    }
    
    public function getByType(string $type): array
    {
        $result = $this->db->query(
            "SELECT * FROM feature_flags WHERE type = ? ORDER BY created_at DESC",
            [$type]
        );
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'mapToFlag'], $rows);
    }
    
    private function mapToFlag(array $row): FeatureFlag
    {
        $flag = new FeatureFlag();
        $flag->key = $row['key'];
        $flag->name = $row['name'];
        $flag->description = $row['description'];
        $flag->type = $row['type'];
        $flag->enabled = (bool) $row['enabled'];
        $flag->conditions = json_decode($row['conditions'], true);
        $flag->percentage = $row['percentage'];
        $flag->startDate = $row['start_date'] ? new DateTime($row['start_date']) : null;
        $flag->endDate = $row['end_date'] ? new DateTime($row['end_date']) : null;
        $flag->createdBy = $row['created_by'];
        $flag->createdAt = new DateTime($row['created_at']);
        $flag->updatedAt = new DateTime($row['updated_at']);
        
        return $flag;
    }
    
    private function getCacheKey(string $key, ?int $userId): string
    {
        return "feature_flag:{$key}:" . ($userId ?? 'anonymous');
    }
    
    private function clearCache(string $key): void
    {
        $this->cache->deletePattern("feature_flag:{$key}:*");
    }
    
    private function getUser(int $userId): ?User
    {
        // Implementation
    }
    
    private function getUserMembership(int $userId): ?Membership
    {
        // Implementation
    }
}
```

---

## Flag Middleware

### Middleware for Route Protection

```php
class FeatureFlagMiddleware
{
    private FeatureFlagService $flagService;
    
    public function __construct(FeatureFlagService $flagService)
    {
        $this->flagService = $flagService;
    }
    
    public function handle(Request $request, string $flagKey): Response
    {
        $userId = $request->getUser()?->id;
        
        if (!$this->flagService->isEnabled($flagKey, $userId)) {
            throw new FeatureDisabledException($flagKey);
        }
        
        return $request;
    }
}
```

### Usage in Routes

```php
// Protect route with feature flag
Route::get('/new-feature', 'NewFeatureController@index')
    ->middleware('feature_flag:new_payment_flow');

// Multiple flags (all must be enabled)
Route::get('/experimental', 'ExperimentalController@index')
    ->middleware('feature_flag:launcher_beta')
    ->middleware('feature_flag:experimental_products');
```

---

## Flag Usage in Code

### Simple Check

```php
class PaymentController
{
    private FeatureFlagService $flagService;
    
    public function processPayment(Request $request): Response
    {
        // Check if new payment flow is enabled
        if ($this->flagService->isEnabled('new_payment_flow', $request->getUser()->id)) {
            return $this->processNewPaymentFlow($request);
        }
        
        // Use old payment flow
        return $this->processOldPaymentFlow($request);
    }
}
```

### Conditional UI Rendering

```php
class ProductController
{
    private FeatureFlagService $flagService;
    
    public function index(Request $request): Response
    {
        $products = $this->productService->getAll();
        
        return view('products.index', [
            'products' => $products,
            'showExperimentalProducts' => $this->flagService->isEnabled(
                'experimental_products',
                $request->getUser()->id
            ),
            'enableDarkModeVariant' => $this->flagService->isEnabled(
                'dark_mode_variant',
                $request->getUser()->id
            )
        ]);
    }
}
```

---

## Flag Database Schema

### Feature Flags Table

```sql
CREATE TABLE feature_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('global', 'user', 'role', 'membership', 'percentage', 'time') NOT NULL DEFAULT 'global',
    enabled BOOLEAN DEFAULT FALSE,
    conditions JSON,
    percentage INT NULL,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (key),
    INDEX idx_type (type),
    INDEX idx_enabled (enabled),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Flag Usage Logs Table

```sql
CREATE TABLE feature_flag_usage_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    flag_key VARCHAR(100) NOT NULL,
    user_id INT NULL,
    result BOOLEAN NOT NULL,
    context JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_flag_key (flag_key),
    INDEX idx_user_id (user_id),
    INDEX idx_result (result),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (flag_key) REFERENCES feature_flags(key) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Flag Management UI

### Owner/Admin Dashboard

```
Feature Flags Dashboard
├── List all flags
├── Create new flag
├── Edit existing flag
├── Enable/Disable flag
├── Delete flag
├── View flag usage statistics
└── Export flag configuration
```

### Flag Creation Form

```
Flag Information
├── Key (unique identifier)
├── Name (display name)
├── Description
└── Type (dropdown)

Flag Configuration (based on type)
├── Global: Enable/Disable toggle
├── User: Select users
├── Role: Select roles
├── Membership: Select memberships
├── Percentage: Slider (0-100%)
└── Time: Start date, End date

Advanced Options
├── Expiration date
└── Rollback plan
```

---

## Flag Events

### Event Types

```php
class FeatureFlagCreated
{
    public function __construct(public string $key) {}
}

class FeatureFlagUpdated
{
    public function __construct(public string $key) {}
}

class FeatureFlagDeleted
{
    public function __construct(public string $key) {}
}

class FeatureFlagEnabled
{
    public function __construct(public string $key) {}
}

class FeatureFlagDisabled
{
    public function __construct(public string $key) {}
}
```

### Event Listeners

```php
class FeatureFlagListener
{
    private CacheInterface $cache;
    private NotificationService $notificationService;
    
    public function onFeatureFlagEnabled(FeatureFlagEnabled $event): void
    {
        // Clear cache
        $this->cache->deletePattern("feature_flag:{$event->key}:*");
        
        // Notify admins
        $this->notificationService->notifyAdmins(
            'Feature flag enabled',
            "Feature flag '{$event->key}' has been enabled"
        );
    }
    
    public function onFeatureFlagDisabled(FeatureFlagDisabled $event): void
    {
        // Clear cache
        $this->cache->deletePattern("feature_flag:{$event->key}:*");
        
        // Notify admins
        $this->notificationService->notifyAdmins(
            'Feature flag disabled',
            "Feature flag '{$event->key}' has been disabled"
        );
    }
}
```

---

## Flag Analytics

### Usage Tracking

```php
class FlagAnalyticsService
{
    private Database $db;
    
    public function logUsage(string $flagKey, bool $result, ?int $userId, array $context = []): void
    {
        $this->db->insert('feature_flag_usage_logs', [
            'flag_key' => $flagKey,
            'user_id' => $userId,
            'result' => $result,
            'context' => json_encode($context),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getUsageStats(string $flagKey, int $days = 30): array
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total_checks,
                SUM(CASE WHEN result = true THEN 1 ELSE 0 END) as enabled_count,
                SUM(CASE WHEN result = false THEN 1 ELSE 0 END) as disabled_count,
                COUNT(DISTINCT user_id) as unique_users
            FROM feature_flag_usage_logs
            WHERE flag_key = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ", [$flagKey, $days]);
        
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUsageTrend(string $flagKey, int $days = 30): array
    {
        $result = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as checks,
                SUM(CASE WHEN result = true THEN 1 ELSE 0 END) as enabled
            FROM feature_flag_usage_logs
            WHERE flag_key = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$flagKey, $days]);
        
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

---

## Example Feature Flags

### New Payment Flow

```php
$flagService->create('new_payment_flow', [
    'name' => 'New Payment Flow',
    'description' => 'Enable the new payment processing flow',
    'type' => 'percentage',
    'enabled' => true,
    'conditions' => [
        'percentage' => 10,
        'percentageBasedOn' => 'user_id'
    ]
]);
```

### Launcher Beta

```php
$flagService->create('launcher_beta', [
    'name' => 'Launcher Beta',
    'description' => 'Enable launcher beta features',
    'type' => 'user',
    'enabled' => true,
    'conditions' => [
        'userIds' => [1, 2, 3, 4, 5] // Beta testers
    ]
]);
```

### Experimental Products

```php
$flagService->create('experimental_products', [
    'name' => 'Experimental Products',
    'description' => 'Show experimental products in product list',
    'type' => 'role',
    'enabled' => true,
    'conditions' => [
        'roleSlugs' => ['admin', 'owner']
    ]
]);
```

### Dark Mode Variant

```php
$flagService->create('dark_mode_variant', [
    'name' => 'Dark Mode Variant',
    'description' => 'Enable new dark mode variant',
    'type' => 'percentage',
    'enabled' => true,
    'conditions' => [
        'percentage' => 50,
        'percentageBasedOn' => 'random'
    ]
]);
```

### Discord Login

```php
$flagService->create('discord_login', [
    'name' => 'Discord Login',
    'description' => 'Enable Discord OAuth login',
    'type' => 'time',
    'enabled' => true,
    'conditions' => [
        'startDate' => '2026-08-01 00:00:00',
        'endDate' => '2026-08-31 23:59:59'
    ]
]);
```

---

## Flag Best Practices

### Naming Conventions

- Use snake_case for flag keys
- Be descriptive but concise
- Prefix with feature category
- Examples: `payment_new_flow`, `launcher_beta`, `ui_dark_mode`

### Lifecycle Management

1. **Development**: Create flag, disable by default
2. **Testing**: Enable for test users
3. **Staging**: Enable for percentage rollout
4. **Production**: Gradual percentage increase
5. **Complete**: Remove flag code after full rollout

### Cleanup Strategy

```php
class FlagCleanupService
{
    public function cleanupOldFlags(int $days = 90): void
    {
        $flags = $this->flagService->all();
        
        foreach ($flags as $flag) {
            $daysSinceUpdate = (new DateTime())->diff($flag->updatedAt)->days;
            
            // Delete flags that haven't been updated in 90 days and are disabled
            if ($daysSinceUpdate > $days && !$flag->enabled) {
                $this->flagService->delete($flag->key);
            }
        }
    }
}
```

---

## Summary

The Feature Flag System provides:
- **Dynamic feature toggling** without code deployment
- **Multiple flag types** (global, user, role, membership, percentage, time)
- **Caching** for performance
- **Event-driven** flag changes
- **Usage analytics** and statistics
- **UI management** for owners/admins
- **Middleware** for route protection
- **A/B testing** capabilities
- **Gradual rollout** support
- **Time-based** feature scheduling

Feature flags enable:
- Safe feature rollouts
- A/B testing
- Beta testing
- Maintenance mode
- Experimental features
- User-specific features
- Role-based features
- Membership-based features
