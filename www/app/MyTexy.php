<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Vlastní nastavení Texy
 *
 * @author Milan Pála
 */
class MyTexy extends Texy
{
	public function __construct()
	{
		parent::__construct();
		$this->imageModule->fileRoot = APP_DIR . "../data";
		$this->imageModule->root = Environment::getVariable("baseUri") . "files";
		$this->headingModule->top = 2;
		$this->allowed['phrase/del'] = true;   // --deleted--
		TEXY::$advertisingNotice = false;
	}
}

