<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Report an Item — Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <a href="index.php" class="back-btn">← Back to Home</a>

      <div style="max-width: 640px; margin: 2rem auto;">
        <div class="card">
          <h1>📝 Report an Item</h1>
          <p class="text-secondary">Help reunite lost and found items by providing accurate information and a clear photo.</p>

          <form id="report-form" enctype="multipart/form-data" class="mt-3">
            <fieldset class="status-toggle">
              <legend>What are you reporting?</legend>
              <label>
                <input type="radio" name="status" value="lost" checked>
                <span>I lost something</span>
              </label>
              <label>
                <input type="radio" name="status" value="found">
                <span>I found something</span>
              </label>
            </fieldset>

            <div class="form-group">
              <label for="title">Item Title *</label>
              <input id="title" name="title" type="text" placeholder="e.g. Black Jansport backpack" required>
            </div>

            <div class="form-group">
              <label for="category">Category</label>
              <select id="category" name="category">
                <option>Electronics</option>
                <option>ID / Cards</option>
                <option>Keys</option>
                <option>Bags</option>
                <option>Clothing</option>
                <option>Books</option>
                <option>Accessories</option>
                <option>Sports Gear</option>
                <option selected>Other</option>
              </select>
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea id="description" name="description" rows="4" placeholder="Color, brand, distinguishing marks, contents..."></textarea>
            </div>

            <div class="form-group">
              <label for="image">Photo of Item *</label>
              <input id="image" type="file" accept="image/*" capture="environment" required>
              <div id="image-preview" class="image-preview" style="display:none;"></div>
            </div>

            <div class="form-group">
              <label for="location_name">Location Name *</label>
              <input id="location_name" name="location_name" type="text" placeholder="e.g. Library 2nd floor, Gym entrance" required>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr;">
              <div class="form-group">
                <label for="latitude">Latitude</label>
                <input id="latitude" name="latitude" type="number" step="any" readonly>
              </div>
              <div class="form-group">
                <label for="longitude">Longitude</label>
                <input id="longitude" name="longitude" type="number" step="any" readonly>
              </div>
            </div>

            <button type="button" id="use-location" class="btn btn-secondary" style="width: 100%;">📍 Use My Current Location</button>

            <div id="error-msg" class="error-msg" style="display:none;"></div>
            <div id="success-msg" class="success-msg" style="display:none;"></div>

            <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Submit Report</button>
          </form>
        </div>
      </div>

      <footer class="site-footer">
        <small>Campus Lost & Found — Report an Item</small>
      </footer>
    </div>
  </div>

  <script>
    const API_BASE = 'http://localhost:8000';
    const DEFAULT_CENTER = { lat: 14.5995, lng: 120.9842 };

    const formEl = document.getElementById('report-form');
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const errorMsg = document.getElementById('error-msg');
    const successMsg = document.getElementById('success-msg');
    const submitBtn = document.getElementById('submit-btn');
    const useLocationBtn = document.getElementById('use-location');

    // Initialize coordinates with default center
    document.getElementById('latitude').value = DEFAULT_CENTER.lat;
    document.getElementById('longitude').value = DEFAULT_CENTER.lng;

    let selectedFile = null;

    imageInput.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return clearImage();
      if (!file.type.startsWith('image/')) {
        showError('Please upload a valid image file.');
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        showError('Image must be smaller than 5 MB.');
        return;
      }
      clearError();
      selectedFile = file;
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Preview';
      imagePreview.innerHTML = '';
      imagePreview.appendChild(img);
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.classList.add('btn', 'btn-secondary');
      removeBtn.textContent = '✕ Remove Photo';
      removeBtn.style.marginTop = '0.5rem';
      removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        clearImage();
      });
      imagePreview.appendChild(removeBtn);
      imagePreview.style.display = '';
    });

    function clearImage() {
      selectedFile = null;
      imageInput.value = '';
      imagePreview.innerHTML = '';
      imagePreview.style.display = 'none';
    }

    function showError(msg) {
      errorMsg.innerHTML = '⚠️ ' + msg;
      errorMsg.style.display = '';
      successMsg.style.display = 'none';
    }

    function clearError() {
      errorMsg.style.display = 'none';
      errorMsg.textContent = '';
    }

    function showSuccess(obj) {
      successMsg.innerHTML = '<strong>✓ Report submitted successfully!</strong><p>Reference ID: <strong>#' + (obj.id || '') + '</strong></p>';
      successMsg.style.display = '';
      clearError();
      setTimeout(() => {
        window.location.href = 'index.php';
      }, 3000);
    }

    useLocationBtn.addEventListener('click', () => {
      if (!navigator.geolocation) {
        showError('Geolocation is not supported by your browser.');
        return;
      }
      useLocationBtn.disabled = true;
      useLocationBtn.textContent = 'Getting location...';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          document.getElementById('latitude').value = pos.coords.latitude.toFixed(6);
          document.getElementById('longitude').value = pos.coords.longitude.toFixed(6);
          useLocationBtn.disabled = false;
          useLocationBtn.textContent = '📍 Use My Current Location';
          clearError();
        },
        () => {
          useLocationBtn.disabled = false;
          useLocationBtn.textContent = '📍 Use My Current Location';
          showError('Unable to get your location. Please enter coordinates manually.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });

    formEl.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearError();
      successMsg.style.display = 'none';

      const title = document.getElementById('title').value.trim();
      const locationName = document.getElementById('location_name').value.trim();

      if (!title) {
        showError('Title is required.');
        return;
      }
      if (!locationName) {
        showError('Location name is required.');
        return;
      }
      if (!selectedFile) {
        showError('Please upload a photo of the item.');
        return;
      }

      const fd = new FormData();
      fd.append('title', title);
      fd.append('description', document.getElementById('description').value.trim());
      fd.append('category', document.getElementById('category').value);
      fd.append('status', document.querySelector('input[name="status"]:checked').value);
      fd.append('location_name', locationName);
      fd.append('latitude', parseFloat(document.getElementById('latitude').value));
      fd.append('longitude', parseFloat(document.getElementById('longitude').value));
      fd.append('reported_by', '1');
      fd.append('image', selectedFile);

      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';

      try {
        const res = await fetch(API_BASE + '/items', { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.detail || 'Failed to submit report.');
        showSuccess({ id: data.id, message: data.message || 'Item reported successfully.' });
        formEl.reset();
        clearImage();
        document.getElementById('latitude').value = DEFAULT_CENTER.lat;
        document.getElementById('longitude').value = DEFAULT_CENTER.lng;
      } catch (err) {
        showError(err.message || 'Something went wrong. Please try again.');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Report';
      }
    });
  </script>
</body>
</html>
