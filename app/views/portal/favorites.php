<?php
/* ─────────────────────────────────────────────────────────────────
   portal/favorites.php
───────────────────────────────────────────────────────────────── */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : APP_URL . '/assets/img/placeholder.jpg';
?>
<div class="portal-list-page">
  <div class="portal-list-header">
    <h1>Mes favoris</h1>
    <span class="portal-list-count"><?= $total ?> article<?= $total > 1 ? 's' : '' ?></span>
  </div>

  <?php if (empty($favorites)): ?>
    <div class="portal-empty">
      <p>Vous n'avez pas encore ajouté d'articles en favoris.</p>
      <a href="<?= APP_URL ?>" class="btn btn--outline btn--sm">Découvrir des articles</a>
    </div>
  <?php else: ?>
  <div class="portal-article-list">
    <?php foreach ($favorites as $art): ?>
    <div class="portal-article-item">
      <?php if ($art['featured_image_thumb']): ?>
        <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="pal-thumb" tabindex="-1" aria-hidden="true">
          <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>" alt="" width="100" height="67" loading="lazy">
        </a>
      <?php endif; ?>
      <div class="pal-body">
        <?php if ($art['category_name']): ?>
          <a href="<?= APP_URL ?>/categorie/<?= $e($art['category_slug']) ?>" class="article-category article-category--xs"><?= $e($art['category_name']) ?></a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="pal-title"><?= $e($art['title']) ?></a>
        <div class="pal-meta">
          <time datetime="<?= $e($art['published_at'] ?? '') ?>">
            <?= $art['published_at'] ? date('d/m/Y', strtotime($art['published_at'])) : 'Récemment' ?>
          </time>
          <span>· Ajouté le <?= date('d/m/Y', strtotime($art['favorited_at'])) ?></span>
        </div>
      </div>
      <form method="post" action="<?= PORTAL_URL ?>/favoris/<?= (int)$art['id'] ?>">
        <?= \Koonect\Helpers\Csrf::field() ?>
        <button type="submit" class="pal-remove" title="Retirer des favoris" aria-label="Retirer <?= $e($art['title']) ?> des favoris">✕</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($paginator->hasPages()): ?>
    <div style="margin-top:24px;"><?= $paginator->links() ?></div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php /* ── portal/history.php ── inclus dans le même fichier via trick PHP ── */ ?>
<?php if (isset($history)): ?>
<div class="portal-list-page">
  <div class="portal-list-header">
    <h1>Historique de lecture</h1>
    <span class="portal-list-count"><?= $total ?> article<?= $total > 1 ? 's' : '' ?></span>
  </div>
  <?php if (empty($history)): ?>
    <div class="portal-empty"><p>Vous n'avez pas encore lu d'articles.</p></div>
  <?php else: ?>
  <div class="portal-article-list">
    <?php foreach ($history as $art): ?>
    <div class="portal-article-item">
      <?php if ($art['featured_image_thumb']): ?>
        <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="pal-thumb" tabindex="-1" aria-hidden="true">
          <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>" alt="" width="100" height="67" loading="lazy">
        </a>
      <?php endif; ?>
      <div class="pal-body">
        <?php if ($art['category_name']): ?>
          <a href="<?= APP_URL ?>/categorie/<?= $e($art['category_slug']) ?>" class="article-category article-category--xs"><?= $e($art['category_name']) ?></a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="pal-title"><?= $e($art['title']) ?></a>
        <div class="pal-meta">
          Lu le <time datetime="<?= $e($art['read_at']) ?>"><?= date('d/m/Y à H\hi', strtotime($art['read_at'])) ?></time>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($paginator->hasPages()): ?><div style="margin-top:24px;"><?= $paginator->links() ?></div><?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<style>
.portal-list-page { max-width: 800px; }
.portal-list-header { display: flex; align-items: baseline; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid #1A1A1A; padding-bottom: 12px; }
.portal-list-header h1 { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }
.portal-list-count { font-family: 'Inter', sans-serif; font-size: .78rem; color: #9ca3af; }
.portal-empty { padding: 48px; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 8px; }
.portal-empty p { margin-bottom: 16px; }
.portal-article-list { display: flex; flex-direction: column; gap: 12px; }
.portal-article-item { display: flex; align-items: center; gap: 16px; padding: 14px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
.pal-thumb { display: block; flex-shrink: 0; overflow: hidden; border-radius: 4px; background: #f3f4f6; }
.pal-thumb img { width: 100px; height: 67px; object-fit: cover; display: block; }
.pal-body { flex: 1; min-width: 0; }
.pal-title { font-weight: 600; font-size: .92rem; color: #1A1A1A; display: block; margin: 3px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pal-title:hover { color: #C8102E; }
.pal-meta { font-family: 'Inter', sans-serif; font-size: .75rem; color: #9ca3af; }
.pal-remove { background: none; border: none; color: #d1d5db; font-size: 1rem; cursor: pointer; padding: 6px; border-radius: 4px; transition: 150ms; flex-shrink: 0; }
.pal-remove:hover { color: #C8102E; background: #fff5f5; }
</style>
