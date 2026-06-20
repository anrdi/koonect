<?php
/* ── category/show.php ──────────────────────────────────────────── */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : APP_URL . '/assets/img/placeholder.jpg';
$timeAgo = function(?string $date): string {
    if (!$date) return 'Récemment';
    $diff = time() - strtotime($date);
    if ($diff < 3600) return (int)($diff/60).' min';
    if ($diff < 86400) return (int)($diff/3600).'h';
    return date('d/m/Y', strtotime($date));
};
?>
<section class="category-page">
  <div class="container">
    <!-- En-tête catégorie -->
    <header class="category-header">
      <nav class="breadcrumb" aria-label="Fil d'ariane">
        <ol>
          <li><a href="<?= APP_URL ?>">Accueil</a></li>
          <li aria-current="page"><?= $e($category['name']) ?></li>
        </ol>
      </nav>
      <h1 class="category-title"><?= $e($category['name']) ?></h1>
      <?php if ($category['description']): ?>
        <p class="category-description"><?= $e($category['description']) ?></p>
      <?php endif; ?>
      <div class="category-count"><?= number_format($total, 0, ',', ' ') ?> article<?= $total > 1 ? 's' : '' ?></div>
    </header>

    <div class="home-main-grid">
      <div>
        <div class="articles-grid">
          <?php foreach ($articles as $art): ?>
          <article class="article-card">
            <?php if ($art['featured_image_thumb']): ?>
            <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
              <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>"
                   alt="<?= $e($art['featured_image_alt'] ?? '') ?>" width="400" height="267" loading="lazy">
            </a>
            <?php endif; ?>
            <div class="article-card-body">
              <h2 class="article-card-title">
                <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>"><?= $e($art['title']) ?></a>
              </h2>
              <?php if ($art['chapo']): ?>
                <p class="article-card-chapo"><?= $e(mb_strimwidth($art['chapo'], 0, 130, '…')) ?></p>
              <?php endif; ?>
              <div class="article-meta">
                <span class="article-author">Par <?= $e($art['author_name']) ?></span>
                <time datetime="<?= $e($art['published_at']) ?>"><?= $timeAgo($art['published_at']) ?></time>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <?php if ($paginator->hasPages()): ?>
          <div style="margin-top:32px;"><?= $paginator->links() ?></div>
        <?php endif; ?>
      </div>

      <aside class="home-sidebar" aria-label="En complément">
        <?php
        $popularTags = \Koonect\Services\CacheService::remember('popular_tags', 3600, fn() => (new \Koonect\Models\Tag())->getPopular(15));
        ?>
        <div class="sidebar-block">
          <h2 class="sidebar-title">Tags populaires</h2>
          <div class="tags-cloud">
            <?php foreach ($popularTags as $tag): ?>
              <a href="<?= APP_URL ?>/tag/<?= $e($tag['slug']) ?>" class="tag-chip"><?= $e($tag['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<style>
.category-header { padding: 32px 0 24px; border-bottom: 3px solid #1A1A1A; margin-bottom: 32px; }
.category-title { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 900; margin: 8px 0; }
.category-description { color: #6b7280; font-size: 1rem; margin-bottom: 8px; }
.category-count { font-family: 'Inter', sans-serif; font-size: .78rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .08em; }
</style>
