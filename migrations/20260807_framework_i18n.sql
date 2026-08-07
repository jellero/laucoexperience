-- Fondazione multilingua per il framework Slim e preview AI a quattro lingue.
-- Migrazione esclusivamente additiva, compatibile con MariaDB 11.4.

CREATE TABLE IF NOT EXISTS `ai_generation_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `mode` VARCHAR(32) NOT NULL DEFAULT 'full',
  `source_snapshot` JSON NOT NULL,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'openai',
  `model` VARCHAR(120) NULL,
  `response_id` VARCHAR(190) NULL,
  `request_id` VARCHAR(190) NULL,
  `status` ENUM('review','approved','rejected','applied','partial') NOT NULL DEFAULT 'review',
  `created_by` INT UNSIGNED NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME NULL,
  `applied_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_batch_entity` (`entity_type`,`entity_id`,`created_at`),
  KEY `idx_ai_batch_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_generation_batch_drafts` (
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `draft_id` BIGINT UNSIGNED NOT NULL,
  `language` CHAR(2) NOT NULL,
  PRIMARY KEY (`batch_id`,`language`),
  UNIQUE KEY `uniq_ai_batch_draft` (`draft_id`),
  CONSTRAINT `fk_ai_batch_drafts_batch` FOREIGN KEY (`batch_id`) REFERENCES `ai_generation_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ai_batch_drafts_draft` FOREIGN KEY (`draft_id`) REFERENCES `ai_content_drafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `site_text_translation_drafts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_revision` CHAR(64) NOT NULL,
  `source_catalog` JSON NOT NULL,
  `generated_catalogs` JSON NOT NULL,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'openai',
  `model` VARCHAR(120) NULL,
  `response_id` VARCHAR(190) NULL,
  `request_id` VARCHAR(190) NULL,
  `status` ENUM('review','rejected','applied') NOT NULL DEFAULT 'review',
  `created_by` INT UNSIGNED NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME NULL,
  `applied_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_text_drafts_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
