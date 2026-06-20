# Koonect — Plateforme de journal en ligne professionnelle


<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-8892BF?style=for-the-badge&logo=php" alt="Version PHP">
  <img src="https://img.shields.io/badge/MariaDB-11.x-003545?style=for-the-badge&logo=mariadb" alt="Version MariaDB">
  <img src="https://img.shields.io/badge/Licence-Propriétaire-red?style=for-the-badge" alt="Licence">
  <img src="https://img.shields.io/badge/Sécurité-OWASP-success?style=for-the-badge" alt="Sécurité">
  <img src="https://img.shields.io/badge/RGPD-Conforme-blue?style=for-the-badge" alt="RGPD">
</p>

> [!NOTE]
> Plateforme média premium moderne et sécurisée, propulsée par un moteur MVC PHP natif performant. Elle intègre un portail abonnés, un espace rédaction collaboratif, une newsletter double opt-in et une sécurité avancée.

> [!CAUTION]
> Ce projet est strictement soumis à une licence **propriétaire**. 
> Tout reproduction, modification, distribution, ou utilisation non autorisée est strictement interdite et susceptible de poursuites judiciaires.
> L'ANRDI est une personne physique en capacité de saisir les tribunaux compétents en cas de non-respect de la licence. 
> La licence est disponible est <a href="/LICENSE">ici</a>
---

## 📋 Sommaire

* Architecture
* Installation standard
* Installation Docker
* Configuration base de données
* Configuration SMTP
* Sécurité
* RGPD
* Sous-domaines
* Sauvegardes
* Mise à jour
* Dépannage
* Performances
* Rôles utilisateurs
* Structure des URLs

---

## 🏗 Architecture

```text
project/
├── app/
│   ├── config/
│   ├── controllers/
│   ├── core/
│   ├── helpers/
│   ├── middleware/
│   ├── models/
│   ├── routes/
│   ├── services/
│   └── views/
├── public/
├── sql/
├── storage/
├── docker/
├── docker-compose.yml
├── composer.json
└── .env.example
```

**Stack :** PHP 8.3 | MariaDB 11 | Apache/Nginx | JavaScript Vanilla | CSS3

---

## 🚀 Installation standard

### Prérequis

* PHP 8.3+
* MariaDB 10.6+
* Composer 2.x

Extensions requises :

```text
pdo
pdo_mysql
gd
intl
mbstring
openssl
fileinfo
```

---

### Configuration des sous-domaines

| Sous-domaine          | Racine                    |
| --------------------- | ------------------------- |
| `example.com`         | `/path/to/project/public` |
| `portail.example.com` | `/path/to/project/public` |
| `admin.example.com`   | `/path/to/project/public` |

Tous les sous-domaines doivent pointer vers le même dossier `public/`.

---

### Configuration de l’environnement

```bash
cp .env.example .env
```

Exemple :

```env
APP_KEY=<clé_aléatoire_64_caractères>

DB_NAME=app_db
DB_USER=app_user
DB_PASS=mot_de_passe_fort

SMTP_HOST=mail.example.com
SMTP_USER=noreply@example.com
SMTP_PASS=mot_de_passe_mail

SESSION_DOMAIN=.example.com
```

Générer une clé :

```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

### Importer la base de données

```bash
mysql -u app_user -p app_db < sql/schema.sql
mysql -u app_user -p app_db < sql/events.sql
mysql -u app_user -p app_db < sql/seed.sql
```

---

### Permissions

```bash
chmod -R 755 public/
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

---

### Exemple Apache

```apache
<Directory /path/to/project/public>
    AllowOverride All
    Require all granted
</Directory>
```

---

### HTTPS

Activer SSL sur tous les domaines.

Exemple :

```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

## 🐳 Installation Docker

```bash
git clone https://github.com/your-org/project.git
cd project

cp .env.example .env
docker-compose up -d --build
```

Importer la base :

```bash
docker exec -i app_db mysql -u app_user -p app_db < sql/schema.sql
```

Installer Composer :

```bash
docker exec app_php composer install --no-dev --optimize-autoloader
```

---

## 🗄 Configuration base de données

Vérifier le scheduler :

```sql
SHOW VARIABLES LIKE 'event_scheduler';
```

Activer :

```sql
SET GLOBAL event_scheduler = ON;
```

Configuration permanente :

```ini
[mysqld]
event_scheduler = ON
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

---

## 📧 Configuration SMTP

```env
SMTP_HOST=mail.example.com
SMTP_PORT=587
SMTP_USER=noreply@example.com
SMTP_PASS=mot_de_passe
SMTP_FROM_NAME=NomDuProjet
SMTP_FROM_EMAIL=noreply@example.com
```

Exemple DNS :

```dns
example.com TXT "v=spf1 mx a include:mail.example.com ~all"
_dmarc.example.com TXT "v=DMARC1; p=quarantine"
mail._domainkey.example.com TXT "v=DKIM1; k=rsa; p=..."
```

---

## 🔐 Sécurité

Protections implémentées :

| Catégorie     | Protection               |
| ------------- | ------------------------ |
| Injection SQL | PDO + requêtes préparées |
| XSS           | CSP + échappement        |
| CSRF          | Token synchronisé        |
| Brute force   | Rate limiting            |
| Sessions      | Cookies sécurisés        |
| Mots de passe | Argon2ID                 |
| 2FA           | TOTP                     |
| Uploads       | Validation MIME          |
| Headers       | HSTS, CSP, XFO           |

Rotation de clé :

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Logs :

```bash
tail -f storage/logs/application.log
tail -f storage/logs/security.log
```

Restriction IP :

```nginx
allow YOUR_OFFICE_IP;
allow YOUR_VPN_IP;
deny all;
```

---

## 🇪🇺 RGPD

Fonctionnalités :

* Journalisation des consentements
* Export des données
* Suppression de compte
* Newsletter double opt-in
* Gestion des cookies

Tâches planifiées :

* cleanup_users
* cleanup_sessions
* cleanup_tokens
* cleanup_newsletters

---

## 🌐 Sous-domaines

| Domaine               | Usage           |
| --------------------- | --------------- |
| `example.com`         | Site public     |
| `portail.example.com` | Portail abonnés |
| `admin.example.com`   | Administration  |

Cookie SSO :

```env
SESSION_DOMAIN=.example.com
```

Signature HMAC-SHA256 via `APP_KEY`.

---

## 💾 Sauvegardes

Base de données :

```bash
mysqldump -u app_user -p"$DB_PASS" app_db > /path/to/backups/db.sql
```

Fichiers :

```bash
tar -czf /path/to/backups/storage.tar.gz storage/uploads/
```

Cron :

```cron
0 3 * * * /path/to/scripts/backup.sh
```

---

## 🔄 Mise à jour

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

Migrations :

```bash
mysql -u app_user -p app_db < sql/migrations/file.sql
```

Vider le cache :

```bash
rm -rf storage/cache/*
```

---

## 🔧 Dépannage

Logs PHP :

```bash
tail -f storage/logs/php.log
```

Permissions :

```bash
ls -la storage/
```

Logs SMTP :

```bash
tail -f storage/logs/mail.log
```

Vider le cache :

```bash
rm -rf storage/cache/*
```

Activer rewrite :

```bash
a2enmod rewrite
systemctl restart apache2
```

---

## 📊 Performances

Configuration OPcache :

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

Vider le cache :

```bash
rm -rf storage/cache/*
```

Optimisation images :

* Redimensionnement automatique
* Conversion WebP
* Génération miniatures

---

## 🧑‍💻 Rôles utilisateurs

| Rôle         | Accès         |
| ------------ | ------------- |
| admin        | Accès complet |
| director     | Publication   |
| chief_editor | Validation    |
| journalist   | Articles      |
| proofreader  | Relecture     |
| moderator    | Commentaires  |
| subscriber   | Portail       |

---

## 📁 Structure des URLs

| URL                 | Description     |
| ------------------- | --------------- |
| `/`                 | Accueil         |
| `/article/:slug`    | Article         |
| `/categorie/:slug`  | Catégorie       |
| `/tag/:slug`        | Tag             |
| `/auteur/:username` | Auteur          |
| `/recherche?q=`     | Recherche       |
| `/portail`          | Portail abonnés |
| `/admin`            | Administration  |

---

## 🤝 Support

* Site : https://example.com
* Portail : https://portail.example.com
* Administration : https://admin.example.com
* Contact : [contact@example.com](mailto:contact@example.com)

---

*Développé avec PHP 8.3 et MariaDB.*
