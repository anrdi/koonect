<?php
$e    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$user = \Koonect\Core\Session::get('user');
$db   = \Koonect\Core\Database::getInstance();
$fullUser = $db->fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [(int)$user['id']]);
?>

<div class="portal-gdpr">
  <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:8px;">Mes données personnelles</h1>
  <p style="color:#6b7280;margin-bottom:32px;">Conformément au RGPD, vous pouvez consulter, exporter ou supprimer vos données.</p>

  <!-- Résumé des données -->
  <div class="gdpr-block">
    <h2 class="gdpr-title">📋 Données collectées</h2>
    <table class="gdpr-table">
      <tbody>
        <tr><td>Email</td><td><?= $e($fullUser['email']) ?></td></tr>
        <tr><td>Nom d'utilisateur</td><td><?= $e($fullUser['username']) ?></td></tr>
        <tr><td>Nom affiché</td><td><?= $e($fullUser['display_name']) ?></td></tr>
        <tr><td>Compte créé le</td><td><?= date('d/m/Y', strtotime($fullUser['created_at'])) ?></td></tr>
        <tr><td>Dernière connexion</td><td><?= $fullUser['last_login_at'] ? date('d/m/Y à H\hi', strtotime($fullUser['last_login_at'])) : 'N/A' ?></td></tr>
        <tr><td>Email vérifié</td><td><?= $fullUser['email_verified_at'] ? '✅ Oui' : '⚠️ Non' ?></td></tr>
        <tr><td>Double authentification</td><td><?= $fullUser['two_factor_enabled'] ? '✅ Activée' : '❌ Désactivée' ?></td></tr>
      </tbody>
    </table>
  </div>

  <!-- Export données -->
  <div class="gdpr-block">
    <h2 class="gdpr-title">📦 Exporter mes données</h2>
    <p>Téléchargez l'ensemble de vos données au format JSON (profil, historique, favoris, commentaires).</p>
    <a href="<?= PORTAL_URL ?>/donnees/exporter" class="btn btn--outline btn--sm">⬇ Exporter mes données (JSON)</a>
  </div>

  <!-- Consentements -->
  <div class="gdpr-block">
    <h2 class="gdpr-title">🍪 Mes consentements</h2>
    <form method="post" action="<?= PORTAL_URL ?>/cookies">
      <?= \Koonect\Helpers\Csrf::field() ?>
      <label class="form-checkbox" style="margin-bottom:12px;">
        <input type="checkbox" name="analytics" value="1"
               <?= (\Koonect\Core\Database::getInstance()->fetch(
                   'SELECT id FROM gdpr_consents WHERE user_id=? AND type="analytics" AND granted=1 ORDER BY created_at DESC LIMIT 1',
                   [(int)$user['id']]
               ) ? 'checked' : '') ?>>
        <span>Cookies analytiques (mesure d'audience anonymisée)</span>
      </label>
      <button type="submit" class="btn btn--outline btn--sm">Mettre à jour mes préférences</button>
    </form>
  </div>

  <!-- Suppression du compte -->
  <div class="gdpr-block gdpr-block--danger">
    <h2 class="gdpr-title gdpr-title--danger">🗑 Supprimer mon compte</h2>
    <p>La suppression est <strong>définitive</strong>. Vos données seront anonymisées sous 30 jours conformément au RGPD. Vos commentaires seront conservés sous forme anonyme.</p>

    <details class="gdpr-delete-details">
      <summary>Je comprends et souhaite supprimer mon compte</summary>
      <form method="post" action="<?= PORTAL_URL ?>/donnees/supprimer"
            onsubmit="return confirm('Êtes-vous absolument certain ? Cette action est irréversible.')"
            style="margin-top:16px;">
        <?= \Koonect\Helpers\Csrf::field() ?>
        <div class="form-group">
          <label for="confirm_email">Confirmez votre adresse email pour procéder</label>
          <input type="email" id="confirm_email" name="confirm_email" required
                 placeholder="<?= $e($user['email']) ?>">
        </div>
        <div class="form-group">
          <label for="confirm_password">Votre mot de passe actuel</label>
          <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••••••">
        </div>
        <button type="submit" class="btn btn--danger">Supprimer définitivement mon compte</button>
      </form>
    </details>
  </div>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.portal-gdpr { max-width: 720px; }
.gdpr-block { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 20px; }
.gdpr-block--danger { border-color: #fca5a5; background: #fff5f5; }
.gdpr-title { font-family: 'Inter', sans-serif; font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 12px; color: #374151; }
.gdpr-title--danger { color: #dc2626; }
.gdpr-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.gdpr-table td { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; }
.gdpr-table td:first-child { color: #6b7280; width: 200px; font-weight: 500; }
.gdpr-delete-details summary { cursor: pointer; color: #dc2626; font-size: .85rem; font-weight: 600; padding: 8px 0; }
.btn--danger { background: #dc2626; color: #fff; border: none; padding: 10px 24px; border-radius: 2px; font-family: 'Inter', sans-serif; font-size: .85rem; font-weight: 600; cursor: pointer; }
.btn--danger:hover { background: #b91c1c; }
</style>
