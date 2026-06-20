<?php
$e    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$user = \Koonect\Core\Session::get('user');
$statusLabels = ['draft'=>'Brouillon','review'=>'Relecture','validation'=>'Validation','published'=>'Publié','archived'=>'Archivé'];
$statusColors = ['draft'=>'#f59e0b','review'=>'#3b82f6','validation'=>'#8b5cf6','published'=>'#16a34a','archived'=>'#9ca3af'];
?>

<div class="rdash-page-header">
  <h1 class="rdash-page-title">Articles</h1>
  <a href="<?= REDAC_URL ?>/articles/nouveau" class="btn btn--primary btn--sm">+ Nouvel article</a>
</div>

<!-- Filtres -->
<div class="rdash-filters">
  <form method="get" action="<?= REDAC_URL ?>/articles" class="rdash-filter-form">
    <input type="text" name="q" placeholder="Rechercher par titre…" value="<?= $e($search ?? '') ?>" class="rdash-filter-input">
    <select name="status" class="rdash-filter-select">
      <option value="">Tous les statuts</option>
      <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= $e($val) ?>" <?= ($status ?? '') === $val ? 'selected' : '' ?>><?= $e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn--outline btn--sm">Filtrer</button>
    <?php if ($search || $status): ?>
      <a href="<?= REDAC_URL ?>/articles" class="btn btn--ghost btn--sm">Effacer</a>
    <?php endif; ?>
  </form>
</div>

<!-- Tableau -->
<div class="rdash-table-wrap">
  <?php if (empty($articles)): ?>
    <div class="rdash-empty">Aucun article trouvé.</div>
  <?php else: ?>
  <table class="rdash-table">
    <thead>
      <tr>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Catégorie</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Vues</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($articles as $art): ?>
      <tr>
        <td class="rdash-table-title">
          <a href="<?= REDAC_URL ?>/articles/<?= (int)$art['id'] ?>/modifier"><?= $e(mb_strimwidth($art['title'], 0, 80, '…')) ?></a>
          <?php if ($art['status'] === 'published'): ?>
            <a href="<?= APP_URL ?>/article/<?= $e($art['slug'] ?? '') ?>" target="_blank" class="rdash-table-preview" title="Voir l'article publié">↗</a>
          <?php endif; ?>
        </td>
        <td class="rdash-table-meta"><?= $e($art['author_name']) ?></td>
        <td class="rdash-table-meta"><?= $e($art['category_name'] ?? '—') ?></td>
        <td>
          <span class="editor-status-badge editor-status-badge--<?= $e($art['status']) ?>" style="font-size:.65rem;">
            <?= $e($statusLabels[$art['status']] ?? $art['status']) ?>
          </span>
        </td>
        <td class="rdash-table-meta">
          <?= $art['published_at'] ? date('d/m/Y', strtotime($art['published_at'])) : date('d/m/Y', strtotime($art['created_at'])) ?>
        </td>
        <td class="rdash-table-meta"><?= number_format((int)$art['views_count'], 0, ',', ' ') ?></td>
        <td class="rdash-table-actions">
          <a href="<?= REDAC_URL ?>/articles/<?= (int)$art['id'] ?>/modifier" class="rdash-action-btn" title="Modifier">✏️</a>
          <?php if (in_array($user['role'], ['admin','director','chief_editor'])): ?>
            <form method="post" action="<?= REDAC_URL ?>/articles/<?= (int)$art['id'] ?>/supprimer"
                  onsubmit="return confirm('Archiver cet article ?')" style="display:inline;">
              <?= \Koonect\Helpers\Csrf::field() ?>
              <button type="submit" class="rdash-action-btn" title="Archiver">🗄</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.rdash-page-title { font-size: 1.3rem; font-weight: 700; color: #1A1A1A; }
.rdash-filters { margin-bottom: 16px; }
.rdash-filter-form { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.rdash-filter-input, .rdash-filter-select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: .83rem; outline: none; font-family: 'Inter', sans-serif; }
.rdash-filter-input { min-width: 240px; }
.rdash-filter-input:focus, .rdash-filter-select:focus { border-color: #0A3D6B; }
.rdash-table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.rdash-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.rdash-table thead tr { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
.rdash-table th { padding: 10px 14px; text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; font-weight: 700; }
.rdash-table td { padding: 10px 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.rdash-table tbody tr:hover { background: #f9fafb; }
.rdash-table tbody tr:last-child td { border-bottom: none; }
.rdash-table-title a { font-weight: 600; color: #1A1A1A; text-decoration: none; }
.rdash-table-title a:hover { color: #C8102E; }
.rdash-table-preview { margin-left: 6px; font-size: .75rem; color: #9ca3af; }
.rdash-table-meta { color: #6b7280; font-size: .8rem; }
.rdash-table-actions { display: flex; gap: 4px; align-items: center; }
.rdash-action-btn { background: none; border: none; cursor: pointer; font-size: .9rem; padding: 4px; border-radius: 3px; text-decoration: none; }
.rdash-action-btn:hover { background: #f3f4f6; }
.rdash-empty { padding: 40px; text-align: center; color: #9ca3af; font-size: .9rem; }
</style>
