function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
    clearMessages();
  }

  // ── PASSWORD TOGGLE ──
  function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
      input.type = 'text';
      icon.textContent = '︶';
    } else {
      input.type = 'password';
      icon.textContent = '👁';
    }
  }

  // ── MESSAGES ──
  function showError(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.style.display = 'block';
  }

  function showSuccess(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.style.display = 'block';
  }

  function clearMessages() {
    document.querySelectorAll('.error-msg, .success-msg').forEach(el => {
      el.style.display = 'none';
      el.textContent = '';
    });
  }

  // ── LOGIN ──
  async function handleLogin() {
    clearMessages();
    const email    = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const terms    = document.getElementById('terms-login').checked;
    const btn      = document.getElementById('login-btn');

    if (!email || !password) return showError('login-error', 'Please fill in all fields.');
    if (!terms) return showError('login-error', 'You must agree to the Terms & Conditions.');

    btn.disabled = true;
    btn.textContent = 'Signing in...';

    try {
      const response = await fetch('http://localhost:8080/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (response.ok) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        showSuccess('login-success', 'Login successful! Redirecting...');
        setTimeout(() => window.location.href = 'dashboard.html', 1200);
      } else {
        showError('login-error', data.message || 'Invalid email or password.');
      }
    } catch (err) {
      showError('login-error', 'Cannot connect to server. Please try again later.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sign In';
    }
  }

  // ── REGISTER ──
  async function handleRegister() {
    clearMessages();
    const name     = document.getElementById('reg-name').value.trim();
    const email    = document.getElementById('reg-email').value.trim();
    const street   = document.getElementById('reg-street').value.trim();
    const barangay = document.getElementById('reg-barangay').value.trim();
    const city     = document.getElementById('reg-city').value.trim();
    const province = document.getElementById('reg-province').value.trim();
    const zip      = document.getElementById('reg-zip').value.trim();
    const password = document.getElementById('reg-password').value;
    const confirm  = document.getElementById('reg-confirm').value;
    const btn      = document.getElementById('register-btn');

    if (!name || !email || !street || !barangay || !city || !province || !zip || !password) {
      return showError('register-error', 'Please fill in all fields.');
    }
    if (password !== confirm) {
      return showError('register-error', 'Passwords do not match.');
    }
    if (password.length < 8) {
      return showError('register-error', 'Password must be at least 8 characters.');
    }

    btn.disabled = true;
    btn.textContent = 'Creating account...';

    try {
      const response = await fetch('http://localhost:8080/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password, street, barangay, city, province, zip }),
      });

      const data = await response.json();

      if (response.ok) {
        showSuccess('register-success', 'Account created! Please log in.');
        setTimeout(() => switchTab('login'), 1500);
      } else {
        showError('register-error', data.message || 'Registration failed. Try again.');
      }
    } catch (err) {
      showError('register-error', 'Cannot connect to server. Please try again later.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Sign Up';
    }
  }

  // ── FORGOT PASSWORD ──
  function showForgotPassword() {
    const email = document.getElementById('login-email').value.trim();
    if (!email) {
      showError('login-error', 'Enter your email above first, then click Forgot Password.');
      return;
    }
    showSuccess('login-success', `Password reset link sent to ${email} (if it exists).`);
    // TODO: wire up to your Spring Boot forgot-password endpoint
  }

  // ── GO BACK ──
  function goBack() {
    window.history.back();
  }