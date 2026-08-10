-- Curated territorial content for Lauco and its documented frazioni.
-- Existing records are preserved: inserts run only when neither the slug nor title exists.

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Lauco','lauco','Il capoluogo sull’altopiano','Borgo','Lauco',
'Il centro principale dell’altopiano, punto di partenza per leggere insieme storia, paesaggio e rete dei sentieri.',
'Lauco è documentato nelle fonti storiche almeno dal 914. Nel capoluogo si conservano testimonianze dell’architettura e della storia locale, tra cui Borgo Cavocjarie, Casa Elena Cimenti e la chiesa parrocchiale di Tutti i Santi, costruita a partire dal Settecento.

Il nucleo abitato è parte di un paesaggio più ampio fatto di prati, boschi, viabilità storica e collegamenti verso le frazioni. La visita al paese può quindi essere letta come introduzione all’intero altopiano e ai suoi itinerari.',
'Tutto l’anno',10,1,1
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='lauco' OR LOWER(`titolo`)=LOWER('Lauco')
);

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Allegnidis','allegnidis','Una delle frazioni dell’altopiano','Frazione','Allegnidis',
'Piccolo nucleo dell’altopiano di Lauco, inserito nel sistema di borghi, radure e collegamenti locali.',
'Allegnidis è una delle frazioni documentate del Comune di Lauco. Le descrizioni territoriali locali la collocano lungo il sistema di piccoli nuclei che caratterizza la parte settentrionale dell’altopiano.

Dal borgo il paesaggio si apre verso radure e percorsi che collegano le altre località del territorio. La scheda è pensata come punto di partenza per integrare fotografie, testimonianze e dettagli storici verificati.',
'Tutto l’anno',20,1,0
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='allegnidis' OR LOWER(`titolo`)=LOWER('Allegnidis')
);

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Avaglio','avaglio','Borgo affacciato sull’altopiano','Frazione','Avaglio',
'Una frazione di Lauco legata al paesaggio rurale e a testimonianze archeologiche del territorio.',
'Avaglio è una delle frazioni documentate del Comune di Lauco. Le fonti locali ricordano la sua posizione soleggiata e segnalano nell’area testimonianze funerarie antiche, oltre a un lavatoio storico restaurato.

Il borgo permette di leggere bene il rapporto tra insediamento, campi, prati e collegamenti verso gli altri nuclei dell’altopiano.',
'Tutto l’anno',30,1,0
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='avaglio' OR LOWER(`titolo`)=LOWER('Avaglio')
);

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Buttea','buttea','Paesaggio rurale verso i rilievi','Frazione','Buttea',
'Frazione nella parte alta del territorio, circondata da un paesaggio di boschi, prati e architetture rurali.',
'Buttea è una delle frazioni documentate del Comune di Lauco. Le descrizioni locali del territorio ricordano, nell’area di Buttea e delle borgate vicine, case in pietra e stavoli che testimoniano il carattere rurale dell’altopiano.

Il contesto è strettamente legato alla rete di strade locali, sentieri e superfici boscate che salgono verso i rilievi.',
'Tutto l’anno',40,1,0
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='buttea' OR LOWER(`titolo`)=LOWER('Buttea')
);

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Chiassis','chiassis','La frazione verso il fondovalle','Frazione','Chiassis',
'Un piccolo nucleo del Comune di Lauco posto più in basso rispetto al cuore dell’altopiano.',
'Chiassis è una delle frazioni documentate del Comune di Lauco. Le descrizioni territoriali la collocano verso il fondovalle, in prossimità del torrente Degano.

La sua posizione mostra bene la forte escursione altimetrica del territorio comunale e il collegamento tra l’altopiano e i fondovalle della Carnia.',
'Tutto l’anno',50,1,0
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='chiassis' OR LOWER(`titolo`)=LOWER('Chiassis')
);

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Trava','trava','Storia, architettura e devozione','Frazione','Trava',
'Una frazione ricca di testimonianze storiche e religiose, immersa nel paesaggio dell’altopiano.',
'Trava è una delle frazioni documentate del Comune di Lauco. PromoTurismoFVG segnala qui la chiesa del Santo Nome di Maria, Palazzo Beorchia e il Santuario della Madonna dei Miracoli.

Il santuario, edificato intorno alla metà del Seicento, è raggiunto da un percorso segnato dalle cappelle della Via Crucis ed è legato a una particolare storia devozionale. Il borgo unisce quindi architettura, memoria religiosa e paesaggio.',
'Tutto l’anno',60,1,1
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='trava' OR LOWER(`titolo`)=LOWER('Trava')
);

INSERT INTO `luoghi`
(`titolo`,`slug`,`sottotitolo`,`categoria`,`localita`,`excerpt`,`descrizione`,`periodo_consigliato`,`ordine`,`pubblicato`,`in_evidenza`)
SELECT
'Vinaio','vinaio','Tra le acque e la Forra del Vinadia','Frazione','Vinaio',
'Frazione legata ai corsi d’acqua e a uno degli accessi storici al sistema della Forra del Vinadia.',
'Vinaio è una delle frazioni documentate del Comune di Lauco. Le fonti territoriali la descrivono tra il torrente Vinadia e il rio Pichions e ricordano il legame storico del paese con i cramars, commercianti ambulanti, e con la tessitura.

Da Vinaio partono inoltre collegamenti escursionistici verso il sistema della Forra del Vinadia. Il rapporto tra borgo, acqua e versanti è uno degli elementi centrali per leggere questa parte del territorio.',
'Tutto l’anno',70,1,1
WHERE NOT EXISTS (
    SELECT 1 FROM `luoghi` WHERE `slug`='vinaio' OR LOWER(`titolo`)=LOWER('Vinaio')
);
