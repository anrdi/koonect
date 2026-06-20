/* ================================================================
   KOONECT — JavaScript Médiathèque (standalone)
   ================================================================ */

'use strict';

// ── Drag & Drop upload ────────────────────────────────────────────
(function () {
  const grid = document.getElementById('media-standalone-grid');
  if (!grid) return;

  const dropZone = document.getElementById('media-drop-zone');
  if (!dropZone) return;

  ['dragenter', 'dragover'].forEach(evt => {
    dropZone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropZone.classList.add('is-drag-over');
    });
  });

  ['dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, () => dropZone.classList.remove('is-drag-over'));
  });

  dropZone.addEventListener('drop', async (e) => {
    e.preventDefault();
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    if (!files.length) return;
    await uploadFiles(files);
  });

  async function uploadFiles(files) {
    const csrf     = window.CSRF_TOKEN || '';
    const formData = new FormData();
    files.forEach(f => formData.append('files[]', f));
    formData.append('csrf_token', csrf);

    const notice = document.createElement('div');
    notice.className = 'media-upload-notice';
    notice.textContent = `Upload de ${files.length} fichier(s)…`;
    dropZone.appendChild(notice);

    try {
      const res  = await fetch((window.MEDIA_API_URL || '/redac/medias') + '/upload', {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body:    formData,
      });
      const data = await res.json();

      notice.remove();

      if (data.success?.length) {
        data.success.forEach(m => prependMediaCard(m));
        showToast(`${data.success.length} image(s) importée(s) avec succès.`, 'success');
      }
      if (data.errors?.length) {
        showToast('Erreurs : ' + data.errors.join(' | '), 'error');
      }
    } catch {
      notice.remove();
      showToast('Erreur réseau lors de l\'upload.', 'error');
    }
  }

  function prependMediaCard(media) {
    const card = document.createElement('div');
    card.className = 'media-library-card';
    card.dataset.id = media.id;
    card.innerHTML = `
      <div class="media-library-thumb">
        <img src="${media.thumb}" alt="${escHtml(media.name)}" loading="lazy">
      </div>
      <div class="media-library-info">
        <span class="media-library-name">${escHtml(media.name)}</span>
        <div class="media-library-actions">
          <button class="media-edit-btn" data-id="${media.id}" title="Modifier les métadonnées">✏️</button>
          <button class="media-delete-btn" data-id="${media.id}" title="Supprimer">🗑</button>
          <button class="media-copy-btn" data-url="${media.webp_path}" title="Copier l'URL">🔗</button>
        </div>
      </div>
    `;
    bindCardEvents(card);
    grid.prepend(card);
  }

  // ── Initialiser les cards existantes ─────────────────────────
  document.querySelectorAll('.media-library-card').forEach(card => bindCardEvents(card));

  function bindCardEvents(card) {
    card.querySelector('.media-delete-btn')?.addEventListener('click', async () => {
      const id = card.dataset.id;
      if (!confirm('Supprimer ce média ? Cette action est irréversible.')) return;

      const csrf = window.CSRF_TOKEN || '';
      try {
        const res  = await fetch(`${window.MEDIA_API_URL || '/redac/medias'}/${id}/supprimer`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body:    `csrf_token=${encodeURIComponent(csrf)}`,
        });
        const data = await res.json();
        if (data.success) {
          card.style.transition = 'opacity 300ms, transform 300ms';
          card.style.opacity = '0';
          card.style.transform = 'scale(.9)';
          setTimeout(() => card.remove(), 300);
          showToast('Média supprimé.', 'success');
        } else {
          showToast(data.error || 'Erreur lors de la suppression.', 'error');
        }
      } catch {
        showToast('Erreur réseau.', 'error');
      }
    });

    card.querySelector('.media-copy-btn')?.addEventListener('click', async (e) => {
      const url = e.currentTarget.dataset.url;
      try {
        await navigator.clipboard.writeText(url);
        showToast('URL copiée dans le presse-papiers.', 'success');
      } catch {
        prompt('URL du média :', url);
      }
    });

    card.querySelector('.media-edit-btn')?.addEventListener('click', () => {
      openEditModal(card);
    });
  }

  // ── Modale édition métadonnées ────────────────────────────────
  let editModal = null;

  function openEditModal(card) {
    if (!editModal) {
      editModal = document.createElement('div');
      editModal.className = 'mini-modal';
      editModal.setAttribute('role', 'dialog');
      editModal.setAttribute('aria-modal', 'true');
      editModal.setAttribute('aria-label', 'Modifier le média');
      editModal.innerHTML = `
        <div class="mini-modal-inner">
          <h3>Modifier le média</h3>
          <label for="media-alt">Texte alternatif (alt)</label>
          <input type="text" id="media-alt" placeholder="Description de l'image pour l'accessibilité">
          <label for="media-caption">Légende</label>
          <input type="text" id="media-caption" placeholder="Légende affichée sous l'image">
          <label for="media-credit">Crédit photo</label>
          <input type="text" id="media-credit" placeholder="© Photographe / Source">
          <div class="mini-modal-actions">
            <button type="button" class="btn btn--secondary" id="media-edit-cancel">Annuler</button>
            <button type="button" class="btn btn--primary" id="media-edit-save">Enregistrer</button>
          </div>
        </div>
      `;
      document.body.appendChild(editModal);
    }

    const id = card.dataset.id;
    editModal.hidden = false;
    editModal.style.display = 'block';
    document.getElementById('media-alt').focus();

    document.getElementById('media-edit-cancel').onclick = () => { editModal.hidden = true; editModal.style.display = ''; };

    document.getElementById('media-edit-save').onclick = async () => {
      const csrf = window.CSRF_TOKEN || '';
      const body = new URLSearchParams({
        alt_text:   document.getElementById('media-alt').value,
        caption:    document.getElementById('media-caption').value,
        credit:     document.getElementById('media-credit').value,
        csrf_token: csrf,
      });
      try {
        await fetch(`${window.MEDIA_API_URL || '/redac/medias'}/${id}/modifier`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body,
        });
        editModal.hidden = true;
        editModal.style.display = '';
        showToast('Média mis à jour.', 'success');
      } catch {
        showToast('Erreur lors de la mise à jour.', 'error');
      }
    };
  }

  // ── Upload via input file ────────────────────────────────────
  document.getElementById('media-upload-input')?.addEventListener('change', async (e) => {
    const files = Array.from(e.target.files);
    if (files.length) await uploadFiles(files);
    e.target.value = '';
  });

  // ── Import via URL ───────────────────────────────────────────
  const importUrlBtn = document.getElementById('media-import-url-btn');
  const importUrlInput = document.getElementById('media-import-url-input');

  async function handleUrlImport() {
    const url = importUrlInput?.value.trim();
    if (!url) {
      showToast('Veuillez saisir une URL.', 'error');
      return;
    }

    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      showToast('L\'URL doit commencer par http:// ou https://.', 'error');
      return;
    }

    const csrf = window.CSRF_TOKEN || '';
    const urlParams = new URLSearchParams(window.location.search);
    const folderId = urlParams.get('folder') || '';

    if (importUrlBtn) importUrlBtn.disabled = true;
    showToast('Importation de l\'image distante en cours…', 'info');

    try {
      const res = await fetch((window.MEDIA_API_URL || '/redac/medias') + '/import', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
          url: url,
          csrf_token: csrf,
          folder_id: folderId
        })
      });
      const data = await res.json();

      if (data.success?.length) {
        if (importUrlInput) importUrlInput.value = '';
        data.success.forEach(m => prependMediaCard(m));
        showToast('Image importée avec succès.', 'success');
        
        // Remove empty state message if present
        const emptyMsg = document.querySelector('.rdash-empty');
        if (emptyMsg) emptyMsg.remove();
      } else if (data.errors?.length) {
        showToast('Erreur : ' + data.errors.join(' | '), 'error');
      } else {
        showToast('Erreur lors de l\'importation.', 'error');
      }
    } catch {
      showToast('Erreur réseau lors de l\'importation.', 'error');
    } finally {
      if (importUrlBtn) importUrlBtn.disabled = false;
    }
  }

  importUrlBtn?.addEventListener('click', handleUrlImport);
  importUrlInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleUrlImport();
    }
  });

  // ── Toast notifications ──────────────────────────────────────
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `media-toast media-toast--${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => {
      toast.classList.remove('is-visible');
      setTimeout(() => toast.remove(), 400);
    }, 3500);
  }

  function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Styles toast (dynamique) ─────────────────────────────────
  const style = document.createElement('style');
  style.textContent = `
    .media-toast {
      position:fixed;bottom:24px;right:24px;z-index:3000;
      padding:12px 20px;border-radius:6px;
      font-family:'Inter',sans-serif;font-size:.85rem;font-weight:500;
      box-shadow:0 4px 16px rgba(0,0,0,.15);
      opacity:0;transform:translateY(8px);transition:opacity 300ms,transform 300ms;
      pointer-events:none;max-width:360px;
    }
    .media-toast.is-visible { opacity:1;transform:translateY(0); }
    .media-toast--success { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0; }
    .media-toast--error   { background:#fff5f5;color:#dc2626;border:1px solid #fca5a5; }
    .media-toast--info    { background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe; }
    .media-drop-zone { border:2px dashed #d1d5db;border-radius:8px;padding:40px;text-align:center;transition:150ms;cursor:pointer;background:#fafaf8; }
    .media-drop-zone.is-drag-over { border-color:#C8102E;background:rgba(200,16,46,.05); }
    .media-upload-notice { background:#f0fdf4;color:#16a34a;padding:8px 12px;border-radius:4px;font-size:.78rem;margin-top:8px; }
  `;
  document.head.appendChild(style);
})();
