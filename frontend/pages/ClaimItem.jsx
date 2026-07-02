import { useState, useRef } from "react";
import { submitClaim, verifyClaimCode } from "../api/client";

export default function ClaimItem() {
  const idPhotoRef = useRef(null);

  const [form, setForm] = useState({
    item_id: "",
    claimant_id: 1, // replace with logged-in user id
    student_id: "",
    proof_description: "",
  });

  const [idPhoto, setIdPhoto] = useState(null);
  const [idPhotoPreview, setIdPhotoPreview] = useState(null);

  const [verificationCode, setVerificationCode] = useState("");
  const [claimResult, setClaimResult] = useState(null);
  const [verifyResult, setVerifyResult] = useState(null);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  function handleIdPhotoChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      setError("Please upload a valid ID photo.");
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError("ID photo must be smaller than 5 MB.");
      return;
    }

    setError("");
    setIdPhoto(file);
    setIdPhotoPreview(URL.createObjectURL(file));
  }

  function clearIdPhoto() {
    setIdPhoto(null);
    setIdPhotoPreview(null);
    if (idPhotoRef.current) idPhotoRef.current.value = "";
  }

  async function handleSubmitClaim(e) {
    e.preventDefault();
    setError("");
    setClaimResult(null);
    setVerifyResult(null);

    if (!form.item_id.trim()) {
      setError("Item ID is required.");
      return;
    }

    if (!form.student_id.trim()) {
      setError("Student ID is required.");
      return;
    }

    if (!form.proof_description.trim()) {
      setError("Please describe how you can prove ownership.");
      return;
    }

    if (form.proof_description.trim().length < 20) {
      setError("Proof description must be at least 20 characters.");
      return;
    }

    try {
      setLoading(true);

      const data = await submitClaim({
        item_id: Number(form.item_id),
        claimant_id: Number(form.claimant_id),
        student_id: form.student_id.trim(),
        proof_description: form.proof_description.trim(),
        id_photo: idPhoto || undefined,
      });

      setClaimResult(data);
    } catch (err) {
      setError(err.message || "Failed to submit claim.");
    } finally {
      setLoading(false);
    }
  }

  async function handleVerifyCode(e) {
    e.preventDefault();
    setError("");
    setVerifyResult(null);

    if (!claimResult?.claim_id) {
      setError("Submit a claim first before verifying the code.");
      return;
    }

    if (!verificationCode.trim()) {
      setError("Enter the verification code sent to you.");
      return;
    }

    try {
      setLoading(true);

      const data = await verifyClaimCode(
        claimResult.claim_id,
        verificationCode.trim()
      );

      setVerifyResult(data);
    } catch (err) {
      setError(err.message || "Verification failed.");
    } finally {
      setLoading(false);
    }
  }

  const verification = claimResult?.verification_result;

  return (
    <div className="claim-item-page">
      <div className="claim-item-card">
        <header>
          <h1>Claim an Item</h1>
          <p>
            Submit proof of ownership. Claims are verified securely before items
            are returned.
          </p>
        </header>

        <form onSubmit={handleSubmitClaim} className="claim-form">
          <div className="field">
            <label htmlFor="item_id">Found item ID *</label>
            <input
              id="item_id"
              name="item_id"
              type="number"
              min="1"
              placeholder="e.g. 12"
              value={form.item_id}
              onChange={handleChange}
              required
            />
            <small>Use the item ID shown in search results or admin listing.</small>
          </div>

          <div className="field">
            <label htmlFor="student_id">Your student ID *</label>
            <input
              id="student_id"
              name="student_id"
              type="text"
              placeholder="e.g. 2024-00123"
              value={form.student_id}
              onChange={handleChange}
              required
            />
          </div>

          <div className="field">
            <label htmlFor="proof_description">Proof of ownership *</label>
            <textarea
              id="proof_description"
              name="proof_description"
              rows={5}
              placeholder="Describe unique marks, contents, serial numbers, name tags, case color, etc."
              value={form.proof_description}
              onChange={handleChange}
              required
            />
          </div>

          <div className="field">
            <label htmlFor="id_photo">School ID photo (optional)</label>
            <input
              id="id_photo"
              ref={idPhotoRef}
              type="file"
              accept="image/*"
              onChange={handleIdPhotoChange}
            />
            {idPhotoPreview && (
              <div className="photo-preview">
                <img src={idPhotoPreview} alt="ID preview" />
                <button type="button" onClick={clearIdPhoto}>
                  Remove photo
                </button>
              </div>
            )}
          </div>

          {error && <p className="error-msg">{error}</p>}

          <button type="submit" className="primary-btn" disabled={loading}>
            {loading ? "Submitting..." : "Submit claim"}
          </button>
        </form>

        {claimResult && (
          <section className="result-box">
            <h2>Claim submitted</h2>
            <p>
              <strong>Claim ID:</strong> #{claimResult.claim_id}
            </p>

            {verification && (
              <>
                <p>
                  <strong>Status:</strong>{" "}
                  <span className={`status-badge ${verification.status}`}>
                    {verification.status}
                  </span>
                </p>
                <p>
                  <strong>Verification score:</strong>{" "}
                  {(verification.verification_score * 100).toFixed(0)}%
                </p>

                {verification.reasons?.length > 0 && (
                  <ul>
                    {verification.reasons.map((reason, index) => (
                      <li key={index}>{reason}</li>
                    ))}
                  </ul>
                )}

                {verification.requires_admin_review && (
                  <p className="info-msg">
                    This claim requires admin review before the item can be released.
                  </p>
                )}
              </>
            )}

            {/* Demo only — remove when OTP is sent via email/SMS */}
            {claimResult.verification_code_sent && (
              <p className="demo-code">
                Demo verification code:{" "}
                <strong>{claimResult.verification_code_sent}</strong>
              </p>
            )}
          </section>
        )}

        {claimResult && (
          <section className="verify-section">
            <h2>Verify claim code</h2>
            <p>Enter the one-time code sent to your registered email or phone.</p>

            <form onSubmit={handleVerifyCode} className="verify-form">
              <div className="field">
                <label htmlFor="verification_code">Verification code</label>
                <input
                  id="verification_code"
                  type="text"
                  maxLength={6}
                  placeholder="6-digit code"
                  value={verificationCode}
                  onChange={(e) => setVerificationCode(e.target.value)}
                />
              </div>

              <button type="submit" className="secondary-btn" disabled={loading}>
                {loading ? "Verifying..." : "Verify code"}
              </button>
            </form>

            {verifyResult && (
              <div className="success-msg">
                <strong>{verifyResult.message || "Claim verified successfully."}</strong>
              </div>
            )}
          </section>
        )}
      </div>

      <style>{`
        .claim-item-page {
          min-height: 100vh;
          display: flex;
          justify-content: center;
          padding: 2rem 1rem;
          background: #f5f7fb;
        }

        .claim-item-card {
          width: 100%;
          max-width: 720px;
          background: #fff;
          border-radius: 16px;
          padding: 2rem;
          box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .claim-item-card h1 {
          margin: 0 0 0.5rem;
          font-size: 1.75rem;
        }

        .claim-item-card p {
          margin: 0;
          color: #667085;
        }

        header {
          margin-bottom: 1.5rem;
        }

        .claim-form,
        .verify-form {
          display: flex;
          flex-direction: column;
          gap: 1rem;
        }

        .field {
          display: flex;
          flex-direction: column;
          gap: 0.4rem;
        }

        .field label {
          font-weight: 600;
        }

        .field small {
          color: #667085;
          font-size: 0.85rem;
        }

        .field input,
        .field textarea {
          border: 1px solid #d0d5dd;
          border-radius: 10px;
          padding: 0.75rem 0.9rem;
          font: inherit;
        }

        .photo-preview {
          display: flex;
          flex-direction: column;
          gap: 0.5rem;
          margin-top: 0.5rem;
        }

        .photo-preview img {
          width: 100%;
          max-height: 220px;
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
          font-weight: 600;
        }

        .primary-btn {
          background: #2563eb;
          color: white;
        }

        .secondary-btn {
          background: #eef2ff;
          color: #3730a3;
        }

        .primary-btn:disabled,
        .secondary-btn:disabled {
          opacity: 0.7;
          cursor: not-allowed;
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
          margin-top: 1rem;
        }

        .info-msg {
          color: #b54708;
          background: #fffaeb;
          border: 1px solid #fedf89;
          padding: 0.75rem 1rem;
          border-radius: 10px;
        }

        .result-box,
        .verify-section {
          margin-top: 2rem;
          padding-top: 1.5rem;
          border-top: 1px solid #e4e7ec;
        }

        .result-box h2,
        .verify-section h2 {
          margin-top: 0;
          font-size: 1.2rem;
        }

        .status-badge {
          display: inline-block;
          padding: 0.2rem 0.6rem;
          border-radius: 999px;
          font-size: 0.85rem;
          text-transform: capitalize;
        }

        .status-badge.verified {
          background: #ecfdf3;
          color: #027a48;
        }

        .status-badge.pending {
          background: #fffaeb;
          color: #b54708;
        }

        .demo-code {
          margin-top: 1rem;
          padding: 0.75rem 1rem;
          background: #f8fafc;
          border: 1px dashed #cbd5e1;
          border-radius: 10px;
          color: #334155;
        }
      `}</style>
    </div>
  );
}