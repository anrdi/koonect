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
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Nouvel utilisateur</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Créer un compte rédaction ou abonné.</p>
  </div>
  <a href="<?= REDAC_URL ?>/utilisateurs" class="btn btn--outline btn--sm">Retour</a>
</div>

<div class="editor-meta-block" style="max-width:720px;">
  <form method="post" action="<?= REDAC_URL ?>/utilisateurs/nouveau">
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="rdash-inline-grid">
      <div class="editor-field">
        <label class="editor-label" for="email">Email</label>
        <input type="email" id="email" name="email" required class="rdash-input" value="<?= $e($_POST['email'] ?? '') ?>">
      </div>
      <div class="editor-field">
        <label class="editor-label" for="username">Nom d'utilisateur</label>
        <input type="text" id="username" name="username" required class="rdash-input" value="<?= $e($_POST['username'] ?? '') ?>">
      </div>
    </div>
    <div class="editor-field" style="margin-top:12px;">
      <label class="editor-label" for="display_name">Nom affiché</label>
      <input type="text" id="display_name" name="display_name" class="rdash-input" value="<?= $e($_POST['display_name'] ?? '') ?>">
    </div>
    <div class="rdash-inline-grid" style="margin-top:12px;">
      <div class="editor-field">
        <label class="editor-label" for="role">Rôle</label>
        <select id="role" name="role" class="editor-select">
          <?php foreach ($roles as $value => $label): ?>
            <option value="<?= $e($value) ?>" <?= (($_POST['role'] ?? 'journalist') === $value) ? 'selected' : '' ?>><?= $e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="editor-field">
        <label class="editor-label" for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required class="rdash-input" minlength="12" placeholder="Minimum 12 caractères">
      </div>
    </div>
    <p style="font-size:.78rem;color:#6b7280;margin-top:10px;">Le compte sera créé inactif puis activé selon le flux de vérification email.</p>
    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
      <button type="submit" class="btn btn--primary">Créer l'utilisateur</button>
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
