<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter sborů
 *
 * @author	Milan Pála
 */
class SboryPresenter extends BasePresenter
{

	/** @persistent */
	public $backlink = '';

	protected $model;

	protected function startup()
	{
		$this->model = new Sbory;
		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->sbory = array();

		$this->template->sbory['muze_editovat'] = $this->user->isAllowed('sbory', 'edit');
		$this->template->sbory['muze_pridavat'] = $this->user->isAllowed('sbory', 'add');
		$this->template->sbory['muze_smazat'] = $this->user->isAllowed('sbory', 'delete');
		$this->template->sbory['sbory'] = $this->model->findAll();

		$this->setTitle('Sbory, které se účastnily závodů nebo pořádaly soutěž');
	}

	public function actionEdit($id = 0)
	{
		if( $id != 0 && !($sbor = $this->model->find($id)->fetch()) ) throw new BadRequestException();

		$backlink = $this->getApplication()->storeRequest();
		if( ($this->user === NULL || !$this->user->isLoggedIn()) ) $this->redirect('Sprava:login', $backlink);

		if( !$this->user->isAllowed('sbory', 'edit') && !$this->jeAutor($sbor['id_kontaktni_osoby']) && !$this->jeAutor($sbor['id_spravce']) )  throw new ForbiddenRequestException();

	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('sbory', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function actionDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Sbor byl úspěšně smazán.');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Nepodařilo se smazat sbor.', 'error');
			Debug::processException($e, true);
		}
		$this->redirect('default');
	}

	public function actionSbor($id = 0)
	{
		if( empty($id) ) $this->redirect('default');

		if( !($sbor = $this->model->find($id)->fetch()) ) throw new BadRequestException();
	}

	public function renderSbor($id)
	{
		$this->template->sbor = $this->model->find($id)->fetch();

		$this->template->sbor['muze_editovat'] = $this->user->isAllowed('sbory', 'edit') || $this->jeAutor($this->template->sbor['id_kontaktni_osoby']) || $this->jeAutor($this->template->sbor['id_spravce']);

		$zavody = new Zavody;
		$this->template->zavody = array();
		$this->template->zavody['zavody'] = $zavody->findByPoradatel($id);

		$terce = new Terce;
		$this->template->terce = $terce->findByMajitel($id);

          $druzstva = new Druzstva;
		$this->template->druzstva = $druzstva->findBySbor($id);

		$uzivateleModel = new Uzivatele();
		$this->template->sbor['uzivatele'] = $uzivateleModel->findBySbor($id);

		$this->setTitle('Sbor '.$this->template->sbor['nazev']);
  	}

	public function renderEdit($id = 0, $backlink = NULL)
	{
		if( $id != 0 ) $this['sborForm']->setDefaults($this->model->find($id)->fetch());

		$this->setTitle('Úprava informací o sboru');
	}

	public function createComponentSborForm()
	{
		$form = new RequestButtonReceiver;
		$uzivatele = new Uzivatele;
		$mista = new Mista;
		$backlink = $this->getApplication()->storeRequest();

		$typy_sboru = $this->model->findTypytoSelect()->fetchPairs('id', 'nazev');

		$form->addGroup('Informace o sboru');
		$form->addSelect('id_typu', 'Typ sboru', $typy_sboru)
			->setOption('description', $form->addRequestButton('addSbory', 'Přidat nový', 'TypySboru:add'));
		$form->addText('privlastek', 'Přívlastek sboru');
          $form->addSelect('id_mista', 'Obec', $mista->findAllToSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat místo sboru.')
			->setOption('description', $form->addRequestButton('addMista', 'Přidat novou', 'Mista:add'));

		$vsichniUzivatele = $uzivatele->findAllToSelect()->fetchPairs('id', 'uzivatel');
		$form->addGroup('Kontaktní informace');
		$form->addSelect('id_kontaktni_osoby', 'Kontaktní osoba', array('0' => 'žádná kontaktní osoba') + $vsichniUzivatele)
			->setOption('description', $form->addRequestButton('addKontaktniOsoby', 'Přidat novou', 'Uzivatele:add'));

		$form->addSelect('id_spravce', 'Správce sboru', array('0' => 'pouze kontaktní osoba') + $vsichniUzivatele)
			->setOption('description', $form->addRequestButton('addSpravceSboru', 'Přidat novou', 'Uzivatele:add'));


		$form->addGroup('Uožení');

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(FALSE);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSubmit[] = array($this, 'sborFormSubmitted');

		return $form;
	}

	public function sborFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
		}
		elseif( $form['save']->isSubmittedBy() )
		{
			try
			{
				if( $id == 0 )
				{
					$this->model->insert($form->getValues());
					$id = $this->model->lastInsertedId();
				}
				else $this->model->update($id, $form->getValues());

				$this->flashMessage('Údaje o sboru byly úspěšně uloženy.');
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Ukládaný sbor již existuje.', 'warning');
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Údaje o sboru se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
		}

		$this->getApplication()->restoreRequest($this->backlink);

		if($id != 0) $this->redirect('Sbory:sbor', $id);
		else $this->redirect('Sbory:add');
	}

}
