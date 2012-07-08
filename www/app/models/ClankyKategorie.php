<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model kategorií článků
 *
 * @author	Milan Pála
 */
class ClankyKategorie extends BaseModel
{

	/** @var string */
	protected $table = 'kategorie_clanku';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		return $this->connection
			->select('*')
			->from($this->table)
			->orderBy('[poradi]');
	}
	
	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		return $ret;
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}
	
}
