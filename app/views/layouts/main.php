<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <?= \Koonect\Helpers\Seo::metaTags($seo ?? []) ?>

  <meta name="theme-color" content="#1A1A1A">
  <link rel="alternate" type="application/rss+xml" title="<?= APP_NAME ?> — Flux RSS" href="<?= APP_URL ?>/rss.xml">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Source+Serif+4:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

  <?php $cssVersion = @filemtime(PUBLIC_PATH . '/assets/css/app.css') ?: time(); ?>
  <?php $jsVersion  = @filemtime(PUBLIC_PATH . '/assets/js/app.js') ?: time(); ?>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css?v=<?= $cssVersion ?>">
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/favicon.svg">
  <?= $schemaJson ?? '' ?>
  <?php if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/'): ?>
    <?= \Koonect\Helpers\Seo::schemaOrganizationAndWebSite() ?>
  <?php endif; ?>
  <script src="https://widget.tabnav.com/limited-widget.min.js.gz?req=o1fqYPKH2PRJ8e-HdatB6mnqok0" tnv-data-config='{"language":"fr","color":"#405ec3","buttonColor":"#405ec3","buttonSize":"small","widgetSize":"small","widgetLocation":"right","buttonLocation":"bottom"}' defer nonce="<?= htmlspecialchars($cspNonce) ?>"></script>
    <noscript> JavaScript is required for our <a href="https://tabnav.com/accessibility-widget">tabnav widget</a> to work properly. </noscript>
</head>
<body class="<?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">

<?php if ($breaking ?? []): ?>
<div class="breaking-bar" role="alert" aria-live="polite">
  <span class="breaking-label">Breaking</span>
  <div class="breaking-ticker">
    <?php foreach ($breaking as $b): ?>
      <a href="<?= APP_URL ?>/article/<?= htmlspecialchars($b['slug']) ?>"><?= htmlspecialchars($b['title']) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<header class="site-header" role="banner">
  <div class="header-top">
    <div class="container header-top-inner">
      <div class="header-date"><?= \Koonect\Helpers\DateFormatter::frenchLong() ?></div>
      <div class="header-actions">
        <?php if ($currentUser): ?>
          <a href="<?= PORTAL_URL ?>/" class="btn-header">Mon espace</a>
        <?php else: ?>
          <a href="<?= PORTAL_URL ?>/connexion" class="btn-header">Connexion</a>
          <a href="<?= PORTAL_URL ?>/inscription" class="btn-header btn-header--primary">S'abonner</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="header-brand">
    <div class="container">
      <a href="<?= APP_URL ?>" class="site-logo" aria-label="<?= APP_NAME ?> — Accueil">
        <span class="logo-text"><?= APP_NAME ?></span>
        <span class="logo-dot">.</span>
      </a>
      <p class="site-tagline">L'actualité sans compromis</p>
    </div>
  </div>

  <nav class="site-nav" role="navigation" aria-label="Navigation principale">
    <div class="container">
      <button class="nav-burger" aria-label="Menu" aria-expanded="false" aria-controls="main-nav">
        <span></span><span></span><span></span>
      </button>
      <ul class="nav-list" id="main-nav" role="list">
        <?php foreach ($siteSettings['nav_categories'] ?? [] as $cat): ?>
          <li><a href="<?= APP_URL ?>/categorie/<?= htmlspecialchars($cat['slug']) ?>"
                 class="nav-link <?= ($_SERVER['REQUEST_URI'] ?? '') === '/categorie/' . $cat['slug'] ? 'is-active' : '' ?>">
            <?= htmlspecialchars($cat['name']) ?>
          </a></li>
        <?php endforeach; ?>
        <li><a href="<?= APP_URL ?>/dossiers" class="nav-link">Dossiers</a></li>
      </ul>
      <form class="nav-search" action="<?= APP_URL ?>/recherche" method="get" role="search">
        <label for="nav-search-input" class="sr-only">Rechercher</label>
        <input id="nav-search-input" type="search" name="q" placeholder="Rechercher…" autocomplete="off"
               aria-label="Rechercher un article">
        <button type="submit" aria-label="Lancer la recherche">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </button>
      </form>
    </div>
  </nav>
</header>

<main id="main-content" role="main">
  <?php if ($flashSuccess ?? null): ?>
    <div class="alert alert--success" role="alert"><?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashError ?? null): ?>
    <div class="alert alert--error" role="alert"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>
  <?php if ($flashInfo ?? null): ?>
    <div class="alert alert--info" role="alert"><?= htmlspecialchars($flashInfo) ?></div>
  <?php endif; ?>

  <?= $content ?>
</main>

<footer class="site-footer" role="contentinfo">
  <div class="footer-newsletter">
    <div class="container">
      <div class="newsletter-block">
        <h2>La newsletter <?= APP_NAME ?></h2>
        <p>Les articles essentiels chaque matin dans votre boîte mail.</p>
        <form class="newsletter-form" action="<?= APP_URL ?>/newsletter/inscription" method="post">
          <?= \Koonect\Helpers\Csrf::field() ?>
          <input type="hidden" name="redirect" value="<?= htmlspecialchars(APP_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
          <div class="newsletter-form-row">
            <input type="email" name="email" placeholder="votre@email.fr" required aria-label="Votre adresse email">
            <button type="submit">S'abonner</button>
          </div>
          <p class="newsletter-legal">En vous inscrivant, vous acceptez notre <a href="<?= APP_URL ?>/rgpd">politique de confidentialité</a>. Désinscription en un clic.</p>
        </form>
      </div>
    </div>
  </div>

  <div class="footer-main">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand-col">
          <a href="<?= APP_URL ?>" class="footer-logo"><?= APP_NAME ?><span class="logo-dot">.</span></a>
          <p>Journal en ligne indépendant.<br>L'actualité en continu, vérifiée et contextualisée.</p>
        </div>
        <div class="footer-nav-col">
          <h3>Rubriques</h3>
          <ul>
            <?php foreach ($siteSettings['nav_categories'] ?? [] as $cat): ?>
              <li><a href="<?= APP_URL ?>/categorie/<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="footer-nav-col">
          <h3>Le journal</h3>
          <ul>
            <li><a href="<?= APP_URL ?>/mentions-legales">Mentions légales</a></li>
            <li><a href="<?= APP_URL ?>/cgu">CGU</a></li>
            <li><a href="<?= APP_URL ?>/politique-de-cookies">Cookies</a></li>
            <li><a href="<?= APP_URL ?>/rgpd">RGPD</a></li>
            <li><a href="<?= APP_URL ?>/declarations-accesibilite">Accessibilité</a></li>
            <li><a href="<?= APP_URL ?>/contact">Contact</a></li>
            <li><a href="<?= APP_URL ?>/sitemap.xml">Plan du site (XML)</a></li>
          </ul>
        </div>
        <div class="footer-nav-col">
          <h3>Mon compte</h3>
          <ul>
            <li><a href="<?= PORTAL_URL ?>/connexion">Se connecter</a></li>
            <li><a href="<?= PORTAL_URL ?>/inscription">S'inscrire</a></li>
            <li><a href="<?= PORTAL_URL ?>/">Mon espace</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p>© <?= date('Y') ?> <?= APP_NAME ?>. Tous droits réservés.</p>
      <button id="cookie-settings-btn" class="btn-link">Gérer les cookies</button>
    </div>
  </div>
</footer>

<!-- Bannière cookies RGPD -->
<div id="cookie-banner" class="cookie-banner" role="dialog" aria-modal="true" aria-label="Gestion des cookies" hidden>
  <div class="cookie-banner-inner">
    <div class="cookie-banner-text">
      <strong>Nous utilisons des cookies</strong>
      <p>Des cookies essentiels assurent le fonctionnement du site. Des cookies analytiques nous aident à améliorer votre expérience.</p>
    </div>
    <div class="cookie-banner-actions">
      <button id="cookie-accept-all" class="btn btn--primary">Tout accepter</button>
      <button id="cookie-accept-essential" class="btn btn--secondary">Essentiels uniquement</button>
      <a href="<?= APP_URL ?>/politique-de-cookies" class="btn-link">En savoir plus</a>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js?v=<?= $jsVersion ?>" defer nonce="<?= htmlspecialchars($cspNonce) ?>"></script>
</body>
</html>
