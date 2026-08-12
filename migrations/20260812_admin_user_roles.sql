-- Ruoli e permessi backoffice.
-- Gli account esistenti mantengono accesso completo.

ALTER TABLE `utenti`
  ADD COLUMN IF NOT EXISTS `ruolo` SET('admin','collaboratore','whatsapp') NOT NULL DEFAULT 'admin' AFTER `password_hash`;

UPDATE `utenti`
SET `ruolo` = 'admin'
WHERE `ruolo` IS NULL OR `ruolo` = '';
