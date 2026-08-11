-- Inserimento manuale degli eventi futuri 2026 verificati da fonti pubbliche.
-- Festa del Miele di Montagna esclusa intenzionalmente: già presente nel CMS.

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
  'Sagra di San Rocco',
  'sagra-di-san-rocco-2026',
  '2026-08-14',
  'Lauco',
  'Tradizioni, Eventi',
  'Dal 14 al 18 agosto 2026 torna a Lauco la Sagra di San Rocco.',
  'Dal 14 al 18 agosto 2026 Lauco ospita la Sagra di San Rocco, appuntamento della tradizione locale dedicato al patrono del paese.',
  'assets/img/eventi.jpg',
  0,
  1
WHERE NOT EXISTS (
  SELECT 1
  FROM `eventi`
  WHERE `slug` = 'sagra-di-san-rocco-2026'
     OR (`titolo` = 'Sagra di San Rocco' AND `data_evento` = '2026-08-14')
);

SET @san_rocco_2026_id := (
  SELECT `id`
  FROM `eventi`
  WHERE `slug` = 'sagra-di-san-rocco-2026'
     OR (`titolo` = 'Sagra di San Rocco' AND `data_evento` = '2026-08-14')
  ORDER BY `id` ASC
  LIMIT 1
);

INSERT INTO `eventi_fonti` (`evento_id`, `source_key`, `source_url`)
SELECT
  @san_rocco_2026_id,
  'manual_proloco',
  'https://prolocoregionefvg.it/eventi-delle-associate/sagra-di-san-rocco-3/'
WHERE @san_rocco_2026_id IS NOT NULL
ON DUPLICATE KEY UPDATE
  `source_url` = VALUES(`source_url`);
