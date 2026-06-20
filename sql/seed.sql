-- ================================================================
-- KOONECT.FR — Données initiales (seed)
-- ================================================================

USE `GlaceVague007`;

-- ── Paramètres par défaut ────────────────────────────────────────
INSERT INTO `settings` (`key`, `value`, `type`, `group`) VALUES
('site_name',            'Koonect',                                  'string',  'general'),
('site_tagline',         "L'actualité sans compromis",               'string',  'general'),
('site_description',     'Journal en ligne indépendant',             'text',    'general'),
('site_logo',            '/assets/img/logo.svg',                     'string',  'general'),
('site_favicon',         '/assets/img/favicon.svg',                  'string',  'general'),
('contact_email',        'contact@koonect.fr',                       'string',  'general'),
('articles_per_page',    '12',                                        'integer', 'display'),
('comments_enabled',     '1',                                         'boolean', 'comments'),
('comments_moderation',  '1',                                         'boolean', 'comments'),
('newsletter_enabled',   '1',                                         'boolean', 'newsletter'),
('analytics_id',         '',                                          'string',  'analytics'),
('color_primary',        '#C8102E',                                   'string',  'design'),
('color_secondary',      '#0A3D6B',                                   'string',  'design'),
('social_twitter',       '',                                          'string',  'social'),
('social_linkedin',      '',                                          'string',  'social'),
('maintenance_mode',     '0',                                         'boolean', 'general'),
('registration_enabled', '1',                                         'boolean', 'auth'),
('smtp_configured',      '0',                                         'boolean', 'smtp')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ── Catégories par défaut ────────────────────────────────────────
INSERT INTO `categories` (`name`, `slug`, `description`, `position`) VALUES
('Politique',     'politique',     'Actualité politique nationale et internationale', 1),
('Économie',      'economie',      'Finance, entreprises, marchés et emploi',         2),
('International', 'international', 'Actualité mondiale et géopolitique',              3),
('Société',       'societe',       'Faits de société, éducation, santé',              4),
('Culture',       'culture',       'Cinéma, musique, arts et littérature',            5),
('Technologie',   'technologie',   'Innovation, numérique et science',                6),
('Sport',         'sport',         'Football, tennis, rugby et toutes disciplines',   7),
('Environnement', 'environnement', 'Climat, écologie et transition énergétique',      8)
ON DUPLICATE KEY UPDATE `position` = VALUES(`position`);

-- ── Tags initiaux ────────────────────────────────────────────────
INSERT INTO `tags` (`name`, `slug`) VALUES
('France',           'france'),
('Europe',           'europe'),
('Élection',         'election'),
('Économie verte',   'economie-verte'),
('Intelligence artificielle', 'intelligence-artificielle'),
('Santé publique',   'sante-publique'),
('Inflation',        'inflation'),
('Réforme',          'reforme'),
('Climat',           'climat'),
('Diplomatie',       'diplomatie')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ── Pages statiques ──────────────────────────────────────────────
INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `status`) VALUES
('Mentions légales', 'mentions-legales',
 '<h2>Éditeur</h2><p>Koonect — Journal en ligne. Directeur de la publication : [Nom]. SIRET : [SIRET]. Hébergeur : [Hébergeur].</p><h2>Propriété intellectuelle</h2><p>L\'ensemble du contenu de ce site est protégé par le droit d\'auteur. Toute reproduction est interdite sans autorisation préalable.</p>',
 'Mentions légales — Koonect', 'published'),
('Conditions générales d\'utilisation', 'cgu',
 '<h2>Objet</h2><p>Les présentes CGU régissent l\'utilisation du site koonect.fr et de ses services associés.</p><h2>Accès au site</h2><p>L\'accès au site est gratuit. Certains contenus premium nécessitent un abonnement.</p>',
 'CGU — Koonect', 'published'),
('Politique de cookies', 'politique-de-cookies',
 '<h2>Utilisation des cookies</h2><p>Nous utilisons des cookies essentiels au fonctionnement du site et des cookies analytiques (avec votre consentement) pour améliorer votre expérience.</p><h2>Gestion des cookies</h2><p>Vous pouvez modifier vos préférences à tout moment via le bouton "Gérer les cookies" en bas de chaque page.</p>',
 'Politique de cookies — Koonect', 'published'),
('Protection des données (RGPD)', 'rgpd',
 '<h2>Responsable du traitement</h2><p>Koonect — contact@koonect.fr</p><h2>Données collectées</h2><p>Email, nom d\'utilisateur, préférences de lecture, consentements.</p><h2>Vos droits</h2><p>Conformément au RGPD, vous disposez d\'un droit d\'accès, de rectification, de suppression et de portabilité de vos données.</p>',
 'RGPD — Koonect', 'published')
ON DUPLICATE KEY UPDATE `status` = 'published';

-- ── Compte admin initial ─────────────────────────────────────────
-- Mot de passe : ChangeMe2024!Koonect (à modifier immédiatement)
-- Hash Argon2ID généré avec les paramètres par défaut
INSERT INTO `users` (`email`, `password_hash`, `username`, `display_name`, `role`, `status`, `email_verified_at`) VALUES
('admin@koonect.fr',
 '$argon2id$v=19$m=65536,t=4,p=2$placeholder_hash_to_regenerate$placeholder',
 'admin',
 'Administrateur',
 'admin',
 'active',
 NOW())
ON DUPLICATE KEY UPDATE `role` = 'admin';

-- Note : Ce hash est un placeholder.
-- Générez un vrai hash avec :
-- php -r "echo password_hash('VotreMotDePasse!', PASSWORD_ARGON2ID, ['memory_cost'=>65536,'time_cost'=>4,'threads'=>2]);"
-- Puis : UPDATE users SET password_hash='$argon2id$...' WHERE username='admin';

-- ── Newsletter : liste par défaut ────────────────────────────────
INSERT INTO `newsletter_lists` (`name`, `slug`, `description`) VALUES
('Newsletter principale', 'principale', 'La newsletter quotidienne de Koonect')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ── Menu principal ───────────────────────────────────────────────
INSERT INTO `menus` (`name`, `location`) VALUES
('Menu principal', 'header'),
('Pied de page',   'footer')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Items du menu principal (basé sur les catégories)
INSERT INTO `menu_items` (`menu_id`, `label`, `url`, `position`) VALUES
(1, 'Politique',     '/categorie/politique',     1),
(1, 'Économie',      '/categorie/economie',      2),
(1, 'International', '/categorie/international', 3),
(1, 'Société',       '/categorie/societe',       4),
(1, 'Culture',       '/categorie/culture',       5),
(1, 'Tech',          '/categorie/technologie',   6),
(1, 'Sport',         '/categorie/sport',         7),
(1, 'Environnement', '/categorie/environnement', 8)
ON DUPLICATE KEY UPDATE `position` = VALUES(`position`);
