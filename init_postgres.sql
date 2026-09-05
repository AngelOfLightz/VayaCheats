-- Basic tables for PostgreSQL
CREATE TABLE IF NOT EXISTS kullanicilar (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    role VARCHAR(50) DEFAULT 'user',
    hwid VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS hileler (
    id SERIAL PRIMARY KEY,
    ad VARCHAR(255) NOT NULL,
    aciklama TEXT,
    fiyat DECIMAL(10, 2) DEFAULT 0.00,
    durum BOOLEAN DEFAULT TRUE,
    kategori VARCHAR(100),
    dosya_yolu VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
);

-- Insert test user
INSERT INTO kullanicilar (username, password, role) VALUES 
('test_user', 'test_password', 'admin')
ON CONFLICT (username) DO NOTHING;
