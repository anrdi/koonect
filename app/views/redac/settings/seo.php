<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$settings = $settings ?? [];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">SEO global</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Modifier les réglages SEO globaux disponibles en base.</p>
  </div>
  <a href="<?= REDAC_URL ?>/parametres" class="btn btn--outline btn--sm">Retour</a>
</div>

<?php if (empty($settings)): ?>
  <div class="rdash-empty">Aucun réglage SEO global n'est défini. Les métadonnées sont principalement gérées au niveau des articles.</div>
<?php else: ?>
  <form method="post" action="<?= REDAC_URL ?>/parametres/seo">
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="rdash-settings-list">
      <?php foreach ($settings as $setting): ?>
        <div class="editor-meta-block" style="margin-bottom:12px;">
          <label class="editor-label" for="seo_<?= $e($setting['key']) ?>">
            <?= $e($setting['key']) ?>
            <span style="font-weight:400;color:#9ca3af;font-size:.7rem;">(<?= $e($setting['type']) ?>)</span>
          </label>
          <?php if ($setting['type'] === 'text'): ?>
            <textarea id="seo_<?= $e($setting['key']) ?>" name="<?= $e($setting['key']) ?>" rows="3" class="rdash-textarea"><?= $e($setting['value'] ?? '') ?></textarea>
          <?php elseif ($setting['type'] === 'boolean'): ?>
            <select id="seo_<?= $e($setting['key']) ?>" name="<?= $e($setting['key']) ?>" class="editor-select">
              <option value="1" <?= ($setting['value'] ?? '') == '1' ? 'selected' : '' ?>>Activé</option>
              <option value="0" <?= ($setting['value'] ?? '') == '0' ? 'selected' : '' ?>>Désactivé</option>
            </select>
          <?php else: ?>
            <input type="text" id="seo_<?= $e($setting['key']) ?>" name="<?= $e($setting['key']) ?>" value="<?= $e($setting['value'] ?? '') ?>" class="rdash-input">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
      <button type="submit" class="btn btn--primary">Enregistrer les réglages</button>
      <a href="<?= REDAC_URL ?>/parametres" class="btn btn--ghost">Annuler</a>
    </div>
  </form>
<?php endif; ?>

<style nonce="<?= $e($cspNonce) ?>">
.rdash-page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
.rdash-page-title { font-size:1.3rem; font-weight:700; color:#1A1A1A; }
.rdash-input, .rdash-textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:4px; font-family:'Inter',sans-serif; font-size:.88rem; outline:none; background:#fff; }
.rdash-input:focus, .rdash-textarea:focus { border-color:#0A3D6B; box-shadow:0 0 0 3px rgba(10,61,107,.08); }
.rdash-textarea { resize:vertical; line-height:1.6; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
</style>
