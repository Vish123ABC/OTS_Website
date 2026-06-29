<?php
include_once "header.php";
// $currentUser already set by header.php
$db          = getDB();
$visionStats = $db->query("SELECT * FROM vision_stats ORDER BY display_order ASC, id ASC")->fetchAll();
$coreValues  = $db->query("SELECT * FROM vision_core_values ORDER BY display_order ASC, id ASC")->fetchAll();
?>
<section class="vision-hero">
  <div class="container vision-hero-content">
    <h1>Our Vision &amp; Values</h1>
    <p>Building bridges, preserving heritage, and nurturing community bonds</p>
  </div>
</section>

<main>
  <div class="container vision-content">

    <div class="vision-section fade-in">
      <h2><i class="bi bi-bullseye"></i> Our Mission</h2>
      <?= getSiteContent('vision_mission') ?>
      <?php if (canEditContent($currentUser)): ?>
      <div class="content-edit-bar">
        <i class="bi bi-pencil-square"></i>
        <span>Mission text is editable.</span>
        <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>?tab=sitecontent" class="btn-edit-content">Edit Content →</a>
      </div>
      <?php endif; ?>
    </div>

    <div class="stats-row fade-in">
      <?php foreach ($visionStats as $stat): ?>
      <div class="stat-card">
        <div class="stat-number"><?= e($stat['number_text']) ?></div>
        <div class="stat-label"><?= e($stat['label']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (isAdmin($currentUser)): ?>
    <div class="content-edit-bar">
      <i class="bi bi-bar-chart-line"></i>
      <span>Stats are editable in the admin panel.</span>
      <a href="admin_panel.php?tab=vision" class="btn-edit-content">Edit Stats →</a>
    </div>
    <?php endif; ?>

    <div class="section-divider-otsb4" aria-hidden="true"></div>

    <div class="vision-section fade-in">
      <h2><i class="bi bi-heart-fill"></i> Our Purpose</h2>
      <?= getSiteContent('vision_purpose') ?>
      <?php if (canEditContent($currentUser)): ?>
      <div class="content-edit-bar">
        <i class="bi bi-pencil-square"></i>
        <span>Purpose text is editable.</span>
        <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>?tab=sitecontent" class="btn-edit-content">Edit Content →</a>
      </div>
      <?php endif; ?>
    </div>

    <div class="vision-section fade-in">
      <h2><i class="bi bi-star-fill"></i> Our Core Values</h2>
      <div class="values-grid">
        <?php foreach ($coreValues as $val): ?>
        <div class="value-card">
          <h4><?= e($val['title']) ?></h4>
          <p><?= e($val['description']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (isAdmin($currentUser)): ?>
      <div class="content-edit-bar" style="margin-top:16px">
        <i class="bi bi-star"></i>
        <span>Core values are editable in the admin panel.</span>
        <a href="admin_panel.php?tab=vision" class="btn-edit-content">Edit Values →</a>
      </div>
      <?php endif; ?>
    </div>

    <div class="section-divider-otsb3" aria-hidden="true"></div>

    <div class="vision-section fade-in">
      <h2><i class="bi bi-compass"></i> Looking Forward</h2>
      <?= getSiteContent('vision_looking_forward') ?>
      <?php if (canEditContent($currentUser)): ?>
      <div class="content-edit-bar">
        <i class="bi bi-pencil-square"></i>
        <span>This section is editable.</span>
        <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>?tab=sitecontent" class="btn-edit-content">Edit Content →</a>
      </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php include_once "footer.php"; ?>
<script src="main.js"></script>
<script>
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));
</script>
</body>
</html>
