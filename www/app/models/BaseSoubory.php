<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model souborů
 *
 * @author	Milan Pála
 */
class BaseSoubory extends BaseModel
{
	/** @var string */
	protected $table = 'soubory';

	/** @var DibiConnection */
	protected $connection;

	/** @var string Cesta ke složce se soubory */
	protected $cestaKsouborum;

	protected $soubor = NULL;

	public $id_autora = NULL;

	public function __construct(HttpUploadedFile $soubor = NULL)
	{
		$this->connection = dibi::getConnection();
		$this->cestaKsouborum = APP_DIR.'/../data/';
		$this->soubor = $soubor;
	}

	public function findAll()
	{
		return $this->findOne();
	}

	private function findOne()
	{
		return $this->connection
			->select('soubory.*, CONCAT([uzivatele].[jmeno], " ", [uzivatele].[prijmeni], ", ", [typy_sboru].[nazev], " ", [mista].[obec]) AS [autor]')
			->from($this->table)
			->leftJoin('[uzivatele] ON [uzivatele].[id] = [soubory].[id_autora]')
			->leftJoin('[sbory] ON [sbory].[id] = [uzivatele].[id_sboru]')
			->leftJoin('[mista] ON [mista].[id] = [sbory].[id_mista]')
			->leftJoin('[typy_sboru] ON [typy_sboru].[id] = [sbory].[id_typu]');
	}

	public function findBySouvisejici($id, $souvisejici = '')
	{
		return $this->findOne()
			->where('[souvisejici] = %s AND [id_souvisejiciho] = %i', $souvisejici, $id);
	}



	public function find($id)
	{
		return $this->findOne()->where('[soubory].[id] = %i', $id);
	}

	/**
	 * Vytvoří kompletní cestu včetně všech adresářů
	 * @param $pathname cesta
	 * @param $mode
	 */
	public function vytvor_cestu( $pathname, $mode = 0755 )
	{
		if( !preg_match( '~.+/$~', $pathname ) ) $pathname = dirname( $pathname ).'/';
		is_dir($pathname) || self::vytvor_cestu(dirname($pathname).'/', $mode);
		return is_dir($pathname) || mkdir($pathname, $mode);
	}

	public function uloz($id_souvisejiciho, $souvisejici = '')
	{
		$casti = pathinfo( $this->soubor->getName() );
		$this->insert(array('souvisejici' => $souvisejici, 'id_souvisejiciho' => $id_souvisejiciho, 'soubor' => $casti['filename'], 'pripona' => $casti['extension'], 'nazev' => $casti['filename'], 'id_autora' => $this->id_autora, 'datum_pridani%sql' => 'NOW()'));
		$id_souboru = $this->lastInsertedId();

		if( $this->soubor->move($this->cestaKsouborum.$id_souboru.'.'.$casti['extension']) == false ) throw new Exception('Soubor '.$this->soubor->getName().' se nepodařilo uložit.');

		return $id_souboru;
	}

	public function noveStazeni($id)
	{
		$data = array('pocet_stahnuti%sql' => 'pocet_stahnuti+1');
		return self::update($id, $data)->execute();
	}

	public function insert(array $data)
	{
		return parent::insert($data);
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data);
	}

	public function delete($id)
	{
		return parent::delete($id);
	}

}