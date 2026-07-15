-- Adminer 4.8.1 MySQL 5.7.39 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) DEFAULT NULL,
  `value` text,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE `settings`;
INSERT INTO `settings` (`id`, `key`, `value`, `description`) VALUES
(2,	'INFO_EMAIL',	'enquiry@thefoxlab.com',	'FROM Email Address for communication mail');

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `social_account`;
CREATE TABLE `social_account` (
  `social_account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `hashtag` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_account_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_account`;
INSERT INTO `social_account` (`social_account_id`, `name`, `status`, `metadata`, `hashtag`, `created_time`, `updated_time`, `deleted_time`) VALUES
(2,	'thefoxlab',	'1',	NULL,	'thefoxlab,webdevelopment,mobileapps,crm,erp',	'2026-07-13 15:04:16',	'2026-07-13 15:04:16',	NULL);

DROP TABLE IF EXISTS `social_connection`;
CREATE TABLE `social_connection` (
  `social_connection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_account_id` int(10) unsigned DEFAULT NULL,
  `parent_connection_id` int(10) unsigned DEFAULT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_expires_at` datetime DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `connected_at` datetime DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_connection_id`),
  UNIQUE KEY `provider_external_id` (`provider`,`external_id`),
  KEY `social_account_id` (`social_account_id`),
  KEY `parent_connection_id` (`parent_connection_id`),
  KEY `provider` (`provider`),
  KEY `status` (`status`),
  CONSTRAINT `fk_connection_parent_connection_id` FOREIGN KEY (`parent_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_social_account_id` FOREIGN KEY (`social_account_id`) REFERENCES `social_account` (`social_account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_connection`;
INSERT INTO `social_connection` (`social_connection_id`, `social_account_id`, `parent_connection_id`, `provider`, `external_id`, `external_name`, `access_token`, `refresh_token`, `token_expires_at`, `permissions`, `status`, `connected_at`, `last_synced_at`, `metadata`, `created_time`, `updated_time`, `deleted_time`) VALUES
(3,	2,	NULL,	'facebook',	'584436661678209',	'TheFoxLab',	'EAAZAJraJ7veUBRZCBYMnpXwVJPZA4oNUY57cTjryWkJkHCQ3LiteYZB72n3GiGGmLFZAiUc0CkkyJsYpIa2cMMXT6LQutVosajKNIw5j5PbeJtmtZBYpJ9uYap8XMfivQhgC9D3ZCSnhrdSb8jtbvzB7Xb7Qgizfn8kPcraqnQyHb2hRKiUfwWsXbpM7bW1qjg20Yw9XNM4oLi58hBupE6R',	NULL,	NULL,	'[\"ADVERTISE\", \"ANALYZE\", \"CREATE_CONTENT\", \"MESSAGING\", \"MODERATE\", \"MANAGE\", \"VIEW_MONETIZATION_INSIGHTS\"]',	'1',	'2026-07-15 05:59:34',	NULL,	'{\"category\":\"Computer company\",\"picture\":\"https:\\/\\/scontent.famd5-3.fna.fbcdn.net\\/v\\/t39.30808-1\\/746494012_1333254532299027_6694894629353335586_n.jpg?stp=cp0_dst-jpg_p50x50_tt6&_nc_cat=107&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=f907e8&_nc_ohc=HKKkgyLV1N8Q7kNvwHvoPHv&_nc_oc=AdrAgEsI0BhWIHUxkyeJDOWt4BRs-CyMTc4tBUqYEMjHjdd04YblRXgdXqaXABnVJYQ9VCdFwG-p4ALkiztgGcDj&_nc_zt=24&_nc_ht=scontent.famd5-3.fna&edm=AGaHXAAEAAAA&_nc_gid=SUz7M-ia0id1Rajfa6cYDQ&_nc_tpa=Q5bMBQEhRkuR3egFwvs477s9t5Dlc8e-58ZkyOVhtE3pnvACtE0rUfcTsZps187tbSS_rvpvFZt7Laz2uQ&oh=00_AQCJLTPtNuA0YG1c4vOlOklVFXnPQIaeF691FuuTx_hDUQ&oe=6A5CF233\"}',	'2026-07-13 15:25:12',	'2026-07-15 05:59:34',	NULL),
(4,	2,	NULL,	'facebook',	'101472188400226',	'JB car rental',	'EAAZAJraJ7veUBR92I19f6hq7fIMWViOq5fo9Vgp5GmZC6xpmzQuvjISvy0yCEozZCR9LVJKx2D2YACcJ9aAqcCajWFN5PMiTMl3bDAF0dZAlnnNzcFlFEJRDOecZCcDHvanCjWcNArXAYYKH0OtBHCtTyBEq7Y5D8isSZBkmlu6tlGv3ZBgkMVfFj5y5JvlDOMN7TDYhmbdSEaZCBRpC1zcZD',	NULL,	NULL,	'[\"MODERATE\", \"MESSAGING\", \"ANALYZE\", \"ADVERTISE\", \"CREATE_CONTENT\", \"MANAGE\"]',	'1',	'2026-07-13 15:51:27',	NULL,	'{\"category\":\"Taxi service\",\"picture\":\"https:\\/\\/scontent.famd5-3.fna.fbcdn.net\\/v\\/t39.30808-1\\/300397190_448565083960202_5184369502830925404_n.jpg?stp=cp0_dst-jpg_s50x50_tt6&_nc_cat=111&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=f907e8&_nc_ohc=MDFiry0pZRYQ7kNvwEYlFiR&_nc_oc=AdridBHiqwVh6eXuPss32TPDwfxERAIenaJGdDCbsjOpo1u-iKbTmFynPy5b1GhjAMDaO0eQbQRyX2YAiWCikmW_&_nc_zt=24&_nc_ht=scontent.famd5-3.fna&edm=AGaHXAAEAAAA&_nc_gid=gWNSLuO0dYUyVqpZEQ6nRw&_nc_tpa=Q5bMBQEdeXerlvSlPFsr6R24bk5u997t0p7wgL2orOy0JX2fKsbaz_mhS2BiHV2BjflvCl5OZx4l9Ftqpg&oh=00_AQBQkCxJkk_H4XLwuGpDF6BMQvAoF1ooEzVL2R4Hi6mjOw&oe=6A5ABF56\"}',	'2026-07-13 15:34:58',	'2026-07-13 15:51:27',	NULL),
(5,	2,	3,	'instagram',	'17841449022852787',	'thefoxlab_in',	'EAAZAJraJ7veUBRZCBYMnpXwVJPZA4oNUY57cTjryWkJkHCQ3LiteYZB72n3GiGGmLFZAiUc0CkkyJsYpIa2cMMXT6LQutVosajKNIw5j5PbeJtmtZBYpJ9uYap8XMfivQhgC9D3ZCSnhrdSb8jtbvzB7Xb7Qgizfn8kPcraqnQyHb2hRKiUfwWsXbpM7bW1qjg20Yw9XNM4oLi58hBupE6R',	NULL,	NULL,	NULL,	'1',	'2026-07-15 06:02:12',	NULL,	'{\"name\":\"TheFoxLab India\",\"profile_picture\":\"https:\\/\\/scontent.famd5-3.fna.fbcdn.net\\/v\\/t51.82787-15\\/745334783_18089539658634096_1325967458819052299_n.jpg?_nc_cat=110&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=7d201b&_nc_ohc=NFumib_UGREQ7kNvwGbXmAV&_nc_oc=AdpIw_CMswsGUz27dtOs8VwPhfrkBfoWKG3tWAru6UDTLK3xnnp6C0qYxAuKakN6OIgiNjMSHf6j_rOw1_zUlGFd&_nc_zt=23&_nc_ht=scontent.famd5-3.fna&edm=AJdBtusEAAAA&_nc_gid=OvO3JMRVDBMdx78hL25FJw&oh=00_AQDnBYxKdLtjFsgOGIb-HtvdWcwOlsQpLVH5H_113SaGYA&oe=6A5CFE14\"}',	'2026-07-13 15:41:37',	'2026-07-15 06:02:12',	NULL);

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
  `parent_external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `permalink` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `sync_time` datetime DEFAULT NULL,
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
  KEY `parent_external_id` (`parent_external_id`),
  KEY `sync_time` (`sync_time`),
  CONSTRAINT `fk_post_social_connection_id` FOREIGN KEY (`social_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_post`;

DROP TABLE IF EXISTS `social_sync`;
CREATE TABLE `social_sync` (
  `social_sync_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `social_connection_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `items_created` int(10) unsigned NOT NULL DEFAULT '0',
  `items_updated` int(10) unsigned NOT NULL DEFAULT '0',
  `items_failed` int(10) unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_time` datetime DEFAULT NULL,
  PRIMARY KEY (`social_sync_id`),
  KEY `social_connection_id` (`social_connection_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_sync_social_connection_id` FOREIGN KEY (`social_connection_id`) REFERENCES `social_connection` (`social_connection_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

TRUNCATE `social_sync`;

-- 2026-07-15 05:36:09
