# Librerie browser locali

Le mappe caricano le librerie da `/assets/vendor/`, incluse le dipendenze dinamiche di Leaflet Elevation. Le versioni, le licenze e l'integrità dei pacchetti sono registrate nei file vendorizzati. La preparazione si esegue in ambiente di build con `bash tools/vendor-browser-assets.sh`; il deploy PHP non richiede Node o npm e il browser non contatta npm/unpkg per queste librerie.

OpenTopoMap resta un servizio cartografico esterno; meteo FVG, Google Analytics con consenso, Google Fonts e le integrazioni backend non vengono modificati. Le immagini esterne già presenti sono un intervento distinto: conservare i diritti e le attribuzioni.
