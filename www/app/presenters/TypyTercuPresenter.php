<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter typů terčů
 *
 * @author	Milan Pála
 */
class TypyTercuPresenter extends SecuredPresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var TypyTercu */
	protected $model = NULL;

	protected function startup()
	{
		$this->model = $this->context->typyTercu;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->typyTercu = array();

		$this->template->typyTercu['muze_pridat'] = $this->user->isAllowed('typytercu', 'add');
		$this->template->typyTercu['muze_editovat'] = $this->user->isAllowed('typytercu', 'edit');
		$this->template->typyTercu['muze_mazat'] = $this->user->isAllowed('typytercu', 'delete');
		$this->template->typyTercu['typyTercu'] = $this->model->findAll();

		$this->setTitle('Správa typů tečů');
	}

	public function actionAdd()
	{
		$this->setView('edit');
	}

	public function renderEdit($id = 0)
	{
		if( $id != 0 ) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		if($id == 0) $this->setTitle('Přidání typu terčů');
		else $this->setTitle('Úprava typů terčů');
	}

	public function createComponentEditForm()
	{
		$form = new Nette\Application\UI\Form;

		$form->addGroup('Přidání typů terčů');

		$form->addText('nazev', 'Typ terčů')
			->addRule(Form::FILLED, 'Je nutné vyplnit typ terčů.');

		$form->addSubmit('save', Texty::$FORM_SAVE);
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
			$this->getApplication()->restoreRequest($this->backlink);
			$this->redirect('default');
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
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

				$this->flashMessage('Údaje o typu terčů byly uloženy.', 'ok');

				if($form['saveAndReturn']->isSubmittedBy())
				{
					$this->getApplication()->restoreRequest($this->backlink);
					$this->redirect('default');
				}
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Typ terčů již existuje.', 'warning');
				//$this->getApplication()->restoreRequest($this->backlink);
				//$this->redirect('default');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Údaje o typech terčů se nepodařilo uložit.', 'error');
			}
		}
	}

	public function createComponentTypyTercuForm()
	{
		$form = new Nette\Application\UI\Form;

		$form->getElementPrototype()->class('ajax');

		foreach( $this->model->findAll()->fetchAll() as $TypyTercu )
		{
			$poradi = $form->addContainer($TypyTercu['id']);

			$poradi->addHidden('id')->setDefaultValue($TypyTercu['id']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($TypyTercu['poradi']);
		}
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = array($this, 'typyTercuFormSubmitted');

		return $form;
	}

	public function typyTercuFormSubmitted(Nette\Application\UI\Form $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );
			}
			$this->flashMessage('Údaje o typech terčů byly úspěšně uloženy.');

			if( !$this->isAjax() ) $this->redirect('default');
		}
		catch(AlreadyExistException $e)
		{
			$this->flashMessage('Typ terčů již existuje.', 'warning');
			$this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o typech terčů se nepodařilo uložit.', 'error');
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Typ terčů byl úspěšně odstraněn.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Typ terčů se nepodařilo odstranit.', 'error');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'error');
		}

		if( $this->isAjax() ) $this->invalidateControl('typyTercu');
		else $this->redirect('this');
	}

}
