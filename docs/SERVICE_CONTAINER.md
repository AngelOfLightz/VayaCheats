# VayaCheats V3.1 - Service Container (Dependency Injection)

## Overview

The Service Container implements Dependency Injection to manage class dependencies, enable loose coupling, and facilitate testing. Every module exposes interfaces, and the container resolves dependencies automatically.

---

## Container Architecture

### Container Responsibilities

1. **Dependency Resolution**: Automatically resolve class dependencies
2. **Interface Binding**: Bind interfaces to concrete implementations
3. **Singleton Management**: Manage singleton instances
4. **Factory Support**: Support factory functions for complex instantiation
5. **Contextual Binding**: Bind different implementations based on context
6. **Automatic Wiring**: Automatically inject constructor dependencies

---

## Container Implementation

### Container Interface

```php
interface ContainerInterface
{
    public function bind(string $abstract, $concrete = null, bool $shared = false): void;
    public function singleton(string $abstract, $concrete = null): void;
    public function instance(string $abstract, $instance): void;
    public function factory(string $abstract, callable $factory): void;
    public function make(string $abstract);
    public function has(string $abstract): bool;
    public function forget(string $abstract): void;
    public function flush(): void;
    public function call(callable $callback, array $parameters = []);
}
```

### Container Implementation

```php
class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $aliases = [];
    private array $buildStack = [];
    
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);
        
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $this->getClosure($abstract, $concrete),
            'shared' => $shared
        ];
    }
    
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }
    
    public function instance(string $abstract, $instance): void
    {
        $this->removeAbstractAlias($abstract);
        
        $this->instances[$abstract] = $instance;
    }
    
    public function factory(string $abstract, callable $factory): void
    {
        $this->bind($abstract, $factory);
    }
    
    public function make(string $abstract)
    {
        $abstract = $this->getAlias($abstract);
        
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        $concrete = $this->getConcrete($abstract);
        
        if ($this->isBuildable($concrete)) {
            $object = $this->build($concrete);
        } else {
            $object = $concrete($this, $abstract);
        }
        
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }
    
    public function forget(string $abstract): void
    {
        $abstract = $this->getAlias($abstract);
        
        unset($this->instances[$abstract]);
        unset($this->bindings[$abstract]);
    }
    
    public function flush(): void
    {
        $this->aliases = [];
        $this->bindings = [];
        $this->instances = [];
    }
    
    public function call(callable $callback, array $parameters = [])
    {
        return $callback($this, $parameters);
    }
    
    protected function getClosure(string $abstract, $concrete): Closure
    {
        return function ($container, $abstract) use ($concrete) {
            if ($concrete instanceof Closure) {
                return $concrete($container, $abstract);
            }
            
            return $container->build($concrete);
        };
    }
    
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        
        return $abstract;
    }
    
    protected function isBuildable($concrete): bool
    {
        return is_string($concrete) && !$this->isCallable($concrete);
    }
    
    protected function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }
        
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }
        
        $constructor = $reflector->getConstructor();
        
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        $dependencies = $constructor->getParameters();
        
        $instances = $this->resolveDependencies($dependencies);
        
        return $reflector->newInstanceArgs($instances);
    }
    
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];
        
        foreach ($dependencies as $dependency) {
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
            } else {
                $results[] = $this->make($dependency->getClass()->name);
            }
        }
        
        return $results;
    }
    
    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }
    
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }
    
    protected function getAlias(string $abstract): string
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }
        
        return $this->getAlias($this->aliases[$abstract]);
    }
    
    protected function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }
    
    protected function removeAbstractAlias(string $abstract): void
    {
        if (!isset($this->bindings[$abstract])) {
            return;
        }
        
        foreach ($this->aliases as $alias => $bound) {
            if ($bound === $abstract) {
                unset($this->aliases[$alias]);
            }
        }
    }
    
    protected function isCallable($concrete): bool
    {
        return is_callable($concrete) || $concrete instanceof Closure;
    }
}
```

---

## Service Providers

### Service Provider Interface

```php
interface ServiceProviderInterface
{
    public function register(): void;
    public function boot(): void;
}
```

### Abstract Service Provider

```php
abstract class ServiceProvider implements ServiceProviderInterface
{
    protected Container $app;
    
    public function __construct(Container $app)
    {
        $this->app = $app;
    }
    
    public function register(): void
    {
        // Override in child classes
    }
    
    public function boot(): void
    {
        // Override in child classes
    }
    
    protected function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->app->bind($abstract, $concrete, $shared);
    }
    
    protected function singleton(string $abstract, $concrete = null): void
    {
        $this->app->singleton($abstract, $concrete);
    }
    
    protected function instance(string $abstract, $instance): void
    {
        $this->app->instance($abstract, $instance);
    }
}
```

---

## Core Service Providers

### DatabaseServiceProvider

```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(Database::class, function ($app) {
            $config = $app->get('config')->get('database');
            
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return new Database($pdo);
        });
    }
    
    public function boot(): void
    {
        // Set up database connection pooling if needed
    }
}
```

### CacheServiceProvider

```php
class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(CacheInterface::class, function ($app) {
            $config = $app->get('config')->get('cache');
            
            return match ($config['driver']) {
                'file' => new FileCache($config['path']),
                'redis' => new RedisCache($config['redis']),
                'apcu' => new APCuCache(),
                default => new FileCache($config['path'])
            };
        });
    }
}
```

### EventDispatcherServiceProvider

```php
class EventDispatcherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(EventDispatcher::class, function ($app) {
            return new EventDispatcher($app->get(QueueService::class));
        });
    }
    
    public function boot(): void
    {
        $dispatcher = $this->app->get(EventDispatcher::class);
        
        // Register event listeners
        $dispatcher->listen(UserRegistered::class, [new UserListener(), 'onUserRegistered']);
        $dispatcher->listen(PaymentCompleted::class, [new PaymentListener(), 'onPaymentCompleted']);
        // ... more listeners
    }
}
```

### QueueServiceProvider

```php
class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(QueueService::class, function ($app) {
            $config = $app->get('config')->get('queue');
            
            $driver = match ($config['driver']) {
                'database' => new DatabaseQueue($app->get(Database::class)),
                'redis' => new RedisQueue($config['redis']),
                default => new DatabaseQueue($app->get(Database::class))
            };
            
            return new QueueService($driver);
        });
    }
}
```

### AuthServiceProvider

```php
class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(AuthService::class, function ($app) {
            return new AuthService(
                $app->get(Database::class),
                $app->get(CacheInterface::class),
                $app->get(EventDispatcher::class)
            );
        });
        
        $this->singleton(PermissionService::class, function ($app) {
            return new PermissionService($app->get(Database::class));
        });
        
        $this->singleton(Gate::class, function ($app) {
            return new Gate($app->get(PermissionService::class));
        });
    }
}
```

### LicenseServiceProvider

```php
class LicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(LicenseService::class, function ($app) {
            return new LicenseService(
                $app->get(LicenseRepository::class),
                $app->get(EventDispatcher::class),
                $app->get(QueueService::class)
            );
        });
        
        $this->singleton(LicenseRepository::class, function ($app) {
            return new LicenseRepository($app->get(Database::class));
        });
        
        $this->singleton(LicenseGenerator::class, function ($app) {
            return new LicenseGenerator();
        });
    }
}
```

### PaymentServiceProvider

```php
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->get(PaymentRepository::class),
                $app->get(GatewayFactory::class),
                $app->get(EventDispatcher::class),
                $app->get(QueueService::class)
            );
        });
        
        $this->singleton(PaymentRepository::class, function ($app) {
            return new PaymentRepository($app->get(Database::class));
        });
        
        $this->singleton(GatewayFactory::class, function ($app) {
            return new GatewayFactory($app->get('config')->get('gateways'));
        });
    }
}
```

### NotificationServiceProvider

```php
class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->get(NotificationRepository::class),
                $app->get(QueueService::class)
            );
        });
        
        $this->singleton(NotificationRepository::class, function ($app) {
            return new NotificationRepository($app->get(Database::class));
        });
    }
}
```

### AnalyticsServiceProvider

```php
class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService(
                $app->get(Database::class),
                $app->get(CacheInterface::class),
                $app->get(EventDispatcher::class)
            );
        });
    }
}
```

### PluginServiceProvider

```php
class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->get(PluginLoader::class),
                $app->get(PluginRegistry::class),
                $app->get(EventDispatcher::class),
                $app->get(Database::class)
            );
        });
        
        $this->singleton(PluginLoader::class, function ($app) {
            return new PluginLoader(
                $app->get(PluginRegistry::class),
                $app->get(EventDispatcher::class)
            );
        });
        
        $this->singleton(PluginRegistry::class, function ($app) {
            return new PluginRegistry($app->get(Database::class));
        });
    }
}
```

### FeatureFlagServiceProvider

```php
class FeatureFlagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(FeatureFlagService::class, function ($app) {
            return new FeatureFlagService(
                $app->get(Database::class),
                $app->get(CacheInterface::class),
                $app->get(EventDispatcher::class)
            );
        });
    }
}
```

### ConfigurationServiceProvider

```php
class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(ConfigurationService::class, function ($app) {
            return new ConfigurationService(
                $app->get(Database::class),
                $app->get(CacheInterface::class),
                $app->get(EventDispatcher::class)
            );
        });
    }
}
```

### HealthMonitorServiceProvider

```php
class HealthMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(HealthMonitorService::class, function ($app) {
            $monitor = new HealthMonitorService($app->get(EventDispatcher::class));
            
            // Register health checks
            $monitor->register(new DatabaseHealthCheck($app->get(Database::class)));
            $monitor->register(new QueueHealthCheck($app->get(QueueService::class)));
            $monitor->register(new CacheHealthCheck($app->get(CacheInterface::class)));
            $monitor->register(new StorageHealthCheck($app->get(Filesystem::class)));
            $monitor->register(new PHPHealthCheck());
            $monitor->register(new SSLHealthCheck($app->get('config')->get('app.url')));
            $monitor->register(new APIStatusHealthCheck($app->get(Database::class)));
            $monitor->register(new CronJobHealthCheck($app->get(Database::class)));
            
            return $monitor;
        });
    }
}
```

---

## Application Bootstrap

### Application Class

```php
class Application
{
    private Container $container;
    private array $serviceProviders = [];
    private array $bootedProviders = [];
    
    public function __construct()
    {
        $this->container = new Container();
        
        // Register core services
        $this->registerCoreServices();
    }
    
    private function registerCoreServices(): void
    {
        $this->container->singleton(Container::class, fn() => $this->container);
        
        $this->container->singleton(Config::class, function () {
            return new Config();
        });
        
        $this->container->singleton(Filesystem::class, function () {
            return new Filesystem();
        });
    }
    
    public function register(ServiceProviderInterface $provider): void
    {
        $this->serviceProviders[] = $provider;
        
        $provider->register();
    }
    
    public function boot(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (!in_array($provider, $this->bootedProviders)) {
                $this->bootProvider($provider);
                $this->bootedProviders[] = $provider;
            }
        }
    }
    
    private function bootProvider(ServiceProviderInterface $provider): void
    {
        $provider->boot();
    }
    
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    public function make(string $abstract)
    {
        return $this->container->make($abstract);
    }
    
    public static function getInstance(): self
    {
        static $instance;
        
        if ($instance === null) {
            $instance = new self();
        }
        
        return $instance;
    }
}
```

### Bootstrap File

```php
// bootstrap.php

require_once __DIR__ . '/vendor/autoload.php';

$app = Application::getInstance();

// Register service providers
$app->register(new DatabaseServiceProvider());
$app->register(new CacheServiceProvider());
$app->register(new EventDispatcherServiceProvider());
$app->register(new QueueServiceProvider());
$app->register(new AuthServiceProvider());
$app->register(new LicenseServiceProvider());
$app->register(new PaymentServiceProvider());
$app->register(new NotificationServiceProvider());
$app->register(new AnalyticsServiceProvider());
$app->register(new PluginServiceProvider());
$app->register(new FeatureFlagServiceProvider());
$app->register(new ConfigurationServiceProvider());
$app->register(new HealthMonitorServiceProvider());

// Boot all providers
$app->boot();

return $app;
```

---

## Dependency Injection Patterns

### Constructor Injection

```php
class UserService
{
    private UserRepository $userRepository;
    private EventDispatcher $events;
    
    public function __construct(
        UserRepository $userRepository,
        EventDispatcher $events
    ) {
        $this->userRepository = $userRepository;
        $this->events = $events;
    }
    
    public function createUser(array $data): User
    {
        $user = $this->userRepository->create($data);
        
        $this->events->dispatch(new UserRegistered($user->id, $user->email, $user->username));
        
        return $user;
    }
}
```

### Setter Injection

```php
class PaymentProcessor
{
    private ?LoggerInterface $logger = null;
    
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

### Interface Binding

```php
// Bind interface to implementation
$container->bind(LoggerInterface::class, FileLogger::class);

// Bind interface to closure
$container->bind(LoggerInterface::class, function ($container) {
    $config = $container->get(Config::class)->get('logging');
    
    return match ($config['driver']) {
        'file' => new FileLogger($config['path']),
        'syslog' => new SyslogLogger(),
        default => new NullLogger()
    };
});
```

### Contextual Binding

```php
// Different implementations based on context
$container->bind(LoggerInterface::class, function ($container) {
    if ($container->get('config')->get('app.debug')) {
        return new DebugLogger();
    }
    
    return new ProductionLogger();
});
```

### Factory Binding

```php
$container->factory(DatabaseConnection::class, function ($container, $abstract) {
    $config = $container->get('config')->get('database');
    
    return new DatabaseConnection($config);
});
```

---

## Repository Pattern

### Repository Interface

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function all(): array;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function where(string $column, $value): UserRepositoryInterface;
}
```

### Repository Implementation

```php
class UserRepository implements UserRepositoryInterface
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function find(int $id): ?User
    {
        $result = $this->db->query("SELECT * FROM users WHERE id = ?", [$id]);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapToEntity($row);
    }
    
    public function findByEmail(string $email): ?User
    {
        $result = $this->db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapToEntity($row);
    }
    
    public function all(): array
    {
        $result = $this->db->query("SELECT * FROM users ORDER BY id DESC");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'mapToEntity'], $rows);
    }
    
    public function create(array $data): User
    {
        $this->db->insert('users', $data);
        
        return $this->find($this->db->lastInsertId());
    }
    
    public function update(int $id, array $data): bool
    {
        return $this->db->update('users', $data, ['id' => $id]) > 0;
    }
    
    public function delete(int $id): bool
    {
        return $this->db->delete('users', ['id' => $id]) > 0;
    }
    
    public function where(string $column, $value): UserRepositoryInterface
    {
        // Implementation for query builder pattern
        return $this;
    }
    
    private function mapToEntity(array $row): User
    {
        $user = new User();
        $user->id = $row['id'];
        $user->username = $row['username'];
        $user->email = $row['email'];
        $user->roleId = $row['role_id'];
        
        return $user;
    }
}
```

---

## Service Layer Pattern

### Service Interface

```php
interface UserServiceInterface
{
    public function registerUser(array $data): User;
    public function loginUser(string $email, string $password): array;
    public function updateUser(int $userId, array $data): User;
    public function deleteUser(int $userId): bool;
}
```

### Service Implementation

```php
class UserService implements UserServiceInterface
{
    private UserRepositoryInterface $userRepository;
    private PasswordHasher $passwordHasher;
    private EventDispatcher $events;
    
    public function __construct(
        UserRepositoryInterface $userRepository,
        PasswordHasher $passwordHasher,
        EventDispatcher $events
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->events = $events;
    }
    
    public function registerUser(array $data): User
    {
        // Hash password
        $data['password_hash'] = $this->passwordHasher->hash($data['password']);
        unset($data['password']);
        
        // Create user
        $user = $this->userRepository->create($data);
        
        // Dispatch event
        $this->events->dispatch(new UserRegistered($user->id, $user->email, $user->username));
        
        return $user;
    }
    
    public function loginUser(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            throw new AuthenticationException('Invalid credentials');
        }
        
        if (!$this->passwordHasher->verify($password, $user->passwordHash)) {
            throw new AuthenticationException('Invalid credentials');
        }
        
        // Dispatch event
        $this->events->dispatch(new UserLoggedIn($user->id, 'session', $_SERVER['REMOTE_ADDR'] ?? null));
        
        return [
            'user' => $user,
            'token' => $this->generateToken($user)
        ];
    }
    
    public function updateUser(int $userId, array $data): User
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            throw new UserNotFoundException($userId);
        }
        
        $this->userRepository->update($userId, $data);
        
        $this->events->dispatch(new UserUpdated($userId, $data, null));
        
        return $this->userRepository->find($userId);
    }
    
    public function deleteUser(int $userId): bool
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            throw new UserNotFoundException($userId);
        }
        
        $result = $this->userRepository->delete($userId);
        
        if ($result) {
            $this->events->dispatch(new UserDeleted($userId, null, 'User deletion'));
        }
        
        return $result;
    }
    
    private function generateToken(User $user): string
    {
        // Generate JWT token
        return JWT::encode([
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600
        ], getenv('JWT_SECRET'), 'HS256');
    }
}
```

---

## Testing with DI

### Unit Testing

```php
class UserServiceTest
{
    private UserService $userService;
    private UserRepositoryInterface $userRepositoryMock;
    
    public function setUp(): void
    {
        $container = new Container();
        
        // Mock repository
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        
        $container->instance(UserRepositoryInterface::class, $this->userRepositoryMock);
        $container->instance(PasswordHasher::class, new PasswordHasher());
        $container->instance(EventDispatcher::class, new EventDispatcher());
        
        $this->userService = $container->make(UserService::class);
    }
    
    public function testRegisterUser(): void
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn(new User());
        
        $user = $this->userService->registerUser($data);
        
        $this->assertInstanceOf(User::class, $user);
    }
}
```

---

## Summary

The Service Container provides:
- **Automatic dependency resolution** through constructor injection
- **Interface binding** for loose coupling
- **Singleton management** for shared instances
- **Factory support** for complex instantiation
- **Service providers** for organized registration
- **Repository pattern** for data access
- **Service layer** for business logic
- **Testing support** through dependency injection
- **Contextual binding** for different implementations

Core service providers:
- Database, Cache, Event Dispatcher, Queue
- Auth, License, Payment, Notification
- Analytics, Plugin, Feature Flag, Configuration, Health Monitor
