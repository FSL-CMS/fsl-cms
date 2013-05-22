<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model družstev
 *
 * @author	Milan Pála
 */
class Druzstva extends BaseModel
{
	/** @var string */
	protected $table = 'druzstva';

	/** @var string Zkratka za vzor pro název družstva */
	public static $_NAZEV = 'TRIM(CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista].[obec], [druzstva].[poddruzstvo]))';
	public static $_NAZEV2 = 'TRIM(CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista_druzstva].[obec], [druzstva].[poddruzstvo]))';

	public function findBySbor($id)
	{
		return $this->findAll()
						->where('[druzstva].[id_sboru] = %u', $id);
	}

	public function find($id)
	{
		return $this->findAll()
						->where('[druzstva].[id] = %u', $id);
	}

	public function findAll()
	{
		return $this->connection->select('[druzstva].[id], [druzstva].[poddruzstvo], [kategorie].[nazev] AS [kategorie], [druzstva].[id_kategorie], SUM([vysledky].[body]) AS [pocet_bodu], COUNT([ucasti].[id_zavodu]) AS [pocet_zavodu], [druzstva].[id_sboru], CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista].[obec]) AS [sbor], ' . Druzstva::$_NAZEV . ' AS [nazev], [okresy].[nazev] AS [okres]')
						->from($this->table)
						->leftJoin('[vysledky] ON [vysledky].[id_druzstva] = [druzstva].[id]')
						->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
						->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
						->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
						->leftJoin('[okresy] ON [mista].[id_okresu] = [okresy].[id]')
						->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
						->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
						->groupBy('[druzstva].[id]')
						->orderBy('[kategorie].[poradi], [nazev], [mista].[obec], [druzstva].[poddruzstvo]');
	}

	public function findAllToSelect()
	{
		return $this->findAll()->groupBy(NULL);
	}

	public function findByKategorieToSelect($id)
	{
		return $this->connection->select('[druzstva].[id], ' . Druzstva::$_NAZEV . ' AS [druzstvo], CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], IF(LENGTH([mista].[obec])>15, CONCAT(SUBSTRING([mista].[obec], 1, 5), "...", SUBSTRING([mista].[obec], -5)), [mista].[obec]), [druzstva].[poddruzstvo]) AS [kratke]')
						->from($this->table)
						->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
						->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
						->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
						->where('[druzstva].[id_kategorie] = %i', $id)
						->orderBy('[typy_sboru].[nazev], [mista].[obec], [druzstva].[poddruzstvo]');
	}

	public function findByUcastiToSelect($id)
	{
		return $this->connection->select('[druzstva].[id], ' . Druzstva::$_NAZEV . ' AS [druzstvo], CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], IF(LENGTH([mista].[obec])>15, CONCAT(SUBSTRING([mista].[obec], 1, 5), "...", SUBSTRING([mista].[obec], -5)), [mista].[obec]), [druzstva].[poddruzstvo]) AS [kratke]')
						->from($this->table)
						->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
						->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
						->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
						->where('[druzstva].[id_kategorie] = (SELECT [id_kategorie] FROM [ucasti] WHERE [id] = %i)', $id)
						->orderBy('[typy_sboru].[zkratka], [mista].[obec], [druzstva].[poddruzstvo]');
	}

	/**
	 *
	 * @param type $id
	 * @return type
	 */
	public function findByLoginedUser($id)
	{
		return $this->connection->select('[druzstva].[id], [kategorie].[nazev], [kategorie].[id] AS [id_kategorie], CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec], [druzstva].[poddruzstvo]) AS [druzstvo]')
						->from($this->table)
						->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
						->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
						->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
						->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
						->leftJoin('[uzivatele] ON [uzivatele].[id_sboru] = [sbory].[id]')
						->where('[uzivatele].[id] = %i', $id)
						->orderBy('[druzstvo]');
	}

	private function constructUri($id, $data)
	{
		if(isset($data['id_sboru']) && isset($data['id_kategorie']) && isset($data['poddruzstvo']))
		{
			$sbory = Nette\Environment::getService('sbory');
			$sbor = $sbory->find($data['id_sboru'])->fetch();
			$kategorieModel = Nette\Environment::getService('kategorie');
			$kategorie = $kategorieModel->find($data['id_kategorie'])->fetch();
			$data['uri'] = '/druzstva/' . Texy::webalize($sbor['nazev'] . ' ' . $kategorie['nazev'] . ' ' . $data['poddruzstvo']);
		}
		return $data;
	}

	public function insert(array $data)
	{
		try
		{
			$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
			$id = $this->connection->insertId();
			$this->lastInsertedId($id);
			$data = $this->constructUri($id, $data);
			$urlsModel = Nette\Environment::getService('urls');
			$urlsModel->setUrl('Druzstva', 'druzstvo', $id, $data['uri']);
			return $ret;
		}
		catch (DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException();
			else throw $e;
		}
	}

	public function update($id, array $data)
	{
		try
		{
			parent::update($id, $data)->execute();
			$data = $this->constructUri($id, $data);
			$urlsModel = Nette\Environment::getService('urls');
			$urlsModel->setUrl('Druzstva', 'druzstvo', $id, $data['uri']);
		}
		catch (DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException();
			else throw $e;
		}
	}

	public function delete($id)
	{
		$vysledky = Nette\Environment::getService('vysledky');
		if(count($vysledky->findByDruzstvo($id)) > 0) throw new RestrictionException('Družstvo nelze smazat, jsou pro něj zaznamenány výsledky.');

		$sp = Nette\Environment::getService('startovniPoradi');
		if(count($sp->findByDruzstvo($id)) > 0) throw new RestrictionException('Družstvo nelze smazat, je přihlášeno na závody.');

		return parent::delete($id)->execute();
	}

	public function udrzba()
	{
		$vsechnaDruzstva = $this->findAll();
		foreach ($vsechnaDruzstva as $data)
		{
			$dataDoDB = array('id_sboru' => (int) $data['id_sboru'], 'id_kategorie' => (int) $data['id_kategorie'], 'poddruzstvo' => $data['poddruzstvo']);
			$this->update($data['id'], $dataDoDB);
		}
	}

}
