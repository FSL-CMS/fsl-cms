<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Komponenta vykreslující přehled ročníků
 *
 * @author	Milan Pála
  */
class PrehledRocnikuControl extends BaseControl
{
	/** @var int */
	private $rocnik;

	/** @var Rocniky */
	private $model;

	public function __construct()
	{
		parent::__construct();
	}

	public function setRocnik($id)
	{
		$this->rocnik = $id;
	}

	public function render()
	{
		$this->model = $this->presenter->context->rocniky;

		if($this->getPresenter()->user->isAllowed('rocniky', 'edit')) $this->model->zobrazitNezverejnene();

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/rocniky.phtml');

		$template->rocniky = array();
		$template->rocniky['rocniky'] = $this->model->findAll();
		$template->rocniky['muze_editovat'] = $this->parent->user->isAllowed('rocniky', 'edit');
		$template->rocniky['muze_smazat'] = $this->parent->user->isAllowed('rocniky', 'delete');
		$template->rocniky['muze_pridat'] = $this->parent->user->isAllowed('rocniky', 'add');

		if($this->rocnik == null)
		{
			$posledni = $this->model->findLast()->fetch();
			$this->template->rocnik = $this->model->find($posledni['id'])->fetch();
			$this->rocnik = $template->rocnik['id'];
		}
		else
		{
			$template->rocnik = $this->model->find($this->rocnik)->fetch();
		}

		$template->i = 0;

		$template->render();
	}

}
