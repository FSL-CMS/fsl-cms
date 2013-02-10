<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter bodových tabulek
 *
 * @author	Milan Pála
 */
class BodoveTabulkyPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var BodoveTabulky */
	protected $model;

	protected function startup()
	{
		$this->model = $this->context->bodoveTabulky;
		parent::startup();
	}

	/**
	 * Připraví přehled všech anket
	 */
	public function renderDefault()
	{
		$this->template->bodoveTabulky = array();
		$bodyModel = $this->context->body;

		$this->template->bodoveTabulky['bodoveTabulky'] = $this->model->findAll()->fetchAssoc('id');
		foreach( $this->template->bodoveTabulky['bodoveTabulky'] as $tabulka )
		{
			$this->template->bodoveTabulky['bodoveTabulky'][$tabulka['id']]['body'] = $bodyModel->findByTabulka($tabulka['id'])->fetchAll();
		}
		$this->template->bodoveTabulky['muze_pridavat'] = $this->user->isAllowed('bodovetabulky', 'add');
		$this->template->bodoveTabulky['muze_editovat'] = $this->user->isAllowed('bodovetabulky', 'edit');

		$this->setTitle('Bodové tabulky');
	}

	public function actionEdit($id = 0)
	{
		if( $id == 0 ) $this->redirect('add');

		if( !$this->user->isLoggedIn() )
		{
			$backlink = $this->getApplication()->storeRequest();
			$this->flashMessage('Nejste přihlášen.');
			$this->forward('Sprava:login', $backlink);
		}

		if( $id != 0 && !$this->model->find($id)->fetch() ) throw new BadRequestException('Anketa nebyla nalezen.');
	}

	public function actionAdd()
	{
		parent::actionAdd();
		$this->setView('edit');
	}

	public function renderEdit($id = 0)
	{
		$body = array();
		$bodyModel = $this->context->body;
		if( $id != 0 )
		{
			$body = $this->model->find($id)->fetch();
			$body['body'] = $bodyModel->findByTabulka($id)->fetchAll();
		}
		if( $id != 0) $this['editForm']->setDefaults($body);

		if( $id == 0 ) $this->setTitle('Přidání nové bodové tabulky');
		else $this->setTitle('Úprava bodové tabulky');
	}

	public function createComponentEditForm($name)
	{
		$id = (int) $this->getParam('id');
		$kategorieSoutezeModel = $this->context->kategorieSouteze;

		$form = new RequestButtonReceiver($this, $name);
		$form->addGroup('Informace o bodové tabulce');
		$form->addText('pocet_bodovanych_pozic', 'Počet bodovaných pozic', 3, 3)
			->addRule(Form::FILLED, 'Je nutné uvést počet bodovaných pozic.');

		if( $id != 0 )
		{
			$bodovaTabulka = $this->model->find($id)->fetch();

			$form->addGroup('Bodové hodnocení');
			$bodyCont = $form->addContainer('body');
			for($i=0; $i<$bodovaTabulka['pocet_bodovanych_pozic']; $i++)
			{
				$bodCont = $bodyCont->addContainer($i);
				$bodCont->addHidden('id');
				$bodCont->addText('poradi', 'Pořadí', 3, 3)->setDisabled(true)->getControlPrototype()->class('poradi');
				$bodCont->addText('body', 'Bodové hodnocení', 3, 3);
			}
		}

		$form->addGroup('Uložit');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zpět')
			->setValidationScope(FALSE);;

		$form->onSuccess[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
			RequestButtonHelper::redirectBack();
			$this->getApplication()->restoreRequest($this->backlink);
			if( $id == 0 ) $this->redirect('default');
			else $this->redirect('default');
		}
		else
		{
			$data = $form->getValues();
			$dataDoDB = array( 'pocet_bodovanych_pozic' => $data['pocet_bodovanych_pozic'] );

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

				if(isset($data['body'])) foreach($data['body'] as $odpoved)
				{
					$bodyModel = $this->context->body;
					$bodyModel->update($odpoved['id'], array('body' => $odpoved['body']));
				}
				$this->flashMessage('Tabulka byla úspěšně uložena.', 'ok');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit bodovou tabulku.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}

			if( $form['saveAndReturn']->isSubmittedBy() )
			{
				RequestButtonHelper::redirectBack();
				$this->getApplication()->restoreRequest($this->backlink);
				$this->redirect('default');
			}
			else
			{
				$this->redirect('edit', $id);
			}
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Bodová tabulka byla odstraněna.', 'ok');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Bodovou tabulku se nepodařilo odstranit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('BodoveTabulky:default');
	}
}
