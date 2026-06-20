<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Espace Rédaction <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/editor.css">
  <meta name="robots" content="noindex, nofollow">
  <style>
    body { background: #1A1A1A; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .redac-login-wrap { background: #fff; border-radius: 8px; padding: 48px; width: 380px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
    .redac-login-logo { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 900; color: #1A1A1A; margin-bottom: 4px; }
    .redac-login-logo .logo-dot { color: #C8102E; }
    .redac-login-badge { display: inline-block; background: #C8102E; color: #fff; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; padding: 3px 10px; border-radius: 2px; margin-bottom: 24px; }
    .redac-login-wrap h1 { font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; color: #1A1A1A; }
    .warning-notice { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 10px 14px; font-size: .78rem; color: #92400e; margin-bottom: 20px; border-radius: 2px; }
  </style>
</head>
<body>
<div class="redac-login-wrap">
  <div class="redac-login-logo"><?= APP_NAME ?><span class="logo-dot">.</span></div>
  <span class="redac-login-badge">Espace Rédaction</span>
  <h1>Connexion sécurisée</h1>

  <?php if ($flashError ?? null): ?>
    <div class="alert alert--error"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <div class="warning-notice">
    ⚠️ Accès réservé aux membres de la rédaction. Toute tentative d'accès non autorisé est enregistrée.
  </div>

  <form method="post" action="<?= REDAC_URL ?>/login" novalidate>
    <?= \Koonect\Helpers\Csrf::field() ?>
    <div class="form-group">
      <label for="email">Adresse email professionnelle</label>
      <input type="email" id="email" name="email" required autocomplete="email" autofocus
             placeholder="vous@koonect.fr">
    </div>
    <div class="form-group">
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required autocomplete="current-password"
             placeholder="••••••••••••">
    </div>
    <div class="form-submit">
      <button type="submit" class="btn btn--primary btn--full">Accéder à la rédaction</button>
    </div>
  </form>
</div>
</body>
</html>
