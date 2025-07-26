CREATE TABLE `reports` (
	`revertid` int(11) NOT NULL,
	`timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`reporterid` int(11) NOT NULL,
	`reporter` varchar(128) NOT NULL,
	`status` int(11) NOT NULL,
	PRIMARY KEY (`revertid`),
	KEY `reporterid` (`reporterid`),
	KEY `status` (`status`),
	CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`revertid`) REFERENCES `vandalism` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `comments` (
	`commentid` int(11) NOT NULL AUTO_INCREMENT,
	`revertid` int(11) NOT NULL,
	`timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`userid` int(11) NOT NULL,
	`user` varchar(128) NOT NULL,
	`comment` text NOT NULL,
	PRIMARY KEY (`commentid`),
	KEY `revertid` (`revertid`),
	KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `users` (
	`userid` int(11) NOT NULL AUTO_INCREMENT,
	`username` varchar(128) NOT NULL,
	`password` varchar(128) NOT NULL DEFAULT '',
	`email` varchar(128) NOT NULL DEFAULT '',
	`admin` tinyint(1) NOT NULL DEFAULT 0,
	`superadmin` tinyint(1) NOT NULL DEFAULT 0,
	`next_on_review` tinyint(1) DEFAULT 1,
	`keyboard_shortcuts` tinyint(1) DEFAULT 1,
    `hide_anon` tinyint(1) DEFAULT 0,
	PRIMARY KEY (`userid`),
	UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `edits_sent_for_review` (
    `revertid` int(11) NOT NULL,
    `userid` int(11) NOT NULL,
    PRIMARY KEY (`revertid`, `userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
