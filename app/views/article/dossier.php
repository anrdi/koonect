<?php
/* ── article/dossier.php ───────────────────────────────────────── */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : APP_URL . '/assets/img/placeholder.jpg';
$truncate = function (string $value, int $limit): string {
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $limit, '…', 'UTF-8');
    }
    if (strlen($value) <= $limit) {
        return $value;
    }
    return rtrim(substr($value, 0, max(0, $limit - 1))) . '…';
};
$initial = function (string $value): string {
    $first = function_exists('mb_substr') ? mb_substr($value, 0, 1, 'UTF-8') : substr($value, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($first, 'UTF-8') : strtoupper($first);
};
$articleCount = (int)($dossier['article_count'] ?? count($articles ?? []));
$articleCountLabel = $articleCount === 1 ? 'article' : 'articles';
?>

<section class="dossier-page">
  <div class="dossier-hero-surface">
    <div class="container">
      <nav class="breadcrumb" aria-label="Fil d'ariane">
        <ol>
          <li><a href="<?= APP_URL ?>">Accueil</a></li>
          <li><a href="<?= APP_URL ?>/dossiers">Dossiers</a></li>
          <li aria-current="page"><?= $e($dossier['title']) ?></li>
        </ol>
      </nav>

      <header class="dossier-hero">
        <div class="dossier-hero-copy">
          <span class="dossier-eyebrow">Dossier thématique</span>
          <h1 class="dossier-title"><?= $e($dossier['title']) ?></h1>
          <?php if (!empty($dossier['description'])): ?>
            <p class="dossier-lead"><?= $e($dossier['description']) ?></p>
          <?php endif; ?>
          <div class="dossier-meta">
            <span><?= $articleCount ?> <?= $articleCountLabel ?></span>
            <span>Créé le <?= date('d/m/Y', strtotime($dossier['created_at'])) ?></span>
          </div>
        </div>

        <div class="dossier-hero-media">
          <?php if (!empty($dossier['cover_image_thumb']) || !empty($dossier['cover_image_webp']) || !empty($dossier['cover_image_path'])): ?>
            <picture>
              <?php if (!empty($dossier['cover_image_thumb'])): ?>
                <source srcset="<?= $e($imgUrl($dossier['cover_image_thumb'])) ?>" type="image/webp">
              <?php elseif (!empty($dossier['cover_image_webp'])): ?>
                <source srcset="<?= $e($imgUrl($dossier['cover_image_webp'])) ?>" type="image/webp">
              <?php endif; ?>
              <img src="<?= $e($imgUrl($dossier['cover_image_thumb'] ?: ($dossier['cover_image_path'] ?? ''))) ?>"
                   alt="<?= $e($dossier['cover_image_alt'] ?? $dossier['title']) ?>"
                   width="1200" height="675" loading="eager">
            </picture>
          <?php else: ?>
            <div class="dossier-hero-placeholder" aria-hidden="true">
              <?= $initial($dossier['title']) ?>
            </div>
          <?php endif; ?>
        </div>
      </header>
    </div>
  </div>

  <div class="container dossier-main">
    <div class="dossier-section-head">
      <h2>Articles du dossier</h2>
      <a href="<?= APP_URL ?>/dossiers" class="dossier-back-link">Tous les dossiers</a>
    </div>

    <?php if (empty($articles)): ?>
      <div class="dossier-empty">
        <p>Aucun article n’est encore associé à ce dossier.</p>
        <a href="<?= APP_URL ?>/dossiers">Explorer les autres dossiers</a>
      </div>
    <?php else: ?>
      <div class="articles-grid">
        <?php foreach ($articles as $art): ?>
          <article class="article-card">
            <?php if (!empty($art['featured_image_thumb'])): ?>
              <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
                <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>"
                     alt="" width="400" height="267" loading="lazy">
              </a>
            <?php endif; ?>
            <div class="article-card-body">
              <h3 class="article-card-title">
                <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>"><?= $e($art['title']) ?></a>
              </h3>
              <?php if (!empty($art['chapo'])): ?>
                <p class="article-card-chapo"><?= $e($truncate($art['chapo'], 140)) ?></p>
              <?php endif; ?>
              <div class="article-meta">
                <time datetime="<?= $e($art['published_at'] ?? '') ?>">
                  <?= $art['published_at'] ? date('d/m/Y', strtotime($art['published_at'])) : 'Récemment' ?>
                </time>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.dossier-page { padding-bottom: 64px; }
.dossier-hero-surface { background: linear-gradient(180deg, #fff 0%, #faf7f2 100%); border-bottom: 1px solid #eadfcf; }
.dossier-hero { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr); gap: 32px; align-items: stretch; padding: 28px 0 36px; }
.dossier-hero-copy { display: flex; flex-direction: column; justify-content: flex-end; padding: 10px 0; }
.dossier-eyebrow { display: inline-block; margin-bottom: 12px; text-transform: uppercase; letter-spacing: .14em; font-size: .72rem; color: #b45309; font-family: 'Inter', sans-serif; font-weight: 700; }
.dossier-title { font-family: 'Playfair Display', serif; font-size: clamp(2.4rem, 5vw, 4rem); line-height: 1.02; font-weight: 900; margin: 0 0 16px; }
.dossier-lead { font-family: 'Source Serif 4', serif; font-size: 1.08rem; line-height: 1.85; color: #4b5563; margin: 0; max-width: 70ch; }
.dossier-meta { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 18px; font-size: .76rem; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; }
.dossier-hero-media { border-radius: 24px; overflow: hidden; background: linear-gradient(135deg, #0f172a, #1d4ed8); box-shadow: 0 20px 42px rgba(17, 24, 39, .14); min-height: 280px; }
.dossier-hero-media picture, .dossier-hero-media img, .dossier-hero-placeholder { display: block; width: 100%; height: 100%; }
.dossier-hero-media img { object-fit: cover; }
.dossier-hero-placeholder { display: flex; align-items: center; justify-content: center; color: rgba(255, 255, 255, .95); font-family: 'Playfair Display', serif; font-size: 6rem; font-weight: 900; }
.dossier-main { padding-top: 32px; }
.dossier-section-head { display: flex; justify-content: space-between; align-items: end; gap: 16px; margin-bottom: 24px; }
.dossier-section-head h2 { margin: 0; font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 800; }
.dossier-back-link { font-size: .76rem; text-transform: uppercase; letter-spacing: .1em; color: #0a3d6b; text-decoration: none; font-weight: 700; }
.dossier-empty { padding: 56px 24px; text-align: center; border: 1px solid #eadfcf; border-radius: 24px; background: #fff; color: #4b5563; }
.dossier-empty a { display: inline-block; margin-top: 14px; color: #0a3d6b; font-weight: 700; text-decoration: none; }
@media (max-width: 920px) {
  .dossier-hero { grid-template-columns: 1fr; }
  .dossier-section-head { align-items: flex-start; flex-direction: column; }
}
</style>
