<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Komponenta vykreslující šablony článků
 *
 * @author	Milan Pála
 */
class SablonyClankuControl extends BaseControl
{
	public function createComponentFotka()
	{
		return new FotkaControl();
	}

	public function render($id)
	{
		$this->renderSablony($id);
	}

	/**
	 *
	 * @param int $id ID článku
	 */
	public function renderSablony($id)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/sablony.phtml');

		$sablonyModel = $this->presenter->context->sablonyClanku;
		$template->sablony = array();
		$template->sablony['prave'] = $sablonyModel->findByClanekPrave($id)->fetchAll();
		$template->sablony['leve'] = $sablonyModel->findByClanekLeve($id)->fetchAll();

		$template->render();
	}

}
