-- Volontariato, gruppi operativi, WhatsApp, planning e stato dei sentieri.
-- Le tabelle contributi e segnalazioni_problemi non vengono modificate.

CREATE TABLE IF NOT EXISTS `volontari` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codice` VARCHAR(32) NOT NULL,
  `stato` ENUM('da_confermare','invitato','attivo','in_pausa','ritirato') NOT NULL DEFAULT 'da_confermare',
  `nome` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(24) NOT NULL,
  `email` VARCHAR(190) NULL,
  `zona` VARCHAR(120) NULL,
  `interessi_json` TEXT NOT NULL,
  `disponibilita` VARCHAR(120) NULL,
  `locale` CHAR(2) NOT NULL DEFAULT 'it',
  `consenso_privacy` TINYINT(1) NOT NULL DEFAULT 1,
  `consenso_whatsapp` TINYINT(1) NOT NULL DEFAULT 1,
  `consenso_visibilita_gruppo` TINYINT(1) NOT NULL DEFAULT 1,
  `maggiorenne` TINYINT(1) NOT NULL DEFAULT 1,
  `versione_consenso` VARCHAR(40) NOT NULL DEFAULT 'volontariato-v1',
  `consenso_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `note_admin` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_volontari_codice` (`codice`),
  UNIQUE KEY `uniq_volontari_telefono` (`telefono`),
  KEY `idx_volontari_stato_created` (`stato`,`created_at`),
  KEY `idx_volontari_zona` (`zona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volontari_gruppi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `tipo` ENUM('generale','zona','attivita') NOT NULL DEFAULT 'generale',
  `zona` VARCHAR(120) NULL,
  `descrizione` TEXT NULL,
  `meta_group_id` VARCHAR(190) NULL,
  `invite_link` VARCHAR(500) NULL,
  `predefinito` TINYINT(1) NOT NULL DEFAULT 0,
  `attivo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_volontari_gruppi_nome` (`nome`),
  UNIQUE KEY `uniq_volontari_gruppi_meta_id` (`meta_group_id`),
  KEY `idx_volontari_gruppi_tipo_zona` (`tipo`,`zona`,`attivo`),
  KEY `idx_volontari_gruppi_default` (`predefinito`,`attivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volontari_gruppi_membri` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gruppo_id` INT UNSIGNED NOT NULL,
  `volontario_id` INT UNSIGNED NOT NULL,
  `stato` ENUM('assegnato','invito_in_coda','invitato','entrato','uscito','errore') NOT NULL DEFAULT 'assegnato',
  `invited_at` DATETIME NULL,
  `joined_at` DATETIME NULL,
  `left_at` DATETIME NULL,
  `ultimo_errore` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_volontari_gruppi_membro` (`gruppo_id`,`volontario_id`),
  KEY `idx_volontari_membri_volontario` (`volontario_id`,`stato`),
  CONSTRAINT `fk_volontari_membri_gruppo` FOREIGN KEY (`gruppo_id`) REFERENCES `volontari_gruppi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_volontari_membri_volontario` FOREIGN KEY (`volontario_id`) REFERENCES `volontari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volontari_attivita` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gruppo_id` INT UNSIGNED NULL,
  `percorso_id` INT UNSIGNED NULL,
  `titolo` VARCHAR(190) NOT NULL,
  `categoria` VARCHAR(100) NOT NULL,
  `zona` VARCHAR(120) NULL,
  `stato` ENUM('bozza','raccolta_adesioni','programmata','in_corso','completata','annullata') NOT NULL DEFAULT 'bozza',
  `data_ora` DATETIME NULL,
  `punto_ritrovo` VARCHAR(255) NULL,
  `coordinatore` VARCHAR(150) NULL,
  `descrizione` TEXT NULL,
  `note_sicurezza` TEXT NULL,
  `checklist` TEXT NULL,
  `avanzamento` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `note_chiusura` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_volontari_attivita_stato_data` (`stato`,`data_ora`),
  KEY `idx_volontari_attivita_gruppo` (`gruppo_id`),
  KEY `idx_volontari_attivita_percorso` (`percorso_id`),
  CONSTRAINT `fk_volontari_attivita_gruppo` FOREIGN KEY (`gruppo_id`) REFERENCES `volontari_gruppi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_volontari_attivita_percorso` FOREIGN KEY (`percorso_id`) REFERENCES `percorsi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_volontari_attivita_admin` FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volontari_attivita_partecipanti` (
  `attivita_id` INT UNSIGNED NOT NULL,
  `volontario_id` INT UNSIGNED NOT NULL,
  `stato` ENUM('invitato','confermato','presente','assente') NOT NULL DEFAULT 'invitato',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attivita_id`,`volontario_id`),
  KEY `idx_volontari_attivita_partecipante` (`volontario_id`,`stato`),
  CONSTRAINT `fk_volontari_partecipanti_attivita` FOREIGN KEY (`attivita_id`) REFERENCES `volontari_attivita` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_volontari_partecipanti_volontario` FOREIGN KEY (`volontario_id`) REFERENCES `volontari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stato_sentieri` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `percorso_id` INT UNSIGNED NOT NULL,
  `stato` ENUM('verificato','attenzione','non_percorribile','in_verifica') NOT NULL DEFAULT 'in_verifica',
  `nota_pubblica` TEXT NULL,
  `ultima_verifica_at` DATETIME NULL,
  `prossima_verifica_at` DATE NULL,
  `pubblicato` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stato_sentieri_percorso` (`percorso_id`),
  KEY `idx_stato_sentieri_stato_pubblicato` (`stato`,`pubblicato`,`ultima_verifica_at`),
  CONSTRAINT `fk_stato_sentieri_percorso` FOREIGN KEY (`percorso_id`) REFERENCES `percorsi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stato_sentieri_admin` FOREIGN KEY (`updated_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stato_sentieri_verifiche` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `percorso_id` INT UNSIGNED NOT NULL,
  `attivita_id` INT UNSIGNED NULL,
  `stato` ENUM('verificato','attenzione','non_percorribile','in_verifica') NOT NULL,
  `nota` TEXT NULL,
  `verificato_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stato_verifiche_percorso_data` (`percorso_id`,`verificato_at`),
  CONSTRAINT `fk_stato_verifiche_percorso` FOREIGN KEY (`percorso_id`) REFERENCES `percorsi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stato_verifiche_attivita` FOREIGN KEY (`attivita_id`) REFERENCES `volontari_attivita` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stato_verifiche_admin` FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_conversazioni` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('diretta','gruppo') NOT NULL DEFAULT 'diretta',
  `volontario_id` INT UNSIGNED NULL,
  `gruppo_id` INT UNSIGNED NULL,
  `external_id` VARCHAR(190) NOT NULL,
  `titolo` VARCHAR(190) NULL,
  `non_letti` INT UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_messaggio_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_whatsapp_conversazioni_external` (`external_id`),
  KEY `idx_whatsapp_conversazioni_last` (`ultimo_messaggio_at`),
  CONSTRAINT `fk_whatsapp_conversazioni_volontario` FOREIGN KEY (`volontario_id`) REFERENCES `volontari` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_conversazioni_gruppo` FOREIGN KEY (`gruppo_id`) REFERENCES `volontari_gruppi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_messaggi` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversazione_id` INT UNSIGNED NOT NULL,
  `external_message_id` VARCHAR(190) NULL,
  `direzione` ENUM('entrata','uscita') NOT NULL,
  `tipo` VARCHAR(40) NOT NULL DEFAULT 'text',
  `testo` TEXT NULL,
  `stato` ENUM('ricevuto','in_coda','inviato','consegnato','letto','fallito') NOT NULL DEFAULT 'ricevuto',
  `raw_json` MEDIUMTEXT NULL,
  `messaggio_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_whatsapp_messaggi_external` (`external_message_id`),
  KEY `idx_whatsapp_messaggi_conversazione` (`conversazione_id`,`messaggio_at`),
  CONSTRAINT `fk_whatsapp_messaggi_conversazione` FOREIGN KEY (`conversazione_id`) REFERENCES `whatsapp_conversazioni` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('invito','diretto','gruppo') NOT NULL,
  `destinatario` VARCHAR(190) NOT NULL,
  `payload_json` MEDIUMTEXT NOT NULL,
  `volontario_id` INT UNSIGNED NULL,
  `gruppo_id` INT UNSIGNED NULL,
  `membro_id` INT UNSIGNED NULL,
  `stato` ENUM('configurazione_mancante','in_coda','inviato','fallito') NOT NULL DEFAULT 'in_coda',
  `tentativi` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_errore` TEXT NULL,
  `scheduled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_whatsapp_outbox_stato_data` (`stato`,`scheduled_at`),
  CONSTRAINT `fk_whatsapp_outbox_volontario` FOREIGN KEY (`volontario_id`) REFERENCES `volontari` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_outbox_gruppo` FOREIGN KEY (`gruppo_id`) REFERENCES `volontari_gruppi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_outbox_membro` FOREIGN KEY (`membro_id`) REFERENCES `volontari_gruppi_membri` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_webhook_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_hash` CHAR(64) NOT NULL,
  `payload_json` MEDIUMTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_whatsapp_webhook_event_hash` (`event_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `volontari_gruppi` (`nome`,`tipo`,`descrizione`,`predefinito`,`attivo`)
SELECT 'Volontari Lauco','generale','Gruppo operativo generale per volontariato e cura del territorio.',1,1
WHERE NOT EXISTS (SELECT 1 FROM `volontari_gruppi` WHERE `predefinito` = 1);
