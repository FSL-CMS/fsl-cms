<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model Galerií
 *
 * @author	Milan Pála
 */
class Galerie extends Zverejnovane
{

	/** @var string */
	protected $table = 'galerie';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		$dotaz = $this->connection->select('[galerie].*, CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[nazev], " ", [mista].[obec]) AS [autor], COUNT([soubory].[id]) AS [pocet_fotografii], COUNT([videa].[id]) AS [pocet_videi]')
			->from($this->table)
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [galerie].[id_autora]')
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[soubory] ON [soubory].[souvisejici] = "galerie" AND [soubory].[id_souvisejiciho] = [galerie].[id]')
			->leftJoin('[videa] ON [videa].[souvisejici] = "galerie" AND [videa].[id_souvisejiciho] = [galerie].[id]')
			->groupBy('[galerie].[id]');
		if( $this->zverejnene == 1 ) $dotaz->where('[galerie].[datum_zverejneni] IS NOT NULL AND [galerie].[datum_zverejneni] <= NOW()')->orderBy('[datum_zverejneni] DESC');
		else $dotaz->orderBy('[datum_pridani] DESC');

		return $dotaz;
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function findAllZverejnene()
	{
		return $this->findAll()->where('[galerie].[datum_zverejneni] IS NOT NULL');
	}

	public function find($id)
	{
		return $this->findAll()->where('[galerie].[id] = %i', $id);
	}

	public function noveZhlednuti($id)
	{
		$data = array('pocet_zhlednuti%sql' => 'pocet_zhlednuti+1');
		return self::update($id, $data);
	}

	public function delete($id, $force = 0)
	{
		$fotky = new Fotky;
		$videa = new Videa;

		if( $force == 0 )
		{
			if( $fotky->findByGalerie($id)->count() != 0 ) throw new RestrictionException('Galerii nelze odstranit, obsahuje fotografie.');
			if( $videa->findByGalerie($id)->count() != 0 ) throw new RestrictionException('Galerii nelze odstranit, obsahuje videa.');
		}
		$fotky->deleteByGalerie($id);
		return parent::delete($id)->execute();
	}

	public function truncate($id, $force = 0)
	{
		$fotky = new Fotky;
		return $fotky->deleteByGalerie($id);
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Galerie', 'galerie', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		if(isset($data['uri']))
		{
			$urlsModel = new Urls;
			$urlsModel->setUrl('Galerie', 'galerie', $id, $data['uri']);
		}
	}

	public function findIdByUri($uri, $column = 'uri')
	{
		return $this->findAll()
			->where('%n.%n = %s', $this->table, $column, $uri);
	}

	private function constructUri($id, $data)
	{
		if( isset($data['nazev']) )
		{
			$data['uri'] = '/galerie/'.$id.'-'.Texy::webalize( $data['nazev'] ).'/';
		}
		return $data;
	}
}
