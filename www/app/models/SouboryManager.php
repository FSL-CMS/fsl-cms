<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Implmentace továrny na model ukládající soubory pomocí FileUploaderControl
 *
 * @author Milan Pála
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
