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
class KontaktyControl extends BaseControl
{

	public function render()
	{
		$template = $this->parent->template;
		$template->setFile(dirname(__FILE__) . '/kontakty.phtml');
		$uzivatele = $this->presenter->context->uzivatele;
		$template->kontaktniOsoby = $uzivatele->findKontaktniOsoby();
		return $template->render();
	}

}
