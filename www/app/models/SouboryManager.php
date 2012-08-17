<?php


/**
 * Description of FotkyManager
 *
 * @author Milan
 */
class SouboryManager implements IFileUploaderFileManager
{

	/** @var string */
	protected $souvisejici = NULL;

	/** @var int */
	protected $id_souvisejiciho = NULL;

	/** @var int */
	protected $id_autora = NULL;

	public function save(HttpUploadedFile $soubor)
	{
		$fotka = new Soubory($soubor);
		$fotka->setAutor($this->id_autora);
		$fotka->setSouvisejici($this->id_souvisejiciho);
		$fotka->uloz($this->id_souvisejiciho, $this->souvisejici);
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

}