<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model účastí kategorií na závodech
 *
 * @author	Milan Pála
 */
class Ucasti extends BaseModel
{

	/** @var string */
	protected $table = 'ucasti';

	/**
	 * Najde všechny kategorie a knim přiřadí účasti družstev na soutži
	 * I nezúčastněné kategorie vybírá kvůli možnosti přihlásit je na soutž
	 * @param int $id ID závodu
	 */
	public function findByZavod($id)
	{
		return $this->connection
			->select('[ucasti].[id], [ucasti].[id_kategorie], [kategorie].[nazev] AS [kategorie], IFNULL([ucasti].[pocet], [kategorie].[pocet_startovnich_mist]) AS [pocet_startovnich_mist], [ucasti].[id] AS [id_ucasti], [souteze].[nazev], [ucasti].[id_bodove_tabulky], [ucasti].[id_souteze]')
			->from('[ucasti]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [ucasti].[id_kategorie]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			//->leftJoin('[bodove_tabulky] ON [bodove_tabulky].[id] = [ucasti].[id_bodove_tabulky]')
			->where('[ucasti].[id_zavodu] = %i', $id)
			->orderBy('[souteze].[poradi], [kategorie].[poradi]')
			;
	}

	public function findByZavodToSelect($id)
	{
		return $this->findByZavod($id)->select('CONCAT([souteze].[nazev], " - ", [kategorie].[nazev]) AS [nazev]');
	}

	public function pripravData(&$data)
	{
		if(isset($data['id_souteze']))
		{
			$data['id_souteze%in'] = $data['id_souteze'];
			unset($data['id_souteze']);
		}
	}

	public function insert(array $data)
	{
		try
		{
			$this->pripravData($data);
			$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
			$this->lastInsertedId($this->connection->insertId());
			return $ret;
		}
		catch(DibiException $e)
		{
			if( $e->getCode() == 1062 ) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
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
		catch(DibiException $e)
		{
			if( $e->getCode() == 1062 ) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
			else throw $e;
		}
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

}
