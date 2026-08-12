-- Lauco Experience - schema iniziale senza dati.
-- Selezionare prima il database di destinazione, quindi importare questo file.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `utenti` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `ruolo` SET('admin','collaboratore','whatsapp') NOT NULL DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_utenti_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `percorsi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titolo` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `tipo` ENUM('piedi','mtb') NOT NULL,
  `sottotitolo` VARCHAR(255) NULL,
  `excerpt` TEXT NULL,
  `descrizione` MEDIUMTEXT NULL,
  `cover_image` VARCHAR(255) NULL,
  `gpx_file` VARCHAR(255) NULL,
  `localita` VARCHAR(190) NULL,
  `difficolta` VARCHAR(80) NULL,
  `distanza_km` DECIMAL(8,2) NULL,
  `dislivello_m` INT NULL,
  `durata` VARCHAR(80) NULL,
  `tempo` VARCHAR(80) NULL,
  `ordine` INT NOT NULL DEFAULT 0,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `consigliato` TINYINT(1) NOT NULL DEFAULT 0,
  `speciale` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_percorsi_slug` (`slug`),
  KEY `idx_tipo_pubblicato` (`tipo`,`pubblicato`,`ordine`),
  KEY `idx_tipo_pubblicato_consigliato` (`tipo`,`pubblicato`,`consigliato`,`ordine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `percorso_gallery` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `percorso_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt` VARCHAR(190) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_percorso_gallery_percorso` (`percorso_id`),
  CONSTRAINT `fk_percorso_gallery_percorso`
    FOREIGN KEY (`percorso_id`) REFERENCES `percorsi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `eventi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titolo` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `data_evento` DATE NULL,
  `localita` VARCHAR(190) NULL,
  `categoria` VARCHAR(190) NULL,
  `excerpt` TEXT NULL,
  `contenuto` MEDIUMTEXT NULL,
  `cover_image` VARCHAR(255) NULL,
  `ordine` INT NOT NULL DEFAULT 0,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_eventi_slug` (`slug`),
  KEY `idx_eventi_pubblicato_data` (`pubblicato`,`data_evento`,`ordine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evento_gallery` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evento_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt` VARCHAR(190) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_evento_gallery_evento` (`evento_id`),
  CONSTRAINT `fk_evento_gallery_evento`
    FOREIGN KEY (`evento_id`) REFERENCES `eventi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `galleria` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titolo` VARCHAR(190) NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt` VARCHAR(190) NULL,
  `categoria` VARCHAR(190) NULL,
  `ordine` INT NOT NULL DEFAULT 0,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_galleria_pubblicato_ordine` (`pubblicato`,`ordine`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `home_slider` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titolo` VARCHAR(190) NOT NULL,
  `sottotitolo` VARCHAR(190) NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `button_label` VARCHAR(80) NOT NULL DEFAULT 'info',
  `link_type` ENUM('none','free','evento','percorso') NOT NULL DEFAULT 'none',
  `custom_url` VARCHAR(255) NULL,
  `evento_id` INT UNSIGNED NULL,
  `percorso_id` INT UNSIGNED NULL,
  `ordine` INT NOT NULL DEFAULT 0,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_home_slider_pubblicato_ordine` (`pubblicato`,`ordine`,`created_at`),
  KEY `idx_home_slider_evento` (`evento_id`),
  KEY `idx_home_slider_percorso` (`percorso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `luoghi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titolo` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `sottotitolo` VARCHAR(255) NULL,
  `categoria` VARCHAR(120) NULL,
  `localita` VARCHAR(160) NULL,
  `excerpt` TEXT NULL,
  `descrizione` MEDIUMTEXT NULL,
  `cover_image` VARCHAR(255) NULL,
  `lat` DECIMAL(10,7) NULL,
  `lng` DECIMAL(10,7) NULL,
  `periodo_consigliato` VARCHAR(160) NULL,
  `accessibilita` TEXT NULL,
  `note` TEXT NULL,
  `ordine` INT NOT NULL DEFAULT 0,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `in_evidenza` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_luoghi_slug` (`slug`),
  KEY `idx_luoghi_pubblicato_ordine` (`pubblicato`,`ordine`,`titolo`),
  KEY `idx_luoghi_evidenza` (`in_evidenza`,`pubblicato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `luogo_gallery` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `luogo_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) NULL,
  `ordine` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_luogo_gallery_luogo` (`luogo_id`,`ordine`,`id`),
  CONSTRAINT `fk_luogo_gallery_luogo`
    FOREIGN KEY (`luogo_id`) REFERENCES `luoghi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contatti_messaggi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codice` VARCHAR(32) NOT NULL,
  `stato` ENUM('nuovo','letto','risposto','archiviato') NOT NULL DEFAULT 'nuovo',
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `oggetto` VARCHAR(190) NOT NULL,
  `messaggio` TEXT NOT NULL,
  `privacy` TINYINT(1) NOT NULL DEFAULT 1,
  `mail_admin_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `mail_customer_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `note_admin` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_contatti_codice` (`codice`),
  KEY `idx_contatti_stato_created` (`stato`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contributi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codice` VARCHAR(32) NOT NULL,
  `stato` ENUM('nuovo','letto','valutato','pubblicato','archiviato') NOT NULL DEFAULT 'nuovo',
  `tipo` VARCHAR(80) NOT NULL,
  `titolo` VARCHAR(190) NOT NULL,
  `descrizione` TEXT NOT NULL,
  `localita` VARCHAR(190) NULL,
  `percorso_gpx` VARCHAR(255) NULL,
  `pagina_url` VARCHAR(255) NULL,
  `nome` VARCHAR(150) NULL,
  `email` VARCHAR(190) NULL,
  `telefono` VARCHAR(80) NULL,
  `allegato_path` VARCHAR(255) NULL,
  `consenso` TINYINT(1) NOT NULL DEFAULT 1,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `note_admin` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_contributi_codice` (`codice`),
  KEY `idx_contributi_stato_created` (`stato`,`created_at`),
  KEY `idx_contributi_tipo` (`tipo`),
  KEY `idx_contributi_percorso_gpx` (`percorso_gpx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `segnalazioni_problemi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codice` VARCHAR(32) NOT NULL,
  `stato` ENUM('nuova','in_lavorazione','risolta','archiviata') NOT NULL DEFAULT 'nuova',
  `priorita` ENUM('bassa','media','alta') NOT NULL DEFAULT 'media',
  `categoria` VARCHAR(80) NOT NULL,
  `titolo` VARCHAR(190) NOT NULL,
  `descrizione` TEXT NOT NULL,
  `luogo` VARCHAR(190) NULL,
  `pagina_url` VARCHAR(255) NULL,
  `percorso_id` INT UNSIGNED NULL,
  `evento_id` INT UNSIGNED NULL,
  `nome` VARCHAR(120) NULL,
  `email` VARCHAR(190) NULL,
  `telefono` VARCHAR(80) NULL,
  `allegato_path` VARCHAR(255) NULL,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `note_admin` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_segnalazioni_codice` (`codice`),
  KEY `idx_segnalazioni_stato_created` (`stato`,`created_at`),
  KEY `idx_segnalazioni_percorso` (`percorso_id`),
  KEY `idx_segnalazioni_evento` (`evento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
