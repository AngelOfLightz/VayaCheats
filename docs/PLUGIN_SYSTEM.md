# VayaCheats V3.1 - Plugin System Architecture

## Overview

The Plugin System enables extending VayaCheats functionality without modifying the core application. Plugins are self-contained modules that can be installed, enabled, disabled, and removed independently.

---

## Plugin Architecture

### Core Components

```
Plugin System
├── Plugin Loader
├── Plugin Registry
├── Plugin Manager
├── Plugin Permissions
├── Plugin Events
├── Plugin Lifecycle
└── Plugin Dependency Resolver
```

---

## Plugin Manifest

### Manifest Structure

Every plugin must include a `plugin.json` manifest:

```json
{
    "name": "discord-integration",
    "version": "1.0.0",
    "description": "Discord bot integration for VayaCheats",
    "author": "VayaCheats Team",
    "license": "MIT",
    "minimum_core_version": "3.1.0",
    "maximum_core_version": "4.0.0",
    "dependencies": {
        "notification-system": ">=1.0.0",
        "api": ">=2.0.0"
    },
    "permissions": [
        "plugin.users.read",
        "plugin.notifications.send",
        "plugin.api.access"
    ],
    "events": {
        "subscribes": [
            "user.registered",
            "payment.completed",
            "license.generated"
        ],
        "publishes": [
            "discord.message.sent",
            "discord.user.linked"
        ]
    },
    "routes": [
        {
            "path": "/discord/link",
            "method": "GET",
            "handler": "DiscordController@link"
        },
        {
            "path": "/discord/webhook",
            "method": "POST",
            "handler": "DiscordController@webhook"
        }
    ],
    "commands": [
        {
            "name": "discord:sync",
            "description": "Sync Discord roles",
            "handler": "DiscordCommand@sync"
        }
    ],
    "settings": [
        {
            "key": "discord_bot_token",
            "type": "encrypted",
            "required": true,
            "description": "Discord bot token"
        },
        {
            "key": "discord_guild_id",
            "type": "string",
            "required": true,
            "description": "Discord server ID"
        },
        {
            "key": "discord_role_mapping",
            "type": "json",
            "required": false,
            "description": "Role mapping configuration"
        }
    ],
    "menu_items": [
        {
            "label": "Discord Integration",
            "icon": "fab fa-discord",
            "route": "admin.discord.index",
            "permission": "plugin.discord.manage"
        }
    ],
    "database": {
        "migrations": [
            "database/migrations/2024_01_01_000001_create_discord_table.php"
        ],
        "seeds": [
            "database/seeds/DiscordSeeder.php"
        ]
    },
    "assets": {
        "css": [
            "assets/css/discord.css"
        ],
        "js": [
            "assets/js/discord.js"
        ]
    },
    "api_endpoints": [
        {
            "path": "/api/v1/discord/sync",
            "method": "POST",
            "authentication": "required",
            "rate_limit": "60/min"
        }
    ]
}
```

---

## Plugin Loader

### Loader Interface

```php
interface PluginLoaderInterface
{
    public function load(string $pluginPath): Plugin;
    public function unload(string $pluginName): void;
    public function reload(string $pluginName): Plugin;
    public function isLoaded(string $pluginName): bool;
    public function getLoadedPlugins(): array;
}
```

### Plugin Loader Implementation

```php
class PluginLoader implements PluginLoaderInterface
{
    private array $loadedPlugins = [];
    private PluginRegistry $registry;
    private EventDispatcher $events;
    
    public function __construct(
        PluginRegistry $registry,
        EventDispatcher $events
    ) {
        $this->registry = $registry;
        $this->events = $events;
    }
    
    public function load(string $pluginPath): Plugin
    {
        // Load manifest
        $manifest = $this->loadManifest($pluginPath);
        
        // Validate manifest
        $this->validateManifest($manifest);
        
        // Check dependencies
        $this->resolveDependencies($manifest->dependencies);
        
        // Check version compatibility
        $this->checkVersionCompatibility($manifest);
        
        // Check permissions
        $this->checkPermissions($manifest->permissions);
        
        // Load plugin class
        $pluginClass = $this->loadPluginClass($pluginPath, $manifest);
        
        // Instantiate plugin
        $plugin = new $pluginClass($manifest);
        
        // Register plugin
        $this->registry->register($plugin);
        
        // Load routes
        $this->loadRoutes($plugin);
        
        // Load commands
        $this->loadCommands($plugin);
        
        // Load assets
        $this->loadAssets($plugin);
        
        // Run migrations
        $this->runMigrations($plugin);
        
        // Boot plugin
        $plugin->boot();
        
        // Register event listeners
        $this->registerEventListeners($plugin);
        
        // Store loaded plugin
        $this->loadedPlugins[$plugin->getName()] = $plugin;
        
        // Dispatch plugin loaded event
        $this->events->dispatch(new PluginLoaded($plugin->getName()));
        
        return $plugin;
    }
    
    public function unload(string $pluginName): void
    {
        if (!isset($this->loadedPlugins[$pluginName])) {
            throw new PluginNotLoadedException($pluginName);
        }
        
        $plugin = $this->loadedPlugins[$pluginName];
        
        // Shutdown plugin
        $plugin->shutdown();
        
        // Unregister event listeners
        $this->unregisterEventListeners($plugin);
        
        // Unregister routes
        $this->unregisterRoutes($plugin);
        
        // Unregister commands
        $this->unregisterCommands($plugin);
        
        // Unregister from registry
        $this->registry->unregister($pluginName);
        
        // Remove from loaded plugins
        unset($this->loadedPlugins[$pluginName]);
        
        // Dispatch plugin unloaded event
        $this->events->dispatch(new PluginUnloaded($pluginName));
    }
    
    private function loadManifest(string $pluginPath): PluginManifest
    {
        $manifestPath = $pluginPath . '/plugin.json';
        
        if (!file_exists($manifestPath)) {
            throw new PluginManifestNotFoundException($manifestPath);
        }
        
        $manifestData = json_decode(file_get_contents($manifestPath), true);
        
        return new PluginManifest($manifestData);
    }
    
    private function validateManifest(PluginManifest $manifest): void
    {
        $validator = new PluginManifestValidator();
        
        if (!$validator->validate($manifest)) {
            throw new PluginManifestValidationException(
                $validator->getErrors()
            );
        }
    }
    
    private function resolveDependencies(array $dependencies): void
    {
        $resolver = new PluginDependencyResolver($this->registry);
        
        if (!$resolver->resolve($dependencies)) {
            throw new PluginDependencyException(
                $resolver->getMissingDependencies()
            );
        }
    }
    
    private function checkVersionCompatibility(PluginManifest $manifest): void
    {
        $coreVersion = $this->getCoreVersion();
        
        if (!$this->isVersionCompatible(
            $coreVersion,
            $manifest->minimum_core_version,
            $manifest->maximum_core_version
        )) {
            throw new PluginVersionIncompatibilityException(
                $coreVersion,
                $manifest->minimum_core_version,
                $manifest->maximum_core_version
            );
        }
    }
    
    private function checkPermissions(array $permissions): void
    {
        $permissionChecker = new PluginPermissionChecker();
        
        foreach ($permissions as $permission) {
            if (!$permissionChecker->isValid($permission)) {
                throw new PluginPermissionException($permission);
            }
        }
    }
}
```

---

## Plugin Registry

### Registry Interface

```php
interface PluginRegistryInterface
{
    public function register(Plugin $plugin): void;
    public function unregister(string $pluginName): void;
    public function get(string $pluginName): ?Plugin;
    public function has(string $pluginName): bool;
    public function all(): array;
    public function enabled(): array;
    public function disabled(): array;
    public function enable(string $pluginName): void;
    public function disable(string $pluginName): void;
}
```

### Plugin Registry Implementation

```php
class PluginRegistry implements PluginRegistryInterface
{
    private array $plugins = [];
    private array $enabledPlugins = [];
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->loadEnabledPlugins();
    }
    
    public function register(Plugin $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }
    
    public function unregister(string $pluginName): void
    {
        unset($this->plugins[$pluginName]);
        unset($this->enabledPlugins[$pluginName]);
    }
    
    public function get(string $pluginName): ?Plugin
    {
        return $this->plugins[$pluginName] ?? null;
    }
    
    public function has(string $pluginName): bool
    {
        return isset($this->plugins[$pluginName]);
    }
    
    public function all(): array
    {
        return $this->plugins;
    }
    
    public function enabled(): array
    {
        return array_filter(
            $this->plugins,
            fn($plugin) => $this->isEnabled($plugin->getName())
        );
    }
    
    public function disabled(): array
    {
        return array_filter(
            $this->plugins,
            fn($plugin) => !$this->isEnabled($plugin->getName())
        );
    }
    
    public function enable(string $pluginName): void
    {
        if (!$this->has($pluginName)) {
            throw new PluginNotFoundException($pluginName);
        }
        
        $plugin = $this->get($pluginName);
        
        // Enable in database
        $this->db->query("
            UPDATE plugins 
            SET status = 'enabled', enabled_at = NOW() 
            WHERE name = ?
        ", [$pluginName]);
        
        $this->enabledPlugins[$pluginName] = true;
        
        // Dispatch event
        $this->events->dispatch(new PluginEnabled($pluginName));
    }
    
    public function disable(string $pluginName): void
    {
        if (!$this->has($pluginName)) {
            throw new PluginNotFoundException($pluginName);
        }
        
        // Disable in database
        $this->db->query("
            UPDATE plugins 
            SET status = 'disabled', disabled_at = NOW() 
            WHERE name = ?
        ", [$pluginName]);
        
        unset($this->enabledPlugins[$pluginName]);
        
        // Dispatch event
        $this->events->dispatch(new PluginDisabled($pluginName));
    }
    
    private function loadEnabledPlugins(): void
    {
        $result = $this->db->query("
            SELECT name FROM plugins WHERE status = 'enabled'
        ");
        
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->enabledPlugins[$row['name']] = true;
        }
    }
    
    private function isEnabled(string $pluginName): bool
    {
        return isset($this->enabledPlugins[$pluginName]);
    }
}
```

---

## Plugin Permissions

### Permission Categories

```php
class PluginPermissions
{
    // User data access
    const USERS_READ = 'plugin.users.read';
    const USERS_WRITE = 'plugin.users.write';
    const USERS_DELETE = 'plugin.users.delete';
    
    // Notification access
    const NOTIFICATIONS_READ = 'plugin.notifications.read';
    const NOTIFICATIONS_SEND = 'plugin.notifications.send';
    const NOTIFICATIONS_DELETE = 'plugin.notifications.delete';
    
    // API access
    const API_ACCESS = 'plugin.api.access';
    const API_WRITE = 'plugin.api.write';
    const API_ADMIN = 'plugin.api.admin';
    
    // Database access
    const DATABASE_READ = 'plugin.database.read';
    const DATABASE_WRITE = 'plugin.database.write';
    
    // File system access
    const FILES_READ = 'plugin.files.read';
    const FILES_WRITE = 'plugin.files.write';
    const FILES_DELETE = 'plugin.files.delete';
    
    // System access
    const SYSTEM_CONFIG_READ = 'plugin.system.config.read';
    const SYSTEM_CONFIG_WRITE = 'plugin.system.config.write';
    const SYSTEM_LOGS_READ = 'plugin.system.logs.read';
    
    // Payment access
    const PAYMENTS_READ = 'plugin.payments.read';
    const PAYMENTS_WRITE = 'plugin.payments.write';
    
    // License access
    const LICENSES_READ = 'plugin.licenses.read';
    const LICENSES_WRITE = 'plugin.licenses.write';
}
```

### Permission Checker

```php
class PluginPermissionChecker
{
    private PermissionService $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    public function isValid(string $permission): bool
    {
        $validPermissions = PluginPermissions::getAll();
        return in_array($permission, $validPermissions);
    }
    
    public function can(string $pluginName, string $permission): bool
    {
        // Check if plugin has this permission
        $plugin = $this->registry->get($pluginName);
        
        if (!$plugin) {
            return false;
        }
        
        return in_array($permission, $plugin->getManifest()->permissions);
    }
}
```

---

## Plugin Events

### Core Events Plugins Can Subscribe To

```php
class CorePluginEvents
{
    // User events
    const USER_REGISTERED = 'user.registered';
    const USER_LOGGED_IN = 'user.logged_in';
    const USER_LOGGED_OUT = 'user.logged_out';
    const USER_UPDATED = 'user.updated';
    const USER_DELETED = 'user.deleted';
    
    // Payment events
    const PAYMENT_INITIATED = 'payment.initiated';
    const PAYMENT_COMPLETED = 'payment.completed';
    const PAYMENT_FAILED = 'payment.failed';
    const PAYMENT_REFUNDED = 'payment.refunded';
    
    // License events
    const LICENSE_GENERATED = 'license.generated';
    const LICENSE_ACTIVATED = 'license.activated';
    const LICENSE_EXPIRED = 'license.expired';
    const LICENSE_REVOKED = 'license.revoked';
    
    // Product events
    const PRODUCT_CREATED = 'product.created';
    const PRODUCT_UPDATED = 'product.updated';
    const PRODUCT_DELETED = 'product.deleted';
    const PRODUCT_DOWNLOADED = 'product.downloaded';
    
    // Comment events
    const COMMENT_POSTED = 'comment.posted';
    const COMMENT_DELETED = 'comment.deleted';
    
    // Notification events
    const NOTIFICATION_SENT = 'notification.sent';
    const NOTIFICATION_READ = 'notification.read';
    
    // System events
    const SYSTEM_MAINTENANCE_MODE = 'system.maintenance_mode';
    const SYSTEM_BACKUP_COMPLETED = 'system.backup_completed';
}
```

### Plugin Event Listener

```php
class PluginEventListener
{
    private Plugin $plugin;
    private EventDispatcher $events;
    
    public function __construct(Plugin $plugin, EventDispatcher $events)
    {
        $this->plugin = $plugin;
        $this->events = $events;
    }
    
    public function register(): void
    {
        $manifest = $this->plugin->getManifest();
        
        foreach ($manifest->events->subscribes as $event) {
            $this->events->listen($event, [$this->plugin, 'handle' . $this->eventToMethodName($event)]);
        }
    }
    
    public function unregister(): void
    {
        $manifest = $this->plugin->getManifest();
        
        foreach ($manifest->events->subscribes as $event) {
            $this->events->forget($event, [$this->plugin, 'handle' . $this->eventToMethodName($event)]);
        }
    }
    
    private function eventToMethodName(string $event): string
    {
        return str_replace('.', '', ucwords($event, '.'));
    }
}
```

---

## Plugin Lifecycle

### Lifecycle States

```
Installed → Disabled → Enabled → Active
    ↓           ↓          ↓
Uninstalled ← Disabled ← Disabled
```

### Lifecycle Methods

```php
interface PluginInterface
{
    public function boot(): void;
    public function shutdown(): void;
    public function install(): void;
    public function uninstall(): void;
    public function upgrade(string $fromVersion, string $toVersion): void;
    public function activate(): void;
    public function deactivate(): void;
}
```

### Base Plugin Class

```php
abstract class BasePlugin implements PluginInterface
{
    protected PluginManifest $manifest;
    protected Container $container;
    
    public function __construct(PluginManifest $manifest, Container $container)
    {
        $this->manifest = $manifest;
        $this->container = $container;
    }
    
    public function boot(): void
    {
        // Register services
        $this->registerServices();
        
        // Register routes
        $this->registerRoutes();
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Load configuration
        $this->loadConfiguration();
    }
    
    public function shutdown(): void
    {
        // Unregister event listeners
        $this->unregisterEventListeners();
        
        // Unregister routes
        $this->unregisterRoutes();
        
        // Cleanup resources
        $this->cleanup();
    }
    
    public function install(): void
    {
        // Run migrations
        $this->runMigrations();
        
        // Seed database
        $this->seedDatabase();
        
        // Create default settings
        $this->createDefaultSettings();
        
        // Register plugin in database
        $this->registerPlugin();
    }
    
    public function uninstall(): void
    {
        // Rollback migrations
        $this->rollbackMigrations();
        
        // Remove settings
        $this->removeSettings();
        
        // Unregister plugin from database
        $this->unregisterPlugin();
        
        // Cleanup files
        $this->cleanupFiles();
    }
    
    public function upgrade(string $fromVersion, string $toVersion): void
    {
        // Run upgrade migrations
        $this->runUpgradeMigrations($fromVersion, $toVersion);
        
        // Update plugin version in database
        $this->updatePluginVersion($toVersion);
    }
    
    public function activate(): void
    {
        // Plugin-specific activation logic
    }
    
    public function deactivate(): void
    {
        // Plugin-specific deactivation logic
    }
    
    abstract protected function registerServices(): void;
    abstract protected function registerRoutes(): void;
    abstract protected function registerEventListeners(): void;
    
    protected function loadConfiguration(): void
    {
        $configPath = $this->getPluginPath() . '/config/config.php';
        
        if (file_exists($configPath)) {
            $config = require $configPath;
            $this->container->get('config')->merge($config);
        }
    }
    
    protected function runMigrations(): void
    {
        $migrator = $this->container->get('migrator');
        $migrationPath = $this->getPluginPath() . '/database/migrations';
        
        $migrator->run($migrationPath);
    }
    
    protected function rollbackMigrations(): void
    {
        $migrator = $this->container->get('migrator');
        $migrationPath = $this->getPluginPath() . '/database/migrations';
        
        $migrator->rollback($migrationPath);
    }
    
    protected function getPluginPath(): string
    {
        return plugin_path($this->manifest->name);
    }
}
```

---

## Plugin Dependency Resolver

### Dependency Resolution

```php
class PluginDependencyResolver
{
    private PluginRegistry $registry;
    
    public function __construct(PluginRegistry $registry)
    {
        $this->registry = $registry;
    }
    
    public function resolve(array $dependencies): bool
    {
        foreach ($dependencies as $pluginName => $versionConstraint) {
            if (!$this->registry->has($pluginName)) {
                return false;
            }
            
            $plugin = $this->registry->get($pluginName);
            
            if (!$this->satisfiesVersionConstraint(
                $plugin->getVersion(),
                $versionConstraint
            )) {
                return false;
            }
        }
        
        return true;
    }
    
    public function getMissingDependencies(array $dependencies): array
    {
        $missing = [];
        
        foreach ($dependencies as $pluginName => $versionConstraint) {
            if (!$this->registry->has($pluginName)) {
                $missing[$pluginName] = $versionConstraint;
            }
        }
        
        return $missing;
    }
    
    private function satisfiesVersionConstraint(
        string $version,
        string $constraint
    ): bool {
        // Implement semantic versioning constraint checking
        return true;
    }
}
```

---

## Plugin Manager

### Manager Interface

```php
interface PluginManagerInterface
{
    public function install(string $pluginPath): Plugin;
    public function uninstall(string $pluginName): void;
    public function enable(string $pluginName): void;
    public function disable(string $pluginName): void;
    public function update(string $pluginName, string $newVersion): void;
    public function getPluginInfo(string $pluginName): array;
    public function getAllPlugins(): array;
}
```

### Plugin Manager Implementation

```php
class PluginManager implements PluginManagerInterface
{
    private PluginLoader $loader;
    private PluginRegistry $registry;
    private EventDispatcher $events;
    private Database $db;
    
    public function __construct(
        PluginLoader $loader,
        PluginRegistry $registry,
        EventDispatcher $events,
        Database $db
    ) {
        $this->loader = $loader;
        $this->registry = $registry;
        $this->events = $events;
        $this->db = $db;
    }
    
    public function install(string $pluginPath): Plugin
    {
        // Load plugin
        $plugin = $this->loader->load($pluginPath);
        
        // Install plugin
        $plugin->install();
        
        // Register in database
        $this->registerPluginInDatabase($plugin);
        
        // Dispatch event
        $this->events->dispatch(new PluginInstalled($plugin->getName()));
        
        return $plugin;
    }
    
    public function uninstall(string $pluginName): void
    {
        $plugin = $this->registry->get($pluginName);
        
        if (!$plugin) {
            throw new PluginNotFoundException($pluginName);
        }
        
        // Disable plugin
        if ($this->registry->isEnabled($pluginName)) {
            $this->disable($pluginName);
        }
        
        // Unload plugin
        $this->loader->unload($pluginName);
        
        // Uninstall plugin
        $plugin->uninstall();
        
        // Remove from database
        $this->removePluginFromDatabase($pluginName);
        
        // Dispatch event
        $this->events->dispatch(new PluginUninstalled($pluginName));
    }
    
    public function enable(string $pluginName): void
    {
        $this->registry->enable($pluginName);
        
        $plugin = $this->registry->get($pluginName);
        $plugin->activate();
    }
    
    public function disable(string $pluginName): void
    {
        $plugin = $this->registry->get($pluginName);
        $plugin->deactivate();
        
        $this->registry->disable($pluginName);
    }
    
    public function update(string $pluginName, string $newVersion): void
    {
        $plugin = $this->registry->get($pluginName);
        $oldVersion = $plugin->getVersion();
        
        // Unload plugin
        $this->loader->unload($pluginName);
        
        // Load new version
        $pluginPath = plugin_path($pluginName);
        $plugin = $this->loader->load($pluginPath);
        
        // Upgrade plugin
        $plugin->upgrade($oldVersion, $newVersion);
        
        // Update version in database
        $this->updatePluginVersionInDatabase($pluginName, $newVersion);
        
        // Dispatch event
        $this->events->dispatch(new PluginUpdated($pluginName, $oldVersion, $newVersion));
    }
    
    public function getPluginInfo(string $pluginName): array
    {
        $plugin = $this->registry->get($pluginName);
        
        if (!$plugin) {
            throw new PluginNotFoundException($pluginName);
        }
        
        return [
            'name' => $plugin->getName(),
            'version' => $plugin->getVersion(),
            'description' => $plugin->getDescription(),
            'author' => $plugin->getAuthor(),
            'status' => $this->registry->isEnabled($pluginName) ? 'enabled' : 'disabled',
            'dependencies' => $plugin->getManifest()->dependencies,
            'permissions' => $plugin->getManifest()->permissions,
            'installed_at' => $this->getPluginInstallDate($pluginName),
        ];
    }
    
    public function getAllPlugins(): array
    {
        $plugins = [];
        
        foreach ($this->registry->all() as $plugin) {
            $plugins[] = $this->getPluginInfo($plugin->getName());
        }
        
        return $plugins;
    }
}
```

---

## Plugin Communication with Core

### Service Injection

Plugins can inject core services through the container:

```php
class DiscordPlugin extends BasePlugin
{
    private NotificationService $notificationService;
    private UserService $userService;
    private APIService $apiService;
    
    protected function registerServices(): void
    {
        // Inject core services
        $this->notificationService = $this->container->get(NotificationService::class);
        $this->userService = $this->container->get(UserService::class);
        $this->apiService = $this->container->get(APIService::class);
        
        // Register plugin services
        $this->container->singleton(DiscordService::class, fn() => new DiscordService(
            $this->getSetting('discord_bot_token'),
            $this->getSetting('discord_guild_id')
        ));
    }
}
```

### Event Subscription

Plugins subscribe to core events:

```php
class DiscordPlugin extends BasePlugin
{
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $payment = $this->paymentService->getPayment($event->paymentId);
        $user = $this->userService->getUser($payment->user_id);
        
        // Send Discord notification
        $this->discordService->sendMessage(
            "Payment completed: {$user->username} - {$payment->amount} USD"
        );
    }
}
```

### Event Publishing

Plugins can publish their own events:

```php
class DiscordPlugin extends BasePlugin
{
    public function linkUser(int $userId, string $discordId): void
    {
        // Link user to Discord
        $this->discordService->linkUser($userId, $discordId);
        
        // Publish event
        $this->events->dispatch(new DiscordUserLinked($userId, $discordId));
    }
}
```

### API Access

Plugins can access core APIs:

```php
class DiscordPlugin extends BasePlugin
{
    public function syncRoles(): void
    {
        // Get users from core API
        $users = $this->apiService->get('/api/v1/users');
        
        // Sync with Discord
        foreach ($users as $user) {
            $this->discordService->syncRole($user);
        }
    }
}
```

---

## Plugin Database Schema

### Plugins Table

```sql
CREATE TABLE plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    author VARCHAR(100),
    status ENUM('installed', 'enabled', 'disabled', 'uninstalled') DEFAULT 'installed',
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enabled_at TIMESTAMP NULL,
    disabled_at TIMESTAMP NULL,
    uninstalled_at TIMESTAMP NULL,
    minimum_core_version VARCHAR(20),
    maximum_core_version VARCHAR(20),
    dependencies JSON,
    permissions JSON,
    settings JSON,
    
    INDEX idx_name (name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Plugin Settings Table

```sql
CREATE TABLE plugin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_name VARCHAR(100) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'encrypted') DEFAULT 'string',
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_setting (plugin_name, setting_key),
    INDEX idx_plugin_name (plugin_name),
    
    FOREIGN KEY (plugin_name) REFERENCES plugins(name) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Plugin Security

### Security Measures

1. **Manifest Validation**: All plugins must have valid manifests
2. **Permission System**: Plugins request specific permissions
3. **Sandboxing**: Plugins run in isolated environment (future)
4. **Code Signing**: Plugins can be signed (future)
5. **Dependency Validation**: All dependencies verified before loading
6. **Version Compatibility**: Core version constraints enforced
7. **Audit Logging**: All plugin actions logged

### Permission Enforcement

```php
class PluginSecurityGuard
{
    private PermissionService $permissionService;
    private AuditService $auditService;
    
    public function checkPermission(string $pluginName, string $permission): bool
    {
        // Check if plugin has permission
        if (!$this->pluginHasPermission($pluginName, $permission)) {
            $this->auditService->log('security', [
                'action' => 'plugin_permission_denied',
                'plugin_name' => $pluginName,
                'permission' => $permission,
                'severity' => 'warning'
            ]);
            
            return false;
        }
        
        return true;
    }
    
    public function enforcePermission(string $pluginName, string $permission): void
    {
        if (!$this->checkPermission($pluginName, $permission)) {
            throw new PluginPermissionException($permission);
        }
    }
}
```

---

## Example Plugins

### Discord Integration Plugin

```php
class DiscordPlugin extends BasePlugin
{
    private DiscordService $discordService;
    
    protected function registerServices(): void
    {
        $this->container->singleton(DiscordService::class, fn() => new DiscordService(
            $this->getSetting('discord_bot_token'),
            $this->getSetting('discord_guild_id')
        ));
        
        $this->discordService = $this->container->get(DiscordService::class);
    }
    
    protected function registerEventListeners(): void
    {
        $this->events->listen(PaymentCompleted::class, [$this, 'handlePaymentCompleted']);
        $this->events->listen(UserRegistered::class, [$this, 'handleUserRegistered']);
    }
    
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $payment = $this->paymentService->getPayment($event->paymentId);
        $user = $this->userService->getUser($payment->user_id);
        
        $this->discordService->sendMessage(
            "💰 Payment: {$user->username} - {$payment->amount} USD"
        );
    }
    
    public function handleUserRegistered(UserRegistered $event): void
    {
        $this->discordService->sendMessage(
            "👋 New user: {$event->username}"
        );
    }
}
```

### Launcher Integration Plugin

```php
class LauncherPlugin extends BasePlugin
{
    private LauncherService $launcherService;
    
    protected function registerServices(): void
    {
        $this->container->singleton(LauncherService::class, fn() => new LauncherService(
            $this->getSetting('launcher_api_key'),
            $this->getSetting('launcher_version')
        ));
        
        $this->launcherService = $this->container->get(LauncherService::class);
    }
    
    protected function registerRoutes(): void
    {
        Route::post('/api/v1/launcher/validate', 'LauncherController@validate');
        Route::post('/api/v1/launcher/download', 'LauncherController@download');
        Route::get('/api/v1/launcher/version', 'LauncherController@version');
    }
}
```

---

## Plugin Marketplace (Future)

### Marketplace Features

- Plugin discovery
- Plugin installation (one-click)
- Plugin updates
- Plugin reviews
- Plugin ratings
- Plugin documentation
- Plugin support

### Marketplace API

```
GET /api/v1/marketplace/plugins
Response: { plugins: [] }

GET /api/v1/marketplace/plugins/{id}
Response: { plugin: {} }

POST /api/v1/marketplace/plugins/{id}/install
Response: { success }

POST /api/v1/marketplace/plugins/{id}/purchase
Response: { success }
```

---

## Summary

The Plugin System provides:
- **Modular extensibility** without core modifications
- **Plugin manifest** for metadata and configuration
- **Plugin loader** for dynamic loading/unloading
- **Plugin registry** for plugin management
- **Permission system** for security
- **Event system** for core communication
- **Lifecycle management** (install/uninstall/enable/disable)
- **Dependency resolution** for plugin compatibility
- **Version compatibility** checking
- **Security measures** (permissions, validation, audit logging)

Plugins enable extending VayaCheats with:
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
