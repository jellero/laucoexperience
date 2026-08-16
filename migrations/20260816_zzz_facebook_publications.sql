CREATE TABLE IF NOT EXISTS facebook_publications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(24) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    content_hash CHAR(64) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(2048) NOT NULL,
    facebook_post_id VARCHAR(190) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    error_message TEXT NULL,
    response_json MEDIUMTEXT NULL,
    created_by INT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_facebook_entity_content (entity_type, entity_id, content_hash),
    KEY idx_facebook_entity_latest (entity_type, entity_id, created_at),
    KEY idx_facebook_status (status, updated_at),
    CONSTRAINT fk_facebook_publications_user
        FOREIGN KEY (created_by) REFERENCES utenti (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
