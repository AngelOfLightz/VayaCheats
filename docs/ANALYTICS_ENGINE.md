# VayaCheats V3.1 - Analytics Engine

## Overview

The Analytics Engine expands VayaCheats into a complete Business Intelligence module, tracking comprehensive metrics across revenue, users, downloads, licenses, and system performance with real-time dashboards and future chart support.

---

## Analytics Categories

### 1. Revenue Analytics
- Total revenue (all time, monthly, daily)
- Revenue by gateway
- Revenue by plan
- Revenue by country
- Revenue trend
- Average order value
- Refund rate
- Recurring revenue (MRR)

### 2. User Analytics
- Total users
- Daily Active Users (DAU)
- Monthly Active Users (MAU)
- User retention rate
- User churn rate
- User growth rate
- User demographics
- User acquisition channels
- User lifetime value (LTV)

### 3. Download Analytics
- Total downloads
- Downloads by product
- Downloads by plan
- Downloads by country
- Download success rate
- Download trends
- Peak download times
- Bandwidth usage

### 4. License Analytics
- Total licenses
- Active licenses
- Expired licenses
- License activations
- License expirations
- License by plan
- License retention
- License churn

### 5. Conversion Analytics
- Registration to purchase conversion
- Free to paid conversion
- Plan upgrade conversion
- Funnel analysis
- Cart abandonment
- Conversion by channel

### 6. Product Analytics
- Top products by downloads
- Top products by revenue
- Product popularity trends
- Product rating trends
- Product view-to-download ratio

### 7. Search Analytics
- Search trends
- Top search terms
- Search result clicks
- Search conversion rate
- Zero-result searches

### 8. Payment Analytics
- Payment success rate
- Payment failure rate
- Payment gateway performance
- Payment processing time
- Payment method distribution

### 9. Session Analytics
- Session length
- Pages per session
- Bounce rate
- Return visitor rate
- Session trends

### 10. Geographic Analytics
- Country distribution
- City distribution
- Region distribution
- Language distribution

### 11. Device Analytics
- Device type distribution
- Operating system distribution
- Browser distribution
- Screen resolution distribution

### 12. System Analytics
- API response times
- Error rates
- Queue depth
- Database performance
- Cache hit rate
- Server load

---

## Analytics Architecture

### Data Collection Layer

```
Events
    ↓
Event Dispatcher
    ↓
Analytics Collectors
    ↓
Data Aggregation
    ↓
Time-Series Database
    ↓
Analytics Engine
    ↓
Dashboard Widgets
```

### Analytics Service

```php
class AnalyticsService
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
    
    public function trackEvent(string $event, array $data, ?int $userId = null): void
    {
        $this->db->insert('analytics_events', [
            'event_type' => $event,
            'user_id' => $userId,
            'data' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getRevenueStats(int $days = 30): array
    {
        $cacheKey = "analytics:revenue:{$days}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $result = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                SUM(amount) as revenue,
                COUNT(*) as transactions,
                AVG(amount) as avg_order_value
            FROM payments
            WHERE status = 'completed'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$days]);
        
        $data = $result->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $totalRevenue = array_sum(array_column($data, 'revenue'));
        $totalTransactions = array_sum(array_column($data, 'transactions'));
        
        $stats = [
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'avg_order_value' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
            'daily_data' => $data
        ];
        
        $this->cache->set($cacheKey, $stats, 300); // 5 minutes
        
        return $stats;
    }
    
    public function getUserStats(int $days = 30): array
    {
        $cacheKey = "analytics:users:{$days}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // Total users
        $totalUsers = $this->db->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC)['count'];
        
        // New users in period
        $newUsers = $this->db->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ", [$days])->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Daily Active Users
        $dau = $this->calculateDAU($days);
        
        // Monthly Active Users
        $mau = $this->calculateMAU();
        
        // Retention rate
        $retentionRate = $this->calculateRetentionRate($days);
        
        $stats = [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'dau' => $dau,
            'mau' => $mau,
            'retention_rate' => $retentionRate,
            'growth_rate' => $totalUsers > 0 ? ($newUsers / $totalUsers) * 100 : 0
        ];
        
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    public function getDownloadStats(int $days = 30): array
    {
        $cacheKey = "analytics:downloads:{$days}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $result = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as downloads,
                SUM(file_size) as bandwidth
            FROM downloads
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$days]);
        
        $data = $result->fetchAll(PDO::FETCH_ASSOC);
        
        // Downloads by product
        $byProduct = $this->db->query("
            SELECT 
                p.name as product_name,
                COUNT(*) as downloads
            FROM downloads d
            JOIN products p ON d.product_id = p.id
            WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY p.id, p.name
            ORDER BY downloads DESC
            LIMIT 10
        ", [$days])->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total_downloads' => array_sum(array_column($data, 'downloads')),
            'total_bandwidth' => array_sum(array_column($data, 'bandwidth')),
            'daily_data' => $data,
            'top_products' => $byProduct
        ];
        
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    public function getLicenseStats(int $days = 30): array
    {
        $cacheKey = "analytics:licenses:{$days}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // Total licenses
        $totalLicenses = $this->db->query("SELECT COUNT(*) as count FROM licenses")->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Active licenses
        $activeLicenses = $this->db->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Expired licenses in period
        $expiredLicenses = $this->db->query("
            SELECT COUNT(*) as count 
            FROM licenses 
            WHERE status = 'expired'
            AND updated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ", [$days])->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Licenses by plan
        $byPlan = $this->db->query("
            SELECT 
                lp.name as plan_name,
                COUNT(*) as count
            FROM licenses l
            JOIN license_plans lp ON l.plan_id = lp.id
            GROUP BY lp.id, lp.name
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total_licenses' => $totalLicenses,
            'active_licenses' => $activeLicenses,
            'expired_licenses' => $expiredLicenses,
            'by_plan' => $byPlan,
            'activation_rate' => $totalLicenses > 0 ? ($activeLicenses / $totalLicenses) * 100 : 0
        ];
        
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    public function getGeographicStats(int $days = 30): array
    {
        $cacheKey = "analytics:geographic:{$days}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // By country
        $byCountry = $this->db->query("
            SELECT 
                country,
                COUNT(*) as count
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND country IS NOT NULL
            GROUP BY country
            ORDER BY count DESC
            LIMIT 20
        ", [$days])->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'by_country' => $byCountry
        ];
        
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    public function getDeviceStats(int $days = 30): array
    {
        $cacheKey = "analytics:device:{$days}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // Parse user agents and aggregate
        $result = $this->db->query("
            SELECT user_agent
            FROM audit_login_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND user_agent IS NOT NULL
        ", [$days]);
        
        $userAgents = $result->fetchAll(PDO::FETCH_ASSOC);
        
        $browsers = [];
        $oses = [];
        
        foreach ($userAgents as $row) {
            $ua = $row['user_agent'];
            
            // Simple parsing (in production, use proper UA parser)
            if (str_contains($ua, 'Chrome')) $browsers['Chrome'] = ($browsers['Chrome'] ?? 0) + 1;
            elseif (str_contains($ua, 'Firefox')) $browsers['Firefox'] = ($browsers['Firefox'] ?? 0) + 1;
            elseif (str_contains($ua, 'Safari')) $browsers['Safari'] = ($browsers['Safari'] ?? 0) + 1;
            elseif (str_contains($ua, 'Edge')) $browsers['Edge'] = ($browsers['Edge'] ?? 0) + 1;
            
            if (str_contains($ua, 'Windows')) $oses['Windows'] = ($oses['Windows'] ?? 0) + 1;
            elseif (str_contains($ua, 'Mac')) $oses['macOS'] = ($oses['macOS'] ?? 0) + 1;
            elseif (str_contains($ua, 'Linux')) $oses['Linux'] = ($oses['Linux'] ?? 0) + 1;
            elseif (str_contains($ua, 'Android')) $oses['Android'] = ($oses['Android'] ?? 0) + 1;
            elseif (str_contains($ua, 'iOS')) $oses['iOS'] = ($oses['iOS'] ?? 0) + 1;
        }
        
        $stats = [
            'browsers' => $browsers,
            'operating_systems' => $oses
        ];
        
        $this->cache->set($cacheKey, $stats, 300);
        
        return $stats;
    }
    
    public function getSystemStats(): array
    {
        $cacheKey = "analytics:system";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // API response times
        $apiStats = $this->db->query("
            SELECT 
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                COUNT(*) as total_requests
            FROM audit_api_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Error rate
        $errorStats = $this->db->query("
            SELECT 
                COUNT(*) as total_errors
            FROM audit_error_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Queue depth
        $queueDepth = $this->db->query("
            SELECT COUNT(*) as count
            FROM queue_jobs
            WHERE status = 'pending'
        ")->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stats = [
            'api_avg_response_time' => round($apiStats['avg_response_time'], 2),
            'api_max_response_time' => round($apiStats['max_response_time'], 2),
            'api_total_requests' => $apiStats['total_requests'],
            'error_count' => $errorStats['total_errors'],
            'queue_depth' => $queueDepth
        ];
        
        $this->cache->set($cacheKey, $stats, 60); // 1 minute
        
        return $stats;
    }
    
    private function calculateDAU(int $days): int
    {
        $result = $this->db->query("
            SELECT COUNT(DISTINCT user_id) as count
            FROM audit_login_logs
            WHERE action = 'login_success'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        
        return $result->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function calculateMAU(): int
    {
        $result = $this->db->query("
            SELECT COUNT(DISTINCT user_id) as count
            FROM audit_login_logs
            WHERE action = 'login_success'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $result->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function calculateRetentionRate(int $days): float
    {
        // Calculate retention rate (simplified)
        $result = $this->db->query("
            SELECT 
                COUNT(DISTINCT CASE WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN user_id END) as retained,
                COUNT(*) as total
            FROM (
                SELECT user_id, MAX(created_at) as last_login_at
                FROM audit_login_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY user_id
            ) as logins
        ", [$days]);
        
        $data = $result->fetch(PDO::FETCH_ASSOC);
        
        return $data['total'] > 0 ? ($data['retained'] / $data['total']) * 100 : 0;
    }
}
```

---

## Dashboard Widgets

### Widget Types

### 1. Counter Widget

```php
class CounterWidget
{
    private string $title;
    private string $value;
    private string $prefix;
    private string $suffix;
    private string $trend; // up, down, neutral
    private float $trendValue;
    
    public function render(): array
    {
        return [
            'type' => 'counter',
            'title' => $this->title,
            'value' => $this->value,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'trend' => $this->trend,
            'trend_value' => $this->trendValue
        ];
    }
}
```

### 2. Chart Widget

```php
class ChartWidget
{
    private string $title;
    private string $type; // line, bar, pie, area
    private array $data;
    private array $labels;
    private string $xAxis;
    private string $yAxis;
    
    public function render(): array
    {
        return [
            'type' => 'chart',
            'chart_type' => $this->type,
            'title' => $this->title,
            'data' => $this->data,
            'labels' => $this->labels,
            'x_axis' => $this->xAxis,
            'y_axis' => $this->yAxis
        ];
    }
}
```

### 3. Table Widget

```php
class TableWidget
{
    private string $title;
    private array $columns;
    private array $rows;
    private bool $sortable;
    private bool $filterable;
    
    public function render(): array
    {
        return [
            'type' => 'table',
            'title' => $this->title,
            'columns' => $this->columns,
            'rows' => $this->rows,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable
        ];
    }
}
```

### 4. Gauge Widget

```php
class GaugeWidget
{
    private string $title;
    private float $value;
    private float $min;
    private float $max;
    private array $thresholds;
    
    public function render(): array
    {
        return [
            'type' => 'gauge',
            'title' => $this->title,
            'value' => $this->value,
            'min' => $this->min,
            'max' => $this->max,
            'thresholds' => $this->thresholds
        ];
    }
}
```

---

## Dashboard Configuration

### Dashboard Definition

```php
class Dashboard
{
    private string $name;
    private string $description;
    private array $widgets;
    private string $refreshInterval;
    
    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
        $this->widgets = [];
        $this->refreshInterval = '5m';
    }
    
    public function addWidget(Widget $widget, string $position): void
    {
        $this->widgets[$position] = $widget;
    }
    
    public function render(): array
    {
        $renderedWidgets = [];
        
        foreach ($this->widgets as $position => $widget) {
            $renderedWidgets[$position] = $widget->render();
        }
        
        return [
            'name' => $this->name,
            'description' => $this->description,
            'refresh_interval' => $this->refreshInterval,
            'widgets' => $renderedWidgets
        ];
    }
}
```

### Owner Dashboard

```php
class OwnerDashboard extends Dashboard
{
    private AnalyticsService $analytics;
    
    public function __construct(AnalyticsService $analytics)
    {
        parent::__construct('Owner Dashboard', 'Complete system overview for owners');
        $this->analytics = $analytics;
        
        $this->buildWidgets();
    }
    
    private function buildWidgets(): void
    {
        // Revenue counter
        $revenueStats = $this->analytics->getRevenueStats(30);
        $this->addWidget(new CounterWidget(
            'Total Revenue (30d)',
            '$' . number_format($revenueStats['total_revenue'], 2),
            '',
            '',
            'up',
            15.5
        ), 'revenue_counter');
        
        // Active users counter
        $userStats = $this->analytics->getUserStats(30);
        $this->addWidget(new CounterWidget(
            'Active Users',
            $userStats['dau'],
            '',
            '',
            'up',
            8.2
        ), 'users_counter');
        
        // Downloads counter
        $downloadStats = $this->analytics->getDownloadStats(30);
        $this->addWidget(new CounterWidget(
            'Downloads (30d)',
            $downloadStats['total_downloads'],
            '',
            '',
            'up',
            12.3
        ), 'downloads_counter');
        
        // Revenue chart
        $this->addWidget(new ChartWidget(
            'Revenue Trend',
            'line',
            array_column($revenueStats['daily_data'], 'revenue'),
            array_column($revenueStats['daily_data'], 'date'),
            'Date',
            'Revenue ($)'
        ), 'revenue_chart');
        
        // Downloads by product table
        $this->addWidget(new TableWidget(
            'Top Products',
            ['Product', 'Downloads'],
            array_map(fn($item) => [$item['product_name'], $item['downloads']], $downloadStats['top_products']),
            true,
            false
        ), 'products_table');
        
        // System health gauge
        $systemStats = $this->analytics->getSystemStats();
        $healthScore = 100 - min($systemStats['error_count'] * 10, 100);
        $this->addWidget(new GaugeWidget(
            'System Health',
            $healthScore,
            0,
            100,
            [
                ['value' => 80, 'color' => 'green'],
                ['value' => 50, 'color' => 'yellow'],
                ['value' => 0, 'color' => 'red']
            ]
        ), 'health_gauge');
    }
}
```

---

## Real-Time Analytics

### WebSocket Integration

```php
class RealTimeAnalytics
{
    private WebSocketServer $server;
    private AnalyticsService $analytics;
    
    public function __construct(WebSocketServer $server, AnalyticsService $analytics)
    {
        $this->server = $server;
        $this->analytics = $analytics;
    }
    
    public function start(): void
    {
        $this->server->on('connection', function ($connection) {
            $connection->send(json_encode([
                'type' => 'connected',
                'message' => 'Connected to real-time analytics'
            ]));
        });
        
        $this->server->on('message', function ($connection, $message) {
            $data = json_decode($message, true);
            
            switch ($data['type']) {
                case 'subscribe':
                    $this->handleSubscribe($connection, $data);
                    break;
                case 'unsubscribe':
                    $this->handleUnsubscribe($connection, $data);
                    break;
            }
        });
        
        // Broadcast updates every 30 seconds
        $this->server->loop()->addPeriodicTimer(30, function () {
            $this->broadcastUpdates();
        });
        
        $this->server->run();
    }
    
    private function broadcastUpdates(): void
    {
        $updates = [
            'revenue' => $this->analytics->getRevenueStats(1),
            'users' => $this->analytics->getUserStats(1),
            'downloads' => $this->analytics->getDownloadStats(1),
            'system' => $this->analytics->getSystemStats()
        ];
        
        $this->server->broadcast(json_encode([
            'type' => 'update',
            'data' => $updates,
            'timestamp' => time()
        ]));
    }
}
```

---

## Analytics Database Schema

### Analytics Events Table

```sql
CREATE TABLE analytics_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(64) NULL,
    data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Analytics Aggregates Table

```sql
CREATE TABLE analytics_aggregates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_type ENUM('counter', 'gauge', 'histogram') NOT NULL,
    dimension VARCHAR(50),
    value DECIMAL(20, 4),
    count INT,
    min_value DECIMAL(20, 4),
    max_value DECIMAL(20, 4),
    sum_value DECIMAL(20, 4),
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_metric (metric_name, dimension, period_start, period_end),
    INDEX idx_metric_name (metric_name),
    INDEX idx_period_start (period_start),
    INDEX idx_period_end (period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Dashboard Configurations Table

```sql
CREATE TABLE dashboard_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    config JSON NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_role (role),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Analytics API Endpoints

```
GET /api/v1/owner/analytics/revenue
Query Params: days, start_date, end_date
Response: { revenue: {}, by_gateway: {}, by_plan: {} }

GET /api/v1/owner/analytics/users
Query Params: days, start_date, end_date
Response: { total_users, new_users, dau, mau, retention_rate }

GET /api/v1/owner/analytics/downloads
Query Params: days, start_date, end_date
Response: { total_downloads, by_product, by_country }

GET /api/v1/owner/analytics/licenses
Query Params: days, start_date, end_date
Response: { total_licenses, active_licenses, by_plan }

GET /api/v1/owner/analytics/geographic
Query Params: days, start_date, end_date
Response: { by_country, by_region }

GET /api/v1/owner/analytics/devices
Query Params: days, start_date, end_date
Response: { browsers, operating_systems }

GET /api/v1/owner/analytics/system
Response: { api_response_time, error_rate, queue_depth }

GET /api/v1/owner/analytics/dashboard/{name}
Response: { widgets: [] }

POST /api/v1/owner/analytics/dashboard/{name}
Request: { widgets: [] }
Response: { success }
```

---

## Analytics Best Practices

### Data Collection

1. **Event Sampling**: Sample high-volume events to reduce storage
2. **Batch Processing**: Aggregate events in batches
3. **Time Partitioning**: Partition tables by date for performance
4. **Data Retention**: Define retention policies for different event types
5. **Privacy**: Anonymize sensitive user data

### Performance

1. **Caching**: Cache aggregate calculations
2. **Pre-aggregation**: Pre-calculate common metrics
3. **Materialized Views**: Use materialized views for complex queries
4. **Indexing**: Proper indexes on time-series data
5. **Query Optimization**: Optimize slow queries

### Accuracy

1. **Data Validation**: Validate data before storage
2. **Deduplication**: Remove duplicate events
3. **Consistency**: Ensure consistent time zones
4. **Reconciliation**: Reconcile data with source systems

---

## Summary

The Analytics Engine provides:
- **Comprehensive metrics** across revenue, users, downloads, licenses
- **Real-time dashboards** with configurable widgets
- **Time-series data** collection and aggregation
- **Geographic analytics** for user distribution
- **Device analytics** for browser/OS statistics
- **System analytics** for performance monitoring
- **WebSocket support** for real-time updates
- **Caching** for performance optimization
- **API endpoints** for data access
- **Dashboard configuration** for custom views

Analytics categories:
- Revenue, Users, Downloads, Licenses
- Conversion, Products, Search, Payments
- Session, Geographic, Device, System
