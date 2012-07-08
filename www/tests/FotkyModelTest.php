<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../document_root/index.php";

/**
 * Test of Rocniky model
 */
class FotkyModelTest extends PHPUnit_Framework_TestCase
{
	/** @var Fotky */
	private $model;

	private static $testovaciData = array(
		array('soubor' => 'fotka01', 'pripona' => 'jpg', 'nazev' => 'Fotografie', 'pocet_stahnuti' => 0, 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:40', 'souvisejici' => 'fotogalerie', 'id_souvisejiciho' => 22),
		array('soubor' => 'fotka02', 'pripona' => 'jpg', 'nazev' => '', 'pocet_stahnuti' => 10, 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:40', 'souvisejici' => 'fotogalerie', 'id_souvisejiciho' => 22),
		array('soubor' => 'fotka03', 'pripona' => 'jpg', 'nazev' => '', 'pocet_stahnuti' => 10, 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:40', 'souvisejici' => 'fotogalerie', 'id_souvisejiciho' => 23)
	);

	private static $vystupniData = array(
		array('id' => 1, 'soubor' => 'fotka01', 'pripona' => 'jpg', 'nazev' => 'Fotografie', 'pocet_stahnuti' => 0, 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:40', 'souvisejici' => 'fotogalerie', 'id_souvisejiciho' => 22, 'id_fotogalerie' => 22, 'autor' => ''),
		array('id' => 2, 'soubor' => 'fotka02', 'pripona' => 'jpg', 'nazev' => '', 'pocet_stahnuti' => 10, 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:40', 'souvisejici' => 'fotogalerie', 'id_souvisejiciho' => 22, 'id_fotogalerie' => 22, 'autor' => ''),
		array('id' => 3, 'soubor' => 'fotka03', 'pripona' => 'jpg', 'nazev' => '', 'pocet_stahnuti' => 10, 'id_autora' => 11, 'datum_pridani' => '2010-10-24 12:20:40', 'souvisejici' => 'fotogalerie', 'id_souvisejiciho' => 23, 'id_fotogalerie' => 23, 'autor' => '')
	);

	protected function setUp()
	{
		$this->model = new Fotky;
		dibi::query("TRUNCATE TABLE %n", $this->model->getTable());
	}

	public function testInsert()
	{
		$this->model->insert(self::$testovaciData[0]);

		$this->assertEquals(1, $this->model->lastInsertedId());

		$this->model->insert(self::$testovaciData[1]);

		$this->model->insert(self::$testovaciData[2]);

		$this->assertEquals(3, (int)dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testFindAll()
	{
		$this->testInsert();

		$rows = $this->model->findAll(22);
		$this->assertEquals(2, (int)count($rows));

		$this->assertEquals(array( 0 => self::$vystupniData[0], 1 => self::$vystupniData[1]), (array)$rows->fetchAssoc('='));

		$rows = $this->model->findAll(20);
		$this->assertEquals(0, (int)count($rows));

		$rows = $this->model->findAll(23);
		$this->assertEquals(1, (int)count($rows));
		$this->assertEquals(array( 0 => self::$vystupniData[2]), (array)$rows->fetchAssoc('='));
	}

	public function testDelete()
	{
		$this->testInsert();
		$id = dibi::fetchSingle("SELECT MAX([id]) FROM %n", $this->model->getTable());
		$this->model->delete($id, true);
		$this->assertEquals(2, (int)dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testDeleteByFotogalerie()
	{
		$this->testInsert();

		$this->model->deleteByFotogalerie(22);
		$this->assertEquals(1, (int)dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testFind()
	{
		$this->testInsert();
		$this->assertEquals(self::$vystupniData[0], (array)$this->model->find(1)->fetch());

		$this->assertEquals(self::$vystupniData[1], (array)$this->model->find(2)->fetch());
	}



	public function testNoveStazeni()
	{
		$this->testInsert();
		
		$row = $this->model->find(2)->fetch();
		$this->assertEquals(self::$vystupniData[1]['pocet_stahnuti'], $row['pocet_stahnuti']);
		
		$this->model->noveStazeni(self::$vystupniData[1]['id']);
		
		$row = $this->model->find(2)->fetch();
		$this->assertEquals(self::$vystupniData[1]['pocet_stahnuti']+1, $row['pocet_stahnuti']);
	}


}
