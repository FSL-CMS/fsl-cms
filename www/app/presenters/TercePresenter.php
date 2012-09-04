<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter terčů
 *
 * @author	Milan Pála
 */
class TercePresenter extends BasePresenter
{
	protected $model;

	public function startup()
	{
		$this->model = new Terce;
		parent::startup();
	}

	public function actionEdit($id = 0, $backlink = NULL)
	{
		parent::actionEdit($id);
	}

	/**
	 * Připraví výpis všech terčů
	 */
	public function renderDefault()
	{
		$this->template->terce = array();

		$this->template->terce['muze_editovat'] = $this->user->isAllowed('terce', 'edit');
		$this->template->terce['muze_mazat'] = $this->user->isAllowed('terce', 'delete');
		$this->template->terce['terce'] = $this->model->findAll();

		$this->setTitle('Terče používané na závodech');
	}

	/**
	 * Připraví výpis jednich terčů
	 * @param int $id ID terčů
	 */
	public function renderTerce($id)
	{
		$this->template->terc = $this->model->find($id)->fetch();
		$sboryModel = new Sbory;
		$sbor = $sboryModel->find($this->template->terc['id_majitele'])->fetch();

		// nejlepší časy na terče
		$this->template->nejlepsiCasy = $this->model->nejlepsi_casy_terce($id)->fetchAssoc('kategorie,id,=');
		foreach( $this->template->nejlepsiCasy as $kategorie => $foo )
		{
			$i = 1;
			foreach( $foo as $vysledkyKategorie => $bar )
			{
				$this->template->nejlepsiCasy[$kategorie][$vysledkyKategorie]['poradi'] = $i++;
			}
		}
		$this->template->terc['muze_editovat'] = $this->user->isAllowed('terce', 'edit') || $this->jeAutor($sbor['id_spravce']) || $this->jeAutor($sbor['id_kontaktni_osoby']);

		$this->setTitle($this->zvetsPrvni($this->template->terc['typ']).' terče '.$this->template->terc['majitel']);
  	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('terce', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function renderEdit($id = 0, $backlink = NULL)
	{
		if( $id != 0 )
		{
			$zDB = $this->model->find($id)->fetch();
			$this['editForm']->setDefaults($zDB);
		}

		if($backlink !== NULL) $this['editForm']['backlink']->setValue($backlink);

		if( $id == 0 ) $this->setTitle('Přidání terčů');
		else $this->setTitle('Úprava terčů');
	}

	public function createComponentEditForm($name)
	{
		$form = new RequestButtonReceiver($this, $name);
		$typyTercu = new TypyTercu;
		$sbory = new Sbory;

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addHidden('backlink');

		$form->addGroup('Informace o terčích');
		$form->addSelect('id_typu', 'Typ terčů', $typyTercu->findAllToSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vyplnit typ terčů.')
			->setOption('description', $form->addRequestButton('addTypyTercu', 'Přidat nový', 'TypyTercu:add'));
		$form->addSelect('id_majitele', 'Majitel', $sbory->findAlltoSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat majitele terčů.')
			->setOption('description', $form->addRequestButton('addSbory', 'Přidat nový', 'Sbory:add'));
		$form->addAdminTexylaTextArea('text', 'Popis terčů');

		$form->addGroup(null);

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSubmit[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int)$this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{
		}
		elseif($form['save']->isSubmittedBy())
		{
			$dataDoDb = array('id_typu' => $form['id_typu']->value, 'id_sboru' => $form['id_majitele']->value, 'text' => $form['text']->value);
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
				$this->flashMessage('Informace o terčích byly uloženy.');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Informace o terčích se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Terče již existují.', 'warning');
			}
		}

		if($form['save']->isSubmittedBy())
		{
			$this->redirect('edit', $id, $form['backlink']->value);
		}
		else
		{
			$this->getApplication()->restoreRequest($form['backlink']->value);
			RequestButtonHelper::redirectBack();

			if($id != 0) $this->redirect('terce', $id);
			else $this->redirect('add');
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Terče byly odstraněny.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Terče se nepodařilo odstranit.');
			Debug::processException($e);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'error');
		}

		if( $this->isAjax() ) $this->invalidateControl('terce');
		else $this->redirect('this');
	}

}
