CREATE TABLE IF NOT EXISTS share_action_daily (
    action_date DATE NOT NULL,
    page_key VARCHAR(255) NOT NULL,
    language CHAR(2) NOT NULL DEFAULT 'it',
    channel VARCHAR(24) NOT NULL,
    actions INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (action_date, page_key, language, channel),
    KEY idx_share_action_channel_date (channel, action_date),
    KEY idx_share_action_page_date (page_key, action_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
