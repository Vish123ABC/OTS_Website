<?php
  include_once "header.php";
?>

<main>
  <section class="page-hero" style="background-image:linear-gradient(rgba(107,15,26,.70),rgba(107,15,26,.70)),url('assets/OTS_pics/489680853_1216008140534806_4606657598310710647_n.jpg');background-position:center 48%">
    <div class="container">
      <h1>News &amp; Updates</h1>
      <p class="hero-subtitle">The latest from Ottawa Tamil Sangam</p>
    </div>
  </section>

  <div class="container" style="max-width:900px;padding:48px 20px">
    <div id="postsContainer"></div>
    <p id="postsEmpty" style="display:none;text-align:center;color:#6b7280;padding:60px 0;font-size:1.05rem">No announcements yet. Check back soon!</p>
    <p id="postsLoading" style="text-align:center;color:#6b7280;padding:60px 0">Loading…</p>
  </div>
</main>

<style>
.post-card {
  background: var(--card, #f4ecd8);
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(107,15,26,.08);
  border-left: 4px solid var(--maroon, #6b0f1a);
  padding: 28px 32px;
  margin-bottom: 24px;
}
.post-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
.post-card-title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--maroon, #6b0f1a);
  margin: 0;
}
.post-card-meta {
  font-size: 0.82rem;
  color: #9ca3af;
  white-space: nowrap;
  margin-top: 4px;
}
.post-type-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
  padding: 3px 10px;
  border-radius: 20px;
  background: rgba(107,15,26,.10);
  color: var(--maroon, #6b0f1a);
  white-space: nowrap;
}
.post-card-body {
  color: #374151;
  font-size: 1rem;
  line-height: 1.7;
}
.post-card-body p { margin: 0 0 8px; color: inherit; }
.post-card-body p:last-child { margin-bottom: 0; }
.post-card-img {
  width: 100%;
  max-height: 340px;
  object-fit: cover;
  border-radius: 8px;
  margin-top: 16px;
}
</style>

<script>
(async () => {
  const container = document.getElementById('postsContainer');
  const loading   = document.getElementById('postsLoading');
  const empty     = document.getElementById('postsEmpty');

  try {
    const r = await fetch('api.php?action=get_posts');
    const d = await r.json();
    loading.style.display = 'none';

    if (!d.success || !d.posts || !d.posts.length) {
      empty.style.display = 'block';
      return;
    }

    d.posts.forEach(post => {
      const date = new Date(post.created_at).toLocaleDateString('en-CA', { year:'numeric', month:'long', day:'numeric' });
      const typeLabel = (post.post_type || 'announcement').charAt(0).toUpperCase() + (post.post_type || 'announcement').slice(1);

      const card = document.createElement('article');
      card.className = 'post-card';
      card.innerHTML = `
        <div class="post-card-header">
          <div>
            <h2 class="post-card-title">${esc(post.title)}</h2>
            <div class="post-card-meta">${date}${post.author_name ? ' · ' + esc(post.author_name) : ''}</div>
          </div>
          <span class="post-type-badge">${esc(typeLabel)}</span>
        </div>
        ${post.content ? `<div class="post-card-body">${post.content}</div>` : ''}
        ${post.image_path ? `<img class="post-card-img" src="assets/${esc(post.image_path)}" alt="${esc(post.title)}" loading="lazy">` : ''}
      `;
      container.appendChild(card);
    });
  } catch (e) {
    loading.textContent = 'Failed to load posts. Please try again later.';
  }

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>

<?php
  include_once "footer.php";
?>
</body>
</html>
