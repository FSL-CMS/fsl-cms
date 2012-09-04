<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model sportovišť
 *
 * @author	Milan Pála
 */
class Sportoviste extends BaseModel
{

	/** @var string */
	protected $table = 'sportoviste';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()->where('%n.[id] = %i', $this->table, $id);
	}

	public function findAll()
	{
		return $this->connection
			->select('[sportoviste].[id], [sportoviste].[id_mista], [sportoviste].[sirka], [sportoviste].[delka], [sportoviste].[popis], [mista].[obec], [okresy].[nazev] AS [okres], [mista].[id_okresu]')
		     ->from($this->table)
		     ->leftJoin('[mista] ON [mista].[id] = [sportoviste].[id_mista]')
			->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
		     ->orderBy('[obec]');
	}

	public function findAllToSelect()
	{
		return $this->findAll()->select('CONCAT([mista].[obec], " (", [okresy].[zkratka], ")") AS [nazev]');
	}

	public function findByOkres($id)
	{
		return $this->findAll()->where('[okresy].[id] = %i', $id);
	}

	public function muzeEditovat($id, $id_uzivatele)
	{
		return (bool)$this->connection
			->select('IF(COUNT([uzivatele].[id])=0,0,1)')
			->from($this->table)
			->rightJoin('[zavody] ON [zavody].[id_mista] = %n.[id]', $this->table)
			->rightJoin('[poradatele] ON [poradatele].[id_zavodu] = [zavody].[id]')
			->rightJoin('[sbory] ON [sbory].[id] = [poradatele].[id_sboru]')
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [sbory].[id_spravce] OR [uzivatele].[id] = [sbory].[id_kontaktni_osoby]')
			->where('[sportoviste].[id] = %i', $id, 'AND [uzivatele].[id] = %i', $id_uzivatele)
			->fetchSingle()
		   ;
	}

	public function delete($id, $force = 0)
	{
		if( $force == 0 || $force == 1)
		{
			$zavody = new Zavody;
			if( $zavody->findBySportoviste($id)->count() > 0 ) throw new RestrictionException('Nelze odstranit sportoviště, konal/koná se v něm závod.');
		}

		return parent::delete($id)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$this->update($id, $data);
		return $ret;
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}
}
