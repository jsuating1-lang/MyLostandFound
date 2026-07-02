<?php ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login / Register — Campus Lost & Found</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="page-wrapper">
    <div class="container">
      <a href="index.php" class="back-btn">← Back to Home</a>

      <div style="max-width: 420px; margin: 3rem auto;">
        <div class="card">
          <h2 id="form-title">🔐 Login</h2>
          <p class="text-secondary mt-1">Sign in to manage your lost and found reports.</p>

          <div id="message" style="display:none" class="message mt-2"></div>

          <form id="auth-form" class="mt-3">
            <div class="form-group" id="email-field">
              <label for="email">Email Address</label>
              <input id="email" name="email" type="email" placeholder="your@email.com" required />
            </div>

            <div class="form-group" id="password-field">
              <label for="password">Password</label>
              <input id="password" name="password" type="password" placeholder="••••••••" required />
            </div>

            <div id="extra-fields" style="display:none">
              <div class="form-group">
                <label for="student_id">Student ID</label>
                <input id="student_id" name="student_id" type="text" placeholder="e.g. 123456" />
              </div>
              <div class="form-group">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" placeholder="Your name" />
              </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%;">Login</button>
          </form>

          <p class="text-center mt-2" style="font-size: 0.95rem;">
            Don't have an account?
            <button id="toggle-btn" class="link" style="background: none; border: none; color: var(--primary); text-decoration: underline; cursor: pointer; padding: 0; font-weight: 600;">Register here</button>
          </p>
        </div>
      </div>

      <footer class="site-footer">
        <small>Campus Lost & Found — Secure Login</small>
      </footer>
    </div>
  </div>

  <script>
    const API_BASE = 'http://127.0.0.1:8000';
    const form = document.getElementById('auth-form');
    const toggleBtn = document.getElementById('toggle-btn');
    const extra = document.getElementById('extra-fields');
    const formTitle = document.getElementById('form-title');
    const submitBtn = document.getElementById('submit-btn');
    const messageEl = document.getElementById('message');
    let mode = 'login';

    function showMessage(text, type = 'error') {
      messageEl.style.display = '';
      messageEl.className = 'message ' + (type === 'error' ? 'error-msg' : 'success-msg');
      messageEl.innerHTML = (type === 'success' ? '✓ ' : '⚠️ ') + text;
    }

    function clearMessage() {
      messageEl.style.display = 'none';
      messageEl.textContent = '';
    }

    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (mode === 'login') {
        mode = 'register';
        extra.style.display = '';
        formTitle.innerHTML = '📝 Register';
        submitBtn.textContent = 'Create Account';
        toggleBtn.textContent = 'Back to Login';
      } else {
        mode = 'login';
        extra.style.display = 'none';
        formTitle.innerHTML = '🔐 Login';
        submitBtn.textContent = 'Login';
        toggleBtn.textContent = 'Register here';
      }
      clearMessage();
      form.reset();
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearMessage();

      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();

      if (!email || !password) {
        showMessage('Email and password are required.');
        return;
      }

      const fd = new FormData();
      fd.append('email', email);
      fd.append('password', password);

      if (mode === 'register') {
        const student_id = document.getElementById('student_id').value.trim();
        const full_name = document.getElementById('full_name').value.trim();
        if (!student_id) {
          showMessage('Student ID is required for registration.');
          return;
        }
        fd.append('student_id', student_id);
        fd.append('full_name', full_name);
      }

      submitBtn.disabled = true;
      submitBtn.textContent = mode === 'login' ? 'Logging in...' : 'Creating account...';

      try {
        const url = API_BASE + (mode === 'register' ? '/auth/register' : '/auth/login');
        const res = await fetch(url, { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
          throw new Error(data.detail || 'Request failed');
        }

        showMessage(data.message || 'Success!', 'success');

        if (mode === 'register') {
          // After register, switch to login and prefill email
          setTimeout(() => {
            mode = 'login';
            extra.style.display = 'none';
            formTitle.innerHTML = '🔐 Login';
            submitBtn.textContent = 'Login';
            toggleBtn.textContent = 'Register here';
            document.getElementById('password').value = '';
            clearMessage();
          }, 1500);
        } else {
          // On successful login store user info and redirect
          try {
            const user = { id: data.id, full_name: data.full_name, email };
            localStorage.setItem('clf_user', JSON.stringify(user));
          } catch (e) {}
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 1000);
        }
      } catch (err) {
        showMessage(err.message || 'An error occurred. Please try again.');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = mode === 'login' ? 'Login' : 'Create Account';
      }
    });
  </script>
</body>
</html>
