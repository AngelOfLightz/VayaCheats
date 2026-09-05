# VayaCheats V3.0 - Audit Logging Flow

## Overview

The audit logging system provides comprehensive, granular logging of all system events across 13 separate log systems. Each log type is stored in its own table for optimal performance and query efficiency.

---

## Audit Log Categories

### 1. User Logs
- Profile changes
- Password changes
- Email changes
- Avatar uploads
- Settings updates

### 2. Admin Logs
- User management actions
- Role changes
- Ban/mute actions
- License revocations
- System configuration changes

### 3. Owner Logs
- Owner management
- System-critical operations
- Database operations
- Security overrides
- Financial operations

### 4. Login Logs
- Successful logins
- Failed logins
- Logout events
- Password resets
- Password changes
- Session creation

### 5. License Logs
- License generation
- License activation
- License deactivation
- License expiration
- License revocation
- License renewal
- License upgrades/downgrades

### 6. Payment Logs
- Payment initiation
- Payment completion
- Payment failure
- Payment refunds
- Payment cancellations
- Gateway events

### 7. Download Logs
- Download started
- Download completed
- Download failed
- Download cancelled
- License validation
- File access

### 8. Security Logs
- Brute force detection
- SQL injection attempts
- CSRF failures
- Invalid tokens
- Rate limit exceeded
- Permission denied
- Suspicious activity

### 9. API Logs
- API requests
- API responses
- Authentication failures
- Authorization failures
- Rate limiting
- API key usage

### 10. Moderation Logs
- User bans
- User unbans
- User mutes
- User unmutes
- Comment deletions
- Comment pins
- User warnings

### 11. Mail Logs
- Email queued
- Email sent
- Email failed
- Email bounced
- Template usage
- Delivery tracking

### 12. Product Logs
- Product creation
- Product updates
- Product deletion
- Status changes
- Version additions
- Feature changes
- Publishing/unpublishing

### 13. System Logs
- Cron job execution
- Queue job processing
- Cache operations
- Database migrations
- System health checks
- Maintenance mode toggles

### 14. Error Logs
- Application errors
- Exceptions
- Warnings
- Fatal errors
- Stack traces
- Error context

---

## Audit Log Structure

### Common Fields

Every audit log entry contains:

```php
class AuditLog
{
    public int $id;
    public int $actor_id;          // Who performed the action
    public int $target_id;         // Who/what was affected
    public string $target_type;    // Type of target (user, product, etc.)
    public string $action;          // Action performed
    public string $severity;       // info, warning, error, critical
    public string $ip_address;     // Actor's IP
    public string $user_agent;     // Actor's user agent
    public string $description;     // Human-readable description
    public array $metadata;        // Additional context
    public DateTime $created_at;   // When it happened
}
```

---

## Audit Logging Flow

### Synchronous Logging (Critical Events)

```
Action Performed
    ↓
AuditService::log()
    ↓
Validate Input
    ↓
Create Log Entry
    ↓
Insert into Database
    ↓
Return
```

### Asynchronous Logging (Non-Critical Events)

```
Action Performed
    ↓
AuditService::logAsync()
    ↓
Queue: CreateAuditLogJob
    ↓
Job Processed by Worker
    ↓
Create Log Entry
    ↓
Insert into Database
    ↓
Return
```

---

## Audit Service Implementation

### Core Audit Service

```php
class AuditService
{
    private array $loggers = [];
    private QueueService $queue;
    
    public function __construct(QueueService $queue)
    {
        $this->queue = $queue;
        
        // Register loggers
        $this->registerLogger('user', new UserLogger());
        $this->registerLogger('admin', new AdminLogger());
        $this->registerLogger('owner', new OwnerLogger());
        $this->registerLogger('login', new LoginLogger());
        $this->registerLogger('license', new LicenseLogger());
        $this->registerLogger('payment', new PaymentLogger());
        $this->registerLogger('download', new DownloadLogger());
        $this->registerLogger('security', new SecurityLogger());
        $this->registerLogger('api', new APILogger());
        $this->registerLogger('moderation', new ModerationLogger());
        $this->registerLogger('mail', new MailLogger());
        $this->registerLogger('product', new ProductLogger());
        $this->registerLogger('system', new SystemLogger());
        $this->registerLogger('error', new ErrorLogger());
    }
    
    public function log(string $type, array $data): void
    {
        $logger = $this->loggers[$type] ?? null;
        
        if (!$logger) {
            throw new InvalidArgumentException("Unknown log type: {$type}");
        }
        
        // Critical events log synchronously
        if ($this->isCritical($data['action'])) {
            $logger->log($data);
        } else {
            // Non-critical events log asynchronously
            $this->queue->dispatch(new CreateAuditLogJob($type, $data));
        }
    }
    
    private function isCritical(string $action): bool
    {
        $criticalActions = [
            'owner_login',
            'owner_action',
            'security_breach',
            'payment_completed',
            'license_generated'
        ];
        
        return in_array($action, $criticalActions);
    }
}
```

### Base Logger

```php
abstract class BaseLogger
{
    protected Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    abstract protected function getTable(): string;
    
    public function log(array $data): void
    {
        $table = $this->getTable();
        
        $this->db->insert($table, [
            'actor_id' => $data['actor_id'] ?? null,
            'target_id' => $data['target_id'] ?? null,
            'target_type' => $data['target_type'] ?? null,
            'action' => $data['action'],
            'severity' => $data['severity'] ?? 'info',
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
            'description' => $data['description'] ?? '',
            'metadata' => json_encode($data['metadata'] ?? []),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

### Specific Logger Implementation

```php
class SecurityLogger extends BaseLogger
{
    protected function getTable(): string
    {
        return 'audit_security_logs';
    }
    
    public function logBruteForce(int $userId, string $ipAddress): void
    {
        $this->log([
            'actor_id' => $userId,
            'action' => 'brute_force_detected',
            'target_type' => 'user',
            'target_id' => $userId,
            'severity' => 'critical',
            'ip_address' => $ipAddress,
            'description' => 'Brute force attack detected',
            'metadata' => [
                'attempt_count' => 5,
                'timeframe' => '1 minute'
            ]
        ]);
    }
    
    public function logSQLInjectionAttempt(string $ipAddress, string $payload): void
    {
        $this->log([
            'actor_id' => null,
            'action' => 'sql_injection_attempt',
            'target_type' => 'system',
            'target_id' => null,
            'severity' => 'critical',
            'ip_address' => $ipAddress,
            'description' => 'SQL injection attempt detected',
            'metadata' => [
                'payload' => $payload,
                'sanitized' => true
            ]
        ]);
    }
    
    public function logPermissionDenied(int $userId, string $permission): void
    {
        $this->log([
            'actor_id' => $userId,
            'action' => 'permission_denied',
            'target_type' => 'permission',
            'target_id' => null,
            'severity' => 'warning',
            'description' => "Permission denied: {$permission}",
            'metadata' => [
                'permission' => $permission
            ]
        ]);
    }
}
```

---

## Audit Job Implementation

### CreateAuditLogJob

```php
class CreateAuditLogJob extends Job
{
    public function __construct(
        public string $type,
        public array $data
    ) {}
    
    public function handle(): void
    {
        $logger = $this->auditService->getLogger($this->type);
        $logger->log($this->data);
    }
}
```

---

## Audit Query Service

### Query Builder

```php
class AuditQueryService
{
    private Database $db;
    
    public function query(string $type): AuditQueryBuilder
    {
        return new AuditQueryBuilder($this->db, $type);
    }
}

class AuditQueryBuilder
{
    private Database $db;
    private string $table;
    private array $wheres = [];
    private array $orders = [];
    private ?int $limit = null;
    
    public function __construct(Database $db, string $type)
    {
        $this->db = $db;
        $this->table = "audit_{$type}_logs";
    }
    
    public function byActor(int $actorId): self
    {
        $this->wheres[] = ['actor_id', '=', $actorId];
        return $this;
    }
    
    public function byTarget(string $targetType, int $targetId): self
    {
        $this->wheres[] = ['target_type', '=', $targetType];
        $this->wheres[] = ['target_id', '=', $targetId];
        return $this;
    }
    
    public function byAction(string $action): self
    {
        $this->wheres[] = ['action', '=', $action];
        return $this;
    }
    
    public function bySeverity(string $severity): self
    {
        $this->wheres[] = ['severity', '=', $severity];
        return $this;
    }
    
    public function since(DateTime $date): self
    {
        $this->wheres[] = ['created_at', '>=', $date->format('Y-m-d H:i:s')];
        return $this;
    }
    
    public function until(DateTime $date): self
    {
        $this->wheres[] = ['created_at', '<=', $date->format('Y-m-d H:i:s')];
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'DESC'): self
    {
        $this->orders[] = [$column, $direction];
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function get(): array
    {
        $query = "SELECT * FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $conditions = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where[0]} {$where[1]} ?";
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order[0]} {$order[1]}";
            }
            $query .= " ORDER BY " . implode(', ', $orderClauses);
        }
        
        if ($this->limit) {
            $query .= " LIMIT {$this->limit}";
        }
        
        $stmt = $this->db->prepare($query);
        $params = array_column($this->wheres, 2);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

---

## Audit Export Service

### Export to CSV

```php
class AuditExportService
{
    private AuditQueryService $queryService;
    
    public function exportToCSV(string $type, array $filters): string
    {
        $query = $this->queryService->query($type);
        
        // Apply filters
        foreach ($filters as $filter) {
            $query->{$filter['method']}(...$filter['args']);
        }
        
        $logs = $query->get();
        
        // Generate CSV
        $csv = fopen('php://temp', 'r+');
        
        // Header
        fputcsv($csv, [
            'ID',
            'Actor ID',
            'Target Type',
            'Target ID',
            'Action',
            'Severity',
            'IP Address',
            'Description',
            'Created At'
        ]);
        
        // Data
        foreach ($logs as $log) {
            fputcsv($csv, [
                $log['id'],
                $log['actor_id'],
                $log['target_type'],
                $log['target_id'],
                $log['action'],
                $log['severity'],
                $log['ip_address'],
                $log['description'],
                $log['created_at']
            ]);
        }
        
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);
        
        return $csvContent;
    }
}
```

---

## Audit Dashboard

### Real-Time Monitoring

```php
class AuditDashboardService
{
    private Database $db;
    
    public function getDashboardStats(): array
    {
        return [
            'total_logs' => $this->getTotalLogs(),
            'critical_events' => $this->getCriticalEvents(),
            'failed_logins' => $this->getFailedLogins(),
            'security_alerts' => $this->getSecurityAlerts(),
            'recent_activity' => $this->getRecentActivity(),
            'top_actors' => $this->getTopActors(),
            'log_distribution' => $this->getLogDistribution()
        ];
    }
    
    private function getTotalLogs(): int
    {
        $total = 0;
        $tables = [
            'audit_user_logs',
            'audit_admin_logs',
            'audit_owner_logs',
            'audit_login_logs',
            'audit_license_logs',
            'audit_payment_logs',
            'audit_download_logs',
            'audit_security_logs',
            'audit_api_logs',
            'audit_moderation_logs',
            'audit_mail_logs',
            'audit_product_logs',
            'audit_system_logs',
            'audit_error_logs'
        ];
        
        foreach ($tables as $table) {
            $result = $this->db->query("SELECT COUNT(*) as count FROM {$table}");
            $total += $result->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
        return $total;
    }
    
    private function getCriticalEvents(): array
    {
        $events = [];
        
        $result = $this->db->query("
            SELECT * FROM audit_security_logs 
            WHERE severity = 'critical' 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getFailedLogins(): int
    {
        $result = $this->db->query("
            SELECT COUNT(*) as count FROM audit_login_logs 
            WHERE action = 'login_failed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return $result->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function getSecurityAlerts(): array
    {
        $result = $this->db->query("
            SELECT * FROM audit_security_logs 
            WHERE severity IN ('warning', 'error', 'critical') 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentActivity(): array
    {
        $activities = [];
        
        $tables = [
            'audit_admin_logs',
            'audit_owner_logs',
            'audit_security_logs'
        ];
        
        foreach ($tables as $table) {
            $result = $this->db->query("
                SELECT * FROM {$table} 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            
            foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $row['table'] = $table;
                $activities[] = $row;
            }
        }
        
        // Sort by created_at
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($activities, 0, 20);
    }
    
    private function getTopActors(): array
    {
        $actors = [];
        
        $tables = [
            'audit_admin_logs' => 'admin_id',
            'audit_owner_logs' => 'owner_id',
            'audit_moderation_logs' => 'moderator_id'
        ];
        
        foreach ($tables as $table => $column) {
            $result = $this->db->query("
                SELECT {$column} as actor_id, COUNT(*) as count 
                FROM {$table} 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY {$column} 
                ORDER BY count DESC 
                LIMIT 5
            ");
            
            foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $actors[] = $row;
            }
        }
        
        return $actors;
    }
    
    private function getLogDistribution(): array
    {
        $distribution = [];
        
        $tables = [
            'audit_user_logs',
            'audit_admin_logs',
            'audit_owner_logs',
            'audit_login_logs',
            'audit_license_logs',
            'audit_payment_logs',
            'audit_download_logs',
            'audit_security_logs',
            'audit_api_logs',
            'audit_moderation_logs',
            'audit_mail_logs',
            'audit_product_logs',
            'audit_system_logs',
            'audit_error_logs'
        ];
        
        foreach ($tables as $table) {
            $result = $this->db->query("
                SELECT COUNT(*) as count FROM {$table} 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $type = str_replace('audit_', '', str_replace('_logs', '', $table));
            $distribution[$type] = $result->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
        return $distribution;
    }
}
```

---

## Audit Retention Policy

### Automatic Cleanup

```php
class AuditCleanupService
{
    private Database $db;
    
    public function cleanupOldLogs(): void
    {
        // User logs: 2 years
        $this->cleanupTable('audit_user_logs', '2 YEAR');
        
        // Admin logs: 5 years
        $this->cleanupTable('audit_admin_logs', '5 YEAR');
        
        // Owner logs: Permanent (never cleanup)
        
        // Login logs: 1 year
        $this->cleanupTable('audit_login_logs', '1 YEAR');
        
        // Security logs: 5 years
        $this->cleanupTable('audit_security_logs', '5 YEAR');
        
        // Error logs: 1 year
        $this->cleanupTable('audit_error_logs', '1 YEAR');
        
        // Queue jobs: 30 days (completed)
        $this->cleanupQueueJobs();
    }
    
    private function cleanupTable(string $table, string $interval): void
    {
        $this->db->query("
            DELETE FROM {$table} 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL {$interval})
        ");
    }
    
    private function cleanupQueueJobs(): void
    {
        $this->db->query("
            DELETE FROM queue_jobs 
            WHERE status = 'completed' 
            AND finished_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }
}
```

---

## Audit API Endpoints

### Admin Endpoints

```
GET /admin/audit/{type}
Query Params: actor_id, target_id, action, severity, since, until, limit
Response: { logs: [] }

GET /admin/audit/{type}/export
Query Params: actor_id, target_id, action, severity, since, until
Response: CSV file

GET /admin/audit/dashboard
Response: { stats: {} }
```

### Owner Endpoints

```
GET /owner/audit/all
Response: { logs: [] }

GET /owner/audit/security
Response: { alerts: [] }

GET /owner/audit/export/all
Response: ZIP file with all logs

POST /owner/audit/cleanup
Request: { tables: [], retention: {} }
Response: { success, deleted_count }
```

---

## Audit Security

### Access Control

```php
class AuditAuthorizationService
{
    public function canViewLogs(int $userId, string $logType): bool
    {
        $user = $this->userRepository->find($userId);
        
        // Owners can view all logs
        if ($user->role->level >= 100) {
            return true;
        }
        
        // Admins can view admin, user, login, download logs
        if ($user->role->level >= 50) {
            $allowed = ['admin', 'user', 'login', 'download'];
            return in_array($logType, $allowed);
        }
        
        // Moderators can view user, login logs
        if ($user->role->level >= 25) {
            $allowed = ['user', 'login'];
            return in_array($logType, $allowed);
        }
        
        // Users can only view their own logs
        return false;
    }
    
    public function canExportLogs(int $userId, string $logType): bool
    {
        // Only owners can export logs
        $user = $this->userRepository->find($userId);
        return $user->role->level >= 100;
    }
}
```

---

## Summary

The audit logging system provides:
- **13 separate log systems** for optimal performance
- **Synchronous logging** for critical events
- **Asynchronous logging** for non-critical events
- **Comprehensive query builder** for filtering
- **Export functionality** to CSV
- **Real-time dashboard** for monitoring
- **Automatic cleanup** based on retention policy
- **Access control** based on roles
- **Security event tracking** for threat detection

All sensitive actions are logged with full context for compliance and security monitoring.
