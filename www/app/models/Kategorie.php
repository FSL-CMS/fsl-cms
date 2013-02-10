<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model kategorií
 *
 * @author	Milan Pála
 */
class Kategorie extends BaseModel
{

	/** @var string */
	protected $table = 'kategorie';

	public function findAll()
	{
		return $this->connection
			->select('*')
			->from($this->table)
			->orderBy('[poradi]');
	}

	public function findBySoutez($id)
	{
		return $this
			->connection
			->select('[kategorie].*, [kategorie_souteze].[id_souteze], [kategorie_souteze].[id] AS [kategorie_souteze_id], [kategorie_souteze].[id_bodove_tabulky]')
			->from('[kategorie_souteze]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [kategorie_souteze].[id_kategorie]')
			->where('[kategorie_souteze].[id_souteze] = %i', $id);
	}

	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function insert(array $data)
	{
		try
		{
			$data['poradi%i'] = $this->najdiMaximalniPoradi()+1;
			$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
			$this->lastInsertedId($this->connection->insertId());
			return $ret;
		}
		catch(DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException();
			else throw $e;
		}

	}

	public function update($id, array $data)
	{
		try
		{
			return parent::update($id, $data)->execute();
		}
		catch(DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException();
			else throw $e;
		}
	}

	public function delete($id, $force = 0)
	{
		if( $force == 0 )
		{
			$druzstvaModel = Nette\Environment::getService('druzstva');
			$druzstva = $druzstvaModel->findByKategorieToSelect($id);
			if( $druzstva->count() != 0 ) throw new RestrictionException('Kategorii nelze odstranit, existují k ní družstva.');

			$startovniPoradiModel = Nette\Environment::getService('startovniPoradi');
			$startovniPoradi = $startovniPoradiModel->findByKategorie($id);
			if( $startovniPoradi->count() != 0 ) throw new RestrictionException('Kategorii nelze odstranit, jsou pro ni pořádány závody.');
		}
		return parent::delete($id)->execute();
	}

}
