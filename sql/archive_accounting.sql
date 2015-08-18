DROP DATABASE IF EXISTS archive_accounting;
CREATE DATABASE archive_accounting
  CHARACTER SET utf8;
USE archive_accounting;

# Dump of table accounts
# ------------------------------------------------------------

CREATE TABLE `accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `archive_directory` varchar(128) DEFAULT '',
  `is_admin` int(11) NOT NULL DEFAULT '0',
  `is_enabled` int(11) NOT NULL DEFAULT '0',
  `time_created` datetime NOT NULL,
  `cfop` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table archive_files
# ------------------------------------------------------------

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

CREATE TABLE `archive_usage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) unsigned NOT NULL,
  `directory_size` int(11) NOT NULL COMMENT 'directory size in MB',
  `num_small_files` int(11) NOT NULL,
  `usage_time` datetime NOT NULL,
  `cost` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table settings
# ------------------------------------------------------------

CREATE TABLE `settings` (
  `key` varchar(64) NOT NULL DEFAULT '',
  `value` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(128) NOT NULL DEFAULT '',
  `modified` datetime NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;

INSERT INTO `settings` (`key`, `value`, `description`, `modified`)
VALUES
	('data_cost','150','Cost per TB, rounded up','2015-08-06 16:43:02'),
	('min_billable_data','51200','Minimum billable data, in MB','2015-08-06 16:43:11');

/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table transactions
# ------------------------------------------------------------

CREATE TABLE `transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) unsigned NOT NULL,
  `amount` int(11) NOT NULL,
  `usage_id` int(11) unsigned DEFAULT NULL,
  `transaction_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usage_id` (`usage_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`usage_id`) REFERENCES `archive_usage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

