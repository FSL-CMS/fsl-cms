<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model ročníků soutěží
 *
 * @author	Milan Pála
 */
class Rocniky extends BaseModel implements IUdrzba
{

	/** @var string */
	protected $table = 'rocniky';

	/** @var DibiConnection */
	protected $connection;

	protected $zverejnene = true;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function zobrazitNezverejnene()
	{
		$this->zverejnene = false;
	}

	public function zobrazitZverejnene()
	{
		$this->zverejnene = true;
	}

	public function findLast()
	{
		$dotaz = $this->connection->select('[id]')->from($this->table)->orderBy('[rocnik] DESC');
		if( $this->zverejnene == true ) $dotaz->where('[rocniky].[zverejneny] = 1');
		return $dotaz;
	}

	public function findAll()
	{
		$dotaz = $this->connection->select('[rocniky].[id], [rok], [rocnik], [zverejneny], [pravidla].[id] AS [id_pravidel]')->from($this->table)->leftJoin('[pravidla] ON [pravidla].[id_rocniku] = [rocniky].[id]')->orderBy('[rocnik]');
		if( $this->zverejnene == true ) $dotaz->where('[rocniky].[zverejneny] = 1');
		return $dotaz;
	}

	public function find($id)
	{
		return $this->findAll()->where('%n.[id] = %i', $this->table, $id);
	}

	public function findPredchozi($id)
	{
          return $this->connection
			->select('[rocniky].[id], [rocniky].[rok], [rocniky].[rocnik], [rocniky].[zverejneny]')
			->from($this->table)
			->leftJoin('[rocniky] [vedlejsi] ON [vedlejsi].[id] = %i', $id)
			->where('%if', $this->zverejnene == 1, '[rocniky].[zverejneny] = 1 AND [vedlejsi].[zverejneny] = 1 AND %end [rocniky].[rocnik] < [vedlejsi].[rocnik]')
			->orderBy('[rocniky].[rocnik] DESC');
	}

	public function findNasledujici($id)
	{
          return $this->connection
			->select('[rocniky].[id], [rocniky].[rok], [rocniky].[rocnik], [rocniky].[zverejneny]')
			->from($this->table)
			->leftJoin('[rocniky] [vedlejsi] ON [vedlejsi].[id] = %i', $id)
			->where('%if', $this->zverejnene == 1, '[rocniky].[zverejneny] = 1 AND [vedlejsi].[zverejneny] = 1 AND %end [rocniky].[rocnik] > [vedlejsi].[rocnik]')
			->orderBy('[rocniky].[rocnik] DESC');
	}

	public function statistikyPoradani()
	{
		return $this->connection
			->select('[rocniky].[rok], COUNT([zavody].[id]) AS [pocet], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze]')
			->from($this->table)
			->leftJoin('[zavody] ON [zavody].[id_rocniku] = [rocniky].[id]')
			->leftJoin('[ucasti] ON [ucasti].[id_zavodu] = [zavody].[id]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->groupBy('[rocniky].[id], [souteze].[id]')
			->orderBy('[rocniky].[rok]');
	}

	public function statistikyUcastiDruzstev()
	{
		return $this->connection
			->select('[rocniky].[rok], COUNT([druzstva].[id]) AS [pocet]')
			->from($this->table)
			->leftJoin('[zavody] ON [zavody].[id_rocniku] = [rocniky].[id]')
			->leftJoin('[vysledky] ON [vysledky].[id_zavodu] = [zavody].[id]')
			->leftJoin('[druzstva] ON [zavody].[id_rocniku] = [vysledky].[id_druzstva]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->groupBy('[rocniky].[id]')
			->orderBy('[rocniky].[rok]');
	}

	public function zverejnit($id)
	{
		$data = array('zverejneny%i' => 1);
		return $this->update($id, $data);
	}

	public function delete($id, $force = 0)
	{
		$zavody = new Zavody();
		if( $force == 0 )
		{
			if( $zavody->findByRocnik($id)->count() != 0 ) throw new RestrictionException ('Ročník nelze odstranit, jsou k němu nahlášené závody.');
		}
		$zavody->deleteByRocnik($id, true);
		return $this->connection->delete($this->table)->where('[id] = %i', $id)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		$pravidlaModel = new Pravidla;
		$pravidlaModel->zkopirujPravidlaProRocnik($this->lastInsertedId());
		return $ret;
	}

	public function update($id, array $data)
	{
		$return = parent::update($id, $data)->execute();
		if(isset($data['rocnik']))
		{
			$data = $this->constructUri($data);
			$urlsModel = new Urls;
			$urlsModel->setUrl('Rocniky', 'rocnik', $id, $data['uri']);
			$urlsModel->setUrl('Rocniky', 'vysledky', $id, $data['uri'].'/vysledky');
			$urlsModel->setUrl('Pravidla', 'pravidla', $id, $data['uri'].'/pravidla');
		}
		return $return;
	}

	private function constructUri(array $data)
	{
		$data['uri'] = '/rocniky/'.$data['rocnik'];
		return $data;
	}

	public function udrzba()
	{
		$rocniky = $this->findAll();
		foreach($rocniky as $rocnik)
		{
			$this->update($rocnik['id'], array('rok' => $rocnik['rok'], 'rocnik' => $rocnik['rocnik']));
		}
	}
}
