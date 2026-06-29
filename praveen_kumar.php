<?php
  include_once "header.php";
?>

<!-- Main Content -->
<main>
  <!-- Hero Banner -->
  <section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.72),rgba(107,15,26,.72)),url('assets/OTS_pics/0X0A5467.jpg');background-position:center 40%">
    <div class="container">
      <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Home
      </a>
      <h1><?= e(strip_tags(getSiteContent('praveen_hero_title', '8, by Praveen Kumar | Tamil Standup Comedy (6+)'))) ?></h1>
      <p class="hero-subtitle"><?= e(strip_tags(getSiteContent('praveen_hero_subtitle', 'November 30, 2025 • Upcoming Event'))) ?></p>
    </div>
  </section>

  <div class="registration-cta">
    <a href="https://www.zeffy.com/en-CA/ticketing/8-by-praveen-kumar-tamil-standup-comedy--6" class="btn-register">
      <i class="bi bi-arrow-right-circle"></i>
      Click Here to get Tickets
    </a>
  </div>
  <section class="event-details-section">
    <div class="container">
      <div class="event-content-wrapper">
        <!-- Left Column: Event Info -->
        <div class="event-info-card">
          <div class="event-header">
            <div class="event-icon">
              <i class="bi bi-calendar-event"></i>
            </div>
            <div>
              <h2>Event Details</h2>
              <p class="event-tagline">Don't miss this spectacular show!</p>
            </div>
          </div>

          <div class="event-detail-item">
            <i class="bi bi-calendar3"></i>
            <div>
              <strong>Date</strong>
              <p>November 30, 2025</p>
            </div>
          </div>

          <div class="event-detail-item">
            <i class="bi bi-clock"></i>
            <div>
              <strong>Time</strong>
              <p>6:45 PM - 8:15 PM EST</p>
              <small>Doors open at 6:30 PM</small>
            </div>
          </div>

          <div class="event-detail-item">
            <i class="bi bi-geo-alt"></i>
            <div>
              <strong>Location</strong>
              <p>1817 Richardson Side Rd</p>
              <p>Ottawa, ON K0A 1L0, Canada</p>
            </div>
          </div>

          <div class="event-detail-item">
            <i class="bi bi-people"></i>
            <div>
              <strong>Age Requirement</strong>
              <p>Audience must be 6 years and above</p>
            </div>
          </div>
        </div>

        <!-- Right Column: Description -->
        <div class="event-description-card">
          <h2>About the Show</h2>
          <?= getSiteContent('praveen_about', '<p class="lead-text">Praveen Kumar is back in Canada with his new Standup show "8" after the stupendous success of his world Tour!</p><p>Praveen Kumar (PK), known for his highly successful Tamil stand up comedy shows such as "36 Vayathiniley", "Kancheepuram Mapla," and "Family Man," is set to captivate the audience in Ottawa for the first time with his "8" on Nov 30, 2025, after his widely appreciated shows all over the world.</p>') ?>

          <div class="highlight-box">
            <i class="bi bi-star-fill"></i>
            <?= getSiteContent('praveen_highlight', '<p>This is PK\'s 5th standup comedy show with Master Mediaworks in Canada and first time in Ottawa.</p>') ?>
          </div>

          <div class="past-shows">
            <h3>Previous Hit Shows:</h3>
            <?= getSiteContent('praveen_past_shows', '<ul><li>36 Vayathiniley</li><li>Kancheepuram Mapla</li><li>Family Man</li></ul>') ?>
          </div>
        </div>
      </div>

      <!-- Pricing Section -->
      <div class="event-pricing-section">
        <h2>Ticket Pricing</h2>
        <div class="pricing-cards">
          <div class="ticket-card member-ticket">
            <div class="ticket-badge">
              <i class="bi bi-award"></i>
              <span>Member Price</span>
            </div>
            <div class="ticket-price">
              <span class="currency">$</span>
              <span class="amount">25</span>
            </div>
            <h3>General Admission - MEMBER</h3>
            <p>Discounted price for Ottawa Tamil Sangam members.</p>
            <p class="ticket-note">
              <i class="bi bi-info-circle"></i>
              Membership Number required for admission
            </p>
          </div>

          <div class="ticket-card non-member-ticket">
            <div class="ticket-badge">
              <i class="bi bi-ticket-perforated"></i>
              <span>Regular Price</span>
            </div>
            <div class="ticket-price">
              <span class="currency">$</span>
              <span class="amount">30</span>
            </div>
            <h3>General Admission - NON-MEMBER</h3>
            <p>Full price for Non-Members.</p>
            <p class="ticket-note membership-prompt">
              <i class="bi bi-arrow-right-circle"></i>
              Save $5 by becoming a member!
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
  include_once "footer.php";
?>
</div>
</div>

<!-- Scripts -->
<script src="main.js"></script>
<script>
// Auth Modal functionality
document.addEventListener("DOMContentLoaded", () => {
  const authModal = document.getElementById("authModal");
  const loginBtn = document.getElementById("loginBtn");
  const signupBtn = document.getElementById("signupBtn");
  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const closeModal = document.getElementById("closeModal");
  const closeRegister = document.getElementById("closeRegister");
  const openRegister = document.getElementById("openRegister");
  const openLogin = document.getElementById("openLogin");

  if (loginBtn) {
    loginBtn.addEventListener("click", () => {
      authModal.classList.add("active");
      loginForm.style.display = "block";
      registerForm.style.display = "none";
      document.body.style.overflow = "hidden";
    });
  }

  if (signupBtn) {
    signupBtn.addEventListener("click", () => {
      authModal.classList.add("active");
      registerForm.style.display = "block";
      loginForm.style.display = "none";
      document.body.style.overflow = "hidden";
    });
  }

  if (openRegister) {
    openRegister.addEventListener("click", (e) => {
      e.preventDefault();
      loginForm.style.display = "none";
      registerForm.style.display = "block";
    });
  }

  if (openLogin) {
    openLogin.addEventListener("click", (e) => {
      e.preventDefault();
      registerForm.style.display = "none";
      loginForm.style.display = "block";
    });
  }

  function closeAuthModal() {
    authModal.classList.remove("active");
    document.body.style.overflow = "";
  }

  if (closeModal) closeModal.addEventListener("click", closeAuthModal);
  if (closeRegister)
    closeRegister.addEventListener("click", closeAuthModal);

  if (authModal) {
    authModal.addEventListener("click", (e) => {
      if (e.target === authModal) closeAuthModal();
    });
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && authModal?.classList.contains("active")) {
      closeAuthModal();
    }
  });
});
</script>
</body>

</html>