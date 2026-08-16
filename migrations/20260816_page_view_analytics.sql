CREATE TABLE IF NOT EXISTS page_view_daily (
    view_date DATE NOT NULL,
    page_key VARCHAR(255) NOT NULL,
    language CHAR(2) NOT NULL DEFAULT 'it',
    views INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (view_date, page_key, language),
    KEY idx_page_view_daily_page_date (page_key, view_date),
    KEY idx_page_view_daily_language_date (language, view_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
