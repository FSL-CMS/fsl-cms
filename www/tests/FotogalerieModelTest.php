<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../document_root/index.php";

/**
 * Test of Rocniky model
 */
class FotogalerieModelTest extends PHPUnit_Framework_TestCase
{
	/** @var Fotogalerie */
	private $model;

	private static $testovaciData = array(
		array('id' => 1, 'nazev' => 'Fotky z Křešic', 'text' => 'Přinášíme fotky z Křešic.', 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:20', 'datum_zverejneni' => NULL, 'posledni_aktualizace' => '2010-10-24 12:20:40', 'pocet_zhlednuti' => 0),
		array('id' => 2, 'nazev' => 'Fotky z Předonína', 'text' => 'Přinášíme fotky z Předonína.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 13:20:20', 'datum_zverejneni' => '2010-10-24 13:20:20', 'posledni_aktualizace' => NULL, 'pocet_zhlednuti' => 10)
	);

	private static $vystupniData = array(
	    array('id' => 1, 'nazev' => 'Fotky z Křešic', 'text' => 'Přinášíme fotky z Křešic.', 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:20', 'datum_zverejneni' => NULL, 'posledni_aktualizace' => '2010-10-24 12:20:40', 'pocet_zhlednuti' => 0, 'pocet_fotografii' => 0, 'autor' => ''),
	    array('id' => 2, 'nazev' => 'Fotky z Předonína', 'text' => 'Přinášíme fotky z Předonína.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 13:20:20', 'datum_zverejneni' => '2010-10-24 13:20:20', 'posledni_aktualizace' => NULL, 'pocet_zhlednuti' => 10, 'pocet_fotografii' => 0, 'autor' => '')
	);

	protected function setUp()
	{
		$this->model = new Fotogalerie;
		dibi::query("TRUNCATE TABLE %n", $this->model->getTable());
	}

	public function testInsert()
	{
		$this->model->insert(self::$testovaciData[0]);

		$this->model->insert(self::$testovaciData[1]);

		$this->assertEquals(2, (int)dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testFindAll()
	{
		$this->testInsert();
		$rows = $this->model->findAll();
		$this->assertEquals(2, (int)count($rows));

		$this->assertEquals(array( 0 => self::$vystupniData[1]), (array)$this->model->findAllZverejnene()->fetchAssoc('='));
	}
	
	public function testFindAllZverejnene()
	{
		$this->testInsert();

		$rows = $this->model->findAllZverejnene();
		$this->assertEquals(1, (int)count($rows));
		
		$this->assertEquals(array(0 => self::$vystupniData[1]), (array)$rows->fetchAssoc('='));
	}	

	public function testFindAllToSelect()
	{
		$this->testInsert();
		$rows = $this->model->findAll();
		$this->assertEquals(2, (int)count($rows));
	}

	public function testDelete()
	{
		$this->testInsert();
		$id = dibi::fetchSingle("SELECT MAX([id]) FROM %n", $this->model->getTable());
		$this->model->delete($id, true);
		$this->assertEquals(1, dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testFind()
	{
		$this->testInsert();
		$this->assertEquals(self::$vystupniData[0], (array)$this->model->find(1)->fetch());

		$this->assertEquals(self::$vystupniData[1], (array)$this->model->find(2)->fetch());
	}



	public function testNoveZhlednuti()
	{
		$this->testInsert();
		
		$row = $this->model->find(2)->fetch();
		$this->assertEquals(self::$vystupniData[1]['pocet_zhlednuti'], $row['pocet_zhlednuti']);
		
		$this->model->noveZhlednuti(self::$vystupniData[1]['id']);
		
		$row = $this->model->find(2)->fetch();
		$this->assertEquals(self::$vystupniData[1]['pocet_zhlednuti']+1, $row['pocet_zhlednuti']);
	}


}
