<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
<div class="container" style="padding-top:80px;padding-bottom:80px;text-align:center;max-width:500px;margin:0 auto;">
  <?php if ($success): ?>
    <div style="font-size:3rem;margin-bottom:16px;">✅</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;margin-bottom:12px;">
      Désabonnement confirmé
    </h1>
    <p style="color:#6b7280;margin-bottom:24px;">
      Vous êtes désormais désabonné de la newsletter de <?= $e(APP_NAME) ?>.
      Vous ne recevrez plus nos emails.
    </p>
    <p style="color:#9ca3af;font-size:.82rem;margin-bottom:24px;">
      Si vous avez fait une erreur, vous pouvez vous réabonner à tout moment depuis votre espace abonné.
    </p>
  <?php else: ?>
    <div style="font-size:3rem;margin-bottom:16px;">❌</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;margin-bottom:12px;">
      Lien invalide
    </h1>
    <p style="color:#6b7280;margin-bottom:24px;">
      Ce lien de désabonnement est invalide ou a déjà été utilisé.
    </p>
  <?php endif; ?>
  <a href="<?= APP_URL ?>" class="btn btn--outline">Retour à l'accueil</a>
</div>
