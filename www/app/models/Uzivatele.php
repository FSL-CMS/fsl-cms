<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model uživatelů
 *
 * @author	Milan Pála
 */
class Uzivatele extends BaseModel implements IAuthenticator
{

	/** @var string */
	protected $table = 'uzivatele';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findKontaktniOsoby()
	{
		$rocniky = new Rocniky;

		return $this->connection->query('
			(SELECT [uzivatele].[id], [uzivatele].[jmeno], [uzivatele].[prijmeni], [uzivatele].[kontakt], CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec]) AS [sbor], [sbory].[id] AS [id_sboru], "rada" AS [kategorie], [funkce_rady].[nazev] AS [funkce], [funkce_rady].[poradi] AS [funkce_poradi], 0 AS [id_zavodu], 0 AS [datum_zavodu], [uzivatele].[email]
   			FROM uzivatele
			LEFT JOIN [sbory] ON [sbory].[id] = [uzivatele].[id_sboru]
			LEFT JOIN [mista] ON [mista].[id] = [sbory].[id_mista]
			LEFT JOIN [okresy] ON [okresy].[id] = [mista].[id_okresu]
			LEFT JOIN [typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]
			LEFT JOIN [funkce_rady] ON [funkce_rady].[id] = [uzivatele].[id_funkce]
			WHERE [uzivatele].[id_funkce] IS NOT NULL
			ORDER BY [funkce_rady].[poradi])
			UNION
			(SELECT [uzivatele].[id], [uzivatele].[jmeno], [uzivatele].[prijmeni], [uzivatele].[kontakt], CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec]) AS [sbor], [sbory].[id] AS [id_sboru], "poradatel" AS [kategorie], CONCAT_WS(" ", [poradatel_mista].[obec]) AS [funkce], 10000 AS [funkce_poradi], [zavody].[id] AS [id_zavodu], [zavody].[datum] AS [datum_zavodu], [uzivatele].[email]
			FROM zavody

			LEFT JOIN [sbory] [poradatel_sbory] ON [poradatel_sbory].[id] = [zavody].[id_poradatele]
			RIGHT JOIN [uzivatele] ON [uzivatele].[id] = [poradatel_sbory].[id_kontaktni_osoby]
			LEFT JOIN [mista] [poradatel_mista] ON [poradatel_mista].[id] = [poradatel_sbory].[id_mista]
			LEFT JOIN [okresy] ON [okresy].[id] = [poradatel_mista].[id_okresu]
			LEFT JOIN [typy_sboru] [poradatel_typy_sboru] ON [poradatel_typy_sboru].[id] = [poradatel_sbory].[id_typu]

			LEFT JOIN [sbory] ON [sbory].[id] = [uzivatele].[id_sboru]
			LEFT JOIN [mista] ON [mista].[id] = [sbory].[id_mista]
			LEFT JOIN [typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]

			WHERE [zavody].[id_rocniku] = %i) ORDER BY [funkce_poradi], [datum_zavodu]
		', $rocniky->findLast()->fetchSingle());
	}


	public function authenticate(array $credentials)
	{
		$login = $credentials[self::USERNAME];
		$heslo = md5($credentials[self::PASSWORD]);

		// přečteme záznam o uživateli z databáze
		$row = $this->connection->query('SELECT id, CONCAT(prijmeni, " ", jmeno) AS jmeno, heslo, docasneheslo, opravneni, id_sboru FROM [uzivatele] WHERE [email] = %s', $login)->fetch();

		if (!$row) { // uživatel nenalezen?
			throw new AuthenticationException("Uživatel nebyl nalezen.", self::IDENTITY_NOT_FOUND);
		}

		if ($row->heslo !== $heslo && $row->docasneheslo !== $heslo) { // hesla se neshodují?
			throw new AuthenticationException("Špatné heslo.", self::INVALID_CREDENTIAL);
		}

		$identita = new Identity($row->jmeno, $row->opravneni); // vrátíme identitu
		$identita->id = $row->id;
		$identita->id_sboru = $row->id_sboru;
		return $identita;
	}

	public function findLogined()
	{
		return $this->connection
			->select('[uzivatele].[jmeno], [uzivatele].[prijmeni], CONCAT_WS(" ", [typy_sboru].[zkratka], [mista].[obec]) AS [sbor], [sbory].[id] AS [id_sboru]')
			->from('[uzivatele]')
   			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->where('[uzivatele].[id] = %i', $this->user->getIdentity()->id);
	}

	public function findAllToSelect()
	{
		return $this->connection
			->select('[uzivatele].[id], CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec]) AS [uzivatel]')
			->from($this->table)
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->orderBy('[uzivatele].[aktivni] DESC, [uzivatele].[prijmeni], [uzivatele].[jmeno]');
	}

	public function find($id)
	{
		return $this->findAll()
			->where('[uzivatele].[id] = %i', $id);
	}

	public function findByEmail($email)
	{
		return $this->findAll()
			->where('[uzivatele].[email] = %s', $email);
	}

	public function findBySbor($id)
	{
		return $this->findAll()
			->where('[uzivatele].[id_sboru] = %i', $id);
	}

	public function findByFunkce($id)
	{
		return $this->findAll()
			->where('[uzivatele].[id_funkce] = %i', $id);
	}

	public function findIdByUri($uri)
	{
		return $this->findAll()
			->where('[uzivatele].[uri] = %s', $uri);
	}

	public function findAll()
	{
		return $this->connection
			->select('[uzivatele].*, CONCAT([typy_sboru].[zkratka], " ", [sbory].[privlastek], " ", [mista].[obec]) AS [sbor], [okresy].[nazev] AS [okres], COUNT([komentare].[id]) AS [pocet_komentaru], CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[zkratka], " ", [mista].[obec]) AS [uzivatel]')
			->from($this->table)
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[okresy] ON [okresy].[id] = [mista].[id_okresu]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]')
			->leftJoin('[komentare] ON [komentare].[id_autora] = [uzivatele].[id]')
			->groupBy('[uzivatele].[id]')
			->orderBy('[uzivatele].[aktivni] DESC, [uzivatele].[prijmeni], [uzivatele].[jmeno]');

	}

	/**
	 * Vytvoří nové heslo, aktualizuje ho uživateli a vrátí ho
	 * @param string $email
	 * @return string nové heslo
	 */
	public function noveHeslo($email)
	{
		$heslo = substr(md5($email.date('H:i:s')), 0, 8);
		$data = array( 'docasneheslo' => md5($heslo) );
		$this->connection->update($this->table, $data)->where('[email] = %s', $email)->execute();
		return $heslo;
	}

	private function constructUri($id, $data)
	{
		if( isset($data['jmeno']) && isset($data['prijmeni']) )
		{
			$data['uri'] = '/uzivatele/'.$id.'-'.Texy::webalize( $data['jmeno'].' '.$data['prijmeni'] );
		}
		return $data;
	}

	public function insert(array $data)
	{
		if( $this->connection->query('SELECT [id] FROM [uzivatele] WHERE [email] = %s', strtolower($data['email']))->fetch() ) throw new RegistredAccountException();

		$ret = parent::insert($data)->execute(dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Uzivatele', 'uzivatel', $id, $data['uri']);
		return $ret;
	}

	public function update($id, array $data)
	{
		if( isset($data['id_funkce']) )
		{
			$data['id_funkce%in'] = intval($data['id_funkce']);
			unset($data['id_funkce']);
		}

		if( isset($data['id_sboru%i']) && intval($data['id_sboru%i']) != 0 ) $data['aktivni'] = 1;

		parent::update($id, $data)->execute();
		$data = $this->constructUri($id, $data);
		$urlsModel = new Urls;
		$urlsModel->setUrl('Uzivatele', 'uzivatel', $id, $data['uri']);
	}

	public function delete($id)
	{
		$data = array('aktivni' => 0);
		return self::update($id, $data)->execute();
	}

	public function odstranFunkci($id)
	{
		return $this->update($id, array('id_funkce%in' => 0));
	}

	public function udrzba()
	{
		$vsichniUzivatele = $this->findAll();
		foreach( $vsichniUzivatele as $data )
		{
			$dataDoDB = array( 'jmeno' => $data['jmeno'], 'prijmeni' => $data['prijmeni'] );
			$this->update($data['id'], $dataDoDB);
		}
	}
}
