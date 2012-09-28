ALTER TABLE `zavody`
DROP FOREIGN KEY `zavody_ibfk_7`,
ADD FOREIGN KEY (`id_mista`) REFERENCES `sportoviste` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `body`
DROP FOREIGN KEY `body_ibfk_4`,
ADD FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `clanky`
DROP FOREIGN KEY `clanky_ibfk_4`,
ADD FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie_clanku` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `clanky`
DROP FOREIGN KEY `clanky_ibfk_5`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `diskuze`
DROP FOREIGN KEY `diskuze_ibfk_1`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `diskuze`
DROP FOREIGN KEY `diskuze_ibfk_2`,
ADD FOREIGN KEY (`id_tematu`) REFERENCES `temata` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `druzstva`
DROP FOREIGN KEY `druzstva_ibfk_1`,
ADD FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `druzstva`
DROP FOREIGN KEY `druzstva_ibfk_2`,
ADD FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `galerie`
DROP FOREIGN KEY `galerie_ibfk_1`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `kategorie_souteze`
DROP FOREIGN KEY `kategorie_souteze_ibfk_10`,
ADD FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `kategorie_souteze`
DROP FOREIGN KEY `kategorie_souteze_ibfk_16`,
ADD FOREIGN KEY (`id_souteze`) REFERENCES `souteze` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `kategorie_souteze`
DROP FOREIGN KEY `kategorie_souteze_ibfk_5`,
ADD FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `komentare`
DROP FOREIGN KEY `komentare_ibfk_1`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `komentare`
DROP FOREIGN KEY `komentare_ibfk_2`,
ADD FOREIGN KEY (`id_diskuze`) REFERENCES `diskuze` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `mista`
DROP FOREIGN KEY `mista_ibfk_1`,
ADD FOREIGN KEY (`id_okresu`) REFERENCES `okresy` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `poradatele`
DROP FOREIGN KEY `poradatele_ibfk_1`,
ADD FOREIGN KEY (`id_zavodu`) REFERENCES `zavody` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `poradatele`
DROP FOREIGN KEY `poradatele_ibfk_2`,
ADD FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `pravidla`
DROP FOREIGN KEY `pravidla_ibfk_1`,
ADD FOREIGN KEY (`id_rocniku`) REFERENCES `rocniky` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `sbory`
DROP FOREIGN KEY `sbory_ibfk_1`,
ADD FOREIGN KEY (`id_mista`) REFERENCES `mista` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `sbory`
DROP FOREIGN KEY `sbory_ibfk_2`,
ADD FOREIGN KEY (`id_kontaktni_osoby`) REFERENCES `uzivatele` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `sbory`
DROP FOREIGN KEY `sbory_ibfk_3`,
ADD FOREIGN KEY (`id_spravce`) REFERENCES `uzivatele` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `sledovani`
DROP FOREIGN KEY `sledovani_ibfk_1`,
ADD FOREIGN KEY (`id_uzivatele`) REFERENCES `uzivatele` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `soubory`
DROP FOREIGN KEY `soubory_ibfk_1`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `souteze_rocniku`
DROP FOREIGN KEY `souteze_rocniku_ibfk_1`,
ADD FOREIGN KEY (`id_souteze`) REFERENCES `souteze` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `souteze_rocniku`
DROP FOREIGN KEY `souteze_rocniku_ibfk_2`,
ADD FOREIGN KEY (`id_rocniku`) REFERENCES `rocniky` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `souteze_rocniku`
DROP FOREIGN KEY `souteze_rocniku_ibfk_3`,
ADD FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `souteze_rocniku`
DROP FOREIGN KEY `souteze_rocniku_ibfk_4`,
ADD FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `sportoviste`
DROP FOREIGN KEY `sportoviste_ibfk_1`,
ADD FOREIGN KEY (`id_mista`) REFERENCES `mista` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `startovni_poradi`
DROP FOREIGN KEY `startovni_poradi_ibfk_1`,
ADD FOREIGN KEY (`id_druzstva`) REFERENCES `druzstva` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `startovni_poradi`
DROP FOREIGN KEY `startovni_poradi_ibfk_2`,
ADD FOREIGN KEY (`id_zavodu`) REFERENCES `zavody` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `startovni_poradi`
DROP FOREIGN KEY `startovni_poradi_ibfk_3`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `temata`
DROP FOREIGN KEY `temata_ibfk_1`,
ADD FOREIGN KEY (`id_autora`) REFERENCES `uzivatele` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `terce`
DROP FOREIGN KEY `terce_ibfk_1`,
ADD FOREIGN KEY (`id_sboru`) REFERENCES `sbory` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `terce`
DROP FOREIGN KEY `terce_ibfk_2`,
ADD FOREIGN KEY (`id_typu`) REFERENCES `typy_tercu` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `ucasti`
DROP FOREIGN KEY `ucasti_ibfk_1`,
ADD FOREIGN KEY (`id_zavodu`) REFERENCES `zavody` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `ucasti`
DROP FOREIGN KEY `ucasti_ibfk_2`,
ADD FOREIGN KEY (`id_souteze`) REFERENCES `souteze` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `ucasti`
DROP FOREIGN KEY `ucasti_ibfk_3`,
ADD FOREIGN KEY (`id_kategorie`) REFERENCES `kategorie` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `ucasti`
DROP FOREIGN KEY `ucasti_ibfk_4`,
ADD FOREIGN KEY (`id_bodove_tabulky`) REFERENCES `bodove_tabulky` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `vysledky`
DROP FOREIGN KEY `vysledky_ibfk_1`,
ADD FOREIGN KEY (`id_druzstva`) REFERENCES `druzstva` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `vysledky`
DROP FOREIGN KEY `vysledky_ibfk_3`,
ADD FOREIGN KEY (`id_ucasti`) REFERENCES `ucasti` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `zavody`
DROP FOREIGN KEY `zavody_ibfk_2`,
ADD FOREIGN KEY (`id_poradatele`) REFERENCES `sbory` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `zavody`
DROP FOREIGN KEY `zavody_ibfk_3`,
ADD FOREIGN KEY (`id_tercu`) REFERENCES `terce` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

ALTER TABLE `zavody`
DROP FOREIGN KEY `zavody_ibfk_4`,
ADD FOREIGN KEY (`id_rocniku`) REFERENCES `rocniky` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION;

UPDATE `verze` SET
`verze` = 5;