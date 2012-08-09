<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Presenter článků
 *
 * @author	Milan Pála
 */
class ClankyPresenter extends BasePresenter
{

	protected $model;

	protected function startup()
	{
		$this->model = new Clanky;
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

		if($id != 0 && !$this->model->find($id)->fetch()) throw new BadRequestException('Článek nebyl nalezen.');
	}

	public function renderEdit($id = 0)
	{
		$this->template->clanek = $this->model->find($id)->fetch();
		if($id != 0) $this['clanekForm']->setDefaults($this->template->clanek);

		if($id != 0) $this->setTitle('Úprava článku');
		else $this->setTitle('Přidání nového článku');
	}

	public function createComponentClanekForm()
	{
		$id = (int) $this->getParam('id');

		$form = new RequestButtonReceiver($this, 'clanekForm');

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o článku');
		$form->addText('nazev', 'Název článku', 50)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit název článku.')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka názvu článku je %d znaků.', 255);
		$form->addAdminTexylaTextArea('perex', 'Úvod článku')
			   ->addRule(Form::FILLED, 'Je nutné vyplnit text článku.')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka úvodu článku je %d znaků.', 65535);
		$form->addAdminTexylaTextArea('text', 'Text článku', NULL, 40)
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka článku je %d znaků.', 65535);

		$kategorie_clanku = $this->model->findKategorie()->fetchPairs('id', 'nazev');
		$form->addSelect('id_kategorie', 'Kategorie článku', $kategorie_clanku)
			   ->setOption('description', $form->addRequestButton('addRocniky', 'Přidat novou', 'KategorieClanku:add'));

		$uzivateleModel = new Uzivatele();
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

		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(FALSE);

		$form->addGroup('Publikace na sociálních sítích');
		$fb = $form->addContainer('facebook');
		$fb->addText('komentar', 'Komentář k příspěvku', 50, 255);
		$fb->addSubmit('zverejnit', 'Zveřejnit na Facebooku');


		$form->onSubmit[] = array($this, 'clanekFormSubmitted');

		return $form;
	}

	public function clanekFormSubmitted(AppForm $form)
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

				$this->flashMessage('Článek byl úspěšně uložen.', 'ok');

				if($form['saveAndReturn']->isSubmittedBy()) $this->redirect('Clanky:clanek', $id);
				$this->redirect('Clanky:edit', $id);
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Článek se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
				$this->redirect('Clanky:edit', $id);
			}
		}
		elseif($form['facebook']['zverejnit']->isSubmittedBy())
		{
			$this->redirect('zverejnitNaFB', array('id' => $id, 'komentar' => $form['facebook']['komentar']->value));
		}
	}

	public function actionZverejnitNaFB($id, $komentar, $tmp = true)
	{
		try
		{
			$fb = new FacebookDriver();

			$clanek = $this->model->find($id)->fetch();
			$data = array(
			    'link' => $this->getHttpRequest()->getUri()->getHostUri() . $this->link('Clanky:clanek', $id),
			    'message' => $komentar,
			    'name' => $clanek->nazev,
			);
			$a = $fb->publishLink($data, false);

			$this->flashMessage('Článek byl úspěšně zveřejněn na Facebooku. '.print_r($a, true), 'ok');
			$this->redirect('Clanky:edit', $id);
		}
		catch(FacebookNeedLoginException $e)
		{
			die("NeedLoginException");
			$this->redirectUri($fb->getLoginUrl());
		}
		catch(FacebookNeedLogoutException $e)
		{
			//die("NeedLogoutException");
			//$this->redirectUri($fb->getLogoutUrl());
			$this->actionZverejnitNaFB($id, $komentar, false);
		}
		catch (FacebookApiException $e)
		{
			$this->flashMessage('Došlo k chybě při zveřejňování na Facebooku: '. $e->getFile().'-' . $e->getMessage(), 'error');
			Debug::processException($e, true);
			$this->redirect('Clanky:edit', $id);
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
			Debug::processException($e, true);
		}
		$this->redirect('default');
	}

}
