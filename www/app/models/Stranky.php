<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model stránek
 *
 * @author	Milan Pála
 */
class Stranky extends BaseModel
{

	/** @var string */
	protected $table = 'stranky';

	public function findIdByUri($uri)
	{
		return $this->connection
			->select('[id]')
			->from($this->table)
			->where('[uri] = %s', $uri);
	}


	public function find($id)
	{
		return $this->findAll()->where('[stranky].[id] = %u', $id);
	}

	public function findAll()
	{
  		return $this->connection->select('*')
		     ->from($this->table)
		     ->orderBy('[poradi]');
	}

	public function findAlltoSelect()
	{
		return $this->findAll()->select('CONCAT(CONCAT_WS(" ", [typy_sboru].[nazev], [sbory].[privlastek], [mista].[obec]), ", okres ", [mista].[okres]) AS [sbor]')->orderBy('[sbor]');
	}

	public function findAlltoMenu()
	{
		return $this->findAll()
			->select('CONCAT_WS(":", "Stranky", "stranka") AS [odkaz]');
	}

	public function findTypytoSelect()
	{
		return $this->connection
			->select('[id], [nazev]')
			->from('[typy_sboru]');
	}

	public function findMistatoSelect()
	{
		return $this->connection
			->select('[id], CONCAT([obec], " (", [okres], ")") AS [misto]')
			->from('[mista]')
			->orderBy('[obec]');
	}

	public function constructUri($id, $data)
	{
		if( isset($data['nazev']) )
		{
			$data['uri'] = '/'.Texy::webalize($data['nazev']);
		}
		return $data;
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = Nette\Environment::getService('urls');
		$urlsModel->setUrl('Stranky', 'stranka', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		$urlsModel = Nette\Environment::getService('urls');
		$urlsModel->setUrl('Stranky', 'stranka', $id, $data['uri']);
	}

	public function udrzba()
	{
		$vsechny = $this->findAll();
		foreach( $vsechny as $data )
		{
			$dataDoDB = array( 'nazev' => $data['nazev'] );
			$this->update($data['id'], $dataDoDB);
		}
	}

}
