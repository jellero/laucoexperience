-- Ripristino definitivo delle cover eventi dopo la rimozione delle PNG danneggiate.
-- Tutti gli eventi vengono riallineati a immagini reali e stabili gia' presenti nel progetto.
-- Festa del Miele e' esclusa esplicitamente per slug e titolo e mantiene la propria cover.
-- Questa migrazione deve restare successiva alle precedenti migrazioni cover del 2026.

UPDATE `eventi`
SET `cover_image` = CASE
    WHEN `slug` = 'festeggiamenti-primo-maggio-porteal-2026'
        THEN 'uploads/luoghi/loc-porteal-20260610213800-b92b1a.jpg'

    WHEN `slug` = 'vertical-race-cronoradime-2026'
        THEN 'assets/img/cronoradima.jpg'

    WHEN `slug` IN (
        'festa-croce-monte-arvenis-2026',
        'festa-degli-alberi-lauco-rosegg-2026',
        'escursione-solstizio-estate-museo-2026',
        'escursione-celtic-fest-museo-2026',
        'foliage-autunno-lauco-2026',
        'fiaccolata-dei-madins-2026'
    ) OR `slug` LIKE 'escursione-%'
        THEN 'assets/img/sentieri.webp'

    WHEN `slug` IN (
        'raduno-provinciale-alpini-2026',
        'maina-dal-cret-2026',
        'unita-nazionale-costituzione-buttea-2026'
    )
        THEN 'assets/img/alpini.jpg'

    WHEN `slug` IN (
        'san-luigi-trava-2026',
        'trava-open-2026',
        'escursione-santuario-trava-agosto-2026',
        'sagra-paesana-trava-2026',
        'escursione-santuario-trava-settembre-2026'
    )
        THEN 'uploads/luoghi/chiesa-trava-20260610213911-b2a7c5.jpg'

    WHEN `slug` IN (
        'apertura-chiosco-piattaforma-panoramica-2026',
        'concerto-coro-polifonico-piattaforma-2026'
    )
        THEN 'uploads/luoghi/terrazza-20260610214112-b51f01.jpg'

    ELSE 'assets/img/eventi.jpg'
END
WHERE LOWER(COALESCE(`slug`, '')) NOT LIKE '%miele%'
  AND LOWER(COALESCE(`titolo`, '')) NOT LIKE '%miele%';
