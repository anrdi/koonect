<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : '';
?>

<div class="rdash-page-header">
  <h1 class="rdash-page-title">Médiathèque</h1>
  <label class="btn btn--primary btn--sm" for="media-upload-input" style="cursor:pointer;">
    + Importer des images
    <input type="file" id="media-upload-input" accept="image/*" multiple hidden>
  </label>
</div>

<!-- Drop zone -->
<div id="media-drop-zone" class="media-drop-zone" style="margin-bottom:24px;" aria-label="Zone de dépôt pour importer des images">
  <div style="font-size:2rem;margin-bottom:8px;">📁</div>
  <p style="font-family:'Inter',sans-serif;font-size:.88rem;color:#6b7280;">
    Glissez-déposez vos images ici, ou utilisez le bouton <strong>Importer</strong>
  </p>
  <p style="font-family:'Inter',sans-serif;font-size:.75rem;color:#9ca3af;margin-top:4px;">
    JPG, PNG, WebP — max <?= (int)(UPLOAD_MAX_SIZE / 1048576) ?> Mo par image
  </p>
</div>

<!-- Import URL field -->
<div class="media-url-import-container" style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px; margin-bottom:24px; display:flex; gap:12px; align-items:center; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
  <div style="font-size:1.2rem; display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; background:#eff6ff; color:#1d4ed8;">🔗</div>
  <div style="flex:1;">
    <label for="media-import-url-input" style="display:block; font-family:'Inter',sans-serif; font-size:.78rem; font-weight:600; color:#374151; margin-bottom:4px;">Importer depuis une URL</label>
    <div style="display:flex; gap:8px;">
      <input type="url" id="media-import-url-input" placeholder="https://exemple.com/image.jpg"
             style="flex:1; padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:.83rem; font-family:'Inter',sans-serif; outline:none; transition: border-color 150ms;">
      <button type="button" id="media-import-url-btn" class="btn btn--primary btn--sm" style="font-weight:600; padding:8px 16px;">Importer</button>
    </div>
  </div>
</div>

<!-- Filtres -->
<div style="display:flex;gap:10px;margin-bottom:20px;align-items:center;flex-wrap:wrap;">
  <input type="search" id="media-filter-search" placeholder="Rechercher par nom…"
         style="padding:7px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:.83rem;font-family:'Inter',sans-serif;outline:none;width:240px;">
  <select id="media-filter-folder"
          style="padding:7px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:.83rem;font-family:'Inter',sans-serif;outline:none;">
    <option value="">Tous les dossiers</option>
    <?php foreach ($folders as $folder): ?>
      <option value="<?= (int)$folder['id'] ?>" <?= ($folderId ?? null) == $folder['id'] ? 'selected' : '' ?>>
        <?= $e($folder['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button type="button" id="create-folder-btn" class="btn btn--outline btn--sm">+ Dossier</button>
</div>

<!-- Grille médias -->
<div id="media-standalone-grid" class="media-standalone-grid">
  <?php if (empty($medias)): ?>
    <div class="rdash-empty" style="grid-column:1/-1;">Aucun média. Importez vos premières images.</div>
  <?php else: ?>
  <?php foreach ($medias as $media): ?>
  <div class="media-library-card" data-id="<?= (int)$media['id'] ?>">
    <div class="media-library-thumb">
      <img src="<?= $e($imgUrl($media['thumb_path'] ?: $media['path'])) ?>"
           alt="<?= $e($media['alt_text'] ?? $media['original_name']) ?>"
           loading="lazy" width="140" height="93">
    </div>
    <div class="media-library-info">
      <span class="media-library-name" title="<?= $e($media['original_name']) ?>">
        <?= $e(mb_strimwidth($media['original_name'], 0, 24, '…')) ?>
      </span>
      <span class="media-library-size"><?= round($media['size'] / 1024) ?> Ko · <?= $media['width'] ?>×<?= $media['height'] ?></span>
      <div class="media-library-actions">
        <button class="media-edit-btn rdash-action-btn" data-id="<?= (int)$media['id'] ?>" title="Modifier">✏️</button>
        <button class="media-copy-btn rdash-action-btn" data-url="<?= $e($imgUrl($media['webp_path'])) ?>" title="Copier l'URL">🔗</button>
        <button class="media-delete-btn rdash-action-btn" data-id="<?= (int)$media['id'] ?>" title="Supprimer">🗑</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Pagination simplifiée -->
<?php if ($page > 1 || count($medias) >= 40): ?>
<div style="display:flex;gap:8px;justify-content:center;margin-top:24px;">
  <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>" class="btn btn--outline btn--sm">← Précédent</a>
  <?php endif; ?>
  <span style="padding:8px 12px;font-family:'Inter',sans-serif;font-size:.82rem;color:#6b7280;">Page <?= $page ?></span>
  <?php if (count($medias) >= 40): ?>
    <a href="?page=<?= $page + 1 ?>" class="btn btn--outline btn--sm">Suivant →</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<style nonce="<?= $e($cspNonce) ?>">
.media-standalone-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
}
.media-library-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
  transition: 150ms;
}
.media-library-card:hover { border-color: #9ca3af; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.media-library-thumb { overflow: hidden; background: #f3f4f6; }
.media-library-thumb img { width: 100%; height: 100px; object-fit: cover; display: block; transition: transform 300ms; }
.media-library-card:hover .media-library-thumb img { transform: scale(1.04); }
.media-library-info { padding: 8px; }
.media-library-name { display: block; font-family: 'Inter', sans-serif; font-size: .72rem; font-weight: 500; color: #374151; margin-bottom: 2px; }
.media-library-size { display: block; font-family: 'Inter', sans-serif; font-size: .65rem; color: #9ca3af; margin-bottom: 6px; }
.media-library-actions { display: flex; gap: 2px; }
</style>

<script nonce="<?= $e($cspNonce) ?>">
window.MEDIA_API_URL = '/medias';
window.CSRF_TOKEN    = '<?= \Koonect\Helpers\Csrf::getToken() ?>';

// Filtrage en direct
let filterTimer = null;
document.getElementById('media-filter-search')?.addEventListener('input', (e) => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        const folder = document.getElementById('media-filter-folder')?.value || '';
        window.location.href = '?q=' + encodeURIComponent(e.target.value) + (folder ? '&folder=' + folder : '');
    }, 600);
});
document.getElementById('media-filter-folder')?.addEventListener('change', (e) => {
    const q = document.getElementById('media-filter-search')?.value || '';
    window.location.href = '?folder=' + e.target.value + (q ? '&q=' + encodeURIComponent(q) : '');
});

// Créer un dossier
document.getElementById('create-folder-btn')?.addEventListener('click', async () => {
    const name = prompt('Nom du dossier :');
    if (!name?.trim()) return;
    const csrf = window.CSRF_TOKEN;
    const res  = await fetch(`${window.MEDIA_API_URL}/dossiers`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body:    `name=${encodeURIComponent(name)}&csrf_token=${encodeURIComponent(csrf)}`,
    });
    const data = await res.json();
    if (data.success) {
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = data.name;
        document.getElementById('media-filter-folder')?.appendChild(opt);
    }
});
</script>
