<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model závodů
 *
 * @author	Milan Pála
 */
class Zavody extends Zverejnovane
{

	/** @var string */
	protected $table = 'zavody';

	/** @var DibiConnection */
	protected $connection;

	public function __construct(\DibiConnection $connection)
    {
        $this->connection = $connection;
    }

	public function findIdByUri($uri, $column = 'uri')
	{
		return $this->findOne()
			->where('%n.%n = %s', $this->table, $column, $uri);
	}

	public function findOne()
	{
		$dotaz = $this->connection->select('[zavody].*, count([poradatele].[id]) AS [pocet_poradatelu], IF(COUNT([poradatele].[id])>1,[mista].[obec],GROUP_CONCAT(DISTINCT [poradatel_mista].[obec] ORDER BY [poradatel_mista].[obec] SEPARATOR "-")) AS [nazev], [typy_tercu].[nazev] AS [terce], CONCAT([majitel_typy_sboru].[zkratka], " ", [majitel_mista].[obec]) AS [majitel_tercu], [mista].[obec] AS [misto], [okresy].[nazev] AS [okres], ([zavody].[hodnoceni_soucet]/[zavody].[hodnoceni_pocet]) AS [hodnoceni], IF([zavody].[vystaveni_vysledku] IS NULL, 0, 1) AS [vysledky], [rocniky].[rocnik], [rocniky].[rok], [sportoviste].[sirka], [sportoviste].[delka], [sportoviste].[popis], SUBSTR([zavody].[datum], 1, 4) AS [sezona]')
			->from($this->table)
			->leftJoin('[rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]')

			->leftJoin('[poradatele] ON [poradatele].[id_zavodu] = [zavody].[id]')

			->leftJoin('[sbory] ON [sbory].[id] = [poradatele].[id_sboru]')
			->leftJoin('[mista] [poradatel_mista] ON [poradatel_mista].[id] = [sbory].[id_mista]')
			->leftJoin('[okresy] [poradatel_okresy] ON [poradatel_okresy].[id] = [poradatel_mista].[id_okresu]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')

			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->leftJoin('[sbory] [majitel] ON [majitel].[id] = [terce].[id_sboru]')
			->leftJoin('[mista] [majitel_mista] ON [majitel_mista].[id] = [majitel].[id_mista]')
			->leftJoin('[okresy] [majitel_okresy] ON [majitel_okresy].[id] = [majitel_mista].[id_okresu]')
			->leftJoin('[typy_sboru] [majitel_typy_sboru] ON [majitel_typy_sboru].[id] = [majitel].[id_typu]')
			->leftJoin('[sportoviste] ON [sportoviste].[id] = [zavody].[id_mista]')
			->leftJoin('[mista] ON [mista].[id] = [sportoviste].[id_mista]')
			->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [sbory].[id_kontaktni_osoby]')
			->leftJoin('[ucasti] ON [ucasti].[id_zavodu] = [zavody].[id]')
				->groupBy('[zavody].[datum]');
			//->orderBy('[zavody].[datum]')
			//->groupBy('[zavody].[id]');
		if( $this->zverejnene == true ) $dotaz->where('[rocniky].[zverejneny] = 1');
		return $dotaz;
	}

	public function findByRocnik($id)
	{
		return $this->findOne()
			->where('[zavody].[id_rocniku] = %u', $id)
			->groupBy('[zavody].[id]');
	}

	public function findByPoradatel($id_poradatele)
	{
		return $this->findOne()
			->where('[sbory].[id] = %u', $id_poradatele)
			->groupBy('[zavody].[id]');
	}

	public function findByTerce($id)
	{
		return $this->findOne()
			->where('[zavody].[id_tercu] = %u', $id)
			->groupBy('[zavody].[id]');
	}

	public function findBySportoviste($id)
	{
		return $this->findOne()
			->where('[zavody].[id_mista] = %u', $id);
	}

	public function findBySbor($id)
	{
		return $this->findByPoradatel($id);
	}

	public function find($id)
	{
		return $this->findOne()
			->where('[zavody].[id] = %u', $id);
	}

	public function findAktualni()
	{
		$datum = strtotime('+3 weeks previous sunday +1 day');
		return $this->findOne()
			->where('[zavody].[zruseno] = 0 AND [zavody].[datum] > NOW() AND [zavody].[datum] <= %d', $datum)
			->groupBy('[zavody].[id]')
			->orderBy('[zavody].[datum]')
			->limit(3);
	}

	private function findRelative($id)
	{
		return $this->findOne()
			->where('[zavody].[id_rocniku] = (SELECT [id_rocniku] FROM [zavody] WHERE [id] = %i) AND [zavody].[zruseno] = 0', $id);
	}

	public function findNext($id)
	{
		return $this->findRelative($id)->where('[zavody].[datum] > (SELECT [datum] FROM [zavody] WHERE [id] = %i)', $id)->orderBy('[zavody].[datum]');
	}

	public function findPrevious($id)
	{
		return $this->findRelative($id)->where('[zavody].[datum] < (SELECT [datum] FROM [zavody] WHERE [id] = %i)', $id)->orderBy(false)->orderBy('[zavody].[datum] DESC');
	}

	public function findAll()
	{
		return $this->findOne()
			->groupBy('[zavody].[id]')
			->orderBy('[zavody].[datum]');
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function findRocniky()
	{
		return $this->connection
			->select('[rocniky].[id], [rocniky].[rok]')
			->from('[rocniky]')
			->orderBy('[rocniky].[rok] DESC');
	}

	public function ohodnot($id, $hodnoceni)
	{
		$data = array('hodnoceni_pocet%sql' => '[hodnoceni_pocet]+1', 'hodnoceni_soucet%sql' => '[hodnoceni_soucet]+'.$hodnoceni);
		return $this->connection->update($this->table, $data)->where('id=%i', $id)->execute();
	}

	public function findZavodyBezKontaktu($id)
	{
		return $this
			->findAll()
			->where('([sbory].[id_kontaktni_osoby] IS NULL OR ([sbory].[id_kontaktni_osoby] IS NOT NULL AND [uzivatele].[kontakt] = "")) AND [rocniky].[id] = %i', $id);
	}

	public function findZavodyBezVysledku($id)
	{
		return $this
			->findAll()
			->where('[zavody].[zruseno] = 0 AND [zavody].[vystaveni_vysledku] IS NULL AND [zavody].[datum] < NOW() AND [rocniky].[id] = %i', $id);
	}

	public function findZavodyBezSP($id)
	{
		return $this->findOne()
			->where('[rocniky].[id] = %i', $id, 'AND [zavody].[zruseno] = 0')
			->groupBy('[zavody].[id]')
			->having('COUNT([ucasti].[id]) = 0');
	}

	public function deleteByRocnik($id, $force = 0)
	{
		while( ($zavod = $this->findByRocnik($id)->fetch()) )
		{
			$this->delete($zavod['id'], $force);
		}

	}

	public function delete($id, $force = 0)
	{
		$startovniPoradi = Nette\Environment::getService('startovniPoradi');
		$vysledky = Nette\Environment::getService('vysledky');
		if($force == 0)
		{
			if( $startovniPoradi->findByZavod($id)->count() != 0 ) throw new RestrictionException('Závod nelze odstranit. Jsou k němu přihlášené týmy.');

			if( $vysledky->findByZavod($id)->count() != 0 ) throw new RestrictionException('Závod nelze odstranit. Jsou z něho evidované výsledky.');
		}
		$startovniPoradi->deleteByZavod($id);
		$vysledky->deleteByZavod($id);
		return $this->connection->delete($this->table)->where('id=%i', $id)->execute();
	}

	public function zverejnitVysledky($id)
	{
		$data = array('vystaveni_vysledku%sql' => 'NOW()');
		return $this->update( $id, $data );
	}

	public function nezverejnitVysledky($id)
	{
		$data = array('vystaveni_vysledku%sql' => 'NULL');
		return $this->update( $id, $data );
	}

	private function constructUri($id, $data)
	{
		if(isset($data['datum%t'])) {
			$zavod = $this->find($id)->fetch();
			$data['uri'] = date('Y-m-d', strtotime($data['datum%t'])).'-'.Texy::webalize($zavod['nazev']);
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
			if(isset($data['uri'])) $urlsModel->setUrl('Zavody', 'zavod', $id, $data['uri']);
			return $ret;
		}
		catch(DibiException $e)
		{
			if( $e->getCode() == 1062 ) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
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
			if(isset($data['uri']))
			{
				$urlsModel->setUrl('Zavody', 'zavod', $id, '/zavody/'.$data['uri']);
				$urlsModel->setUrl('Zavody', 'vysledky', $id, '/zavody/'.$data['uri'].'/vysledky');
				$urlsModel->setUrl('Zavody', 'vysledkyExcel', $id, '/zavody/'.$data['uri'].'/vysledky-excel');
				$urlsModel->setUrl('Zavody', 'startovniPoradi', $id, '/zavody/'.$data['uri'].'/startovni-poradi');
				$urlsModel->setUrl('Zavody', 'pripravaProKomentatora', $id, '/zavody/'.$data['uri'].'/priprava-pro-komentatora');
			}
		}
		catch(DibiException $e)
		{
			if( $e->getCode() == 1062 ) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
			else throw $e;
		}
	}

	public function udrzba()
	{
		$vysledky = Nette\Environment::getService('vysledky');
		$vsechnyZavody = $this->findAll();
		foreach( $vsechnyZavody as $zavod )
		{
			$zavod_data = array( 'id_rocniku' => (int)$zavod['id_rocniku'], 'id_mista%in' => (int)$zavod['id_mista'], 'text' => $zavod['text'], 'datum%t' => $zavod['datum'], 'id_tercu' => (int)$zavod['id_tercu'] );
			if( count($vysledky->findByZavod($zavod['id'])) == 0 ) $zavod_data['vystaveni_vysledku%sql'] = 'NULL';
			$this->update($zavod['id'], $zavod_data);
		}
	}

	public function findPredchoziKola($id)
	{
		$poradatel = $this->connection->query('SELECT GROUP_CONCAT([id_sboru] ORDER BY [id_sboru] SEPARATOR ",") AS [poradatele] FROM [poradatele] WHERE [id_zavodu] = %i GROUP BY [id_zavodu]', $id)->fetch();
		$poradatele = $this->connection->query('SELECT [id_zavodu], GROUP_CONCAT([id_sboru] ORDER BY [id_sboru] SEPARATOR ",") AS [poradatele] FROM [poradatele] WHERE [id_zavodu] != %i GROUP BY [id_zavodu]', $id)->fetchAll();

		$zavody = array();

		foreach($poradatele as $por)
		{
			if($por['poradatele'] == $poradatel['poradatele']) $zavody[] = $por['id_zavodu'];
		}

		return $this->findAll()->where('[zavody].[id] IN %in', $zavody);
	}

	public function findPoradatele($id)
	{
		$sboryModel = Nette\Environment::getService('sbory');
		return $sboryModel->findByZavod($id);
	}

	public function findPoradateleToSelect($id)
	{
		$sboryModel = Nette\Environment::getService('sbory');
		return $sboryModel->findByZavod($id);
	}

	public function pridejPoradatele($id_zavodu, $id_poradatele)
	{
		return $this->connection->insert('poradatele', array('id_zavodu%i' => $id_zavodu, 'id_sboru%i' => $id_poradatele))->execute();
	}

	public function odeberPoradatele($id_zavodu, $id_poradatele, $force = 0)
	{
		if( $force == 0 && count($this->findPoradatele($id_zavodu)) < 2 ) throw new RestrictionException('Nelze odebrat pořadatele, závod by žádného neměl.');
		return $this->connection->delete('poradatele')->where('%and', array('id_zavodu%i' => $id_zavodu, 'id_sboru%i' => $id_poradatele))->execute();
	}

}
