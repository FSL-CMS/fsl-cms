<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter typů sborů
 *
 * @author	Milan Pála
 */
class TypySboruPresenter extends SecuredPresenter
{
	protected $model = NULL;

	/** @persistent */
	public $backlink = '';

	protected function startup()
	{
		$this->model = new TypySboru;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->typySboru = array();

		$this->template->typySboru['muze_pridat'] = $this->user->isAllowed('typysboru', 'add');
		$this->template->typySboru['muze_editovat'] = $this->user->isAllowed('typysboru', 'edit');
		$this->template->typySboru['muze_mazat'] = $this->user->isAllowed('typysboru', 'delete');
		$this->template->typySboru['typySboru'] = $this->model->findAll();

		$this->setTitle('Typy sborů');
	}

	public function handleEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		$this->invalidateControl('editForm');

		if($id == 0) $this->setTitle('Přidání typu sboru');
		else $this->setTitle('Úprava typu sboru');
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('typysboru', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Typ sborů byl úspěšně odstraněn.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Typ sborů se nepodařilo odstranit.', 'error');
			Debug::processException($e, true);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}

		if( $this->isAjax() ) $this->invalidateControl('typySboru');
		else $this->redirect('this');
	}

	public function createComponentEditForm()
	{
		$form = new AppForm;

		$form->getElementPrototype()->class('ajax');

		$form->addGroup('Přidání typu sboru');

		$form->addHidden('id');

		$form->addText('nazev', 'Typ sboru')
			->addRule(Form::FILLED, 'Je nutné vyplnit typ sboru.');
		$form->addText('zkratka', 'Zkratka')
			->addRule(Form::FILLED, 'Je nutné vyplnit zkratku kategorie.');

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(AppForm $form)
	{
		if($form['cancel']->isSubmittedBy())
		{
		}
		elseif($form['save']->isSubmittedBy())
		{
			$id = $form['id']->value;
			try
			{
				$dataDoDb = array('nazev' => $form['nazev']->value, 'zkratka' => $form['zkratka']->value);
				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}
				$this->flashMessage('Typ sboru byl uložen.', 'ok');
			}
			catch(AlreadyExistsException $e)
			{
				$this->flashMessage($e->getMessage(), 'warning');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit typ sboru.', 'error');
				Debug::processException($e, true);
			}
		}

		$this->getApplication()->restoreRequest($this->backlink);

		if( $this->isAjax() ) $this->invalidateControl('typySboru');
		else $this->redirect('this');
	}

}