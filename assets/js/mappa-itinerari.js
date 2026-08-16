(function () {
  "use strict";

  const mapElement = document.getElementById("itinerary-map");
  const statusElement = document.getElementById("itinerary-map-status");
  const routes = Array.isArray(window.ITINERARY_MAP_ROUTES) ? window.ITINERARY_MAP_ROUTES : [];
  const labels = window.ITINERARY_MAP_LABELS || {};

  if (!mapElement || typeof L === "undefined" || typeof toGeoJSON === "undefined") return;

  const map = L.map(mapElement, {
    center: [46.416824, 12.932648],
    zoom: 13,
    gestureHandling: true,
    scrollWheelZoom: false,
    zoomControl: false
  });

  L.control.zoom({ position: "topright" }).addTo(map);
  L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
    maxZoom: 17,
    attribution: "© OpenTopoMap (CC-BY-SA)"
  }).addTo(map);

  const routeLayers = new Map();
  const routeLookup = new Map(routes.map((route) => [route.id, route]));
  routes.forEach((route, index) => {
    const hue = Math.round((index * 137.508 + 8) % 360);
    route.color = `hsl(${hue}, 72%, 40%)`;
  });
  let activeFilter = "all";
  let selectedRouteId = null;

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function colorFor(route) {
    return route && route.color ? route.color : "#333333";
  }

  function isVisible(route) {
    return activeFilter === "all" || route.category === activeFilter;
  }

  function popupHtml(route) {
    const locality = route.locality ? `<div>${escapeHtml(route.locality)}</div>` : "";
    return `<div class="itinerary-popup">
      <span class="itinerary-popup-kind">${escapeHtml(route.categoryLabel)}</span>
      <h3>${escapeHtml(route.title)}</h3>
      ${locality}
      <div class="itinerary-popup-meta">
        ${escapeHtml(route.distance)} · ${escapeHtml(route.ascent)}<br>
        ${escapeHtml(route.duration)} · ${escapeHtml(route.difficulty)}
      </div>
      <a class="itinerary-explore" href="${escapeHtml(route.detailUrl)}">${escapeHtml(labels.explore || "Esplora")}</a>
    </div>`;
  }

  function setSelected(routeId) {
    selectedRouteId = routeId;
    document.querySelectorAll(".itinerary-map-card").forEach((card) => {
      card.classList.toggle("is-selected", card.dataset.routeId === routeId);
    });
    routeLayers.forEach((layer, id) => {
      const route = routeLookup.get(id);
      layer.setStyle({
        color: colorFor(route),
        weight: id === routeId ? 7 : 5,
        opacity: id === routeId ? 1 : 0.88
      });
      if (id === routeId) layer.bringToFront();
    });
  }

  function focusRoute(routeId, scrollToMap) {
    const route = routeLookup.get(routeId);
    const layer = routeLayers.get(routeId);
    if (!route || !layer) return;

    if (!isVisible(route)) {
      setFilter(route.category);
    }
    setSelected(routeId);
    const bounds = layer.getBounds();
    if (bounds.isValid()) {
      map.fitBounds(bounds.pad(0.18), { maxZoom: 15 });
      layer.openPopup(bounds.getCenter());
    }
    if (scrollToMap) {
      mapElement.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  }

  function fitVisibleRoutes() {
    const bounds = L.latLngBounds();
    routeLayers.forEach((layer, id) => {
      const route = routeLookup.get(id);
      if (route && isVisible(route) && layer.getBounds().isValid()) bounds.extend(layer.getBounds());
    });
    if (bounds.isValid()) map.fitBounds(bounds.pad(0.08), { maxZoom: 15 });
    else map.setView([46.416824, 12.932648], 13);
  }

  function setFilter(filter) {
    activeFilter = filter;
    selectedRouteId = null;
    map.closePopup();

    document.querySelectorAll(".itinerary-map-filters button").forEach((button) => {
      const active = button.dataset.filter === filter;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", active ? "true" : "false");
    });
    document.querySelectorAll(".itinerary-map-card").forEach((card) => {
      card.hidden = filter !== "all" && card.dataset.category !== filter;
      card.classList.remove("is-selected");
    });
    routeLayers.forEach((layer, id) => {
      const route = routeLookup.get(id);
      if (route && isVisible(route)) layer.addTo(map);
      else layer.removeFrom(map);
    });
    fitVisibleRoutes();
  }

  async function loadRoute(route) {
    const response = await fetch(route.gpxUrl, { cache: "no-store" });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const xml = new DOMParser().parseFromString(await response.text(), "text/xml");
    const geojson = toGeoJSON.gpx(xml);
    const features = (geojson.features || []).filter((feature) => {
      const type = feature && feature.geometry ? feature.geometry.type : "";
      return type === "LineString" || type === "MultiLineString";
    });
    if (!features.length) throw new Error("GPX senza tracce");

    const layer = L.geoJSON({ type: "FeatureCollection", features }, {
      style: { color: colorFor(route), weight: 5, opacity: 0.82 }
    });
    layer.bindPopup(popupHtml(route), { maxWidth: 320 });
    layer.on("click", function () { setSelected(route.id); });
    routeLayers.set(route.id, layer);
    if (isVisible(route)) layer.addTo(map);
  }

  document.querySelectorAll(".itinerary-map-filters button").forEach((button) => {
    button.addEventListener("click", function () { setFilter(this.dataset.filter || "all"); });
  });
  document.querySelectorAll(".itinerary-map-focus").forEach((button) => {
    button.addEventListener("click", function () { focusRoute(this.dataset.routeId || "", true); });
  });
  document.querySelectorAll(".itinerary-map-card").forEach((card) => {
    const route = routeLookup.get(card.dataset.routeId || "");
    card.style.setProperty("--route-color", colorFor(route));
  });

  Promise.allSettled(routes.map(loadRoute)).then((results) => {
    const loaded = results.filter((result) => result.status === "fulfilled").length;
    if (statusElement) {
      statusElement.textContent = loaded > 0
        ? `${loaded} ${labels.loaded || "itinerari visualizzati"}`
        : (labels.mapError || "Nessuna traccia GPX disponibile.");
      statusElement.classList.toggle("is-error", loaded === 0);
    }
    fitVisibleRoutes();
  });
})();
