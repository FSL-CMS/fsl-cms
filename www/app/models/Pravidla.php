<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model pravidel soutěží
 *
 * @author	Milan Pála
 */
class Pravidla extends BaseModel
{

	/** @var string */
	protected $table = 'pravidla';

	/** @var DibiConnection */
	protected $connection;

	protected $zverejnene = true;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		return $this->connection
			->select('%n.[id], [pravidla], [rocnik], [id_rocniku], [rok]', $this->table)
			->from($this->table)
			->leftJoin('[rocniky] ON [rocniky].[id] = %n.[id_rocniku]', $this->table)
			->orderBy('[id_rocniku]');
	}

	public function find($id)
	{
		return $this->findAll()->where('%n.[id] = %i', $this->table, $id);
	}

	public function findByRocnik($id)
	{
		return $this->findAll()->where('[id_rocniku] = %i', $id);
	}

	public function findLast()
	{
		$rocnikyModel = new Rocniky;
		$posledni = $rocnikyModel->findLast()->fetch();
		return $this->findByRocnik($posledni['id']);
	}

	public function delete($id, $force = 0)
	{
		return $this->connection->delete($this->table)->where('[id] = %i', $id)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		return $ret;
	}

	public function update($id, array $data)
	{
		$return = parent::update($id, $data)->execute();
		return $return;
	}

	public function zkopirujPravidlaProRocnik($id)
	{
		$rocnikyModel = new Rocniky;
		$posledni = $rocnikyModel->findLast()->fetch();
		if($posledni != false)
		{
			$predchozi = $rocnikyModel->findPredchozi($posledni['id'])->fetch();
			$posledniPravidla = $this->findByRocnik($predchozi['id'])->fetch();
			return $this->insert(array('pravidla' => $posledniPravidla['pravidla'], 'id_rocniku' => $id));
		}
	}
}
