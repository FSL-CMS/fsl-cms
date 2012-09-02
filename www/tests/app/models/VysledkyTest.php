<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../../../document_root/index.php";

/**
 * Description of VysledkyTest
 *
 * @author Milan
 */
class VysledkyTest extends PHPUnit_Framework_TestCase
{
	private static $model;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		//dibi::loadFile(dirname(__FILE__).'/../../../app/models/sql/tabulky.sql');
		//dibi::loadFile(dirname(__FILE__).'/../../test_data.sql');

		self::$model = new Vysledky;
	}


	protected function setUp()
	{

	}

	/**
	 * Otestuje rekordy ligy.
	 * - Dosavadní rekordy pro první závod -> žádné rekordy.
	 * - Dosavadní rekordy pro druhý závod se stejnými podmínkami, jako první -> budou to výsledky prvního závodu.
	 * - Dosavadní rekordy pro první závod, který má jiné podmínky než předchozí závody -> žádné rekordy.
	 * - Dosavadní rekordy pro druhý závod se stejnými podmínkami, jako měl předchozí test -> budou to výsledky jako měl závod v předchozím testu.
	 * - Dosavadní rekordy pro závod, kde jsou rekordy poskládané z více předchozích závodů.
	 * - Aktuální rekordy se musí shodovat u prvního i posledního závodu.
	 */
	public function testRekordyLigy()
	{
		// Dosavadní rekordy pro první závod -> žádné rekordy.
		$this->assertEquals(array(), (array)self::$model->rekordyLigy(1)->fetchAll());

		// Dosavadní rekordy pro druhý závod se stejnými podmínkami, jako první -> budou to výsledky prvního závodu.
		$this->porovnejRekordyVuciZavodu(1, 2);

		// Dosavadní rekordy pro první závod, který má jiné podmínky než předchozí závody -> žádné rekordy.
		$this->assertEquals(array(), (array)self::$model->rekordyLigy(12)->fetchAll());

		// Dosavadní rekordy pro druhý závod se stejnými podmínkami, jako měl předchozí test -> budou to výsledky jako měl závod v předchozím testu.
		$this->porovnejRekordyVuciZavodu(12, 14);

		// Dosavadní rekordy pro závod, kde jsou rekordy poskládané z více předchozích závodů.
		$ocekavaneRekordy = array(
			'Požární útok na vlastní stroj na 3B' => array(
				'muži' => array( array('id_zavodu' => 13, 'id_druzstva' => 3, 'vysledny_cas' => 23.12) )
			),
			'Požární útok na jednotný stroj na 2B' => array(
				'muži' => array( array('id_zavodu' => 15, 'id_druzstva' => 3, 'vysledny_cas' => 24.78) ),
				'ženy' => array( array('id_zavodu' => 16, 'id_druzstva' => 24, 'vysledny_cas' => 28.53) )
			),
			'Požární útok na vlastní stroj na 2B' => array(
				'ženy' => array( array('id_zavodu' => 7, 'id_druzstva' => 36, 'vysledny_cas' => 26.73) )
			)
		);
		$this->porovnejRekordyZavodu(20, $ocekavaneRekordy);

		// Aktuální rekordy se musí shodovat u prvního i posledního závodu.
		$this->assertEquals(self::$model->rekordyLigy(1, false)->fetchAssoc('soutez,kategorie,id,='), self::$model->rekordyLigy(6, false)->fetchAssoc('soutez,kategorie,id,='));
	}

	/**
	 * Porovná rekordy pro porovnávaný závod, zda odpovídají výsledkům z rekordního závodu.
	 * Rekordní závod je závod, kde musely padnout všechny rekordy => zřejmě první závod v daných podmínkách.
	 *
	 * @param type $id_rekordniho_zavodu ID závodu, kde padly rekordy.
	 * @param type $id_porovnavaneho_zavodu ID závodu, pro který platí rekordy jiného závodu.
	 * @param bool $dosavadni Mají se hledat pouze rekordy do začátku závodu
	 */
	private function porovnejRekordyVuciZavodu($id_rekordniho_zavodu, $id_porovnavaneho_zavodu, $dosavadni = true)
	{
		$vysledky = self::$model->findByZavod($id_rekordniho_zavodu)->fetchAssoc('soutez,kategorie,id,=');
		$rekordy = self::$model->rekordyLigy($id_porovnavaneho_zavodu, $dosavadni)->fetchAssoc('soutez,kategorie,id,=');
		foreach($vysledky as $soutez => $foo)
		{
			foreach($foo as $kategorie => $bar)
			{
				// Existuje rekord
				$this->assertCount(1, $rekordy[$soutez][$kategorie]);

				$rekord = current($rekordy[$soutez][$kategorie]);
				$vysledek = current($vysledky[$soutez][$kategorie]);

				// Sedí rekord?
				$this->assertArrayHasKey('vysledny_cas', $vysledek);
				$this->assertArrayHasKey('vysledny_cas', $rekord);
				$this->assertEquals($vysledek['vysledny_cas'], $rekord['vysledny_cas']);

				// Sedí družstvo, které dosáhlo rekordu?
				$this->assertArrayHasKey('id_druzstva', $vysledek);
				$this->assertArrayHasKey('id_druzstva', $rekord);
				$this->assertEquals($vysledek['id_druzstva'], $rekord['id_druzstva']);

				// Sedí závod, na kterém se dosáhlo rekordu?
				$this->assertArrayHasKey('id_zavodu', $rekord);
				$this->assertEquals($id_rekordniho_zavodu, $rekord['id_zavodu']);
			}
		}
	}

	/**
	 * Porovná rekordy závodu s očekávanými hodnotami.
	 * @param int $id_zavodu ID závodu, pro který se hledají rekordy.
	 * @param array $ocekavaneRekordy Očekávané rekordy, které by měly být.
	 * @param bool $dosavadni Mají se hledat pouze rekordy do začátku závodu.
	 */
	private function porovnejRekordyZavodu($id_zavodu, array $ocekavaneRekordy, $dosavadni = true)
	{
		$rekordy = self::$model->rekordyLigy($id_zavodu, $dosavadni)->fetchAssoc('soutez,kategorie,id,=');
		foreach($rekordy as $soutez => $foo)
		{
			$this->assertArrayHasKey($soutez, $ocekavaneRekordy);
			foreach($foo as $kategorie => $bar)
			{
				// Existuje rekord
				$this->assertCount(1, $rekordy[$soutez][$kategorie]);

				$this->assertArrayHasKey($kategorie, $ocekavaneRekordy[$soutez]);
				$this->assertCount(1, $ocekavaneRekordy[$soutez][$kategorie]);

				$rekord = current($rekordy[$soutez][$kategorie]);
				$vysledek = current($ocekavaneRekordy[$soutez][$kategorie]);

				// Sedí rekord?
				$this->assertArrayHasKey('vysledny_cas', $vysledek);
				$this->assertArrayHasKey('vysledny_cas', $rekord);
				$this->assertEquals($vysledek['vysledny_cas'], $rekord['vysledny_cas']);

				// Sedí družstvo, které dosáhlo rekordu?
				$this->assertArrayHasKey('id_druzstva', $vysledek);
				$this->assertArrayHasKey('id_druzstva', $rekord);
				$this->assertEquals($vysledek['id_druzstva'], $rekord['id_druzstva']);

				// Sedí závod, na kterém se dosáhlo rekordu?
				$this->assertArrayHasKey('id_zavodu', $rekord);
				$this->assertArrayHasKey('id_zavodu', $vysledek);
				$this->assertEquals($vysledek['id_zavodu'], $rekord['id_zavodu']);
			}
		}
	}
}
