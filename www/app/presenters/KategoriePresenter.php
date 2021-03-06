<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter sportovních kategorií
 *
 * @author	Milan Pála
 */
class KategoriePresenter extends SecuredPresenter
{
	protected $model = NULL;
	/** @persistent */
	public $backlink = '';

	public function startup()
	{
		$this->model = $this->context->kategorie;

		parent::startup();
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('kategorie', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function renderDefault()
	{
		$this->setTitle('Kategorie sportovních týmů');

		$this->template->kategorie = array();

		$this->template->kategorie['muze_pridat'] = $this->user->isAllowed('kategorie', 'add');
		$this->template->kategorie['muze_editovat'] = $this->user->isAllowed('kategorie', 'edit');
		$this->template->kategorie['muze_smazat'] = $this->user->isAllowed('kategorie', 'delete');
		$this->template->kategorie['kategorie'] = $this->model->findAll();
	}

	public function createComponentKategorieForm()
	{
		$form = new Nette\Application\UI\Form;

		$form->getElementPrototype()->class('ajax');

		foreach( $this->model->findAll()->fetchAll() as $kategorie )
		{
			$poradi = $form->addContainer($kategorie['id']);

			$poradi->addHidden('id')->setDefaultValue($kategorie['id']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($kategorie['poradi']);
		}
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = array($this, 'kategorieFormSubmitted');

		return $form;
	}

	public function kategorieFormSubmitted(Nette\Application\UI\Form $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );
			}
			$this->flashMessage('Údaje o kategoriích byly úspěšně uloženy.');

			if( !$this->isAjax() ) $this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o pořadích se nepodařilo uložit.', 'error');
		}
	}

	public function renderEdit($id = 0)
	{
		if( $id != 0 && ($zDB = $this->model->find($id)->fetch()) !== false )
		{
			$this['editForm']->setDefaults($zDB);
		}

		if($id == 0) $this->setTitle('Přidání kategorie');
		else $this->setTitle('Úprava kategorie');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver($this, 'editForm');

		$form->addGroup('Informace o kategorii');
		$form->addText('nazev', 'Název')
			->addRule(Form::FILLED, 'Je nutné vyplnit název kategorie.');
		$form->addText('pocet_startovnich_mist', 'Výchozí počet startovních míst', 4)
			->addRule(Form::FILLED, 'Je nutné vyplnit počet startovních míst.');

		$form->setCurrentGroup(NULL);

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndAdd', 'Uložit a přidat nový');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSuccess[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
			if( $id == 0 ) $this->redirect('default');
			else $this->redirect('default');
		}
		elseif( $form['save']->isSubmittedBy() || $form['saveAndAdd']->isSubmittedBy() )
		{
			$data = $form->getValues();
			$dataDoDB = array( 'nazev' => $data['nazev'], 'pocet_startovnich_mist' => (int)$data['pocet_startovnich_mist']);

			try
			{
				if( $id == 0 )
				{
					$this->model->insert( $dataDoDB );
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update( $id, $dataDoDB );
				}

				$this->flashMessage('Informace o kategorii byly úspěšně uloženy.', 'ok');

				$this->getApplication()->restoreRequest($this->backlink);

				if( $form['save']->isSubmittedBy() ) $this->redirect('default');
				elseif( $form['saveAndAdd']->isSubmittedBy() ) $this->redirect('edit');
				else $this->redirect('default');
			}
			catch( DibiException $e )
			{
				$this->flashMessage('Informace o kategorii se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);

			$this->flashMessage('Kategorie byla odstraněna.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Kategorii se nepodařilo odstranit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'error');
		}

		//$this->redirect('this');
	}


}
