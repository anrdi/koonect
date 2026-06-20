<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$campaigns = $campaigns ?? [];
$subscribers = (int)($subscribers ?? 0);
$db = \Koonect\Core\Database::getInstance();
$lists = $db->fetchAll('SELECT id, name, slug FROM newsletter_lists ORDER BY name');
$listNames = [];
foreach ($lists as $list) {
    $listNames[(int)$list['id']] = $list['name'];
}
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Newsletter</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Créer des campagnes et suivre les envois.</p>
  </div>
  <div class="rdash-stats-mini">
    <div class="rdash-mini-card">
      <span class="rdash-mini-value"><?= number_format($subscribers, 0, ',', ' ') ?></span>
      <span class="rdash-mini-label">Abonnés confirmés</span>
    </div>
    <div class="rdash-mini-card">
      <span class="rdash-mini-value"><?= number_format(count($campaigns), 0, ',', ' ') ?></span>
      <span class="rdash-mini-label">Campagnes</span>
    </div>
  </div>
</div>

<div class="rdash-two-col">
  <section class="editor-meta-block">
    <h2 class="editor-meta-title">Nouvelle campagne</h2>
    <form method="post" action="<?= REDAC_URL ?>/newsletter/campagne">
      <?= \Koonect\Helpers\Csrf::field() ?>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="nl_subject">Objet</label>
        <input type="text" id="nl_subject" name="subject" required class="rdash-input" placeholder="La sélection du matin">
      </div>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="nl_list">Liste</label>
        <select id="nl_list" name="list_id" class="editor-select">
          <option value="">Aucune liste</option>
          <?php foreach ($lists as $list): ?>
            <option value="<?= (int)$list['id'] ?>"><?= $e($list['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="nl_content">Contenu HTML</label>
        <textarea id="nl_content" name="content_html" rows="10" required class="rdash-textarea" placeholder="<p>Bonjour…</p>"></textarea>
      </div>
      <button type="submit" class="btn btn--primary">Enregistrer en brouillon</button>
    </form>
  </section>

  <section>
    <h2 class="editor-meta-title" style="margin-bottom:12px;">Campagnes récentes</h2>
    <?php if (empty($campaigns)): ?>
      <div class="rdash-empty">Aucune campagne pour le moment.</div>
    <?php else: ?>
      <div class="rdash-campaign-list">
        <?php foreach ($campaigns as $campaign): ?>
          <?php
            $stats = !empty($campaign['stats_json']) ? json_decode((string)$campaign['stats_json'], true) : [];
            $status = $campaign['status'] ?? 'draft';
            $statusLabels = ['draft' => 'Brouillon', 'sending' => 'Envoi', 'sent' => 'Envoyée', 'failed' => 'En échec'];
            $statusColors = ['draft' => '#f59e0b', 'sending' => '#3b82f6', 'sent' => '#16a34a', 'failed' => '#dc2626'];
          ?>
          <article class="rdash-campaign-card">
            <div class="rdash-campaign-head">
              <div>
                <div class="rdash-campaign-subject"><?= $e($campaign['subject']) ?></div>
                <div class="rdash-campaign-meta">
                  <?= $e($listNames[(int)($campaign['list_id'] ?? 0)] ?? 'Aucune liste') ?>
                  · créée le <?= date('d/m/Y H:i', strtotime($campaign['created_at'])) ?>
                  <?php if (!empty($campaign['sent_at'])): ?>
                    · envoyée le <?= date('d/m/Y H:i', strtotime($campaign['sent_at'])) ?>
                  <?php endif; ?>
                </div>
              </div>
              <span class="rdash-campaign-status" style="background:<?= $statusColors[$status] ?? '#6b7280' ?>18;color:<?= $statusColors[$status] ?? '#6b7280' ?>;">
                <?= $e($statusLabels[$status] ?? $status) ?>
              </span>
            </div>
            <div class="rdash-campaign-stats">
              <span>Expédiés: <?= (int)($stats['sent'] ?? 0) ?></span>
              <span>Erreurs: <?= (int)($stats['errors'] ?? 0) ?></span>
            </div>
            <div class="rdash-campaign-actions">
              <form method="post" action="<?= REDAC_URL ?>/newsletter/envoyer/<?= (int)$campaign['id'] ?>">
                <?= \Koonect\Helpers\Csrf::field() ?>
                <?php if (($campaign['status'] ?? 'draft') === 'draft'): ?>
                  <button type="submit" class="btn btn--primary btn--sm" onclick="return confirm('Envoyer cette campagne maintenant ?')">Envoyer</button>
                <?php else: ?>
                  <button type="button" class="btn btn--outline btn--sm" disabled>Envoi indisponible</button>
                <?php endif; ?>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-stats-mini { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
.rdash-mini-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; min-width:160px; display:flex; flex-direction:column; align-items:flex-start; }
.rdash-mini-value { font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:900; color:#C8102E; line-height:1; }
.rdash-mini-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; font-weight:600; margin-top:6px; }
.rdash-two-col { display:grid; grid-template-columns: 420px minmax(0, 1fr); gap:16px; align-items:start; }
.rdash-input, .rdash-textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:4px; font-family:'Inter',sans-serif; font-size:.88rem; outline:none; background:#fff; }
.rdash-input:focus, .rdash-textarea:focus { border-color:#0A3D6B; box-shadow:0 0 0 3px rgba(10,61,107,.08); }
.rdash-textarea { resize:vertical; line-height:1.6; }
.rdash-campaign-list { display:flex; flex-direction:column; gap:10px; }
.rdash-campaign-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:14px 16px; }
.rdash-campaign-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
.rdash-campaign-subject { font-weight:700; color:#1A1A1A; margin-bottom:4px; }
.rdash-campaign-meta { font-size:.75rem; color:#9ca3af; font-family:'Inter',sans-serif; }
.rdash-campaign-status { display:inline-block; padding:4px 10px; border-radius:2px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
.rdash-campaign-stats { display:flex; gap:16px; flex-wrap:wrap; font-size:.78rem; color:#6b7280; margin-top:10px; }
.rdash-campaign-actions { margin-top:12px; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
@media (max-width: 1100px) {
  .rdash-two-col { grid-template-columns:1fr; }
}
@media (max-width: 640px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-campaign-head { flex-direction:column; }
}
</style>
