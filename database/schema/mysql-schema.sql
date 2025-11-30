/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `repository_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `branches_repository_id_foreign` (`repository_id`),
  CONSTRAINT `branches_repository_id_foreign` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commits` (
  `sha` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `repository_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`sha`),
  KEY `commits_branch_id_foreign` (`branch_id`),
  KEY `commits_repository_id_foreign` (`repository_id`),
  KEY `commits_user_id_foreign` (`user_id`),
  CONSTRAINT `commits_repository_id_foreign` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `github_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `github_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `github_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'User',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `github_users_github_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incoming_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incoming_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_assignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_assignees` (
  `issue_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`issue_id`,`user_id`),
  KEY `issue_assignees_issue_id_index` (`issue_id`),
  KEY `issue_assignees_github_user_id_index` (`user_id`),
  CONSTRAINT `issue_assignees_issue_id_foreign` FOREIGN KEY (`issue_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `issue_assignees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `github_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_comments` (
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL,
  `issue_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_comments_github_id_unique` (`id`),
  KEY `issue_comments_user_id_foreign` (`user_id`),
  KEY `issue_comments_issue_id_foreign` (`issue_id`),
  CONSTRAINT `issue_comments_issue_id_foreign` FOREIGN KEY (`issue_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `issue_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `github_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `repository_id` bigint(20) unsigned NOT NULL,
  `number` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `state` enum('open','closed','draft','merged') NOT NULL DEFAULT 'open',
  `labels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`labels`)),
  `opened_by_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('issue','pull_request') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `items_repository_id_number_index` (`repository_id`,`number`),
  KEY `items_opened_by_id_foreign` (`opened_by_id`),
  CONSTRAINT `items_opened_by_id_foreign` FOREIGN KEY (`opened_by_id`) REFERENCES `github_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_repository_id_foreign` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations` (
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `avatar_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `organizations_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pull_request_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pull_request_comments` (
  `id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pull_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` longtext DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `in_reply_to_id` bigint(20) unsigned DEFAULT NULL,
  `diff_hunk` longtext DEFAULT NULL,
  `line_start` int(11) DEFAULT NULL,
  `line_end` int(11) DEFAULT NULL,
  `original_line` int(10) unsigned DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `side` varchar(255) DEFAULT NULL,
  `pull_request_review_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pull_request_comments_pull_request_id_foreign` (`pull_request_id`),
  KEY `pull_request_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `pull_request_comments_pull_request_id_foreign` FOREIGN KEY (`pull_request_id`) REFERENCES `pull_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pull_request_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `github_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pull_request_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pull_request_reviews` (
  `id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pull_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` longtext DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `state` enum('approved','changes_requested','commented') NOT NULL DEFAULT 'commented',
  PRIMARY KEY (`id`),
  KEY `pull_request_reviews_pull_request_id_foreign` (`pull_request_id`),
  KEY `pull_request_reviews_user_id_foreign` (`user_id`),
  CONSTRAINT `pull_request_reviews_pull_request_id_foreign` FOREIGN KEY (`pull_request_id`) REFERENCES `pull_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pull_request_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `github_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pull_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pull_requests` (
  `id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `head_branch` varchar(255) DEFAULT NULL,
  `head_sha` varchar(255) DEFAULT NULL,
  `base_branch` varchar(255) DEFAULT NULL,
  `merge_base_sha` varchar(255) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pull_request_github_id_unique` (`id`),
  CONSTRAINT `pull_requests_id_foreign` FOREIGN KEY (`id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repositories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repositories` (
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `organization_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `private` tinyint(1) NOT NULL,
  `last_updated` datetime NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `pr_count` int(11) NOT NULL DEFAULT 0,
  `issue_count` int(11) NOT NULL DEFAULT 0,
  `master_branch` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repositories_github_id_unique` (`id`),
  KEY `repositories_organization_id_index` (`organization_id`),
  CONSTRAINT `repositories_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repository_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repository_users` (
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `repository_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`repository_id`,`user_id`),
  CONSTRAINT `repository_users_repository_id_foreign` FOREIGN KEY (`repository_id`) REFERENCES `repositories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requested_reviewers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requested_reviewers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pull_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `state` enum('pending','approved','changes_requested','commented') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `requested_reviewers_pull_request_id_foreign` (`pull_request_id`),
  KEY `requested_reviewers_user_id_foreign` (`user_id`),
  CONSTRAINT `requested_reviewers_pull_request_id_foreign` FOREIGN KEY (`pull_request_id`) REFERENCES `pull_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requested_reviewers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `github_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_09_06_154428_create_organizations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_09_06_190900_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_09_08_171656_create_repositories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_09_12_173951_update_repositories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_09_12_183911_create_issue_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_09_13_130301_update_issues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_09_13_130919_create_system_info_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_09_13_145504_update_system_info_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_09_14_120710_use_datetime_expires_at',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_09_14_120800_create_console_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_09_14_122207_create_repositories_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_09_14_141718_update_table_name_repositories_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_09_15_182218_create_github_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_09_15_182248_create_timeline_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_09_18_175437_clean_db_up',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_09_18_175538_clean_db_up',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_09_18_194310_clean_db_up',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_09_18_201344_add_github_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_09_18_203825_fix_issues_table_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_09_18_204059_remove_foreign_key_constraints_from_issues',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_09_18_204931_clean_issue_table_up',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_09_20_213047_create_issue_comments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_09_21_095513_finalize_github_id_conversion',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_09_21_095915_convert_issue_comments_to_github_id_pk',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_09_21_114050_create_issue_assignees_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_09_21_114129_migrate_to_github_users_structure',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_09_21_114527_remove_redundant_columns_from_repository_users',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_09_21_164403_create_pull_request_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_09_21_175443_add_resolved_and_line_to_review_comments',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_09_21_200000_add_pr_comments_and_links',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_09_21_182411_drop_pull_request_tables',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_09_24_173309_drop_assignees_column_from_issues_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_09_25_122904_add_indexes_for_issue_queries',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_09_25_123139_create_pull_request_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_09_25_163117_add_labels_to_pull_requests_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_09_25_163144_create_pull_request_assignees_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_09_25_170033_mark_comments_resloved',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_09_25_200446_create_requested_reviewers_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_09_26_175531_create_pull_request_reviews',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_09_26_180511_create_pull_request_commends',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_09_26_200017_add_diff_hunk',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_09_26_205636_add_linked_issues_to_pull_requests_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_09_28_104327_store_inreply_to',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_09_28_130914_mark_comments_resloved',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_09_28_141834_create_pull_request_issues_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_09_28_174614_add_base_branch',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_09_30_171212_log_webhooks',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_09_30_175352_add_side_to_pull_request_comments',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_10_09_130419_create_branches_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_10_09_140859_create_commits_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_10_09_162217_rename_github_id_to_id_across_all_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_10_09_182944_create_viewed_files_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_10_12_102706_store_closed_at',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_10_12_103803_add_merge_base_sha_to_pull_requests_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_10_19_140001_merge_issues_and_pull_requests_into_items',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_10_19_120340_merge_pull_request_assignees_into_issue_assignees',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_10_26_195947_rename_to_item_comments',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_11_01_181235_tie_review_comment_to_review',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_11_01_182001_tie_review_comment_to_review',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_11_09_142343_add_display_name_to_users',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_11_17_173852_add_master_branch_to__repositories',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_11_23_144255_add_draft_as_enum_to_items',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_11_29_115010_remove_console_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_11_29_115153_remove_viewed_files',39);
