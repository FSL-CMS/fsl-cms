<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model článků
 *
 * @author	Milan Pála
 */
class Clanky extends Zverejnovane implements IUdrzba
{

	/** @var string */
	protected $table = 'clanky';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findNezverejnene()
	{
		return $this->findAll()->where(false)->where('[clanky].[datum_zverejneni] IS NULL OR [clanky].[datum_zverejneni] > NOW()');
	}

	public function findAll()
	{
		$dotaz = $this->connection->select('[clanky].*, [kategorie_clanku].[cssstyl], CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[zkratka], " ", [mista].[obec]) AS [autor], COUNT([komentare].[id]) AS [pocet_komentaru]')
			->from($this->table)
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [clanky].[id_autora]')
               ->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
               ->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[souvisejici] [souvisejici] ON [souvisejici].[rodic] = "clanky" AND [souvisejici].[id_rodice] = [clanky].[id] AND [souvisejici].[souvisejici] = "diskuze"')
               ->leftJoin('[diskuze] ON [diskuze].[id] = [souvisejici].[id_souvisejiciho]')
			->leftJoin('[komentare] ON [komentare].[id_diskuze] = [diskuze].[id]')
			->leftJoin('[kategorie_clanku] ON [kategorie_clanku].[id] = [clanky].[id_kategorie]')
			->groupBy('[clanky].[id]')
			->orderBy('[datum_zverejneni] DESC');
		if( $this->zverejnene == 1 ) $dotaz->where('[clanky].[datum_zverejneni] IS NOT NULL AND [clanky].[datum_zverejneni] <= NOW()');

		return $dotaz;
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function find($id)
	{
		$dotaz = $this->findAll()->where('[clanky].[id] = %i', $id);
		if( $this->zverejnene == 1 ) $dotaz->where('[clanky].[datum_zverejneni] IS NOT NULL');
		return $dotaz;
	}

	public function findIdByUri($uri, $column = 'uri')
	{
		return $this->connection
			->select('id')
			->from($this->table)
			->where('%n = %s', $column, $uri);
	}

	public function findByKategorie($id)
	{
		return $this->findAll()->where('[clanky].[id_kategorie] = %i', $id);
	}

	public function findByAutor($id)
	{
		return $this->findAll()->where('[clanky].[id_autora] = %i', $id);
	}

	public function prehledKategorii()
	{
		return $this->connection
			->select('[kategorie_clanku].[id], [kategorie_clanku].[nazev] AS [text], COUNT([clanky].[id]) AS [pocet_clanku]')
			->from('[kategorie_clanku]')
			->leftJoin('[clanky] ON [kategorie_clanku].[id] = [clanky].[id_kategorie] AND [clanky].[datum_zverejneni] IS NOT NULL')
			->groupBy('[kategorie_clanku].[id]')
			->orderBy('[kategorie_clanku].[poradi]');
	}

	public function findKategorie()
	{
		return $this->connection
			->select('[kategorie_clanku].[id], [kategorie_clanku].[nazev]')
			->from('[kategorie_clanku]')
			->orderBy('[poradi]');
	}

	public function findKategorii($id)
	{
		return $this->findKategorie()->where('[kategorie_clanku].[id] = %i', $id);
	}

	public function deleteSouvisejiciZavody($data)
	{
		$smazat = array();
		foreach( $data as $foo ) $smazat[] = '[id_clanku] = '.$foo['id_clanku'].' AND [id_zavodu] = '.$foo['id_zavodu'];
		return $this->connection->delete('clanky_zavody')->where('%or', $smazat)->execute();
	}

	public function insertSouvisejiciZavody($data)
	{
		$vlozit = array();
		foreach( $data as $foo ) $vlozit[] = '('.$foo['id_clanku'].', '.$foo['id_zavodu'].')';
		return $this->connection->command()->insert()->into('clanky_zavody', '([id_clanku], [id_zavodu])')->values('%sql', implode(', ', $vlozit))->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Clanky', 'clanek', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		$return = parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Clanky', 'clanek', $id, $data['uri']);
		return $return;
	}

	public function precteno($id)
	{
		$data = array('pocet_cteni%sql' => '[pocet_cteni]+1');
		return parent::update($id, $data)->execute();
	}

	public function delete($id, $force = 0)
	{
		$diskuzeModel = new Diskuze;
		if( $force == false || $force == true )
		{
			if( $diskuzeModel->findByClanek($id)->count() != 0 ) throw new RestrictionException('Nelze smazat článek, jsou k němu komentáře.');
		}
		return parent::delete($id, $force)->execute();
	}

	public function udrzba()
	{
		$vsechnyClanky = $this->findAll();
		foreach( $vsechnyClanky as $data )
		{
			$dataDoDB = array( 'nazev' => $data['nazev'], 'text' => $data['text'], 'id_kategorie' => (int)$data['id_kategorie'], 'stare_uri%s' => 'view.php?cisloclanku='.$data['stary_link'] );
			$this->update($data['id'], $dataDoDB);
		}
	}

	private function constructUri($id, array $data)
	{
		if(isset($data['nazev']) && isset($id))
		{
			$data['uri'] = '/clanky/'.$id.'-'.Texy::webalize($data['nazev']);
		}
		return $data;
	}
}
