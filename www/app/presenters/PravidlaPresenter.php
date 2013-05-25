<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Presenter pravidel
 *
 * @author	Milan Pála
 */
class PravidlaPresenter extends BasePresenter
{

	/** @persistent */
	public $backlink = '';

	/** @var Pravidla */
	protected $model;

	/** @var DibiRow */
	protected $pravidla;

	public function startup()
	{
		$this->model = $this->context->pravidla;
		parent::startup();
	}

	public function actionDefault()
	{
		$posledni = $this->model->findLast()->fetch();
		if($posledni === false)
		{
			$this->flashMessage('Neexistují pravidla k poslednímu ročníku.', 'warning');
			$this->redirect('Rocniky:');
		}
		else $this->redirect('pravidla', $posledni['id']);
	}

	public function actionEdit($id = 0)
	{
		parent::actionEdit($id);
	}

	public function actionPravidla($id = 0)
	{
		if($id == 0) $this->redirect('default');
		$this->template->pravidla = $this->model->find($id)->fetch();
		if(!$this->template->pravidla)
		{
			$this->flashMessage('Požadovaná pravidla neexistují.', 'warning');
			$this->redirect('default');
		}
	}

	/**
	 * Připraví výpis jednich pravidel
	 * @param int $id ID pravidel
	 */
	public function renderPravidla($id)
	{
		$this->template->pravidla['muze_editovat'] = $this->user->isAllowed('pravidla', 'edit');

		$this['prehledRocniku']->setRocnik($this->template->pravidla['id_rocniku']);

		$this->setTitle('Pravidla pro sezónu ' . $this->template->pravidla['rok']);
	}

	public function actionAdd()
	{
		throw new BadRequestException();
	}

	public function renderEdit($id = 0)
	{
		if($id != 0)
		{
			$zDB = $this->model->find($id)->fetch();
			$this['editForm']->setDefaults($zDB);
		}

		if($id == 0) $this->setTitle('Přidání pravidel');
		else $this->setTitle('Úprava pravidel');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver($this, 'editForm');

		$form->addGroup('Informace o pravidlech');
		$form->addAdminTexylaTextArea('pravidla', 'Pravidla', null, 30, $this->getPresenter()->getName(), $this->getParam('id', 0));

		$form->addGroup();
		$form->addSubmit('save', Texty::$FORM_SAVE);
		$form->addSubmit('saveAndReturn', Texty::$FORM_SAVEANDRETURN);
		$form->addSubmit('cancel', Texty::$FORM_CANCEL)
			   ->setValidationScope(false);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			$dataDoDb = array('pravidla' => $form['pravidla']->value);
			try
			{
				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}

				$this->flashMessage('Pravidla byla uložena.');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Pravidla se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}

		if($form['saveAndReturn']->isSubmittedBy() || $form['cancel']->isSubmittedBy()) $this->redirect('Pravidla:pravidla', $id);
		else $this->redirect('this');
	}

}
