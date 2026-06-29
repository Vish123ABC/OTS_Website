<?php
require_once __DIR__ . '/auth.php';
$user = requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Admin Panel — Ottawa Tamil Sangam</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="admin_styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap"
    rel="stylesheet" />
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet" />
</head>

<body class="panel-body">

  <div class="panel-layout">

    <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
    <aside class="panel-sidebar admin-sidebar">
      <div class="sidebar-brand">
        <img src="assets/ots_logo.png" alt="OTS" onerror="this.style.display='none'" class="sidebar-logo" />
        <span>OTS</span>
        <button class="sidebar-collapse-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar"
          title="Collapse / expand sidebar"><i class="bi bi-chevron-double-left"></i></button>
      </div>
      <div class="sidebar-role-badge admin-badge">
        <i class="bi bi-shield-check"></i> Admin
      </div>
      <nav class="sidebar-nav">
        <a href="#" class="sidebar-link active" data-tab="overview"><i class="bi bi-grid-1x2"></i> Overview</a>
        <a href="#" class="sidebar-link" data-tab="events"><i class="bi bi-calendar-event"></i> Events</a>
        <a href="#" class="sidebar-link" data-tab="posts"><i class="bi bi-megaphone"></i> Posts</a>
        <a href="#" class="sidebar-link" data-tab="committee"><i class="bi bi-people-fill"></i> Committee</a>
        <a href="#" class="sidebar-link" data-tab="pricing"><i class="bi bi-currency-dollar"></i> Pricing</a>
        <a href="#" class="sidebar-link" data-tab="slideshow"><i class="bi bi-images"></i> Slideshow</a>
        <a href="#" class="sidebar-link" data-tab="vision"><i class="bi bi-stars"></i> Vision</a>
        <a href="#" class="sidebar-link" data-tab="sitecontent"><i class="bi bi-file-text"></i> Site Content</a>
        <a href="#" class="sidebar-link" data-tab="memberships"><i class="bi bi-award"></i> Memberships</a>
        <a href="#" class="sidebar-link" data-tab="users"><i class="bi bi-people"></i> Users</a>
        <a href="#" class="sidebar-link" data-tab="tickets"><i class="bi bi-ticket-perforated"></i> Tickets</a>
      </nav>
      <div class="sidebar-footer">
        <a href="index.php" class="sidebar-link"><i class="bi bi-house"></i> View Site</a>
        <a href="#" id="logoutBtn" class="sidebar-link logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </aside>

    <!-- ── Main ────────────────────────────────────────────────────────────── -->
    <main class="panel-main">
      <header class="panel-topbar">
        <div>
          <h1 class="panel-title">Admin Panel</h1>
          <p class="panel-subtitle">Full control over Ottawa Tamil Sangam</p>
        </div>
        <div class="topbar-user">
          <span class="user-avatar-lg admin-avatar"><?= e(strtoupper(substr($user['first_name'],0,1))) ?></span>
          <div>
            <p class="topbar-name"><?= e($user['first_name'].' '.$user['last_name']) ?></p>
            <span class="role-badge role-admin">Admin</span>
          </div>
        </div>
      </header>

      <!-- ── Overview ──────────────────────────────────────────────────────── -->
      <section class="tab-panel active" id="tab-overview">
        <div class="stats-row">
          <div class="stat-card-panel"><i class="bi bi-people stat-icon"></i>
            <div>
              <p class="stat-num" id="stat-users">—</p>
              <p class="stat-label">Total Users</p>
            </div>
          </div>
          <div class="stat-card-panel"><i class="bi bi-calendar-event stat-icon"></i>
            <div>
              <p class="stat-num" id="stat-events">—</p>
              <p class="stat-label">Events</p>
            </div>
          </div>
          <div class="stat-card-panel"><i class="bi bi-megaphone stat-icon"></i>
            <div>
              <p class="stat-num" id="stat-posts">—</p>
              <p class="stat-label">Posts</p>
            </div>
          </div>
          <div class="stat-card-panel"><i class="bi bi-ticket-perforated stat-icon"></i>
            <div>
              <p class="stat-num" id="stat-tickets">—</p>
              <p class="stat-label">Tickets</p>
            </div>
          </div>
        </div>
        <div class="overview-grid">
          <div class="panel-card">
            <h3>Recent Users</h3>
            <div id="overviewUsers">
              <p class="loading-text">Loading…</p>
            </div>
          </div>
          <div class="panel-card">
            <h3>Recent Tickets</h3>
            <div id="overviewTickets">
              <p class="loading-text">Loading…</p>
            </div>
          </div>
        </div>
        <div class="panel-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3>Quick Actions</h3>
          </div>
          <div class="quick-actions">
            <button class="quick-btn" onclick="switchTab('events');openNewEvent()"><i
                class="bi bi-calendar-plus"></i><span>New Event</span></button>
            <button class="quick-btn" onclick="switchTab('posts');openNewPost()"><i
                class="bi bi-plus-square"></i><span>New Post</span></button>
            <button class="quick-btn" onclick="switchTab('users')"><i class="bi bi-person-plus"></i><span>Manage
                Users</span></button>
            <button class="quick-btn" onclick="switchTab('tickets')"><i class="bi bi-ticket-perforated"></i><span>View
                Tickets</span></button>
            <button class="quick-btn" onclick="switchTab('sitecontent')"><i class="bi bi-pencil-square"></i><span>Edit
                Site</span></button>
          </div>
        </div>
      </section>

      <!-- ── Events ─────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-events">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Events Management</h3>
            <button class="btn-primary" id="newEventBtn"><i class="bi bi-plus-lg"></i> New Event</button>
          </div>
          <div class="filter-bar">
            <label><input type="checkbox" id="showUnpublished" checked /> Show all (incl. unpublished)</label>
          </div>
          <div id="eventsTable">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
      </section>

      <!-- ── Posts ─────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-posts">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Posts &amp; Announcements</h3>
            <button class="btn-primary" id="newPostBtn"><i class="bi bi-plus-lg"></i> New Post</button>
          </div>
          <div id="postsTable">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
      </section>

      <!-- ── Committee ────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-committee">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Committee Members</h3>
            <button class="btn-primary" id="newMemberBtn"><i class="bi bi-plus-lg"></i> Add Member</button>
          </div>
          <p class="section-hint">Drag rows to reorder. Changes appear immediately on the committee page.</p>
          <div id="committeeMembersList">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
      </section>

      <!-- ── Pricing ──────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-pricing">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Membership Pricing</h3>
            <button class="btn-primary" id="newTierBtn"><i class="bi bi-plus-lg"></i> Add Tier</button>
          </div>
          <p class="section-hint">Prices appear on the Membership page. Changes are live immediately.</p>
          <div id="pricingTiersList">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
        <div class="panel-card" style="margin-top:20px">
          <h3>Registration Link</h3>
          <p class="section-hint">The URL visitors click to purchase membership.</p>
          <div class="form-group" style="margin-top:12px">
            <label>Eventbrite / Registration URL</label>
            <input type="url" id="registerUrlInput" placeholder="https://…" style="font-size:.85rem" />
          </div>
          <button class="btn-primary" id="saveRegisterUrlBtn" style="margin-top:8px">Save URL</button>
          <div id="registerUrlMsg" class="panel-alert" style="display:none;margin-top:8px"></div>
        </div>
        <div class="panel-card" style="margin-top:20px">
          <div class="panel-card-header">
            <h3>Benefit Panels</h3>
            <button class="btn-primary" id="newBenefitBtn"><i class="bi bi-plus-lg"></i> Add Panel</button>
          </div>
          <p class="section-hint">These cards appear in the Benefits section of the Membership page. Edit titles, icons,
            and content — or add/remove panels entirely.</p>
          <div id="benefitPanelsList">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
      </section>

      <!-- ── Slideshow ─────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-slideshow">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Home Page Slideshow</h3>
            <label class="btn-primary" style="cursor:pointer">
              <i class="bi bi-cloud-upload"></i> Upload Photos
              <input type="file" id="slideshowUploadInput" accept="image/*" multiple style="display:none" />
            </label>
          </div>
          <p class="section-hint">Active photos appear in the home page slideshow. Toggle to show/hide without deleting.
            Upload new photos or remove ones you no longer want.</p>
          <div id="slideshowGrid" class="slideshow-manage-grid">
            <p class="loading-text">Loading…</p>
          </div>
          <div id="slideshowMsg" class="panel-alert" style="display:none;margin-top:12px"></div>
        </div>
      </section>

      <!-- ── Vision ──────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-vision">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Stats Row</h3>
            <button class="btn-primary" id="newStatBtn"><i class="bi bi-plus-lg"></i> Add Stat</button>
          </div>
          <p class="section-hint">The three headline numbers shown below the Mission section on the Vision page.</p>
          <div id="visionStatsList">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
        <div class="panel-card" style="margin-top:20px">
          <div class="panel-card-header">
            <h3>Core Values</h3>
            <button class="btn-primary" id="newValueBtn"><i class="bi bi-plus-lg"></i> Add Value</button>
          </div>
          <p class="section-hint">The value cards shown in the "Our Core Values" section on the Vision page.</p>
          <div id="visionValuesList">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
      </section>

      <!-- ── Site Content ───────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-sitecontent">
        <div class="panel-card" style="margin-bottom:20px">
          <h3><i class="bi bi-envelope-at" style="color:var(--maroon)"></i> Contact Form Recipient</h3>
          <p class="section-hint">Messages submitted via the "Send Us a Message" form on the Contact page will be
            delivered to this address.</p>
          <div class="form-group" style="margin-top:12px">
            <label>Recipient Email Address</label>
            <input type="email" id="contactRecipientEmail" placeholder="ottawatamilsangam@gmail.com"
              style="max-width:360px" />
          </div>
          <button class="btn-primary" id="saveContactEmailBtn" style="margin-top:8px"><i class="bi bi-floppy"></i>
            Save</button>
          <div id="contactEmailMsg" class="panel-alert" style="display:none;margin-top:8px"></div>
        </div>

        <div class="panel-card" style="margin-bottom:20px">
          <div class="panel-card-header">
            <h3><i class="bi bi-inbox-fill" style="color:var(--maroon)"></i> Contact Messages
              <span id="contactMsgBadge"
                style="display:none;background:#6b0f1a;color:#fff;font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:999px;margin-left:6px"></span>
            </h3>
            <button class="btn-outline btn-sm" id="refreshContactMsgs"><i class="bi bi-arrow-clockwise"></i>
              Refresh</button>
          </div>
          <p class="section-hint">Messages from the "Send Us a Message" form. Saved here so nothing is lost even if the
            email notification doesn't go through.</p>
          <div id="contactMessagesList">
            <p class="loading-text">Loading…</p>
          </div>
        </div>

        <div class="panel-card">
          <h3>Site Content Editor</h3>
          <p class="section-hint">Edit the text content of the live website. Changes appear immediately for all
            visitors.</p>
          <div id="siteContentSections">
            <p class="loading-text">Loading…</p>
          </div>
          <div id="contentSaveMsg" class="panel-alert" style="display:none"></div>
        </div>
      </section>

      <!-- ── Users ─────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-users">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>User Management</h3>
            <div class="filter-bar">
              <input type="text" id="userSearch" placeholder="Search name or email…" style="width:220px" />
              <select id="roleFilter">
                <option value="">All Roles</option>
                <option value="non_member">Non-Members</option>
                <option value="member">Members</option>
                <option value="social_media">Social Media</option>
                <option value="membership_coordinator">Membership Coord.</option>
                <option value="cultural_coordinator">Cultural Coord.</option>
                <option value="sports_coordinator">Sports Coord.</option>
                <option value="coordinator">Coordinator (General)</option>
                <option value="admin">Admins</option>
              </select>
            </div>
          </div>
          <div id="usersTable">
            <p class="loading-text">Loading…</p>
          </div>
        </div>

        <!-- Role Assignment Search -->
        <div class="panel-card" style="margin-top:20px">
          <h3><i class="bi bi-person-gear" style="color:var(--maroon)"></i> Assign Role</h3>
          <p class="section-hint">Search by email to find a user and assign them a role instantly.</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:12px">
            <div class="form-group" style="flex:1;min-width:220px;margin:0">
              <label>Search Email</label>
              <input type="email" id="roleSearchEmail" placeholder="user@example.com" style="width:100%" />
            </div>
            <button class="btn-primary" id="roleSearchBtn"><i class="bi bi-search"></i> Find</button>
          </div>
          <div id="roleSearchResults" style="margin-top:12px"></div>
        </div>
      </section>

      <!-- ── Memberships ──────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-memberships">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>Membership Management</h3>
            <div class="filter-bar">
              <input type="text" id="memSearch" placeholder="Search name or email…" style="width:220px"
                oninput="filterMemberships()" />
              <select id="memStatusFilter" onchange="filterMemberships()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="none">Non-Member</option>
                <option value="expired">Expired</option>
              </select>
            </div>
          </div>
          <p class="section-hint">Activate memberships for users who have purchased externally. Set their plan, price
            paid, and expiry date.</p>
          <div id="membershipsTable">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
        <div class="panel-card" style="margin-top:20px">
          <h3>Membership Purchase History</h3>
          <p class="section-hint">All membership activations logged below.</p>
          <div id="membershipHistoryTable">
            <p class="loading-text">Loading…</p>
          </div>
        </div>

        <!-- Sync memberships & tickets -->
        <div class="zeffy-settings-box" id="zeffySettingsBox">
          <h4>
            <i class="bi bi-arrow-repeat" style="color:#d4a73a"></i>
            Sync Memberships &amp; Tickets
            <span class="zeffy-status-dot unknown" id="zeffyStatusDot" title="Sync status"></span>
          </h4>
          <p style="font-size:.85rem;color:#6b7280;margin-bottom:20px">
            Member purchases and event tickets are matched to accounts by email. Memberships get activated, and tickets
            show up in the buyer's account. Set up automatic updates below, or upload a file anytime to catch up.
          </p>
          <div id="zeffySettingsMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>

          <!-- Automatic updates -->
          <h5 style="font-size:.92rem;color:#1f2937;margin:0 0 6px"><i class="bi bi-lightning-charge-fill"
              style="color:#d4a73a"></i> Automatic updates</h5>
          <p style="font-size:.84rem;color:#6b7280;margin-bottom:12px">
            Send purchases here the moment they happen so accounts stay current without anyone lifting a finger. In your
            payment provider's settings, add the address below as a webhook for completed payments.
          </p>
          <div class="form-group">
            <label>Sync address <small>(copy this into your payment provider's webhook settings)</small></label>
            <div style="display:flex;gap:8px">
              <input type="text" id="zeffyWebhookUrl" readonly
                style="flex:1;font-family:monospace;font-size:.82rem;background:#f9fafb" />
              <button class="btn-outline btn-sm" id="copyWebhookBtn" type="button"><i class="bi bi-clipboard"></i>
                Copy</button>
            </div>
            <small style="color:#9ca3af">Treat this like a password. Regenerate it if it ever gets shared by
              mistake.</small>
          </div>

          <hr style="margin:18px 0;border-color:#eef0f3" />

          <!-- Upload a file -->
          <h5 style="font-size:.92rem;color:#1f2937;margin:0 0 6px"><i class="bi bi-upload" style="color:#d4a73a"></i>
            Upload a file</h5>
          <p style="font-size:.84rem;color:#6b7280;margin-bottom:12px">
            Already have an export? Drop in a spreadsheet of purchases and every row is matched to an account. Great for
            a first import or a periodic catch-up.
          </p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="file" id="zeffyCsvFile" accept=".csv,text/csv" style="font-size:.85rem;max-width:320px" />
            <button class="btn-primary btn-sm" id="csvUploadBtn" type="button"><i class="bi bi-cloud-arrow-up"></i>
              Upload &amp; Sync</button>
          </div>
          <small style="color:#9ca3af;display:block;margin-top:6px">CSV format. We read the buyer's email, amount,
            date, and the product/event name from each row, then match by email.</small>
          <div id="csvImportMsg" class="panel-alert" style="display:none;margin-top:12px"></div>

          <hr style="margin:18px 0;border-color:#eef0f3" />

          <!-- Shared setting -->
          <div class="form-group">
            <label>Membership product name <small>(how we tell a membership apart from an event ticket)</small></label>
            <input type="text" id="zeffyFormSlug" placeholder="annual membership" style="font-size:.9rem" />
            <small style="color:#6b7280">
              Any purchase whose product name contains this text (or the word "membership") counts as a membership.
              Current: <strong id="zeffySlugPreview">annual membership</strong>
            </small>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px">
            <button class="btn-primary btn-sm" id="saveZeffySettingsBtn"><i class="bi bi-floppy"></i> Save</button>
            <button class="btn-outline btn-sm" id="regenWebhookBtn" type="button"><i class="bi bi-arrow-repeat"></i>
              Regenerate sync address</button>
          </div>

          <!-- Status -->
          <div id="zeffyStatusBox"
            style="margin-top:18px;padding:12px 16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:.84rem;color:#374151;display:flex;gap:24px;flex-wrap:wrap">
            <span><i class="bi bi-clock-history" style="color:#d4a73a"></i> Last update: <strong
                id="zeffyLastReceived">—</strong></span>
            <span><i class="bi bi-inbox" style="color:#d4a73a"></i> Updates received: <strong
                id="zeffyTotalReceived">0</strong></span>
            <span><i class="bi bi-hourglass-split" style="color:#d4a73a"></i> Waiting on sign-up: <strong
                id="zeffyPendingCount">0</strong></span>
          </div>

          <details id="zeffyPayloadWrap" style="margin-top:10px;display:none">
            <summary style="cursor:pointer;font-size:.83rem;color:#6b7280">Show the last update we received</summary>
            <pre id="zeffyLastPayload"
              style="font-size:.74rem;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto;max-height:280px;margin-top:8px"></pre>
          </details>

          <hr style="margin:20px 0;border-color:#e5e7eb" />
          <h5 style="font-size:.92rem;color:#1f2937;margin-bottom:6px"><i class="bi bi-people-fill"
              style="color:#d4a73a"></i> Match waiting purchases</h5>
          <p style="font-size:.83rem;color:#6b7280;margin-bottom:10px">
            When someone pays before they have an account, we hold their purchase until they sign up. Use this to match
            any waiting purchases against existing accounts right now.
          </p>
          <div id="bulkSyncMsg" class="panel-alert" style="display:none;margin-bottom:10px"></div>
          <button class="btn-primary btn-sm" id="bulkSyncBtn"><i class="bi bi-arrow-repeat"></i> Match waiting
            purchases</button>
        </div>
      </section>

      <!-- ── Tickets ────────────────────────────────────────────────────────── -->
      <section class="tab-panel" id="tab-tickets">
        <div class="panel-card">
          <div class="panel-card-header">
            <h3>All Ticket Purchases</h3>
            <div class="filter-bar">
              <select id="ticketStatusFilter">
                <option value="">All Statuses</option>
                <option value="confirmed">Confirmed</option>
                <option value="cancelled">Cancelled</option>
                <option value="pending">Pending</option>
              </select>
            </div>
          </div>
          <div id="ticketsTable">
            <p class="loading-text">Loading…</p>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- ════════════════════ MODALS ════════════════════════════════════════════ -->

  <!-- Event Modal -->
  <div id="eventModal" class="panel-modal wide-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeEventModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="eventModalTitle">New Event</h3>
      <div id="eventSaveMsg" class="panel-alert" style="display:none"></div>
      <input type="hidden" id="editEventId" />

      <div class="form-row">
        <div class="form-group">
          <label>Title (English) <span class="required">*</span></label>
          <input type="text" id="evTitle" placeholder="Event title" />
        </div>
        <div class="form-group">
          <label>Title (Tamil)</label>
          <input type="text" id="evTitleTamil" placeholder="நிகழ்வு தலைப்பு" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Date</label>
          <input type="date" id="evDate" />
        </div>
        <div class="form-group">
          <label>Time</label>
          <input type="text" id="evTime" placeholder="6:00 PM" />
        </div>
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" id="evLocation" placeholder="Venue name and address" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Member Price ($)</label>
          <input type="number" id="evMemberPrice" placeholder="20.00" step="0.01" min="0" />
        </div>
        <div class="form-group">
          <label>Regular Price ($)</label>
          <input type="number" id="evRegularPrice" placeholder="30.00" step="0.01" min="0" />
        </div>
      </div>
      <div class="form-group">
        <label>External Ticket URL</label>
        <input type="url" id="evTicketUrl" placeholder="https://eventbrite.ca/…" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Status</label>
          <select id="evPublished">
            <option value="1">✅ Published</option>
            <option value="0">📋 Draft / Hidden</option>
          </select>
        </div>
        <!-- is_upcoming is auto-derived from event_date on the server; hidden field kept for no-date edge case -->
        <input type="hidden" id="evUpcoming" value="1" />
      </div>
      <div class="form-group">
        <label>Description</label>
        <div id="evDescEditor" style="height:200px;background:#fff"></div>
      </div>
      <div class="form-group">
        <label>Event Image</label>
        <div class="image-uploader">
          <div class="upload-zone" id="evUploadZone"><i class="bi bi-cloud-upload"></i>
            <p>Drag &amp; drop or click</p><small>JPEG, PNG, WebP · max 8 MB</small>
          </div>
          <input type="file" id="evImageFile" accept="image/*" style="display:none" />
          <div class="image-preview" id="evImagePreview" style="display:none">
            <img id="evPreviewImg" src="" alt="Preview" />
            <button class="remove-image" id="removeEvImage"><i class="bi bi-x-circle-fill"></i></button>
          </div>
          <input type="hidden" id="evImagePath" value="" />
        </div>
      </div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button class="btn-primary" id="saveEventBtn">Save Event</button>
        <button class="btn-outline" id="cancelEventBtn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- ── Event Forms Hub Modal ─────────────────────────────────────────────── -->
  <div id="eventFormsModal" class="panel-modal wide-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeEventFormsModal"><i class="bi bi-x-lg"></i></button>
      <h3>Event Forms — <span id="eventFormsTitle"></span></h3>
      <p class="section-hint">Create volunteer sign-up and performer application forms for this event. By default no
        forms exist.</p>
      <div id="eventFormsMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px" id="eventFormsGrid">
        <p class="loading-text" style="grid-column:1/-1">Loading…</p>
      </div>
      <button class="btn-outline" id="closeEventFormsBtn" style="margin-top:16px">Close</button>
    </div>
  </div>

  <!-- ── Form Builder Modal ────────────────────────────────────────────────── -->
  <div id="formBuilderModal" class="panel-modal wide-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeFormBuilderModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="formBuilderTitle">Edit Form</h3>
      <div id="formBuilderMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>
      <input type="hidden" id="fbEventId" />
      <input type="hidden" id="fbFormType" />
      <input type="hidden" id="fbFormId" />

      <div class="form-group"><label>Form Title</label><input type="text" id="fbTitle"
          placeholder="e.g. Volunteer Sign-Up" /></div>
      <div class="form-group"><label>Description <small>(shown to user)</small></label><textarea id="fbDesc" rows="2"
          placeholder="Brief intro about this form…"></textarea></div>
      <div class="form-row">
        <div class="form-group">
          <label>Status</label>
          <select id="fbActive">
            <option value="1">✅ Active (accepting submissions)</option>
            <option value="0">🚫 Inactive</option>
          </select>
        </div>
        <div class="form-group"><label>Deadline <small>(leave blank = no deadline)</small></label><input
            type="datetime-local" id="fbDeadline" /></div>
      </div>
      <div class="form-group"><label>Max Submissions <small>(0 = unlimited)</small></label><input type="number"
          id="fbMax" value="0" min="0" style="width:120px" /></div>
      <button class="btn-primary btn-sm" id="saveFormSettingsBtn" style="margin-bottom:20px"><i
          class="bi bi-floppy"></i> Save Form Settings</button>

      <div style="border-top:1px solid var(--border);padding-top:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <h4 style="margin:0">Questions</h4>
          <button class="btn-primary btn-sm" id="addQuestionBtn"><i class="bi bi-plus-lg"></i> Add Question</button>
        </div>
        <div id="questionsList">
          <p class="empty-state">No questions yet — this form just collects a name/sign-up.</p>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:16px">
        <button class="btn-outline" id="backToFormsBtn"><i class="bi bi-arrow-left"></i> Back to Forms</button>
        <button class="btn-outline btn-sm" id="viewSubmissionsBtn"><i class="bi bi-list-ul"></i> View
          Submissions</button>
      </div>
    </div>
  </div>

  <!-- ── Question Editor Modal ────────────────────────────────────────────── -->
  <div id="questionModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeQuestionModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="questionModalTitle">Add Question</h3>
      <div id="questionMsg" class="panel-alert" style="display:none"></div>
      <input type="hidden" id="qId" />
      <div class="form-group"><label>Question <span class="required">*</span></label><input type="text" id="qText"
          placeholder="e.g. What experience do you have?" /></div>
      <div class="form-row">
        <div class="form-group">
          <label>Input Type</label>
          <select id="qType" onchange="toggleQTypeFields()">
            <option value="text">Short Text</option>
            <option value="textarea">Long Text</option>
            <option value="radio">Multiple Choice (pick one)</option>
            <option value="select">Dropdown</option>
            <option value="checkbox">Checkboxes (pick many)</option>
          </select>
        </div>
        <div class="form-group" style="flex:0 0 auto;width:140px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" id="qRequired" checked style="width:14px;height:14px" /> Required
          </label>
        </div>
      </div>
      <div id="qLimitsRow" class="form-row">
        <div class="form-group"><label>Word Limit <small>(0 = none)</small></label><input type="number" id="qWordLimit"
            value="0" min="0" style="width:100px" /></div>
        <div class="form-group"><label>Char Limit <small>(0 = none)</small></label><input type="number" id="qCharLimit"
            value="0" min="0" style="width:100px" /></div>
      </div>
      <div id="qOptionsGroup" class="form-group" style="display:none">
        <label>Options <small>(one per line)</small></label>
        <textarea id="qOptions" rows="5" placeholder="Option A&#10;Option B&#10;Option C"></textarea>
      </div>
      <div class="form-group"><label>Display Order</label><input type="number" id="qOrder" value="0" min="0"
          style="width:100px" /></div>
      <div class="modal-actions">
        <button class="btn-outline" id="cancelQuestionBtn">Cancel</button>
        <button class="btn-primary" id="saveQuestionBtn">Save Question</button>
      </div>
    </div>
  </div>

  <!-- ── Submissions Viewer Modal ──────────────────────────────────────────── -->
  <div id="submissionsModal" class="panel-modal wide-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeSubmissionsModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="submissionsModalTitle">Submissions</h3>
      <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;align-items:center">
        <span id="submissionCount" class="photo-count-badge"></span>
        <button class="btn-outline btn-sm" id="backToBuilderBtn"><i class="bi bi-arrow-left"></i> Back to Form</button>
      </div>
      <div id="submissionsTable">
        <p class="loading-text">Loading…</p>
      </div>
    </div>
  </div>

  <!-- Post Modal -->
  <div id="postModal" class="panel-modal wide-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closePostModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="postModalTitle">New Post</h3>
      <div id="postSaveMsg" class="panel-alert" style="display:none"></div>
      <div class="form-group"><label>Title <span class="required">*</span></label><input type="text" id="postTitle"
          placeholder="Post title…" /></div>
      <div class="form-row">
        <div class="form-group"><label>Type</label><select id="postType">
            <option value="announcement">📢 Announcement</option>
            <option value="news">📰 News</option>
            <option value="event">🎉 Event Update</option>
          </select></div>
        <div class="form-group"><label>Status</label><select id="postPublished">
            <option value="1">✅ Published</option>
            <option value="0">📋 Draft</option>
          </select></div>
      </div>
      <div class="form-group"><label>Content</label>
        <div id="postContentEditor" style="height:220px;background:#fff"></div>
      </div>
      <div class="form-group">
        <label>Featured Image</label>
        <div class="image-uploader">
          <div class="upload-zone" id="postUploadZone"><i class="bi bi-cloud-upload"></i>
            <p>Drag &amp; drop or click</p><small>JPEG, PNG, WebP · max 8 MB</small>
          </div>
          <input type="file" id="postImageFile" accept="image/*" style="display:none" />
          <div class="image-preview" id="postImagePreview" style="display:none"><img id="postPreviewImg" src=""
              alt="" /><button class="remove-image" id="removePostImage"><i class="bi bi-x-circle-fill"></i></button>
          </div>
          <input type="hidden" id="postImagePath" value="" />
        </div>
      </div>
      <div style="display:flex;gap:12px"><button class="btn-primary" id="savePostBtn">Save Post</button><button
          class="btn-outline" id="cancelPostBtn">Cancel</button></div>
    </div>
  </div>

  <!-- Committee Member Modal -->
  <div id="memberModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeMemberModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="memberModalTitle">Add Committee Member</h3>
      <div id="memberSaveMsg" class="panel-alert" style="display:none"></div>
      <form id="memberForm" onsubmit="return false">
        <div class="form-row">
          <div class="form-group">
            <label>Name (English) <span class="required">*</span></label>
            <input type="text" id="memberNameEn" placeholder="e.g. Sangeetha" required />
          </div>
          <div class="form-group">
            <label>Name (Tamil)</label>
            <input type="text" id="memberNameTa" placeholder="e.g. சங்கீதா" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Role (English)</label>
            <input type="text" id="memberRoleEn" placeholder="e.g. President" />
          </div>
          <div class="form-group">
            <label>Role (Tamil)</label>
            <input type="text" id="memberRoleTa" placeholder="e.g. தலைவர்" />
          </div>
        </div>
        <div class="form-group">
          <label>Display Order</label>
          <input type="number" id="memberOrder" value="0" min="0" />
        </div>
        <div class="form-group">
          <label>Photo</label>
          <input type="file" id="memberPhotoInput" accept="image/*" />
          <img id="memberPhotoPreview" src="" alt="Preview"
            style="display:none;margin-top:8px;width:80px;height:80px;object-fit:cover;border-radius:50%;border:3px solid var(--gold)" />
          <input type="hidden" id="memberPhotoPath" />
        </div>
      </form>
      <div class="modal-actions">
        <button class="btn-outline" id="cancelMemberBtn">Cancel</button>
        <button class="btn-primary" id="saveMemberBtn">Save Member</button>
      </div>
    </div>
  </div>

  <!-- Pricing Tier Modal -->
  <div id="tierModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeTierModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="tierModalTitle">Add Pricing Tier</h3>
      <div id="tierSaveMsg" class="panel-alert" style="display:none"></div>
      <div class="form-row">
        <div class="form-group">
          <label>Tier Name <span class="required">*</span></label>
          <input type="text" id="tierName" placeholder="e.g. Family" />
        </div>
        <div class="form-group">
          <label>Icon (Bootstrap Icons class)</label>
          <input type="text" id="tierIcon" placeholder="bi-house-heart" />
          <small style="color:var(--text-muted)">Browse at <a href="https://icons.getbootstrap.com"
              target="_blank">icons.getbootstrap.com</a></small>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price</label>
          <input type="number" id="tierPrice" min="0" step="0.01" placeholder="45" />
        </div>
        <div class="form-group">
          <label>Currency Symbol</label>
          <input type="text" id="tierCurrency" value="$" maxlength="3" />
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <input type="text" id="tierDescription" placeholder="Best value for families of all sizes" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Display Order</label>
          <input type="number" id="tierOrder" value="0" min="0" />
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:2px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500">
            <input type="checkbox" id="tierFeatured" style="width:16px;height:16px" /> Mark as "Most Popular"
          </label>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn-outline" id="cancelTierBtn">Cancel</button>
        <button class="btn-primary" id="saveTierBtn">Save Tier</button>
      </div>
    </div>
  </div>

  <!-- Benefit Panel Modal -->
  <div id="benefitModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeBenefitModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="benefitModalTitle">Add Benefit Panel</h3>
      <div id="benefitSaveMsg" class="panel-alert" style="display:none"></div>
      <div class="form-row" style="align-items:flex-end">
        <div class="form-group" style="flex:0 0 auto;width:60px">
          <label>Preview</label>
          <div
            style="width:44px;height:44px;background:rgba(107,15,26,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--maroon)">
            <i id="benefitIconPreview" class="bi bi-star"></i>
          </div>
        </div>
        <div class="form-group" style="flex:1">
          <label>Icon (Bootstrap Icons class) <span class="required">*</span></label>
          <input type="text" id="benefitIcon" placeholder="bi-ticket-perforated"
            oninput="document.getElementById('benefitIconPreview').className='bi '+this.value.trim()" />
          <small style="color:var(--text-muted)">Browse at <a href="https://icons.getbootstrap.com"
              target="_blank">icons.getbootstrap.com</a></small>
        </div>
      </div>
      <div class="form-group">
        <label>Title <span class="required">*</span></label>
        <input type="text" id="benefitTitle" placeholder="e.g. Reduced Entry Fees" />
      </div>
      <div class="form-group">
        <label>Content <small>(HTML supported)</small></label>
        <textarea id="benefitContent" rows="6" placeholder="<p>Description of this benefit…</p>"
          style="font-family:monospace;font-size:.85rem"></textarea>
      </div>
      <div class="form-group">
        <label>Display Order</label>
        <input type="number" id="benefitOrder" value="0" min="0" />
      </div>
      <div class="modal-actions">
        <button class="btn-outline" id="cancelBenefitBtn">Cancel</button>
        <button class="btn-primary" id="saveBenefitBtn">Save Panel</button>
      </div>
    </div>
  </div>

  <!-- Vision Stat Modal -->
  <div id="statModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeStatModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="statModalTitle">Add Stat</h3>
      <div id="statSaveMsg" class="panel-alert" style="display:none"></div>
      <div class="form-row">
        <div class="form-group">
          <label>Number / Text <span class="required">*</span></label>
          <input type="text" id="statNumber" placeholder="e.g. 500+ or 2015" />
        </div>
        <div class="form-group">
          <label>Label <span class="required">*</span></label>
          <input type="text" id="statLabel" placeholder="e.g. Community Members" />
        </div>
      </div>
      <div class="form-group">
        <label>Display Order</label>
        <input type="number" id="statOrder" value="0" min="0" />
      </div>
      <div class="modal-actions">
        <button class="btn-outline" id="cancelStatBtn">Cancel</button>
        <button class="btn-primary" id="saveStatBtn">Save Stat</button>
      </div>
    </div>
  </div>

  <!-- Vision Core Value Modal -->
  <div id="valueModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeValueModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="valueModalTitle">Add Core Value</h3>
      <div id="valueSaveMsg" class="panel-alert" style="display:none"></div>
      <div class="form-group">
        <label>Title <span class="required">*</span></label>
        <input type="text" id="valueTitle" placeholder="e.g. Unity" />
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea id="valueDescription" rows="3" placeholder="Short description of this value…"></textarea>
      </div>
      <div class="form-group">
        <label>Display Order</label>
        <input type="number" id="valueOrder" value="0" min="0" />
      </div>
      <div class="modal-actions">
        <button class="btn-outline" id="cancelValueBtn">Cancel</button>
        <button class="btn-primary" id="saveValueBtn">Save Value</button>
      </div>
    </div>
  </div>

  <!-- Activate Membership Modal -->
  <div id="activateMemModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeActivateMemModal"><i class="bi bi-x-lg"></i></button>
      <h3 id="activateMemTitle">Activate Membership</h3>
      <div id="activateMemMsg" class="panel-alert" style="display:none"></div>
      <input type="hidden" id="activateMemUserId" />
      <p id="activateMemUserName" style="color:#6b7280;margin-bottom:16px"></p>
      <div class="form-row">
        <div class="form-group">
          <label>Plan</label>
          <select id="activateMemTierId">
            <option value="">Select tier…</option>
          </select>
        </div>
        <div class="form-group">
          <label>Price Paid ($)</label>
          <input type="number" id="activateMemPrice" min="0" step="0.01" placeholder="20.00" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" id="activateMemStart" />
        </div>
        <div class="form-group">
          <label>Expiry Date</label>
          <input type="date" id="activateMemExpiry" />
        </div>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:10px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;margin:0">
          <input type="checkbox" id="activateMemRecurring" style="width:16px;height:16px" /> Auto-Renew / Recurring
        </label>
      </div>
      <div class="form-group">
        <label>Notes <small>(internal)</small></label>
        <input type="text" id="activateMemNotes" placeholder="e.g. Paid via Eventbrite order #123" />
      </div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button class="btn-primary" id="confirmActivateMemBtn">Activate Membership</button>
        <button class="btn-outline" id="cancelActivateMemBtn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- User Edit Modal -->
  <div id="userModal" class="panel-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closeUserModal"><i class="bi bi-x-lg"></i></button>
      <h3>Edit User</h3>
      <div id="userSaveMsg" class="panel-alert" style="display:none"></div>
      <input type="hidden" id="editUserId" />
      <div class="form-row">
        <div class="form-group"><label>First Name</label><input type="text" id="uFirstName" /></div>
        <div class="form-group"><label>Last Name</label><input type="text" id="uLastName" /></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" id="uEmail" /></div>
      <div class="form-group"><label>Phone</label><input type="tel" id="uPhone" /></div>
      <div class="form-group">
        <label>Roles <small style="color:#6b7280;font-weight:400">(select all that apply — highest privilege becomes
            primary)</small></label>
        <div class="role-checkbox-grid" id="uRolesGrid">
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="non_member" /> Non-Member</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="member" /> Member</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="social_media" /> Social Media
            Coordinator</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="membership_coordinator" />
            Membership Coordinator</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="cultural_coordinator" /> Cultural
            Coordinator</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="sports_coordinator" /> Sports
            Coordinator</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="coordinator" /> Coordinator
            (General)</label>
          <label class="role-check-label"><input type="checkbox" name="uRoles" value="admin" /> Admin</label>
        </div>
      </div>
      <div class="form-group"><label>Membership Expiry</label><input type="date" id="uExpiry" /></div>
      <div class="form-group"><label>New Password <small>(blank = no change)</small></label><input type="password"
          id="uPassword" placeholder="Leave blank to keep current" /></div>
      <div style="display:flex;gap:12px"><button class="btn-primary" id="saveUserBtn">Save User</button><button
          class="btn-outline" id="cancelUserBtn">Cancel</button></div>
    </div>
  </div>

  <!-- Photo Management Modal -->
  <div id="photoModal" class="panel-modal wide-modal">
    <div class="panel-modal-inner">
      <button class="modal-close" id="closePhotoModal"><i class="bi bi-x-lg"></i></button>
      <h3>Manage Photos — <span id="photoModalEventTitle"></span></h3>
      <div
        style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
        <span id="photoCountBadge" class="photo-count-badge"><i class="bi bi-images"></i> 0 / 200 photos</span>
        <div id="photoSaveMsg" class="panel-alert" style="display:none;margin:0"></div>
      </div>

      <!-- Upload Zone -->
      <div class="multi-upload-zone" id="photoUploadZone">
        <i class="bi bi-cloud-upload"></i>
        <p>Drag &amp; drop photos or click to select</p>
        <small>JPEG, PNG, WebP &middot; max 10 MB each (auto-compressed) &middot; up to 200 total</small>
      </div>
      <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" multiple
        style="display:none" />
      <div class="upload-progress-bar" id="photoProgressBar" style="display:none">
        <div class="upload-progress-fill" id="photoProgressFill" style="width:0%"></div>
      </div>
      <ul class="upload-queue" id="uploadQueue"></ul>

      <!-- Existing photos grid -->
      <div class="photo-manage-grid" id="photoManageGrid">
        <p class="loading-text" style="grid-column:1/-1">Loading photos…</p>
      </div>

      <!-- Videos & Links section -->
      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border)" />
      <h4 style="margin:0 0 14px;font-size:1rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <i class="bi bi-play-btn"></i> Videos &amp; Links
      </h4>
      <div id="mediaMsg" class="panel-alert" style="display:none;margin-bottom:10px"></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">
        <div style="flex:1;min-width:220px">
          <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">URL
            <small>(YouTube or any link)</small></label>
          <input type="url" id="newMediaUrl" placeholder="https://youtube.com/watch?v=… or any URL"
            style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;font-family:inherit" />
        </div>
        <div style="flex:0 0 180px">
          <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Label
            <small>(optional)</small></label>
          <input type="text" id="newMediaLabel" placeholder="e.g. Highlights Video"
            style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;font-family:inherit" />
        </div>
        <button class="btn-primary btn-sm" onclick="addEventMedia()" style="white-space:nowrap;padding:9px 16px"><i
            class="bi bi-plus-lg"></i> Add</button>
      </div>
      <div id="mediaList" style="display:flex;flex-direction:column;gap:10px">
        <p class="loading-text">Loading…</p>
      </div>

      <!-- Footer -->
      <div
        style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
        <button class="btn-secondary" id="closePhotoModalBtn">Close</button>
        <button class="btn-primary" id="savePhotoCaptionsBtn"><i class="bi bi-check-lg"></i> Save Changes</button>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="modalBackdrop"></div>

  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script>
  const API = 'api.php';
  let postQuill = null,
    evQuill = null;
  const siteEditors = {};
  let editingPostId = null,
    editingEventId = null,
    editingUserId = null;
  let allUsers = [],
    allEvents = [],
    allPosts = [],
    allTickets = [];

  async function api(action, data = null) {
    const opts = data ? {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    } : {
      method: 'GET'
    };
    const r = await fetch(`${API}?action=${action}`, opts);
    return r.json();
  }

  // ── Tab switching ─────────────────────────────────────────────────────────
  function switchTab(tab) {
    document.querySelectorAll('.sidebar-link[data-tab]').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelector(`.sidebar-link[data-tab="${tab}"]`)?.classList.add('active');
    document.getElementById('tab-' + tab)?.classList.add('active');
    const loaders = {
      events: loadEvents,
      posts: loadPosts,
      committee: loadCommitteeMembers,
      pricing: loadPricingTiers,
      vision: loadVision,
      slideshow: loadSlideshowPhotos,
      memberships: () => {
        loadMemberships();
        loadZeffySettings();
      },
      users: loadUsers,
      tickets: loadTickets,
      sitecontent: loadSiteContent
    };
    loaders[tab]?.();
  }
  document.querySelectorAll('.sidebar-link[data-tab]').forEach(l => {
    l.addEventListener('click', e => {
      e.preventDefault();
      switchTab(l.dataset.tab);
    });
  });

  // ── Overview ──────────────────────────────────────────────────────────────
  async function loadOverview() {
    const [ud, ed, pd, td] = await Promise.all([
      api('get_users'), api('get_events&all=1'), api('get_posts&all=1'), api('get_all_tickets')
    ]);
    allUsers = ud.users || [];
    allEvents = ed.events || [];
    allPosts = pd.posts || [];
    allTickets = td.tickets || [];
    document.getElementById('stat-users').textContent = allUsers.length;
    document.getElementById('stat-events').textContent = allEvents.length;
    document.getElementById('stat-posts').textContent = allPosts.length;
    document.getElementById('stat-tickets').textContent = allTickets.length;

    document.getElementById('overviewUsers').innerHTML = allUsers.slice(0, 5).map(u => `
    <div class="mini-row"><span class="user-dot">${esc(u.first_name[0]?.toUpperCase())}</span>
    <span>${esc(u.first_name+' '+u.last_name)}</span>
    <span class="role-badge role-${u.role}" style="margin-left:auto">${u.role}</span></div>`).join('') ||
      '<p class="empty-state">No users.</p>';

    document.getElementById('overviewTickets').innerHTML = allTickets.slice(0, 5).map(t => `
    <div class="mini-row"><i class="bi bi-ticket-perforated"></i>
    <span>${esc(t.member_name)} — ${esc(t.event_title)}</span>
    <span class="status-badge status-${t.status}" style="margin-left:auto">${t.status}</span></div>`).join('') ||
      '<p class="empty-state">No tickets.</p>';
  }

  // ── Events ────────────────────────────────────────────────────────────────
  async function loadEvents() {
    const d = await api('get_events&all=1');
    allEvents = d.events || [];
    renderEventsTable();
  }

  function renderEventsTable() {
    const el = document.getElementById('eventsTable');
    if (!allEvents.length) {
      el.innerHTML = '<p class="empty-state">No events yet.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Prices</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>${allEvents.map(ev => `<tr>
      <td><strong>${esc(ev.title)}</strong>${ev.title_tamil?`<br><small class="text-muted">${esc(ev.title_tamil)}</small>`:''}</td>
      <td>${ev.event_date||'TBD'}${ev.event_time?'<br><small>'+esc(ev.event_time)+'</small>':''}</td>
      <td>${esc(ev.location||'—')}</td>
      <td><small>Member: $${parseFloat(ev.member_price||0).toFixed(2)}<br>Regular: $${parseFloat(ev.regular_price||0).toFixed(2)}</small></td>
      <td><span class="badge badge-${ev.is_upcoming?'upcoming':'past'}">${ev.is_upcoming?'Upcoming':'Past'}</span></td>
      <td><span class="status-badge status-${ev.is_published?'confirmed':'draft'}">${ev.is_published?'Published':'Hidden'}</span></td>
      <td class="action-btns">
        <button class="btn-sm btn-outline" onclick="editEvent(${ev.id})"><i class="bi bi-pencil"></i></button>
        <button class="btn-sm btn-outline" onclick="openEventForms(${ev.id},'${esc(ev.title).replace(/'/g,"\\'")}' )" title="Manage Volunteer/Performer Forms"><i class="bi bi-person-lines-fill"></i> Forms</button>
        ${!ev.is_upcoming ? `<button class="btn-sm btn-outline" onclick="openPhotoModal(${ev.id})"><i class="bi bi-images"></i> Photos</button>` : ''}
        <button class="btn-sm btn-danger" onclick="deleteEvent(${ev.id})"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`).join('')}</tbody></table>`;
  }

  function openNewEvent() {
    editingEventId = null;
    document.getElementById('eventModalTitle').textContent = 'New Event';
    ['evTitle', 'evTitleTamil', 'evDate', 'evTime', 'evLocation', 'evMemberPrice', 'evRegularPrice', 'evTicketUrl']
    .forEach(id => document.getElementById(id).value = '');
    document.getElementById('evPublished').value = '1';
    document.getElementById('evUpcoming').value = '1';
    document.getElementById('evImagePath').value = '';
    document.getElementById('evImagePreview').style.display = 'none';
    document.getElementById('evUploadZone').style.display = 'flex';
    document.getElementById('eventSaveMsg').style.display = 'none';
    if (evQuill) evQuill.setContents([]);
    openModal('eventModal');
  }

  function editEvent(id) {
    const ev = allEvents.find(e => e.id == id);
    if (!ev) return;
    editingEventId = id;
    document.getElementById('eventModalTitle').textContent = 'Edit Event';
    document.getElementById('evTitle').value = ev.title;
    document.getElementById('evTitleTamil').value = ev.title_tamil || '';
    document.getElementById('evDate').value = ev.event_date || '';
    document.getElementById('evTime').value = ev.event_time || '';
    document.getElementById('evLocation').value = ev.location || '';
    document.getElementById('evMemberPrice').value = ev.member_price || '';
    document.getElementById('evRegularPrice').value = ev.regular_price || '';
    document.getElementById('evTicketUrl').value = ev.ticket_url || '';
    document.getElementById('evPublished').value = ev.is_published;
    document.getElementById('evUpcoming').value = ev.is_upcoming;
    document.getElementById('evImagePath').value = ev.image_path || '';
    document.getElementById('eventSaveMsg').style.display = 'none';
    if (ev.image_path) {
      document.getElementById('evPreviewImg').src = ev.image_path;
      document.getElementById('evImagePreview').style.display = 'block';
      document.getElementById('evUploadZone').style.display = 'none';
    } else {
      document.getElementById('evImagePreview').style.display = 'none';
      document.getElementById('evUploadZone').style.display = 'flex';
    }
    if (evQuill) evQuill.clipboard.dangerouslyPasteHTML(ev.description || '');
    openModal('eventModal');
  }

  document.getElementById('saveEventBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveEventBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const data = {
      title: document.getElementById('evTitle').value,
      title_tamil: document.getElementById('evTitleTamil').value,
      event_date: document.getElementById('evDate').value,
      event_time: document.getElementById('evTime').value,
      location: document.getElementById('evLocation').value,
      member_price: document.getElementById('evMemberPrice').value,
      regular_price: document.getElementById('evRegularPrice').value,
      ticket_url: document.getElementById('evTicketUrl').value,
      is_published: parseInt(document.getElementById('evPublished').value),
      is_upcoming: parseInt(document.getElementById('evUpcoming').value),
      image_path: document.getElementById('evImagePath').value,
      description: evQuill ? evQuill.root.innerHTML : '',
    };
    if (editingEventId) data.id = editingEventId;
    const d = await api(editingEventId ? 'update_event' : 'create_event', data);
    const msg = document.getElementById('eventSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Event saved!';
      msg.style.display = 'block';
      allEvents = [];
      await loadEvents();
      loadOverview();
      setTimeout(closeModals, 1200);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Save failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Event';
  });

  async function deleteEvent(id) {
    if (!confirm('Delete this event? This will also remove associated tickets.')) return;
    const d = await api('delete_event', {
      id
    });
    if (d.success) {
      allEvents = [];
      loadEvents();
      loadOverview();
    }
  }

  // ── Event Forms ───────────────────────────────────────────────────────────
  let currentFormsEventId = null,
    currentFormType = null,
    currentFormId = null,
    editingQId = null;

  async function openEventForms(eventId, eventTitle) {
    currentFormsEventId = eventId;
    document.getElementById('eventFormsTitle').textContent = eventTitle;
    document.getElementById('eventFormsMsg').style.display = 'none';
    document.getElementById('eventFormsGrid').innerHTML =
      '<p class="loading-text" style="grid-column:1/-1">Loading…</p>';
    openModal('eventFormsModal');
    await refreshEventFormsGrid();
  }

  async function refreshEventFormsGrid() {
    const d = await api(`get_event_forms&event_id=${currentFormsEventId}`);
    const forms = d.forms || [];
    const byType = {};
    forms.forEach(f => byType[f.form_type] = f);
    const grid = document.getElementById('eventFormsGrid');
    grid.innerHTML = ['volunteer', 'performer'].map(type => {
      const f = byType[type];
      const icon = type === 'volunteer' ? 'bi-hand-raised-fill' : 'bi-music-note-beamed';
      const label = type === 'volunteer' ? 'Volunteer Sign-Up' : 'Performer Application';
      const color = type === 'volunteer' ? '#065f46' : '#92400e';
      const bg = type === 'volunteer' ? '#d1fae5' : '#fef3c7';
      if (f) {
        return `<div class="panel-card" style="margin:0">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <span style="background:${bg};color:${color};border-radius:8px;padding:6px 10px;font-size:1.2rem"><i class="bi ${icon}"></i></span>
          <div style="flex:1">
            <strong>${esc(f.title)}</strong>
            <div style="font-size:.8rem;margin-top:2px">
              <span class="status-badge status-${f.is_active?'confirmed':'draft'}">${f.is_active?'Active':'Inactive'}</span>
              <span style="color:#6b7280;margin-left:6px">${f.submission_count} submission${f.submission_count!=1?'s':''}</span>
            </div>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn-sm btn-outline" onclick="openFormBuilder(${currentFormsEventId},'${type}',${f.id})"><i class="bi bi-pencil"></i> Edit Form</button>
          <button class="btn-sm btn-outline" onclick="openSubmissions(${f.id}, '${esc(f.title)}')"><i class="bi bi-list-ul"></i> Submissions</button>
          <button class="btn-sm btn-danger" onclick="deleteEventFormConfirm(${f.id},'${type}')"><i class="bi bi-trash"></i></button>
        </div>
      </div>`;
      } else {
        return `<div class="panel-card" style="margin:0;border:2px dashed var(--border);background:#fafaf8">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <span style="background:${bg};color:${color};border-radius:8px;padding:6px 10px;font-size:1.2rem;opacity:.5"><i class="bi ${icon}"></i></span>
          <div><strong>${label}</strong><div style="font-size:.8rem;color:#9ca3af">No form created</div></div>
        </div>
        <button class="btn-sm btn-primary" onclick="openFormBuilder(${currentFormsEventId},'${type}',null)"><i class="bi bi-plus-lg"></i> Create Form</button>
      </div>`;
      }
    }).join('');
  }

  async function openFormBuilder(eventId, formType, formId) {
    currentFormType = formType;
    currentFormId = formId;
    const label = formType === 'volunteer' ? 'Volunteer Sign-Up' : 'Performer Application';
    document.getElementById('formBuilderTitle').textContent = (formId ? 'Edit' : 'Create') + ' — ' + label;
    document.getElementById('formBuilderMsg').style.display = 'none';
    document.getElementById('fbEventId').value = eventId;
    document.getElementById('fbFormType').value = formType;
    document.getElementById('fbFormId').value = formId || '';

    if (formId) {
      const d = await api(`get_event_form_detail&form_id=${formId}`);
      const f = d.form || {};
      document.getElementById('fbTitle').value = f.title || label;
      document.getElementById('fbDesc').value = f.description || '';
      document.getElementById('fbActive').value = f.is_active ?? 1;
      document.getElementById('fbDeadline').value = f.deadline ? f.deadline.replace(' ', 'T').slice(0, 16) : '';
      document.getElementById('fbMax').value = f.max_submissions || 0;
      renderQuestionsList(d.questions || []);
    } else {
      document.getElementById('fbTitle').value = label;
      document.getElementById('fbDesc').value = '';
      document.getElementById('fbActive').value = '1';
      document.getElementById('fbDeadline').value = '';
      document.getElementById('fbMax').value = '0';
      renderQuestionsList([]);
    }

    // Hide event forms modal, show builder
    document.getElementById('eventFormsModal').classList.remove('active');
    openModal('formBuilderModal');
  }

  function renderQuestionsList(questions) {
    const el = document.getElementById('questionsList');
    if (!questions.length) {
      el.innerHTML = '<p class="empty-state">No questions yet — this form just collects a name/sign-up.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr><th>#</th><th>Question</th><th>Type</th><th>Required</th><th>Limits</th><th>Actions</th></tr></thead>
  <tbody>${questions.map((q,i) => `<tr>
    <td>${q.display_order || i+1}</td>
    <td>${esc(q.question_text)}</td>
    <td><span style="font-size:.8rem;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:99px">${q.input_type}</span></td>
    <td>${q.is_required ? '✓' : '—'}</td>
    <td style="font-size:.8rem;color:#6b7280">${q.word_limit>0?q.word_limit+' words':''}${q.char_limit>0?(q.word_limit>0?', ':'')+q.char_limit+' chars':''||'—'}</td>
    <td class="action-btns">
      <button class="btn-sm btn-outline" onclick="openEditQuestion(${JSON.stringify(q).replace(/"/g,'&quot;')})"><i class="bi bi-pencil"></i></button>
      <button class="btn-sm btn-danger" onclick="deleteQuestion(${q.id})"><i class="bi bi-trash"></i></button>
    </td>
  </tr>`).join('')}</tbody></table>`;
  }

  document.getElementById('saveFormSettingsBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveFormSettingsBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
    const d = await api('save_event_form', {
      event_id: document.getElementById('fbEventId').value,
      form_type: document.getElementById('fbFormType').value,
      title: document.getElementById('fbTitle').value.trim(),
      description: document.getElementById('fbDesc').value.trim(),
      is_active: document.getElementById('fbActive').value,
      deadline: document.getElementById('fbDeadline').value || null,
      max_submissions: document.getElementById('fbMax').value,
    });
    const msg = document.getElementById('formBuilderMsg');
    if (d.success) {
      currentFormId = d.form_id;
      document.getElementById('fbFormId').value = d.form_id;
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      setTimeout(() => msg.style.display = 'none', 2000);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-floppy"></i> Save Form Settings';
  });

  document.getElementById('addQuestionBtn')?.addEventListener('click', async () => {
    // Auto-save form settings if the form hasn't been persisted yet
    if (!currentFormId) {
      const btn2 = document.getElementById('saveFormSettingsBtn');
      btn2.disabled = true;
      btn2.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
      const d = await api('save_event_form', {
        event_id: document.getElementById('fbEventId').value,
        form_type: document.getElementById('fbFormType').value,
        title: document.getElementById('fbTitle').value.trim(),
        description: document.getElementById('fbDesc').value.trim(),
        is_active: document.getElementById('fbActive').value,
        deadline: document.getElementById('fbDeadline').value || null,
        max_submissions: document.getElementById('fbMax').value,
      });
      btn2.disabled = false;
      btn2.innerHTML = '<i class="bi bi-floppy"></i> Save Form Settings';
      if (!d.success) {
        const msg = document.getElementById('formBuilderMsg');
        msg.className = 'panel-alert alert-error';
        msg.textContent = d.error || 'Failed to save form.';
        msg.style.display = 'block';
        return;
      }
      currentFormId = d.form_id;
      document.getElementById('fbFormId').value = d.form_id;
    }
    editingQId = null;
    document.getElementById('questionModalTitle').textContent = 'Add Question';
    document.getElementById('questionMsg').style.display = 'none';
    document.getElementById('qId').value = '';
    document.getElementById('qText').value = '';
    document.getElementById('qType').value = 'text';
    document.getElementById('qRequired').checked = true;
    document.getElementById('qWordLimit').value = '0';
    document.getElementById('qCharLimit').value = '0';
    document.getElementById('qOptions').value = '';
    document.getElementById('qOrder').value = '0';
    toggleQTypeFields();
    document.getElementById('formBuilderModal').classList.remove('active');
    openModal('questionModal');
  });

  function openEditQuestion(q) {
    editingQId = q.id;
    document.getElementById('questionModalTitle').textContent = 'Edit Question';
    document.getElementById('questionMsg').style.display = 'none';
    document.getElementById('qId').value = q.id;
    document.getElementById('qText').value = q.question_text;
    document.getElementById('qType').value = q.input_type;
    document.getElementById('qRequired').checked = !!+q.is_required;
    document.getElementById('qWordLimit').value = q.word_limit || 0;
    document.getElementById('qCharLimit').value = q.char_limit || 0;
    document.getElementById('qOptions').value = (q.options || []).join('\n');
    document.getElementById('qOrder').value = q.display_order || 0;
    toggleQTypeFields();
    document.getElementById('formBuilderModal').classList.remove('active');
    openModal('questionModal');
  }

  function toggleQTypeFields() {
    const t = document.getElementById('qType').value;
    const needsOptions = ['radio', 'select', 'checkbox'].includes(t);
    const needsLimits = ['text', 'textarea'].includes(t);
    document.getElementById('qOptionsGroup').style.display = needsOptions ? '' : 'none';
    document.getElementById('qLimitsRow').style.display = needsLimits ? '' : 'none';
  }

  document.getElementById('saveQuestionBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveQuestionBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const type = document.getElementById('qType').value;
    const needsOptions = ['radio', 'select', 'checkbox'].includes(type);
    const d = await api('save_form_question', {
      id: editingQId || 0,
      form_id: currentFormId,
      question_text: document.getElementById('qText').value.trim(),
      input_type: type,
      options: needsOptions ? document.getElementById('qOptions').value.split('\n').map(s => s.trim()).filter(
        Boolean) : [],
      word_limit: document.getElementById('qWordLimit').value,
      char_limit: document.getElementById('qCharLimit').value,
      is_required: document.getElementById('qRequired').checked ? 1 : 0,
      display_order: document.getElementById('qOrder').value,
    });
    const msg = document.getElementById('questionMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      // Refresh questions list
      const fd = await api(`get_event_form_detail&form_id=${currentFormId}`);
      renderQuestionsList(fd.questions || []);
      setTimeout(() => {
        msg.style.display = 'none';
        closeModals();
        openModal('formBuilderModal');
      }, 700);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Question';
  });

  async function deleteQuestion(id) {
    if (!confirm('Delete this question? Existing answers will also be removed.')) return;
    await api('delete_form_question', {
      id
    });
    const fd = await api(`get_event_form_detail&form_id=${currentFormId}`);
    renderQuestionsList(fd.questions || []);
  }

  document.getElementById('cancelQuestionBtn')?.addEventListener('click', () => {
    closeModals();
    openModal('formBuilderModal');
  });
  document.getElementById('closeQuestionModal')?.addEventListener('click', () => {
    closeModals();
    openModal('formBuilderModal');
  });

  document.getElementById('backToFormsBtn')?.addEventListener('click', () => {
    closeModals();
    openModal('eventFormsModal');
    refreshEventFormsGrid();
  });
  document.getElementById('closeEventFormsModal')?.addEventListener('click', closeModals);
  document.getElementById('closeEventFormsBtn')?.addEventListener('click', closeModals);
  document.getElementById('closeFormBuilderModal')?.addEventListener('click', closeModals);

  async function deleteEventFormConfirm(id, type) {
    if (!confirm(`Delete the ${type} form and ALL its submissions? This cannot be undone.`)) return;
    await api('delete_event_form', {
      id
    });
    await refreshEventFormsGrid();
  }

  async function openSubmissions(formId, formTitle) {
    document.getElementById('submissionsModalTitle').textContent = 'Submissions — ' + formTitle;
    document.getElementById('submissionsTable').innerHTML = '<p class="loading-text">Loading…</p>';
    document.getElementById('formBuilderModal').classList.remove('active');
    openModal('submissionsModal');
    const d = await api(`get_form_submissions&form_id=${formId}`);
    const subs = d.submissions || [];
    document.getElementById('submissionCount').innerHTML =
      `<i class="bi bi-people"></i> ${subs.length} submission${subs.length!=1?'s':''}`;
    if (!subs.length) {
      document.getElementById('submissionsTable').innerHTML = '<p class="empty-state">No submissions yet.</p>';
      return;
    }
    document.getElementById('submissionsTable').innerHTML = subs.map(s => `
    <div class="submission-card">
      <div class="submission-header">
        <div>
          <strong>${esc((s.first_name||'')+ ' ' + (s.last_name||''))}</strong>
          <span style="color:#6b7280;font-size:.85rem;margin-left:8px">${esc(s.email||s.guest_email||'')}</span>
          ${s.membership_number?`<span style="font-size:.8rem;background:#e0f2fe;color:#0369a1;padding:1px 6px;border-radius:99px;margin-left:6px">${esc(s.membership_number)}</span>`:''}
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span class="status-badge status-${s.status}">${s.status}</span>
          <span style="font-size:.78rem;color:#9ca3af">${s.submitted_at?.slice(0,16)||''}</span>
          <select onchange="updateSubStatus(${s.id},this.value)" style="font-size:.8rem;padding:3px 6px;border:1px solid var(--border);border-radius:6px">
            <option value="pending"${s.status==='pending'?' selected':''}>Pending</option>
            <option value="approved"${s.status==='approved'?' selected':''}>Approved</option>
            <option value="rejected"${s.status==='rejected'?' selected':''}>Rejected</option>
          </select>
        </div>
      </div>
      ${s.answers.length ? `<div class="submission-answers">${s.answers.map(a=>`
        <div class="submission-qa"><span class="submission-q">${esc(a.question_text)}</span><span class="submission-a">${esc(a.answer_text||'—')}</span></div>
      `).join('')}</div>` : ''}
      ${s.admin_notes?`<div style="font-size:.82rem;color:#6b7280;padding:6px 0;border-top:1px solid var(--border);margin-top:8px"><i class="bi bi-chat-left-text"></i> ${esc(s.admin_notes)}</div>`:''}
    </div>
  `).join('');
  }

  async function updateSubStatus(id, status) {
    await api('update_submission_status', {
      id,
      status
    });
  }

  document.getElementById('closeSubmissionsModal')?.addEventListener('click', closeModals);
  document.getElementById('backToBuilderBtn')?.addEventListener('click', () => {
    closeModals();
    openModal('formBuilderModal');
  });
  document.getElementById('viewSubmissionsBtn')?.addEventListener('click', async () => {
    if (!currentFormId) return;
    const d = await api(`get_event_form_detail&form_id=${currentFormId}`);
    await openSubmissions(currentFormId, d.form?.title || 'Form');
  });

  // ── Posts ─────────────────────────────────────────────────────────────────
  async function loadPosts() {
    const d = await api('get_posts&all=1');
    allPosts = d.posts || [];
    renderPostsTable();
  }

  function renderPostsTable() {
    const el = document.getElementById('postsTable');
    if (!allPosts.length) {
      el.innerHTML = '<p class="empty-state">No posts.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr><th>Title</th><th>Type</th><th>Author</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>${allPosts.map(p => `<tr>
      <td><strong>${esc(p.title)}</strong></td>
      <td><span class="badge badge-${p.post_type}">${p.post_type}</span></td>
      <td>${esc(p.author_name||'—')}</td>
      <td><span class="status-badge status-${p.is_published?'confirmed':'draft'}">${p.is_published?'Published':'Draft'}</span></td>
      <td>${p.created_at?.slice(0,10)||'—'}</td>
      <td class="action-btns">
        <button class="btn-sm btn-outline" onclick="editPost(${p.id})"><i class="bi bi-pencil"></i></button>
        <button class="btn-sm btn-danger" onclick="deletePost(${p.id})"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`).join('')}</tbody></table>`;
  }

  function openNewPost() {
    editingPostId = null;
    document.getElementById('postModalTitle').textContent = 'New Post';
    document.getElementById('postTitle').value = '';
    document.getElementById('postType').value = 'announcement';
    document.getElementById('postPublished').value = '1';
    document.getElementById('postImagePath').value = '';
    document.getElementById('postImagePreview').style.display = 'none';
    document.getElementById('postUploadZone').style.display = 'flex';
    document.getElementById('postSaveMsg').style.display = 'none';
    if (postQuill) postQuill.setContents([]);
    openModal('postModal');
  }

  function editPost(id) {
    const p = allPosts.find(x => x.id == id);
    if (!p) return;
    editingPostId = id;
    document.getElementById('postModalTitle').textContent = 'Edit Post';
    document.getElementById('postTitle').value = p.title;
    document.getElementById('postType').value = p.post_type;
    document.getElementById('postPublished').value = p.is_published;
    document.getElementById('postImagePath').value = p.image_path || '';
    document.getElementById('postSaveMsg').style.display = 'none';
    if (p.image_path) {
      document.getElementById('postPreviewImg').src = p.image_path;
      document.getElementById('postImagePreview').style.display = 'block';
      document.getElementById('postUploadZone').style.display = 'none';
    } else {
      document.getElementById('postImagePreview').style.display = 'none';
      document.getElementById('postUploadZone').style.display = 'flex';
    }
    if (postQuill) postQuill.clipboard.dangerouslyPasteHTML(p.content || '');
    openModal('postModal');
  }
  document.getElementById('savePostBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('savePostBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const data = {
      title: document.getElementById('postTitle').value,
      content: postQuill ? postQuill.root.innerHTML : '',
      image_path: document.getElementById('postImagePath').value,
      post_type: document.getElementById('postType').value,
      is_published: parseInt(document.getElementById('postPublished').value)
    };
    if (editingPostId) data.id = editingPostId;
    const d = await api(editingPostId ? 'update_post' : 'create_post', data);
    const msg = document.getElementById('postSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      allPosts = [];
      await loadPosts();
      loadOverview();
      setTimeout(closeModals, 1200);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Post';
  });
  async function deletePost(id) {
    if (!confirm('Delete this post?')) return;
    const d = await api('delete_post', {
      id
    });
    if (d.success) {
      allPosts = [];
      loadPosts();
      loadOverview();
    }
  }

  // ── Committee Members ─────────────────────────────────────────────────────
  let allMembers = [];
  let editingMemberId = null;

  async function loadCommitteeMembers() {
    const d = await api('get_committee_members');
    allMembers = d.members || [];
    renderCommitteeList();
  }

  function renderCommitteeList() {
    const el = document.getElementById('committeeMembersList');
    if (!allMembers.length) {
      el.innerHTML = '<p class="empty-state">No committee members yet. Click "Add Member" to get started.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr>
    <th style="width:56px">Photo</th><th>Name</th><th>Tamil Name</th><th>Role</th><th>Order</th><th>Actions</th>
  </tr></thead><tbody>${allMembers.map(m => `<tr>
    <td>${m.photo_path ? `<img src="${esc(m.photo_path)}" style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:2px solid var(--gold)" onerror="this.style.display='none'"/>` : '<span style="width:40px;height:40px;background:#f3f4f6;border-radius:50%;display:inline-flex;align-items:center;justify-content:center"><i class="bi bi-person" style="color:#9ca3af"></i></span>'}</td>
    <td><strong>${esc(m.name_english)}</strong></td>
    <td>${esc(m.name_tamil||'—')}</td>
    <td>${esc(m.role_english||'—')}</td>
    <td>${m.display_order}</td>
    <td class="action-btns">
      <button class="btn-sm btn-outline" onclick="openEditMember(${m.id})"><i class="bi bi-pencil"></i></button>
      <button class="btn-sm btn-danger" onclick="deleteMember(${m.id})"><i class="bi bi-trash"></i></button>
    </td>
  </tr>`).join('')}</tbody></table>`;
  }

  function openNewMember() {
    editingMemberId = null;
    document.getElementById('memberModalTitle').textContent = 'Add Committee Member';
    document.getElementById('memberSaveMsg').style.display = 'none';
    document.getElementById('memberForm').reset();
    document.getElementById('memberPhotoPreview').style.display = 'none';
    openModal('memberModal');
  }

  function openEditMember(id) {
    const m = allMembers.find(x => x.id == id);
    if (!m) return;
    editingMemberId = id;
    document.getElementById('memberModalTitle').textContent = 'Edit Committee Member';
    document.getElementById('memberSaveMsg').style.display = 'none';
    document.getElementById('memberNameEn').value = m.name_english || '';
    document.getElementById('memberNameTa').value = m.name_tamil || '';
    document.getElementById('memberRoleEn').value = m.role_english || '';
    document.getElementById('memberRoleTa').value = m.role_tamil || '';
    document.getElementById('memberOrder').value = m.display_order || 0;
    document.getElementById('memberPhotoPath').value = m.photo_path || '';
    const prev = document.getElementById('memberPhotoPreview');
    if (m.photo_path) {
      prev.src = m.photo_path;
      prev.style.display = 'block';
    } else {
      prev.style.display = 'none';
    }
    openModal('memberModal');
  }

  async function deleteMember(id) {
    if (!confirm('Delete this committee member?')) return;
    const d = await api('delete_committee_member', {
      id
    });
    if (d.success) {
      allMembers = [];
      await loadCommitteeMembers();
    }
  }

  document.getElementById('newMemberBtn')?.addEventListener('click', openNewMember);

  document.getElementById('memberPhotoInput')?.addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('photo', file);
    const r = await fetch('api.php?action=upload_committee_photo', {
      method: 'POST',
      body: fd
    });
    const d = await r.json();
    if (d.success) {
      document.getElementById('memberPhotoPath').value = d.path;
      const prev = document.getElementById('memberPhotoPreview');
      prev.src = d.path;
      prev.style.display = 'block';
    } else {
      alert(d.error || 'Upload failed');
    }
  });

  document.getElementById('saveMemberBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveMemberBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const data = {
      id: editingMemberId || 0,
      name_english: document.getElementById('memberNameEn').value.trim(),
      name_tamil: document.getElementById('memberNameTa').value.trim(),
      role_english: document.getElementById('memberRoleEn').value.trim(),
      role_tamil: document.getElementById('memberRoleTa').value.trim(),
      display_order: parseInt(document.getElementById('memberOrder').value) || 0,
      photo_path: document.getElementById('memberPhotoPath').value.trim(),
    };
    const d = await api('save_committee_member', data);
    const msg = document.getElementById('memberSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      allMembers = [];
      await loadCommitteeMembers();
      setTimeout(closeModals, 1000);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Member';
  });

  // ── Pricing Tiers ─────────────────────────────────────────────────────────
  let allTiers = [];
  let editingTierId = null;

  async function loadPricingTiers() {
    const [td, cd] = await Promise.all([api('get_membership_tiers'), api('get_site_content')]);
    allTiers = td.tiers || [];
    renderTiersList();
    const regUrl = cd.content?.membership_register_url?.content_html || '';
    document.getElementById('registerUrlInput').value = regUrl;
    loadBenefitPanels();
  }

  function renderTiersList() {
    const el = document.getElementById('pricingTiersList');
    if (!allTiers.length) {
      el.innerHTML = '<p class="empty-state">No pricing tiers. Click "Add Tier" to create one.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr>
    <th>Name</th><th>Icon</th><th>Price</th><th>Description</th><th>Featured</th><th>Order</th><th>Actions</th>
  </tr></thead><tbody>${allTiers.map(t => `<tr>
    <td><strong>${esc(t.name)}</strong></td>
    <td><i class="bi ${esc(t.icon)}"></i> <code style="font-size:.75rem">${esc(t.icon)}</code></td>
    <td><strong>${esc(t.currency)}${parseFloat(t.price).toFixed(2)}</strong><span style="color:var(--text-muted);font-size:.8rem">/yr</span></td>
    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(t.description)}</td>
    <td>${t.is_featured ? '<span class="role-badge role-admin">★ Featured</span>' : '—'}</td>
    <td>${t.display_order}</td>
    <td class="action-btns">
      <button class="btn-sm btn-outline" onclick="openEditTier(${t.id})"><i class="bi bi-pencil"></i></button>
      <button class="btn-sm btn-danger" onclick="deleteTier(${t.id})"><i class="bi bi-trash"></i></button>
    </td>
  </tr>`).join('')}</tbody></table>`;
  }

  function openNewTier() {
    editingTierId = null;
    document.getElementById('tierModalTitle').textContent = 'Add Pricing Tier';
    document.getElementById('tierSaveMsg').style.display = 'none';
    document.getElementById('tierName').value = '';
    document.getElementById('tierIcon').value = 'bi-person';
    document.getElementById('tierPrice').value = '';
    document.getElementById('tierCurrency').value = '$';
    document.getElementById('tierDescription').value = '';
    document.getElementById('tierOrder').value = allTiers.length + 1;
    document.getElementById('tierFeatured').checked = false;
    openModal('tierModal');
  }

  function openEditTier(id) {
    const t = allTiers.find(x => x.id == id);
    if (!t) return;
    editingTierId = id;
    document.getElementById('tierModalTitle').textContent = 'Edit Pricing Tier';
    document.getElementById('tierSaveMsg').style.display = 'none';
    document.getElementById('tierName').value = t.name;
    document.getElementById('tierIcon').value = t.icon;
    document.getElementById('tierPrice').value = t.price;
    document.getElementById('tierCurrency').value = t.currency;
    document.getElementById('tierDescription').value = t.description;
    document.getElementById('tierOrder').value = t.display_order;
    document.getElementById('tierFeatured').checked = !!t.is_featured;
    openModal('tierModal');
  }

  async function deleteTier(id) {
    if (!confirm('Delete this pricing tier?')) return;
    const d = await api('delete_membership_tier', {
      id
    });
    if (d.success) {
      allTiers = [];
      loadPricingTiers();
    }
  }

  document.getElementById('newTierBtn')?.addEventListener('click', openNewTier);
  document.getElementById('saveTierBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveTierBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const d = await api('save_membership_tier', {
      id: editingTierId || 0,
      name: document.getElementById('tierName').value.trim(),
      icon: document.getElementById('tierIcon').value.trim() || 'bi-person',
      price: parseFloat(document.getElementById('tierPrice').value) || 0,
      currency: document.getElementById('tierCurrency').value.trim() || '$',
      description: document.getElementById('tierDescription').value.trim(),
      display_order: parseInt(document.getElementById('tierOrder').value) || 0,
      is_featured: document.getElementById('tierFeatured').checked ? 1 : 0,
    });
    const msg = document.getElementById('tierSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      allTiers = [];
      loadPricingTiers();
      setTimeout(closeModals, 900);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Tier';
  });
  document.getElementById('saveRegisterUrlBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveRegisterUrlBtn');
    const url = document.getElementById('registerUrlInput').value.trim();
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const d = await api('update_site_content', {
      section_key: 'membership_register_url',
      content_html: url
    });
    const msg = document.getElementById('registerUrlMsg');
    msg.className = d.success ? 'panel-alert alert-success' : 'panel-alert alert-error';
    msg.textContent = d.success ? '✓ URL saved!' : (d.error || 'Failed.');
    msg.style.display = 'block';
    btn.disabled = false;
    btn.textContent = 'Save URL';
    setTimeout(() => msg.style.display = 'none', 3000);
  });

  // ── Benefit Panels ────────────────────────────────────────────────────────
  let allBenefits = [];
  let editingBenefitId = null;

  async function loadBenefitPanels() {
    const d = await api('get_benefit_panels');
    allBenefits = d.panels || [];
    renderBenefitsList();
  }

  function renderBenefitsList() {
    const el = document.getElementById('benefitPanelsList');
    if (!allBenefits.length) {
      el.innerHTML = '<p class="empty-state">No benefit panels yet. Click "Add Panel" to create one.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr>
    <th>Icon</th><th>Title</th><th>Content Preview</th><th>Order</th><th>Actions</th>
  </tr></thead><tbody>${allBenefits.map(b => `<tr>
    <td><i class="bi ${esc(b.icon)}" style="font-size:1.2rem;color:var(--maroon)"></i></td>
    <td><strong>${esc(b.title)}</strong></td>
    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);font-size:.85rem">${esc(b.content.replace(/<[^>]+>/g,''))}</td>
    <td>${b.display_order}</td>
    <td class="action-btns">
      <button class="btn-sm btn-outline" onclick="openEditBenefit(${b.id})"><i class="bi bi-pencil"></i></button>
      <button class="btn-sm btn-danger" onclick="deleteBenefit(${b.id})"><i class="bi bi-trash"></i></button>
    </td>
  </tr>`).join('')}</tbody></table>`;
  }

  function openNewBenefit() {
    editingBenefitId = null;
    document.getElementById('benefitModalTitle').textContent = 'Add Benefit Panel';
    document.getElementById('benefitSaveMsg').style.display = 'none';
    document.getElementById('benefitIcon').value = 'bi-star';
    document.getElementById('benefitIconPreview').className = 'bi bi-star';
    document.getElementById('benefitTitle').value = '';
    document.getElementById('benefitContent').value = '';
    document.getElementById('benefitOrder').value = allBenefits.length + 1;
    openModal('benefitModal');
  }

  function openEditBenefit(id) {
    const b = allBenefits.find(x => x.id == id);
    if (!b) return;
    editingBenefitId = id;
    document.getElementById('benefitModalTitle').textContent = 'Edit Benefit Panel';
    document.getElementById('benefitSaveMsg').style.display = 'none';
    document.getElementById('benefitIcon').value = b.icon;
    document.getElementById('benefitIconPreview').className = 'bi ' + b.icon;
    document.getElementById('benefitTitle').value = b.title;
    document.getElementById('benefitContent').value = b.content;
    document.getElementById('benefitOrder').value = b.display_order;
    openModal('benefitModal');
  }

  async function deleteBenefit(id) {
    if (!confirm('Delete this benefit panel?')) return;
    const d = await api('delete_benefit_panel', {
      id
    });
    if (d.success) {
      allBenefits = [];
      loadBenefitPanels();
    }
  }

  document.getElementById('newBenefitBtn')?.addEventListener('click', openNewBenefit);
  document.getElementById('cancelBenefitBtn')?.addEventListener('click', closeModals);
  document.getElementById('closeBenefitModal')?.addEventListener('click', closeModals);
  document.getElementById('saveBenefitBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveBenefitBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const d = await api('save_benefit_panel', {
      id: editingBenefitId || 0,
      icon: document.getElementById('benefitIcon').value.trim() || 'bi-star',
      title: document.getElementById('benefitTitle').value.trim(),
      content: document.getElementById('benefitContent').value,
      display_order: parseInt(document.getElementById('benefitOrder').value) || 0,
    });
    const msg = document.getElementById('benefitSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      allBenefits = [];
      loadBenefitPanels();
      setTimeout(closeModals, 900);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Panel';
  });

  // ── Vision Stats & Core Values ────────────────────────────────────────────
  let allVisionStats = [],
    allVisionValues = [];
  let editingStatId = null,
    editingValueId = null;

  async function loadVision() {
    const [ds, dv] = await Promise.all([api('get_vision_stats'), api('get_vision_core_values')]);
    allVisionStats = ds.stats || [];
    allVisionValues = dv.values || [];
    renderVisionStats();
    renderVisionValues();
  }

  function renderVisionStats() {
    const el = document.getElementById('visionStatsList');
    if (!allVisionStats.length) {
      el.innerHTML = '<p class="empty-state">No stats yet. Click "Add Stat" to create one.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr>
    <th>Number / Text</th><th>Label</th><th>Order</th><th>Actions</th>
  </tr></thead><tbody>${allVisionStats.map(s => `<tr>
    <td><strong>${esc(s.number_text)}</strong></td>
    <td>${esc(s.label)}</td>
    <td>${s.display_order}</td>
    <td class="action-btns">
      <button class="btn-sm btn-outline" onclick="openEditStat(${s.id})"><i class="bi bi-pencil"></i></button>
      <button class="btn-sm btn-danger"  onclick="deleteStat(${s.id})"><i class="bi bi-trash"></i></button>
    </td>
  </tr>`).join('')}</tbody></table>`;
  }

  function renderVisionValues() {
    const el = document.getElementById('visionValuesList');
    if (!allVisionValues.length) {
      el.innerHTML = '<p class="empty-state">No core values yet. Click "Add Value" to create one.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr>
    <th>Title</th><th>Description</th><th>Order</th><th>Actions</th>
  </tr></thead><tbody>${allVisionValues.map(v => `<tr>
    <td><strong>${esc(v.title)}</strong></td>
    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);font-size:.85rem">${esc(v.description)}</td>
    <td>${v.display_order}</td>
    <td class="action-btns">
      <button class="btn-sm btn-outline" onclick="openEditValue(${v.id})"><i class="bi bi-pencil"></i></button>
      <button class="btn-sm btn-danger"  onclick="deleteValue(${v.id})"><i class="bi bi-trash"></i></button>
    </td>
  </tr>`).join('')}</tbody></table>`;
  }

  function openNewStat() {
    editingStatId = null;
    document.getElementById('statModalTitle').textContent = 'Add Stat';
    document.getElementById('statSaveMsg').style.display = 'none';
    document.getElementById('statNumber').value = '';
    document.getElementById('statLabel').value = '';
    document.getElementById('statOrder').value = allVisionStats.length + 1;
    openModal('statModal');
  }

  function openEditStat(id) {
    const s = allVisionStats.find(x => x.id == id);
    if (!s) return;
    editingStatId = id;
    document.getElementById('statModalTitle').textContent = 'Edit Stat';
    document.getElementById('statSaveMsg').style.display = 'none';
    document.getElementById('statNumber').value = s.number_text;
    document.getElementById('statLabel').value = s.label;
    document.getElementById('statOrder').value = s.display_order;
    openModal('statModal');
  }
  async function deleteStat(id) {
    if (!confirm('Delete this stat?')) return;
    const d = await api('delete_vision_stat', {
      id
    });
    if (d.success) {
      allVisionStats = [];
      loadVision();
    }
  }

  document.getElementById('newStatBtn')?.addEventListener('click', openNewStat);
  document.getElementById('cancelStatBtn')?.addEventListener('click', closeModals);
  document.getElementById('closeStatModal')?.addEventListener('click', closeModals);
  document.getElementById('saveStatBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveStatBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const d = await api('save_vision_stat', {
      id: editingStatId || 0,
      number_text: document.getElementById('statNumber').value.trim(),
      label: document.getElementById('statLabel').value.trim(),
      display_order: parseInt(document.getElementById('statOrder').value) || 0,
    });
    const msg = document.getElementById('statSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      allVisionStats = [];
      loadVision();
      setTimeout(closeModals, 900);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Stat';
  });

  function openNewValue() {
    editingValueId = null;
    document.getElementById('valueModalTitle').textContent = 'Add Core Value';
    document.getElementById('valueSaveMsg').style.display = 'none';
    document.getElementById('valueTitle').value = '';
    document.getElementById('valueDescription').value = '';
    document.getElementById('valueOrder').value = allVisionValues.length + 1;
    openModal('valueModal');
  }

  function openEditValue(id) {
    const v = allVisionValues.find(x => x.id == id);
    if (!v) return;
    editingValueId = id;
    document.getElementById('valueModalTitle').textContent = 'Edit Core Value';
    document.getElementById('valueSaveMsg').style.display = 'none';
    document.getElementById('valueTitle').value = v.title;
    document.getElementById('valueDescription').value = v.description;
    document.getElementById('valueOrder').value = v.display_order;
    openModal('valueModal');
  }
  async function deleteValue(id) {
    if (!confirm('Delete this core value?')) return;
    const d = await api('delete_vision_core_value', {
      id
    });
    if (d.success) {
      allVisionValues = [];
      loadVision();
    }
  }

  document.getElementById('newValueBtn')?.addEventListener('click', openNewValue);
  document.getElementById('cancelValueBtn')?.addEventListener('click', closeModals);
  document.getElementById('closeValueModal')?.addEventListener('click', closeModals);
  document.getElementById('saveValueBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveValueBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const d = await api('save_vision_core_value', {
      id: editingValueId || 0,
      title: document.getElementById('valueTitle').value.trim(),
      description: document.getElementById('valueDescription').value.trim(),
      display_order: parseInt(document.getElementById('valueOrder').value) || 0,
    });
    const msg = document.getElementById('valueSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Saved!';
      msg.style.display = 'block';
      allVisionValues = [];
      loadVision();
      setTimeout(closeModals, 900);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save Value';
  });

  // ── Slideshow Photos ──────────────────────────────────────────────────────
  let allSlidePhotos = [];

  async function loadSlideshowPhotos() {
    const d = await api('get_slideshow_photos');
    allSlidePhotos = d.photos || [];
    renderSlideshowGrid();
  }

  function renderSlideshowGrid() {
    const el = document.getElementById('slideshowGrid');
    if (!allSlidePhotos.length) {
      el.innerHTML = '<p class="empty-state">No photos yet. Upload some to get started.</p>';
      return;
    }
    el.innerHTML = allSlidePhotos.map(p => `
    <div class="slideshow-thumb ${p.is_active ? '' : 'slide-inactive'}" data-id="${p.id}">
      <img src="assets/${esc(p.photo_path)}" alt="" onerror="this.src='assets/logo.webp'"/>
      <div class="slideshow-thumb-overlay">
        <button class="slide-btn-toggle" onclick="toggleSlidePhoto(${p.id})" title="${p.is_active ? 'Hide' : 'Show'}">
          <i class="bi bi-${p.is_active ? 'eye-slash' : 'eye'}"></i>
        </button>
        <button class="slide-btn-delete" onclick="deleteSlidePhoto(${p.id})" title="Delete">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <div class="slideshow-thumb-status">${p.is_active ? '<span class="slide-active-dot"></span> Shown' : '<span class="slide-inactive-dot"></span> Hidden'}</div>
    </div>`).join('');
  }

  async function toggleSlidePhoto(id) {
    await api('toggle_slideshow_photo', {
      id
    });
    loadSlideshowPhotos();
  }

  async function deleteSlidePhoto(id) {
    if (!confirm('Remove this photo from the slideshow?')) return;
    const d = await api('delete_slideshow_photo', {
      id
    });
    if (d.success) {
      allSlidePhotos = [];
      loadSlideshowPhotos();
    }
  }

  document.getElementById('slideshowUploadInput')?.addEventListener('change', async function() {
    const files = Array.from(this.files);
    if (!files.length) return;
    const msg = document.getElementById('slideshowMsg');
    msg.className = 'panel-alert';
    msg.textContent = `Uploading ${files.length} photo(s)…`;
    msg.style.display = 'block';
    let ok = 0,
      fail = 0;
    for (const file of files) {
      const fd = new FormData();
      fd.append('photo', file);
      const r = await fetch('api.php?action=upload_slideshow_photo', {
        method: 'POST',
        body: fd
      });
      const d = await r.json();
      if (d.success) ok++;
      else fail++;
    }
    msg.className = fail ? 'panel-alert alert-error' : 'panel-alert alert-success';
    msg.textContent = `✓ ${ok} uploaded${fail ? `, ${fail} failed` : ''}.`;
    this.value = '';
    loadSlideshowPhotos();
    setTimeout(() => msg.style.display = 'none', 4000);
  });

  // ── Site Content ──────────────────────────────────────────────────────────
  const CONTENT_SECTIONS = [{
      group: '🏠 Home Page'
    },
    {
      key: 'home_section_heading',
      label: 'Section Heading (Tamil)',
      icon: 'bi-type-h1'
    },
    {
      key: 'home_welcome_tamil',
      label: 'Welcome Text — Tamil',
      icon: 'bi-translate'
    },
    {
      key: 'home_welcome_english',
      label: 'Welcome Text — English',
      icon: 'bi-type'
    },
    {
      group: '👁 Vision & Values'
    },
    {
      key: 'vision_mission',
      label: 'Our Mission',
      icon: 'bi-bullseye'
    },
    {
      key: 'vision_purpose',
      label: 'Our Purpose & Activities',
      icon: 'bi-heart'
    },
    {
      key: 'vision_looking_forward',
      label: 'Looking Forward',
      icon: 'bi-compass'
    },
    {
      group: '🤝 Membership'
    },
    {
      key: 'membership_hero_subtitle',
      label: 'Hero Subtitle',
      icon: 'bi-type'
    },
    {
      key: 'membership_benefits_intro',
      label: 'Benefits Intro',
      icon: 'bi-list-ul'
    },
    {
      key: 'membership_benefit_events',
      label: 'Benefit — Events',
      icon: 'bi-ticket-perforated'
    },
    {
      key: 'membership_benefit_movies',
      label: 'Benefit — Movies',
      icon: 'bi-film'
    },
    {
      key: 'membership_benefit_voting',
      label: 'Benefit — Voting Rights',
      icon: 'bi-person-check'
    },
    {
      key: 'membership_note',
      label: 'Membership Note',
      icon: 'bi-info-circle'
    },
    {
      key: 'membership_pricing_intro',
      label: 'Pricing Intro',
      icon: 'bi-currency-dollar'
    },
    {
      key: 'membership_cta_note',
      label: 'Registration CTA Note',
      icon: 'bi-arrow-right-circle'
    },
    {
      group: '📞 Contact'
    },
    {
      key: 'contact_hero_subtitle',
      label: 'Hero Subtitle',
      icon: 'bi-type'
    },
    {
      key: 'contact_email_card',
      label: 'Email Card Text',
      icon: 'bi-envelope'
    },
    {
      key: 'contact_social_card',
      label: 'Social Media Card Text',
      icon: 'bi-chat-dots'
    },
    {
      group: '👥 Committee'
    },
    {
      key: 'committee_intro',
      label: 'Committee Page Intro',
      icon: 'bi-people'
    },
  ];
  async function loadContactMessages() {
    const list = document.getElementById('contactMessagesList');
    const d = await api('get_contact_messages');
    if (!d.success) {
      list.innerHTML = '<p class="empty-state">Could not load messages.</p>';
      return;
    }

    const badge = document.getElementById('contactMsgBadge');
    if (d.unread > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = d.unread + ' new';
    } else {
      badge.style.display = 'none';
    }

    if (!d.messages.length) {
      list.innerHTML = '<p class="empty-state" style="color:#6b7280;padding:8px 0">No messages yet.</p>';
      return;
    }
    list.innerHTML = d.messages.map(m => `
      <div class="contact-msg ${m.is_read == 0 ? 'unread' : ''}" data-id="${m.id}"
           style="border:1px solid #e5e7eb;border-left:4px solid ${m.is_read == 0 ? '#6b0f1a' : '#e5e7eb'};border-radius:8px;padding:14px 16px;margin-bottom:10px;background:${m.is_read == 0 ? '#fffaf5' : '#fff'}">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:baseline">
          <strong>${esc(m.first_name)} ${esc(m.last_name)}</strong>
          <span style="font-size:.8rem;color:#9ca3af">${esc((m.created_at||'').slice(0,16).replace('T',' '))}${m.emailed == 1 ? '' : ' · ✉️ not emailed'}</span>
        </div>
        <div style="font-size:.85rem;margin:2px 0 8px"><a href="mailto:${esc(m.email)}" style="color:#6b0f1a;font-weight:600">${esc(m.email)}</a></div>
        <div style="white-space:pre-wrap;color:#374151;font-size:.92rem">${esc(m.message)}</div>
        <div style="margin-top:10px;display:flex;gap:8px">
          ${m.is_read == 0 ? `<button class="btn-sm btn-outline msg-read-btn" data-id="${m.id}"><i class="bi bi-check2"></i> Mark read</button>` : ''}
          <a class="btn-sm btn-outline" href="mailto:${esc(m.email)}?subject=Re:%20Your%20message%20to%20Ottawa%20Tamil%20Sangam"><i class="bi bi-reply"></i> Reply</a>
          <button class="btn-sm btn-outline msg-del-btn" data-id="${m.id}" style="color:#b91c1c"><i class="bi bi-trash"></i> Delete</button>
        </div>
      </div>`).join('');

    list.querySelectorAll('.msg-read-btn').forEach(b => b.addEventListener('click', async () => {
      await api('mark_contact_message_read', {
        id: b.dataset.id
      });
      loadContactMessages();
    }));
    list.querySelectorAll('.msg-del-btn').forEach(b => b.addEventListener('click', async () => {
      if (!confirm('Delete this message?')) return;
      await api('delete_contact_message', {
        id: b.dataset.id
      });
      loadContactMessages();
    }));
  }
  document.getElementById('refreshContactMsgs')?.addEventListener('click', loadContactMessages);

  async function loadSiteContent() {
    // Load contact recipient email
    const cs = await api('get_contact_settings');
    if (cs.success) document.getElementById('contactRecipientEmail').value = cs.contact_recipient_email || '';
    loadContactMessages();

    const d = await api('get_site_content');
    const container = document.getElementById('siteContentSections');
    if (!Object.keys(siteEditors).length) {
      container.innerHTML = CONTENT_SECTIONS.map(s => {
        if (s.group) return `<div class="content-group-header"><span>${s.group}</span></div>`;
        return `<div class="content-section-editor">
        <div class="editor-section-label">
          <i class="bi ${s.icon}"></i> ${s.label}
          <button class="btn-sm btn-outline save-section-btn" data-key="${s.key}">Save</button>
        </div>
        <div class="quill-container" data-key="${s.key}"></div>
      </div>`;
      }).join('');
      document.querySelectorAll('#siteContentSections .quill-container').forEach(el => {
        const key = el.dataset.key;
        siteEditors[key] = new Quill(el, {
          theme: 'snow',
          modules: {
            toolbar: [
              ['bold', 'italic', 'underline'],
              ['link'],
              ['list', 'bullet'],
              ['clean']
            ]
          }
        });
      });
      document.querySelectorAll('#siteContentSections .save-section-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
          const key = btn.dataset.key;
          const quill = siteEditors[key];
          btn.disabled = true;
          btn.textContent = 'Saving…';
          const r = await api('update_site_content', {
            section_key: key,
            content_html: quill.root.innerHTML
          });
          const msg = document.getElementById('contentSaveMsg');
          msg.className = r.success ? 'panel-alert alert-success' : 'panel-alert alert-error';
          msg.textContent = r.success ? `✓ "${key}" saved!` : (r.error || 'Failed.');
          msg.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Save';
          setTimeout(() => msg.style.display = 'none', 3000);
        });
      });
    }
    if (d.success) {
      Object.entries(siteEditors).forEach(([key, quill]) => {
        if (d.content[key]) quill.clipboard.dangerouslyPasteHTML(d.content[key].content_html || '');
      });
    }
  }

  // ── Users ─────────────────────────────────────────────────────────────────
  async function loadUsers() {
    const d = await api('get_users');
    allUsers = d.users || [];
    renderUsersTable();
  }

  function renderUsersTable() {
    const search = (document.getElementById('userSearch')?.value || '').toLowerCase();
    const role = document.getElementById('roleFilter')?.value || '';
    let users = allUsers.filter(u => {
      const allRoles = [u.role].concat(safeParseRoles(u.extra_roles));
      return (!search || (u.first_name + ' ' + u.last_name + ' ' + u.email).toLowerCase().includes(search)) &&
        (!role || allRoles.includes(role));
    });
    const el = document.getElementById('usersTable');
    if (!users.length) {
      el.innerHTML = '<p class="empty-state">No users match.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Member #</th><th>Expiry</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>${users.map(u => `<tr>
      <td><strong>${esc(u.first_name+' '+u.last_name)}</strong></td>
      <td>${esc(u.email)}</td>
      <td>${[u.role].concat(safeParseRoles(u.extra_roles)).map(r=>`<span class="role-badge role-${r.replace(/_/g,'-')}" style="font-size:.75rem;margin-right:2px">${{non_member:'Non-Member',member:'Member',social_media:'Social Media',membership_coordinator:'Membership Coord.',cultural_coordinator:'Cultural Coord.',sports_coordinator:'Sports Coord.',coordinator:'Coordinator',admin:'Admin'}[r]||r}</span>`).join('')}</td>
      <td>${esc(u.membership_number||'—')}</td>
      <td>${u.membership_expiry||'—'}</td>
      <td>${u.created_at?.slice(0,10)||'—'}</td>
      <td class="action-btns">
        <button class="btn-sm btn-outline" onclick="editUser(${u.id})"><i class="bi bi-pencil"></i></button>
        <button class="btn-sm btn-danger" onclick="deleteUser(${u.id})"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`).join('')}</tbody></table><p class="table-footer">${users.length} user${users.length!=1?'s':''}</p>`;
  }
  document.getElementById('userSearch')?.addEventListener('input', renderUsersTable);
  document.getElementById('roleFilter')?.addEventListener('change', renderUsersTable);

  function editUser(id) {
    const u = allUsers.find(x => x.id == id);
    if (!u) return;
    editingUserId = id;
    document.getElementById('editUserId').value = id;
    document.getElementById('uFirstName').value = u.first_name;
    document.getElementById('uLastName').value = u.last_name;
    document.getElementById('uEmail').value = u.email;
    document.getElementById('uPhone').value = u.phone || '';
    document.getElementById('uExpiry').value = u.membership_expiry || '';
    document.getElementById('uPassword').value = '';
    document.getElementById('userSaveMsg').style.display = 'none';
    // Set role checkboxes: primary role + extra_roles
    const allRoles = [u.role].concat(safeParseRoles(u.extra_roles));
    document.querySelectorAll('#uRolesGrid input[name="uRoles"]').forEach(cb => {
      cb.checked = allRoles.includes(cb.value);
    });
    openModal('userModal');
  }
  document.getElementById('saveUserBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveUserBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const checkedRoles = [...document.querySelectorAll('#uRolesGrid input[name="uRoles"]:checked')].map(cb => cb
      .value);
    const roleHierarchy = ['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator',
      'membership_coordinator', 'social_media', 'member', 'non_member'
    ];
    checkedRoles.sort((a, b) => roleHierarchy.indexOf(a) - roleHierarchy.indexOf(b));
    const primaryRole = checkedRoles[0] || 'non_member';
    const extraRoles = checkedRoles.slice(1);
    const d = await api('update_user', {
      id: editingUserId,
      first_name: document.getElementById('uFirstName').value,
      last_name: document.getElementById('uLastName').value,
      email: document.getElementById('uEmail').value,
      phone: document.getElementById('uPhone').value,
      role: primaryRole,
      extra_roles: extraRoles,
      membership_expiry: document.getElementById('uExpiry').value,
      new_password: document.getElementById('uPassword').value,
    });
    const msg = document.getElementById('userSaveMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ User updated!';
      msg.style.display = 'block';
      allUsers = [];
      await loadUsers();
      loadOverview();
      setTimeout(closeModals, 1200);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Save User';
  });
  async function deleteUser(id) {
    if (!confirm('Delete this user? This cannot be undone.')) return;
    const d = await api('delete_user', {
      id
    });
    if (d.success) {
      allUsers = [];
      loadUsers();
      loadOverview();
    } else alert(d.error);
  }

  // ── Role Assignment Search ────────────────────────────────────────────────
  const ROLE_LABELS = {
    non_member: 'Non-Member',
    member: 'Member',
    social_media: 'Social Media Coordinator',
    membership_coordinator: 'Membership Coordinator',
    cultural_coordinator: 'Cultural Coordinator',
    sports_coordinator: 'Sports Coordinator',
    coordinator: 'Coordinator (General)',
    admin: 'Admin',
  };

  document.getElementById('roleSearchBtn')?.addEventListener('click', async () => {
    const email = document.getElementById('roleSearchEmail').value.trim();
    const el = document.getElementById('roleSearchResults');
    if (!email) {
      el.innerHTML = '<p style="color:#991b1b;font-size:.9rem">Enter an email to search.</p>';
      return;
    }
    el.innerHTML = '<p class="loading-text">Searching…</p>';
    const d = await api('search_user_by_email', {
      email
    });
    if (!d.success || !d.users.length) {
      el.innerHTML = '<p class="empty-state">No users found for that email.</p>';
      return;
    }
    el.innerHTML = d.users.map(u => {
      const allRoles = [u.role].concat(safeParseRoles(u.extra_roles));
      const roleChecks = Object.entries(ROLE_LABELS).map(([v, l]) =>
        `<label class="role-check-label" style="font-size:.82rem"><input type="checkbox" class="arCheck_${u.id}" value="${v}"${allRoles.includes(v)?' checked':''}/> ${l}</label>`
      ).join('');
      const currentBadges = allRoles.map(r =>
        `<span class="role-badge role-${r.replace(/_/g,'-')}" style="font-size:.75rem">${ROLE_LABELS[r]||r}</span>`
      ).join(' ');
      return `<div class="panel-card" style="margin-bottom:12px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <strong>${esc(u.first_name+' '+u.last_name)}</strong>
          <div style="font-size:.84rem;color:#6b7280">${esc(u.email)}</div>
          <div style="margin-top:6px">${currentBadges}</div>
        </div>
        <div>
          <div class="role-checkbox-grid" style="grid-template-columns:1fr 1fr">${roleChecks}</div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
            <button class="btn-sm btn-primary" onclick="doAssignRole(${u.id})">Save Roles</button>
            <span id="assignMsg_${u.id}" style="font-size:.8rem;display:none"></span>
          </div>
        </div>
      </div>
    </div>`;
    }).join('');
  });

  document.getElementById('roleSearchEmail')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('roleSearchBtn')?.click();
  });

  // Parse extra_roles JSON safely
  function safeParseRoles(raw) {
    try {
      const r = JSON.parse(raw || '[]');
      return Array.isArray(r) ? r : [];
    } catch {
      return [];
    }
  }

  async function doAssignRole(id) {
    const checked = [...document.querySelectorAll(`.arCheck_${id}:checked`)].map(cb => cb.value);
    const msg = document.getElementById(`assignMsg_${id}`);
    if (!checked.length) {
      msg.style.display = 'inline';
      msg.style.color = '#991b1b';
      msg.textContent = 'Select at least one role.';
      return;
    }
    const d = await api('assign_role', {
      id,
      roles: checked
    });
    msg.style.display = 'inline';
    if (d.success) {
      msg.style.color = '#166534';
      msg.textContent = '✓ Roles updated!';
      allUsers = [];
      loadUsers();
    } else {
      msg.style.color = '#991b1b';
      msg.textContent = d.error || 'Failed.';
    }
    setTimeout(() => msg.style.display = 'none', 3000);
  }

  // ── Tickets ───────────────────────────────────────────────────────────────
  async function loadTickets() {
    const d = await api('get_all_tickets');
    allTickets = d.tickets || [];
    renderTicketsTable();
  }

  function renderTicketsTable() {
    const filter = document.getElementById('ticketStatusFilter')?.value || '';
    let tix = filter ? allTickets.filter(t => t.status === filter) : allTickets;
    const el = document.getElementById('ticketsTable');
    if (!tix.length) {
      el.innerHTML = '<p class="empty-state">No tickets found.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table"><thead><tr><th>Member</th><th>Membership #</th><th>Event</th><th>Date</th><th>Type</th><th>Qty</th><th>Total</th><th>Status</th><th>Purchased</th><th>Actions</th></tr></thead>
    <tbody>${tix.map(t => `<tr>
      <td><strong>${esc(t.member_name)}</strong><br><small>${esc(t.member_email)}</small></td>
      <td>${esc(t.membership_number||'—')}</td>
      <td>${esc(t.event_title)}</td>
      <td>${t.event_date||'—'}</td>
      <td><span class="badge badge-${t.ticket_type}">${t.ticket_type}</span></td>
      <td>${t.quantity}</td>
      <td>$${parseFloat(t.total_price||0).toFixed(2)}</td>
      <td><span class="status-badge status-${t.status}">${t.status}</span></td>
      <td>${t.purchase_date?.slice(0,10)||'—'}</td>
      <td>
        ${t.status!=='confirmed'?`<button class="btn-sm btn-outline" onclick="updateTicketStatus(${t.id},'confirmed')">Confirm</button>`:''}
        ${t.status!=='cancelled'?`<button class="btn-sm btn-danger"  onclick="updateTicketStatus(${t.id},'cancelled')">Cancel</button>`:''}
      </td>
    </tr>`).join('')}</tbody></table><p class="table-footer">${tix.length} ticket${tix.length!=1?'s':''}</p>`;
  }
  document.getElementById('ticketStatusFilter')?.addEventListener('change', renderTicketsTable);
  async function updateTicketStatus(id, status) {
    const d = await api('update_ticket_status', {
      id,
      status
    });
    if (d.success) {
      allTickets = [];
      loadTickets();
    }
  }

  // ── Contact Email Setting ─────────────────────────────────────────────────
  document.getElementById('saveContactEmailBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveContactEmailBtn');
    const msg = document.getElementById('contactEmailMsg');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
    const d = await api('save_contact_settings', {
      contact_recipient_email: document.getElementById('contactRecipientEmail').value.trim(),
    });
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Contact email saved!';
      msg.style.display = 'block';
      setTimeout(() => msg.style.display = 'none', 3000);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-floppy"></i> Save';
  });

  // ── Zeffy ─────────────────────────────────────────────────────────────────
  async function loadZeffySettings() {
    const d = await api('zeffy_get_settings');
    if (!d.success) return;
    if (d.webhook_url) document.getElementById('zeffyWebhookUrl').value = d.webhook_url;
    if (d.form_slug) {
      document.getElementById('zeffyFormSlug').value = d.form_slug;
      document.getElementById('zeffySlugPreview').textContent = d.form_slug;
    }
    document.getElementById('zeffyLastReceived').textContent = d.last_received || 'Nothing yet';
    document.getElementById('zeffyTotalReceived').textContent = d.total_received ?? 0;
    document.getElementById('zeffyPendingCount').textContent = d.pending_count ?? 0;
    const dot = document.getElementById('zeffyStatusDot');
    if (d.last_received) {
      dot.className = 'zeffy-status-dot connected';
      dot.title = 'Last received ' + d.last_received;
    } else {
      dot.className = 'zeffy-status-dot unknown';
      dot.title = 'No payments received yet';
    }
    if (d.last_payload) {
      document.getElementById('zeffyPayloadWrap').style.display = 'block';
      document.getElementById('zeffyLastPayload').textContent = d.last_payload;
    }
  }

  document.getElementById('zeffyFormSlug')?.addEventListener('input', function() {
    document.getElementById('zeffySlugPreview').textContent = this.value || 'annual membership';
  });

  document.getElementById('csvUploadBtn')?.addEventListener('click', async () => {
    const input = document.getElementById('zeffyCsvFile');
    const msg = document.getElementById('csvImportMsg');
    const file = input.files?. [0];
    if (!file) {
      msg.className = 'panel-alert alert-error';
      msg.style.display = 'block';
      msg.textContent = 'Choose a CSV file first.';
      return;
    }
    const btn = document.getElementById('csvUploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Uploading…';
    msg.style.display = 'none';

    const fd = new FormData();
    fd.append('csv', file);
    let d;
    try {
      const r = await fetch('api.php?action=zeffy_import_csv', {
        method: 'POST',
        body: fd
      });
      d = await r.json();
    } catch (e) {
      d = {
        success: false,
        error: 'Upload failed. Please try again.'
      };
    }

    msg.style.display = 'block';
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.innerHTML = `<i class="bi bi-check-circle-fill"></i> Read <strong>${d.rows}</strong> row(s): ` +
        `activated <strong>${d.activated}</strong> membership(s), added <strong>${d.tickets}</strong> ticket(s), ` +
        `held <strong>${d.pending}</strong> for accounts that don't exist yet` +
        (d.skipped ? `, skipped <strong>${d.skipped}</strong> with no valid email` : '') + '.';
      input.value = '';
      allUsers = [];
      loadMemberships();
      loadZeffySettings();
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Import failed.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-arrow-up"></i> Upload & Sync';
  });

  document.getElementById('copyWebhookBtn')?.addEventListener('click', async () => {
    const url = document.getElementById('zeffyWebhookUrl').value;
    try {
      await navigator.clipboard.writeText(url);
    } catch (e) {
      const el = document.getElementById('zeffyWebhookUrl');
      el.select();
      document.execCommand('copy');
    }
    const btn = document.getElementById('copyWebhookBtn');
    btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
    setTimeout(() => {
      btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
    }, 1800);
  });

  document.getElementById('regenWebhookBtn')?.addEventListener('click', async () => {
    if (!confirm('Regenerate the webhook URL? The old URL will stop working — you must update it in Zapier.'))
      return;
    const d = await api('zeffy_regenerate_webhook');
    if (d.success) {
      loadZeffySettings();
    }
  });

  document.getElementById('saveZeffySettingsBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('saveZeffySettingsBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    const slug = document.getElementById('zeffyFormSlug').value.trim();
    const d = await api('zeffy_save_settings', {
      zeffy_membership_form_slug: slug
    });
    const msg = document.getElementById('zeffySettingsMsg');
    msg.className = d.success ? 'panel-alert alert-success' : 'panel-alert alert-error';
    msg.textContent = d.success ? '✓ Settings saved.' : (d.error || 'Failed.');
    msg.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-floppy"></i> Save Settings';
    setTimeout(() => {
      msg.style.display = 'none';
    }, 4000);
  });

  document.getElementById('bulkSyncBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('bulkSyncBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Applying…';
    const d = await api('zeffy_reconcile_all');
    const msg = document.getElementById('bulkSyncMsg');
    msg.style.display = 'block';
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.innerHTML =
        `<i class="bi bi-check-circle-fill"></i> Applied <strong>${d.applied}</strong> held purchase(s)` +
        (d.plans_fixed ? `, corrected <strong>${d.plans_fixed}</strong> membership plan(s)` : '') +
        `. <strong>${d.pending_remaining}</strong> still awaiting signup.`;
      allUsers = [];
      loadMemberships();
      loadZeffySettings();
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Apply Held Purchases Now';
  });

  // ── Memberships ───────────────────────────────────────────────────────────
  let allMemberships = [],
    allMembershipHistory = [],
    memTiers = [];
  let activatingUserId = null;

  async function loadMemberships() {
    const [ud, td, tierd] = await Promise.all([
      api('get_users'), api('get_all_memberships'), api('get_membership_tiers')
    ]);
    allUsers = ud.users || [];
    allMembershipHistory = td.memberships || [];
    memTiers = tierd.tiers || [];

    // Populate tier dropdown in activate modal
    const sel = document.getElementById('activateMemTierId');
    if (sel) {
      sel.innerHTML = '<option value="">Select tier…</option>' +
        memTiers.map(t => `<option value="${t.id}" data-price="${t.price}">${esc(t.name)} — $${t.price}/yr</option>`)
        .join('');
    }

    renderMembershipsTable();
    renderMembershipHistory();
  }

  function filterMemberships() {
    renderMembershipsTable();
  }

  function renderMembershipsTable() {
    const search = (document.getElementById('memSearch')?.value || '').toLowerCase();
    const statusFilter = document.getElementById('memStatusFilter')?.value || '';
    let users = allUsers.filter(u => u.role === 'member' || u.role === 'non_member');

    if (search) users = users.filter(u =>
      (u.first_name + ' ' + u.last_name).toLowerCase().includes(search) ||
      (u.email || '').toLowerCase().includes(search)
    );
    if (statusFilter) users = users.filter(u => (u.membership_status || 'none') === statusFilter);

    const el = document.getElementById('membershipsTable');
    if (!users.length) {
      el.innerHTML = '<p class="empty-state">No users found.</p>';
      return;
    }

    el.innerHTML = `<table class="data-table">
    <thead><tr><th>Member</th><th>Email</th><th>Member #</th><th>Status</th><th>Expiry</th><th>Actions</th></tr></thead>
    <tbody>${users.map(u => {
      const status = u.membership_status || 'none';
      const isActive = status === 'active';
      return `<tr>
        <td><strong>${esc(u.first_name + ' ' + u.last_name)}</strong></td>
        <td>${esc(u.email)}</td>
        <td><small>${esc(u.membership_number || '—')}</small></td>
        <td><span class="mem-status-pill mem-status-pill-${status}">${status}</span></td>
        <td>${u.membership_expiry || '—'}</td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
          <button class="btn-sm btn-primary" onclick="openActivateMem(${u.id},'${esc(u.first_name)} ${esc(u.last_name)}',${isActive})">
            <i class="bi bi-${isActive ? 'arrow-repeat' : 'award'}"></i> ${isActive ? 'Renew' : 'Activate'}
          </button>
          ${isActive ? `<button class="btn-sm btn-danger" onclick="deactivateMem(${u.id},'${esc(u.first_name)} ${esc(u.last_name)}')"><i class="bi bi-slash-circle"></i> Deactivate</button>` : ''}
        </td>
      </tr>`;
    }).join('')}</tbody></table>
    <p class="table-footer">${users.length} user${users.length !== 1 ? 's' : ''}</p>`;
  }

  function renderMembershipHistory() {
    const el = document.getElementById('membershipHistoryTable');
    if (!allMembershipHistory.length) {
      el.innerHTML = '<p class="empty-state">No membership records yet.</p>';
      return;
    }
    el.innerHTML = `<table class="data-table">
    <thead><tr><th>Member</th><th>Plan</th><th>Price Paid</th><th>Started</th><th>Expires</th><th>Recurring</th><th>Status</th><th>Notes</th></tr></thead>
    <tbody>${allMembershipHistory.map(m => `<tr>
      <td><strong>${esc(m.member_name)}</strong><br><small>${esc(m.member_email)}</small></td>
      <td>${esc(m.tier_name || 'Standard')}</td>
      <td>$${parseFloat(m.price_paid||0).toFixed(2)}</td>
      <td>${m.started_at || '—'}</td>
      <td>${m.expires_at || '—'}</td>
      <td>${parseInt(m.is_recurring) ? 'Yes' : 'No'}</td>
      <td><span class="mem-status-pill mem-status-pill-${m.status}">${m.status}</span></td>
      <td><small>${esc(m.notes || '—')}</small></td>
    </tr>`).join('')}</tbody></table>`;
  }

  function openActivateMem(userId, userName, isRenewal) {
    activatingUserId = userId;
    document.getElementById('activateMemUserId').value = userId;
    document.getElementById('activateMemTitle').textContent = isRenewal ? 'Renew Membership' : 'Activate Membership';
    document.getElementById('activateMemUserName').textContent = 'User: ' + userName;
    document.getElementById('activateMemMsg').style.display = 'none';
    // Default dates
    const today = new Date().toISOString().split('T')[0];
    const nextYear = new Date(Date.now() + 365 * 86400000).toISOString().split('T')[0];
    document.getElementById('activateMemStart').value = today;
    document.getElementById('activateMemExpiry').value = nextYear;
    document.getElementById('activateMemPrice').value = '';
    document.getElementById('activateMemNotes').value = '';
    document.getElementById('activateMemRecurring').checked = false;
    openModal('activateMemModal');
  }

  async function deactivateMem(userId, userName) {
    if (!confirm(`Deactivate membership for ${userName}? They will lose dashboard access immediately.`)) return;
    const d = await api('deactivate_membership', {
      user_id: userId
    });
    if (d.success) {
      allMemberships = [];
      allUsers = [];
      loadMemberships();
    } else alert(d.error || 'Failed to deactivate.');
  }

  document.getElementById('confirmActivateMemBtn')?.addEventListener('click', async () => {
    const tierId = document.getElementById('activateMemTierId').value;
    const tierOpt = document.querySelector(`#activateMemTierId option[value="${tierId}"]`);
    const tierName = tierOpt ? tierOpt.textContent.split(' — ')[0] : '';
    const data = {
      user_id: parseInt(document.getElementById('activateMemUserId').value),
      tier_id: parseInt(tierId) || 0,
      tier_name: tierName,
      price_paid: parseFloat(document.getElementById('activateMemPrice').value) || 0,
      started_at: document.getElementById('activateMemStart').value,
      expires_at: document.getElementById('activateMemExpiry').value,
      is_recurring: document.getElementById('activateMemRecurring').checked ? 1 : 0,
      notes: document.getElementById('activateMemNotes').value,
    };
    if (!data.expires_at) {
      alert('Please set an expiry date.');
      return;
    }
    const d = await api('activate_membership', data);
    const msg = document.getElementById('activateMemMsg');
    if (d.success) {
      msg.className = 'panel-alert alert-success';
      msg.textContent = '✓ Membership activated!';
      msg.style.display = 'block';
      allUsers = [];
      allMembershipHistory = [];
      loadMemberships();
      setTimeout(closeModals, 1000);
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed.';
      msg.style.display = 'block';
    }
  });

  // Auto-fill price when tier changes
  document.getElementById('activateMemTierId')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.price) {
      document.getElementById('activateMemPrice').value = opt.dataset.price;
    }
  });

  // ── Image uploaders ───────────────────────────────────────────────────────
  function setupUploader(zoneId, fileId, previewId, previewImgId, removeId, pathId, dir) {
    const zone = document.getElementById(zoneId),
      file = document.getElementById(fileId),
      prev = document.getElementById(previewId),
      prevImg = document.getElementById(previewImgId),
      rem = document.getElementById(removeId),
      path = document.getElementById(pathId);
    zone?.addEventListener('click', () => file.click());
    zone?.addEventListener('dragover', e => {
      e.preventDefault();
      zone.classList.add('drag-over');
    });
    zone?.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone?.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('drag-over');
      if (e.dataTransfer.files[0]) doUpload(e.dataTransfer.files[0]);
    });
    file?.addEventListener('change', () => {
      if (file.files[0]) doUpload(file.files[0]);
    });
    rem?.addEventListener('click', () => {
      path.value = '';
      prev.style.display = 'none';
      zone.style.display = 'flex';
    });
    async function doUpload(f) {
      zone.innerHTML = '<div class="upload-spinner"><i class="bi bi-arrow-repeat spin"></i> Uploading…</div>';
      const fd = new FormData();
      fd.append('image', f);
      fd.append('upload_dir', dir);
      const r = await fetch('api.php?action=upload_image', {
        method: 'POST',
        body: fd
      });
      const d = await r.json();
      if (d.success) {
        path.value = d.path;
        prevImg.src = d.path;
        prev.style.display = 'block';
        zone.style.display = 'none';
      }
      zone.innerHTML =
        '<i class="bi bi-cloud-upload"></i><p>Drag & drop or click</p><small>JPEG, PNG, WebP · max 8 MB</small>';
    }
  }

  // ── Modal helpers ─────────────────────────────────────────────────────────
  function openModal(id) {
    document.getElementById(id)?.classList.add('active');
    document.getElementById('modalBackdrop')?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeModals() {
    document.querySelectorAll('.panel-modal').forEach(m => m.classList.remove('active'));
    document.getElementById('modalBackdrop')?.classList.remove('active');
    document.body.style.overflow = '';
  }
  ['closeEventModal', 'cancelEventBtn', 'closePostModal', 'cancelPostBtn', 'closeMemberModal', 'cancelMemberBtn',
    'closeTierModal', 'cancelTierBtn', 'closeUserModal', 'cancelUserBtn', 'closeActivateMemModal',
    'cancelActivateMemBtn'
  ].forEach(id => document.getElementById(id)?.addEventListener('click', closeModals));
  document.getElementById('closePhotoModal')?.addEventListener('click', closeModals);
  document.getElementById('closePhotoModalBtn')?.addEventListener('click', closeModals);
  document.getElementById('savePhotoCaptionsBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('savePhotoCaptionsBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
    await saveAllCaptions();
    closeModals();
  });
  document.getElementById('modalBackdrop')?.addEventListener('click', closeModals);
  document.getElementById('newEventBtn')?.addEventListener('click', () => {
    loadEvents();
    openNewEvent();
  });
  document.getElementById('newPostBtn')?.addEventListener('click', openNewPost);

  // ── Logout ────────────────────────────────────────────────────────────────
  document.getElementById('logoutBtn')?.addEventListener('click', async e => {
    e.preventDefault();
    const d = await (await fetch('api.php?action=logout', {
      method: 'POST'
    })).json();
    if (d.success) window.location.href = d.redirect || 'index.php';
  });

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  window.addEventListener('DOMContentLoaded', () => {
    const qOpts = {
      theme: 'snow',
      modules: {
        toolbar: [
          ['bold', 'italic', 'underline'],
          [{
            'header': [1, 2, 3, false]
          }],
          [{
            'list': 'ordered'
          }, {
            'list': 'bullet'
          }],
          ['link'],
          ['clean']
        ]
      }
    };
    evQuill = new Quill('#evDescEditor', qOpts);
    postQuill = new Quill('#postContentEditor', qOpts);
    setupUploader('evUploadZone', 'evImageFile', 'evImagePreview', 'evPreviewImg', 'removeEvImage', 'evImagePath',
      'events');
    setupUploader('postUploadZone', 'postImageFile', 'postImagePreview', 'postPreviewImg', 'removePostImage',
      'postImagePath', 'posts');
    const initTab = new URLSearchParams(location.search).get('tab');
    if (initTab && document.getElementById('tab-' + initTab)) switchTab(initTab);
    else loadOverview();
    initPhotoModal();
  });

  // ── Photo Modal ───────────────────────────────────────────────────────────
  let currentPhotoEventId = null;
  let currentPhotos = [];
  let dirtyCaptions = {};

  function initPhotoModal() {
    const zone = document.getElementById('photoUploadZone');
    const input = document.getElementById('photoFileInput');
    if (!zone || !input) return;

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', e => {
      e.preventDefault();
      zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('drag-over');
      if (e.dataTransfer.files.length) handlePhotoUploadQueue(Array.from(e.dataTransfer.files));
    });
    input.addEventListener('change', () => {
      if (input.files.length) handlePhotoUploadQueue(Array.from(input.files));
      input.value = '';
    });
  }

  async function openPhotoModal(eventId) {
    dirtyCaptions = {};
    currentPhotoEventId = eventId;
    const ev = allEvents.find(e => String(e.id) === String(eventId));
    document.getElementById('photoModalEventTitle').textContent = ev?.title || '';
    document.getElementById('uploadQueue').innerHTML = '';
    document.getElementById('photoProgressBar').style.display = 'none';
    document.getElementById('photoSaveMsg').style.display = 'none';
    document.getElementById('mediaMsg').style.display = 'none';
    document.getElementById('newMediaUrl').value = '';
    document.getElementById('newMediaLabel').value = '';
    openModal('photoModal');
    loadEventPhotos();
    loadEventMedia();
  }

  async function loadEventPhotos() {
    const grid = document.getElementById('photoManageGrid');
    grid.innerHTML = '<p class="loading-text" style="grid-column:1/-1">Loading…</p>';

    const d = await api('get_event_photos&event_id=' + currentPhotoEventId);
    currentPhotos = d.photos || [];
    renderPhotoGrid();
  }

  function renderPhotoGrid() {
    const grid = document.getElementById('photoManageGrid');
    const badge = document.getElementById('photoCountBadge');
    const n = currentPhotos.length;
    badge.textContent = n + ' / 200 photos';
    badge.className = 'photo-count-badge' + (n >= 200 ? ' at-limit' : '');
    badge.innerHTML = `<i class="bi bi-images"></i> ${n} / 200 photos`;

    if (!currentPhotos.length) {
      grid.innerHTML = '<p class="empty-state" style="grid-column:1/-1">No photos yet. Upload some above!</p>';
      return;
    }

    grid.innerHTML = currentPhotos.map(p => `
    <div class="photo-thumb-item" data-id="${p.id}">
      <img src="${escAttr(p.photo_url)}" alt="${escAttr(p.caption||'')}" loading="lazy"/>
      <div class="photo-thumb-actions">
        <button class="photo-thumb-del" onclick="deleteEventPhoto(${p.id})" title="Delete"><i class="bi bi-trash"></i></button>
      </div>
      <input type="text" class="photo-caption-input" placeholder="Add caption…"
             value="${escAttr(p.caption||'')}"
             oninput="dirtyCaptions[${p.id}] = this.value"
             title="Caption"/>
    </div>
  `).join('');
  }

  async function deleteEventPhoto(photoId) {
    if (!confirm('Delete this photo?')) return;
    const d = await api('delete_event_photo', {
      id: photoId
    });
    if (d.success) await loadEventPhotos();
    else alert(d.error || 'Delete failed.');
  }

  async function saveAllCaptions() {
    const ids = Object.keys(dirtyCaptions);
    if (!ids.length) return;
    await Promise.all(ids.map(id => api('update_photo_caption', {
      id: parseInt(id),
      caption: dirtyCaptions[id]
    })));
    dirtyCaptions = {};
  }

  async function handlePhotoUploadQueue(files) {
    const MAX = 200;
    const remaining = MAX - currentPhotos.length;
    if (remaining <= 0) {
      showPhotoMsg('Maximum 200 photos per event reached.', false);
      return;
    }

    const toUpload = files.slice(0, remaining);
    const queue = document.getElementById('uploadQueue');
    const progressBar = document.getElementById('photoProgressBar');
    const progressFill = document.getElementById('photoProgressFill');

    queue.innerHTML = toUpload.map((f, i) =>
      `<li class="upload-queue-item" id="qitem-${i}">
      <i class="bi bi-hourglass-split status-icon pending"></i>
      <span>${esc(f.name)}</span>
     </li>`
    ).join('');

    progressBar.style.display = 'block';
    let done = 0;

    for (let i = 0; i < toUpload.length; i++) {
      const f = toUpload[i];
      const item = document.getElementById('qitem-' + i);
      const icon = item?.querySelector('.status-icon');
      if (icon) {
        icon.className = 'bi bi-arrow-repeat spin status-icon pending';
      }

      try {
        const compressed = await compressImage(f);
        const fd = new FormData();
        fd.append('photo', compressed);
        fd.append('event_id', currentPhotoEventId);

        const r = await fetch('api.php?action=upload_event_photo', {
          method: 'POST',
          body: fd
        });
        const d = await r.json();

        if (icon) {
          icon.className = d.success ?
            'bi bi-check-circle-fill status-icon done' :
            'bi bi-x-circle-fill status-icon error';
        }
        if (!d.success && item) {
          item.innerHTML += ` <small style="color:var(--red)">${esc(d.error||'Failed')}</small>`;
        }
      } catch (e) {
        if (icon) icon.className = 'bi bi-x-circle-fill status-icon error';
      }

      done++;
      progressFill.style.width = Math.round((done / toUpload.length) * 100) + '%';
    }

    await loadEventPhotos();
    setTimeout(() => {
      queue.innerHTML = '';
      progressBar.style.display = 'none';
      progressFill.style.width = '0%';
    }, 2500);
  }

  function showPhotoMsg(msg, success) {
    const el = document.getElementById('photoSaveMsg');
    el.className = 'panel-alert ' + (success ? 'alert-success' : 'alert-error');
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 3000);
  }

  // ── Event Media (Videos & Links) ──────────────────────────────────────────
  function ytIdFromUrl(url) {
    const m = (url || '').match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|v\/)|youtu\.be\/)([^&\s?]+)/);
    return m ? m[1] : null;
  }

  async function loadEventMedia() {
    const list = document.getElementById('mediaList');
    list.innerHTML = '<p class="loading-text">Loading…</p>';
    const d = await api('get_event_media&event_id=' + currentPhotoEventId);
    const items = d.media || [];
    if (!items.length) {
      list.innerHTML = '<p class="empty-state" style="margin:0;font-size:.87rem">No videos or links yet.</p>';
      return;
    }
    list.innerHTML = items.map(m => {
      const ytId = m.type === 'youtube' ? ytIdFromUrl(m.url) : null;
      const thumb = ytId ?
        `<img src="https://img.youtube.com/vi/${escAttr(ytId)}/mqdefault.jpg" style="width:100px;height:56px;object-fit:cover;border-radius:6px;flex-shrink:0" onerror="this.style.display='none'"/>` :
        `<span style="width:36px;height:36px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="bi bi-link-45deg" style="color:#6b7280"></i></span>`;
      return `<div style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--card-bg);border:1px solid var(--border);border-radius:10px">
      ${thumb}
      <div style="flex:1;min-width:0">
        <p style="margin:0;font-weight:600;font-size:.87rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(m.label || (m.type === 'youtube' ? 'YouTube Video' : m.url))}</p>
        <a href="${escAttr(m.url)}" target="_blank" rel="noopener" style="font-size:.78rem;color:var(--gold);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block">${esc(m.url)}</a>
      </div>
      <span class="badge badge-${m.type === 'youtube' ? 'upcoming' : 'past'}" style="flex-shrink:0">${m.type === 'youtube' ? '<i class="bi bi-youtube"></i> YouTube' : '<i class="bi bi-link-45deg"></i> Link'}</span>
      <button class="btn-sm btn-danger" onclick="deleteEventMedia(${m.id})" title="Remove"><i class="bi bi-trash"></i></button>
    </div>`;
    }).join('');
  }

  async function addEventMedia() {
    const url = document.getElementById('newMediaUrl').value.trim();
    const label = document.getElementById('newMediaLabel').value.trim();
    const msg = document.getElementById('mediaMsg');
    if (!url) {
      msg.className = 'panel-alert alert-error';
      msg.textContent = 'Please enter a URL.';
      msg.style.display = 'block';
      return;
    }
    msg.style.display = 'none';
    const d = await api('add_event_media', {
      event_id: currentPhotoEventId,
      url,
      label
    });
    if (d.success) {
      document.getElementById('newMediaUrl').value = '';
      document.getElementById('newMediaLabel').value = '';
      await loadEventMedia();
    } else {
      msg.className = 'panel-alert alert-error';
      msg.textContent = d.error || 'Failed to add.';
      msg.style.display = 'block';
    }
  }

  async function deleteEventMedia(id) {
    if (!confirm('Remove this video/link?')) return;
    const d = await api('delete_event_media', {
      id
    });
    if (d.success) loadEventMedia();
  }

  function escAttr(s) {
    return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function compressImage(file, maxBytes = 9 * 1024 * 1024, maxDim = 3840) {
    return new Promise((resolve) => {
      if (file.size <= maxBytes && file.type !== 'image/png') {
        resolve(file);
        return;
      }
      const img = new Image();
      const url = URL.createObjectURL(file);
      img.onload = () => {
        URL.revokeObjectURL(url);
        let {
          width,
          height
        } = img;
        if (width > maxDim || height > maxDim) {
          const scale = maxDim / Math.max(width, height);
          width = Math.round(width * scale);
          height = Math.round(height * scale);
        }
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
        let quality = 0.92;
        const tryEncode = () => {
          canvas.toBlob(blob => {
            if (!blob) {
              resolve(file);
              return;
            }
            if (blob.size <= maxBytes || quality <= 0.30) {
              resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                type: 'image/jpeg'
              }));
            } else {
              quality = Math.max(0.30, quality - 0.10);
              tryEncode();
            }
          }, 'image/jpeg', quality);
        };
        tryEncode();
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        resolve(file);
      };
      img.src = url;
    });
  }

  // ── Collapsible sidebar ──────────────────────────────────────────────────
  (function() {
    var KEY = 'otsSidebarCollapsed';
    var layout = document.querySelector('.panel-layout');
    var btn = document.getElementById('sidebarToggle');
    if (!layout || !btn) return;
    if (localStorage.getItem(KEY) === '1') layout.classList.add('collapsed');
    btn.addEventListener('click', function() {
      layout.classList.toggle('collapsed');
      localStorage.setItem(KEY, layout.classList.contains('collapsed') ? '1' : '0');
    });
  })();
  </script>
</body>

</html>