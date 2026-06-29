<?php
include_once "header.php";
// $currentUser already set by header.php
?>
<main>
  <section class="page-hero" style="background-color:#d4a73a">
    <div class="container">
      <h1>Contact Us</h1>
      <div class="hero-subtitle"><?= getSiteContent('contact_hero_subtitle', '<p>We would love to hear your thoughts and suggestions!</p>') ?></div>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="contact-container">
        <div class="contact-info">
          <div class="info-card">
            <h3><i class="bi bi-envelope-fill"></i> Email Us</h3>
            <?= getSiteContent('contact_email_card') ?>
          </div>
          <div class="info-card">
            <h3><i class="bi bi-chat-dots-fill"></i> Follow &amp; Chat With Us</h3>
            <?= getSiteContent('contact_social_card') ?>
            <div class="social-links">
              <a href="https://www.facebook.com/TamilSangamofOttawa/" class="social-link" aria-label="Facebook" target="_blank"><i class="bi bi-facebook"></i></a>
              <a href="https://www.instagram.com/ottawatamilsangam/" class="social-link" aria-label="Instagram" target="_blank"><i class="bi bi-instagram"></i></a>
              <a href="https://www.youtube.com/@ottawa-tamil-sangam" class="social-link" aria-label="YouTube" target="_blank"><i class="bi bi-youtube"></i></a>
            </div>
          </div>
          <?php if (canEditContent($currentUser)): ?>
          <div class="content-edit-bar">
            <i class="bi bi-pencil-square"></i>
            <span>Contact info text is editable.</span>
            <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>?tab=sitecontent" class="btn-edit-content">Edit Content →</a>
          </div>
          <?php endif; ?>
        </div>

        <div class="contact-form-wrapper">
          <h2>Send Us a Message</h2>
          <div id="contactAlert" style="display:none;margin-bottom:16px;padding:14px 18px;border-radius:8px;font-size:.95rem"></div>
          <form id="contactForm">
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">First Name<span class="required">*</span></label>
                <input type="text" id="firstName" name="firstName" required />
              </div>
              <div class="form-group">
                <label for="lastName">Last Name<span class="required">*</span></label>
                <input type="text" id="lastName" name="lastName" required />
              </div>
            </div>
            <div class="form-group">
              <label for="email">Email<span class="required">*</span></label>
              <input type="email" id="email" name="email" required />
              <small style="color:var(--text-muted);margin-top:4px;display:block">We'll reply to this address</small>
            </div>
            <div class="form-group">
              <label for="message">Message<span class="required">*</span></label>
              <textarea id="message" name="message" required></textarea>
            </div>
            <button type="submit" class="btn-send" id="contactSubmitBtn">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include_once "footer.php"; ?>
<script src="main.js"></script>
<script>
document.getElementById('contactForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn   = document.getElementById('contactSubmitBtn');
  const alert = document.getElementById('contactAlert');
  btn.disabled = true;
  btn.textContent = 'Sending…';
  alert.style.display = 'none';

  try {
    const r = await fetch('api.php?action=send_contact_message', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        action:     'send_contact_message',
        first_name: document.getElementById('firstName').value.trim(),
        last_name:  document.getElementById('lastName').value.trim(),
        email:      document.getElementById('email').value.trim(),
        message:    document.getElementById('message').value.trim(),
      }),
    });
    const d = await r.json();
    if (d.success) {
      alert.style.cssText = 'display:block;background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:14px 18px;border-radius:8px;font-size:.95rem;margin-bottom:16px';
      alert.textContent = "✓ Message sent! We'll get back to you soon.";
      e.target.reset();
    } else {
      alert.style.cssText = 'display:block;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:14px 18px;border-radius:8px;font-size:.95rem;margin-bottom:16px';
      alert.textContent = d.error || 'Something went wrong. Please try again or email us directly.';
    }
  } catch {
    alert.style.cssText = 'display:block;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:14px 18px;border-radius:8px;font-size:.95rem;margin-bottom:16px';
    alert.textContent = 'Network error. Please try again.';
  }

  btn.disabled = false;
  btn.textContent = 'Send Message';
});
</script>
</body>
</html>
