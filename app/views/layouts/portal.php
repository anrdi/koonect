<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($seo['seo_title'] ?? 'Mon espace — ' . APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700;900&family=Source+Serif+4:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/app.css') ?>">
  <link rel="stylesheet" href="/assets/css/portal.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/portal.css') ?>">
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="portal-body">

<header class="portal-header" role="banner">
  <div class="portal-header-inner">
    <a href="<?= APP_URL ?>" class="portal-logo" aria-label="Retour à <?= APP_NAME ?>">
      <span class="logo-text"><?= APP_NAME ?></span><span class="logo-dot">.</span>
    </a>
    <span class="portal-badge">Espace abonné</span>
    <nav class="portal-nav" aria-label="Navigation espace abonné">
      <?php if ($currentUser): ?>
        <a href="<?= PORTAL_URL ?>/">Tableau de bord</a>
        <a href="<?= PORTAL_URL ?>/profil">Mon profil</a>
        <a href="<?= PORTAL_URL ?>/favoris">Favoris</a>
        <a href="<?= PORTAL_URL ?>/donnees">Mes données</a>
        <a href="<?= PORTAL_URL ?>/deconnexion" class="portal-nav-logout">Déconnexion</a>
      <?php else: ?>
        <a href="<?= PORTAL_URL ?>/connexion">Connexion</a>
        <a href="<?= PORTAL_URL ?>/inscription" class="btn btn--primary btn--sm">S'inscrire</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="portal-main" role="main">
  <?php if ($flashSuccess ?? null): ?>
    <div class="alert alert--success"><?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashError ?? null): ?>
    <div class="alert alert--error"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>
  <?php if ($flashInfo ?? null): ?>
    <div class="alert alert--info"><?= htmlspecialchars($flashInfo) ?></div>
  <?php endif; ?>

  <?= $content ?>
</main>

<footer class="portal-footer">
  <div>
    <a href="<?= APP_URL ?>">← Retour au journal</a>
    · <a href="<?= APP_URL ?>/rgpd">RGPD</a>
    · <a href="<?= APP_URL ?>/mentions-legales">Mentions légales</a>
    · <a href="<?= APP_URL ?>/politique-de-cookies">Cookies</a>
    · <a href="<?= APP_URL ?>/declarations-accesibilite">Accessibilité</a>
  </div>
  <div>© <?= date('Y') ?> <?= APP_NAME ?></div>
</footer>

<script src="/assets/js/portal.js" defer nonce="<?= htmlspecialchars($cspNonce) ?>"></script>
</body>
</html>
