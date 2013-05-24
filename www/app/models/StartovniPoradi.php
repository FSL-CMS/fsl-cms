<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model startovních pořadí
 *
 * @author	Milan Pála
 */
class StartovniPoradi extends BaseModel
{

	/** @var string */
	protected $table = 'startovni_poradi';

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
			if($e->getCode() == 1062) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
			else throw $e;
		}
	}

	public function update($id, array $data)
	{
		try
		{
			return $this->connection->update($this->table, $data)->where('id=%i', $id)->execute();
		}
		catch (DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
			else throw $e;
		}
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

	public function deleteByZavod($id)
	{
		return $this->connection->delete($this->table)->where('id_zavodu=%i', $id)->execute();
	}

	public function find($id)
	{
		return $this->findBy()->where('[startovni_poradi].[id] = %i', $id);
	}

	protected function findBy()
	{
		return $this->connection
						->select('[startovni_poradi].[id], CONCAT_WS(" ", [typ_sboru_druzstva].[zkratka], [sbor_druzstva].[privlastek], [misto_druzstva].[obec], [druzstva].[poddruzstvo]) AS [druzstvo], [okresy].[nazev] AS [okres], [okresy].[zkratka] AS [okres_zkratka], [kategorie].[nazev] AS [kategorie], [kategorie].[id] AS [id_kategorie], [startovni_poradi].[poradi], [startovni_poradi].[datum], [startovni_poradi].[id_druzstva], CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec]) AS [uzivatel], [startovni_poradi].[id_autora], [sbory].[id] AS [id_sboru], [startovni_poradi].[id_zavodu], [sbor_druzstva].[id] AS [id_sboru_druzstva], [ucasti].[id] AS [id_ucasti]')
						->from($this->table)
						->leftJoin('[druzstva] ON [druzstva].[id] = [startovni_poradi].[id_druzstva]')
						->leftJoin('[sbory] [sbor_druzstva] ON [sbor_druzstva].[id] = [druzstva].[id_sboru]')
						->leftJoin('[mista] [misto_druzstva] ON [misto_druzstva].[id] = [sbor_druzstva].[id_mista]')
						->leftJoin('[okresy] ON [okresy].[id] = [misto_druzstva].[id_okresu]')
						->leftJoin('[typy_sboru] [typ_sboru_druzstva] ON [typ_sboru_druzstva].[id] = [sbor_druzstva].[id_typu]')
						->leftJoin('[uzivatele] ON [uzivatele].[id] = [startovni_poradi].[id_autora]')
						->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
						->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
						->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
						->rightJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
						->rightJoin('[ucasti] ON [ucasti].[id_zavodu] = [startovni_poradi].[id_zavodu] AND [ucasti].[id_kategorie] = [druzstva].[id_kategorie]')
						->orderBy('[kategorie].[poradi], [startovni_poradi].[poradi]');
	}

	public function findByZavod($id)
	{
		return $this->findBy()->where('[startovni_poradi].[id_zavodu] = %i', $id);
	}

	public function findByKategorie($id)
	{
		return $this->findBy()->where('[kategorie].[id] = %i', $id);
	}

	public function findByDruzstvo($id)
	{
		return $this->findBy()->where('[startovni_poradi].[id_druzstva] = %i', $id);
	}

}
