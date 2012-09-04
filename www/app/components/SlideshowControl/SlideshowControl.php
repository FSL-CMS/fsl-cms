<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Komponenta vykreslující obrázky, které se náhodně mění
 *
 * @author	Milan Pála
 */
class SlideshowControl extends BaseControl
{

	/**
	 * Cesta ke složce s obrázky. Kořenem je WWW_DIR. Stejná cesta se předá i do šablony.
	 */
	private $slideshowDir = '/liga/images/slideshow';

	public function __construct()
	{
		parent::__construct();
	}

	public function render()
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/slideshow.phtml');

		$slideshow = array();
		$d = dir(WWW_DIR . $this->slideshowDir);
		if($d !== false && $d !== NULL)
		{
			while (false !== ($fotka = $d->read()))
			{
				if($fotka == '.' || $fotka == '..') continue;
				$slideshow[] = $fotka;
			}
			shuffle($slideshow);
		}
		else
		{
			Debug::processException(new FileNotFoundException('Neexistuje cesta ('.WWW_DIR . $this->slideshowDir.') ke složce s obrázky pro SlideshowControl.'), true);
		}
		$template->slideshow = $slideshow;
		$template->slideshowDir = $this->slideshowDir;

		$template->render();
	}

}
