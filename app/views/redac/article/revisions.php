<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$revisions = $revisions ?? [];
$article = $article ?? null;
$statusLabels = [
    'draft' => 'Brouillon',
    'review' => 'Relecture',
    'validation' => 'Validation',
    'published' => 'Publié',
    'archived' => 'Archivé',
];
$statusColors = [
    'draft' => '#f59e0b',
    'review' => '#3b82f6',
    'validation' => '#8b5cf6',
    'published' => '#16a34a',
    'archived' => '#6b7280',
];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Historique des révisions</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;"><?= $article ? $e($article['title']) : 'Article introuvable' ?></p>
  </div>
  <a href="<?= REDAC_URL ?>/articles/<?= (int)($article['id'] ?? 0) ?>/modifier" class="btn btn--outline btn--sm">Retour à l'article</a>
</div>

<?php if (!$article): ?>
  <div class="rdash-empty">Article introuvable.</div>
<?php elseif (empty($revisions)): ?>
  <div class="rdash-empty">Aucune révision enregistrée pour cet article.</div>
<?php else: ?>
  <div class="rdash-article-info editor-meta-block" style="margin-bottom:16px;">
    <div class="rdash-article-line">
      <strong>Statut actuel</strong>
      <span class="editor-status-badge editor-status-badge--<?= $e($article['status']) ?>" style="font-size:.65rem;">
        <?= $e($statusLabels[$article['status']] ?? $article['status']) ?>
      </span>
    </div>
    <div class="rdash-article-line">
      <strong>Slug</strong>
      <span><?= $e($article['slug']) ?></span>
    </div>
  </div>

  <div class="rdash-table-wrap">
    <table class="rdash-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Éditeur</th>
          <th>De</th>
          <th>Vers</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($revisions as $revision): ?>
          <tr>
            <td class="rdash-table-meta"><?= date('d/m/Y H:i', strtotime($revision['created_at'])) ?></td>
            <td class="rdash-table-meta"><?= $e($revision['editor_name'] ?? '—') ?></td>
            <td>
              <span class="rdash-revision-status" style="background:<?= $statusColors[$revision['status_from']] ?? '#e5e7eb' ?>18;color:<?= $statusColors[$revision['status_from']] ?? '#6b7280' ?>;">
                <?= $e($statusLabels[$revision['status_from']] ?? $revision['status_from']) ?>
              </span>
            </td>
            <td>
              <span class="rdash-revision-status" style="background:<?= $statusColors[$revision['status_to']] ?? '#e5e7eb' ?>18;color:<?= $statusColors[$revision['status_to']] ?? '#6b7280' ?>;">
                <?= $e($statusLabels[$revision['status_to']] ?? $revision['status_to']) ?>
              </span>
            </td>
            <td class="rdash-table-meta"><?= $e($revision['note'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
.rdash-table-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.rdash-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.rdash-table thead tr { background:#f9fafb; border-bottom:2px solid #e5e7eb; }
.rdash-table th { padding:10px 14px; text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; font-weight:700; }
.rdash-table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.rdash-table tbody tr:last-child td { border-bottom:none; }
.rdash-table-meta { color:#6b7280; font-size:.8rem; }
.rdash-revision-status { display:inline-block; padding:4px 10px; border-radius:2px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
.rdash-article-info { display:flex; flex-direction:column; gap:10px; }
.rdash-article-line { display:flex; align-items:center; gap:10px; font-size:.88rem; }
.rdash-article-line strong { min-width:100px; color:#374151; }
@media (max-width: 720px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-article-line { flex-direction:column; align-items:flex-start; }
}
</style>
