<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Search by Image — Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <a href="index.php" class="back-btn">← Back to Home</a>

      <div style="max-width: 800px; margin: 2rem auto;">
        <div class="card">
          <h1>🔍 Search by Image</h1>
          <p class="text-secondary">Upload a photo to find matching found items in the system.</p>

          <form id="search-form" class="mt-3">
            <div class="form-group">
              <label for="query-image">Select Image *</label>
              <input type="file" id="query-image" accept="image/*" required />
            </div>

            <div id="error" class="error-msg" style="display:none;"></div>

            <div id="results" class="mt-3"></div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">🔎 Search</button>
          </form>
        </div>

        <div id="results-container" style="margin-top: 2rem;"></div>
      </div>

      <footer class="site-footer">
        <small>Campus Lost & Found — Image Search</small>
      </footer>
    </div>
  </div>

  <script>
    const API_BASE = 'http://127.0.0.1:8000';
    const form = document.getElementById('search-form');
    const input = document.getElementById('query-image');
    const errorEl = document.getElementById('error');
    const resultsContainer = document.getElementById('results-container');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      errorEl.style.display = 'none';
      resultsContainer.innerHTML = '';

      if (!input.files || !input.files[0]) {
        errorEl.innerHTML = '⚠️ Please select an image to search.';
        errorEl.style.display = '';
        return;
      }

      const fd = new FormData();
      fd.append('image', input.files[0]);

      submitBtn.disabled = true;
      submitBtn.textContent = 'Searching...';

      try {
        const res = await fetch(API_BASE + '/search/image', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.matches || data.matches.length === 0) {
          resultsContainer.innerHTML = '<div class="card"><p class="text-muted text-center">No matching items found. Try uploading a different photo.</p></div>';
          return;
        }

        const resultsHTML = `
          <div class="card">
            <h2>🎯 Search Results</h2>
            <p class="text-secondary">Found ${data.matches.length} matching item(s):</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
              ${data.matches.map(m => `
                <div class="card" style="border: 2px solid var(--primary);">
                  <p><strong>Item ID:</strong> #${m.item_id}</p>
                  <p><strong>Match Score:</strong> <span style="font-weight: 600; color: var(--primary);">${(m.similarity_score * 100).toFixed(1)}%</span></p>
                  <p class="text-muted"><small>Higher scores indicate better matches</small></p>
                </div>
              `).join('')}
            </div>
          </div>
        `;
        resultsContainer.innerHTML = resultsHTML;
      } catch (e) {
        errorEl.innerHTML = '⚠️ Search failed. Please try again.';
        errorEl.style.display = '';
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '🔎 Search';
      }
    });
  </script>
</body>
</html>
