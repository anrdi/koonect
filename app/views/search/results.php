<?php
/* ── search/results.php ──────────────────────────────────────────── */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : APP_URL . '/assets/img/placeholder.jpg';
?>
<div class="container" style="padding-top:32px;padding-bottom:64px;">
  <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;margin-bottom:8px;">
    Recherche<?= $query ? ' : "' . $e($query) . '"' : '' ?>
  </h1>
  <?php if ($query): ?>
    <p style="color:#6b7280;margin-bottom:24px;"><?= $total ?? 0 ?> résultat<?= ($total ?? 0) > 1 ? 's' : '' ?> trouvé<?= ($total ?? 0) > 1 ? 's' : '' ?></p>
  <?php endif; ?>

  <?php if (empty($articles) && $query): ?>
    <div style="text-align:center;padding:60px 0;">
      <p style="font-size:1.1rem;color:#6b7280;">Aucun article trouvé pour cette recherche.</p>
      <p style="margin-top:8px;font-size:.9rem;color:#9ca3af;">Essayez avec d'autres mots-clés.</p>
    </div>
  <?php elseif (!$query): ?>
    <form action="<?= APP_URL ?>/recherche" method="get" style="max-width:600px;margin:40px auto;display:flex;gap:8px;">
      <input type="search" name="q" placeholder="Rechercher un article…" class="nav-search" style="flex:1;padding:12px 16px;font-size:1rem;border:2px solid #e5e7eb;border-radius:4px;outline:none;font-family:'Inter',sans-serif;" autofocus>
      <button type="submit" class="btn btn--primary">Rechercher</button>
    </form>
  <?php else: ?>
    <div class="articles-grid">
      <?php foreach ($articles as $art): ?>
      <article class="article-card">
        <?php if ($art['featured_image_thumb']): ?>
        <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
          <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>" alt="<?= $e($art['title']) ?>" width="400" height="267" loading="lazy">
        </a>
        <?php endif; ?>
        <div class="article-card-body">
          <?php if ($art['category_name']): ?>
            <a href="<?= APP_URL ?>/categorie/<?= $e($art['category_slug']) ?>" class="article-category"><?= $e($art['category_name']) ?></a>
          <?php endif; ?>
          <h2 class="article-card-title"><a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>"><?= $e($art['title']) ?></a></h2>
          <?php if ($art['chapo']): ?>
            <p class="article-card-chapo"><?= $e(mb_strimwidth($art['chapo'], 0, 140, '…')) ?></p>
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
    <?php if (($paginator ?? null)?->hasPages()): ?>
      <div style="margin-top:32px;"><?= $paginator->links(APP_URL . '/recherche?q=' . urlencode($query)) ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>
