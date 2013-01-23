<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Bázový model obsahující společné metody
 *
 * @author	Milan Pála
 */
class BaseModel extends Object
{

	protected $connection;

	protected $table;

	protected $lastInsertedId = NULL;

	const RESTRICTION_CONSTRAINT = 1062;

	public function getTable()
	{
		return $this->table;
	}

	protected function insert(array $data)
	{
		return $this->connection->insert($this->table, $data);
	}

	protected function update($id, array $data)
	{
		return $this->connection->update($this->table, $data)->where('id=%i', $id);
	}

	protected function delete($id)
	{
		return $this->connection->delete($this->table)->where('id=%i', $id);
	}

	/**
	 * Vrátí ID posledního vloženého záznamu, nebo nastaví hodnotu.
	 * @param type $id Nová hodnota, nebo NULL pro vrácení hodnoty.
	 * @return type
	 * @throws DibiException
	 */
	public function lastInsertedId($id = NULL)
	{
		if($id === NULL)
		{
			if($this->lastInsertedId === NULL || $this->lastInsertedId == 0 ) throw new DibiException('Nebyl vložen žádný záznam.');
			return $this->lastInsertedId;
		}
		else $this->lastInsertedId = $id;
	}

	public function najdiMaximalniPoradi($sloupec = 'poradi')
	{
		return $this->connection->query('SELECT MAX(%n) FROM %n LIMIT 1', $sloupec, $this->table)->fetchSingle();
	}

}
