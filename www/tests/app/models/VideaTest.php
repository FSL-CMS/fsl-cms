<?php

require_once('PHPUnit/Autoload.php');
require_once dirname(__FILE__) . "/../../../document_root/index.php";

/**
 * Test of Videa model
 */
class VideaTest extends PHPUnit_Framework_TestCase
{

	/** @var Galerie */
	private $model;

	protected function setUp()
	{
		$this->model = new Videa;
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

		$this->model->insert(array('nazev' => 'vide', 'typ' => 'facebook', 'identifikator' => 'aaaaaa', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'url' => 'aaaaaa'));

		$this->assertEquals(1, count($this->model->findByGalerie($id)));

		$this->model->insert(array('nazev' => 'vide', 'typ' => 'facebook', 'identifikator' => 'aaaaaab', 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'url' => 'aaaaaab'));

		$this->assertEquals(2, count($this->model->findByGalerie($id)));


	}

}
