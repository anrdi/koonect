<?php
/** @var array|null $article @var array $categories @var array $tags @var array $articleTags */
$e    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$imgUrl = fn($p) => $p ? (preg_match('#^https?://|^data:#i', $p) ? $p : APP_URL . '/' . ltrim($p, '/')) : '';
$isEdit = $article !== null;
$action = $isEdit
    ? REDAC_URL . '/articles/' . $article['id'] . '/modifier'
    : REDAC_URL . '/articles/nouveau';
$pageTitle = $isEdit ? 'Modifier : ' . mb_strimwidth($article['title'], 0, 60, '…') : 'Nouvel article';
$user = \Koonect\Core\Session::get('user');
$role = $user['role'] ?? '';
$canPublish  = in_array($role, ['admin', 'director']);
$canValidate = in_array($role, ['admin', 'director', 'chief_editor']);
?>

<div class="editor-page">
  <div class="editor-header">
    <h1 class="editor-page-title"><?= $e($pageTitle) ?></h1>
    <?php if ($isEdit): ?>
      <div class="editor-status-badge editor-status-badge--<?= $e($article['status']) ?>">
        <?= $e($article['status']) ?>
      </div>
      <?php if ($article['status'] === 'published'): ?>
        <a href="<?= APP_URL ?>/article/<?= $e($article['slug']) ?>" target="_blank" class="btn btn--sm btn--ghost">Voir l'article →</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($isEdit): ?>
  <!-- Autosave indicator -->
  <div class="autosave-bar" id="autosave-bar" aria-live="polite">
    <span id="autosave-status">Non sauvegardé</span>
  </div>
  <?php endif; ?>

  <form method="post" action="<?= $e($action) ?>" id="article-form" class="editor-form" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>

    <div class="editor-layout">

      <!-- COLONNE PRINCIPALE -->
      <div class="editor-main-col">

        <!-- Titre -->
        <div class="editor-field">
          <label for="title" class="editor-label editor-label--required">Titre</label>
          <input type="text" id="title" name="title" class="editor-title-input"
                 value="<?= $e($article['title'] ?? '') ?>"
                 placeholder="Titre de l'article…" required maxlength="255"
                 aria-describedby="title-counter">
          <div class="field-counter"><span id="title-counter">0</span>/255</div>
        </div>

        <!-- Sous-titre -->
        <div class="editor-field">
          <label for="subtitle" class="editor-label">Sous-titre</label>
          <input type="text" id="subtitle" name="subtitle"
                 value="<?= $e($article['subtitle'] ?? '') ?>"
                 placeholder="Sous-titre ou accroche…" maxlength="500">
        </div>

        <!-- Chapô -->
        <div class="editor-field">
          <label for="chapo" class="editor-label">Chapô <small>(intro mise en avant)</small></label>
          <textarea id="chapo" name="chapo" rows="3" maxlength="1000"
                    placeholder="Le chapô est l'introduction mise en avant sous le titre…"><?= $e($article['chapo'] ?? '') ?></textarea>
          <div class="field-counter"><span id="chapo-counter">0</span>/1000</div>
        </div>

        <!-- ÉDITEUR RICHE -->
        <div class="editor-field">
          <label class="editor-label editor-label--required">Contenu</label>
          <div class="rich-editor-wrap">
            <!-- Toolbar -->
            <div class="rich-editor-toolbar" role="toolbar" aria-label="Outils de mise en forme">
              <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-cmd="bold" title="Gras (Ctrl+B)"><strong>G</strong></button>
                <button type="button" class="toolbar-btn" data-cmd="italic" title="Italique (Ctrl+I)"><em>I</em></button>
                <button type="button" class="toolbar-btn" data-cmd="underline" title="Souligné"><u>S</u></button>
                <button type="button" class="toolbar-btn" data-cmd="strikethrough" title="Barré"><s>B</s></button>
              </div>
              <div class="toolbar-group">
                <select class="toolbar-select" id="heading-select" title="Format de titre">
                  <option value="">Paragraphe</option>
                  <option value="h2">Titre H2</option>
                  <option value="h3">Titre H3</option>
                  <option value="h4">Titre H4</option>
                  <option value="h5">Titre H5</option>
                  <option value="h6">Titre H6</option>
                </select>
              </div>
              <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-cmd="insertUnorderedList" title="Liste à puces">☰</button>
                <button type="button" class="toolbar-btn" data-cmd="insertOrderedList" title="Liste numérotée">1.</button>
              </div>
              <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-action="blockquote" title="Citation">❝</button>
                <button type="button" class="toolbar-btn" data-action="highlight" title="Encadré mis en avant">⬛</button>
                <button type="button" class="toolbar-btn" data-action="table" title="Tableau">⊞</button>
              </div>
              <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-action="link" title="Lien">🔗</button>
                <button type="button" class="toolbar-btn" data-action="anchor" title="Ancre">#</button>
                <button type="button" class="toolbar-btn" data-action="media" title="Insérer une image/vidéo">🖼</button>
                <button type="button" class="toolbar-btn" data-action="iframe" title="Vidéo / iframe">▶</button>
              </div>
              <div class="toolbar-group">
                <button type="button" class="toolbar-btn" data-cmd="undo" title="Annuler (Ctrl+Z)">↩</button>
                <button type="button" class="toolbar-btn" data-cmd="redo" title="Rétablir (Ctrl+Y)">↪</button>
              </div>
            </div>

            <!-- Zone d'édition -->
            <div id="rich-editor"
                 class="rich-editor-content"
                 contenteditable="true"
                 role="textbox"
                 aria-multiline="true"
                 aria-label="Contenu de l'article"
                 spellcheck="true"
                 data-placeholder="Commencez à rédiger votre article…"><?= $article['content'] ?? '' ?></div>

            <!-- Textarea cachée synchronisée -->
            <textarea id="content" name="content" hidden><?= $e($article['content'] ?? '') ?></textarea>
          </div>
          <div class="editor-word-count"><span id="word-count">0</span> mots · <span id="read-time">0</span> min de lecture estimée</div>
        </div>

      </div>

      <!-- COLONNE DROITE (méta) -->
      <div class="editor-meta-col">

        <!-- Actions de publication -->
        <div class="editor-meta-block editor-publish-block">
          <h2 class="editor-meta-title">Publication</h2>
          <div class="publish-actions" style="display: flex; flex-direction: column; gap: 8px;">
            <button type="submit" name="action" value="save" class="btn btn--secondary btn--full">
              💾 Enregistrer le brouillon
            </button>
            <?php if ($canPublish): ?>
              <button type="submit" name="action" value="publish" class="btn btn--primary btn--full">
                🚀 Publier directement
              </button>
            <?php endif; ?>
          </div>

          <!-- Note de révision -->
          <?php if ($isEdit): ?>
          <div class="revision-note-wrap">
            <label for="revision_note" class="editor-label">Note pour l'équipe</label>
            <textarea id="revision_note" name="revision_note" rows="2" placeholder="Note optionnelle pour l'équipe…"></textarea>
          </div>
          <?php endif; ?>

          <!-- Publication programmée -->
          <div class="editor-field editor-field--inline">
            <label for="scheduled_at" class="editor-label">Publication programmée</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                   value="<?= $e($article['scheduled_at'] ?? '') ?>">
          </div>
        </div>

        <!-- Options article -->
        <div class="editor-meta-block">
          <h2 class="editor-meta-title">Options</h2>
          <label class="toggle-label">
            <input type="checkbox" name="is_featured" value="1" <?= ($article['is_featured'] ?? 0) ? 'checked' : '' ?>>
            <span>⭐ Article mis en avant (Une)</span>
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="is_breaking" value="1" <?= ($article['is_breaking'] ?? 0) ? 'checked' : '' ?>>
            <span>🔴 Breaking news</span>
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="is_premium" value="1" <?= ($article['is_premium'] ?? 0) ? 'checked' : '' ?>>
            <span>💎 Article Premium (abonnés)</span>
          </label>
        </div>

        <!-- Catégorie -->
        <div class="editor-meta-block">
          <h2 class="editor-meta-title">Catégorie</h2>
          <select name="category_id" class="editor-select" id="category-select">
            <option value="">— Aucune catégorie —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ($article['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                <?= $e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tags -->
        <div class="editor-meta-block">
          <h2 class="editor-meta-title">Tags</h2>
          <div class="tags-input-wrap" id="tags-input-wrap">
            <input type="text" id="tag-input" placeholder="Ajouter un tag…" autocomplete="off"
                   list="tags-datalist" aria-label="Ajouter un tag">
            <datalist id="tags-datalist">
              <?php foreach ($tags as $tag): ?>
                <option value="<?= $e($tag['name']) ?>" data-id="<?= (int)$tag['id'] ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="selected-tags" id="selected-tags" aria-live="polite">
            <?php foreach ($articleTags ?? [] as $tagId): ?>
              <?php $tag = array_values(array_filter($tags, fn($t) => $t['id'] == $tagId))[0] ?? null; ?>
              <?php if ($tag): ?>
                <span class="tag-chip tag-chip--removable" data-id="<?= (int)$tag['id'] ?>">
                  <?= $e($tag['name']) ?>
                  <button type="button" class="tag-remove" aria-label="Supprimer le tag <?= $e($tag['name']) ?>">×</button>
                </span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="tag_ids" id="tag-ids-input" value="<?= $e(implode(',', $articleTags ?? [])) ?>">
        </div>

        <!-- Image à la une -->
        <div class="editor-meta-block">
          <h2 class="editor-meta-title">Image à la une</h2>
          <div class="featured-image-wrap" id="featured-image-wrap">
            <?php if (!empty($article['featured_image_id'])): ?>
              <div class="featured-image-preview" id="featured-image-preview">
                <img src="<?= $e($imgUrl($article['featured_image_thumb'] ?? '')) ?>" alt="Image à la une" width="280" height="187" loading="lazy">
                <button type="button" class="featured-image-remove" id="remove-featured-image" aria-label="Supprimer l'image">×</button>
              </div>
            <?php else: ?>
              <div class="featured-image-empty" id="featured-image-preview" hidden></div>
            <?php endif; ?>
            <button type="button" class="btn btn--outline btn--sm btn--full" id="choose-featured-image" data-open-media="true">
              📷 Choisir une image
            </button>
            <input type="hidden" name="featured_image_id" id="featured-image-id" value="<?= $e($article['featured_image_id'] ?? '') ?>">
          </div>
        </div>

        <!-- SEO -->
        <div class="editor-meta-block">
          <h2 class="editor-meta-title">SEO</h2>
          <div class="editor-field">
            <label for="seo_title" class="editor-label">Titre SEO</label>
            <input type="text" id="seo_title" name="seo_title" maxlength="70"
                   value="<?= $e($article['seo_title'] ?? '') ?>" placeholder="Titre pour Google…">
            <div class="field-counter"><span id="seo-title-counter">0</span>/70</div>
          </div>
          <div class="editor-field">
            <label for="seo_description" class="editor-label">Méta-description</label>
            <textarea id="seo_description" name="seo_description" rows="3" maxlength="160"
                      placeholder="Description pour les moteurs de recherche…"><?= $e($article['seo_description'] ?? '') ?></textarea>
            <div class="field-counter"><span id="seo-desc-counter">0</span>/160</div>
          </div>
          <!-- Aperçu Google -->
          <div class="seo-preview" aria-label="Aperçu Google">
            <div class="seo-preview-url"><?= $e(APP_URL . '/article/' . ($article['slug'] ?? 'slug-de-larticle')) ?></div>
            <div class="seo-preview-title" id="seo-preview-title"><?= $e($article['seo_title'] ?? $article['title'] ?? '') ?></div>
            <div class="seo-preview-desc" id="seo-preview-desc"><?= $e($article['seo_description'] ?? '') ?></div>
          </div>
        </div>

      </div><!-- .editor-meta-col -->
    </div><!-- .editor-layout -->
  </form>

</div><!-- .editor-page -->

<!-- MODALE MÉDIATHÈQUE -->
<div id="media-modal" class="media-modal" role="dialog" aria-modal="true" aria-label="Médiathèque" hidden>
  <div class="media-modal-inner">
    <div class="media-modal-header">
      <h2>Médiathèque</h2>
      <button type="button" class="media-modal-close" aria-label="Fermer">×</button>
    </div>
    <div class="media-modal-toolbar" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <input type="search" id="media-search" placeholder="Rechercher un média…" aria-label="Rechercher" style="flex: 1; min-width: 150px;">
      <label class="btn btn--primary btn--sm media-upload-label" for="media-upload-input" style="cursor:pointer; margin-bottom:0;">
        + Fichier
        <input type="file" id="media-upload-input" accept="image/*" multiple hidden>
      </label>
      <span style="color:#9ca3af; font-size:.85rem;">ou</span>
      <input type="url" id="media-modal-import-url-input" placeholder="Lien de l'image (https://...)" 
             style="padding:8px 12px; border:1px solid #d1d5db; border-radius:4px; font-size:.85rem; font-family:'Inter',sans-serif; outline:none; flex: 2; min-width: 200px;">
      <button type="button" id="media-modal-import-url-btn" class="btn btn--secondary btn--sm">Importer Lien</button>
    </div>
    <div id="media-grid" class="media-grid" aria-live="polite">
      <!-- Chargé dynamiquement via JS -->
    </div>
    <div class="media-modal-footer">
      <div id="media-selected-info"></div>
      <button type="button" class="btn btn--primary" id="media-insert-btn" disabled>Insérer</button>
    </div>
  </div>
</div>
<div id="media-modal-backdrop" class="media-modal-backdrop" hidden></div>

<!-- MODALE LIEN -->
<div id="link-modal" class="mini-modal" role="dialog" aria-modal="true" aria-label="Insérer un lien" hidden>
  <div class="mini-modal-inner">
    <h3>Insérer un lien</h3>
    <label for="link-url">URL</label>
    <input type="url" id="link-url" placeholder="https://…">
    <label for="link-text">Texte du lien</label>
    <input type="text" id="link-text" placeholder="Texte affiché">
    <label class="toggle-label">
      <input type="checkbox" id="link-new-tab"> Ouvrir dans un nouvel onglet
    </label>
    <div class="mini-modal-actions">
      <button type="button" class="btn btn--secondary" id="link-cancel">Annuler</button>
      <button type="button" class="btn btn--primary" id="link-insert">Insérer</button>
    </div>
  </div>
</div>

<!-- MODALE IFRAME -->
<div id="iframe-modal" class="mini-modal" role="dialog" aria-modal="true" aria-label="Insérer une vidéo" hidden>
  <div class="mini-modal-inner">
    <h3>Insérer une vidéo / iframe</h3>
    <label for="iframe-url">URL YouTube, Vimeo, ou code iframe</label>
    <textarea id="iframe-url" rows="3" placeholder="https://www.youtube.com/watch?v=…"></textarea>
    <div class="mini-modal-actions">
      <button type="button" class="btn btn--secondary" id="iframe-cancel">Annuler</button>
      <button type="button" class="btn btn--primary" id="iframe-insert">Insérer</button>
    </div>
  </div>
</div>

<script nonce="<?= htmlspecialchars($cspNonce) ?>">
  <?php if ($isEdit): ?>
    window.ARTICLE_ID    = <?= (int)$article['id'] ?>;
    window.AUTOSAVE_URL  = '/articles/<?= (int)$article['id'] ?>/autosave';
  <?php endif; ?>
  window.MEDIA_API_URL = '/medias';
  window.CSRF_TOKEN    = '<?= \Koonect\Helpers\Csrf::getToken() ?>';
</script>

