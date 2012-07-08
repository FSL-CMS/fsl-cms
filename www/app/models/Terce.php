<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model terčů
 *
 * @author	Milan Pála
 */
class Terce extends BaseModel
{

	/** @var string */
	protected $table = 'terce';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findByMajitel($id)
	{
		return $this->findAll()
			->where('[terce].[id_sboru] = %u', $id);
	}

	public function findByTyp($id)
	{
		return $this->findAll()
			->where('[terce].[id_typu] = %u', $id);
	}

	public function findBySbor($id)
	{
		return $this->findByMajitel($id);
	}

	public function findPouzite()
	{
		return $this->connection->select('[terce].[id], [typy_tercu].[nazev] AS [typ], [terce].[text], COUNT([zavody].[id]) AS [pocet_pouziti], [terce].[id_sboru] AS [id_majitele], CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec]) AS [majitel], [okresy].[nazev] AS [okres], CONCAT_WS(" ", [typy_tercu].[nazev], "terče", [typy_sboru].[nazev], [mista].[obec]) AS [nazev], [terce].[id_typu]')
			->from('[zavody]')
			->rightJoin('[terce] ON [zavody].[id_tercu] = [terce].[id]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->leftJoin('[sbory] ON [sbory].[id] = [terce].[id_sboru]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[okresy] ON [mista].[id_okresu] = [okresy].[id]')
			->groupBy('[zavody].[id_tercu]');
	}

	public function findAll()
	{
		return $this->connection->select('[terce].[id], [typy_tercu].[nazev] AS [typ], [terce].[text], COUNT([zavody].[id]) AS [pocet_pouziti], [terce].[id_sboru] AS [id_majitele], CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec]) AS [majitel], [okresy].[nazev] AS [okres], CONCAT_WS(" ", [typy_tercu].[nazev], "terče", [typy_sboru].[nazev], [mista].[obec]) AS [nazev], [terce].[id_typu]')
			->from('[terce]')
			->leftJoin('[zavody] ON [zavody].[id_tercu] = [terce].[id]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->leftJoin('[sbory] ON [sbory].[id] = [terce].[id_sboru]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[okresy] ON [mista].[id_okresu] = [okresy].[id]')
			->groupBy('[terce].[id]');
	}

	public function findAlltoSelect()
	{
		return $this->findAll()->select('CONCAT([typy_tercu].[nazev], ", ", CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec])) AS [terce]');
	}

	public function find($id)
	{
		return $this->findAll()
			->where('[terce].[id] = %u', $id);
	}

	public function nejlepsi_casy_terce($id)
	{
		// vyhledá nejlepší čas pro každou kategorii
		$vysledky = $this->connection->query('
			SELECT MIN([vysledky].[vysledny_cas]) AS [vysledny_cas], [id_kategorie]
			FROM [vysledky]
			LEFT JOIN [druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]
			LEFT JOIN [zavody] ON [zavody].[id] = [vysledky].[id_zavodu]
			WHERE [zavody].[platne_casy] = 1 AND [vysledky].[vysledny_cas] < 500 AND [id_tercu] = %u', $id, '
			GROUP BY [zavody].[id_tercu], [druzstva].[id_kategorie]
		');

		$spolecne = array('
			SELECT [sbory].[id], CONCAT([typy_sboru].[zkratka], " ", [druzstva_mista].[obec], " ", [druzstva].[poddruzstvo]) AS [druzstvo], [kategorie].[nazev] AS [kategorie], [vysledky].[vysledny_cas], CONCAT( [poradatel_mista].[obec] ) AS [poradatel], [poradatel].[id] AS [id_poradatele], [poradatel_okresy].[nazev] AS [okres], [vysledky].[id_zavodu], [vysledky].[id_druzstva]
			FROM [vysledky]

			LEFT JOIN [zavody] ON [zavody].[id] = [vysledky].[id_zavodu]
			LEFT JOIN [poradatele] ON [poradatele].[id_zavodu] = [zavody].[id]
			LEFT JOIN [sbory] [poradatel] ON [poradatel].[id] = [poradatele].[id_sboru]
			LEFT JOIN [mista] [poradatel_mista] ON [poradatel_mista].[id] = [poradatel].[id_mista]
			LEFT JOIN [okresy] [poradatel_okresy] ON [poradatel_okresy].[id] = [poradatel_mista].[id_okresu]
			LEFT JOIN [typy_sboru] [poradatel_typy_sboru] ON [poradatel_typy_sboru].[id] = [poradatel].[id_typu]

			LEFT JOIN [druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]
			LEFT JOIN [sbory] ON [sbory].[id] = [druzstva].[id_sboru]
			LEFT JOIN [mista] [druzstva_mista] ON [druzstva_mista].[id] = [sbory].[id_mista]
			LEFT JOIN [typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]

			LEFT JOIN [mista] ON [mista].[id] = [zavody].[id_mista]

			LEFT JOIN [kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]

			WHERE [zavody].[platne_casy] = 1 AND [vysledky].[vysledny_cas] < 500 AND [zavody].[id_tercu] = %u', $id, ' AND [vysledky].[vysledny_cas] LIKE %s AND [druzstva].[id_kategorie] = %u
			ORDER BY [druzstva].[id_kategorie], [vysledky].[vysledny_cas]');

		// poskládá dotaz: (nejlepší výsledek kategorie) UNION (nejlepší jiné kategorie)
		$kompletni = array();
		foreach( $vysledky as $vysledek )
		{
			$kompletni[] = '(';
			$kompletni = array_merge( $kompletni, $spolecne, array($vysledek['vysledny_cas'].'%', $vysledek['id_kategorie']), array(')'));
			$kompletni[] = 'UNION';
		}
		if( count($kompletni) )
		{
			array_pop($kompletni); // odstraní posledí "UNION"
			return $this->connection->query($kompletni);
		}
		else
		{
			return $vysledky;
		}


	}

	public function delete($id, $force = 0)
	{
		if( $force == 0 || $force == 1 )
		{
			$zavody = new Zavody;
			if( $zavody->findByTerce($id)->count() != 0 ) throw new RestrictionException('Terče nelze odstranit, běželo se na nich na závodě.');
		}

		return parent::delete($id);
	}

	public function constructUri($id, $data)
	{
		if( isset($data['id_sboru']) && isset($data['id_typu']) )
		{
			$sborModel = new Sbory;
			$sbor = $sborModel->find($data['id_sboru'])->fetch();
			$typyModel = new TypyTercu;
			$typ = $typyModel->find($data['id_typu'])->fetch();
			$data['uri'] = '/terce/'.$id.'-'.String::webalize(String::webalize($typ['nazev'])).'-'.String::webalize($sbor['nazev']);
		}
		return $data;
	}

	public function insert(array $data)
	{
		try
		{
			$ret = parent::insert($data)->execute();
			$id = $this->connection->insertId();
			$this->lastInsertedId($id);
			$this->constructUri($id, $data);
			if(isset($data['uri']))
			{
				$urlsModel = new Urls;
				$urlsModel->setUrl('Terce', 'terce', $id, $data['uri']);
			}
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
			parent::update($id, $data)->execute();
			$data = $this->constructUri($id, $data);
			if(isset($data['uri']))
			{
				$urlsModel = new Urls;
				$urlsModel->setUrl('Terce', 'terce', $id, $data['uri']);
			}
		}
		catch(DibiException $e)
		{
			if($e->getCode() == 1062) throw new AlreadyExistException();
			else throw $e;
		}
	}

	public function udrzba()
	{
		$vsechny = $this->findAll();
		foreach( $vsechny as $data )
		{
			$dataDoDB = array( 'id_sboru' => $data['id_majitele'], 'id_typu' => (int)$data['id_typu'] );
			$this->update($data['id'], $dataDoDB);
		}
	}
}
