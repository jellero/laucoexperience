CREATE TABLE IF NOT EXISTS `admin_push_preferences` (
  `admin_id` INT UNSIGNED NOT NULL,
  `category` VARCHAR(40) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `category`),
  CONSTRAINT `fk_admin_push_preferences_user`
    FOREIGN KEY (`admin_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_push_event_state` (
  `event_key` VARCHAR(80) NOT NULL,
  `event_value` VARCHAR(255) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
