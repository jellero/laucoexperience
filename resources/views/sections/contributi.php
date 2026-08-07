<?php
/*
 * Sezione Home: Contributi e Segnalazioni
 *
 * Da includere in home con:
 * <?php require LAUCO_VIEW_PATH . '/sections/contributi.php'; ?>
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


<section id="call-to-action-partecipa" class="elementor-section elementor-top-section elementor-section-full_width elementor-section-height-default">
    <div class="elementor-container elementor-column-gap-no">
        <div class="elementor-column elementor-col-100 elementor-top-column">
            <div class="elementor-widget-wrap elementor-element-populated">
                <div class="elementor-widget-container">

                    <div class="row text margin-leftright-null color-background cta-partecipa">
                        <div class="container">
                            <div class="row margin-leftright-null">

                                <div class="col-md-8 col-sm-12">
                                    <h4 class="big white margin-bottom-small">
                                        Vuoi dare una mano concreta?
                                    </h4>

                                    <p class="white cta-partecipa-text">
                                        Se vuoi contribuire alla cura dei sentieri, alla pulizia del territorio,
                                        alla manutenzione leggera dei percorsi o alla valorizzazione dell’immagine di Lauco,
                                        scrivici: verrai ricontattato e potrai essere inserito nel gruppo operativo dedicato.
                                    </p>

                                    <p class="white cta-partecipa-subtext">
                                        Anche una piccola disponibilità può essere utile: una segnalazione, una fotografia,
                                        una giornata di pulizia, un controllo sul posto o un aggiornamento sui percorsi
                                        aiutano a rendere Lauco Experience più vivo, preciso e vicino al territorio.
                                    </p>
                                </div>

                                <div class="col-md-4 col-sm-12 text-center cta-partecipa-actions">
                                    <div class="cta-partecipa-box">
                                        <h5 class="white">Partecipa al gruppo</h5>

                                        <p class="white">
                                            Lascia il tuo contatto e indica come vorresti contribuire.
                                            Potremo organizzare attività, aggiornamenti e comunicazioni operative.
                                        </p>

                                        <a href="/contatti" class="btn-pro simple white">
                                            Scrivici
                                        </a>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <style>
                        #call-to-action-partecipa .cta-partecipa {
                            padding: 70px 0;
                        }

                        #call-to-action-partecipa .cta-partecipa h4 {
                            line-height: 1.25;
                        }

                        #call-to-action-partecipa .cta-partecipa-text {
                            font-size: 18px;
                            line-height: 1.75;
                            max-width: 850px;
                            margin-bottom: 18px;
                            opacity: .96;
                        }

                        #call-to-action-partecipa .cta-partecipa-subtext {
                            font-size: 15px;
                            line-height: 1.7;
                            max-width: 820px;
                            opacity: .86;
                            margin-bottom: 0;
                        }

                        #call-to-action-partecipa .cta-partecipa-actions {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        #call-to-action-partecipa .cta-partecipa-box {
                            width: 100%;
                            padding: 34px 28px;
                            border: 1px solid rgba(255,255,255,.28);
                            background: rgba(255,255,255,.08);
                        }

                        #call-to-action-partecipa .cta-partecipa-box h5 {
                            margin-top: 0;
                            margin-bottom: 14px;
                            font-size: 20px;
                            font-weight: 700;
                        }

                        #call-to-action-partecipa .cta-partecipa-box p {
                            font-size: 14px;
                            line-height: 1.65;
                            margin-bottom: 22px;
                            opacity: .92;
                        }

                        #call-to-action-partecipa .cta-secondary-link {
                            margin-top: 10px;
                        }

                        @media (max-width: 991px) {
                            #call-to-action-partecipa .cta-partecipa {
                                padding: 55px 0;
                            }

                            #call-to-action-partecipa .cta-partecipa-actions {
                                margin-top: 28px;
                            }

                            #call-to-action-partecipa .cta-partecipa-box {
                                max-width: 520px;
                                margin: 0 auto;
                            }
                        }

                        @media (max-width: 767px) {
                            #call-to-action-partecipa .cta-partecipa {
                                padding: 45px 0;
                            }

                            #call-to-action-partecipa .cta-partecipa-text {
                                font-size: 16px;
                            }

                            #call-to-action-partecipa .cta-partecipa-box {
                                padding: 28px 22px;
                            }
                        }
                    </style>

                </div>
            </div>
        </div>
    </div>
</section>

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
