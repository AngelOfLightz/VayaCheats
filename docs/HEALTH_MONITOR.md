# VayaCheats V3.1 - Health Monitor System

## Overview

The Health Monitor System provides comprehensive monitoring of all system components, services, and infrastructure. Every service exposes health information through standardized endpoints for real-time monitoring and alerting.

---

## Health Check Architecture

### Health Check Hierarchy

```
Health Monitor
├── System Health
│   ├── Database
│   ├── Queue
│   ├── Cache
│   ├── Storage
│   └── External Services
├── Application Health
│   ├── PHP
│   ├── Extensions
│   ├── Dependencies
│   └── Configuration
├── Infrastructure Health
│   ├── Disk Usage
│   ├── Memory Usage
│   ├── CPU Usage
│   └── Network
├── Service Health
│   ├── API Status
│   ├── Web Server
│   ├── SSL Certificate
│   └── Cron Jobs
└── Business Health
    ├── Active Users
    ├── Error Rate
    ├── Response Time
    └── Queue Depth
```

---

## Health Check Interface

### Health Check Interface

```php
interface HealthCheckInterface
{
    public function check(): HealthCheckResult;
    public function getName(): string;
    public function getCategory(): string;
}

class HealthCheckResult
{
    public string $status; // healthy, degraded, unhealthy
    public string $message;
    public array $metrics;
    public array $details;
    public DateTime $timestamp;
}
```

---

## System Health Checks

### Database Health Check

```php
class DatabaseHealthCheck implements HealthCheckInterface
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            // Test connection
            $start = microtime(true);
            $this->db->query("SELECT 1");
            $latency = (microtime(true) - $start) * 1000;
            
            // Get connection info
            $info = $this->db->query("SHOW STATUS LIKE 'Threads_connected'");
            $connections = $info->fetch(PDO::FETCH_ASSOC)['Value'];
            
            // Get slow queries
            $slow = $this->db->query("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueries = $slow->fetch(PDO::FETCH_ASSOC)['Value'];
            
            // Check replication lag (if applicable)
            $lag = $this->checkReplicationLag();
            
            $result->status = $latency < 100 ? 'healthy' : 'degraded';
            $result->message = 'Database connection successful';
            $result->metrics = [
                'latency_ms' => round($latency, 2),
                'connections' => (int) $connections,
                'slow_queries' => (int) $slowQueries,
                'replication_lag' => $lag
            ];
            $result->details = [
                'host' => $this->db->getHost(),
                'database' => $this->db->getDatabase(),
                'version' => $this->db->getVersion()
            ];
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'Database connection failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'database';
    }
    
    public function getCategory(): string
    {
        return 'system';
    }
    
    private function checkReplicationLag(): ?int
    {
        try {
            $result = $this->db->query("SHOW SLAVE STATUS");
            $status = $result->fetch(PDO::FETCH_ASSOC);
            
            if ($status && isset($status['Seconds_Behind_Master'])) {
                return (int) $status['Seconds_Behind_Master'];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
```

### Queue Health Check

```php
class QueueHealthCheck implements HealthCheckInterface
{
    private QueueService $queue;
    
    public function __construct(QueueService $queue)
    {
        $this->queue = $queue;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            // Get queue statistics
            $pending = $this->queue->getPendingCount();
            $processing = $this->queue->getProcessingCount();
            $failed = $this->queue->getFailedCount();
            $completed = $this->queue->getCompletedCount(3600); // Last hour
            
            // Check worker status
            $workers = $this->queue->getWorkerCount();
            $lastJobTime = $this->queue->getLastJobTime();
            
            // Determine status
            $total = $pending + $processing + $failed;
            
            if ($failed > 100) {
                $result->status = 'unhealthy';
                $result->message = 'Too many failed jobs';
            } elseif ($pending > 1000) {
                $result->status = 'degraded';
                $result->message = 'Queue backlog detected';
            } elseif ($workers === 0) {
                $result->status = 'unhealthy';
                $result->message = 'No active workers';
            } else {
                $result->status = 'healthy';
                $result->message = 'Queue operating normally';
            }
            
            $result->metrics = [
                'pending' => $pending,
                'processing' => $processing,
                'failed' => $failed,
                'completed_last_hour' => $completed,
                'workers' => $workers,
                'total' => $total
            ];
            $result->details = [
                'driver' => $this->queue->getDriver(),
                'last_job_time' => $lastJobTime ? $lastJobTime->format('Y-m-d H:i:s') : 'never'
            ];
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'Queue check failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'queue';
    }
    
    public function getCategory(): string
    {
        return 'system';
    }
}
```

### Cache Health Check

```php
class CacheHealthCheck implements HealthCheckInterface
{
    private CacheInterface $cache;
    
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            // Test read/write
            $testKey = 'health_check_test';
            $testValue = time();
            
            $start = microtime(true);
            $this->cache->set($testKey, $testValue, 60);
            $retrieved = $this->cache->get($testKey);
            $latency = (microtime(true) - $start) * 1000;
            
            $this->cache->delete($testKey);
            
            if ($retrieved !== $testValue) {
                $result->status = 'unhealthy';
                $result->message = 'Cache read/write failed';
            } elseif ($latency > 50) {
                $result->status = 'degraded';
                $result->message = 'Cache latency high';
            } else {
                $result->status = 'healthy';
                $result->message = 'Cache operating normally';
            }
            
            // Get cache statistics if available
            $stats = $this->getCacheStats();
            
            $result->metrics = [
                'latency_ms' => round($latency, 2),
                'hit_rate' => $stats['hit_rate'] ?? null,
                'memory_usage' => $stats['memory_usage'] ?? null,
                'keys_count' => $stats['keys_count'] ?? null
            ];
            $result->details = [
                'driver' => $this->cache->getDriver(),
                'version' => $stats['version'] ?? null
            ];
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'Cache check failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'cache';
    }
    
    public function getCategory(): string
    {
        return 'system';
    }
    
    private function getCacheStats(): array
    {
        // Implementation depends on cache driver
        return [];
    }
}
```

### Storage Health Check

```php
class StorageHealthCheck implements HealthCheckInterface
{
    private Filesystem $filesystem;
    
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            // Test write
            $testFile = 'health_check_test_' . time();
            $this->filesystem->put($testFile, 'test');
            
            // Test read
            $content = $this->filesystem->get($testFile);
            
            // Cleanup
            $this->filesystem->delete($testFile);
            
            if ($content !== 'test') {
                $result->status = 'unhealthy';
                $result->message = 'Storage read(write failed';
            } else {
                // Check disk usage
                $diskTotal = disk_total_space($this->filesystem->getPath());
                $diskFree = disk_free_space($this->filesystem->getPath());
                $diskUsed = $diskTotal - $diskFree;
                $diskPercent = ($diskUsed / $diskTotal) * 100;
                
                if ($diskPercent > 90) {
                    $result->status = 'unhealthy';
                    $result->message = 'Disk space critical';
                } elseif ($diskPercent > 75) {
                    $result->status = 'degraded';
                    $result->message = 'Disk space low';
                } else {
                    $result->status = 'healthy';
                    $result->message = 'Storage operating normally';
                }
                
                $result->metrics = [
                    'disk_total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
                    'disk_free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
                    'disk_used_gb' => round($diskUsed / 1024 / 1024 / 1024, 2),
                    'disk_percent' => round($diskPercent, 2)
                ];
                $result->details = [
                    'path' => $this->filesystem->getPath(),
                    'type' => $this->filesystem->getType()
                ];
            }
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'Storage check failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'storage';
    }
    
    public function getCategory(): string
    {
        return 'system';
    }
}
```

---

## Application Health Checks

### PHP Health Check

```php
class PHPHealthCheck implements HealthCheckInterface
{
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        $version = PHP_VERSION;
        $sapi = php_sapi_name();
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        if (!empty($missingExtensions)) {
            $result->status = 'unhealthy';
            $result->message = 'Missing PHP extensions: ' . implode(', ', $missingExtensions);
        } elseif (version_compare($version, '8.0.0', '<')) {
            $result->status = 'degraded';
            $result->message = 'PHP version outdated';
        } else {
            $result->status = 'healthy';
            $result->message = 'PHP operating normally';
        }
        
        $result->metrics = [
            'version' => $version,
            'sapi' => $sapi,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
        $result->details = [
            'extensions' => get_loaded_extensions(),
            'missing_extensions' => $missingExtensions
        ];
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'php';
    }
    
    public function getCategory(): string
    {
        return 'application';
    }
}
```

### SSL Certificate Health Check

```php
class SSLHealthCheck implements HealthCheckInterface
{
    private string $domain;
    
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $stream = stream_socket_client(
                'ssl://' . $this->domain . ':443',
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$stream) {
                $result->status = 'unhealthy';
                $result->message = 'SSL connection failed';
                $result->metrics = [];
                $result->details = [
                    'error' => $errstr
                ];
                return $result;
            }
            
            $cert = stream_context_get_params($stream)['options']['ssl']['peer_certificate'];
            $certData = openssl_x509_parse($cert);
            
            $validFrom = DateTime::createFromFormat('U', $certData['validFrom_time_t']);
            $validTo = DateTime::createFromFormat('U', $certData['validTo_time_t']);
            $now = new DateTime();
            $daysUntilExpiry = $now->diff($validTo)->days;
            
            if ($daysUntilExpiry < 0) {
                $result->status = 'unhealthy';
                $result->message = 'SSL certificate expired';
            } elseif ($daysUntilExpiry < 7) {
                $result->status = 'unhealthy';
                $result->message = 'SSL certificate expiring soon';
            } elseif ($daysUntilExpiry < 30) {
                $result->status = 'degraded';
                $result->message = 'SSL certificate expiring soon';
            } else {
                $result->status = 'healthy';
                $result->message = 'SSL certificate valid';
            }
            
            $result->metrics = [
                'days_until_expiry' => $daysUntilExpiry,
                'valid_from' => $validFrom->format('Y-m-d'),
                'valid_to' => $validTo->format('Y-m-d'),
                'issuer' => $certData['issuer']['O'] ?? 'Unknown'
            ];
            $result->details = [
                'domain' => $this->domain,
                'subject' => $certData['subject']['CN'] ?? 'Unknown',
                'signature_algorithm' => $certData['signatureTypeSN'] ?? 'Unknown'
            ];
            
            fclose($stream);
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'SSL check failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'ssl';
    }
    
    public function getCategory(): string
    {
        return 'infrastructure';
    }
}
```

---

## Business Health Checks

### API Status Health Check

```php
class APIStatusHealthCheck implements HealthCheckInterface
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            // Get API statistics from last hour
            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as server_errors,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as client_errors,
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time
                FROM audit_api_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            
            $data = $stats->fetch(PDO::FETCH_ASSOC);
            
            $errorRate = $data['total_requests'] > 0 
                ? (($data['server_errors'] + $data['client_errors']) / $data['total_requests']) * 100 
                : 0;
            
            if ($errorRate > 10) {
                $result->status = 'unhealthy';
                $result->message = 'High API error rate';
            } elseif ($errorRate > 5) {
                $result->status = 'degraded';
                $result->message = 'Elevated API error rate';
            } elseif ($data['avg_response_time'] > 1000) {
                $result->status = 'degraded';
                $result->message = 'High API response time';
            } else {
                $result->status = 'healthy';
                $result->message = 'API operating normally';
            }
            
            $result->metrics = [
                'total_requests' => (int) $data['total_requests'],
                'server_errors' => (int) $data['server_errors'],
                'client_errors' => (int) $data['client_errors'],
                'error_rate_percent' => round($errorRate, 2),
                'avg_response_time_ms' => round($data['avg_response_time'], 2),
                'max_response_time_ms' => round($data['max_response_time'], 2)
            ];
            $result->details = [
                'period' => '1 hour'
            ];
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'API status check failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'api_status';
    }
    
    public function getCategory(): string
    {
        return 'business';
    }
}
```

### Cron Job Health Check

```php
class CronJobHealthCheck implements HealthCheckInterface
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function check(): HealthCheckResult
    {
        $result = new HealthCheckResult();
        $result->timestamp = new DateTime();
        
        try {
            // Get cron job execution history
            $stats = $this->db->query("
                SELECT 
                    job_name,
                    MAX(last_run_at) as last_run,
                    MAX(status) as last_status,
                    COUNT(*) as total_runs,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_runs
                FROM cron_job_logs
                WHERE last_run_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY job_name
            ");
            
            $jobs = $stats->fetchAll(PDO::FETCH_ASSOC);
            
            $failedJobs = array_filter($jobs, fn($job) => $job['last_status'] === 'failed');
            $stalledJobs = array_filter($jobs, fn($job) => 
                (new DateTime())->diff(new DateTime($job['last_run']))->h > 2
            );
            
            if (!empty($failedJobs)) {
                $result->status = 'unhealthy';
                $result->message = 'Failed cron jobs detected';
            } elseif (!empty($stalledJobs)) {
                $result->status = 'degraded';
                $result->message = 'Stalled cron jobs detected';
            } else {
                $result->status = 'healthy';
                $result->message = 'Cron jobs operating normally';
            }
            
            $result->metrics = [
                'total_jobs' => count($jobs),
                'failed_jobs' => count($failedJobs),
                'stalled_jobs' => count($stalledJobs),
                'total_runs' => array_sum(array_column($jobs, 'total_runs'))
            ];
            $result->details = [
                'jobs' => $jobs
            ];
            
        } catch (Exception $e) {
            $result->status = 'unhealthy';
            $result->message = 'Cron job check failed: ' . $e->getMessage();
            $result->metrics = [];
            $result->details = [
                'error' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    public function getName(): string
    {
        return 'cron_jobs';
    }
    
    public function getCategory(): string
    {
        return 'service';
    }
}
```

---

## Health Monitor Service

 ### Health Monitor Service

```php
class HealthMonitorService
{
    private array $checks = [];
    private EventDispatcher $events;
    
    public function __construct(EventDispatcher $events)
    {
        $this->events = $events;
    }
    
    public function register(HealthCheckInterface $check): void
    {
        $this->checks[$check->getName()] = $check;
    }
    
    public function check(string $name): ?HealthCheckResult
    {
        if (!isset($this->checks[$name])) {
            return null;
        }
        
        return $this->checks[$name]->check();
    }
    
    public function checkAll(): array
    {
        $results = [];
        
        foreach ($this->checks as $name => $check) {
            $results[$name] = $check->check();
        }
        
        return $results;
    }
    
    public function checkByCategory(string $category): array
    {
        $results = [];
        
        foreach ($this->checks as $name => $check) {
            if ($check->getCategory() === $category) {
                $results[$name] = $check->check();
            }
        }
        
        return $results;
    }
    
    public function getOverallStatus(): string
    {
        $results = $this->checkAll();
        
        $unhealthy = array_filter($results, fn($r) => $r->status === 'unhealthy');
        $degraded = array_filter($results, fn($r) => $r->status === 'degraded');
        
        if (!empty($unhealthy)) {
            return 'unhealthy';
        }
        
        if (!empty($degraded)) {
            return 'degraded';
        }
        
        return 'healthy';
    }
    
    public function getHealthSummary(): array
    {
        $results = $this->checkAll();
        
        $summary = [
            'overall_status' => $this->getOverallStatus(),
            'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
            'checks' => [],
            'by_category' => []
        ];
        
        foreach ($results as $name => $result) {
            $summary['checks'][$name] = [
                'status' => $result->status,
                'message' => $result->message,
                'category' => $result->details['category'] ?? 'unknown'
            ];
        }
        
        // Group by category
        foreach ($results as $name => $result) {
            $category = $result->getCategory();
            
            if (!isset($summary['by_category'][$category])) {
                $summary['by_category'][$category] = [
                    'status' => 'healthy',
                    'checks' => []
                ];
            }
            
            $summary['by_category'][$category]['checks'][$name] = $result->status;
            
            if ($result->status === 'unhealthy') {
                $summary['by_category'][$category]['status'] = 'unhealthy';
            } elseif ($result->status === 'degraded' && $summary['by_category'][$category]['status'] !== 'unhealthy') {
                $summary['by_category'][$category]['status'] = 'degraded';
            }
        }
        
        return $summary;
    }
}
```

---

## Health Check Endpoints

### Public Health Endpoint

```
GET /health
Response: {
    "status": "healthy",
    "timestamp": "2026-07-31T20:00:00Z",
    "checks": {
        "database": "healthy",
        "cache": "healthy",
        "queue": "healthy"
    }
}
```

### Detailed Health Endpoint

```
GET /health/detailed
Response: {
    "overall_status": "healthy",
    "timestamp": "2026-07-31T20:00:00Z",
    "checks": {
        "database": {
            "status": "healthy",
            "message": "Database connection successful",
            "metrics": {...},
            "details": {...}
        },
        ...
    }
}
```

### Category Health Endpoint

```
GET /health/{category}
Response: {
    "category": "system",
    "status": "healthy",
    "checks": {...}
}
```

---

## Health Alerting

### Alert Rules

```php
class HealthAlertService
{
    private HealthMonitorService $monitor;
    private NotificationService $notificationService;
    private array $alertRules = [
        'database.unhealthy' => ['immediate', 'owner', 'admin'],
        'queue.failed_high' => ['immediate', 'owner', 'admin'],
        'disk.critical' => ['immediate', 'owner', 'admin'],
        'ssl.expiring_soon' => ['daily', 'owner'],
        'api.high_error_rate' => ['immediate', 'owner', 'admin'],
    ];
    
    public function checkAlerts(): void
    {
        $results = $this->monitor->checkAll();
        
        foreach ($results as $name => $result) {
            $alertKey = "{$name}.{$result->status}";
            
            if (isset($this->alertRules[$alertKey])) {
                $rule = $this->alertRules[$alertKey];
                $this->sendAlert($name, $result, $rule);
            }
        }
    }
    
    private function sendAlert(string $name, HealthCheckResult $result, array $rule): void
    {
        $message = "Health check '{$name}' is {$result->status}: {$result->message}";
        
        foreach ($rule[1] as $role) {
            $this->notificationService->notifyRole($role, 'Health Alert', $message);
        }
    }
}
```

---

## Health Database Schema

### Health Check Logs Table

```sql
CREATE TABLE health_check_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    check_name VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'degraded', 'unhealthy') NOT NULL,
    message TEXT,
    metrics JSON,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_check_name (check_name),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Health Alerts Table

```sql
CREATE TABLE health_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    check_name VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    message TEXT,
    sent_to JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by INT NULL,
    
    INDEX idx_check_name (check_name),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Summary

The Health Monitor System provides:
- **Comprehensive health checks** for all system components
- **Standardized health check interface** for consistency
- **Categorized health checks** (system, application, infrastructure, service, business)
- **Real-time monitoring** through HTTP endpoints
- **Health metrics** collection and tracking
- **Alert system** with configurable rules
- **Historical logging** of health check results
- **Overall health status** aggregation
- **Category-based health** reporting

Monitored components:
- Database, Queue, Cache, Storage
- PHP, Extensions, Configuration
- Disk Usage, Memory Usage, CPU Usage
- API Status, Web Server, SSL Certificate
- Cron Jobs, Background Jobs, Scheduler
