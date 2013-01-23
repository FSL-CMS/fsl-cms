<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model šablon článků
 *
 * @author	Milan Pála
 */
class SablonyClanku extends BaseModel
{

	/** @var string */
	protected $table = 'sablony_clanku';

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
			->select('%n.[id], %n.[nazev], [soubory].[id] AS [obrazek_id], %n.[obrazek_umisteni]', $this->table, $this->table, $this->table)
			->from($this->table)
			->leftJoin('[soubory] ON [soubory].[souvisejici] = "sablony_clanku" AND [soubory].[id_souvisejiciho] = %n.[id]', $this->table)
			->groupBy('%n.[id]', $this->table);
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	/**
	 *
	 * @param int $id ID článku
	 */
	public function findByClanek($id)
	{
		return $this->findAll()
				->rightJoin('[clanky_sablony] ON [clanky_sablony].[id_sablony] = %n.[id] AND [clanky_sablony].[id_clanku] = %i', $this->table, $id);
	}

	public function findByClanekLeve($id)
	{
		return $this->findByClanek($id)->where(array('obrazek_umisteni' => 'vlevo'));
	}

	public function findByClanekPrave($id)
	{
		return $this->findByClanek($id)->where(array('obrazek_umisteni' => 'vpravo'));
	}

	public function delete($id)
	{
		try
		{
			$pocet = $this->connection->select('[id]')->from('clanky_sablony')->where(array('id_sablony%i' => $id))->count();
			if($pocet > 0) throw new RestrictionException('Šablonu nelze odstranit, je přiřazena k článkům.');

			return parent::delete($id)->execute();
		}
		catch (DibiException $e)
		{
			if($e->getCode() == self::RESTRICTION_CONSTRAINT) throw new RestrictionException('Šablonu nelze odstranit.');
			else throw $e;
		}
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
