<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../document_root/index.php";

/**
 * Test of Rocniky model
 */
class RocnikyModelTest extends PHPUnit_Framework_TestCase
{
	/** @var Rocniky */
	private $model;

	private static $testovaciRocniky = array( array('id' => 1, 'rok' => 2010, 'rocnik' => 14, 'zverejneny' => 1), array('id' => 2, 'rok' => 2011, 'rocnik' => 15, 'zverejneny' => 0) );

	protected function setUp()
	{
		$this->model = new Rocniky;
		dibi::query("TRUNCATE TABLE %n", $this->model->getTable());
	}

	public function testInsert()
	{
		$this->model->insert(self::$testovaciRocniky[0]);

		$this->model->insert(self::$testovaciRocniky[1]);

		$this->assertEquals(2, (int)dibi::fetchSingle("SELECT COUNT([id]) FROM %n", $this->model->getTable()));
	}

	public function testSelect()
	{
		$this->testInsert();
		$rows = $this->model->findAll();
		$this->assertEquals(1, (int)count($rows));

		$this->model->zobrazitNezverejnene();
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

	public function testFindLast()
	{
		$this->testInsert();
		$this->assertEquals(1, (int)$this->model->findLast()->fetchSingle());

		$this->model->zobrazitNezverejnene();
		$this->assertEquals(2, (int)$this->model->findLast()->fetchSingle());
	}

	public function testFindAll()
	{
		$this->testInsert();
		$this->assertEquals(array( 0 => self::$testovaciRocniky[0]), (array)$this->model->findAll()->fetchAssoc('='));

		$this->model->zobrazitNezverejnene();
		$this->assertEquals(self::$testovaciRocniky, (array)$this->model->findAll()->fetchAssoc('='));
	}

	public function testFind()
	{
		$this->testInsert();
		$this->assertEquals(self::$testovaciRocniky[0], (array)$this->model->find(1)->fetch());

		$this->assertEquals(array(), $this->model->find(2)->fetchAll());

		$this->model->zobrazitNezverejnene();
		$this->assertEquals(self::$testovaciRocniky[1], (array)$this->model->find(2)->fetch());
	}

	public function testFindPredchozi()
	{
		$this->testInsert();

		$this->model->zobrazitZverejnene();
		$this->assertEquals(array(), (array)$this->model->findPredchozi(1)->fetchAll());
		$this->assertEquals(array(), (array)$this->model->findPredchozi(2)->fetchAll());

		$this->model->zobrazitNezverejnene();
		$this->assertEquals(array(), (array)$this->model->findPredchozi(1)->fetchAssoc('='));
		$this->assertEquals(self::$testovaciRocniky[0], (array)$this->model->findPredchozi(2)->fetch());
	}

	public function testFindNasledujici()
	{
		$this->testInsert();

		$this->model->zobrazitZverejnene();
		$this->assertEquals(array(), (array)$this->model->findNasledujici(1)->fetchAll());
		$this->assertEquals(array(), (array)$this->model->findNasledujici(2)->fetchAll());

		$this->model->zobrazitNezverejnene();
		$this->assertEquals(array(), (array)$this->model->findNasledujici(2)->fetchAssoc('='));
		$this->assertEquals(self::$testovaciRocniky[1], (array)$this->model->findNasledujici(1)->fetch());
	}

	public function testZverejnit()
	{
		$this->testInsert();
		$rows = $this->model->findAll();
		$this->assertEquals(1, (int)count($rows));

		$this->model->zverejnit(2);
		$rows = $this->model->findAll();
		$this->assertEquals(2, (int)count($rows));
	}


}
