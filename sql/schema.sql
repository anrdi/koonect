-- ================================================================
-- KOONECT.FR — Schéma MariaDB complet
-- Encodage : utf8mb4 / utf8mb4_unicode_ci
-- ================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+01:00';


USE `GlaceVague007`;

-- ── Activer l'EVENT SCHEDULER ───────────────────────────────────

-- ================================================================
-- UTILISATEURS
-- ================================================================
CREATE TABLE `users` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `email`               VARCHAR(254)     NOT NULL,
  `password_hash`       VARCHAR(255)     NOT NULL DEFAULT '',
  `username`            VARCHAR(60)      NOT NULL,
  `display_name`        VARCHAR(120)     NOT NULL,
  `avatar`              VARCHAR(500)     DEFAULT NULL,
  `role`                ENUM('admin','director','chief_editor','journalist','proofreader','moderator','subscriber')
                                         NOT NULL DEFAULT 'subscriber',
  `status`              ENUM('active','inactive','banned','deleted')
                                         NOT NULL DEFAULT 'inactive',
  `email_verified_at`   DATETIME         DEFAULT NULL,
  `two_factor_secret`   VARCHAR(32)      DEFAULT NULL,
  `two_factor_enabled`  TINYINT(1)       NOT NULL DEFAULT 0,
  `last_login_at`       DATETIME         DEFAULT NULL,
  `created_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`          DATETIME         DEFAULT NULL,
  `anonymized_at`       DATETIME         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email`    (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role`           (`role`),
  KEY `idx_users_status`         (`status`),
  KEY `idx_users_deleted_at`     (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PROFILS ABONNÉS
-- ================================================================
CREATE TABLE `subscriber_profiles` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`               INT UNSIGNED NOT NULL,
  `bio`                   TEXT DEFAULT NULL,
  `preferences_json`      JSON DEFAULT NULL,
  `newsletter_opt_in`     TINYINT(1) NOT NULL DEFAULT 0,
  `newsletter_confirmed_at` DATETIME DEFAULT NULL,
  `reading_score`         INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriber_user` (`user_id`),
  CONSTRAINT `fk_subscriber_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SESSIONS
-- ================================================================
CREATE TABLE `sessions` (
  `id`            VARCHAR(36)  NOT NULL,
  `user_id`       INT UNSIGNED DEFAULT NULL,
  `payload`       MEDIUMTEXT   NOT NULL,
  `ip_address`    VARCHAR(45)  DEFAULT NULL,
  `user_agent`    VARCHAR(500) DEFAULT NULL,
  `last_activity` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user_id`       (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`),
  KEY `idx_sessions_expires_at`    (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TOKENS (reset mdp, vérif email, newsletter, 2FA)
-- ================================================================
CREATE TABLE `tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `type`       ENUM('email_verification','password_reset','2fa_backup','api') NOT NULL,
  `token_hash` VARCHAR(64)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used_at`    DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_tokens_user_type` (`user_id`, `type`),
  KEY `idx_tokens_expires_at` (`expires_at`),
  CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- CATÉGORIES
-- ================================================================
CREATE TABLE `categories` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL,
  `slug`             VARCHAR(120) NOT NULL,
  `description`      TEXT DEFAULT NULL,
  `parent_id`        INT UNSIGNED DEFAULT NULL,
  `meta_title`       VARCHAR(70)  DEFAULT NULL,
  `meta_description` VARCHAR(160) DEFAULT NULL,
  `og_image`         VARCHAR(500) DEFAULT NULL,
  `position`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  KEY `idx_categories_position` (`position`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- DOSSIERS THÉMATIQUES
-- ================================================================
CREATE TABLE `dossiers` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`            VARCHAR(255) NOT NULL,
  `slug`             VARCHAR(280) NOT NULL,
  `description`      TEXT DEFAULT NULL,
  `cover_image_id`   INT UNSIGNED DEFAULT NULL,
  `meta_title`       VARCHAR(70)  DEFAULT NULL,
  `meta_description` VARCHAR(160) DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dossiers_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TAGS
-- ================================================================
CREATE TABLE `tags` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)  NOT NULL,
  `slug`       VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- DOSSIERS MÉDIATHÈQUE
-- ================================================================
CREATE TABLE `media_folders` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `parent_id`  INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_folders_parent` (`parent_id`),
  CONSTRAINT `fk_media_folders_parent` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- MÉDIAS
-- ================================================================
CREATE TABLE `media` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `filename`      VARCHAR(255)  NOT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `path`          VARCHAR(500)  NOT NULL,
  `webp_path`     VARCHAR(500)  DEFAULT NULL,
  `thumb_path`    VARCHAR(500)  DEFAULT NULL,
  `mime_type`     VARCHAR(100)  NOT NULL,
  `size`          INT UNSIGNED  NOT NULL DEFAULT 0,
  `width`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `height`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `alt_text`      VARCHAR(255)  DEFAULT NULL,
  `caption`       TEXT          DEFAULT NULL,
  `credit`        VARCHAR(255)  DEFAULT NULL,
  `folder_id`     INT UNSIGNED  DEFAULT NULL,
  `uploaded_by`   INT UNSIGNED  DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_folder`      (`folder_id`),
  KEY `idx_media_uploaded_by` (`uploaded_by`),
  KEY `idx_media_created_at`  (`created_at`),
  CONSTRAINT `fk_media_folder`      FOREIGN KEY (`folder_id`)   REFERENCES `media_folders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ARTICLES
-- ================================================================
CREATE TABLE `articles` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(500)  NOT NULL,
  `subtitle`          VARCHAR(500)  DEFAULT NULL,
  `chapo`             TEXT          DEFAULT NULL,
  `content`           LONGTEXT      NOT NULL,
  `slug`              VARCHAR(550)  NOT NULL,
  `author_id`         INT UNSIGNED  NOT NULL,
  `category_id`       INT UNSIGNED  DEFAULT NULL,
  `status`            ENUM('draft','review','validation','published','archived')
                                    NOT NULL DEFAULT 'draft',
  `featured_image_id` INT UNSIGNED  DEFAULT NULL,
  `reading_time`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `views_count`       INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_breaking`       TINYINT(1)    NOT NULL DEFAULT 0,
  `is_featured`       TINYINT(1)    NOT NULL DEFAULT 0,
  `is_premium`        TINYINT(1)    NOT NULL DEFAULT 0,
  `published_at`      DATETIME      DEFAULT NULL,
  `scheduled_at`      DATETIME      DEFAULT NULL,
  `seo_title`         VARCHAR(70)   DEFAULT NULL,
  `seo_description`   VARCHAR(160)  DEFAULT NULL,
  `og_image`          VARCHAR(500)  DEFAULT NULL,
  `canonical_url`     VARCHAR(500)  DEFAULT NULL,
  `schema_type`       VARCHAR(50)   NOT NULL DEFAULT 'NewsArticle',
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_articles_slug` (`slug`),
  KEY `idx_articles_status`       (`status`),
  KEY `idx_articles_author`       (`author_id`),
  KEY `idx_articles_category`     (`category_id`),
  KEY `idx_articles_published_at` (`published_at`),
  KEY `idx_articles_is_featured`  (`is_featured`),
  KEY `idx_articles_is_breaking`  (`is_breaking`),
  KEY `idx_articles_deleted_at`   (`deleted_at`),
  KEY `idx_articles_views`        (`views_count`),
  FULLTEXT KEY `ft_articles_search` (`title`, `chapo`, `content`),
  CONSTRAINT `fk_articles_author`       FOREIGN KEY (`author_id`)         REFERENCES `users`      (`id`),
  CONSTRAINT `fk_articles_category`     FOREIGN KEY (`category_id`)       REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_articles_featured_img` FOREIGN KEY (`featured_image_id`) REFERENCES `media`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PIVOTS
-- ================================================================
CREATE TABLE `article_tags` (
  `article_id` INT UNSIGNED NOT NULL,
  `tag_id`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  CONSTRAINT `fk_at_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_at_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `tags`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `article_dossiers` (
  `article_id` INT UNSIGNED NOT NULL,
  `dossier_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`article_id`, `dossier_id`),
  CONSTRAINT `fk_ad_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ad_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- GALERIES ARTICLES
-- ================================================================
CREATE TABLE `article_galleries` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL,
  `media_id`   INT UNSIGNED NOT NULL,
  `position`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `caption`    TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ag_article` (`article_id`),
  CONSTRAINT `fk_ag_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ag_media`   FOREIGN KEY (`media_id`)   REFERENCES `media`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- RÉVISIONS ARTICLES (workflow)
-- ================================================================
CREATE TABLE `article_revisions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id`  INT UNSIGNED NOT NULL,
  `editor_id`   INT UNSIGNED NOT NULL,
  `status_from` VARCHAR(20) NOT NULL DEFAULT '',
  `status_to`   VARCHAR(20) NOT NULL,
  `note`        TEXT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_revisions_article` (`article_id`),
  CONSTRAINT `fk_revisions_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_revisions_editor`  FOREIGN KEY (`editor_id`)  REFERENCES `users`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- COMMENTAIRES
-- ================================================================
CREATE TABLE `comments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `parent_id`  INT UNSIGNED DEFAULT NULL,
  `content`    TEXT NOT NULL,
  `status`     ENUM('pending','approved','rejected','spam') NOT NULL DEFAULT 'pending',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comments_article` (`article_id`),
  KEY `idx_comments_user`    (`user_id`),
  KEY `idx_comments_status`  (`status`),
  KEY `idx_comments_parent`  (`parent_id`),
  CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_parent`  FOREIGN KEY (`parent_id`)  REFERENCES `comments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- HISTORIQUE LECTURE
-- ================================================================
CREATE TABLE `reading_history` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `article_id` INT UNSIGNED NOT NULL,
  `read_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration_seconds` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reading_history` (`user_id`, `article_id`),
  KEY `idx_rh_article` (`article_id`),
  CONSTRAINT `fk_rh_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rh_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- FAVORIS
-- ================================================================
CREATE TABLE `favorites` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `article_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_favorites` (`user_id`, `article_id`),
  KEY `idx_favorites_article` (`article_id`),
  CONSTRAINT `fk_fav_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- NEWSLETTER — ABONNÉS
-- ================================================================
CREATE TABLE `newsletter_subscribers` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`            VARCHAR(254) NOT NULL,
  `user_id`          INT UNSIGNED DEFAULT NULL,
  `token_hash`       VARCHAR(64)  NOT NULL,
  `confirmed_at`     DATETIME DEFAULT NULL,
  `unsubscribed_at`  DATETIME DEFAULT NULL,
  `ip_address`       VARCHAR(45)  DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newsletter_email` (`email`),
  KEY `idx_nl_sub_user`         (`user_id`),
  KEY `idx_nl_sub_confirmed`    (`confirmed_at`),
  KEY `idx_nl_sub_unsubscribed` (`unsubscribed_at`),
  CONSTRAINT `fk_nl_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- NEWSLETTER — LISTES
-- ================================================================
CREATE TABLE `newsletter_lists` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nl_lists_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- NEWSLETTER — CAMPAGNES
-- ================================================================
CREATE TABLE `newsletter_campaigns` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `list_id`      INT UNSIGNED DEFAULT NULL,
  `subject`      VARCHAR(255) NOT NULL,
  `preheader`    VARCHAR(255) DEFAULT NULL,
  `content_html` MEDIUMTEXT   NOT NULL,
  `content_text` MEDIUMTEXT   DEFAULT NULL,
  `status`       ENUM('draft','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `sent_at`      DATETIME DEFAULT NULL,
  `stats_json`   JSON DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaigns_status` (`status`),
  CONSTRAINT `fk_campaigns_list` FOREIGN KEY (`list_id`) REFERENCES `newsletter_lists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- NEWSLETTER — ENVOIS
-- ================================================================
CREATE TABLE `newsletter_sends` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`   INT UNSIGNED NOT NULL,
  `subscriber_id` INT UNSIGNED NOT NULL,
  `sent_at`       DATETIME DEFAULT NULL,
  `opened_at`     DATETIME DEFAULT NULL,
  `clicked_at`    DATETIME DEFAULT NULL,
  `bounced_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nl_sends` (`campaign_id`, `subscriber_id`),
  KEY `idx_nl_sends_subscriber` (`subscriber_id`),
  CONSTRAINT `fk_nl_sends_campaign`   FOREIGN KEY (`campaign_id`)   REFERENCES `newsletter_campaigns`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nl_sends_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- CONSENTEMENTS RGPD
-- ================================================================
CREATE TABLE `gdpr_consents` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(36)  DEFAULT NULL,
  `type`       VARCHAR(50)  NOT NULL,
  `granted`    TINYINT(1)   NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gdpr_user`       (`user_id`),
  KEY `idx_gdpr_type`       (`type`),
  KEY `idx_gdpr_created_at` (`created_at`),
  CONSTRAINT `fk_gdpr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- LOGS DE SÉCURITÉ
-- ================================================================
CREATE TABLE `security_logs` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type`   VARCHAR(60)  NOT NULL,
  `user_id`      INT UNSIGNED DEFAULT NULL,
  `ip_address`   VARCHAR(45)  DEFAULT NULL,
  `user_agent`   VARCHAR(500) DEFAULT NULL,
  `details_json` JSON DEFAULT NULL,
  `severity`     ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seclog_event`      (`event_type`),
  KEY `idx_seclog_user`       (`user_id`),
  KEY `idx_seclog_ip`         (`ip_address`),
  KEY `idx_seclog_created_at` (`created_at`),
  KEY `idx_seclog_severity`   (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- RATE LIMITING
-- ================================================================
CREATE TABLE `rate_limits` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address`    VARCHAR(45)  NOT NULL,
  `action`        VARCHAR(255) NOT NULL,
  `attempts`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `window_start`  INT UNSIGNED NOT NULL,
  `blocked_until` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate_limits` (`ip_address`, `action`(100)),
  KEY `idx_rate_limits_blocked` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- REDIRECTIONS 301
-- ================================================================
CREATE TABLE `redirects` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_url`   VARCHAR(500) NOT NULL,
  `to_url`     VARCHAR(500) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redirects_from` (`from_url`(200))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PAGES STATIQUES
-- ================================================================
CREATE TABLE `pages` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`            VARCHAR(255) NOT NULL,
  `slug`             VARCHAR(280) NOT NULL,
  `content`          LONGTEXT     NOT NULL,
  `meta_title`       VARCHAR(70)  DEFAULT NULL,
  `meta_description` VARCHAR(160) DEFAULT NULL,
  `status`           ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- MENUS
-- ================================================================
CREATE TABLE `menus` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80) NOT NULL,
  `location`   VARCHAR(40) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menus_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id`     INT UNSIGNED NOT NULL,
  `parent_id`   INT UNSIGNED DEFAULT NULL,
  `label`       VARCHAR(120) NOT NULL,
  `url`         VARCHAR(500) DEFAULT NULL,
  `article_id`  INT UNSIGNED DEFAULT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `page_id`     INT UNSIGNED DEFAULT NULL,
  `position`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `target`      VARCHAR(10) NOT NULL DEFAULT '_self',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_items_menu`   (`menu_id`),
  KEY `idx_menu_items_parent` (`parent_id`),
  CONSTRAINT `fk_menu_items_menu`   FOREIGN KEY (`menu_id`)   REFERENCES `menus`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PARAMÈTRES GLOBAUX
-- ================================================================
CREATE TABLE `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT DEFAULT NULL,
  `type`       ENUM('string','boolean','integer','json','text') NOT NULL DEFAULT 'string',
  `group`      VARCHAR(50) NOT NULL DEFAULT 'general',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
