-- Adminer 3.1.0 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `article`;
CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `name_webalized` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_id` smallint(6) NOT NULL,
  `date` datetime NOT NULL,
  `text` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `article_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `article` (`id`, `name`, `name_webalized`, `user_id`, `date`, `text`) VALUES
(1,	'Some article',	'some-article',	1,	'2011-11-07 20:25:47',	'<p>Some article text</p>');

DROP TABLE IF EXISTS `menuitem`;
CREATE TABLE `menuitem` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `parent` smallint(6) DEFAULT NULL,
  `type` enum('modulelink','submenu') COLLATE utf8_bin NOT NULL,
  `order` smallint(6) NOT NULL,
  `module_name` varchar(128) COLLATE utf8_bin NULL,
  `module_view` varchar(128) COLLATE utf8_bin NULL,
  `module_view_param` varchar(256) COLLATE utf8_bin NULL,
  `strict_link_comparison` tinyint(1) DEFAULT '1',
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `menuitem` (`id`, `parent`, `type`, `order`, `module_name`, `module_view`, `module_view_param`, `strict_link_comparison`, `name`) VALUES
(1,	NULL,	'modulelink',	2,	'Page',	'default',	'name=page-1',	1,	'Page 1'),
(2,	NULL,	'modulelink',	1,	'Article',	'default',	'',	1,	'Articles'),
(3,	NULL,	'modulelink',	3,	'Page',	'default',	'name=contact',	1,	'Contact page');

DROP TABLE IF EXISTS `page`;
CREATE TABLE `page` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `name_webalized` varchar(256) COLLATE utf8_bin NOT NULL,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `template` varchar(128) COLLATE utf8_bin DEFAULT NULL,
  `text` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `page` (`id`, `name_webalized`, `name`, `template`, `text`) VALUES
(1,	'page-1',	'All Quiet On The Western Front',	'',	'<h3>Chapter one</h3>\r\n<p>Close behind us were our friends: Tjaden, a skinny locksmith of our own age, the biggest eater of the company. He sits down to eat as thin as a grasshopper and gets up as big as a bug in the <strong>family</strong> way; Haie Westhus, of the same age, a peat-digger, who can easily hold a ration-loaf in his hand and say: Guess what I\'ve got in my fist; then Detering, a peasant, who thinks of nothing but<br />his farm-yard and his wife; and finally <em>Stanislaus</em> <em>Katczinsky</em>, the leader of our group, shrewd, cunning, and hard-bitten, forty years of age, with a face of the soil, blue eyes, bent shoulders, and a remarkable nose for dirty weather, good food, and soft jobs.</p>\r\n<h3>Chapter twelve</h3>\r\n<p>And men will not understand us--for the generation that grew up before us, though it has passed these years with us already had a home and a calling; now it will return to its old occupations, and the war will be forgotten--and the generation that has grown up after us will be strange to us and push us aside. We will be superfluous even to ourselves, we will grow older, a few will adapt themselves, some others will merely submit, and most will be bewildered;--the years will pass by and in the end we shall fall into ruin. </p>\r\n<hr />\r\n<p>He fell in <strong>October</strong> 1918, on a day that was so quiet and still on the whole front, that the army report confined itself to the single sentence: All quiet on the Western Front.</p>\r\n<p>He had fallen forward and lay on the <em>earth</em> as though <a href=\"#\">sleeping</a>. Turning him over one saw that he could not have suffered long; his face had an expression of calm, as though almost glad the end had come.</p>'),
(2,	'contact',	'Contact',	'contact',	'<ul>\r\n<li>Clyde M. White</li>\r\n<li>4712 McDowell Street</li>\r\n<li>Nashville, TN 37210</li>\r\n<li>Phone: 931-307-2410</li>\r\n</ul>');

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `email` varchar(254) COLLATE utf8_bin NOT NULL,
  `password` varchar(64) COLLATE utf8_bin NOT NULL,
  `role` enum('user','admin') COLLATE utf8_bin NOT NULL DEFAULT 'user',
  `token` varchar(23) COLLATE utf8_bin DEFAULT NULL,
  `token_created` datetime DEFAULT NULL,
  `name` varchar(128) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `user` (`id`, `email`, `password`, `role`, `token`, `token_created`, `name`) VALUES
(1,	'admin@example.com',	'0ce94d16aee929e03ee138283cc06cb017c591341c6d10e93194e1efde747551',	'admin',	null,	null,	'Admin');

-- 2011-11-21 16:58:04
