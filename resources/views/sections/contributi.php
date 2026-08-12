<?php
/*
 * Sezione Home: Contributi e Segnalazioni.
 * Le due funzionalità restano separate dal nuovo modulo volontariato.
 */
?>
<section id="contributi-segnalazioni" class="">
    <div class="container">
        <div class="row margin-leftright-null">
            <div class="col-md-12 padding-leftright-null text text-center">
                <h2 class="margin-bottom-null title line center">Partecipa al progetto</h2>
                <p class="heading center grey margin-bottom">
                    Contributi e segnalazioni aiutano a mantenere Lauco Experience vivo, utile e aggiornato.
                </p>
            </div>
        </div>

        <div class="row margin-leftright-null">
            <div class="col-md-12 text">
                <div class="contribution-grid">
                    <a class="contribution-card" href="/contribuisci">
                        <span class="code">+</span>
                        <h3>Contribuisci</h3>
                        <p>
                            Invia fotografie, descrizioni, tracce, punti di interesse o informazioni utili
                            per arricchire le schede del sito e raccontare meglio il territorio di Lauco.
                        </p>
                        <span class="card-link">Invia un contributo</span>
                    </a>

                    <a class="contribution-card" href="/segnala-problema">
                        <span class="code">!</span>
                        <h3>Segnalazioni</h3>
                        <p>
                            Segnala errori, tratti non percorribili, problemi di segnaletica, ostacoli,
                            variazioni del percorso o informazioni da correggere e aggiornare.
                        </p>
                        <span class="card-link">Segnala un problema</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require LAUCO_VIEW_PATH . '/sections/volontariato.php'; ?>

<style>
    #contributi-segnalazioni {
        padding: 85px 0;
    }

    #contributi-segnalazioni .contribution-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 28px;
        margin-top: 18px;
    }

    #contributi-segnalazioni .contribution-card {
        display: block;
        position: relative;
        background: #fff;
        padding: 34px 32px 32px;
        min-height: 280px;
        color: inherit;
        text-decoration: none;
        box-shadow: 0 10px 30px rgba(0,0,0,.06);
        border: 1px solid rgba(0,0,0,.04);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }

    #contributi-segnalazioni .contribution-card:hover,
    #contributi-segnalazioni .contribution-card:focus {
        color: inherit;
        text-decoration: none;
        transform: translateY(-4px);
        box-shadow: 0 18px 42px rgba(0,0,0,.12);
        border-color: rgba(0,0,0,.08);
    }

    #contributi-segnalazioni .contribution-card .code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 58px;
        height: 58px;
        margin-bottom: 22px;
        border-radius: 50%;
        background: #222;
        color: #fff;
        font-size: 26px;
        font-weight: 700;
        line-height: 1;
    }

    #contributi-segnalazioni .contribution-card h3 {
        margin-top: 0;
        margin-bottom: 14px;
        color: #222;
    }

    #contributi-segnalazioni .contribution-card p {
        color: #666;
        line-height: 1.75;
        margin-bottom: 24px;
    }

    #contributi-segnalazioni .contribution-card .card-link {
        display: inline-block;
        color: #222;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        border-bottom: 2px solid #222;
        padding-bottom: 5px;
    }

    @media (max-width: 767px) {
        #contributi-segnalazioni {
            padding: 60px 0;
        }

        #contributi-segnalazioni .contribution-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        #contributi-segnalazioni .contribution-card {
            min-height: auto;
            padding: 28px 24px;
        }
    }
</style>
