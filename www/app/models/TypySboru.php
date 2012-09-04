<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model typů sborů
 *
 * @author	Milan Pála
 */
class TypySboru extends BaseModel
{

	/** @var string */
	protected $table = 'typy_sboru';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}

	public function findAll()
	{
		return $this->connection
					 ->select('*')
					 ->from($this->table)
					 ->orderBy('[nazev]');
	}

	public function delete($id, $force = 0)
	{
		if ($force == 0)
		{
			$sbory = new Sbory;
			if ($sbory->findByTyp($id)->count() > 0)
				throw new RestrictionException('Nelze odstranit typ sboru, existují sbory toho typu.');
		}

		parent::delete($id)->execute();
	}

	public function insert(array $data)
	{
		try
		{
			$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
			$id = $this->connection->insertId();
			$this->lastInsertedId($id);
			$this->update($id, $data);
			return $ret;
		}
		catch (DibiException $e)
		{
			if ($e->getCode() == 1062)
				throw new AlreadyExistException('Typ sborů již existuje.');
			else
				throw $e;
		}
	}

	public function update($id, array $data)
	{
		try
		{
			return parent::update($id, $data)->execute();
		}
		catch (DibiException $e)
		{
			if ($e->getCode() == 1062)
				throw new AlreadyExistException('Typ sborů již existuje.');
			else
				throw $e;
		}
	}

}
