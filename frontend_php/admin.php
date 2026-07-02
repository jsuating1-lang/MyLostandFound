<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard — Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <!-- Top Back Button -->
      <a href="index.php" class="back-btn" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
        <span>←</span> Back to Home
      </a>

      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
          <div>
            <h1>👮 Admin Dashboard</h1>
            <p class="text-secondary">All reported items from the Campus Lost & Found system</p>
          </div>
          <a href="index.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem;">
            <span>←</span> Back
          </a>
        </div>
        
        <div id="content" class="mt-3">
          <div class="flex flex-center" style="padding: 2rem;">
            <p class="text-muted">Loading reports...</p>
          </div>
        </div>
      </div>

      <!-- Bottom Back Button -->
      <div style="text-align: center; margin-top: 2rem;">
        <a href="index.php" class="back-btn">← Back to Home</a>
      </div>

      <footer class="site-footer">
        <small>Campus Lost & Found — Admin Dashboard</small>
      </footer>
    </div>
  </div>

  <!-- Image Viewer Modal -->
  <div id="imageModal" class="modal">
    <div class="modal-content" style="max-width: 90vw; max-height: 90vh; padding: 0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;">
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--surface-hover); border-bottom: 1px solid var(--border);">
        <h2 style="margin: 0; font-size: 1.1rem;">Image Preview</h2>
        <button type="button" id="closeImageModal" class="btn btn-ghost" style="padding: 0.5rem 0.75rem;">✕ Close</button>
      </div>
      <div style="flex: 1; overflow: auto; display: flex; align-items: center; justify-content: center; padding: 1rem; background: #f9fafb;">
        <img id="modalImage" src="" alt="Item Image" style="max-width: 100%; max-height: 100%; object-fit: contain;">
      </div>
      <div style="padding: 1rem; background: var(--surface-hover); border-top: 1px solid var(--border); display: flex; justify-content: space-between;">
        <a href="index.php" class="back-btn" style="margin: 0;">← Back to Home</a>
        <button type="button" id="backFromModal" class="btn btn-primary">← Back to Admin</button>
      </div>
    </div>
  </div>

  <script>
    const API_BASE = 'http://127.0.0.1:8000';
    const content = document.getElementById('content');
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const closeImageModal = document.getElementById('closeImageModal');
    const backFromModal = document.getElementById('backFromModal');

    function openImageModal(imageUrl) {
      modalImage.src = imageUrl;
      imageModal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      imageModal.classList.remove('open');
      document.body.style.overflow = 'auto';
      modalImage.src = '';
    }

    closeImageModal.addEventListener('click', closeModal);
    backFromModal.addEventListener('click', closeModal);

    // Close modal when clicking outside of content
    imageModal.addEventListener('click', (e) => {
      if (e.target === imageModal) {
        closeModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && imageModal.classList.contains('open')) {
        closeModal();
      }
    });

    function render(items) {
      if (!items.length) {
        content.innerHTML = '<p class="text-muted">No reports found.</p>';
        return;
      }

      const rows = items.map(i => {
        const imageUrl = i.image_url || i.image_path;
        const img = imageUrl
          ? `<button type="button" class="btn btn-primary" onclick="openImageModal('${imageUrl}');" style="cursor: pointer;">View Image</button>`
          : '—';
        const status = `<span class="status-badge status-${i.status}">${i.status || 'unknown'}</span>`;
        return `<tr>
          <td><strong>${i.id}</strong></td>
          <td><strong>${escapeHtml(i.title || '')}</strong></td>
          <td>${escapeHtml((i.description || '').substring(0, 40))}...</td>
          <td>${i.category || '—'}</td>
          <td>${status}</td>
          <td>${i.latitude ? parseFloat(i.latitude).toFixed(4) : '—'}, ${i.longitude ? parseFloat(i.longitude).toFixed(4) : '—'}</td>
          <td>${escapeHtml(i.location_name || '—')}</td>
          <td>${i.reported_by || '—'}</td>
          <td>${img}</td>
          <td><small>${i.created_at ? new Date(i.created_at).toLocaleDateString() : '—'}</small></td>
        </tr>`;
      }).join('');

      content.innerHTML = `<div style="overflow-x: auto;"><table><thead><tr><th>ID</th><th>Title</th><th>Description</th><th>Category</th><th>Status</th><th>Coordinates</th><th>Location</th><th>By</th><th>Image</th><th>Date</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    }

    function escapeHtml(s) {
      return (s + '').replace(/[&<>"]/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;'
      }[c]));
    }

    async function load() {
      try {
        const res = await fetch(API_BASE + '/items');
        const json = await res.json();
        render(json.items || []);
      } catch (e) {
        content.innerHTML = '<div class="error-msg">⚠️ Error loading reports. Make sure the backend is running.</div>';
      }
    }

    load();
  </script>

  <style>
    .status-badge {
      display: inline-block;
      padding: 0.375rem 0.75rem;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-lost {
      background: #fef3f2;
      color: #b42318;
    }

    .status-found {
      background: #ecfdf3;
      color: #027a48;
    }

    .status-claimed {
      background: #fffbeb;
      color: #92400e;
    }

    .status-returned {
      background: #e0f2fe;
      color: #0369a1;
    }
  </style>
</body>
</html>
