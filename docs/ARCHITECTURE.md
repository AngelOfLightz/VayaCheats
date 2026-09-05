# VayaCheats V3.1 - System Architecture

## Overview

VayaCheats V3.1 is an evolution of V3.0, expanding the platform into a comprehensive enterprise-grade system with plugin architecture, API-first design, feature flags, centralized configuration, health monitoring, launcher support, and advanced analytics. The platform is designed to support 100,000+ users with clean separation of concerns and maintainable architecture.

## V3.1 Architecture Upgrades

### New Systems in V3.1

1. **Plugin System**: Extensible architecture for installing plugins without core modifications
2. **API-First Architecture**: Unified API for all clients (Website, Launcher, Discord, Mobile)
3. **Feature Flag System**: Dynamic feature toggling without code deployment
4. **Configuration Center**: Centralized, editable configuration service
5. **Health Monitor**: Comprehensive system health monitoring and alerting
6. **Launcher Ready**: Architecture prepared for Windows Launcher application
7. **Analytics Engine**: Complete Business Intelligence module with real-time dashboards
8. **Service Container**: Full Dependency Injection implementation
9. **System Events Catalog**: Comprehensive domain event documentation

## Design Principles

1. **Modularity**: Each system component is an independent module
2. **Event-Driven**: Modules communicate through domain events
3. **Scalability**: Architecture ready for horizontal scaling
4. **Security**: Defense-in-depth with comprehensive audit logging
5. **Maintainability**: No code duplication, clear interfaces
6. **Portability**: Infrastructure-agnostic design

---

## Module Structure

```
/app
├── Core/
│   ├── Container.php              # Dependency Injection Container
│   ├── EventDispatcher.php        # Event Bus
│   ├── QueueService.php           # Queue Abstraction
│   ├── Config.php                 # Configuration Manager
│   └── Database.php               # Database Connection
│
├── Modules/
│   ├── Plugin/
│   │   ├── PluginManager.php
│   │   ├── PluginLoader.php
│   │   ├── PluginRegistry.php
│   │   ├── PluginManifest.php
│   │   ├── PluginDependencyResolver.php
│   │   └── Events/
│   │       ├── PluginInstalled.php
│   │       ├── PluginUninstalled.php
│   │       ├── PluginEnabled.php
│   │       └── PluginDisabled.php
│   │
│   ├── API/
│   │   ├── APIService.php
│   │   ├── JWTService.php
│   │   ├── APIKeyService.php
│   │   ├── ResponseBuilder.php
│   │   ├── PaginationService.php
│   │   └── Middleware/
│   │       ├── AuthenticationMiddleware.php
│   │       ├── AuthorizationMiddleware.php
│   │       ├── RateLimitMiddleware.php
│   │       ├── CORSMiddleware.php
│   │       └── SecurityHeadersMiddleware.php
│   │
│   ├── FeatureFlag/
│   │   ├── FeatureFlagService.php
│   │   ├── FeatureFlagValidator.php
│   │   ├── FlagAnalyticsService.php
│   │   └── Middleware/
│   │       └── FeatureFlagMiddleware.php
│   │
│   ├── Configuration/
│   │   ├── ConfigurationService.php
│   │   ├── ConfigurationValidator.php
│   │   ├── ConfigurationExporter.php
│   │   ├── ConfigurationImporter.php
│   │   ├── ConfigurationRollbackService.php
│   │   └── Events/
│   │       ├── ConfigurationUpdated.php
│   │       ├── ConfigurationDeleted.php
│   │       └── ConfigurationRolledBack.php
│   │
│   ├── HealthMonitor/
│   │   ├── HealthMonitorService.php
│   │   ├── HealthAlertService.php
│   │   ├── Checks/
│   │   │   ├── DatabaseHealthCheck.php
│   │   │   ├── QueueHealthCheck.php
│   │   │   ├── CacheHealthCheck.php
│   │   │   ├── StorageHealthCheck.php
│   │   │   ├── PHPHealthCheck.php
│   │   │   ├── SSLHealthCheck.php
│   │   │   ├── APIStatusHealthCheck.php
│   │   │   └── CronJobHealthCheck.php
│   │   └── Events/
│   │       ├── HealthCheckCompleted.php
│   │       └── HealthAlertTriggered.php
│   │
│   ├── Launcher/
│   │   ├── LauncherAuthService.php
│   │   ├── LicenseValidationService.php
│   │   ├── DownloadManagementService.php
│   │   ├── FileVerificationService.php
│   │   ├── UpdateService.php
│   │   └── Events/
│   │       ├── LauncherAuthenticated.php
│   │       ├── LauncherDownloadStarted.php
│   │       └── LauncherReportReceived.php
│   │
│   ├── Authentication/
│   │   ├── AuthService.php
│   │   ├── SessionManager.php
│   │   ├── PasswordHasher.php
│   │   └── Events/
│   │       ├── UserLoggedIn.php
│   │       ├── UserLoggedOut.php
│   │       ├── UserRegistered.php
│   │       └── PasswordChanged.php
│   │
│   ├── Authorization/
│   │   ├── PermissionService.php
│   │   ├── RoleService.php
│   │   ├── Gate.php                # Permission Gate
│   │   └── Policies/
│   │       ├── UserPolicy.php
│   │       ├── ProductPolicy.php
│   │       ├── LicensePolicy.php
│   │       └── PaymentPolicy.php
│   │
│   ├── License/
│   │   ├── LicenseService.php
│   │   ├── LicenseGenerator.php
│   │   ├── LicenseValidator.php
│   │   ├── LicenseManager.php
│   │   └── Events/
│   │       ├── LicenseGenerated.php
│   │       ├── LicenseActivated.php
│   │       ├── LicenseExpired.php
│   │       └── LicenseRevoked.php
│   │
│   ├── Membership/
│   │   ├── MembershipService.php
│   │   ├── MembershipManager.php
│   │   └── Events/
│   │       ├── MembershipGranted.php
│   │       ├── MembershipUpgraded.php
│   │       ├── MembershipExpired.php
│   │       └── MembershipCancelled.php
│   │
│   ├── Product/
│   │   ├── ProductService.php
│   │   ├── ProductManager.php
│   │   ├── DownloadService.php
│   │   └── Events/
│   │       ├── ProductCreated.php
│   │       ├── ProductUpdated.php
│   │       ├── ProductDownloaded.php
│   │       └── ProductStatusChanged.php
│   │
│   ├── Payment/
│   │   ├── PaymentService.php
│   │   ├── PaymentProcessor.php
│   │   ├── GatewayFactory.php
│   │   ├── Gateways/
│   │   │   ├── StripeGateway.php
│   │   │   ├── PayTRGateway.php
│   │   │   ├── IyzicoGateway.php
│   │   │   └── PayPalGateway.php
│   │   └── Events/
│   │       ├── PaymentInitiated.php
│   │       ├── PaymentCompleted.php
│   │       ├── PaymentFailed.php
│   │       ├── PaymentRefunded.php
│   │       └── WebhookReceived.php
│   │
│   ├── Download/
│   │   ├── DownloadService.php
│   │   ├── DownloadTracker.php
│   │   └── Events/
│   │       ├── DownloadStarted.php
│   │       ├── DownloadCompleted.php
│   │       └── DownloadFailed.php
│   │
│   ├── Comment/
│   │   ├── CommentService.php
│   │   ├── CommentModerator.php
│   │   └── Events/
│   │       ├── CommentPosted.php
│   │       ├── CommentDeleted.php
│   │       └── CommentPinned.php
│   │
│   ├── Moderation/
│   │   ├── ModerationService.php
│   │   ├── BanService.php
│   │   ├── MuteService.php
│   │   └── Events/
│   │       ├── UserBanned.php
│   │       ├── UserUnbanned.php
│   │       ├── UserMuted.php
│   │       └── UserUnmuted.php
│   │
│   ├── Mail/
│   │   ├── MailService.php
│   │   ├── MailTemplate.php
│   │   ├── MailQueue.php
│   │   └── Events/
│   │       ├── MailQueued.php
│   │       ├── MailSent.php
│   │       └── MailFailed.php
│   │
│   ├── Audit/
│   │   ├── AuditService.php
│   │   ├── AuditLogger.php
│   │   ├── Loggers/
│   │   │   ├── UserLogger.php
│   │   │   ├── AdminLogger.php
│   │   │   ├── OwnerLogger.php
│   │   │   ├── LoginLogger.php
│   │   │   ├── LicenseLogger.php
│   │   │   ├── PaymentLogger.php
│   │   │   ├── DownloadLogger.php
│   │   │   ├── SecurityLogger.php
│   │   │   ├── APILogger.php
│   │   │   ├── ModerationLogger.php
│   │   │   ├── MailLogger.php
│   │   │   ├── ProductLogger.php
│   │   │   ├── SystemLogger.php
│   │   │   └── ErrorLogger.php
│   │
│   ├── Notification/
│   │   ├── NotificationService.php
│   │   ├── NotificationCenter.php
│   │   ├── NotificationDispatcher.php
│   │   └── Events/
│   │       ├── NotificationCreated.php
│   │       ├── NotificationRead.php
│   │       └── NotificationDeleted.php
│   │
│   ├── Security/
│   │   ├── SecurityService.php
│   │   ├── RateLimiter.php
│   │   ├── CSRFProtection.php
│   │   ├── InputValidator.php
│   │   ├── XSSProtection.php
│   │   └── Events/
│   │       ├── SecurityAlert.php
│   │       ├── BruteForceDetected.php
│   │       └── SQLInjectionAttempt.php
│   │
│   ├── Analytics/
│   │   ├── AnalyticsService.php
│   │   ├── StatisticsCollector.php
│   │   ├── DashboardService.php
│   │   └── Events/
│   │       ├── StatisticsUpdated.php
│   │       └── DashboardRefreshed.php
│   │
│   └── Queue/
│       ├── QueueService.php
│       ├── JobDispatcher.php
│       ├── Jobs/
│       │   ├── GenerateLicenseJob.php
│       │   ├── SendEmailJob.php
│       │   ├── CreateAuditLogJob.php
│       │   ├── CreateNotificationJob.php
│       │   ├── GenerateInvoiceJob.php
│       │   ├── UpdateStatisticsJob.php
│       │   ├── RefreshDashboardJob.php
│       │   ├── ExpireLicensesJob.php
│       │   ├── ExpireMembershipsJob.php
│       │   ├── ExpireBansJob.php
│       │   ├── ExpireMutesJob.php
│       │   └── CleanTempFilesJob.php
│       └── Events/
│           ├── JobQueued.php
│           ├── JobStarted.php
│           ├── JobCompleted.php
│           └── JobFailed.php
│
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   ├── License.php
│   ├── Membership.php
│   ├── Product.php
│   ├── Payment.php
│   ├── Download.php
│   ├── Comment.php
│   ├── Notification.php
│   ├── AuditLog.php
│   └── QueueJob.php
│
├── Repositories/
│   ├── UserRepository.php
│   ├── RoleRepository.php
│   ├── LicenseRepository.php
│   ├── MembershipRepository.php
│   ├── ProductRepository.php
│   ├── PaymentRepository.php
│   ├── DownloadRepository.php
│   ├── CommentRepository.php
│   ├── NotificationRepository.php
│   ├── AuditLogRepository.php
│   └── QueueJobRepository.php
│
├── Controllers/
│   ├── AuthController.php
│   ├── UserController.php
│   ├── AdminController.php
│   ├── OwnerController.php
│   ├── ProductController.php
│   ├── PaymentController.php
│   ├── DownloadController.php
│   ├── CommentController.php
│   ├── NotificationController.php
│   └── DashboardController.php
│
└── Middleware/
    ├── AuthMiddleware.php
    ├── RoleMiddleware.php
    ├── PermissionMiddleware.php
    ├── RateLimitMiddleware.php
    ├── CSRFMiddleware.php
    └── AuditMiddleware.php

/public
├── index.php                    # Application entry point
├── assets/                      # CSS, JS, images
└── uploads/                     # User uploads

/config
├── app.php                      # Application config
├── database.php                 # Database config
├── queue.php                    # Queue config
├── mail.php                     # Mail config
└── gateways.php                 # Payment gateway config

/database
└── migrations/                  # Database migrations

/storage
├── logs/                        # Application logs
├── cache/                       # Cache files
└── queue/                       # Queue storage (database fallback)

/tests
├── Unit/
├── Integration/
└── Feature/
```

---

## Module Dependencies

### Core Layer (No Dependencies)
- Container (Service Container / Dependency Injection)
- EventDispatcher
- QueueService
- Config
- Database

### V3.1 New Systems
- **Plugin**: Depends on Core, EventDispatcher, Database
- **API**: Depends on Core, Authentication, Authorization, Security
- **FeatureFlag**: Depends on Core, Cache, EventDispatcher
- **Configuration**: Depends on Core, Cache, EventDispatcher, Database
- **HealthMonitor**: Depends on Core, EventDispatcher
- **Launcher**: Depends on Core, API, License, Authentication

### Authentication Layer
- Depends on: Core, Security
- Used by: All modules

### Authorization Layer
- Depends on: Core
- Used by: All modules

### Security Layer
- Depends on: Core, Audit
- Used by: All modules

### Business Logic Modules
- **License**: Depends on Core, Audit, Queue, Notification
- **Membership**: Depends on Core, Audit, Queue, Notification
- **Product**: Depends on Core, Audit, Download, Notification
- **Payment**: Depends on Core, Audit, Queue, License, Notification
- **Download**: Depends on Core, Audit, License, Queue
- **Comment**: Depends on Core, Audit, Moderation, Notification
- **Moderation**: Depends on Core, Audit, Notification
- **Mail**: Depends on Core, Queue, Audit
- **Notification**: Depends on Core, Queue
- **Analytics**: Depends on Core, Audit, Cache

### Infrastructure Layer
- **Queue**: Depends on Core
- **Audit**: Depends on Core

---

## API-First Architecture

### API Layer

All functionality is exposed through APIs. The website itself consumes the same APIs as other clients.

```
Clients
├── Website (Current)
├── Launcher (Future)
├── Discord Bot (Future)
├── Desktop App (Future)
└── Mobile App (Future)
    ↓
API Gateway
├── Rate Limiting
├── Authentication
├── Authorization
└── Logging
    ↓
API Controllers
├── v1 (Current)
├── v2 (Future)
└── v3 (Future)
    ↓
Services
    ↓
Repositories
    ↓
Database
```

### API Features

- **Versioned APIs**: `/api/v1/*`, `/api/v2/*`
- **Multiple Authentication**: JWT, API Keys, Session Cookies
- **Standardized Response Format**: Consistent JSON responses
- **Pagination**: Built-in pagination support
- **Rate Limiting**: Per-endpoint and per-user limits
- **CORS**: Configurable CORS policies
- **Auto-Documentation**: OpenAPI/Swagger specification

---

## Event-Driven Architecture

### Event Flow

```
User Action
    ↓
Controller
    ↓
Service (Business Logic)
    ↓
EventDispatcher
    ↓
Event Listeners (Multiple)
    ↓
Side Effects (Database, Queue, Mail, etc.)
```

### Event Examples

#### PaymentCompleted Event
```php
class PaymentCompleted
{
    public function __construct(
        public string $paymentId,
        public int $userId,
        public float $amount,
        public string $gateway
    ) {}
}

// Listeners:
// 1. GenerateLicenseJob (Queue)
// 2. CreateAuditLogJob (Queue)
// 3. CreateNotificationJob (Queue)
// 4. SendEmailJob (Queue)
// 5. UpdateStatisticsJob (Queue)
```

#### UserRegistered Event
```php
class UserRegistered
{
    public function __construct(
        public int $userId,
        public string $email,
        public string $username
    ) {}
}

// Listeners:
// 1. CreateProfileJob (Queue)
// 2. CreateNotificationJob (Queue)
// 3. WriteAuditLogJob (Queue)
// 4. SendWelcomeEmailJob (Queue)
```

#### ProductDownloaded Event
```php
class ProductDownloaded
{
    public function __construct(
        public int $userId,
        public int $productId,
        public string $licenseKey
    ) {}
}

// Listeners:
// 1. WriteDownloadLogJob (Queue)
// 2. UpdateStatisticsJob (Queue)
// 3. CreateNotificationJob (Queue)
```

---

## Queue System Architecture

### Queue Abstraction

```php
interface QueueInterface
{
    public function push(string $job, array $payload): string;
    public function pop(): ?Job;
    public function release(string $jobId): void;
    public function fail(string $jobId, string $error): void;
    public function status(string $jobId): ?string;
}

class QueueService
{
    private QueueInterface $driver;
    
    public function __construct(QueueInterface $driver)
    {
        $this->driver = $driver;
    }
    
    public function dispatch(Job $job): string
    {
        return $this->driver->push(
            get_class($job),
            $job->getPayload()
        );
    }
}
```

### Queue Drivers

1. **DatabaseQueue** (Current - InfinityFree compatible)
2. **RedisQueue** (Future - VPS)
3. **RabbitMQQueue** (Future - Dedicated)
4. **BeanstalkdQueue** (Future - Dedicated)

### Job Structure

```php
abstract class Job
{
    public int $priority = 5;
    public int $maxAttempts = 3;
    public int $timeout = 60;
    
    abstract public function handle(): void;
    
    public function getPayload(): array
    {
        return get_object_vars($this);
    }
}
```

---

## Plugin System

### Plugin Architecture

Plugins enable extending VayaCheats without modifying the core application.

```
Plugin System
├── Plugin Loader
├── Plugin Registry
├── Plugin Manager
├── Plugin Permissions
├── Plugin Events
└── Plugin Dependency Resolver
```

### Plugin Capabilities

- **Install/Uninstall**: Dynamic plugin management
- **Enable/Disable**: Runtime plugin control
- **Event Subscription**: Plugins can subscribe to core events
- **Event Publishing**: Plugins can publish their own events
- **Service Injection**: Plugins can inject core services
- **API Access**: Plugins can access core APIs
- **Database Migrations**: Plugin-specific database changes
- **Asset Loading**: CSS, JS, and other assets

### Example Plugins

- Discord Integration
- Launcher Integration
- Support Ticket System
- Forum
- Marketplace
- Analytics Extensions
- Payment Providers
- Notification Providers
- Mail Providers
- Future AI Assistant

---

## Feature Flag System

### Flag Types

- **Global Flags**: Enabled/disabled for all users
- **User Flags**: Enabled/disabled for specific users
- **Role Flags**: Enabled/disabled for specific roles
- **Membership Flags**: Enabled/disabled for specific membership levels
- **Percentage Rollout**: Enabled for a percentage of users
- **Time-Based Flags**: Enabled/disabled based on date/time

### Flag Use Cases

- New payment flow
- Launcher beta
- Experimental products
- Discord login
- Maintenance features
- Dark mode variants
- A/B testing

---

## Configuration Center

### Configuration Categories

All system settings managed from a centralized UI:

- SMTP Configuration
- Payment Configuration
- Download Configuration
- Registration Configuration
- Security Configuration
- Maintenance Configuration
- Feature Flags
- Analytics Configuration
- API Configuration
- Launcher Configuration
- Discord Configuration
- SEO Configuration
- Site Identity
- Uploads Configuration
- Rate Limits
- System Limits

### Configuration Features

- **Dynamic Updates**: Changes without code deployment
- **Type Casting**: Automatic type conversion
- **Encryption**: Sensitive values encrypted
- **Validation**: Configuration value validation
- **Import/Export**: Configuration backup and restore
- **Rollback**: Configuration history and rollback
- **Event-Driven**: Configuration change events

---

## Health Monitor

### Health Check Categories

- **System Health**: Database, Queue, Cache, Storage
- **Application Health**: PHP, Extensions, Dependencies
- **Infrastructure Health**: Disk, Memory, CPU, Network
- **Service Health**: API, Web Server, SSL, Cron Jobs
- **Business Health**: Active Users, Error Rate, Response Time, Queue Depth

### Health Features

- **Standardized Checks**: Consistent health check interface
- **Real-Time Monitoring**: HTTP endpoints for health status
- **Alert System**: Configurable alert rules
- **Historical Logging**: Health check history
- **Overall Status**: Aggregated health status

---

## Launcher Architecture

### Launcher Capabilities

The Windows Launcher communicates exclusively through APIs:

- Authentication (Login with email/password, Discord)
- License Validation (Real-time validation, hardware binding)
- Product Download (Secure download, resume support)
- Product Updates (Auto-update, delta updates)
- News Feed (Announcements, updates)
- Notifications (License warnings, system alerts)
- Version Check (Launcher auto-update)
- File Verification (Hash verification, corruption repair)
- Auto Update (Background updates, rollback)
- Repair Installation (Corruption repair, re-download)

### Launcher Security

- **No Database Access**: Launcher never connects directly to database
- **API-Only Communication**: All data through authenticated APIs
- **Hardware Binding**: Licenses bound to hardware IDs
- **Secure Storage**: Windows DPAPI for sensitive data
- **Certificate Pinning**: MITM attack prevention
- **Offline Mode**: Cached validation with time limits

---

## Analytics Engine

### Analytics Categories

- **Revenue Analytics**: Total revenue, by gateway, by plan, by country
- **User Analytics**: DAU, MAU, retention, churn, LTV
- **Download Analytics**: Total downloads, by product, by country
- **License Analytics**: Active licenses, expirations, by plan
- **Conversion Analytics**: Registration to purchase, free to paid
- **Product Analytics**: Top products, popularity trends
- **Search Analytics**: Search trends, top terms
- **Payment Analytics**: Success rate, gateway performance
- **Session Analytics**: Session length, bounce rate
- **Geographic Analytics**: Country, city, region distribution
- **Device Analytics**: Browser, OS distribution
- **System Analytics**: API response times, error rates

### Dashboard Widgets

- **Counter Widget**: Single metric display
- **Chart Widget**: Line, bar, pie, area charts
- **Table Widget**: Sortable, filterable tables
- **Gauge Widget**: Progress meters, health indicators

---

## Dependency Injection Container

```php
class Container
{
    private array $bindings = [];
    private array $instances = [];
    
    public function bind(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => true
        ];
    }
    
    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Resolve dependencies recursively
    }
}
```

---

## Service Layer Pattern

Each module follows the Service Layer pattern:

```php
class LicenseService
{
    private LicenseRepository $repository;
    private EventDispatcher $events;
    private QueueService $queue;
    
    public function __construct(
        LicenseRepository $repository,
        EventDispatcher $events,
        QueueService $queue
    ) {
        $this->repository = $repository;
        $this->events = $events;
        $this->queue = $queue;
    }
    
    public function generate(int $userId, string $plan): License
    {
        $license = $this->repository->create([
            'user_id' => $userId,
            'plan' => $plan,
            'key' => $this->generateKey(),
            'status' => 'active'
        ]);
        
        $this->events->dispatch(new LicenseGenerated($license->id));
        
        return $license;
    }
}
```

---

## Repository Pattern

```php
interface RepositoryInterface
{
    public function find(int $id): ?Model;
    public function all(): Collection;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function where(string $column, $value): RepositoryInterface;
}

class LicenseRepository implements RepositoryInterface
{
    private Database $db;
    
    public function find(int $id): ?License
    {
        // Database query
    }
    
    // Other methods...
}
```

---

## Middleware Pipeline

```
Request
    ↓
CSRFMiddleware
    ↓
RateLimitMiddleware
    ↓
AuthMiddleware
    ↓
RoleMiddleware
    ↓
PermissionMiddleware
    ↓
AuditMiddleware
    ↓
Controller
    ↓
Response
```

---

## Scalability Considerations

### Database Sharding Strategy
- **Users**: Shard by user_id
- **Audit Logs**: Shard by date
- **Downloads**: Shard by date
- **Payments**: Shard by date

### Caching Strategy
- **Session**: Redis (future) / Database (current)
- **Cache**: Redis (future) / File (current)
- **Queue**: Redis (future) / Database (current)

### Horizontal Readiness
- Stateless application design
- Session storage externalized
- Queue system abstracted
- Cache layer abstracted
- Database connection pooling ready

---

## Security Architecture

### Defense in Depth

1. **Input Validation**: All inputs validated at entry point
2. **Output Encoding**: XSS protection on all outputs
3. **Authentication**: Secure session management
4. **Authorization**: Permission-based access control
5. **Rate Limiting**: Prevent brute force attacks
6. **CSRF Protection**: Token-based validation
7. **Audit Logging**: All sensitive actions logged
8. **Encryption**: Sensitive data encrypted at rest

### Security Events

All security-related events trigger immediate alerts:
- Failed login attempts (>5)
- Permission denied
- SQL injection patterns detected
- CSRF token validation failed
- Invalid API tokens
- Rate limit exceeded
- Owner account access
- Admin privilege escalation

---

## Monitoring & Observability

### Metrics Collection
- Request latency
- Error rate
- Queue depth
- Database query time
- Cache hit rate
- Active sessions
- Failed logins

### Health Checks
- Database connectivity
- Queue connectivity
- Cache connectivity
- Disk space
- Memory usage

---

## Migration Path

### Phase 1: Architecture Foundation
- Implement DI Container
- Implement Event Dispatcher
- Implement Queue Service (Database)
- Implement Audit Service

### Phase 2: Core Modules
- Authentication Module
- Authorization Module
- Security Module

### Phase 3: Business Modules
- License Module
- Membership Module
- Product Module
- Payment Module

### Phase 4: Supporting Modules
- Notification Module
- Mail Module
- Analytics Module
- Moderation Module

### Phase 5: Infrastructure Migration
- Move to VPS
- Switch to Redis
- Switch to Redis Queue
- Implement proper workers

---

## Performance Targets

- **Response Time**: <200ms (p95)
- **Database Queries**: <10 per request
- **Queue Processing**: <5s per job
- **Cache Hit Rate**: >80%
- **Uptime**: >99.9%

---

## Code Quality Standards

- **PSR-12**: Coding style
- **PHPDoc**: All classes documented
- **Type Hints**: Strict typing
- **Unit Tests**: >80% coverage
- **Integration Tests**: Critical paths
- **Static Analysis**: Psalm/PHPStan

---

## Summary

VayaCheats V3.1 architecture is designed for:
- **Modularity**: Independent, reusable components with plugin support
- **API-First**: Unified API for all clients (Website, Launcher, Discord, Mobile)
- **Scalability**: Ready for 100,000+ users with horizontal scaling
- **Extensibility**: Plugin system for adding features without core modifications
- **Dynamic Configuration**: Centralized, editable configuration service
- **Feature Flags**: Dynamic feature toggling without code deployment
- **Observability**: Comprehensive health monitoring and analytics
- **Launcher Ready**: Architecture prepared for Windows Launcher
- **Maintainability**: Clean code, clear interfaces, dependency injection
- **Security**: Defense-in-depth with comprehensive audit logging
- **Portability**: Infrastructure-agnostic design

The architecture follows industry best practices while maintaining the unique cyberpunk identity of the platform.

### V3.1 Architecture Documents

1. [ARCHITECTURE.md](./ARCHITECTURE.md) - Complete system architecture (this document)
2. [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) - Database schema design
3. [ROLE_MATRIX.md](./ROLE_MATRIX.md) - Role and permission matrix
4. [LICENSE_FLOW.md](./LICENSE_FLOW.md) - License system flow
5. [PAYMENT_FLOW.md](./PAYMENT_FLOW.md) - Payment system flow
6. [AUDIT_FLOW.md](./AUDIT_FLOW.md) - Audit logging system
7. [OWNER_FLOW.md](./OWNER_FLOW.md) - Owner control center flow
8. [PLUGIN_SYSTEM.md](./PLUGIN_SYSTEM.md) - Plugin architecture
9. [API_FIRST_ARCHITECTURE.md](./API_FIRST_ARCHITECTURE.md) - API-first design
10. [FEATURE_FLAG_SYSTEM.md](./FEATURE_FLAG_SYSTEM.md) - Feature flag system
11. [CONFIGURATION_CENTER.md](./CONFIGURATION_CENTER.md) - Configuration management
12. [HEALTH_MONITOR.md](./HEALTH_MONITOR.md) - Health monitoring system
13. [LAUNCHER_READY.md](./LAUNCHER_READY.md) - Launcher architecture
14. [ANALYTICS_ENGINE.md](./ANALYTICS_ENGINE.md) - Analytics and BI
15. [SYSTEM_EVENTS.md](./SYSTEM_EVENTS.md) - System events catalog
16. [SERVICE_CONTAINER.md](./SERVICE_CONTAINER.md) - Dependency injection container
