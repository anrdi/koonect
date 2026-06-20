<?php
/* ── DASHBOARD ──────────────────────────────────────────────────── */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$user = \Koonect\Core\Session::get('user');
$db   = \Koonect\Core\Database::getInstance();

$favoriteCount = (int)($db->fetch(
    'SELECT COUNT(*) AS cnt FROM favorites WHERE user_id = ?', [(int)$user['id']]
)['cnt'] ?? 0);

$historyCount = (int)($db->fetch(
    'SELECT COUNT(*) AS cnt FROM reading_history WHERE user_id = ?', [(int)$user['id']]
)['cnt'] ?? 0);

$commentCount = (int)($db->fetch(
    'SELECT COUNT(*) AS cnt FROM comments WHERE user_id = ? AND deleted_at IS NULL', [(int)$user['id']]
)['cnt'] ?? 0);

$recentHistory = $db->fetchAll(
    'SELECT a.title, a.slug, a.published_at, c.name AS category_name, rh.read_at
     FROM reading_history rh
     INNER JOIN articles a ON rh.article_id = a.id
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE rh.user_id = ? AND a.status = "published"
     ORDER BY rh.read_at DESC LIMIT 5',
    [(int)$user['id']]
);
?>

<div class="portal-dashboard">
  <div class="dashboard-header">
    <h1>Bonjour, <?= $e($user['display_name']) ?> 👋</h1>
    <p class="dashboard-subtitle">Votre espace personnel sur <?= $e(APP_NAME) ?></p>
  </div>

  <!-- Stats rapides -->
  <div class="dashboard-stats">
    <div class="stat-card">
      <div class="stat-value"><?= $favoriteCount ?></div>
      <div class="stat-label">Favoris</div>
      <a href="<?= PORTAL_URL ?>/favoris" class="stat-link">Voir →</a>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $historyCount ?></div>
      <div class="stat-label">Articles lus</div>
      <a href="<?= PORTAL_URL ?>/historique" class="stat-link">Voir →</a>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $commentCount ?></div>
      <div class="stat-label">Commentaires</div>
      <a href="<?= PORTAL_URL ?>/commentaires" class="stat-link">Voir →</a>
    </div>
  </div>

  <!-- Lecture récente -->
  <?php if (!empty($recentHistory)): ?>
  <div class="dashboard-section">
    <h2 class="dashboard-section-title">Récemment lus</h2>
    <div class="recent-reads">
      <?php foreach ($recentHistory as $item): ?>
      <div class="recent-read-item">
        <?php if ($item['category_name']): ?>
          <span class="article-category article-category--xs"><?= $e($item['category_name']) ?></span>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/article/<?= $e($item['slug']) ?>" class="recent-read-title"><?= $e($item['title']) ?></a>
        <time class="recent-read-date" datetime="<?= $e($item['read_at']) ?>">
          Lu le <?= date('d/m/Y à H\hi', strtotime($item['read_at'])) ?>
        </time>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= PORTAL_URL ?>/historique" class="btn btn--outline btn--sm">Voir tout l'historique</a>
  </div>
  <?php endif; ?>

  <!-- Liens rapides -->
  <div class="dashboard-section">
    <h2 class="dashboard-section-title">Mon compte</h2>
    <div class="dashboard-links">
      <a href="<?= PORTAL_URL ?>/profil" class="dashboard-link-card">
        <span class="dlc-icon">👤</span>
        <span class="dlc-label">Mon profil</span>
        <span class="dlc-desc">Modifier mes informations</span>
      </a>
      <a href="<?= PORTAL_URL ?>/newsletter" class="dashboard-link-card">
        <span class="dlc-icon">📧</span>
        <span class="dlc-label">Newsletter</span>
        <span class="dlc-desc">Gérer mes abonnements</span>
      </a>
      <a href="<?= PORTAL_URL ?>/donnees" class="dashboard-link-card">
        <span class="dlc-icon">🔒</span>
        <span class="dlc-label">Mes données</span>
        <span class="dlc-desc">RGPD · Export · Suppression</span>
      </a>
      <a href="<?= PORTAL_URL ?>/2fa/configurer" class="dashboard-link-card">
        <span class="dlc-icon">🔐</span>
        <span class="dlc-label">Sécurité</span>
        <span class="dlc-desc">Double authentification</span>
      </a>
    </div>
  </div>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.portal-dashboard { max-width: 800px; }
.dashboard-header { margin-bottom: 32px; }
.dashboard-header h1 { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; margin-bottom: 4px; }
.dashboard-subtitle { color: #6b7280; font-size: .9rem; }
.dashboard-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
.stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; }
.stat-value { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 900; color: #C8102E; line-height: 1; margin-bottom: 4px; }
.stat-label { font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; font-weight: 600; margin-bottom: 12px; }
.stat-link { font-size: .78rem; color: #0A3D6B; text-decoration: underline; }
.dashboard-section { margin-bottom: 32px; }
.dashboard-section-title { font-family: 'Inter', sans-serif; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #374151; border-bottom: 2px solid #1A1A1A; padding-bottom: 8px; margin-bottom: 16px; }
.recent-reads { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
.recent-read-item { padding: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; }
.recent-read-title { display: block; font-weight: 600; font-size: .9rem; color: #1A1A1A; margin: 4px 0; }
.recent-read-title:hover { color: #C8102E; }
.recent-read-date { font-size: .75rem; color: #9ca3af; font-family: 'Inter', sans-serif; }
.dashboard-links { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.dashboard-link-card { display: flex; flex-direction: column; gap: 4px; padding: 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; text-decoration: none; transition: 150ms; }
.dashboard-link-card:hover { border-color: #C8102E; box-shadow: 0 2px 8px rgba(200,16,46,.1); }
.dlc-icon { font-size: 1.4rem; }
.dlc-label { font-weight: 700; font-size: .9rem; color: #1A1A1A; }
.dlc-desc { font-size: .78rem; color: #6b7280; }
@media (max-width: 600px) {
  .dashboard-stats { grid-template-columns: 1fr; }
  .dashboard-links { grid-template-columns: 1fr; }
}
</style>
