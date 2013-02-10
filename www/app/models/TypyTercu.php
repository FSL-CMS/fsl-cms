<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model typů terčů
 *
 * @author	Milan Pála
 */
class TypyTercu extends BaseModel
{

	/** @var string */
	protected $table = 'typy_tercu';

	public function findAllToSelect()
	{
		return $this->connection
					 ->select('[id], [nazev]')
					 ->from($this->table);
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
					 ->orderBy('[poradi]');
	}

	public function delete($id, $force = 0)
	{
		if ($force == 0 || $force == 1)
		{
			$terce = Nette\Environment::getService('terce');
			if ($terce->findByTyp($id)->count() != 0)
				throw new RestrictionException('Typ terčů nelze odstranit, je použit u používaných terčů.');
		}

		return parent::delete($id)->execute();
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
				throw new AlreadyExistException('Typ terčů již existuje.');
			else
				throw $e;
		}
	}

	public function insert(array $data)
	{
		try
		{
			$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
			$this->lastInsertedId($this->connection->insertId());
			return $ret;
		}
		catch (DibiException $e)
		{
			if ($e->getCode() == 1062)
				throw new AlreadyExistException('Typ terčů již existuje.');
			else
				throw $e;
		}
	}

}
