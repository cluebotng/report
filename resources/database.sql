CREATE TABLE `reports` (
	`revertid` INT PRIMARY KEY NOT NULL,
	`timestamp` TIMESTAMP NOT NULL,
	`reporterid` INT NOT NULL,
	`reporter` VARCHAR(128) NOT NULL,
	`status` INT NOT NULL,
	
	INDEX USING BTREE (`reporterid`),
	INDEX USING BTREE (`status`)
) ENGINE=InnoDB;

CREATE TABLE `comments` (
	`commentid` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	`revertid` INT NOT NULL,
	`timestamp` TIMESTAMP NOT NULL,
	`userid` INT NOT NULL,
	`user` VARCHAR(128) NOT NULL,
	`comment` TEXT NOT NULL,
	
	INDEX USING BTREE (`revertid`),
	INDEX USING BTREE (`userid`)
) ENGINE=InnoDB;

CREATE TABLE `users` (
	`userid` int(11) NOT NULL AUTO_INCREMENT,
	`username` varchar(128) NOT NULL,
	`password` varchar(128) NOT NULL DEFAULT '',
	`email` varchar(128) NOT NULL DEFAULT '',
	`admin` tinyint(1) NOT NULL DEFAULT 0,
	`superadmin` tinyint(1) NOT NULL DEFAULT 0,
	`next_on_review` tinyint(1) DEFAULT 0,
	`keyboard_shortcuts` tinyint(1) DEFAULT 0,
	PRIMARY KEY (`userid`),
	UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
