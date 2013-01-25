SET NAMES utf8;

ALTER TABLE `zavody`
ADD `prihlasovani_od` datetime NULL COMMENT 'Začátek přihlašování družstev na závody' AFTER `spolecne_startovni_poradi`,
ADD `prihlasovani_do` datetime NULL COMMENT 'Konec přihlašování družstev na závody' AFTER `prihlasovani_od`,
ADD `aktivni_startovni_poradi` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `ustream_stav`;

UPDATE `verze` SET
`verze` = 7;