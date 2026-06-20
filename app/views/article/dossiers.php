<?php
/* ── article/dossiers.php ───────────────────────────────────────── */
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
$dossierCount = count($dossiers ?? []);
$dossierCountLabel = $dossierCount === 1 ? 'dossier disponible' : 'dossiers disponibles';
?>

<section class="dossiers-page">
  <div class="dossiers-hero-surface">
    <div class="container">
      <nav class="breadcrumb" aria-label="Fil d'ariane">
        <ol>
          <li><a href="<?= APP_URL ?>">Accueil</a></li>
          <li aria-current="page">Dossiers</li>
        </ol>
      </nav>

      <header class="dossiers-hero">
        <div class="dossiers-hero-copy">
          <span class="dossiers-eyebrow">Dossiers thématiques</span>
          <h1 class="dossiers-title">Approfondir les sujets qui comptent</h1>
          <p class="dossiers-lead">
            Retrouvez ici nos enquêtes, séries éditoriales et sujets de fond regroupés par thème pour naviguer plus facilement dans l’actualité.
          </p>
        </div>

        <div class="dossiers-hero-card" aria-label="Résumé des dossiers">
          <strong><?= $dossierCount ?></strong>
          <span><?= $dossierCountLabel ?></span>
        </div>
      </header>
    </div>
  </div>

  <div class="container dossiers-main">
    <?php if (empty($dossiers)): ?>
      <div class="dossiers-empty">
        <p>Aucun dossier n’est disponible pour le moment.</p>
        <a href="<?= APP_URL ?>" class="dossiers-empty-link">Retour à l’accueil</a>
      </div>
    <?php else: ?>
      <div class="dossiers-grid">
        <?php foreach ($dossiers as $dossier): ?>
          <article class="dossier-card">
            <a href="<?= APP_URL ?>/dossier/<?= $e($dossier['slug']) ?>" class="dossier-card-link">
              <div class="dossier-card-media">
                <?php if (!empty($dossier['cover_image_thumb']) || !empty($dossier['cover_image_webp']) || !empty($dossier['cover_image_path'])): ?>
                  <picture>
                    <?php if (!empty($dossier['cover_image_thumb'])): ?>
                      <source srcset="<?= $e($imgUrl($dossier['cover_image_thumb'])) ?>" type="image/webp">
                    <?php elseif (!empty($dossier['cover_image_webp'])): ?>
                      <source srcset="<?= $e($imgUrl($dossier['cover_image_webp'])) ?>" type="image/webp">
                    <?php endif; ?>
                    <img src="<?= $e($imgUrl($dossier['cover_image_thumb'] ?: ($dossier['cover_image_path'] ?? ''))) ?>"
                         alt="<?= $e($dossier['cover_image_alt'] ?? $dossier['title']) ?>"
                         width="1200" height="675" loading="lazy">
                  </picture>
                <?php else: ?>
                  <div class="dossier-card-placeholder" aria-hidden="true">
                    <?= $initial($dossier['title']) ?>
                  </div>
                <?php endif; ?>

                <span class="dossier-card-badge">
                  <?php $count = (int)$dossier['article_count']; ?>
                  <?= $count ?> article<?= $count === 1 ? '' : 's' ?>
                </span>
              </div>

              <div class="dossier-card-body">
                <h2 class="dossier-card-title"><?= $e($dossier['title']) ?></h2>
                <?php if (!empty($dossier['description'])): ?>
                  <p class="dossier-card-description"><?= $e($truncate($dossier['description'], 180)) ?></p>
                <?php endif; ?>
                <div class="dossier-card-meta">
                  <span>Créé le <?= date('d/m/Y', strtotime($dossier['created_at'])) ?></span>
                  <span>Lire le dossier</span>
                </div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.dossiers-page { padding-bottom: 64px; }
.dossiers-hero-surface { background: linear-gradient(180deg, #f7f4ef 0%, #fff 100%); border-bottom: 1px solid #eadfcf; }
.dossiers-hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; padding: 28px 0 36px; }
.dossiers-hero-copy { max-width: 760px; }
.dossiers-eyebrow { display: inline-block; margin-bottom: 12px; text-transform: uppercase; letter-spacing: .14em; font-size: .72rem; color: #b45309; font-family: 'Inter', sans-serif; font-weight: 700; }
.dossiers-title { font-family: 'Playfair Display', serif; font-size: clamp(2.2rem, 4vw, 3.5rem); line-height: 1.02; font-weight: 900; margin: 0 0 14px; }
.dossiers-lead { font-family: 'Source Serif 4', serif; font-size: 1.05rem; line-height: 1.8; color: #4b5563; margin: 0; }
.dossiers-hero-card { min-width: 180px; padding: 18px 20px; border-radius: 18px; background: #111827; color: #fff; box-shadow: 0 18px 36px rgba(17, 24, 39, .14); text-align: center; }
.dossiers-hero-card strong { display: block; font-size: 2.3rem; line-height: 1; font-family: 'Playfair Display', serif; margin-bottom: 6px; }
.dossiers-hero-card span { display: block; font-size: .76rem; text-transform: uppercase; letter-spacing: .1em; color: rgba(255, 255, 255, .75); }
.dossiers-main { padding-top: 32px; }
.dossiers-empty { padding: 56px 24px; text-align: center; border: 1px solid #eadfcf; border-radius: 24px; background: #fff; color: #4b5563; }
.dossiers-empty-link { display: inline-block; margin-top: 16px; color: #0a3d6b; font-weight: 700; text-decoration: none; }
.dossiers-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; }
.dossier-card { grid-column: span 6; border: 1px solid #e5e7eb; border-radius: 24px; overflow: hidden; background: #fff; box-shadow: 0 10px 24px rgba(17, 24, 39, .06); transition: transform .18s ease, box-shadow .18s ease; }
.dossier-card:hover { transform: translateY(-3px); box-shadow: 0 18px 34px rgba(17, 24, 39, .1); }
.dossier-card-link { display: flex; flex-direction: column; height: 100%; color: inherit; text-decoration: none; }
.dossier-card-media { position: relative; aspect-ratio: 16 / 9; background: linear-gradient(135deg, #0f172a, #1d4ed8); overflow: hidden; }
.dossier-card-media picture, .dossier-card-media img { display: block; width: 100%; height: 100%; }
.dossier-card-media img { object-fit: cover; }
.dossier-card-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-size: 4rem; font-weight: 900; color: rgba(255, 255, 255, .9); }
.dossier-card-badge { position: absolute; left: 16px; bottom: 16px; padding: 8px 12px; border-radius: 999px; background: rgba(17, 24, 39, .84); color: #fff; font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; }
.dossier-card-body { padding: 22px; display: flex; flex-direction: column; gap: 12px; }
.dossier-card-title { margin: 0; font-family: 'Playfair Display', serif; font-size: 1.55rem; line-height: 1.1; font-weight: 800; }
.dossier-card-description { margin: 0; color: #4b5563; font-family: 'Source Serif 4', serif; font-size: 1rem; line-height: 1.75; }
.dossier-card-meta { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: .74rem; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; }
@media (max-width: 920px) {
  .dossiers-hero { flex-direction: column; align-items: flex-start; }
  .dossier-card { grid-column: span 12; }
}
</style>
