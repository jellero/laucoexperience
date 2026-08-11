-- Inserimento manuale degli eventi futuri 2026 verificati dal calendario ufficiale del Comune di Lauco.
-- Festa del Miele di Alta Montagna esclusa intenzionalmente: già presente nel CMS.

INSERT INTO `eventi` (
  `titolo`,
  `slug`,
  `data_evento`,
  `localita`,
  `categoria`,
  `excerpt`,
  `contenuto`,
  `cover_image`,
  `ordine`,
  `pubblicato`
)
SELECT
  'San Rocco - S. Messa e festeggiamenti',
  'san-rocco-2026',
  '2026-08-16',
  'Lauco',
  'Tradizioni, Eventi',
  'Il 16 agosto 2026 a Lauco: S. Messa alle 11.15, processione e festeggiamenti per San Rocco.',
  'San Rocco a Lauco: S. Messa alle ore 11.15, processione e festeggiamenti.',
  'assets/img/eventi.jpg',
  0,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `eventi`
  WHERE `slug` = 'san-rocco-2026'
     OR (`titolo` LIKE 'San Rocco%' AND `data_evento` = '2026-08-16')
);

SET @san_rocco_2026_id := (
  SELECT `id` FROM `eventi`
  WHERE `slug` = 'san-rocco-2026'
     OR (`titolo` LIKE 'San Rocco%' AND `data_evento` = '2026-08-16')
  ORDER BY `id` ASC LIMIT 1
);

INSERT INTO `eventi_fonti` (`evento_id`, `source_key`, `source_url`)
SELECT @san_rocco_2026_id, 'manual_comune_lauco', 'https://www.comune.lauco.ud.it/'
WHERE @san_rocco_2026_id IS NOT NULL
ON DUPLICATE KEY UPDATE `source_url` = VALUES(`source_url`);
