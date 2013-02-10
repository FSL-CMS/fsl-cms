SET NAMES utf8;

-- Nahrát manuálně
-- CREATE TABLE IF NOT EXISTS `nastaveni` (
--  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--  `verze` int(10) unsigned NOT NULL COMMENT 'Aktuální číslo verze',
--  `liga_nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL COMMENT 'Název ligy',
--  `liga_zkratka` varchar(255) COLLATE utf8_czech_ci NOT NULL COMMENT 'Zkratka názvu ligy',
--   `liga_popis` varchar(255) COLLATE utf8_czech_ci NOT NULL COMMENT 'Popis ligy',
--  PRIMARY KEY (`id`)
-- ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Informace o lize';

-- INSERT INTO `nastaveni` (`id`, `verze`, `liga_nazev`, `liga_zkratka`, `liga_popis`) VALUES
-- (1,	7,	'FireSport League CMS',	'FSL CMS',	'FSL CMS je redakční systém určený pro sportovní hasičské ligy');

-- ALTER TABLE `poll_control_answers` RENAME TO `pollie_answers`, COMMENT='' ENGINE='InnoDB';

-- ALTER TABLE `poll_control_votes` RENAME TO `pollie_votes`, COMMENT='' ENGINE='InnoDB';

-- ALTER TABLE `poll_control_questions` RENAME TO `pollie_questions`, COMMENT='' ENGINE='InnoDB';

ALTER TABLE `pollie_votes`
ADD FOREIGN KEY (`questionID`) REFERENCES `pollie_questions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `clanky`
DROP `uri`,
DROP `stare_uri`,
DROP `stary_link`,
COMMENT='';

ALTER TABLE `diskuze`
DROP `uri`,
COMMENT='';

ALTER TABLE `druzstva`
DROP `uri`,
DROP `stare_id`,
COMMENT='';

ALTER TABLE `galerie`
DROP `uri`,
DROP `stare_id`,
DROP `stare_uri`,
COMMENT='';

ALTER TABLE `okresy`
DROP `stare_id`,
COMMENT='';

ALTER TABLE `sbory`
DROP `uri`,
DROP `stare_id`,
DROP `nic`,
COMMENT='';

ALTER TABLE `soubory`
DROP `uri`,
DROP `stare_uri`,
DROP `stare_id`,
COMMENT='';

ALTER TABLE `stranky`
DROP `uri`,
COMMENT='';

ALTER TABLE `temata`
DROP `uri`,
COMMENT='';

ALTER TABLE `terce`
DROP `uri`,
COMMENT='';

ALTER TABLE `typy_sboru`
DROP `nic`,
COMMENT='';

ALTER TABLE `uzivatele`
DROP `stare_jmeno`,
DROP `stare_id`,
DROP `uri`,
COMMENT='';

ALTER TABLE `zavody`
DROP `uri`,
DROP `stare_id`,
DROP `stare_uri`,
COMMENT='';

ALTER TABLE `pollie_questions`
DROP `stare_id`,
COMMENT='';

DROP TABLE `verze`;

UPDATE `nastaveni` SET `verze` = 8;