<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
<div class="auth-form-wrap">
  <h1>Nouveau mot de passe</h1>
  <p class="subtitle">Choisissez un mot de passe sécurisé (minimum 12 caractères).</p>

  <form method="post" action="<?= PORTAL_URL ?>/reinitialiser-mot-de-passe" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>
    <input type="hidden" name="token" value="<?= $e($token ?? '') ?>">

    <div class="form-group">
      <label for="password">Nouveau mot de passe</label>
      <input type="password" id="password" name="password" required
             autocomplete="new-password" placeholder="Minimum 12 caractères" minlength="12">
    </div>
    <div class="form-group">
      <label for="password_confirm">Confirmer le mot de passe</label>
      <input type="password" id="password_confirm" name="password_confirm" required
             autocomplete="new-password" placeholder="Répétez votre mot de passe">
    </div>
    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Réinitialiser</button>
    </div>
  </form>
</div>

<script nonce="<?= $e($cspNonce) ?>">
document.getElementById('password_confirm')?.addEventListener('input', function () {
  const pw = document.getElementById('password');
  this.setCustomValidity(this.value !== pw.value ? 'Les mots de passe ne correspondent pas.' : '');
});
</script>
