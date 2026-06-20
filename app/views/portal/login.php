<?php $pageTitle = 'Connexion — ' . APP_NAME; ?>
<div class="auth-form-wrap">
  <a href="<?= APP_URL ?>" class="portal-back-link">← Retour au journal</a>
  <h1>Connexion</h1>
  <p class="subtitle">Accédez à votre espace abonné</p>

  <form method="post" action="<?= PORTAL_URL ?>/connexion" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="form-group">
      <label for="email">Adresse email</label>
      <input type="email" id="email" name="email" required
             autocomplete="email" placeholder="vous@exemple.fr"
             value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>">
    </div>
    <div class="form-group">
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required
             autocomplete="current-password" placeholder="••••••••••••">
    </div>
    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Se connecter</button>
    </div>
    <div class="form-links">
      <a href="<?= PORTAL_URL ?>/mot-de-passe-oublie">Mot de passe oublié ?</a>
      <a href="<?= PORTAL_URL ?>/inscription">Créer un compte</a>
    </div>
  </form>
</div>
