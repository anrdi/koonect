-- ================================================================
-- KOONECT.FR — MariaDB EVENT SCHEDULER
-- Tâches automatiques RGPD, sécurité, nettoyage
-- ================================================================

USE `GlaceVague007`;

-- Activer le planificateur (à ajouter dans my.cnf aussi)

-- ── 1. Purge des sessions expirées (toutes les heures) ───────────
DROP EVENT IF EXISTS `evt_purge_sessions`;
CREATE EVENT `evt_purge_sessions`
  ON SCHEDULE EVERY 1 HOUR
  STARTS CURRENT_TIMESTAMP
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Suppression des sessions expirées'
  DO
    DELETE FROM `sessions`
    WHERE `expires_at` < NOW();

-- ── 2. Purge des tokens expirés (toutes les heures) ──────────────
DROP EVENT IF EXISTS `evt_purge_tokens`;
CREATE EVENT `evt_purge_tokens`
  ON SCHEDULE EVERY 1 HOUR
  STARTS CURRENT_TIMESTAMP
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Suppression des tokens expirés non utilisés'
  DO
    DELETE FROM `tokens`
    WHERE `expires_at` < NOW()
      AND `used_at` IS NULL;

-- ── 3. Nettoyage rate limiting (toutes les 30 minutes) ───────────
DROP EVENT IF EXISTS `evt_purge_rate_limits`;
CREATE EVENT `evt_purge_rate_limits`
  ON SCHEDULE EVERY 30 MINUTE
  STARTS CURRENT_TIMESTAMP
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Nettoyage des entrées rate limiting périmées'
  DO BEGIN
    -- Supprimer les blocages expirés et réinitialiser les compteurs
    DELETE FROM `rate_limits`
    WHERE (`blocked_until` IS NOT NULL AND `blocked_until` < UNIX_TIMESTAMP())
       OR (`blocked_until` IS NULL AND `window_start` < UNIX_TIMESTAMP() - 3600);
  END;

-- ── 4. RGPD — Anonymisation des comptes supprimés (quotidien 02h) ─
DROP EVENT IF EXISTS `evt_gdpr_anonymize_deleted`;
CREATE EVENT `evt_gdpr_anonymize_deleted`
  ON SCHEDULE EVERY 1 DAY
  STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 2 HOUR)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'RGPD : anonymisation des comptes supprimés depuis plus de 30 jours'
  DO BEGIN
    -- Anonymiser les utilisateurs supprimés depuis > 30 jours et pas encore anonymisés
    UPDATE `users`
    SET
      `email`              = CONCAT('anonyme_', SUBSTRING(MD5(CAST(`id` AS CHAR)), 1, 8), '@supprime.local'),
      `username`           = CONCAT('anonyme_', SUBSTRING(MD5(CAST(`id` AS CHAR)), 1, 8)),
      `display_name`       = 'Utilisateur supprimé',
      `password_hash`      = '',
      `avatar`             = NULL,
      `two_factor_secret`  = NULL,
      `two_factor_enabled` = 0,
      `anonymized_at`      = NOW(),
      `updated_at`         = NOW()
    WHERE
      `deleted_at` IS NOT NULL
      AND `deleted_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND `anonymized_at` IS NULL;

    -- Supprimer l'historique de lecture des comptes supprimés
    DELETE rh FROM `reading_history` rh
    INNER JOIN `users` u ON rh.`user_id` = u.`id`
    WHERE u.`anonymized_at` IS NOT NULL;

    -- Supprimer les favoris des comptes supprimés
    DELETE f FROM `favorites` f
    INNER JOIN `users` u ON f.`user_id` = u.`id`
    WHERE u.`anonymized_at` IS NOT NULL;

    -- Supprimer les profils abonnés anonymisés
    DELETE sp FROM `subscriber_profiles` sp
    INNER JOIN `users` u ON sp.`user_id` = u.`id`
    WHERE u.`anonymized_at` IS NOT NULL;
  END;

-- ── 5. RGPD — Suppression newsletter non confirmée (quotidien 03h)
DROP EVENT IF EXISTS `evt_gdpr_purge_newsletter_unconfirmed`;
CREATE EVENT `evt_gdpr_purge_newsletter_unconfirmed`
  ON SCHEDULE EVERY 1 DAY
  STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'RGPD : suppression des inscriptions newsletter non confirmées après 48h'
  DO
    DELETE FROM `newsletter_subscribers`
    WHERE
      `confirmed_at` IS NULL
      AND `created_at` < DATE_SUB(NOW(), INTERVAL 48 HOUR);

-- ── 6. Purge des consentements RGPD anciens (mensuel) ────────────
DROP EVENT IF EXISTS `evt_gdpr_purge_old_consents`;
CREATE EVENT `evt_gdpr_purge_old_consents`
  ON SCHEDULE EVERY 1 MONTH
  STARTS (CURRENT_DATE + INTERVAL 1 MONTH)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'RGPD : conservation des consentements 3 ans maximum'
  DO
    DELETE FROM `gdpr_consents`
    WHERE
      `user_id` IS NULL  -- Consentements anonymes uniquement
      AND `created_at` < DATE_SUB(NOW(), INTERVAL 3 YEAR);

-- ── 7. Purge des logs de sécurité anciens (mensuel 1er du mois) ──
DROP EVENT IF EXISTS `evt_purge_security_logs`;
CREATE EVENT `evt_purge_security_logs`
  ON SCHEDULE EVERY 1 MONTH
  STARTS (LAST_DAY(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 4 HOUR)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Purge des logs de sécurité de plus de 12 mois'
  DO BEGIN
    -- Conserver les logs CRITICAL 24 mois, les autres 12 mois
    DELETE FROM `security_logs`
    WHERE
      (`severity` != 'critical' AND `created_at` < DATE_SUB(NOW(), INTERVAL 12 MONTH))
      OR (`severity` = 'critical' AND `created_at` < DATE_SUB(NOW(), INTERVAL 24 MONTH));
  END;

-- ── 8. Purge des uploads temporaires (quotidien 05h) ─────────────
DROP EVENT IF EXISTS `evt_purge_tmp_uploads`;
CREATE EVENT `evt_purge_tmp_uploads`
  ON SCHEDULE EVERY 1 DAY
  STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 5 HOUR)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Nettoyage des tokens API expirés'
  DO
    DELETE FROM `tokens`
    WHERE
      `type` = 'api'
      AND `expires_at` < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- ── 9. Réinitialisation vues (hebdomadaire — dimanche 01h) ───────
-- Note : conserve un historique mais réinitialise pour le "plus lu de la semaine"
DROP EVENT IF EXISTS `evt_weekly_views_archive`;
CREATE EVENT `evt_weekly_views_archive`
  ON SCHEDULE EVERY 1 WEEK
  STARTS (CURRENT_DATE + INTERVAL (7 - WEEKDAY(CURRENT_DATE)) DAY + INTERVAL 1 HOUR)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Archive hebdomadaire des vues pour le classement "plus lu de la semaine"'
  DO BEGIN
    -- Rien à faire pour l'instant : les vues sont filtrées sur 7 jours dans la requête
    -- Ce slot est prévu pour une future table d'archives de vues
    DO SLEEP(0);
  END;

-- ── 10. Nettoyage commentaires spam anciens (mensuel) ────────────
DROP EVENT IF EXISTS `evt_purge_spam_comments`;
CREATE EVENT `evt_purge_spam_comments`
  ON SCHEDULE EVERY 1 MONTH
  STARTS (CURRENT_DATE + INTERVAL 1 MONTH + INTERVAL 6 HOUR)
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Suppression définitive des commentaires spam de plus de 90 jours'
  DO
    DELETE FROM `comments`
    WHERE
      `status` IN ('rejected', 'spam')
      AND `updated_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ── Vérifier les événements créés ────────────────────────────────
-- SELECT EVENT_NAME, STATUS, LAST_EXECUTED, NEXT_NOT_FOR_SAVE FROM information_schema.EVENTS WHERE EVENT_SCHEMA = 'koonect_db';
