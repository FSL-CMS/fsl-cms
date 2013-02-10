<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter typů sborů
 *
 * @author	Milan Pála
 */
class TypySboruPresenter extends BasePresenter
{
	/** @var TypySboru */
	protected $model;

	/** @persistent */
	public $backlink = '';

	protected function startup()
	{
		$this->model = $this->context->typySboru;
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

	public function renderEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

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
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}

		$this->redirect('this');
	}

	public function createComponentEditForm($name)
	{
		$form = new RequestButtonReceiver($this, $name);

		$form->addGroup('Přidání typu sboru');

		$form->addHidden('id');

		$form->addText('nazev', 'Typ sboru')
			->addRule(Form::FILLED, 'Je nutné vyplnit typ sboru.');
		$form->addText('zkratka', 'Zkratka')
			->addRule(Form::FILLED, 'Je nutné vyplnit zkratku kategorie.');

		$form->addSubmit('save', Texty::$FORM_SAVE);
		$form->addSubmit('saveAndReturn', Texty::$FORM_SAVEANDRETURN);
		$form->addSubmit('cancel', Texty::$FORM_CANCEL)
			->setValidationScope(false);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		if($form['cancel']->isSubmittedBy())
		{
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
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
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}

		if($form['cancel']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			$this->getApplication()->restoreRequest($this->backlink);
			RequestButtonHelper::redirectBack($form);
			$this->redirect('default');
		}
		else
		{
			$this->redirect('this');
		}
	}

}
