<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Presenter ročníků soutěží
 *
 * @author	Milan Pála
 */
class NastaveniPresenter extends SecuredPresenter
{

	/** @var Nastaveni */
	protected $model;

	protected function startup()
	{
		parent::startup();

		$this->model = $this->context->nastaveni;
	}

	public function renderDefault()
	{
		$this['editForm']->setValues($this->model->find()->fetch());

		$this->setTitle('Nastavení');
	}

	public function createComponentEditForm($name)
	{
		$form = new Form($this, $name);

		$form->addGroup('Informace o lize');
		$form->addText('liga_nazev', 'Název ligy', 50, 255)
				->addRule(Form::FILLED, 'Je nutné vyplnit název ligy.');
		$form->addText('liga_zkratka', 'Zkratka ligy', 50, 255)
				->addRule(Form::FILLED, 'Je nutné vyplnit zkratku ligy.');
		$form->addText('liga_popis', 'Popis ligy', 50, 255);

		$form->addGroup(Texty::$FORM_SAVEGROUP);
		$form->addSubmit('save', Texty::$FORM_SAVE);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(Form $form)
	{
		if($form['save']->isSubmittedBy())
		{
			try
			{
				$dataDoDb = array('liga_nazev%s' => $form['liga_nazev']->value, 'liga_zkratka%s' => $form['liga_zkratka']->value, 'liga_popis%s' => $form['liga_popis']->value);
				$this->model->save($dataDoDb);

				$this->flashMessage('Nastavení bylo uloženo.', 'ok');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Nastavení se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}
		$this->redirect('this');
	}

	public function actionVysledkyPredZavodem($id)
	{
		$zavody = $this->context->zavody;
		if($this->user->isAllowed('rocniky', 'edit')) $zavody->zobrazitNezverejnene();
		$zavod = $zavody->find($id)->fetch();
		if(!$zavod) throw new BadRequestException();

		$this->forward('vysledky', array('id' => $zavod->id_rocniku, 'id_zavodu' => $id));
	}
}
