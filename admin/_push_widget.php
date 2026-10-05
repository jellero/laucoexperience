<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin-push.php';

$adminPushPublicKey = '';
$adminPushError = '';
try {
    $adminPushPdo = $GLOBALS['pdo'] ?? null;
    if (!$adminPushPdo instanceof PDO) {
        throw new RuntimeException('Connessione database non disponibile.');
    }
    $adminPushPublicKey = admin_push_vapid_keypair($adminPushPdo)['public'];
} catch (Throwable $exception) {
    error_log('[Lauco Push] ' . $exception->getMessage());
    $adminPushError = 'Notifiche non disponibili: applica la migrazione Web Push sul database.';
}
?>

<style>
    .admin-push-card {
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        gap:18px;
        align-items:center;
        background:#fff;
        box-shadow:var(--admin-shadow);
        border-left:5px solid #202020;
        padding:20px 22px;
        margin-bottom:18px;
    }
    .admin-push-card.is-active { border-left-color:#0f7b32; }
    .admin-push-card.is-error { border-left-color:#b00020; }
    .admin-push-copy { min-width:0; }
    .admin-push-kicker {
        display:block;
        margin-bottom:5px;
        color:#707070;
        font-size:11px;
        font-weight:700;
        letter-spacing:.08em;
        text-transform:uppercase;
    }
    .admin-push-title { margin:0 0 5px; font-size:18px; line-height:1.25; }
    .admin-push-status { margin:0; color:#666; line-height:1.45; font-size:13px; }
    .admin-push-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .admin-push-button { white-space:nowrap; min-width:190px; }
    .admin-push-button[disabled] { opacity:.65; cursor:default; }
    .admin-push-disable {
        appearance:none;
        border:0;
        background:transparent;
        color:#666;
        padding:8px 4px;
        text-decoration:underline;
        cursor:pointer;
        font:inherit;
        font-size:12px;
    }
    .admin-push-ios-help {
        grid-column:1 / -1;
        background:#f7f7f7;
        border:1px solid #e6e6e6;
        padding:14px 16px;
        font-size:13px;
        line-height:1.55;
    }
    .admin-push-ios-help strong { display:block; margin-bottom:4px; }
    .admin-push-ios-help ol { margin:8px 0 0 19px; padding:0; }
    @media(max-width:780px) {
        .admin-push-card { grid-template-columns:1fr; padding:18px; }
        .admin-push-actions { justify-content:stretch; }
        .admin-push-button { width:100%; min-width:0; min-height:48px; font-size:14px; }
        .admin-push-disable { width:100%; text-align:center; }
    }
</style>

<section
    class="admin-push-card<?= $adminPushError !== '' ? ' is-error' : '' ?>"
    id="adminPushCard"
    data-public-key="<?= e($adminPushPublicKey) ?>"
    data-csrf="<?= e(csrf_token()) ?>"
>
    <div class="admin-push-copy">
        <span class="admin-push-kicker">Questo dispositivo</span>
        <h2 class="admin-push-title">Notifiche del backoffice</h2>
        <p class="admin-push-status" id="adminPushStatus">
            <?= $adminPushError !== '' ? e($adminPushError) : 'Preparazione delle notifiche…' ?>
        </p>
    </div>
    <div class="admin-push-actions">
        <button
            class="btn admin-push-button"
            type="button"
            id="adminPushButton"
            disabled
        >Abilita notifiche</button>
        <button class="admin-push-disable" type="button" id="adminPushDisable" hidden>Disattiva su questo dispositivo</button>
    </div>
    <div class="admin-push-ios-help" id="adminPushIosHelp" hidden>
        <strong>Su iPhone Apple richiede prima di aprire Lauco Experience dalla schermata Home.</strong>
        <ol>
            <li>In Safari tocca <strong>Condividi</strong> (quadrato con freccia verso l’alto).</li>
            <li>Scegli <strong>Aggiungi alla schermata Home</strong> e lascia attivo “Apri come app”.</li>
            <li>Apri l’icona <strong>Lauco Admin</strong>, torna alla dashboard e tocca una sola volta <strong>Abilita notifiche</strong>.</li>
        </ol>
    </div>
</section>

<?php if ($adminPushError === ''): ?>
<script>
(function () {
    'use strict';

    const card = document.getElementById('adminPushCard');
    const button = document.getElementById('adminPushButton');
    const disableButton = document.getElementById('adminPushDisable');
    const status = document.getElementById('adminPushStatus');
    const iosHelp = document.getElementById('adminPushIosHelp');
    if (!card || !button || !disableButton || !status || !iosHelp) return;

    const publicKey = card.dataset.publicKey || '';
    const csrf = card.dataset.csrf || '';
    const isIos = /iPad|iPhone|iPod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    let registration = null;
    let currentSubscription = null;

    function setStatus(message, kind) {
        status.textContent = message;
        card.classList.toggle('is-active', kind === 'active');
        card.classList.toggle('is-error', kind === 'error');
    }

    function setInactive() {
        currentSubscription = null;
        button.hidden = false;
        button.disabled = false;
        button.textContent = 'Abilita notifiche';
        disableButton.hidden = true;
        if (isIos && !isStandalone) {
            setStatus('Su iPhone ti guidiamo nel passaggio richiesto da Apple. Poi l’attivazione richiede un solo tap.', '');
        } else {
            setStatus('Ricevi gli avvisi di Lauco Experience anche quando il browser è chiuso.', '');
        }
    }

    function setActive(subscription) {
        currentSubscription = subscription;
        button.hidden = false;
        button.disabled = true;
        button.textContent = 'Notifiche attive';
        disableButton.hidden = false;
        iosHelp.hidden = true;
        setStatus('Attive su questo dispositivo. Non dovrai abilitarle di nuovo ai prossimi accessi.', 'active');
    }

    function setBusy(message) {
        button.hidden = false;
        button.disabled = true;
        button.textContent = message;
        disableButton.hidden = true;
    }

    function base64UrlToUint8Array(value) {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        return Uint8Array.from(raw, (char) => char.charCodeAt(0));
    }

    function platformName() {
        if (isIos) return 'ios';
        if (/Android/i.test(navigator.userAgent)) return 'android';
        return 'desktop';
    }

    async function api(action, payload) {
        const response = await fetch('push.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf
            },
            body: JSON.stringify(Object.assign({action: action}, payload || {}))
        });
        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            data = null;
        }
        if (!response.ok || !data || !data.success) {
            throw new Error(data && data.error ? data.error : 'Operazione non riuscita.');
        }
        return data;
    }

    async function syncSubscription(subscription) {
        await api('subscribe', {
            subscription: subscription.toJSON(),
            platform: platformName()
        });
        setActive(subscription);
    }

    async function prepare() {
        if (isIos && !isStandalone) {
            // Safari su iPhone non consente Web Push alla normale scheda del browser.
            setInactive();
            return;
        }
        if (!supported || !publicKey) {
            button.disabled = true;
            setStatus('Questo browser non supporta le notifiche Web Push.', 'error');
            return;
        }
        if (Notification.permission === 'denied') {
            button.disabled = true;
            setStatus(
                isIos
                    ? 'Le notifiche sono bloccate. Riattivale in Impostazioni > Notifiche > Lauco Admin.'
                    : 'Le notifiche sono bloccate nelle impostazioni del browser.',
                'error'
            );
            return;
        }

        try {
            await navigator.serviceWorker.register('/admin-push-sw.js', {scope: '/admin/'});
            registration = await navigator.serviceWorker.ready;
            currentSubscription = await registration.pushManager.getSubscription();
            if (currentSubscription) {
                await syncSubscription(currentSubscription);
            } else {
                setInactive();
            }
        } catch (error) {
            console.error('[Lauco Push]', error);
            button.disabled = true;
            setStatus('Impossibile preparare le notifiche su questo dispositivo.', 'error');
        }
    }

    button.addEventListener('click', function () {
        if (isIos && !isStandalone) {
            iosHelp.hidden = false;
            iosHelp.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            setStatus('Aggiungi Lauco alla schermata Home, poi aprila dall’icona. È l’unico passaggio imposto da iPhone.', '');
            return;
        }

        if (!supported || !registration) {
            setStatus('Notifiche non ancora pronte. Ricarica la dashboard e riprova.', 'error');
            return;
        }

        setBusy('Attivazione…');

        // subscribe() viene chiamato direttamente dal gesto dell'utente: è essenziale su iPhone/iPad.
        registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: base64UrlToUint8Array(publicKey)
        }).then(async function (subscription) {
            await syncSubscription(subscription);
            try {
                await registration.showNotification('Notifiche attive', {
                    body: 'Questo dispositivo riceverà gli avvisi del backoffice Lauco Experience.',
                    icon: '/android-icon-192x192.png',
                    badge: '/android-icon-192x192.png',
                    tag: 'lauco-admin-enabled'
                });
            } catch (error) {
                // La subscription è comunque valida anche se il test locale non viene mostrato.
            }
        }).catch(function (error) {
            console.error('[Lauco Push]', error);
            if (Notification.permission === 'denied') {
                button.disabled = true;
                setStatus(
                    isIos
                        ? 'Hai negato le notifiche. Puoi riattivarle dalle Impostazioni di iPhone.'
                        : 'Hai negato le notifiche. Puoi riattivarle nelle impostazioni del browser.',
                    'error'
                );
                return;
            }
            setInactive();
            setStatus('Attivazione non riuscita. Tocca di nuovo “Abilita notifiche”.', 'error');
        });
    });

    disableButton.addEventListener('click', async function () {
        if (!currentSubscription) return;
        disableButton.disabled = true;
        try {
            const endpoint = currentSubscription.endpoint;
            await api('unsubscribe', {endpoint: endpoint});
            await currentSubscription.unsubscribe();
            setInactive();
        } catch (error) {
            console.error('[Lauco Push]', error);
            setStatus('Disattivazione non riuscita. Riprova.', 'error');
        } finally {
            disableButton.disabled = false;
        }
    });

    prepare();
})();
</script>
<?php endif; ?>
