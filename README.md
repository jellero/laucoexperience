# Lauco Experience

Versione production-ready del sito Lauco Experience: micro-framework Slim 4 con front controller unico, grafica pubblica invariata, backoffice editoriale e contenuti in italiano, inglese, tedesco e sloveno.

## Contratto

- tema, CSS, JavaScript, immagini e impaginazioni pubbliche restano invariati;
- URL pubblici e amministrativi rimangono disponibili;
- tabelle, contenuti e CRUD esistenti sono conservati;
- Slim 4 orchestra pagine, form, API e risposte HTTP tramite route e Action PSR-7;
- le pagine sono view in `resources/views/pages`, le sezioni in `resources/views/sections` e la root non contiene endpoint PHP;
- le migrazioni sono additive e non cancellano dati.

## Installazione

```bash
cp .env.example .env
# configurare database e OpenAI
composer install --no-dev --optimize-autoloader
mariadb lauco < database.sql
mariadb lauco < migrations/20260806_ai_event_import.sql
mariadb lauco < migrations/20260807_framework_i18n.sql
mariadb lauco < migrations/20261005_admin_web_push.sql
mariadb lauco < migrations/20261005_admin_push_preferences.sql
mariadb lauco < migrations/20261005_admin_push_queue.sql
```

Le credenziali restano esclusivamente nel file locale `.env`; `.env` e i dump con dati reali non devono essere versionati. La directory `storage/translations` deve essere scrivibile dal processo PHP.

Per il deploy da hosting condiviso, caricare `tools/deploy-web.php` nel document root con il nome `deploy.php`: conserva automaticamente `.env`, upload e cataloghi aggiornati, installa le dipendenze, applica le migrazioni additive e mantiene le release precedenti per il rollback.

## Posta nel backoffice

La voce `Posta` usa la sessione amministrativa esistente per leggere via IMAP e inviare via SMTP dalla casella condivisa. Host, porte e utente hanno valori predefiniti in `.env.example`; sul server è sufficiente impostare `MAIL_PASSWORD`, oppure le due variabili separate `MAIL_IMAP_PASSWORD` e `MAIL_SMTP_PASSWORD`. Le credenziali non vanno mai inserite nel repository.

## Notifiche del backoffice

La dashboard mostra a ogni utente autenticato il comando `Abilita notifiche` per registrare il browser o il dispositivo corrente tramite Web Push. Le chiavi VAPID P-256 vengono generate automaticamente al primo utilizzo e conservate nel database; non è richiesta configurazione Firebase né una chiave manuale. È possibile impostare opzionalmente `PUSH_VAPID_SUBJECT` con un valore `mailto:` o HTTPS per identificare il mittente VAPID.

Su Android e desktop il pulsante attiva direttamente la subscription dopo il consenso del browser. Su iPhone/iPad la Web Push di Safari richiede che il backoffice sia prima aggiunto alla schermata Home come web app: se la dashboard è ancora aperta nella normale scheda Safari, lo stesso pulsante mostra le istruzioni minime; una volta aperta l'icona `Lauco Admin`, un singolo tap su `Abilita notifiche` avvia il consenso di sistema e registra il dispositivo. La subscription resta associata all'utente amministrativo e viene sincronizzata nuovamente ai login successivi.

Sotto al pulsante è presente il pannello `Preferenze notifiche`: ogni account può attivare o disattivare con autosalvataggio Posta, Messaggi, Segnalazioni, Contributi, Volontariato, WhatsApp e Newsletter. Sono mostrate solo le categorie compatibili con i permessi dell'account; in assenza di una preferenza esplicita le categorie consentite sono attive per impostazione predefinita.

Messaggi contatti, volontariato, WhatsApp e nuove iscrizioni newsletter inviano il push direttamente quando l'evento viene registrato. Contributi e segnalazioni vengono accodati tramite trigger DB. La posta viene rilevata confrontando il numero di messaggi non letti. Per elaborare la coda e controllare la posta anche quando nessun amministratore ha il sito aperto, configurare il cron dell'hosting a eseguire ogni minuto:

```bash
* * * * * /usr/bin/php /percorso/del/sito/tools/admin-push-worker.php >/dev/null 2>&1
```

Il worker è eseguibile esclusivamente da CLI, usa un lock MySQL per evitare esecuzioni concorrenti e non espone endpoint web. Il service worker `admin-push-sw.js` mostra notifiche generiche e apre `/admin/` al tocco. Gli endpoint push scaduti (`404`/`410`) vengono rimossi automaticamente.

## Architettura

```text
public/index.php             front controller unico
bootstrap/app.php            bootstrap e dependency wiring
config/routes.php            inventario dichiarativo delle route
src/Http/Action/             controller per login, form e newsletter
src/View/PhpView.php         renderer PHP confinato alle view
resources/views/pages/       layout delle pagine
resources/views/sections/    componenti editoriali della home e degli elenchi
resources/views/partials/    head, menu, footer e script condivisi
resources/lang/              cataloghi statici IT / EN / DE / SL
admin/                       backoffice protetto
inc/                         servizi legacy isolati e compatibili
```

Le URL canoniche sono prive dell'estensione (`/eventi`, `/luoghi`, `/privacy`). Gli indirizzi storici `.php` restano alias compatibili; le richieste web GET/HEAD vengono reindirizzate alla forma pulita. Una route sconosciuta usa la view 404 del framework.

## Contenuti AI e quattro lingue

Le voci `AI / Lingue`, `Traduzioni` e `Testi sito` sono disponibili nel backoffice.

Per percorsi, eventi, luoghi, galleria e slider il server:

1. legge il record esistente e conserva lo slug;
2. invia a OpenAI soltanto i dati editoriali disponibili;
3. genera contemporaneamente una preview coordinata IT / EN / DE / SL;
4. salva quattro bozze collegate in stato `review`;
5. pubblica soltanto dopo approvazione esplicita dell’editor;
6. aggiorna i campi italiani e conserva EN / DE / SL in `content_translations`.

I testi non gestiti dal database sono in `resources/lang/site.{locale}.json`. Il pannello `Testi sito` consente modifica manuale e rigenerazione assistita delle tre traduzioni in una preview unica. Le versioni approvate sono scritte in `storage/translations` e prevalgono sui cataloghi versionati.

La lingua viene risolta da `?lang=it|en|de|sl`, poi dal cookie e infine da `Accept-Language`. In assenza di una traduzione editoriale dinamica viene mostrato il contenuto italiano.

## Eventi da fonti esterne

La pagina `admin/eventi-importa.php` usa esclusivamente sorgenti HTTPS allowlisted, estrae eventi JSON-LD, salva candidati deduplicati e richiede revisione prima di creare un evento in bozza.

## Verifica

```bash
composer check
composer validate --strict --no-check-publish
```

La CI protegge gli asset e i template visivi originali, valida i 4 cataloghi, esegue lint, smoke test, PHPUnit e applica le migrazioni su MariaDB 11.4.
