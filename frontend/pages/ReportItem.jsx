import { useState, useRef } from "react";

const API_BASE = import.meta.env.VITE_API_URL || "http://localhost:8000";

const CATEGORIES = [
  "Electronics",
  "ID / Cards",
  "Keys",
  "Bags",
  "Clothing",
  "Books",
  "Accessories",
  "Sports Gear",
  "Other",
];

const DEFAULT_CENTER = { lat: 14.5995, lng: 120.9842 }; // change to your campus

export default function ReportItem() {
  const fileInputRef = useRef(null);

  const [form, setForm] = useState({
    title: "",
    description: "",
    category: "Other",
    status: "lost", // "lost" | "found"
    location_name: "",
    latitude: DEFAULT_CENTER.lat,
    longitude: DEFAULT_CENTER.lng,
    reported_by: 1, // replace with logged-in user id from auth context
  });

  const [imageFile, setImageFile] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(null);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  function handleImageChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      setError("Please upload a valid image file.");
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError("Image must be smaller than 5 MB.");
      return;
    }

    setError("");
    setImageFile(file);
    setImagePreview(URL.createObjectURL(file));
  }

  function clearImage() {
    setImageFile(null);
    setImagePreview(null);
    if (fileInputRef.current) fileInputRef.current.value = "";
  }

  function useCurrentLocation() {
    if (!navigator.geolocation) {
      setError("Geolocation is not supported by your browser.");
      return;
    }

    setLoading(true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setForm((prev) => ({
          ...prev,
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
        }));
        setLoading(false);
        setError("");
      },
      () => {
        setLoading(false);
        setError("Unable to get your location. Enter coordinates manually.");
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setSuccess(null);

    if (!form.title.trim()) {
      setError("Title is required.");
      return;
    }

    if (!form.location_name.trim()) {
      setError("Location name is required.");
      return;
    }

    if (!imageFile) {
      setError("Please upload a photo of the item.");
      return;
    }

    const payload = new FormData();
    payload.append("title", form.title.trim());
    payload.append("description", form.description.trim());
    payload.append("category", form.category);
    payload.append("status", form.status);
    payload.append("location_name", form.location_name.trim());
    payload.append("latitude", String(form.latitude));
    payload.append("longitude", String(form.longitude));
    payload.append("reported_by", String(form.reported_by));
    payload.append("image", imageFile);

    try {
      setLoading(true);

      const res = await fetch(`${API_BASE}/items`, {
        method: "POST",
        body: payload,
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        throw new Error(data.detail || "Failed to submit report.");
      }

      setSuccess({
        id: data.id,
        message: data.message || "Item reported successfully.",
      });

      // reset form
      setForm({
        title: "",
        description: "",
        category: "Other",
        status: "lost",
        location_name: "",
        latitude: DEFAULT_CENTER.lat,
        longitude: DEFAULT_CENTER.lng,
        reported_by: form.reported_by,
      });
      clearImage();
    } catch (err) {
      setError(err.message || "Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="report-item-page">
      <div className="report-item-card">
        <header>
          <h1>Report an Item</h1>
          <p>Upload a photo and location to help match lost and found items.</p>
        </header>

        <form onSubmit={handleSubmit} className="report-form">
          {/* Lost / Found toggle */}
          <fieldset className="status-toggle">
            <legend>Report type</legend>
            <label>
              <input
                type="radio"
                name="status"
                value="lost"
                checked={form.status === "lost"}
                onChange={handleChange}
              />
              I lost something
            </label>
            <label>
              <input
                type="radio"
                name="status"
                value="found"
                checked={form.status === "found"}
                onChange={handleChange}
              />
              I found something
            </label>
          </fieldset>

          {/* Basic info */}
          <div className="field">
            <label htmlFor="title">Item title *</label>
            <input
              id="title"
              name="title"
              type="text"
              placeholder="e.g. Black Jansport backpack"
              value={form.title}
              onChange={handleChange}
              required
            />
          </div>

          <div className="field">
            <label htmlFor="category">Category</label>
            <select
              id="category"
              name="category"
              value={form.category}
              onChange={handleChange}
            >
              {CATEGORIES.map((cat) => (
                <option key={cat} value={cat}>
                  {cat}
                </option>
              ))}
            </select>
          </div>

          <div className="field">
            <label htmlFor="description">Description</label>
            <textarea
              id="description"
              name="description"
              rows={4}
              placeholder="Color, brand, distinguishing marks, contents..."
              value={form.description}
              onChange={handleChange}
            />
          </div>

          {/* Image upload */}
          <div className="field">
            <label htmlFor="image">Photo *</label>
            <input
              id="image"
              ref={fileInputRef}
              type="file"
              accept="image/*"
              capture="environment"
              onChange={handleImageChange}
            />
            {imagePreview && (
              <div className="image-preview">
                <img src={imagePreview} alt="Preview" />
                <button type="button" onClick={clearImage}>
                  Remove photo
                </button>
              </div>
            )}
          </div>

          {/* Location */}
          <div className="field">
            <label htmlFor="location_name">Location name *</label>
            <input
              id="location_name"
              name="location_name"
              type="text"
              placeholder="e.g. Library 2nd floor, Gym entrance"
              value={form.location_name}
              onChange={handleChange}
              required
            />
          </div>

          <div className="coords-row">
            <div className="field">
              <label htmlFor="latitude">Latitude</label>
              <input
                id="latitude"
                name="latitude"
                type="number"
                step="any"
                value={form.latitude}
                onChange={handleChange}
              />
            </div>

            <div className="field">
              <label htmlFor="longitude">Longitude</label>
              <input
                id="longitude"
                name="longitude"
                type="number"
                step="any"
                value={form.longitude}
                onChange={handleChange}
              />
            </div>
          </div>

          <button
            type="button"
            className="secondary-btn"
            onClick={useCurrentLocation}
            disabled={loading}
          >
            Use my current location
          </button>

          {error && <p className="error-msg">{error}</p>}

          {success && (
            <div className="success-msg">
              <strong>Report submitted!</strong>
              <p>Reference ID: #{success.id}</p>
              <p>{success.message}</p>
            </div>
          )}

          <button type="submit" className="primary-btn" disabled={loading}>
            {loading ? "Submitting..." : "Submit report"}
          </button>
        </form>
      </div>

      <style>{`
        .report-item-page {
          min-height: 100vh;
          display: flex;
          justify-content: center;
          padding: 2rem 1rem;
          background: #f5f7fb;
        }

        .report-item-card {
          width: 100%;
          max-width: 640px;
          background: #fff;
          border-radius: 16px;
          padding: 2rem;
          box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .report-item-card h1 {
          margin: 0 0 0.5rem;
          font-size: 1.75rem;
        }

        .report-item-card p {
          margin: 0 0 1.5rem;
          color: #667085;
        }

        .report-form {
          display: flex;
          flex-direction: column;
          gap: 1rem;
        }

        .status-toggle {
          border: 1px solid #e4e7ec;
          border-radius: 12px;
          padding: 1rem;
          display: flex;
          gap: 1.5rem;
        }

        .status-toggle legend {
          padding: 0 0.25rem;
          font-weight: 600;
        }

        .field {
          display: flex;
          flex-direction: column;
          gap: 0.4rem;
        }

        .field label {
          font-weight: 600;
          font-size: 0.95rem;
        }

        .field input,
        .field select,
        .field textarea {
          border: 1px solid #d0d5dd;
          border-radius: 10px;
          padding: 0.75rem 0.9rem;
          font: inherit;
        }

        .coords-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 1rem;
        }

        .image-preview {
          margin-top: 0.75rem;
          display: flex;
          flex-direction: column;
          gap: 0.5rem;
        }

        .image-preview img {
          width: 100%;
          max-height: 240px;
          object-fit: cover;
          border-radius: 12px;
          border: 1px solid #e4e7ec;
        }

        .primary-btn,
        .secondary-btn {
          border: none;
          border-radius: 10px;
          padding: 0.85rem 1rem;
          font: inherit;
          cursor: pointer;
        }

        .primary-btn {
          background: #2563eb;
          color: white;
          font-weight: 600;
        }

        .primary-btn:disabled {
          opacity: 0.7;
          cursor: not-allowed;
        }

        .secondary-btn {
          background: #eef2ff;
          color: #3730a3;
          font-weight: 600;
        }

        .error-msg {
          color: #b42318;
          background: #fef3f2;
          border: 1px solid #fecdca;
          padding: 0.75rem 1rem;
          border-radius: 10px;
          margin: 0;
        }

        .success-msg {
          color: #027a48;
          background: #ecfdf3;
          border: 1px solid #abefc6;
          padding: 0.75rem 1rem;
          border-radius: 10px;
        }
      `}</style>
    </div>
  );
}