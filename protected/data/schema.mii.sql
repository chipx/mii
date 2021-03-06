CREATE TABLE `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author` int(11) NOT NULL,
  `title` varchar(512) NOT NULL,
  `body` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `state` enum('public','protected','private','hide') NOT NULL,
  `visible` enum('yes','no') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cu` (`created`,`updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
