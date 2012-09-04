<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Komponenta vykreslující kontakty na členy
 *
 * @author	Milan Pála
  */
class KontaktyControl extends Control
{
	public function __construct()
	{
		parent::__construct();
	}

	
	
	public function render()
	{
		$template = $this->parent->template;
		//$template->registerHelper('substr', function($str, $offset=NULL) { return iconv_substr($str, $offset); });
		$template->setFile(dirname(__FILE__) . '/kontakty.phtml');
		$uzivatele = new Uzivatele;
		$template->kontaktniOsoby = $uzivatele->findKontaktniOsoby();
		return $template->render();
	}
	
}