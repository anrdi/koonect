<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$users = $users ?? [];
$roleLabels = [
    'admin' => 'Admin',
    'director' => 'Directeur',
    'chief_editor' => 'Rédacteur en chef',
    'journalist' => 'Journaliste',
    'proofreader' => 'Relecteur',
    'moderator' => 'Modérateur',
    'subscriber' => 'Abonné',
];
$statusColors = [
    'active' => '#16a34a',
    'inactive' => '#f59e0b',
    'banned' => '#dc2626',
    'deleted' => '#6b7280',
];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Utilisateurs</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Gérer les accès à la rédaction.</p>
  </div>
  <a href="<?= REDAC_URL ?>/utilisateurs/nouveau" class="btn btn--primary btn--sm">+ Nouvel utilisateur</a>
</div>

<?php if (empty($users)): ?>
  <div class="rdash-empty">Aucun utilisateur trouvé.</div>
<?php else: ?>
  <div class="rdash-table-wrap">
    <table class="rdash-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Statut</th>
          <th>Dernière connexion</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td class="rdash-table-meta"><?= (int)$user['id'] ?></td>
            <td>
              <div style="font-weight:700;color:#1A1A1A;"><?= $e($user['display_name']) ?></div>
              <div class="rdash-table-meta">@<?= $e($user['username']) ?></div>
            </td>
            <td class="rdash-table-meta"><?= $e($user['email']) ?></td>
            <td class="rdash-table-meta"><?= $e($roleLabels[$user['role']] ?? $user['role']) ?></td>
            <td>
              <span class="rdash-user-status" style="background:<?= $statusColors[$user['status']] ?? '#6b7280' ?>18;color:<?= $statusColors[$user['status']] ?? '#6b7280' ?>;">
                <?= $e($user['status']) ?>
              </span>
            </td>
            <td class="rdash-table-meta"><?= !empty($user['last_login_at']) ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : 'Jamais' ?></td>
            <td class="rdash-table-actions">
              <a href="<?= REDAC_URL ?>/utilisateurs/<?= (int)$user['id'] ?>/modifier" class="rdash-action-btn" title="Modifier">✏️</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-table-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.rdash-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.rdash-table thead tr { background:#f9fafb; border-bottom:2px solid #e5e7eb; }
.rdash-table th { padding:10px 14px; text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; font-weight:700; }
.rdash-table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.rdash-table tbody tr:hover { background:#f9fafb; }
.rdash-table tbody tr:last-child td { border-bottom:none; }
.rdash-table-meta { color:#6b7280; font-size:.8rem; }
.rdash-table-actions { display:flex; gap:4px; align-items:center; }
.rdash-action-btn { background:none; border:none; cursor:pointer; font-size:.9rem; padding:4px; border-radius:3px; text-decoration:none; }
.rdash-action-btn:hover { background:#f3f4f6; }
.rdash-user-status { display:inline-block; padding:4px 10px; border-radius:2px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
@media (max-width: 720px) {
  .rdash-page-header { flex-direction:column; }
}
</style>
