document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('percorso-map');

    if (!mapEl || typeof L === 'undefined') {
        return;
    }

    var gpxUrl = mapEl.getAttribute('data-gpx');

    var options = {
        zoomControl: true,
        scrollWheelZoom: false,
        gestureHandling: true
    };

    var map = L.map(mapEl, options).setView([45.5, 10.5], 10);

    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data: &copy; OpenStreetMap contributors, SRTM | Map style: &copy; OpenTopoMap'
    }).addTo(map);

    if (!gpxUrl) {
        return;
    }

    fetch(gpxUrl)
        .then(function (response) {
            if (!response.ok) {
                throw new Error('GPX non disponibile.');
            }
            return response.text();
        })
        .then(function (xmlText) {
            var xml = new DOMParser().parseFromString(xmlText, 'application/xml');
            var geojson = toGeoJSON.gpx(xml);

            var routeLayer = L.geoJSON(geojson, {
                style: function () {
                    return {
                        weight: 4,
                        opacity: 0.9
                    };
                }
            }).addTo(map);

            if (routeLayer.getBounds && routeLayer.getBounds().isValid()) {
                map.fitBounds(routeLayer.getBounds(), {
                    padding: [30, 30]
                });
            }

            if (typeof L.control.elevation === 'function') {
                var elevation = L.control.elevation({
                    theme: 'lightblue-theme',
                    detached: true,
                    elevationDiv: '#percorso-elevation',
                    collapsed: false,
                    followMarker: true,
                    autofitBounds: false,
                    legend: true,
                    ruler: true
                }).addTo(map);

                elevation.load(gpxUrl);
            }
        })
        .catch(function (error) {
            mapEl.insertAdjacentHTML(
                'beforeend',
                '<div class="percorso-map-error">Impossibile caricare il GPX.</div>'
            );
            console.error(error);
        });
});
