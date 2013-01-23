-- Adminer 3.6.3 MySQL dump

SET NAMES utf8;

CREATE TABLE `bodove_tabulky` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `platnost_od` date NOT NULL,
  `platnost_do` date DEFAULT NULL,
  `pocet_bodovanych_pozic` tinyint(4) NOT NULL DEFAULT '10',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `body` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bodove_tabulky` int(11) unsigned NOT NULL,
  `body` tinyint(4) NOT NULL,
  `poradi` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_bodove_tabulky_poradi` (`id_bodove_tabulky`,`poradi`),
  KEY `id_bodove_tabulky` (`id_bodove_tabulky`),
  CONSTRAINT `body_ibfk_5` FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `clanky` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `perex` text COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `id_kategorie` int(11) unsigned DEFAULT NULL,
  `id_autora` int(11) unsigned NOT NULL,
  `datum_pridani` datetime NOT NULL,
  `datum_zverejneni` datetime DEFAULT NULL,
  `posledni_aktualizace` datetime DEFAULT NULL,
  `pocet_cteni` int(11) unsigned NOT NULL DEFAULT '0',
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stare_uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stary_link` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_kategorie` (`id_kategorie`),
  KEY `id_autora` (`id_autora`),
  CONSTRAINT `clanky_ibfk_7` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `clanky_ibfk_6` FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie_clanku` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


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


CREATE TABLE `diskuze` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `id_autora` int(11) unsigned DEFAULT NULL,
  `id_tematu` int(11) unsigned DEFAULT NULL,
  `pocet_precteni` int(11) unsigned NOT NULL DEFAULT '0',
  `zamknuto` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_tematu` (`id_tematu`),
  KEY `id_autora` (`id_autora`),
  CONSTRAINT `diskuze_ibfk_4` FOREIGN KEY (`id_tematu`) REFERENCES `temata` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `diskuze_ibfk_3` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `druzstva` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_sboru` int(11) unsigned NOT NULL,
  `id_kategorie` int(11) unsigned NOT NULL,
  `poddruzstvo` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stare_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_sboru_2` (`id_sboru`,`id_kategorie`,`poddruzstvo`),
  KEY `id_sboru` (`id_sboru`),
  KEY `id_kategorie` (`id_kategorie`),
  CONSTRAINT `druzstva_ibfk_4` FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `druzstva_ibfk_3` FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `funkce_rady` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `poradi` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `galerie` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `id_autora` int(11) unsigned NOT NULL,
  `datum_pridani` datetime NOT NULL,
  `datum_zverejneni` datetime DEFAULT NULL,
  `posledni_aktualizace` datetime DEFAULT NULL,
  `pocet_zhlednuti` int(11) unsigned NOT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stare_id` int(11) unsigned NOT NULL,
  `stare_uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `typ` enum('nativni','rajce') COLLATE utf8_czech_ci NOT NULL DEFAULT 'nativni',
  `typ_key` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_autora` (`id_autora`),
  CONSTRAINT `galerie_ibfk_2` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `kategorie` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `pocet_startovnich_mist` tinyint(4) unsigned NOT NULL DEFAULT '20' COMMENT 'Výchozí počet startovních pozic pro kategorii',
  `poradi` tinyint(4) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kategorie` (`nazev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Sportovní kategorie, které se mohou účastnit závodů';


CREATE TABLE `kategorie_clanku` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `cssstyl` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `poradi` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poradi` (`poradi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `kategorie_souteze` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_souteze` int(11) unsigned NOT NULL,
  `id_kategorie` int(11) unsigned NOT NULL,
  `id_bodove_tabulky` int(11) unsigned DEFAULT NULL COMMENT 'Výchozí bodová tabulka',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_souteze_id_kategorie` (`id_souteze`,`id_kategorie`),
  KEY `id_souteze` (`id_souteze`),
  KEY `id_bodove_tabulky` (`id_bodove_tabulky`),
  KEY `id_kategorie` (`id_kategorie`),
  CONSTRAINT `kategorie_souteze_ibfk_19` FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `kategorie_souteze_ibfk_17` FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `kategorie_souteze_ibfk_18` FOREIGN KEY (`id_souteze`) REFERENCES `souteze` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Vazební tabulka pro dvojici kategorie-soutěže';


CREATE TABLE `komentare` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_autora` int(11) unsigned NOT NULL,
  `id_diskuze` int(11) unsigned NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `datum_pridani` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_autora` (`id_autora`),
  KEY `id_diskuze` (`id_diskuze`),
  CONSTRAINT `komentare_ibfk_4` FOREIGN KEY (`id_diskuze`) REFERENCES `diskuze` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `komentare_ibfk_3` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `mista` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `obec` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `id_okresu` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `obec_id_okresu` (`obec`,`id_okresu`),
  KEY `id_okresu` (`id_okresu`),
  CONSTRAINT `mista_ibfk_2` FOREIGN KEY (`id_okresu`) REFERENCES `okresy` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `okresy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `zkratka` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `stare_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `poll_control_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionId` int(11) NOT NULL,
  `answer` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `votes` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `poll_control_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `datum_pridani` datetime NOT NULL,
  `datum_zverejneni` datetime DEFAULT NULL,
  `id_autora` int(11) NOT NULL,
  `stare_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datum_zverejneni` (`datum_zverejneni`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `poll_control_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionID` int(11) NOT NULL,
  `ip` varchar(15) COLLATE utf8_czech_ci NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `poradatele` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_sboru` int(10) unsigned NOT NULL,
  `id_zavodu` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_sboru_id_zavodu` (`id_sboru`,`id_zavodu`),
  KEY `id_zavodu` (`id_zavodu`),
  CONSTRAINT `poradatele_ibfk_4` FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `poradatele_ibfk_3` FOREIGN KEY (`id_zavodu`) REFERENCES `zavody` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `pravidla` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pravidla` text COLLATE utf8_czech_ci NOT NULL,
  `id_rocniku` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_rocniku` (`id_rocniku`),
  CONSTRAINT `pravidla_ibfk_2` FOREIGN KEY (`id_rocniku`) REFERENCES `rocniky` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `rocniky` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rok` smallint(6) NOT NULL,
  `rocnik` tinyint(3) unsigned NOT NULL,
  `zverejneny` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rok` (`rok`),
  UNIQUE KEY `rocnik` (`rocnik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `sablony_clanku` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `obrazek_umisteni` enum('vlevo','vpravo') COLLATE utf8_czech_ci NOT NULL DEFAULT 'vlevo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Šablony článků';


CREATE TABLE `sbory` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_typu` int(11) unsigned DEFAULT NULL,
  `id_mista` int(11) unsigned NOT NULL,
  `privlastek` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `kontakt` text COLLATE utf8_czech_ci NOT NULL,
  `id_kontaktni_osoby` int(11) unsigned DEFAULT NULL,
  `id_spravce` int(11) unsigned DEFAULT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stare_id` int(11) unsigned NOT NULL,
  `nic` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_typu` (`id_typu`,`privlastek`,`id_mista`),
  KEY `id_mista` (`id_mista`),
  KEY `id_kontaktni_osoby` (`id_kontaktni_osoby`),
  KEY `id_spravce` (`id_spravce`),
  CONSTRAINT `sbory_ibfk_5` FOREIGN KEY (`id_typu`) REFERENCES `typy_sboru` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `sbory_ibfk_6` FOREIGN KEY (`id_mista`) REFERENCES `mista` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `sbory_ibfk_7` FOREIGN KEY (`id_kontaktni_osoby`) REFERENCES `uzivatele` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `sbory_ibfk_8` FOREIGN KEY (`id_spravce`) REFERENCES `uzivatele` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `sledovani` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tabulka` enum('diskuze','temata') COLLATE utf8_czech_ci NOT NULL,
  `id_radku` int(11) unsigned NOT NULL,
  `id_uzivatele` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tabulka` (`tabulka`,`id_radku`,`id_uzivatele`),
  KEY `id_uzivatele` (`id_uzivatele`),
  CONSTRAINT `sledovani_ibfk_2` FOREIGN KEY (`id_uzivatele`) REFERENCES `uzivatele` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `soubory` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `soubor` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `pripona` varchar(11) COLLATE utf8_czech_ci NOT NULL,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `pocet_stahnuti` int(11) unsigned NOT NULL,
  `id_autora` int(11) unsigned NOT NULL,
  `datum_pridani` datetime NOT NULL,
  `souvisejici` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `id_souvisejiciho` int(11) unsigned NOT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stare_uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `stare_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `soubor` (`soubor`,`pripona`),
  KEY `id_autora` (`id_autora`),
  KEY `id_souvisejiciho` (`id_souvisejiciho`),
  CONSTRAINT `soubory_ibfk_2` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `souteze` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `popis` text COLLATE utf8_czech_ci NOT NULL,
  `platnost_od` datetime NOT NULL,
  `platnost_do` datetime DEFAULT NULL,
  `poradi` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poradi` (`poradi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `souteze_rocniku` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_souteze` int(11) unsigned NOT NULL,
  `id_rocniku` int(11) unsigned NOT NULL,
  `id_kategorie` int(11) unsigned NOT NULL,
  `id_bodove_tabulky` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_souteze_id_rocniku_id_kategorie_id_bodove_tabulky` (`id_souteze`,`id_rocniku`,`id_kategorie`,`id_bodove_tabulky`),
  KEY `id_rocniku` (`id_rocniku`),
  KEY `id_kategorie` (`id_kategorie`),
  KEY `id_bodove_tabulky` (`id_bodove_tabulky`),
  CONSTRAINT `souteze_rocniku_ibfk_8` FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `souteze_rocniku_ibfk_5` FOREIGN KEY (`id_souteze`) REFERENCES `souteze` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `souteze_rocniku_ibfk_6` FOREIGN KEY (`id_rocniku`) REFERENCES `rocniky` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `souteze_rocniku_ibfk_7` FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `souvisejici` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rodic` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `id_rodice` int(11) unsigned NOT NULL,
  `souvisejici` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `id_souvisejiciho` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `souvisejici1` (`rodic`,`id_rodice`,`souvisejici`,`id_souvisejiciho`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `sportoviste` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_mista` int(11) unsigned NOT NULL,
  `popis` text COLLATE utf8_czech_ci NOT NULL,
  `sirka` int(11) NOT NULL,
  `delka` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_mista` (`id_mista`),
  CONSTRAINT `sportoviste_ibfk_2` FOREIGN KEY (`id_mista`) REFERENCES `mista` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `startovni_poradi` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_druzstva` int(11) unsigned NOT NULL,
  `id_zavodu` int(11) unsigned NOT NULL,
  `id_autora` int(11) unsigned NOT NULL,
  `datum` datetime NOT NULL,
  `poradi` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_zavodu_2` (`id_zavodu`,`id_druzstva`),
  KEY `id_druzstva` (`id_druzstva`),
  KEY `id_autora` (`id_autora`),
  CONSTRAINT `startovni_poradi_ibfk_6` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `startovni_poradi_ibfk_4` FOREIGN KEY (`id_druzstva`) REFERENCES `druzstva` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `startovni_poradi_ibfk_5` FOREIGN KEY (`id_zavodu`) REFERENCES `zavody` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `stranky` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `uri` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `poradi` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poradi` (`poradi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `temata` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `id_autora` int(11) unsigned NOT NULL,
  `souvisejici` varchar(30) COLLATE utf8_czech_ci DEFAULT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `poradi` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poradi` (`poradi`),
  KEY `id_autora` (`id_autora`),
  CONSTRAINT `temata_ibfk_2` FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `terce` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_sboru` int(11) unsigned NOT NULL,
  `id_typu` int(11) unsigned NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_sboru_id_typu` (`id_sboru`,`id_typu`),
  KEY `id_sboru` (`id_sboru`),
  KEY `id_typu` (`id_typu`),
  CONSTRAINT `terce_ibfk_4` FOREIGN KEY (`id_typu`) REFERENCES `typy_tercu` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `terce_ibfk_3` FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `typy_sboru` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `zkratka` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `nic` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nazev` (`nazev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `typy_tercu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `poradi` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `ucasti` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_zavodu` int(11) unsigned NOT NULL,
  `id_souteze` int(11) unsigned NOT NULL,
  `id_kategorie` int(11) unsigned NOT NULL,
  `id_bodove_tabulky` int(11) unsigned NOT NULL,
  `pocet` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_zavodu_id_souteze_id_kategorie` (`id_zavodu`,`id_souteze`,`id_kategorie`),
  KEY `id_souteze` (`id_souteze`),
  KEY `id_kategorie` (`id_kategorie`),
  KEY `id_bodove_tabulky` (`id_bodove_tabulky`),
  CONSTRAINT `ucasti_ibfk_8` FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `ucasti_ibfk_5` FOREIGN KEY (`id_zavodu`) REFERENCES `zavody` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `ucasti_ibfk_6` FOREIGN KEY (`id_souteze`) REFERENCES `souteze` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `ucasti_ibfk_7` FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `urls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `presenter` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `action` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `param` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `redirect` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  UNIQUE KEY `presenter_action_param_url` (`presenter`,`action`,`param`,`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `uzivatele` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `jmeno` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `prijmeni` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `prezdivka` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `pohlavi` enum('muz','zena') COLLATE utf8_czech_ci NOT NULL DEFAULT 'muz',
  `id_sboru` int(11) unsigned DEFAULT NULL,
  `heslo` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `docasneheslo` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `opravneni` enum('user','author','admin') COLLATE utf8_czech_ci NOT NULL DEFAULT 'user',
  `kontakt` text COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `id_funkce` int(11) unsigned DEFAULT NULL,
  `facebookId` int(11) unsigned DEFAULT NULL,
  `stare_jmeno` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL,
  `stare_id` int(11) unsigned DEFAULT NULL,
  `uri` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `aktivni` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `aktivni` (`aktivni`,`prijmeni`,`jmeno`),
  KEY `id_sboru` (`id_sboru`),
  KEY `id_funkce` (`id_funkce`),
  CONSTRAINT `uzivatele_ibfk_4` FOREIGN KEY (`id_funkce`) REFERENCES `funkce_rady` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `uzivatele_ibfk_5` FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `verze` (
  `verze` int(10) unsigned NOT NULL COMMENT 'Aktuální číslo verze'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Verze databáze';


CREATE TABLE `videa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `typ` enum('youtube','youtubeplaylist','stream','facebook') COLLATE utf8_czech_ci NOT NULL,
  `identifikator` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `datum_pridani` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `souvisejici` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `id_souvisejiciho` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_galerie` (`id_souvisejiciho`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `vysledky` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_druzstva` int(11) unsigned NOT NULL,
  `id_ucasti` int(11) unsigned DEFAULT NULL,
  `lepsi_terc` enum('l','p') COLLATE utf8_czech_ci DEFAULT NULL,
  `lepsi_cas` decimal(6,2) DEFAULT NULL,
  `vysledny_cas` decimal(6,2) NOT NULL,
  `body` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `umisteni` tinyint(3) unsigned DEFAULT NULL,
  `platne_casy` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `platne_body` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_druzstva_id_ucasti` (`id_druzstva`,`id_ucasti`),
  KEY `id_tymu` (`id_druzstva`),
  KEY `id_ucasti` (`id_ucasti`),
  CONSTRAINT `vysledky_ibfk_5` FOREIGN KEY (`id_ucasti`) REFERENCES `ucasti` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `vysledky_ibfk_4` FOREIGN KEY (`id_druzstva`) REFERENCES `druzstva` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `zavody` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_mista` int(11) unsigned NOT NULL,
  `id_poradatele` int(11) unsigned DEFAULT NULL,
  `vystaveni_vysledku` datetime DEFAULT NULL,
  `id_rocniku` int(11) unsigned NOT NULL,
  `datum` datetime NOT NULL,
  `platne_body` tinyint(1) NOT NULL DEFAULT '1',
  `platne_casy` tinyint(1) NOT NULL DEFAULT '1',
  `id_tercu` int(11) unsigned NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `hodnoceni_soucet` int(11) unsigned NOT NULL,
  `hodnoceni_pocet` int(11) unsigned NOT NULL,
  `uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `zruseno` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `stare_id` int(11) unsigned NOT NULL,
  `stare_uri` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `ustream_id` int(11) DEFAULT NULL,
  `ustream_stav` enum('ne','ano','live','zaznam') COLLATE utf8_czech_ci NOT NULL DEFAULT 'ne',
  `spolecne_startovni_poradi` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `vystaveni_vysledku` (`vystaveni_vysledku`),
  KEY `rocnik` (`id_rocniku`),
  KEY `platne_casy` (`platne_casy`),
  KEY `id_tercu` (`id_tercu`),
  KEY `datum` (`datum`),
  KEY `id_poradatele` (`id_poradatele`),
  KEY `id_mista` (`id_mista`),
  CONSTRAINT `zavody_ibfk_10` FOREIGN KEY (`id_rocniku`) REFERENCES `rocniky` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `zavody_ibfk_11` FOREIGN KEY (`id_mista`) REFERENCES `mista` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `zavody_ibfk_12` FOREIGN KEY (`id_poradatele`) REFERENCES `sbory` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `zavody_ibfk_9` FOREIGN KEY (`id_tercu`) REFERENCES `terce` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2013-01-23 15:45:56