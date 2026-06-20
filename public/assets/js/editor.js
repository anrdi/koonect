/* ================================================================
   KOONECT — Éditeur riche (vanilla JS)
   Éditeur type presse professionnelle
   ================================================================ */

'use strict';

(function () {

  // ── Références DOM ─────────────────────────────────────────────
  const editorEl   = document.getElementById('rich-editor');
  const contentEl  = document.getElementById('content');
  const titleEl    = document.getElementById('title');
  const chapoEl    = document.getElementById('chapo');
  const seoTitleEl = document.getElementById('seo_title');
  const seoDescEl  = document.getElementById('seo_description');
  const form       = document.getElementById('article-form');

  if (!editorEl || !contentEl) return;

  // ── Synchronisation éditeur → textarea caché ───────────────────
  function syncContent() {
    contentEl.value = editorEl.innerHTML;
    updateWordCount();
  }

  editorEl.addEventListener('input', syncContent);
  editorEl.addEventListener('paste', (e) => {
    e.preventDefault();
    // Coller sans mise en forme sauf balises de base
    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, text);
  });

  // ── Compteurs de caractères ────────────────────────────────────
  function setupCounter(input, counterId, max) {
    const counter = document.getElementById(counterId);
    if (!input || !counter) return;
    const update = () => {
      const len = input.value.length;
      counter.textContent = len;
      counter.style.color = len > max * 0.9 ? '#C8102E' : '';
    };
    input.addEventListener('input', update);
    update();
  }

  setupCounter(titleEl,   'title-counter',    255);
  setupCounter(chapoEl,   'chapo-counter',    1000);
  setupCounter(seoTitleEl,'seo-title-counter', 70);
  setupCounter(seoDescEl, 'seo-desc-counter',  160);

  // ── Compteur de mots & temps de lecture ───────────────────────
  function updateWordCount() {
    const text  = editorEl.innerText || '';
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    const time  = Math.max(1, Math.ceil(words / 200));
    const wc    = document.getElementById('word-count');
    const rt    = document.getElementById('read-time');
    if (wc) wc.textContent = words.toLocaleString('fr');
    if (rt) rt.textContent = time;
    // Remplir reading_time implicitement
    const rtInput = document.querySelector('[name=reading_time]');
    if (rtInput) rtInput.value = time;
  }
  updateWordCount();

  // ── Aperçu SEO dynamique ──────────────────────────────────────
  function updateSeoPreview() {
    const title = seoTitleEl?.value || titleEl?.value || '';
    const desc  = seoDescEl?.value || chapoEl?.value || '';
    const pt = document.getElementById('seo-preview-title');
    const pd = document.getElementById('seo-preview-desc');
    if (pt) pt.textContent = title.substring(0, 70) || 'Titre de l\'article';
    if (pd) pd.textContent = desc.substring(0, 160) || 'Description méta…';
  }

  [titleEl, chapoEl, seoTitleEl, seoDescEl].forEach(el => el?.addEventListener('input', updateSeoPreview));
  updateSeoPreview();

  // ── Synchroniser titre → SEO title si vide ────────────────────
  titleEl?.addEventListener('input', () => {
    if (seoTitleEl && !seoTitleEl.dataset.dirty) {
      seoTitleEl.value = titleEl.value.substring(0, 70);
      document.getElementById('seo-title-counter').textContent = seoTitleEl.value.length;
      updateSeoPreview();
    }
  });
  seoTitleEl?.addEventListener('input', () => { seoTitleEl.dataset.dirty = '1'; });

  // ── TOOLBAR ───────────────────────────────────────────────────
  const toolbar = document.querySelector('.rich-editor-toolbar');
  if (!toolbar) return;

  // Boutons execCommand
  toolbar.querySelectorAll('[data-cmd]').forEach(btn => {
    btn.addEventListener('mousedown', (e) => {
      e.preventDefault(); // Ne pas perdre le focus
      document.execCommand(btn.dataset.cmd, false, null);
      editorEl.focus();
      syncContent();
      updateToolbarState();
    });
  });

  // Sélecteur de titres
  const headingSelect = document.getElementById('heading-select');
  headingSelect?.addEventListener('change', () => {
    editorEl.focus();
    const val = headingSelect.value;
    if (val) {
      document.execCommand('formatBlock', false, '<' + val + '>');
    } else {
      document.execCommand('formatBlock', false, '<p>');
    }
    headingSelect.value = '';
    syncContent();
  });

  // Boutons d'action spéciale
  toolbar.querySelectorAll('[data-action]').forEach(btn => {
    btn.addEventListener('mousedown', (e) => {
      e.preventDefault();
      editorEl.focus();
      handleAction(btn.dataset.action);
    });
  });

  function handleAction(action) {
    switch (action) {
      case 'blockquote':
        insertBlock('<blockquote><p>Citation…</p></blockquote>');
        break;
      case 'highlight':
        insertBlock('<div class="highlight-block"><p>Texte mis en avant…</p></div>');
        break;
      case 'table':
        insertBlock(`<table>
  <thead><tr><th>Colonne 1</th><th>Colonne 2</th><th>Colonne 3</th></tr></thead>
  <tbody>
    <tr><td>Donnée</td><td>Donnée</td><td>Donnée</td></tr>
    <tr><td>Donnée</td><td>Donnée</td><td>Donnée</td></tr>
  </tbody>
</table>`);
        break;
      case 'link':
        openLinkModal();
        break;
      case 'anchor':
        insertAnchor();
        break;
      case 'media':
        openMediaModal({ onSelect: insertImageFromMedia });
        break;
      case 'iframe':
        openIframeModal();
        break;
    }
    syncContent();
  }

  function insertBlock(html) {
    const sel = window.getSelection();
    if (!sel.rangeCount) { editorEl.innerHTML += html; return; }
    const range = sel.getRangeAt(0);
    range.collapse(true);
    const div = document.createElement('div');
    div.innerHTML = html;
    const node = div.firstChild;
    range.insertNode(node);
    // Placer le curseur après
    const after = document.createRange();
    after.setStartAfter(node);
    after.collapse(true);
    sel.removeAllRanges();
    sel.addRange(after);
    editorEl.focus();
  }

  function insertImageFromMedia(media) {
    const html = `<figure>
  <picture>
    <source srcset="${media.webp_path}" type="image/webp">
    <img src="${media.path}" alt="${escapeHtml(media.alt || '')}" loading="lazy">
  </picture>
  <figcaption>${escapeHtml(media.caption || '')}${media.credit ? ' © ' + escapeHtml(media.credit) : ''}</figcaption>
</figure>`;
    insertBlock(html);
    syncContent();
  }

  function insertAnchor() {
    const id = prompt('Identifiant de l\'ancre (ex: section-2) :');
    if (!id || !/^[a-z0-9-_]+$/.test(id)) return;
    const html = `<span id="${escapeHtml(id)}" class="anchor" aria-hidden="true"></span>`;
    document.execCommand('insertHTML', false, html);
    syncContent();
  }

  // ── Mise à jour état toolbar (bold/italic actif) ───────────────
  function updateToolbarState() {
    toolbar.querySelectorAll('[data-cmd]').forEach(btn => {
      try {
        btn.classList.toggle('is-active', document.queryCommandState(btn.dataset.cmd));
      } catch { /* ignore */ }
    });
  }

  editorEl.addEventListener('keyup', updateToolbarState);
  editorEl.addEventListener('mouseup', updateToolbarState);

  // ── Raccourcis clavier ────────────────────────────────────────
  editorEl.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey) {
      switch (e.key.toLowerCase()) {
        case 'b': e.preventDefault(); document.execCommand('bold');      syncContent(); break;
        case 'i': e.preventDefault(); document.execCommand('italic');    syncContent(); break;
        case 'u': e.preventDefault(); document.execCommand('underline'); syncContent(); break;
      }
    }
    // Tab → indenter dans tableaux ou listes
    if (e.key === 'Tab') {
      const sel = window.getSelection();
      const node = sel.anchorNode;
      if (node && (node.closest?.('li') || node.parentElement?.closest('li'))) {
        e.preventDefault();
        document.execCommand(e.shiftKey ? 'outdent' : 'indent');
      }
    }
  });

  // ── MODALE LIEN ──────────────────────────────────────────────
  let savedRange = null;

  function openLinkModal() {
    // Sauvegarder la sélection
    const sel = window.getSelection();
    savedRange = sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;

    const modal     = document.getElementById('link-modal');
    const urlInput  = document.getElementById('link-url');
    const textInput = document.getElementById('link-text');
    const newTab    = document.getElementById('link-new-tab');
    const insert    = document.getElementById('link-insert');
    const cancel    = document.getElementById('link-cancel');

    if (!modal) return;

    // Pré-remplir avec texte sélectionné
    if (savedRange) textInput.value = savedRange.toString();
    urlInput.value = '';
    modal.hidden = false;
    urlInput.focus();

    insert.onclick = () => {
      const url  = urlInput.value.trim();
      const text = textInput.value.trim() || url;
      if (!url) { urlInput.focus(); return; }

      // Restaurer sélection
      if (savedRange) {
        const sel2 = window.getSelection();
        sel2.removeAllRanges();
        sel2.addRange(savedRange);
      }

      const attrs = newTab.checked ? ' target="_blank" rel="noopener noreferrer"' : '';
      document.execCommand('insertHTML', false, `<a href="${escapeHtmlAttr(url)}"${attrs}>${escapeHtml(text)}</a>`);
      modal.hidden = true;
      syncContent();
    };

    cancel.onclick = () => { modal.hidden = true; };
    modal.onkeydown = (e) => { if (e.key === 'Escape') modal.hidden = true; };
  }

  // ── MODALE IFRAME ─────────────────────────────────────────────
  function openIframeModal() {
    const modal    = document.getElementById('iframe-modal');
    const urlInput = document.getElementById('iframe-url');
    const insert   = document.getElementById('iframe-insert');
    const cancel   = document.getElementById('iframe-cancel');
    if (!modal) return;

    urlInput.value = '';
    modal.hidden = false;
    urlInput.focus();

    insert.onclick = () => {
      const raw = urlInput.value.trim();
      if (!raw) return;
      let html = '';

      // Détecter YouTube
      const ytMatch = raw.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
      const vimeoMatch = raw.match(/vimeo\.com\/(\d+)/);

      if (ytMatch) {
        html = `<figure class="video-embed"><iframe src="https://www.youtube.com/embed/${ytMatch[1]}" frameborder="0" allowfullscreen loading="lazy" title="Vidéo YouTube"></iframe></figure>`;
      } else if (vimeoMatch) {
        html = `<figure class="video-embed"><iframe src="https://player.vimeo.com/video/${vimeoMatch[1]}" frameborder="0" allowfullscreen loading="lazy" title="Vidéo Vimeo"></iframe></figure>`;
      } else if (raw.startsWith('<iframe')) {
        // Code iframe brut — nettoyer
        html = raw.replace(/javascript:/gi, '').replace(/on\w+=/gi, '');
      } else {
        html = `<figure class="video-embed"><iframe src="${escapeHtmlAttr(raw)}" frameborder="0" allowfullscreen loading="lazy"></iframe></figure>`;
      }

      insertBlock(html);
      modal.hidden = true;
      syncContent();
    };

    cancel.onclick = () => { modal.hidden = true; };
    modal.onkeydown = (e) => { if (e.key === 'Escape') modal.hidden = true; };
  }

  // ── AUTOSAVE ──────────────────────────────────────────────────
  if (window.AUTOSAVE_URL && window.ARTICLE_ID) {
    let autosaveTimer  = null;
    let lastSavedContent = editorEl.innerHTML;
    const statusEl     = document.getElementById('autosave-status');
    const autosaveBar  = document.getElementById('autosave-bar');

    async function autosave() {
      const current = editorEl.innerHTML;
      if (current === lastSavedContent) return;

      if (autosaveBar) autosaveBar.className = 'autosave-bar is-saving';
      if (statusEl) statusEl.textContent = 'Sauvegarde…';

      try {
        const res = await fetch(window.AUTOSAVE_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new URLSearchParams({
            content:    current,
            title:      titleEl?.value || '',
            csrf_token: window.CSRF_TOKEN || '',
          }),
        });
        const data = await res.json();

        if (data.success) {
          lastSavedContent = current;
          if (autosaveBar) autosaveBar.className = 'autosave-bar is-saved';
          if (statusEl) statusEl.textContent = 'Sauvegardé à ' + (data.saved_at || new Date().toLocaleTimeString('fr'));
        }
      } catch {
        if (autosaveBar) autosaveBar.className = 'autosave-bar is-error';
        if (statusEl) statusEl.textContent = 'Échec de la sauvegarde automatique';
      }
    }

    // Déclencher autosave 3s après la dernière frappe
    editorEl.addEventListener('input', () => {
      clearTimeout(autosaveTimer);
      if (autosaveBar) autosaveBar.className = 'autosave-bar';
      if (statusEl) statusEl.textContent = 'Modifications non sauvegardées…';
      autosaveTimer = setTimeout(autosave, 3000);
    });

    // Autosave périodique toutes les 60s
    setInterval(autosave, 60000);

    // Avertissement avant de quitter
    window.addEventListener('beforeunload', (e) => {
      if (editorEl.innerHTML !== lastSavedContent) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
    // Pas d'avertissement si on soumet le formulaire
    form?.addEventListener('submit', () => {
      window.onbeforeunload = null;
    });

    const archiveBtn = document.querySelector('.js-archive-btn');
    if (archiveBtn && form) {
      archiveBtn.addEventListener('click', () => {
        if (confirm('Archiver cet article ?')) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'action';
          input.value = 'archive';
          form.appendChild(input);
          window.onbeforeunload = null; // Désactiver l'alerte avant soumission
          form.submit();
        }
      });
    }
  }

  // ── TAGS ──────────────────────────────────────────────────────
  const tagInput     = document.getElementById('tag-input');
  const selectedTags = document.getElementById('selected-tags');
  const tagIdsInput  = document.getElementById('tag-ids-input');

  if (tagInput && selectedTags && tagIdsInput) {
    let selectedTagsMap = {};

    // Initialiser depuis l'état existant
    selectedTags.querySelectorAll('.tag-chip--removable').forEach(chip => {
      const id   = chip.dataset.id;
      const name = chip.querySelector('.tag-remove')?.previousSibling?.textContent?.trim() || chip.textContent.replace('×', '').trim();
      if (id) selectedTagsMap[id] = name;
    });

    function syncTagIds() {
      tagIdsInput.value = Object.keys(selectedTagsMap).join(',');
    }

    function addTag(name, id) {
      if (!name) return;
      // Vérifier si déjà ajouté
      if (Object.values(selectedTagsMap).includes(name)) return;

      const chip = document.createElement('span');
      chip.className = 'tag-chip tag-chip--removable';
      chip.dataset.id = id || 'new_' + Date.now();
      chip.innerHTML = escapeHtml(name) + '<button type="button" class="tag-remove" aria-label="Supprimer ' + escapeHtml(name) + '">×</button>';

      chip.querySelector('.tag-remove').addEventListener('click', () => {
        delete selectedTagsMap[chip.dataset.id];
        chip.remove();
        syncTagIds();
      });

      selectedTagsMap[chip.dataset.id] = name;
      selectedTags.appendChild(chip);
      syncTagIds();
    }

    tagInput.addEventListener('keydown', async (e) => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = tagInput.value.trim().replace(/,$/, '');
        if (!val) return;

        // Chercher dans la datalist
        const option  = document.querySelector(`#tags-datalist option[value="${CSS.escape(val)}"]`);
        const existId = option?.dataset?.id;

        if (existId) {
          addTag(val, existId);
        } else {
          // Créer le tag via API
          try {
            const res  = await fetch(window.MEDIA_API_URL?.replace('/medias', '/tags') || '/redac/tags', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
              body: new URLSearchParams({ name: val, csrf_token: window.CSRF_TOKEN || '' }),
            });
            const data = await res.json();
            if (data.id) addTag(val, data.id);
            else addTag(val, 'new_' + Date.now());
          } catch {
            addTag(val, 'new_' + Date.now());
          }
        }
        tagInput.value = '';
      }
    });
  }

  // ── IMAGE À LA UNE ────────────────────────────────────────────
  const chooseFeaturedBtn = document.getElementById('choose-featured-image');
  const featuredIdInput   = document.getElementById('featured-image-id');
  const featuredPreview   = document.getElementById('featured-image-preview');

  chooseFeaturedBtn?.addEventListener('click', () => {
    openMediaModal({
      onSelect: (media) => {
        if (featuredIdInput) featuredIdInput.value = media.id;
        if (featuredPreview) {
          featuredPreview.hidden = false;
          featuredPreview.innerHTML = `
            <img src="${media.thumb}" alt="Image à la une" width="280" height="187" loading="lazy">
            <button type="button" class="featured-image-remove" id="remove-featured-image" aria-label="Supprimer l'image">×</button>
          `;
          featuredPreview.querySelector('#remove-featured-image')?.addEventListener('click', () => {
            featuredPreview.hidden = true;
            featuredPreview.innerHTML = '';
            if (featuredIdInput) featuredIdInput.value = '';
          });
        }
      }
    });
  });

  document.getElementById('remove-featured-image')?.addEventListener('click', () => {
    if (featuredPreview) { featuredPreview.hidden = true; featuredPreview.innerHTML = ''; }
    if (featuredIdInput) featuredIdInput.value = '';
  });

  // ── Utilitaires ───────────────────────────────────────────────
  function escapeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function escapeHtmlAttr(str) {
    return String(str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

})(); // Fin éditeur


// ================================================================
// MÉDIATHÈQUE MODALE (partagée éditeur + médiathèque standalone)
// ================================================================

let mediaModalCallback = null;
let mediaCurrentPage   = 1;
let mediaSelectedItem  = null;

function openMediaModal({ onSelect } = {}) {
  mediaModalCallback = onSelect || null;
  mediaSelectedItem  = null;
  mediaCurrentPage   = 1;

  const modal    = document.getElementById('media-modal');
  const backdrop = document.getElementById('media-modal-backdrop');
  const insertBtn= document.getElementById('media-insert-btn');
  if (!modal) return;

  modal.hidden    = false;
  backdrop.hidden = false;
  document.body.style.overflow = 'hidden';

  if (insertBtn) insertBtn.disabled = true;

  loadMediaGrid();

  // Close
  modal.querySelector('.media-modal-close')?.addEventListener('click', closeMediaModal, { once: true });
  backdrop.addEventListener('click', closeMediaModal, { once: true });
}

function closeMediaModal() {
  const modal    = document.getElementById('media-modal');
  const backdrop = document.getElementById('media-modal-backdrop');
  if (modal)    modal.hidden = true;
  if (backdrop) backdrop.hidden = true;
  document.body.style.overflow = '';
  mediaSelectedItem = null;
  mediaModalCallback = null;
}

async function loadMediaGrid(search = '', page = 1) {
  const grid = document.getElementById('media-grid');
  if (!grid) return;

  grid.innerHTML = '<div class="media-loading">Chargement…</div>';

  try {
    const url    = (window.MEDIA_API_URL || '/redac/medias') + '?q=' + encodeURIComponent(search) + '&page=' + page;
    const res    = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) {
      throw new Error(`HTTP ${res.status} ${res.statusText}`);
    }
    const data   = await res.json();
    const medias = data.medias || data.data || [];

    if (!medias.length) {
      grid.innerHTML = '<p style="padding:20px;color:#9ca3af;font-family:sans-serif;font-size:.85rem;">Aucun média trouvé.</p>';
      return;
    }

    grid.innerHTML = '';
    const insertBtn = document.getElementById('media-insert-btn');

    medias.forEach(media => {
      const item = document.createElement('div');
      item.className = 'media-grid-item';
      item.dataset.id     = media.id;
      item.dataset.path   = media.path;
      item.dataset.webp   = media.webp_path || media.path;
      item.dataset.thumb  = media.thumb_path || media.path;
      item.dataset.alt    = media.alt_text || '';
      item.dataset.caption= media.caption || '';
      item.dataset.credit = media.credit || '';

      const fixUrl = (url) => url ? (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:') ? url : (window.APP_URL || '') + '/' + url) : '';
      item.innerHTML = `
        <img src="${escapeHtml(fixUrl(media.thumb_path))}" alt="${escapeHtml(media.alt_text || media.original_name)}" loading="lazy">
        <div class="media-name">${escapeHtml(media.original_name)}</div>
      `;

      item.addEventListener('click', () => {
        grid.querySelectorAll('.media-grid-item').forEach(el => el.classList.remove('is-selected'));
        item.classList.add('is-selected');
        mediaSelectedItem = item.dataset;
        if (insertBtn) insertBtn.disabled = false;
      });

      // Double-clic → insérer directement
      item.addEventListener('dblclick', () => {
        mediaSelectedItem = item.dataset;
        insertSelectedMedia();
      });

      grid.appendChild(item);
    });

  } catch (e) {
    console.error('loadMediaGrid error:', e);
    grid.innerHTML = `<p style="padding:20px;color:#dc2626;font-family:sans-serif;font-size:.85rem;">Erreur de chargement : ${escapeHtml(e.message)}<br><small style="color:#9ca3af;font-size:.7rem;display:block;margin-top:4px;">Consultez la console F12 pour plus de détails.</small></p>`;
  }
}

function insertSelectedMedia() {
  if (!mediaSelectedItem || !mediaModalCallback) { closeMediaModal(); return; }
  const fixUrl = (url) => url ? (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:') ? url : (window.APP_URL || '') + '/' + url) : '';
  mediaModalCallback({
    id:      mediaSelectedItem.id,
    path:    fixUrl(mediaSelectedItem.path),
    webp_path: fixUrl(mediaSelectedItem.webp),
    thumb:   fixUrl(mediaSelectedItem.thumb),
    alt:     mediaSelectedItem.alt,
    caption: mediaSelectedItem.caption,
    credit:  mediaSelectedItem.credit,
  });
  closeMediaModal();
}

// Bouton Insérer
document.getElementById('media-insert-btn')?.addEventListener('click', insertSelectedMedia);

// Recherche
let mediaSearchTimer = null;
document.getElementById('media-search')?.addEventListener('input', (e) => {
  clearTimeout(mediaSearchTimer);
  mediaSearchTimer = setTimeout(() => loadMediaGrid(e.target.value), 400);
});

// Upload depuis la médiathèque
document.getElementById('media-upload-input')?.addEventListener('change', async (e) => {
  const files   = Array.from(e.target.files);
  const csrf    = window.CSRF_TOKEN || '';
  const grid    = document.getElementById('media-grid');
  const notice  = document.createElement('div');
  notice.style.cssText = 'padding:10px;font-family:sans-serif;font-size:.8rem;color:#6b7280;';
  notice.textContent   = 'Upload en cours (' + files.length + ' fichier(s))…';
  grid?.prepend(notice);

  const formData = new FormData();
  files.forEach(f => formData.append('files[]', f));
  formData.append('csrf_token', csrf);

  try {
    const res  = await fetch((window.MEDIA_API_URL || '/redac/medias') + '/upload', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    });
    const data = await res.json();
    notice.remove();
    loadMediaGrid();
    if (data.errors?.length) {
      alert('Erreurs : ' + data.errors.join('\n'));
    }
  } catch {
    notice.textContent = 'Erreur lors de l\'upload.';
  }

  e.target.value = '';
});

// Import par URL depuis la médiathèque (modale)
document.getElementById('media-modal-import-url-btn')?.addEventListener('click', async () => {
  const urlInput = document.getElementById('media-modal-import-url-input');
  const url      = urlInput?.value.trim();
  if (!url) {
    alert('Veuillez saisir une URL.');
    return;
  }

  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    alert('L\'URL doit commencer par http:// ou https://.');
    return;
  }

  const importBtn = document.getElementById('media-modal-import-url-btn');
  const csrf      = window.CSRF_TOKEN || '';
  const grid      = document.getElementById('media-grid');
  const notice    = document.createElement('div');
  
  notice.style.cssText = 'padding:10px;font-family:sans-serif;font-size:.8rem;color:#6b7280;';
  notice.textContent   = 'Importation de l\'image distante en cours…';
  grid?.prepend(notice);

  if (importBtn) importBtn.disabled = true;

  try {
    const res = await fetch((window.MEDIA_API_URL || '/redac/medias') + '/import', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new URLSearchParams({
        url: url,
        csrf_token: csrf
      }),
    });
    const data = await res.json();
    notice.remove();
    
    if (data.success?.length) {
      if (urlInput) urlInput.value = '';
      loadMediaGrid();
    } else if (data.errors?.length) {
      alert('Erreur : ' + data.errors.join('\n'));
    } else {
      alert('Erreur lors de l\'importation.');
    }
  } catch {
    notice.textContent = 'Erreur réseau lors de l\'importation.';
  } finally {
    if (importBtn) importBtn.disabled = false;
  }
});

// Permettre d'importer avec Entrée dans le champ URL
document.getElementById('media-modal-import-url-input')?.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('media-modal-import-url-btn')?.click();
  }
});

// Constante APP_URL accessible dans editor.js
if (typeof window.APP_URL === 'undefined') {
  window.APP_URL = document.querySelector('base')?.href?.replace(/\/$/, '') || '';
}

function escapeHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
