<?php
include_once "header.php";
// $currentUser already set by header.php
$db = getDB();
$pricingTiers  = $db->query("SELECT * FROM membership_tiers ORDER BY display_order ASC, id ASC")->fetchAll();
$benefitPanels = $db->query("SELECT * FROM benefit_panels ORDER BY display_order ASC, id ASC")->fetchAll();
$registerUrl  = getSiteContent('membership_register_url', 'https://www.eventbrite.ca/e/ottawa-tamil-sangam-membership-annual-12-months-from-date-of-purchase-tickets-876297869517?aff=oddtdtcreator');
$isActive     = isActiveMember($currentUser);
$upgrade      = isset($_GET['upgrade']);
?>

<main>
<?php if ($isActive && !isAdmin($currentUser) && !isCoordinator($currentUser)): ?>
<!-- ── MANAGE MEMBERSHIP (Active Members) ─────────────────────────────── -->
<section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.70),rgba(107,15,26,.70)),url('assets/OTS_pics/0X0A5467.jpg');background-position:center 30%">
  <div class="container">
    <h1>My Membership</h1>
    <div class="hero-subtitle"><p>Manage your Ottawa Tamil Sangam membership</p></div>
  </div>
</section>

<div class="container" style="max-width:700px;padding-bottom:60px">
  <div class="membership-manage-card" id="manageMembershipCard">
    <div class="loading-text">Loading membership details…</div>
  </div>

  <div class="membership-perks-reminder">
    <h3><i class="bi bi-stars"></i> Your Member Benefits</h3>
    <ul>
      <li><i class="bi bi-check-circle-fill"></i> Member pricing on all events</li>
      <li><i class="bi bi-check-circle-fill"></i> Movie ticket discounts</li>
      <li><i class="bi bi-check-circle-fill"></i> Voting rights at AGM</li>
      <li><i class="bi bi-check-circle-fill"></i> Access to member dashboard</li>
    </ul>
    <a href="member_panel.php" class="btn-dashboard">
      <i class="bi bi-grid-1x2"></i> Go to Dashboard
      <i class="bi bi-arrow-right"></i>
    </a>
  </div>
</div>

<script>
async function loadMyMembership() {
  const r = await fetch('api.php?action=get_my_membership');
  const d = await r.json();
  const card = document.getElementById('manageMembershipCard');

  if (!d.success) { card.innerHTML = '<p>Could not load membership details.</p>'; return; }

  const m = d.membership;
  if (!m) {
    card.innerHTML = `
      <div class="mem-status-row"><span class="mem-status-badge active"><i class="bi bi-award"></i> Active Member</span></div>
      <p style="color:#6b7280;margin-top:12px">Your membership is active. Contact us at ottawatamilsangam@gmail.com for plan details.</p>`;
    return;
  }

  card.innerHTML = `
    <div class="mem-status-row">
      <span class="mem-status-badge active"><i class="bi bi-award"></i> Active Member</span>
    </div>
    <div class="mem-detail-grid">
      <div class="mem-detail-item"><label>Plan</label><strong>${esc(m.tier_name||'Standard')}</strong></div>
      <div class="mem-detail-item"><label>Amount Paid</label><strong>$${parseFloat(m.price_paid||0).toFixed(2)}/year</strong></div>
      <div class="mem-detail-item"><label>Started</label><strong>${m.started_at||'—'}</strong></div>
      <div class="mem-detail-item"><label>Expires</label><strong>${m.expires_at||'—'}</strong></div>
    </div>
    <p style="font-size:.85rem;color:#6b7280;margin-top:16px"><i class="bi bi-info-circle"></i> To renew or cancel your membership, visit <a href="https://www.zeffy.com" target="_blank" style="color:#d4a73a">Zeffy</a> or contact us at <a href="mailto:ottawatamilsangam@gmail.com" style="color:#d4a73a">ottawatamilsangam@gmail.com</a>.</p>`;
}

function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

loadMyMembership();
</script>

<?php else: ?>
<!-- ── BUY MEMBERSHIP (Non-members or admin/coordinator viewing public page) ─ -->

<?php if ($upgrade): ?>
<div class="container" style="padding-top:24px">
  <div class="panel-alert alert-info" style="display:flex;align-items:center;gap:12px;font-size:.95rem">
    <i class="bi bi-lock-fill" style="font-size:1.3rem"></i>
    <div>
      <strong>Member access required.</strong>
      Purchase a membership below to unlock your dashboard, member event pricing, and more.
    </div>
  </div>
</div>
<?php endif; ?>

<section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.70),rgba(107,15,26,.70)),url('assets/OTS_pics/0X0A5467.jpg');background-position:center 30%">
  <div class="container">
    <h1>Become a Member!</h1>
    <div class="hero-subtitle"><?= getSiteContent('membership_hero_subtitle', '<p>Join our growing family and enjoy tons of benefits!</p>') ?></div>
  </div>
</section>

<div class="container">
  <section class="membership-benefits fade-in">
    <h2>Benefits</h2>
    <div class="membership-benefits-intro"><?= getSiteContent('membership_benefits_intro') ?></div>

    <div class="benefits-grid">
      <?php foreach ($benefitPanels as $bp): ?>
      <div class="benefit-card">
        <div class="benefit-icon"><i class="bi <?= e($bp['icon']) ?>"></i></div>
        <h3><?= e($bp['title']) ?></h3>
        <?= $bp['content'] ?>
      </div>
      <?php endforeach; ?>
      <?php if (empty($benefitPanels)): ?>
      <p style="color:#6b7280;text-align:center;grid-column:1/-1;padding:24px 0">No benefit panels added yet.</p>
      <?php endif; ?>
    </div>

    <div class="membership-note">
      <i class="bi bi-info-circle"></i>
      <div><?= getSiteContent('membership_note') ?></div>
    </div>

    <?php if (isAdmin($currentUser)): ?>
    <div class="content-edit-bar">
      <i class="bi bi-pencil-square"></i>
      <span>Benefit panels are manageable by admins.</span>
      <a href="admin_panel.php?tab=pricing" class="btn-edit-content">Manage Benefits →</a>
    </div>
    <?php endif; ?>

    <div class="section-divider-otsb5" aria-hidden="true"></div>

    <section class="membership-pricing fade-in">
      <h2>Membership Fees</h2>
      <div class="membership-pricing-intro"><?= getSiteContent('membership_pricing_intro') ?></div>

      <div class="pricing-grid">
        <?php foreach ($pricingTiers as $tier): ?>
        <div class="pricing-card <?= $tier['is_featured'] ? 'featured' : '' ?>">
          <div class="pricing-header">
            <?php if ($tier['is_featured']): ?><div class="featured-badge">Most Popular</div><?php endif; ?>
            <i class="bi <?= e($tier['icon']) ?>"></i>
            <h3><?= e($tier['name']) ?></h3>
          </div>
          <div class="pricing-amount">
            <span class="currency"><?= e($tier['currency']) ?></span>
            <span class="price"><?= e($tier['price'] == floor($tier['price']) ? (int)$tier['price'] : $tier['price']) ?></span>
            <span class="period">/year</span>
          </div>
          <p class="pricing-description"><?= e($tier['description']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="registration-cta">
        <p class="cta-text">Ready to join our community? Purchase your annual membership today!</p>
        <a href="<?= e($registerUrl) ?>" class="btn-register" target="_blank">
          <i class="bi bi-arrow-right-circle"></i> Click Here to Register
        </a>
        <div class="cta-note"><?= getSiteContent('membership_cta_note') ?></div>
        <?php if (!$currentUser): ?>
        <p style="margin-top:12px;font-size:.9rem;color:rgba(255,255,255,0.85)">
          Already purchased? <a href="#" id="loginToActivate" style="color:var(--gold,#d4a73a);font-weight:600">Log in to your account</a> — we'll automatically verify your purchase from Zeffy.
        </p>
        <?php elseif (!isAdmin($currentUser) && !isCoordinator($currentUser)): ?>
        <!-- Zeffy verification for logged-in non-members -->
        <div class="zeffy-verify-box" id="zeffyVerifyBox">
          <div class="zeffy-verify-header">
            <i class="bi bi-check2-circle"></i>
            <strong>Already purchased on Zeffy?</strong>
          </div>
          <p>Zeffy purchases activate your membership automatically, usually within a minute of paying.
             If you just paid and aren't active yet — or used a different email on Zeffy — enter it below and re-check.</p>
          <div id="zeffyVerifyMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>
          <div class="zeffy-email-row">
            <div>
              <label style="font-size:.82rem;color:#6b7280;display:block;margin-bottom:4px">Zeffy email (if different from your OTS email)</label>
              <input type="email" id="zeffyEmailInput" placeholder="Leave blank to use <?= e($currentUser['email']) ?>"
                     style="width:100%;max-width:320px;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem"/>
            </div>
            <button class="btn-verify-zeffy" id="verifyZeffyBtn">
              <i class="bi bi-patch-check"></i> Verify Purchase
            </button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php if (isAdmin($currentUser)): ?>
      <div class="content-edit-bar" style="margin-top:20px">
        <i class="bi bi-pencil-square"></i>
        <span>Pricing tiers &amp; registration link are editable by admins.</span>
        <a href="admin_panel.php?tab=pricing" class="btn-edit-content">Edit Pricing →</a>
      </div>
      <?php elseif (canEditContent($currentUser)): ?>
      <div class="content-edit-bar" style="margin-top:20px">
        <i class="bi bi-pencil-square"></i>
        <span>Pricing intro &amp; notes are editable.</span>
        <a href="coordinator_panel.php?tab=sitecontent" class="btn-edit-content">Edit Content →</a>
      </div>
      <?php endif; ?>
    </section>
  </section>
</div>

<script>
document.getElementById('loginToActivate')?.addEventListener('click', e => {
  e.preventDefault();
  document.getElementById('loginBtn')?.click();
});

document.getElementById('verifyZeffyBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('verifyZeffyBtn');
  const msg = document.getElementById('zeffyVerifyMsg');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Checking Zeffy…';

  const emailInput = document.getElementById('zeffyEmailInput').value.trim();
  const body = emailInput ? { zeffy_email: emailInput } : {};

  const r = await fetch('api.php?action=zeffy_verify_membership', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const d = await r.json();

  msg.style.display = 'block';
  if (d.success) {
    msg.className = 'panel-alert alert-success';
    msg.innerHTML = `<i class="bi bi-check-circle-fill"></i> <strong>${d.message}</strong>`;
    // Reload after 2 seconds to show manage view
    setTimeout(() => location.reload(), 2000);
  } else {
    msg.className = 'panel-alert alert-error';
    msg.textContent = d.error || 'Verification failed. Please try again.';
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-patch-check"></i> Verify Purchase';
  }
});
</script>
<?php endif; ?>
</main>

<?php include_once "footer.php"; ?>
<script src="main.js"></script>
<style>
.membership-manage-card {
  background: var(--card, #f4ecd8);
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 32px;
  margin-bottom: 24px;
}
.mem-status-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.mem-status-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 999px; font-size: .85rem; font-weight: 600;
}
.mem-status-badge.active  { background: #ecfdf5; color: #065f46; }
.mem-status-badge.cancelled { background: #fef2f2; color: #991b1b; }
.mem-detail-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(140px,1fr));
  gap: 16px; margin: 8px 0;
}
.mem-detail-item label { display: block; font-size: .78rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.mem-detail-item strong { font-size: 1rem; color: #1f2937; }
.membership-perks-reminder {
  background: linear-gradient(135deg,#6b0f1a,#8b1f2a);
  color: #fff; border-radius: 12px; padding: 28px 32px;
}
.membership-perks-reminder h3 { color: #d4a73a; margin-bottom: 16px; }
.membership-perks-reminder ul { list-style: none; padding: 0; margin: 0 0 8px; }
.membership-perks-reminder li { padding: 6px 0; display: flex; align-items: center; gap: 10px; }
.membership-perks-reminder .bi-check-circle-fill { color: #d4a73a; }
.btn-dashboard {
  display: inline-flex; align-items: center; gap: 9px;
  margin-top: 20px; padding: 13px 28px;
  background: #d4a73a; color: #5c0d17;
  font-weight: 700; font-size: .98rem; letter-spacing: .01em;
  border-radius: 10px; text-decoration: none;
  box-shadow: 0 6px 18px rgba(0,0,0,.22);
  transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
}
.btn-dashboard:hover {
  background: #e3bd5e;
  transform: translateY(-2px);
  box-shadow: 0 9px 22px rgba(0,0,0,.3);
}
.btn-dashboard .bi-arrow-right { font-size: .9rem; transition: transform .16s ease; }
.btn-dashboard:hover .bi-arrow-right { transform: translateX(4px); }
.panel-alert.alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 10px; padding: 14px 18px; }

.zeffy-verify-box {
  margin-top: 24px;
  border: 2px dashed #d4a73a;
  border-radius: 12px;
  padding: 20px 24px;
  background: #fffdf5;
  text-align: left;
}
.zeffy-verify-header {
  display: flex; align-items: center; gap: 10px;
  font-size: 1rem; color: #1f2937; margin-bottom: 8px;
}
.zeffy-verify-header i { color: #d4a73a; font-size: 1.3rem; }
.zeffy-verify-box p { color: #6b7280; font-size: .88rem; margin-bottom: 14px; }
.zeffy-email-row { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
.btn-verify-zeffy {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 22px; background: #6b0f1a; color: #fff;
  border: none; border-radius: 8px; font-size: .9rem; font-weight: 700;
  cursor: pointer; white-space: nowrap; transition: background .2s;
}
.btn-verify-zeffy:hover  { background: #8b1f2a; }
.btn-verify-zeffy:disabled { opacity: .6; cursor: not-allowed; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { display: inline-block; animation: spin .8s linear infinite; }
</style>
<script>
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));
</script>
</body>
</html>
