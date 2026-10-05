CREATE TABLE IF NOT EXISTS admin_remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_admin_remember_token (token_hash),
    KEY idx_admin_remember_admin_expiry (admin_id, expires_at),
    CONSTRAINT fk_admin_remember_admin FOREIGN KEY (admin_id) REFERENCES utenti(id) ON DELETE CASCADE
);
