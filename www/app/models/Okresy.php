<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model okresů
 *
 * @author	Milan Pála
 */
class Okresy extends BaseModel
{

	/** @var string */
	protected $table = 'okresy';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()->where('[okresy].[id] = %i', $id);
	}
	
	public function findAll()
	{
		return $this->connection
			->select('[okresy].[id], [okresy].[nazev], [okresy].[zkratka]')
		     ->from($this->table)
		     ->orderBy('[nazev]');
	}	
	
	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function delete($id, $force = 0)
	{
		if( $force == 0 || $force == 1)
		{
			$mista = new Mista;
			if( $mista->findByOkres($id)->count() > 0 ) throw new RestrictionException('Nelze odstranit okres, exitstují místa v tomto okresu.');
		}

		return parent::delete($id)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		//$this->constructUri($id, $data);
		$this->update($id, $data);
		return $ret;
	}

	public function update($id, array $data)
	{
		//$data = $this->constructUri($id, $data);
		return parent::update($id, $data)->execute();
	}
	
}
