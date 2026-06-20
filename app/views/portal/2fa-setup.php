<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
<div class="auth-form-wrap" style="max-width:520px;">
  <h1>Activer la 2FA</h1>
  <p class="subtitle">Scannez le QR code avec Google Authenticator, Authy ou toute autre application TOTP.</p>

  <div style="text-align:center;margin:24px 0;">
    <img src="<?= $e($qrUrl) ?>" alt="QR Code 2FA" width="200" height="200"
         style="border:4px solid #f3f4f6;border-radius:8px;">
  </div>

  <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px 16px;margin-bottom:20px;font-family:monospace;text-align:center;font-size:1rem;letter-spacing:.2em;word-break:break-all;">
    <?= $e($secret) ?>
  </div>
  <p style="font-size:.78rem;color:#6b7280;text-align:center;margin-bottom:24px;">
    Si vous ne pouvez pas scanner le QR code, entrez cette clé manuellement dans votre application.
  </p>

  <form method="post" action="<?= PORTAL_URL ?>/2fa/configurer" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="form-group">
      <label for="code">Code de vérification (6 chiffres)</label>
      <input type="text" id="code" name="code" required
             inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
             placeholder="123456" autocomplete="one-time-code"
             style="font-size:1.2rem;letter-spacing:.2em;text-align:center;">
    </div>
    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Activer la 2FA</button>
    </div>
  </form>

  <div style="text-align:center;margin-top:16px;">
    <a href="<?= PORTAL_URL ?>/profil" class="btn-link">Annuler</a>
  </div>
</div>
