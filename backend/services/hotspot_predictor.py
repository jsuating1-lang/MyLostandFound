import numpy as np
from sklearn.cluster import DBSCAN
from collections import Counter

class HotspotPredictor:
    """
    Predicts loss hotspots from historical lost-item coordinates.
    Uses DBSCAN clustering + density scoring.
    """

    def __init__(self, eps_km: float = 0.05, min_samples: int = 3):
        self.eps_km = eps_km
        self.min_samples = min_samples

    def _km_to_deg(self, km: float) -> float:
        return km / 111.0  # rough conversion

    def predict_hotspots(self, points: list[dict]) -> list[dict]:
        """
        points: [{"lat": float, "lng": float, "category": str}, ...]
        returns ranked hotspot zones
        """
        if len(points) < self.min_samples:
            return []

        coords = np.array([[p["lat"], p["lng"]] for p in points])
        eps = self._km_to_deg(self.eps_km)

        clustering = DBSCAN(eps=eps, min_samples=self.min_samples).fit(coords)
        labels = clustering.labels_

        hotspots = []
        for label in set(labels):
            if label == -1:
                continue

            cluster_points = [points[i] for i, l in enumerate(labels) if l == label]
            lats = [p["lat"] for p in cluster_points]
            lngs = [p["lng"] for p in cluster_points]

            categories = Counter(p.get("category", "unknown") for p in cluster_points)
            top_category = categories.most_common(1)[0][0]

            hotspots.append({
                "center_lat": float(np.mean(lats)),
                "center_lng": float(np.mean(lngs)),
                "incident_count": len(cluster_points),
                "risk_score": min(len(cluster_points) / 10.0, 1.0),
                "top_category": top_category,
                "radius_km": self.eps_km,
            })

        hotspots.sort(key=lambda h: h["risk_score"], reverse=True)
        return hotspots

    def suggest_watch_zones(self, hotspots: list[dict], top_n: int = 5) -> list[dict]:
        return hotspots[:top_n]