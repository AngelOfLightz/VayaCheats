-- API Token Table for Launcher Authentication
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_used TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
);

-- Index for faster token lookup
CREATE INDEX idx_token ON api_tokens(token);
CREATE INDEX idx_user_id ON api_tokens(user_id);
