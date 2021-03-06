<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
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

	/** @var string Cesta ke složce se soubory */
	protected $cestaKsouborum = DATA_DIR;

	protected $soubor = NULL;

	/** @var string */
	protected $souvisejici = NULL;

	/** @var int */
	protected $id_souvisejiciho = NULL;

	/** @var int */
	protected $id_autora = NULL;

	public function setSoubor(Nette\Http\FileUpload $soubor)
	{
		$this->soubor = $soubor;
	}

	public function setSouvisejici($id, $tabulka = NULL)
	{
		$this->id_souvisejiciho = $id;
		$this->souvisejici = $tabulka;
	}

	public function setAutor($id)
	{
		$this->id_autora = $id;
	}

	public function findAll()
	{
		return $this->findOne();
	}

	private function findOne()
	{
		return $this->connection
			->select('soubory.*, '.Uzivatele::$_UZIVATEL.' AS [autor]')
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
		$casti = pathinfo($this->soubor->getName());
		$this->insert(array('souvisejici' => $souvisejici, 'id_souvisejiciho' => $id_souvisejiciho, 'soubor' => \Nette\Utils\Strings::webalize($casti['filename'], NULL, TRUE), 'nazev' => $casti['filename'], 'pripona' => $casti['extension'], 'id_autora' => $this->id_autora, 'datum_pridani%sql' => 'NOW()'));
		$id_souboru = $this->lastInsertedId();

		if( $this->soubor->move($this->cestaKsouborum.'/'.$id_souboru.'.'.$casti['extension']) == false ) throw new Exception('Soubor '.$this->soubor->getName().' se nepodařilo uložit.');

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
