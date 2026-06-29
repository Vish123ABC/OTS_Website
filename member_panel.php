<?php
require_once __DIR__ . '/auth.php';
$user = requireLogin();

$isMember = isActiveMember($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Dashboard — Ottawa Tamil Sangam</title>
  <link rel="stylesheet" href="styles.css"/>
  <link rel="stylesheet" href="admin_styles.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet"/>
</head>
<body class="panel-body">

<div class="panel-layout">

  <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
  <aside class="panel-sidebar">
    <div class="sidebar-brand">
      <img src="assets/ots_logo.png" alt="OTS" onerror="this.style.display='none'" class="sidebar-logo"/>
      <span>OTS</span>
      <button class="sidebar-collapse-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar" title="Collapse / expand sidebar"><i class="bi bi-chevron-double-left"></i></button>
    </div>
    <nav class="sidebar-nav">
      <a href="#" class="sidebar-link active" data-tab="overview"><i class="bi bi-grid-1x2"></i> Overview</a>
      <a href="#" class="sidebar-link" data-tab="membership"><i class="bi bi-award"></i> My Membership</a>
      <a href="#" class="sidebar-link" data-tab="profile"><i class="bi bi-person-circle"></i> My Profile</a>
      <a href="#" class="sidebar-link" data-tab="tickets"><i class="bi bi-ticket-perforated"></i> My Tickets</a>
      <a href="#" class="sidebar-link" data-tab="events"><i class="bi bi-calendar-event"></i> Browse Events</a>
    </nav>
    <div class="sidebar-footer">
      <a href="index.php" class="sidebar-link"><i class="bi bi-house"></i> Back to Site</a>
      <a href="#" id="logoutBtn" class="sidebar-link logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </aside>

  <!-- ── Main ────────────────────────────────────────────────────────────── -->
  <main class="panel-main">
    <header class="panel-topbar">
      <div>
        <h1 class="panel-title">My Dashboard</h1>
        <p class="panel-subtitle">Manage your membership and tickets</p>
      </div>
      <div class="topbar-user">
        <span class="user-avatar-lg"><?= e(strtoupper(substr($user['first_name'],0,1))) ?></span>
        <div>
          <p class="topbar-name"><?= e($user['first_name'].' '.$user['last_name']) ?></p>
          <?php if ($isMember): ?>
          <span class="role-badge role-member"><i class="bi bi-award"></i> Member</span>
          <?php else: ?>
          <span class="role-badge role-non-member">Non-Member</span>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- ── Overview Tab ──────────────────────────────────────────────────── -->
    <section class="tab-panel active" id="tab-overview">
      <div class="stats-row">
        <div class="stat-card-panel">
          <i class="bi bi-ticket-perforated stat-icon"></i>
          <div>
            <p class="stat-num" id="stat-tickets">—</p>
            <p class="stat-label">Total Tickets</p>
          </div>
        </div>
        <div class="stat-card-panel">
          <i class="bi bi-calendar-check stat-icon"></i>
          <div>
            <p class="stat-num" id="stat-events">—</p>
            <p class="stat-label">Events Attended</p>
          </div>
        </div>
        <div class="stat-card-panel">
          <i class="bi bi-award stat-icon"></i>
          <div>
            <p class="stat-num" id="stat-plan">—</p>
            <p class="stat-label">Membership Plan</p>
          </div>
        </div>
        <div class="stat-card-panel">
          <i class="bi bi-calendar2-check stat-icon"></i>
          <div>
            <p class="stat-num" id="stat-expiry"><?= e($user['membership_expiry'] ?? 'N/A') ?></p>
            <p class="stat-label">Expires</p>
          </div>
        </div>
      </div>

      <div class="panel-card">
        <h3>Recent Tickets</h3>
        <div id="recentTickets"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Membership Tab ───────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-membership">
      <div class="panel-card" style="max-width:640px">
        <h3>My Membership</h3>
        <div id="memMsg" class="panel-alert" style="display:none"></div>
        <div id="membershipDetails"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Profile Tab ───────────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-profile">
      <div class="panel-card" style="max-width:560px">
        <h3>Personal Information</h3>
        <div id="profileMsg" class="panel-alert" style="display:none"></div>
        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <input type="text" id="pfFirstName" value="<?= e($user['first_name']) ?>"/>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" id="pfLastName" value="<?= e($user['last_name']) ?>"/>
          </div>
        </div>
        <div class="form-group">
          <label>Email <small>(cannot be changed)</small></label>
          <input type="email" value="<?= e($user['email']) ?>" disabled/>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="tel" id="pfPhone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+1 613 555 0000"/>
        </div>
        <hr style="margin:20px 0;border-color:var(--border)"/>
        <h4 style="margin-bottom:12px">Change Password</h4>
        <div class="form-group">
          <label>New Password <small>(leave blank to keep current)</small></label>
          <input type="password" id="pfPassword" placeholder="New password (min 8 chars)"/>
        </div>
        <button class="btn-primary" id="saveProfile">Save Changes</button>
      </div>
    </section>

    <!-- ── Tickets Tab (Zeffy) ───────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-tickets">
      <div class="panel-card">
        <div class="panel-card-header">
          <h3>My Tickets &amp; Purchases</h3>
          <button class="btn-primary btn-sm" id="syncZeffyBtn"><i class="bi bi-arrow-repeat"></i> Sync from Zeffy</button>
        </div>
        <div id="zeffySyncInfo" style="display:none" class="zeffy-sync-info"></div>
        <div id="zeffyEmailNote" style="display:none" class="zeffy-email-note">
          <p><i class="bi bi-info-circle"></i> Using a different email on Zeffy?
            <input type="email" id="zeffyAltEmail" placeholder="your@zeffy-email.com" style="margin:0 8px;padding:5px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.85rem"/>
            <button class="btn-sm btn-outline" id="saveZeffyEmail">Update</button>
          </p>
        </div>
        <div id="zeffySyncMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>
        <div id="allTickets"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Events Tab ────────────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-events">
      <div class="panel-card">
        <h3>Upcoming Events</h3>
        <?php if ($isMember): ?>
        <p class="section-hint"><i class="bi bi-award text-gold"></i> As a member, you automatically get member pricing on all events.</p>
        <?php else: ?>
        <p class="section-hint"><i class="bi bi-info-circle"></i> You're seeing non-member prices. <a href="membership.php" style="color:var(--gold,#d4a73a);font-weight:600">Get a membership</a> for exclusive discounted pricing.</p>
        <?php endif; ?>
        <div id="eventsList" class="events-grid-panel">
          <p class="loading-text">Loading…</p>
        </div>
      </div>
    </section>

  </main>
</div>


<script>
const API = 'api.php';
const USER_IS_MEMBER = <?= $isMember ? 'true' : 'false' ?>;
let allTicketsData  = [];
let allEventsData   = [];
let membershipData  = null;

async function api(action, data = null) {
  const opts = data
    ? { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) }
    : { method: 'GET' };
  const r = await fetch(`${API}?action=${action}`, opts);
  return r.json();
}

// ── Tab switching ─────────────────────────────────────────────────────────
document.querySelectorAll('.sidebar-link[data-tab]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const tab = link.dataset.tab;
    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    link.classList.add('active');
    document.getElementById('tab-' + tab)?.classList.add('active');
    if (tab === 'tickets')    loadTickets();
    if (tab === 'events')     loadEvents();
    if (tab === 'membership') loadMembership();
  });
});

// ── Load data ─────────────────────────────────────────────────────────────
async function loadOverview() {
  const el = document.getElementById('recentTickets');

  if (USER_IS_MEMBER) {
    // Members: pull from Zeffy cache
    const d = await api('zeffy_get_tickets');
    if (d.success) {
      allTicketsData = d.purchases || [];
      document.getElementById('stat-tickets').textContent = allTicketsData.length;
      document.getElementById('stat-events').textContent  = allTicketsData.length;
    }
    const recent = allTicketsData.slice(0, 5);
    if (!recent.length) {
      el.innerHTML = '<p class="empty-state">No Zeffy purchases synced yet. Go to <strong>My Tickets</strong> → <em>Sync from Zeffy</em>.</p>';
    } else {
      el.innerHTML = `<table class="data-table"><thead><tr><th>Event / Form</th><th>Ticket Type</th><th>Qty</th><th>Amount</th><th>Date</th></tr></thead>
        <tbody>${recent.map(t => `
          <tr>
            <td>${esc(t.form_title)}</td>
            <td>${esc(t.ticket_type||'General')}</td>
            <td>${t.quantity}</td>
            <td>${t.currency} $${parseFloat(t.amount||0).toFixed(2)}</td>
            <td>${t.bought_at ? t.bought_at.slice(0,10) : '—'}</td>
          </tr>`).join('')}
        </tbody></table>`;
    }
    // Load membership plan name
    const md = await api('get_my_membership');
    if (md.success && md.membership) {
      document.getElementById('stat-plan').textContent = md.membership.tier_name || 'Active';
    } else {
      document.getElementById('stat-plan').textContent = 'Active';
    }
  } else {
    // Non-members: pull from site tickets table
    const d = await api('get_my_tickets');
    if (d.success) {
      allTicketsData = d.tickets || [];
      document.getElementById('stat-tickets').textContent = allTicketsData.length;
      document.getElementById('stat-events').textContent  = allTicketsData.length;
    }
    document.getElementById('stat-plan').textContent = 'None';
    const recent = allTicketsData.slice(0, 5);
    if (!recent.length) {
      el.innerHTML = `<p class="empty-state">No tickets yet. <a href="events.php" style="color:var(--gold,#d4a73a);font-weight:600">Browse events</a> to get started.</p>
        <div style="margin-top:16px;padding:16px;background:#fefce8;border:1.5px dashed #d4a73a;border-radius:10px">
          <p style="margin:0;font-size:.9rem;color:#92400e"><i class="bi bi-award"></i> <strong>Become a member</strong> to unlock discounted ticket prices and exclusive events. <a href="membership.php" style="color:#92400e;font-weight:600;text-decoration:underline">Learn more →</a></p>
        </div>`;
    } else {
      el.innerHTML = `<table class="data-table"><thead><tr><th>Event</th><th>Type</th><th>Qty</th><th>Total</th><th>Date</th></tr></thead>
        <tbody>${recent.map(t => `
          <tr>
            <td>${esc(t.title||'Event')}</td>
            <td>${esc(t.ticket_type||'Regular')}</td>
            <td>${t.quantity||1}</td>
            <td>$${parseFloat(t.total_price||0).toFixed(2)}</td>
            <td>${t.purchase_date ? t.purchase_date.slice(0,10) : '—'}</td>
          </tr>`).join('')}
        </tbody></table>`;
    }
  }
}

async function loadTickets() {
  if (!USER_IS_MEMBER) {
    // Non-members see regular site tickets
    const d = await api('get_my_tickets');
    const el = document.getElementById('allTickets');
    const tickets = d.tickets || [];
    if (!tickets.length) {
      el.innerHTML = `<p class="empty-state">No tickets yet. <a href="events.php" style="color:var(--gold,#d4a73a);font-weight:600">Browse events</a> to purchase tickets at non-member prices.</p>`;
    } else {
      el.innerHTML = `<table class="data-table"><thead><tr><th>Event</th><th>Type</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>${tickets.map(t => `
          <tr>
            <td><strong>${esc(t.title||'Event')}</strong>${t.event_date?`<br><small>${t.event_date}</small>`:''}</td>
            <td>${esc(t.ticket_type||'Regular')}</td>
            <td>${t.quantity||1}</td>
            <td>$${parseFloat(t.total_price||0).toFixed(2)}</td>
            <td><span class="status-badge status-${(t.status||'active').toLowerCase()}">${t.status||'active'}</span></td>
            <td>${t.purchase_date ? t.purchase_date.slice(0,10) : '—'}</td>
          </tr>`).join('')}
        </tbody></table>`;
    }
    return;
  }

  // Members: pull from Zeffy cache
  const d = await api('zeffy_get_tickets');
  if (!d.success) {
    document.getElementById('allTickets').innerHTML = '<p class="empty-state">Could not load tickets. Try syncing from Zeffy.</p>';
    showZeffySyncHint();
    return;
  }
  const syncEl = document.getElementById('zeffySyncInfo');
  if (d.synced_at) {
    syncEl.style.display = 'flex';
    syncEl.innerHTML = `<i class="bi bi-clock"></i> Last synced: ${new Date(d.synced_at.replace(' ', 'T')).toLocaleString()}`;
  }
  document.getElementById('zeffyEmailNote').style.display = 'block';
  if (d.zeffy_email) document.getElementById('zeffyAltEmail').value = d.zeffy_email;

  renderZeffyTickets(d.purchases || []);
}

function showZeffySyncHint() {
  document.getElementById('zeffyEmailNote').style.display = 'block';
  document.getElementById('zeffySyncInfo').style.display = 'flex';
  document.getElementById('zeffySyncInfo').innerHTML = '<i class="bi bi-info-circle"></i> No Zeffy data cached. Click "Sync from Zeffy" to load your purchases.';
}

function renderZeffyTickets(purchases) {
  const el = document.getElementById('allTickets');
  if (!purchases.length) {
    el.innerHTML = '<p class="empty-state">No Zeffy purchases found. Click "Sync from Zeffy" to check your purchases, or make sure you\'re using the same email as your Zeffy account.</p>';
    return;
  }
  el.innerHTML = `<table class="data-table">
    <thead><tr><th>Event / Form</th><th>Ticket Type</th><th>Qty</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>${purchases.map(p => `
      <tr>
        <td><strong>${esc(p.form_title)}</strong></td>
        <td>${esc(p.ticket_type || 'General')}</td>
        <td>${p.quantity}</td>
        <td>${p.currency} $${parseFloat(p.amount||0).toFixed(2)}</td>
        <td><span class="status-badge status-${(p.status||'').toLowerCase()}">${p.status||'completed'}</span></td>
        <td>${p.bought_at ? p.bought_at.slice(0,10) : '—'}</td>
      </tr>`).join('')}
    </tbody></table>
    <p class="table-footer">${purchases.length} purchase${purchases.length !== 1 ? 's' : ''} from Zeffy</p>`;
}

async function loadEvents() {
  const d = await api('get_events&upcoming=1');
  allEventsData = d.events || [];
  const el = document.getElementById('eventsList');
  if (!allEventsData.length) { el.innerHTML = '<p class="empty-state">No upcoming events.</p>'; return; }
  el.innerHTML = allEventsData.map(ev => `
    <div class="event-card-panel">
      ${ev.image_path ? `<img src="${esc(ev.image_path)}" class="event-card-img" alt="${esc(ev.title)}"/>` : ''}
      <div class="event-card-body">
        <h4>${esc(ev.title)}</h4>
        ${ev.title_tamil ? `<p class="event-tamil">${esc(ev.title_tamil)}</p>` : ''}
        <p class="event-meta"><i class="bi bi-calendar3"></i> ${ev.event_date||'TBD'} ${ev.event_time ? '@ '+ev.event_time : ''}</p>
        ${ev.location ? `<p class="event-meta"><i class="bi bi-geo-alt"></i> ${esc(ev.location)}</p>` : ''}
        ${ev.member_price ? `<div class="event-prices"><span class="price-member"><i class="bi bi-award"></i> Member: $${parseFloat(ev.member_price).toFixed(2)}</span></div>` : ''}
        ${ev.ticket_url ? `<div style="margin-top:12px"><a href="${esc(ev.ticket_url)}" target="_blank" class="btn-primary btn-sm"><i class="bi bi-ticket-perforated"></i> Get Tickets</a></div>` : ''}
      </div>
    </div>`).join('');
}

// ── Membership management ─────────────────────────────────────────────────
async function loadMembership() {
  const el = document.getElementById('membershipDetails');

  if (!USER_IS_MEMBER) {
    el.innerHTML = `
      <div class="mem-status-row">
        <span class="mem-status-badge" style="background:#f3f4f6;color:#6b7280;border:1.5px solid #d1d5db">
          <i class="bi bi-person"></i> Non-Member
        </span>
      </div>
      <p style="color:#6b7280;margin-top:14px">You don't have an active membership yet.</p>
      <p style="color:#6b7280;font-size:.9rem;margin-top:6px">Members get access to discounted ticket prices and exclusive events.</p>
      <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="membership.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;padding:10px 20px;border-radius:8px;font-weight:600">
          <i class="bi bi-award"></i> Get Membership
        </a>
        <a href="membership.php#verify" class="btn-outline" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;padding:10px 20px;border-radius:8px">
          <i class="bi bi-link-45deg"></i> Link Zeffy Account
        </a>
      </div>`;
    return;
  }

  const d = await api('get_my_membership');
  membershipData = d.membership;

  if (!d.membership) {
    el.innerHTML = `
      <div class="mem-status-row">
        <span class="mem-status-badge active"><i class="bi bi-award"></i> Active Member</span>
      </div>
      <p style="color:#6b7280;margin-top:12px">Your membership is active. Contact us for details about your plan.</p>`;
    return;
  }

  const m = d.membership;

  el.innerHTML = `
    <div class="mem-status-row">
      <span class="mem-status-badge active"><i class="bi bi-award"></i> Active Member</span>
    </div>
    <div class="mem-detail-grid">
      <div class="mem-detail-item">
        <label>Plan</label>
        <strong>${esc(m.tier_name || 'Standard')}</strong>
      </div>
      <div class="mem-detail-item">
        <label>Amount Paid</label>
        <strong>$${parseFloat(m.price_paid||0).toFixed(2)}/year</strong>
      </div>
      <div class="mem-detail-item">
        <label>Started</label>
        <strong>${m.started_at || '—'}</strong>
      </div>
      <div class="mem-detail-item">
        <label>Expires</label>
        <strong>${m.expires_at || '—'}</strong>
      </div>
    </div>
    <p style="font-size:.85rem;color:#6b7280;margin-top:16px"><i class="bi bi-info-circle"></i> To renew or cancel your membership, visit <a href="https://www.zeffy.com" target="_blank" style="color:var(--gold,#d4a73a)">Zeffy</a> or contact us at <a href="mailto:ottawatamilsangam@gmail.com" style="color:var(--gold,#d4a73a)">ottawatamilsangam@gmail.com</a>.</p>`;
}


// ── Zeffy sync ────────────────────────────────────────────────────────────
document.getElementById('syncZeffyBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('syncZeffyBtn');
  const msg = document.getElementById('zeffySyncMsg');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Syncing…';

  const altEmail = document.getElementById('zeffyAltEmail')?.value?.trim();
  const body = altEmail ? { zeffy_email: altEmail } : {};

  const d = await api('zeffy_sync_tickets', body);
  msg.style.display = 'block';
  if (d.success) {
    msg.className = 'panel-alert alert-success';
    msg.textContent = `✓ Synced ${d.count} purchase${d.count !== 1 ? 's' : ''} from Zeffy.`;
    renderZeffyTickets(d.purchases || []);
    // Refresh the "Last synced" timestamp shown to the user
    const syncEl = document.getElementById('zeffySyncInfo');
    if (syncEl) {
      syncEl.style.display = 'flex';
      const when = d.synced_at ? new Date(d.synced_at.replace(' ', 'T')) : new Date();
      syncEl.innerHTML = `<i class="bi bi-clock"></i> Last synced: ${when.toLocaleString()}`;
    }
    // Update overview stat
    allTicketsData = d.purchases || [];
    document.getElementById('stat-tickets').textContent = d.count;
  } else {
    msg.className = 'panel-alert alert-error';
    msg.textContent = d.error || 'Sync failed. Check your Zeffy email or contact support.';
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync from Zeffy';
  setTimeout(() => { msg.style.display = 'none'; }, 6000);
});

document.getElementById('saveZeffyEmail')?.addEventListener('click', async () => {
  const email = document.getElementById('zeffyAltEmail')?.value?.trim();
  if (!email) return;
  const d = await api('zeffy_update_user_email', { zeffy_email: email });
  if (d.success) {
    document.getElementById('zeffySyncMsg').className = 'panel-alert alert-success';
    document.getElementById('zeffySyncMsg').textContent = '✓ Zeffy email saved. Click "Sync from Zeffy" to load purchases.';
    document.getElementById('zeffySyncMsg').style.display = 'block';
  }
});

// ── Profile save ──────────────────────────────────────────────────────────
document.getElementById('saveProfile')?.addEventListener('click', async () => {
  const btn = document.getElementById('saveProfile');
  btn.disabled = true; btn.textContent = 'Saving…';
  const d = await api('update_profile', {
    first_name:   document.getElementById('pfFirstName').value,
    last_name:    document.getElementById('pfLastName').value,
    phone:        document.getElementById('pfPhone').value,
    new_password: document.getElementById('pfPassword').value,
  });
  const msg = document.getElementById('profileMsg');
  msg.className = d.success ? 'panel-alert alert-success' : 'panel-alert alert-error';
  msg.textContent = d.success ? '✓ Profile updated successfully.' : (d.error || 'Save failed.');
  msg.style.display = 'block';
  btn.disabled = false; btn.textContent = 'Save Changes';
  if (d.success) document.getElementById('pfPassword').value = '';
});

// ── Logout ────────────────────────────────────────────────────────────────
document.getElementById('logoutBtn')?.addEventListener('click', async e => {
  e.preventDefault();
  const d = await (await fetch('api.php?action=logout', {method:'POST'})).json();
  if (d.success) window.location.href = d.redirect || 'index.php';
});

function esc(s) {
  const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
}

// Init
loadOverview();

// ── Collapsible sidebar ──────────────────────────────────────────────────
(function () {
  var KEY = 'otsSidebarCollapsed';
  var layout = document.querySelector('.panel-layout');
  var btn = document.getElementById('sidebarToggle');
  if (!layout || !btn) return;
  if (localStorage.getItem(KEY) === '1') layout.classList.add('collapsed');
  btn.addEventListener('click', function () {
    layout.classList.toggle('collapsed');
    localStorage.setItem(KEY, layout.classList.contains('collapsed') ? '1' : '0');
  });
})();
</script>
</body>
</html>
