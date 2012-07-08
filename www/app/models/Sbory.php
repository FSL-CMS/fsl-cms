<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Model sborů
 *
 * @author	Milan Pála
 */
class Sbory extends BaseModel
{

	/** @var string */
	protected $table = 'sbory';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()
					 ->where('[sbory].[id] = %u', $id);
	}

	protected function findOne()
	{
		return $this->connection
					 ->select('[sbory].*, CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista].[obec]) AS [nazev], [okresy].[nazev] AS [okres], [uzivatele].[jmeno] AS [kontakt_jmeno], [uzivatele].[prijmeni] AS [kontakt_prijmeni], [uzivatele].[kontakt] AS [kontakt_kontakt], [uzivatele].[id] AS [kontakt_id], [uzivatele].[email] AS [kontakt_email]')
					 ->from($this->table)
					 ->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
					 ->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
					 ->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
					 ->leftJoin('[uzivatele] ON [uzivatele].[id] = [sbory].[id_kontaktni_osoby]');
	}

	public function findAll()
	{
		return $this->findOne()
					 ->orderBy('[typy_sboru].[nazev], [mista].[obec]');
	}

	public function findByTyp($id)
	{
		return $this->findAll()->where('[typy_sboru].[id] = %i', $id);
	}

	public function findByMisto($id)
	{
		return $this->findAll()->where('[mista].[id] = %i', $id);
	}

	public function findAlltoSelect()
	{
		return $this->findAll()->select('CONCAT(CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista].[obec]), ", okres ", [okresy].[zkratka]) AS [sbor]')->orderBy('[typy_sboru].[nazev], [mista].[obec]');
	}

	public function findTypytoSelect()
	{
		return $this->connection
					 ->select('[id], [nazev]')
					 ->from('[typy_sboru]');
	}

	public function findByZavod($id)
	{
		return $this->findOne()
					 ->leftJoin('[poradatele] ON [poradatele].[id_sboru] = [sbory].[id]')
					 ->where('[poradatele].[id_zavodu] = %i', $id)
					 ->orderBy('[typy_sboru].[nazev], [mista].[obec]')
					 ->groupBy('[sbory].[id]');
	}

	public function findIdByUri($uri)
	{
		return $this->findAll()
					 ->where('%n.uri = %s', $this->table, $uri);
	}

	private function constructUri($id, $data)
	{
		if (isset($data['id_mista']) && isset($data['id_typu']))
		{
			$mista = new Mista;
			$misto = $mista->find($data['id_mista'])->fetch();
			$typySboru = new TypySboru;
			$typSboru = $typySboru->find($data['id_typu'])->fetch();
			if (empty($data['privlastek']))
				$data['privlastek'] = '';
			$data['uri'] = Texy::webalize($typSboru['zkratka'] . ' ' . $data['privlastek'] . ' ' . $misto['obec']);
		}
		return $data;
	}

	private function pripravData(array &$data)
	{
		if (isset($data['id_spravce']))
		{
			$data['id_spravce%in'] = (int) $data['id_spravce'];
			unset($data['id_spravce']);
		}
		if (isset($data['id_kontaktni_osoby']))
		{
			$data['id_kontaktni_osoby%in'] = intval($data['id_kontaktni_osoby']);
			unset($data['id_kontaktni_osoby']);
		}
		if (isset($data['id_mista']))
		{
			$data['id_mista%in'] = intval($data['id_mista']);
			unset($data['id_mista']);
		}
		if (isset($data['id_typu']))
		{
			$data['id_typu%in'] = $data['id_typu'];
			unset($data['id_typu']);
		}
	}

	public function insert(array $data)
	{
		try
		{
			$this->pripravData($data);
			$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
			$id = $this->connection->insertId(dibi::IDENTIFIER);
			$this->lastInsertedId($id);
			$this->update($id, $data);
			return $ret;
		}
		catch (DibiException $e)
		{
			if ($e->getCode() == 1062)
				throw new AlreadyExistException();
			else
				throw $e;
		}
	}

	public function update($id, array $data)
	{
		try
		{
			$data = $this->constructUri($id, $data);
			$this->pripravData($data);
			return $this->connection->update($this->table, $data)->where('id=%i', $id)->execute();
		}
		catch (DibiException $e)
		{
			if ($e->getCode() == 1062)
				throw new AlreadyExistException();
			else
				throw $e;
		}
	}

	public function delete($id)
	{
		$druzstva = new Druzstva;
		if (count($druzstva->findBySbor($id)) > 0)
			throw new RestrictionException('Sbor nelze odstranit, obsahuje družstva.');

		$terce = new Terce;
		if (count($terce->findBySbor($id)) > 0)
			throw new RestrictionException('Sbor nelze odstranit, vlastní terče.');

		$zavody = new Zavody;
		if (count($zavody->findBySbor($id)) > 0)
			throw new RestrictionException('Sbor nelze odstranit, pořádal závod.');

		return parent::delete($id)->execute();
	}

	public function udrzba()
	{
		$vsechnySbory = $this->findAll();
		foreach ($vsechnySbory as $data)
		{
			$dataDoDB = array('id_mista' => (int) $data['id_mista'], 'id_typu' => (int) $data['id_typu'], 'privlastek' => $data['privlastek']);
			$this->update($data['id'], $dataDoDB);
		}
	}

}
