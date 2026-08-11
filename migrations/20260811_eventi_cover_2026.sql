-- Aggiorna le cover degli eventi 2026/2027 con le immagini editoriali dedicate.
-- Migrazione additiva: non modifica 20260811_calendario_eventi_2026_completo.sql,
-- gia' applicata in alcuni ambienti.
--
-- Per non sovrascrivere eventuali immagini personalizzate dal backoffice,
-- una cover viene aggiornata solo quando coincide ancora con il valore precedente.

DROP TEMPORARY TABLE IF EXISTS `tmp_lauco_event_cover_updates`;
CREATE TEMPORARY TABLE `tmp_lauco_event_cover_updates` (
  `slug` VARCHAR(220) NOT NULL,
  `old_cover` VARCHAR(255) NOT NULL,
  `new_cover` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tmp_lauco_event_cover_updates` (`slug`, `old_cover`, `new_cover`) VALUES
  ('presentazione-acque-vive-cristina-noacco-2026', 'assets/img/old.jpg', 'assets/img/eventi/libro.png'),
  ('festeggiamenti-primo-maggio-porteal-2026', 'uploads/luoghi/loc-porteal-20260610213800-b92b1a.jpg', 'assets/img/eventi/festa-paese.png'),
  ('autoemoteca-maggio-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/autoemoteca.png'),
  ('teatro-friulano-teatri-di-pais-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/teatro.png'),
  ('pentecoste-40-ore-comunioni-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('apertura-chiosco-vinaio-giugno-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/chiosco.png'),
  ('sant-antonio-avaglio-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('gemellaggio-vinaio-runchia-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/festa-paese.png'),
  ('trava-open-2026', 'uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg', 'assets/img/eventi/festa-paese.png'),
  ('san-giovanni-buttea-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('evento-chiosco-vinaio-5-luglio-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/chiosco.png'),
  ('celtic-fest-lauco-2026', 'assets/img/old.jpg', 'assets/img/eventi/celtic-fest.png'),
  ('eventi-chiosco-vinaio-12-luglio-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/chiosco.png'),
  ('concerto-coro-polifonico-piattaforma-2026', 'uploads/luoghi/terrazza-20260610214112-b51f01.jpg', 'assets/img/eventi/concerto-coro.png'),
  ('la-pelle-della-terra-trava-2026', 'assets/img/old.jpg', 'assets/img/eventi/cinema.png'),
  ('sagra-paesana-trava-2026', 'uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg', 'assets/img/eventi/festa-paese.png'),
  ('apertura-chiosco-vinaio-ferragosto-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/chiosco.png'),
  ('san-rocco-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('rally-valli-della-carnia-lauco-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/rally.png'),
  ('sagra-paesana-allegnidis-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/festa-paese.png'),
  ('madonna-delle-grazie-allegnidis-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('madonna-cintura-vinaio-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('autoemoteca-ottobre-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/autoemoteca.png'),
  ('las-cidules-buttea-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/cidules.png'),
  ('las-cidules-uerpa-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/cidules.png'),
  ('corona-avvento-rosegg-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/natale.png'),
  ('giro-degli-auguri-12-13-dicembre-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/natale.png'),
  ('concerto-santa-lucia-polifonico-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/concerto-coro.png'),
  ('giro-degli-auguri-19-20-dicembre-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/natale.png'),
  ('las-cidules-avaglio-2026', 'assets/img/eventi.jpg', 'assets/img/eventi/cidules.png'),
  ('brindisi-inizio-anno-lauco-2027', 'assets/img/eventi.jpg', 'assets/img/eventi/festa-paese.png');

UPDATE `eventi` AS e
JOIN `tmp_lauco_event_cover_updates` AS u ON u.`slug` = e.`slug`
SET e.`cover_image` = u.`new_cover`
WHERE e.`cover_image` = u.`old_cover`;

-- Compatibilita' con il vecchio inserimento manuale di San Rocco, qualora fosse
-- ancora presente perche' la migrazione calendario completa non lo ha ancora corretto.
UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/celebrazione-religiosa.png'
WHERE `slug` = 'sagra-di-san-rocco-2026'
  AND `cover_image` = 'assets/img/eventi.jpg';

DROP TEMPORARY TABLE IF EXISTS `tmp_lauco_event_cover_updates`;
