<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model vazby kategorií a soutěží
 *
 * @author	Milan Pála
 */
class SoutezeRocniku extends BaseModel
{

	/** @var string */
	protected $table = 'souteze_rocniku';

	public function findAll()
	{
		return $this->connection
						->select('%n.[id], [souteze].[nazev], [kategorie].[nazev] AS [kategorie], %n.[id_kategorie], %n.[id_souteze], [kategorie].[pocet_startovnich_mist], %n.[id_bodove_tabulky]', $this->table, $this->table, $this->table, $this->table)
						->from($this->table)
						->leftJoin('[souteze] ON [souteze].[id] = %n.[id_souteze]', $this->table)
						->leftJoin('[kategorie] ON [kategorie].[id] = %n.[id_kategorie]', $this->table)
						->orderBy('[souteze].[poradi], [kategorie].[poradi]');
	}

	public function findByRocnik($id_rocniku)
	{
		return $this->findAll()->where('%n.[id_rocniku] = %i', $this->table, $id_rocniku);
	}

	public function findAllToSelect()
	{
		return $this->connection
						->select('%n.[id], CONCAT([souteze].[nazev], " ", [kategorie].[nazev]) AS [nazev]', $this->table)
						->from($this->table)
						->leftJoin('[souteze] ON [souteze].[id] = %n.[id_souteze]', $this->table)
						->leftJoin('[kategorie] ON [kategorie].[id] = %n.[id_kategorie]', $this->table)
						->orderBy('[souteze].[poradi], [kategorie].[poradi]');
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute();
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
