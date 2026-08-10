-- Statistiche QR privacy-first: nessun evento individuale, nessun identificativo visitatore.
CREATE TABLE IF NOT EXISTS qr_scan_daily (
    scan_date DATE NOT NULL,
    qr_code VARCHAR(96) NOT NULL,
    scans INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (scan_date, qr_code),
    KEY idx_qr_scan_daily_code_date (qr_code, scan_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
