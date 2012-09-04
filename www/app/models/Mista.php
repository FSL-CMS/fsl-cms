<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model míst
 *
 * @author	Milan Pála
 */
class Mista extends BaseModel
{

	/** @var string */
	protected $table = 'mista';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()->where('[mista].[id] = %i', $id);
	}

	public function findAll()
	{
		return $this->connection
					 ->select('[mista].[id], [mista].[obec], [okresy].[nazev] AS [okres], [mista].[id_okresu]')
					 ->from($this->table)
					 ->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
					 ->orderBy('[obec]');
	}

	public function findAllToSelect()
	{
		return $this->connection
					 ->select('[mista].[id], CONCAT([mista].[obec], " (", [okresy].[zkratka], ")") AS [nazev]')
					 ->from($this->table)
					 ->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
					 ->orderBy('[obec]');
	}

	public function findByOkres($id)
	{
		return $this->findAll()->where('[okresy].[id] = %i', $id);
	}

	public function delete($id, $force = 0)
	{
		if ($force == 0 || $force == 1)
		{
			$sbory = new Sbory;
			if ($sbory->findByMisto($id)->count() > 0)
				throw new RestrictionException('Nelze odstranit místo, existuje v něm sbor.');

			$zavody = new Zavody;
			if ($zavody->findBySportoviste($id)->count() > 0)
				throw new RestrictionException('Nelze odstranit místo, konal/koná se v něm závod.');
		}

		return parent::delete($id)->execute();
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
				throw new AlreadyExistException();
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
				throw new AlreadyExistException();
			else
				throw $e;
		}
	}

}
