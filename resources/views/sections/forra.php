<!-- Forra del Vinadia -->
<style>
    #forra-home {
        overflow: hidden;
    }

    #forra-home .forra-home-image {
        min-height: 420px;
        background-image: url('/assets/img/sentieri.webp');
        background-size: cover;
        background-position: center;
    }

    #forra-home .forra-home-content {
        min-height: 420px;
        display: flex;
        align-items: center;
    }

    #forra-home .forra-home-inner {
        width: 100%;
        padding: 55px 60px;
    }

    #forra-home .forra-home-safety {
        margin: 22px 0;
        padding: 16px 18px;
        border-left: 4px solid #9b1c1c;
        background: rgba(155, 28, 28, .06);
        line-height: 1.65;
    }

    #forra-home .forra-home-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    #forra-home .forra-home-actions .btn-alt {
        margin: 0;
    }

    @media (max-width: 991px) {
        #forra-home .forra-home-image {
            min-height: 320px;
        }

        #forra-home .forra-home-content {
            min-height: auto;
        }

        #forra-home .forra-home-inner {
            padding: 40px 30px;
        }
    }
</style>

<div id="forra-home" class="row margin-leftright-null grey-background">
    <div class="col-md-6 padding-leftright-null">
        <div class="forra-home-image" role="img" aria-label="Forra del Vinadia"></div>
    </div>

    <div class="col-md-6 padding-leftright-null forra-home-content">
        <div class="forra-home-inner">
            <h2 class="margin-bottom-null title line left">Forra del Vinadia</h2>
            <p class="heading left grey">Una delle esperienze naturalistiche più riconoscibili del territorio di Lauco.</p>

            <p>
                Scopri la Forra partendo da Vinaio: informazioni essenziali per orientarti, indicazioni di sicurezza,
                collegamenti alla mappa di Lauco Experience e ai sentieri vicini.
            </p>

            <div class="forra-home-safety">
                <strong>Sicurezza prima di tutto.</strong> Prima della visita verifica meteo, condizioni dell’acqua e
                indicazioni aggiornate. Per l’interno della gola è raccomandato l’accompagnamento di una guida.
            </div>

            <div class="forra-home-actions">
                <a href="/forra" class="btn-alt small active">Scopri la Forra</a>
                <a href="https://www.forravinadia.it/" class="btn-alt small" target="_blank" rel="noopener noreferrer">Sito dedicato</a>
            </div>
        </div>
    </div>
</div>
<!-- END Forra del Vinadia -->
