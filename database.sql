CREATE TABLE `teams` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL DEFAULT '',
	`title` varchar(200) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`username` varchar(100) NOT NULL DEFAULT '',
	`additionalInfo` longtext,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `teammembers` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`teamId` int(10) unsigned NOT NULL,
	`userId` int(10) unsigned NOT NULL,
	`startDate` date DEFAULT NULL,
	`endDate` date DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `teamId` (`teamId`),
	KEY `userId` (`userId`),
	CONSTRAINT `teammembers_ibfk_1` FOREIGN KEY (`teamId`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT `teammembers_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `entries` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`date` date NOT NULL,
	`type` varchar(100) NOT NULL DEFAULT '',
	`memberId` int(11) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `date_userId` (`date`,`memberId`),
	KEY `memberId` (`memberId`),
	CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`memberId`) REFERENCES `teammembers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;