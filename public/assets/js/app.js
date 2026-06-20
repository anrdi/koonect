/* ================================================================
   KOONECT.FR — JavaScript principal (vanilla)
   ================================================================ */

'use strict';

// ── Mobile nav ────────────────────────────────────────────────────
(function () {
  const burger  = document.querySelector('.nav-burger');
  const navList = document.querySelector('.nav-list');
  if (!burger || !navList) return;

  burger.addEventListener('click', () => {
    const open = navList.classList.toggle('is-open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    burger.querySelectorAll('span').forEach((s, i) => {
      if (open) {
        if (i === 0) s.style.transform = 'rotate(45deg) translate(5px, 5px)';
        if (i === 1) s.style.opacity = '0';
        if (i === 2) s.style.transform = 'rotate(-45deg) translate(5px, -5px)';
      } else {
        s.style.transform = '';
        s.style.opacity = '';
      }
    });
  });

  // Fermer sur clic extérieur
  document.addEventListener('click', (e) => {
    if (!burger.contains(e.target) && !navList.contains(e.target)) {
      navList.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
    }
  });
})();

// ── Breaking news ticker ──────────────────────────────────────────
(function () {
  const ticker = document.querySelector('.breaking-ticker');
  if (!ticker || ticker.children.length < 2) return;

  let index = 0;
  const items = Array.from(ticker.children);

  // N'afficher qu'un seul élément à la fois avec transition
  items.forEach((item, i) => { item.style.display = i === 0 ? 'block' : 'none'; });

  setInterval(() => {
    items[index].style.display = 'none';
    index = (index + 1) % items.length;
    items[index].style.display = 'block';
  }, 5000);
})();

// ── Cookie banner RGPD ───────────────────────────────────────────
(function () {
  const banner    = document.getElementById('cookie-banner');
  const acceptAll = document.getElementById('cookie-accept-all');
  const essential = document.getElementById('cookie-accept-essential');
  const settingsBtn = document.getElementById('cookie-settings-btn');

  function getCookie(name) {
    return document.cookie.split(';').some(c => c.trim().startsWith(name + '='));
  }

  function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + days * 864e5);
    document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax;Secure';
  }

  function hideBanner() {
    if (banner) { banner.hidden = true; banner.removeAttribute('role'); }
  }

  if (banner && !getCookie('koonect_consent')) {
    banner.hidden = false;
  }

  acceptAll?.addEventListener('click', () => {
    setCookie('koonect_consent', 'all', 365);
    hideBanner();
    sendConsent('all');
  });

  essential?.addEventListener('click', () => {
    setCookie('koonect_consent', 'essential', 365);
    hideBanner();
    sendConsent('essential');
  });

  settingsBtn?.addEventListener('click', () => {
    if (banner) banner.hidden = false;
  });

  function sendConsent(type) {
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    fetch('/api/consent', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ type }),
    }).catch(() => {});
  }
})();

// ── Article : Table des matières automatique ─────────────────────
(function () {
  const articleText = document.querySelector('.article-text');
  const tocContainer = document.getElementById('article-toc');
  if (!articleText || !tocContainer) return;

  const headings = articleText.querySelectorAll('h2, h3');
  if (headings.length < 2) { tocContainer.closest('.toc-block')?.remove(); return; }

  const fragment = document.createDocumentFragment();
  headings.forEach((h, i) => {
    if (!h.id) h.id = 'section-' + i;
    const a = document.createElement('a');
    a.href = '#' + h.id;
    a.textContent = h.textContent;
    if (h.tagName === 'H3') a.style.paddingLeft = '20px';
    a.addEventListener('click', (e) => {
      e.preventDefault();
      h.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.pushState(null, '', '#' + h.id);
    });
    fragment.appendChild(a);
  });
  tocContainer.appendChild(fragment);

  // Highlight actif au scroll
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      const id = entry.target.id;
      const link = tocContainer.querySelector('a[href="#' + id + '"]');
      if (link) link.classList.toggle('is-active', entry.isIntersecting);
    });
  }, { rootMargin: '-80px 0px -80% 0px', threshold: 0 });

  headings.forEach(h => observer.observe(h));
})();

// ── Article : Commentaires ────────────────────────────────────────
(function () {
  const form         = document.getElementById('comment-form');
  const parentInput  = document.getElementById('comment-parent-id');
  const indicator    = document.getElementById('reply-indicator');
  const replyName    = document.getElementById('reply-to-name');
  const cancelReply  = document.getElementById('cancel-reply');
  const textarea     = document.getElementById('comment-content');
  const charCount    = document.getElementById('char-count');

  if (!form) return;

  // Compteur de caractères
  textarea?.addEventListener('input', () => {
    if (charCount) charCount.textContent = textarea.value.length;
  });

  // Boutons répondre
  document.querySelectorAll('.comment-reply-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const parentId = btn.dataset.parent;
      const authorName = btn.closest('.comment')?.querySelector('.comment-author')?.textContent || '';
      if (parentInput) parentInput.value = parentId;
      if (indicator) { indicator.hidden = false; }
      if (replyName) replyName.textContent = authorName;
      textarea?.focus();
    });
  });

  cancelReply?.addEventListener('click', () => {
    if (parentInput) parentInput.value = '';
    if (indicator) indicator.hidden = true;
  });

  // Soumission AJAX
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = form.querySelector('[type=submit]');
    const csrf = form.querySelector('[name="csrf_token"]')?.value || '';

    btn.disabled = true;
    btn.textContent = 'Publication…';

    try {
      const res = await fetch('/api/commentaire', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams(new FormData(form)),
      });
      const data = await res.json();

      if (data.success) {
        showFlash('success', data.message || 'Commentaire soumis à modération.');
        textarea.value = '';
        if (charCount) charCount.textContent = '0';
        if (parentInput) parentInput.value = '';
        if (indicator) indicator.hidden = true;
      } else {
        showFlash('error', data.error || 'Une erreur est survenue.');
      }
    } catch {
      showFlash('error', 'Erreur réseau. Veuillez réessayer.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Publier';
    }
  });
})();

// ── Galerie — Lightbox ────────────────────────────────────────────
(function () {
  const galleryDataEl = document.getElementById('gallery-data');
  if (!galleryDataEl) return;

  let images = [];
  try { images = JSON.parse(galleryDataEl.textContent); } catch { return; }
  if (!images.length) return;

  let currentIndex = 0;
  let overlay, imgEl, captionEl, closeBtn, prevBtn, nextBtn;

  function buildOverlay() {
    overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Visionneuse de photos');
    overlay.innerHTML = `
      <button class="lightbox-close" aria-label="Fermer">×</button>
      <button class="lightbox-prev" aria-label="Photo précédente">‹</button>
      <div class="lightbox-img-wrap">
        <img src="" alt="" class="lightbox-img">
        <div class="lightbox-caption"></div>
      </div>
      <button class="lightbox-next" aria-label="Photo suivante">›</button>
    `;
    document.body.appendChild(overlay);

    imgEl     = overlay.querySelector('.lightbox-img');
    captionEl = overlay.querySelector('.lightbox-caption');
    closeBtn  = overlay.querySelector('.lightbox-close');
    prevBtn   = overlay.querySelector('.lightbox-prev');
    nextBtn   = overlay.querySelector('.lightbox-next');

    closeBtn.addEventListener('click', closeLightbox);
    prevBtn.addEventListener('click',  () => navigate(-1));
    nextBtn.addEventListener('click',  () => navigate(1));
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeLightbox(); });

    document.addEventListener('keydown', (e) => {
      if (overlay.style.display === 'flex') {
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  navigate(-1);
        if (e.key === 'ArrowRight') navigate(1);
      }
    });
  }

  function show(index) {
    if (!overlay) buildOverlay();
    currentIndex = index;
    const item = images[index];
    imgEl.src = item.src;
    imgEl.alt = item.alt || '';
    captionEl.textContent = [item.caption, item.credit ? '© ' + item.credit : ''].filter(Boolean).join(' — ');
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  }

  function closeLightbox() {
    overlay.style.display = 'none';
    document.body.style.overflow = '';
  }

  function navigate(dir) {
    show((currentIndex + dir + images.length) % images.length);
  }

  document.querySelectorAll('.gallery-trigger').forEach((btn, i) => {
    btn.addEventListener('click', () => show(i));
  });
})();

// ── Copier le lien ────────────────────────────────────────────────
document.querySelectorAll('.share-btn--copy').forEach(btn => {
  btn.addEventListener('click', async () => {
    const url = btn.dataset.copy;
    try {
      await navigator.clipboard.writeText(url);
      const orig = btn.innerHTML;
      btn.textContent = 'Copié !';
      setTimeout(() => { btn.innerHTML = orig; }, 2000);
    } catch { /* silencieux */ }
  });
});

// ── Load more articles ────────────────────────────────────────────
(function () {
  const btn = document.getElementById('load-more-articles');
  if (!btn) return;

  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    const page = parseInt(btn.dataset.page || '2', 10);
    btn.textContent = 'Chargement…';
    btn.disabled = true;

    try {
      const res  = await fetch('/api/articles?page=' + page);
      const data = await res.json();

      if (data.articles && data.articles.length) {
        const grid = document.querySelector('.articles-grid');
        data.articles.forEach(art => {
          const el = document.createElement('article');
          el.className = 'article-card';
          el.innerHTML = `
            <div class="article-card-body">
              ${art.category_name ? `<a href="/categorie/${art.category_slug}" class="article-category">${art.category_name}</a>` : ''}
              <h3 class="article-card-title"><a href="/article/${art.slug}">${escapeHtml(art.title)}</a></h3>
              ${art.chapo ? `<p class="article-card-chapo">${escapeHtml(art.chapo.substring(0, 130))}…</p>` : ''}
              <div class="article-meta">
                <span class="article-author">Par ${escapeHtml(art.author_name)}</span>
                <time datetime="${art.published_at}">${art.published_at}</time>
              </div>
            </div>
          `;
          grid.appendChild(el);
        });

        btn.dataset.page = page + 1;
        btn.disabled = false;
        btn.textContent = 'Voir plus d\'articles';

        if (!data.has_more) btn.closest('.load-more-wrap').remove();
      } else {
        btn.closest('.load-more-wrap').remove();
      }
    } catch {
      btn.disabled = false;
      btn.textContent = 'Erreur — Réessayer';
    }
  });
})();

// ── Alertes auto-dismiss ──────────────────────────────────────────
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 500ms';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 500);
  }, 6000);
});

// ── Utilitaires ───────────────────────────────────────────────────
function escapeHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showFlash(type, message) {
  const el = document.createElement('div');
  el.className = 'alert alert--' + type;
  el.setAttribute('role', 'alert');
  el.textContent = message;
  const main = document.getElementById('main-content');
  if (main) main.prepend(el);
  setTimeout(() => { el.style.transition = 'opacity 400ms'; el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 5000);
}

// Ajouter les styles lightbox dynamiquement
(function () {
  const style = document.createElement('style');
  style.textContent = `
    .lightbox-overlay {
      position: fixed; inset: 0; z-index: 2000;
      background: rgba(0,0,0,.92);
      display: none; align-items: center; justify-content: center;
    }
    .lightbox-img-wrap { max-width: 90vw; max-height: 90vh; text-align: center; }
    .lightbox-img { max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 4px; }
    .lightbox-caption { color: #ccc; font-family: sans-serif; font-size: .8rem; margin-top: 8px; }
    .lightbox-close, .lightbox-prev, .lightbox-next {
      position: fixed; background: rgba(255,255,255,.1); color: #fff;
      border: none; cursor: pointer; font-size: 1.8rem; border-radius: 4px; padding: 8px 14px; transition: 150ms;
    }
    .lightbox-close:hover, .lightbox-prev:hover, .lightbox-next:hover { background: rgba(255,255,255,.25); }
    .lightbox-close { top: 20px; right: 20px; font-size: 1.4rem; }
    .lightbox-prev  { left: 20px; top: 50%; transform: translateY(-50%); }
    .lightbox-next  { right: 20px; top: 50%; transform: translateY(-50%); }
  `;
  document.head.appendChild(style);
})();
