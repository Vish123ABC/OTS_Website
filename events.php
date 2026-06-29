<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ots_session();
$currentUser = getCurrentUser();
$db = getDB();

// Single event view
$eventId = (int)($_GET['id'] ?? 0);
if ($eventId) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id=? AND is_published=1 LIMIT 1");
    $stmt->execute([$eventId]);
    $singleEvent = $stmt->fetch();
}

include_once "header.php";
?>

<main>
  <?php if (!empty($singleEvent)): ?>

    <?php if (!$singleEvent['is_upcoming']): ?>
    <!-- ── PAST EVENT: Photo Gallery View ────────────────────────────────── -->
    <section class="event-gallery-section">
      <div class="gallery-header">
        <div class="container">
          <a href="events.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Events</a>
          <h1><?= e($singleEvent['title']) ?></h1>
          <?php if ($singleEvent['title_tamil']): ?>
          <p class="gallery-title-tamil"><?= e($singleEvent['title_tamil']) ?></p>
          <?php endif; ?>
          <div class="gallery-meta-pills">
            <?php if ($singleEvent['event_date']): ?>
            <span class="gallery-pill"><i class="bi bi-calendar3"></i> <?= date('F j, Y', strtotime($singleEvent['event_date'])) ?></span>
            <?php endif; ?>
            <?php if ($singleEvent['event_time']): ?>
            <span class="gallery-pill"><i class="bi bi-clock"></i> <?= e($singleEvent['event_time']) ?></span>
            <?php endif; ?>
            <?php if ($singleEvent['location']): ?>
            <span class="gallery-pill"><i class="bi bi-geo-alt"></i> <?= e($singleEvent['location']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="container">
        <div class="gallery-count-bar">
          <span id="galleryCount" class="gallery-count">Loading photos…</span>
          <span class="past-event-badge"><i class="bi bi-archive"></i> Past Event</span>
        </div>

        <!-- Photo grid -->
        <div id="photoGrid" class="photo-grid">
          <!-- Loaded via JS -->
        </div>

        <!-- Empty state -->
        <div id="noPhotosState" class="no-photos-state" style="display:none">
          <div class="no-photos-icon"><i class="bi bi-camera"></i></div>
          <h3>No photos yet for this event</h3>
          <p>Photos from this event will be added soon. Check back later!</p>
        </div>

        <!-- Videos & Links (loaded via JS) -->
        <div id="eventMediaSection" style="display:none;margin-top:32px">
          <h3 style="margin-bottom:16px;font-size:1.15rem;font-weight:700"><i class="bi bi-play-btn"></i> Videos &amp; Links</h3>
          <div id="eventMediaGrid"></div>
        </div>

        <!-- Event description -->
        <?php if ($singleEvent['description']): ?>
        <div class="gallery-description-card">
          <h3><i class="bi bi-info-circle"></i> About This Event</h3>
          <div class="gallery-desc-html"><?= $singleEvent['description'] ?></div>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ── Gallery Lightbox ───────────────────────────────────────────────── -->
    <div id="galleryLightbox" class="gallery-lightbox" style="display:none" role="dialog" aria-modal="true" aria-label="Photo viewer">
      <div class="lightbox-overlay"></div>
      <button class="lightbox-close" id="lightboxClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
      <button class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
      <button class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
      <div class="lightbox-content">
        <img id="lightboxImg" class="lightbox-img" src="" alt="" />
        <div class="lightbox-caption" id="lightboxCaption"></div>
      </div>
      <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>

    <script>
    (function() {
      const eventId = <?= (int)$singleEvent['id'] ?>;
      let photos = [];
      let currentIdx = 0;

      // ── Fetch photos ───────────────────────────────────────────────────────
      async function loadPhotos() {
        const r = await fetch(`api.php?action=get_event_photos&event_id=${eventId}`);
        const d = await r.json();
        if (!d.success) return;
        photos = d.photos || [];

        const grid     = document.getElementById('photoGrid');
        const empty    = document.getElementById('noPhotosState');
        const countEl  = document.getElementById('galleryCount');

        if (!photos.length) {
          grid.style.display   = 'none';
          empty.style.display  = 'block';
          countEl.textContent  = 'No photos yet';
          return;
        }

        const n = photos.length;
        countEl.textContent = n + ' photo' + (n !== 1 ? 's' : '') + ' from this event';

        grid.innerHTML = photos.map((p, i) => `
          <div class="photo-item" data-index="${i}" role="button" tabindex="0" aria-label="View photo ${i+1}">
            <img src="${escAttr(p.photo_url)}" alt="${escAttr(p.caption || 'Event photo')}" loading="lazy" />
            ${p.caption ? `<div class="photo-overlay"><span class="photo-caption-overlay">${esc(p.caption)}</span></div>` : ''}
          </div>
        `).join('');

        // Bind click/key handlers
        grid.querySelectorAll('.photo-item').forEach(el => {
          el.addEventListener('click', () => openLightbox(+el.dataset.index));
          el.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLightbox(+el.dataset.index); }
          });
        });
      }

      // ── Lightbox ───────────────────────────────────────────────────────────
      const lightbox  = document.getElementById('galleryLightbox');
      const lbImg     = document.getElementById('lightboxImg');
      const lbCaption = document.getElementById('lightboxCaption');
      const lbCounter = document.getElementById('lightboxCounter');

      function openLightbox(idx) {
        currentIdx = Math.max(0, Math.min(idx, photos.length - 1));
        renderLightbox();
        lightbox.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        lbImg.focus();
      }

      function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = '';
      }

      function renderLightbox() {
        const p = photos[currentIdx];
        lbImg.src = p.photo_url;
        lbImg.alt = p.caption || 'Event photo';
        lbCaption.textContent = p.caption || '';
        lbCaption.style.display = p.caption ? 'block' : 'none';
        lbCounter.textContent = (currentIdx + 1) + ' / ' + photos.length;
        document.getElementById('lightboxPrev').style.display = currentIdx > 0 ? 'flex' : 'none';
        document.getElementById('lightboxNext').style.display = currentIdx < photos.length - 1 ? 'flex' : 'none';
      }

      document.getElementById('lightboxClose')?.addEventListener('click', closeLightbox);
      document.getElementById('lightboxPrev')?.addEventListener('click', () => { if (currentIdx > 0) { currentIdx--; renderLightbox(); } });
      document.getElementById('lightboxNext')?.addEventListener('click', () => { if (currentIdx < photos.length - 1) { currentIdx++; renderLightbox(); } });
      document.querySelector('.lightbox-overlay')?.addEventListener('click', closeLightbox);

      document.addEventListener('keydown', e => {
        if (lightbox.style.display !== 'flex') return;
        if (e.key === 'Escape')      closeLightbox();
        if (e.key === 'ArrowLeft'  && currentIdx > 0)               { currentIdx--; renderLightbox(); }
        if (e.key === 'ArrowRight' && currentIdx < photos.length - 1) { currentIdx++; renderLightbox(); }
      });

      // ── Media (videos & links) ────────────────────────────────────────────
      async function loadMedia() {
        const r = await fetch(`api.php?action=get_event_media&event_id=${eventId}`);
        const d = await r.json();
        if (!d.success || !d.media || !d.media.length) return;

        document.getElementById('eventMediaSection').style.display = 'block';
        const grid = document.getElementById('eventMediaGrid');

        const youtubeItems = d.media.filter(m => m.type === 'youtube');
        const linkItems    = d.media.filter(m => m.type !== 'youtube');

        let html = '';

        if (youtubeItems.length) {
          html += `<div class="media-video-grid">${youtubeItems.map(m => {
            const ytId = (m.url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|v\/)|youtu\.be\/)([^&\s?]+)/) || [])[1] || '';
            return `<div class="media-video-wrap">
              <iframe
                src="https://www.youtube.com/embed/${escAttr(ytId)}"
                title="${escAttr(m.label || 'YouTube Video')}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
                loading="lazy">
              </iframe>
              ${m.label ? `<p class="media-video-label">${esc(m.label)}</p>` : ''}
            </div>`;
          }).join('')}</div>`;
        }

        if (linkItems.length) {
          html += `<div class="media-links-list">${linkItems.map(m => `
            <a href="${escAttr(m.url)}" target="_blank" rel="noopener" class="media-link-item">
              <i class="bi bi-link-45deg"></i>
              <span>${esc(m.label || m.url)}</span>
              <i class="bi bi-box-arrow-up-right" style="margin-left:auto;font-size:.8rem;opacity:.6"></i>
            </a>`).join('')}</div>`;
        }

        grid.innerHTML = html;
      }

      // ── Escape helpers ────────────────────────────────────────────────────
      function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
      function escAttr(s) { return (s || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }

      loadPhotos();
      loadMedia();
    })();
    </script>

    <?php else: ?>
    <!-- ── UPCOMING EVENT: Standard Detail View ───────────────────────────── -->
    <?php
    $detailBgImg = !empty($singleEvent['image_path'])
        ? "url('assets/" . htmlspecialchars($singleEvent['image_path'], ENT_QUOTES) . "')"
        : "url('assets/OTS_pics/490328409_1216009183868035_3908810661872386342_n.jpg')";
    ?>
    <section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.72),rgba(107,15,26,.72)),<?= $detailBgImg ?>">
      <div class="container">
        <a href="events.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Events</a>
        <h1><?= e($singleEvent['title']) ?></h1>
        <?php if ($singleEvent['title_tamil']): ?>
        <p class="hero-subtitle"><?= e($singleEvent['title_tamil']) ?></p>
        <?php endif; ?>
      </div>
    </section>

    <div id="eventActionBar" class="event-action-bar">
      <?php if ($singleEvent['ticket_url']): ?>
      <a href="<?= e($singleEvent['ticket_url']) ?>" class="btn-register" target="_blank">
        <i class="bi bi-arrow-right-circle"></i> Get Tickets
      </a>
      <?php endif; ?>
      <button class="btn-event-form btn-volunteer" id="volunteerFormBtn" style="display:none" onclick="openPublicForm('volunteer')">
        <i class="bi bi-hand-raised-fill"></i> Volunteer Sign-Up
      </button>
      <button class="btn-event-form btn-performer" id="performerFormBtn" style="display:none" onclick="openPublicForm('performer')">
        <i class="bi bi-music-note-beamed"></i> Performer Application
      </button>
    </div>

    <section class="event-details-section">
      <div class="container">
        <div class="event-content-wrapper">
          <!-- Left: Event Details -->
          <div class="event-info-card">
            <div class="event-header">
              <div class="event-icon"><i class="bi bi-calendar-event"></i></div>
              <div>
                <h2>Event Details</h2>
                <p class="event-tagline">Don't miss this!</p>
              </div>
            </div>
            <?php if ($singleEvent['event_date']): ?>
            <div class="event-detail-item">
              <i class="bi bi-calendar3"></i>
              <div><strong>Date</strong>
                <p><?= date('F j, Y', strtotime($singleEvent['event_date'])) ?></p>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($singleEvent['event_time']): ?>
            <div class="event-detail-item">
              <i class="bi bi-clock"></i>
              <div><strong>Time</strong>
                <p><?= e($singleEvent['event_time']) ?></p>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($singleEvent['location']): ?>
            <div class="event-detail-item">
              <i class="bi bi-geo-alt"></i>
              <div><strong>Location</strong>
                <p><?= e($singleEvent['location']) ?></p>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($singleEvent['member_price'] || $singleEvent['regular_price']): ?>
            <div class="event-detail-item">
              <i class="bi bi-ticket-perforated"></i>
              <div>
                <strong>Ticket Prices</strong>
                <?php if (isActiveMember($currentUser)): ?>
                  <?php if ($singleEvent['member_price']): ?>
                  <p><i class="bi bi-award" style="color:#d4a73a"></i> Member: <strong>$<?= number_format($singleEvent['member_price'], 2) ?></strong></p>
                  <?php endif; ?>
                <?php else: ?>
                  <?php if ($singleEvent['member_price']): ?>
                  <p>Member: <strong>$<?= number_format($singleEvent['member_price'], 2) ?></strong></p>
                  <?php endif; ?>
                  <?php if ($singleEvent['regular_price']): ?>
                  <p>Regular: <strong>$<?= number_format($singleEvent['regular_price'], 2) ?></strong></p>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Right: Description -->
          <div class="event-description-card">
            <?php if ($singleEvent['image_path']): ?>
            <img src="<?= e($singleEvent['image_path']) ?>" alt="<?= e($singleEvent['title']) ?>"
              class="event-detail-img" />
            <?php endif; ?>
            <h2>About This Event</h2>
            <div class="event-desc-html"><?= $singleEvent['description'] ?: '<p>Details coming soon.</p>' ?></div>
          </div>
        </div>

        <?php if ($singleEvent['member_price'] || $singleEvent['regular_price']): ?>
        <div class="event-pricing-section">
          <h2>Ticket Pricing</h2>
          <?php if (isActiveMember($currentUser)): ?>
          <!-- Active member: show only member price with badge -->
          <div class="pricing-cards">
            <?php if ($singleEvent['member_price']): ?>
            <div class="ticket-card member-ticket">
              <div class="ticket-badge"><i class="bi bi-award"></i><span>Member Price</span></div>
              <div class="ticket-price"><span class="currency">$</span><span
                  class="amount"><?= number_format($singleEvent['member_price'], 2) ?></span></div>
              <h3>General Admission — MEMBER</h3>
              <p>You're getting the discounted member rate.</p>
              <p class="ticket-note" style="color:#065f46"><i class="bi bi-check-circle-fill"></i> Member pricing applied to your account</p>
            </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <!-- Non-member: show both prices with join CTA on member price -->
          <div class="pricing-cards">
            <?php if ($singleEvent['member_price']): ?>
            <div class="ticket-card member-ticket member-ticket-locked">
              <div class="ticket-badge"><i class="bi bi-award"></i><span>Member Price</span></div>
              <div class="ticket-price"><span class="currency">$</span><span
                  class="amount"><?= number_format($singleEvent['member_price'], 2) ?></span></div>
              <h3>General Admission — MEMBER</h3>
              <p>Discounted price for Ottawa Tamil Sangam members.</p>
              <a href="membership.php" class="btn-join-prompt">
                <i class="bi bi-arrow-right-circle"></i> Join to get this price
              </a>
            </div>
            <?php endif; ?>
            <?php if ($singleEvent['regular_price']): ?>
            <div class="ticket-card non-member-ticket">
              <div class="ticket-badge"><i class="bi bi-ticket-perforated"></i><span>Regular Price</span></div>
              <div class="ticket-price"><span class="currency">$</span><span
                  class="amount"><?= number_format($singleEvent['regular_price'], 2) ?></span></div>
              <h3>General Admission — NON-MEMBER</h3>
              <p>Full price for non-members.</p>
              <?php if ($singleEvent['member_price'] && $singleEvent['regular_price']): ?>
              <p class="ticket-note membership-prompt"><i class="bi bi-arrow-right-circle"></i> Save
                $<?= number_format($singleEvent['regular_price'] - $singleEvent['member_price'], 2) ?> by
                <a href="membership.php" style="color:#6b0f1a;font-weight:600">becoming a member</a>!</p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ── Public Event Form Modal ───────────────────────────────────────── -->
    <div id="publicFormModal" class="public-form-modal-overlay" style="display:none" role="dialog" aria-modal="true">
      <div class="public-form-modal-box">
        <button class="public-form-close" id="publicFormClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        <div id="publicFormContent">
          <p class="loading-text">Loading form…</p>
        </div>
      </div>
    </div>

    <script>
    (function() {
      const eventId = <?= (int)$singleEvent['id'] ?>;
      const isLoggedIn = <?= $currentUser ? 'true' : 'false' ?>;
      let publicFormData = null;
      let activeFormType = null;

      // ── Escape helpers ────────────────────────────────────────────────────
      function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
      function escAttr(s) { return (s || '').replace(/"/g, '&quot;'); }

      // ── Load forms and show buttons ───────────────────────────────────────
      async function loadEventForms() {
        const r = await fetch(`api.php?action=get_event_forms&event_id=${eventId}`);
        const d = await r.json();
        if (!d.success) return;
        (d.forms || []).forEach(f => {
          if (f.form_type === 'volunteer' && f.is_active) document.getElementById('volunteerFormBtn').style.display = '';
          if (f.form_type === 'performer' && f.is_active) document.getElementById('performerFormBtn').style.display = '';
        });
      }

      // ── Helpers ───────────────────────────────────────────────────────────
      function pfTypeClass(t) { return t === 'volunteer' ? 'type-volunteer' : t === 'performer' ? 'type-performer' : 'type-default'; }
      function pfTypeIcon(t)  { return t === 'volunteer' ? 'bi-hand-raised-fill' : 'bi-music-note-beamed'; }

      function pfHeader(formType, title, description, deadline) {
        const tc = pfTypeClass(formType);
        const icon = pfTypeIcon(formType);
        return `<div class="pf-header ${tc}">
          <div class="pf-header-icon"><i class="bi ${icon}"></i></div>
          <h3>${esc(title)}</h3>
          ${description ? `<p class="pf-header-desc">${esc(description)}</p>` : ''}
          ${deadline ? `<span class="pf-deadline"><i class="bi bi-clock"></i> Deadline: ${esc(deadline.slice(0,16).replace('T',' '))}</span>` : ''}
        </div>`;
      }

      // ── Open a form ───────────────────────────────────────────────────────
      window.openPublicForm = async function(formType) {
        activeFormType = formType;
        const label = formType === 'volunteer' ? 'Volunteer Sign-Up' : 'Performer Application';
        const modal = document.getElementById('publicFormModal');
        document.getElementById('publicFormContent').innerHTML = '<p class="loading-text" style="padding:40px;text-align:center">Loading…</p>';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        if (!isLoggedIn) {
          document.getElementById('publicFormContent').innerHTML = `
            ${pfHeader(formType, label, '', '')}
            <div class="pf-status-screen">
              <i class="bi bi-person-lock pf-status-icon" style="color:#6b0f1a"></i>
              <h3>Login Required</h3>
              <p>You need to be logged in to fill out this form.</p>
              <button class="pf-submit-btn type-default" onclick="closePublicForm(); document.getElementById('loginBtn')?.click()">
                <i class="bi bi-box-arrow-in-right"></i> Log In to Continue
              </button>
            </div>`;
          return;
        }

        const r = await fetch(`api.php?action=get_event_form_detail&event_id=${eventId}&form_type=${formType}`);
        const d = await r.json();
        if (!d.success || !d.form) {
          document.getElementById('publicFormContent').innerHTML = `
            ${pfHeader(formType, label, '', '')}
            <div class="pf-status-screen">
              <i class="bi bi-exclamation-circle pf-status-icon" style="color:#9ca3af"></i>
              <h3>Not Available</h3>
              <p>This form is not available right now. Check back later.</p>
              <button class="pf-cancel-btn" onclick="closePublicForm()">Close</button>
            </div>`;
          return;
        }

        publicFormData = d;
        const f = d.form;
        const questions = d.questions || [];

        if (d.user_submission) {
          const sub = d.user_submission;
          const statusIcon = { pending:'hourglass-split', approved:'check-circle-fill', rejected:'x-circle-fill' }[sub.status] || 'hourglass-split';
          const answersHtml = sub.answers && sub.answers.length
            ? `<div class="pf-answers-recap">${sub.answers.map(a=>`
                <div class="pf-answers-recap-row">
                  <span class="pf-answers-recap-q">${esc(a.question_text)}</span>
                  <span class="pf-answers-recap-a">${esc(a.answer_text||'—')}</span>
                </div>`).join('')}</div>`
            : '';
          document.getElementById('publicFormContent').innerHTML = `
            ${pfHeader(formType, f.title, '', '')}
            <div class="pf-status-screen">
              <i class="bi bi-${statusIcon} pf-status-icon" style="color:${{pending:'#d97706',approved:'#059669',rejected:'#dc2626'}[sub.status]||'#6b7280'}"></i>
              <span class="pf-status-badge ${sub.status}"><i class="bi bi-${statusIcon}"></i> ${sub.status.charAt(0).toUpperCase()+sub.status.slice(1)}</span>
              <h3>Already Submitted</h3>
              <p>Submitted on ${esc(sub.submitted_at?.slice(0,10)||'')}. Your application is <strong>${esc(sub.status)}</strong>.</p>
              ${answersHtml}
              <button class="pf-cancel-btn" onclick="closePublicForm()" style="margin-top:4px">Close</button>
            </div>`;
          return;
        }

        // Render form
        const tc = pfTypeClass(formType);
        let questionsHtml = '';
        questions.forEach(q => {
          const fieldId = `pf_q_${q.id}`;
          const reqMark = q.is_required ? '<span class="pf-req">*</span>' : '';
          const limitsHint = q.word_limit > 0 ? `max ${q.word_limit} words` : (q.char_limit > 0 ? `max ${q.char_limit} chars` : '');
          questionsHtml += `<div class="pf-question" data-qid="${q.id}">
            <label class="pf-question-label" for="${fieldId}">${esc(q.question_text)}${reqMark}${limitsHint ? `<span class="pf-hint">${limitsHint}</span>` : ''}</label>`;

          if (q.input_type === 'text') {
            questionsHtml += `<input type="text" id="${fieldId}" name="${fieldId}" ${q.is_required?'required':''}
              ${q.char_limit>0?`maxlength="${q.char_limit}"`:''} class="pf-field" data-word="${q.word_limit}" data-char="${q.char_limit}" placeholder="Your answer…"/>`;
          } else if (q.input_type === 'textarea') {
            questionsHtml += `<textarea id="${fieldId}" name="${fieldId}" rows="4" ${q.is_required?'required':''}
              ${q.char_limit>0?`maxlength="${q.char_limit}"`:''} class="pf-field" data-word="${q.word_limit}" data-char="${q.char_limit}" placeholder="Your answer…"></textarea>`;
            if (q.word_limit > 0 || q.char_limit > 0) {
              questionsHtml += `<small class="pf-counter" id="ctr_${q.id}"></small>`;
            }
          } else if (q.input_type === 'radio') {
            const opts = q.options || [];
            questionsHtml += `<div class="pf-options">${opts.map(o => `<label class="pf-option-label"><input type="radio" name="${fieldId}" value="${escAttr(o)}" ${q.is_required?'required':''}/>${esc(o)}</label>`).join('')}</div>`;
          } else if (q.input_type === 'select') {
            const opts = q.options || [];
            questionsHtml += `<select id="${fieldId}" name="${fieldId}" class="pf-field" ${q.is_required?'required':''}><option value="">— Select an option —</option>${opts.map(o=>`<option value="${escAttr(o)}">${esc(o)}</option>`).join('')}</select>`;
          } else if (q.input_type === 'checkbox') {
            const opts = q.options || [];
            questionsHtml += `<div class="pf-options">${opts.map(o => `<label class="pf-option-label"><input type="checkbox" name="${fieldId}" value="${escAttr(o)}"/>${esc(o)}</label>`).join('')}</div>`;
          }
          questionsHtml += `</div>`;
        });

        document.getElementById('publicFormContent').innerHTML = `
          ${pfHeader(formType, f.title, f.description, f.deadline)}
          <div class="pf-body">
            <div id="pfMsg" class="pf-alert error"></div>
            <form id="publicFormEl" onsubmit="return false">${questionsHtml}</form>
          </div>
          <div class="pf-footer">
            <button class="pf-submit-btn ${tc}" id="pfSubmitBtn" onclick="submitPublicForm()"><i class="bi bi-send-fill"></i> Submit</button>
            <button class="pf-cancel-btn" onclick="closePublicForm()">Cancel</button>
          </div>`;

        // Word/char counters
        document.querySelectorAll('.pf-field[data-word], .pf-field[data-char]').forEach(field => {
          const qid = field.closest('.pf-question')?.dataset.qid;
          const ctr = document.getElementById('ctr_' + qid);
          if (!ctr) return;
          const wl = +field.dataset.word, cl = +field.dataset.char;
          field.addEventListener('input', () => {
            const val = field.value;
            const words = val.trim() ? val.trim().split(/\s+/).length : 0;
            if (wl > 0) {
              ctr.textContent = `${words} / ${wl} words`;
              ctr.classList.toggle('over', words > wl);
            } else if (cl > 0) {
              ctr.textContent = `${val.length} / ${cl} chars`;
              ctr.classList.toggle('over', val.length > cl);
            }
          });
        });
      };

      window.submitPublicForm = async function() {
        const f = publicFormData?.form;
        if (!f) return;
        const btn = document.getElementById('pfSubmitBtn');
        btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting…';
        const msg = document.getElementById('pfMsg');

        // Collect answers
        const questions = publicFormData.questions || [];
        const answers = {};
        let valid = true;
        for (const q of questions) {
          const fieldId = `pf_q_${q.id}`;
          let val = '';
          if (q.input_type === 'checkbox') {
            val = [...document.querySelectorAll(`input[name="${fieldId}"]:checked`)].map(el => el.value).join(', ');
          } else if (q.input_type === 'radio') {
            const sel = document.querySelector(`input[name="${fieldId}"]:checked`);
            val = sel ? sel.value : '';
          } else {
            val = (document.getElementById(fieldId)?.value || '').trim();
          }
          if (q.is_required && !val) {
            msg.textContent = `Please answer: "${q.question_text}"`;
            msg.style.display = 'block';
            document.getElementById(`pf_q_${q.id}`)?.scrollIntoView({ behavior:'smooth', block:'center' });
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit';
            valid = false; break;
          }
          const field = document.getElementById(fieldId);
          if (field && +field.dataset.word > 0) {
            const wc = val ? val.trim().split(/\s+/).length : 0;
            if (wc > +field.dataset.word) {
              msg.textContent = `Answer to "${q.question_text}" exceeds ${field.dataset.word} words.`;
              msg.style.display = 'block';
              btn.disabled = false; btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit';
              valid = false; break;
            }
          }
          answers[q.id] = val;
        }
        if (!valid) return;

        const r = await fetch('api.php?action=submit_event_form', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ form_id: f.id, answers }),
        });
        const d = await r.json();
        if (d.success) {
          const label = activeFormType === 'volunteer' ? 'volunteer sign-up' : 'performer application';
          document.getElementById('publicFormContent').innerHTML = `
            ${pfHeader(activeFormType, f.title, '', '')}
            <div class="pf-status-screen">
              <i class="bi bi-check-circle-fill pf-status-icon" style="color:#059669"></i>
              <h3>You're signed up!</h3>
              <p>Your ${label} has been received. We'll be in touch soon!</p>
              <button class="pf-cancel-btn" onclick="closePublicForm()">Close</button>
            </div>`;
        } else {
          msg.textContent = d.error || 'Submission failed. Please try again.';
          msg.style.display = 'block';
          btn.disabled = false; btn.innerHTML = '<i class="bi bi-send-fill"></i> Submit';
        }
      };

      window.closePublicForm = function() {
        document.getElementById('publicFormModal').style.display = 'none';
        document.body.style.overflow = '';
      };

      document.getElementById('publicFormClose')?.addEventListener('click', closePublicForm);
      document.getElementById('publicFormModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closePublicForm(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closePublicForm(); });

      loadEventForms();
    })();

    document.getElementById('loginToRegister')?.addEventListener('click', () => {
      document.getElementById('loginBtn')?.click();
    });

    document.getElementById('registerEventBtn')?.addEventListener('click', async () => {
      const eventId = document.getElementById('registerEventBtn').dataset.id;
      if (!confirm('Register for this event? (1 ticket)')) return;
      const r = await fetch('api.php?action=purchase_ticket', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event_id: eventId, quantity: 1 }),
      });
      const d = await r.json();
      if (d.success) alert(`Registered! Ticket ID: #${d.ticket_id}. View in your dashboard.`);
      else alert('Error: ' + (d.error || 'Registration failed.'));
    });
    </script>

    <?php endif; // end is_upcoming check ?>

  <?php else: ?>
  <!-- ── Events Listing ───────────────────────────────────────────── -->
  <section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.70),rgba(107,15,26,.70)),url('assets/OTS_pics/490001484_1216009457201341_1078509298641835755_n.jpg')">
    <div class="container">
      <h1>Events</h1>
      <p class="hero-subtitle">Upcoming and past events from Ottawa Tamil Sangam</p>
    </div>
  </section>

  <div class="container">
    <?php if (isEventCoordinator($currentUser)): ?>
    <div class="content-edit-bar" style="margin-bottom:20px">
      <i class="bi bi-pencil-square"></i>
      <span>You can manage events from your panel.</span>
      <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>" class="btn-edit-content">
        Manage Events →
      </a>
    </div>
    <?php endif; ?>

    <?php
      $upcoming = $db->query("SELECT * FROM events WHERE is_published=1 AND is_upcoming=1 ORDER BY event_date ASC")->fetchAll();
      $past     = $db->query("SELECT * FROM events WHERE is_published=1 AND is_upcoming=0 ORDER BY event_date DESC")->fetchAll();
    ?>

    <?php if (!empty($upcoming)): ?>
    <section class="events-listing fade-in">
      <h2>Upcoming Events</h2>
      <div class="event-grid">
        <?php foreach ($upcoming as $ev): ?>
        <a href="events.php?id=<?= $ev['id'] ?>" class="event-card">
          <div>
            <?php if ($ev['image_path']): ?>
            <img src="<?= e($ev['image_path']) ?>" alt="<?= e($ev['title']) ?>" />
            <?php endif; ?>
            <div class="content">
              <h3><?= e($ev['title']) ?></h3>
              <?php if ($ev['title_tamil']): ?><p class="ev-tamil"><?= e($ev['title_tamil']) ?></p><?php endif; ?>
              <p>UPCOMING<?= $ev['event_date'] ? ' · '.date('M j, Y', strtotime($ev['event_date'])) : '' ?></p>
              <p>Click to view</p>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($past)): ?>
    <section class="events-listing fade-in" style="margin-top:24px">
      <h2>Past Events</h2>
      <div class="event-grid">
        <?php foreach ($past as $ev): ?>
        <a href="events.php?id=<?= $ev['id'] ?>" class="event-card past-event">
          <div>
            <?php if ($ev['image_path']): ?>
            <img src="<?= e($ev['image_path']) ?>" alt="<?= e($ev['title']) ?>" />
            <?php endif; ?>
            <div class="content">
              <h3><?= e($ev['title']) ?></h3>
              <?php if ($ev['title_tamil']): ?><p class="ev-tamil"><?= e($ev['title_tamil']) ?></p><?php endif; ?>
              <p>PAST EVENT<?= $ev['event_date'] ? ' · '.date('M j, Y', strtotime($ev['event_date'])) : '' ?></p>
              <p>Click to view gallery</p>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (empty($upcoming) && empty($past)): ?>
    <p style="text-align:center;color:#6b7280;padding:60px 0;font-size:1.05rem">
      No events found. Check back soon!
    </p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</main>

<?php include_once "footer.php"; ?>

<script src="main.js"></script>
<script>
// Fade in
const obs = new IntersectionObserver(entries =>
  entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
  }), { threshold: 0.1 });
document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));
</script>

</body>
</html>
