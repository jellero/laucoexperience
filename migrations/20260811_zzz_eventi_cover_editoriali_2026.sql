-- Cover editoriali definitive per gli eventi di Lauco.
-- Usa esclusivamente immagini locali gia' presenti e valide nel progetto.
-- Festa del Miele e' esclusa esplicitamente e mantiene la propria cover.
-- Migrazione additiva: non modificare le migrazioni precedenti gia' applicate.

UPDATE `eventi`
SET `cover_image` = CASE
    WHEN `slug` IN (
        'presentazione-acque-vive-cristina-noacco-2026',
        'teatro-friulano-teatri-di-pais-2026',
        'celtic-fest-lauco-2026',
        'la-pelle-della-terra-trava-2026',
        'concerto-coro-polifonico-piattaforma-2026',
        'concerto-santa-lucia-polifonico-2026'
    ) THEN 'assets/img/eventi/cultura.jpg'

    WHEN `slug` IN (
        'pentecoste-40-ore-comunioni-2026',
        'sant-antonio-avaglio-2026',
        'san-luigi-trava-2026',
        'san-giovanni-buttea-2026',
        'san-rocco-2026',
        'sagra-di-san-rocco-2026',
        'madonna-delle-grazie-allegnidis-2026',
        'madonna-cintura-vinaio-2026'
    ) THEN 'assets/img/eventi/religioso.jpg'

    WHEN `slug` IN (
        'festa-croce-monte-arvenis-2026',
        'festa-degli-alberi-lauco-rosegg-2026',
        'escursione-solstizio-estate-museo-2026',
        'escursione-celtic-fest-museo-2026',
        'escursione-santuario-trava-agosto-2026',
        'escursione-santuario-trava-settembre-2026',
        'foliage-autunno-lauco-2026',
        'fiaccolata-dei-madins-2026'
    ) OR `slug` LIKE 'escursione-%'
      THEN 'assets/img/eventi/outdoor.webp'

    WHEN `slug` IN (
        'vertical-race-cronoradime-2026',
        'rally-valli-della-carnia-lauco-2026'
    ) THEN 'assets/img/eventi/sport.jpg'

    WHEN `slug` IN (
        'raduno-provinciale-alpini-2026',
        'maina-dal-cret-2026',
        'unita-nazionale-costituzione-buttea-2026'
    ) THEN 'assets/img/eventi/alpini.jpg'

    WHEN `slug` IN (
        'trava-open-2026',
        'sagra-paesana-trava-2026'
    ) THEN 'assets/img/eventi/trava.jpg'

    WHEN `slug` IN (
        'apertura-chiosco-piattaforma-panoramica-2026',
        'concerto-coro-polifonico-piattaforma-2026'
    ) THEN 'assets/img/eventi/panoramica.jpg'

    WHEN `slug` = 'festeggiamenti-primo-maggio-porteal-2026'
      THEN 'assets/img/eventi/porteal.jpg'

    WHEN `slug` IN (
        'apertura-chiosco-vinaio-giugno-2026',
        'gemellaggio-vinaio-runchia-2026',
        'evento-chiosco-vinaio-5-luglio-2026',
        'eventi-chiosco-vinaio-12-luglio-2026',
        'apertura-chiosco-vinaio-ferragosto-2026',
        'sagra-paesana-allegnidis-2026'
    ) THEN 'assets/img/eventi/territorio.jpg'

    ELSE 'assets/img/eventi/comunita.jpg'
END
WHERE LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%'
  AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';
