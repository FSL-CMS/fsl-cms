<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../../../document_root/index.php";

/**
 * Test of Fotky model
 */
class FotkyTest extends PHPUnit_Framework_TestCase
{

	/** @var Galerie */
	private $model;

	protected function setUp()
	{
		$this->model = new Fotky;
		dibi::query("TRUNCATE TABLE %n", $this->model->getTable());
		dibi::query("TRUNCATE TABLE %n", 'galerie');
	}

	/**
	 * Test nalezení fotek v galerii
	 */
	public function testFindByGalerie()
	{
		$galerieModel = new Galerie;
		$galerieModel->insert(array('nazev' => 'Fotky z Předonína', 'text' => 'Přinášíme fotky z Předonína.', 'id_autora' => 23, 'datum_pridani' => '2010-10-24 13:20:20', 'datum_zverejneni' => '2010-10-24 13:20:20', 'pocet_zhlednuti' => 10, 'typ' => Galerie::$TYP_INTERNI, 'typ_key' => ''));
		$id = $galerieModel->lastInsertedId();

		$this->model->insert(array('soubor' => 'soubor', 'pripona' => 'txt', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'id_autora' => 1));

		$this->assertEquals(1, count($this->model->findByGalerie($id)));

		$this->model->insert(array('soubor' => 'soubor', 'pripona' => 'txt', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'id_autora' => 1));

		$this->assertEquals(2, count($this->model->findByGalerie($id)));


	}

}
