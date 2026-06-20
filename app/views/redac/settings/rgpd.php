<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$stats = $stats ?? [];
$recentConsents = $stats['recent_consents'] ?? [];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">RGPD</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Suivi des suppressions, abonnements et consentements.</p>
  </div>
  <a href="<?= REDAC_URL ?>/parametres" class="btn btn--outline btn--sm">Retour</a>
</div>

<div class="rdash-stats-mini" style="margin-bottom:16px;">
  <div class="rdash-mini-card">
    <span class="rdash-mini-value"><?= number_format((int)($stats['pending_deletions'] ?? 0), 0, ',', ' ') ?></span>
    <span class="rdash-mini-label">Suppressions en attente</span>
  </div>
  <div class="rdash-mini-card">
    <span class="rdash-mini-value"><?= number_format((int)($stats['nl_unconfirmed'] ?? 0), 0, ',', ' ') ?></span>
    <span class="rdash-mini-label">Inscrits newsletter non confirmés</span>
  </div>
</div>

<div class="editor-meta-block" style="margin-bottom:16px;">
  <h2 class="editor-meta-title">Consentements récents</h2>
  <?php if (empty($recentConsents)): ?>
    <div class="rdash-empty" style="margin-top:0;">Aucun consentement récent enregistré.</div>
  <?php else: ?>
    <table class="rdash-table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Nombre</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentConsents as $consent): ?>
          <tr>
            <td class="rdash-table-meta"><?= $e($consent['type']) ?></td>
            <td class="rdash-table-meta"><?= (int)$consent['cnt'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="rdash-empty">
  La gestion détaillée des données utilisateurs reste dans l'espace abonné. Cette page sert de tableau de suivi interne.
</div>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-stats-mini { display:flex; gap:10px; flex-wrap:wrap; }
.rdash-mini-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; min-width:180px; display:flex; flex-direction:column; align-items:flex-start; }
.rdash-mini-value { font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:900; color:#C8102E; line-height:1; }
.rdash-mini-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; font-weight:600; margin-top:6px; }
.rdash-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.rdash-table thead tr { background:#f9fafb; border-bottom:2px solid #e5e7eb; }
.rdash-table th { padding:10px 14px; text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; font-weight:700; }
.rdash-table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; }
.rdash-table tbody tr:last-child td { border-bottom:none; }
.rdash-table-meta { color:#6b7280; font-size:.8rem; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
@media (max-width: 720px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-mini-card { min-width:100%; }
}
</style>
