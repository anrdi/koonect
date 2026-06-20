<?php
$e    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$user = \Koonect\Core\Session::get('user');
$db   = \Koonect\Core\Database::getInstance();
$sp   = $db->fetch('SELECT bio FROM subscriber_profiles WHERE user_id = ? LIMIT 1', [(int)$user['id']]);
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : null;
?>

<div class="portal-profile">
  <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:24px;">Mon profil</h1>

  <form method="post" action="<?= PORTAL_URL ?>/profil" enctype="multipart/form-data" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>

    <!-- Avatar -->
    <div class="profile-section">
      <h2 class="profile-section-title">Photo de profil</h2>
      <div class="profile-avatar-row">
        <?php if ($profile['avatar']): ?>
          <img src="<?= $e($imgUrl($profile['avatar'])) ?>" alt="Avatar" class="profile-avatar-preview" width="80" height="80">
        <?php else: ?>
          <div class="profile-avatar-placeholder"><?= mb_strtoupper(mb_substr($profile['display_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div>
          <label for="avatar" class="btn btn--outline btn--sm" style="cursor:pointer;">
            Changer la photo
            <input type="file" id="avatar" name="avatar" accept="image/*" hidden>
          </label>
          <p style="font-size:.72rem;color:#9ca3af;margin-top:6px;">JPG, PNG ou WebP. Max 5 Mo.</p>
        </div>
      </div>
    </div>

    <!-- Informations générales -->
    <div class="profile-section">
      <h2 class="profile-section-title">Informations</h2>
      <div class="form-group">
        <label for="display_name">Nom affiché</label>
        <input type="text" id="display_name" name="display_name" required
               minlength="2" maxlength="120"
               value="<?= $e($profile['display_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="bio">Biographie <span style="color:#9ca3af;font-weight:400;">(optionnel, max 500 car.)</span></label>
        <textarea id="bio" name="bio" rows="3" maxlength="500"
                  placeholder="Dites quelques mots sur vous…"><?= $e($sp['bio'] ?? '') ?></textarea>
      </div>
      <!-- Email en lecture seule -->
      <div class="form-group">
        <label>Adresse email</label>
        <input type="email" value="<?= $e($profile['email']) ?>" disabled
               style="background:#f9fafb;color:#6b7280;cursor:not-allowed;">
        <span class="helper">L'email ne peut pas être modifié ici. Contactez le support.</span>
      </div>
    </div>

    <!-- Changement de mot de passe -->
    <div class="profile-section">
      <h2 class="profile-section-title">Changer le mot de passe</h2>
      <p style="font-size:.82rem;color:#6b7280;margin-bottom:16px;">Laissez vide pour ne pas changer.</p>
      <div class="form-group">
        <label for="current_password">Mot de passe actuel</label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password" placeholder="••••••••••••">
      </div>
      <div class="form-group">
        <label for="new_password">Nouveau mot de passe</label>
        <input type="password" id="new_password" name="new_password" autocomplete="new-password" placeholder="Minimum 12 caractères" minlength="12">
      </div>
      <div class="form-group">
        <label for="new_password_confirm">Confirmer le nouveau mot de passe</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" placeholder="Répétez le nouveau mot de passe">
      </div>
    </div>

    <!-- Sécurité 2FA -->
    <div class="profile-section">
      <h2 class="profile-section-title">Double authentification (2FA)</h2>
      <?php if ($profile['two_factor_enabled']): ?>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <span style="color:#16a34a;font-weight:600;font-size:.88rem;">✅ 2FA activée</span>
          <a href="<?= PORTAL_URL ?>/2fa/desactiver" class="btn btn--outline btn--sm"
             onclick="return confirm('Désactiver la double authentification ?')">Désactiver</a>
        </div>
      <?php else: ?>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <span style="color:#f59e0b;font-weight:600;font-size:.88rem;">⚠️ 2FA désactivée</span>
          <a href="<?= PORTAL_URL ?>/2fa/configurer" class="btn btn--outline btn--sm">Activer la 2FA</a>
        </div>
        <p style="font-size:.78rem;color:#6b7280;margin-top:8px;">Renforcez la sécurité de votre compte avec une application d'authentification.</p>
      <?php endif; ?>
    </div>

    <div style="padding-top:16px;border-top:1px solid #e5e7eb;margin-top:16px;">
      <button type="submit" class="btn btn--primary">Enregistrer les modifications</button>
      <a href="<?= PORTAL_URL ?>/" class="btn btn--ghost" style="margin-left:8px;">Annuler</a>
    </div>
  </form>
</div>

<style>
.portal-profile { max-width: 640px; }
.profile-section { margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid #e5e7eb; }
.profile-section:last-of-type { border-bottom: none; }
.profile-section-title { font-family: 'Inter', sans-serif; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #374151; margin-bottom: 16px; }
.profile-avatar-row { display: flex; align-items: center; gap: 20px; }
.profile-avatar-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
.profile-avatar-placeholder { width: 80px; height: 80px; border-radius: 50%; background: #0A3D6B; color: #fff; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 900; flex-shrink: 0; }
.helper { font-size: .72rem; color: #9ca3af; display: block; margin-top: 4px; }
</style>

<script nonce="<?= $e($cspNonce) ?>">
// Vérification concordance nouveaux mots de passe
document.getElementById('new_password_confirm')?.addEventListener('input', function () {
    const np = document.getElementById('new_password');
    this.setCustomValidity(this.value && this.value !== np.value ? 'Les mots de passe ne correspondent pas.' : '');
});
// Prévisualisation avatar
document.getElementById('avatar')?.addEventListener('change', function () {
    if (this.files[0]) {
        const url = URL.createObjectURL(this.files[0]);
        const preview = document.querySelector('.profile-avatar-preview') || document.querySelector('.profile-avatar-placeholder');
        if (preview) {
            const img = document.createElement('img');
            img.src = url; img.width = 80; img.height = 80;
            img.className = 'profile-avatar-preview';
            preview.replaceWith(img);
        }
    }
});
</script>
