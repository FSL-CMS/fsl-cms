<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

/**
 * Presenter článků
 *
 * @author	Milan Pála
 */
class ClankyPresenter extends BasePresenter
{

	/** @var Clanky */
	protected $model;

	protected function startup()
	{
		$this->model = $this->context->clanky;
		parent::startup();

		if($this->user->isAllowed('clanky', 'edit')) $this->model->zobrazitNezverejnene();
	}

	public function actionAdd()
	{
		parent::actionAdd();

		$this->setView('edit');
	}

	/**
	 * Připraví přehled všech článků
	 */
	public function renderDefault()
	{
		$this->template->clanky = array();
		$result = $this->model->findAll();

		$this->template->fulltext = false;

		$vp = new VisualPaginator($this, 'vp');
		$paginator = $vp->getPaginator();
		$paginator->itemsPerPage = 15;
		$paginator->itemCount = count($result);

		$this->template->clanky['nezverejnene'] = array();
		if($this->user->isAllowed('clanky', 'edit')) $this->template->clanky['nezverejnene'] = $this->model->findNezverejnene()->fetchAll();

		$this->template->clanky['clanky'] = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);

		$this->template->kategorieClanku = $this->model->prehledKategorii();

		$this->setTitle('Články');

		$this->zpracujClanky($this);
	}

	/**
	 * Připraví přehled článků v jedné kategorii
	 * @param int $id ID kategorie
	 */
	public function renderKategorie($id)
	{
		$result = $this->model->findByKategorie($id);

		$vp = new VisualPaginator($this, 'vp');
		$paginator = $vp->getPaginator();
		$paginator->itemsPerPage = 15;
		$paginator->itemCount = count($result);

		$this->template->fulltext = false;

		$this->template->clanky = array();
		$this->template->clanky['clanky'] = $result->fetchAll($paginator->offset, $paginator->itemsPerPage);
		$this->template->clanky['nezverejnene'] = array();
		$this->template->clanky['tema'] = $this->model->findKategorii($id)->fetch();

		$this->setTitle('Články na téma: ' . $this->template->clanky['tema']['nazev']);

		$this->zpracujClanky($this);
	}

	public function zpracujClanky($self, $muzeMazat = true)
	{
		foreach ($self->template->clanky['clanky'] as &$clanek)
		{
			$clanek = (array) $clanek;
			$clanek['muze_editovat'] = $self->user->isAllowed('clanky', 'edit') || $self->user->isLoggedIn() && $self->user->getIdentity()->id == $clanek['id_autora'];
			$clanek['muze_smazat'] = $muzeMazat && $self->user->isAllowed('clanky', 'delete') || $self->user->isLoggedIn() && $self->user->getIdentity()->id == $clanek['id_autora'];
			if(!empty($clanek->datum_zverejneni))
			{
				$clanek['datum'] = array();
				$clanek['datum']['den'] = substr($clanek->datum_zverejneni, 8, 2);
				$clanek['datum']['mesic'] = Datum::$nazvy['mesic'][intval(substr($clanek->datum_zverejneni, 5, 2))];
				$clanek['datum']['rok'] = substr($clanek->datum_zverejneni, 0, 4);
			}
		}
		foreach ($self->template->clanky['nezverejnene'] as &$clanek)
		{
			$clanek['muze_smazat'] = $muzeMazat && $self->user->isAllowed('clanky', 'delete') || $self->user->isLoggedIn() && $self->user->getIdentity()->id == $clanek['id_autora'];
			$clanek['muze_editovat'] = $self->user->isAllowed('clanky', 'edit') || $self->user->isLoggedIn() && $self->user->getIdentity()->id == $clanek['id_autora'];
		}
		$self->template->clanky['muze_pridavat'] = $self->user->isAllowed('clanky', 'edit');
	}

	public function actionClanek($id = 0)
	{
		if($id == 0) $this->redirect('default');

		if(!$this->model->find($id)->fetch()) throw new BadRequestException('Článek nebyl nalezen.');
	}

	/**
	 * Připraví výpis jednoho článku
	 * @param int $id ID článku, který se má zobrazit
	 */
	public function renderClanek($id)
	{
		$this->model->precteno($id);
		$this->template->clanek = (array) $this->model->find($id)->fetch();
		$this->template->clanek['muze_editovat'] = $this->user->isAllowed('clanky', 'edit');
		$this->template->clanek['muze_smazat'] = $this->user->isAllowed('clanky', 'delete');

		if(!empty($this->template->clanek->datum_zverejneni))
		{
			$this->template->clanek['datum']['den'] = substr($this->template->clanek->datum_zverejneni, 8, 2);
			$this->template->clanek['datum']['mesic'] = Datum::$nazvy['mesic'][intval(substr($this->template->clanek->datum_zverejneni, 5, 2))];
			$this->template->clanek['datum']['rok'] = substr($this->template->clanek->datum_zverejneni, 0, 4);
		}

		$this->template->fulltext = true;

		$this->setTitle($this->template->clanek['nazev']);
	}

	public function actionEdit($id = 0)
	{
		parent::actionEdit($id);

		//if($id != 0 && !$this->model->find($id)->fetch()) throw new BadRequestException('Článek nebyl nalezen.');
	}

	public function renderEdit($id = 0)
	{
		$this->template->clanek = $this->model->find($id)->fetch();
		if($id != 0)
		{
			$defaults = $this->template->clanek;
			$defaults['sablony_clanku'] = $this->model->findSablony($id)->fetchPairs('id', 'id');
			$this['clanekForm']->setValues($defaults);
		}

		if($id != 0) $this->setTitle('Úprava článku');
		else $this->setTitle('Přidání nového článku');
	}

	public function createComponentClanekForm()
	{
		$id = (int) $this->getParam('id');

		$form = new RequestButtonReceiver($this, 'clanekForm');
		$sablonyClankuModel = $this->context->sablonyClanku;

		$form->addGroup('Informace o článku');
		$form->addText('nazev', 'Název článku', 50)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit název článku.')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka názvu článku je %d znaků.', 255);
		$form->addAdminTexylaTextArea('perex', 'Úvod článku', null, null, $this->getPresenter()->getName(), $id)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit text článku.')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka úvodu článku je %d znaků.', 65535);
		$form->addAdminTexylaTextArea('text', 'Text článku', NULL, 40, $this->getPresenter()->getName(), $id)
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka článku je %d znaků.', 65535);

		$kategorie_clanku = $this->model->findKategorie()->fetchPairs('id', 'nazev');
		$form->addSelect('id_kategorie', 'Kategorie článku', $kategorie_clanku)
			   ->setOption('description', $form->addRequestButton('addRocniky', 'Přidat novou', 'KategorieClanku:add'));

		$sablonyClanku = $sablonyClankuModel->findAllToSelect()->fetchPairs('id', 'nazev');
		$form->addMultiSelect('sablony_clanku', 'Šablony článků', $sablonyClanku, 3)
			   ->setOption('description', 'Šablona určuje dodatečný vzhled článku, především tématický obrázek. Můžete vybrat více šablon.');
		$form->addRequestButton('addSablony', 'Přidat novou šablonu', 'SablonyClanku:add');

		$uzivateleModel = $this->context->uzivatele;
		$form->addSelect('id_autora', 'Autor článku', $uzivateleModel->findAllToSelect()->fetchPairs('id', 'uzivatel'))->setDefaultValue($this->user->getIdentity()->id)
			   ->addRule(Form::FILLED, 'Je nutné vybrat autora článku.');
		$form->addRequestButton('addUzivatel', 'Přidat nového', 'Uzivatele:add');

		$zverejneni = array('ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		if($id != 0) $zverejneni += array('ponechat' => 'nechat bez změny');
		$form->addGroup('Zveřejnění článku');
		$form->addRadioList('zverejneni', 'Zveřejnění článku', $zverejneni)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit, kdy se má článek zveřejnit.');
		$form->addDateTimePicker('datum_zverejneni', 'Datum zveřejnění článku')
			   ->addConditionOn($form['zverejneni'], Form::EQUAL, 'datum_zverejneni')
			   ->addRule(Form::FILLED, 'Je nutné vyplnit datum zveřejnění článku.');

		if($id == 0) $form['zverejneni']->setDefaultValue('ihned');
		else $form['zverejneni']->setDefaultValue('ponechat');

		$form->addGroup('Uložit');
		$form->addCheckbox('saveAsUpdated', 'Označit článek jako aktualizovaný')->setOption('description', 'Datum a čas uložení bude uveden jako datum aktualizace článku.');

		$form->addSubmit('save', Texty::$FORM_SAVE);
		$form->addSubmit('saveAndReturn', Texty::$FORM_SAVEANDRETURN);
		$form->addSubmit('cancel', Texty::$FORM_CANCEL)
			->setValidationScope(FALSE);

		$form->onSuccess[] = array($this, 'clanekFormSubmitted');

		return $form;
	}

	public function clanekFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if($form['cancel']->isSubmittedBy())
		{
			if($id == 0) $this->redirect('Clanky:default');
			else $this->redirect('Clanky:clanek', $id);
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			$data = $form->getValues();

			$clanek_data = array('nazev' => $data['nazev'], 'perex' => $data['perex'], 'text' => $data['text'], 'id_kategorie' => (int) $data['id_kategorie'], 'id_autora%i' => $data['id_autora']);
			if($data['zverejneni'] == 'ulozit') $clanek_data['datum_zverejneni%sn'] = '';
			elseif($data['zverejneni'] == 'ihned') $clanek_data['datum_zverejneni%sql'] = "NOW()";
			elseif($data['zverejneni'] == 'ponechat')
			{

			}
			else $clanek_data['datum_zverejneni%t'] = $data['datum_zverejneni'];

			try
			{
				if($id == 0)
				{
					$clanek_data['datum_pridani%sql'] = 'NOW()';
					$this->model->insert($clanek_data);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					if(isset($data['saveAsUpdated']) && $data['saveAsUpdated'] == true) $clanek_data['posledni_aktualizace%sql'] = 'NOW()';
					$this->model->update($id, $clanek_data);
				}

				// uložení šablon článku
				if(isset($data['sablony_clanku']))
				{
					$sablony_insert = array();
					$sablony_delete = $this->model->findSablony($id)->fetchPairs('id', 'id');
					foreach ($data['sablony_clanku'] as $id_sablony)
					{
						if(isset($sablony_delete[$id_sablony]))
						{
							unset($sablony_delete[$id_sablony]);
						}
						else $sablony_insert[] = $id_sablony;
					}
					if(count($sablony_insert)) foreach ($sablony_insert as $poradatel)
							$this->model->pridejSablonu($id, $poradatel);
					if(count($sablony_delete)) foreach ($sablony_delete as $poradatel)
							$this->model->odeberSablonu($id, $poradatel);
				}

				$this->flashMessage('Článek byl úspěšně uložen.', 'ok');

				if($form['saveAndReturn']->isSubmittedBy()) $this->redirect('Clanky:clanek', $id);
				$this->redirect('Clanky:edit', $id);
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Článek se nepodařilo uložit.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
				$this->redirect('Clanky:edit', $id);
			}
		}
	}

	public function handleDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Článek byl úspěšně smazán.');
		}
		catch (RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Nepodařilo se smazat článek.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('default');
	}

}
