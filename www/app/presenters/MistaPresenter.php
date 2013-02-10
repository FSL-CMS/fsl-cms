<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter míst
 *
 * @author	Milan Pála
 */
class MistaPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var Mista */
	protected $model;

	protected function startup()
	{
		$this->model = $this->context->mista;
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

	public function renderEdit($id = 0, $backlink = NULL)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		if($backlink !== NULL) $this['editForm']['backlink']->setValue($backlink);

		if($id == 0) $this->setTitle('Přidání místa');
		else $this->setTitle('Úprava místa');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;

		//$form->getElementPrototype()->class('ajax');

		$okresyModel = $this->context->okresy;

		$form->addHidden('backlink');

		$form->addGroup('Informace o místě');
		$form->addText('obec', 'Obec')
			->addRule(Form::FILLED, 'Je nutné vyplnit název obce.');
		$form->addSelect('id_okresu', 'Okres', $okresyModel->findAllToSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat okres obce.');
		$form->addRequestButton('addOkres', 'Přidat nový', 'Okresy:add');

		$form->addGroup('Uložení');
		$form->addSubmit('saveAndReturn', Texty::$FORM_SAVEANDRETURN);
		$form->addSubmit('cancel', Texty::$FORM_CANCEL)
			->setValidationScope(false);

		$form->onSuccess[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int)$this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{
			$this->getApplication()->restoreRequest($form['backlink']->value);
			RequestButtonHelper::redirectBack();

			$this->redirect('default');
		}
		elseif($form['saveAndReturn']->isSubmittedBy())
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

				$this->getApplication()->restoreRequest($form['backlink']->value);
				RequestButtonHelper::redirectBack();

				$this->redirect('default');
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Ukládaná obec již existuje.', 'warning');

				$this->getApplication()->restoreRequest($this->backlink);
				RequestButtonHelper::redirectBack();

				$this->redirect('default');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Místo se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
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
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}

		if( $this->isAjax() ) $this->invalidateControl('mista');
		else $this->redirect('this');
	}
}
