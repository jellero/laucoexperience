# WhatsApp per il modulo Volontariato

Il sito registra i volontari anche quando WhatsApp non è configurato. Inviti e messaggi restano visibili nella coda del backoffice e possono essere elaborati dopo l'attivazione.

## Configurazione

Impostare nel file `.env` del server le variabili `WHATSAPP_*` documentate in `.env.example`.

1. Creare o collegare l'app Meta alla WhatsApp Business Account del progetto.
2. Inserire access token permanente, Phone Number ID, WABA ID, App Secret e un token di verifica webhook scelto dal gestore.
3. Registrare il webhook pubblico `https://laucoexperience.it/api/whatsapp/webhook` e sottoscrivere gli eventi dei messaggi.
4. Creare e far approvare un template di invito in italiano. Il corpo deve usare, in questo ordine, nome volontario, nome gruppo e link di invito.
5. Inserire il nome del template in `WHATSAPP_INVITE_TEMPLATE_NAME`.
6. Nel backoffice, aprire **Volontariato → Gruppi** e indicare il link d'invito e, quando disponibile, il Meta Group ID.

La variabile `WHATSAPP_GROUPS_ENABLED` va attivata solo dopo che Meta ha abilitato l'account alla Groups API. Senza questa abilitazione restano operativi iscrizione, gestione volontari, planning, stato sentieri, inviti individuali e chat dirette; l'invio e la lettura delle chat di gruppo restano disattivati.

## Invii

Il modulo tenta immediatamente ogni invio. Per ritentare la coda dal server si può eseguire periodicamente:

```sh
php tools/whatsapp-worker.php
```

La stessa operazione è disponibile nel riepilogo del modulo attraverso il pulsante **Elabora coda adesso**.

## Consenso

Il modulo pubblico richiede separatamente maggiore età, trattamento dei dati, comunicazioni WhatsApp e consapevolezza che il numero diventa visibile agli altri partecipanti dopo l'ingresso nel gruppo. La registrazione conserva versione e data dei consensi.
