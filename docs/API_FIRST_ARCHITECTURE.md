# VayaCheats V3.1 - API-First Architecture

## Overview

VayaCheats V3.1 transforms from a website-centric platform to an API-first platform. The website itself will consume the same internal APIs that future applications (Launcher, Discord Bot, Mobile App, Desktop App) will use.

---

## API-First Principles

1. **API is the Core**: All functionality exposed through APIs
2. **Website is a Client**: Website consumes APIs like any other client
3. **Versioned APIs**: Breaking changes require new versions
4. **Consistent Responses**: Standardized response format across all endpoints
5. **Authentication & Authorization**: Every endpoint secured
6. **Rate Limiting**: Protect against abuse
7. **Documentation**: Auto-generated API documentation

---

## API Architecture

### API Layers

```
┌─────────────────────────────────────────────────────────┐
│                    API Gateway                          │
│  (Rate Limiting, Authentication, Logging, Routing)     │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                   API Versioning                         │
│  /api/v1/*, /api/v2/*, /api/v3/*                        │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                   API Controllers                        │
│  (Request Validation, Business Logic, Response)          │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                   Services Layer                         │
│  (Business Logic, Event Dispatching)                     │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                   Repositories                           │
│  (Data Access, Database Queries)                        │
└─────────────────────────────────────────────────────────┘
```

---

## API Versioning

### Versioning Strategy

```
/api/v1/*    - Current stable version
/api/v2/*    - Next version (when breaking changes needed)
/api/v3/*    - Future versions
```

### Versioning Rules

- **Major Version**: Breaking changes (v1 → v2)
- **Minor Version**: New features, backward compatible (v1.1 → v1.2)
- **Patch Version**: Bug fixes, backward compatible (v1.1.1 → v1.1.2)

### Version Lifecycle

```
Development → Beta → Stable → Deprecated → Sunset
```

### Version Implementation

```php
class APIVersionRouter
{
    private array $versions = [
        'v1' => 'App\API\V1',
        'v2' => 'App\API\V2',
    ];
    
    public function route(string $version, string $endpoint): callable
    {
        if (!isset($this->versions[$version])) {
            throw new APIVersionNotFoundException($version);
        }
        
        $namespace = $this->versions[$version];
        $controllerClass = "{$namespace}\\Controllers\\{$this->getControllerName($endpoint)}";
        $method = $this->getMethodName($endpoint);
        
        return [new $controllerClass, $method];
    }
}
```

---

## Authentication

### Authentication Methods

#### 1. JWT (JSON Web Tokens)

**Flow**:
```
Client
    ↓
POST /api/v1/auth/login
    ↓
Validate Credentials
    ↓
Generate JWT (Access Token + Refresh Token)
    ↓
Return Tokens
    ↓
Client stores tokens
    ↓
Subsequent requests include Access Token in header
    ↓
Server validates token
    ↓
Access granted/denied
```

**JWT Structure**:
```php
class JWTService
{
    private string $secret;
    private int $accessTokenExpiry = 3600; // 1 hour
    private int $refreshTokenExpiry = 2592000; // 30 days
    
    public function generateTokens(int $userId): array
    {
        $accessToken = $this->generateAccessToken($userId);
        $refreshToken = $this->generateRefreshToken($userId);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry
        ];
    }
    
    private function generateAccessToken(int $userId): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + $this->accessTokenExpiry,
            'type' => 'access'
        ];
        
        return JWT::encode($payload, $this->secret, 'HS256');
    }
    
    private function generateRefreshToken(int $userId): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + $this->refreshTokenExpiry,
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16))
        ];
        
        return JWT::encode($payload, $this->secret, 'HS256');
    }
    
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, $this->secret, ['HS256']);
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}
```

#### 2. API Keys

**Flow**:
```
Client
    ↓
Generate API Key (in dashboard)
    ↓
Include API Key in header
    ↓
Server validates key
    ↓
Access granted/denied
```

**API Key Structure**:
```php
class APIKeyService
{
    public function generateKey(int $userId, string $name): string
    {
        $key = 'vaya_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $key);
        
        $this->db->insert('api_keys', [
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $hash,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $key;
    }
    
    public function validateKey(string $key): ?array
    {
        $hash = hash('sha256', $key);
        
        $result = $this->db->query("
            SELECT * FROM api_keys 
            WHERE key_hash = ? AND status = 'active'
        ", [$hash]);
        
        $apiKey = $result->fetch(PDO::FETCH_ASSOC);
        
        if (!$apiKey) {
            return null;
        }
        
        return [
            'user_id' => $apiKey['user_id'],
            'key_id' => $apiKey['id'],
            'permissions' => json_decode($apiKey['permissions'], true)
        ];
    }
}
```

#### 3. Session Cookies (Website)

**Flow**:
```
Website
    ↓
Login via form
    ↓
Session created
    ↓
Session cookie set
    ↓
Subsequent requests include cookie
    ↓
Server validates session
    ↓
Access granted/denied
```

### Authentication Middleware

```php
class AuthenticationMiddleware
{
    private JWTService $jwtService;
    private APIKeyService $apiKeyService;
    private SessionService $sessionService;
    
    public function handle(Request $request): Response
    {
        $authHeader = $request->header('Authorization');
        
        // Try JWT authentication
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $payload = $this->jwtService->validateToken($token);
            
            if ($payload) {
                $request->setUser($this->getUser($payload['sub']));
                $request->setAuthMethod('jwt');
                return $request;
            }
        }
        
        // Try API key authentication
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            $keyData = $this->apiKeyService->validateKey($apiKey);
            
            if ($keyData) {
                $request->setUser($this->getUser($keyData['user_id']));
                $request->setAuthMethod('api_key');
                $request->setAPIKeyPermissions($keyData['permissions']);
                return $request;
            }
        }
        
        // Try session authentication (for website)
        $session = $this->sessionService->get();
        if ($session && isset($session['user_id'])) {
            $request->setUser($this->getUser($session['user_id']));
            $request->setAuthMethod('session');
            return $request;
        }
        
        // No authentication
        throw new AuthenticationException('Authentication required');
    }
}
```

---

## Authorization

### Permission-Based Authorization

```php
class AuthorizationMiddleware
{
    private Gate $gate;
    
    public function handle(Request $request, string $permission): Response
    {
        $user = $request->getUser();
        
        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }
        
        // Check permission
        if (!$this->gate->allows($user->id, $permission)) {
            throw new AuthorizationException("Permission denied: {$permission}");
        }
        
        return $request;
    }
}
```

### Role-Based Authorization

```php
class RoleMiddleware
{
    private RoleService $roleService;
    
    public function handle(Request $request, array $allowedRoles): Response
    {
        $user = $request->getUser();
        
        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }
        
        if (!in_array($user->role->slug, $allowedRoles)) {
            throw new AuthorizationException('Role denied');
        }
        
        return $request;
    }
}
```

---

## Response Format

### Standard Response Structure

```json
{
    "success": true,
    "data": {},
    "meta": {
        "timestamp": "2026-07-31T20:00:00Z",
        "request_id": "req_abc123",
        "version": "v1"
    },
    "errors": []
}
```

### Success Response

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "john_doe",
            "email": "john@example.com",
            "role": "user"
        }
    },
    "meta": {
        "timestamp": "2026-07-31T20:00:00Z",
        "request_id": "req_abc123",
        "version": "v1"
    }
}
```

### Error Response

```json
{
    "success": false,
    "data": null,
    "meta": {
        "timestamp": "2026-07-31T20:00:00Z",
        "request_id": "req_abc123",
        "version": "v1"
    },
    "errors": [
        {
            "code": "AUTH_001",
            "message": "Invalid credentials",
            "field": "password",
            "details": {}
        }
    ]
}
```

### Validation Error Response

```json
{
    "success": false,
    "data": null,
    "meta": {
        "timestamp": "2026-07-31T20:00:00Z",
        "request_id": "req_abc123",
        "version": "v1"
    },
    "errors": [
        {
            "code": "VALIDATION_001",
            "message": "Validation failed",
            "field": "email",
            "details": {
                "rule": "email",
                "value": "invalid-email"
            }
        }
    ]
}
```

### Response Builder

```php
class ResponseBuilder
{
    public static function success($data = null, int $statusCode = 200): Response
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->id(),
                'version' => 'v1'
            ],
            'errors' => []
        ], $statusCode);
    }
    
    public static function error(string $code, string $message, int $statusCode = 400, array $details = []): Response
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->id(),
                'version' => 'v1'
            ],
            'errors' => [
                [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details
                ]
            ]
        ], $statusCode);
    }
    
    public static function validationErrors(array $errors): Response
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->id(),
                'version' => 'v1'
            ],
            'errors' => $errors
        ], 422);
    }
}
```

---

## Pagination

### Pagination Strategy

```
GET /api/v1/users?page=1&per_page=20&sort=created_at&order=desc
```

### Paginated Response

```json
{
    "success": true,
    "data": {
        "items": [],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total_items": 100,
            "total_pages": 5,
            "has_next_page": true,
            "has_previous_page": false,
            "next_page_url": "/api/v1/users?page=2&per_page=20",
            "previous_page_url": null
        }
    },
    "meta": {
        "timestamp": "2026-07-31T20:00:00Z",
        "request_id": "req_abc123",
        "version": "v1"
    }
}
```

### Pagination Implementation

```php
class PaginationService
{
    public function paginate(Builder $query, int $page, int $perPage): array
    {
        $totalItems = $query->count();
        $totalPages = (int) ceil($totalItems / $perPage);
        
        $offset = ($page - 1) * $perPage;
        $items = $query->offset($offset)->limit($perPage)->get();
        
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
                'next_page_url' => $page < $totalPages 
                    ? request()->url() . '?page=' . ($page + 1) . '&per_page=' . $perPage 
                    : null,
                'previous_page_url' => $page > 1 
                    ? request()->url() . '?page=' . ($page - 1) . '&per_page=' . $perPage 
                    : null
            ]
        ];
    }
}
```

---

## Rate Limiting

### Rate Limiting Strategy

```
Rate limits by:
- API endpoint
- Authentication method
- User ID (if authenticated)
- IP address (if not authenticated)
```

### Rate Limit Configuration

```php
class RateLimitConfig
{
    public static array $limits = [
        // Public endpoints
        'public' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000
        ],
        
        // Authenticated endpoints
        'authenticated' => [
            'requests_per_minute' => 120,
            'requests_per_hour' => 2000
        ],
        
        // Admin endpoints
        'admin' => [
            'requests_per_minute' => 300,
            'requests_per_hour' => 5000
        ],
        
        // Specific endpoints
        'auth.login' => [
            'requests_per_minute' => 5,
            'requests_per_hour' => 20
        ],
        'auth.register' => [
            'requests_per_minute' => 3,
            'requests_per_hour' => 10
        ],
        'api.license.validate' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 500
        ]
    ];
}
```

### Rate Limit Middleware

```php
class RateLimitMiddleware
{
    private RateLimiter $limiter;
    
    public function handle(Request $request, string $limitKey): Response
    {
        $identifier = $this->getIdentifier($request);
        $limit = RateLimitConfig::$limits[$limitKey];
        
        if (!$this->limiter->attempt($identifier, $limit['requests_per_minute'], 60)) {
            throw new RateLimitException('Rate limit exceeded');
        }
        
        // Add rate limit headers to response
        $response = $request;
        $response->headers->set('X-RateLimit-Limit', $limit['requests_per_minute']);
        $response->headers->set('X-RateLimit-Remaining', $this->limiter->remaining($identifier, 60));
        $response->headers->set('X-RateLimit-Reset', $this->limiter->availableIn($identifier, 60));
        
        return $response;
    }
    
    private function getIdentifier(Request $request): string
    {
        $user = $request->getUser();
        
        if ($user) {
            return 'user:' . $user->id;
        }
        
        return 'ip:' . $request->ip();
    }
}
```

---

## API Endpoints

### Authentication Endpoints

```
POST   /api/v1/auth/login
POST   /api/v1/auth/register
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
GET    /api/v1/auth/me
```

### User Endpoints

```
GET    /api/v1/users
GET    /api/v1/users/{id}
GET    /api/v1/users/me
PUT    /api/v1/users/me
PATCH  /api/v1/users/me
DELETE /api/v1/users/me
GET    /api/v1/users/{id}/profile
```

### License Endpoints

```
GET    /api/v1/licenses
GET    /api/v1/licenses/{id}
GET    /api/v1/licenses/me
POST   /api/v1/licenses/validate
POST   /api/v1/licenses/activate
POST   /api/v1/licenses/deactivate
```

### Product Endpoints

```
GET    /api/v1/products
GET    /api/v1/products/{id}
GET    /api/v1/products/{id}/versions
GET    /api/v1/products/{id}/download
```

### Payment Endpoints

```
POST   /api/v1/payments/create
GET    /api/v1/payments/{id}
POST   /api/v1/payments/{id}/refund
GET    /api/v1/payments/gateways
```

### Notification Endpoints

```
GET    /api/v1/notifications
GET    /api/v1/notifications/{id}
POST   /api/v1/notifications/{id}/read
POST   /api/v1/notifications/read-all
DELETE /api/v1/notifications/{id}
```

### Admin Endpoints

```
GET    /api/v1/admin/users
POST   /api/v1/admin/users
PUT    /api/v1/admin/users/{id}
DELETE /api/v1/admin/users/{id}
GET    /api/v1/admin/licenses
POST   /api/v1/admin/licenses/generate
POST   /api/v1/admin/licenses/{id}/revoke
GET    /api/v1/admin/payments
GET    /api/v1/admin/audit
```

### Owner Endpoints

```
GET    /api/v1/owner/dashboard
GET    /api/v1/owner/settings
PUT    /api/v1/owner/settings
POST   /api/v1/owner/roles/add-owner
POST   /api/v1/owner/plugins/install
POST   /api/v1/owner/plugins/uninstall
```

### Launcher Endpoints

```
POST   /api/v1/launcher/auth
POST   /api/v1/launcher/validate-license
GET    /api/v1/launcher/products
GET    /api/v1/launcher/products/{id}/download
GET    /api/v1/launcher/version
POST   /api/v1/launcher/report
```

---

## API Documentation

### OpenAPI/Swagger Specification

```yaml
openapi: 3.0.0
info:
  title: VayaCheats API
  version: 1.0.0
  description: VayaCheats API Documentation
servers:
  - url: https://api.vayacheats.com/v1
    description: Production server
  - url: https://api-staging.vayacheats.com/v1
    description: Staging server
paths:
  /auth/login:
    post:
      summary: Login user
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                email:
                  type: string
                password:
                  type: string
      responses:
        '200':
          description: Successful login
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/LoginResponse'
components:
  schemas:
    LoginResponse:
      type: object
      properties:
        success:
          type: boolean
        data:
          type: object
          properties:
            access_token:
              type: string
            refresh_token:
              type: string
```

### Auto-Documentation

```php
class APIDocumentationGenerator
{
    public function generate(): string
    {
        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'VayaCheats API',
                'version' => '1.0.0'
            ],
            'paths' => $this->generatePaths(),
            'components' => [
                'schemas' => $this->generateSchemas()
            ]
        ];
        
        return json_encode($openapi, JSON_PRETTY_PRINT);
    }
    
    private function generatePaths(): array
    {
        // Scan controllers for route annotations
        // Generate OpenAPI paths
    }
}
```

---

## API Client SDKs

### JavaScript SDK (Website)

```javascript
class VayaCheatsAPI {
    constructor(baseURL, apiKey = null) {
        this.baseURL = baseURL;
        this.apiKey = apiKey;
        this.accessToken = null;
    }
    
    async login(email, password) {
        const response = await fetch(`${this.baseURL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            this.accessToken = data.data.access_token;
            localStorage.setItem('refresh_token', data.data.refresh_token);
        }
        
        return data;
    }
    
    async get(endpoint) {
        const response = await fetch(`${this.baseURL}${endpoint}`, {
            headers: {
                'Authorization': `Bearer ${this.accessToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        return response.json();
    }
    
    async post(endpoint, data) {
        const response = await fetch(`${this.baseURL}${endpoint}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.accessToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        return response.json();
    }
}
```

### C# SDK (Launcher)

```csharp
public class VayaCheatsAPI
{
    private HttpClient _client;
    private string _baseURL;
    private string _accessToken;
    
    public VayaCheatsAPI(string baseURL)
    {
        _baseURL = baseURL;
        _client = new HttpClient();
        _client.BaseAddress = new Uri(baseURL);
    }
    
    public async Task<LoginResponse> LoginAsync(string email, string password)
    {
        var request = new LoginRequest { Email = email, Password = password };
        var response = await _client.PostAsJsonAsync("/auth/login", request);
        
        var result = await response.Content.ReadAsAsync<APIResponse<LoginResponse>>();
        
        if (result.Success)
        {
            _accessToken = result.Data.AccessToken;
            _client.DefaultRequestHeaders.Authorization = 
                new AuthenticationHeaderValue("Bearer", _accessToken);
        }
        
        return result.Data;
    }
    
    public async Task<T> GetAsync<T>(string endpoint)
    {
        var response = await _client.GetAsync(endpoint);
        var result = await response.Content.ReadAsAsync<APIResponse<T>>();
        return result.Data;
    }
}
```

---

## API Security

### Security Headers

```php
class SecurityHeadersMiddleware
{
    public function handle(Request $request): Response
    {
        $response = $request;
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        
        return $response;
    }
}
```

### CORS Configuration

```php
class CORSMiddleware
{
    private array $allowedOrigins = [
        'https://vayacheats.com',
        'https://www.vayacheats.com'
    ];
    
    public function handle(Request $request): Response
    {
        $origin = $request->header('Origin');
        
        if (in_array($origin, $this->allowedOrigins)) {
            $response = $request;
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }
        
        return $request;
    }
}
```

---

## API Monitoring

### Request Logging

```php
class APIRequestLogger
{
    private AuditService $auditService;
    
    public function log(Request $request, Response $response): void
    {
        $this->auditService->log('api', [
            'actor_id' => $request->getUser()?->id,
            'action' => 'api_request',
            'target_type' => 'endpoint',
            'target_id' => $request->path(),
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => "API request: {$request->method()} {$request->path()}",
            'metadata' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->status(),
                'response_time' => $response->getResponseTime(),
                'auth_method' => $request->getAuthMethod()
            ]
        ]);
    }
}
```

### Error Tracking

```php
class APIErrorTracker
{
    private ErrorLogger $errorLogger;
    
    public function track(Exception $exception, Request $request): void
    {
        $this->errorLogger->log([
            'user_id' => $request->getUser()?->id,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'file_path' => $exception->getFile(),
            'line_number' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'severity' => 'error',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'endpoint' => $request->path(),
                'method' => $request->method()
            ]
        ]);
    }
}
```

---

## Summary

The API-First Architecture provides:
- **Unified API** for all clients (Website, Launcher, Mobile, Discord)
- **Multiple authentication methods** (JWT, API Keys, Sessions)
- **Versioned APIs** for backward compatibility
- **Standardized response format** across all endpoints
- **Pagination** for large datasets
- **Rate limiting** to prevent abuse
- **Comprehensive authorization** with permissions
- **Auto-generated documentation** (OpenAPI/Swagger)
- **Client SDKs** for easy integration
- **Security headers** and CORS configuration
- **Request logging** and error tracking

All future clients will consume the same APIs as the website, ensuring consistency and reducing duplication.
