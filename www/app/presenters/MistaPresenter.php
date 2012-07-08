<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter míst
 *
 * @author	Milan Pála
 */
class MistaPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';

	protected $model = NULL;

	protected function startup()
	{
		$this->model = new Mista;
		parent::startup();
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('mista', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function renderDefault()
	{
		$this->template->mista = array();

		$this->template->mista['muze_pridat'] = $this->user->isAllowed('mista', 'add');
		$this->template->mista['muze_editovat'] = $this->user->isAllowed('mista', 'edit');
		$this->template->mista['muze_mazat'] = $this->user->isAllowed('mista', 'delete');
		$this->template->mista['mista'] = $this->model->findAll();

		$this->setTitle('Obce sborů nebo sportovišť');
	}

	public function renderEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		if($id == 0) $this->setTitle('Přidání místa');
		else $this->setTitle('Úprava místa');
	}

	public function handleEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		$this->invalidateControl('editForm');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;

		//$form->getElementPrototype()->class('ajax');

		$okresyModel = new Okresy;

		$form->addGroup('Informace o místě');
		$form->addText('obec', 'Obec')
			->addRule(Form::FILLED, 'Je nutné vyplnit název obce.');
		$form->addSelect('id_okresu', 'Okres', $okresyModel->findAllToSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat okres obce.');
		$form->addRequestButton('addOkres', 'Přidat nový', 'Okresy:add');

		$form->addGroup('Uložení');
		$form->addSubmit('save', 'Uložit');
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
			if( $this->isAjax() )
			{
				$this['editForm']->setValues(array(), true);
				$this->invalidateControl('editForm');
			}
			else
			{
				$this->getApplication()->restoreRequest($this->backlink);
				$this->redirect('default');
			}
		}
		elseif($form['save']->isSubmittedBy())
		{
			try
			{
				$dataDoDb = array('obec' => $form['obec']->value, 'id_okresu' => $form['id_okresu']->value);

				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}

				$this->flashMessage('Místo bylo úspěšně uloženo.');

				$this['editForm']->setValues(array(), true);
				$this->invalidateControl('editForm');

				$this->getApplication()->restoreRequest($this->backlink);

				if( $this->isAjax() ) $this->invalidateControl('mista');
				else $this->redirect('default');
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Ukládaná obec již existuje.', 'warning');

				$this->getApplication()->restoreRequest($this->backlink);

				if( $this->isAjax() ) $this->invalidateControl('mista');
				else $this->redirect('default');
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
			$this->flashMessage('Místo bylo úspěšně odstraněno.', 'ok');
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
