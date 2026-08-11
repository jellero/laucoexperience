-- Aggiorna le cover degli eventi con immagini verificate e pertinenti.
-- Festa del Miele e' esclusa: questa migrazione aggiorna esclusivamente slug espliciti e applica inoltre un doppio safeguard su slug/titolo.
-- Migrazione additiva: non modificare migrazioni precedenti gia' applicate.

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/acque-vive.jpg'
WHERE `slug` IN ('presentazione-acque-vive-cristina-noacco-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/alpini-adunata.jpg'
WHERE `slug` IN ('raduno-provinciale-alpini-2026','unita-nazionale-costituzione-buttea-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/autoemoteca.jpg'
WHERE `slug` IN ('autoemoteca-maggio-2026','autoemoteca-ottobre-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/avaglio.jpg'
WHERE `slug` IN ('sant-antonio-avaglio-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/cidules.jpg'
WHERE `slug` IN ('las-cidules-buttea-2026','las-cidules-uerpa-2026','las-cidules-avaglio-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/coro.jpg'
WHERE `slug` IN ('concerto-santa-lucia-polifonico-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/cronoradima.jpg'
WHERE `slug` IN ('vertical-race-cronoradime-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/fiaccolata.jpg'
WHERE `slug` IN ('fiaccolata-dei-madins-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/lauco-foliage.jpg'
WHERE `slug` IN ('foliage-autunno-lauco-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/lauco-inverno.jpg'
WHERE `slug` IN ('giro-degli-auguri-12-13-dicembre-2026','giro-degli-auguri-19-20-dicembre-2026','brindisi-inizio-anno-lauco-2027')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/lauco-panorama.jpg'
WHERE `slug` IN ('pentecoste-40-ore-comunioni-2026','san-rocco-2026','sagra-di-san-rocco-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/lauco-territorio.jpg'
WHERE `slug` IN ('festeggiamenti-primo-maggio-porteal-2026','apertura-chiosco-vinaio-giugno-2026','gemellaggio-vinaio-runchia-2026','escursione-solstizio-estate-museo-2026','san-giovanni-buttea-2026','evento-chiosco-vinaio-5-luglio-2026','eventi-chiosco-vinaio-12-luglio-2026','apertura-chiosco-vinaio-ferragosto-2026','sagra-paesana-allegnidis-2026','madonna-delle-grazie-allegnidis-2026','madonna-cintura-vinaio-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/maina.jpg'
WHERE `slug` IN ('maina-dal-cret-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/monte-arvenis.jpg'
WHERE `slug` IN ('festa-croce-monte-arvenis-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/piattaforma-panoramica.jpg'
WHERE `slug` IN ('apertura-chiosco-piattaforma-panoramica-2026','concerto-coro-polifonico-piattaforma-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/rally.jpg'
WHERE `slug` IN ('rally-valli-della-carnia-lauco-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/rosegg-gemellaggio.jpg'
WHERE `slug` IN ('festa-degli-alberi-lauco-rosegg-2026','corona-avvento-rosegg-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/teatro.jpg'
WHERE `slug` IN ('teatro-friulano-teatri-di-pais-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/tombe-curs.jpg'
WHERE `slug` IN ('escursione-celtic-fest-museo-2026','celtic-fest-lauco-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';

UPDATE `eventi`
SET `cover_image` = 'assets/img/eventi/trava-santuario.jpg'
WHERE `slug` IN ('san-luigi-trava-2026','trava-open-2026','escursione-santuario-trava-agosto-2026','la-pelle-della-terra-trava-2026','sagra-paesana-trava-2026','escursione-santuario-trava-settembre-2026')
  AND LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%' AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';
