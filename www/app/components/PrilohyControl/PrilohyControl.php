<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;
use Nette\Diagnostics\Debugger;

/**
 * Komponenta vykreslující přiložené soubory
 *
 * @author	Milan Pála
  */
class PrilohyControl extends BaseControl
{
	/** @var Soubory */
	protected $model;

	/** @var string */
	protected $render;

	public function __construct()
	{
		parent::__construct();
		$this->render = 'prilohy';
	}

	public function createComponentFileUploader($name)
	{
		$c = new FileUploaderControl();

		$souvisejici = $this->getPresenter()->getName();
		$id_souvisejiciho = $this->getPresenter()->getParam('id');

		$soubory = new SouboryManager();
		$soubory->setAutor($this->getPresenter()->user->getIdentity()->id);
		$soubory->setSouvisejici($id_souvisejiciho, $souvisejici);
		$c->setFileModel($soubory);
		$c->setType('images,docs,photos');

		return $c;
	}

	public function createComponentPrilohyForm($name)
	{
		$form = new Form($this, $name);

		$form->getElementPrototype()->class[] = "ajax";

		$souvisejici = $this->getPresenter()->getName();
		$id_souvisejiciho = $this->getPresenter()->getParam('id');

		$souboryModel = $this->presenter->context->soubory;
		$soubory = $souboryModel->findBySouvisejici($id_souvisejiciho, $souvisejici)->fetchAll();

		$prilohyCont = $form->addContainer('prilohy');

		foreach($soubory as $soubor)
		{
			$souborCont = $prilohyCont->addContainer($soubor['id']);
			$souborCont->addHidden('id');
			$souborCont->addText('nazev', 255, 30);
		}

		$form->addGroup('Nahrání nového souboru');
		$form->addSubmit('save', Texty::$FORM_SAVE);
		$form->addSubmit('addNew', 'Přiložit nové soubory');
		$form->onSuccess[] = array($this, 'prilohyFormSubmitted');
	}

	public function prilohyFormSubmitted(Form $form)
	{
		$data = $form->getValues();
		$this->model = $this->presenter->context->soubory;

		if($form['save']->isSubmittedBy())
		{
			try
			{
				foreach($data['prilohy'] as $priloha)
				{
					$dataDoDB = array('nazev' => $priloha['nazev']);
					$this->model->update($priloha['id'], $dataDoDB);
				}
				$this->parent->flashMessage('Informace o souborech byly uloženy.', 'ok');
			}
			catch(DibiException $e)
			{
				$this->parent->flashMessage('Nepodařilo se uložit informace o souborech.', 'error');
			}
			$this->redirect('this');
		}
		elseif($form['addNew']->isSubmittedBy())
		{
			if( $this->parent->isAjax() )
			{
				$this->render = 'upload';
				$this->invalidateControl('prilohy');
			}
			else $this->redirect('upload!');
		}
	}

	public function createComponentNahraniSouboruForm()
	{
		$form = new Form;
		$form->getElementPrototype()->class[] = "ajax";
		$form->addSubmit('save', 'Zobrazit nahrané soubory');
		$form->onSuccess[] = array($this, 'nahraniSouboruFormSubmitted');

		$form->onSuccess[] = array($this,"handlePrekresliForm");

		return $form;
	}

	public function handlePrekresliForm()
	{
		$this->invalidateControl("prilohy");
	}

	public function nahraniSouboruFormSubmitted(Form $form)
	{
		if(!$this->parent->isAjax()) $this->redirect('this');
	}

	public function handleUpload()
	{
		$this->render = 'upload';
		if( $this->parent->isAjax() ) $this->invalidateControl('prilohy');
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model = $this->presenter->context->soubory;
			$this->model->delete($id);
			if($this->parent->isAjax()) $this->invalidateControl('prilohy');
			else $this->redirect('this');
		}
		catch(DibiException $e)
		{
			$this->parent->flashMessage('Nepodařilo se smazat soubor.', 'error');
			Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

	}

	public function render()
	{
		//$this->template->parent = $this->getPresenter();

		if($this->render == 'upload') $this->renderUpload();
		else $this->renderPrehled();
	}

	public function renderUpload()
	{
		$this->template->setFile(dirname(__FILE__).'/nahraj.phtml');
		$this->template->render();
	}

	public function renderPrehled()
	{
		$soubory = $this->presenter->context->soubory;
		$user = $this->getPresenter()->getUser();

		$this->template->setFile(dirname(__FILE__).'/prilohy.phtml');

		$souvisejici = $this->parent->getPresenter()->getName();
		$id_souvisejiciho = $this->parent->getPresenter()->getParam('id');

		$this->template->prilohy = array();
		$this->template->prilohy['prilohy'] = $soubory->findBySouvisejici($id_souvisejiciho, $souvisejici)->fetchAssoc('id,=');
		$this->template->prilohy['muze_editovat'] = $user->isAllowed('soubory', 'edit');

		$tmp = array();
		$tmp['prilohy'] = $this->template->prilohy['prilohy'];
		$this['prilohyForm']->setValues($tmp);

		$this->template->render();
	}

}
