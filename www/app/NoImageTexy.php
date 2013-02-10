<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Nastavení Texy nezobrazující obrázky
 */
class NoImageTexy extends MyTexy
{
	public function __construct()
	{
		parent::__construct();
		$this->allowed['image/definition'] = FALSE;
		$this->allowed['image'] = FALSE;
	}
}
