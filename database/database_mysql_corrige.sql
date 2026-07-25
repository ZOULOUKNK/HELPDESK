SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'client',
  `department` VARCHAR(255) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `phone` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` INT NOT NULL,
  `reserved_at` INT DEFAULT NULL,
  `available_at` INT NOT NULL,
  `created_at` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` LONGTEXT DEFAULT NULL,
  `cancelled_at` INT DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` LONGTEXT NOT NULL,
  `queue` LONGTEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `color` VARCHAR(50) NOT NULL DEFAULT '#6B7280',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `reference` VARCHAR(255) NOT NULL,
  `user_id` INT NOT NULL,
  `assigned_to` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `priority` VARCHAR(50) NOT NULL DEFAULT 'medium',
  `category` VARCHAR(50) NOT NULL DEFAULT 'other',
  `department` VARCHAR(255) DEFAULT NULL,
  `first_response_at` DATETIME DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `due_at` DATETIME DEFAULT NULL,
  `satisfaction_rating` INT DEFAULT NULL,
  `satisfaction_comment` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_tickets_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tickets_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `message` TEXT NOT NULL,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_comments_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ticket_replies` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `body` TEXT NOT NULL,
  `is_internal` TINYINT(1) NOT NULL DEFAULT 0,
  `is_solution` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_ticket_replies_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_replies_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `ticket_reply_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(255) NOT NULL,
  `size` INT NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_attachments_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachments_ticket_reply_id` FOREIGN KEY (`ticket_reply_id`) REFERENCES `ticket_replies`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_attachments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ticket_histories` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `old_value` VARCHAR(255) DEFAULT NULL,
  `new_value` VARCHAR(255) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_ticket_histories_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_histories_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ticket_tag` (
  `ticket_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`ticket_id`, `tag_id`),
  CONSTRAINT `fk_ticket_tag_ticket_id` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_tag_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2024_01_01_000003_create_ticket_support_tables',1),
(5,'2026_03_28_194837_create_tickets_table',1),
(6,'2026_03_28_194838_create_comments_table',1),
(7,'2024_01_02_000001_add_fields_to_users_table',2);

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `avatar`, `last_login_at`, `remember_token`, `created_at`, `updated_at`, `is_active`, `phone`, `bio`) VALUES
(2,'Marie Lecomte','admin@helpdesk.fr','$2y$12$6kE4RDbPuoGUIWOgsgrVeuEmUUOBBzUICBawy5GAOwIIUeFX8aG5.','admin','IT',NULL,'2026-04-03 21:24:20',NULL,'2026-04-03 20:43:27','2026-04-03 21:24:20',1,NULL,NULL),
(3,'Thomas Bernard','thomas@helpdesk.fr','$2y$12$vtnoD0FLVytZgHmDINiQf.ol3TK2Dk0CQqBxS62bIjbiGIJlwYee.','agent','Infrastructure',NULL,NULL,NULL,'2026-04-03 20:43:27','2026-04-03 20:43:27',1,NULL,NULL),
(4,'Sophie Roux','sophie@helpdesk.fr','$2y$12$wWqHBcjS6Tq44R9rHXRRa.rgv/QhZWMEYilCukWCoWzpsNgL2xJiG','agent','Logiciels',NULL,NULL,NULL,'2026-04-03 20:43:27','2026-04-03 20:43:27',1,NULL,NULL),
(5,'Lucas Martin','lucas@helpdesk.fr','$2y$12$Fm3CvcSTVCVnd82ceD51Vuyh7El0cefEejdfrGf/h6e6PKb2MYmAG','agent','Réseau',NULL,NULL,NULL,'2026-04-03 20:43:27','2026-04-03 20:43:27',1,NULL,NULL);

INSERT INTO `tags` (`id`, `name`, `color`, `created_at`, `updated_at`) VALUES
(1,'Urgent','#EF4444','2026-04-03 20:43:26','2026-04-03 20:43:26'),
(2,'VIP','#8B5CF6','2026-04-03 20:43:26','2026-04-03 20:43:26'),
(3,'Récurrent','#F59E0B','2026-04-03 20:43:26','2026-04-03 20:43:26'),
(4,'Infrastructure','#3B82F6','2026-04-03 20:43:26','2026-04-03 20:43:26'),
(5,'Sécurité','#10B981','2026-04-03 20:43:26','2026-04-03 20:43:26'),
(6,'Formation','#EC4899','2026-04-03 20:43:26','2026-04-03 20:43:26');

CREATE UNIQUE INDEX `users_email_unique` ON `users` (`email`);
CREATE INDEX `cache_expiration_index` ON `cache` (`expiration`);
CREATE INDEX `cache_locks_expiration_index` ON `cache_locks` (`expiration`);
CREATE INDEX `jobs_queue_index` ON `jobs` (`queue`);
CREATE UNIQUE INDEX `failed_jobs_uuid_unique` ON `failed_jobs` (`uuid`);
CREATE INDEX `ticket_replies_ticket_id_created_at_index` ON `ticket_replies` (`ticket_id`, `created_at`);
CREATE INDEX `ticket_histories_ticket_id_created_at_index` ON `ticket_histories` (`ticket_id`, `created_at`);
CREATE UNIQUE INDEX `tags_name_unique` ON `tags` (`name`);
CREATE INDEX `tickets_status_priority_index` ON `tickets` (`status`, `priority`);
CREATE INDEX `tickets_user_id_status_index` ON `tickets` (`user_id`, `status`);
CREATE INDEX `tickets_assigned_to_status_index` ON `tickets` (`assigned_to`, `status`);
CREATE UNIQUE INDEX `tickets_reference_unique` ON `tickets` (`reference`);

ALTER TABLE `migrations` AUTO_INCREMENT=8;
ALTER TABLE `users` AUTO_INCREMENT=11;
ALTER TABLE `tickets` AUTO_INCREMENT=8;
ALTER TABLE `ticket_histories` AUTO_INCREMENT=14;
ALTER TABLE `tags` AUTO_INCREMENT=7;
ALTER TABLE `ticket_replies` AUTO_INCREMENT=7;

SET FOREIGN_KEY_CHECKS=1;
