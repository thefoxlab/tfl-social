-- Adminer 4.8.1 MySQL 5.7.39 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `social_account`;
CREATE TABLE `social_account` (
  `social_account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_account_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_account`;

DROP TABLE IF EXISTS `social_connection`;
CREATE TABLE `social_connection` (
  `social_connection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_account_id` int(10) unsigned NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_connection_id`),
  UNIQUE KEY `provider_external_id` (`provider`,`external_id`),
  KEY `social_account_id` (`social_account_id`),
  KEY `provider` (`provider`),
  KEY `status` (`status`),
  CONSTRAINT `fk_connection_social_account_id` FOREIGN KEY (`social_account_id`) REFERENCES `social_account` (`social_account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_connection`;

DROP TABLE IF EXISTS `social_media`;
CREATE TABLE `social_media` (
  `social_media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_post_id` int(10) unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_media_id`),
  KEY `social_post_id` (`social_post_id`),
  CONSTRAINT `fk_media_social_post_id` FOREIGN KEY (`social_post_id`) REFERENCES `social_post` (`social_post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_media`;

DROP TABLE IF EXISTS `social_post`;
CREATE TABLE `social_post` (
  `social_post_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_connection_id` int(10) unsigned NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `caption` text COLLATE utf8mb4_unicode_ci,
  `permalink` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `metrics` text COLLATE utf8mb4_unicode_ci,
  `raw_json` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_post_id`),
  UNIQUE KEY `social_connection_id_external_id` (`social_connection_id`,`external_id`),
  KEY `social_connection_id` (`social_connection_id`),
  KEY `provider` (`provider`),
  KEY `external_id` (`external_id`),
  KEY `published_at` (`published_at`),
  KEY `status` (`status`),
  CONSTRAINT `fk_post_social_connection_id` FOREIGN KEY (`social_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_post`;

DROP TABLE IF EXISTS `social_sync`;
CREATE TABLE `social_sync` (
  `social_sync_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_account_id` int(10) unsigned DEFAULT NULL,
  `social_connection_id` int(10) unsigned DEFAULT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `raw_json` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_sync_id`),
  KEY `social_account_id` (`social_account_id`),
  KEY `social_connection_id` (`social_connection_id`),
  KEY `provider` (`provider`),
  KEY `status` (`status`),
  CONSTRAINT `fk_sync_social_account_id` FOREIGN KEY (`social_account_id`) REFERENCES `social_account` (`social_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sync_social_connection_id` FOREIGN KEY (`social_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_sync`;

-- 2026-07-10 11:03:48
