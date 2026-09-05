<?php
/**
 * Payment Gateway Interface
 * Defines the contract for all payment gateway implementations
 */
interface PaymentGatewayInterface {
    /**
     * Process a payment
     * @param array $paymentData Payment details (amount, currency, etc.)
     * @return array Result with success status and payment details
     */
    public function processPayment(array $paymentData): array;
    
    /**
     * Verify a webhook/notification from the gateway
     * @param array $data Webhook data
     * @return bool Verification result
     */
    public function verifyWebhook(array $data): bool;
    
    /**
     * Handle webhook notification
     * @param array $data Webhook data
     * @return array Result with payment status
     */
    public function handleWebhook(array $data): array;
    
    /**
     * Refund a payment
     * @param string $paymentId Payment ID
     * @param float $amount Amount to refund
     * @return array Result
     */
    public function refundPayment(string $paymentId, float $amount): array;
    
    /**
     * Get gateway name
     * @return string
     */
    public function getGatewayName(): string;
}

/**
 * Base Payment Gateway Class
 * Provides common functionality for all gateways
 */
abstract class BasePaymentGateway implements PaymentGatewayInterface {
    protected $db;
    protected $config;
    protected $gatewayName;
    
    public function __construct($db, $config = []) {
        $this->db = $db;
        $this->config = $config;
    }
    
    /**
     * Log payment event to database
     */
    protected function logPaymentEvent(string $paymentId, string $eventType, string $status, array $requestData = [], array $responseData = [], string $errorMessage = '') {
        try {
            $logStmt = $this->db->prepare("
                INSERT INTO payment_logs (payment_id, gateway, event_type, status, request_data, response_data, error_message, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $paymentId,
                $this->gatewayName,
                $eventType,
                $status,
                json_encode($requestData),
                json_encode($responseData),
                $errorMessage,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Payment log error: " . $e->getMessage());
        }
    }
    
    public function getGatewayName(): string {
        return $this->gatewayName;
    }
}

/**
 * Stripe Payment Gateway Implementation
 */
class StripeGateway extends BasePaymentGateway {
    protected $gatewayName = 'stripe';
    
    public function __construct($db, $config = []) {
        parent::__construct($db, $config);
        // Stripe initialization would go here
        // $this->stripe = new \Stripe\StripeClient($config['secret_key'] ?? '');
    }
    
    public function processPayment(array $paymentData): array {
        $paymentId = 'stripe_' . uniqid();
        
        try {
            // Stripe payment processing logic
            // $paymentIntent = $this->stripe->paymentIntents->create([...]);
            
            $this->logPaymentEvent($paymentId, 'payment_created', 'success', $paymentData);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'status' => 'pending',
                'message' => 'Payment initiated'
            ];
        } catch (Exception $e) {
            $this->logPaymentEvent($paymentId, 'payment_failed', 'error', $paymentData, [], $e->getMessage());
            
            return [
                'success' => false,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyWebhook(array $data): bool {
        // Verify Stripe webhook signature
        // $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        // $event = \Stripe\Webhook::constructEvent($data, $sigHeader, $this->config['webhook_secret']);
        return true;
    }
    
    public function handleWebhook(array $data): array {
        if (!$this->verifyWebhook($data)) {
            return ['success' => false, 'error' => 'Invalid webhook signature'];
        }
        
        $eventType = $data['type'] ?? '';
        $paymentId = $data['data']['object']['id'] ?? '';
        
        $this->logPaymentEvent($paymentId, $eventType, 'received', $data);
        
        // Handle different Stripe event types
        switch ($eventType) {
            case 'payment_intent.succeeded':
                return ['success' => true, 'status' => 'completed', 'payment_id' => $paymentId];
            case 'payment_intent.failed':
                return ['success' => false, 'status' => 'failed', 'payment_id' => $paymentId];
            default:
                return ['success' => true, 'status' => 'pending', 'payment_id' => $paymentId];
        }
    }
    
    public function refundPayment(string $paymentId, float $amount): array {
        try {
            // $refund = $this->stripe->refunds->create(['payment_intent' => $paymentId, 'amount' => $amount * 100]);
            
            $this->logPaymentEvent($paymentId, 'refund_created', 'success', ['amount' => $amount]);
            
            return ['success' => true, 'refund_id' => 'refund_' . uniqid()];
        } catch (Exception $e) {
            $this->logPaymentEvent($paymentId, 'refund_failed', 'error', [], [], $e->getMessage());
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * PayTR Payment Gateway Implementation
 */
class PayTRGateway extends BasePaymentGateway {
    protected $gatewayName = 'paytr';
    
    public function __construct($db, $config = []) {
        parent::__construct($db, $config);
    }
    
    public function processPayment(array $paymentData): array {
        $paymentId = 'paytr_' . uniqid();
        
        try {
            // PayTR payment processing logic
            // Generate merchant_oid, calculate hash, etc.
            
            $this->logPaymentEvent($paymentId, 'payment_created', 'success', $paymentData);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'status' => 'pending',
                'message' => 'Payment initiated'
            ];
        } catch (Exception $e) {
            $this->logPaymentEvent($paymentId, 'payment_failed', 'error', $paymentData, [], $e->getMessage());
            
            return [
                'success' => false,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyWebhook(array $data): bool {
        // Verify PayTR callback hash
        // $hash = base64_encode(hash_hmac('sha256', $merchant_key . $merchant_salt . $data['merchant_oid'], $data['hash'], true));
        return true;
    }
    
    public function handleWebhook(array $data): array {
        if (!$this->verifyWebhook($data)) {
            return ['success' => false, 'error' => 'Invalid callback signature'];
        }
        
        $paymentId = $data['merchant_oid'] ?? '';
        $status = $data['status'] ?? '';
        
        $this->logPaymentEvent($paymentId, 'callback_received', 'success', $data);
        
        // Handle PayTR status values
        switch ($status) {
            case 'success':
                return ['success' => true, 'status' => 'completed', 'payment_id' => $paymentId];
            case 'failed':
                return ['success' => false, 'status' => 'failed', 'payment_id' => $paymentId];
            default:
                return ['success' => true, 'status' => 'pending', 'payment_id' => $paymentId];
        }
    }
    
    public function refundPayment(string $paymentId, float $amount): array {
        // PayTR refund logic
        $this->logPaymentEvent($paymentId, 'refund_requested', 'success', ['amount' => $amount]);
        
        return ['success' => true, 'refund_id' => 'refund_' . uniqid()];
    }
}

/**
 * iyzico Payment Gateway Implementation
 */
class IyzicoGateway extends BasePaymentGateway {
    protected $gatewayName = 'iyzico';
    
    public function __construct($db, $config = []) {
        parent::__construct($db, $config);
    }
    
    public function processPayment(array $paymentData): array {
        $paymentId = 'iyzico_' . uniqid();
        
        try {
            // iyzico payment processing logic
            // Create payment request using iyzico API
            
            $this->logPaymentEvent($paymentId, 'payment_created', 'success', $paymentData);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'status' => 'pending',
                'message' => 'Payment initiated'
            ];
        } catch (Exception $e) {
            $this->logPaymentEvent($paymentId, 'payment_failed', 'error', $paymentData, [], $e->getMessage());
            
            return [
                'success' => false,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyWebhook(array $data): array {
        // Verify iyzico webhook signature
        return true;
    }
    
    public function handleWebhook(array $data): array {
        $paymentId = $data['paymentId'] ?? '';
        $status = $data['paymentStatus'] ?? '';
        
        $this->logPaymentEvent($paymentId, 'webhook_received', 'success', $data);
        
        switch ($status) {
            case 'SUCCESS':
                return ['success' => true, 'status' => 'completed', 'payment_id' => $paymentId];
            case 'FAILURE':
                return ['success' => false, 'status' => 'failed', 'payment_id' => $paymentId];
            default:
                return ['success' => true, 'status' => 'pending', 'payment_id' => $paymentId];
        }
    }
    
    public function refundPayment(string $paymentId, float $amount): array {
        // iyzico refund logic
        $this->logPaymentEvent($paymentId, 'refund_requested', 'success', ['amount' => $amount]);
        
        return ['success' => true, 'refund_id' => 'refund_' . uniqid()];
    }
}

/**
 * PayPal Payment Gateway Implementation
 */
class PayPalGateway extends BasePaymentGateway {
    protected $gatewayName = 'paypal';
    
    public function __construct($db, $config = []) {
        parent::__construct($db, $config);
    }
    
    public function processPayment(array $paymentData): array {
        $paymentId = 'paypal_' . uniqid();
        
        try {
            // PayPal payment processing logic
            // Create order using PayPal REST API
            
            $this->logPaymentEvent($paymentId, 'payment_created', 'success', $paymentData);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'status' => 'pending',
                'message' => 'Payment initiated'
            ];
        } catch (Exception $e) {
            $this->logPaymentEvent($paymentId, 'payment_failed', 'error', $paymentData, [], $e->getMessage());
            
            return [
                'success' => false,
                'payment_id' => $paymentId,
                'gateway' => $this->gatewayName,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyWebhook(array $data): bool {
        // Verify PayPal webhook signature
        // Verify webhook ID with PayPal API
        return true;
    }
    
    public function handleWebhook(array $data): array {
        $eventType = $data['event_type'] ?? '';
        $paymentId = $data['resource']['id'] ?? '';
        
        $this->logPaymentEvent($paymentId, $eventType, 'received', $data);
        
        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                return ['success' => true, 'status' => 'completed', 'payment_id' => $paymentId];
            case 'PAYMENT.CAPTURE.DENIED':
                return ['success' => false, 'status' => 'failed', 'payment_id' => $paymentId];
            default:
                return ['success' => true, 'status' => 'pending', 'payment_id' => $paymentId];
        }
    }
    
    public function refundPayment(string $paymentId, float $amount): array {
        // PayPal refund logic
        $this->logPaymentEvent($paymentId, 'refund_requested', 'success', ['amount' => $amount]);
        
        return ['success' => true, 'refund_id' => 'refund_' . uniqid()];
    }
}

/**
 * Payment Gateway Factory
 * Creates appropriate gateway instance based on configuration
 */
class PaymentGatewayFactory {
    private $db;
    private $configs;
    
    public function __construct($db, array $configs = []) {
        $this->db = $db;
        $this->configs = $configs;
    }
    
    public function createGateway(string $gatewayName): ?PaymentGatewayInterface {
        $config = $this->configs[$gatewayName] ?? [];
        
        switch (strtolower($gatewayName)) {
            case 'stripe':
                return new StripeGateway($this->db, $config);
            case 'paytr':
                return new PayTRGateway($this->db, $config);
            case 'iyzico':
                return new IyzicoGateway($this->db, $config);
            case 'paypal':
                return new PayPalGateway($this->db, $config);
            default:
                return null;
        }
    }
    
    public function getAvailableGateways(): array {
        return ['stripe', 'paytr', 'iyzico', 'paypal'];
    }
}

/**
 * Payment Manager
 * High-level payment processing manager
 */
class PaymentManager {
    private $factory;
    private $db;
    
    public function __construct($db, array $configs = []) {
        $this->db = $db;
        $this->factory = new PaymentGatewayFactory($db, $configs);
    }
    
    /**
     * Process payment using specified gateway
     */
    public function processPayment(string $gatewayName, array $paymentData): array {
        $gateway = $this->factory->createGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'error' => 'Unsupported payment gateway: ' . $gatewayName
            ];
        }
        
        return $gateway->processPayment($paymentData);
    }
    
    /**
     * Handle webhook from any gateway
     */
    public function handleWebhook(string $gatewayName, array $data): array {
        $gateway = $this->factory->createGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'error' => 'Unsupported payment gateway: ' . $gatewayName
            ];
        }
        
        return $gateway->handleWebhook($data);
    }
    
    /**
     * Refund payment
     */
    public function refundPayment(string $gatewayName, string $paymentId, float $amount): array {
        $gateway = $this->factory->createGateway($gatewayName);
        
        if (!$gateway) {
            return [
                'success' => false,
                'error' => 'Unsupported payment gateway: ' . $gatewayName
            ];
        }
        
        return $gateway->refundPayment($paymentId, $amount);
    }
    
    /**
     * Get payment logs
     */
    public function getPaymentLogs(string $paymentId): array {
        $stmt = $this->db->prepare("SELECT * FROM payment_logs WHERE payment_id = ? ORDER BY created_at DESC");
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
