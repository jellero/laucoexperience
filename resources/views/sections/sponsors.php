<?php
$sponsors = [];
try {
    $sponsors = $pdo->query('SELECT image_path, alt_text, url FROM sponsors WHERE pubblicato = 1 ORDER BY ordine ASC, id ASC')->fetchAll();
} catch (Throwable $e) {
    error_log('[Lauco Sponsor] ' . $e->getMessage());
    $sponsors = [[
        'image_path' => 'assets/img/logocomune.png',
        'alt_text' => 'Comune di Lauco',
        'url' => null,
    ]];
}
?>

<?php if ($sponsors !== []): ?>
<!-- Sponsor -->
<div class="row no-margin">
    <div class="container text">
        <div class="col-md-12 sponsor-carousel padding-leftright-null">
            <?php foreach ($sponsors as $sponsor): ?>
                <?php
                $url = trim((string) ($sponsor['url'] ?? ''));
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
                $hasSafeUrl = filter_var($url, FILTER_VALIDATE_URL) !== false && in_array($scheme, ['http', 'https'], true);
                ?>
                <div class="item">
                    <?php if ($hasSafeUrl): ?>
                        <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">
                    <?php endif; ?>
                    <img class="center" src="/<?= e(ltrim((string) $sponsor['image_path'], '/')) ?>" alt="<?= e($sponsor['alt_text']) ?>" loading="lazy">
                    <?php if ($hasSafeUrl): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- END Sponsor -->
<?php endif; ?>
