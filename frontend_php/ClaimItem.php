<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Claim an Item — Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <a href="index.php" class="back-btn">← Back to Home</a>

      <div style="max-width: 640px; margin: 2rem auto;">
        <div class="card">
          <h1>✅ Submit a Claim</h1>
          <p class="text-secondary">Provide proof to claim an item that belongs to you.</p>

          <form id="claim-form" class="mt-3">
            <div class="form-group">
              <label for="item_id">Item ID *</label>
              <input id="item_id" name="item_id" type="number" placeholder="e.g. 1, 2, 3..." required />
            </div>

            <div class="form-group">
              <label for="claimant_id">Your User ID *</label>
              <input id="claimant_id" name="claimant_id" type="number" placeholder="Your account ID" required />
            </div>

            <div class="form-group">
              <label for="student_id">Student ID *</label>
              <input id="student_id" name="student_id" type="text" placeholder="Your student ID" required />
            </div>

            <div class="form-group">
              <label for="proof_description">Proof of Ownership *</label>
              <textarea
                id="proof_description"
                name="proof_description"
                rows="5"
                placeholder="Describe distinctive features, serial numbers, or other proof that this item belongs to you..."
                required
              ></textarea>
              <small class="text-muted mt-1">Provide detailed information to help verify your claim</small>
            </div>

            <div id="error" class="error-msg" style="display:none;"></div>
            <div id="result" class="success-msg" style="display:none;"></div>

            <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Submit Claim</button>
          </form>
        </div>
      </div>

      <footer class="site-footer">
        <small>Campus Lost & Found — Claim an Item</small>
      </footer>
    </div>
  </div>

  <script>
    const API_BASE = 'http://127.0.0.1:8000';
    const form = document.getElementById('claim-form');
    const err = document.getElementById('error');
    const result = document.getElementById('result');
    const submitBtn = document.getElementById('submit-btn');

    function showError(msg) {
      err.innerHTML = '⚠️ ' + msg;
      err.style.display = '';
      result.style.display = 'none';
    }

    function showSuccess(data) {
      result.innerHTML = `<strong>✓ Claim submitted successfully!</strong><p>Claim ID: <strong>#${data.claim_id || 'pending'}</strong></p><p>Verification Status: ${data.verification_result || 'Pending review'}</p>`;
      result.style.display = '';
      err.style.display = 'none';
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      err.style.display = 'none';
      result.style.display = 'none';

      const item_id = document.getElementById('item_id').value.trim();
      const claimant_id = document.getElementById('claimant_id').value.trim();
      const student_id = document.getElementById('student_id').value.trim();
      const proof = document.getElementById('proof_description').value.trim();

      if (!item_id || !claimant_id || !student_id || proof.length < 10) {
        showError('Please fill all fields and provide detailed proof (at least 10 characters).');
        return;
      }

      const fd = new FormData();
      fd.append('item_id', item_id);
      fd.append('claimant_id', claimant_id);
      fd.append('student_id', student_id);
      fd.append('proof_description', proof);

      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';

      try {
        const res = await fetch(API_BASE + '/claims', { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.detail || 'Failed to submit claim');
        
        showSuccess(data);
        form.reset();
      } catch (errx) {
        showError(errx.message || 'An error occurred. Please try again.');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Claim';
      }
    });
  </script>
</body>
</html>
