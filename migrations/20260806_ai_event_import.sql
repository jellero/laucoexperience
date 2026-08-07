-- Estensioni additive per il CMS esistente. Nessuna tabella o colonna corrente viene rimossa.

CREATE TABLE IF NOT EXISTS `ai_content_drafts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `target_language` CHAR(2) NOT NULL DEFAULT 'it',
  `mode` VARCHAR(32) NOT NULL DEFAULT 'full',
  `source_snapshot` JSON NOT NULL,
  `generated_payload` JSON NOT NULL,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'openai',
  `model` VARCHAR(120) NULL,
  `response_id` VARCHAR(190) NULL,
  `request_id` VARCHAR(190) NULL,
  `status` ENUM('review','approved','rejected','applied') NOT NULL DEFAULT 'review',
  `created_by` INT UNSIGNED NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME NULL,
  `applied_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_entity` (`entity_type`,`entity_id`,`created_at`),
  KEY `idx_ai_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_translations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `language` CHAR(2) NOT NULL,
  `title` VARCHAR(255) NULL,
  `subtitle` VARCHAR(255) NULL,
  `excerpt` TEXT NULL,
  `body` MEDIUMTEXT NULL,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` VARCHAR(320) NULL,
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `source_draft_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `published_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_content_translation` (`entity_type`,`entity_id`,`language`),
  KEY `idx_translation_status` (`language`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_import_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_key` VARCHAR(80) NOT NULL,
  `status` ENUM('running','completed','failed') NOT NULL,
  `candidate_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `error_message` TEXT NULL,
  `started_by` INT UNSIGNED NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_event_import_runs` (`source_key`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_import_candidates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id` BIGINT UNSIGNED NULL,
  `source_key` VARCHAR(80) NOT NULL,
  `external_id` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `start_at_raw` VARCHAR(190) NULL,
  `end_at_raw` VARCHAR(190) NULL,
  `location_name` VARCHAR(255) NULL,
  `locality` VARCHAR(190) NULL,
  `organizer` VARCHAR(255) NULL,
  `source_url` VARCHAR(1000) NOT NULL,
  `image_url` VARCHAR(1000) NULL,
  `raw_payload` JSON NOT NULL,
  `review_status` ENUM('pending','approved','rejected','published') NOT NULL DEFAULT 'pending',
  `published_event_id` INT UNSIGNED NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event_candidate` (`source_key`,`external_id`),
  KEY `idx_event_candidate_status` (`review_status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `eventi_fonti` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evento_id` INT UNSIGNED NOT NULL,
  `source_key` VARCHAR(80) NOT NULL,
  `source_url` VARCHAR(1000) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_evento_fonte` (`evento_id`,`source_key`),
  KEY `idx_evento_fonte_evento` (`evento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NULL,
  `action_name` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(32) NULL,
  `entity_id` INT UNSIGNED NULL,
  `details_json` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`,`created_at`),
  KEY `idx_audit_admin` (`admin_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
