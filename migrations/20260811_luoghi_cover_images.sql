-- Copertine territoriali per le schede luogo che non hanno ancora una foto.
-- Le immagini già caricate dal CMS vengono preservate.

UPDATE `luoghi`
SET `cover_image` = CASE `slug`
  WHEN 'lauco' THEN 'assets/img/luoghi/lauco.png'
  WHEN 'allegnidis' THEN 'assets/img/luoghi/allegnidis.png'
  WHEN 'avaglio' THEN 'assets/img/luoghi/avaglio.png'
  WHEN 'buttea' THEN 'assets/img/luoghi/buttea.png'
  WHEN 'chiassis' THEN 'assets/img/luoghi/chiassis.png'
  WHEN 'trava' THEN 'assets/img/luoghi/trava.png'
  WHEN 'vinaio' THEN 'assets/img/luoghi/vinaio.png'
  ELSE `cover_image`
END
WHERE `slug` IN (
  'lauco',
  'allegnidis',
  'avaglio',
  'buttea',
  'chiassis',
  'trava',
  'vinaio'
)
AND (`cover_image` IS NULL OR TRIM(`cover_image`) = '');
