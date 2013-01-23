<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter stránek
 *
 * @author	Milan Pála
 */
class StrankyPresenter extends BasePresenter
{

	protected $model;

	protected function startup()
	{
		$this->model = new Stranky;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->stranky = array();

		$this->template->stranky['muze_editovat'] = $this->user->isAllowed('stranky', 'edit');
		$this->template->stranky['stranky'] = $this->model->findAll();

		$this->setTitle('Stránky');
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('stranky', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function createComponentStrankyForm()
	{
		$form = new AppForm;

		$form->getElementPrototype()->class('ajax');

		foreach( $this->model->findAll()->fetchAll() as $stranka )
		{
			$poradi = $form->addContainer($stranka['id']);

			$poradi->addHidden('id')->setDefaultValue($stranka['id']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($stranka['poradi']);
		}
		$form->addSubmit('save', 'Uložit');

		$form->onSubmit[] = array($this, 'strankyFormSubmitted');

		return $form;
	}

	public function strankyFormSubmitted(AppForm $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );
			}
			$this->flashMessage('Údaje o stránkách byly úspěšně uloženy.');

			if( !$this->isAjax() ) $this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o stránkách se nepodařilo uložit.', 'error');
		}
	}

	public function actionStranka($id)
	{
		$this->template->stranka = $this->model->find($id)->fetch();
		if(!$this->template->stranka) throw new BadRequestException();
	}

	public function renderStranka($id)
	{
		//$id = $this->model->findIdByUri($uri)->fetchSingle();

          $this->template->stranka['muze_editovat'] = $this->user->isAllowed('stranky', 'edit');

		$this->setTitle($this->template->stranka['nazev']);
  	}

	public function renderEdit($id = 0, $backlink = NULL)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		if( $id == 0 ) $this->setTitle('Přidání nové stránky');
		else $this->setTitle('Úprava stránky');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;

		$backlink = $this->getApplication()->storeRequest();

		$form->addGroup('Informace o stránce');
		$form->addText('nazev', 'Název stránky')
			->addRule(Form::FILLED, 'Je nutné vyplnit název stránky');

		$form->addGroup('Obsah stránky');
		$form->addAdminTexylaTextArea('text', 'Obsah');
		$form->setCurrentGroup(NULL);

		$form->addGroup('Uložení');
		$form->addSubmit('save', Texty::$FORM_SAVE);
		$form->addSubmit('cancel', Texty::$FORM_CANCEL)
			->setValidationScope(FALSE);

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
		}
		elseif( $form['save']->isSubmittedBy() )
		{
			if( $id == 0 )
			{
				$this->model->insert($form->getValues());
				$id = $this->model->lastInsertedId();
			}
			else $this->model->update($id, $form->getValues());

			$this->flashMessage('Údaje o stránce byly úspěšně uloženy.');
		}

		if($id != 0) $this->redirect('Stranky:stranka', $id);
		else $this->redirect('Stranky:default');
	}

}
