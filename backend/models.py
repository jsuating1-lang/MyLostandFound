from sqlalchemy import Column, Integer, String, Float, DateTime, Text, Enum, ForeignKey
from sqlalchemy.orm import relationship
try:
    from pgvector.sqlalchemy import Vector
except Exception:
    Vector = None

from datetime import datetime
import enum
from .database import Base

class ItemStatus(str, enum.Enum):
    LOST = "lost"
    FOUND = "found"
    CLAIMED = "claimed"
    RETURNED = "returned"

class ClaimStatus(str, enum.Enum):
    PENDING = "pending"
    VERIFIED = "verified"
    REJECTED = "rejected"

class User(Base):
    __tablename__ = "users"
    id = Column(Integer, primary_key=True)
    email = Column(String(255), unique=True, nullable=False)
    password_hash = Column(String(255), nullable=False)
    student_id = Column(String(50), unique=True)
    full_name = Column(String(255))

class Item(Base):
    __tablename__ = "items"
    id = Column(Integer, primary_key=True)
    title = Column(String(255), nullable=False)
    description = Column(Text)
    category = Column(String(100))
    status = Column(Enum(ItemStatus), nullable=False)
    image_path = Column(String(500))
    # If pgvector is available, use Vector; otherwise store embedding as JSON text.
    if Vector is not None:
        embedding = Column(Vector(512))  # CLIP embedding dimension
    else:
        from sqlalchemy import Text

        embedding = Column(Text)
    latitude = Column(Float)
    longitude = Column(Float)
    location_name = Column(String(255))
    reported_by = Column(Integer, ForeignKey("users.id"))
    created_at = Column(DateTime, default=datetime.utcnow)
    claims = relationship("Claim", back_populates="item")

class Claim(Base):
    __tablename__ = "claims"
    id = Column(Integer, primary_key=True)
    item_id = Column(Integer, ForeignKey("items.id"))
    claimant_id = Column(Integer, ForeignKey("users.id"))
    status = Column(Enum(ClaimStatus), default=ClaimStatus.PENDING)
    verification_code = Column(String(6))
    proof_description = Column(Text)
    id_photo_path = Column(String(500))
    created_at = Column(DateTime, default=datetime.utcnow)
    item = relationship("Item", back_populates="claims")