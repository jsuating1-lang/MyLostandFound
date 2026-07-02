<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hotspot Map — Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <a href="index.php" class="back-btn">← Back to Home</a>

      <div class="card">
        <h1>🗺️ Loss Hotspots Map</h1>
        <p class="text-secondary">Campus areas with the highest frequency of reported lost items. Higher risk zones are shown in orange.</p>

        <div id="map" style="width:100%;height:520px;border-radius:12px;margin:1.5rem 0;border:1px solid var(--border);"></div>

        <div id="hotspots-list">
          <div class="flex flex-center" style="padding: 2rem;">
            <p class="text-muted">Loading hotspots...</p>
          </div>
        </div>
      </div>

      <footer class="site-footer">
        <small>Campus Lost & Found — Hotspot Analysis</small>
      </footer>
    </div>
  </div>

  <!-- Leaflet library -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    const API_BASE = 'http://127.0.0.1:8000';
    const hotspotsEl = document.getElementById('hotspots-list');

    // Center for Passi City College (exact coords provided by user)
    const PASSI_CENTER = { lat: 11.1329477, lng: 122.6473106 };
    
    // Bounding box around Passi City College (~2km radius by default)
    const PASSI_BOUNDS = L.latLngBounds([
      [PASSI_CENTER.lat - 0.02, PASSI_CENTER.lng - 0.02],
      [PASSI_CENTER.lat + 0.02, PASSI_CENTER.lng + 0.02]
    ]);

    // Initialize map after Leaflet loads
    function initMap() {
      if (typeof L === 'undefined') {
        hotspotsEl.innerHTML = '<div class="error-msg">⚠️ Map failed to load (Leaflet library not available).</div>';
        return;
      }

      const map = L.map('map', { maxBoundsViscosity: 0.8 }).setView([PASSI_CENTER.lat, PASSI_CENTER.lng], 14);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      // Restrict view to the Passi area
      map.fitBounds(PASSI_BOUNDS);
      map.setMaxBounds(PASSI_BOUNDS.pad(0.1));

      // Add a marker for the center
      L.marker([PASSI_CENTER.lat, PASSI_CENTER.lng], {
        title: 'Campus Center'
      }).addTo(map).bindPopup('<strong>Passi City College</strong><br>Campus Center');

      window._clf_map = map; // Expose for debugging
    }

    async function loadHotspots() {
      try {
        const res = await fetch(API_BASE + '/hotspots');
        const data = await res.json();
        const hotspots = data.hotspots || [];

        if (hotspots.length === 0) {
          hotspotsEl.innerHTML = '<p class="text-muted">No hotspots found. Not enough data yet.</p>';
          return;
        }

        // Add markers / circles only if inside PASSI_BOUNDS
        const visible = [];
        hotspots.forEach(h => {
          const lat = parseFloat(h.center_lat);
          const lng = parseFloat(h.center_lng);
          if (!PASSI_BOUNDS.contains([lat, lng])) return; // Skip hotspots outside campus area
          
          visible.push(h);
          const radiusMeters = (h.radius_km || 0.05) * 1000;
          const riskScore = h.risk_score || 0;
          
          // Color intensity based on risk score
          const color = riskScore > 0.7 ? '#ef4444' : riskScore > 0.4 ? '#f97316' : '#eab308';
          const fillColor = riskScore > 0.7 ? '#fecaca' : riskScore > 0.4 ? '#ffd7b5' : '#fef3c7';
          
          const circle = L.circle([lat, lng], {
            radius: radiusMeters,
            color: color,
            fillColor: fillColor,
            fillOpacity: 0.4,
            weight: 2
          }).addTo(window._clf_map);

          const popupHtml = `
            <strong>Loss Hotspot</strong><br>
            <strong>Incidents:</strong> ${h.incident_count}<br>
            <strong>Top Category:</strong> ${h.top_category}<br>
            <strong>Risk Level:</strong> ${Math.round(riskScore * 100)}%
          `;
          circle.bindPopup(popupHtml);
        });

        if (visible.length === 0) {
          hotspotsEl.innerHTML = '<p class="text-muted">No hotspots found within campus area.</p>';
        } else {
          const html = `
            <h3 class="mt-3 mb-2">Active Hotspots (${visible.length})</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
              ${visible.map(h => `
                <div class="card">
                  <p><strong>Location:</strong><br><small>${h.center_lat.toFixed(4)}, ${h.center_lng.toFixed(4)}</small></p>
                  <p><strong>Incidents:</strong> ${h.incident_count}</p>
                  <p><strong>Top Category:</strong> ${h.top_category}</p>
                  <p><strong>Risk Level:</strong> <span style="font-weight: 600; color: ${h.risk_score > 0.7 ? '#ef4444' : h.risk_score > 0.4 ? '#f97316' : '#eab308'};">${Math.round((h.risk_score || 0) * 100)}%</span></p>
                </div>
              `).join('')}
            </div>
          `;
          hotspotsEl.innerHTML = html;
        }
      } catch (e) {
        hotspotsEl.innerHTML = '<div class="error-msg">⚠️ Error loading hotspots. Please try again.</div>';
      }
    }

    // Initialize map and load hotspots
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        initMap();
        loadHotspots();
      });
    } else {
      initMap();
      loadHotspots();
    }
  </script>
</body>
</html>
