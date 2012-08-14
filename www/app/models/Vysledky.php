<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model výsledků
 *
 * @author	Milan Pála
 */
class Vysledky extends BaseModel
{

	/** @var string */
	protected $table = 'vysledky';

	/** @var DibiConnection */
	protected $connection;

	const HRANICE_PLATNYCH_CASU = 500;
	const NEPLATNY_POKUS = 1000;
	const POUZE_PLATNE_CASY = 1;
	const POUZE_PLATNE_BODY = 1;
	public static $SPECIALNI_VYSLEDKY = array( self::NEPLATNY_POKUS => 'neplatný pokus' );

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		return $this->connection
			->select('[vysledky].[umisteni] AS [poradi], CONCAT( [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec], " ", [druzstva].[poddruzstvo] ) AS [druzstvo], [okresy].[nazev] AS [okres], [okresy].[zkratka] AS [okres_zkratka], [druzstva].[id] AS [id_druzstva], [kategorie].[nazev] AS [kategorie], [kategorie].[id] AS [id_kategorie], [vysledky].[vysledny_cas], [vysledky].[body], [vysledky].[id], [vysledky].[lepsi_cas], [vysledky].[lepsi_terc], [vysledky].[platne_body], [vysledky].[platne_casy], [ucasti].[id_zavodu]')
			->from($this->table)
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			//->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			//->orderBy('[kategorie].[poradi], [vysledky].[umisteni], [vysledky].[body]');
			->orderBy('[zavody].[datum]');
		}

	/**
	 * Nalezne vsšechny výsledky jednoho závody.
	 * @param int $id ID závodu
	 * @return DibiFluent
	 */
	public function findByZavod($id)
	{
		return $this->connection
			->select('[vysledky].[umisteni] AS [poradi], CONCAT( [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec], " ", [druzstva].[poddruzstvo] ) AS [druzstvo], [okresy].[nazev] AS [okres], [okresy].[zkratka] AS [okres_zkratka], [druzstva].[id] AS [id_druzstva], [kategorie].[nazev] AS [kategorie], [kategorie].[id] AS [id_kategorie], [vysledky].[vysledny_cas], [vysledky].[body], [vysledky].[id], [vysledky].[lepsi_cas], [vysledky].[lepsi_terc], [vysledky].[platne_body], [vysledky].[platne_casy], [souteze].[nazev] AS [soutez], [ucasti].[id] AS [id_ucasti], [ucasti].[id_souteze]')
			->from($this->table)
			->rightJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->rightJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->rightJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->rightJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
			->rightJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->rightJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->rightJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->rightJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->where('[ucasti].[id_zavodu] = %u', $id)
			->orderBy('[souteze].[poradi], [kategorie].[poradi], [vysledky].[umisteni], [vysledky].[body]');
	}

	/**
	 * Nalezne všechny výsledky jednoho družstva
	 * @param int $id družstva
	 * @return DibiFluent
	 */
	public function findByDruzstvo($id)
	{
		return $this->connection
			->select('[vysledky].[id], IF( [sbor_poradatele].[id_mista] <> [sportoviste].[id_mista], CONCAT([poradatel_mista].[obec], " - ", [mista].[obec]), [poradatel_mista].[obec] ) as [zavod], [vysledky].[umisteni] AS [poradi], CONCAT( [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec], " ", [druzstva].[poddruzstvo] ) AS [druzstvo], [druzstva].[id] AS [id_druzstva], [kategorie].[nazev] AS [kategorie], [kategorie].[id] AS [id_kategorie], [vysledky].[vysledny_cas], [vysledky].[body], [vysledky].[lepsi_cas], [vysledky].[lepsi_terc], [vysledky].[platne_body], [vysledky].[platne_casy], [souteze].[nazev] AS [soutez], [ucasti].[id] AS [id_ucasti], [ucasti].[id_souteze], [ucasti].[id_zavodu], [zavody].[datum], SUBSTR([zavody].[datum], 1, 4) AS [sezona]')
			->from($this->table)
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')

			->rightJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[poradatele] ON [poradatele].[id_zavodu] = [zavody].[id]')
			->leftJoin('[sportoviste] ON [sportoviste].[id] = [zavody].[id_mista]')
			->leftJoin('[mista] ON [mista].[id] = [sportoviste].[id_mista]')
			->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
			->leftJoin('[sbory] [sbor_poradatele] ON [sbor_poradatele].[id] = [poradatele].[id_sboru]')

			->rightJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[mista] [poradatel_mista] ON [poradatel_mista].[id] = [sbor_poradatele].[id_mista]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]')
			->where('[vysledky].[id_druzstva] = %u', $id)
			->orderBy('[zavody].[datum]');
	}

	public function findByZavodZverejnene($id)
	{
		return $this->findByZavod($id)->where('[zavody].[vystaveni_vysledku] IS NOT NULL AND [zavody].[vystaveni_vysledku] < NOW()');
	}


	public function findByRocnik($id)
	{
		return $this->connection
			->select('CONCAT( [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec], " ", [druzstva].[poddruzstvo] ) AS [druzstvo], [okresy].[nazev] AS [okres], [okresy].[zkratka] AS [okres_zkratka], [druzstva].[id] AS [id_druzstva], [kategorie].[nazev] AS [kategorie], SUM([vysledky].[body]) AS [celkem_bodu], [zavody].[id_rocniku], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze]')
			->from($this->table)
			->rightJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->rightJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->rightJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->rightJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
			->rightJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->rightJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->rightJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->rightJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->where('[zavody].[id_rocniku] = %i', $id, ' AND [zavody].[vystaveni_vysledku] IS NOT NULL') //AND [zavody].[platne_body] = %i', self::POUZE_PLATNE_BODY
			->groupBy('[druzstva].[id], [souteze].[id]')
			->orderBy('[souteze].[poradi], [kategorie].[poradi], [celkem_bodu] DESC');
	}

	/**
	 * Vrátí výsledky ročníky platné před začátkem uvedeného závodu
	 * @param int $id ID ročníku
	 * @param int $id_zavodu ID závodu
	 * @return DibiFluent
	 */
	public function findByRocnikAndZavod($id, $id_zavodu)
	{
		return $this->findByRocnik($id)->where('[zavody].[datum] < (SELECT [datum] FROM [zavody] WHERE [id] = %i)', $id_zavodu);
	}

	/**
	 * Vrátí výsledky ročníky platné po skončení uvedeného závodu
	 * @param int $id ID ročníku
	 * @param int $id_zavodu ID závodu
	 * @return DibiFluent
	 */
	public function findByRocnikAndZavodAfter($id, $id_zavodu)
	{
		return $this->findByRocnik($id)->where('[zavody].[datum] <= (SELECT [datum] FROM [zavody] WHERE [id] = %i)', $id_zavodu);
	}

	/**
	 * Nalezne všechny vítěze jednotlivých ročníků
	 * @return DibiFluent
	 */
	public function findVitezeRocniku()
	{
		return $this->connection
			->select('CONCAT( [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec], " ", [druzstva].[poddruzstvo] ) AS [druzstvo], [druzstva].[id] AS [id_druzstva], [kategorie].[nazev] AS [kategorie], SUM([vysledky].[body]) AS [celkem_bodu], [zavody].[id_rocniku], [rocniky].[rok], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze]')
			->from($this->table)
			->rightJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->rightJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->rightJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->rightJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->rightJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->rightJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->rightJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->rightJoin('[rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]')
			->where('[vysledky].[platne_body] = %i', self::POUZE_PLATNE_BODY)
			->groupBy('[rocniky].[id], [kategorie].[id], [druzstva].[id], [souteze].[id]')
			->orderBy('[rocniky].[rok] DESC, [souteze].[poradi], [kategorie].[id], [celkem_bodu] DESC');
	}

	/**
	 * Porovnání dvou družstev vrámci jedné sezóny pro potřeby celkového hodnocení
	 * - funkce vrátí tabulku obsahující pořadí na všech soutěžích v jedné sezóně dvou družstev
	 * - řádky jsou jednotlivé závody, dva sloupce obsahují umístění týmu a a týmu b (NULL, pokud není účast)
	 * @param array $a První porovnávané družstvo, požadovaný formát: array('id_rocniku', 'id_druzstva', 'id_souteze', ...)
	 * @param array $b Druhé porovnávané družstvo, požadovaný formát: array('id_rocniku', 'id_druzstva', 'id_souteze', ...)
	 * @return resource výsledek dotazu
	 */
	public function porovnejDruzstva($a, $b)
	{
		return $this->connection->query('
			SELECT [a].[umisteni] AS [a_umisteni], [b].[umisteni] AS [b_umisteni]
			FROM [zavody]
			RIGHT JOIN [ucasti] ON [ucasti].[id_zavodu] = [zavody].[id] AND [ucasti].[id_souteze] = %i', $a['id_souteze'], '
			LEFT JOIN [vysledky] [a] ON [a].[id_ucasti] = [ucasti].[id] AND [a].[id_druzstva] = %i', $a['id_druzstva'], '
	  		LEFT JOIN [vysledky] [b] ON [b].[id_ucasti] = [ucasti].[id] AND [b].[id_druzstva] = %i', $b['id_druzstva'], '
			WHERE [zavody].[id_rocniku] = %i', $a['id_rocniku'], ' AND [zavody].[vystaveni_vysledku] IS NOT NULL
		');
	}

	/**
	 * Vybere nejrychlejší místa, na kterých se závodilo
	 */
	public function nejrychlejsiDrahy()
	{
		$prehled = $this->connection
			->select('[druzstva].[id_kategorie], [zavody].[id_tercu]')
			->from('[vysledky]')
			->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->groupBy('[druzstva].[id_kategorie], [zavody].[id_tercu]');
		//Debug::dump($prehled->fetchAssoc('id_kategorie,id_tercu'));

		$spolecne = array('
			SELECT [mista].[id], [mista].[obec], COUNT([sbory].[id]) AS [pocet], [kategorie].[nazev] AS [kategorie], [typy_tercu].[nazev] AS [typ], [kategorie].[poradi] AS [kategorie_poradi], [typy_tercu].[poradi] AS [typy_tercu_poradi]
			FROM [vysledky]
		 	LEFT JOIN [ucasti] ON [vysledky].[id_ucasti] = [ucasti].[id]
		 	LEFT JOIN [zavody] ON [ucasti].[id_zavodu] = [zavody].[id]
		 	LEFT JOIN [poradatele] ON [poradatele].[id_zavodu] = [zavody].[id]
		 	LEFT JOIN [sbory] ON [sbory].[id] = [poradatele].[id_sboru]
			LEFT JOIN [mista] ON [zavody].[id_mista] IS NOT NULL AND [zavody].[id_mista] = [mista].[id] OR [zavody].[id_mista] IS NULL AND [sbory].[id_mista] = [mista].[id]
			LEFT JOIN [terce] ON [zavody].[id_tercu] = [terce].[id]
			LEFT JOIN [typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]
			LEFT JOIN [druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]
			LEFT JOIN [kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]
			WHERE [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY, ' AND [zavody].[datum] < NOW() AND [vysledky].[id] IN %in
   			GROUP BY [mista].[id]
			ORDER BY [kategorie].[poradi], [typy_tercu].[poradi], [pocet] DESC
		');

		$prehled_vsechny = $prehled->fetchAll();
		if( count($prehled_vsechny) )
		{
			foreach( $prehled_vsechny as $id_kategorie => $tmp )
			{
				$nejlepsi = $this->connection->query('
					SELECT [vysledky].[id]
					FROM [vysledky]
					LEFT JOIN [ucasti] ON [vysledky].[id_ucasti] = [ucasti].[id]
					LEFT JOIN [zavody] ON [ucasti].[id_zavodu] = [zavody].[id]
					LEFT JOIN [druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]
					WHERE [druzstva].[id_kategorie] = %i', $tmp['id_kategorie'], ' AND [zavody].[id_tercu] = %i', $tmp['id_tercu'], ' AND [vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, '
					ORDER BY [vysledky].[vysledny_cas]
					LIMIT 100
				');
				$kompletni[] = '(';
				$kompletni = array_merge( $kompletni, $spolecne, array($nejlepsi->fetchAll()), array(')'));
				$kompletni[] = 'UNION';
			}
			array_pop($kompletni); // odstraní posledí "UNION"
			$kompletni[] = 'ORDER BY [kategorie_poradi], [typy_tercu_poradi], [pocet] DESC';
		}
		// poskládá dotaz: (nejlepší výsledek kategorie) UNION (nejlepší jiné kategorie)
		return $this->connection->query($kompletni);
	}

	public function nejrychlejsiDrahyByCas()
	{
		return $this->connection
			->select('AVG([vysledky].[vysledny_cas]) AS [prumer], GROUP_CONCAT([vysledky].[vysledny_cas]) AS [casy], COUNT([vysledky].[vysledny_cas]) AS [pocet_casu], [mista].[obec], [kategorie].[nazev] AS [kategorie], [typy_tercu].[nazev] AS [terce], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze]')
			->from('[vysledky]')
			->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->leftJoin('[sportoviste] ON [zavody].[id_mista] = [sportoviste].[id]')
			->leftJoin('[mista] ON [sportoviste].[id_mista] = [mista].[id]')
			->where('[vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, ' AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY)
			->groupBy('[souteze].[id], [zavody].[id_tercu], [druzstva].[id_kategorie], [mista].[id]')
			->orderBy('[souteze].[poradi], [kategorie].[poradi], [typy_tercu].[poradi], [prumer]');
	}

	/**
	 * Nalezne nejrychlejší časy pro každou soutěž, kategorii a terče
	 * @return DibiResult
	 */
	public function nejlepsiCasy()
	{
		$prehled = $this->connection
			->select('[druzstva].[id_kategorie], [terce].[id_typu], [ucasti].[id_souteze]')
			->from('[vysledky]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->groupBy('[ucasti].[id_souteze], [druzstva].[id_kategorie], [typy_tercu].[id]');
//print_r($prehled->fetchAll());
		$spolecne = array('
			SELECT [vysledky].[id], CONCAT( [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ",[mista].[obec], " ", [druzstva].[poddruzstvo] ) AS [druzstvo], [rocniky].[rok], [vysledky].[vysledny_cas], [typy_tercu].[nazev] AS [typ], [kategorie].[nazev] AS [kategorie], [vysledky].[id_druzstva], [kategorie].[poradi] AS [kategorie_poradi], [typy_tercu].[poradi] AS [typy_tercu_poradi], [zavody].[id] AS [id_zavodu], [zavody].[datum], [mista_poradatel].[obec], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze], [souteze].[poradi] AS [souteze_poradi]
			FROM [vysledky]
		 	LEFT JOIN [ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]
			LEFT JOIN [zavody] ON [ucasti].[id_zavodu] = [zavody].[id]
			LEFT JOIN [souteze] ON [souteze].[id] = [ucasti].[id_souteze]
			LEFT JOIN [rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]
			LEFT JOIN [poradatele] ON [poradatele].[id_zavodu] = [zavody].[id]
			LEFT JOIN [sbory] [poradatel] ON [poradatel].[id] = [poradatele].[id_sboru]
			LEFT JOIN [mista] [mista_poradatel] ON [mista_poradatel].[id] = [poradatel].[id_mista]

			LEFT JOIN [druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]
			LEFT JOIN [sbory] ON [sbory].[id] = [druzstva].[id_sboru]
			LEFT JOIN [mista] ON [sbory].[id_mista] = [mista].[id]
			LEFT JOIN [typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]
			LEFT JOIN [terce] ON [zavody].[id_tercu] = [terce].[id]
			LEFT JOIN [typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]
   			LEFT JOIN [kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]
			WHERE [zavody].[datum] < NOW() AND [vysledky].[id] IN %in
			ORDER BY [pocet] DESC
		');

		foreach( $prehled->fetchAll() as $id_kategorie => $tmp )
		{
			$nejlepsi = $this->connection
				->select('[vysledky].[id]')
				->from($this->table)
				->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
				->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
				->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
				->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
				->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
				->where('[ucasti].[id_souteze] = %i', $tmp['id_souteze'], 'AND [druzstva].[id_kategorie] = %i', $tmp['id_kategorie'], ' AND [terce].[id_typu] = %i', $tmp['id_typu'], ' AND [vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, 'AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY)
				->orderBy('[vysledky].[vysledny_cas]')
				->limit(100)
				->execute();

			$kompletni[] = '(';
			$kompletni = array_merge( $kompletni, $spolecne, array($nejlepsi->fetchAll()), array(')'));
			$kompletni[] = 'UNION';
		}
		array_pop($kompletni); // odstraní posledí "UNION"
		$kompletni[] = 'ORDER BY [souteze_poradi], [kategorie_poradi], [typy_tercu_poradi], [vysledny_cas] ASC';

		return $this->connection->query($kompletni);
	}

	public function nejviceBodovanaDruzstva()
	{
		return $this->connection
			->select('[druzstva].[id], [druzstva].[id] AS [id_druzstva], CONCAT_WS( " ", [typy_sboru].[zkratka], [mista].[obec], [druzstva].[poddruzstvo] ) AS [druzstvo], [kategorie].[nazev] AS [kategorie], SUM([vysledky].[body]) AS [celkem_bodu], COUNT([zavody].[id]) AS [celkem_zavodu], AVG([vysledky].[body]) AS [prumer], GROUP_CONCAT([vysledky].[body]) AS [body], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze]')
			->from('[vysledky]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
   			->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->leftJoin('[mista] ON [sbory].[id_mista] = [mista].[id]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->where('[zavody].[datum] < NOW()  AND [vysledky].[platne_body] = %i', self::POUZE_PLATNE_BODY)
			->groupBy('[ucasti].[id_souteze], [ucasti].[id_kategorie], [vysledky].[id_druzstva]')
			->orderBy('[souteze].[poradi], [kategorie].[poradi], [celkem_bodu] DESC');
	}

	public function prumerneCasySezon()
	{
		return $this->connection
			->select('[rocniky].[id], [rocniky].[rok], [typy_tercu].[nazev] AS [typ], [kategorie].[nazev] AS [kategorie], [vysledny_cas], GROUP_CONCAT([vysledky].[vysledny_cas]) AS [casy], AVG([vysledky].[vysledny_cas]) AS [prumer], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze]')
			->from($this->table)
			->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->where('[vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, ' AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY)
			->groupBy('[rocniky].[id], [typy_tercu].[id], [kategorie].[id], [souteze].[id]')
			->orderBy('[rocniky].[rok] DESC, [kategorie].[poradi], [typy_tercu].[poradi]');
	}

	/**
	 * Nalezne průměrné a minimální časy sezón pro všechny sezóny jednoho družstva
	 * @param int $id družstva
	 * @return DibiFluent
	 */
	public function vyznacneCasySezonDruzstva($id)
	{
		return $this->connection->select('[rocniky].[id], [rocniky].[rok], [typy_tercu].[nazev] AS [typ], [kategorie].[nazev] AS [kategorie], [vysledny_cas], GROUP_CONCAT([vysledky].[vysledny_cas]) AS [casy], AVG([vysledky].[vysledny_cas]) AS [prumer], MIN([vysledky].[vysledny_cas]) AS [rekord], [ucasti].[id_souteze], [souteze].[nazev] AS [soutez]')
			->from('[vysledky]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->leftJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->where('[vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, ' AND [druzstva].[id] = %i', $id, ' AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY)
			->groupBy('[rocniky].[id], [souteze].[id], [typy_tercu].[id], [kategorie].[id]')
			->orderBy('[rocniky].[rok]');
	}

	/**
	 * Nalezne nejlepší časy družstva vůbec
	 * @param int $id družstva
	 * @return array
	 */
	public function rekordyDruzstva($id)
	{
		$minima = $this->connection->select('MIN([vysledky].[vysledny_cas]) AS [rekord]')
			->from('[vysledky]')
			->leftJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->where('[vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, ' AND [druzstva].[id] = %i', $id, ' AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY)
			->groupBy('[ucasti].[id_souteze], [terce].[id_typu]');

		$return = array();
		foreach($minima->fetchAll() as $minimum)
		{
			$return[] = $this->connection->select('[rocniky].[id], [rocniky].[rok], [typy_tercu].[nazev] AS [terce], [vysledky].[vysledny_cas] AS [rekord], [zavody].[id] AS [id_zavodu], [mista_poradatele].[obec] AS [zavod], [zavody].[datum], [souteze].[nazev] AS [soutez], [ucasti].[id_souteze], [kategorie].[nazev] AS [kategorie], [kategorie].[id] AS [id_kategorie], [vysledky].[id_druzstva], [zavody].[id_tercu]')
				->from('[vysledky]')
				->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
				->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
				->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
				->leftJoin('[rocniky] ON [rocniky].[id] = [zavody].[id_rocniku]')
				->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
				->rightJoin('[typy_tercu] ON [typy_tercu].[id] = [terce].[id_typu]')
				->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
				->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
				->leftJoin('[poradatele] ON [poradatele].[id_zavodu] = [ucasti].[id_zavodu]')
				->leftJoin('[sbory] [poradatel] ON [poradatel].[id] = [poradatele].[id_sboru]')
				->leftJoin('[mista] [mista_poradatele] ON [mista_poradatele].[id] = [poradatel].[id_mista]')
				->where('[vysledky].[vysledny_cas] < %i', self::HRANICE_PLATNYCH_CASU, ' AND [druzstva].[id] = %i', $id, ' AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY, 'AND [vysledky].[vysledny_cas] = '.$minimum['rekord']); // %f je pro float, potřebuji decimal
		}
		if( count($return) ) return $this->connection->query('('.implode(') UNION (', $return).')');
		else return $minima;
	}

	public function dosavadniRekordyZavodu($id, $id_druzstva = NULL)
	{
		$maxima = $this->connection
			->select('[ucasti].[id_souteze], [druzstva].[id_kategorie], MIN([vysledky].[vysledny_cas]) AS [vysledny_cas]')
			->from('[vysledky]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[poradatele] ON [zavody].[id] = [poradatele].[id_zavodu]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[mista] [mista_druzstva] ON [mista_druzstva].[id] = [sbory].[id_mista]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->where('[poradatele].[id_sboru] IN (SELECT [id_sboru] FROM [poradatele] WHERE [poradatele].[id_zavodu] = %i)', $id, ' AND [zavody].[datum] < (SELECT [datum] FROM [zavody] WHERE [zavody].[id] = %i)', $id, 'AND [terce].[id_typu] = (SELECT [terce].[id_typu] FROM [zavody] LEFT JOIN [terce] ON [terce].[id] = [zavody].[id_tercu] WHERE [zavody].[id] = %i)', $id, 'AND [souteze].[id] IN (SELECT [ucasti].[id_souteze] FROM [ucasti] WHERE [id_zavodu] = %i)', $id)
			->groupBy('[ucasti].[id_souteze], [druzstva].[id_kategorie]')
			->orderBy('[souteze].[poradi], [kategorie].[poradi]');
		if( $id_druzstva != NULL ) $maxima->where('[druzstva].[id] = %i', $id_druzstva);
		$maxima_ = $maxima->fetchAll();

		if(!count($maxima_)) return $maxima;

		$prikaz = '(';
		$i = 0;
		foreach($maxima_ as $maximum)
		{
			$i++;
			$maximum_ = $this->connection
				->select('[druzstva].[id], [ucasti].[id_souteze], CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista_druzstva].[obec], [kategorie].[nazev], [druzstva].[poddruzstvo]) AS [druzstvo], [vysledky].[vysledny_cas], [zavody].[id] AS [id_zavodu], [mista_poradatele].[obec] AS [zavod], [zavody].[datum], [vysledky].[id], [souteze].[nazev] AS [soutez], [kategorie].[nazev] AS [kategorie]')
				->from('[vysledky]')
				->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
				->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
				->leftJoin('[poradatele] ON [zavody].[id] = [poradatele].[id_zavodu]')
				->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
				->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
				->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
				->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
				->leftJoin('[mista] [mista_druzstva] ON [mista_druzstva].[id] = [sbory].[id_mista]')
				->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
				->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
				->leftJoin('[sbory] [poradatel] ON [poradatel].[id] = [poradatele].[id_sboru]')
				->leftJoin('[mista] [mista_poradatele] ON [mista_poradatele].[id] = [poradatel].[id_mista]')
				->where('[poradatele].[id_sboru] IN (SELECT [id_sboru] FROM [poradatele] WHERE [poradatele].[id_zavodu] = %i)', $id, ' AND [terce].[id_typu] = (SELECT [terce].[id_typu] FROM [zavody] LEFT JOIN [terce] ON [terce].[id] = [zavody].[id_tercu] WHERE [zavody].[id] = %i)', $id, ' AND [vysledky].[vysledny_cas] = '.$maximum['vysledny_cas'].' AND [druzstva].[id_kategorie] = %i', $maximum['id_kategorie'], ' AND [ucasti].[id_souteze] = %i', $maximum['id_souteze']);
			if( $id_druzstva != NULL ) $maximum_->where('[druzstva].[id] = %i', $id_druzstva);
			$prikaz .= (string)$maximum_;
			if( $i<count($maxima_) ) $prikaz .= ') UNION (';
		}
		$prikaz .= ')';
		return $this->connection->query($prikaz);
	}

	/**
	 * Vrátí aktuální platné rekordy závodu.
	 * @param type $id ID závodu.
	 * @param type $id_druzstva ID družstva, pro které se mají rekordy závodu nelézt, nebo NULL.
	 * @return type
	 */
	public function rekordyZavodu($id, $id_druzstva = NULL)
	{
		$maxima = $this->connection
			->select('[ucasti].[id_souteze], [druzstva].[id_kategorie], MIN([vysledky].[vysledny_cas]) AS [vysledny_cas]')
			->from('[vysledky]')
			->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
			->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
			->leftJoin('[poradatele] ON [zavody].[id] = [poradatele].[id_zavodu]')
			->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
			->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
			->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[mista] [mista_druzstva] ON [mista_druzstva].[id] = [sbory].[id_mista]')
			->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
			->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
			->where('[poradatele].[id_sboru] IN (SELECT [id_sboru] FROM [poradatele] WHERE [poradatele].[id_zavodu] = %i)', $id, ' AND [terce].[id_typu] = (SELECT [terce].[id_typu] FROM [zavody] LEFT JOIN [terce] ON [terce].[id] = [zavody].[id_tercu] WHERE [zavody].[id] = %i)', $id, ' AND [vysledky].[platne_casy] = %i', self::POUZE_PLATNE_CASY, 'AND [souteze].[id] IN (SELECT [ucasti].[id_souteze] FROM [ucasti] WHERE [id_zavodu] = %i)', $id)
			->groupBy('[ucasti].[id_souteze], [druzstva].[id_kategorie]')
			->orderBy('[souteze].[poradi], [kategorie].[poradi]');
		if( $id_druzstva != NULL ) $maxima->where('[druzstva].[id] = %i', $id_druzstva);
		$maxima_ = $maxima->fetchAll();

		if(!count($maxima_)) return $maxima;

		$prikaz = '(';
		$i = 0;
		foreach($maxima_ as $maximum)
		{
			$i++;
			$maximum_ = $this->connection
				->select('[druzstva].[id], [ucasti].[id_souteze], CONCAT_WS(" ", [typy_sboru].[zkratka], [sbory].[privlastek], [mista_druzstva].[obec], [kategorie].[nazev], [druzstva].[poddruzstvo]) AS [druzstvo], [vysledky].[vysledny_cas], [zavody].[id] AS [id_zavodu], [mista_poradatele].[obec] AS [zavod], [zavody].[datum], [vysledky].[id], [souteze].[nazev] AS [soutez], [kategorie].[nazev] AS [kategorie]')
				->from('[vysledky]')
				->rightJoin('[ucasti] ON [ucasti].[id] = [vysledky].[id_ucasti]')
				->leftJoin('[zavody] ON [zavody].[id] = [ucasti].[id_zavodu]')
				->leftJoin('[poradatele] ON [zavody].[id] = [poradatele].[id_zavodu]')
				->leftJoin('[souteze] ON [souteze].[id] = [ucasti].[id_souteze]')
				->leftJoin('[druzstva] ON [druzstva].[id] = [vysledky].[id_druzstva]')
				->leftJoin('[sbory] ON [sbory].[id] = [druzstva].[id_sboru]')
				->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
				->leftJoin('[mista] [mista_druzstva] ON [mista_druzstva].[id] = [sbory].[id_mista]')
				->leftJoin('[kategorie] ON [kategorie].[id] = [druzstva].[id_kategorie]')
				->leftJoin('[terce] ON [terce].[id] = [zavody].[id_tercu]')
				->leftJoin('[sbory] [poradatel] ON [poradatel].[id] = [poradatele].[id_sboru]')
				->leftJoin('[mista] [mista_poradatele] ON [mista_poradatele].[id] = [poradatel].[id_mista]')
				->where('[poradatele].[id_sboru] IN (SELECT [id_sboru] FROM [poradatele] WHERE [poradatele].[id_zavodu] = %i)', $id, ' AND [terce].[id_typu] = (SELECT [terce].[id_typu] FROM [zavody] LEFT JOIN [terce] ON [terce].[id] = [zavody].[id_tercu] WHERE [zavody].[id] = %i)', $id, ' AND [vysledky].[vysledny_cas] = '.$maximum['vysledny_cas'].' AND [druzstva].[id_kategorie] = %i', $maximum['id_kategorie'], ' AND [ucasti].[id_souteze] = %i', $maximum['id_souteze']);
			if( $id_druzstva != NULL ) $maximum_->where('[druzstva].[id] = %i', $id_druzstva);
			$prikaz .= (string)$maximum_;
			if( $i<count($maxima_) ) $prikaz .= ') UNION (';
		}
		$prikaz .= ')';
		return $this->connection->query($prikaz);
	}

	/**
	 * Porovnávací funkce pro celkové bodování ligy
	 * Porovnává lepší umístění v průběhu sezóny
	 * @param array $a porovnávané družstvo
	 * @param array $b porovnávané družstvo
	 * @return int -1, pokud má první víc bodů, 0, pokud stejně a 1 pokud má druhý víc bodů
	 */
	public function orderVysledky(&$a, &$b)
	{
		if( $a['celkem_bodu'] > $b['celkem_bodu'] ) return -1;
		elseif( $a['celkem_bodu'] < $b['celkem_bodu'] ) return 1;
		else
		{
			// obě družstva mají stejně bodů, rozhodne se podle počtu lepších umístění v sezóně
			// kdo má víc lepších pozic, vyhrává

			// nalezne všechna umístění týmů v sezóně
			$umisteni = $this->porovnejDruzstva($a, $b)->fetchAll();

			$a_umisteni = array();
			$b_umisteni = array();

			$nejhorsiUmisteni = 0;
			foreach( $umisteni as $misto )
			{
				$misto->a_umisteni = intval($misto->a_umisteni);
				$misto->b_umisteni = intval($misto->b_umisteni);

				if( $nejhorsiUmisteni < $misto->a_umisteni ) $nejhorsiUmisteni = $misto->a_umisteni;
				if( $nejhorsiUmisteni < $misto->b_umisteni ) $nejhorsiUmisteni = $misto->b_umisteni;

				if( !isset($a_umisteni[$misto->a_umisteni]) ) $a_umisteni[$misto->a_umisteni] = 1;
				else $a_umisteni[$misto->a_umisteni]++;

				if( !isset($b_umisteni[$misto->b_umisteni]) ) $b_umisteni[$misto->b_umisteni] = 1;
				else $b_umisteni[$misto->b_umisteni]++;
			}
			$prubeh_umisteni_a = array();
			$prubeh_umisteni_b = array();

			for( $i = 1; $i<($nejhorsiUmisteni+1); $i++ )
			{
				if(isset($a_umisteni[$i])) for($j=0; $j<$a_umisteni[$i]; $j++) $prubeh_umisteni_a[] = $i;

				if(isset($b_umisteni[$i])) for($j=0; $j<$b_umisteni[$i]; $j++) $prubeh_umisteni_b[] = $i;
			}
			$a['prubeh'] = $prubeh_umisteni_a;
			$b['prubeh'] = $prubeh_umisteni_b;
			$b_temp = $b; unset($b_temp['shoda']); unset($b_temp['lepsi']); unset($b_temp['horsi']);
			$a_temp = $a; unset($a_temp['shoda']); unset($a_temp['lepsi']); unset($a_temp['horsi']);
			for( $i = 1; $i<($nejhorsiUmisteni+1); $i++ )
			{
				if(!isset($a_umisteni[$i]) ) $a_umisteni[$i] = 0;
				if(!isset($b_umisteni[$i]) ) $b_umisteni[$i] = 0;

				if($a_umisteni[$i] > $b_umisteni[$i]) { $b_temp['pocet'] = $a_umisteni[$i]; $b_temp['rozhodujici'] = $i; $a_temp['rozhodujici'] = $i; $a_temp['pocet'] = $b_umisteni[$i]; $a['lepsi'][] = $b_temp; $b['horsi'][] = $a_temp; return -1; }
				if($a_umisteni[$i] < $b_umisteni[$i]) { $a_temp['pocet'] = $b_umisteni[$i]; $b_temp['rozhodujici'] = $i; $a_temp['rozhodujici'] = $i; $b_temp['pocet'] = $a_umisteni[$i]; $b['lepsi'][] = $a_temp; $a['horsi'][] = $b_temp; return 1; }
			}
			$a['shoda'][] = $b_temp;
			$b['shoda'][] = $a_temp;
			return strcmp($a['druzstvo'], $b['druzstvo']);
		}
	}

	/**
	 * Vyhodnotí celkové body týmů ve všech soutěžích ze všech závodů jednoho ročníku podle pravidel daných pro bodování ligy.
	 * @param $vysledky Associativní pole indexované podle (soutez,kategorie,id_druzstva).
	 */
	public function vyhodnotVysledkyRocniku(&$vysledky)
	{
		// projde všechny soutěže
		foreach( $vysledky as $soutez => $foo )
		{
			// projde všechny kategorie
			foreach( $foo as $kategorie => $bar )
			{
				// seřadí týmy vrámci kategorie
				// bere v potaz shodu bodů, ale neurčuje nové pořadí, pouze řadí
				usort($vysledky[$soutez][$kategorie], array($this, "orderVysledky"));
			}
		}

		// projde seřazené výsledky a určí nové pořadí podle bodů
		// při shodných umístěních nastavuje stejné pořadí
		foreach( $vysledky as $soutez => $foobar )
		{
			foreach( $foobar as $kategorie => $foo )
			{
				$i = 0;
				$krok = 1;
				$bylaShoda = false;
				foreach( $foo as $vysledkyKategorie => $bar )
				{
					if( !empty($bar['shoda']) )
					{
						foreach( $bar['shoda'] as $shodne )
						{
							if( $shodne['id_druzstva'] == $predchoziDruzstvo['id_druzstva'] ) { $bylaShoda = true; break; }
						}
					}
					if( $bylaShoda == true ) { $vysledky[$soutez][$kategorie][$vysledkyKategorie]['poradi'] = $i; $krok++; $bylaShoda = false; }
					else { $vysledky[$soutez][$kategorie][$vysledkyKategorie]['poradi'] = $i+$krok; $i+=$krok; $krok=1; }
					$predchoziDruzstvo = $bar;
				}
			}
		}
		$return = array();
		foreach( $vysledky as $soutez => $foobar )
		{
			foreach( $foobar as $kategorie => $foo )
			{
				$i = 0;
				$krok = 1;
				$bylaShoda = false;
				foreach( $foo as $vysledkyKategorie => $bar )
				{
					$return[$soutez][$kategorie][$bar['id_druzstva']] = $bar;
				}
			}
		}
		$vysledky = $return;
		unset($vysledky);
	}

	public function deleteByZavod($id)
	{
		return parent::delete(NULL)->clause('where', true)->where('id_zavodu = %i', $id)->execute();
	}

	private function pripravData($data)
	{
		//if( empty($data['lepsi_cas']) ) { $data['lepsi_terc%in'] = 0; unset($data['lepsi_terc']); }

		//if( empty($data['lepsi_cas']) ) { $data['lepsi_cas%in'] = 0; unset($data['lepsi_cas']); }
		//else { $data['lepsi_cas%f'] = $data['lepsi_cas']; unset($data['lepsi_cas']); }
		if( !empty($data['lepsi_cas']) ) { $data['lepsi_cas%f'] = $data['lepsi_cas']; unset($data['lepsi_cas']); }

		return $data;
	}

	public function insert(array $data)
	{
		try
		{
			$ret = parent::insert($this->pripravData($data))->execute(dibi::IDENTIFIER);
			$id = $this->connection->insertId();
			$this->lastInsertedId($id);
			return $ret;
		}
		catch(DibiException $e)
		{
			if( $e->getCode() == 1062 ) throw new AlreadyExistException($e->getMessage(), $e->getCode(), $e);
			else throw $e;
		}
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

	public function update($id, array $data)
	{
		return parent::update($id, $this->pripravData($data))->execute();
	}

}
