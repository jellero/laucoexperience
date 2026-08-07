<?php
require_once __DIR__ . '/../inc/db.php';

function event_date_label(?string $date): string
{
    if (!$date) {
        return '';
    }

    $ts = strtotime($date);
    return $ts ? date('d.m.Y', $ts) : '';
}

function event_categories(?string $category): array
{
    if (!$category) {
        return ['Eventi'];
    }

    $items = array_filter(array_map('trim', explode(',', $category)));

    if (!$items) {
        return ['Eventi'];
    }

    if (!in_array('Eventi', $items, true)) {
        $items[] = 'Eventi';
    }

    return array_slice($items, 0, 2);
}

$stmt = $pdo->query("
    SELECT *
    FROM eventi
    WHERE pubblicato = 1
    ORDER BY ordine ASC, data_evento DESC, created_at DESC
    LIMIT 2
");

$eventiHome = $stmt->fetchAll();
?>

<style>
    #news .home-event-card article {
        height: 300px;
        overflow: hidden;
        background: #fff;
    }

    #news .home-event-card .image {
        height: 300px;
        background-size: cover;
        background-position: center;
    }

    #news .home-event-card .content {
        height: 300px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    #news .home-event-card h3 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 58px;
        margin-bottom: 8px;
    }

    #news .home-event-card p {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 66px;
        margin-bottom: 12px;
    }

    #news .home-event-card .date {
        display: block;
        margin-bottom: 10px;
    }

    #news .home-event-card .category {
        margin-top: 4px;
    }

    @media (max-width: 991px) {
        #news .home-event-card article,
        #news .home-event-card .image,
        #news .home-event-card .content {
            height: auto;
        }

        #news .home-event-card .image {
            min-height: 260px;
        }

        #news .home-event-card .content {
            min-height: 260px;
        }
    }
</style>

<!-- News -->
<div class="row margin-leftright-null grey-background">
    <div class="container">
        <div class="col-md-12 padding-leftright-null text padding-bottom-null text-center">
            <h2 class="margin-bottom-null title line center">EVENTI</h2>
            <p class="heading center grey margin-bottom-null">
                Ogni evento è un’occasione per vivere il territorio e sentirsi parte di qualcosa di più grande.
            </p>
        </div>

        <div class="col-md-12 text" id="news">
            <?php if (!$eventiHome): ?>
                <p class="text-center margin-bottom-null">Al momento non ci sono eventi pubblicati.</p>
            <?php endif; ?>

            <?php foreach ($eventiHome as $evento): ?>
                <?php
                    $cover = $evento['cover_image'] ?: 'assets/img/cronoradima.jpg';
                    $date = event_date_label($evento['data_evento'] ?? null);
                    $categories = event_categories($evento['categoria'] ?? '');
                ?>

                <div class="col-sm-6 single-news horizontal-news home-event-card">
                    <article>
                        <div class="col-md-6 padding-leftright-null">
                            <div class="image" style="background-image:url(<?= e($cover) ?>)"></div>
                        </div>

                        <div class="col-md-6 padding-leftright-null">
                            <div class="content">
                                <h3><?= e($evento['titolo']) ?></h3>

                                <?php if ($date): ?>
                                    <span class="date"><?= e($date) ?></span>
                                <?php endif; ?>

                                <p><?= e($evento['excerpt'] ?: $evento['localita']) ?></p>

                                <div>
                                    <?php foreach ($categories as $category): ?>
                                        <span class="category"><?= e($category) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <a href="evento.php?slug=<?= urlencode($evento['slug']) ?>" class="link" aria-label="<?= e($evento['titolo']) ?>"></a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($eventiHome): ?>
            <div class="col-md-12 text text-center padding-top-null">
                <a href="eventi.php" class="btn-alt small margin-null active">Tutti gli eventi</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- END News -->
