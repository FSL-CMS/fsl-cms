<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter sportovišť
 *
 * @author	Milan Pála
 */
class SportovistePresenter extends SecuredPresenter
{
	/** @persistent */
	public $backlink = '';
	
	protected $model = NULL;
	
	protected function startup()
	{
		$this->model = new Sportoviste;
		parent::startup();
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('sportoviste', 'add') ) throw new ForbiddenRequestException();
		
		$this->setView('edit');
	}
	
	public function actionEdit($id = 0)
	{
		if( $id == 0 ) $this->redirect('add');

		if( !$this->model->find($id)->fetch() ) throw new BadRequestException();
		
		$backlink = $this->getApplication()->storeRequest();
		if( ($this->user === NULL || !$this->user->isLoggedIn()) ) $this->redirect('Sprava:login', $backlink);
		
		if( !$this->user->isAllowed('sportoviste', 'edit') && !$this->model->muzeEditovat($id, $this->user->getIdentity()->id) ) throw new ForbiddenRequestException();

	}
	
	public function renderDefault()
	{
		$this->template->sportoviste = array();
		
		$this->template->sportoviste['muze_pridat'] = $this->user->isAllowed('sportoviste', 'add');
		$this->template->sportoviste['muze_editovat'] = $this->user->isAllowed('sportoviste', 'edit');
		$this->template->sportoviste['muze_mazat'] = $this->user->isAllowed('sportoviste', 'delete');
		$this->template->sportoviste['sportoviste'] = $this->model->findAll();
		
		$this->setTitle('Přehled sportovišť');
	}	
	
	public function renderEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());
		
		if($id == 0) $this->setTitle('Přidání sportoviště');
		else $this->setTitle('Úprava sportovistě');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;

		//$form->getElementPrototype()->class('ajax');
		
		$mistaModel = new Mista;
		
		$form->addGroup('Informace o sportovišti');
		$form->addSelect('id_mista', 'Obec', $mistaModel->findAllToSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat obec.');
		$form->addRequestButton('addMista', 'Přidat novou', 'Mista:add');
		$form->addTexylaTextArea('popis', 'Popis sportoviště');
		
		$form->addText('sirka', 'Zeměpisná šířka');
		$form->addText('delka', 'Zeměpisná délka');
		
		$form->addSouradnice('mapa', 'Pozice na mapě', $form['sirka'], $form['delka']);

		$form->addSubmit('save', 'Uložit a pokračovat v úpravách');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět')
			->setValidationScope(false);

		$form->onSubmit[] = array($this, 'editFormSubmitted');
		
		return $form;
	}
	
	public function editFormSubmitted(AppForm $form)
	{
		$id = (int)$this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{
			$this->getApplication()->restoreRequest($this->backlink);
			$this->redirect('default');
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			try
			{
				$dataDoDb = array('id_mista' => $form['id_mista']->value, 'popis' => $form['popis']->value, 'sirka' => $form['sirka']->value, 'delka' => $form['delka']->value);

				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}
				
				$this->flashMessage('Sportoviště bylo úspěšně uloženo.', 'ok');
				$this->getApplication()->restoreRequest($this->backlink);
				if( $form['saveAndReturn']->isSubmittedBy() ) $this->redirect('default');
				else $this->redirect('this');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Místo se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Sportoviště bylo úspěšně odstraněno.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Místo se nepodařilo odstranit.', 'error');
			Debug::processException($e, true);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}

		if( $this->isAjax() ) $this->invalidateControl('mista');
		else $this->redirect('this');
	}
}
