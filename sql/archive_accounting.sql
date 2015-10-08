DROP DATABASE IF EXISTS archive_accounting;
CREATE DATABASE archive_accounting
  CHARACTER SET utf8;
USE archive_accounting;

# Dump of table archive_files
# ------------------------------------------------------------

DROP TABLE IF EXISTS `archive_files`;

CREATE TABLE `archive_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(256) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL,
  `usage_id` int(11) unsigned NOT NULL DEFAULT '0',
  `file_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usage_id` (`usage_id`),
  CONSTRAINT `archive_files_ibfk_1` FOREIGN KEY (`usage_id`) REFERENCES `archive_usage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table archive_usage
# ------------------------------------------------------------

DROP TABLE IF EXISTS `archive_usage`;

CREATE TABLE `archive_usage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `directory_id` int(11) unsigned NOT NULL,
  `directory_size` int(11) NOT NULL DEFAULT '0' COMMENT 'directory size in MB',
  `num_small_files` int(11) NOT NULL DEFAULT '0',
  `usage_time` datetime NOT NULL,
  `cost` varchar(16) NOT NULL DEFAULT '0',
  `pending` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table cfops
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cfops`;

CREATE TABLE `cfops` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `directory_id` int(11) unsigned NOT NULL,
  `cfop` varchar(64) NOT NULL DEFAULT '',
  `activity_code` varchar(16) DEFAULT NULL,
  `active` int(11) NOT NULL,
  `time_created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table directories
# ------------------------------------------------------------

DROP TABLE IF EXISTS `directories`;

CREATE TABLE `directories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `directory` varchar(256) NOT NULL DEFAULT '',
  `time_created` datetime NOT NULL,
  `is_enabled` int(11) NOT NULL DEFAULT '1',
  `do_not_bill` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table settings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `key` varchar(64) NOT NULL DEFAULT '',
  `value` varchar(64) NOT NULL DEFAULT '',
  `modified` datetime NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;

INSERT INTO `settings` (`key`, `value`, `modified`, `name`)
VALUES
	('data_cost','150','2015-08-21 10:48:38','Data Cost ($/TB)'),
	('min_billable_data','51200','2015-08-06 16:43:11','Min Billable Data (MB)'),
	('small_file_size','1048576','2015-08-21 10:49:47','Small File Size (KB)');

/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table transactions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `directory_id` int(11) unsigned NOT NULL,
  `amount` int(11) NOT NULL,
  `usage_id` int(11) unsigned DEFAULT NULL,
  `transaction_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usage_id` (`usage_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`usage_id`) REFERENCES `archive_usage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `is_admin` int(11) NOT NULL DEFAULT '0',
  `is_enabled` int(11) NOT NULL DEFAULT '0',
  `time_created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;