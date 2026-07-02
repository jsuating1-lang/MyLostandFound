# Campus Lost & Found Web System

An AI-powered campus lost and found platform that helps students and staff report, search, and reclaim items using **image similarity matching**, **predictive loss hotspot mapping**, and **secure claim verification**.

---

## Features

- **Report lost/found items** with photo, description, category, and location
- **Search by image** using CLIP-based visual similarity matching
- **Loss hotspot map** that clusters historical reports and highlights high-risk campus zones
- **Secure claim verification** with proof-of-ownership checks and OTP verification
- **Admin-ready workflow** for pending claims and audit tracking

---

## Tech Stack

### Frontend
- React
- Vite
- React Router
- Leaflet (campus hotspot map)
- Fetch API via centralized `client.js`

### Backend
- Python FastAPI
- PostgreSQL + pgvector
- SQLAlchemy
- Sentence Transformers (CLIP: `clip-ViT-B-32`)
- scikit-learn (DBSCAN hotspot clustering)
- Pillow (image processing)

---

## Project Structure

## PHP Frontend (optional)

A minimal PHP-based frontend mirror of the React `ReportItem` page is available under `frontend_php/`. It's useful if you prefer server-rendered pages or a lightweight PHP deployment.

Run the builtin PHP server from the project root to serve the PHP frontend on port 8001:

```bash
php -S localhost:8001 -t frontend_php
# then open http://localhost:8001
```

The `report_item.php` page submits to the same backend endpoints used by the React app (defaults to `http://localhost:8000/items`). Update the `API_BASE` constant in `frontend_php/report_item.php` if your backend runs elsewhere.
