CREATE TABLE IF NOT EXISTS `#__ariadminer_connections` (
  `connection_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('server','sqlite','pgsql','oracle','mssql','firebird','simpledb','mongo','elastic') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'server',
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `db_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `crypt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`connection_id`)
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;