<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Komponenta vykreslující přiložené soubory
 *
 * @author	Milan Pála
  */
class PrilohyControl extends BaseControl
{
	protected $model;
	protected $render;
	//public $template;

	public function __construct()
	{

		$this->model = new Soubory;
		$this->render = 'prilohy';

		//$this->template = parent::createTemplate();

		parent::__construct();
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

	public function createComponentPrilohyForm()
	{
		$form = new AppForm($this, 'prilohyForm');

		$form->getElementPrototype()->class[] = "ajax";

		$souvisejici = $this->getPresenter()->getName();
		$id_souvisejiciho = $this->getPresenter()->getParam('id');

		$souboryModel = new Soubory;
		$soubory = $souboryModel->findBySouvisejici($id_souvisejiciho, $souvisejici)->fetchAll();

		$prilohyCont = $form->addContainer('prilohy');

		foreach($soubory as $soubor)
		{
			$souborCont = $prilohyCont->addContainer($soubor['id']);
			$souborCont->addHidden('id');
			$souborCont->addText('nazev', 255, 30);
		}

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('addNew', 'Přiložit nové soubory');
		$form->onSubmit[] = array($this, 'prilohyFormSubmitted');
		$form->addGroup('Nahrání nového souboru', true);
		return $form;
	}

	public function prilohyFormSubmitted(AppForm $form)
	{
		$data = $form->getValues();

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
		$form = new AppForm;
		$form->getElementPrototype()->class[] = "ajax";
		$form->addSubmit('save', 'Zobrazit nahrané soubory');
		$form->onSubmit[] = array($this, 'nahraniSouboruFormSubmitted');

		$form->onSubmit[] = array($this,"handlePrekresliForm");

		return $form;
	}

	public function handlePrekresliForm()
	{
		$this->invalidateControl("prilohy");
	}

	public function nahraniSouboruFormSubmitted(AppForm $form)
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
			$this->model->delete($id);
			if($this->parent->isAjax()) $this->invalidateControl('prilohy');
			else $this->redirect('this');
		}
		catch(DibiException $e)
		{
			$this->parent->flashMessage('Nepodařilo se smazat soubor.', 'error');
			Debug::processException($e);
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
		$soubory = new Soubory;
		$user = Environment::getUser();

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
