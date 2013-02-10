<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Komponenta vykreslující navigaci na dílčí nadpisy stránky
 *
 * @author	Milan Pála
  */
class TocControl extends BaseControl
{
	private $nadpisy = array();

	public function add($href, $title)
	{
		$this->nadpisy['toc-'.Nette\Utils\Strings::webalize($href)] = $title;
	}

	public function render()
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/toc.phtml');
		$template->odkazy = $this->nadpisy;
		$template->render();
	}

}
