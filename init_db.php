<?php
// Initialize PostgreSQL database tables
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config.php';
    
    // Create tables with full schema
    $sql = "
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

        -- Add missing columns if they don't exist
        ALTER TABLE kullanicilar ADD COLUMN IF NOT EXISTS bitis_tarihi TIMESTAMP NULL;
        ALTER TABLE kullanicilar ADD COLUMN IF NOT EXISTS profil_color VARCHAR(50) DEFAULT '#3498db';
        ALTER TABLE kullanicilar ADD COLUMN IF NOT EXISTS profil_bg VARCHAR(50) DEFAULT '#2c3e50';

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

        INSERT INTO kullanicilar (username, password, role) VALUES 
        ('test_user', 'test_password', 'admin')
        ON CONFLICT (username) DO NOTHING;
    ";
    
    $db->exec($sql);
    
    echo json_encode(['success' => true, 'message' => 'Database tables created successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
