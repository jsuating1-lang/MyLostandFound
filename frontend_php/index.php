<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <header class="site-header">
        <div>
          <h1>Campus Lost & Found</h1>
          <p class="subtitle">Report, search, and reclaim items on campus</p>
        </div>
        <div class="auth-area">
          <span id="user-greet"></span>
          <button id="logout" class="btn btn-ghost" style="display:none">Logout</button>
        </div>
      </header>

      <main class="grid">
        <a class="card" href="report_item.php">
          <h3>📝 Report an Item</h3>
          <p>Upload a photo and location to create a report.</p>
        </a>

        <a class="card" href="SearchByImage.php">
          <h3>🔍 Search by Image</h3>
          <p>Find matching found items using a photo.</p>
        </a>

        <a class="card" href="HotspotMap.php">
          <h3>🗺️ Hotspot Map</h3>
          <p>View campus loss hotspots and watch zones.</p>
        </a>

        <a class="card" href="ClaimItem.php">
          <h3>✅ Submit a Claim</h3>
          <p>Claim an item with proof and student ID.</p>
        </a>

        <a class="card" href="admin.php">
          <h3>👮 Admin Dashboard</h3>
          <p>View all submitted reports and item listings.</p>
        </a>

        <a class="card" href="login.php">
          <h3>🔐 Login / Register</h3>
          <p>Sign in or create an account to manage reports.</p>
        </a>
      </main>

      <footer class="site-footer">
        <small>Campus Lost & Found — Prototype v1.0</small>
      </footer>
    </div>
  </div>

  <script>
    try {
      const u = JSON.parse(localStorage.getItem('clf_user') || 'null');
      const greet = document.getElementById('user-greet');
      const logout = document.getElementById('logout');
      if (u && u.full_name) {
        greet.textContent = `Welcome back, ${u.full_name}`;
        logout.style.display = '';
      } else {
        greet.innerHTML = `<a href="login.php" class="link">Sign in</a>`;
      }
      logout.addEventListener('click', () => {
        localStorage.removeItem('clf_user');
        window.location.reload();
      });
    } catch (e) { }
  </script>
</body>
</html>
