<?php /* forgot-password.php */ ?>
<div class="auth-form-wrap">
  <a href="<?= PORTAL_URL ?>/connexion" class="portal-back-link">← Retour à la connexion</a>
  <h1>Mot de passe oublié</h1>
  <p class="subtitle">Entrez votre email pour recevoir un lien de réinitialisation.</p>

  <form method="post" action="<?= PORTAL_URL ?>/mot-de-passe-oublie" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="form-group">
      <label for="email">Adresse email</label>
      <input type="email" id="email" name="email" required
             autocomplete="email" placeholder="vous@exemple.fr">
    </div>
    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Envoyer le lien</button>
    </div>
  </form>
</div>
