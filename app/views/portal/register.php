<?php $pageTitle = 'Inscription — ' . APP_NAME; ?>
<div class="auth-form-wrap">
  <a href="<?= APP_URL ?>" class="portal-back-link">← Retour au journal</a>
  <h1>Créer un compte</h1>
  <p class="subtitle">Rejoignez la communauté <?= htmlspecialchars(APP_NAME, ENT_QUOTES) ?></p>

  <form method="post" action="<?= PORTAL_URL ?>/inscription" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>

    <div class="form-group">
      <label for="email">Adresse email <span style="color:#C8102E">*</span></label>
      <input type="email" id="email" name="email" required
             autocomplete="email" placeholder="vous@exemple.fr"
             value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>">
    </div>

    <div class="form-group">
      <label for="username">Nom d'utilisateur <span style="color:#C8102E">*</span></label>
      <input type="text" id="username" name="username" required
             autocomplete="username" placeholder="jean_dupont"
             pattern="[a-zA-Z0-9_-]+" minlength="3" maxlength="60"
             value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>">
      <span class="helper">Lettres, chiffres, tirets et underscores uniquement (min. 3 caractères)</span>
    </div>

    <div class="form-group">
      <label for="password">Mot de passe <span style="color:#C8102E">*</span></label>
      <input type="password" id="password" name="password" required
             autocomplete="new-password" placeholder="Minimum 12 caractères"
             minlength="12">
      <div class="password-strength" id="password-strength" aria-live="polite"></div>
    </div>

    <div class="form-group">
      <label for="password_confirm">Confirmer le mot de passe <span style="color:#C8102E">*</span></label>
      <input type="password" id="password_confirm" name="password_confirm" required
             autocomplete="new-password" placeholder="Répétez votre mot de passe">
    </div>

    <div class="form-group">
      <label class="form-checkbox">
        <input type="checkbox" name="gdpr_consent" value="1" required>
        <span>J'accepte la <a href="<?= APP_URL ?>/rgpd" target="_blank">politique de confidentialité</a> et les <a href="<?= APP_URL ?>/cgu" target="_blank">CGU</a> de <?= htmlspecialchars(APP_NAME, ENT_QUOTES) ?>. <span style="color:#C8102E">*</span></span>
      </label>
    </div>

    <div class="form-group">
      <label class="form-checkbox">
        <input type="checkbox" name="newsletter_opt_in" value="1">
        <span>Je souhaite recevoir la newsletter de <?= htmlspecialchars(APP_NAME, ENT_QUOTES) ?> (vous pouvez vous désabonner à tout moment).</span>
      </label>
    </div>

    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Créer mon compte</button>
    </div>

    <div class="form-divider">Déjà un compte ?</div>
    <a href="<?= PORTAL_URL ?>/connexion" class="btn btn--outline btn--full">Se connecter</a>
  </form>
</div>

<script nonce="<?= htmlspecialchars($cspNonce) ?>">
(function () {
  const pw  = document.getElementById('password');
  const bar = document.getElementById('password-strength');
  if (!pw || !bar) return;

  pw.addEventListener('input', () => {
    const v = pw.value;
    let score = 0;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^a-zA-Z0-9]/.test(v)) score++;

    const labels = ['', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    const colors = ['', '#dc2626', '#f59e0b', '#16a34a', '#0A3D6B'];
    bar.textContent  = v.length ? 'Force : ' + (labels[score] || 'Faible') : '';
    bar.style.color  = colors[score] || '#dc2626';
    bar.style.fontSize = '.75rem';
    bar.style.marginTop = '4px';
  });

  // Vérification concordance
  const confirm = document.getElementById('password_confirm');
  confirm?.addEventListener('input', () => {
    if (confirm.value && confirm.value !== pw.value) {
      confirm.setCustomValidity('Les mots de passe ne correspondent pas.');
    } else {
      confirm.setCustomValidity('');
    }
  });
})();
</script>
