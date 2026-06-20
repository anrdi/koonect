/* ================================================================
   KOONECT — JavaScript Portail Abonnés
   ================================================================ */

'use strict';

// ── Favoris AJAX ──────────────────────────────────────────────────
(function () {
  document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const articleId = btn.dataset.article;
      const csrf      = document.querySelector('input[name="csrf_token"]')?.value || '';

      try {
        const res  = await fetch(`${window.PORTAL_URL || ''}/favoris/${articleId}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: new URLSearchParams({ csrf_token: csrf }),
        });
        const data = await res.json();

        if (data.action === 'added') {
          btn.classList.add('is-favorited');
          btn.setAttribute('aria-label', 'Retirer des favoris');
          btn.title = 'Retirer des favoris';
        } else {
          btn.classList.remove('is-favorited');
          btn.setAttribute('aria-label', 'Ajouter aux favoris');
          btn.title = 'Ajouter aux favoris';
        }
      } catch {
        // Silencieux
      }
    });
  });
})();

// ── Suppression de compte : confirmation double ───────────────────
(function () {
  const deleteForm = document.querySelector('form[action$="/donnees/supprimer"]');
  if (!deleteForm) return;

  deleteForm.addEventListener('submit', (e) => {
    const email = document.getElementById('confirm_email')?.value || '';
    const pw    = document.getElementById('confirm_password')?.value || '';

    if (!email || !pw) {
      e.preventDefault();
      alert('Veuillez remplir tous les champs pour confirmer la suppression.');
      return;
    }

    if (!confirm('⚠️ Cette action est IRRÉVERSIBLE.\n\nVotre compte sera définitivement supprimé et vos données anonymisées.\n\nÊtes-vous absolument certain ?')) {
      e.preventDefault();
    }
  });
})();

// ── Export données : feedback visuel ─────────────────────────────
document.querySelector('a[href$="/exporter"]')?.addEventListener('click', function () {
  this.textContent = '⏳ Préparation de l\'export…';
  this.style.pointerEvents = 'none';
  setTimeout(() => {
    this.textContent = '⬇ Exporter mes données (JSON)';
    this.style.pointerEvents = '';
  }, 3000);
});

// ── Alertes auto-dismiss ──────────────────────────────────────────
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 500ms';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 500);
  }, 6000);
});
