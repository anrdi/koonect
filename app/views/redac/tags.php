<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$tags = $tags ?? [];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Tags</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Créer et suivre les mots-clés utilisés dans les articles.</p>
  </div>
  <div class="rdash-kpi"><?= number_format(count($tags), 0, ',', ' ') ?></div>
</div>

<div class="rdash-two-col">
  <section class="editor-meta-block">
    <h2 class="editor-meta-title">Nouveau tag</h2>
    <form method="post" action="<?= REDAC_URL ?>/tags">
      <?= \Koonect\Helpers\Csrf::field() ?>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="tag_name">Nom</label>
        <input type="text" id="tag_name" name="name" required class="rdash-input" placeholder="Intelligence artificielle">
      </div>
      <button type="submit" class="btn btn--primary">Créer le tag</button>
    </form>
  </section>

  <section>
    <h2 class="editor-meta-title" style="margin-bottom:12px;">Tags existants</h2>
    <?php if (empty($tags)): ?>
      <div class="rdash-empty">Aucun tag enregistré.</div>
    <?php else: ?>
      <div class="rdash-tag-cloud">
        <?php foreach ($tags as $tag): ?>
          <div class="rdash-tag-card">
            <div>
              <div class="rdash-tag-name"><?= $e($tag['name']) ?></div>
              <div class="rdash-tag-slug">/<?= $e($tag['slug']) ?></div>
            </div>
            <div class="rdash-tag-date"><?= !empty($tag['created_at']) ? date('d/m/Y', strtotime($tag['created_at'])) : '' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-kpi { min-width:64px; padding:10px 14px; border-radius:8px; background:#fff; border:1px solid #e5e7eb; font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:900; color:#C8102E; text-align:center; }
.rdash-two-col { display:grid; grid-template-columns: 360px minmax(0, 1fr); gap:16px; align-items:start; }
.rdash-input { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:4px; font-family:'Inter',sans-serif; font-size:.88rem; outline:none; background:#fff; }
.rdash-input:focus { border-color:#0A3D6B; box-shadow:0 0 0 3px rgba(10,61,107,.08); }
.rdash-tag-cloud { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:10px; }
.rdash-tag-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:14px 16px; display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.rdash-tag-name { font-weight:700; color:#1A1A1A; }
.rdash-tag-slug, .rdash-tag-date { font-size:.75rem; color:#9ca3af; font-family:'Inter',sans-serif; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
@media (max-width: 960px) {
  .rdash-two-col { grid-template-columns:1fr; }
}
@media (max-width: 640px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-tag-card { flex-direction:column; }
}
</style>
