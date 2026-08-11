CREATE TABLE IF NOT EXISTS qr_scan_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    qr_code VARCHAR(80) NOT NULL,
    scanned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(512) NOT NULL DEFAULT '',
    device_type VARCHAR(32) NOT NULL DEFAULT 'unknown',
    PRIMARY KEY (id),
    KEY idx_qr_scan_log_code_date (qr_code, scanned_at),
    KEY idx_qr_scan_log_date (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
