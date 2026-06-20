<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : APP_URL . '/assets/img/placeholder.jpg';
$timeAgo = function(?string $date): string {
    if (!$date) return 'Récemment';
    $diff = time() - strtotime($date);
    if ($diff < 3600)  return (int)($diff / 60) . ' min';
    if ($diff < 86400) return (int)($diff / 3600) . 'h';
    return date('d/m/Y', strtotime($date));
};
?>

<section class="tag-page">
  <div class="container">
    <header class="category-header">
      <nav class="breadcrumb" aria-label="Fil d'ariane">
        <ol>
          <li><a href="<?= APP_URL ?>">Accueil</a></li>
          <li aria-current="page">Tag : <?= $e($tag['name']) ?></li>
        </ol>
      </nav>
      <h1 class="category-title" style="display:flex;align-items:center;gap:12px;margin-top:8px;font-size:2rem;font-family:'Playfair Display',serif;font-weight:900;">
        Tag : <span class="tag-chip" style="font-size:1rem;padding:6px 16px;margin-left:8px;"><?= $e($tag['name']) ?></span>
        <span class="category-count" style="font-size:.78rem;color:#9ca3af;font-family:'Inter',sans-serif;font-weight:normal;text-transform:uppercase;letter-spacing:.08em;margin-left:8px;"><?= number_format($total, 0, ',', ' ') ?> article<?= $total > 1 ? 's' : '' ?></span>
      </h1>
    </header>

    <div class="home-main-grid">
      <div>
        <?php if (empty($articles)): ?>
          <p style="color:#6b7280;font-size:.9rem;">Aucun article avec ce tag pour l'instant.</p>
        <?php else: ?>
        <div class="articles-grid">
          <?php foreach ($articles as $art): ?>
          <article class="article-card">
            <?php if ($art['featured_image_thumb']): ?>
            <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
              <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>"
                   alt="<?= $e($art['featured_image_alt'] ?? $art['title']) ?>" width="400" height="267" loading="lazy">
            </a>
            <?php endif; ?>
            <div class="article-card-body">
              <?php if ($art['category_name']): ?>
                <a href="<?= APP_URL ?>/categorie/<?= $e($art['category_slug']) ?>" class="article-category"><?= $e($art['category_name']) ?></a>
              <?php endif; ?>
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
        <?php endif; ?>
      </div>

      <aside class="home-sidebar" aria-label="Tags populaires">
        <div class="sidebar-block">
          <h2 class="sidebar-title">Tags populaires</h2>
          <div class="tags-cloud">
            <?php
            $popular = \Koonect\Services\CacheService::remember('popular_tags', 3600, fn() => (new \Koonect\Models\Tag())->getPopular(20));
            foreach ($popular as $t):
            ?>
              <a href="<?= APP_URL ?>/tag/<?= $e($t['slug']) ?>"
                 class="tag-chip <?= $t['id'] == $tag['id'] ? 'tag-chip--active' : '' ?>"><?= $e($t['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<style>
.tag-chip--active { background: #C8102E; color: #fff; border-color: #C8102E; }
</style>
