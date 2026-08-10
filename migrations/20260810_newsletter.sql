-- Newsletter subscribers and HTML campaigns.

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `status` ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  `locale` CHAR(2) NOT NULL DEFAULT 'it',
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `subscribed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_newsletter_subscribers_email` (`email`),
  KEY `idx_newsletter_subscribers_status_date` (`status`,`subscribed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject` VARCHAR(190) NOT NULL,
  `preheader` VARCHAR(255) NULL,
  `html_body` MEDIUMTEXT NOT NULL,
  `status` ENUM('draft','sent','failed') NOT NULL DEFAULT 'draft',
  `created_by` INT UNSIGNED NULL,
  `sent_at` DATETIME NULL DEFAULT NULL,
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_newsletter_campaigns_status_created` (`status`,`created_at`),
  KEY `idx_newsletter_campaigns_created_by` (`created_by`),
  CONSTRAINT `fk_newsletter_campaigns_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
