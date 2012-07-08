<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter funkcí rady
 *
 * @author	Milan Pála
 */
class FunkceRadyPresenter extends SecuredPresenter
{
	/** @persistent */
	public $backlink = '';
	
	protected $model = NULL;
	
	protected function startup()
	{
		$this->model = new FunkceRady;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->setTitle('Funkce rady');
		
		$this->template->funkceRady = array();
		
		$this->template->funkceRady['muze_editovat'] = $this->user->isAllowed('funkcerady', 'edit');
		$this->template->funkceRady['muze_mazat'] = $this->user->isAllowed('funkcerady', 'delete');
		$this->template->funkceRady['funkceRady'] = $this->model->findAll();		
	}	

	public function actionAdd()
	{
		$this->setView('edit');
	}

	public function renderEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());
		
		if($id == 0) $this->setTitle('Přidání funkce rady');
		else $this->setTitle('Úprava funkce rady');
	}
	
	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;
		
		$form->addText('nazev', 'Funkce rady KL')
			->addRule(Form::FILLED, 'Je nutné vyplnit název funkce.');

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSubmit[] = array($this, 'editFormSubmitted');
		
		return $form;
	}
	
	public function editFormSubmitted(AppForm $form)
	{
		$id = (int)$this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{
		}
		elseif($form['save']->isSubmittedBy())
		{
			try
			{
				$dataDoDb = array('nazev' => $form['nazev']->value);
				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}

				$this->getApplication()->restoreRequest($this->backlink);

				$this->redirect('FunkceRady:default');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit funkci rady.', 'error');
			}
		}
	}

	public function createComponentFunkceRadyForm()
	{
		$form = new AppForm;

		$form->getElementPrototype()->class('ajax');
		
		foreach( $this->model->findAll()->fetchAll() as $kategorie )
		{
			$poradi = $form->addContainer($kategorie['id']);
				
			$poradi->addHidden('id')->setDefaultValue($kategorie['id']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($kategorie['poradi']);
		}
		$form->addSubmit('save', 'Uložit');
		
		$form->onSubmit[] = array($this, 'funkceRadyFormSubmitted');
		
		return $form;
	}
	
	public function funkceRadyFormSubmitted(AppForm $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );	
			}
			$this->flashMessage('Údaje o funkcích byly úspěšně uloženy.', 'ok');

			if( !$this->isAjax() ) $this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o funkcích se nepodařilo uložit.', 'error');	
		}	
	}	

	public function handleDelete($id, $force = false)
	{
		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Funkce byla úspěšně odstraněna.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Funkci se nepodařilo odstranit.', 'error');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage().' "Přesto odstranit?":'.$this->link('delete!', array('id' => $id, 'force' => true)), 'error');
		}
		if( !$this->isAjax() ) $this->redirect('this');
	}
	
}