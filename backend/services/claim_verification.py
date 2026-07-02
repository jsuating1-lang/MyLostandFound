import secrets
import hashlib
from datetime import datetime, timedelta

class ClaimVerificationService:
    CODE_LENGTH = 6
    CODE_TTL_MINUTES = 30

    def generate_verification_code(self) -> str:
        return f"{secrets.randbelow(10**self.CODE_LENGTH):0{self.CODE_LENGTH}d}"

    def hash_code(self, code: str) -> str:
        return hashlib.sha256(code.encode()).hexdigest()

    def verify_code(self, stored_hash: str, provided_code: str) -> bool:
        return stored_hash == self.hash_code(provided_code)

    def validate_claim(
        self,
        item_description: str,
        claimant_proof: str,
        claimant_student_id: str,
        registered_student_id: str,
    ) -> dict:
        """
        Multi-factor claim validation:
        1. Student ID match
        2. Proof description relevance (simple keyword overlap; upgrade with NLP)
        3. Admin review flag for high-value items
        """
        score = 0.0
        reasons = []

        if claimant_student_id == registered_student_id:
            score += 0.4
            reasons.append("Student ID verified")

        item_words = set(item_description.lower().split())
        proof_words = set(claimant_proof.lower().split())
        overlap = len(item_words & proof_words)
        if overlap >= 2:
            score += 0.4
            reasons.append("Ownership description matches item details")
        elif overlap == 1:
            score += 0.2
            reasons.append("Partial description match")

        if len(claimant_proof.strip()) >= 20:
            score += 0.2
            reasons.append("Detailed proof provided")

        status = "verified" if score >= 0.7 else "pending"

        return {
            "verification_score": round(score, 2),
            "status": status,
            "reasons": reasons,
            "requires_admin_review": score < 0.7,
        }