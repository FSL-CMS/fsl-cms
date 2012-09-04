<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model sledování
 *
 * @author	Milan Pála
 */
class Sledovani extends BaseModel
{

	/** @var string */
	protected $table = 'sledovani';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		return $this->connection
			->select('[sledovani].[id], CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[nazev], " ", [mista].[obec]) AS [uzivatel], [sledovani].[id_uzivatele], [sledovani].[tabulka], [sledovani].[id_radku]')
			->from($this->table)
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [sledovani].[id_uzivatele]')
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]');
	}

	protected function findBy($tabulka, $id)
	{
		return $this->connection
			->select('%n.*', $this->table)
			->from($this->table)
			->where('[tabulka] = %s AND [id_radku] = %i', $tabulka, $id);
	}

	public function findByDiskuze($id)
	{
		return $this->findBy("diskuze", $id);
	}

	public function findByTemata($id)
	{
		return $this->findBy("temata", $id);
	}
	
	protected function deleteBy($tabulka, $id)
	{
		return $this->connection->delete($this->table)->where('[tabulka] = %s AND [id_radku] = %i', $tabulka, $id)->execute();
	}

	public function deleteByDiskuze($id)
	{
		return $this->deleteBy("diskuze", $id);
	}

	public function deleteByTemata($id)
	{
		return $this->deleteBy("temata", $id);
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

	public function sledovat($tabulka, $id, $id_uzivatele)
	{
		$data = array('tabulka' => $tabulka, 'id_radku%i' => $id, 'id_uzivatele%i' => $id_uzivatele);
		return $this->insert($data);
	}

	public function nesledovat($tabulka, $id, $id_uzivatele)
	{
		if( $this->jeSledovana($tabulka, $id, $id_uzivatele) )
		{
			$data = array('tabulka%s' => $tabulka, 'id_radku%i' => $id, 'id_uzivatele%i' => $id_uzivatele);
			return $this->connection->delete($this->table)->where('%and', $data)->execute();
		}
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		return $ret;
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

	public function upozornit($tabulka, $id)
	{
		try
		{
			$nazev = '';
			$text = '';
			if( $tabulka == 'diskuze' )
			{
				$model = new Diskuze();
				$polozka = $model->find($id)->fetch();
				$nazev = $polozka['tema_diskuze'];
				$text = 'Do diskuze '.$nazev.' byla přidána nová odpověď.';
			}
			elseif( $tabulka == 'temata' )
			{
				$model = new Diskuze();
				$polozka = $model->find($id)->fetch();
				$nazev = $polozka['tema'];
				$text = 'Do diskuze '.$nazev.' byla přidána nová odpověď.';
			}
			else return;

			$results = $this->findBy($tabulka, $id)->fetchAll();
			$uzivatele = new Uzivatele;
			foreach( $results as $result )
			{
				$mailer = new MyMailer;
				$uzivatel = $uzivatele->find($result->id_uzivatele)->fetch();
				$mailer->addTo($uzivatel->email, $uzivatel->uzivatel);
				$mailer->setFrom('sledovani@'.preg_replace('~www\.~', '', $_SERVER['HTTP_HOST']));
				$mailer->setSubject('Upozornění na nový příspěvek v diskuzi');
				$mailer->setBody($text);
				$mailer->send();
			}
		}
		catch(Exception $e)
		{
			Debug::processException($e, true);
		}
	}

	public function jeSledovana($tabulka, $id, $id_uzivatele)
	{
		return $this->connection
			->select('[id]')
			->from($this->table)
			->where('[tabulka] = %s AND [id_radku] = %i AND [id_uzivatele] = %i', $tabulka, $id, $id_uzivatele)->execute()->fetch() !== false ? true : false;
	}

}
