<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
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

	public function __construct(\DibiConnection $connection)
	{
		$this->connection = $connection;
	}

	public function findNezverejnene()
	{
		return $this->findAll()->where(false)->where('[clanky].[datum_zverejneni] IS NULL OR [clanky].[datum_zverejneni] > NOW()');
	}

	public function findAll()
	{
		$dotaz = $this->connection->select('[clanky].*, [kategorie_clanku].[cssstyl], ' . Uzivatele::$_UZIVATEL . ' AS [autor], COUNT([komentare].[id]) AS [pocet_komentaru]')
				->from($this->table)
				->leftJoin('[uzivatele] ON [uzivatele].[id] = [clanky].[id_autora]')
				->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
				->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
				->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
				->leftJoin('[souvisejici] [souvisejici] ON [souvisejici].[rodic] = "clanky" AND [souvisejici].[id_rodice] = [clanky].[id] AND [souvisejici].[souvisejici] = "diskuze"')
				->leftJoin('[diskuze] ON [diskuze].[id] = [souvisejici].[id_souvisejiciho]')
				->leftJoin('[komentare] ON [komentare].[id_diskuze] = [diskuze].[id]')
				->leftJoin('[kategorie_clanku] ON [kategorie_clanku].[id] = [clanky].[id_kategorie]')
				->groupBy('[clanky].[datum_zverejneni] DESC');
		//->groupBy('[clanky].[id]')
		//->orderBy('[datum_zverejneni] DESC');
		if($this->zverejnene == 1) $dotaz->where('[clanky].[datum_zverejneni] IS NOT NULL AND [clanky].[datum_zverejneni] <= NOW()');

		return $dotaz;
	}

	public function findAllToSelect()
	{
		return $this->findAll();
	}

	public function find($id)
	{
		$dotaz = $this->findAll()->where('[clanky].[id] = %i', $id);
		if($this->zverejnene == 1) $dotaz->where('[clanky].[datum_zverejneni] IS NOT NULL');
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

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = Nette\Environment::getService('urls');
		$urlsModel->setUrl('Clanky', 'clanek', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		$return = parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		$urlsModel = Nette\Environment::getService('urls');
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
		$diskuzeModel = Nette\Environment::getService('diskuze');
		if($force == false || $force == true)
		{
			if($diskuzeModel->findByClanek($id)->count() != 0) throw new RestrictionException('Nelze smazat článek, jsou k němu komentáře.');
		}
		return parent::delete($id, $force)->execute();
	}

	public function udrzba()
	{
		$vsechnyClanky = $this->findAll();
		foreach ($vsechnyClanky as $data)
		{
			$dataDoDB = array('nazev' => $data['nazev'], 'text' => $data['text'], 'id_kategorie' => (int) $data['id_kategorie'], 'stare_uri%s' => 'view.php?cisloclanku=' . $data['stary_link']);
			$this->update($data['id'], $dataDoDB);
		}
	}

	private function constructUri($id, array $data)
	{
		if(isset($data['nazev']) && isset($id))
		{
			$data['uri'] = '/clanky/' . $id . '-' . Texy::webalize($data['nazev']);
		}
		return $data;
	}

	/**
	 * Nalezne šablony článků pro jeden článek
	 * @param int $id ID článku
	 * @return DibiResult
	 */
	public function findSablony($id)
	{
		$sablonyClankuModel = Nette\Environment::getService('sablonyClanku');
		return $sablonyClankuModel->findByClanek($id);
	}

	public function pridejSablonu($id, $id_sablony)
	{
		$this->connection->insert('clanky_sablony', array('id_clanku%i' => $id, 'id_sablony%i' => $id_sablony))->execute();
	}

	public function odeberSablonu($id, $id_sablony)
	{
		$this->connection->delete('clanky_sablony')->where(array('id_clanku%i' => $id, 'id_sablony%i' => $id_sablony))->execute();
	}

}
