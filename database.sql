CREATE TABLE `teams` (
  `id`    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`  VARCHAR(100)     NOT NULL,
  `title` VARCHAR(200)     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `users` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`       VARCHAR(100)     NOT NULL,
  `additionalInfo` LONGTEXT,
  `token`          VARCHAR(32)               DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `token` (`token`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `teammembers` (
  `id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teamId`    INT(10) UNSIGNED NOT NULL,
  `userId`    INT(10) UNSIGNED NOT NULL,
  `startDate` DATE                      DEFAULT NULL,
  `endDate`   DATE                      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teamId` (`teamId`),
  KEY `userId` (`userId`),
  CONSTRAINT `teammembers_ibfk_1` FOREIGN KEY (`teamId`) REFERENCES `teams` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `teammembers_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `entries` (
  `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date`     DATE             NOT NULL,
  `type`     VARCHAR(100)     NOT NULL,
  `memberId` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_memberId` (`date`, `memberId`),
  KEY `memberId` (`memberId`),
  CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`memberId`) REFERENCES `teammembers` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;