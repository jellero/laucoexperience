-- Web Push per il backoffice: chiavi VAPID e dispositivi autorizzati.
-- Migrazione additiva e idempotente.

CREATE TABLE IF NOT EXISTS `admin_push_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `vapid_public_key` VARCHAR(120) NOT NULL,
  `vapid_private_key` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_push_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `endpoint` TEXT NOT NULL,
  `endpoint_hash` CHAR(64) NOT NULL,
  `p256dh` VARCHAR(160) NOT NULL,
  `auth` VARCHAR(80) NOT NULL,
  `platform` VARCHAR(32) NOT NULL DEFAULT 'web',
  `user_agent` VARCHAR(500) NULL,
  `failure_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `last_success_at` DATETIME NULL,
  `last_failure_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_push_endpoint` (`endpoint_hash`),
  KEY `idx_admin_push_admin` (`admin_id`,`updated_at`),
  CONSTRAINT `fk_admin_push_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
