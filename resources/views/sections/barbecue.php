<?php
require_once LAUCO_ROOT . '/inc/barbecue-images.php';

$barbecueVinaioImage = lauco_barbecue_image_data_uri('vinaio');
$barbecuePortealImage = lauco_barbecue_image_data_uri('porteal');
?>
<style>
    #barbecue-home {padding:70px 0;background:#f7f7f7}
    #barbecue-home .barbecue-intro {max-width:760px;margin:0 auto 34px;text-align:center}
    #barbecue-home .barbecue-intro p {font-size:17px;line-height:1.7;color:#666}
    #barbecue-home .barbecue-card {background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.06);height:100%;margin-bottom:24px}
    #barbecue-home .barbecue-card img {display:block;width:100%;height:300px;object-fit:cover}
    #barbecue-home .barbecue-card-body {padding:22px 24px 25px}
    #barbecue-home .barbecue-card-body h3 {margin:0 0 8px}
    #barbecue-home .barbecue-card-body p {margin:0;color:#666;line-height:1.65}
    #barbecue-home .barbecue-credit {margin:12px auto 24px;text-align:center;color:#666}
    #barbecue-home .barbecue-action {text-align:center}
    @media (max-width:767px) {#barbecue-home{padding:48px 0}#barbecue-home .barbecue-card img{height:auto}}
</style>

<section id="barbecue-home">
    <div class="container">
        <div class="barbecue-intro">
            <h2 class="margin-bottom-null title line center">Barbecue ad uso comune</h2>
            <p class="heading grey">Nuovi punti attrezzati a Porteal e Vinaio. Prossimamente anche in Val di Lauco.</p>
            <p>Due barbecue a disposizione della comunità e di chi frequenta il territorio, pensati come punti di sosta e convivialità all’aria aperta.</p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <article class="barbecue-card">
                    <?php if ($barbecueVinaioImage !== ''): ?>
                        <img src="<?= htmlspecialchars($barbecueVinaioImage, ENT_QUOTES, 'UTF-8') ?>" width="1000" height="750" loading="lazy" alt="Barbecue ad uso comune a Vinaio vicino al torrente e al ponte">
                    <?php endif; ?>
                    <div class="barbecue-card-body">
                        <h3>Vinaio</h3>
                        <p>Il barbecue ad uso comune installato nell’area vicina al torrente e al ponte.</p>
                    </div>
                </article>
            </div>
            <div class="col-md-6">
                <article class="barbecue-card">
                    <?php if ($barbecuePortealImage !== ''): ?>
                        <img src="<?= htmlspecialchars($barbecuePortealImage, ENT_QUOTES, 'UTF-8') ?>" width="1000" height="750" loading="lazy" alt="Barbecue ad uso comune a Porteal con vista sul paesaggio montano">
                    <?php endif; ?>
                    <div class="barbecue-card-body">
                        <h3>Porteal</h3>
                        <p>Il barbecue ad uso comune inserito in un contesto aperto e panoramico.</p>
                    </div>
                </article>
            </div>
        </div>

        <p class="barbecue-credit"><strong>Un omaggio del Giro degli Auguri, con il supporto di Valerio.</strong></p>
        <div class="barbecue-action"><a href="/barbecue" class="btn-alt small active">Scopri le aree barbecue</a></div>
    </div>
</section>
