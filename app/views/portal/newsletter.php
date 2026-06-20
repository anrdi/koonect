<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
<div style="max-width:600px;">
  <div style="border-bottom:2px solid #1A1A1A;padding-bottom:12px;margin-bottom:32px;">
    <h1 style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;">Newsletter</h1>
  </div>

  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;margin-bottom:16px;">
    <h2 style="font-family:'Inter',sans-serif;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:16px;color:#374151;">
      Statut de mon abonnement
    </h2>

    <?php if (!$subscription): ?>
      <p style="color:#6b7280;font-size:.9rem;margin-bottom:16px;">
        Vous n'êtes pas encore abonné à la newsletter de <?= $e(APP_NAME) ?>.
      </p>
      <form method="post" action="<?= PORTAL_URL ?>/newsletter">
        <?= \Koonect\Helpers\Csrf::field() ?>
        <input type="hidden" name="action" value="subscribe">
        <button type="submit" class="btn btn--primary">S'abonner à la newsletter</button>
      </form>

    <?php elseif ($subscription['unsubscribed_at']): ?>
      <p style="color:#f59e0b;font-weight:600;font-size:.9rem;margin-bottom:8px;">
        ⚠️ Vous êtes désabonné depuis le <?= date('d/m/Y', strtotime($subscription['unsubscribed_at'])) ?>.
      </p>
      <form method="post" action="<?= PORTAL_URL ?>/newsletter">
        <?= \Koonect\Helpers\Csrf::field() ?>
        <input type="hidden" name="action" value="subscribe">
        <button type="submit" class="btn btn--primary">Se réabonner</button>
      </form>

    <?php elseif (!$subscription['confirmed_at']): ?>
      <p style="color:#f59e0b;font-weight:600;font-size:.9rem;margin-bottom:8px;">
        ⏳ En attente de confirmation. Vérifiez votre boîte mail.
      </p>
      <form method="post" action="<?= PORTAL_URL ?>/newsletter">
        <?= \Koonect\Helpers\Csrf::field() ?>
        <input type="hidden" name="action" value="subscribe">
        <button type="submit" class="btn btn--outline btn--sm">Renvoyer l'email de confirmation</button>
      </form>

    <?php else: ?>
      <p style="color:#16a34a;font-weight:600;font-size:.9rem;margin-bottom:16px;">
        ✅ Abonné depuis le <?= date('d/m/Y', strtotime($subscription['confirmed_at'])) ?>
      </p>
      <form method="post" action="<?= PORTAL_URL ?>/newsletter"
            onsubmit="return confirm('Confirmer le désabonnement ?')">
        <?= \Koonect\Helpers\Csrf::field() ?>
        <input type="hidden" name="action" value="unsubscribe">
        <button type="submit" class="btn btn--outline btn--sm">Se désabonner</button>
      </form>
    <?php endif; ?>
  </div>

  <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
    <p style="font-size:.78rem;color:#6b7280;line-height:1.6;">
      Conformément au RGPD, vous pouvez vous désabonner à tout moment.
      Votre adresse email ne sera jamais partagée avec des tiers.
      Voir notre <a href="<?= APP_URL ?>/rgpd" style="color:#0A3D6B;text-decoration:underline;">politique de confidentialité</a>.
    </p>
  </div>
</div>
