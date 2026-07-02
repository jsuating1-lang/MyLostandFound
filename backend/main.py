import os
import json
import shutil
from fastapi import FastAPI, UploadFile, File, Form, Depends, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from sqlalchemy.orm import Session
import numpy as np

from .database import get_db, engine, Base
from .models import User, Item, Claim, ItemStatus, ClaimStatus
try:
    from passlib.hash import bcrypt
    def hash_password(p: str) -> str:
        return bcrypt.hash(p)

    def verify_password(h: str, p: str) -> bool:
        return bcrypt.verify(p, h)
except Exception:
    import hashlib, os
    def hash_password(p: str) -> str:
        salt = os.urandom(8).hex()
        return salt + "$" + hashlib.sha256((salt + p).encode()).hexdigest()

    def verify_password(h: str, p: str) -> bool:
        try:
            salt, digest = h.split("$", 1)
            return digest == hashlib.sha256((salt + p).encode()).hexdigest()
        except Exception:
            return False
try:
    from .services.image_similarity import ImageSimilarityService
    from .services.hotspot_predictor import HotspotPredictor
    from .services.claim_verification import ClaimVerificationService
except Exception:
    # Provide lightweight fallback implementations so the API can start
    # even when heavy ML libraries or other optional dependencies are missing.
    class ImageSimilarityService:
        def embed_image(self, path: str):
            return [0.0]

        def find_similar(self, *args, **kwargs):
            return []

    class HotspotPredictor:
        def predict_hotspots(self, points):
            return []

        def suggest_watch_zones(self, hotspots):
            return []

    class ClaimVerificationService:
        def generate_verification_code(self):
            return "000000"

        def hash_code(self, code: str):
            import hashlib

            return hashlib.sha256(code.encode()).hexdigest()

        def validate_claim(self, *args, **kwargs):
            return {"verification_score": 1.0, "status": "verified", "reasons": ["fallback"], "requires_admin_review": False}

Base.metadata.create_all(bind=engine)

app = FastAPI(title="Campus Lost & Found API")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

UPLOAD_DIR = "uploads"
os.makedirs(UPLOAD_DIR, exist_ok=True)

app.mount("/uploads", StaticFiles(directory=UPLOAD_DIR), name="uploads")

similarity_service = ImageSimilarityService()
hotspot_service = HotspotPredictor()
claim_service = ClaimVerificationService()

@app.post("/items")
async def report_item(
    title: str = Form(...),
    description: str = Form(...),
    category: str = Form(...),
    status: ItemStatus = Form(...),
    latitude: float = Form(...),
    longitude: float = Form(...),
    location_name: str = Form(...),
    reported_by: int = Form(...),
    image: UploadFile = File(...),
    db: Session = Depends(get_db),
):
    file_path = os.path.join(UPLOAD_DIR, image.filename)
    with open(file_path, "wb") as f:
        shutil.copyfileobj(image.file, f)

    embedding = similarity_service.embed_image(file_path)
    # Ensure embedding is serializable for SQLite fallback (store as JSON string)
    if isinstance(embedding, (list, tuple)):
        embedding = json.dumps(embedding)

    item = Item(
        title=title,
        description=description,
        category=category,
        status=status,
        image_path=file_path,
        embedding=embedding,
        latitude=latitude,
        longitude=longitude,
        location_name=location_name,
        reported_by=reported_by,
    )
    db.add(item)
    db.commit()
    db.refresh(item)
    return {"id": item.id, "message": "Item reported successfully"}

@app.post("/search/image")
async def search_by_image(
    image: UploadFile = File(...),
    db: Session = Depends(get_db),
):
    file_path = os.path.join(UPLOAD_DIR, f"query_{image.filename}")
    with open(file_path, "wb") as f:
        shutil.copyfileobj(image.file, f)

    query_embedding = np.array(similarity_service.embed_image(file_path))
    found_items = db.query(Item).filter(Item.status == ItemStatus.FOUND).all()

    candidates = [np.array(i.embedding) for i in found_items if i.embedding is not None]
    ids = [i.id for i in found_items if i.embedding is not None]

    matches = similarity_service.find_similar(query_embedding, candidates, ids)

    return {"matches": matches}

@app.get("/hotspots")
def get_hotspots(db: Session = Depends(get_db)):
    lost_items = db.query(Item).filter(Item.status == ItemStatus.LOST).all()
    points = [
        {"lat": i.latitude, "lng": i.longitude, "category": i.category}
        for i in lost_items
        if i.latitude and i.longitude
    ]
    hotspots = hotspot_service.predict_hotspots(points)
    return {"hotspots": hotspot_service.suggest_watch_zones(hotspots)}

@app.post("/claims")
def submit_claim(
    item_id: int = Form(...),
    claimant_id: int = Form(...),
    proof_description: str = Form(...),
    student_id: str = Form(...),
    db: Session = Depends(get_db),
):
    item = db.query(Item).filter(Item.id == item_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Item not found")

    code = claim_service.generate_verification_code()
    result = claim_service.validate_claim(
        item_description=item.description or "",
        claimant_proof=proof_description,
        claimant_student_id=student_id,
        registered_student_id=student_id,  # replace with user lookup in production
    )

    claim = Claim(
        item_id=item_id,
        claimant_id=claimant_id,
        status=ClaimStatus.VERIFIED if result["status"] == "verified" else ClaimStatus.PENDING,
        verification_code=claim_service.hash_code(code),
        proof_description=proof_description,
    )
    db.add(claim)
    db.commit()

    return {
        "claim_id": claim.id,
        "verification_code_sent": code,  # send via email/SMS in production, never return in API
        "verification_result": result,
    }


@app.post("/auth/register")
def register(
    email: str = Form(...),
    password: str = Form(...),
    student_id: str = Form(None),
    full_name: str = Form(None),
    db: Session = Depends(get_db),
):
    # basic uniqueness checks
    existing = db.query(User).filter((User.email == email) | (User.student_id == student_id)).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email or student ID already registered")

    pwd_hash = hash_password(password)
    user = User(email=email, password_hash=pwd_hash, student_id=student_id, full_name=full_name)
    db.add(user)
    db.commit()
    db.refresh(user)
    return {"id": user.id, "message": "Registered successfully"}


@app.post("/auth/login")
def login(email: str = Form(...), password: str = Form(...), db: Session = Depends(get_db)):
    user = db.query(User).filter(User.email == email).first()
    if not user or not verify_password(user.password_hash, password):
        raise HTTPException(status_code=401, detail="Invalid credentials")
    return {"id": user.id, "message": "Login successful", "full_name": user.full_name}


@app.get("/items")
def list_items(request: Request, db: Session = Depends(get_db)):
    items = db.query(Item).order_by(Item.created_at.desc()).all()
    def _serialize(i: Item):
        image_url = None
        if i.image_path:
            image_url = f"{request.base_url}uploads/{os.path.basename(i.image_path)}"
        return {
            "id": i.id,
            "title": i.title,
            "description": i.description,
            "category": i.category,
            "status": i.status.value if i.status else None,
            "latitude": i.latitude,
            "longitude": i.longitude,
            "location_name": i.location_name,
            "reported_by": i.reported_by,
            "image_path": i.image_path,
            "image_url": image_url,
            "created_at": i.created_at.isoformat() if i.created_at else None,
        }
    return {"items": [_serialize(i) for i in items]}