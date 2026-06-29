<?php
require_once __DIR__ . '/auth.php';
$user = requireRole(['coordinator', 'admin', 'social_media', 'membership_coordinator', 'cultural_coordinator', 'sports_coordinator']);

// Tab visibility by role — uses userHasRole() so multi-role users get correct access
$canPosts     = userHasRole($user, ['admin','coordinator','social_media']);
$canEvents    = userHasRole($user, ['admin','coordinator','cultural_coordinator','sports_coordinator']);
$canCommittee = userHasRole($user, ['admin','coordinator']);
$canSlideshow = userHasRole($user, ['admin','coordinator','social_media','cultural_coordinator','sports_coordinator']);
$canContent   = userHasRole($user, ['admin','coordinator','social_media']);
$canMembers   = userHasRole($user, ['admin','membership_coordinator']);
$canForms     = userHasRole($user, ['admin','coordinator','cultural_coordinator']);
$canViewForms = userHasRole($user, ['admin','coordinator','cultural_coordinator','sports_coordinator']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Coordinator Panel — Ottawa Tamil Sangam</title>
  <link rel="stylesheet" href="styles.css"/>
  <link rel="stylesheet" href="admin_styles.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet"/>
</head>
<body class="panel-body">

<div class="panel-layout">

  <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
  <aside class="panel-sidebar coordinator-sidebar">
    <div class="sidebar-brand">
      <img src="assets/ots_logo.png" alt="OTS" onerror="this.style.display='none'" class="sidebar-logo"/>
      <span>OTS</span>
      <button class="sidebar-collapse-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar" title="Collapse / expand sidebar"><i class="bi bi-chevron-double-left"></i></button>
    </div>
    <div class="sidebar-role-badge">
      <i class="bi bi-pencil-square"></i> <?= e(roleLabel($user['role'])) ?>
    </div>
    <nav class="sidebar-nav">
      <a href="#" class="sidebar-link active" data-tab="overview"><i class="bi bi-grid-1x2"></i> Overview</a>
      <?php if ($canPosts): ?>
      <a href="#" class="sidebar-link" data-tab="posts"><i class="bi bi-megaphone"></i> Posts / News</a>
      <?php endif; ?>
      <?php if ($canEvents): ?>
      <a href="#" class="sidebar-link" data-tab="events"><i class="bi bi-calendar-event"></i> Edit Events</a>
      <?php endif; ?>
      <?php if ($canCommittee): ?>
      <a href="#" class="sidebar-link" data-tab="committee"><i class="bi bi-people-fill"></i> Committee</a>
      <?php endif; ?>
      <?php if ($canSlideshow): ?>
      <a href="#" class="sidebar-link" data-tab="slideshow"><i class="bi bi-images"></i> Slideshow / Gallery</a>
      <?php endif; ?>
      <?php if ($canContent): ?>
      <a href="#" class="sidebar-link" data-tab="sitecontent"><i class="bi bi-file-text"></i> Site Content</a>
      <?php endif; ?>
      <?php if ($canMembers): ?>
      <a href="#" class="sidebar-link" data-tab="members"><i class="bi bi-people"></i> Members</a>
      <?php endif; ?>
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
        <h1 class="panel-title">Coordinator Panel</h1>
        <p class="panel-subtitle">Manage posts, event content &amp; home page</p>
      </div>
      <div class="topbar-user">
        <span class="user-avatar-lg"><?= e(strtoupper(substr($user['first_name'],0,1))) ?></span>
        <div>
          <p class="topbar-name"><?= e($user['first_name'].' '.$user['last_name']) ?></p>
          <span class="role-badge role-<?= e(str_replace('_','-',$user['role'])) ?>"><?= e(roleLabel($user['role'])) ?></span>
        </div>
      </div>
    </header>

    <!-- ── Overview ──────────────────────────────────────────────────────── -->
    <section class="tab-panel active" id="tab-overview">
      <div class="stats-row">
        <div class="stat-card-panel"><i class="bi bi-megaphone stat-icon"></i><div><p class="stat-num" id="stat-posts">—</p><p class="stat-label">Total Posts</p></div></div>
        <div class="stat-card-panel"><i class="bi bi-calendar-event stat-icon"></i><div><p class="stat-num" id="stat-events">—</p><p class="stat-label">Events</p></div></div>
      </div>
      <div class="panel-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h3>Your Recent Posts</h3>
          <button class="btn-primary btn-sm" onclick="switchTab('posts');openNewPost()"><i class="bi bi-plus-lg"></i> New Post</button>
        </div>
        <div id="overviewPosts"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Posts ─────────────────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-posts">
      <div class="panel-card">
        <div class="panel-card-header">
          <h3>Posts &amp; Announcements</h3>
          <button class="btn-primary" id="newPostBtn"><i class="bi bi-plus-lg"></i> New Post</button>
        </div>
        <div id="postsTable"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Events (edit only) ─────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-events">
      <div class="panel-card">
        <h3>Edit Event Details</h3>
        <p class="section-hint">You can update event descriptions and images. Contact an admin to change dates, prices, or locations.</p>
        <div id="eventsTable"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Committee ────────────────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-committee">
      <div class="panel-card">
        <div class="panel-card-header">
          <h3>Committee Members</h3>
          <button class="btn-primary" id="newMemberBtn"><i class="bi bi-plus-lg"></i> Add Member</button>
        </div>
        <p class="section-hint">Changes appear immediately on the committee page.</p>
        <div id="committeeMembersList"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

    <!-- ── Slideshow ─────────────────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-slideshow">
      <div class="panel-card">
        <div class="panel-card-header">
          <h3>Home Page Slideshow</h3>
          <label class="btn-primary" style="cursor:pointer">
            <i class="bi bi-cloud-upload"></i> Upload Photos
            <input type="file" id="slideshowUploadInput" accept="image/*" multiple style="display:none"/>
          </label>
        </div>
        <p class="section-hint">Active photos appear in the home page slideshow. Toggle to show/hide without deleting.</p>
        <div id="slideshowGrid" class="slideshow-manage-grid"><p class="loading-text">Loading…</p></div>
        <div id="slideshowMsg" class="panel-alert" style="display:none;margin-top:12px"></div>
      </div>
    </section>

    <!-- ── Site Content ───────────────────────────────────────────────────── -->
    <section class="tab-panel" id="tab-sitecontent">
      <div class="panel-card">
        <h3>Site Content Editor</h3>
        <p class="section-hint">Edit text content across all pages. Changes appear immediately.</p>
        <div id="siteContentSections"><p class="loading-text">Loading…</p></div>
        <div id="contentSaveMsg" class="panel-alert" style="display:none"></div>
      </div>
    </section>

    <!-- ── Members (Membership Coordinator) ────────────────────────────────── -->
    <section class="tab-panel" id="tab-members">
      <div class="panel-card">
        <div class="panel-card-header">
          <h3>Member Accounts</h3>
          <button class="btn-primary" id="newMemberAccountBtn"><i class="bi bi-plus-lg"></i> Add Member</button>
        </div>
        <p class="section-hint">Add, edit, or deactivate member accounts. Deactivated accounts are flagged INACTIVE — no data is permanently deleted.</p>
        <div class="filter-bar" style="margin-bottom:12px">
          <input type="text" id="memberSearch" placeholder="Search name or email…" style="width:260px" oninput="filterMembersList()"/>
          <select id="memberStatusFilter" onchange="filterMembersList()">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div id="memberAccountsTable"><p class="loading-text">Loading…</p></div>
      </div>
    </section>

  </main>
</div>

<!-- ── Member Account Modal (Membership Coordinator) ──────────────────────── -->
<div id="memberAccountModal" class="panel-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeMemberAccountModal"><i class="bi bi-x-lg"></i></button>
    <h3 id="memberAccountModalTitle">Add Member</h3>
    <div id="memberAccountMsg" class="panel-alert" style="display:none"></div>
    <input type="hidden" id="maId"/>
    <div class="form-row">
      <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" id="maFirst"/></div>
      <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" id="maLast"/></div>
    </div>
    <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" id="maEmail"/></div>
    <div class="form-group"><label>Phone</label><input type="tel" id="maPhone"/></div>
    <div class="form-row">
      <div class="form-group">
        <label>Membership Status</label>
        <select id="maMembershipStatus">
          <option value="none">Non-Member</option>
          <option value="active">Active Member</option>
          <option value="expired">Expired</option>
        </select>
      </div>
      <div class="form-group"><label>Membership Expiry</label><input type="date" id="maMembershipExpiry"/></div>
    </div>
    <div class="form-group" id="maPasswordGroup">
      <label>Temporary Password <span class="required">*</span></label>
      <input type="password" id="maPassword" placeholder="Set initial password"/>
    </div>
    <div class="modal-actions">
      <button class="btn-outline" id="cancelMemberAccountBtn">Cancel</button>
      <button class="btn-primary" id="saveMemberAccountBtn">Save</button>
    </div>
  </div>
</div>

<!-- ── Committee Member Modal ─────────────────────────────────────────────── -->
<div id="memberModal" class="panel-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeMemberModal"><i class="bi bi-x-lg"></i></button>
    <h3 id="memberModalTitle">Add Committee Member</h3>
    <div id="memberSaveMsg" class="panel-alert" style="display:none"></div>
    <form id="memberForm" onsubmit="return false">
      <div class="form-row">
        <div class="form-group">
          <label>Name (English) <span class="required">*</span></label>
          <input type="text" id="memberNameEn" placeholder="e.g. Sangeetha" required/>
        </div>
        <div class="form-group">
          <label>Name (Tamil)</label>
          <input type="text" id="memberNameTa" placeholder="e.g. சங்கீதா"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role (English)</label>
          <input type="text" id="memberRoleEn" placeholder="e.g. President"/>
        </div>
        <div class="form-group">
          <label>Role (Tamil)</label>
          <input type="text" id="memberRoleTa" placeholder="e.g. தலைவர்"/>
        </div>
      </div>
      <div class="form-group">
        <label>Display Order</label>
        <input type="number" id="memberOrder" value="0" min="0"/>
      </div>
      <div class="form-group">
        <label>Photo</label>
        <input type="file" id="memberPhotoInput" accept="image/*"/>
        <img id="memberPhotoPreview" src="" alt="Preview" style="display:none;margin-top:8px;width:80px;height:80px;object-fit:cover;border-radius:50%;border:3px solid var(--gold)"/>
        <input type="hidden" id="memberPhotoPath"/>
      </div>
    </form>
    <div class="modal-actions">
      <button class="btn-outline" id="cancelMemberBtn">Cancel</button>
      <button class="btn-primary" id="saveMemberBtn">Save Member</button>
    </div>
  </div>
</div>

<!-- ── Post Editor Modal ──────────────────────────────────────────────────── -->
<div id="postModal" class="panel-modal wide-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closePostModal"><i class="bi bi-x-lg"></i></button>
    <h3 id="postModalTitle">New Post</h3>

    <div id="postSaveMsg" class="panel-alert" style="display:none"></div>

    <div class="form-group">
      <label>Title <span class="required">*</span></label>
      <input type="text" id="postTitle" placeholder="Post title…"/>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Type</label>
        <select id="postType">
          <option value="announcement">📢 Announcement</option>
          <option value="news">📰 News</option>
          <option value="event">🎉 Event Update</option>
        </select>
      </div>
      <div class="form-group">
        <label>Published</label>
        <select id="postPublished">
          <option value="1">✅ Published</option>
          <option value="0">📋 Draft</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Content</label>
      <div id="postContentEditor" style="height:220px;background:#fff"></div>
    </div>

    <div class="form-group">
      <label>Featured Image</label>
      <div class="image-uploader" id="postImageUploader">
        <div class="upload-zone" id="postUploadZone">
          <i class="bi bi-cloud-upload"></i>
          <p>Drag &amp; drop or click to upload</p>
          <small>JPEG, PNG, WebP · max 8 MB</small>
        </div>
        <input type="file" id="postImageFile" accept="image/*" style="display:none"/>
        <div class="image-preview" id="postImagePreview" style="display:none">
          <img id="postPreviewImg" src="" alt="Preview"/>
          <button class="remove-image" id="removePostImage"><i class="bi bi-x-circle-fill"></i></button>
        </div>
        <input type="hidden" id="postImagePath" value=""/>
      </div>
    </div>

    <div style="display:flex;gap:12px;margin-top:8px">
      <button class="btn-primary" id="savePostBtn">Save Post</button>
      <button class="btn-outline" id="cancelPostBtn">Cancel</button>
    </div>
  </div>
</div>

<!-- ── Event Edit Modal ───────────────────────────────────────────────────── -->
<div id="eventModal" class="panel-modal wide-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeEventModal"><i class="bi bi-x-lg"></i></button>
    <h3>Edit Event — <span id="editEventTitle"></span></h3>
    <p class="section-hint">You can update the description and image. For other changes, contact an admin.</p>
    <div id="eventSaveMsg" class="panel-alert" style="display:none"></div>
    <input type="hidden" id="editEventId"/>

    <div class="form-group">
      <label>Description</label>
      <div id="eventDescEditor" style="height:200px;background:#fff"></div>
    </div>

    <div class="form-group">
      <label>Event Image</label>
      <div class="image-uploader">
        <div class="upload-zone" id="eventUploadZone">
          <i class="bi bi-cloud-upload"></i>
          <p>Drag &amp; drop or click to upload</p>
        </div>
        <input type="file" id="eventImageFile" accept="image/*" style="display:none"/>
        <div class="image-preview" id="eventImagePreview" style="display:none">
          <img id="eventPreviewImg" src="" alt="Preview"/>
          <button class="remove-image" id="removeEventImage"><i class="bi bi-x-circle-fill"></i></button>
        </div>
        <input type="hidden" id="editEventImagePath" value=""/>
      </div>
    </div>

    <div style="display:flex;gap:12px;margin-top:16px">
      <button class="btn-primary" id="saveEventBtn">Save Changes</button>
      <button class="btn-outline" id="cancelEventBtn">Cancel</button>
    </div>
  </div>
</div>

<!-- Photo Management Modal -->
<div id="photoModal" class="panel-modal wide-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closePhotoModal"><i class="bi bi-x-lg"></i></button>
    <h3>Manage Photos — <span id="photoModalEventTitle"></span></h3>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <span id="photoCountBadge" class="photo-count-badge"><i class="bi bi-images"></i> 0 / 200 photos</span>
      <div id="photoSaveMsg" class="panel-alert" style="display:none;margin:0"></div>
    </div>

    <!-- Upload Zone -->
    <div class="multi-upload-zone" id="photoUploadZone">
      <i class="bi bi-cloud-upload"></i>
      <p>Drag &amp; drop photos or click to select</p>
      <small>JPEG, PNG, WebP &middot; max 10 MB each (auto-compressed) &middot; up to 200 total</small>
    </div>
    <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" multiple style="display:none"/>
    <div class="upload-progress-bar" id="photoProgressBar" style="display:none">
      <div class="upload-progress-fill" id="photoProgressFill" style="width:0%"></div>
    </div>
    <ul class="upload-queue" id="uploadQueue"></ul>

    <!-- Existing photos grid -->
    <div class="photo-manage-grid" id="photoManageGrid">
      <p class="loading-text" style="grid-column:1/-1">Loading photos…</p>
    </div>

    <!-- Footer -->
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <button class="btn-secondary" id="closePhotoModalBtn">Close</button>
      <button class="btn-primary" id="savePhotoCaptionsBtn"><i class="bi bi-check-lg"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- ── Event Forms Hub Modal ────────────────────────────────────────────── -->
<?php if ($canViewForms): ?>
<div id="eventFormsModal" class="panel-modal wide-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeEventFormsModal"><i class="bi bi-x-lg"></i></button>
    <h3>Event Forms — <span id="eventFormsTitle"></span></h3>
    <p class="section-hint">Create volunteer sign-up and performer application forms for this event.</p>
    <div id="eventFormsMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px" id="eventFormsGrid">
      <p class="loading-text" style="grid-column:1/-1">Loading…</p>
    </div>
    <button class="btn-outline" id="closeEventFormsBtn" style="margin-top:16px">Close</button>
  </div>
</div>

<!-- ── Form Builder Modal (create/edit — canForms only) ──────────────────── -->
<?php if ($canForms): ?>
<div id="formBuilderModal" class="panel-modal wide-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeFormBuilderModal"><i class="bi bi-x-lg"></i></button>
    <h3 id="formBuilderTitle">Edit Form</h3>
    <div id="formBuilderMsg" class="panel-alert" style="display:none;margin-bottom:12px"></div>
    <input type="hidden" id="fbEventId"/>
    <input type="hidden" id="fbFormType"/>
    <input type="hidden" id="fbFormId"/>
    <div class="form-group"><label>Form Title</label><input type="text" id="fbTitle" placeholder="e.g. Volunteer Sign-Up"/></div>
    <div class="form-group"><label>Description <small>(shown to user)</small></label><textarea id="fbDesc" rows="2" placeholder="Brief intro about this form…"></textarea></div>
    <div class="form-row">
      <div class="form-group"><label>Status</label><select id="fbActive"><option value="1">✅ Active (accepting submissions)</option><option value="0">🚫 Inactive</option></select></div>
      <div class="form-group"><label>Deadline <small>(leave blank = no deadline)</small></label><input type="datetime-local" id="fbDeadline"/></div>
    </div>
    <div class="form-group"><label>Max Submissions <small>(0 = unlimited)</small></label><input type="number" id="fbMax" value="0" min="0" style="width:120px"/></div>
    <button class="btn-primary btn-sm" id="saveFormSettingsBtn" style="margin-bottom:20px"><i class="bi bi-floppy"></i> Save Form Settings</button>
    <div style="border-top:1px solid var(--border);padding-top:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <h4 style="margin:0">Questions</h4>
        <button class="btn-primary btn-sm" id="addQuestionBtn"><i class="bi bi-plus-lg"></i> Add Question</button>
      </div>
      <div id="questionsList"><p class="empty-state">No questions yet — this form just collects a name/sign-up.</p></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn-outline" id="backToFormsBtn"><i class="bi bi-arrow-left"></i> Back to Forms</button>
      <button class="btn-outline btn-sm" id="viewSubmissionsBtn"><i class="bi bi-list-ul"></i> View Submissions</button>
    </div>
  </div>
</div>

<!-- ── Question Editor Modal ────────────────────────────────────────────── -->
<div id="questionModal" class="panel-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeQuestionModal"><i class="bi bi-x-lg"></i></button>
    <h3 id="questionModalTitle">Add Question</h3>
    <div id="questionMsg" class="panel-alert" style="display:none"></div>
    <input type="hidden" id="qId"/>
    <div class="form-group"><label>Question <span class="required">*</span></label><input type="text" id="qText" placeholder="e.g. What experience do you have?"/></div>
    <div class="form-row">
      <div class="form-group"><label>Input Type</label>
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
          <input type="checkbox" id="qRequired" checked style="width:14px;height:14px"/> Required
        </label>
      </div>
    </div>
    <div id="qLimitsRow" class="form-row">
      <div class="form-group"><label>Word Limit <small>(0 = none)</small></label><input type="number" id="qWordLimit" value="0" min="0" style="width:100px"/></div>
      <div class="form-group"><label>Char Limit <small>(0 = none)</small></label><input type="number" id="qCharLimit" value="0" min="0" style="width:100px"/></div>
    </div>
    <div id="qOptionsGroup" class="form-group" style="display:none">
      <label>Options <small>(one per line)</small></label>
      <textarea id="qOptions" rows="5" placeholder="Option A&#10;Option B&#10;Option C"></textarea>
    </div>
    <div class="form-group"><label>Display Order</label><input type="number" id="qOrder" value="0" min="0" style="width:100px"/></div>
    <div class="modal-actions">
      <button class="btn-outline" id="cancelQuestionBtn">Cancel</button>
      <button class="btn-primary" id="saveQuestionBtn">Save Question</button>
    </div>
  </div>
</div>

<?php endif; // $canForms ?>

<!-- ── Submissions Viewer Modal ──────────────────────────────────────────── -->
<div id="submissionsModal" class="panel-modal wide-modal">
  <div class="panel-modal-inner">
    <button class="modal-close" id="closeSubmissionsModal"><i class="bi bi-x-lg"></i></button>
    <h3 id="submissionsModalTitle">Submissions</h3>
    <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;align-items:center">
      <span id="submissionCount" class="photo-count-badge"></span>
      <?php if ($canForms): ?>
      <button class="btn-outline btn-sm" id="backToBuilderBtn"><i class="bi bi-arrow-left"></i> Back to Form</button>
      <?php endif; ?>
      <button class="btn-outline btn-sm" id="backToFormsFromSubBtn"><i class="bi bi-arrow-left"></i> Back to Forms</button>
    </div>
    <div id="submissionsTable"><p class="loading-text">Loading…</p></div>
  </div>
</div>
<?php endif; ?>

<div class="modal-backdrop" id="modalBackdrop"></div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
const API = 'api.php';
const canManageForms = <?= $canForms ? 'true' : 'false' ?>;
const canViewForms   = <?= $canViewForms ? 'true' : 'false' ?>;
let postQuill  = null;
let eventQuill = null;
let editingPostId   = null;
let editingEventId  = null;
const siteEditors   = {};
let allPostsData    = [];
let allEventsData   = [];

async function api(action, data = null) {
  const opts = data
    ? { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) }
    : { method:'GET' };
  const r = await fetch(`${API}?action=${action}`, opts);
  return r.json();
}

// ── Tab switching ─────────────────────────────────────────────────────────
function switchTab(tab) {
  document.querySelectorAll('.sidebar-link[data-tab]').forEach(l => l.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelector(`.sidebar-link[data-tab="${tab}"]`)?.classList.add('active');
  document.getElementById('tab-' + tab)?.classList.add('active');
  if (tab === 'posts') loadPosts();
  if (tab === 'events') loadEvents();
  if (tab === 'sitecontent') loadSiteContent();
}
document.querySelectorAll('.sidebar-link[data-tab]').forEach(link => {
  link.addEventListener('click', e => { e.preventDefault(); switchTab(link.dataset.tab); });
});

// ── Overview ──────────────────────────────────────────────────────────────
async function loadOverview() {
  const [pd, ed] = await Promise.all([api('get_posts&all=1'), api('get_events&all=1')]);
  allPostsData = pd.posts || [];
  allEventsData = ed.events || [];
  document.getElementById('stat-posts').textContent  = allPostsData.length;
  document.getElementById('stat-events').textContent = allEventsData.length;

  const el = document.getElementById('overviewPosts');
  const recent = allPostsData.slice(0,5);
  if (!recent.length) { el.innerHTML = '<p class="empty-state">No posts yet.</p>'; return; }
  el.innerHTML = `<table class="data-table"><thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>${recent.map(p => `<tr>
      <td><strong>${esc(p.title)}</strong></td>
      <td><span class="badge badge-${p.post_type}">${p.post_type}</span></td>
      <td><span class="status-badge status-${p.is_published?'confirmed':'draft'}">${p.is_published?'Published':'Draft'}</span></td>
      <td>${p.created_at?.slice(0,10)||'—'}</td>
    </tr>`).join('')}</tbody></table>`;
}

// ── Posts ─────────────────────────────────────────────────────────────────
async function loadPosts() {
  if (!allPostsData.length) {
    const d = await api('get_posts&all=1');
    allPostsData = d.posts || [];
  }
  renderPostsTable();
}

function renderPostsTable() {
  const el = document.getElementById('postsTable');
  if (!allPostsData.length) { el.innerHTML = '<p class="empty-state">No posts yet. Create your first!</p>'; return; }
  el.innerHTML = `<table class="data-table"><thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>${allPostsData.map(p => `<tr>
      <td><strong>${esc(p.title)}</strong></td>
      <td><span class="badge badge-${p.post_type}">${p.post_type}</span></td>
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
  const post = allPostsData.find(p => p.id == id);
  if (!post) return;
  editingPostId = id;
  document.getElementById('postModalTitle').textContent = 'Edit Post';
  document.getElementById('postTitle').value = post.title;
  document.getElementById('postType').value = post.post_type;
  document.getElementById('postPublished').value = post.is_published;
  document.getElementById('postImagePath').value = post.image_path || '';
  document.getElementById('postSaveMsg').style.display = 'none';
  if (post.image_path) {
    document.getElementById('postPreviewImg').src = post.image_path;
    document.getElementById('postImagePreview').style.display = 'block';
    document.getElementById('postUploadZone').style.display = 'none';
  } else {
    document.getElementById('postImagePreview').style.display = 'none';
    document.getElementById('postUploadZone').style.display = 'flex';
  }
  if (postQuill) postQuill.clipboard.dangerouslyPasteHTML(post.content || '');
  openModal('postModal');
}

document.getElementById('savePostBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('savePostBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const data = {
    title:        document.getElementById('postTitle').value,
    content:      postQuill ? postQuill.root.innerHTML : '',
    image_path:   document.getElementById('postImagePath').value,
    post_type:    document.getElementById('postType').value,
    is_published: parseInt(document.getElementById('postPublished').value),
  };
  if (editingPostId) data.id = editingPostId;
  const action = editingPostId ? 'update_post' : 'create_post';
  const d = await api(action, data);
  const msg = document.getElementById('postSaveMsg');
  if (d.success) {
    msg.className = 'panel-alert alert-success'; msg.textContent = '✓ Post saved!'; msg.style.display = 'block';
    allPostsData = [];
    await loadPosts();
    loadOverview();
    setTimeout(closeModals, 1200);
  } else {
    msg.className = 'panel-alert alert-error'; msg.textContent = d.error || 'Save failed.'; msg.style.display = 'block';
  }
  btn.disabled = false; btn.textContent = 'Save Post';
});

async function deletePost(id) {
  if (!confirm('Delete this post?')) return;
  const d = await api('delete_post', { id });
  if (d.success) { allPostsData = []; loadPosts(); loadOverview(); }
}

// ── Events ────────────────────────────────────────────────────────────────
async function loadEvents() {
  if (!allEventsData.length) {
    const d = await api('get_events&all=1');
    allEventsData = d.events || [];
  }
  const el = document.getElementById('eventsTable');
  if (!allEventsData.length) { el.innerHTML = '<p class="empty-state">No events found.</p>'; return; }
  el.innerHTML = `<table class="data-table"><thead><tr><th>Event</th><th>Date</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>${allEventsData.map(ev => `<tr>
      <td><strong>${esc(ev.title)}</strong>${ev.title_tamil?'<br><small>'+esc(ev.title_tamil)+'</small>':''}</td>
      <td>${ev.event_date||'TBD'}</td>
      <td><span class="badge badge-${ev.is_upcoming?'upcoming':'past'}">${ev.is_upcoming?'Upcoming':'Past'}</span></td>
      <td><span class="status-badge status-${ev.is_published?'confirmed':'draft'}">${ev.is_published?'Published':'Hidden'}</span></td>
      <td class="action-btns">
        <button class="btn-sm btn-outline" onclick="editEvent(${ev.id})"><i class="bi bi-pencil"></i> Edit</button>
        ${!ev.is_upcoming ? `<button class="btn-sm btn-outline" onclick="openPhotoModal(${ev.id})"><i class="bi bi-images"></i> Photos</button>` : ''}
        ${canViewForms && ev.is_upcoming ? `<button class="btn-sm btn-outline" onclick="openEventForms(${ev.id},'${esc(ev.title).replace(/'/g,"\\'")}' )" title="Volunteer/Performer Forms"><i class="bi bi-person-lines-fill"></i> Forms</button>` : ''}
      </td>
    </tr>`).join('')}</tbody></table>`;
}

function editEvent(id) {
  const ev = allEventsData.find(e => e.id == id);
  if (!ev) return;
  editingEventId = id;
  document.getElementById('editEventTitle').textContent = ev.title;
  document.getElementById('editEventId').value = id;
  document.getElementById('editEventImagePath').value = ev.image_path || '';
  document.getElementById('eventSaveMsg').style.display = 'none';
  if (ev.image_path) {
    document.getElementById('eventPreviewImg').src = ev.image_path;
    document.getElementById('eventImagePreview').style.display = 'block';
    document.getElementById('eventUploadZone').style.display = 'none';
  } else {
    document.getElementById('eventImagePreview').style.display = 'none';
    document.getElementById('eventUploadZone').style.display = 'flex';
  }
  if (eventQuill) eventQuill.clipboard.dangerouslyPasteHTML(ev.description || '');
  openModal('eventModal');
}

document.getElementById('saveEventBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('saveEventBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const d = await api('update_event', {
    id:          editingEventId,
    description: eventQuill ? eventQuill.root.innerHTML : '',
    image_path:  document.getElementById('editEventImagePath').value,
  });
  const msg = document.getElementById('eventSaveMsg');
  if (d.success) {
    msg.className = 'panel-alert alert-success'; msg.textContent = '✓ Event updated!'; msg.style.display = 'block';
    allEventsData = [];
    await loadEvents();
    setTimeout(closeModals, 1200);
  } else {
    msg.className = 'panel-alert alert-error'; msg.textContent = d.error || 'Save failed.'; msg.style.display = 'block';
  }
  btn.disabled = false; btn.textContent = 'Save Changes';
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
  await api('toggle_slideshow_photo', { id });
  loadSlideshowPhotos();
}

async function deleteSlidePhoto(id) {
  if (!confirm('Remove this photo from the slideshow?')) return;
  const d = await api('delete_slideshow_photo', { id });
  if (d.success) { allSlidePhotos = []; loadSlideshowPhotos(); }
}

document.getElementById('slideshowUploadInput')?.addEventListener('change', async function() {
  const files = Array.from(this.files);
  if (!files.length) return;
  const msg = document.getElementById('slideshowMsg');
  msg.className = 'panel-alert'; msg.textContent = `Uploading ${files.length} photo(s)…`; msg.style.display = 'block';
  let ok = 0, fail = 0;
  for (const file of files) {
    const fd = new FormData();
    fd.append('photo', file);
    const r = await fetch('api.php?action=upload_slideshow_photo', { method:'POST', body:fd });
    const d = await r.json();
    if (d.success) ok++; else fail++;
  }
  msg.className = fail ? 'panel-alert alert-error' : 'panel-alert alert-success';
  msg.textContent = `✓ ${ok} uploaded${fail ? `, ${fail} failed` : ''}.`;
  this.value = '';
  loadSlideshowPhotos();
  setTimeout(() => msg.style.display='none', 4000);
});

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
  document.getElementById('memberNameEn').value    = m.name_english || '';
  document.getElementById('memberNameTa').value    = m.name_tamil   || '';
  document.getElementById('memberRoleEn').value    = m.role_english || '';
  document.getElementById('memberRoleTa').value    = m.role_tamil   || '';
  document.getElementById('memberOrder').value     = m.display_order || 0;
  document.getElementById('memberPhotoPath').value = m.photo_path   || '';
  const prev = document.getElementById('memberPhotoPreview');
  if (m.photo_path) { prev.src = m.photo_path; prev.style.display = 'block'; } else { prev.style.display = 'none'; }
  openModal('memberModal');
}

async function deleteMember(id) {
  if (!confirm('Delete this committee member?')) return;
  const d = await api('delete_committee_member', { id });
  if (d.success) { allMembers = []; await loadCommitteeMembers(); }
}

document.getElementById('newMemberBtn')?.addEventListener('click', openNewMember);

document.getElementById('memberPhotoInput')?.addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('photo', file);
  const r = await fetch('api.php?action=upload_committee_photo', { method:'POST', body:fd });
  const d = await r.json();
  if (d.success) {
    document.getElementById('memberPhotoPath').value = d.path;
    const prev = document.getElementById('memberPhotoPreview');
    prev.src = d.path; prev.style.display = 'block';
  } else { alert(d.error || 'Upload failed'); }
});

document.getElementById('saveMemberBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('saveMemberBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const data = {
    id:            editingMemberId || 0,
    name_english:  document.getElementById('memberNameEn').value.trim(),
    name_tamil:    document.getElementById('memberNameTa').value.trim(),
    role_english:  document.getElementById('memberRoleEn').value.trim(),
    role_tamil:    document.getElementById('memberRoleTa').value.trim(),
    display_order: parseInt(document.getElementById('memberOrder').value) || 0,
    photo_path:    document.getElementById('memberPhotoPath').value.trim(),
  };
  const d = await api('save_committee_member', data);
  const msg = document.getElementById('memberSaveMsg');
  if (d.success) {
    msg.className = 'panel-alert alert-success'; msg.textContent = '✓ Saved!'; msg.style.display = 'block';
    allMembers = []; await loadCommitteeMembers();
    setTimeout(closeModals, 1000);
  } else {
    msg.className = 'panel-alert alert-error'; msg.textContent = d.error || 'Failed.'; msg.style.display = 'block';
  }
  btn.disabled = false; btn.textContent = 'Save Member';
});

// ── Site Content ──────────────────────────────────────────────────────────
const CONTENT_SECTIONS = [
  { group: '🏠 Home Page' },
  { key:'home_section_heading', label:'Section Heading (Tamil)',   icon:'bi-type-h1' },
  { key:'home_welcome_tamil',   label:'Welcome Text — Tamil',      icon:'bi-translate' },
  { key:'home_welcome_english', label:'Welcome Text — English',    icon:'bi-type' },
  { group: '👁 Vision & Values' },
  { key:'vision_mission',        label:'Our Mission',              icon:'bi-bullseye' },
  { key:'vision_purpose',        label:'Our Purpose & Activities', icon:'bi-heart' },
  { key:'vision_looking_forward',label:'Looking Forward',          icon:'bi-compass' },
  { group: '🤝 Membership' },
  { key:'membership_hero_subtitle',  label:'Hero Subtitle',            icon:'bi-type' },
  { key:'membership_benefits_intro', label:'Benefits Intro',           icon:'bi-list-ul' },
  { key:'membership_benefit_events', label:'Benefit — Events',         icon:'bi-ticket-perforated' },
  { key:'membership_benefit_movies', label:'Benefit — Movies',         icon:'bi-film' },
  { key:'membership_benefit_voting', label:'Benefit — Voting Rights',  icon:'bi-person-check' },
  { key:'membership_note',           label:'Membership Note',          icon:'bi-info-circle' },
  { key:'membership_pricing_intro',  label:'Pricing Intro',            icon:'bi-currency-dollar' },
  { key:'membership_cta_note',       label:'Registration CTA Note',    icon:'bi-arrow-right-circle' },
  { group: '📞 Contact' },
  { key:'contact_hero_subtitle', label:'Hero Subtitle',           icon:'bi-type' },
  { key:'contact_email_card',    label:'Email Card Text',          icon:'bi-envelope' },
  { key:'contact_social_card',   label:'Social Media Card Text',   icon:'bi-chat-dots' },
  { group: '👥 Committee' },
  { key:'committee_intro',       label:'Committee Page Intro',     icon:'bi-people' },
];

async function loadSiteContent() {
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
      siteEditors[key] = new Quill(el, { theme:'snow', modules:{ toolbar:[['bold','italic','underline'],['link'],[{'list':'ordered'},{'list':'bullet'}],['clean']] }});
    });
    document.querySelectorAll('#siteContentSections .save-section-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const key = btn.dataset.key;
        const quill = siteEditors[key];
        if (!quill) return;
        btn.disabled = true; btn.textContent = 'Saving…';
        const r = await api('update_site_content', { section_key: key, content_html: quill.root.innerHTML });
        const msg = document.getElementById('contentSaveMsg');
        msg.className = r.success ? 'panel-alert alert-success' : 'panel-alert alert-error';
        msg.textContent = r.success ? `✓ "${key}" saved!` : (r.error || 'Save failed.');
        msg.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Save';
        setTimeout(() => { msg.style.display='none'; }, 3000);
      });
    });
  }
  if (d.success) {
    Object.entries(siteEditors).forEach(([key, quill]) => {
      if (d.content[key]) quill.clipboard.dangerouslyPasteHTML(d.content[key].content_html || '');
    });
  }
}

// ── Image uploaders ───────────────────────────────────────────────────────
function setupUploader(zoneId, fileInputId, previewId, previewImgId, removeId, pathInputId, uploadDir) {
  const zone     = document.getElementById(zoneId);
  const fileInp  = document.getElementById(fileInputId);
  const preview  = document.getElementById(previewId);
  const previewI = document.getElementById(previewImgId);
  const removeB  = document.getElementById(removeId);
  const pathInp  = document.getElementById(pathInputId);

  zone?.addEventListener('click', () => fileInp.click());
  zone?.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone?.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone?.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('drag-over'); if (e.dataTransfer.files[0]) uploadImage(e.dataTransfer.files[0]); });
  fileInp?.addEventListener('change', () => { if (fileInp.files[0]) uploadImage(fileInp.files[0]); });
  removeB?.addEventListener('click', () => { pathInp.value = ''; preview.style.display='none'; zone.style.display='flex'; });

  async function uploadImage(file) {
    zone.innerHTML = '<div class="upload-spinner"><i class="bi bi-arrow-repeat spin"></i> Uploading…</div>';
    const fd = new FormData();
    fd.append('image', file);
    fd.append('upload_dir', uploadDir);
    const r = await fetch('api.php?action=upload_image', { method:'POST', body: fd });
    const d = await r.json();
    if (d.success) {
      pathInp.value    = d.path;
      previewI.src     = d.path;
      preview.style.display = 'block';
      zone.style.display    = 'none';
      zone.innerHTML = `<i class="bi bi-cloud-upload"></i><p>Drag & drop or click to upload</p><small>JPEG, PNG, WebP · max 8 MB</small>`;
    } else {
      zone.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i><p>${d.error}</p>`;
      setTimeout(() => { zone.innerHTML = '<i class="bi bi-cloud-upload"></i><p>Drag & drop or click to upload</p><small>JPEG, PNG, WebP · max 8 MB</small>'; }, 3000);
    }
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
['closePostModal','cancelPostBtn','closeMemberModal','cancelMemberBtn','closeEventModal','cancelEventBtn'].forEach(id => document.getElementById(id)?.addEventListener('click', closeModals));
document.getElementById('closePhotoModal')?.addEventListener('click', closeModals);
document.getElementById('closePhotoModalBtn')?.addEventListener('click', closeModals);
document.getElementById('savePhotoCaptionsBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('savePhotoCaptionsBtn');
  btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
  await saveAllCaptions();
  closeModals();
});
document.getElementById('modalBackdrop')?.addEventListener('click', closeModals);
document.getElementById('newPostBtn')?.addEventListener('click', openNewPost);

// ── Logout ────────────────────────────────────────────────────────────────
document.getElementById('logoutBtn')?.addEventListener('click', async e => {
  e.preventDefault();
  const d = await (await fetch('api.php?action=logout',{method:'POST'})).json();
  if (d.success) window.location.href = d.redirect || 'index.php';
});

function esc(s) { const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }
function switchTab(tab) {
  document.querySelectorAll('.sidebar-link[data-tab]').forEach(l=>l.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelector(`.sidebar-link[data-tab="${tab}"]`)?.classList.add('active');
  document.getElementById('tab-'+tab)?.classList.add('active');
  const loaders = { posts:loadPosts, events:loadEvents, committee:loadCommitteeMembers, slideshow:loadSlideshowPhotos, sitecontent:loadSiteContent, members:loadMemberAccounts };
  loaders[tab]?.();
}

// ── Init Quill editors ────────────────────────────────────────────────────
const quillOptions = { theme:'snow', modules:{ toolbar:[
  ['bold','italic','underline'],
  [{'header':[1,2,3,false]}],
  [{'list':'ordered'},{'list':'bullet'}],
  ['link'],
  ['clean'],
]}};

window.addEventListener('DOMContentLoaded', () => {
  postQuill  = new Quill('#postContentEditor', quillOptions);
  eventQuill = new Quill('#eventDescEditor', quillOptions);

  setupUploader('postUploadZone','postImageFile','postImagePreview','postPreviewImg','removePostImage','postImagePath','posts');
  setupUploader('eventUploadZone','eventImageFile','eventImagePreview','eventPreviewImg','removeEventImage','editEventImagePath','events');

  const initTab = new URLSearchParams(location.search).get('tab');
  if (initTab && document.getElementById('tab-' + initTab)) switchTab(initTab);
  else loadOverview();
  initPhotoModal();
});

// ── Member Accounts (Membership Coordinator) ──────────────────────────────
let allMemberAccounts = [], editingMaId = null;

async function loadMemberAccounts() {
  const d = await api('get_members_list');
  allMemberAccounts = d.users || [];
  filterMembersList();
}

function filterMembersList() {
  const q      = (document.getElementById('memberSearch')?.value || '').toLowerCase();
  const status = document.getElementById('memberStatusFilter')?.value || '';
  const list   = allMemberAccounts.filter(u =>
    (!q      || u.email.toLowerCase().includes(q) || (u.first_name+' '+u.last_name).toLowerCase().includes(q)) &&
    (!status || (u.account_status || 'active') === status)
  );
  const el = document.getElementById('memberAccountsTable');
  if (!list.length) {
    el.innerHTML = '<p class="empty-state">No members found.</p>'; return;
  }
  el.innerHTML = `<table class="data-table"><thead><tr>
    <th>Name</th><th>Email</th><th>Membership</th><th>Account</th><th>Actions</th>
  </tr></thead><tbody>${list.map(u => {
    const acct   = u.account_status || 'active';
    const acctBg = acct === 'inactive' ? 'background:#fee2e2;color:#991b1b' : 'background:#d1fae5;color:#065f46';
    return `<tr style="${acct==='inactive'?'opacity:.65':''}">
      <td><strong>${esc(u.first_name+' '+u.last_name)}</strong></td>
      <td style="font-size:.85rem;color:var(--text-muted)">${esc(u.email)}</td>
      <td><span style="font-size:.8rem;padding:2px 8px;border-radius:99px;background:#e0f2fe;color:#0369a1">${esc(u.membership_status||'none')}</span></td>
      <td><span style="font-size:.8rem;padding:2px 8px;border-radius:99px;${acctBg}">${acct.toUpperCase()}</span></td>
      <td class="action-btns">
        <button class="btn-sm btn-outline" onclick="openEditMa(${u.id})"><i class="bi bi-pencil"></i></button>
        ${acct==='active'
          ? `<button class="btn-sm btn-danger" onclick="setMaStatus(${u.id},'inactive')" title="Deactivate"><i class="bi bi-person-slash"></i></button>`
          : `<button class="btn-sm btn-outline" onclick="setMaStatus(${u.id},'active')" title="Reactivate"><i class="bi bi-person-check"></i></button>`}
      </td>
    </tr>`;
  }).join('')}</tbody></table>`;
}

function openNewMa() {
  editingMaId = null;
  document.getElementById('memberAccountModalTitle').textContent = 'Add Member';
  document.getElementById('memberAccountMsg').style.display = 'none';
  document.getElementById('maId').value = '';
  document.getElementById('maFirst').value = '';
  document.getElementById('maLast').value  = '';
  document.getElementById('maEmail').value = '';
  document.getElementById('maPhone').value = '';
  document.getElementById('maMembershipStatus').value = 'none';
  document.getElementById('maMembershipExpiry').value = '';
  document.getElementById('maPassword').value = '';
  document.getElementById('maPasswordGroup').style.display = '';
  openModal('memberAccountModal');
}

function openEditMa(id) {
  const u = allMemberAccounts.find(x => x.id == id); if (!u) return;
  editingMaId = id;
  document.getElementById('memberAccountModalTitle').textContent = 'Edit Member';
  document.getElementById('memberAccountMsg').style.display = 'none';
  document.getElementById('maId').value  = u.id;
  document.getElementById('maFirst').value = u.first_name;
  document.getElementById('maLast').value  = u.last_name;
  document.getElementById('maEmail').value = u.email;
  document.getElementById('maPhone').value = u.phone || '';
  document.getElementById('maMembershipStatus').value  = u.membership_status || 'none';
  document.getElementById('maMembershipExpiry').value  = u.membership_expiry || '';
  document.getElementById('maPasswordGroup').style.display = 'none';
  openModal('memberAccountModal');
}

async function setMaStatus(id, status) {
  const label = status === 'inactive' ? 'deactivate' : 'reactivate';
  if (!confirm(`Are you sure you want to ${label} this account? No data will be deleted.`)) return;
  const d = await api('set_account_status', { id, status });
  if (d.success) { allMemberAccounts = []; loadMemberAccounts(); }
}

document.getElementById('newMemberAccountBtn')?.addEventListener('click', openNewMa);
document.getElementById('cancelMemberAccountBtn')?.addEventListener('click', closeModals);
document.getElementById('closeMemberAccountModal')?.addEventListener('click', closeModals);
document.getElementById('saveMemberAccountBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('saveMemberAccountBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const msg  = document.getElementById('memberAccountMsg');
  const isNew = !editingMaId;
  const payload = {
    id:                editingMaId || 0,
    first_name:        document.getElementById('maFirst').value.trim(),
    last_name:         document.getElementById('maLast').value.trim(),
    email:             document.getElementById('maEmail').value.trim(),
    phone:             document.getElementById('maPhone').value.trim(),
    membership_status: document.getElementById('maMembershipStatus').value,
    membership_expiry: document.getElementById('maMembershipExpiry').value || null,
  };
  if (isNew) payload.password = document.getElementById('maPassword').value;
  const d = await api('mem_coord_save_user', payload);
  if (d.success) {
    msg.className = 'panel-alert alert-success'; msg.textContent = '✓ Saved!'; msg.style.display = 'block';
    allMemberAccounts = []; loadMemberAccounts(); setTimeout(closeModals, 800);
  } else {
    msg.className = 'panel-alert alert-error'; msg.textContent = d.error || 'Failed.'; msg.style.display = 'block';
  }
  btn.disabled = false; btn.textContent = 'Save';
});

// ── Photo Modal ───────────────────────────────────────────────────────────
let currentPhotoEventId = null;
let currentPhotos = [];
let dirtyCaptions = {};

function initPhotoModal() {
  const zone  = document.getElementById('photoUploadZone');
  const input = document.getElementById('photoFileInput');
  if (!zone || !input) return;

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag-over');
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
  const ev = allEventsData.find(e => String(e.id) === String(eventId));
  document.getElementById('photoModalEventTitle').textContent = ev?.title || '';
  document.getElementById('uploadQueue').innerHTML = '';
  document.getElementById('photoProgressBar').style.display = 'none';
  document.getElementById('photoSaveMsg').style.display = 'none';
  openModal('photoModal');
  await loadEventPhotos();
}

async function loadEventPhotos() {
  const grid = document.getElementById('photoManageGrid');
  grid.innerHTML = '<p class="loading-text" style="grid-column:1/-1">Loading…</p>';
  const d = await api('get_event_photos&event_id=' + currentPhotoEventId);
  currentPhotos = d.photos || [];
  renderPhotoGrid();
}

function renderPhotoGrid() {
  const grid  = document.getElementById('photoManageGrid');
  const badge = document.getElementById('photoCountBadge');
  const n = currentPhotos.length;
  badge.innerHTML = `<i class="bi bi-images"></i> ${n} / 200 photos`;
  badge.className = 'photo-count-badge' + (n >= 200 ? ' at-limit' : '');

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
  const d = await api('delete_event_photo', { id: photoId });
  if (d.success) await loadEventPhotos();
  else alert(d.error || 'Delete failed.');
}

async function saveAllCaptions() {
  const ids = Object.keys(dirtyCaptions);
  if (!ids.length) return;
  await Promise.all(ids.map(id => api('update_photo_caption', { id: parseInt(id), caption: dirtyCaptions[id] })));
  dirtyCaptions = {};
}

async function handlePhotoUploadQueue(files) {
  const remaining = 200 - currentPhotos.length;
  if (remaining <= 0) { showPhotoMsg('Maximum 200 photos per event reached.', false); return; }

  const toUpload = files.slice(0, remaining);
  const queue = document.getElementById('uploadQueue');
  const bar   = document.getElementById('photoProgressBar');
  const fill  = document.getElementById('photoProgressFill');

  queue.innerHTML = toUpload.map((f, i) =>
    `<li class="upload-queue-item" id="qitem-${i}">
      <i class="bi bi-hourglass-split status-icon pending"></i>
      <span>${esc(f.name)}</span>
     </li>`
  ).join('');

  bar.style.display = 'block';
  let done = 0;

  for (let i = 0; i < toUpload.length; i++) {
    const f = toUpload[i];
    const item = document.getElementById('qitem-' + i);
    const icon = item?.querySelector('.status-icon');
    if (icon) icon.className = 'bi bi-arrow-repeat spin status-icon pending';

    try {
      const compressed = await compressImage(f);
      const fd = new FormData();
      fd.append('photo', compressed);
      fd.append('event_id', currentPhotoEventId);
      const r = await fetch('api.php?action=upload_event_photo', { method: 'POST', body: fd });
      const d = await r.json();
      if (icon) icon.className = d.success ? 'bi bi-check-circle-fill status-icon done' : 'bi bi-x-circle-fill status-icon error';
      if (!d.success && item) item.innerHTML += ` <small style="color:var(--red)">${esc(d.error||'Failed')}</small>`;
    } catch(e) {
      if (icon) icon.className = 'bi bi-x-circle-fill status-icon error';
    }

    done++;
    fill.style.width = Math.round((done / toUpload.length) * 100) + '%';
  }

  await loadEventPhotos();
  setTimeout(() => { queue.innerHTML=''; bar.style.display='none'; fill.style.width='0%'; }, 2500);
}

function showPhotoMsg(msg, success) {
  const el = document.getElementById('photoSaveMsg');
  el.className = 'panel-alert ' + (success ? 'alert-success' : 'alert-error');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => el.style.display='none', 3000);
}

// ── Event Forms ───────────────────────────────────────────────────────────
<?php if ($canViewForms): ?>
let currentFormsEventId = null, currentFormType = null, currentFormId = null, editingQId = null;

async function openEventForms(eventId, eventTitle) {
  currentFormsEventId = eventId;
  document.getElementById('eventFormsTitle').textContent = eventTitle;
  document.getElementById('eventFormsMsg').style.display = 'none';
  document.getElementById('eventFormsGrid').innerHTML = '<p class="loading-text" style="grid-column:1/-1">Loading…</p>';
  openModal('eventFormsModal');
  await refreshEventFormsGrid();
}

async function refreshEventFormsGrid() {
  const d = await api(`get_event_forms&event_id=${currentFormsEventId}`);
  const forms = d.forms || [];
  const byType = {};
  forms.forEach(f => byType[f.form_type] = f);
  const grid = document.getElementById('eventFormsGrid');
  grid.innerHTML = ['volunteer','performer'].map(type => {
    const f = byType[type];
    const icon  = type === 'volunteer' ? 'bi-hand-raised-fill' : 'bi-music-note-beamed';
    const label = type === 'volunteer' ? 'Volunteer Sign-Up' : 'Performer Application';
    const color = type === 'volunteer' ? '#065f46' : '#92400e';
    const bg    = type === 'volunteer' ? '#d1fae5' : '#fef3c7';
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
          ${canManageForms ? `<button class="btn-sm btn-outline" onclick="openFormBuilder(${currentFormsEventId},'${type}',${f.id})"><i class="bi bi-pencil"></i> Edit Form</button>` : ''}
          <button class="btn-sm btn-outline" onclick="openSubmissions(${f.id}, '${esc(f.title)}')"><i class="bi bi-list-ul"></i> Submissions</button>
          ${canManageForms ? `<button class="btn-sm btn-danger" onclick="deleteEventFormConfirm(${f.id},'${type}')"><i class="bi bi-trash"></i></button>` : ''}
        </div>
      </div>`;
    } else {
      return `<div class="panel-card" style="margin:0;border:2px dashed var(--border);background:#fafaf8">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <span style="background:${bg};color:${color};border-radius:8px;padding:6px 10px;font-size:1.2rem;opacity:.5"><i class="bi ${icon}"></i></span>
          <div><strong>${label}</strong><div style="font-size:.8rem;color:#9ca3af">No form created</div></div>
        </div>
        ${canManageForms ? `<button class="btn-sm btn-primary" onclick="openFormBuilder(${currentFormsEventId},'${type}',null)"><i class="bi bi-plus-lg"></i> Create Form</button>` : '<p style="font-size:.85rem;color:#9ca3af">No form created yet.</p>'}
      </div>`;
    }
  }).join('');
}

<?php if ($canForms): ?>
async function openFormBuilder(eventId, formType, formId) {
  currentFormType = formType;
  currentFormId   = formId;
  const label = formType === 'volunteer' ? 'Volunteer Sign-Up' : 'Performer Application';
  document.getElementById('formBuilderTitle').textContent = (formId ? 'Edit' : 'Create') + ' — ' + label;
  document.getElementById('formBuilderMsg').style.display = 'none';
  document.getElementById('fbEventId').value  = eventId;
  document.getElementById('fbFormType').value = formType;
  document.getElementById('fbFormId').value   = formId || '';
  if (formId) {
    const d = await api(`get_event_form_detail&form_id=${formId}`);
    const f = d.form || {};
    document.getElementById('fbTitle').value    = f.title || label;
    document.getElementById('fbDesc').value     = f.description || '';
    document.getElementById('fbActive').value   = f.is_active ?? 1;
    document.getElementById('fbDeadline').value = f.deadline ? f.deadline.replace(' ','T').slice(0,16) : '';
    document.getElementById('fbMax').value      = f.max_submissions || 0;
    renderQuestionsList(d.questions || []);
  } else {
    document.getElementById('fbTitle').value    = label;
    document.getElementById('fbDesc').value     = '';
    document.getElementById('fbActive').value   = '1';
    document.getElementById('fbDeadline').value = '';
    document.getElementById('fbMax').value      = '0';
    renderQuestionsList([]);
  }
  document.getElementById('eventFormsModal').classList.remove('active');
  openModal('formBuilderModal');
}

function renderQuestionsList(questions) {
  const el = document.getElementById('questionsList');
  if (!questions.length) { el.innerHTML = '<p class="empty-state">No questions yet — this form just collects a name/sign-up.</p>'; return; }
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
  btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
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
    msg.className='panel-alert alert-success'; msg.textContent='✓ Saved!'; msg.style.display='block';
    setTimeout(()=>msg.style.display='none',2000);
  } else {
    msg.className='panel-alert alert-error'; msg.textContent=d.error||'Failed.'; msg.style.display='block';
  }
  btn.disabled=false; btn.innerHTML='<i class="bi bi-floppy"></i> Save Form Settings';
});

document.getElementById('addQuestionBtn')?.addEventListener('click', async () => {
  if (!currentFormId) {
    const btn2 = document.getElementById('saveFormSettingsBtn');
    btn2.disabled = true; btn2.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
    const d = await api('save_event_form', {
      event_id: document.getElementById('fbEventId').value,
      form_type: document.getElementById('fbFormType').value,
      title: document.getElementById('fbTitle').value.trim(),
      description: document.getElementById('fbDesc').value.trim(),
      is_active: document.getElementById('fbActive').value,
      deadline: document.getElementById('fbDeadline').value || null,
      max_submissions: document.getElementById('fbMax').value,
    });
    btn2.disabled = false; btn2.innerHTML = '<i class="bi bi-floppy"></i> Save Form Settings';
    if (!d.success) {
      const m = document.getElementById('formBuilderMsg');
      m.className='panel-alert alert-error'; m.textContent=d.error||'Failed to save form.'; m.style.display='block';
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
  document.getElementById('qId').value   = q.id;
  document.getElementById('qText').value = q.question_text;
  document.getElementById('qType').value = q.input_type;
  document.getElementById('qRequired').checked = !!+q.is_required;
  document.getElementById('qWordLimit').value = q.word_limit || 0;
  document.getElementById('qCharLimit').value = q.char_limit || 0;
  document.getElementById('qOptions').value   = (q.options || []).join('\n');
  document.getElementById('qOrder').value     = q.display_order || 0;
  toggleQTypeFields();
  document.getElementById('formBuilderModal').classList.remove('active');
  openModal('questionModal');
}

function toggleQTypeFields() {
  const t = document.getElementById('qType').value;
  const needsOptions = ['radio','select','checkbox'].includes(t);
  const needsLimits  = ['text','textarea'].includes(t);
  document.getElementById('qOptionsGroup').style.display = needsOptions ? '' : 'none';
  document.getElementById('qLimitsRow').style.display    = needsLimits  ? '' : 'none';
}

document.getElementById('saveQuestionBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('saveQuestionBtn');
  btn.disabled=true; btn.textContent='Saving…';
  const type = document.getElementById('qType').value;
  const needsOptions = ['radio','select','checkbox'].includes(type);
  const d = await api('save_form_question', {
    id: editingQId || 0,
    form_id: currentFormId,
    question_text: document.getElementById('qText').value.trim(),
    input_type: type,
    options: needsOptions ? document.getElementById('qOptions').value.split('\n').map(s=>s.trim()).filter(Boolean) : [],
    word_limit: document.getElementById('qWordLimit').value,
    char_limit: document.getElementById('qCharLimit').value,
    is_required: document.getElementById('qRequired').checked ? 1 : 0,
    display_order: document.getElementById('qOrder').value,
  });
  const msg = document.getElementById('questionMsg');
  if (d.success) {
    msg.className='panel-alert alert-success'; msg.textContent='✓ Saved!'; msg.style.display='block';
    const fd = await api(`get_event_form_detail&form_id=${currentFormId}`);
    renderQuestionsList(fd.questions || []);
    setTimeout(() => { msg.style.display='none'; closeModals(); openModal('formBuilderModal'); }, 700);
  } else {
    msg.className='panel-alert alert-error'; msg.textContent=d.error||'Failed.'; msg.style.display='block';
  }
  btn.disabled=false; btn.textContent='Save Question';
});

async function deleteQuestion(id) {
  if (!confirm('Delete this question? Existing answers will also be removed.')) return;
  await api('delete_form_question', { id });
  const fd = await api(`get_event_form_detail&form_id=${currentFormId}`);
  renderQuestionsList(fd.questions || []);
}

document.getElementById('cancelQuestionBtn')?.addEventListener('click', () => { closeModals(); openModal('formBuilderModal'); });
document.getElementById('closeQuestionModal')?.addEventListener('click', () => { closeModals(); openModal('formBuilderModal'); });
document.getElementById('backToFormsBtn')?.addEventListener('click', () => { closeModals(); openModal('eventFormsModal'); refreshEventFormsGrid(); });
document.getElementById('closeEventFormsModal')?.addEventListener('click', closeModals);
document.getElementById('closeEventFormsBtn')?.addEventListener('click', closeModals);
document.getElementById('closeFormBuilderModal')?.addEventListener('click', closeModals);

async function deleteEventFormConfirm(id, type) {
  if (!confirm(`Delete the ${type} form and ALL its submissions? This cannot be undone.`)) return;
  await api('delete_event_form', { id });
  await refreshEventFormsGrid();
}
<?php endif; // $canForms ?>

async function openSubmissions(formId, formTitle) {
  document.getElementById('submissionsModalTitle').textContent = 'Submissions — ' + formTitle;
  document.getElementById('submissionsTable').innerHTML = '<p class="loading-text">Loading…</p>';
  document.getElementById('formBuilderModal')?.classList.remove('active');
  openModal('submissionsModal');
  const d = await api(`get_form_submissions&form_id=${formId}`);
  const subs = d.submissions || [];
  document.getElementById('submissionCount').innerHTML = `<i class="bi bi-people"></i> ${subs.length} submission${subs.length!=1?'s':''}`;
  if (!subs.length) { document.getElementById('submissionsTable').innerHTML = '<p class="empty-state">No submissions yet.</p>'; return; }
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
    </div>
  `).join('');
}

async function updateSubStatus(id, status) {
  await api('update_submission_status', { id, status });
}

document.getElementById('closeSubmissionsModal')?.addEventListener('click', closeModals);
document.getElementById('backToBuilderBtn')?.addEventListener('click', () => { closeModals(); openModal('formBuilderModal'); });
document.getElementById('backToFormsFromSubBtn')?.addEventListener('click', () => { closeModals(); openModal('eventFormsModal'); refreshEventFormsGrid(); });
document.getElementById('viewSubmissionsBtn')?.addEventListener('click', async () => {
  if (!currentFormId) return;
  const d = await api(`get_event_form_detail&form_id=${currentFormId}`);
  await openSubmissions(currentFormId, d.form?.title || 'Form');
});
<?php endif; // $canViewForms ?>

function escAttr(s) { return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Compress an image File to a Blob under maxBytes using Canvas.
// Resizes large dimensions first, then iteratively lowers JPEG quality.
function compressImage(file, maxBytes = 9 * 1024 * 1024, maxDim = 3840) {
  return new Promise((resolve) => {
    if (file.size <= maxBytes && file.type !== 'image/png') { resolve(file); return; }
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      URL.revokeObjectURL(url);
      let { width, height } = img;
      if (width > maxDim || height > maxDim) {
        const scale = maxDim / Math.max(width, height);
        width = Math.round(width * scale);
        height = Math.round(height * scale);
      }
      const canvas = document.createElement('canvas');
      canvas.width = width; canvas.height = height;
      canvas.getContext('2d').drawImage(img, 0, 0, width, height);
      let quality = 0.92;
      const tryEncode = () => {
        canvas.toBlob(blob => {
          if (!blob) { resolve(file); return; }
          if (blob.size <= maxBytes || quality <= 0.30) {
            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' }));
          } else {
            quality = Math.max(0.30, quality - 0.10);
            tryEncode();
          }
        }, 'image/jpeg', quality);
      };
      tryEncode();
    };
    img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
    img.src = url;
  });
}

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
