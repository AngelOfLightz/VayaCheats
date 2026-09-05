# VayaCheats V3.0 - Payment System Flow

## Overview

The payment system handles all monetary transactions, license generation, and membership assignment through a secure, event-driven architecture.

---

## Payment Flow Overview

```
User Initiates Payment
    ↓
Select Plan (License/Membership)
    ↓
Select Gateway (Stripe/PayTR/Iyzico/PayPal)
    ↓
Create Payment Intent
    ↓
[Event: PaymentInitiated]
    ↓
Redirect to Gateway
    ↓
User Completes Payment
    ↓
Gateway Webhook/Callback
    ↓
Verify Payment
    ↓
[Event: PaymentCompleted]
    ↓
Queue: GenerateLicenseJob
    ↓
Queue: CreateAuditLogJob
    ↓
Queue: CreateNotificationJob
    ↓
Queue: SendEmailJob
    ↓
Update User Status
    ↓
Redirect to Success Page
```

---

## Payment Initiation Flow

### Step 1: User Selection

```
User Dashboard
    ↓
Click "Upgrade" or "Purchase"
    ↓
Select Plan (Starter/Pro/Ultimate/Lifetime)
    ↓
Select Duration (Monthly/Yearly/Lifetime)
    ↓
Select Gateway
    ↓
Click "Proceed to Payment"
```

### Step 2: Payment Intent Creation

```php
class PaymentService
{
    public function createPaymentIntent(int $userId, int $planId, string $gateway, string $duration): Payment
    {
        // Get plan details
        $plan = $this->planRepository->find($planId);
        
        // Calculate amount
        $amount = $this->calculateAmount($plan, $duration);
        
        // Create payment record
        $payment = $this->paymentRepository->create([
            'user_id' => $userId,
            'gateway' => $gateway,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'plan_id' => $planId,
            'metadata' => [
                'duration' => $duration,
                'plan_name' => $plan->name
            ]
        ]);
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new PaymentInitiated($payment->id));
        
        return $payment;
    }
}
```

### Step 3: Gateway Redirection

```php
class PaymentProcessor
{
    public function redirectToGateway(Payment $payment): string
    {
        $gateway = $this->gatewayFactory->create($payment->gateway);
        
        $paymentUrl = $gateway->createPayment([
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_id' => $payment->id,
            'return_url' => route('payment.success', $payment->id),
            'cancel_url' => route('payment.cancel', $payment->id)
        ]);
        
        // Update payment with gateway payment ID
        $this->paymentRepository->update($payment->id, [
            'gateway_payment_id' => $gateway->getPaymentId()
        ]);
        
        return $paymentUrl;
    }
}
```

---

## Payment Completion Flow

### Webhook/Callback Handling

```
Gateway Sends Webhook
    ↓
PaymentController@webhook
    ↓
Verify Webhook Signature
    ↓
Parse Webhook Data
    ↓
Find Payment Record
    ↓
Validate Payment Amount
    ↓
Update Payment Status
    ↓
[Event: PaymentCompleted]
    ↓
Return 200 OK
```

### Payment Completion Handler

```php
class PaymentController
{
    public function webhook(Request $request, string $gateway): Response
    {
        // Get gateway instance
        $gateway = $this->gatewayFactory->create($gateway);
        
        // Verify webhook signature
        if (!$gateway->verifyWebhook($request->all())) {
            return response('Invalid signature', 400);
        }
        
        // Handle webhook
        $result = $gateway->handleWebhook($request->all());
        
        // Find payment
        $payment = $this->paymentRepository->findByGatewayId(
            $result['payment_id'],
            $gateway
        );
        
        if (!$payment) {
            return response('Payment not found', 404);
        }
        
        // Update payment status
        $this->paymentRepository->update($payment->id, [
            'status' => $result['status'],
            'completed_at' => now()
        ]);
        
        // If completed, dispatch event
        if ($result['status'] === 'completed') {
            $this->eventDispatcher->dispatch(new PaymentCompleted($payment->id));
        }
        
        return response('OK', 200);
    }
}
```

---

## Post-Payment Processing

### Event Listeners

```php
class PaymentCompletedListener
{
    public function handle(PaymentCompleted $event): void
    {
        $payment = $this->paymentRepository->find($event->paymentId);
        
        // Queue license generation
        $this->queueService->dispatch(new GenerateLicenseJob(
            $payment->user_id,
            $payment->plan_id,
            $payment->id
        ));
        
        // Queue audit log
        $this->queueService->dispatch(new CreateAuditLogJob(
            'payment_completed',
            $payment->user_id,
            $payment->id,
            [
                'amount' => $payment->amount,
                'gateway' => $payment->gateway,
                'plan_id' => $payment->plan_id
            ]
        ));
        
        // Queue notification
        $this->queueService->dispatch(new CreateNotificationJob(
            $payment->user_id,
            'payments',
            'normal',
            'Payment Successful',
            "Your payment of {$payment->amount} USD was successful.",
            route('licenses.index')
        ));
        
        // Queue email
        $this->queueService->dispatch(new SendEmailJob(
            $payment->user_id,
            'payment_receipt',
            [
                'amount' => $payment->amount,
                'plan' => $payment->plan->name,
                'payment_id' => $payment->id
            ]
        ));
    }
}
```

---

## License Generation After Payment

### GenerateLicenseJob

```php
class GenerateLicenseJob extends Job
{
    public function __construct(
        public int $userId,
        public int $planId,
        public int $paymentId
    ) {}
    
    public function handle(): void
    {
        // Get plan details
        $plan = $this->planRepository->find($this->planId);
        
        // Generate license
        $licenseKey = $this->licenseGenerator->generate();
        $licenseHash = $this->licenseGenerator->hash($licenseKey);
        
        // Calculate expiration
        $expirationDate = $this->calculateExpiration($plan);
        
        // Create license
        $license = $this->licenseRepository->create([
            'user_id' => $this->userId,
            'license_key' => $licenseKey,
            'license_hash' => $licenseHash,
            'plan_id' => $this->planId,
            'payment_id' => $this->paymentId,
            'status' => 'active',
            'expiration_date' => $expirationDate,
            'max_activations' => $plan->max_activations,
            'activation_count' => 0
        ]);
        
        // Link license to payment
        $this->paymentRepository->update($this->paymentId, [
            'license_id' => $license->id
        ]);
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new LicenseGenerated($license->id));
    }
    
    private function calculateExpiration(Plan $plan): ?string
    {
        if ($plan->duration_days === null) {
            return null; // Lifetime
        }
        
        return now()->addDays($plan->duration_days);
    }
}
```

---

## Payment Failure Handling

### Failure Scenarios

```
Payment Failed
    ↓
[Event: PaymentFailed]
    ↓
Update Payment Status to 'failed'
    ↓
Queue: CreateNotificationJob
    ↓
Queue: SendEmailJob
    ↓
Notify User of Failure
    ↓
Offer Retry Option
```

### Payment Failed Listener

```php
class PaymentFailedListener
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $this->paymentRepository->find($event->paymentId);
        
        // Queue notification
        $this->queueService->dispatch(new CreateNotificationJob(
            $payment->user_id,
            'payments',
            'high',
            'Payment Failed',
            'Your payment could not be processed. Please try again.',
            route('payment.retry', $payment->id)
        ));
        
        // Queue email
        $this->queueService->dispatch(new SendEmailJob(
            $payment->user_id,
            'payment_failed',
            [
                'amount' => $payment->amount,
                'error' => $event->errorMessage
            ]
        ));
    }
}
```

---

## Payment Refund Flow

### Manual Refund (Owner/Admin)

```
Owner/Admin
    ↓
Select Payment
    ↓
Provide Refund Reason
    ↓
Request Refund from Gateway
    ↓
Gateway Processes Refund
    ↓
[Event: PaymentRefunded]
    ↓
Update Payment Status to 'refunded'
    ↓
Revoke Linked License
    ↓
Queue: CreateNotificationJob
    ↓
Queue: SendEmailJob
    ↓
Queue: CreateAuditLogJob
```

### Refund Handler

```php
class RefundService
{
    public function refund(int $paymentId, string $reason): bool
    {
        $payment = $this->paymentRepository->find($paymentId);
        
        // Get gateway
        $gateway = $this->gatewayFactory->create($payment->gateway);
        
        // Process refund
        $result = $gateway->refundPayment(
            $payment->gateway_payment_id,
            $payment->amount
        );
        
        if ($result['success']) {
            // Update payment status
            $this->paymentRepository->update($paymentId, [
                'status' => 'refunded'
            ]);
            
            // Revoke license if exists
            if ($payment->license_id) {
                $this->licenseService->revoke($payment->license_id, $reason);
            }
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new PaymentRefunded($paymentId));
            
            return true;
        }
        
        return false;
    }
}
```

---

## Payment Gateway Implementations

### Stripe Gateway

```php
class StripeGateway extends BasePaymentGateway
{
    private $stripe;
    
    public function __construct($db, $config)
    {
        parent::__construct($db, $config);
        $this->stripe = new \Stripe\StripeClient($config['secret_key']);
    }
    
    public function createPayment(array $data): array
    {
        $intent = $this->stripe->paymentIntents->create([
            'amount' => $data['amount'] * 100, // cents
            'currency' => $data['currency'],
            'metadata' => [
                'payment_id' => $data['payment_id']
            ]
        ]);
        
        return [
            'payment_id' => $intent->id,
            'client_secret' => $intent->client_secret
        ];
    }
    
    public function verifyWebhook(array $data): bool
    {
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = \Stripe\Webhook::constructEvent(
            $data,
            $sigHeader,
            $this->config['webhook_secret']
        );
        return true;
    }
    
    public function handleWebhook(array $data): array
    {
        $eventType = $data['type'];
        
        switch ($eventType) {
            case 'payment_intent.succeeded':
                return [
                    'payment_id' => $data['data']['object']['id'],
                    'status' => 'completed'
                ];
            case 'payment_intent.payment_failed':
                return [
                    'payment_id' => $data['data']['object']['id'],
                    'status' => 'failed'
                ];
            default:
                return ['status' => 'pending'];
        }
    }
}
```

### PayTR Gateway

```php
class PayTRGateway extends BasePaymentGateway
{
    public function createPayment(array $data): array
    {
        $merchantOid = uniqid();
        $userIp = $_SERVER['REMOTE_ADDR'];
        $userBrowser = $_SERVER['HTTP_USER_AGENT'];
        
        // Generate hash
        $hash = base64_encode(hash_hmac('sha256', 
            $this->config['merchant_key'] . 
            $merchantOid . 
            $data['amount'] . 
            $this->config['merchant_salt'], 
            $data['payment_id'],
            true
        ));
        
        // Create payment request
        $postData = [
            'merchant_id' => $this->config['merchant_id'],
            'merchant_oid' => $merchantOid,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'user_ip' => $userIp,
            'user_browser' => $userBrowser,
            'merchant_ok_url' => route('payment.success', $data['payment_id']),
            'merchant_fail_url' => route('payment.cancel', $data['payment_id']),
            'hash' => $hash
        ];
        
        return [
            'payment_url' => 'https://www.paytr.com/odeme',
            'post_data' => $postData
        ];
    }
    
    public function verifyWebhook(array $data): bool
    {
        $hash = base64_encode(hash_hmac('sha256',
            $this->config['merchant_key'] .
            $this->config['merchant_salt'] .
            $data['merchant_oid'],
            $data['hash'],
            true
        ));
        
        return $hash === $data['hash'];
    }
    
    public function handleWebhook(array $data): array
    {
        $status = $data['status'];
        
        switch ($status) {
            case 'success':
                return [
                    'payment_id' => $data['merchant_oid'],
                    'status' => 'completed'
                ];
            case 'failed':
                return [
                    'payment_id' => $data['merchant_oid'],
                    'status' => 'failed'
                ];
            default:
                return ['status' => 'pending'];
        }
    }
}
```

---

## Payment Security

### Security Measures

1. **Webhook Signature Verification**: All webhooks verified
2. **Idempotency Keys**: Prevent duplicate processing
3. **Amount Validation**: Verify amounts match
4. **IP Whitelisting**: Webhook IPs whitelisted
5. **Rate Limiting**: Payment attempts limited
6. **Audit Logging**: All payment actions logged

### Idempotency

```php
class PaymentService
{
    public function createPaymentIntent(int $userId, int $planId, string $gateway, string $duration): Payment
    {
        // Generate idempotency key
        $idempotencyKey = md5($userId . $planId . $gateway . $duration . time());
        
        // Check for existing payment with same key
        $existing = $this->paymentRepository->findByIdempotencyKey($idempotencyKey);
        
        if ($existing &&in_array($existing->status, ['pending', 'processing'])) {
            return $existing;
        }
        
        // Create new payment
        $payment = $this->paymentRepository->create([
            'user_id' => $userId,
            'gateway' => $gateway,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'plan_id' => $planId,
            'idempotency_key' => $idempotencyKey,
            'metadata' => [
                'duration' => $duration,
                'plan_name' => $plan->name
            ]
        ]);
        
        return $payment;
    }
}
```

---

## Payment Analytics

### Metrics Tracked

- Total revenue by gateway
- Total revenue by plan
- Payment success rate
- Payment failure rate
- Average payment amount
- Revenue by time period
- Refund rate
- Chargeback rate

### Dashboard Display

```
Owner Dashboard → Payment Analytics
├── Total Revenue: $45,678.90
├── This Month: $12,345.67
├── Success Rate: 94.5%
├── Failure Rate: 5.5%
├── Refund Rate: 1.2%
└── Top Gateway:  Stripe (65%)
```

---

## Payment Error Handling

### Error Codes

| Code | Description |
|------|-------------|
| PAY-001 | Invalid payment amount |
| PAY-002 | Invalid currency |
| PAY-003 | Gateway not available |
| PAY-004 | Payment creation failed |
| PAY-005 | Webhook verification failed |
| PAY-006 | Payment not found |
| PAY-007 | Amount mismatch |
| PAY-008 | Refund failed |
| PAY-009 | Duplicate payment |
| PAY-010 | Rate limit exceeded |

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "PAY-004",
        "message": "Payment creation failed",
        "details": {
            "gateway": "stripe",
            "gateway_error": "Invalid API key"
        }
    }
}
```

---

## Payment API Endpoints

### Public Endpoints

```
POST /api/payment/create
Request: { plan_id, duration, gateway }
Response: { payment_id, payment_url, client_secret }

GET /api/payment/{id}/status
Response: { payment_id, status, amount, gateway }
```

### Admin Endpoints

```
GET /admin/payments
Response: { payments: [] }

POST /admin/payments/{id}/refund
Request: { reason }
Response: { success, refund_id }

GET /admin/payments/analytics
Response: { revenue, success_rate, gateway_breakdown }
```

### Owner Endpoints

```
POST /owner/payments/bulk-refund
Request: { payment_ids[], reason }
Response: { refunds: [] }

POST /owner/payments/gateway/config
Request: { gateway, config }
Response: { success }
```

---

## Payment Testing

### Test Mode

All gateways support test mode:

```php
class PaymentService
{
    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
        
        foreach ($this->gateways as $gateway) {
            $gateway->setTestMode($testMode);
        }
    }
}
```

### Test Cards

**Stripe Test Cards:**
- Success: `4242 4242 4242 4242`
- Insufficient Funds: `4000 0025 0000 3155`
- Card Declined: `4000 0000 0000 9995`

**PayTR Test Mode:**
- Use test merchant credentials
- Test amounts: 1.00, 10.00, 100.00

---

## Payment Backup & Recovery

### Backup Strategy

```php
class PaymentBackupService
{
    public function backup(): string
    {
        $payments = $this->paymentRepository->all();
        $backup = json_encode($payments);
        $filename = 'payments_backup_' . date('Y-m-d_His') . '.json';
        Storage::put('backups/' . $filename, $backup);
        return $filename;
    }
    
    public function restore(string $filename): bool
    {
        $backup = Storage::get('backups/' . $filename);
        $payments = json_decode($backup, true);
        
        foreach ($payments as $payment) {
            $this->paymentRepository->updateOrCreate(
                ['id' => $payment['id']],
                $payment
            );
        }
        
        return true;
    }
}
```

---

## Summary

The payment system provides:
- **Multi-gateway support** (Stripe, PayTR, Iyzico, PayPal)
- **Event-driven architecture** for post-payment processing
- **Automatic license generation** on successful payment
- **Comprehensive audit logging** of all payment actions
- **Webhook verification** for security
- **Idempotency** to prevent duplicate processing
- **Refund handling** with license revocation
- **Analytics dashboard** for revenue tracking
- **Test mode** for development

Payments NEVER directly modify user roles.
Payments generate licenses which control product access.
