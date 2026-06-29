<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ots_session();
$currentUser = getCurrentUser();

// Load the 2 most recent events for the "Featured Events" section
$db = getDB();
$featuredEvents = $db->query(
    "SELECT * FROM events WHERE is_published=1 ORDER BY event_date DESC LIMIT 2"
)->fetchAll();
// Total published events — used to decide whether to show the "see all" link
$totalEvents = (int)$db->query("SELECT COUNT(*) FROM events WHERE is_published=1")->fetchColumn();

// Load recent published posts
$recentPosts = $db->query(
    "SELECT p.*, u.first_name||' '||u.last_name AS author_name
     FROM posts p LEFT JOIN users u ON p.created_by=u.id
     WHERE p.is_published=1 ORDER BY p.created_at DESC LIMIT 3"
)->fetchAll();

// Dynamic site content
$welcomeTamil   = getSiteContent('home_welcome_tamil');
$welcomeEnglish = getSiteContent('home_welcome_english');
$sectionHeading = getSiteContent('home_section_heading', '<h2>வரவேற்கிறோம்</h2>');

include_once "header.php";
?>

<main>
  <!-- ── Hero Slideshow ───────────────────────────────────────────────── -->
  <section class="hero">
    <div id="heroSlide" class="slide active">
      <div class="overlay">
        <div class="container">
          <h1>Welcome to Ottawa Tamil Sangam</h1>
          <p class="hero-subtitle">Celebrating Tamil culture and heritage in Ottawa</p>
        </div>
      </div>
    </div>
  </section>

  <div class="container">

    <!-- ── Welcome Section (dynamic content) ──────────────────────────── -->
    <section class="about fade-in" id="homeWelcomeSection">
      <?= $sectionHeading ?>
      <div id="welcomeTamil"><?= $welcomeTamil ?></div>
      <h2>Welcome</h2>
      <div id="welcomeEnglish"><?= $welcomeEnglish ?></div>

      <?php if (canEditContent($currentUser)): ?>
      <div class="content-edit-bar">
        <i class="bi bi-pencil-square"></i>
        <span>This content is editable.</span>
        <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>" class="btn-edit-content">
          Edit Welcome Text →
        </a>
      </div>
      <?php endif; ?>
    </section>

<!-- ── Live Announcements / Posts ─────────────────────────────────── -->
    <?php if (!empty($recentPosts)): ?>
    <section class="posts-section fade-in">
      <h2>Latest Announcements</h2>
      <div class="posts-grid">
        <?php foreach ($recentPosts as $post): ?>
        <article class="post-card">
          <?php if ($post['image_path']): ?>
          <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>" class="post-img" />
          <?php endif; ?>
          <div class="post-body">
            <span class="post-type-badge post-type-<?= e($post['post_type']) ?>"><?= e($post['post_type']) ?></span>
            <h3><?= e($post['title']) ?></h3>
            <div class="post-content"><?= $post['content'] ?></div>
            <p class="post-meta">
              <i class="bi bi-clock"></i>
              <?= date('M j, Y', strtotime($post['created_at'])) ?>
              <?php if ($post['author_name']): ?> · <?= e($post['author_name']) ?><?php endif; ?>
            </p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

<!-- ── Section divider ─────────────────────────────────────────────── -->
    <div class="section-divider-otsb2" aria-hidden="true"></div>

<!-- ── Featured Events (dynamic from DB) ──────────────────────────── -->
    <section class="events fade-in">
      <div
        style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <h2>Featured Events</h2>
        <?php if (isEventCoordinator($currentUser)): ?>
        <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>" class="btn-edit-content">
          <i class="bi bi-calendar-plus"></i> Manage Events
        </a>
        <?php endif; ?>
      </div>

      <?php if (empty($featuredEvents)): ?>
      <p class="no-events">No upcoming events scheduled. Check back soon!</p>
      <?php else: ?>
      <div class="events-row">
      <div class="event-grid">
        <?php foreach ($featuredEvents as $ev): ?>
        <?php
              $slug = 'events.php?id=' . $ev['id'];
              $img  = $ev['image_path'] ?: 'assets/praveen_kumar.webp';
            ?>
        <a href="<?= e($slug) ?>" class="event-card">
          <div>
            <img src="<?= e($img) ?>" alt="<?= e($ev['title']) ?>" onerror="this.src='assets/praveen_kumar.webp'" />
            <div class="content">
              <h3><?= e($ev['title']) ?></h3>
              <?php if ($ev['title_tamil']): ?>
              <p class="event-tamil-inline"><?= e($ev['title_tamil']) ?></p>
              <?php endif; ?>
              <p>
                <span class="ev-status-dot <?= $ev['is_upcoming'] ? 'upcoming' : 'past' ?>">
                  <?= $ev['is_upcoming'] ? 'UPCOMING' : 'PAST' ?>
                </span>
                <?php if ($ev['event_date']): ?>
                · <?= date('M j, Y', strtotime($ev['event_date'])) ?>
                <?php endif; ?>
              </p>
              <p>Click to view</p>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php if ($totalEvents > count($featuredEvents)): ?>
      <a href="events.php" class="see-all-link">
        <span>See all events</span>
        <i class="bi bi-arrow-right"></i>
      </a>
      <?php endif; ?>
      </div><!-- /events-row -->
      <?php endif; ?>
    </section>

  </div><!-- /container -->
</main>

<?php include_once "footer.php"; ?>

<!-- Scripts -->
<script src="main.js"></script>
<script>
// ── Image Slideshow ─────────────────────────────────────────────────────
let currentIndex = 0;
let shuffledImages = [];
let slideInterval;
const heroSlide = document.getElementById('heroSlide');

function shuffleArray(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

function showImage(i) {
  if (heroSlide && shuffledImages.length)
    heroSlide.style.cssText =
    `background-image:url('assets/${shuffledImages[i]}');background-size:cover;background-position:center 30%;background-repeat:no-repeat`;
}

function nextImg() {
  currentIndex = (currentIndex + 1) % shuffledImages.length;
  showImage(currentIndex);
}

function prevImg() {
  currentIndex = (currentIndex - 1 + shuffledImages.length) % shuffledImages.length;
  showImage(currentIndex);
}

async function initSlideshow() {
  try {
    const r = await fetch('api.php?action=get_slideshow_photos');
    const d = await r.json();
    const active = (d.photos || []).filter(p => p.is_active == 1).map(p => p.photo_path);
    shuffledImages = shuffleArray(active.length ? active : ['hero1.jpg', 'hero2.jpg']);
  } catch {
    shuffledImages = ['hero1.jpg', 'hero2.jpg'];
  }
  if (!shuffledImages.length) return;
  showImage(currentIndex);
  slideInterval = setInterval(nextImg, 4000);
  document.getElementById('next')?.addEventListener('click', () => {
    nextImg();
    clearInterval(slideInterval);
    slideInterval = setInterval(nextImg, 4000);
  });
  document.getElementById('prev')?.addEventListener('click', () => {
    prevImg();
    clearInterval(slideInterval);
    slideInterval = setInterval(nextImg, 4000);
  });
}
initSlideshow();

// ── Fade-in on scroll ───────────────────────────────────────────────────
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      obs.unobserve(e.target);
    }
  });
}, {
  threshold: 0.1
});
document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));
</script>

<style>
/* Index-page extras */

/* Tamil welcome heading + paragraph use a traditional Tamil typeface */
#homeWelcomeSection > h2:first-child,
#welcomeTamil,
#welcomeTamil * {
  font-family: "Tiro Tamil", "Noto Sans Tamil", Georgia, serif;
}
#welcomeTamil {
  line-height: 1.95;
}

.post-card {
  background: var(--card, #f4ecd8);
  border-radius: 10px;
  border: 1px solid #e2e5ed;
  overflow: hidden;
  transition: box-shadow .15s, transform .15s;
}

.post-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
  transform: translateY(-2px);
}

.post-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
}

.post-body {
  padding: 16px 18px;
}

.post-body h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 8px 0 6px;
}

.post-content {
  font-size: .84rem;
  color: #5a6479;
  line-height: 1.5;
  margin-bottom: 8px;
  max-height: 80px;
  overflow: hidden;
}

.post-meta {
  font-size: .75rem;
  color: #9ca3af;
  display: flex;
  align-items: center;
  gap: 5px;
}

.post-type-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: .7rem;
  font-weight: 600;
  text-transform: capitalize;
}

.post-type-announcement {
  background: #dbeafe;
  color: #1d4ed8;
}

.post-type-news {
  background: #dcfce7;
  color: #166534;
}

.post-type-event {
  background: #fef3c7;
  color: #92400e;
}

.posts-section h2 {
  margin-bottom: 16px;
}

.posts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.event-tamil-inline {
  font-size: .8rem;
  color: rgba(255, 255, 255, .75);
  margin: 2px 0;
}

.ev-status-dot {
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .06em;
}

.ev-status-dot.upcoming {
  color: #34d399;
}

.ev-status-dot.past {
  color: #d97706;
}

.no-events {
  color: #6b7280;
  text-align: center;
  padding: 24px 0;
}

.content-edit-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 16px;
  padding: 10px 14px;
  background: #fffbeb;
  border: 1px dashed #f59e0b;
  border-radius: 8px;
  font-size: .82rem;
  color: #92400e;
}

.btn-edit-content {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  background: #f59e0b;
  color: #fff;
  border-radius: 6px;
  font-size: .78rem;
  font-weight: 600;
  text-decoration: none;
  transition: background .15s;
}

.btn-edit-content:hover {
  background: #d97706;
}

.events-row {
  display: flex;
  align-items: stretch;
  gap: 28px;
}
.events-row .event-grid {
  flex: 1;
  min-width: 0;
}
.see-all-link {
  flex: 0 0 auto;
  align-self: stretch;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-width: 180px;
  padding: 28px 24px;
  color: var(--maroon, #6b0f1a);
  font-family: "Cormorant Garamond", Georgia, serif;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.25;
  text-align: center;
  text-decoration: none;
  border: 1.5px solid rgba(107,15,26,.22);
  border-radius: 14px;
  background: linear-gradient(135deg, #fffdf8 0%, #fbf3e4 100%);
  transition: color .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.see-all-link .bi-arrow-right {
  font-size: 1.15rem;
  transition: transform .18s ease;
}
.see-all-link:hover {
  color: #fff;
  background: linear-gradient(135deg, #6b0f1a 0%, #8b1f2a 100%);
  border-color: #6b0f1a;
  box-shadow: 0 10px 24px rgba(107,15,26,.25);
  transform: translateY(-2px);
}
.see-all-link:hover .bi-arrow-right {
  transform: translateX(6px);
}
@media (max-width: 760px) {
  .events-row {
    flex-direction: column;
    gap: 20px;
  }
  .see-all-link {
    flex-direction: row;
    align-self: stretch;
    padding: 18px 24px;
    font-size: 1.3rem;
  }
}
</style>
</body>

</html>