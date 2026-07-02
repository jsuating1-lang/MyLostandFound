import { MapContainer, TileLayer, CircleMarker, Popup } from "react-leaflet";
import { useEffect, useState } from "react";

export default function HotspotMap() {
  const [hotspots, setHotspots] = useState([]);

  useEffect(() => {
    fetch("http://localhost:8000/hotspots")
      .then((res) => res.json())
      .then((data) => setHotspots(data.hotspots));
  }, []);

  return (
    <MapContainer center={[14.5995, 120.9842]} zoom={16} style={{ height: "500px" }}>
      <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
      {hotspots.map((h, i) => (
        <CircleMarker
          key={i}
          center={[h.center_lat, h.center_lng]}
          radius={20 + h.risk_score * 30}
          pathOptions={{ color: "red", fillOpacity: 0.4 }}
        >
          <Popup>
            <strong>Loss Hotspot</strong><br />
            Incidents: {h.incident_count}<br />
            Top category: {h.top_category}<br />
            Risk: {(h.risk_score * 100).toFixed(0)}%
          </Popup>
        </CircleMarker>
      ))}
    </MapContainer>
  );
}