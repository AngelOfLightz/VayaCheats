# VayaCheats V3.1 - Role & Permission Matrix

## Role Hierarchy

```
Owner (Level 100)
    ↓
Admin (Level 50)
    ↓
Moderator (Level 25)
    ↓
User (Level 10)
```

**Important**: Roles control ADMINISTRATION only. Product access is controlled by LICENSES.

---

## Role Definitions

### Owner
- **Level**: 100
- **Description**: Full system access, can manage everything
- **Capabilities**: All permissions plus owner-exclusive tools

### Admin
- **Level**: 50
- **Description**: Administrative access, can manage users and content
- **Capabilities**: Most administrative permissions, cannot manage owners

### Moderator
- **Level**: 25
- **Description**: Content moderation, community management
- **Capabilities**: Comment moderation, basic user management

### User
- **Level**: 10
- **Description**: Standard user access
- **Capabilities**: View content, download products (with license), comment

---

## Permission Categories

### Authentication
- `auth.login` - Login to system
- `auth.logout` - Logout from system
- `auth.register` - Register new account
- `auth.reset_password` - Reset password
- `auth.change_password` - Change own password

### User Management
- `user.view` - View user profiles
- `user.view_own` - View own profile
- `user.edit` - Edit user profiles
- `user.edit_own` - Edit own profile
- `user.delete` - Delete users
- `user.ban` - Ban users
- `user.unban` - Unban users
- `user.mute` - Mute users
- `user.unmute` - Unmute users
- `user.reset_password` - Reset user passwords
- `user.change_role` - Change user roles
- `user.view_all` - View all users list
- `user.view_admins` - View admin accounts
- `user.view_owners` - View owner accounts

### Role Management
- `role.view` - View roles
- `role.create` - Create roles
- `role.edit` - Edit roles
- `role.delete` - Delete roles
- `role.assign` - Assign roles to users
- `role.revoke` - Revoke roles from users

### License Management
- `license.view` - View licenses
- `license.view_own` - View own licenses
- `license.generate` - Generate licenses
- `license.revoke` - Revoke licenses
- `license.activate` - Activate licenses
- `license.deactivate` - Deactivate licenses
- `license.extend` - Extend licenses
- `license.view_all` - View all licenses
- `license.view_history` - View license history

### Membership Management
- `membership.view` - View memberships
- `membership.view_own` - View own membership
- `membership.grant` - Grant memberships
- `membership.revoke` - Revoke memberships
- `membership.extend` - Extend memberships
- `membership.upgrade` - Upgrade memberships
- `membership.downgrade` - Downgrade memberships
- `membership.view_all` - View all memberships

### Product Management
- `product.view` - View products
- `product.view_all` - View all products
- `product.create` - Create products
- `product.edit` - Edit products
- `product.delete` - Delete products
- `product.publish` - Publish products
- `product.unpublish` - Unpublish products
- `product.feature` - Feature products
- `product.unfeature` - Unfeature products
- `product.manage_versions` - Manage product versions
- `product.download` - Download products

### Download Management
- `download.view` - View downloads
- `download.view_own` - View own downloads
- `download.view_all` - View all downloads
- `download.block` - Block downloads
- `download.unblock` - Unblock downloads

### Comment Management
- `comment.view` - View comments
- `comment.create` - Create comments
- `comment.edit_own` - Edit own comments
- `comment.delete_own` - Delete own comments
- `comment.delete_any` - Delete any comments
- `comment.pin` - Pin comments
- `comment.unpin` - Unpin comments
- `comment.moderate` - Moderate comments

### Payment Management
- `payment.view` - View payments
- `payment.view_own` - View own payments
- `payment.view_all` - View all payments
- `payment.refund` - Refund payments
- `payment.manage_gateways` - Manage payment gateways
- `payment.view_analytics` - View payment analytics

### Audit Management
- `audit.view` - View audit logs
- `audit.view_user` - View user audit logs
- `audit.view_admin` - View admin audit logs
- `audit.view_owner` - View owner audit logs
- `audit.view_security` - View security logs
- `audit.view_all` - View all audit logs
- `audit.export` - Export audit logs

### Notification Management
- `notification.view` - View notifications
- `notification.view_own` - View own notifications
- `notification.send` - Send notifications
- `notification.broadcast` - Send broadcast notifications
- `notification.manage` - Manage notification templates

### Security Management
- `security.view` - View security logs
- `security.view_all` - View all security logs
- `security.block_ip` - Block IP addresses
- `security.unblock_ip` - Unblock IP addresses
- `security.manage_rate_limits` - Manage rate limits
- `security.view_attempts` - View failed attempts

### Analytics Management
- `analytics.view` - View analytics
- `analytics.view_dashboard` - View dashboard
- `analytics.view_statistics` - View statistics
- `analytics.view_revenue` - View revenue
- `analytics.view_users` - View user analytics
- `analytics.view_downloads` - View download analytics

### System Management
- `system.view` - View system status
- `system.manage_settings` - Manage system settings
- `system.manage_mail_templates` - Manage mail templates
- `system.maintenance_mode` - Toggle maintenance mode
- `system.clear_cache` - Clear cache
- `system.run_migrations` - Run database migrations
- `system.view_queue` - View queue status
- `system.manage_queue` - Manage queue jobs
- `system.backup_database` - Backup database
- `system.restore_database` - Restore database

### Owner Exclusive
- `owner.manage_owners` - Add/remove owners
- `owner.view_owner_panel` - Access owner panel
- `owner.manage_owner_settings` - Manage owner-specific settings
- `owner.system_critical` - Critical system operations

### Plugin Management (V3.1)
- `plugin.view` - View plugins
- `plugin.install` - Install plugins
- `plugin.uninstall` - Uninstall plugins
- `plugin.enable` - Enable plugins
- `plugin.disable` - Disable plugins
- `plugin.configure` - Configure plugin settings
- `plugin.view_settings` - View plugin settings
- `plugin.manage_permissions` - Manage plugin permissions

### API Management (V3.1)
- `api.view` - View API documentation
- `api.view_keys` - View API keys
- `api.create_key` - Create API keys
- `api.revoke_key` - Revoke API keys
- `api.manage_keys_own` - Manage own API keys
- `api.view_logs` - View API logs
- `api.view_analytics` - View API analytics

### Feature Flag Management (V3.1)
- `feature_flag.view` - View feature flags
- `feature_flag.create` - Create feature flags
- `feature_flag.edit` - Edit feature flags
- `feature_flag.delete` - Delete feature flags
- `feature_flag.toggle` - Toggle feature flags
- `feature_flag.view_analytics` - View flag usage analytics

### Configuration Management (V3.1)
- `config.view` - View configuration
- `config.edit` - Edit configuration
- `config.view_sensitive` - View sensitive configuration
- `config.edit_sensitive` - Edit sensitive configuration
- `config.import` - Import configuration
- `config.export` - Export configuration
- `config.rollback` - Rollback configuration
- `config.view_history` - View configuration history

### Health Monitor Management (V3.1)
- `health.view` - View health status
- `health.view_logs` - View health check logs
- `health.view_alerts` - View health alerts
- `health.acknowledge_alert` - Acknowledge health alerts
- `health.manage_alerts` - Manage alert rules

### Launcher Management (V3.1)
- `launcher.view` - View launcher sessions
- `launcher.view_downloads` - View launcher downloads
- `launcher.view_reports` - View launcher reports
- `launcher.manage_versions` - Manage launcher versions
- `launcher.manage_updates` - Manage launcher updates

### Analytics Management (V3.1)
- `analytics.view` - View analytics
- `analytics.view_dashboard` - View dashboard
- `analytics.view_statistics` - View statistics
- `analytics.view_revenue` - View revenue
- `analytics.view_users` - View user analytics
- `analytics.view_downloads` - View download analytics
- `analytics.manage_dashboards` - Manage dashboard configurations
- `analytics.export` - Export analytics data

---

## Permission Matrix

| Permission | Owner | Admin | Moderator | User |
|------------|-------|-------|-----------|------|
| **Authentication** | | | | |
| auth.login | ✅ | ✅ | ✅ | ✅ |
| auth.logout | ✅ | ✅ | ✅ | ✅ |
| auth.register | ✅ | ✅ | ✅ | ✅ |
| auth.reset_password | ✅ | ✅ | ✅ | ✅ |
| auth.change_password | ✅ | ✅ | ✅ | ✅ |
| **User Management** | | | | |
| user.view | ✅ | ✅ | ✅ | ✅ |
| user.view_own | ✅ | ✅ | ✅ | ✅ |
| user.edit | ✅ | ✅ | ❌ | ❌ |
| user.edit_own | ✅ | ✅ | ✅ | ✅ |
| user.delete | ✅ | ❌ | ❌ | ❌ |
| user.ban | ✅ | ✅ | ❌ | ❌ |
| user.unban | ✅ | ✅ | ❌ | ❌ |
| user.mute | ✅ | ✅ | ✅ | ❌ |
| user.unmute | ✅ | ✅ | ✅ | ❌ |
| user.reset_password | ✅ | ✅ | ❌ | ❌ |
| user.change_role | ✅ | ❌ | ❌ | ❌ |
| user.view_all | ✅ | ✅ | ✅ | ❌ |
| user.view_admins | ✅ | ❌ | ❌ | ❌ |
| user.view_owners | ✅ | ❌ | ❌ | ❌ |
| **Role Management** | | | | |
| role.view | ✅ | ✅ | ❌ | ❌ |
| role.create | ✅ | ❌ | ❌ | ❌ |
| role.edit | ✅ | ❌ | ❌ | ❌ |
| role.delete | ✅ | ❌ | ❌ | ❌ |
| role.assign | ✅ | ❌ | ❌ | ❌ |
| role.revoke | ✅ | ❌ | ❌ | ❌ |
| **License Management** | | | | |
| license.view | ✅ | ✅ | ❌ | ❌ |
| license.view_own | ✅ | ✅ | ✅ | ✅ |
| license.generate | ✅ | ✅ | ❌ | ❌ |
| license.revoke | ✅ | ✅ | ❌ | ❌ (*)
| license.activate | ✅ | ✅ | ✅ | ✅ |
| license.deactivate | ✅ | ❌ | ❌ | ❌ |
| license.extend | ✅ | ✅ | ❌ | ❌ |
| license.view_all | ✅ | ✅ | ❌ | ❌ |
| license.view_history | ✅ | ✅ | ❌ | ❌ |
| **Membership Management** | | | | |
| membership.view | ✅ | ✅ | ❌ | ❌ |
| membership.view_own | ✅ | ✅ | ✅ | ✅ |
| membership.grant | ✅ | ✅ | ❌ | ❌ (*)
| membership.revoke | ✅ | ✅ | ❌ | ❌ (*)
| membership.extend | ✅ | ✅ | ❌ | ❌ (*)
| membership.upgrade | ✅ | ✅ | ❌ | ❌ (*)
| membership.downgrade | ✅ | ✅ | ❌ | ❌ (*)
| membership.view_all | ✅ | ✅ | ❌ | ❌ |
| **Product Management** | | | | |
| product.view | ✅ | ✅ | ✅ | ✅ |
| product.view_all | ✅ | ✅ | ✅ | ✅ |
| product.create | ✅ | ✅ | ❌ | ❌ |
| product.edit | ✅ | ✅ | ❌ | ❌ |
| product.delete | ✅ | ❌ | ❌ | ❌ |
| product.publish | ✅ | ✅ | ❌ | ❌ |
| product.unpublish | ✅ | ✅ | ❌ | ❌ |
| product.feature | ✅ | ✅ | ❌ | ❌ |
| product.unfeature | ✅ | ✅ | ❌ | ❌ |
| product.manage_versions | ✅ | ✅ | ❌ | ❌ |
| product.download | ✅ | ✅ | ✅ | ✅ |
| **Download Management** | | | | |
| download.view | ✅ | ✅ | ❌ | ❌ |
| download.view_own | ✅ | ✅ | ✅ | ✅ |
| download.view_all | ✅ | ✅ | ❌ | ❌ |
| download.block | ✅ | ✅ | ❌ | ❌ |
| download.unblock | ✅ | ✅ | ❌ | ❌ |
| **Comment Management** | | | | |
| comment.view | ✅ | ✅ | ✅ | ✅ |
| comment.create | ✅ | ✅ | ✅ | ✅ |
| comment.edit_own | ✅ | ✅ | ✅ | ✅ |
| comment.delete_own | ✅ | ✅ | ✅ | ✅ |
| comment.delete_any | ✅ | ✅ | ✅ | ❌ |
| comment.pin | ✅ | ✅ | ✅ | ❌ |
| comment.unpin | ✅ | ✅ | ✅ | ❌ |
| comment.moderate | ✅ | ✅ | ✅ | ❌ |
| **Payment Management** | | | | |
| payment.view | ✅ | ✅ | ❌ | ❌ |
| payment.view_own | ✅ | ✅ | ✅ | ✅ |
| payment.view_all | ✅ | ✅ | ❌ | ❌ |
| payment.refund | ✅ | ✅ | ❌ | ❌ |
| payment.manage_gateways | ✅ | ❌ | ❌ | ❌ |
| payment.view_analytics | ✅ | ✅ | ❌ | ❌ |
| **Audit Management** | | | | |
| audit.view | ✅ | ✅ | ❌ | ❌ |
| audit.view_user | ✅ | ✅ | ❌ | ❌ |
| audit.view_admin | ✅ | ❌ | ❌ | ❌ |
| audit.view_owner | ✅ | ❌ | ❌ | ❌ |
| audit.view_security | ✅ | ✅ | ❌ | ❌ |
| audit.view_all | ✅ | ❌ | ❌ | ❌ |
| audit.export | ✅ | ❌ | ❌ | ❌ |
| **Notification Management** | | | | |
| notification.view | ✅ | ✅ | ✅ | ✅ |
| notification.view_own | ✅ | ✅ | ✅ | ✅ |
| notification.send | ✅ | ✅ | ❌ | ❌ |
| notification.broadcast | ✅ | ✅ | ❌ | ❌ |
| notification.manage | ✅ | ❌ | ❌ | ❌ |
| **Security Management** | | | | |
| security.view | ✅ | ✅ | ❌ | ❌ |
| security.view_all | ✅ | ❌ | ❌ | ❌ |
| security.block_ip | ✅ | ❌ | ❌ | ❌ |
| security.unblock_ip | ✅ | ❌ | ❌ | ❌ |
| security.manage_rate_limits | ✅ | ❌ | ❌ | ❌ |
| security.view_attempts | ✅ | ✅ | ❌ | ❌ |
| **Analytics Management** | | | | |
| analytics.view | ✅ | ✅ | ❌ | ❌ |
| analytics.view_dashboard | ✅ | ✅ | ❌ | ❌ |
| analytics.view_statistics | ✅ | ✅ | ❌ | ❌ |
| analytics.view_revenue | ✅ | ✅ | ❌ | ❌ |
| analytics.view_users | ✅ | ✅ | ❌ | ❌ |
| analytics.view_downloads | ✅ | ✅ | ❌ | ❌ |
| **System Management** | | | | |
| system.view | ✅ | ✅ | ❌ | ❌ |
| system.manage_settings | ✅ | ❌ | ❌ | ❌ |
| system.manage_mail_templates | ✅ | ❌ | ❌ | ❌ |
| system.maintenance_mode | ✅ | ❌ | ❌ | ❌ |
| system.clear_cache | ✅ | ✅ | ❌ | ❌ |
| system.run_migrations | ✅ | ❌ | ❌ | ❌ |
| system.view_queue | ✅ | ✅ | ❌ | ❌ |
| system.manage_queue | ✅ | ❌ | ❌ | ❌ |
| system.backup_database | ✅ | ❌ | ❌ | ❌ |
| system.restore_database | ✅ | ❌ | ❌ | ❌ |
| **Owner Exclusive** | | | | |
| owner.manage_owners | ✅ | ❌ | ❌ | ❌ |
| owner.view_owner_panel | ✅ | ❌ | ❌ | ❌ |
| owner.manage_owner_settings | ✅ | ❌ | ❌ | ❌ |
| owner.system_critical | ✅ | ❌ | ❌ | ❌ |
| **Plugin Management (V3.1)** | | | | |
| plugin.view | ✅ | ✅ | ❌ | ❌ |
| plugin.install | ✅ | ❌ | ❌ | ❌ |
| plugin.uninstall | ✅ | ❌ | ❌ | ❌ |
| plugin.enable | ✅ | ❌ | ❌ | ❌ |
| plugin.disable | ✅ | ❌ | ❌ | ❌ |
| plugin.configure | ✅ | ❌ | ❌ | ❌ |
| plugin.view_settings | ✅ | ✅ | ❌ | ❌ |
| plugin.manage_permissions | ✅ | ❌ | ❌ | ❌ |
| **API Management (V3.1)** | | | | |
| api.view | ✅ | ✅ | ✅ | ✅ |
| api.view_keys | ✅ | ✅ | ❌ | ❌ |
| api.create_key | ✅ | ✅ | ✅ | ✅ |
| api.revoke_key | ✅ | ✅ | ❌ | ❌ |
| api.manage_keys_own | ✅ | ✅ | ✅ | ✅ |
| api.view_logs | ✅ | ✅ | ❌ | ❌ |
| api.view_analytics | ✅ | ✅ | ❌ | ❌ |
| **Feature Flag Management (V3.1)** | | | | |
| feature_flag.view | ✅ | ✅ | ❌ | ❌ |
| feature_flag.create | ✅ | ❌ | ❌ | ❌ |
| feature_flag.edit | ✅ | ❌ | ❌ | ❌ |
| feature_flag.delete | ✅ | ❌ | ❌ | ❌ |
| feature_flag.toggle | ✅ | ❌ | ❌ | ❌ |
| feature_flag.view_analytics | ✅ | ✅ | ❌ | ❌ |
| **Configuration Management (V3.1)** | | | | |
| config.view | ✅ | ✅ | ❌ | ❌ |
| config.edit | ✅ | ❌ | ❌ | ❌ |
| config.view_sensitive | ✅ | ❌ | ❌ | ❌ |
| config.edit_sensitive | ✅ | ❌ | ❌ | ❌ |
| config.import | ✅ | ❌ | ❌ | ❌ |
| config.export | ✅ | ✅ | ❌ | ❌ |
| config.rollback | ✅ | ❌ | ❌ | ❌ |
| config.view_history | ✅ | ✅ | ❌ | ❌ |
| **Health Monitor Management (V3.1)** | | | | |
| health.view | ✅ | ✅ | ❌ | ❌ |
| health.view_logs | ✅ | ✅ | ❌ | ❌ |
| health.view_alerts | ✅ | ✅ | ❌ | ❌ |
| health.acknowledge_alert | ✅ | ✅ | ❌ | ❌ |
| health.manage_alerts | ✅ | ❌ | ❌ | ❌ |
| **Launcher Management (V3.1)** | | | | |
| launcher.view | ✅ | ✅ | ❌ | ❌ |
| launcher.view_downloads | ✅ | ✅ | ❌ | ❌ |
| launcher.view_reports | ✅ | ✅ | ❌ | ❌ |
| launcher.manage_versions | ✅ | ❌ | ❌ | ❌ |
| launcher.manage_updates | ✅ | ❌ | ❌ | ❌ |
| **Analytics Management (V3.1)** | | | | |
| analytics.view | ✅ | ✅ | ❌ | ❌ |
| analytics.view_dashboard | ✅ | ✅ | ❌ | ❌ |
| analytics.view_statistics | ✅ | ✅ | ❌ | ❌ |
| analytics.view_revenue | ✅ | ✅ | ❌ | ❌ |
| analytics.view_users | ✅ | ✅ | ❌ | ❌ |
| analytics.view_downloads | ✅ | ✅ | ❌ | ❌ |
| analytics.manage_dashboards | ✅ | ❌ | ❌ | ❌ |
| analytics.export | ✅ | ❌ | ❌ | ❌ |

**Notes:**
- (*) Admin cannot modify owner licenses/memberships
- Admin cannot ban/mute other admins
- Admin cannot change their own role
- Admin cannot grant Ultimate/Lifetime licenses
- Admin cannot grant Ultimate/Lifetime memberships

---

## Permission Implementation

### Gate Pattern

```php
class Gate
{
    private PermissionService $permissionService;
    
    public function allows(int $userId, string $permission): bool
    {
        return $this->permissionService->hasPermission($userId, $permission);
    }
    
    public function denies(int $userId, string $permission): bool
    {
        return !$this->allows($userId, $permission);
    }
    
    public function authorize(int $userId, string $permission): void
    {
        if (!$this->allows($userId, $permission)) {
            throw new AuthorizationException("Permission denied: {$permission}");
        }
    }
}
```

### Policy Pattern

```php
class UserPolicy
{
    public function view(User $actor, User $target): bool
    {
        // Users can view their own profile
        if ($actor->id === $target->id) {
            return true;
        }
        
        // Admins can view all users
        if ($actor->role->level >= 50) {
            return true;
        }
        
        // Moderators can view users
        if ($actor->role->level >= 25) {
            return true;
        }
        
        return false;
    }
    
    public function ban(User $actor, User $target): bool
    {
        // Only admins and owners can ban
        if ($actor->role->level < 50) {
            return false;
        }
        
        // Cannot ban owners
        if ($target->role->level >= 100) {
            return false;
        }
        
        // Admins cannot ban other admins
        if ($actor->role->level === 50 && $target->role->level >= 50) {
            return false;
        }
        
        return true;
    }
}
```

### Middleware Implementation

```php
class PermissionMiddleware
{
    private Gate $gate;
    
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }
    
    public function handle(string $permission): callable
    {
        return function (Request $request) use ($permission) {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId || !$this->gate->allows($userId, $permission)) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                exit;
            }
            
            return $request;
        };
    }
}
```

---

## Server-Side Validation

Every endpoint MUST validate permissions server-side:

```php
// Example: Ban User Endpoint
public function banUser(Request $request): Response
{
    // 1. Authenticate user
    $actor = $this->authService->getCurrentUser();
    
    // 2. Validate permission
    $this->gate->authorize($actor->id, 'user.ban');
    
    // 3. Get target user
    $target = $this->userRepository->find($request->input('user_id'));
    
    // 4. Apply policy
    if (!$this->userPolicy->ban($actor, $target)) {
        throw new AuthorizationException('Cannot ban this user');
    }
    
    // 5. Execute action
    $this->banService->ban($target->id, $request->input('reason'));
    
    // 6. Log action
    $this->auditService->log('user_banned', $actor->id, $target->id);
    
    return response()->json(['success' => true]);
}
```

---

## Permission Caching

To optimize performance, permissions are cached:

```php
class PermissionCache
{
    private CacheInterface $cache;
    private int $ttl = 3600; // 1 hour
    
    public function get(int $userId, string $permission): ?bool
    {
        $key = "permissions:{$userId}:{$permission}";
        return $this->cache->get($key);
    }
    
    public function set(int $userId, string $permission, bool $value): void
    {
        $key = "permissions:{$userId}:{$permission}";
        $this->cache->set($key, $value, $this->ttl);
    }
    
    public function invalidate(int $userId): void
    {
        // Invalidate all permissions for a user
        $pattern = "permissions:{$userId}:*";
        $this->cache->deletePattern($pattern);
    }
}
```

Cache is invalidated when:
- User role changes
- User permissions change
- Role permissions change

---

## Summary

The permission system ensures:
- **Role-based access control** with clear hierarchy
- **Fine-grained permissions** for specific actions
- **Policy-based authorization** for complex rules
- **Server-side validation** on every endpoint
- **Performance optimization** through caching
- **Audit logging** for all permission checks

Roles control ADMINISTRATION only.
Product access is controlled by LICENSES.
