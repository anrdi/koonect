<?php
$e    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$user = \Koonect\Core\Session::get('user');
$statusLabels = [
    'draft'      => 'Brouillons',
    'review'     => 'En relecture',
    'validation' => 'En validation',
    'published'  => 'Publiés',
    'archived'   => 'Archivés',
];
$statusColors = [
    'draft'      => '#f59e0b',
    'review'     => '#3b82f6',
    'validation' => '#8b5cf6',
    'published'  => '#16a34a',
    'archived'   => '#9ca3af',
];
?>

<div class="redac-dashboard">
  <div class="redac-welcome">
    <h1>Bonjour, <?= $e($user['display_name']) ?> 👋</h1>
    <p>Bienvenue dans l'espace rédaction de <strong><?= $e(APP_NAME) ?></strong></p>
  </div>

  <!-- Stats articles -->
  <div class="rdash-stats">
    <?php foreach ($stats as $status => $count): ?>
    <div class="rdash-stat-card">
      <div class="rdash-stat-value" style="color:<?= $e($statusColors[$status] ?? '#6b7280') ?>"><?= (int)$count ?></div>
      <div class="rdash-stat-label"><?= $e($statusLabels[$status] ?? $status) ?></div>
      <a href="<?= REDAC_URL ?>/articles?status=<?= $e($status) ?>" class="rdash-stat-link">Voir →</a>
    </div>
    <?php endforeach; ?>
    <div class="rdash-stat-card">
      <div class="rdash-stat-value" style="color:#C8102E"><?= (int)$totalComments ?></div>
      <div class="rdash-stat-label">Commentaires</div>
      <a href="<?= REDAC_URL ?>/commentaires" class="rdash-stat-link">Modérer →</a>
    </div>
  </div>

  <!-- Actions rapides -->
  <div class="rdash-quick-actions">
    <a href="<?= REDAC_URL ?>/articles/nouveau" class="rdash-quick-btn rdash-quick-btn--primary">
      ✏️ Rédiger un article
    </a>
    <a href="<?= REDAC_URL ?>/medias" class="rdash-quick-btn">
      🖼 Médiathèque
    </a>
    <a href="<?= APP_URL ?>" target="_blank" class="rdash-quick-btn">
      🌐 Voir le site →
    </a>
  </div>

  <!-- Articles en attente de validation -->
  <?php if (!empty($pending)): ?>
  <div class="rdash-pending">
    <h2 class="rdash-section-title">En attente d'action</h2>
    <div class="rdash-pending-list">
      <?php foreach ($pending as $art): ?>
      <div class="rdash-pending-item">
        <div class="rdash-pending-info">
          <span class="editor-status-badge editor-status-badge--<?= $e($art['status']) ?>"><?= $e($art['status']) ?></span>
          <a href="<?= REDAC_URL ?>/articles/<?= (int)$art['id'] ?>/modifier" class="rdash-pending-title"><?= $e($art['title']) ?></a>
          <span class="rdash-pending-meta">Par <?= $e($art['author_name']) ?> · <?= date('d/m/Y H:i', strtotime($art['updated_at'])) ?></span>
        </div>
        <a href="<?= REDAC_URL ?>/articles/<?= (int)$art['id'] ?>/modifier" class="btn btn--sm btn--outline">Ouvrir</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.redac-welcome { margin-bottom: 24px; }
.redac-welcome h1 { font-size: 1.5rem; font-weight: 700; color: #1A1A1A; margin-bottom: 4px; }
.redac-welcome p { color: #6b7280; font-size: .9rem; }
.rdash-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; }
.rdash-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; text-align: center; }
.rdash-stat-card--alert { border-color: #fca5a5; background: #fff5f5; }
.rdash-stat-value { font-size: 2rem; font-weight: 900; line-height: 1; margin-bottom: 4px; font-family: 'Playfair Display', serif; }
.rdash-stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin-bottom: 10px; font-weight: 600; }
.rdash-stat-link { font-size: .75rem; color: #0A3D6B; text-decoration: underline; }
.rdash-quick-actions { display: flex; gap: 10px; margin-bottom: 32px; flex-wrap: wrap; }
.rdash-quick-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: .85rem; font-weight: 600; background: #fff; color: #1A1A1A; text-decoration: none; transition: 150ms; }
.rdash-quick-btn:hover { border-color: #1A1A1A; }
.rdash-quick-btn--primary { background: #C8102E; color: #fff; border-color: #C8102E; }
.rdash-quick-btn--primary:hover { background: #a80d24; }
.rdash-section-title { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #374151; border-bottom: 2px solid #1A1A1A; padding-bottom: 8px; margin-bottom: 16px; }
.rdash-pending-list { display: flex; flex-direction: column; gap: 8px; }
.rdash-pending-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; gap: 12px; }
.rdash-pending-info { display: flex; flex-direction: column; gap: 3px; flex: 1; min-width: 0; }
.rdash-pending-title { font-weight: 600; font-size: .9rem; color: #1A1A1A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rdash-pending-title:hover { color: #C8102E; }
.rdash-pending-meta { font-size: .75rem; color: #9ca3af; }
</style>
