<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Model anket
 *
 * @author	Milan Pála
 */
class AktualizaceDB extends BaseModel
{

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function aktualizuj($z, $na)
	{
		$methods = $this->getReflection()->getMethods();
		$found = false;
		do
		{
			$found = false;
			foreach($methods as $method)
			{
				if( preg_match('/from'.$z.'to([0-9]+)/', $method->getName(), $matched) > 0 )
				{
					$z = $matched[1];
					//$method->invoke($this);
					call_user_func(array($this, $method->getName()));
					$found == true;
				}
			}
		}
		while($found && $z < $na);
		if($found == false && $z < $na) throw new DBVersionMismatchException('Nepodařilo se povýšit na požadovanou verzi.');
	}

	private function from1to2()
	{
		$this->connection->query("
			CREATE TABLE `videa` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`nazev` varchar(255) COLLATE utf8_czech_ci NOT NULL,
				`typ` enum('youtube','stream','facebook') COLLATE utf8_czech_ci NOT NULL,
				`identifikator` varchar(255) COLLATE utf8_czech_ci NOT NULL,
				`url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
				`datum_pridani` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`souvisejici` varchar(255) COLLATE utf8_czech_ci NOT NULL,
				`id_souvisejiciho` int(11) unsigned NOT NULL,
				PRIMARY KEY (`id`),
				KEY `id_galerie` (`id_souvisejiciho`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
		");
		$this->connection->query("
			UPDATE `urls` SET
			`presenter` = 'Galerie',
			`action` = 'galerie'
			WHERE `presenter` = 'Fotogalerie';
		");
		$this->connection->query("
			ALTER TABLE `fotogalerie` RENAME TO `galerie`;
		");
		$this->connection->query("
			UPDATE `souvisejici` SET
			`rodic` = 'galerie'
			WHERE `rodic` = 'fotogalerie';
		");
		$this->connection->query("
			UPDATE `souvisejici` SET
			`souvisejici` = 'galerie'
			WHERE `souvisejici` = 'fotogalerie';
		");
		$this->connection->query("
			UPDATE `soubory` SET
			`souvisejici` = 'galerie'
			WHERE `souvisejici` = 'fotogalerie';
		");
		$this->connection->query("
			UPDATE `verze` SET
			`verze` = 2;
		");
	}

}