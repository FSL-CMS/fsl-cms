<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Presenter hlavní stránky
 *
 * @author	Milan Pála
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		$clankyModel = $this->context->clanky;

		$this->template->clanky = array();
		$result = $clankyModel->findAll();
		$this->template->clanky['clanky'] = $result->fetchAll(0, 3);

		$this->template->fulltext = false;

		$this->template->clanky['nezverejnene'] = array();

		$this->setTitle('Úvodní stránka');

		$clankyPresenter = new ClankyPresenter;
		$clankyPresenter->zpracujClanky($this, false);
	}

}
