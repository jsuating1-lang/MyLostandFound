import numpy as np
from PIL import Image
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity

class ImageSimilarityService:
    def __init__(self):
        # CLIP model for image + text embeddings
        self.model = SentenceTransformer("clip-ViT-B-32")

    def embed_image(self, image_path: str) -> list[float]:
        image = Image.open(image_path).convert("RGB")
        embedding = self.model.encode(image, convert_to_numpy=True)
        return embedding.tolist()

    def embed_text(self, query: str) -> list[float]:
        return self.model.encode(query, convert_to_numpy=True).tolist()

    def find_similar(
        self,
        query_embedding: np.ndarray,
        candidate_embeddings: list[np.ndarray],
        candidate_ids: list[int],
        top_k: int = 10,
        threshold: float = 0.65,
    ) -> list[dict]:
        if not candidate_embeddings:
            return []

        matrix = np.vstack(candidate_embeddings)
        scores = cosine_similarity([query_embedding], matrix)[0]

        results = []
        for idx, score in enumerate(scores):
            if score >= threshold:
                results.append({
                    "item_id": candidate_ids[idx],
                    "similarity_score": float(score),
                })

        results.sort(key=lambda x: x["similarity_score"], reverse=True)
        return results[:top_k]