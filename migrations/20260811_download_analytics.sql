CREATE TABLE IF NOT EXISTS download_daily (
    download_date DATE NOT NULL,
    download_type ENUM('gpx', 'map_pdf') NOT NULL,
    resource_key VARCHAR(255) NOT NULL,
    downloads INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (download_date, download_type, resource_key),
    KEY idx_download_daily_type_date (download_type, download_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS download_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    download_type ENUM('gpx', 'map_pdf') NOT NULL,
    resource_key VARCHAR(255) NOT NULL,
    downloaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_agent VARCHAR(512) NOT NULL DEFAULT '',
    device_type ENUM('mobile', 'tablet', 'desktop', 'unknown') NOT NULL DEFAULT 'unknown',
    PRIMARY KEY (id),
    KEY idx_download_log_type_date (download_type, downloaded_at),
    KEY idx_download_log_resource_date (resource_key, downloaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
