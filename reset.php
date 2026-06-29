<?php
/**
 * reset.php — Ottawa Tamil Sangam · Password Reset
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

ots_session();

$token   = trim($_GET['token'] ?? '');
$valid   = false;
$expired = false;

if ($token) {
    // Validate token without consuming it
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, reset_expires FROM users WHERE reset_token=? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        if ($row['reset_expires'] && strtotime($row['reset_expires']) > time()) {
            $valid = true;
        } else {
            $expired = true;
        }
    }
}

include_once 'header.php';
?>

<main>
  <section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.72),rgba(107,15,26,.72)),url('assets/OTS_pics/494413252_1234216415380645_7752173219688425099_n.jpg')">
    <div class="container">
      <h1>Reset Password</h1>
    </div>
  </section>

  <div class="container" style="max-width:520px;margin:60px auto;padding:0 20px">

    <?php if ($valid): ?>
    <!-- ── Valid token: show form ── -->
    <div class="reset-card">
      <div class="reset-icon"><i class="bi bi-shield-lock"></i></div>
      <h2>Choose a New Password</h2>
      <p>Enter and confirm your new password below.</p>

      <div id="resetMsg" class="reset-alert" style="display:none"></div>

      <div class="form-group" style="margin-top:20px">
        <label style="font-weight:600;margin-bottom:6px;display:block">New Password <small style="font-weight:400;color:#6b7280">(min 8 characters)</small></label>
        <div class="pass-wrap" style="position:relative">
          <input type="password" id="resetPw" placeholder="New password" style="width:100%;padding:12px 44px 12px 14px;border:1.5px solid #d1d5db;border-radius:8px;font-size:1rem;font-family:inherit;outline:none;transition:border-color .2s"/>
          <button type="button" class="pass-toggle" data-target="resetPw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280"><i class="bi bi-eye"></i></button>
        </div>
      </div>

      <div class="form-group" style="margin-top:14px">
        <label style="font-weight:600;margin-bottom:6px;display:block">Confirm Password</label>
        <div class="pass-wrap" style="position:relative">
          <input type="password" id="resetPwConfirm" placeholder="Confirm password" style="width:100%;padding:12px 44px 12px 14px;border:1.5px solid #d1d5db;border-radius:8px;font-size:1rem;font-family:inherit;outline:none;transition:border-color .2s"/>
          <button type="button" class="pass-toggle" data-target="resetPwConfirm" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280"><i class="bi bi-eye"></i></button>
        </div>
      </div>

      <button id="resetSubmitBtn" class="btn-reset-primary" style="margin-top:20px">Update Password</button>
    </div>

    <?php elseif ($expired): ?>
    <!-- ── Expired token ── -->
    <div class="reset-card reset-error-card">
      <div class="reset-icon" style="color:var(--gold,#d4a73a)"><i class="bi bi-clock-history"></i></div>
      <h2>Link Expired</h2>
      <p>This password reset link has expired. Reset links are valid for 1 hour.</p>
      <p>Please request a new one below.</p>
      <a href="index.php?forgot=1" class="btn-reset-primary">Request New Link</a>
    </div>

    <?php else: ?>
    <!-- ── Invalid / no token ── -->
    <div class="reset-card reset-error-card">
      <div class="reset-icon" style="color:#ef4444"><i class="bi bi-x-circle-fill"></i></div>
      <h2>Invalid Link</h2>
      <p>This password reset link is invalid or has already been used.</p>
      <a href="index.php" class="btn-reset-secondary">Return to Home</a>
    </div>
    <?php endif; ?>

  </div>
</main>

<style>
.reset-card {
  background: #fff;
  border-radius: 16px;
  padding: 48px 40px;
  box-shadow: 0 8px 32px rgba(107,15,26,.10);
  border-top: 4px solid var(--maroon, #6b0f1a);
  text-align: center;
}
.reset-icon { font-size: 3rem; color: var(--maroon,#6b0f1a); margin-bottom: 16px; }
.reset-card h2 {
  color: var(--maroon, #6b0f1a);
  font-family: 'Cormorant Garamond', Georgia, serif;
  margin-bottom: 10px;
}
.reset-card .form-group { text-align: left; }
.reset-alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: .9rem;
  font-weight: 600;
  text-align: left;
}
.reset-alert.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.reset-alert.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.btn-reset-primary {
  display: inline-block;
  width: 100%;
  background: var(--maroon, #6b0f1a);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 14px 28px;
  font-size: 1rem;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: background .2s;
}
.btn-reset-primary:hover { background: #8b1f2a; }
.btn-reset-secondary {
  display: inline-block;
  color: var(--maroon, #6b0f1a);
  border: 2px solid var(--maroon, #6b0f1a);
  border-radius: 8px;
  padding: 12px 28px;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  margin-top: 12px;
  transition: background .2s;
}
.btn-reset-secondary:hover { background: rgba(107,15,26,.05); }
</style>

<script>
// Password visibility toggles
document.querySelectorAll('.pass-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById(btn.dataset.target);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  });
});

document.getElementById('resetSubmitBtn')?.addEventListener('click', async function() {
  const pw      = document.getElementById('resetPw').value;
  const pwConf  = document.getElementById('resetPwConfirm').value;
  const msg     = document.getElementById('resetMsg');
  msg.style.display = 'none';

  if (pw.length < 8) {
    msg.className = 'reset-alert error';
    msg.textContent = 'Password must be at least 8 characters.';
    msg.style.display = 'block';
    return;
  }
  if (pw !== pwConf) {
    msg.className = 'reset-alert error';
    msg.textContent = 'Passwords do not match.';
    msg.style.display = 'block';
    return;
  }

  this.disabled = true;
  this.textContent = 'Updating…';

  try {
    const r = await fetch('api.php?action=reset_password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: <?= json_encode($token) ?>, password: pw }),
    });
    const d = await r.json();
    msg.style.display = 'block';
    if (d.success) {
      msg.className = 'reset-alert success';
      msg.textContent = d.message || 'Password updated!';
      // Redirect after short delay
      setTimeout(() => { window.location.href = 'index.php?reset_ok=1'; }, 1500);
    } else {
      msg.className = 'reset-alert error';
      msg.textContent = d.error || 'Failed to update password. Please try again.';
      this.disabled = false;
      this.textContent = 'Update Password';
    }
  } catch (e) {
    msg.style.display = 'block';
    msg.className = 'reset-alert error';
    msg.textContent = 'Network error. Please try again.';
    this.disabled = false;
    this.textContent = 'Update Password';
  }
});
</script>

<?php include_once 'footer.php'; ?>
</body>
</html>
