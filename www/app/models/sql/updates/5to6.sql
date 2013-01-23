SET NAMES utf8;

CREATE TABLE `sablony_clanku` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `obrazek_umisteni` enum('vlevo','vpravo') COLLATE utf8_czech_ci NOT NULL DEFAULT 'vlevo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Šablony článků';

CREATE TABLE `clanky_sablony` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_clanku` int(10) unsigned NOT NULL,
  `id_sablony` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_clanku` (`id_clanku`),
  KEY `id_sablony` (`id_sablony`),
  CONSTRAINT `clanky_sablony_ibfk_2` FOREIGN KEY (`id_sablony`) REFERENCES `sablony_clanku` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `clanky_sablony_ibfk_1` FOREIGN KEY (`id_clanku`) REFERENCES `clanky` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Vazební tabulka článků a šablon';

UPDATE `verze` SET
`verze` = 6;