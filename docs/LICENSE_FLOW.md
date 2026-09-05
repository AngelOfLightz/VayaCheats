# VayaCheats V3.0 - License System Flow

## Overview

The license system is the core access control mechanism for VayaCheats. Licenses are independent from user roles and determine product access.

---

## License Types

### License Plans

| Plan | Level | Monthly | Yearly | Lifetime | Duration | Max Activations |
|------|-------|---------|--------|----------|----------|-----------------|
| Free | 0 | $0.00 | $0.00 | N/A | 365 days | 1 |
| Starter | 1 | $9.99 | $99.99 | N/A | 365 days | 1 |
| Pro | 2 | $19.99 | $199.99 | N/A | 365 days | 2 |
| Ultimate | 3 | $39.99 | $399.99 | N/A | 365 days | 3 |
| Lifetime | 99 | N/A | N/A | $999.99 | Permanent | 5 |

---

## License Generation Flow

### Manual Generation (Owner/Admin)

```
Owner/Admin
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
Save to Database
    ↓
[Event: LicenseCreated]
    ↓
Send Email to User
    ↓
[Event: LicenseDelivered]
    ↓
Log to Audit
```

### Automatic Generation (Payment)

```
Payment Completed
    ↓
[Event: PaymentCompleted]
    ↓
Queue: GenerateLicenseJob
    ↓
Generate License Key
    ↓
Hash License Key
    ↓
Save to Database
    ↓
[Event: LicenseGenerated]
    ↓
Link to Payment
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
    ↓
[Event: LicenseDelivered]
```

---

## License Key Generation

### Algorithm

```php
class LicenseGenerator
{
    private const PREFIX = 'VAYA';
    private const SEGMENTS = 4;
    private const SEGMENT_LENGTH = 4;
    
    public function generate(): string
    {
        $segments = [];
        
        for ($i = 0; $i < self::SEGMENTS; $i++) {
            $segment = '';
            for ($j = 0; $j < self::SEGMENT_LENGTH; $j++) {
                $segment .= $this->randomChar();
            }
            $segments[] = $segment;
        }
        
        $key = self::PREFIX . '-' . implode('-', $segments);
        return $key;
    }
    
    private function randomChar(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        return $chars[random_int(0, strlen($chars) - 1)];
    }
    
    public function hash(string $key): string
    {
        return hash('sha256', $key . getenv('LICENSE_SECRET'));
    }
}
```

### Example Output

```
VAYA-X7K2-M9P4-Q1R8-T3N5
```

---

## License Activation Flow

### Initial Activation

```
User Receives License
    ↓
User Enters License Key
    ↓
Validate License Format
    ↓
Check License Status
    ↓
Check Expiration Date
    ↓
Check Activation Count
    ↓
Generate Hardware ID
    ↓
[Event: LicenseActivationRequested]
    ↓
Create Activation Record
    ↓
Increment Activation Count
    ↓
Update Last Used
    ↓
[Event: LicenseActivated]
    ↓
Send Success Response
    ↓
Queue: CreateAuditLogJob
```

### Subsequent Activations (Same Hardware)

```
User Enters License Key
    ↓
Validate License Format
    ↓
Check License Status
    ↓
Check Expiration Date
    ↓
Generate Hardware ID
    ↓
Check Existing Activations
    ↓
Match Found (Same Hardware ID)
    ↓
Update Last Used
    ↓
[Event: LicenseReactivated]
    ↓
Send Success Response
```

### Activation Denied Scenarios

```
Invalid License Key Format
    ↓
[Event: LicenseValidationFailed]
    ↓
Error: Invalid license key

License Not Found
    ↓
[Event: LicenseNotFound]
    ↓
Error: License key not found

License Expired
    ↓
[Event: LicenseExpired]
    ↓
Error: License has expired

License Revoked
    ↓
[Event: LicenseRevoked]
    ↓
Error: License has been revoked

Max Activations Reached
    ↓
[Event: LicenseMaxActivationsReached]
    ↓
Error: Maximum activations reached
```

---

## License Validation Flow

### Real-Time Validation

```
Application Requests Validation
    ↓
Provide License Key
    ↓
Provide Hardware ID
    ↓
Validate License Format
    ↓
Check License Status (Active)
    ↓
Check Expiration Date
    ↓
Check Activation Count
    ↓
Check Hardware ID Match
    ↓
[Event: LicenseValidationRequested]
    ↓
Return Validation Result
    ↓
Queue: CreateAuditLogJob
```

### Validation Response

```json
{
    "valid": true,
    "license": {
        "plan": "Pro",
        "level": 2,
        "expires_at": "2026-12-31T23:59:59Z",
        "activations": {
            "current": 1,
            "maximum": 2
        }
    },
    "products": [
        {
            "id": 1,
            "name": "Advanced Cheat",
            "minimum_level": 2,
            "accessible": true
        }
    ]
}
```

---

## License Expiration Flow

### Automatic Expiration

```
Cron Job: ExpireLicensesJob
    ↓
Query Active Licenses
    ↓
Filter: expiration_date < NOW()
    ↓
For Each License:
    ↓
Update Status to 'expired'
    ↓
[Event: LicenseExpired]
    ↓
Queue: CreateNotificationJob
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateAuditLogJob
```

### Expiration Warning (7 Days Before)

```
Cron Job: CheckExpiringLicensesJob
    ↓
Query Active Licenses
    ↓
Filter: expiration_date BETWEEN NOW() AND NOW() + 7 DAYS
    ↓
For Each License:
    ↓
Check if warning already sent
    ↓
Queue: CreateNotificationJob
    ↓
Queue: SendEmailJob
    ↓
Mark warning sent
```

---

## License Revocation Flow

### Manual Revocation (Owner/Admin)

```
Owner/Admin
    ↓
Select License
    ↓
Provide Reason
    ↓
Revoke License
    ↓
[Event: LicenseRevocationRequested]
    ↓
Update Status to 'revoked'
    ↓
Deactivate All Activations
    ↓
[Event: LicenseRevoked]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
```

### Automatic Revocation (Payment Refunded)

```
Payment Refunded
    ↓
[Event: PaymentRefunded]
    ↓
Find Linked License
    ↓
Revoke License
    ↓
[Event: LicenseRevoked]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
```

---

## License Renewal Flow

### Manual Renewal (Owner/Admin)

```
Owner/Admin
    ↓
Select License
    ↓
Set New Duration
    ↓
Renew License
    ↓
[Event: LicenseRenewalRequested]
    ↓
Update Expiration Date
    ↓
Update Status to 'active'
    ↓
[Event: LicenseRenewed]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
```

### Automatic Renewal (Payment)

```
Payment Completed (Renewal)
    ↓
[Event: PaymentCompleted]
    ↓
Find Existing License
    ↓
Extend Expiration Date
    ↓
[Event: LicenseRenewed]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
```

---

## License Upgrade Flow

```
User Requests Upgrade
    ↓
Select New Plan
    ↓
Process Payment
    ↓
Payment Completed
    ↓
[Event: PaymentCompleted]
    ↓
Find Existing License
    ↓
Update Plan ID
    ↓
Update Expiration Date
    ↓
[Event: LicenseUpgraded]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
```

---

## License Downgrade Flow

```
Owner/Admin
    ↓
Select License
    ↓
Select New Plan (Lower Level)
    ↓
Provide Reason
    ↓
Downgrade License
    ↓
[Event: LicenseDowngradeRequested]
    ↓
Update Plan ID
    ↓
Keep Expiration Date
    ↓
[Event: LicenseDowngraded]
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: CreateAuditLogJob
```

---

## License History Tracking

Every license action is logged:

```php
class LicenseHistoryService
{
    public function log(
        int $licenseId,
        int $userId,
        string $action,
        ?int $fromPlanId,
        ?int $toPlanId,
        ?string $oldExpiration,
        ?string $newExpiration,
        ?int $performedBy,
        ?string $reason
    ): void {
        $this->repository->create([
            'license_id' => $licenseId,
            'user_id' => $userId,
            'action' => $action,
            'from_plan_id' => $fromPlanId,
            'to_plan_id' => $toPlanId,
            'old_expiration_date' => $oldExpiration,
            'new_expiration_date' => $newExpiration,
            'performed_by' => $performedBy,
            'reason' => $reason
        ]);
    }
}
```

### History Actions

- `generated` - License created
- `activated` - License activated
- `deactivated` - License deactivated
- `expired` - License expired
- `revoked` - License revoked
- `renewed` - License renewed
- `upgraded` - License upgraded
- `downgraded` - License downgraded

---

## License Delivery Flow

### Email Delivery

```
License Generated
    ↓
Queue: SendEmailJob
    ↓
Load Template: license_delivery
    ↓
Replace Variables:
    - {username}
    - {license_key}
    - {plan_name}
    - {expiration_date}
    - {activation_instructions}
    ↓
Send Email
    ↓
[Event: LicenseEmailSent]
    ↓
Log to audit_mail_logs
```

### Email Template

```
Subject: Your VayaCheats License Key

Dear {username},

Your license has been generated successfully!

License Key: {license_key}
Plan: {plan_name}
Expires: {expiration_date}

Activation Instructions:
1. Open the VayaCheats launcher
2. Enter your license key
3. Click "Activate"
4. Enjoy!

If you have any questions, please contact support.

Best regards,
VayaCheats Team
```

---

## License Security

### Protection Measures

1. **Hash Storage**: License keys stored as SHA-256 hashes
2. **Hardware Binding**: Licenses bound to hardware IDs
3. **Activation Limits**: Maximum activations per license
4. **Expiration Checks**: Real-time expiration validation
5. **Revocation**: Immediate revocation capability
6. **Audit Logging**: All license actions logged

### Anti-Piracy Measures

1. **Server Validation**: Real-time validation required
2. **Hardware Fingerprinting**: Unique hardware ID generation
3. **Rate Limiting**: Validation attempts limited
4. **IP Tracking**: Validation attempts tracked by IP
5. **Anomaly Detection**: Suspicious activity flagged

---

## License API Endpoints

### Public Endpoints

```
POST /api/license/validate
Request: { license_key, hardware_id }
Response: { valid, license, products }

POST /api/license/activate
Request: { license_key, hardware_id }
Response: { success, message, activation_id }
```

### Admin Endpoints

```
GET /admin/licenses
Response: { licenses: [] }

POST /admin/licenses/generate
Request: { user_id, plan_id, duration_days }
Response: { license_id, license_key }

POST /admin/licenses/{id}/revoke
Request: { reason }
Response: { success }

POST /admin/licenses/{id}/renew
Request: { duration_days }
Response: { success, new_expiration }
```

### Owner Endpoints

```
POST /owner/licenses/bulk-generate
Request: { user_ids[], plan_id, duration_days }
Response: { licenses: [] }

POST /owner/licenses/{id}/downgrade
Request: { plan_id, reason }
Response: { success }
```

---

## License Statistics

### Metrics Tracked

- Total licenses by plan
- Active licenses by plan
- Expired licenses by plan
- Revoked licenses by plan
- Activation rate
- Average activations per license
- License expiration timeline
- Revenue by license type

### Dashboard Display

```
Owner Dashboard → License Statistics
├── Total Licenses: 1,234
├── Active Licenses: 987
├── Expired Licenses: 234
├── Revoked Licenses: 13
├── Activation Rate: 89.5%
└── Revenue This Month: $12,345.67
```

---

## License Error Handling

### Error Codes

| Code | Description |
|------|-------------|
| LIC-001 | Invalid license key format |
| LIC-002 | License not found |
| LIC-003 | License expired |
| LIC-004 | License revoked |
| LIC-005 | Maximum activations reached |
| LIC-006 | Hardware ID mismatch |
| LIC-007 | License already active on this hardware |
| LIC-008 | License generation failed |
| LIC-009 | License validation failed |
| LIC-010 | License revocation failed |

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "LIC-005",
        "message": "Maximum activations reached",
        "details": {
            "current": 2,
            "maximum": 2
        }
    }
}
```

---

## License Backup & Recovery

### Backup Strategy

```php
class LicenseBackupService
{
    public function backup(): string
    {
        $licenses = $this->repository->all();
        $backup = json_encode($licenses);
        $filename = 'licenses_backup_' . date('Y-m-d_His') . '.json';
        Storage::put('backups/' . $filename, $backup);
        return $filename;
    }
    
    public function restore(string $filename): bool
    {
        $backup = Storage::get('backups/' . $filename);
        $licenses = json_decode($backup, true);
        
        foreach ($licenses as $license) {
            $this->repository->updateOrCreate(
                ['id' => $license['id']],
                $license
            );
        }
        
        return true;
    }
}
```

---

## License Migration Path

### From Current System

1. **Export existing licenses** from current database
2. **Transform data** to new schema
3. **Import to new tables**
4. **Validate all licenses**
5. **Notify users** of migration
6. **Phase out old system**

### Migration Script

```php
class LicenseMigration
{
    public function migrate(): void
    {
        // Get old licenses
        $oldLicenses = DB::table('kullanicilar')->get();
        
        foreach ($oldLicenses as $old) {
            // Transform to new format
            $newLicense = [
                'user_id' => $old->id,
                'license_key' => $this->generateKey(),
                'license_hash' => $this->hash($key),
                'plan_id' => $this->mapPlan($old->role),
                'status' => 'active',
                'expiration_date' => $old->bitis_tarihi,
                'max_activations' => 1,
                'activation_count' => 0,
            ];
            
            // Insert into new table
            DB::table('licenses')->insert($newLicense);
        }
    }
}
```

---

## Summary

The license system provides:
- **Secure key generation** with unique format
- **Hardware binding** for anti-piracy
- **Activation limits** per license
- **Automatic expiration** handling
- **Comprehensive audit logging**
- **Email delivery** of licenses
- **API endpoints** for validation
- **Backup & recovery** capabilities
- **Migration path** from current system

Licenses are independent from user roles and control product access.
