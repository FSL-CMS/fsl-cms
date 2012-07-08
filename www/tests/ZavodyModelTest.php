<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../document_root/index.php";

/**
 * Test of Rocniky model
 */
class ZavodyModelTest extends PHPUnit_Framework_TestCase
{
	/** @var Fotky */
	private $model;

	private static $testovaciData = array(
		array('id_mista' => 1, 'id_poradatele' => 3, 'id_rocniku' => 15, 'datum' => '2011-09-24 12:20:40', 'id_tercu' => 12, 'text' => 'Dlouhé povídání o závodech.', 'ustream_stav' => 'ne'),
		array('id_mista' => 2, 'id_poradatele' => 2, 'id_rocniku' => 15, 'datum' => '2011-10-24 12:20:40', 'id_tercu' => 11, 'text' => '', 'ustream_stav' => 'ano'),
		array('id_mista' => 3, 'id_poradatele' => 1, 'id_rocniku' => 14, 'datum' => '2011-11-24 12:20:40', 'id_tercu' => 12, 'text' => 'Dlouhé povídání o závodech.', 'ustream_stav' => 'ne'),
	);

	private static $vystupniData = array(
		array('id' => 1, 'id_mista' => 1, 'id_poradatele' => 3, 'id_rocniku' => 15, 'datum' => '2011-09-24 12:20:40', 'id_tercu' => 12, 'text' => 'Dlouhé povídání o závodech.', 'ustream_stav' => 'ne'),
		array('id' => 2, 'id_mista' => 2, 'id_poradatele' => 2, 'id_rocniku' => 15, 'datum' => '2011-10-24 12:20:40', 'id_tercu' => 11, 'text' => '', 'ustream_stav' => 'ano'),
		array('id' => 3, 'id_mista' => 3, 'id_poradatele' => 1, 'id_rocniku' => 14, 'datum' => '2011-11-24 12:20:40', 'id_tercu' => 12, 'text' => 'Dlouhé povídání o závodech.', 'ustream_stav' => 'ne'),
	);

	protected function setUp()
	{
		$this->model = new Zavody;
		dibi::query("TRUNCATE TABLE %n", $this->model->getTable());
	}

	public function testInsert()
	{
		$this->model->insert(self::$testovaciData[0]);

		$this->assertEquals(1, $this->model->lastInsertedId());

		$this->model->insert(self::$testovaciData[1]);

		$this->model->insert(self::$testovaciData[2]);
		
		$this->assertEquals(3, $this->model->lastInsertedId());

		$this->assertEquals(3, (int)dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testFindAll()
	{
		$this->testInsert();

		$rows = $this->model->findAll();
		$this->assertEquals(3, (int)count($rows));

		$this->assertEquals(array( 0 => self::$vystupniData[0], 1 => self::$vystupniData[1]), (array)$rows->fetchAssoc('='));

		$rows = $this->model->findAll(20);
		$this->assertEquals(0, (int)count($rows));

		$rows = $this->model->findAll(23);
		$this->assertEquals(1, (int)count($rows));
		$this->assertEquals(array( 0 => self::$vystupniData[2]), (array)$rows->fetchAssoc('='));
	}

}
