<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$categories = $categories ?? [];
?>

<div class="rdash-page-header">
  <div>
    <h1 class="rdash-page-title">Catégories</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-top:4px;">Créer et ajuster les rubriques du journal.</p>
  </div>
  <div class="rdash-kpi"><?= number_format(count($categories), 0, ',', ' ') ?></div>
</div>

<div class="rdash-two-col">
  <section class="editor-meta-block">
    <h2 class="editor-meta-title">Nouvelle catégorie</h2>
    <form method="post" action="<?= REDAC_URL ?>/categories">
      <?= \Koonect\Helpers\Csrf::field() ?>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="cat_name">Nom</label>
        <input type="text" id="cat_name" name="name" required class="rdash-input" placeholder="Politique">
      </div>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="cat_description">Description</label>
        <textarea id="cat_description" name="description" rows="3" class="rdash-textarea" placeholder="Description de la rubrique"></textarea>
      </div>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="cat_parent">Catégorie parente</label>
        <select id="cat_parent" name="parent_id" class="editor-select">
          <option value="">Aucune</option>
          <?php foreach ($categories as $parent): ?>
            <option value="<?= (int)$parent['id'] ?>"><?= $e($parent['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="rdash-inline-grid">
        <div class="editor-field">
          <label class="editor-label" for="cat_position">Position</label>
          <input type="number" id="cat_position" name="position" value="0" class="rdash-input">
        </div>
        <div class="editor-field">
          <label class="editor-label" for="cat_og_image">OG image</label>
          <input type="text" id="cat_og_image" name="og_image" class="rdash-input" placeholder="/assets/img/...">
        </div>
      </div>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="cat_meta_title">Titre SEO</label>
        <input type="text" id="cat_meta_title" name="meta_title" class="rdash-input" maxlength="70">
      </div>
      <div class="editor-field" style="margin-bottom:12px;">
        <label class="editor-label" for="cat_meta_description">Méta-description</label>
        <textarea id="cat_meta_description" name="meta_description" rows="3" maxlength="160" class="rdash-textarea"></textarea>
      </div>
      <button type="submit" class="btn btn--primary">Créer la catégorie</button>
    </form>
  </section>

  <section>
    <h2 class="editor-meta-title" style="margin-bottom:12px;">Catégories existantes</h2>
    <?php if (empty($categories)): ?>
      <div class="rdash-empty">Aucune catégorie enregistrée.</div>
    <?php else: ?>
      <div class="rdash-category-list">
        <?php foreach ($categories as $cat): ?>
          <details class="rdash-category-card" <?= ((int)($cat['position'] ?? 0) === 1 ? 'open' : '') ?>>
            <summary>
              <span class="rdash-category-name"><?= $e($cat['name']) ?></span>
              <span class="rdash-category-slug">/<?= $e($cat['slug']) ?></span>
              <span class="rdash-category-pos">#<?= (int)($cat['position'] ?? 0) ?></span>
            </summary>
            <form method="post" action="<?= REDAC_URL ?>/categories/<?= (int)$cat['id'] ?>" class="rdash-category-form">
              <?= \Koonect\Helpers\Csrf::field() ?>
              <div class="rdash-inline-grid">
                <div class="editor-field">
                  <label class="editor-label" for="cat_name_<?= (int)$cat['id'] ?>">Nom</label>
                  <input type="text" id="cat_name_<?= (int)$cat['id'] ?>" name="name" value="<?= $e($cat['name']) ?>" class="rdash-input" required>
                </div>
                <div class="editor-field">
                  <label class="editor-label" for="cat_position_<?= (int)$cat['id'] ?>">Position</label>
                  <input type="number" id="cat_position_<?= (int)$cat['id'] ?>" name="position" value="<?= (int)($cat['position'] ?? 0) ?>" class="rdash-input">
                </div>
              </div>
              <div class="editor-field" style="margin-top:12px;">
                <label class="editor-label" for="cat_description_<?= (int)$cat['id'] ?>">Description</label>
                <textarea id="cat_description_<?= (int)$cat['id'] ?>" name="description" rows="3" class="rdash-textarea"><?= $e($cat['description'] ?? '') ?></textarea>
              </div>
              <div class="editor-field" style="margin-top:12px;">
                <label class="editor-label" for="cat_parent_<?= (int)$cat['id'] ?>">Catégorie parente</label>
                <select id="cat_parent_<?= (int)$cat['id'] ?>" name="parent_id" class="editor-select">
                  <option value="">Aucune</option>
                  <?php foreach ($categories as $parent): ?>
                    <?php if ((int)$parent['id'] === (int)$cat['id']) continue; ?>
                    <option value="<?= (int)$parent['id'] ?>" <?= (int)($cat['parent_id'] ?? 0) === (int)$parent['id'] ? 'selected' : '' ?>>
                      <?= $e($parent['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="rdash-inline-grid" style="margin-top:12px;">
                <div class="editor-field">
                  <label class="editor-label" for="cat_meta_title_<?= (int)$cat['id'] ?>">Titre SEO</label>
                  <input type="text" id="cat_meta_title_<?= (int)$cat['id'] ?>" name="meta_title" value="<?= $e($cat['meta_title'] ?? '') ?>" class="rdash-input" maxlength="70">
                </div>
                <div class="editor-field">
                  <label class="editor-label" for="cat_og_image_<?= (int)$cat['id'] ?>">OG image</label>
                  <input type="text" id="cat_og_image_<?= (int)$cat['id'] ?>" name="og_image" value="<?= $e($cat['og_image'] ?? '') ?>" class="rdash-input">
                </div>
              </div>
              <div class="editor-field" style="margin-top:12px;">
                <label class="editor-label" for="cat_meta_description_<?= (int)$cat['id'] ?>">Méta-description</label>
                <textarea id="cat_meta_description_<?= (int)$cat['id'] ?>" name="meta_description" rows="3" maxlength="160" class="rdash-textarea"><?= $e($cat['meta_description'] ?? '') ?></textarea>
              </div>
              <div class="rdash-form-actions">
                <button type="submit" class="btn btn--primary btn--sm">Enregistrer</button>
                <button type="submit" class="btn btn--outline btn--sm" formaction="<?= REDAC_URL ?>/categories/<?= (int)$cat['id'] ?>/supprimer" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</button>
              </div>
            </form>
          </details>
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
.rdash-inline-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
.rdash-input, .rdash-textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:4px; font-family:'Inter',sans-serif; font-size:.88rem; outline:none; background:#fff; }
.rdash-input:focus, .rdash-textarea:focus { border-color:#0A3D6B; box-shadow:0 0 0 3px rgba(10,61,107,.08); }
.rdash-textarea { resize:vertical; line-height:1.6; }
.rdash-category-list { display:flex; flex-direction:column; gap:10px; }
.rdash-category-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.rdash-category-card summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:10px; justify-content:space-between; padding:14px 16px; }
.rdash-category-card summary::-webkit-details-marker { display:none; }
.rdash-category-name { font-weight:700; color:#1A1A1A; }
.rdash-category-slug { font-family:'Inter',sans-serif; font-size:.75rem; color:#9ca3af; }
.rdash-category-pos { font-family:'Inter',sans-serif; font-size:.75rem; color:#6b7280; margin-left:auto; }
.rdash-category-form { border-top:1px solid #f3f4f6; padding:16px; }
.rdash-form-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
.rdash-empty { padding:40px; text-align:center; color:#9ca3af; font-size:.92rem; background:#fff; border:1px solid #e5e7eb; border-radius:8px; }
@media (max-width: 960px) {
  .rdash-two-col { grid-template-columns:1fr; }
}
@media (max-width: 640px) {
  .rdash-page-header { flex-direction:column; }
  .rdash-inline-grid { grid-template-columns:1fr; }
  .rdash-category-card summary { flex-wrap:wrap; }
}
</style>
