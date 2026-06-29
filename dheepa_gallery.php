<?php
  include_once "header.php";
?>

<!-- Main Content -->
<main>
  <!-- Hero Banner -->
  <section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.70),rgba(107,15,26,.70)),url('assets/OTS_pics/490196779_1216009157201371_4095350843885857665_n.jpg')">
    <div class="container">
      <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Home
      </a>
      <h1><?= e(strip_tags(getSiteContent('dheepa_hero_title', 'Dheepa Thirunaal Kondattam'))) ?></h1>
      <p class="hero-subtitle"><?= e(strip_tags(getSiteContent('dheepa_hero_subtitle', 'November 8, 2025 • Event Gallery'))) ?></p>
    </div>
  </section>

  <div class="container">
    <!-- Gallery Controls -->
    <section class="gallery-controls fade-in">
      <div class="gallery-info">
        <p id="imageCount">Loading images...</p>
      </div>
      <div class="view-controls">
        <button id="gridView" class="view-btn active" aria-label="Grid view">
          <i class="bi bi-grid-3x3-gap"></i>
        </button>
        <button id="listView" class="view-btn" aria-label="List view">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </section>

    <!-- Photo Gallery -->
    <section class="photo-gallery fade-in">
      <div id="galleryContainer" class="gallery-grid">
        <!-- Images will be loaded dynamically -->
      </div>

      <!-- Loading Indicator -->
      <div id="loadingIndicator" class="loading-indicator">
        <div class="spinner"></div>
        <p>Loading images...</p>
      </div>

      <!-- Load More Button -->
      <div id="loadMoreContainer" class="load-more-container" style="display: none">
        <button id="loadMoreBtn" class="btn-load-more">
          Load More Photos
        </button>
      </div>
    </section>
  </div>
</main>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox">
  <button class="lightbox-close" id="lightboxClose">
    <i class="bi bi-x-lg"></i>
  </button>
  <button class="lightbox-nav lightbox-prev" id="lightboxPrev">
    <i class="bi bi-chevron-left"></i>
  </button>
  <button class="lightbox-nav lightbox-next" id="lightboxNext">
    <i class="bi bi-chevron-right"></i>
  </button>
  <div class="lightbox-content">
    <img id="lightboxImage" src="" alt="Gallery image" />
    <div class="lightbox-caption" id="lightboxCaption"></div>
  </div>
</div>

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

  // ================= GALLERY FUNCTIONALITY =================
  // ================= GALLERY FUNCTIONALITY =================
  const galleryContainer = document.getElementById("galleryContainer");
  const loadingIndicator = document.getElementById("loadingIndicator");
  const loadMoreContainer = document.getElementById("loadMoreContainer");
  const loadMoreBtn = document.getElementById("loadMoreBtn");
  const imageCount = document.getElementById("imageCount");
  const gridViewBtn = document.getElementById("gridView");
  const listViewBtn = document.getElementById("listView");

  // Lightbox elements
  const lightbox = document.getElementById("lightbox");
  const lightboxImage = document.getElementById("lightboxImage");
  const lightboxCaption = document.getElementById("lightboxCaption");
  const lightboxClose = document.getElementById("lightboxClose");
  const lightboxPrev = document.getElementById("lightboxPrev");
  const lightboxNext = document.getElementById("lightboxNext");

  let allImages = [];
  let displayedImages = 0;
  const imagesPerLoad = 20;
  let currentLightboxIndex = 0;

  // Generate image list based on naming pattern
  function generateImageList() {
    const images = ["deepa2.jpg"]; // First image

    // Generate deepa2-2.jpg through deepa2-90.jpg
    for (let i = 2; i <= 90; i++) {
      images.push(`deepa2-${i}.jpg`);
    }

    return images;
  }

  // Initialize gallery
  function initializeGallery() {
    allImages = generateImageList();

    console.log(`Found ${allImages.length} images`);
    imageCount.textContent = `${allImages.length} photos`;

    loadingIndicator.style.display = "none";
    loadMoreImages();
  }

  // Load more images
  function loadMoreImages() {
    const start = displayedImages;
    const end = Math.min(start + imagesPerLoad, allImages.length);

    for (let i = start; i < end; i++) {
      const imgPath = `assets/album-d464556583-downloads-pt1/${allImages[i]}`;
      const galleryItem = document.createElement("div");
      galleryItem.className = "gallery-item";
      galleryItem.innerHTML = `
      <img src="${imgPath}" alt="Event photo ${i + 1}" loading="lazy" />
      <div class="gallery-overlay">
        <i class="bi bi-zoom-in"></i>
      </div>
    `;

      galleryItem.addEventListener("click", () => openLightbox(i));
      galleryContainer.appendChild(galleryItem);
    }

    displayedImages = end;

    if (displayedImages < allImages.length) {
      loadMoreContainer.style.display = "flex";
    } else {
      loadMoreContainer.style.display = "none";
    }
  }

  // View toggle
  gridViewBtn.addEventListener("click", () => {
    galleryContainer.className = "gallery-grid";
    gridViewBtn.classList.add("active");
    listViewBtn.classList.remove("active");
  });

  listViewBtn.addEventListener("click", () => {
    galleryContainer.className = "gallery-list";
    gridViewBtn.classList.remove("active");
    listViewBtn.classList.add("active");
  });

  loadMoreBtn.addEventListener("click", loadMoreImages);

  // Lightbox functionality
  function openLightbox(index) {
    currentLightboxIndex = index;
    updateLightboxImage();
    lightbox.classList.add("active");
    document.body.style.overflow = "hidden";
  }

  function closeLightbox() {
    lightbox.classList.remove("active");
    document.body.style.overflow = "";
  }

  function updateLightboxImage() {
    const imgPath = `assets/album-d464556583-downloads-pt1/${allImages[currentLightboxIndex]}`;
    lightboxImage.src = imgPath;
    lightboxCaption.textContent = `Photo ${currentLightboxIndex + 1} of ${
            allImages.length
          }`;
  }

  function nextImage() {
    currentLightboxIndex = (currentLightboxIndex + 1) % allImages.length;
    updateLightboxImage();
  }

  function prevImage() {
    currentLightboxIndex =
      (currentLightboxIndex - 1 + allImages.length) % allImages.length;
    updateLightboxImage();
  }

  lightboxClose.addEventListener("click", closeLightbox);
  lightboxNext.addEventListener("click", nextImage);
  lightboxPrev.addEventListener("click", prevImage);

  lightbox.addEventListener("click", (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener("keydown", (e) => {
    if (lightbox.classList.contains("active")) {
      if (e.key === "Escape") closeLightbox();
      if (e.key === "ArrowRight") nextImage();
      if (e.key === "ArrowLeft") prevImage();
    }
  });

  // Initialize gallery on load
  initializeGallery();
});
</script>
</body>

</html>