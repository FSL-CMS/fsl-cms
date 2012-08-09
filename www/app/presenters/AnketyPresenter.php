<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter správy anket
 *
 * @author	Milan Pála
 */
class AnketyPresenter extends BasePresenter
{

	protected $model;

	protected function startup()
	{
		$this->model = new Ankety;
		parent::startup();
	}

	/**
	 * Připraví přehled všech anket
	 */
	public function renderDefault()
	{
		$this->template->ankety = array();

		$this->template->ankety['ankety'] = $this->model->findAll();
		$this->template->ankety['muze_pridavat'] = $this->user->isAllowed('ankety', 'add');
		$this->template->ankety['muze_editovat'] = $this->user->isAllowed('ankety', 'edit');

		$this->setTitle('Ankety');
	}

	public function actionAnketa($id = 0)
	{
		if( $id == 0 ) $this->redirect('default');

		if( !$this->model->find($id)->fetch() ) throw new BadRequestException('Anketa nebyl nalezen.');

		$this->redirect('default#anketa-'.$id);
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
		$anketa = array();
		if( $id != 0 )
		{
			$anketa['answers'] = array();
			$anketa['answers'] = $this->model->find($id)->fetchAssoc('id,=');
			$anketa += current($anketa['answers']);
		}
		if( $id != 0) $this['editForm']->setDefaults($anketa);

		if( $id == 0 ) $this->setTitle('Přidání nové ankety');
		else $this->setTitle('Úprava ankety');
	}

	public function createComponentEditForm()
	{
		$id = (int) $this->getParam('id');

		$form = new AppForm($this, 'editForm');
		$form->addGroup('Informace o anketě');
		$form->addText('question', 'Otázka', 50, 255)
			->addRule(Form::FILLED, 'Je nutné vyplnit anketní otázku.')
			->addRule(Form::MAX_LENGTH, 'Maximální délka otázky je %d znaků.', 255);

		$odpovedi = $this->model->findOdpovediByAnketa($id)->fetchAll();
		$odpovedi+= array('id' => 'nove');
		$form->addGroup('Odpovědi');
		$odpovediCont = $form->addContainer('answers');
		foreach($odpovedi as $odpoved)
		{
			$odpovedCont = $odpovediCont->addContainer($odpoved['id']);
			$t = $odpovedCont->addText('answer', 'Odpověď', 50, 255);
			if( intval($odpoved['id']) != 0 )
				$t->setOption('description', Html::el('a', 'Odebrat!')->href($this->link('smazOdpoved!', array($id, $odpoved['id'])))->class('delete'));
		}
		$form->addSubmit('addNext', 'Přidat další odpoveď');

		if( $id == 0 ) $zverejneni = array('ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		elseif( $id != 0 && !$this->model->jeZverejnena($id) ) $zverejneni = array('ponechat' => 'nechat bez změny', 'ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		else $zverejneni = array('ponechat' => 'nechat bez změny', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		$form->addGroup('Zveřejnění ankety');
		$form->addRadioList('zverejneni', 'Zveřejnění ankety', $zverejneni)
			->addRule(Form::FILLED, 'Je nutné vyplnit, kdy se má anketa zveřejnit.');
		$form->addDateTimePicker('datum_zverejneni', 'Datum zveřejnění ankety')
			->addConditionOn($form['zverejneni'], Form::EQUAL, 'datum_zverejneni')
				->addRule(Form::FILLED, 'Je nutné vyplnit datum zveřejnění ankety.');

		if( $id == 0 ) $form['zverejneni']->setDefaultValue('ihned');
		else $form['zverejneni']->setDefaultValue('ponechat');

		$form->addGroup('Uložit');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(FALSE);;

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
			if( $id == 0 ) $this->redirect('Ankety:default');
			else $this->redirect('Ankety:anketa', $id);
		}
		else
		{
			$data = $form->getValues();

			$dataDoDB = array( 'question' => $data['question'] );

			try
			{
				if( $data['zverejneni'] == 'ulozit' || $form['addNext']->isSubmittedBy() ) $dataDoDB['datum_zverejneni%sn'] = '';
				elseif( $data['zverejneni'] == 'ihned' ) $dataDoDB['datum_zverejneni%sql'] = "NOW()";
				elseif( $data['zverejneni'] == 'ponechat' ) {}
				else $dataDoDB['datum_zverejneni%t'] = $data['datum_zverejneni'];

				if( $id == 0 )
				{
					$dataDoDB['id_autora'] = $this->user->getIdentity()->id;
					$dataDoDB['datum_pridani%sql'] = 'NOW()';
					$this->model->insert( $dataDoDB );
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update( $id, $dataDoDB );
				}

				foreach($data['answers'] as $idod => $odpoved)
				{
					if( intval($idod) == 0 && !empty($odpoved['answer']) ) $this->model->pridejOdpoved($id, $odpoved);
					else $this->model->upravOdpoved($idod, $odpoved);
				}
				$this->flashMessage('Anketa byla úspěšně uložena.', 'ok');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit anketu.', 'error');
				Debug::processException($e, true);
			}

			if( !empty($data['souvisejici']) && !empty($data['souvisejici']['souvisejici']) )
				{
					$dataDoDB = array('rodic' => 'ankety', 'id_rodice' => $id);
					$dataDoDB['souvisejici'] = strtolower($data['souvisejici']['souvisejici']);
					$dataDoDB['id_souvisejiciho'] = $data['souvisejici']['id_souvisejiciho'.$data['souvisejici']['souvisejici']];
					$souvisejici = new Souvisejici;
					try
					{
						$souvisejici->insert($dataDoDB);
					}
					catch(DibiException $e)
					{
						$this->flashMessage('Nepodařilo se uložit související položku.', 'error');
						Debug::processException($e, true);
					}
				}

			if( $form['saveAndReturn']->isSubmittedBy() ) $this->redirect('Ankety:anketa', $id);
			else $this->redirect('Ankety:edit', $id);
		}
	}

	public function actionDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Anketa byla odstraněna.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flahMessage('Anketu se nepodařilo odstranit.', 'error');
		}
		$this->redirect('Ankety:default');
	}

	public function handleSmazOdpoved($id, $id_odpovedi)
	{
		try
		{
			$this->model->smazOdpoved($id_odpovedi);
			$this->flashMessage('Odpověď byla odstraněna.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flahMessage('Odpověď se nepodařilo odstranit.', 'error');
		}

		$this->redirect('this');
	}

}
