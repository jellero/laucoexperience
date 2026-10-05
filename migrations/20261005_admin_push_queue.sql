CREATE TABLE IF NOT EXISTS `admin_push_event_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(40) NOT NULL,
  `event_key` VARCHAR(190) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_push_event_key` (`event_key`),
  KEY `idx_admin_push_event_queue_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS `trg_admin_push_contributi_insert`;
CREATE TRIGGER `trg_admin_push_contributi_insert`
AFTER INSERT ON `contributi`
FOR EACH ROW
INSERT IGNORE INTO `admin_push_event_queue` (`category`, `event_key`)
VALUES ('contributions', CONCAT('contribution:', NEW.id));

DROP TRIGGER IF EXISTS `trg_admin_push_segnalazioni_insert`;
CREATE TRIGGER `trg_admin_push_segnalazioni_insert`
AFTER INSERT ON `segnalazioni_problemi`
FOR EACH ROW
INSERT IGNORE INTO `admin_push_event_queue` (`category`, `event_key`)
VALUES ('reports', CONCAT('report:', NEW.id));
