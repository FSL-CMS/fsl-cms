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
	protected $model;

	public function startup()
	{
		$this->model = new Pravidla;
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
		if(!$this->model->find($id)->fetch())
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
		$this->template->pravidla = $this->model->find($id)->fetch();
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

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o pravidlech');
		$form->addAdminTexylaTextArea('pravidla', 'Pravidla');

		$form->setCurrentGroup(NULL);

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			   ->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSubmit[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{
			$this->redirect('Terce:default');
		}
		elseif($form['save']->isSubmittedBy())
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
				$this->redirect('Pravidla:pravidla', $id);
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Pravidla se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
		}
	}

}
