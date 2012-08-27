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

	/** @var string $SQL_PATH Cesta k inicializačním skriptům. */
	private static $SQL_PATH;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
		self::$SQL_PATH = APP_DIR.'/models/sql';
	}

	/**
	 * Provede prvotní inicializaci databáze. Nahraje do zvolené databáze
	 * tabulky a data nezbytná pro první spuštění aplikace.
	 */
	public function inicializuj()
	{
		// Nahraje tabulky do zvolené databáze.
		$this->connection->loadFile(self::$SQL_PATH.'/tabulky.sql');

		// Nahraje data nezbytná pro spuštění aplikace.
		$this->connection->loadFile(self::$SQL_PATH.'/data.sql');
	}

	/**
	 * Pokusí se aktualizovat databázi na požadovanou verzi.
	 *
	 * Aktuální verze DB je v tabulce "verze" v databázi a je předána jako první
	 * argument. Požadovaná verze databáze je v CommonBasePresenter jako VERZE_DB
	 * a je předána jako druhý argument.
	 *
	 * Převod probíhá hledáním funkcí, které mají název ve tvaru fromXtoY(), kde
	 * X je číslo verze, ze které se aktualizuje a Y je verze, na kterou daná funkce
	 * aktualizuje. Poukouší se hledat aktualizace, dokud se neshodují čísla verzí.
	 *
	 * @param type $z Číslo verze, ze které se aktualizuje.
	 * @param type $na Číslo verze, na kterou se aktualizuje.
	 * @throws DBVersionMismatchException
	 */
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