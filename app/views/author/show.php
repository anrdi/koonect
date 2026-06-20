<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : null;
$timeAgo = function(?string $date): string {
    if (!$date) return 'Récemment';
    $diff = time() - strtotime($date);
    if ($diff < 3600)  return (int)($diff / 60) . ' min';
    if ($diff < 86400) return (int)($diff / 3600) . 'h';
    return date('d/m/Y', strtotime($date));
};
?>

<div class="container author-page">

  <!-- En-tête auteur -->
  <header class="author-header">
    <div class="author-header-inner">
      <?php if ($author['avatar']): ?>
        <img src="<?= $e($imgUrl($author['avatar'])) ?>" alt="<?= $e($author['display_name']) ?>"
             class="author-header-avatar" width="80" height="80" loading="lazy">
      <?php else: ?>
        <div class="author-header-placeholder" aria-hidden="true">
          <?= mb_strtoupper(mb_substr($author['display_name'], 0, 1)) ?>
        </div>
      <?php endif; ?>
      <div class="author-header-info">
        <h1 class="author-header-name"><?= $e($author['display_name']) ?></h1>
        <p class="author-header-role">
          <?= match($author['role']) {
              'admin'        => 'Administrateur',
              'director'     => 'Directeur de la publication',
              'chief_editor' => 'Rédacteur en chef',
              'journalist'   => 'Journaliste',
              'proofreader'  => 'Correcteur',
              default        => 'Rédacteur',
          } ?>
          · <?= $total ?> article<?= $total > 1 ? 's' : '' ?>
        </p>
        <?php
        $db  = \Koonect\Core\Database::getInstance();
        $bio = $db->fetch('SELECT bio FROM subscriber_profiles WHERE user_id = ? LIMIT 1', [(int)$author['id']]);
        if ($bio && $bio['bio']):
        ?>
          <p class="author-header-bio"><?= $e($bio['bio']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Articles de l'auteur -->
  <div class="home-main-grid">
    <div>
      <h2 class="section-title">Articles de <?= $e($author['display_name']) ?></h2>

      <?php if (empty($articles)): ?>
        <p style="color:#6b7280;font-size:.9rem;padding:24px 0;">Aucun article publié pour l'instant.</p>
      <?php else: ?>
      <div class="articles-grid">
        <?php foreach ($articles as $art): ?>
        <article class="article-card">
          <?php if (!empty($art['featured_image_thumb'])): ?>
          <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>" class="article-card-image" tabindex="-1" aria-hidden="true">
            <img src="<?= $e($imgUrl($art['featured_image_thumb'])) ?>"
                 alt="<?= $e($art['featured_image_alt'] ?? '') ?>" width="400" height="267" loading="lazy">
          </a>
          <?php endif; ?>
          <div class="article-card-body">
            <?php if (!empty($art['category_name'])): ?>
              <a href="<?= APP_URL ?>/categorie/<?= $e($art['category_slug']) ?>" class="article-category"><?= $e($art['category_name']) ?></a>
            <?php endif; ?>
            <h3 class="article-card-title">
              <a href="<?= APP_URL ?>/article/<?= $e($art['slug']) ?>"><?= $e($art['title']) ?></a>
            </h3>
            <?php if (!empty($art['chapo'])): ?>
              <p class="article-card-chapo"><?= $e(mb_strimwidth($art['chapo'], 0, 130, '…')) ?></p>
            <?php endif; ?>
            <div class="article-meta">
              <time datetime="<?= $e($art['published_at']) ?>"><?= $timeAgo($art['published_at']) ?></time>
              <?php if (!empty($art['reading_time'])): ?>
                <span class="article-readtime"><?= (int)$art['reading_time'] ?> min</span>
              <?php endif; ?>
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

    <aside class="home-sidebar">
      <div class="sidebar-block">
        <h2 class="sidebar-title">À propos</h2>
        <div class="author-card">
          <?php if ($author['avatar']): ?>
            <img src="<?= $e($imgUrl($author['avatar'])) ?>" alt="<?= $e($author['display_name']) ?>"
                 class="author-card-avatar" width="56" height="56" loading="lazy">
          <?php endif; ?>
          <div>
            <span class="author-card-name"><?= $e($author['display_name']) ?></span>
            <span style="display:block;font-size:.75rem;color:#9ca3af;font-family:'Inter',sans-serif;margin-top:2px;">
              @<?= $e($author['username']) ?>
            </span>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div>

<style>
.author-page { padding-top: 32px; padding-bottom: 64px; }
.author-header { padding-bottom: 32px; border-bottom: 3px solid #1A1A1A; margin-bottom: 40px; }
.author-header-inner { display: flex; gap: 24px; align-items: flex-start; }
.author-header-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.author-header-placeholder { width: 80px; height: 80px; border-radius: 50%; background: #0A3D6B; color: #fff; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 900; flex-shrink: 0; }
.author-header-name { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 900; margin-bottom: 6px; }
.author-header-role { font-family: 'Inter', sans-serif; font-size: .82rem; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
.author-header-bio { font-size: .92rem; color: #4b5563; line-height: 1.6; max-width: 600px; }
@media (max-width: 600px) { .author-header-inner { flex-direction: column; } }
</style>
