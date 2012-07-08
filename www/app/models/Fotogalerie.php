<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model fotogalerií
 *
 * @author	Milan Pála
 */
class Fotogalerie extends Zverejnovane
{

	/** @var string */
	protected $table = 'fotogalerie';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		$dotaz = $this->connection->select('[fotogalerie].*, CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[nazev], " ", [mista].[obec]) AS [autor], COUNT([soubory].[id]) AS [pocet_fotografii]')
			->from($this->table)
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [fotogalerie].[id_autora]')
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[soubory] ON [soubory].[souvisejici] = "fotogalerie" AND [soubory].[id_souvisejiciho] = [fotogalerie].[id]')
			->groupBy('[fotogalerie].[id]');
		if( $this->zverejnene == 1 ) $dotaz->where('[fotogalerie].[datum_zverejneni] IS NOT NULL AND [fotogalerie].[datum_zverejneni] <= NOW()')->orderBy('[datum_zverejneni] DESC');
		else $dotaz->orderBy('[datum_pridani] DESC');

		return $dotaz;
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function findAllZverejnene()
	{
		return $this->findAll()->where('[fotogalerie].[datum_zverejneni] IS NOT NULL');
	}

	public function find($id)
	{
		return $this->findAll()->where('[fotogalerie].[id] = %i', $id);
	}

	public function noveZhlednuti($id)
	{
		$data = array('pocet_zhlednuti%sql' => 'pocet_zhlednuti+1');
		return self::update($id, $data);
	}

	public function delete($id, $force = 0)
	{
		$fotky = new Fotky;

		if( $force == 0 )
		{
			if( $fotky->findByFotogalerie($id)->count() != 0 ) throw new RestrictionException('Fotogalerii nelze odstranit, obsahuje fotografie.');
		}
		$fotky->deleteByFotogalerie($id);
		return parent::delete($id)->execute();
	}

	public function truncate($id, $force = 0)
	{
		$fotky = new Fotky;
		return $fotky->deleteByFotogalerie($id);
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Fotogalerie', 'fotogalerie', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		if(isset($data['uri']))
		{
			$urlsModel = new Urls;
			$urlsModel->setUrl('Fotogalerie', 'fotogalerie', $id, $data['uri']);
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
			$data['uri'] = '/fotogalerie/'.$id.'-'.Texy::webalize( $data['nazev'] ).'/';
		}
		return $data;
	}
}
