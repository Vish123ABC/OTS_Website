<?php
include_once "header.php";
// $currentUser already set by header.php
$db = getDB();
$members = $db->query("SELECT * FROM committee_members ORDER BY display_order ASC, id ASC")->fetchAll();
?>

<main>
  <section class="page-hero" style="background-color:#d4a73a">
    <div class="container">
      <h1>செயற்குழு</h1>
      <p class="hero-subtitle">Executive Committee</p>
    </div>
  </section>

  <div class="container">
    <section class="committee-section fade-in">
      <div class="committee-intro">
        <?= getSiteContent('committee_intro', '<p>Meet the dedicated individuals who lead and guide Ottawa Tamil Sangam</p>') ?>
      </div>

      <?php if (isCoordinator($currentUser) || isAdmin($currentUser)): ?>
      <div class="content-edit-bar" style="margin-bottom:24px">
        <i class="bi bi-pencil-square"></i>
        <span>Committee members are manageable by admins.</span>
        <a href="<?= isAdmin($currentUser) ? 'admin_panel.php' : 'coordinator_panel.php' ?>?tab=committee"
          class="btn-edit-content">Manage Committee →</a>
      </div>
      <?php endif; ?>


      <div class="committee-grid">
        <?php foreach ($members as $m): ?>
        <div class="committee-card">
          <div class="committee-image">
            <?php if ($m['photo_path']): ?>
            <img src="<?= e($m['photo_path']) ?>" alt="<?= e($m['name_english']) ?>"
              onerror="this.src='assets/logo.webp'" />
            <?php else: ?>
            <div class="committee-photo-placeholder"><i class="bi bi-person-fill"></i></div>
            <?php endif; ?>
          </div>
          <div class="committee-info">
            <?php if ($m['name_tamil']): ?><h3><?= e($m['name_tamil']) ?></h3><?php endif; ?>
            <h4><?= e($m['name_english']) ?></h4>
            <?php if ($m['role_tamil']): ?><p class="committee-role-tamil"><?= e($m['role_tamil']) ?></p><?php endif; ?>
            <?php if ($m['role_english']): ?><p class="committee-role"><?= e($m['role_english']) ?></p><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($members)): ?>
        <p class="empty-state" style="grid-column:1/-1;text-align:center;color:#6b7280;padding:40px 0">No committee
          members added yet.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<?php include_once "footer.php"; ?>
<script src="main.js"></script>
<script>
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
</body>

</html>