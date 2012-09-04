<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model komentářů
 *
 * @author	Milan Pála
 */
class Komentare extends BaseModel
{

	/** @var string */
	protected $table = 'komentare';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->connection
			->select('[komentare].*')
			->from($this->table)
			->where('id = %i', $id);
	}
	
	public function findByDiskuze($id)
	{
		return $this->connection
			->select('[komentare].*')
			->from($this->table)
			->where('[id_diskuze] = %i', $id);
	}	
	
	public function findLastByDiskuze($id)
	{
		return $this->connection
			->select('[komentare].*, CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[nazev], " ", [mista].[obec]) AS [autor]')
			->from($this->table)
			
			// autor komentáře
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [komentare].[id_autora]')
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
               
			->where('[komentare].[id] = (SELECT [id] FROM [komentare] WHERE [id_diskuze] = %i', $id, ' ORDER BY [datum_pridani] DESC LIMIT 1)');		
	}
	
	public function deleteByDiskuze($id)
	{
		return $this->connection->delete($this->table)->where('[id_diskuze] = %i', $id)->execute();
	}

	public function delete($id, $force = 0)
	{
		$komentar = $this->find($id)->fetch();
		$diskuze = $this->findByDiskuze($komentar['id_diskuze']);
		
		if( $diskuze->count() == 1 )
		{
			if( $force == 0 ) throw new RestrictionException('Komentář je v diskuzi poslední, byla by odstraněna i diskuze.');
			$diskuze = new Diskuze;
			$ret = parent::delete($id)->execute();
			$diskuze->delete($komentar['id_diskuze'], true);
		}
		else $ret = parent::delete($id)->execute();
		
		return $ret;
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		return $ret;
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

}
