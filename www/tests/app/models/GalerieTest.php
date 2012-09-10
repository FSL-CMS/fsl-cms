<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../../../document_root/index.php";

/**
 * Test of Rocniky model
 */
class GalerieTest extends PHPUnit_Framework_TestCase
{

	/** @var Galerie */
	private $model;
	private static $testovaciData;
	private static $vystupniData;

	protected function setUp()
	{
		self::$testovaciData = array(
		    array('nazev' => 'Fotky z Křešic', 'text' => 'Přinášíme fotky z Křešic.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 12:20:20', 'datum_zverejneni' => NULL, 'posledni_aktualizace' => '2010-10-24 12:20:40', 'pocet_zhlednuti' => 0, 'typ' => Galerie::$TYP_RAJCE, 'typ_key' => 'http://predonin.rajce.idnes.cz/PL_v_Sulejovicich/'),
		    array('nazev' => 'Fotky z Předonína', 'text' => 'Přinášíme fotky z Předonína.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 13:20:20', 'datum_zverejneni' => '2010-10-24 13:20:20', 'pocet_zhlednuti' => 10, 'typ' => Galerie::$TYP_INTERNI, 'typ_key' => '')
		);

		self::$vystupniData = array(
		    1 => array('id' => 1, 'nazev' => 'Fotky z Křešic', 'text' => 'Přinášíme fotky z Křešic.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 12:20:20', 'datum_zverejneni' => NULL, 'posledni_aktualizace' => '2010-10-24 12:20:40', 'pocet_zhlednuti' => 0, 'pocet_fotografii' => 0, 'pocet_videi' => 0, 'typ' => Galerie::$TYP_RAJCE, 'typ_key' => 'http://predonin.rajce.idnes.cz/PL_v_Sulejovicich/', 'autor' => 'Jakub Konfršt, SDH Bechlín'),
		    2 => array('id' => 2, 'nazev' => 'Fotky z Předonína', 'text' => 'Přinášíme fotky z Předonína.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 13:20:20', 'datum_zverejneni' => '2010-10-24 13:20:20', 'posledni_aktualizace' => NULL, 'pocet_zhlednuti' => 10, 'pocet_fotografii' => 0, 'pocet_videi' => 0, 'typ' => Galerie::$TYP_INTERNI, 'typ_key' => '', 'autor' => 'Jakub Konfršt, SDH Bechlín')
		);
		$this->model = new Galerie;
		dibi::query("TRUNCATE TABLE %n", $this->model->getTable());
		dibi::query("TRUNCATE TABLE %n", 'soubory');
		dibi::query("TRUNCATE TABLE %n", 'videa');
	}

	/**
	 * Test vložení galerie
	 * - Vložení zveřejněné galerie
	 * - Vložení zatím nezveřejněné galerie
	 */
	public function testInsert()
	{
		$this->model->insert(self::$testovaciData[0]);

		$this->model->insert(self::$testovaciData[1]);

		$this->assertEquals(2, (int) dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	/**
	 * Test výběru všech galerií
	 * @depends testInsert
	 */
	public function testFindAll()
	{
		$this->assertEquals(0, count($this->model->findAll()));

		$this->model->insert(self::$testovaciData[0]);
		$this->model->insert(self::$testovaciData[1]);

		$rows = $this->model->findAll();
		$this->assertEquals(1, (int) count($rows));

		$this->assertEquals(array(2 => self::$vystupniData[2]), (array) $rows->fetchAssoc('id,='));

		$this->model->zobrazitNezverejnene();

		$rows = $this->model->findAll();
		$this->assertEquals(2, (int) count($rows));

		$this->assertEquals(self::$vystupniData, (array) $rows->fetchAssoc('id,='));
	}

	/**
	 * Otestuje data pro tag select. Musí obsahovat hodnoty ID a NAZEV
	 * @depends testInsert
	 */
	public function testFindAllToSelect()
	{
		$this->assertEquals(0, count($this->model->findAll()));

		$this->model->insert(self::$testovaciData[0]);
		$this->model->insert(self::$testovaciData[1]);

		$rows = $this->model->findAll();
		$this->assertEquals(1, (int) count($rows));

		foreach ($rows->fetchAssoc('#,=') as $row)
		{
			$this->assertArrayHasKey('id', $row);
			$this->assertArrayHasKey('nazev', $row);
		}

		$this->model->zobrazitNezverejnene();

		$rows = $this->model->findAll();
		$this->assertEquals(2, (int) count($rows));

		foreach ($rows->fetchAssoc('#,=') as $row)
		{
			$this->assertArrayHasKey('id', $row);
			$this->assertArrayHasKey('nazev', $row);
		}
	}

	/**
	 * @depends testInsert
	 */
	public function testFind()
	{
		$this->model->insert(self::$testovaciData[0]);
		$this->model->insert(self::$testovaciData[1]);

		$this->assertEquals(self::$vystupniData[2], (array) $this->model->find(2)->fetch());

		$this->assertEquals(false, $this->model->find(1)->fetch());

		$this->model->zobrazitNezverejnene();

		$this->assertEquals(self::$vystupniData[1], (array) $this->model->find(1)->fetch());
	}

	/**
	 * @depends testInsert
	 * @depends testFind
	 */
	public function testNoveZhlednuti()
	{
		/* try
		  {
		  $this->model->noveZhlednuti(5);
		  $this->assertFalse(true);
		  }
		  catch(DibiException $e)
		  {
		  $this->assertTrue(true);
		  } */

		$this->model->insert(self::$testovaciData[1]);

		$row = $this->model->find(1)->fetch();
		$this->assertEquals(self::$vystupniData[2]['pocet_zhlednuti'], $row['pocet_zhlednuti']);

		$this->model->noveZhlednuti(1);

		$row = $this->model->find(1)->fetch();
		$this->assertEquals(self::$vystupniData[2]['pocet_zhlednuti'] + 1, $row['pocet_zhlednuti']);
	}

	/**
	 * @depends testInsert
	 * @depends testFind
	 */
	public function testDelete()
	{
		// Vložit a smazat galerii - OK
		$this->model->insert(self::$testovaciData[1]);
		$id = $this->model->lastInsertedId();
		$this->model->delete($id);
		$this->assertFalse($this->model->find($id)->fetch());


		// Vložit galerii, přidat fotku a smazat - výjimka
		$this->model->insert(self::$testovaciData[1]);
		$id = $this->model->lastInsertedId();

		$fotkyModel = new Fotky;
		$fotkyModel->insert(array('soubor' => 'soubor', 'pripona' => 'txt', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'id_autora' => 1));
		$this->assertNotEmpty($fotkyModel->findByGalerie($id)->fetchAll());
		try
		{
			$this->model->delete($id);
			$this->assertTrue(false);
		}
		catch (RestrictionException $e)
		{
			$this->assertTrue(true);
		}

		// Smazat galerii s fotkou natvrdo - OK
		$this->model->delete($id, true);
		$this->assertFalse($this->model->find($id)->fetch());

		// Vložit galerii, přidat video a smazat - výjimka
		$this->model->insert(self::$testovaciData[1]);
		$id = $this->model->lastInsertedId();

		$videaModel = new Videa;
		$videaModel->insert(array('nazev' => 'vide', 'typ' => 'facebook', 'identifikator' => 'aaaaaa', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'url' => 'aaaaaa'));
		$this->assertNotEmpty($videaModel->findByGalerie($id)->fetchAll());
		try
		{
			$this->model->delete($id);
			$this->assertTrue(false);
		}
		catch (RestrictionException $e)
		{
			$this->assertTrue(true);
		}

		// Smazat galerii s videem natvrdo - OK
		$this->model->delete($id, true);
		$this->assertFalse($this->model->find($id)->fetch());
	}

	/**
	 * @depends testInsert
	 * @depends testFind
	 */
	public function testTruncate()
	{
		$this->model->insert(self::$testovaciData[1]);
		$id = $this->model->lastInsertedId();

		$fotkyModel = new Fotky;
		$fotkyModel->insert(array('soubor' => 'soubor', 'pripona' => 'txt', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'id_autora' => 1));
		$this->assertNotEmpty($fotkyModel->findByGalerie($id)->fetchAll());

		$videaModel = new Videa;
		$videaModel->insert(array('nazev' => 'vide', 'typ' => 'facebook', 'identifikator' => 'aaaaaa', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'url' => 'aaaaaa'));
		$this->assertNotEmpty($videaModel->findByGalerie($id)->fetchAll());

		$this->model->truncate($id);

		$this->assertEmpty($fotkyModel->findByGalerie($id)->fetchAll());
		$this->assertEmpty($videaModel->findByGalerie($id)->fetchAll());
	}

	/**
	 * @depends testInsert
	 * @depends testFind
	 */
	public function testUpdate()
	{
		$this->model->insert(self::$testovaciData[1]);
		$id = $this->model->lastInsertedId();

		$this->model->update($id, array('nazev' => 'Galerie ze závodů', 'text' => 'Všechny fotky ze závodů'));
		$row = $this->model->find($id)->fetch();

		$this->assertNotEmpty($row);

		$this->assertEquals('Galerie ze závodů', $row['nazev']);
		$this->assertEquals('Všechny fotky ze závodů', $row['text']);
	}

}
