<?php
/**
 * header.php — Ottawa Tamil Sangam
 * Session-aware nav: shows Dashboard/Logout when logged in.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
ots_session();
$currentUser  = getCurrentUser();
$needLogin    = !empty($_GET['need_login']);
$showForgot   = !empty($_GET['forgot']);
$passwordReset = !empty($_GET['reset_ok']);
$emailVerified = !empty($_GET['verified']);
$verifyError   = $_GET['verify_error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ottawa Tamil Sangam</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&family=Noto+Sans+Tamil:wght@400;600;700&family=Kaushan+Script&family=Tiro+Tamil&display=swap"
    rel="stylesheet" />
</head>

<body>

  <!-- ═══ NAVIGATION ═══════════════════════════════════════════════════════════ -->
  <header class="site-header">
    <nav class="navbar">
      <a href="index.php" class="nav-logo">
        <img src="assets/ots_logo.png" alt="Ottawa Tamil Sangam" class="logo-img" onerror="this.style.display='none'" />
        <span class="logo-text">Ottawa Tamil Sangam</span>
      </a>

      <button id="navToggle" class="nav-toggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
      </button>

      <ul id="navList" class="nav-list">
        <li><a href="index.php" class="nav-link">Home</a></li>
        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle">About <i class="bi bi-chevron-down"></i></a>
          <ul class="dropdown-menu">
            <li><a href="vision.php">Vision &amp; Values</a></li>
            <li><a href="committee.php">Executive Committee</a></li>
          </ul>
        </li>
        <li><a href="events.php" class="nav-link">Events</a></li>
        <li><a href="news.php" class="nav-link">News</a></li>
        <li><a href="membership.php" class="nav-link">Membership</a></li>
        <li><a href="contact.php" class="nav-link">Contact</a></li>

        <?php if ($currentUser): ?>
        <!-- Logged-in state -->
        <li class="nav-user-menu">
          <div class="user-pill">
            <span class="user-avatar"><?= e(strtoupper(substr($currentUser['first_name'],0,1))) ?></span>
            <span class="user-name"><?= e($currentUser['first_name']) ?></span>
            <?php if (isStaff($currentUser)): ?>
            <span class="role-badge role-<?= e(str_replace('_','-',$currentUser['role'])) ?>">
              <?= e(roleLabel($currentUser['role'])) ?>
            </span>
            <?php elseif (($currentUser['membership_status'] ?? 'none') === 'none'): ?>
            <span class="role-badge role-non-member">Non-Member</span>
            <?php endif; ?>
            <i class="bi bi-chevron-down"></i>
          </div>
          <ul class="user-dropdown">
            <?php if (isAdmin($currentUser)): ?>
            <li><a href="admin_panel.php"><i class="bi bi-shield-check"></i> Admin Panel</a></li>
            <li><a href="member_panel.php"><i class="bi bi-person"></i> My Profile</a></li>
            <?php elseif (isStaff($currentUser)): ?>
            <li><a href="coordinator_panel.php"><i class="bi bi-pencil-square"></i> Staff Panel</a></li>
            <li><a href="member_panel.php"><i class="bi bi-person"></i> My Profile</a></li>
            <?php else: ?>
            <li><a href="member_panel.php"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
            <?php endif; ?>
            <li class="divider"></li>
            <li>
              <a href="#" id="logoutLink"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
          </ul>
        </li>
        <?php else: ?>
        <!-- Guest state -->
        <li class="nav-auth-btns">
          <button id="loginBtn" class="btn-outline-nav">Log In</button>
          <button id="signupBtn" class="btn-primary-nav">Sign Up</button>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- ═══ PASSWORD RESET SUCCESS BANNER ════════════════════════════════════════ -->
  <?php if ($passwordReset): ?>
  <div id="resetSuccessBanner" style="background:#dcfce7;border-bottom:2px solid #86efac;padding:12px 24px;text-align:center;font-size:.95rem;font-weight:600;color:#166534;display:flex;align-items:center;justify-content:center;gap:10px">
    <i class="bi bi-check-circle-fill"></i>
    Password updated! You can now log in.
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#166534;font-size:1.1rem;margin-left:8px"><i class="bi bi-x-lg"></i></button>
  </div>
  <?php endif; ?>

  <!-- ═══ EMAIL VERIFIED SUCCESS BANNER ════════════════════════════════════════ -->
  <?php if ($emailVerified): ?>
  <div id="verifiedBanner" style="background:#dcfce7;border-bottom:2px solid #86efac;padding:12px 24px;text-align:center;font-size:.95rem;font-weight:600;color:#166534;display:flex;align-items:center;justify-content:center;gap:10px">
    <i class="bi bi-check-circle-fill"></i>
    <?= $currentUser ? 'Your email address has been verified — thank you!' : 'Email verified! You can now log in to your account.' ?>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#166534;font-size:1.1rem;margin-left:8px"><i class="bi bi-x-lg"></i></button>
  </div>
  <?php endif; ?>

  <!-- ═══ EMAIL VERIFY ERROR BANNER ════════════════════════════════════════════ -->
  <?php if ($verifyError): ?>
  <div id="verifyErrorBanner" style="background:#fef2f2;border-bottom:2px solid #fecaca;padding:12px 24px;text-align:center;font-size:.95rem;font-weight:600;color:#991b1b;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?php if ($verifyError === 'expired'): ?>
      This verification link has expired. <?= $currentUser ? 'Use the banner below to send a new one.' : 'Please log in and request a new verification email.' ?>
    <?php elseif ($verifyError === 'none'): ?>
      No verification token was provided. Please use the link sent to your email.
    <?php else: ?>
      This verification link is invalid or has already been used.
    <?php endif; ?>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#991b1b;font-size:1.1rem;margin-left:8px"><i class="bi bi-x-lg"></i></button>
  </div>
  <?php endif; ?>

  <!-- ═══ EMAIL VERIFICATION BANNER (logged in, unverified) ════════════════════ -->
  <?php if ($currentUser && empty($currentUser['email_verified'])): ?>
  <div id="verifyBanner" style="background:linear-gradient(135deg,#fefce8,#fef9c3);border-bottom:2px solid #fde68a;padding:10px 24px;text-align:center;font-size:.9rem;color:#92400e;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap">
    <i class="bi bi-envelope-exclamation-fill"></i>
    <span>Please verify your email address to unlock all features.</span>
    <button id="resendVerifyHeaderBtn" style="background:#92400e;color:#fff;border:none;border-radius:6px;padding:4px 14px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:inherit">Resend Email</button>
    <span id="resendVerifyHeaderMsg" style="display:none;font-weight:600"></span>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#92400e;margin-left:4px"><i class="bi bi-x-lg"></i></button>
  </div>
  <?php endif; ?>

  <!-- ═══ AUTH MODAL (only for guests) ════════════════════════════════════════ -->
  <?php if (!$currentUser): ?>
  <div id="authModal" class="auth-modal <?= ($needLogin || $showForgot || $emailVerified) ? 'active' : '' ?>">
    <div class="auth-modal-inner">

      <!-- LOGIN FORM -->
      <div id="loginForm" class="auth-form" style="display:<?= ($needLogin || (!$showForgot)) ? 'block' : 'none' ?>">
        <button class="modal-close" id="closeModal"><i class="bi bi-x-lg"></i></button>
        <div class="auth-logo">
          <img src="assets/ots_logo.png" alt="OTS" onerror="this.style.display='none'" />
          <h2>Welcome Back</h2>
          <p>Log in to your Ottawa Tamil Sangam account</p>
        </div>
        <div id="loginError" class="auth-error" style="display:none"></div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="loginEmail" placeholder="your@email.com" />
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="pass-wrap">
            <input type="password" id="loginPassword" placeholder="Password" />
            <button type="button" class="pass-toggle" data-target="loginPassword"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div style="text-align:right;margin:-6px 0 10px">
          <a href="#" id="openForgotLink" style="font-size:.85rem;color:var(--maroon,#6b0f1a);font-weight:600">Forgot password?</a>
        </div>
        <button id="loginSubmit" class="btn-auth">Log In</button>
        <p class="auth-switch">Don't have an account? <a href="#" id="openRegister">Sign up</a></p>
      </div>

      <!-- FORGOT PASSWORD FORM -->
      <div id="forgotForm" class="auth-form" style="display:none">
        <button class="modal-close" id="closeForgot"><i class="bi bi-x-lg"></i></button>
        <div class="auth-logo">
          <img src="assets/ots_logo.png" alt="OTS" onerror="this.style.display='none'" />
          <h2>Forgot Password</h2>
          <p>Enter your email and we'll send you a reset link</p>
        </div>
        <div id="forgotMsg" class="auth-error" style="display:none"></div>
        <div id="forgotFields">
          <div class="form-group">
            <label>Email</label>
            <input type="email" id="forgotEmail" placeholder="your@email.com" />
          </div>
          <button id="forgotSubmit" class="btn-auth">Send Reset Link</button>
        </div>
        <div id="forgotSuccess" style="display:none;text-align:center;padding:16px 0">
          <div style="font-size:2.5rem;color:#22c55e;margin-bottom:10px"><i class="bi bi-envelope-check-fill"></i></div>
          <p style="color:#166534;font-weight:600">Check your email!</p>
          <p style="color:#4a4a4a;font-size:.9rem">If that email is registered, we sent a reset link. Check your inbox (and spam folder).</p>
        </div>
        <p class="auth-switch" style="margin-top:12px"><a href="#" id="backToLogin">Back to Log In</a></p>
      </div>

      <!-- REGISTER FORM -->
      <div id="registerForm" class="auth-form" style="display:none">
        <button class="modal-close" id="closeRegister"><i class="bi bi-x-lg"></i></button>
        <div class="auth-logo">
          <img src="assets/ots_logo.png" alt="OTS" onerror="this.style.display='none'" />
          <h2>Join Our Community</h2>
          <p>Create your Ottawa Tamil Sangam account</p>
        </div>
        <div id="registerError" class="auth-error" style="display:none"></div>
        <div id="registerFields">
          <div class="form-row">
            <div class="form-group">
              <label>First Name</label>
              <input type="text" id="regFirstName" placeholder="First name" />
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" id="regLastName" placeholder="Last name" />
            </div>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" id="regEmail" placeholder="your@email.com" />
            <p style="margin:6px 0 0;font-size:.82rem;color:#6b7280;line-height:1.45">
              <i class="bi bi-info-circle" style="color:var(--gold,#d4a73a);margin-right:4px"></i>
              If you've purchased a membership or tickets on Zeffy, use that same email here — it makes syncing your membership and tickets much easier.
            </p>
          </div>
          <div class="form-group">
            <label>Phone <span class="optional">(optional)</span></label>
            <input type="tel" id="regPhone" placeholder="+1 613 555 0000" />
          </div>
          <div class="form-group">
            <label>Password <span class="optional">(min 8 characters)</span></label>
            <div class="pass-wrap">
              <input type="password" id="regPassword" placeholder="Create a password" />
              <button type="button" class="pass-toggle" data-target="regPassword"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button id="registerSubmit" class="btn-auth">Create Account</button>
        </div>
        <div id="registerVerifyMsg" style="display:none;text-align:center;padding:16px 0">
          <div style="font-size:2.5rem;color:var(--gold,#d4a73a);margin-bottom:10px"><i class="bi bi-envelope-check-fill"></i></div>
          <p style="color:var(--maroon,#6b0f1a);font-weight:600">Account created!</p>
          <p style="color:#4a4a4a;font-size:.9rem">Check your email to verify your account before logging in.</p>
        </div>
        <p class="auth-switch">Already have an account? <a href="#" id="openLogin">Log in</a></p>
      </div>

    </div>
  </div>
  <?php endif; ?>

  <!-- ═══ INLINE SCRIPTS ════════════════════════════════════════════════════════ -->
  <script>
  (function() {
    // ── Mobile nav toggle ────────────────────────────────────────────────────
    const navToggle = document.getElementById('navToggle');
    const navList = document.getElementById('navList');
    navToggle?.addEventListener('click', () => navList.classList.toggle('show'));

    // ── Dropdown menus ───────────────────────────────────────────────────────
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        btn.closest('.dropdown').classList.toggle('open');
      });
    });

    // ── User pill dropdown ───────────────────────────────────────────────────
    document.querySelector('.user-pill')?.addEventListener('click', e => {
      e.stopPropagation();
      document.querySelector('.nav-user-menu')?.classList.toggle('open');
    });
    document.addEventListener('click', () => {
      document.querySelector('.nav-user-menu')?.classList.remove('open');
    });

    // ── Logout ───────────────────────────────────────────────────────────────
    document.getElementById('logoutLink')?.addEventListener('click', async e => {
      e.preventDefault();
      const r = await fetch('api.php?action=logout', { method: 'POST' });
      const d = await r.json();
      if (d.success) window.location.href = d.redirect || 'index.php';
    });

    // ── Resend verification (header banner) ──────────────────────────────────
    document.getElementById('resendVerifyHeaderBtn')?.addEventListener('click', async function() {
      this.disabled = true; this.textContent = 'Sending…';
      const msg = document.getElementById('resendVerifyHeaderMsg');
      try {
        const r = await fetch('api.php?action=resend_verification', { method: 'POST' });
        const d = await r.json();
        msg.style.display = 'inline';
        msg.textContent = d.success ? (d.message || 'Sent! Check your inbox.') : (d.error || 'Failed.');
        msg.style.color = d.success ? '#166534' : '#991b1b';
        if (!d.success) { this.disabled = false; this.textContent = 'Resend Email'; }
      } catch(e) {
        msg.style.display='inline'; msg.textContent='Network error.'; msg.style.color='#991b1b';
        this.disabled=false; this.textContent='Resend Email';
      }
    });

    // ── Auth modal ───────────────────────────────────────────────────────────
    const modal        = document.getElementById('authModal');
    const loginForm    = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const forgotForm   = document.getElementById('forgotForm');
    if (!modal) return;

    function openModal(form) {
      modal.classList.add('active');
      loginForm.style.display    = form === 'login'    ? 'block' : 'none';
      registerForm.style.display = form === 'register' ? 'block' : 'none';
      if (forgotForm) forgotForm.style.display = form === 'forgot' ? 'block' : 'none';
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    document.getElementById('loginBtn')?.addEventListener('click', () => openModal('login'));
    document.getElementById('signupBtn')?.addEventListener('click', () => openModal('register'));
    document.getElementById('closeModal')?.addEventListener('click', closeModal);
    document.getElementById('closeRegister')?.addEventListener('click', closeModal);
    document.getElementById('closeForgot')?.addEventListener('click', closeModal);
    document.getElementById('openRegister')?.addEventListener('click', e => {
      e.preventDefault(); openModal('register');
    });
    document.getElementById('openLogin')?.addEventListener('click', e => {
      e.preventDefault(); openModal('login');
    });
    document.getElementById('openForgotLink')?.addEventListener('click', e => {
      e.preventDefault(); openModal('forgot');
    });
    document.getElementById('backToLogin')?.addEventListener('click', e => {
      e.preventDefault(); openModal('login');
    });
    modal.addEventListener('click', e => {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeModal();
    });

    // Password visibility toggles
    document.querySelectorAll('.pass-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const inp = document.getElementById(btn.dataset.target);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
      });
    });

    // ── API helpers ──────────────────────────────────────────────────────────
    async function apiPost(action, data) {
      const r = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      return r.json();
    }

    function showError(el, msg) {
      el.textContent = msg;
      el.style.display = 'block';
    }

    // ── Login submit ─────────────────────────────────────────────────────────
    document.getElementById('loginSubmit')?.addEventListener('click', async () => {
      const btn = document.getElementById('loginSubmit');
      const err = document.getElementById('loginError');
      err.style.display = 'none';
      btn.disabled = true; btn.textContent = 'Logging in…';

      const d = await apiPost('login', {
        email: document.getElementById('loginEmail').value,
        password: document.getElementById('loginPassword').value,
      });

      if (d.success) {
        window.location.href = d.redirect || 'dashboard.php';
      } else {
        // If unverified, show option to resend
        let errMsg = d.error || 'Login failed.';
        showError(err, errMsg);
        if (d.unverified) {
          err.innerHTML = errMsg + ' <a href="verify.php" style="color:inherit;font-weight:700;text-decoration:underline">Resend verification</a>';
        }
        btn.disabled = false; btn.textContent = 'Log In';
      }
    });

    // ── Register submit ──────────────────────────────────────────────────────
    document.getElementById('registerSubmit')?.addEventListener('click', async () => {
      const btn    = document.getElementById('registerSubmit');
      const err    = document.getElementById('registerError');
      const fields = document.getElementById('registerFields');
      const verMsg = document.getElementById('registerVerifyMsg');
      err.style.display = 'none';
      btn.disabled = true; btn.textContent = 'Creating account…';

      const d = await apiPost('register', {
        first_name: document.getElementById('regFirstName').value,
        last_name:  document.getElementById('regLastName').value,
        email:      document.getElementById('regEmail').value,
        phone:      document.getElementById('regPhone').value,
        password:   document.getElementById('regPassword').value,
      });

      if (d.success && d.verify) {
        // Email verification required — show message instead of redirecting
        if (fields) fields.style.display = 'none';
        if (verMsg) verMsg.style.display = 'block';
      } else if (d.success) {
        window.location.href = d.redirect || 'dashboard.php';
      } else {
        showError(err, d.error || 'Registration failed.');
        btn.disabled = false; btn.textContent = 'Create Account';
      }
    });

    // ── Forgot password submit ────────────────────────────────────────────────
    document.getElementById('forgotSubmit')?.addEventListener('click', async () => {
      const btn    = document.getElementById('forgotSubmit');
      const msg    = document.getElementById('forgotMsg');
      const fields = document.getElementById('forgotFields');
      const succ   = document.getElementById('forgotSuccess');
      msg.style.display = 'none';
      btn.disabled = true; btn.textContent = 'Sending…';

      const email = document.getElementById('forgotEmail').value;
      if (!email) {
        msg.textContent = 'Please enter your email.'; msg.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Send Reset Link';
        return;
      }

      const d = await apiPost('forgot_password', { email });
      if (d.success) {
        if (fields) fields.style.display = 'none';
        if (succ)   succ.style.display   = 'block';
      } else {
        msg.textContent = d.error || 'Something went wrong. Please try again.';
        msg.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Send Reset Link';
      }
    });

    // Enter key on login
    document.getElementById('loginPassword')?.addEventListener('keydown', e => {
      if (e.key === 'Enter') document.getElementById('loginSubmit').click();
    });
    document.getElementById('forgotEmail')?.addEventListener('keydown', e => {
      if (e.key === 'Enter') document.getElementById('forgotSubmit')?.click();
    });

    // Auto-open forgot form if ?forgot=1 in URL
    <?php if ($showForgot): ?>
    openModal('forgot');
    <?php elseif ($needLogin): ?>
    openModal('register');
    <?php endif; ?>
  })();
  </script>