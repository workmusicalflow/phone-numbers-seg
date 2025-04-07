-- Migration pour créer la table des modèles de SMS

CREATE TABLE IF NOT EXISTS `sms_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `content` TEXT NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sms_templates_user_id` (`user_id`),
  CONSTRAINT `fk_sms_templates_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter quelques modèles de SMS par défaut pour les tests
INSERT INTO `sms_templates` (`user_id`, `title`, `content`, `description`) VALUES
(1, 'Confirmation de commande', 'Bonjour {nom}, votre commande de {quantite} crédits SMS a été confirmée. Merci pour votre confiance.', 'Modèle pour confirmer une commande de crédits SMS'),
(1, 'Rappel de rendez-vous', 'Rappel: Vous avez un rendez-vous le {date} à {heure}. Pour annuler, veuillez nous contacter au {telephone}.', 'Modèle pour rappeler un rendez-vous'),
(1, 'Notification de promotion', 'Offre spéciale: {promotion}. Valable jusqu\'au {date_fin}. Profitez-en dès maintenant!', 'Modèle pour annoncer une promotion');
