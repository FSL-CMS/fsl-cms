<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model diskuzí
 *
 * @author	Milan Pála
 */
class Diskuze extends BaseModel implements IUdrzba
{

	/** @var string */
	protected $table = 'diskuze';
	private static $DNY_AKTIVNI_DISKUZE = 7;

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	protected function findBy()
	{
		return $this->connection
					 ->select('[komentare].[id], [diskuze].[id] AS [id_diskuze], [diskuze].[nazev] AS [tema_diskuze], [diskuze].[zamknuto], [komentare].[id] AS [id_komentare], [komentare].[text], [komentare].[datum_pridani], '.Uzivatele::$_UZIVATEL.' AS [autor], [komentare].[id_autora], [diskuze].[id_autora] AS [id_autora_diskuze], [diskuze].[id_tematu], [temata].[nazev] AS [tema], [temata].[souvisejici] AS [souvisejiciTabulka]')
					 ->from('[diskuze]')
					 ->leftJoin('[komentare] ON [komentare].[id_diskuze] = [diskuze].[id]')
					 ->leftJoin('[temata] ON [temata].[id] = [diskuze].[id_tematu]')

					 // vybere autora komentáře
					 ->leftJoin('[uzivatele] ON [uzivatele].[id] = [komentare].[id_autora]')
					 ->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
					 ->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
					 ->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
					 ->orderBy('[komentare].[datum_pridani]');
	}

	public function findByAll()
	{
		return $this->findBy()
					 ->select('COUNT([komentare].[id]) AS [pocet_komentaru]')
					 ->groupBy('[diskuze].[id]')
					 ->orderBy(false)->orderBy('[komentare].[datum_pridani] DESC');
	}

	public function findAll()
	{
		return $this->connection
					 ->select('[diskuze].[id], [diskuze].[nazev], [diskuze].[zamknuto], '.Uzivatele::$_UZIVATEL.' AS [autor], [diskuze].[id_autora], [diskuze].[id_tematu], [temata].[nazev] AS [tema], [temata].[souvisejici] AS [souvisejiciTabulka], [diskuze].[uri]')
					 ->from('[diskuze]')
					 ->leftJoin('[temata] ON [temata].[id] = [diskuze].[id_tematu]')

					 // vybere autora komentáře
					 ->leftJoin('[uzivatele] ON [uzivatele].[id] = [diskuze].[id_autora]')
					 ->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
					 ->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
					 ->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]');
	}

	private function najdiSouvisejiciDiskuze($souvisejiciTabulka, $id)
	{
		$souvisejici = new Souvisejici;
		$souvisejiciDiskuze = $souvisejici->findByRodic($souvisejiciTabulka, $id, 'diskuze')->fetchAll();
		$souvisejiciID = array();
		foreach ($souvisejiciDiskuze as $souv)
		{
			$souvisejiciID[] = $souv['id_souvisejiciho'];
		}
		return $souvisejiciID;
	}

	public function findByZavod($id)
	{
		$souvisejiciID = $this->najdiSouvisejiciDiskuze('zavody', $id);
		return $this->findBy()->where('[diskuze].[id] IN %in', $souvisejiciID);
	}

	public function findByClanek($id)
	{
		$souvisejiciID = $this->najdiSouvisejiciDiskuze('clanky', $id);
		return $this->findBy()->where('[diskuze].[id] IN %in', $souvisejiciID);
	}

	public function findByTerce($id)
	{
		$souvisejiciID = $this->najdiSouvisejiciDiskuze('terce', $id);
		return $this->findBy()->where('[diskuze].[id] IN %in', $souvisejiciID);
	}

	public function findByDruzstvo($id)
	{
		$souvisejiciID = $this->najdiSouvisejiciDiskuze('druzstva', $id);
		return $this->findBy()->where('[diskuze].[id] IN %in', $souvisejiciID);
	}

	public function findBySbor($id)
	{
		$souvisejiciID = $this->najdiSouvisejiciDiskuze('sbory', $id);
		return $this->findBy()->where('[diskuze].[id] IN %in', $souvisejiciID);
	}

	public function find($id)
	{
		return $this
					 ->findBy()
					 ->where('[diskuze].[id] = %i', $id);
	}

	public function prehledTemat()
	{
		return $this->connection
					 ->select('[temata].[id], [temata].[nazev], COUNT([diskuze].[id]) AS [pocet_diskuzi], [temata].[souvisejici] AS [souvisejiciTabulka]')
					 ->from('[temata]')
					 ->leftJoin('[diskuze] ON [diskuze].[id_tematu] = [temata].[id]')
					 ->groupBy('[temata].[id]')
					 ->orderBy('[temata].[poradi]');
	}

	public function findTema($id)
	{
		return $this->prehledTemat()->where('[temata].[id] = %i', $id);
	}

	public function findTemaBySouvisejici($id)
	{
		return $this->prehledTemat()->where('[temata].[souvisejici] = %s', $id);
	}

	public function findByTema($id)
	{
		return $this->findBy()
					 ->select('COUNT([komentare].[id]) AS [pocet_komentaru]')
					 ->where('[diskuze].[id_tematu] = %i', $id)
					 ->groupBy('[diskuze].[id]');
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute();
		$id = $this->connection->getInsertId();
		if($id == 0) throw new DibiException('Nebyl vložený záznam.');
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Diskuze', 'diskuze', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		$ret = parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		if(isset($data['uri']))
		{
			$urlsModel = new Urls;
			$urlsModel->setUrl('Diskuze', 'diskuze', $id, $data['uri']);
		}
		return $ret;
	}

	public function delete($id, $force = 0)
	{
		if($force == 1)
		{
			$komentare = new Komentare;
			$komentare->deleteByDiskuze($id);

			$sledovani = new Sledovani;
			$sledovani->deleteByDiskuze($id);
		}
		else
		{
			$komentare = new Komentare;
			if($komentare->findByDiskuze($id)->count() != 0) throw new RestrictionException('Diskuzi nelze odstranit, obsahuje komentáře.');
		}
		return parent::delete($id)->execute();
	}

	public function deleteByTema($id, $force = 0)
	{
		$diskuze = $this->findByTema($id);
		$ret = NULL;
		foreach ($diskuze as $disk)
		{
			$ret = $this->delete($disk['id_diskuze'], $force);
		}
		return $ret;
	}

	public function zamknout($id)
	{
		return $this->connection->update('diskuze', array('zamknuto' => 1))->where('id = %i', $id)->execute();
	}

	public function odemknout($id)
	{
		return $this->connection->update('diskuze', array('zamknuto' => 0))->where('id = %i', $id)->execute();
	}

	public function findAktivni()
	{
		return $this->findBy()->where('DATEDIFF(NOW(), [komentare].[datum_pridani]) < %i', self::$DNY_AKTIVNI_DISKUZE)->orderBy(false)->orderBy('[datum_pridani] DESC');
	}

	public function sledovat($id, $id_uzivatele)
	{
		$sledovani = new Sledovani;
		return $sledovani->sledovat("diskuze", $id, $id_uzivatele);
	}

	public function nesledovat($id, $id_uzivatele)
	{
		$sledovani = new Sledovani;
		return $sledovani->nesledovat("diskuze", $id, $id_uzivatele);
	}

	public function jeSledovana($id, $id_uzivatele)
	{
		$sledovani = new Sledovani;
		return $sledovani->jeSledovana("diskuze", $id, $id_uzivatele);
	}

	public function findIdByUri($uri)
	{
		return $this->connection->select('[id]')
					 ->from($this->table)
					 ->where('%n.uri = %s', $this->table, $uri);
	}

	private function constructUri($id, $data)
	{
		if(isset($data['nazev']) && isset($data['id_tematu']))
		{
			$urlsModel = new Urls;
			$url = $urlsModel->findUrlByPresenterAndActionAndParam('Forum', 'forum', $data['id_tematu'])->fetch();
			if($url != false && !empty($url['url']))
			{
				$data['uri'] = $url['url'] . $id . '-' . Texy::webalize($data['nazev']);
			}
		}
		return $data;
	}

	public function noveZhlednuti($id)
	{
		$data = array('pocet_precteni%sql' => '[pocet_precteni]+1');
		return $this->update($id, $data);
	}

	public function udrzba()
	{
		$vsechnyDiskuze = $this->findAll();
		foreach ($vsechnyDiskuze as $data)
		{
			$dataDoDB = array('nazev' => $data['nazev'], 'id_tematu' => $data['id_tematu']);
			$this->update($data['id'], $dataDoDB);
		}
	}

}
