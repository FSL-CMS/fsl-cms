SET NAMES utf8;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `temata` (`nazev`, `id_autora`, `souvisejici`, `uri`, `poradi`) VALUES
('Komentáře k článkům',	1,	'clanky',	'komentare-k-clankum',	2),
('Komentáře k závodům',	1,	'zavody',	'komentare-k-zavodum',	3),
('Komentáře ke sborům',	1,	'sbory',	'komentare-ke-sborum',	5),
('Komentáře ke Krušnohorské lize',	1,	NULL,	'komentare-ke-krusnohorske-lize',	1),
('Komentáře k terčům',	1,	'terce',	'komentare-k-tercum',	6),
('Komentáře k družstvům',	1,	'druzstva',	'komentare-k-druzstvum',	4),
('Komentáře ke stránkám',	1,	NULL,	'komentare-ke-strankam',	7),
('Ostatní komentáře',	1,	NULL,	'',	8);

INSERT INTO `stranky` (`id`, `nazev`, `uri`, `text`, `poradi`) VALUES
(1,	'Kontakty',	'',	'{{kontakty}}',	1),
(2,	'Kronika',	'',	'',	2);