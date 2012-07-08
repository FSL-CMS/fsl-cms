<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
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
		$this->render = 'prehled';

		//$this->template = parent::createTemplate();

		parent::__construct();
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

		$form->addGroup('Soubory k nahrání');

		$form->getElementPrototype()->class[] = "ajax";
		$form->addMultipleFileUpload('upload', 'Nahrát soubory', 20)
			/*->addRule("MultipleFileUpload::validateFilled", "Musíte odeslat alespoň jeden soubor!")
			->addRule("MultipleFileUpload::validateFileSize", "Soubory jsou dohromady moc veliké!",1024*1024)*/;

		$form->addSubmit('save', 'Nahrát soubory');
		$form->onSubmit[] = array($this, 'nahraniSouboruFormSubmitted');

		$form->onInvalidSubmit[] = array($this,"handlePrekresliForm");
		$form->onSubmit[] = array($this,"handlePrekresliForm");

		return $form;
	}

	public function handlePrekresliForm()
	{
		$this->invalidateControl("prilohy");
	}

	public function nahraniSouboruFormSubmitted(AppForm $form)
	{
		$data = $form->getValues();

		foreach($data["upload"] AS $file)
		{
			try
			{
				$soubor = new Soubory($file);
				$soubor->id_autora = $this->parent->user->getIdentity()->id;

				$souvisejici = $this->parent->getPresenter()->getName();
				$id_souvisejiciho = $this->parent->getPresenter()->getParam('id');

				$soubor->uloz($id_souvisejiciho, $souvisejici);
				$this->parent->flashMessage('Soubor '.$file->getName().' byl úspěšně uložen.', 'ok');
			}
			catch(Exception $e)
			{
				$this->parent->flashMessage('Nepodařilo se uložit soubor '.$file->getName().'. Chyba: '.$e->getMessage(), 'error');
				Debug::processException($e, true);
			}
		}

		// Předáme data do šablony
		//$this->template->values = $data;
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

		$souvisejici = $this->getPresenter()->getName();
		$id_souvisejiciho = $this->getPresenter()->getParam('id');

		$this->template->setFile(dirname(__FILE__).'/prilohy.phtml');

		$this->template->prilohy = array();
		$this->template->prilohy['prilohy'] = $soubory->findBySouvisejici($id_souvisejiciho, $souvisejici)->fetchAssoc('id,=');
		$this->template->prilohy['muze_editovat'] = $user->isAllowed('soubory', 'edit');

		$tmp = array();
		$tmp['prilohy'] = $this->template->prilohy['prilohy'];
		$this['prilohyForm']->setValues($tmp);

		$this->template->render();
	}

}
