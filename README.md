# 📰 Koonect.fr — Journal en ligne professionnel

> Plateforme média premium PHP 8.3 · MariaDB · Architecture MVC maison · RGPD conforme · OWASP sécurisé

---
> [!NOTE]
> ### Sommaire
> [Architecture](#architecture)
> [Installation Plesk](#installation-plesk)
> [Installation Docker](#installation-docker)
> [Configuration base de données](#configuration-base-de-données)
> [Configuration SMTP](#configuration-smtp)
> [Sécurité](#sécurité)
> [RGPD](#rgpd)
> [Sous-domaines](#sous-domaines)
> [Backups](#backups)
> [Mise à jour](#mise-à-jour)
> [Troubleshooting](#troubleshooting)

---

## 🏗 Architecture

```
koonect/
├── app/                    # Application MVC
│   ├── config/             # Configuration (config.php)
│   ├── controllers/        # Contrôleurs (public, portal, redac)
│   ├── core/               # Kernel : Router, Request, Response, Session, View
│   ├── helpers/            # CSRF, Slug, Sanitizer, SEO, Paginator
│   ├── middleware/         # Auth, CSRF, RateLimit, Role
│   ├── models/             # Article, User, Category, Tag, Media, Newsletter
│   ├── routes/             # web.php, portal.php, redac.php
│   ├── services/           # Cache, Image, Mail, SSO, TwoFactor
│   └── views/              # Templates PHP (layouts, partials, pages)
├── public/                 # Document root (Apache/Nginx)
│   ├── index.php           # Front controller
│   ├── .htaccess
│   └── assets/             # CSS, JS, images
├── sql/                    # Schémas et seeds MariaDB
├── storage/                # Uploads, cache, logs (hors webroot)
├── docker/                 # Configurations Docker
├── docker-compose.yml
├── composer.json
└── .env.example
```

**Stack :** PHP 8.3 · MariaDB 11 · Apache/Nginx · Vanilla JS · CSS3

---

## 🚀 Installation Plesk

### Prérequis

- PHP 8.3+ avec extensions : `pdo`, `pdo_mysql`, `gd`, `intl`, `mbstring`, `openssl`, `fileinfo`
- MariaDB 10.6+ (11.x recommandé)
- Composer 2.x
- Plesk Obsidian ou Onyx

### Étape 1 — Créer les sous-domaines dans Plesk

Dans Plesk > **Sites Web & Domaines** :

| Sous-domaine | Document Root |
|---|---|
| `koonect.fr` | `/httpdocs/public` |
| `portail.koonect.fr` | `/httpdocs/public` |
| `espace-redactionnel-beta-test-prive-interne-introuvable.koonect.fr` | `/httpdocs/public` |

> ⚠️ Tous les sous-domaines pointent vers le **même** `public/` — c'est le `HTTP_HOST` qui route.

### Étape 2 — Déployer les fichiers

```bash
# Cloner ou uploader le projet dans /httpdocs
cd /var/www/vhosts/koonect.fr/httpdocs

# Copier le projet (hors dossier public/)
cp -r /path/to/koonect/* .

# Installer les dépendances Composer
composer install --no-dev --optimize-autoloader
```

### Étape 3 — Configurer le fichier .env

```bash
cp .env.example .env
nano .env
```

Remplir **au minimum** :
```
APP_KEY=<32 caractères aléatoires>
DB_NAME=koonect_db
DB_USER=koonect_user
DB_PASS=votre_mot_de_passe
SMTP_HOST=mail.koonect.fr
SMTP_USER=noreply@koonect.fr
SMTP_PASS=votre_smtp_password
```

Générer `APP_KEY` :
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Étape 4 — Créer la base de données

Dans Plesk > **Bases de données** :
1. Créer une BDD `koonect_db`
2. Créer un utilisateur `koonect_user` avec tous les droits sur `koonect_db`

Importer le schéma :
```bash
mysql -u koonect_user -p koonect_db < sql/schema.sql
mysql -u koonect_user -p koonect_db < sql/events.sql
mysql -u koonect_user -p koonect_db < sql/seed.sql
```

### Étape 5 — Configurer les permissions

```bash
chmod -R 755 public/
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### Étape 6 — Configurer Apache dans Plesk

Dans Plesk > **Paramètres Apache & nginx** pour chaque sous-domaine :

**Document Root :** `/httpdocs/public`

Activer `mod_rewrite` et ajouter dans les directives additionnelles :
```apache
<Directory /var/www/vhosts/koonect.fr/httpdocs/public>
    AllowOverride All
    Require all granted
</Directory>
```

### Étape 7 — Définir le mot de passe admin

```bash
php -r "echo password_hash('VotreMotDePasseSécurisé!', PASSWORD_ARGON2ID, ['memory_cost'=>65536,'time_cost'=>4,'threads'=>2]);"
```

```sql
UPDATE users SET password_hash='$argon2id$...' WHERE username='admin';
```

### Étape 8 — Activer HTTPS (SSL)

Dans Plesk > **SSL/TLS** : activer Let's Encrypt pour tous les sous-domaines.

Décommenter dans `.htaccess` :
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

## 🐳 Installation Docker

```bash
# 1. Cloner le projet
git clone https://github.com/votre-org/koonect.git
cd koonect

# 2. Configurer l'environnement
cp .env.example .env
# Éditer .env avec vos valeurs

# 3. Lancer les conteneurs
docker-compose up -d --build

# 4. Importer la base de données
docker exec -i koonect_mariadb mysql -u koonect_user -pvotre_pass koonect_db < sql/schema.sql
# Les fichiers events.sql et seed.sql sont automatiquement importés via docker-entrypoint-initdb.d/

# 5. Installer Composer
docker exec koonect_php composer install --no-dev --optimize-autoloader

# 6. Permissions
docker exec koonect_php chown -R www-data:www-data storage/
```

Accès : `http://localhost` (configurer `/etc/hosts` si besoin)

---

## 🗄 Configuration base de données

### MariaDB Event Scheduler

Vérifier que l'Event Scheduler est actif :
```sql
SHOW VARIABLES LIKE 'event_scheduler';
-- Doit afficher : ON
```

Lister les événements actifs :
```sql
SELECT EVENT_NAME, STATUS, LAST_EXECUTED, NEXT_NOT_FOR_SAVE
FROM information_schema.EVENTS
WHERE EVENT_SCHEMA = 'koonect_db'
ORDER BY NEXT_NOT_FOR_SAVE;
```

Forcer l'activation (si pas dans my.cnf) :
```sql
SET GLOBAL event_scheduler = ON;
```

Ajouter dans `/etc/mysql/conf.d/koonect.cnf` :
```ini
[mysqld]
event_scheduler = ON
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

### Index de recherche FULLTEXT

```sql
-- Vérifier l'index FULLTEXT sur articles
SHOW INDEX FROM articles WHERE Key_name = 'ft_articles_search';
```

---

## 📧 Configuration SMTP

### Fichier .env

```env
SMTP_HOST=mail.koonect.fr
SMTP_PORT=587
SMTP_USER=noreply@koonect.fr
SMTP_PASS=votre_password
SMTP_FROM_NAME=Koonect
SMTP_FROM_EMAIL=noreply@koonect.fr
```

### Test SMTP depuis l'espace rédaction

Aller dans : `Paramètres > SMTP > Tester la connexion`

### Configuration DNS pour la délivrabilité

Ajouter ces enregistrements DNS :

```dns
; SPF
koonect.fr.     TXT  "v=spf1 mx a include:mail.koonect.fr ~all"

; DMARC
_dmarc.koonect.fr.  TXT  "v=DMARC1; p=quarantine; rua=mailto:dmarc@koonect.fr"

; DKIM (généré par votre serveur mail)
mail._domainkey.koonect.fr.  TXT  "v=DKIM1; k=rsa; p=..."
```

---

## 🔐 Sécurité

### Mesures implémentées

| Catégorie | Mesure |
|---|---|
| SQL Injection | PDO + requêtes préparées exclusivement |
| XSS | `htmlspecialchars()` systématique, CSP stricte |
| CSRF | Token Synchronizer Pattern sur tous POST/PUT/DELETE |
| Brute force | Rate limiting IP/action en BDD + lockout 15 min |
| Session | Régénération post-auth, cookie httpOnly/Secure/SameSite |
| Passwords | Argon2ID (mémoire 64Mo, 4 itérations, 2 threads) |
| 2FA | TOTP RFC 6238 (Google Authenticator compatible) |
| Uploads | Vérification MIME réelle (finfo), whitelist extensions, no-exec |
| Headers HTTP | CSP, HSTS, X-Frame-Options, X-Content-Type-Options |
| Logs | Événements sécurité horodatés en BDD + fichiers |

### Rotation des clés

Changer `APP_KEY` en production invalide tous les tokens SSO (reconnexion requise) :
```bash
php -r "echo bin2hex(random_bytes(32));"
# Mettre à jour .env
```

### Surveillance des logs

```bash
# Logs applicatifs
tail -f storage/logs/app.log

# Logs sécurité
tail -f storage/logs/security.log

# Logs PHP
tail -f storage/logs/php_errors.log
```

### Restreindre l'espace rédaction par IP (recommandé)

Dans Nginx :
```nginx
location / {
    allow 1.2.3.4;  # IP bureau
    allow 5.6.7.8;  # IP VPN
    deny all;
}
```

Dans Apache (`.htaccess`) :
```apache
Order deny,allow
Deny from all
Allow from 1.2.3.4
```

---

## 🇪🇺 RGPD

### Architecture de conformité

- **Consentements :** table `gdpr_consents` avec IP, user-agent, horodatage
- **Export données :** portail abonné > Mes données > Exporter (JSON)
- **Suppression compte :** soft-delete immédiat, anonymisation automatique sous 30 jours (Event MariaDB)
- **Newsletter :** double opt-in obligatoire, désabonnement en 1 clic dans chaque email
- **Cookies :** bannière RGPD, consentement requis pour cookies analytiques

### Events RGPD automatiques

| Event | Déclenchement | Action |
|---|---|---|
| `evt_gdpr_anonymize_deleted` | Quotidien 02h | Anonymise les comptes supprimés depuis > 30 jours |
| `evt_gdpr_purge_newsletter_unconfirmed` | Quotidien 03h | Supprime les inscriptions non confirmées > 48h |
| `evt_gdpr_purge_old_consents` | Mensuel | Purge les consentements anonymes > 3 ans |
| `evt_purge_sessions` | Horaire | Supprime sessions expirées |
| `evt_purge_tokens` | Horaire | Supprime tokens expirés |

### Droits utilisateurs

| Droit | Implémentation |
|---|---|
| Accès | Portail > Mes données (affichage complet) |
| Rectification | Portail > Mon profil |
| Suppression | Portail > Mes données > Supprimer mon compte |
| Portabilité | Portail > Mes données > Exporter (JSON) |
| Opposition newsletter | Lien dans chaque email + Portail > Newsletter |

---

## 🌐 Sous-domaines

### Routage automatique

L'application détecte le sous-domaine via `HTTP_HOST` et charge les routes correspondantes :

| Host | Routes chargées | Usage |
|---|---|---|
| `koonect.fr` | `routes/web.php` | Site public |
| `portail.koonect.fr` | `routes/portal.php` | Espace abonnés |
| `espace-redactionnel-...` | `routes/redac.php` | Espace rédaction |

### SSO cross-sous-domaines

Le cookie SSO `KOONECT_SSO` est défini sur `.koonect.fr` (domaine parent), ce qui le rend accessible à tous les sous-domaines.

Signature HMAC-SHA256 avec `APP_KEY`. Durée de vie : `SESSION_LIFETIME` (défaut : 1 heure).

---

## 💾 Backups

### Base de données (recommandé : quotidien)

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/koonect"
mkdir -p "$BACKUP_DIR"

mysqldump -u koonect_user -p"$DB_PASS" \
  --single-transaction \
  --routines \
  --events \
  --triggers \
  koonect_db | gzip > "$BACKUP_DIR/koonect_db_$DATE.sql.gz"

# Garder 30 jours
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete

echo "Backup BDD : $BACKUP_DIR/koonect_db_$DATE.sql.gz"
```

### Fichiers (uploads + config)

```bash
# Backup storage (uploads)
tar -czf "/var/backups/koonect/storage_$DATE.tar.gz" storage/uploads/

# Backup .env
cp .env "/var/backups/koonect/env_$DATE.bak"
```

### Crontab recommandé

```cron
# Backup BDD quotidien à 03h00
0 3 * * * /opt/scripts/backup_koonect.sh

# Backup fichiers hebdomadaire dimanche 04h00
0 4 * * 0 tar -czf /var/backups/koonect/uploads_$(date +\%Y\%m\%d).tar.gz /var/www/koonect/storage/uploads/
```

### Restauration

```bash
# Restaurer la BDD
gunzip -c koonect_db_20241201_030000.sql.gz | mysql -u koonect_user -p koonect_db

# Restaurer les uploads
tar -xzf storage_20241201_030000.tar.gz -C /var/www/koonect/
```

---

## 🔄 Mise à jour

```bash
# 1. Sauvegarder avant toute mise à jour
/opt/scripts/backup_koonect.sh

# 2. Mettre le site en maintenance
# Dans l'espace redaction > Paramètres > Mode maintenance : ON

# 3. Déployer les nouveaux fichiers
git pull origin main

# 4. Mettre à jour les dépendances
composer install --no-dev --optimize-autoloader

# 5. Appliquer les migrations SQL si nécessaire
mysql -u koonect_user -p koonect_db < sql/migrations/YYYY_MM_DD_description.sql

# 6. Vider le cache
rm -rf storage/cache/pages/*
rm -rf storage/cache/queries/*

# 7. Désactiver le mode maintenance
# Dans l'espace redaction > Paramètres > Mode maintenance : OFF
```

---

## 🔧 Troubleshooting

### Erreur 500 au démarrage

```bash
# Vérifier les logs PHP
cat storage/logs/php_errors.log | tail -50

# Vérifier les permissions
ls -la storage/
# storage/ doit appartenir à www-data avec 775

# Vérifier la connexion BDD
php -r "
require 'app/config/config.php';
try {
  \$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
  echo 'Connexion BDD OK';
} catch(Exception \$e) {
  echo 'Erreur : ' . \$e->getMessage();
}
"
```

### Redirections en boucle infinie

Vérifier que le Document Root pointe vers `/public` et non vers la racine du projet.

Dans Plesk : Sites Web & Domaines > Paramètres d'hébergement > Répertoire racine : `/httpdocs/public`

### Uploads qui échouent

```bash
# Vérifier les permissions
chmod -R 775 storage/uploads/
chown -R www-data:www-data storage/

# Vérifier la config PHP
php -i | grep upload_max_filesize
# Doit afficher : upload_max_filesize => 10M => 10M

# Dans Plesk : PHP > upload_max_filesize = 10M, post_max_size = 12M
```

### Emails non reçus

```bash
# Test SMTP depuis CLI
php -r "
require 'app/config/config.php';
require 'vendor/autoload.php';
use Koonect\Services\MailService;
\$result = MailService::send('test@example.com', 'Test', 'Test SMTP', '<p>Test</p>');
echo \$result ? 'OK' : 'ÉCHEC';
"

# Vérifier les logs mail
cat storage/logs/mail.log | tail -20
```

### Event Scheduler MariaDB inactif

```bash
# Vérifier
mysql -u root -p -e "SHOW VARIABLES LIKE 'event_scheduler';"

# Activer sans redémarrer
mysql -u root -p -e "SET GLOBAL event_scheduler = ON;"

# Rendre permanent dans /etc/mysql/conf.d/koonect.cnf
echo "[mysqld]" >> /etc/mysql/conf.d/koonect.cnf
echo "event_scheduler = ON" >> /etc/mysql/conf.d/koonect.cnf
```

### Cache obsolète

```bash
# Vider tout le cache
rm -rf storage/cache/pages/*
rm -rf storage/cache/queries/*
echo "Cache vidé"
```

### 404 sur toutes les pages (mod_rewrite)

```bash
# Vérifier que mod_rewrite est activé
apache2ctl -M | grep rewrite

# Activer si absent
a2enmod rewrite
systemctl restart apache2

# Dans le VirtualHost, s'assurer que AllowOverride All est défini
```

### Problème de session entre sous-domaines (SSO)

Vérifier dans `.env` :
```env
SESSION_DOMAIN=.koonect.fr   # Point initial obligatoire
APP_KEY=<valeur non vide>    # Requis pour la signature SSO
```

---

## 📊 Performances

### PHP OPcache (recommandé en production)

```ini
; Dans php.ini ou conf.d/opcache.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0   ; Désactiver en production
opcache.revalidate_freq=0
```

### Cache des pages

Le cache fichier (CacheService) est activé par défaut. TTL configurable via `CACHE_TTL` dans `.env`.

Vider le cache manuellement :
```bash
rm -rf storage/cache/pages/*
```

### Optimisation images

Les images sont automatiquement :
- Redimensionnées (max 1920×1080)
- Converties en WebP (qualité 80)
- Miniaturisées (400×267 WebP)

---

## 🧑‍💻 Rôles utilisateurs

| Rôle | Code | Accès |
|---|---|---|
| Super Administrateur | `admin` | Tout |
| Directeur de publication | `director` | Publication, paramètres |
| Rédacteur en chef | `chief_editor` | Validation, équipe |
| Journaliste | `journalist` | Ses articles uniquement |
| Correcteur | `proofreader` | Relecture, annotations |
| Modérateur | `moderator` | Commentaires |
| Abonné | `subscriber` | Portail lecteur |

### Workflow article

```
[journalist]    draft → review
[proofreader]   review → validation | draft (retour)
[chief_editor]  validation → published | draft (retour)
[director/admin] → published à tout moment
```

---

## 📁 Structure des URLs

| URL | Page |
|---|---|
| `/` | Accueil |
| `/article/:slug` | Article |
| `/categorie/:slug` | Catégorie |
| `/tag/:slug` | Tag |
| `/auteur/:username` | Page auteur |
| `/recherche?q=...` | Recherche |
| `/dossiers` | Dossiers thématiques |
| `/sitemap.xml` | Sitemap index |
| `portail.koonect.fr/` | Tableau de bord abonné |
| `portail.koonect.fr/connexion` | Connexion |
| `portail.koonect.fr/inscription` | Inscription |
| `espace-redactionnel-[...]/` | Dashboard rédaction |
| `espace-redactionnel-[...]/articles` | Liste articles |
| `espace-redactionnel-[...]/articles/nouveau` | Éditeur |

---

## 🤝 Support & Contact

- **Site :** https://koonect.fr
- **Rédaction :** https://espace-redactionnel-beta-test-prive-interne-introuvable.koonect.fr
- **Contact :** contact@koonect.fr

---

*Koonect.fr — Développé avec PHP 8.3, MariaDB et beaucoup d'encre numérique.*
