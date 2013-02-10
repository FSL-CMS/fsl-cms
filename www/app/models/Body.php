<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model bodů
 *
 * @author	Milan Pála
 */
class Body extends BaseModel
{
	/** @var string */
	protected $table = 'body';

	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}

	public function findByTabulka($id)
	{
		return $this->findAll()->where('[id_bodove_tabulky] = %i', $id);
	}

	public function findAll()
	{
		return $this->connection
			->select('[id], [poradi], [body]')
		     ->from($this->table)
		     ->orderBy('[poradi]');
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function delete($id, $force = 0)
	{
		return parent::delete($id)->execute();
	}

	public function findByZavod($id)
	{
		return $this->connection
			->select('[body].[body], [ucasti].[id_souteze], [ucasti].[id_kategorie], [body].[poradi]')
			->from($this->table)
			->leftJoin('[bodove_tabulky] ON [body].[id_bodove_tabulky] = [bodove_tabulky].[id]')
			->leftJoin('[ucasti] ON [ucasti].[id_bodove_tabulky] = [bodove_tabulky].[id]')
			->where('[ucasti].[id_zavodu] = %i', $id);
	}

	public function odstranVetsi($id, $poradi)
	{
		return parent::delete($id)->where(NULL)->where('[id_bodove_tabulky] = %i AND [poradi] > %i', $id, $poradi)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		return $ret;
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

}
