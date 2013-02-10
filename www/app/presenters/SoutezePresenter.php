<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter soutěží
 *
 * @author	Milan Pála
 */
class SoutezePresenter extends SecuredPresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var Souteze */
	protected $model;

	protected function startup()
	{
		$this->model = $this->context->souteze;
		parent::startup();
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('souteze', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function renderDefault()
	{
		$this->template->souteze = array();

		$this->template->souteze['muze_pridat'] = $this->user->isAllowed('souteze', 'add');
		$this->template->souteze['muze_editovat'] = $this->user->isAllowed('souteze', 'edit');
		$this->template->souteze['muze_mazat'] = $this->user->isAllowed('souteze', 'delete');
		$this->template->souteze['souteze'] = $this->model->findAll();

		$this->setTitle('Přehled soutěží');
	}

	public function createComponentSoutezeForm($name)
	{
		$form = new Nette\Application\UI\Form($this, $name);

		$form->getElementPrototype()->class('ajax');

		foreach( $this->model->findAll()->fetchAll() as $soutez )
		{
			$poradi = $form->addContainer($soutez['id']);

			$poradi->addHidden('id')->setDefaultValue($soutez['id']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($soutez['poradi']);
		}
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = array($this, 'soutezeFormSubmitted');
	}

	public function soutezeFormSubmitted(Nette\Application\UI\Form $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );
			}
			$this->flashMessage('Pořadí bylo uloženo.', 'ok');

			if( !$this->isAjax() ) $this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o kategoriích se nepodařilo uložit.', 'error');
		}
	}

	public function renderEdit($id = 0)
	{
		$kategorieModel = $this->context->kategorie;
		$this->template->body = array('body' => array());
		$this->template->kategorie = array('kategorie' => array());
		$this->template->kategorie['kategorie'] = $kategorieModel->findAll();
		if( $id != 0 )
		{
			$defaultValues = $this->model->find($id)->fetch();
			$defaultValues['kategorie'] = $kategorieModel->findBySoutez($id)->fetchAssoc('id');
			$defaultValues['body'] = $this->template->body['body'];
			$this['editForm']->setValues($defaultValues);
		}

		if($id == 0) $this->setTitle('Přidání soutěže');
		else $this->setTitle('Úprava soutěže');
	}

	public function createComponentEditForm($name)
	{
		$id = $this->getParam('id');
		$form = new RequestButtonReceiver($this, $name);
		$bodoveTabulkyModel = $this->context->bodoveTabulky;
		$kategorieModel = $this->context->kategorie;
		$kategorie = $kategorieModel->findAllToSelect()->fetchPairs('id', 'nazev');

		$bodoveTabulky = $bodoveTabulkyModel->findAllToSelect()->fetchPairs('id','nazev');
		if(count($bodoveTabulky) == 0) $bodoveTabulky = array(0 => 'žádná není');

		$form->addGroup('Informace o soutěži');
		$form->addText('nazev', 'Název', 50)
			->addRule(Form::FILLED, 'Je nutné vyplnit název soutěže.')
			   ->addRule(Form::MAX_LENGTH, 'Název může mít maximálně %d znaků.', 255);
		$form->addAdminTexylaTextArea('popis', 'Popis soutěže');

		$form->addGroup('Sportovní kategorie a bodové tabulky');
		$form->addRequestButton('addKategorie', 'Přidat novou kategorii', 'Kategorie:add');
		$form->addRequestButton('addBodovaTabulka', 'Přidat novou bodovou tabulku', 'BodoveTabulky:add');
		$form->addRequestButton('editBodovaTabulka', 'Spravovat bodové tabulky', 'BodoveTabulky:default');

		$kategorieCont = $form->addContainer('kategorie');
		foreach($kategorie as $key => $val)
		{
			$kategorieCont->setCurrentGroup($form->addGroup('Kategorie '.$val, false));
			$katCont = $kategorieCont->addContainer($key);
			$form->addGroup('Kategorie '.$val, true);
			$katCont->addHidden('kategorie_souteze_id');
			$katCont->addCheckbox('id', $val.' - tato kategorie se může účastnit soutěže');
			$katCont->addSelect('id_bodove_tabulky', 'Výchozí bodová tabulka', $bodoveTabulky);
			//$katCont->addRequestButton('addBodoveTabulky', 'Přidat novou', 'BodoveTabulky:add');
		}

		$form->addGroup('Uložení');
		$form->addSubmit('save', 'Uložit a pokračovat v úpravách');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět')
			->setValidationScope(false);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
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
			$data = $form->getValues();
			$kategorieModel = $this->context->kategorie;
			$kategorieSoutezeModel = $this->context->kategorieSouteze;
			try
			{
				$dataDoDb = array('nazev' => $data['nazev'], 'popis' => $data['popis'], /*'platnost_od' => $data['platnost_od'], 'platnost_do' => $data['platnost_do']*/);

				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}
				$this->flashMessage('Soutěž byla úspěšně uložena.', 'ok');

				$kategorie = $kategorieModel->findBySoutez($id)->fetchAssoc('id,=');

				$kategorie_insert = array();
				$kategorie_update = array();
				$kategorie_delete = array();
				foreach($data['kategorie'] as $key => $val)
				{
					if( $val['id'] === true && !isset($kategorie[$key])) $kategorie_insert[] = array('id_kategorie' => (int)$key, 'id_souteze' => (int)$id, 'id_bodove_tabulky' => (int)$val['id_bodove_tabulky']);
					if( $val['id'] === true && isset($kategorie[$key])) $kategorie_update[$val['kategorie_souteze_id']] = array('id_bodove_tabulky' => (int)$val['id_bodove_tabulky']);
					if( $val['id'] === false && isset($kategorie[$key]) ) $kategorie_delete[] = $kategorie[$key]['kategorie_souteze_id'];
				}
				foreach($kategorie_insert as $kat) $kategorieSoutezeModel->insert($kat);
				foreach($kategorie_update as $key => $kat) $kategorieSoutezeModel->update($key, $kat);
				foreach($kategorie_delete as $kat) $kategorieSoutezeModel->delete($kat);

				$this->getApplication()->restoreRequest($this->backlink);
				if( $form['saveAndReturn']->isSubmittedBy() ) $this->redirect('default');
				else
				{
					$this->redirect('edit', $id);
				}
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Soutěž se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Soutěž již existuje.', 'warning');
			}
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Sportoviště bylo úspěšně odstraněno.');
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

		$this->redirect('this');
	}
}
