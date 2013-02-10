<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Komponenta, z níž vycházejí všechny komponenty. Obsahuje společné prvky.
 *
 * @author Milan Pála
 */
class BaseControl extends Nette\Application\UI\Control
{

	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class = NULL);

		$texy = $this->presenter->context->Texy;
		$texy2 = $this->presenter->context->Texy;

		/*$texy->addHandler('script', array($this, 'scriptHandler'));
		$texy->addHandler('image', array($this, 'videoHandler'));*/

		$template->registerHelper('texy', array($texy, 'process'));
		$template->registerHelper('texy2', array($texy2, 'process'));

		$datum = new Datum();
		$template->registerHelper('datum', array($datum, 'date'));

		$template->registerHelper('zvetsPrvni', array($this, 'zvetsPrvni'));

		return $template;
	}

}
