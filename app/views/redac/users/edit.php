<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$roles = [
    'admin' => 'Administrateur',
    'director' => 'Directeur',
    'chief_editor' => 'Rédacteur en chef',
    'journalist' => 'Journaliste',
    'proofreader' => 'Relecteur',
    'moderator' => 'Modérateur',
    'subscriber' => 'Abonné',
];
$statuses = [
    'active' => 'Actif',
    'inactive' => 'Inactif',
    'banned' => 'Banni',
    'deleted' => 'Supprimé',
];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Modifier l'utilisateur</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Ajuster le rôle, le statut et le nom affiché.</p>
  </div>
  <a href="<?= REDAC_URL ?>/utilisateurs" class="btn btn--outline btn--sm">Retour</a>
</div>

<div class="editor-meta-block" style="max-width:780px;">
  <form method="post" action="<?= REDAC_URL ?>/utilisateurs/<?= (int)$editUser['id'] ?>/modifier">
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="rdash-inline-grid">
      <div class="editor-field">
        <label class="editor-label">Email</label>
        <input type="text" value="<?= $e($editUser['email']) ?>" class="rdash-input" disabled>
      </div>
      <div class="editor-field">
        <label class="editor-label">Nom d'utilisateur</label>
        <input type="text" value="<?= $e($editUser['username']) ?>" class="rdash-input" disabled>
      </div>
    </div>
    <div class="editor-field" style="margin-top:12px;">
      <label class="editor-label" for="display_name">Nom affiché</label>
      <input type="text" id="display_name" name="display_name" class="rdash-input" value="<?= $e($editUser['display_name']) ?>">
    </div>
    <div class="rdash-inline-grid" style="margin-top:12px;">
      <div class="editor-field">
        <label class="editor-label" for="role">Rôle</label>
        <select id="role" name="role" class="editor-select">
          <?php foreach ($roles as $value => $label): ?>
            <option value="<?= $e($value) ?>" <?= ($editUser['role'] === $value) ? 'selected' : '' ?>><?= $e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="editor-field">
        <label class="editor-label" for="status">Statut</label>
        <select id="status" name="status" class="editor-select">
          <?php foreach ($statuses as $value => $label): ?>
            <option value="<?= $e($value) ?>" <?= ($editUser['status'] === $value) ? 'selected' : '' ?>><?= $e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
      <button type="submit" class="btn btn--primary">Enregistrer</button>
      <a href="<?= REDAC_URL ?>/utilisateurs" class="btn btn--ghost">Annuler</a>
    </div>
  </form>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-inline-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
.rdash-input { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:4px; font-family:'Inter',sans-serif; font-size:.88rem; outline:none; background:#fff; }
.rdash-input:focus { border-color:#0A3D6B; box-shadow:0 0 0 3px rgba(10,61,107,.08); }
@media (max-width: 640px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-inline-grid { grid-template-columns:1fr; }
}
</style>
