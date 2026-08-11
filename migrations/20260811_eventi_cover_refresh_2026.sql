-- Forza l'aggiornamento delle cover editoriali degli eventi.
-- La Festa del Miele e' esclusa esplicitamente e non viene modificata.
-- Migrazione additiva e idempotente: non modifica migrazioni gia' applicate.

DROP TEMPORARY TABLE IF EXISTS `tmp_lauco_event_cover_refresh`;
CREATE TEMPORARY TABLE `tmp_lauco_event_cover_refresh` (
  `slug` VARCHAR(220) NOT NULL,
  `new_cover` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tmp_lauco_event_cover_refresh` (`slug`, `new_cover`) VALUES
  ('presentazione-acque-vive-cristina-noacco-2026', 'assets/img/eventi/libro.png'),
  ('festeggiamenti-primo-maggio-porteal-2026', 'assets/img/eventi/festa-paese.png'),
  ('autoemoteca-maggio-2026', 'assets/img/eventi/autoemoteca.png'),
  ('teatro-friulano-teatri-di-pais-2026', 'assets/img/eventi/teatro.png'),
  ('pentecoste-40-ore-comunioni-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('apertura-chiosco-vinaio-giugno-2026', 'assets/img/eventi/chiosco.png'),
  ('sant-antonio-avaglio-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('gemellaggio-vinaio-runchia-2026', 'assets/img/eventi/festa-paese.png'),
  ('trava-open-2026', 'assets/img/eventi/festa-paese.png'),
  ('san-giovanni-buttea-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('evento-chiosco-vinaio-5-luglio-2026', 'assets/img/eventi/chiosco.png'),
  ('celtic-fest-lauco-2026', 'assets/img/eventi/celtic-fest.png'),
  ('eventi-chiosco-vinaio-12-luglio-2026', 'assets/img/eventi/chiosco.png'),
  ('concerto-coro-polifonico-piattaforma-2026', 'assets/img/eventi/concerto-coro.png'),
  ('la-pelle-della-terra-trava-2026', 'assets/img/eventi/cinema.png'),
  ('sagra-paesana-trava-2026', 'assets/img/eventi/festa-paese.png'),
  ('apertura-chiosco-vinaio-ferragosto-2026', 'assets/img/eventi/chiosco.png'),
  ('san-rocco-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('sagra-di-san-rocco-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('rally-valli-della-carnia-lauco-2026', 'assets/img/eventi/rally.png'),
  ('sagra-paesana-allegnidis-2026', 'assets/img/eventi/festa-paese.png'),
  ('madonna-delle-grazie-allegnidis-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('madonna-cintura-vinaio-2026', 'assets/img/eventi/celebrazione-religiosa.png'),
  ('autoemoteca-ottobre-2026', 'assets/img/eventi/autoemoteca.png'),
  ('las-cidules-buttea-2026', 'assets/img/eventi/cidules.png'),
  ('las-cidules-uerpa-2026', 'assets/img/eventi/cidules.png'),
  ('corona-avvento-rosegg-2026', 'assets/img/eventi/natale.png'),
  ('giro-degli-auguri-12-13-dicembre-2026', 'assets/img/eventi/natale.png'),
  ('concerto-santa-lucia-polifonico-2026', 'assets/img/eventi/concerto-coro.png'),
  ('giro-degli-auguri-19-20-dicembre-2026', 'assets/img/eventi/natale.png'),
  ('las-cidules-avaglio-2026', 'assets/img/eventi/cidules.png'),
  ('brindisi-inizio-anno-lauco-2027', 'assets/img/eventi/festa-paese.png');

UPDATE `eventi` AS e
JOIN `tmp_lauco_event_cover_refresh` AS u ON u.`slug` = e.`slug`
SET e.`cover_image` = u.`new_cover`
WHERE LOWER(e.`slug`) NOT LIKE '%miele%'
  AND LOWER(COALESCE(e.`titolo`, '')) NOT LIKE '%miele%';

DROP TEMPORARY TABLE IF EXISTS `tmp_lauco_event_cover_refresh`;
