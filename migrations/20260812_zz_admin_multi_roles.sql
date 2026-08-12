-- Consente di assegnare più permessi allo stesso account.
-- Esempio: collaboratore + whatsapp senza accesso amministratore.

ALTER TABLE `utenti`
  MODIFY COLUMN `ruolo` SET('admin','collaboratore','whatsapp') NOT NULL DEFAULT 'admin';
