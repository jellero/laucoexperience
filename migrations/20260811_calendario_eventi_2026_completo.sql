-- Calendario ufficiale Lauco 2026: import completo degli appuntamenti.
-- Fonte: locandina ufficiale del Comune di Lauco.
-- La Festa del Miele di Alta Montagna del 23/08/2026 non viene inserita:
-- e' gia presente nel CMS e va mantenuta senza duplicati.
--
-- La tabella eventi non dispone di una data_fine: per gli appuntamenti su piu giorni
-- data_evento contiene il primo giorno e l'intervallo completo resta in excerpt/contenuto.

DROP TEMPORARY TABLE IF EXISTS `tmp_lauco_calendario_2026`;
CREATE TEMPORARY TABLE `tmp_lauco_calendario_2026` (
  `data_evento` DATE NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `titolo` VARCHAR(190) NOT NULL,
  `localita` VARCHAR(190) NULL,
  `categoria` VARCHAR(190) NULL,
  `excerpt` TEXT NULL,
  `contenuto` MEDIUMTEXT NULL,
  `cover_image` VARCHAR(255) NULL,
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tmp_lauco_calendario_2026`
  (`data_evento`,`slug`,`titolo`,`localita`,`categoria`,`excerpt`,`contenuto`,`cover_image`)
VALUES
  ('2026-04-26','presentazione-acque-vive-cristina-noacco-2026','Presentazione del libro “Acque Vive” di Cristina Noacco','Lauco','Eventi','Presentazione Libro “Acque Vive” di Cristina Noacco presso Centro Socio Culturale Lauco (ex latteria) ore 17.00','Presentazione Libro “Acque Vive” di Cristina Noacco presso Centro Socio Culturale Lauco (ex latteria) ore 17.00

Organizzatore: Comune di Lauco.','assets/img/old.jpg'),
  ('2026-05-01','festeggiamenti-primo-maggio-porteal-2026','Festeggiamenti del 1° maggio','Lauco/Porteal','Eventi','Festeggiamenti del 1° maggio','Festeggiamenti del 1° maggio

Organizzatore: Casa del Popolo.','uploads/luoghi/loc-porteal-20260610213800-b92b1a.jpg'),
  ('2026-05-03','autoemoteca-maggio-2026','Autoemoteca','Lauco','Eventi','Autoemoteca','Autoemoteca

Organizzatore: Donatori sangue Lauco/Buttea.','assets/img/eventi.jpg'),
  ('2026-05-10','teatro-friulano-teatri-di-pais-2026','Teatro in Friulano con “TEATRI DI PAIS”','Lauco','Eventi','Teatro in Friulano con la Compagnia “TEATRI DI PAIS” di Buja presso Casa del Popolo ore 17.00','Teatro in Friulano con la Compagnia “TEATRI DI PAIS” di Buja presso Casa del Popolo ore 17.00

Organizzatore: Comune di Lauco / Casa del Popolo.','assets/img/eventi.jpg'),
  ('2026-05-23','vertical-race-cronoradime-2026','Vertical Race Cronoradime','Lauco','Eventi','“Vertical Race Cronoradime”','“Vertical Race Cronoradime”

Organizzatore: G. S. M.te Arvenis Aps.','assets/img/cronoradima.jpg'),
  ('2026-05-24','pentecoste-40-ore-comunioni-2026','Pentecoste e inizio delle 40 ORE / Comunioni','Lauco','Eventi','Pentecoste e inizio delle 40 ORE / Comunioni','Pentecoste e inizio delle 40 ORE / Comunioni

Organizzatore: Parrocchia.','assets/img/eventi.jpg'),
  ('2026-05-31','festa-croce-monte-arvenis-2026','Festa della Croce del Monte Arvenis','Arvenis','Eventi','Festa della Croce del Monte Arvenis','Festa della Croce del Monte Arvenis

Organizzatore: G. S. M.te Arvenis Aps / Gruppo A.N.A. Buttea.','assets/img/sentieri.webp'),
  ('2026-06-01','apertura-chiosco-vinaio-giugno-2026','Apertura Chiosco presso campo giochi','Vinaio','Eventi','Apertura Chiosco presso campo giochi','Apertura Chiosco presso campo giochi

Organizzatore: Pro Vinaio Aps.','assets/img/eventi.jpg'),
  ('2026-06-03','festa-degli-alberi-lauco-rosegg-2026','Festa degli Alberi - Scuola di Lauco e Rosegg','Lauco','Eventi','Scuola di Lauco e Rosegg - FESTA DEGLI ALBERI','Scuola di Lauco e Rosegg - FESTA DEGLI ALBERI

Organizzatore: Scuola e Comune di Lauco.','assets/img/sentieri.webp'),
  ('2026-06-14','sant-antonio-avaglio-2026','Festa di S. Antonio','Avaglio','Eventi','Festa di S. Antonio con S. Messa ore 15.00','Festa di S. Antonio con S. Messa ore 15.00

Organizzatore: Parrocchia.','assets/img/eventi.jpg'),
  ('2026-06-21','san-luigi-trava-2026','Festa di S. Luigi','Trava','Eventi','Festa di S. Luigi con S. Messa ore 15.30','Festa di S. Luigi con S. Messa ore 15.30

Organizzatore: Parrocchia.','uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg'),
  ('2026-06-21','gemellaggio-vinaio-runchia-2026','Gemellaggio con Runchia','Vinaio/Runchia','Eventi','Gemellaggio con Runchia','Gemellaggio con Runchia

Organizzatore: Pro Vinaio Aps.','assets/img/eventi.jpg'),
  ('2026-06-21','escursione-solstizio-estate-museo-2026','Escursione del Solstizio d’Estate con visita al Museo','Lauco','Eventi','Escursione del Solstizio d’Estate con visita Museo (con Guida Ambientale Escursionistica e Naturalistica)','Escursione del Solstizio d’Estate con visita Museo (con Guida Ambientale Escursionistica e Naturalistica)

Organizzatore: Comune di Lauco.','assets/img/sentieri.webp'),
  ('2026-06-21','apertura-chiosco-piattaforma-panoramica-2026','Apertura Chiosco in Piattaforma Panoramica','Lauco','Eventi','Apertura Chiosco in Piattaforma Panoramica','Apertura Chiosco in Piattaforma Panoramica

Organizzatore: G. S. M.te Arvenis Aps.','uploads/luoghi/terrazza-20260610214112-b51f01.jpg'),
  ('2026-06-27','trava-open-2026','Trava Open','Trava','Eventi','Trava Open (in caso maltempo 4 luglio)','Trava Open (in caso maltempo 4 luglio)

Organizzatore: Gruppo volontari di Trava.','uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg'),
  ('2026-06-28','san-giovanni-buttea-2026','San Giovanni','Buttea','Eventi','San Giovanni con S. Messa ore 15.30','San Giovanni con S. Messa ore 15.30

Organizzatore: Parrocchia.','assets/img/eventi.jpg'),
  ('2026-07-05','evento-chiosco-vinaio-5-luglio-2026','Evento in Chiosco','Vinaio','Eventi','Evento in Chiosco','Evento in Chiosco

Organizzatore: Pro Vinaio Aps.','assets/img/eventi.jpg'),
  ('2026-07-10','escursione-celtic-fest-museo-2026','Escursione collegata al Celtic Fest e visita al Museo','Lauco','Eventi','Escursione sul territorio collegato a Celtic Fest e Visita in Museo','Escursione sul territorio collegato a Celtic Fest e Visita in Museo

Organizzatore: Comune di Lauco.','assets/img/sentieri.webp'),
  ('2026-07-10','celtic-fest-lauco-2026','Celtic Fest','Lauco','Eventi','Celtic Fest (Villaggio Celtico con attività sul tema) nei pressi delle tombe in località Curs. In calendario il 10-11-12 luglio 2026.','Celtic Fest (Villaggio Celtico con attività sul tema) nei pressi delle tombe in località Curs. In calendario il 10-11-12 luglio 2026.

Organizzatore: Comune di Lauco.','assets/img/old.jpg'),
  ('2026-07-12','eventi-chiosco-vinaio-12-luglio-2026','Eventi in Chiosco','Vinaio','Eventi','Eventi in Chiosco','Eventi in Chiosco

Organizzatore: Pro Vinaio Aps.','assets/img/eventi.jpg'),
  ('2026-07-19','raduno-provinciale-alpini-2026','Raduno Provinciale Alpini','Lauco','Eventi','Raduno Provinciale Alpini con sfilata sul territorio per le vie del Capoluogo','Raduno Provinciale Alpini con sfilata sul territorio per le vie del Capoluogo

Organizzatore: Gruppo A.N.A. Buttea.','assets/img/alpini.jpg'),
  ('2026-07-26','concerto-coro-polifonico-piattaforma-2026','Concerto del Coro Polifonico con solisti','Lauco','Eventi','Concerto Coro Polifonico con solisti presso Piattaforma Panoramica','Concerto Coro Polifonico con solisti presso Piattaforma Panoramica

Organizzatore: Comune di Lauco.','uploads/luoghi/terrazza-20260610214112-b51f01.jpg'),
  ('2026-08-02','maina-dal-cret-2026','Ricorrenza “Maina dal Cret”','Lauco','Eventi','Ricorrenza “Maina dal Cret”','Ricorrenza “Maina dal Cret”

Organizzatore: Gruppo A.N.A. Buttea.','assets/img/alpini.jpg'),
  ('2026-08-08','escursione-santuario-trava-agosto-2026','Escursione con visita al Santuario di Trava','Lauco','Eventi','Escursione sul territorio con Visita al Santuario di Trava (con Guida Ambientale Escursionistica e Naturalistica)','Escursione sul territorio con Visita al Santuario di Trava (con Guida Ambientale Escursionistica e Naturalistica)

Organizzatore: Comune di Lauco.','uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg'),
  ('2026-08-14','la-pelle-della-terra-trava-2026','Proiezione del film “La Pelle della Terra”','Trava','Eventi','Nella ricorrenza del 50° del terremoto in Friuli, proiezione del Film “La Pelle della Terra”','Nella ricorrenza del 50° del terremoto in Friuli, proiezione del Film “La Pelle della Terra”

Organizzatore: Gruppo Volontari di Trava.','assets/img/old.jpg'),
  ('2026-08-15','sagra-paesana-trava-2026','Sagra Paesana e S. Messa al Santuario','Trava','Eventi','Sagra Paesana e S. Messa al Santuario ore 10.00','Sagra Paesana e S. Messa al Santuario ore 10.00

Organizzatore: Gruppo Volontari di Trava.','uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg'),
  ('2026-08-15','apertura-chiosco-vinaio-ferragosto-2026','Apertura Chiosco a mezzodì','Vinaio','Eventi','Apertura Chiosco a mezzodì','Apertura Chiosco a mezzodì

Organizzatore: Pro Vinaio Aps.','assets/img/eventi.jpg'),
  ('2026-08-16','san-rocco-2026','San Rocco - S. Messa e festeggiamenti','Lauco','Eventi','San Rocco - S. Messa ore 11.15 e processione / festeggiamenti','San Rocco - S. Messa ore 11.15 e processione / festeggiamenti

Organizzatore: Parrocchia.','assets/img/eventi.jpg'),
  ('2026-08-21','rally-valli-della-carnia-lauco-2026','Rally Valli della Carnia','Lauco','Eventi','Rally Valli della Carnia. In calendario il 21-22 agosto 2026.','Rally Valli della Carnia. In calendario il 21-22 agosto 2026.

Organizzatore: A.S.D. Carnia Pistons.','assets/img/eventi.jpg'),
  ('2026-09-05','sagra-paesana-allegnidis-2026','Sagra Paesana','Allegnidis','Eventi','Sagra Paesana. In calendario il 5-6 settembre 2026.','Sagra Paesana. In calendario il 5-6 settembre 2026.

Organizzatore: A.S.D. Lauco.','assets/img/eventi.jpg'),
  ('2026-09-06','madonna-delle-grazie-allegnidis-2026','Madonna della Grazie','Allegnidis','Eventi','Madonna della Grazie - S. Messa ore 11.15','Madonna della Grazie - S. Messa ore 11.15

Organizzatore: Parrocchia.','assets/img/eventi.jpg'),
  ('2026-09-13','madonna-cintura-vinaio-2026','Madonna della Cintura','Vinaio','Eventi','Madonna della Cintura - S. Messa ore 15.00. Rassegna Scampanotadôrs e Chiusura chiosco','Madonna della Cintura - S. Messa ore 15.00. Rassegna Scampanotadôrs e Chiusura chiosco

Organizzatore: Parrocchia.','assets/img/eventi.jpg'),
  ('2026-09-27','escursione-santuario-trava-settembre-2026','Escursione con visita al Santuario di Trava','Lauco','Eventi','Escursione sul territorio (con Guida Ambientale Escursionistica e Naturalistica) con visita al Santuario di Trava','Escursione sul territorio (con Guida Ambientale Escursionistica e Naturalistica) con visita al Santuario di Trava

Organizzatore: Comune di Lauco.','uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg'),
  ('2026-10-10','foliage-autunno-lauco-2026','Escursione “Foliage d’Autunno”','Lauco','Eventi','Escursione sul territorio “Foliage d’Autunno” (con Guida Ambientale Escursionistica e Naturalistica)','Escursione sul territorio “Foliage d’Autunno” (con Guida Ambientale Escursionistica e Naturalistica)

Organizzatore: Comune di Lauco.','assets/img/sentieri.webp'),
  ('2026-10-11','autoemoteca-ottobre-2026','Autoemoteca','Lauco','Eventi','Autoemoteca','Autoemoteca

Organizzatore: Donatori sangue Lauco/Buttea.','assets/img/eventi.jpg'),
  ('2026-10-30','las-cidules-buttea-2026','Las Cidules','Buttea','Eventi','Las Cidules','Las Cidules

Organizzatore: Gruppo Cidulârs Buttea.','assets/img/eventi.jpg'),
  ('2026-10-31','las-cidules-uerpa-2026','Las Cidules','Uerpa','Eventi','Las Cidules','Las Cidules

Organizzatore: Gruppo Cidulârs Vas, Uerpa e Pesmolet.','assets/img/eventi.jpg'),
  ('2026-11-04','unita-nazionale-costituzione-buttea-2026','Festa dell’Unità Nazionale e consegna della Costituzione','Buttea','Eventi','Festa dell’Unità Nazionale e delle Forze Armate presso il Monumento ai caduti. Consegna Costituzione ai maggiorenni.','Festa dell’Unità Nazionale e delle Forze Armate presso il Monumento ai caduti. Consegna Costituzione ai maggiorenni.

Organizzatore: Comune di Lauco / Gruppo A.N.A. Buttea.','assets/img/alpini.jpg'),
  ('2026-11-26','corona-avvento-rosegg-2026','Consegna della Corona dell’Avvento da Rosegg','Lauco','Eventi','Consegna della Corona dell’Avvento da parte del Comune gemellato di Rosegg (A) ore 18.00','Consegna della Corona dell’Avvento da parte del Comune gemellato di Rosegg (A) ore 18.00

Organizzatore: Comune di Lauco.','assets/img/eventi.jpg'),
  ('2026-12-12','giro-degli-auguri-12-13-dicembre-2026','Tradizionale giro degli Auguri','Lauco','Eventi','Tradizionale giro degli Auguri. In calendario il 12-13 dicembre 2026.','Tradizionale giro degli Auguri. In calendario il 12-13 dicembre 2026.

Organizzatore: Gruppo degli Auguri.','assets/img/eventi.jpg'),
  ('2026-12-13','concerto-santa-lucia-polifonico-2026','Concerto di S. Lucia del Coro di voci bianche','Lauco','Eventi','Concerto di S. Lucia del Coro di voci bianche del Polifonico “Voci dell’Antoniano” con Scuole di Lauco e Anziani','Concerto di S. Lucia del Coro di voci bianche del Polifonico “Voci dell’Antoniano” con Scuole di Lauco e Anziani

Organizzatore: Comune di Lauco.','assets/img/eventi.jpg'),
  ('2026-12-19','giro-degli-auguri-19-20-dicembre-2026','Tradizionale giro degli Auguri','Lauco','Eventi','Tradizionale giro degli Auguri. In calendario il 19-20 dicembre 2026.','Tradizionale giro degli Auguri. In calendario il 19-20 dicembre 2026.

Organizzatore: Gruppo degli Auguri.','assets/img/eventi.jpg'),
  ('2026-12-24','las-cidules-avaglio-2026','Las Cidules','Avaglio','Eventi','Las Cidules','Las Cidules

Organizzatore: Gruppo Avaglio.','assets/img/eventi.jpg'),
  ('2026-12-24','fiaccolata-dei-madins-2026','Fiaccolata dei Madins','Lauco','Eventi','Fiaccolata dei Madins (partenza da Villa Santina)','Fiaccolata dei Madins (partenza da Villa Santina)

Organizzatore: G. S. M.te Arvenis Aps.','assets/img/sentieri.webp'),
  ('2027-01-01','brindisi-inizio-anno-lauco-2027','Brindisi di inizio anno','Lauco','Eventi','Brindisi di inizio anno presso la Piazza del Municipio (nel pomeriggio)','Brindisi di inizio anno presso la Piazza del Municipio (nel pomeriggio)

Organizzatore: Comune di Lauco.','assets/img/eventi.jpg');

-- Corregge l'eventuale vecchio inserimento manuale errato di San Rocco (14 agosto).
-- Se esiste gia' la riga corretta, elimina solo il duplicato obsoleto e le sue fonti.
UPDATE `eventi` AS e
SET
  e.`titolo` = 'San Rocco - S. Messa e festeggiamenti',
  e.`slug` = 'san-rocco-2026',
  e.`data_evento` = '2026-08-16',
  e.`localita` = 'Lauco',
  e.`categoria` = 'Eventi',
  e.`excerpt` = 'San Rocco - S. Messa ore 11.15 e processione / festeggiamenti',
  e.`contenuto` = 'San Rocco - S. Messa ore 11.15 e processione / festeggiamenti

Organizzatore: Parrocchia.',
  e.`cover_image` = 'assets/img/eventi.jpg',
  e.`pubblicato` = 1
WHERE
  (e.`slug` = 'sagra-di-san-rocco-2026'
   OR (e.`titolo` = 'Sagra di San Rocco' AND e.`data_evento` = '2026-08-14'))
  AND NOT EXISTS (
    SELECT 1
    FROM (SELECT `id` FROM `eventi` WHERE `slug` = 'san-rocco-2026') AS correct_san_rocco
  );

DELETE ef
FROM `eventi_fonti` AS ef
JOIN `eventi` AS old_event
  ON old_event.`id` = ef.`evento_id`
WHERE
  (old_event.`slug` = 'sagra-di-san-rocco-2026'
   OR (old_event.`titolo` = 'Sagra di San Rocco' AND old_event.`data_evento` = '2026-08-14'))
  AND EXISTS (
    SELECT 1
    FROM (SELECT `id` FROM `eventi` WHERE `slug` = 'san-rocco-2026') AS correct_san_rocco
  );

DELETE old_event
FROM `eventi` AS old_event
WHERE
  (old_event.`slug` = 'sagra-di-san-rocco-2026'
   OR (old_event.`titolo` = 'Sagra di San Rocco' AND old_event.`data_evento` = '2026-08-14'))
  AND EXISTS (
    SELECT 1
    FROM (SELECT `id` FROM `eventi` WHERE `slug` = 'san-rocco-2026') AS correct_san_rocco
  );

-- Inserisce solo gli eventi realmente mancanti.
INSERT INTO `eventi`
  (`titolo`,`slug`,`data_evento`,`localita`,`categoria`,`excerpt`,`contenuto`,`cover_image`,`ordine`,`pubblicato`)
SELECT
  t.`titolo`, t.`slug`, t.`data_evento`, t.`localita`, t.`categoria`,
  t.`excerpt`, t.`contenuto`, t.`cover_image`, 0, 1
FROM `tmp_lauco_calendario_2026` AS t
WHERE NOT EXISTS (
  SELECT 1
  FROM `eventi` AS e
  WHERE e.`slug` = t.`slug`
     OR (e.`titolo` = t.`titolo` AND e.`data_evento` = t.`data_evento`)
);

-- Se un evento era gia' stato creato manualmente/da import, completa i dati e assegna una cover locale.
UPDATE `eventi` AS e
JOIN `tmp_lauco_calendario_2026` AS t
  ON e.`slug` = t.`slug`
  OR (e.`titolo` = t.`titolo` AND e.`data_evento` = t.`data_evento`)
SET
  e.`localita` = t.`localita`,
  e.`categoria` = t.`categoria`,
  e.`excerpt` = t.`excerpt`,
  e.`contenuto` = t.`contenuto`,
  e.`cover_image` = t.`cover_image`,
  e.`pubblicato` = 1;

-- Collega ogni evento alla locandina ufficiale del Comune.
INSERT INTO `eventi_fonti` (`evento_id`,`source_key`,`source_url`)
SELECT DISTINCT
  e.`id`,
  'ai_calendar_lauco',
  'https://www.comune.lauco.ud.it/media/files/030047/attachment/Locandina_Eventi_Lauco_2026_30x60_colore.pdf'
FROM `eventi` AS e
JOIN `tmp_lauco_calendario_2026` AS t
  ON e.`slug` = t.`slug`
  OR (e.`titolo` = t.`titolo` AND e.`data_evento` = t.`data_evento`)
ON DUPLICATE KEY UPDATE
  `source_url` = VALUES(`source_url`);

DROP TEMPORARY TABLE IF EXISTS `tmp_lauco_calendario_2026`;
