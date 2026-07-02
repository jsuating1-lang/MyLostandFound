const API_BASE = import.meta.env.VITE_API_URL || "http://localhost:8000";

/**
 * Shared fetch wrapper with JSON + error handling.
 */
async function request(path, options = {}) {
  const { headers = {}, body, ...rest } = options;

  const isFormData = body instanceof FormData;

  const res = await fetch(`${API_BASE}${path}`, {
    ...rest,
    body,
    headers: {
      ...(isFormData ? {} : { "Content-Type": "application/json" }),
      ...getAuthHeaders(),
      ...headers,
    },
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    const message =
      data.detail ||
      data.message ||
      (typeof data === "string" ? data : "Request failed");

    throw new Error(
      Array.isArray(message)
        ? message.map((m) => m.msg || m).join(", ")
        : message
    );
  }

  return data;
}

/**
 * Attach JWT token when auth is added later.
 */
function getAuthHeaders() {
  const token = localStorage.getItem("access_token");
  return token ? { Authorization: `Bearer ${token}` } : {};
}

/* =========================
   Items
========================= */

/**
 * Report a lost or found item.
 */
export async function reportItem({
  title,
  description,
  category,
  status, // "lost" | "found"
  location_name,
  latitude,
  longitude,
  reported_by,
  image,
}) {
  const formData = new FormData();
  formData.append("title", title);
  formData.append("description", description || "");
  formData.append("category", category);
  formData.append("status", status);
  formData.append("location_name", location_name);
  formData.append("latitude", String(latitude));
  formData.append("longitude", String(longitude));
  formData.append("reported_by", String(reported_by));
  formData.append("image", image);

  return request("/items", {
    method: "POST",
    body: formData,
  });
}

/**
 * Get all items (optional endpoint — add on backend if needed).
 */
export async function getItems(params = {}) {
  const query = new URLSearchParams(params).toString();
  const path = query ? `/items?${query}` : "/items";
  return request(path);
}

/**
 * Get one item by ID (optional endpoint).
 */
export async function getItemById(itemId) {
  return request(`/items/${itemId}`);
}

/* =========================
   Image Search
========================= */

/**
 * Search found items using an uploaded image.
 */
export async function searchByImage(imageFile) {
  const formData = new FormData();
  formData.append("image", imageFile);

  return request("/search/image", {
    method: "POST",
    body: formData,
  });
}

/* =========================
   Hotspots
========================= */

/**
 * Get predictive loss hotspot zones.
 */
export async function getHotspots() {
  return request("/hotspots");
}

/* =========================
   Claims
========================= */

/**
 * Submit a claim for a found item.
 */
export async function submitClaim({
  item_id,
  claimant_id,
  proof_description,
  student_id,
  id_photo, // optional File
}) {
  const formData = new FormData();
  formData.append("item_id", String(item_id));
  formData.append("claimant_id", String(claimant_id));
  formData.append("proof_description", proof_description);
  formData.append("student_id", student_id);

  if (id_photo) {
    formData.append("id_photo", id_photo);
  }

  return request("/claims", {
    method: "POST",
    body: formData,
  });
}

/**
 * Verify claim with OTP/code (optional endpoint).
 */
export async function verifyClaimCode(claimId, code) {
  return request(`/claims/${claimId}/verify`, {
    method: "POST",
    body: JSON.stringify({ code }),
  });
}

/* =========================
   Auth helpers (optional)
========================= */

export async function login(email, password) {
  const data = await request("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });

  if (data.access_token) {
    localStorage.setItem("access_token", data.access_token);
  }

  return data;
}

export function logout() {
  localStorage.removeItem("access_token");
}

export function isAuthenticated() {
  return Boolean(localStorage.getItem("access_token"));
}

/* =========================
   Default export
========================= */

const api = {
  reportItem,
  getItems,
  getItemById,
  searchByImage,
  getHotspots,
  submitClaim,
  verifyClaimCode,
  login,
  logout,
  isAuthenticated,
};

export default api;