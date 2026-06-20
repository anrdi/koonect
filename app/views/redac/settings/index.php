<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$typeLabels = ['string'=>'Texte','boolean'=>'Booléen','integer'=>'Nombre','json'=>'JSON','text'=>'Texte long'];
$groupLabels = ['general'=>'Général','display'=>'Affichage','comments'=>'Commentaires',
                'newsletter'=>'Newsletter','analytics'=>'Analytiques','design'=>'Design',
                'social'=>'Réseaux sociaux','auth'=>'Authentification','smtp'=>'SMTP'];
?>

<div class="rdash-page-header">
  <h1 class="rdash-page-title">Paramètres du site</h1>
  <div style="display:flex;gap:8px;">
    <a href="<?= REDAC_URL ?>/parametres/smtp" class="btn btn--outline btn--sm">🔌 Tester SMTP</a>
    <a href="<?= REDAC_URL ?>/parametres/rgpd" class="btn btn--outline btn--sm">🇪🇺 RGPD</a>
    <a href="<?= REDAC_URL ?>/parametres/seo" class="btn btn--outline btn--sm">🔍 SEO</a>
  </div>
</div>

<form method="post" action="<?= REDAC_URL ?>/parametres">
  <?= \Koonect\Helpers\Csrf::field() ?>

  <?php foreach ($grouped as $group => $settings): ?>
  <div class="editor-meta-block" style="margin-bottom:20px;">
    <h2 class="editor-meta-title"><?= $e($groupLabels[$group] ?? ucfirst($group)) ?></h2>
    <?php foreach ($settings as $setting): ?>
    <div class="editor-field" style="margin-bottom:12px;">
      <label for="setting_<?= $e($setting['key']) ?>" class="editor-label">
        <?= $e($setting['key']) ?>
        <span style="font-weight:400;color:#9ca3af;font-size:.7rem;">(<?= $e($typeLabels[$setting['type']] ?? $setting['type']) ?>)</span>
      </label>

      <?php if ($setting['type'] === 'boolean'): ?>
        <select name="<?= $e($setting['key']) ?>" id="setting_<?= $e($setting['key']) ?>" class="editor-select">
          <option value="1" <?= $setting['value'] == '1' ? 'selected' : '' ?>>Activé</option>
          <option value="0" <?= $setting['value'] == '0' ? 'selected' : '' ?>>Désactivé</option>
        </select>
      <?php elseif ($setting['type'] === 'text'): ?>
        <textarea name="<?= $e($setting['key']) ?>" id="setting_<?= $e($setting['key']) ?>"
                  rows="3" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-family:'Inter',sans-serif;font-size:.85rem;outline:none;resize:vertical;"><?= $e($setting['value'] ?? '') ?></textarea>
      <?php else: ?>
        <input type="text" name="<?= $e($setting['key']) ?>" id="setting_<?= $e($setting['key']) ?>"
               value="<?= $e($setting['value'] ?? '') ?>"
               style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-family:'Inter',sans-serif;font-size:.85rem;outline:none;">
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <div style="position:sticky;bottom:0;background:#F5F5F5;padding:16px 0;border-top:1px solid #e5e7eb;display:flex;gap:8px;z-index:10;">
    <button type="submit" class="btn btn--primary">Enregistrer les paramètres</button>
    <a href="<?= REDAC_URL ?>/" class="btn btn--ghost">Annuler</a>
  </div>
</form>
