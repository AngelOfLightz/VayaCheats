# VayaCheats V3.1 - System Events Catalog

## Overview

The System Events Catalog documents all domain events in the VayaCheats platform. Events enable loose coupling between modules and support the event-driven architecture.

---

## Event Categories

### Authentication Events
### User Events
### License Events
### Membership Events
### Product Events
### Payment Events
### Download Events
### Comment Events
### Moderation Events
### Notification Events
### Mail Events
### Security Events
### System Events
### Plugin Events
### Queue Events
### Feature Flag Events
### Configuration Events
### Analytics Events
### Launcher Events
### Discord Events

---

## Authentication Events

### UserLoggedIn

```php
class UserLoggedIn
{
    public function __construct(
        public int $userId,
        public string $method, // session, jwt, api_key, discord
        public ?string $ipAddress,
        public ?string $userAgent
    ) {}
}
```

**Listeners**:
- Update last login timestamp
- Log to audit_login_logs
- Create session record
- Track login analytics

### UserLoggedOut

```php
class UserLoggedOut
{
    public function __construct(
        public int $userId,
        public string $method
    ) {}
}
```

**Listeners**:
- Destroy session
- Log to audit_login_logs
- Clear cache

### UserRegistered

```php
class UserRegistered
{
    public function __construct(
        public int $userId,
        public string $email,
        public string $username,
        public ?string $referralCode
    ) {}
}
```

**Listeners**:
- Send welcome email
- Create user profile
- Create user statistics
- Log to audit_user_logs
- Track registration analytics
- Create notification

### PasswordChanged

```php
class PasswordChanged
{
    public function __construct(
        public int $userId,
        public string $method, // self, reset, admin
        public ?int $changedBy
    ) {}
}
```

**Listeners**:
- Send password change notification
- Log to audit_user_logs
- Invalidate all sessions except current
- Clear password reset tokens

### PasswordResetRequested

```php
class PasswordResetRequested
{
    public function __construct(
        public int $userId,
        public string $email,
        public string $token
    ) {}
}
```

**Listeners**:
- Send password reset email
- Log to audit_user_logs
- Track password reset analytics

---

## User Events

### UserUpdated

```php
class UserUpdated
{
    public function __construct(
        public int $userId,
        public array $changes,
        public ?int $updatedBy
    ) {}
}
```

**Listeners**:
- Log to audit_user_logs
- Invalidate user cache
- Send notification if profile changed

### UserDeleted

```php
class UserDeleted
{
    public function __construct(
        public int $userId,
        public ?int $deletedBy,
        public string $reason
    ) {}
}
```

**Listeners**:
- Revoke all licenses
- Cancel all memberships
- Delete user data
- Log to audit_user_logs
- Send deletion confirmation email

### UserBanned

```php
class UserBanned
{
    public function __construct(
        public int $userId,
        public int $bannedBy,
        public string $reason,
        public ?DateTime $expiresAt
   ） {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Send ban notification
- Revoke active sessions
- Disable licenses
- Create notification

### UserUnbanned

```php
class UserUnbanned
{
    public function __construct(
        public int $userId,
        public int $unbannedBy,
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Send unban notification
- Re-enable licenses
- Create notification

### UserMuted

```php
class UserMuted
{
    public function __construct(
        public int $userId,
        public int $mutedBy,
        public string $reason,
        public ?DateTime $expiresAt
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Send mute notification
- Disable comment posting
- Create notification

### UserUnmuted

```php
class UserUnmuted
{
    public function __construct(
        public int $userId,
        public int $unmutedBy,
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Send unmute notification
- Re-enable comment posting
- Create notification

---

## License Events

### LicenseGenerated

```php
class LicenseGenerated
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public int $planId,
        public ?int $paymentId
    ) {}
}
```

**Listeners**:
- Send license email
- Log to audit_license_logs
- Create notification
- Track license analytics
- Update user statistics

### LicenseActivated

```php
class LicenseActivated
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public string $hardwareId
    ) {}
}
```

**Listeners**:
- Log to audit_license_logs
- Update activation count
- Track activation analytics
- Send activation confirmation

### LicenseDeactivated

```php
class LicenseDeactivated
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public string $hardwareId,
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_license_logs
- Update activation count
- Send deactivation notification

### LicenseExpired

```php
class LicenseExpired
{
    public function __construct(
        public int $licenseId,
        public int $userId
    ) {}
}
```

**Listeners**:
- Update license status
- Log to audit_license_logs
- Send expiration notification
- Create notification
- Track expiration analytics

### LicenseRevoked

```php
class LicenseRevoked
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public ?int $revokedBy,
        public string $reason
    ) {}
}
```

**Listeners**:
- Deactivate all activations
- Log to audit_license_logs
- Send revocation notification
- Create notification
- Update user statistics

### LicenseRenewed

```php
class LicenseRenewed
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public DateTime $oldExpiration,
        public DateTime $newExpiration
    ) {}
}
```

**Listeners**:
- Log to audit_license_logs
- Send renewal notification
- Create notification
- Track renewal analytics

### LicenseUpgraded

```php
class LicenseUpgraded
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public int $fromPlanId,
        public int $toPlanId
    ) {}
}
```

**Listeners**:
- Log to audit_license_logs
- Send upgrade notification
- Create notification
- Track upgrade analytics

### LicenseDowngraded

```php
class LicenseDowngraded
{
    public function __construct(
        public int $licenseId,
        public int $userId,
        public int $fromPlanId,
        public int $toPlanId,
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_license_logs
- Send downgrade notification
- Create notification
- Track downgrade analytics

---

## Membership Events

### MembershipGranted

```php
class MembershipGranted
{
    public function __construct(
        public int $membershipId,
        public int $userId,
        public int $planId,
        public ?int $grantedBy
    ) {}
}
```

**Listeners**:
- Log to audit_admin_logs
- Send membership notification
- Create notification
- Track membership analytics

### MembershipUpgraded

```php
class MembershipUpgraded
{
    public function __construct(
        public int $membershipId,
        public int $userId,
        public int $fromPlanId,
        public int $toPlanId
    ) {}
}
```

**Listeners**:
- Log to audit_admin_logs
- Send upgrade notification
- Create notification
- Track upgrade analytics

### MembershipExpired

```php
class MembershipExpired
{
    public function __construct(
        public int $membershipId,
        public int $userId
    ) {}
}
```

**Listeners**:
- Update membership status
- Log to audit_admin_logs
- Send expiration notification
- Create notification
- Track expiration analytics

### MembershipCancelled

```php
class MembershipCancelled
{
    public function __construct(
        public int $membershipId,
        public int $userId,
        public string $reason
    ) {}
}
```

**Listeners**:
- Update membership status
- Log to audit_admin_logs
- Send cancellation notification
- Create notification

---

## Product Events

### ProductCreated

```php
class ProductCreated
{
    public function __construct(
        public int $productId,
        public string $name,
        public ?int $createdBy
    ) {}
}
```

**Listeners**:
- Log to audit_product_logs
- Create notification for admins
- Track product analytics

### ProductUpdated

```php
class ProductUpdated
{
    public function __construct(
        public int $productId,
        public array $changes,
        public ?int $updatedBy
    ) {}
}
```

**Listeners**:
- Log to audit_product_logs
- Invalidate product cache
- Create notification if version changed

### ProductDeleted

```php
class ProductDeleted
{
    public function __construct(
        public int $productId,
        public string $name,
        public ?int $deletedBy,
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_product_logs
- Delete product files
- Invalidate product cache
- Create notification for admins

### ProductDownloaded

```php
class ProductDownloaded
{
    public function __construct(
        public int $downloadId,
        public int $userId,
        public int $productId,
        public ?int $licenseId,
        public string $version
    ) {}
}
```

**Listeners**:
- Log to audit_download_logs
- Update download count
- Update product statistics
- Track download analytics
- Create notification

### ProductStatusChanged

```php
class ProductStatusChanged
{
    public function __construct(
        public int $productId,
        public string $oldStatus,
        public string $newStatus,
        public ?int $changedBy
    ) {}
}
```

**Listeners**:
- Log to audit_product_logs
- Invalidate product cache
- Send status change notification
- Create notification for affected users

---

## Payment Events

### PaymentInitiated

```php
class PaymentInitiated
{
    public function __construct(
        public int $paymentId,
        public int $userId,
        public string $gateway,
        public float $amount,
        public int $planId
    ) {}
}
```

**Listeners**:
- Log to audit_payment_logs
- Track payment analytics

### PaymentCompleted

```php
class PaymentCompleted
{
    public function __construct(
        public int $paymentId,
        public int $userId,
        public string $gateway,
        public float $amount,
        public int $planId
    ) {}
}
```

**Listeners**:
- Queue: GenerateLicenseJob
- Queue: CreateAuditLogJob
- Queue: CreateNotificationJob
- Queue: SendEmailJob
- Queue: UpdateStatisticsJob
- Log to audit_payment_logs
- Track revenue analytics

### PaymentFailed

```php
class PaymentFailed
{
    public function __construct(
        public int $paymentId,
        public int $userId,
        public string $gateway,
        public string $errorMessage
    ) {}
}
```

**Listeners**:
- Queue: CreateNotificationJob
- Queue: SendEmailJob
- Log to audit_payment_logs
- Track payment analytics

### PaymentRefunded

```php
class PaymentRefunded
{
    public function __construct(
        public int $paymentId,
        public int $userId,
        public float $amount,
        public string $reason
    ) {}
}
```

**Listeners**:
- Revoke linked license
- Queue: CreateNotificationJob
- Queue: SendEmailJob
- Log to audit_payment_logs
- Track refund analytics

---

## Download Events

### DownloadStarted

```php
class DownloadStarted
{
    public function __construct(
        public int $downloadId,
        public int $userId,
        public int $productId,
        public string $ipAddress
    ) {}
}
```

**Listeners**:
- Log to audit_download_logs
- Track download analytics

### DownloadCompleted

```php
class DownloadCompleted
{
    public function __construct(
        public int $downloadId,
        public int $userId,
        public int $productId,
        public int $fileSize,
        public int $downloadTime
    ) {}
}
```

**Listeners**:
- Log to audit_download_logs
- Update download statistics
- Track bandwidth usage
- Create notification

### DownloadFailed

```php
class DownloadFailed
{
    public function __construct(
        public int $downloadId,
        public int $userId,
        public int $productId,
        public string $errorMessage
    ) {}
}
```

**Listeners**:
- Log to audit_download_logs
- Track error analytics
- Create notification

---

## Comment Events

### CommentPosted

```php
class CommentPosted
{
    public function __construct(
        public int $commentId,
        public int $userId,
        public int $productId,
        public string $content
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Check for moderation
- Notify product owner
- Track comment analytics

### CommentDeleted

```php
class CommentDeleted
{
    public function __construct(
        public int $commentId,
        public int $userId,
        public int $deletedBy,
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Send deletion notification
- Track comment analytics

### CommentPinned

```php
class CommentPinned
{
    public function __construct(
        public int $commentId,
        public int $pinnedBy
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Update comment display order

---

## Moderation Events

### ModerationActionTaken

```php
class ModerationActionTaken
{
    public function __construct(
        public int $moderatorId,
        public int $targetUserId,
        public string $action, // ban, mute, warn, delete_comment
        public string $reason
    ) {}
}
```

**Listeners**:
- Log to audit_moderation_logs
- Send notification to target user
- Track moderation analytics

---

## Notification Events

### NotificationCreated

```php
class NotificationCreated
{
    public function __construct(
        public int $notificationId,
        public int $userId,
        public string $category,
        public string $title,
        public string $message
    ) {}
}
```

**Listeners**:
- Update notification count
- Send real-time notification via WebSocket

### NotificationRead

```php
class NotificationRead
{
    public function __construct(
        public int $notificationId,
        public int $userId
    ) {}
}
```

**Listeners**:
- Update notification count
- Clear notification badge

---

## Mail Events

### MailQueued

```php
class MailQueued
{
    public function __construct(
        public int $mailId,
        public string $recipientEmail,
        public string $template,
        public array $data
    ) {}
}
```

**Listeners**:
- Log to audit_mail_logs

### MailSent

```php
class MailSent
{
    public function __construct(
        public int $mailId,
        public string $recipientEmail,
        public string $template
    ) {}
}
```

**Listeners**:
- Log to audit_mail_logs
- Update mail statistics

### MailFailed

```php
class MailFailed
{
    public function __construct(
        public int $mailId,
        public string $recipientEmail,
        public string $errorMessage
    ) {}
}
```

**Listeners**:
- Log to audit_mail_logs
- Queue retry job
- Alert admins for critical failures

---

## Security Events

### SecurityAlert

```php
class SecurityAlert
{
    public function __construct(
        public string $type,
        public string $message,
        public ?int $userId,
        public string $ipAddress,
        public string $severity // warning, error, critical
    ) {}
}
```

**Listeners**:
- Log to audit_security_logs
- Send alert to admins
- Create notification
- Block IP if critical

### BruteForceDetected

```php
class BruteForceDetected
{
    public function __construct(
        public ?int $userId,
        public string $ipAddress,
        public int $attemptCount
    ) {}
}
```

**Listeners**:
- Log to audit_security_logs
- Block IP temporarily
- Send alert to admins
- Create notification

### SQLInjectionAttempt

```php
class SQLInjectionAttempt
{
    public function __construct(
        public string $ipAddress,
        public string $payload,
        public string $userAgent
    ) {}
}
```

**Listeners**:
- Log to audit_security_logs
- Block IP permanently
- Send critical alert to admins
- Create notification

---

## System Events

### SystemMaintenanceMode

```php
class SystemMaintenanceMode
{
    public function __construct(
        public bool $enabled,
        public ?string $message,
        public ?int $enabledBy
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Send broadcast notification
- Update system status

### SystemBackupCompleted

```php
class SystemBackupCompleted
{
    public function __construct(
        public string $backupPath,
        public int $fileSize,
        public int $duration
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Send notification to owners
- Update backup statistics

### CronJobExecuted

```php
class CronJobExecuted
{
    public function __construct(
        public string $jobName,
        public bool $success,
        public ?string $errorMessage,
        public int $executionTime
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Track cron job performance
- Alert on failures

---

## Plugin Events

### PluginInstalled

```php
class PluginInstalled
{
    public function __construct(
        public string $pluginName,
        public string $version
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Run plugin migrations
- Register plugin services

### PluginUninstalled

```php
class PluginUninstalled
{
    public function __construct(
        public string $pluginName,
        public string $version
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Rollback plugin migrations
- Unregister plugin services

### PluginEnabled

```php
class PluginEnabled
{
    public function __construct(
        public string $pluginName
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Boot plugin

### PluginDisabled

```php
class PluginDisabled
{
    public function __construct(
        public string $pluginName
    ) {}
}
```

**Listeners**:
- Log to audit_system_logs
- Shutdown plugin

---

## Queue Events

### JobQueued

```php
class JobQueued
{
    public function __construct(
        public string $jobId,
        public string $jobType,
        public array $payload
    ) {}
}
```

**Listeners**:
- Log queue depth
- Track queue analytics

### JobStarted

```php
class JobStarted
{
    public function __construct(
        public string $jobId,
        public string $jobType
    ) {}
}
```

**Listeners**:
- Update job status
- Track job performance

### JobCompleted

```php
class JobCompleted
{
    public function __construct(
        public string $jobId,
        public string $jobType,
        public int $executionTime
    ) {}
}
```

**Listeners**:
- Update job status
- Track job performance
- Clear queue depth

### JobFailed

```php
class JobFailed
{
    public function __construct(
        public string $jobId,
        public string $jobType,
        public string $errorMessage
    ) {}
}
```

**Listeners**:
- Update job status
- Queue retry job
- Alert on critical failures
- Track error analytics

---

## Feature Flag Events

### FeatureFlagEnabled

```php
class FeatureFlagEnabled
{
    public function __construct(
        public string $flagKey
    ) {}
}
```

**Listeners**:
- Clear feature flag cache
- Log to audit_system_logs
- Send notification to admins

### FeatureFlagDisabled

```php
class FeatureFlagDisabled
{
    public function __construct(
        public string $flagKey
    ) {}
}
```

**Listeners**:
- Clear feature flag cache
- Log to audit_system_logs
- Send notification to admins

---

## Configuration Events

### ConfigurationUpdated

```php
class ConfigurationUpdated
{
    public function __construct(
        public string $key,
        public $value
    ) {}
}
```

**Listeners**:
- Clear relevant caches
- Log to audit_system_logs
- Send notification for sensitive changes

---

## Analytics Events

### StatisticsUpdated

```php
class StatisticsUpdated
{
    public function __construct(
        public string $metric,
        public $value
    ) {}
}
```

**Listeners**:
- Update analytics cache
- Broadcast to dashboard

---

## Launcher Events

### LauncherAuthenticated

```php
class LauncherAuthenticated
{
    public function __construct(
        public int $userId,
        public string $hardwareId,
        public string $launcherVersion
    ) {}
}
```

**Listeners**:
- Log to audit_login_logs
- Create launcher session
- Track launcher analytics

### LauncherDownloadStarted

```php
class LauncherDownloadStarted
{
    public function __construct(
        public int $userId,
        public int $productId,
        public string $hardwareId
    ) {}
}
```

**Listeners**:
- Log to audit_download_logs
- Track launcher analytics

---

## Discord Events

### DiscordUserLinked

```php
class DiscordUserLinked
{
    public function __construct(
        public int $userId,
        public string $discordId
    ) {}
}
```

**Listeners**:
- Log to audit_user_logs
- Update user profile
- Sync Discord roles

### DiscordMessageSent

```php
class DiscordMessageSent
{
    public function __construct(
        public string $channelId,
        public string $message
    ) {}
}
```

**Listeners**:
- Log to audit_mail_logs
- Track Discord analytics

---

## Event Dispatcher Implementation

### Event Dispatcher

```php
class EventDispatcher
{
    private array $listeners = [];
    private QueueService $queue;
    
    public function __construct(QueueService $queue)
    {
        $this->queue = $queue;
    }
    
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }
    
    public function forget(string $event, ?callable $listener = null): void
    {
        if ($listener === null) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners[$event] = array_filter(
                $this->listeners[$event],
                fn($l) => $l !== $listener
            );
        }
    }
    
    public function dispatch(object $event): void
    {
        $eventName = get_class($event);
        
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        
        foreach ($this->listeners[$eventName] as $listener) {
            $this->callListener($listener, $event);
        }
    }
    
    public function dispatchAsync(object $event): void
    {
        $this->queue->dispatch(new DispatchEventJob($event));
    }
    
    private function callListener(callable $listener, object $event): void
    {
        try {
            $listener($event);
        } catch (Exception $e) {
            // Log error but don't stop other listeners
            error_log("Event listener error: " . $e->getMessage());
        }
    }
}
```

---

## Event Listeners Registration

### Registration Service

```php
class EventListenersRegistrar
{
    private EventDispatcher $dispatcher;
    private Container $container;
    
    public function __construct(EventDispatcher $dispatcher, Container $container)
    {
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }
    
    public function register(): void
    {
        // Authentication events
        $this->registerAuthenticationListeners();
        
        // User events
        $this->registerUserListeners();
        
        // License events
        $this->registerLicenseListeners();
        
        // Payment events
        $this->registerPaymentListeners();
        
        // Security events
        $this->registerSecurityListeners();
        
        // System events
        $this->registerSystemListeners();
    }
    
    private function registerAuthenticationListeners(): void
    {
        $this->dispatcher->listen(UserLoggedIn::class, function ($event) {
            $this->container->get(AuthenticationListener::class)->onUserLoggedIn($event);
        });
        
        $this->dispatcher->listen(UserRegistered::class, function ($event) {
            $this->container->get(AuthenticationListener::class)->onUserRegistered($event);
        });
    }
    
    private function registerPaymentListeners(): void
    {
        $this->dispatcher->listen(PaymentCompleted::class, function ($event) {
            $this->container->get(PaymentListener::class)->onPaymentCompleted($event);
        });
        
        $this->dispatcher->listen(PaymentFailed::class, function ($event) {
            $this->container->get(PaymentListener::class)->onPaymentFailed($event);
        });
    }
    
    // ... other registration methods
}
```

---

## Summary

The System Events Catalog provides:
- **Comprehensive event definitions** for all domain events
- **Event listener documentation** for each event
- **Event dispatcher implementation** for event handling
- **Async event support** through queue system
- **Event listener registration** service
- **Loose coupling** between modules

Event categories:
- Authentication, User, License, Membership
- Product, Payment, Download, Comment
- Moderation, Notification, Mail, Security
- System, Plugin, Queue, Feature Flag
- Configuration, Analytics, Launcher, Discord
