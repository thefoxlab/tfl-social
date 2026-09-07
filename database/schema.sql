-- --------------------------------------------------------
-- TFL-Social Database Schema
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for `social_account`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `social_account` (
  `social_account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT '1',
  `metadata` text DEFAULT NULL,
  `hashtag` text DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_account_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `social_connection`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `social_connection` (
  `social_connection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_account_id` int(10) unsigned DEFAULT NULL,
  `parent_connection_id` int(10) unsigned DEFAULT NULL,
  `provider` varchar(50) NOT NULL,
  `external_id` varchar(191) NOT NULL,
  `external_name` varchar(255) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT '1',
  `connected_at` datetime DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_connection_id`),
  UNIQUE KEY `account_provider_external_id` (`social_account_id`, `provider`, `external_id`),
  KEY `social_account_id` (`social_account_id`),
  KEY `parent_connection_id` (`parent_connection_id`),
  KEY `provider` (`provider`),
  KEY `status` (`status`),
  CONSTRAINT `fk_connection_social_account_id` FOREIGN KEY (`social_account_id`) REFERENCES `social_account` (`social_account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_parent_connection_id` FOREIGN KEY (`parent_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `social_post`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `social_post` (
  `social_post_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_connection_id` int(10) unsigned NOT NULL,
  `provider` varchar(50) NOT NULL,
  `external_id` varchar(191) NOT NULL,
  `parent_external_id` varchar(191) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `permalink` varchar(512) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `sync_time` datetime DEFAULT NULL,
  `metrics` json DEFAULT NULL,
  `raw_json` mediumtext DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT '1',
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_post_id`),
  UNIQUE KEY `social_connection_id_external_id` (`social_connection_id`, `external_id`),
  KEY `social_connection_id` (`social_connection_id`),
  KEY `provider` (`provider`),
  KEY `external_id` (`external_id`),
  KEY `published_at` (`published_at`),
  KEY `status` (`status`),
  KEY `parent_external_id` (`parent_external_id`),
  KEY `sync_time` (`sync_time`),
  CONSTRAINT `fk_post_social_connection_id` FOREIGN KEY (`social_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `social_media`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `social_media` (
  `social_media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_post_id` int(10) unsigned NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `thumbnail_url` varchar(2048) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `metadata` text DEFAULT NULL,
  `media_refresh_at` datetime DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_media_id`),
  KEY `social_post_id` (`social_post_id`),
  KEY `media_refresh_at` (`media_refresh_at`),
  CONSTRAINT `fk_media_social_post_id` FOREIGN KEY (`social_post_id`) REFERENCES `social_post` (`social_post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `social_sync`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `social_sync` (
  `social_sync_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_connection_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `items_created` int(10) unsigned NOT NULL DEFAULT 0,
  `items_updated` int(10) unsigned NOT NULL DEFAULT 0,
  `items_failed` int(10) unsigned NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_sync_id`),
  KEY `social_connection_id` (`social_connection_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_sync_social_connection_id` FOREIGN KEY (`social_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
