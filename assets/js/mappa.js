(function () {
  const NEAR_METERS = 500;
  const defaultCenter = L.latLng(46.41682407610547, 12.932647686180651);
  const defaultZoom = 15;
  const FULL_MAP_PDF_URL = "pdf/mappa-completa.pdf";

  // Stima tempi CAI semplificata
  const CAI_FLAT_KMH = 4.0;
  const CAI_ASCENT_M_PER_H = 375;
  const CAI_DESCENT_M_PER_H = 550;

  const CAL_WEIGHT_KG = 70;

  const rawRoutes = Array.isArray(window.GPX_ROUTES) ? window.GPX_ROUTES : [];

  const TRAILS = rawRoutes.map((route, index) => ({
    id: route.id || `trail_${index + 1}`,
    name: route.title || `Percorso ${index + 1}`,
    filename: route.filename || "",
    fileUrl: route.url || "",
    updated: route.updated || "-",
    format: "gpx",
    details: "",
    stats: {
      lengthKm: null,
      ascentM: null,
      descentM: null,
      durationSec: null,
      difficulty: null,
      calories: null
    }
  }));

  const elDenied = document.getElementById("permissionDenied");
  const elOk = document.getElementById("permissionOk");
  const btnEnableGps = document.getElementById("btnEnableGps");
  const btnInteractive = document.getElementById("btnInteractive");
  const btnFallback = document.getElementById("btnFallback");
  const btnPdfFull = document.getElementById("btnPdfFull");
  const gpsInfo = document.getElementById("gpsInfo");
  const trailInfo = document.getElementById("trailInfo");
  const trailDetails = document.getElementById("trailDetails");
  const treks = document.getElementById("treks");
  const downloadCurrentGpx = document.getElementById("downloadCurrentGpx");

  const statUpdated = document.getElementById("statUpdated");
  const statDifficulty = document.getElementById("statDifficulty");
  const statCalories = document.getElementById("statCalories");
  const statDuration = document.getElementById("statDuration");
  const statLength = document.getElementById("statLength");
  const statAscent = document.getElementById("statAscent");

  const permModal = document.getElementById("permModal");
  const btnClosePermModal = document.getElementById("btnClosePermModal");
  const btnRetryGps = document.getElementById("btnRetryGps");

  const mapEl = document.getElementById("map");
  const elevationEl = document.getElementById("elevation");

  if (
    !mapEl ||
    !elDenied ||
    !elOk ||
    typeof L === "undefined" ||
    typeof toGeoJSON === "undefined" ||
    typeof turf === "undefined"
  ) {
    return;
  }

  function openPermModal() {
    if (!permModal) return;
    permModal.style.display = "block";
    permModal.setAttribute("aria-hidden", "false");
  }

  function closePermModal() {
    if (!permModal) return;
    permModal.style.display = "none";
    permModal.setAttribute("aria-hidden", "true");
  }

  if (btnClosePermModal) {
    btnClosePermModal.addEventListener("click", closePermModal);
  }

  if (permModal) {
    permModal.addEventListener("click", (e) => {
      if (e.target === permModal) closePermModal();
    });
  }

  const map = L.map("map", {
    center: defaultCenter,
    zoom: defaultZoom,
    gestureHandling: true,
    scrollWheelZoom: false,
    zoomControl: false
  });

  L.control.zoom({ position: "topright" }).addTo(map);

  if (!map.gestureHandling) {
    console.warn("leaflet-gesture-handling non caricato: verifica CSS e JS del plugin.");
  }

  L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
    maxZoom: 17,
    attribution: "© OpenTopoMap (CC-BY-SA)"
  }).addTo(map);

  const trailsLayer = L.featureGroup().addTo(map);
  const userLayer = L.layerGroup().addTo(map);

  const elevControl = elevationEl ? L.control.elevation({
    position: "bottomleft",
    theme: "lightblue-theme",
    detached: true,
    elevationDiv: "#elevation",
    collapsed: false,
    autohide: false,
    autofitBounds: false,
    imperial: false,
    legend: true,
    summary: "inline",
    followMarker: true,
    almostOver: true,
    distanceMarkers: false
  }).addTo(map) : null;

  const trailGeo = new Map();
  const trailLineLayers = new Map();

  let selectedTrail = null;

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function formatKm(km) {
    if (!Number.isFinite(km)) return "-";
    return km < 10 ? km.toFixed(2) : km.toFixed(1);
  }

  function formatDuration(seconds) {
    if (!Number.isFinite(seconds) || seconds <= 0) return "-";

    const totalMinutes = Math.round(seconds / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours > 0 && minutes > 0) return `${hours} h ${minutes} min`;
    if (hours > 0) return `${hours} h`;
    return `${minutes} min`;
  }

  function detectFormat(trail) {
    if (trail.format) return trail.format.toLowerCase();
    const url = (trail.fileUrl || "").toLowerCase();
    if (url.endsWith(".gpx")) return "gpx";
    if (url.endsWith(".kml")) return "kml";
    return "auto";
  }

  function xmlRootName(xmlDoc) {
    return (xmlDoc?.documentElement?.nodeName || "").toLowerCase();
  }

  function normalizeToLineOnly(geojson) {
    return {
      type: "FeatureCollection",
      features: (geojson?.features || []).filter((f) => {
        const type = f?.geometry?.type;
        return type === "LineString" || type === "MultiLineString";
      })
    };
  }

  function getTrailColor(index) {
    const colors = [
      "#d32f2f", "#1976d2", "#388e3c", "#f57c00", "#7b1fa2",
      "#0097a7", "#5d4037", "#c2185b", "#455a64", "#689f38"
    ];
    return colors[index % colors.length];
  }

  function segmentDistanceMeters(a, b) {
    if (!Array.isArray(a) || !Array.isArray(b) || a.length < 2 || b.length < 2) return 0;

    const R = 6371000;
    const lat1 = a[1] * Math.PI / 180;
    const lat2 = b[1] * Math.PI / 180;
    const dLat = (b[1] - a[1]) * Math.PI / 180;
    const dLon = (b[0] - a[0]) * Math.PI / 180;

    const sinDLat = Math.sin(dLat / 2);
    const sinDLon = Math.sin(dLon / 2);

    const h =
      sinDLat * sinDLat +
      Math.cos(lat1) * Math.cos(lat2) * sinDLon * sinDLon;

    return 2 * R * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
  }

  function getElevation(coord) {
    if (!Array.isArray(coord) || coord.length < 3) return null;
    const ele = Number(coord[2]);
    return Number.isFinite(ele) ? ele : null;
  }

  function accumulateElevationStatsFromCoords(coords, stats) {
    for (let i = 1; i < coords.length; i++) {
      const prev = coords[i - 1];
      const curr = coords[i];

      const prevEle = getElevation(prev);
      const currEle = getElevation(curr);

      if (prevEle === null || currEle === null) continue;

      const delta = currEle - prevEle;
      if (delta > 0) stats.ascentM += delta;
      if (delta < 0) stats.descentM += Math.abs(delta);
    }
  }

  function estimateCaiDurationFromCoords(coords) {
    let totalSeconds = 0;

    for (let i = 1; i < coords.length; i++) {
      const prev = coords[i - 1];
      const curr = coords[i];

      const distM = segmentDistanceMeters(prev, curr);
      if (distM > 0) {
        totalSeconds += (distM / (CAI_FLAT_KMH * 1000)) * 3600;
      }

      const prevEle = getElevation(prev);
      const currEle = getElevation(curr);

      if (prevEle !== null && currEle !== null) {
        const delta = currEle - prevEle;
        if (delta > 0) {
          totalSeconds += (delta / CAI_ASCENT_M_PER_H) * 3600;
        } else if (delta < 0) {
          totalSeconds += (Math.abs(delta) / CAI_DESCENT_M_PER_H) * 3600;
        }
      }
    }

    return totalSeconds;
  }

  function geojsonHasElevation(geojson) {
    try {
      for (const f of (geojson?.features || [])) {
        const g = f?.geometry;
        if (!g) continue;

        if (g.type === "LineString") {
          for (const c of (g.coordinates || [])) {
            if (Array.isArray(c) && c.length >= 3 && Number.isFinite(+c[2])) return true;
          }
        }

        if (g.type === "MultiLineString") {
          for (const line of (g.coordinates || [])) {
            for (const c of (line || [])) {
              if (Array.isArray(c) && c.length >= 3 && Number.isFinite(+c[2])) return true;
            }
          }
        }
      }
    } catch (_) {}

    return false;
  }

  function clearElevationUI() {
    if (!elevControl) return;
    try {
      elevControl.clear();
    } catch (_) {}
  }

  function showElevationForTrailGeoJSON(geojson) {
    if (!elevControl) return;

    try {
      elevControl.clear();
      elevControl.addData(geojson);

      if (!geojsonHasElevation(geojson)) {
        console.warn("Il GPX non contiene quote utili per il profilo altimetrico.");
      }
    } catch (e) {
      console.warn("Errore profilo altimetrico:", e);
      clearElevationUI();
    }
  }

  function estimateDifficulty(stats) {
    const lengthKm = Number(stats?.lengthKm || 0);
    const ascentM = Number(stats?.ascentM || 0);
    const durationH = Number(stats?.durationSec || 0) / 3600;

    if (lengthKm <= 6 && ascentM <= 250 && durationH <= 2.5) return "T";
    if (lengthKm <= 15 && ascentM <= 900 && durationH <= 6.5) return "E";
    return "EE";
  }

  function estimateCalories(stats) {
    const hours = Number(stats?.durationSec || 0) / 3600;
    if (!Number.isFinite(hours) || hours <= 0) return null;

    const difficulty = stats?.difficulty || "E";
    const metMap = {
      T: 4.5,
      E: 5.5,
      EE: 6.5
    };

    const met = metMap[difficulty] || 5.5;
    return Math.round(met * CAL_WEIGHT_KG * hours);
  }

  function computeTrailStats(geojson) {
    let lengthKm = null;
    let ascentM = 0;
    let descentM = 0;
    let durationSec = 0;

    try {
      lengthKm = turf.length(geojson, { units: "kilometers" });
    } catch (_) {}

    for (const feature of (geojson?.features || [])) {
      const g = feature?.geometry;
      if (!g) continue;

      if (g.type === "LineString") {
        const coords = g.coordinates || [];
        const tmp = { ascentM, descentM };
        accumulateElevationStatsFromCoords(coords, tmp);
        ascentM = tmp.ascentM;
        descentM = tmp.descentM;
        durationSec += estimateCaiDurationFromCoords(coords);
      } else if (g.type === "MultiLineString") {
        for (const line of (g.coordinates || [])) {
          const tmp = { ascentM, descentM };
          accumulateElevationStatsFromCoords(line || [], tmp);
          ascentM = tmp.ascentM;
          descentM = tmp.descentM;
          durationSec += estimateCaiDurationFromCoords(line || []);
        }
      }
    }

    const stats = {
      lengthKm: Number.isFinite(lengthKm) ? lengthKm : null,
      ascentM: Number.isFinite(ascentM) ? ascentM : null,
      descentM: Number.isFinite(descentM) ? descentM : null,
      durationSec: Number.isFinite(durationSec) && durationSec > 0 ? durationSec : null,
      difficulty: null,
      calories: null
    };

    stats.difficulty = estimateDifficulty(stats);
    stats.calories = estimateCalories(stats);

    return stats;
  }

  function buildTrailDetails(stats) {
    const parts = [];

    if (Number.isFinite(stats?.lengthKm)) {
      parts.push(`Lunghezza: ${formatKm(stats.lengthKm)} km`);
    }
    if (Number.isFinite(stats?.ascentM)) {
      parts.push(`Dislivello: ${Math.round(stats.ascentM)} m`);
    }

    return parts.join(" · ");
  }

  function updateDownloadLink(trail) {
    if (!downloadCurrentGpx) return;
    downloadCurrentGpx.href = trail?.fileUrl || "#";
  }

  function updateSummaryStats(trail) {
    if (!trail) {
      if (statUpdated) statUpdated.textContent = "-";
      if (statDifficulty) statDifficulty.textContent = "-";
      if (statCalories) statCalories.textContent = "-";
      if (statDuration) statDuration.textContent = "-";
      if (statLength) statLength.textContent = "-";
      if (statAscent) statAscent.textContent = "-";
      return;
    }

    if (statUpdated) statUpdated.textContent = trail.updated || "-";
    if (statDifficulty) statDifficulty.textContent = trail.stats?.difficulty || "-";
    if (statCalories) {
      statCalories.textContent = Number.isFinite(trail.stats?.calories)
        ? `${trail.stats.calories} kcal`
        : "-";
    }
    if (statDuration) {
      statDuration.textContent = Number.isFinite(trail.stats?.durationSec)
        ? formatDuration(trail.stats.durationSec)
        : "-";
    }
    if (statLength) {
      statLength.textContent = Number.isFinite(trail.stats?.lengthKm)
        ? `${formatKm(trail.stats.lengthKm)} km`
        : "-";
    }
    if (statAscent) {
      statAscent.textContent = Number.isFinite(trail.stats?.ascentM)
        ? `${Math.round(trail.stats.ascentM)} m`
        : "-";
    }
  }

  function updateCardStats(trail) {
    const lenEl = document.querySelector(`.js-route-length[data-route-id="${trail.id}"]`);
    const ascEl = document.querySelector(`.js-route-ascent[data-route-id="${trail.id}"]`);

    if (lenEl) {
      lenEl.textContent = Number.isFinite(trail.stats?.lengthKm)
        ? `Lunghezza: ${formatKm(trail.stats.lengthKm)} km`
        : "Lunghezza: -";
    }

    if (ascEl) {
      ascEl.textContent = Number.isFinite(trail.stats?.ascentM)
        ? `Dislivello: ${Math.round(trail.stats.ascentM)} m`
        : "Dislivello: -";
    }
  }

  function updateActiveCards(activeTrailId) {
    document.querySelectorAll(".js-route-item").forEach((card) => {
      const isActive = card.getAttribute("data-route-id") === activeTrailId;
      card.style.border = isActive ? "2px solid #1976d2" : "1px solid #e5e5e5";
      card.style.boxShadow = isActive ? "0 0 0 3px rgba(25,118,210,0.12)" : "none";
    });
  }

  function selectTrail(trail, options = {}) {
    const { focusMap = false, showElevation = true, scrollToMap = false } = options;
    if (!trail) return;

    selectedTrail = trail;

    if (btnInteractive) btnInteractive.disabled = false;
    updateDownloadLink(trail);
    updateSummaryStats(trail);
    updateActiveCards(trail.id);

    if (showElevation) {
      const gj = trailGeo.get(trail.id);
      if (gj) showElevationForTrailGeoJSON(gj);
    }

    if (focusMap) {
      const layer = trailLineLayers.get(trail.id);
      if (layer && layer.getBounds && layer.getBounds().isValid()) {
        map.fitBounds(layer.getBounds().pad(0.2));
      }
    }

    if (scrollToMap && mapEl) {
      mapEl.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }

  async function loadTrail(trail) {
    const res = await fetch(trail.fileUrl, { cache: "no-store" });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const text = await res.text();
    const dom = new DOMParser().parseFromString(text, "text/xml");

    let fmt = detectFormat(trail);
    if (fmt === "auto") {
      const root = xmlRootName(dom);
      if (root.includes("gpx")) fmt = "gpx";
      else fmt = "kml";
    }

    let geojson;
    if (fmt === "gpx") geojson = toGeoJSON.gpx(dom);
    else geojson = toGeoJSON.kml(dom);

    return normalizeToLineOnly(geojson);
  }

  async function loadAllTrails() {
    trailsLayer.clearLayers();
    const bounds = L.latLngBounds();

    await Promise.all(TRAILS.map(async (trail, index) => {
      try {
        const geojson = await loadTrail(trail);
        trailGeo.set(trail.id, geojson);

        trail.stats = computeTrailStats(geojson);
        trail.details = buildTrailDetails(trail.stats);
        updateCardStats(trail);

        const gjLayer = L.geoJSON(geojson, {
          style: () => ({
            weight: 4,
            color: getTrailColor(index),
            opacity: 0.9
          }),
          onEachFeature: (_feature, layer) => {
            layer.on("click", () => {
              selectTrail(trail, { focusMap: false, showElevation: true, scrollToMap: false });
            });

            const popup = [
              `<strong>${escapeHtml(trail.name)}</strong>`,
              trail.details ? escapeHtml(trail.details) : "",
              trail.stats?.difficulty ? `Difficoltà: ${escapeHtml(trail.stats.difficulty)}` : "",
              Number.isFinite(trail.stats?.calories) ? `Calorie: ${trail.stats.calories} kcal` : "",
              Number.isFinite(trail.stats?.durationSec) ? `Tempo: ${escapeHtml(formatDuration(trail.stats.durationSec))}` : "",
              `<a href="${escapeHtml(trail.fileUrl)}" target="_blank" rel="noopener">Scarica traccia</a>`
            ].filter(Boolean).join("<br>");

            layer.bindPopup(popup);
          }
        });

        gjLayer.addTo(trailsLayer);
        trailLineLayers.set(trail.id, gjLayer);

        if (gjLayer.getBounds && gjLayer.getBounds().isValid()) {
          bounds.extend(gjLayer.getBounds());
        }
      } catch (err) {
        console.warn("Errore caricamento traccia:", trail.fileUrl, err);
        updateCardStats(trail);
      }
    }));

    if (bounds.isValid()) {
      map.fitBounds(bounds.pad(0.08));
    } else {
      map.setView(defaultCenter, defaultZoom);
    }

    const firstLoadedTrail = TRAILS.find((trail) => trailLineLayers.has(trail.id));
    if (firstLoadedTrail) {
      selectTrail(firstLoadedTrail, { focusMap: false, showElevation: true, scrollToMap: false });
    } else {
      updateSummaryStats(null);
    }
  }

  function computeNearestTrail(userLatLng) {
    const userPoint = turf.point([userLatLng.lng, userLatLng.lat]);
    let best = null;

    for (const trail of TRAILS) {
      const geojson = trailGeo.get(trail.id);
      if (!geojson?.features?.length) continue;

      for (const f of geojson.features) {
        const type = f?.geometry?.type;
        if (type !== "LineString" && type !== "MultiLineString") continue;

        const meters = turf.pointToLineDistance(userPoint, f, { units: "kilometers" }) * 1000;
        if (!best || meters < best.meters) {
          best = { trail, meters };
        }
      }
    }

    return best;
  }

  function updateGpsTrailUI(nearest) {
    if (!nearest) {
      trailInfo.innerHTML = `<strong>Sentiero:</strong> <span class="muted">non determinato</span>`;
      trailDetails.textContent = "";
      return;
    }

    const metersRound = Math.round(nearest.meters);
    const isNear = nearest.meters <= NEAR_METERS;

    if (isNear) {
      trailInfo.innerHTML = `<strong>Ti trovi sul sentiero:</strong> ${escapeHtml(nearest.trail.name)} <span class="pill">~ ${metersRound} m</span>`;
      trailDetails.textContent = nearest.trail.details || "";
      selectTrail(nearest.trail, { focusMap: false, showElevation: true, scrollToMap: false });
    } else {
      trailInfo.innerHTML = `<strong>Sentiero:</strong> <span class="muted">più vicino: ${escapeHtml(nearest.trail.name)} a ~${metersRound} m</span>`;
      trailDetails.textContent = nearest.trail.details || "";
    }
  }

  function showDenied() {
    elDenied.style.display = "block";
    elOk.style.display = "none";
    map.setView(defaultCenter, defaultZoom);
  }

  function showOk() {
    elDenied.style.display = "none";
    elOk.style.display = "block";
  }

  function setUserOnMap(lat, lng, accuracyMeters) {
    userLayer.clearLayers();

    const ll = L.latLng(lat, lng);
    L.marker(ll).addTo(userLayer).bindPopup("Sei qui").openPopup();

    if (Number.isFinite(accuracyMeters)) {
      L.circle(ll, { radius: accuracyMeters }).addTo(userLayer);
    }

    const nearest = computeNearestTrail(ll);
    updateGpsTrailUI(nearest);

    gpsInfo.textContent = `Posizione rilevata. Accuratezza stimata: ~${Math.round(accuracyMeters || 0)} m.`;
  }

  function requestLocationOnce() {
    if (!navigator.geolocation) {
      showDenied();
      openPermModal();
      return;
    }

    gpsInfo.textContent = "Richiesta permesso GPS…";

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        showOk();
        setUserOnMap(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
      },
      (err) => {
        console.warn("Geolocation error:", err);
        showDenied();
        openPermModal();
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
  }

  async function getGeoPermissionState() {
    if (!navigator.permissions?.query) return "unknown";
    try {
      const s = await navigator.permissions.query({ name: "geolocation" });
      return s.state;
    } catch (_) {
      return "unknown";
    }
  }

  if (btnPdfFull) {
    btnPdfFull.addEventListener("click", () => {
      window.open(FULL_MAP_PDF_URL, "_blank");
    });
  }

  if (btnFallback) {
    btnFallback.addEventListener("click", () => {
      if (treks) {
        treks.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    });
  }

  if (btnInteractive) {
    btnInteractive.addEventListener("click", () => {
      if (selectedTrail) {
        selectTrail(selectedTrail, { focusMap: true, showElevation: true, scrollToMap: true });
      }
    });
  }

  if (btnEnableGps) {
    btnEnableGps.addEventListener("click", () => openPermModal());
  }

  if (btnRetryGps) {
    btnRetryGps.addEventListener("click", async () => {
      closePermModal();
      const state = await getGeoPermissionState();

      if (state === "denied") {
        openPermModal();
        return;
      }

      showOk();
      requestLocationOnce();
    });
  }

  document.querySelectorAll(".js-route-item").forEach((item) => {
    item.addEventListener("click", function () {
      const routeId = this.getAttribute("data-route-id");
      const trail = TRAILS.find((t) => t.id === routeId);
      if (trail) {
        selectTrail(trail, { focusMap: true, showElevation: true, scrollToMap: true });
      }
    });
  });

  async function init() {
    clearElevationUI();
    await loadAllTrails();

    const state = await getGeoPermissionState();

    if (state === "granted") {
      showOk();
      requestLocationOnce();
    } else if (state === "denied") {
      showDenied();
    } else {
      showOk();
      requestLocationOnce();
    }
  }

  init();
})();

