<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter okresů
 *
 * @author	Milan Pála
 */
class OkresyPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var Okresy */
	protected $model = NULL;

	protected function startup()
	{
		$this->model = $this->context->okresy;
		parent::startup();
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('okresy', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function renderDefault()
	{
		$this->template->okresy = array();

		$this->template->okresy['muze_pridat'] = $this->user->isAllowed('okresy', 'add');
		$this->template->okresy['muze_editovat'] = $this->user->isAllowed('okresy', 'edit');
		$this->template->okresy['muze_mazat'] = $this->user->isAllowed('okresy', 'delete');
		$this->template->okresy['okresy'] = $this->model->findAll();

		$this->setTitle('Správa okresů');
	}

	public function renderEdit($id = 0)
	{
		if($id != 0) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		if($id == 0) $this->setTitle('Přidání nového okresu');
		else $this->setTitle('Úprava okresu');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;

		$form->addHidden('id')->setDefaultValue(0);

		$form->addGroup('Informace o okresu');
		$form->addText('nazev', 'Název')
			->addRule(Form::FILLED, 'Je nutné vyplnit název okresu.');
		$form->addText('zkratka', 'Zkratka')
			->addRule(Form::FILLED, 'Je nutné vyplnit zkratku okresu.');

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět')
			->setValidationScope(false);

		$form->onSuccess[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(RequestButtonReceiver $form)
	{
		$id = (int)$form['id']->value;

		if($form['cancel']->isSubmittedBy())
		{
			$this->redirect('default');
		}
		elseif($form['save']->isSubmittedBy())
		{
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

				$this->flashMessage('Informace o okresu byly úspěšně uloženy.', 'ok');

				$this['editForm']->setValues(array(), true);
				$this->invalidateControl('editForm');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit okres.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}

			$this->getApplication()->restoreRequest($this->backlink);

			if( $this->isAjax() ) $this->invalidateControl('okresy');
			else $this->redirect('Okresy:default');
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Okres byl úspěšně odstraněn.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Okres se nepodařilo odstranit.');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'error');
		}

		if( $this->isAjax() ) $this->invalidateControl('okresy');
		else $this->redirect('this');
	}
}
