<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter témat diskuze
 *
 * @author	Milan Pála
 */
class TemataPresenter extends SecuredPresenter
{
	/** @persistent */
	public $backlink = '';

	/** @var Temata */
	protected $model = NULL;

	protected function startup()
	{
		$this->model = $this->context->temata;

		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->temata = array();

		$this->template->temata['muze_pridat'] = $this->user->isAllowed('temata', 'add');
		$this->template->temata['muze_editovat'] = $this->user->isAllowed('temata', 'edit');
		$this->template->temata['muze_mazat'] = $this->user->isAllowed('temata', 'delete');
		$this->template->temata['temata'] = $this->model->findAll();

		$this->setTitle('Správa témat diskuzí');
	}

	public function handleEdit($id = 0)
	{
		if($id != 0) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		$this->invalidateControl('editForm');

		if($id == 0) $this->setTitle ('Přidání tématu diskuze');
		else $this->setTitle('Úprava tématu diskuze');
	}

	public function actionAdd()
	{
		parent::actionAdd();
		$this->setView('edit');
	}

	public function actionEdit($id = 0)
	{
		parent::actionEdit($id);

		if($id != 0) $this['editForm']->setDefaults($this->model->find($id)->fetch());

		if( $id == 0 ) $this['editForm']->addGroup('Přidání tématu diskuze', true);
		else $this['editForm']->addGroup('Úprava tématu diskuze', true);

		$this->invalidateControl('editForm');

		if($id == 0) $this->setTitle ('Přidání tématu diskuze');
		else $this->setTitle('Úprava tématu diskuze');
	}

	public function createComponentEditForm($name)
	{
		$form = new Nette\Application\UI\Form($this, $name);

		$uzivatele = $this->context->uzivatele;

		$form->addGroup('Úprava tématu diskuze', true);

		$form->addText('nazev', 'Název tématu')
			->addRule(Form::FILLED, 'Je nutné vyplnit název tématu.');
		$form->addSelect('id_autora', 'Autor tématu', $uzivatele->findAlltoSelect()->fetchPairs('id', 'uzivatel'))
			->addRule(Form::FILLED, 'Je nutné vyplnit autora tématu.')
			   ->setDefaultValue($this->getUser()->getIdentity()->id);
		$form->addSelect('souvisejici', 'Související téma', array('' => 'žádné')+self::$SOUVISEJICI)
			   ->setOption('description', 'Jaké související položky se budou ke komentáři nabízet.');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(FALSE);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
			$this->invalidateControl('editForm');

			if($id == 0) $this->redirect('Temata:default');
			else $this->redirect('Temata:tema', $id);
		}
		elseif( $form['save']->isSubmittedBy() )
		{
			try
			{
				$data = array('nazev' => $form['nazev']->value, 'id_autora' => $form['id_autora']->value, 'souvisejici%sn' => $form['souvisejici']->value);
				if($id == 0)
				{
					$this->model->insert($data);
					$id = $this->model->lastInsertedId();
					$this->flashMessage('Téma bylo úspěšně založeno.', 'ok');
				}
				else
				{
					$this->model->update($id, $data);
					$this->flashMessage('Téma bylo úspěšně aktualizováno.', 'ok');
				}
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Téma se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}
		$this->redirect('Temata:default');
	}

	public function createComponentTemataForm($name)
	{
		$form = new Nette\Application\UI\Form($this, $name);

		foreach( $this->model->findAll()->fetchAll() as $temata )
		{
			$poradi = $form->addContainer($temata['id']);

			$poradi->addHidden('id')->setDefaultValue($temata['id']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($temata['poradi']);
		}
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = array($this, 'temataFormSubmitted');
	}

	public function temataFormSubmitted(Nette\Application\UI\Form $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );
			}
			$this->flashMessage('Údaje o tématech byly úspěšně uloženy.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o tématech se nepodařilo uložit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('default');
	}

	public function handleDelete($id, $force = 0)
	{
		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Téma bylo úspěšně odstraněno.');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Téma se nepodařilo odstranit.', 'error');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage().' <a href="'.$this->link('delete!', array('id' => $id, 'force' => true)).'">Přesto smazat!</a>', 'error');
		}

		if( $this->isAjax() ) $this->invalidateControl('temata');
		else $this->redirect('this');
	}

}
