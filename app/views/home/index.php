<?php
/**  @var array $featured @var array $latest @var array $mostRead @var array $breaking @var array $categories */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($path) => $path ? (preg_match('#^https?://|^data:#i', $path) ? $path : APP_URL . '/' . ltrim($path, '/')) : APP_URL . '/assets/img/placeholder.jpg';
$timeAgo = function(?string $date): string {
    if (!$date) return 'Récemment';
    $diff = time() - strtotime($date);
    if ($diff < 3600)  return (int)($diff/60) . ' min';
    if ($diff < 86400) return (int)($diff/3600) . 'h';
    return date('d/m/Y', strtotime($date));
};
?>

<!-- UNE ÉDITORIALE -->
<section class="home-une" aria-label="À la une">
  <div class="container">
    <?php if (!empty($featured)): ?>

    <?php $hero = $featured[0]; ?>
    <div class="une-grid">

      <!-- Article héros -->
      <article class="une-hero" aria-label="Article principal">
        <a href="<?= APP_URL ?>/article/<?= $e($hero['slug']) ?>" class="une-hero-link">
          <div class="une-hero-image">
            <picture>
              <source srcset="<?= $e($imgUrl($hero['featured_image_webp'])) ?>" type="image/webp">
              <img src="<?= $e($imgUrl($hero['featured_image_path'])) ?>"
                   alt="<?= $e($hero['featured_image_alt'] ?? $hero['title']) ?>"
                   width="860" height="574" loading="eager" fetchpriority="high">
            </picture>
            <?php if ($hero['category_name']): ?>
              <span class="article-category"><?= $e($hero['category_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="une-hero-body">
            <h1 class="une-hero-title"><?= $e($hero['title']) ?></h1>
            <?php if ($hero['subtitle']): ?>
              <p class="une-hero-subtitle"><?= $e($hero['subtitle']) ?></p>
            <?php endif; ?>
            <div class="article-meta">
              <span class="article-author">Par <?= $e($hero['author_name']) ?></span>
              <time datetime="<?= $e($hero['published_at']) ?>"><?= $timeAgo($hero['published_at']) ?></time>
              <?php if (!empty($hero['reading_time'])): ?>
                <span class="article-readtime"><?= (int)$hero['reading_time'] ?> min de lecture</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </article>

      <!-- Articles secondaires -->
      <div class="une-secondary">
        <?php foreach (array_slice($featured, 1, 3) as $art): ?>
        <article class="une-secondary-item">
          <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>">
            <div class="une-secondary-image">
              <picture>
                <source srcset="<?= $e($imgUrl($art['featured_image_webp'])) ?>" type="image/webp">
                <img src="<?= $e($imgUrl($art['featured_image_path'] ?? '')) ?>"
                     alt="<?= $e($art['featured_image_alt'] ?? $art['title']) ?>"
                     width="300" height="200" loading="lazy">
              </picture>
              <?php if ($art['category_name']): ?>
                <span class="article-category article-category--sm"><?= $e($art['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="une-secondary-body">
              <h2 class="une-secondary-title"><?= $e($art['title']) ?></h2>
              <time datetime="<?= $e($art['published_at']) ?>" class="article-time"><?= $timeAgo($art['published_at']) ?></time>
            </div>
          </a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- SÉPARATEUR ÉDITORIAL -->
<div class="editorial-divider container"><hr></div>

<!-- DERNIÈRES ACTUALITÉS -->
<section class="home-latest" aria-labelledby="latest-heading">
  <div class="container">
    <div class="home-main-grid">

      <!-- Articles principaux -->
      <div class="latest-articles">
        <h2 id="latest-heading" class="section-title">Dernières actualités</h2>
        <div class="articles-grid">
          <?php foreach ($latest as $art): ?>
          <article class="article-card <?= $art['is_featured'] ? 'article-card--featured' : '' ?>">
            <?php if ($art['featured_image_thumb']): ?>
            <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
              <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>"
                   alt="<?= $e($art['featured_image_alt'] ?? '') ?>"
                   width="400" height="267" loading="lazy">
            </a>
            <?php endif; ?>
            <div class="article-card-body">
              <?php if ($art['category_name']): ?>
                <a href="<?= APP_URL ?>/categorie/<?= $e($art['category_slug']) ?>" class="article-category"><?= $e($art['category_name']) ?></a>
              <?php endif; ?>
              <h3 class="article-card-title">
                <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>"><?= $e($art['title']) ?></a>
              </h3>
              <?php if ($art['chapo']): ?>
                <p class="article-card-chapo"><?= $e(mb_strimwidth($art['chapo'], 0, 130, '…')) ?></p>
              <?php endif; ?>
              <div class="article-meta">
                <span class="article-author">Par <?= $e($art['author_name']) ?></span>
                <time datetime="<?= $e($art['published_at']) ?>"><?= $timeAgo($art['published_at']) ?></time>
                <?php if ($art['is_premium']): ?>
                  <span class="badge-premium" title="Article réservé aux abonnés">Premium</span>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <?php if (count($latest) >= 12): ?>
          <div class="load-more-wrap">
            <a href="#" class="btn btn--outline" id="load-more-articles" data-page="2">Voir plus d'articles</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <aside class="home-sidebar" aria-label="En complément">

        <!-- Les plus lus -->
        <div class="sidebar-block">
          <h2 class="sidebar-title">Les plus lus</h2>
          <ol class="most-read-list">
            <?php foreach ($mostRead as $i => $art): ?>
            <li class="most-read-item">
              <span class="most-read-rank"><?= $i + 1 ?></span>
              <div class="most-read-body">
                <?php if ($art['category_name']): ?>
                  <span class="article-category article-category--xs"><?= $e($art['category_name']) ?></span>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="most-read-title"><?= $e($art['title']) ?></a>
                <time datetime="<?= $e($art['published_at']) ?>" class="article-time"><?= $timeAgo($art['published_at']) ?></time>
              </div>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>

        <!-- Newsletter inline -->
        <div class="sidebar-block sidebar-newsletter">
          <h2 class="sidebar-title">La newsletter</h2>
          <p>L'essentiel chaque matin. Gratuit.</p>
          <form action="<?= APP_URL ?>/newsletter/inscription" method="post" class="sidebar-newsletter-form">
            <?= \Koonect\Helpers\Csrf::field() ?>
            <input type="hidden" name="redirect" value="<?= $e(APP_URL . '/') ?>">
            <input type="email" name="email" placeholder="votre@email.fr" required aria-label="Email pour la newsletter">
            <button type="submit" class="btn btn--primary">S'abonner</button>
            <p class="newsletter-legal">Sans spam. Désabonnement en 1 clic.</p>
          </form>
        </div>

        <!-- Tags populaires -->
        <div class="sidebar-block">
          <h2 class="sidebar-title">Mots-clés</h2>
          <div class="tags-cloud">
            <?php
            $popularTags = \Koonect\Services\CacheService::remember('popular_tags', 3600, fn() => (new \Koonect\Models\Tag())->getPopular(15));
            foreach ($popularTags as $tag):
            ?>
              <a href="<?= APP_URL ?>/tag/<?= $e($tag['slug']) ?>" class="tag-chip"><?= $e($tag['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

      </aside>
    </div>
  </div>
</section>
