<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Espace rédaction') ?> — <?= APP_NAME ?> Rédaction</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/app.css') ?>">
  <link rel="stylesheet" href="/assets/css/editor.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/editor.css') ?>">
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="redac-body">

<div class="redac-layout">

  <!-- SIDEBAR NAVIGATION -->
  <nav class="redac-sidebar" role="navigation" aria-label="Navigation rédaction">
    <div class="redac-sidebar-logo">
      <a href="<?= REDAC_URL ?>/">
        <span class="logo-text"><?= APP_NAME ?></span><span class="logo-dot">.</span>
      </a>
      <span class="redac-badge">Rédaction</span>
    </div>

    <?php $user = \Koonect\Core\Session::get('user'); $role = $user['role'] ?? ''; ?>
    <div class="redac-user-info">
      <strong><?= htmlspecialchars($user['display_name'] ?? '') ?></strong>
      <span class="redac-role-badge redac-role-badge--<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span>
    </div>

    <ul class="redac-nav-list" role="list">
      <li><a href="<?= REDAC_URL ?>/" class="redac-nav-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Tableau de bord
      </a></li>
      <li><a href="<?= REDAC_URL ?>/articles" class="redac-nav-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Articles
      </a></li>
      <li><a href="<?= REDAC_URL ?>/articles/nouveau" class="redac-nav-link redac-nav-link--cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouvel article
      </a></li>
      <li><a href="<?= REDAC_URL ?>/medias" class="redac-nav-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        Médiathèque
      </a></li>
      <li><a href="<?= REDAC_URL ?>/commentaires" class="redac-nav-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Commentaires
      </a></li>

      <?php if (in_array($role, ['admin', 'director', 'chief_editor'])): ?>
      <li class="redac-nav-separator">Administration</li>
      <li><a href="<?= REDAC_URL ?>/categories" class="redac-nav-link">Catégories</a></li>
      <li><a href="<?= REDAC_URL ?>/tags" class="redac-nav-link">Tags</a></li>
      <li><a href="<?= REDAC_URL ?>/newsletter" class="redac-nav-link">Newsletter</a></li>
      <?php endif; ?>

      <?php if (in_array($role, ['admin', 'director'])): ?>
      <li><a href="<?= REDAC_URL ?>/utilisateurs" class="redac-nav-link">Utilisateurs</a></li>
      <li><a href="<?= REDAC_URL ?>/parametres" class="redac-nav-link">Paramètres</a></li>
      <?php endif; ?>
    </ul>

    <div class="redac-sidebar-footer">
      <a href="<?= APP_URL ?>" target="_blank" class="redac-nav-link">Voir le site →</a>
      <a href="<?= REDAC_URL ?>/logout" class="redac-nav-link redac-nav-link--logout">Déconnexion</a>
    </div>
  </nav>

  <!-- CONTENU PRINCIPAL -->
  <div class="redac-content">
    <div class="redac-topbar">
      <?php if ($flashSuccess ?? null): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError ?? null): ?>
        <div class="alert alert--error"><?= htmlspecialchars($flashError) ?></div>
      <?php endif; ?>
    </div>

    <main class="redac-main" role="main">
      <?= $content ?>
    </main>
  </div>
</div>

<script src="/assets/js/app.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/app.js') ?>" defer nonce="<?= htmlspecialchars($cspNonce) ?>"></script>
<script src="/assets/js/editor.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/editor.js') ?>" defer nonce="<?= htmlspecialchars($cspNonce) ?>"></script>
<script src="/assets/js/media.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/media.js') ?>" defer nonce="<?= htmlspecialchars($cspNonce) ?>"></script>
</body>
</html>
