-- Sentieri autonomi dagli itinerari: anagrafica, GPX, stato e storico verifiche.

CREATE TABLE IF NOT EXISTS `sentieri` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(190) NOT NULL,
  `codice` VARCHAR(80) NULL,
  `slug` VARCHAR(220) NOT NULL,
  `localita` VARCHAR(190) NULL,
  `descrizione` TEXT NULL,
  `gpx_file` VARCHAR(255) NOT NULL,
  `stato` ENUM('verificato','attenzione','non_percorribile','in_verifica') NOT NULL DEFAULT 'in_verifica',
  `nota_pubblica` TEXT NULL,
  `ultima_verifica_at` DATETIME NULL,
  `prossima_verifica_at` DATE NULL,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `ordine` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sentieri_slug` (`slug`),
  KEY `idx_sentieri_pubblicazione` (`pubblicato`,`stato`,`ordine`,`nome`),
  KEY `idx_sentieri_codice` (`codice`),
  CONSTRAINT `fk_sentieri_created_by` FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sentieri_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sentieri_verifiche` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sentiero_id` INT UNSIGNED NOT NULL,
  `stato` ENUM('verificato','attenzione','non_percorribile','in_verifica') NOT NULL,
  `nota` TEXT NULL,
  `verificato_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sentieri_verifiche_data` (`sentiero_id`,`verificato_at`),
  CONSTRAINT `fk_sentieri_verifiche_sentiero` FOREIGN KEY (`sentiero_id`) REFERENCES `sentieri` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sentieri_verifiche_admin` FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
