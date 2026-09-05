# VayaCheats V3.1 - Database Schema Design

## Overview

Normalized database schema designed for 100,000+ users with proper indexing, foreign keys, and separation of concerns. V3.1 adds tables for plugin system, API keys, feature flags, configuration history, health monitoring, launcher support, and analytics.

---

## Core Tables

### users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### roles
```sql
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default roles
INSERT INTO roles (name, slug, level, description) VALUES
('Owner', 'owner', 100, 'Full system access'),
('Admin', 'admin', 50, 'Administrative access'),
('Moderator', 'moderator', 25, 'Content moderation'),
('User', 'user', 10, 'Standard user access');
```

### permissions
```sql
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### role_permissions
```sql
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (role_id, permission_id),
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## License System Tables

### license_plans
```sql
CREATE TABLE license_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL UNIQUE,
    price_monthly DECIMAL(10, 2) DEFAULT 0.00,
    price_yearly DECIMAL(10, 2) DEFAULT 0.00,
    price_lifetime DECIMAL(10, 2) DEFAULT 0.00,
    duration_days INT NULL,
    max_activations INT DEFAULT 1,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_level (level),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default plans
INSERT INTO license_plans (name, slug, level, price_monthly, price_yearly, price_lifetime, duration_days, max_activations, features, is_active) VALUES
('Free', 'free', 0, 0.00, 0.00, 0.00, 365, 1, '[]', TRUE),
('Starter', 'starter', 1, 9.99, 99.99, 0.00, 365, 1, '["3 products", "Standard support"]', TRUE),
('Pro', 'pro', 2, 19.99, 199.99, 0.00, 365, 2, '["10 products", "Priority support"]', TRUE),
('Ultimate', 'ultimate', 3, 39.99, 399.99, 0.00, 365, 3, '["All products", "24/7 support"]', TRUE),
('Lifetime', 'lifetime', 99, 0.00, 0.00, 999.99, NULL, 5, '["All products", "Lifetime access"]', TRUE);
```

### licenses
```sql
CREATE TABLE licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    license_key VARCHAR(64) NOT NULL UNIQUE,
    license_hash VARCHAR(64) NOT NULL,
    plan_id INT NOT NULL,
    payment_id INT NULL,
    status ENUM('active', 'expired', 'revoked', 'suspended') DEFAULT 'active',
    generated_by INT NULL,
    activated_by INT NULL,
    activation_date TIMESTAMP NULL,
    expiration_date TIMESTAMP NULL,
    max_activations INT DEFAULT 1,
    activation_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    last_used_ip VARCHAR(45) NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_license_key (license_key),
    INDEX idx_license_hash (license_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_plan_id (plan_id),
    INDEX idx_status (status),
    INDEX idx_expiration_date (expiration_date),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES license_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### license_activations
```sql
CREATE TABLE license_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    hardware_id VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    status ENUM('active', 'revoked') DEFAULT 'active',
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    revoke_reason TEXT,
    
    INDEX idx_license_id (license_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_status (status),
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### license_history
```sql
CREATE TABLE license_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('generated', 'activated', 'deactivated', 'expired', 'revoked', 'renewed', 'upgraded', 'downgraded') NOT NULL,
    from_plan_id INT NULL,
    to_plan_id INT NULL,
    old_expiration_date TIMESTAMP NULL,
    new_expiration_date TIMESTAMP NULL,
    performed_by INT NULL,
    reason TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_license_id (license_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Membership System Tables

### memberships
```sql
CREATE TABLE memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'inactive', 'cancelled', 'expired', 'suspended') DEFAULT 'active',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    payment_method VARCHAR(50) NULL,
    granted_by INT NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_plan_id (plan_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES license_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### membership_history
```sql
CREATE TABLE membership_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    membership_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('granted', 'upgraded', 'downgraded', 'renewed', 'cancelled', 'expired', 'suspended', 'reactivated', 'manually_granted', 'manually_removed', 'extended', 'shortened') NOT NULL,
    from_plan_id INT NULL,
    to_plan_id INT NULL,
    old_end_date TIMESTAMP NULL,
    new_end_date TIMESTAMP NULL,
    performed_by INT NULL,
    reason TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_membership_id (membership_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Product System Tables

### products
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(255),
    category VARCHAR(50),
    minimum_license_level INT DEFAULT 0,
    supported_version VARCHAR(50),
    current_version VARCHAR(20),
    file_path VARCHAR(255),
    file_size BIGINT,
    file_hash VARCHAR(64),
    status ENUM('undetected', 'detected', 'maintenance', 'deprecated') DEFAULT 'undetected',
    download_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    rating_average DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    favorite_count INT DEFAULT 0,
    features JSON,
    images JSON,
    is_featured BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_minimum_license_level (minimum_license_level),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_public (is_public),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### product_versions
```sql
CREATE TABLE product_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT,
    file_hash VARCHAR(64),
    changelog TEXT,
    release_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_latest BOOLEAN DEFAULT FALSE,
    
    INDEX idx_product_id (product_id),
    INDEX idx_version (version),
    INDEX idx_release_date (release_date),
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Payment System Tables

### payments
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gateway VARCHAR(50) NOT NULL,
    gateway_payment_id VARCHAR(100) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    plan_id INT NULL,
    license_id INT NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_gateway (gateway),
    INDEX idx_gateway_payment_id (gateway_payment_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES license_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### payment_logs
```sql
CREATE TABLE payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id VARCHAR(100) NOT NULL,
    gateway VARCHAR(50) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    request_data JSON,
    response_data JSON,
    error_message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_gateway (gateway),
    INDEX idx_event_type (event_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Download System Tables

### downloads
```sql
CREATE TABLE downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    license_id INT NULL,
    version VARCHAR(20),
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    status ENUM('completed', 'failed', 'cancelled') DEFAULT 'completed',
    file_size BIGINT,
    download_time INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_license_id (license_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Comment System Tables

### comments
```sql
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    parent_id INT NULL,
    content TEXT NOT NULL,
    status ENUM('approved', 'pending', 'rejected', 'deleted') DEFAULT 'approved',
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Moderation System Tables

### bans
```sql
CREATE TABLE bans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    banned_by INT NOT NULL,
    ban_type ENUM('temporary', 'permanent') DEFAULT 'temporary',
    duration_hours INT NULL,
    expires_at TIMESTAMP NULL,
    reason TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_banned_by (banned_by),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### mutes
```sql
CREATE TABLE mutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    muted_by INT NOT NULL,
    mute_type ENUM('temporary', 'permanent') DEFAULT 'temporary',
    duration_minutes INT NULL,
    expires_at TIMESTAMP NULL,
    reason TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_muted_by (muted_by),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (muted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Notification System Tables

### notifications
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('system', 'payments', 'products', 'licenses', 'security', 'community', 'announcements') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Audit Logging Tables

### audit_user_logs
```sql
CREATE TABLE audit_user_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id INT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_actor_id (actor_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_admin_logs
```sql
CREATE TABLE audit_admin_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id INT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_owner_logs
```sql
CREATE TABLE audit_owner_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id INT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_owner_id (owner_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_login_logs
```sql
CREATE TABLE audit_login_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50),
    email VARCHAR(255),
    action ENUM('login_success', 'login_failed', 'logout', 'password_reset', 'password_changed') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_action (action),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_license_logs
```sql
CREATE TABLE audit_license_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    user_id INT NOT NULL,
    actor_id INT NULL,
    action ENUM('generated', 'activated', 'deactivated', 'expired', 'revoked', 'renewed', 'upgraded', 'downgraded') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_license_id (license_id),
    INDEX idx_user_id (user_id),
    INDEX idx_actor_id (actor_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_payment_logs
```sql
CREATE TABLE audit_payment_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('initiated', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL,
    gateway VARCHAR(50),
    amount DECIMAL(10, 2),
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_gateway (gateway),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_download_logs
```sql
CREATE TABLE audit_download_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    download_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    license_id INT NULL,
    action ENUM('started', 'completed', 'failed', 'cancelled') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    file_size BIGINT,
    download_time INT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_download_id (download_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_license_id (license_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (download_id) REFERENCES downloads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_security_logs
```sql
CREATE TABLE audit_security_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    actor_id INT NULL,
    action ENUM('brute_force_detected', 'sql_injection_attempt', 'csrf_failure', 'invalid_token', 'rate_limit_exceeded', 'permission_denied', 'suspicious_activity') NOT NULL,
    severity ENUM('warning', 'error', 'critical') DEFAULT 'warning',
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_actor_id (actor_id),
    INDEX idx_action (action),
    INDEX idx_severity (severity),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_api_logs
```sql
CREATE TABLE audit_api_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    api_key VARCHAR(64),
    endpoint VARCHAR(255),
    method VARCHAR(10),
    action ENUM('request', 'success', 'error', 'unauthorized', 'forbidden', 'rate_limited') NOT NULL,
    status_code INT,
    response_time INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_api_key (api_key),
    INDEX idx_endpoint (endpoint),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_moderation_logs
```sql
CREATE TABLE audit_moderation_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    moderator_id INT NOT NULL,
    target_user_id INT NOT NULL,
    action ENUM('banned', 'unbanned', 'muted', 'unmuted', 'comment_deleted', 'comment_pinned', 'user_warned') NOT NULL,
    target_type VARCHAR(50),
    target_id INT NULL,
    reason TEXT,
    duration INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_moderator_id (moderator_id),
    INDEX idx_target_user_id (target_user_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_mail_logs
```sql
CREATE TABLE audit_mail_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    template VARCHAR(100),
    subject VARCHAR(255),
    action ENUM('queued', 'sent', 'failed', 'bounced') NOT NULL,
    gateway VARCHAR(50),
    error_message TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_template (template),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_product_logs
```sql
CREATE TABLE audit_product_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    actor_id INT NULL,
    action ENUM('created', 'updated', 'deleted', 'status_changed', 'version_added', 'featured', 'unfeatured') NOT NULL,
    changes JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_product_id (product_id),
    INDEX idx_actor_id (actor_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_system_logs
```sql
CREATE TABLE audit_system_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    component VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_component (component),
    INDEX idx_action (action),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### audit_error_logs
```sql
CREATE TABLE audit_error_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    error_type VARCHAR(100),
    error_message TEXT,
    file_path VARCHAR(255),
    line_number INT,
    stack_trace TEXT,
    severity ENUM('warning', 'error', 'critical') DEFAULT 'error',
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_error_type (error_type),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Queue System Tables

### queue_jobs
```sql
CREATE TABLE queue_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(64) NOT NULL UNIQUE,
    type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    priority INT DEFAULT 5,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    timeout INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    worker VARCHAR(100) NULL,
    execution_time INT NULL,
    error_message TEXT,
    
    INDEX idx_job_id (job_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### queue_job_attempts
```sql
CREATE TABLE queue_job_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(64) NOT NULL,
    attempt_number INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    worker VARCHAR(100) NULL,
    execution_time INT NULL,
    error_message TEXT,
    status ENUM('success', 'failed') DEFAULT 'success',
    
    INDEX idx_job_id (job_id),
    INDEX idx_attempt_number (attempt_number),
    
    FOREIGN KEY (job_id) REFERENCES queue_jobs(job_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## User Profile Tables

### user_profiles
```sql
CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    country VARCHAR(2) NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    language VARCHAR(10) DEFAULT 'en',
    theme VARCHAR(20) DEFAULT 'cyberpunk',
    notifications_enabled BOOLEAN DEFAULT TRUE,
    email_notifications_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### user_statistics
```sql
CREATE TABLE user_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_downloads INT DEFAULT 0,
    total_comments INT DEFAULT 0,
    total_likes INT DEFAULT 0,
    total_dislikes INT DEFAULT 0,
    login_count INT DEFAULT 0,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### user_sessions
```sql
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    device_type VARCHAR(50),
    device_name VARCHAR(100),
    browser VARCHAR(50),
    platform VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## System Settings Tables

### system_settings
```sql
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (key),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public),
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### mail_templates
```sql
CREATE TABLE mail_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Entity Relationships

```
users
├── roles (many-to-one)
├── permissions (many-to-many via role_permissions)
├── licenses (one-to-many)
├── memberships (one-to-many)
├── downloads (one-to-many)
├── comments (one-to-many)
├── notifications (one-to-many)
├── sessions (one-to-many)
├── profile (one-to-one)
├── statistics (one-to-one)
└── payments (one-to-many)

licenses
├── user (many-to-one)
├── plan (many-to-one)
├── payment (one-to-one)
├── activations (one-to-many)
└── history (one-to-many)

products
├── versions (one-to-many)
├── downloads (one-to-many)
└── comments (one-to-many)

payments
├── user (many-to-one)
├── plan (many-to-one)
├── license (one-to-one)
└── logs (one-to-many)
```

---

## V3.1 New Tables

### Plugin System Tables

### plugins
```sql
CREATE TABLE plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    author VARCHAR(100),
    license VARCHAR(50),
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

### plugin_settings
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

### API Tables

### api_keys
```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    permissions JSON,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_key_hash (key_hash),
    INDEX idx_status (status),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### api_logs
```sql
CREATE TABLE api_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    api_key_id INT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    status_code INT NOT NULL,
    response_time INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Feature Flag Tables

### feature_flags
```sql
CREATE TABLE feature_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('global', 'user', 'role', 'membership', 'percentage', 'time') NOT NULL DEFAULT 'global',
    enabled BOOLEAN DEFAULT FALSE,
    conditions JSON,
    percentage INT NULL,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (key),
    INDEX idx_type (type),
    INDEX idx_enabled (enabled),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### feature_flag_usage_logs
```sql
CREATE TABLE feature_flag_usage_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    flag_key VARCHAR(100) NOT NULL,
    user_id INT NULL,
    result BOOLEAN NOT NULL,
    context JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_flag_key (flag_key),
    INDEX idx_user_id (user_id),
    INDEX idx_result (result),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (flag_key) REFERENCES feature_flags(key) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Configuration Tables

### system_settings
```sql
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    type ENUM('string', 'integer', 'boolean', 'float', 'json', 'encrypted') DEFAULT 'string',
    category VARCHAR(50),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    is_sensitive BOOLEAN DEFAULT FALSE,
    validation_rule VARCHAR(255),
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_key (key),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public),
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### configuration_history
```sql
CREATE TABLE configuration_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NULL,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_changed_by (changed_by),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (setting_key) REFERENCES system_settings(key) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Health Monitor Tables

### health_check_logs
```sql
CREATE TABLE health_check_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    check_name VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'degraded', 'unhealthy') NOT NULL,
    message TEXT,
    metrics JSON,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_check_name (check_name),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### health_alerts
```sql
CREATE TABLE health_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    check_name VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    message TEXT,
    sent_to JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by INT NULL,
    
    INDEX idx_check_name (check_name),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Launcher Tables

### launcher_sessions
```sql
CREATE TABLE launcher_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hardware_id VARCHAR(64) NOT NULL,
    launcher_version VARCHAR(20),
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_last_seen_at (last_seen_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### launcher_downloads
```sql
CREATE TABLE launcher_downloads (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    license_id INT NULL,
    hardware_id VARCHAR(64) NOT NULL,
    version VARCHAR(20),
    file_size BIGINT,
    file_hash VARCHAR(64),
    status ENUM('started', 'completed', 'failed', 'cancelled') DEFAULT 'started',
    download_time INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### launcher_reports
```sql
CREATE TABLE launcher_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    hardware_id VARCHAR(64),
    launcher_version VARCHAR(20),
    report_type ENUM('error', 'crash', 'performance', 'feature_request') NOT NULL,
    title VARCHAR(255),
    message TEXT,
    stack_trace TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_hardware_id (hardware_id),
    INDEX idx_report_type (report_type),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Analytics Tables

### analytics_events
```sql
CREATE TABLE analytics_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(64) NULL,
    data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### analytics_aggregates
```sql
CREATE TABLE analytics_aggregates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_type ENUM('counter', 'gauge', 'histogram') NOT NULL,
    dimension VARCHAR(50),
    value DECIMAL(20, 4),
    count INT,
    min_value DECIMAL(20, 4),
    max_value DECIMAL(20, 4),
    sum_value DECIMAL(20, 4),
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_metric (metric_name, dimension, period_start, period_end),
    INDEX idx_metric_name (metric_name),
    INDEX idx_period_start (period_start),
    INDEX idx_period_end (period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### dashboard_configurations
```sql
CREATE TABLE dashboard_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    config JSON NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_role (role),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Indexing Strategy

### High-Read Tables
- `users`: username, email, role_id, status
- `products`: slug, category, status, minimum_license_level
- `licenses`: license_key, license_hash, user_id, status
- `downloads`: user_id, product_id, created_at
- `notifications`: user_id, is_read, created_at

### High-Write Tables
- `audit_*_logs`: user_id, action, created_at
- `queue_jobs`: status, priority, created_at
- `downloads`: user_id, created_at

### Time-Series Tables
- All `audit_*_logs`: created_at (for partitioning)
- `downloads`: created_at (for partitioning)
- `queue_jobs`: created_at (for partitioning)

---

## Partitioning Strategy (Future)

### Audit Logs by Date
```sql
-- Partition audit_user_logs by month
ALTER TABLE audit_user_logs 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202601 VALUES LESS THAN (202602),
    PARTITION p202602 VALUES LESS THAN (202603),
    -- ...
    PARTITION p_max VALUES LESS THAN MAXVALUE
);
```

### Downloads by Date
```sql
-- Partition downloads by month
ALTER TABLE downloads 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202601 VALUES LESS THAN (202602),
    PARTITION p202602 VALUES LESS THAN (202603),
    -- ...
    PARTITION p_max VALUES LESS THAN MAXVALUE
);
```

---

## Data Retention Policy

| Table | Retention Period |
|-------|----------------|
| audit_user_logs | 2 years |
| audit_admin_logs | 5 years |
| audit_owner_logs | Permanent |
| audit_login_logs | 1 year |
| audit_security_logs | 5 years |
| audit_error_logs | 1 year |
| queue_jobs | 30 days (completed) |
| queue_job_attempts | 30 days |
| user_sessions | 90 days (inactive) |
| api_logs | 1 year |
| feature_flag_usage_logs | 90 days |
| health_check_logs | 30 days |
| health_alerts | 1 year |
| launcher_sessions | 90 days (inactive) |
| launcher_downloads | 1 year |
| launcher_reports | 1 year |
| analytics_events | 2 years |
| analytics_aggregates | 5 years |

---

## Migration Strategy

1. **Phase 1**: Create new tables alongside existing
2. **Phase 2**: Migrate data from old tables to new structure
3. **Phase 3**: Update application to use new tables
4. **Phase 4**: Drop old tables after verification

---

## Summary

The database schema is designed for:
- **Scalability**: Proper indexing, partitioning ready
- **Performance**: Optimized queries, foreign keys with indexes
- **Security**: Audit logging for all sensitive operations
- **Maintainability**: Clear naming, proper relationships
- **Portability**: Standard SQL, no vendor-specific features

Total tables: 45+
Total audit log tables: 13
Queue tables: 2
User profile tables: 3
System tables: 2
