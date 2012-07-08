<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter hlavní stránky
 *
 * @author	Milan Pála
 */
class HomepagePresenter extends BasePresenter
{

	protected $model;

	protected function startup()
	{
		parent::startup();
	}

	public function actionDefault()
	{
		//$this->redirect('Clanky:clanek');
	}

	public function renderDefault()
	{
		$clankyModel = new Clanky;
		$this->template->clanky = array();
		$result = $clankyModel->findAll();

		$this->template->fulltext = false;

		$this->template->clanky['clanky'] = $result->fetchAll( 0, 3 );
		$this->template->clanky['nezverejnene'] = array();

		$this->setTitle('Úvodní stránka');

		$clankyPresenter = new ClankyPresenter;
		$clankyPresenter->zpracujClanky($this, false);
	}

}
