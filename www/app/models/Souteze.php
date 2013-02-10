<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model soutěží
 *
 * @author	Milan Pála
 */
class Souteze extends BaseModel
{

	/** @var string */
	protected $table = 'souteze';

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

	public function findByObdobiZavodu($id)
	{
		return $this->findAll()->where('[platnost_od] <= (SELECT [datum] FROM [zavody] WHERE [zavody].[id] = %i) AND ([platnost_do] >= (SELECT [datum] FROM [zavody] WHERE [zavody].[id] = %i) OR [platnost_do] IS NULL)', $id, $id);
	}

	public function pripravData(&$data)
	{
		if(isset($data['platnost_do']) && $data['platnost_do'] == '0000-00-00 00:00:00' || empty($data['platnost_do']))
		{
			unset($data['platnost_do']);
			$data['platnost_do%sql'] = 'NULL';
		}
	}

	public function insert(array $data)
	{
		try
		{
			$max = $this->connection->query('SELECT MAX(poradi) FROM %n LIMIT 1', $this->table)->fetchSingle();
			$this->pripravData($data);
			$data['poradi%i'] = $max + 1;
			$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
			$this->lastInsertedId($this->connection->insertId());
			return $ret;
		}
		catch (DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException('Typ terčů již existuje.');
			else throw $e;
		}
	}

	public function update($id, array $data)
	{
		try
		{
			$this->pripravData($data);
			return parent::update($id, $data)->execute();
		}
		catch (DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException('Typ terčů již existuje.');
			else throw $e;
		}
	}

	public function delete($id)
	{
		try
		{
			// TODO: doplnit restrikce
			return parent::delete($id)->execute();
		}
		catch (DibiException $e)
		{
			if($e->getCode() == 1451) throw new RestrictionException('Soutěž nelze smazat.');
			else throw $e;
		}
	}

}
