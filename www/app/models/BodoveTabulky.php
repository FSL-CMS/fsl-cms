<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model anket
 *
 * @author	Milan Pála
 */
class BodoveTabulky extends BaseModel
{
	/** @var string */
	protected $table = 'bodove_tabulky';

	/** @var DibiConnection */
	protected $connection;
	

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}
	
	public function findAll()
	{
		return $this->connection->select('*')->from($this->table)->orderBy('[platnost_od]');
	}
	
	public function findAllToSelect()
	{
		return $this->connection
			->select('[bodove_tabulky].[id], GROUP_CONCAT([body].[body] ORDER BY [body].[poradi] SEPARATOR ", ") AS [nazev]')
			->from($this->table)
			->leftJoin('[body] ON [body].[id_bodove_tabulky] = [bodove_tabulky].[id]')
			->groupBy('[bodove_tabulky].[id]');
	}
	
	public function find($id)
	{
		return $this->connection->select('*')->from($this->table)->where('[bodove_tabulky].[id] = %i', $id);
	}

	public function update($id, array $data)
	{
		$tabulka = $this->find($id)->fetch();
		$bodyModel = new Body;
		if( $tabulka['pocet_bodovanych_pozic'] < $data['pocet_bodovanych_pozic'] )
		{
			for($i=$tabulka['pocet_bodovanych_pozic']; $i<$data['pocet_bodovanych_pozic']; $i++)
			{
				$bodyModel->insert(array('poradi' => $i+1, 'id_bodove_tabulky' => $id));
			}
		}
		elseif( $tabulka['pocet_bodovanych_pozic'] > $data['pocet_bodovanych_pozic'] )
		{
			$bodyModel->odstranVetsi($id, $data['pocet_bodovanych_pozic']);
		}
		return parent::update($id, $data)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		
		$bodyModel = new Body;
		for($i=0; $i<$data['pocet_bodovanych_pozic']; $i++)
		{
			$bodyModel->insert(array('poradi' => $i+1, 'id_bodove_tabulky' => $id));
		}
		return $ret;
	}
	
	public function delete($id)
	{
		try
		{
			return parent::delete($id)->execute();
		}
		catch(DibiException $e)
		{
			if( $e->getCode() == '1451' ) throw new RestrictionException('Bodovou tabulku nelze smazat, je používaná.');
			else throw $e;
		}
		
	}
}