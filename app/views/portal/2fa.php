<?php /* 2fa.php - Saisie code 2FA */ ?>
<div class="auth-form-wrap">
  <h1>Authentification à deux facteurs</h1>
  <p class="subtitle">Saisissez le code à 6 chiffres affiché dans votre application d'authentification.</p>

  <form method="post" action="<?= PORTAL_URL ?>/2fa" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="form-group">
      <label for="code">Code de vérification</label>
      <input type="text" id="code" name="code" required
             autocomplete="one-time-code"
             inputmode="numeric"
             pattern="[0-9]{6}"
             maxlength="6"
             placeholder="000000"
             style="font-size:1.4rem;letter-spacing:.3em;text-align:center;"
             autofocus>
    </div>
    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Vérifier</button>
    </div>
    <div style="text-align:center;margin-top:12px;">
      <a href="<?= PORTAL_URL ?>/deconnexion" style="font-size:.78rem;color:#9ca3af;">Annuler et se déconnecter</a>
    </div>
  </form>
</div>
