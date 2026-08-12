<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$isAdmin = admin_can('admin.all');
$canCommunications = admin_can('communications.respond');
$canWhatsApp = admin_can('whatsapp.manage');

function dash_count(PDO $pdo, string $sql): array
{
    try {
        $stmt = $pdo->query($sql);
        return [
            'value' => (int) $stmt->fetchColumn(),
            'ok' => true,
        ];
    } catch (Throwable $e) {
        return [
            'value' => 0,
            'ok' => false,
        ];
    }
}

function dash_rows(PDO $pdo, string $sql): array
{
    try {
        $stmt = $pdo->query($sql);
        return [
            'rows' => $stmt->fetchAll() ?: [],
            'ok' => true,
        ];
    } catch (Throwable $e) {
        return [
            'rows' => [],
            'ok' => false,
        ];
    }
}

function dash_date($value): string
{
    if (!$value) {
        return '-';
    }

    $ts = strtotime((string) $value);
    return $ts ? date('d.m.Y', $ts) : '-';
}

function dash_missing(array $result): string
{
    return $result['ok'] ? '' : '<span class="dash-warning">non disponibile</span>';
}

$counts = [
    'percorsi' => dash_count($pdo, 'SELECT COUNT(*) FROM percorsi'),
    'percorsi_pubblicati' => dash_count($pdo, 'SELECT COUNT(*) FROM percorsi WHERE pubblicato = 1'),
    'percorsi_speciali' => dash_count($pdo, 'SELECT COUNT(*) FROM percorsi WHERE speciale = 1'),
    'eventi' => dash_count($pdo, 'SELECT COUNT(*) FROM eventi'),
    'eventi_pubblicati' => dash_count($pdo, 'SELECT COUNT(*) FROM eventi WHERE pubblicato = 1'),
    'luoghi' => dash_count($pdo, 'SELECT COUNT(*) FROM luoghi'),
    'luoghi_pubblicati' => dash_count($pdo, 'SELECT COUNT(*) FROM luoghi WHERE pubblicato = 1'),
    'luoghi_evidenza' => dash_count($pdo, 'SELECT COUNT(*) FROM luoghi WHERE in_evidenza = 1'),
    'messaggi_contatti' => dash_count($pdo, 'SELECT COUNT(*) FROM contatti_messaggi'),
    'messaggi_contatti_nuovi' => dash_count($pdo, "SELECT COUNT(*) FROM contatti_messaggi WHERE stato = 'nuovo'"),
    'contributi' => dash_count($pdo, 'SELECT COUNT(*) FROM contributi'),
    'contributi_nuovi' => dash_count($pdo, "SELECT COUNT(*) FROM contributi WHERE stato = 'nuovo'"),
    'galleria' => dash_count($pdo, 'SELECT COUNT(*) FROM galleria'),
    'slider' => dash_count($pdo, 'SELECT COUNT(*) FROM home_slider'),
    'segnalazioni' => dash_count($pdo, 'SELECT COUNT(*) FROM segnalazioni_problemi'),
    'segnalazioni_nuove' => dash_count($pdo, "SELECT COUNT(*) FROM segnalazioni_problemi WHERE stato = 'nuova'"),
    'utenti' => dash_count($pdo, 'SELECT COUNT(*) FROM utenti'),
    'volontari' => dash_count($pdo, 'SELECT COUNT(*) FROM volontari'),
    'volontari_attivi' => dash_count($pdo, "SELECT COUNT(*) FROM volontari WHERE stato = 'attivo'"),
];

$latestSegnalazioni = dash_rows($pdo, "
    SELECT id, codice, titolo, categoria, stato, priorita, created_at
    FROM segnalazioni_problemi
    ORDER BY FIELD(stato, 'nuova','in_lavorazione','risolta','archiviata'), created_at DESC
    LIMIT 5
");

$latestMessaggiContatti = dash_rows($pdo, "
    SELECT id, codice, nome, email, oggetto, stato, created_at
    FROM contatti_messaggi
    ORDER BY FIELD(stato, 'nuovo','letto','risposto','archiviato'), created_at DESC
    LIMIT 5
");

$latestContributi = dash_rows($pdo, "
    SELECT id, codice, tipo, titolo, stato, created_at
    FROM contributi
    ORDER BY FIELD(stato, 'nuovo','letto','valutato','pubblicato','archiviato'), created_at DESC
    LIMIT 5
");

$latestPercorsi = dash_rows($pdo, "
    SELECT titolo, slug, tipo, pubblicato, created_at
    FROM percorsi
    ORDER BY updated_at DESC, created_at DESC
    LIMIT 4
");

$latestEventi = dash_rows($pdo, "
    SELECT titolo, slug, data_evento, pubblicato, created_at
    FROM eventi
    ORDER BY created_at DESC
    LIMIT 4
");

$dashboardPriorityCards = $canCommunications ? [
    [
        'href' => 'contatti-messaggi.php',
        'label' => 'Messaggi · nuovi',
        'number' => (int) $counts['messaggi_contatti_nuovi']['value'],
        'text' => $counts['messaggi_contatti']['ok']
            ? (int) $counts['messaggi_contatti']['value'] . ' messaggi totali. Apri i contatti.'
            : 'Conteggio messaggi non disponibile.',
        'urgent' => (int) $counts['messaggi_contatti_nuovi']['value'] > 0,
    ],
    [
        'href' => 'segnalazioni.php',
        'label' => 'Segnalazioni · nuove',
        'number' => (int) $counts['segnalazioni_nuove']['value'],
        'text' => $counts['segnalazioni']['ok']
            ? (int) $counts['segnalazioni']['value'] . ' segnalazioni totali. Gestisci i problemi.'
            : 'Conteggio segnalazioni non disponibile.',
        'urgent' => (int) $counts['segnalazioni_nuove']['value'] > 0,
    ],
    [
        'href' => 'contributi.php',
        'label' => 'Contributi · nuovi',
        'number' => (int) $counts['contributi_nuovi']['value'],
        'text' => $counts['contributi']['ok']
            ? (int) $counts['contributi']['value'] . ' contributi totali. Apri le proposte.'
            : 'Conteggio contributi non disponibile.',
        'urgent' => (int) $counts['contributi_nuovi']['value'] > 0,
    ],
] : [];

if ($canWhatsApp && !$isAdmin) {
    $whatsAppUnread = dash_count($pdo, 'SELECT COALESCE(SUM(non_letti), 0) FROM whatsapp_conversazioni');
    $dashboardPriorityCards[] = [
        'href' => 'volontariato.php?view=chat',
        'label' => 'WhatsApp · da leggere',
        'number' => (int) $whatsAppUnread['value'],
        'text' => 'Apri le conversazioni WhatsApp e rispondi dal backoffice.',
        'urgent' => (int) $whatsAppUnread['value'] > 0,
    ];
}

admin_page_open('Dashboard', 'dashboard', $dashboardPriorityCards);
?>

<style>
    .dash-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: center;
    }

    .dash-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .dash-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .dash-card {
        display: block;
        background: #fff;
        color: #222;
        text-decoration: none;
        box-shadow: var(--admin-shadow);
        padding: 24px;
        min-height: 156px;
        border-left: 5px solid #222;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .dash-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 38px rgba(0,0,0,.11);
        color: #222;
        text-decoration: none;
    }

    .dash-card small {
        display: block;
        color: #777;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: 11px;
        margin-bottom: 14px;
    }

    .dash-card strong {
        display: block;
        font-size: 34px;
        line-height: 1;
        margin-bottom: 10px;
    }

    .dash-card h2 {
        margin: 0 0 8px;
        font-size: 21px;
        line-height: 1.25;
    }

    .dash-card p {
        margin: 0;
        color: #707070;
        line-height: 1.45;
    }

    .dash-card.urgent {
        border-left-color: #b00020;
    }

    .dash-card.urgent strong {
        color: #b00020;
    }

    .dash-main-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 22px;
        align-items: start;
    }

    .dash-panel {
        background: #fff;
        box-shadow: var(--admin-shadow);
        padding: 24px;
        min-width: 0;
    }

    .dash-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
    }

    .dash-panel-head h2 {
        margin: 0;
        line-height: 1.25;
    }

    .dash-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .dash-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid var(--admin-border);
    }

    .dash-item:last-child {
        border-bottom: 0;
    }

    .dash-item-title {
        min-width: 0;
    }

    .dash-item-title a,
    .dash-item-title strong {
        display: block;
        font-weight: 700;
        text-decoration: none;
        overflow-wrap: anywhere;
        line-height: 1.35;
    }

    .dash-item-title a:hover {
        text-decoration: underline;
    }

    .dash-meta {
        margin-top: 5px;
        color: #777;
        font-size: 13px;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .dash-pill {
        display: inline-block;
        padding: 6px 8px;
        font-size: 12px;
        line-height: 1;
        font-weight: 700;
        background: #eee;
        white-space: nowrap;
    }

    .dash-pill.ok {
        background: #d1e7dd;
        color: #0f5132;
    }

    .dash-pill.draft {
        background: #f1f1f1;
        color: #666;
    }

    .dash-pill.nuova,
    .dash-pill.nuovo {
        background: #ffe0e0;
        color: #8a0000;
    }

    .dash-pill.in_lavorazione,
    .dash-pill.letto,
    .dash-pill.valutato {
        background: #fff0c2;
        color: #684c00;
    }

    .dash-pill.risolta,
    .dash-pill.risposto,
    .dash-pill.pubblicato {
        background: #d1e7dd;
        color: #0f5132;
    }

    .dash-pill.archiviata,
    .dash-pill.archiviato {
        background: #e9ecef;
        color: #555;
    }

    .dash-quick {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .dash-quick a {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        background: #f7f7f7;
        padding: 14px 16px;
        text-decoration: none;
        color: #222;
        border: 1px solid #eee;
    }

    .dash-quick a:hover {
        background: #222;
        color: #fff;
    }

    .dash-warning {
        display: inline-block;
        background: #fff3cd;
        color: #664d03;
        padding: 3px 6px;
        font-size: 11px;
        margin-left: 5px;
    }

    .dash-empty {
        margin: 0;
        color: #777;
    }

    .dash-mobile-stack {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 22px;
        margin-top: 22px;
    }

    @media (max-width: 1080px) {
        .dash-cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dash-main-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .dash-hero {
            grid-template-columns: 1fr;
        }

        .dash-hero-actions {
            justify-content: flex-start;
        }

        .dash-hero-actions .btn {
            width: 100%;
            text-align: center;
        }

        .dash-cards {
            grid-template-columns: 1fr;
        }

        .dash-card {
            min-height: 0;
            padding: 20px;
        }

        .dash-panel {
            padding: 20px;
        }

        .dash-panel-head {
            flex-direction: column;
            align-items: stretch;
        }

        .dash-panel-head .mini-btn {
            text-align: center;
        }

        .dash-item {
            grid-template-columns: 1fr;
            align-items: start;
        }

        .dash-pill {
            justify-self: start;
        }

        .dash-mobile-stack {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="wrap">
    <section class="hero-admin dash-hero">
        <div>
            <h1>Dashboard</h1>
            <p>
                <?php if ($isAdmin): ?>Gestione completa del sito, delle comunicazioni e degli utenti.
                <?php elseif ($canCommunications): ?>Leggi e rispondi a messaggi, email, segnalazioni e contributi.
                <?php else: ?>Gestisci gruppi, inviti e conversazioni WhatsApp.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($isAdmin): ?>
        <div class="dash-hero-actions">
            <a class="btn" href="percorso-form.php">Nuovo percorso</a>
            <a class="btn" href="evento-form.php">Nuovo evento</a>
            <a class="btn" href="luogo-form.php">Nuovo luogo</a>
            <a class="btn" href="slider-form.php">Nuova slide</a>
            <a class="btn" href="galleria-form.php">Nuove immagini</a>
            <a class="btn secondary" href="../index.php" target="_blank">Vedi sito</a>
        </div>
        <?php endif; ?>
    </section>

    <?php if ($isAdmin): ?>
    <section class="dash-cards">
        <a class="dash-card" href="percorsi.php">
            <small>Percorsi</small>
            <strong><?= (int) $counts['percorsi']['value'] ?></strong>
            <h2>Itinerari</h2>
            <p>
                <?= (int) $counts['percorsi_pubblicati']['value'] ?> pubblicati ·
                <?= (int) $counts['percorsi_speciali']['value'] ?> speciali
                <?= dash_missing($counts['percorsi']) ?>
            </p>
        </a>

        <a class="dash-card" href="eventi.php">
            <small>Eventi</small>
            <strong><?= (int) $counts['eventi']['value'] ?></strong>
            <h2>Eventi</h2>
            <p><?= (int) $counts['eventi_pubblicati']['value'] ?> pubblicati <?= dash_missing($counts['eventi']) ?></p>
        </a>


        <a class="dash-card" href="luoghi.php">
            <small>Territorio</small>
            <strong><?= (int) $counts['luoghi']['value'] ?></strong>
            <h2>Luoghi</h2>
            <p>
                <?= (int) $counts['luoghi_pubblicati']['value'] ?> pubblicati ·
                <?= (int) $counts['luoghi_evidenza']['value'] ?> in evidenza
                <?= dash_missing($counts['luoghi']) ?>
            </p>
        </a>

        <a class="dash-card" href="galleria.php">
            <small>Homepage</small>
            <strong><?= (int) $counts['galleria']['value'] ?></strong>
            <h2>Galleria</h2>
            <p>Immagini nel carousel <?= dash_missing($counts['galleria']) ?></p>
        </a>

        <a class="dash-card" href="slider.php">
            <small>Homepage</small>
            <strong><?= (int) $counts['slider']['value'] ?></strong>
            <h2>Slider</h2>
            <p>Slide principali <?= dash_missing($counts['slider']) ?></p>
        </a>

        <a class="dash-card" href="volontariato.php">
            <small>Territorio</small>
            <strong><?= (int) $counts['volontari']['value'] ?></strong>
            <h2>Volontariato</h2>
            <p><?= (int) $counts['volontari_attivi']['value'] ?> attivi · gruppi, chat e planning <?= dash_missing($counts['volontari']) ?></p>
        </a>

        <a class="dash-card" href="crea-account.php">
            <small>Sicurezza</small>
            <strong><?= (int) $counts['utenti']['value'] ?></strong>
            <h2>Utenti e permessi</h2>
            <p>Gestione ruoli e accessi backoffice <?= dash_missing($counts['utenti']) ?></p>
        </a>
    </section>
    <?php elseif ($canWhatsApp): ?>
        <section class="dash-cards">
            <a class="dash-card" href="volontariato.php?view=chat">
                <small>Comunicazioni</small>
                <strong><?= (int) ($dashboardPriorityCards[0]['number'] ?? 0) ?></strong>
                <h2>Chat WhatsApp</h2>
                <p>Leggi e rispondi alle conversazioni.</p>
            </a>
            <a class="dash-card" href="volontariato.php?view=gruppi">
                <small>WhatsApp</small>
                <strong><?= (int) $counts['volontari']['value'] ?></strong>
                <h2>Gruppi e inviti</h2>
                <p>Gestisci i gruppi e invita i volontari.</p>
            </a>
        </section>
    <?php endif; ?>

    <?php if ($canCommunications): ?>
    <section class="">
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2>Segnalazioni recenti <?= dash_missing($latestSegnalazioni) ?></h2>
                <a class="mini-btn secondary" href="segnalazioni.php">Vedi tutte</a>
            </div>

            <?php if (!$latestSegnalazioni['rows']): ?>
                <p class="dash-empty">Nessuna segnalazione recente.</p>
            <?php else: ?>
                <div class="dash-list">
                    <?php foreach ($latestSegnalazioni['rows'] as $row): ?>
                        <div class="dash-item">
                            <div class="dash-item-title">
                                <a href="segnalazione.php?id=<?= (int) $row['id'] ?>">
                                    <?= e($row['titolo']) ?>
                                </a>
                                <div class="dash-meta">
                                    <?= e($row['codice']) ?> · <?= e($row['categoria']) ?> · <?= e(dash_date($row['created_at'] ?? null)) ?>
                                </div>
                            </div>
                            <span class="dash-pill <?= e($row['stato']) ?>"><?= e($row['stato']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        
    </section>
    <?php endif; ?>

    <?php if ($canCommunications || $isAdmin): ?>
    <section class="dash-mobile-stack">
        <?php if ($canCommunications): ?>
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2>Ultimi contributi <?= dash_missing($latestContributi) ?></h2>
                <a class="mini-btn secondary" href="contributi.php">Vedi tutti</a>
            </div>

            <?php if (!$latestContributi['rows']): ?>
                <p class="dash-empty">Nessun contributo recente.</p>
            <?php else: ?>
                <div class="dash-list">
                    <?php foreach ($latestContributi['rows'] as $row): ?>
                        <div class="dash-item">
                            <div class="dash-item-title">
                                <a href="contributo.php?id=<?= (int) $row['id'] ?>">
                                    <?= e($row['titolo']) ?>
                                </a>
                                <div class="dash-meta">
                                    <?= e($row['codice']) ?> · <?= e($row['tipo']) ?> · <?= e(dash_date($row['created_at'] ?? null)) ?>
                                </div>
                            </div>
                            <span class="dash-pill <?= e($row['stato']) ?>"><?= e($row['stato']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2>Ultimi messaggi contatti <?= dash_missing($latestMessaggiContatti) ?></h2>
                <a class="mini-btn secondary" href="contatti-messaggi.php">Vedi tutti</a>
            </div>

            <?php if (!$latestMessaggiContatti['rows']): ?>
                <p class="dash-empty">Nessun messaggio recente.</p>
            <?php else: ?>
                <div class="dash-list">
                    <?php foreach ($latestMessaggiContatti['rows'] as $row): ?>
                        <div class="dash-item">
                            <div class="dash-item-title">
                                <a href="contatto-messaggio.php?id=<?= (int) $row['id'] ?>">
                                    <?= e($row['oggetto']) ?>
                                </a>
                                <div class="dash-meta">
                                    <?= e($row['codice']) ?> · <?= e($row['nome']) ?> · <?= e($row['email']) ?> · <?= e(dash_date($row['created_at'] ?? null)) ?>
                                </div>
                            </div>
                            <span class="dash-pill <?= e($row['stato']) ?>"><?= e($row['stato']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2>Ultimi percorsi <?= dash_missing($latestPercorsi) ?></h2>
                <a class="mini-btn secondary" href="percorsi.php">Vedi tutti</a>
            </div>

            <?php if (!$latestPercorsi['rows']): ?>
                <p class="dash-empty">Nessun percorso recente.</p>
            <?php else: ?>
                <div class="dash-list">
                    <?php foreach ($latestPercorsi['rows'] as $row): ?>
                        <div class="dash-item">
                            <div class="dash-item-title">
                                <a href="../percorso.php?slug=<?= urlencode($row['slug']) ?>" target="_blank">
                                    <?= e($row['titolo']) ?>
                                </a>
                                <div class="dash-meta">
                                    <?= e(strtoupper($row['tipo'] ?? '-')) ?> · <?= e(dash_date($row['created_at'] ?? null)) ?>
                                </div>
                            </div>
                            <span class="dash-pill <?= !empty($row['pubblicato']) ? 'ok' : 'draft' ?>">
                                <?= !empty($row['pubblicato']) ? 'Pubblicato' : 'Bozza' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2>Ultimi eventi <?= dash_missing($latestEventi) ?></h2>
                <a class="mini-btn secondary" href="eventi.php">Vedi tutti</a>
            </div>

            <?php if (!$latestEventi['rows']): ?>
                <p class="dash-empty">Nessun evento recente.</p>
            <?php else: ?>
                <div class="dash-list">
                    <?php foreach ($latestEventi['rows'] as $row): ?>
                        <div class="dash-item">
                            <div class="dash-item-title">
                                <a href="../evento.php?slug=<?= urlencode($row['slug']) ?>" target="_blank">
                                    <?= e($row['titolo']) ?>
                                </a>
                                <div class="dash-meta">
                                    Evento <?= e(dash_date($row['data_evento'] ?? null)) ?> · Creato <?= e(dash_date($row['created_at'] ?? null)) ?>
                                </div>
                            </div>
                            <span class="dash-pill <?= !empty($row['pubblicato']) ? 'ok' : 'draft' ?>">
                                <?= !empty($row['pubblicato']) ? 'Pubblicato' : 'Bozza' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>

<?php admin_page_close(); ?>
