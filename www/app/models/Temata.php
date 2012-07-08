<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Model témat diskuze
 *
 * @author	Milan Pála
 */
class Temata extends BaseModel
{

	/** @var string */
	protected $table = 'temata';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}

	public function findBySouvisejici($souvisejiciTabulka)
	{
		return $this->findAll()->where('[souvisejici] = %s', $souvisejiciTabulka);
	}

	public function findAll()
	{
		return $this->connection
					 ->select('*')
					 ->from($this->table)
					 ->orderBy('[poradi]');
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function delete($id, $force = false)
	{
		$diskuze = new Diskuze;

		if($force == false)
		{
			if($diskuze->findByTema($id)->count() != 0) throw new RestrictionException('Téma nelze odstranit, obsahuje diskuze.');
		}

		$diskuze->deleteByTema($id, true);

		parent::delete($id)->execute();
	}

	public function findIdByUri($uri)
	{
		return $this->findAll()
					 ->where('%n.uri = %s', $this->table, $uri);
	}

	private function constructUri($id, $data)
	{
		if(isset($data['nazev']))
		{
			$data['uri'] = '/forum/' . Texy::webalize($data['nazev']) . '/';
		}
		return $data;
	}

	public function update($id, array $data)
	{
		parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		if(isset($data['uri']))
		{
			$urlsModel = new Urls;
			$urlsModel->setUrl('Forum', 'forum', $id, $data['uri']);
		}
	}

	public function insert(array $data)
	{
		$data['poradi%i'] = $this->najdiMaximalniPoradi() + 1;
		$ret = parent::insert($data)->execute();
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		if(isset($data['uri']))
		{
			$urlsModel = new Urls;
			$urlsModel->setUrl('Forum', 'forum', $id, $data['uri']);
		}
		return $ret;
	}

	public function udrzba()
	{
		$vsechnaTemata = $this->findAll();
		foreach ($vsechnaTemata as $data)
		{
			$dataDoDB = array('nazev' => $data['nazev']);
			$this->update($data['id'], $dataDoDB);
		}
	}

}
