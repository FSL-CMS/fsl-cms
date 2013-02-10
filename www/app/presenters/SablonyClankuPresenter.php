<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter šablon článků
 *
 * @author	Milan Pála
 */
class SablonyClankuPresenter extends SecuredPresenter
{
	/** @var SablonyClanku */
	protected $model;

	/** @persistent */
	public $backlink = '';

	public function startup()
	{
		$this->model = $this->context->sablonyClanku;
		parent::startup();
	}

	public function actionEdit($id = 0, $backlink = NULL)
	{
		parent::actionEdit($id);
	}

	/**
	 * Připraví výpis všech šablon
	 */
	public function renderDefault()
	{
		$this->template->sablony = array();

		$this->template->sablony['sablony'] = $this->model->findAll();

		$this->setTitle('Šablony článků');
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('sablonyclanku', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function renderEdit($id = 0, $backlink = NULL)
	{
		if( $id != 0 )
		{
			$zDB = $this->model->find($id)->fetch();
			$this['editForm']->setDefaults($zDB);

			$this->template->sablona = $zDB;
		}

		if($backlink !== NULL) $this['editForm']['backlink']->setValue($backlink);

		if( $id == 0 ) $this->setTitle('Přidání šablony');
		else $this->setTitle('Úprava šablony');
	}

	public function createComponentEditForm($name)
	{
		$form = new RequestButtonReceiver($this, $name);
		$id = $this->getParam('id', 0);

		$form->addHidden('backlink');

		$form->addGroup('Informace o šabloně');
		$form->addText('nazev', 'Název šablony')
			->addRule(Form::FILLED, 'Je nutné vyplnit název šablony.')
			->setOption('description', 'Název se zobrazuje pouze při úpravě článku, v samotném článku dostupný není.');
		$form->addSelect('obrazek_umisteni', 'Umístění obrázku vedle článku', array('vlevo' => 'vlevo', 'vpravo' => 'vpravo'))
			->addRule(Form::FILLED, 'Je nutné vybrat umístění obrázku.');
		$form->addFile('obrazek', 'Nový obrázek');
		if($id == 0) {
			$form['obrazek']->addRule(Form::FILLED, 'Je nutné vybrat obrázek.');
		}

		$form->addGroup(null);

		$form->addSubmit('save', Texty::$FORM_SAVE);
		$form->addSubmit('saveAndReturn', Texty::$FORM_SAVEANDRETURN);
		$form->addSubmit('cancel', Texty::$FORM_CANCEL)
			->setValidationScope(false);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int)$this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			$dataDoDb = array('obrazek_umisteni' => $form['obrazek_umisteni']->value, 'nazev' => $form['nazev']->value);
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

				if($form['obrazek']->value instanceof HttpUploadedFile && $form['obrazek']->value->getError() != UPLOAD_ERR_NO_FILE) {
					if($form['obrazek']->value->getError() !== UPLOAD_ERR_OK) throw new Exception('Obrázek se nepodařilo nahrát v pořádku, chyba '.$form['obrazek']->value->getError().'.');

					$stary = $this->model->find($id)->fetch();

					$fotka = $this->context->fotky;
					$fotka->setSoubor($form['obrazek']->value);
					$fotka->setAutor($this->user->getIdentity()->id);
					$fotka->uloz($id, 'sablony_clanku');

					$fotkyModel = $this->context->fotky;
					$fotkyModel->delete($stary['obrazek_id']);
				}

				$this->flashMessage('Informace šabloně byly uloženy.');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Informace o šabloně se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Šablona s tímto názvem už existuje.', 'warning');
			}
			catch(Exception $e)
			{
				$this->flashMessage($e->getMessage(), 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
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

			$this->redirect('default');
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Šablona článku byla odstraněna.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Šablonu se nepodařilo odstranit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}

		$this->redirect('this');
	}

}
