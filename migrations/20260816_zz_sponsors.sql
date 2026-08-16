CREATE TABLE IF NOT EXISTS sponsors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    url VARCHAR(2048) NULL,
    ordine INT NOT NULL DEFAULT 0,
    pubblicato TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sponsors_pubblicato_ordine (pubblicato, ordine, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sponsors (image_path, alt_text, url, ordine, pubblicato)
SELECT 'assets/img/logocomune.png', 'Comune di Lauco', NULL, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM sponsors);
