# VayaCheats V3.0 - Owner Control Center Flow

## Overview

The Owner Control Center is a comprehensive dashboard exclusively accessible to Owners (Level 100). It provides complete system control, monitoring, and management capabilities.

---

## Owner Panel Access

### Access Control

```php
class OwnerAccessMiddleware
{
    public function handle(Request $request): Response
    {
        $user = $this->authService->getCurrentUser();
        
        // Only owners can access
        if (!$user || $user->role->level < 100) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Owner access required'
            ], 403);
        }
        
        // Log owner access
        $this->auditService->log('owner', [
            'actor_id' => $user->id,
            'action' => 'owner_panel_access',
            'target_type' => 'system',
            'target_id' => null,
            'severity' => 'info',
            'description' => 'Owner accessed control center',
            'ip_address' => $request->ip()
        ]);
        
        return $request;
    }
}
```

---

## Owner Dashboard

### Live Statistics

```
Owner Dashboard → Overview
├── Current Online Users: 1,234
├── Today's Downloads: 567
├── Today's Revenue: $12,345.67
├── Failed Logins (24h): 23
├── Security Alerts (24h): 5
├── Active Licenses: 4,567
├── Expired Licenses (30d): 234
├── Newest Users (24h): 45
├── Newest Payments (24h): 67
├── Newest Licenses (24h): 67
├── Newest Errors (24h): 3
├── System Health: 98.5%
└── Queue Depth: 12
```

### Dashboard Refresh Flow

```
Owner Opens Dashboard
    ↓
Queue: RefreshDashboardJob
    ↓
Job Processes
    ↓
Collect Statistics
    ↓
[Event: DashboardRefreshed]
    ↓
Cache Results (5 minutes)
    ↓
Return to Frontend
```

---

## Owner Tools

### 1. Role Management

#### Add Owner

```
Owner
    ↓
Select User
    ↓
Confirm Action
    ↓
[Event: OwnerAddRequested]
    ↓
Update User Role to 'owner'
    ↓
[Event: OwnerAdded]
    ↓
Queue: SendEmailJob (to new owner)
    ↓
Queue: CreateNotificationJob (to new owner)
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_owner_logs
```

#### Remove Owner

```
Owner
    ↓
Select Owner
    ↓
Confirm Action
    ↓
[Event: OwnerRemoveRequested]
    ↓
Update User Role to 'admin'
    ↓
[Event: OwnerRemoved]
    ↓
Queue: SendEmailJob (to removed owner)
    ↓
Queue: CreateNotificationJob (to removed owner)
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_owner_logs
```

#### Change User Role

```
Owner
    ↓
Select User
    ↓
Select New Role
    ↓
Provide Reason
    ↓
[Event: RoleChangeRequested]
    ↓
Update User Role
    ↓
[Event: RoleChanged]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_admin_logs
```

### 2. License Management

#### Generate License

```
Owner
    ↓
Select User
    ↓
Select Plan
    ↓
Set Duration (optional)
    ↓
Generate License
    ↓
[Event: LicenseGenerated]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_license_logs
```

#### Bulk Generate Licenses

```
Owner
    ↓
Select Multiple Users
    ↓
Select Plan
    ↓
Set Duration
    ↓
Queue: BulkGenerateLicenseJob
    ↓
Job Processes Each User
    ↓
For Each License:
    ↓
Generate License
    ↓
[Event: LicenseGenerated]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateAuditLogJob
```

#### Revoke License

```
Owner
    ↓
Select License
    ↓
Provide Reason
    ↓
Revoke License
    ↓
[Event: LicenseRevoked]
    ↓
Deactivate All Activations
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_license_logs
```

### 3. Subscription Management

#### Grant Subscription

```
Owner
    ↓
Select User
    ↓
Select Plan
    ↓
Set Duration
    ↓
Grant Subscription
    ↓
[Event: MembershipGranted]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_admin_logs
```

#### Remove Subscription

```
Owner
    ↓
Select User
    ↓
Provide Reason
    ↓
Remove Subscription
    ↓
[Event: MembershipCancelled]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_admin_logs
```

#### Extend Subscription

```
Owner
    ↓
Select User
    ↓
Set Additional Days
    ↓
Extend Subscription
    ↓
[Event: MembershipExtended]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_admin_logs
```

### 4. Payment Management

#### View All Payments

```
Owner
    ↓
Request Payment List
    ↓
Apply Filters (date, gateway, status)
    ↓
Query Database
    ↓
Return Results
    ↓
Log to audit_owner_logs
```

#### Refund Payment

```
Owner
    ↓
Select Payment
    ↓
Provide Reason
    ↓
Request Refund from Gateway
    ↓
Gateway Processes Refund
    ↓
[Event: PaymentRefunded]
    ↓
Update Payment Status
    ↓
Revoke Linked License
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_owner_logs
```

#### Configure Gateway

```
Owner
    ↓
Select Gateway
    ↓
Update Configuration
    ↓
[Event: GatewayConfigured]
    ↓
Update System Settings
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_owner_logs
```

### 5. Audit Center

#### View All Logs

```
Owner
    ↓
Select Log Type
    ↓
Apply Filters
    ↓
Query Database
    ↓
Return Results
    ↓
Log to audit_owner_logs
```

#### Export Logs

```
Owner
    ↓
Select Log Types
    ↓
Set Date Range
    ↓
Queue: ExportAuditLogsJob
    ↓
Job Processes
    ↓
Generate CSV Files
    ↓
Create ZIP Archive
    ↓
[Event: AuditLogsExported]
    ↓
Queue: SendEmailJob (with download link)
    ↓
Queue: CreateAuditLogJob
```

#### View Security Alerts

```
Owner
    ↓
Request Security Alerts
    ↓
Query audit_security_logs
    ↓
Filter by severity (critical, error, warning)
    ↓
Return Results
    ↓
Log to audit_owner_logs
```

### 6. Security Center

#### Block IP Address

```
Owner
    ↓
Enter IP Address
    ↓
Provide Reason
    ↓
Block IP
    ↓
[Event: IPBlocked]
    ↓
Update Security Settings
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_security_logs
```

#### Unblock IP Address

```
Owner
    ↓
Select Blocked IP
    ↓
Unblock IP
    ↓
[Event: IPUnblocked]
    ↓
Update Security Settings
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_security_logs
```

#### Manage Rate Limits

```
Owner
    ↓
Select Endpoint
    ↓
Set Rate Limit
    ↓
Update Configuration
    ↓
[Event: RateLimitUpdated]
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_security_logs
```

### 7. Maintenance Mode

#### Enable Maintenance Mode

```
Owner
    ↓
Confirm Action
    ↓
Enable Maintenance Mode
    ↓
[Event: MaintenanceModeEnabled]
    ↓
Update System Settings
    ↓
Queue: CreateNotificationJob (broadcast)
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

#### Disable Maintenance Mode

```
Owner
    ↓
Confirm Action
    ↓
Disable Maintenance Mode
    ↓
[Event: MaintenanceModeDisabled]
    ↓
Update System Settings
    ↓
Queue: CreateNotificationJob (broadcast)
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

### 8. Mail Templates

#### Edit Template

```
Owner
    ↓
Select Template
    ↓
Edit Content
    ↓
Save Changes
    ↓
[Event: MailTemplateUpdated]
    ↓
Update Database
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_owner_logs
```

#### Test Template

```
Owner
    ↓
Select Template
    ↓
Enter Test Email
    ↓
Send Test Email
    ↓
Queue: SendEmailJob
    ↓
[Event: MailTemplateTested]
    ↓
Log to audit_mail_logs
```

### 9. System Settings

#### Update Setting

```
Owner
    ↓
Select Setting
    ↓
Update Value
    ↓
Save Changes
    ↓
[Event: SystemSettingUpdated]
    ↓
Update Database
    ↓
Clear Cache
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

### 10. Database Status

#### View Database Status

```
Owner
    ↓
Request Database Status
    ↓
Check Connection
    ↓
Get Table Sizes
    ↓
Get Index Status
    ↓
Return Results
    ↓
Log to audit_system_logs
```

#### Run Database Backup

```
Owner
    ↓
Confirm Action
    ↓
Queue: DatabaseBackupJob
    ↓
Job Processes
    ↓
Create Backup File
    ↓
[Event: DatabaseBackedUp]
    ↓
Queue: SendEmailJob (with download link)
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

#### Restore Database

```
Owner
    ↓
Select Backup File
    ↓
Confirm Action (DANGEROUS)
    ↓
Queue: DatabaseRestoreJob
    ↓
Job Processes
    ↓
Restore from Backup
    ↓
[Event: DatabaseRestored]
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

### 11. Background Jobs

#### View Queue Status

```
Owner
    ↓
Request Queue Status
    ↓
Get Queue Depth
    ↓
Get Pending Jobs
    ↓
Get Processing Jobs
    ↓
Get Failed Jobs
    ↓
Return Results
    ↓
Log to audit_system_logs
```

#### Retry Failed Job

```
Owner
    ↓
Select Failed Job
    ↓
Retry Job
    ↓
Queue: RetryJobJob
    ↓
Job Processes
    ↓
[Event: JobRetried]
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

#### Cancel Job

```
Owner
    ↓
Select Pending Job
    ↓
Cancel Job
    ↓
Update Job Status
    ↓
[Event: JobCancelled]
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

### 12. Cron Status

#### View Cron Status

```
Owner
    ↓
Request Cron Status
    ↓
Check Last Execution
    ↓
Check Next Execution
    ↓
Check Execution Time
    ↓
Return Results
    ↓
Log to audit_system_logs
```

#### Run Cron Job Manually

```
Owner
    ↓
Select Cron Job
    ↓
Run Manually
    ↓
Queue: CronJob
    ↓
Job Processes
    ↓
[Event: CronJobExecuted]
    ↓
Queue: CreateAuditLogJob
    ↓
Log to audit_system_logs
```

---

## Owner API Endpoints

### Dashboard

```
GET /owner/dashboard
Response: { stats: {} }

GET /owner/dashboard/refresh
Response: { stats: {} }
```

### Role Management

```
POST /owner/roles/add-owner
Request: { user_id }
Response: { success }

POST /owner/roles/remove-owner
Request: { user_id, reason }
Response: { success }

POST /owner/roles/change
Request: { user_id, new_role, reason }
Response: { success }
```

### License Management

```
POST /owner/licenses/generate
Request: { user_id, plan_id, duration_days }
Response: { license_id, license_key }

POST /owner/licenses/bulk-generate
Request: { user_ids[], plan_id, duration_days }
Response: { licenses: [] }

POST /owner/licenses/{id}/revoke
Request: { reason }
Response: { success }
```

### Subscription Management

```
POST /owner/subscriptions/grant
Request: { user_id, plan_id, duration_days }
Response: { success }

POST /owner/subscriptions/remove
Request: { user_id, reason }
Response: { success }

POST /owner/subscriptions/extend
Request: { user_id, additional_days }
Response: { success }
```

### Payment Management

```
GET /owner/payments
Response: { payments: [] }

POST /owner/payments/{id}/refund
Request: { reason }
Response: { success }

POST /owner/payments/gateway/config
Request: { gateway, config }
Response: { success }
```

### Audit Center

```
GET /owner/audit/all
Response: { logs: [] }

GET /owner/audit/security
Response: { alerts: [] }

GET /owner/audit/export/all
Response: { download_url }
```

### Security Center

```
POST /owner/security/block-ip
Request: { ip_address, reason }
Response: { success }

POST /owner/security/unblock-ip
Request: { ip_address }
Response: { success }

POST /owner/security/rate-limit
Request: { endpoint, limit }
Response: { success }
```

### Maintenance Mode

```
POST /owner/maintenance/enable
Response: { success }

POST /owner/maintenance/disable
Response: { success }
```

### Mail Templates

```
GET /owner/mail/templates
Response: { templates: [] }

POST /owner/mail/templates/{id}/update
Request: { content }
Response: { success }

POST /owner/mail/templates/{id}/test
Request: { test_email }
Response: { success }
```

### System Settings

```
GET /owner/settings
Response: { settings: {} }

POST /owner/settings/update
Request: { key, value }
Response: { success }
```

### Database

```
GET /owner/database/status
Response: { status: {} }

POST /owner/database/backup
Response: { backup_id }

POST /owner/database/restore
Request: { backup_id }
Response: { success }
```

### Queue

```
GET /owner/queue/status
Response: { status: {} }

POST /owner/queue/jobs/{id}/retry
Response: { success }

POST /owner/queue/jobs/{id}/cancel
Response: { success }
```

### Cron

```
GET /owner/cron/status
Response: { status: {} }

POST /owner/cron/{job}/run
Response: { success }
```

---

## Owner Security

### Additional Security Measures

1. **2FA Required**: Owners must have 2FA enabled
2. **IP Whitelisting**: Owner panel access restricted to whitelisted IPs
3. **Session Timeout**: Owner sessions timeout after 15 minutes
4. **Action Confirmation**: Critical actions require confirmation
5. **Audit Logging**: All owner actions logged to audit_owner_logs
6. **Email Notifications**: Critical actions trigger email notifications

### 2FA Implementation

```php
class Owner2FAService
{
    public function require2FA(int $userId): bool
    {
        $user = $this->userRepository->find($userId);
        
        // Owners must have 2FA
        if ($user->role->level >= 100 && !$user->two_factor_enabled) {
            return true;
        }
        
        return false;
    }
    
    public function verify2FA(int $userId, string $code): bool
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user->two_factor_enabled) {
            return false;
        }
        
        return $this->twoFactorService->verify($user, $code);
    }
}
```

### IP Whitelisting

```php
class OwnerIPWhitelistService
{
    public function isWhitelisted(string $ip): bool
    {
        $whitelistedIPs = $this->getWhitelistedIPs();
        
        return in_array($ip, $whitelistedIPs);
    }
    
    public function addToWhitelist(string $ip): void
    {
        $this->settingRepository->set('owner_whitelist', [
            ...$this->getWhitelistedIPs(),
            $ip
        ]);
    }
    
    public function removeFromWhitelist(string $ip): void
    {
        $whitelist = array_filter(
            $this->getWhitelistedIPs(),
            fn($item) => $item !== $ip
        );
        
        $this->settingRepository->set('owner_whitelist', $whitelist);
    }
}
```

---

## Owner Notifications

### Critical Event Notifications

Owners receive notifications for:

- Owner added/removed
- Security alerts (critical)
- Payment failures (high value)
- License generation failures
- Database errors
- System health issues
- Maintenance mode changes

### Notification Categories

```php
class OwnerNotificationService
{
    public function notifyOwner(string $category, string $title, string $message): void
    {
        $owners = $this->userRepository->findByRole('owner');
        
        foreach ($owners as $owner) {
            $this->notificationService->create(
                $owner->id,
                'system',
                'urgent',
                $title,
                $message,
                route('owner.dashboard')
            );
        }
    }
}
```

---

## Summary

The Owner Control Center provides:
- **Complete system control** for owners only
- **Live dashboard** with real-time statistics
- **Role management** (add/remove owners, change roles)
- **License management** (generate, revoke, bulk operations)
- **Subscription management** (grant, remove, extend)
- **Payment management** (view, refund, configure gateways)
- **Audit center** (view all logs, export, security alerts)
- **Security center** (IP blocking, rate limits)
- **Maintenance mode** (enable/disable)
- **Mail templates** (edit, test)
- **System settings** (update configuration)
- **Database operations** (backup, restore, status)
- **Queue management** (view status, retry, cancel)
- **Cron management** (view status, manual execution)

All owner actions are logged to audit_owner_logs with full context.
Admins cannot access any owner panel features.
