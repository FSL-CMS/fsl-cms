<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Presenter závodů
 *
 * @author	Milan Pála
 */
class ZavodyPresenter extends BasePresenter
{

	/** @persistent */
	//public $backlink = '';
	protected $model;

	protected function startup()
	{
		$this->model = new Zavody;
		parent::startup();
		if($this->user->isAllowed('rocniky', 'edit')) $this->model->zobrazitNezverejnene();
	}

	public function actionDefault()
	{
		$rocniky = new Rocniky;
		$this->redirect('Rocniky:rocnik', $rocniky->findLast()->fetchSingle());
	}

	public function actionZavod($id = 0)
	{
		if($id == 0) $this->redirect('default');

		$zavod = $this->model->find($id)->fetch();
		if(!$zavod) throw new BadRequestException('Závod nebyl nalezen.');

		$this['souteze']->setRocnik($zavod['rocnik']);
	}

	/**
	 * Přidání nového závodu. Pokud není uveden ročník, proběhne přesměrování
	 * na poslední ročník. Ročník je důležitý pro výběr soutěží.
	 *
	 * @param $id_rocniku Ročník, ke kterému se závod vkládá.
	 */
	public function actionAdd($id_rocniku = 0)
	{
		if($this->user === NULL || !$this->user->isAllowed('zavody', 'add')) throw new ForbiddenRequestException();

		if($id_rocniku == 0)
		{
			$rocnikyModel = new Rocniky;
			$rocnikyModel->zobrazitNezverejnene();
			$this->redirect('Zavody:add', $rocnikyModel->findLast()->fetchSingle('id'));
		}

		$this->setView('edit');
	}

	public function actionVysledky($id)
	{
		if(intval($id) != 0 && !($zavod = $this->model->find($id)->fetch())) throw new BadRequestException();
	}

	/**
	 * Připraví výsledky ze závodů do atributu třídy.
	 * @param type $id ID závodu, ke kterému se mají výsledky připravit.
	 * @param type $nahled Příznak, zda jsou výsledky určeny pouze pro nahlížení (např. tisk), nebo k editaci (do formuláře).
	 */
	private function pripravVysledky($id, $nahled = false)
	{
		$vysledkyModel = new Vysledky;
		$this->template->vysledky = array();
		$this->template->vysledky['vysledky'] = array();
		$vysledky = $vysledkyModel->findByZavodZverejnene($id)->fetchAssoc('soutez,kategorie,id,=');
		$this->template->vysledky['muze_editovat'] = !$nahled && ($this->user->isAllowed('vysledky', 'edit') || $this->user->isAllowed(new ZavodResource($this->template->zavod)));

		$vysledkyPredZavodem = $vysledkyModel->findByRocnikAndZavod($this->template->zavod['id_rocniku'], $id)->fetchAssoc('soutez,kategorie,id_druzstva,=');
		$vysledkyModel->vyhodnotVysledkyRocniku($vysledkyPredZavodem);

		$vysledkyPoZavodu = $vysledkyModel->findByRocnikAndZavodAfter($this->template->zavod['id_rocniku'], $id)->fetchAssoc('soutez,kategorie,id_druzstva,=');
		$vysledkyModel->vyhodnotVysledkyRocniku($vysledkyPoZavodu);

		$this->template->nahled = $nahled;

		if(count($vysledky) != 0 || $nahled == true)
		{
			if(count($vysledky) == 0)
			{
				$startovniPoradiModel = new StartovniPoradi;
				$startovniPoradi = $startovniPoradiModel->findByZavod($id)->fetchAssoc('kategorie,id,=');

				$ucastiModel = new Ucasti;
				$ucasti = $ucastiModel->findByZavod($id)->fetchAssoc('nazev,kategorie,id');
				foreach ($ucasti as $soutez => $ucastiVsoutezi)
				{
					foreach ($startovniPoradi as $kategorie => $foo)
					{
						if(!isset($ucastiVsoutezi[$kategorie])) continue;
						$this->template->vysledky['vysledky'][$soutez][$kategorie]['jeLepsiCas'] = true;
						$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPred'] = false;
						$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPo'] = false;
						foreach ($foo as $id_vysledku => $bar)
						{
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku] = $bar;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['lepsi_cas'] = NULL;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['body'] = NULL;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['vysledny_cas'] = NULL;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPredZavodem'] = isset($vysledkyPredZavodem[$soutez][$kategorie][$bar['id_druzstva']]) ? $vysledkyPredZavodem[$soutez][$kategorie][$bar['id_druzstva']]['poradi'] : 0;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPred'] |= 0 != $this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPredZavodem'];
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPoZavodu'] = NULL;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradi'] = NULL;
						}
						for ($i = 0; $i < 5; $i++)
						{
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][] = array('lepsi_cas' => false, 'poradiPredZavodem' => 0);
						}
					}
				}
			}
			else
			{
				foreach ($vysledky as $soutez => $foobar)
				{
					foreach ($foobar as $kategorie => $foo)
					{
						$this->template->vysledky['vysledky'][$soutez][$kategorie]['jeLepsiCas'] = false;
						$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPred'] = false;
						$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPo'] = false;
						foreach ($foo as $id_vysledku => $bar)
						{
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku] = $bar;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['lepsi_cas'] = sprintf("%.2f", $bar['lepsi_cas']);
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['vysledny_cas'] = sprintf("%.2f", $bar['vysledny_cas']);
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['jeLepsiCas'] |= $bar['lepsi_cas'] != 0;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPredZavodem'] = isset($vysledkyPredZavodem[$soutez][$kategorie][$bar['id_druzstva']]) ? $vysledkyPredZavodem[$soutez][$kategorie][$bar['id_druzstva']]['poradi'] : 0;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPoZavodu'] = isset($vysledkyPoZavodu[$soutez][$kategorie][$bar['id_druzstva']]) ? $vysledkyPoZavodu[$soutez][$kategorie][$bar['id_druzstva']]['poradi'] : 0;
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPo'] |= 0 != $this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPoZavodu'];
							$this->template->vysledky['vysledky'][$soutez][$kategorie]['jePoradiPred'] |= 0 != $this->template->vysledky['vysledky'][$soutez][$kategorie]['vysledky'][$id_vysledku]['poradiPredZavodem'];
						}
					}
				}
			}
		}
	}

	public function renderVysledky($id)
	{
		$this->template->zavod = $this->model->find($id)->fetch();
		$this->pripravVysledky($id, true);

		$datum = new Datum;
		$this->setTitle('Výsledky ze závodu ' . $this->template->zavod['nazev'] . ', ' . $datum->date(substr($this->template->zavod['datum'], 0, 10), 0, 0, 0));
	}

	public function renderZavod($id)
	{
		$this->template->zavod = $this->model->find($id)->fetch();
		$this->template->zavod['poradatele'] = $this->model->findPoradatele($id);
		$this->template->zavod['muze_editovat'] = (bool) $this->user->isAllowed(new ZavodResource($this->template->zavod), 'edit');
		$this->template->zavod['muze_hodnotit'] = (bool) (strtotime($this->template->zavod['datum']) < strtotime('NOW'));

		$datum = new Datum;
		$this->setTitle('Závod ' . $this->template->zavod['nazev'] . ', ' . $datum->date(substr($this->template->zavod['datum'], 0, 10), 1, 0, 0));

		$this->pripravVysledky($id, false);

		$this->template->nasledujici = $this->model->findNext($id)->fetch();
		$this->template->predchozi = $this->model->findPrevious($id)->fetch();

		$this->pripravRekordy($id);

		$this->template->startovni_poradi = array();
		$this->startovniPoradi($id, false);

		$this->template->predchoziKola = array();
		$this->template->predchoziKola = $this->model->findPredchoziKola($id); //Poradatel($this->template->zavod['id_poradatele'])->where('[zavody].[id] != %i', $id);

		$this->template->backlink = $this->application->storeRequest();
		$this->template->adresaLigy = $this->getHttpRequest()->getUri()->getScheme().'://'.$this->getHttpRequest()->getUri()->getHost();
	}

	public function actionStartovniPoradi($id = 0)
	{
		if($id == 0) $this->redirect('default');
		if($id != 0 && !($zavod = $this->model->find($id)->fetch())) throw new BadRequestException();
	}

	protected function startovniPoradi($id, $nahled = false)
	{
		//!TODO: předělat na verzi se soutěžemi
		//!TODO: opravit když nejsem přihlášen a propadl závod
		$sp = new StartovniPoradi;
		$this->template->startovni_poradi = array('startovni_poradi' => array());
		if(!isset($this->template->zavod))
		{
			$this->template->zavod = array();
			$this->template->zavod = $this->model->find($id)->fetch();
		}

		$this->template->startovni_poradi['muze_editovat'] = !$nahled && ($this->user->isAllowed('startovni_poradi', 'edit') || $this->template->zavod['muze_editovat']);
		$this->template->startovni_poradi['muze_pridavat'] = !$nahled && $this->user->isAllowed('startovni_poradi', 'add');

		$this->template->nahled = $nahled;

		if($nahled || $this->template->startovni_poradi['muze_editovat'] || strtotime($this->template->zavod['datum']) >= strtotime('NOW'))
		{
			$ucasti = new Ucasti;
			$druzstva = new Druzstva;

			if($this->user->isLoggedIn()) $uziv_druzstva = $druzstva->findByLoginedUser($this->user->getIdentity()->id)->fetchAssoc('id_kategorie,id,=');
			$uc = $ucasti->findByZavod($id)->fetchAssoc('kategorie,=');

			$prihlasenaSP = $sp->findByZavod($id)->fetchAssoc('kategorie,poradi,=');
			$this->template->startovni_poradi['startovni_poradi'] = array();

			foreach ($uc as $ucast)
			{
				if($ucast['id_ucasti'] === NULL) continue; // neúčastnícíse kategorie

				$this->template->startovni_poradi['startovni_poradi'][$ucast['kategorie']] = array();

				for ($i = 1; $i < (1 + $ucast['pocet_startovnich_mist']); $i++)
				{
					if(isset($prihlasenaSP[$ucast['kategorie']][$i]))
					{
						// obsazené startovní místo
						// vloží se do volných startovních míst a určí se, zda lze smazat
						$this->template->startovni_poradi['startovni_poradi'][$ucast['kategorie']][$i] = $prihlasenaSP[$ucast['kategorie']][$i];
						$this->template->startovni_poradi['startovni_poradi'][$ucast['kategorie']][$i]['muze_smazat'] = $this->user->isAllowed(new StartovniPoradiResource($this->template->startovni_poradi['startovni_poradi'][$ucast['kategorie']][$i]), 'delete');
					}
					else
					{ // volné startovní místo
						if($this->user->isLoggedIn())
						{
							$druzstva_uzivatele = array();
							if(isset($uziv_druzstva[$ucast['id_kategorie']]))
							{
								$druzstva_uzivatele = $uziv_druzstva[$ucast['id_kategorie']];
							}
							$this->template->startovni_poradi['startovni_poradi'][$ucast['kategorie']][$i] = array('id' => 0, 'id_kategorie' => $ucast['id_kategorie'], 'poradi' => $i, 'druzstvo' => '', 'datum' => '', 'id_druzstva' => 0, 'id_druzstev' => $druzstva_uzivatele, 'uzivatel' => '', 'muze_smazat' => false, 'id_sboru' => NULL);
						}
					}
					//ksort($this->template->startovni_poradi['startovni_poradi'][$ucast['nazev']]);
				}
			}
		}
		if(true || count($this->template->startovni_poradi['startovni_poradi']))
		{
			$this->template->startovni_poradi['datum_prihlasovani_od'] = date('Y-m-d H:i:s', strtotime('-14 days next monday', strtotime($this->template->zavod['datum'])));
			$this->template->startovni_poradi['datum_prihlasovani_do'] = date('Y-m-d 20:00:00', strtotime('-1 day', strtotime($this->template->zavod['datum'])));
		}
		$this->template->startovni_poradi['datum_prihlasovani_uplynulo'] = isset($this->template->startovni_poradi['datum_prihlasovani_do']) && date('Y-m-d H:i:s') > $this->template->startovni_poradi['datum_prihlasovani_do'] || date('Y-m-d H:i:s') > $this->template->zavod['datum'];
		$this->template->startovni_poradi['datum_prihlasovani_nezacalo'] = isset($this->template->startovni_poradi['datum_prihlasovani_od']) && date('Y-m-d H:i:s') < $this->template->startovni_poradi['datum_prihlasovani_od'];
	}

	public function renderStartovniPoradi($id)
	{
		$this->startovniPoradi($id, true);

		$zavod = $this->model->find($id)->fetch();

		$datum = new Datum;
		$this->setTitle('Startovní pořadí závodu ' . $zavod['nazev'] . ', ' . $datum->date(substr($zavod['datum'], 0, 10), 0, 0, 0));
	}

	public function actionEdit($id = 0)
	{
		if($id == 0) $this->redirect('add');

		if($id != 0 && !($zavod = $this->model->find($id)->fetch())) throw new BadRequestException();

		$backlink = $this->getApplication()->storeRequest();
		if($this->user === NULL || !$this->user->isLoggedIn()) $this->redirect('Sprava:login', $backlink);

		if(!$this->user->isAllowed(new ZavodResource($zavod), 'edit')) throw new ForbiddenRequestException();
	}

	public function renderEdit($id = 0, $id_rocniku = NULL, $backlink = NULL)
	{
		if($id != 0)
		{
			$defaults = array();
			$defaults = $this->model->find($id)->fetch();
			$defaults['id_poradatele'] = $this->model->findPoradatele($id)->fetchPairs('id', 'id');
			$ucastiModel = new Ucasti;
			$defaults['ucasti'] = $ucastiModel->findByZavod($id)->fetchAssoc('id_souteze,id_kategorie');
			$defaults['spolecne_startovni_poradi'] = true;
			$this['editForm']->setValues($defaults);
			$id_rocniku = $defaults['id_rocniku'];
			$this['zmenitRocnikForm']->setValues(array('id_rocniku' => $id_rocniku));
			$this['zmenitRocnikForm']['id_rocniku']->setDisabled();
			$this['zmenitRocnikForm']['id_rocniku']->setOption('description', 'Chcete-li změnit ročník závodu, je nutné závod smazat a vytvořit nový závod.');
			$this['zmenitRocnikForm']['save']->setDisabled();
			$this->setTitle('Úprava informací o závodu');
		}
		else // $id == 0
		{
			$defaults = array();
			$defaults['id_rocniku'] = $id_rocniku;
			$defaults['spolecne_startovni_poradi'] = true;
			$this['editForm']->setDefaults($defaults);
			//$this['editForm']['spolecne_startovni_poradi']->setValue(true);
			$this['zmenitRocnikForm']->setValues(array('id_rocniku' => $id_rocniku));
			$this->setTitle('Přidání nového závodu');
		}
		if($backlink !== NULL) $this['editForm']['backlink']->setValue($backlink);
	}

	public function createComponentZmenitRocnikForm()
	{
		$f = new AppForm($this, 'zmenitRocnikForm');
		$rocnikyModel = new Rocniky;
		$rocnikyModel->zobrazitNezverejnene();

		$f->addGroup('Ročník, ke kterému se vkládá závod');
		$f->addSelect('id_rocniku', 'Ročník', $rocnikyModel->findAll()->fetchPairs('id', 'rok'))
			->setDefaultValue($rocnikyModel->findLast()->fetchSingle('id'));
		$f->addSubmit('save', 'Změnit');

		$f->onSubmit[] = array($this, 'zmenitRocnikFormSubmitted');
	}

	public function zmenitRocnikFormSubmitted(AppForm $f)
	{
		$id = $this->getParam('id', 0);

		if($id == 0) $this->redirect('Zavody:add', array('id_rocniku' => $f['id_rocniku']->value));
		else $this->redirect('Zavody:edit', array('id' => $id));
	}

	public function createComponentEditForm($name)
	{
		$id = (int) $this->getParam('id', 0);

		$sbory = new Sbory;
		$terceModel = new Terce;
		$druzstva = new Druzstva;
		$ucasti = new Ucasti;
		$sportovisteModel = new Sportoviste;

		$backlink = $this->getApplication()->storeRequest();

		$mistaPoradani = $sportovisteModel->findAllToSelect()->fetchPairs('id', 'nazev');
		//if(count($mistaPoradani) == 0) $mistaPoradani = array('žádné není');

		$terce = $terceModel->findAlltoSelect()->fetchPairs('id', 'terce');
		//if(count($terce) == 0) $terce = array(null => 'žádné nejsou');

		$zavod = $this->model->find($id)->fetch();

		if($id == 0) $id_rocniku = (int) $this->getParam('id_rocniku');
		else $id_rocniku = $zavod->id_rocniku;

		$form = new RequestButtonReceiver($this, $name);

		$form->addHidden('backlink');

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o závodu');
		$form->addHidden('id_rocniku', 'Ročník')
			   ->setRequired('Je nutné vybrat ročník pořádání.');
		$form->addMultiSelect('id_poradatele', 'Pořadatelé', $sbory->findAlltoSelect()->fetchPairs('id', 'sbor'), 6)
			   ->setRequired('Je nutné vybrat ročník pořádání.')
			   ->setOption('description', 'Více pořadatelů vyberete, když podržíte Ctrl a označíte je myší.');
		$form->addRequestButton('addSbory', 'Přidat nového pořadatele', 'Sbory:add');
		$form->addSelect('id_mista', 'Sportoviště', $mistaPoradani)
			   ->setRequired('Je nutné vybrat místo konání závodů.')
			   ->setOption('description', $form->addRequestButton('addSportoviste', 'Přidat nové sportoviště', 'Sportoviste:add'));
		$form->addDatetimePicker('datum', 'Datum a čas')
			   ->setRequired('Je nutné vyplnit datum závodu.')
			   ->setDefaultValue(date('Y-m-d H:i'));
		$form->addSelect('id_tercu', 'Terče', $terce)
			   ->setRequired('Je nutné vybrat terče.');
		$form->addRequestButton('addTerce', 'Přidat nové terče', 'Terce:add');
		$form->addCheckbox('zruseno', 'Zrušený závod', 'ano');

		/*$form->addSelect('ustream_stav', 'Video přenos', array('ne' => 'není/nebude', 'ano' => 'bude', 'live' => 'běží živě', 'zaznam' => 'ze záznamu'))
			   ->addRule(Form::FILLED, 'Je nutné zvolit, zda bude video přenos.');
		/* $form->addText('ustream_id', 'Ustream ID videa')
		  ->addConditionOn($form['ustream_stav'], Form::EQUAL, 'live')
		  ->addRule(Form::FILLED, 'Je nutné vyplnit Ustream ID videa.'); */

		$form->addAdminTexylaTextArea('text', 'Poznámka k závodu')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků.', 65535);

		$soutezeRocnikuModel = new SoutezeRocniku;
		$soutezeRocniku = $soutezeRocnikuModel->findByRocnik($id_rocniku)->fetchAssoc('nazev,id');

		$form->addGroup('Informace o soutěžích');

		$poradiCont = $form->addContainer('poradi');
		$poradiCont->setCurrentGroup($form->addGroup('Společné startovní pořadí'));
		$form->addCheckbox('spolecne_startovni_poradi', 'Společné startovní pořadí pro všechny soutěže na tomto závodu', true)->setDisabled(true);
		$form->addRequestButton('addSouteze', 'Přidat novou soutěž', 'Souteze:add');
		$form->addRequestButton('addKategorie', 'Přidat novou sportovní kategorii', 'Kategorie:add');
		foreach ($soutezeRocniku as $soutez => $val)
		{
			foreach ($val as $kategorie)
			{
				if(isset($poradiCont[$kategorie->id_kategorie])) continue;

				$katCont = $poradiCont->addContainer($kategorie->id_kategorie);
				$katCont->addText('pocet', 'Počet startovních míst - ' . $kategorie->kategorie)
					   ->setDefaultValue($kategorie->pocet_startovnich_mist);
			}
		}

		$zavod = $this->model->find($id)->fetch();
		$bodoveTabulkyModel = new BodoveTabulky;
		if(true || strtotime($zavod['datum']) > strtotime('NOW') || $id == 0)
		{
			$ucastiCont = $form->addContainer('ucasti');
			foreach ($soutezeRocniku as $soutez => $val)
			{
				$ucastiCont->setCurrentGroup($form->addGroup('Soutěž: ' . $soutez, false));
				$soutCont = $ucastiCont->addContainer(reset($val)->id_souteze);
				foreach ($val as $kategorie)
				{
					$katCont = $soutCont->addContainer($kategorie->id_kategorie);
					$katCont->addHidden('id_ucasti');
					$katCont->addHidden('id_souteze')
						   ->setDefaultValue($kategorie->id_souteze);
					$katCont->addCheckBox('id_kategorie', 'Kategorie ' . $kategorie->kategorie);
					/* $katCont->addText('pocet', 'Počet startovních míst')
					  //->setOption('container', Html::el('tr')->id('container')->style('text-decoration:underline;'))
					  ->setDefaultValue($kategorie->pocet_startovnich_mist)
					  //->addConditionOn($form['spolecne_startovni_poradi'], Form::EQUAL, false)->toggle($katCont['pocet']->getHtmlId())
					  ; */
					$katCont->addSelect('id_bodove_tabulky', 'Bodová tabulka', $bodoveTabulkyModel->findAllToSelect()->fetchPairs('id', 'nazev'))->setDefaultValue($kategorie->id_bodove_tabulky);
				}
			}
		}

		$form->addGroup('Uložit');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('saveAndAdd', 'Uložit a přidat nový závod');
		$form->addSubmit('cancel', 'Zrušit')
			   ->setValidationScope(FALSE);

		$form->onSubmit[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		if($form['cancel']->isSubmittedBy())
		{
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndAdd']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			$data = $form->getValues();
			$zavod_data = array('id_rocniku' => (int) $data['id_rocniku'], 'id_mista%in' => (int) $data['id_mista'], 'text' => $data['text'], 'datum%t' => $data['datum'], 'id_tercu' => (int) $data['id_tercu'], 'zruseno' => (bool) $data['zruseno'], /*'ustream_stav' => $data['ustream_stav'], */'spolecne_startovni_poradi' => true);

			$fazeUlozeni = 'zavod';

			try
			{
				if($id == 0)
				{
					$this->model->insert($zavod_data);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $zavod_data);
				}

				if(isset($data['id_poradatele']))
				{
					$poradatele_insert = array();
					$poradatele_delete = $this->model->findPoradatele($id)->fetchPairs('id', 'id');
					foreach ($data['id_poradatele'] as $id_poradatele)
					{
						if(isset($poradatele_delete[$id_poradatele]))
						{
							unset($poradatele_delete[$id_poradatele]);
						}
						else $poradatele_insert[] = $id_poradatele;
					}
					if(count($poradatele_insert)) foreach ($poradatele_insert as $poradatel)
							$this->model->pridejPoradatele($id, $poradatel);
					if(count($poradatele_delete)) foreach ($poradatele_delete as $poradatel)
							$this->model->odeberPoradatele($id, $poradatel);
				}

				$fazeUlozeni = 'ucasti';

				if(isset($data['ucasti']))
				{
					$ucasti_update = array();
					$ucasti_delete = array();
					$ucasti_insert = array();
					$ucastiModel = new Ucasti;
					$ucasti = $ucastiModel->findByZavod($id)->fetchAssoc('id_souteze,id_kategorie');
					foreach ($data['ucasti'] as $id_souteze => $foo)
					{
						foreach ($foo as $id_kategorie => $ucast)
						{
							if($ucast['id_kategorie'] === true && !isset($ucasti[$id_souteze][$id_kategorie])) $ucasti_insert[] = array('id_zavodu' => (int) $id, 'pocet' => (int) $data['poradi'][$id_kategorie]['pocet'], 'id_bodove_tabulky' => (int) $ucast['id_bodove_tabulky'], 'id_kategorie' => (int) $id_kategorie, 'id_souteze' => (int) $ucast['id_souteze']);
							if($ucast['id_kategorie'] === true && isset($ucasti[$id_souteze][$id_kategorie])) $ucasti_update[$ucast['id_ucasti']] = array('id_zavodu' => (int) $id, 'pocet' => (int) $data['poradi'][$id_kategorie]['pocet'], 'id_bodove_tabulky' => (int) $ucast['id_bodove_tabulky'], 'id_kategorie' => (int) $id_kategorie, 'id_souteze' => (int) $ucast['id_souteze']);
							if($ucast['id_kategorie'] === false && isset($ucasti[$id_souteze][$id_kategorie])) $ucasti_delete[] = $ucast['id_ucasti'];
						}
					}
					if(count($ucasti_delete)) foreach ($ucasti_delete as $ucast)
							$ucastiModel->delete($ucast);
					if(count($ucasti_update)) foreach ($ucasti_update as $id_ => $ucast)
							$ucastiModel->update($id_, $ucast);
					if(count($ucasti_insert)) foreach ($ucasti_insert as $ucast)
							$ucastiModel->insert($ucast);
				}

				$this->flashMessage('Závod byl úspěšně uložen.', 'ok');
			}
			catch (AlreadyExistException $e)
			{
				if($fazeUlozeni == 'zavod') $this->flashMessage('Ukládaný závod již existuje.', 'warning');
				else $this->flashMessage('Ukládaná kategorie se již soutěže účastní.', 'warning');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Závod se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}

			if($form['save']->isSubmittedBy())
			{
				$this->redirect('Zavody:edit', $id, $form['backlink']->value);
			}
			elseif($form['saveAndAdd']->isSubmittedBy()) $this->redirect('Zavody:add');
			else
			{
				$this->getApplication()->restoreRequest($form['backlink']->value);
				RequestButtonHelper::redirectBack();

				if($id == 0) $this->redirect('Zavody:default');
				else $this->redirect('Zavody:zavod', $id);
			}
		}
	}

	protected function createComponent($name)
	{
		if(preg_match("#^prihlasitSP_([0-9]+)_([0-9]+)$#", $name, $matches))
		{
			$id_kategorie = $matches[1];
			$poradi = $matches[2];
			return $this->createInstancePrihlasitSP($name, $id_kategorie, $poradi);
		}
		elseif($name == 'prihlasitSP')
		{
			return $this->createComponentPrihlasitSP($name);
		}
		elseif($name == 'editForm')
		{
			return $this->createComponentEditForm($name);
		}
		elseif($name == 'vysledkyForm')
		{
			return $this->createComponentVysledkyForm($name);
		}
		else
		{
			return parent::createComponent($name);
		}
	}

	protected function createInstancePrihlasitSP($name, $id_kategorie, $poradi)
	{
		$druzstva = new Druzstva;

		$form = new RequestButtonReceiver();

		$kategorie_form = $form->addContainer($id_kategorie);
		$poradi_form = $kategorie_form->addContainer($poradi);
		$poradi_form->addSelect('id_druzstva', 'Družstvo', $druzstva->findByKategorieToSelect($id_kategorie)->fetchPairs('id', 'druzstvo'));
		//->addRule(Form::FILLED, 'Je nutné vybrat družstvo.');
		$poradi_form->addHidden('poradi')->setDefaultValue($poradi);
		$form->addRequestButton('addDruzstva', 'Nové', 'Druzstva:add', array('id_kategorie' => $id_kategorie));
		$form->addSubmit('save', 'Přihlásit');

		$form->onSubmit[] = array($this, 'prihlasitSPSubmitted');

		return $form;
	}

	public function createComponentPrihlasitSP()
	{
		$druzstva = new Druzstva;
		$ucasti = new Ucasti;
		$form = new RequestButtonReceiver;
		$id = (int) $this->getParam('id');

		$uc = $ucasti->findByZavod($id)->fetchAssoc('id_kategorie,id');
		//Debug::dump($ucasti->findByZavod($id)->fetchAssoc('id_kategorie,id'));
		//$form->getElementPrototype()->class('ajax');

		$this->template->poradi = array();
		foreach ($uc as $id_kategorie => $foo)
		{
			$kategorie = reset($foo);

			$kategorieCont = $form->addContainer($id_kategorie);
			$kategorieCont->setCurrentGroup($form->addGroup($kategorie['kategorie']));

			$druzstva_v_kategorii = $druzstva->findByKategorieToSelect($id_kategorie)->fetchPairs('id', 'kratke');
			$druzstva_v_kategorii = array(0 => 'volná pozice') + $druzstva_v_kategorii;

			for ($i = 1; $i < (1 + $kategorie['pocet_startovnich_mist']); $i++)
			{
				$poradiCont = $kategorieCont->addContainer($i);
				$poradiCont->addHidden('id');
				$poradiCont->addHidden('puvodni_poradi')->setValue($i);
				$poradiCont->addHidden('puvodni_id_druzstva');
				$poradiCont->addText('poradi', 'Pořadí')
					   ->setValue($i);
				$poradiCont->addSelect('id_druzstva', 'Družstvo', $druzstva_v_kategorii);
			}
			$kategorieCont->addRequestButton('addDruzstva', 'Nové', 'Druzstva:add', array('id_kategorie' => $kategorie['id']));
			foreach ($foo as $soutez)
			{
				$this->template->poradi[$id_kategorie]['souteze'][] = $soutez['nazev'];
			}
		}

		$form->addSubmit('save', 'Uložit');

		$form->onSubmit[] = array($this, 'prihlasitSPSubmitted');

		return $form;
	}

	public function prihlasitSPSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		$posledni_sp = NULL;

		try
		{
			$sp = new StartovniPoradi;
			foreach ($form->getValues() as $id_kategorie => $foo)
			{
				foreach ($foo as $bar)
				{
					$id_druzstva = intval($bar['id_druzstva']);
					$puvodni_id_druzstva = isset($bar['puvodni_id_druzstva']) ? intval($bar['puvodni_id_druzstva']) : 0;
					$zmena_druzstva = $puvodni_id_druzstva != $id_druzstva;
					$poradi = intval($bar['poradi']);
					$puvodni_poradi = isset($bar['puvodni_poradi']) ? intval($bar['puvodni_poradi']) : 0;
					$zmena_poradi = $puvodni_poradi != $poradi;

					if(!$zmena_druzstva && $id_druzstva == 0)
					{
						continue;
					}
					elseif(!$zmena_druzstva && $id_druzstva != 0 && !$zmena_poradi)
					{
						continue;
					}
					elseif($zmena_druzstva && $id_druzstva == 0)
					{
						$sp->delete($bar['id']);
					}
					elseif($zmena_druzstva && $puvodni_id_druzstva == 0 && $id_druzstva != 0)
					{
						$sp->insert(array('id_zavodu' => (int) $id, 'poradi' => (int) $poradi, 'id_druzstva' => (int) $id_druzstva, 'id_autora' => (int) $this->user->getIdentity()->id, 'datum%sql' => 'NOW()'));
					}
					else
					{
						$sp->update((int) $bar['id'], array('poradi' => $poradi, 'id_druzstva' => $id_druzstva, 'id_druzstva' => (int) $id_druzstva, 'id_autora' => (int) $this->user->getIdentity()->id, 'datum%sql' => 'NOW()'));
					}
				}
			}
			$this->flashMessage('Změna startovního pořadí proběhla úspěšně.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Startovní pořadí se nepodařilo uložit.', 'error');
			Debug::processException($e, true);
		}
		catch (AlreadyExistException $e)
		{
			$this->flashMessage('Přihlašované družstvo je již přihlášeno. "Odhlásit!":' . $this->presenter->link("odepsat", $posledni_sp, $id), 'warning');
		}

		//$this->getApplication()->restoreRequest($this->backlink);
		$this->invalidateControl();
		if(!$this->isAjax()) $this->redirect('Zavody:zavod', $id);
	}

	public function actionOdepsat($id, $id_zavodu)
	{
		$spm = new StartovniPoradi;
		$sp = $spm->find($id)->fetch();

		if( !$this->user->isAllowed(new StartovniPoradiResource($sp), 'delete') )
		{
			$this->flashMessage('Nemáte oprávnění odhlásit toto družstvo.', 'warning');
			$this->redirect('Zavody:zavod', $id_zavodu);
			//throw new ForbiddenRequestException('Na tuto akci nemáte dostatečné oprávnění.');
		}

		$spm->delete($id);

		$this->flashMessage('Startovní pořadí bylo úspěšně odhlášeno.', 'ok');
		//$this->getApplication()->restoreRequest($this->backlink);
		$this->redirect('Zavody:zavod', $id_zavodu);
	}

	public function actionDelete($id, $force = 0)
	{
		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Závod byl úspěšně odstraněn.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Závod se nepodařilo odstranit.', 'error');
		}
		catch (RestrictionException $e)
		{
			$this->flashMessage($e->getMessage() . ' "Přesto smazat!":' . $this->link('delete', array('id' => $id, 'force' => true)), 'error');
		}
		//$this->getApplication()->restoreRequest($this->backlink);
		$this->redirect('Zavody:default');
	}

	public function actionPridatVysledky($id)
	{
		if(intval($id) != 0 && !($zavod = $this->model->find($id)->fetch())) throw new BadRequestException();

		$backlink = $this->getApplication()->storeRequest();
		if($this->user === NULL || !$this->user->isLoggedIn()) $this->redirect('Sprava:login', $backlink);

		if(!($id != 0 /* && $this->jeAutor($zavod['id_kontaktni_osoby']) */) && $this->jeAutor($zavod['id_spravce']) && !$this->user->isAllowed(strtolower(preg_replace('/Presenter/', '', $this->reflection->name)), $this->getAction())) throw new ForbiddenRequestException();

		$this['souteze']->setRocnik($zavod['id_rocniku']);
	}

	public function renderPridatVysledky($id)
	{
		$vysledky = new Vysledky;
		$this->template->vysledky = array();
		$this->template->vysledky['vysledky'] = $vysledky->findByZavod($id)->fetchAssoc('soutez,kategorie,id,=');
		$this->template->vysledky['jeLepsiCas'] = false;
		$this->template->vysledky['muze_mazat'] = $this->user->isAllowed('vysledky', 'delete');

		$dataDoFormu = $vysledky->findByZavod($id)->fetchAssoc('id,=');
		foreach ($dataDoFormu as &$vysledek)
		{
			if(isset(Vysledky::$SPECIALNI_VYSLEDKY[(int) $vysledek['vysledny_cas']]))
			{
				$vysledek['specialni_vysledek'] = (int) $vysledek['vysledny_cas'];
				unset($vysledek['vysledny_cas']);
			}
			if($vysledek['lepsi_cas'] == 0)
			{
				unset($vysledek['lepsi_cas']);
			}
		}
		$this['vysledkyForm']->setDefaults($dataDoFormu);

		$this->template->lzeZverejnit = true;

		$this->template->zavod = $this->model->find($id)->fetch();
		$this->template->lzeZverejnit &= empty($this->template->zavod['vystaveni_vysledku']);

		$datum = new Datum;
		$this->setTitle('Editace výsledků ze závodu ' . $this->template->zavod['nazev']);

		$this['vysledkyForm']['platne_body']->setDefaultValue($this->template->zavod['platne_body']);
		$this['vysledkyForm']['platne_casy']->setDefaultValue($this->template->zavod['platne_casy']);

		foreach ($this->template->vysledky['vysledky'] as $soutez => $foobar)
		{
			foreach ($foobar as $kategorie => $foo)
			{
				foreach ($foo as $vysledkyKategorie => $bar)
				{
					$this->template->lzeZverejnit &= $this->template->vysledky['vysledky'][$soutez][$kategorie][$vysledkyKategorie]['poradi'] != 0;
					$this->template->vysledky['vysledky'][$soutez][$kategorie][$vysledkyKategorie]['lepsi_cas'] = sprintf("%.2f", $this->template->vysledky['vysledky'][$soutez][$kategorie][$vysledkyKategorie]['lepsi_cas']);
					$this->template->vysledky['vysledky'][$soutez][$kategorie][$vysledkyKategorie]['vysledny_cas'] = sprintf("%.2f", $this->template->vysledky['vysledky'][$soutez][$kategorie][$vysledkyKategorie]['vysledny_cas']);
					$this->template->vysledky['jeLepsiCas'] |= $this->template->vysledky['vysledky'][$soutez][$kategorie][$vysledkyKategorie]['lepsi_cas'] != 0;
				}
			}
		}

		$this['vysledkyForm']['zverejnit']->setDisabled(!$this->template->lzeZverejnit);

		$ucastiModel = new Ucasti;
		$ucasti = $ucastiModel->findByZavod($id);
		$bodoveTabulkyModel = new Body();
		$this->template->ucasti = array();
		foreach($ucasti as $ucast)
		{
			$bt = $bodoveTabulkyModel->findByTabulka($ucast['id_bodove_tabulky'])->fetchAll();
			$this->template->ucasti[] = (array)$ucast + array('bodova_tabulka' => $bt);
		}
	}

	public function getDruzstva($form)
	{
		$druzstva = new Druzstva;
		return $druzstva->findByUcastiToSelect($form['id_ucasti']->getValue())->fetchPairs('id', 'druzstvo');
	}

	public function createComponentPridatVysledekForm($name)
	{
		$form = new RequestButtonReceiver($this, $name);
		$kategorie = new Kategorie;
		$druzstva = new Druzstva;
		$ucastiModel = new Ucasti;
		$id_zavodu = $this->getParam('id');

		DependentSelectBox::$disableChilds = false;

		$form->getElementPrototype()->class('ajax');
		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o družstvu');

		$form->addSelect('id_ucasti', 'Soutěž a kategorie', $ucastiModel->findByZavodToSelect($id_zavodu)->fetchPairs('id', 'nazev'))
			   ->addRule(Form::FILLED, 'Je nutné vybrat soutěž a kategorii.');

		$form->addJsonDependentSelectBox('id_druzstva', 'Družstvo', $form['id_ucasti'], array($this, "getDruzstva"))
			   ->addRule(Form::FILLED, 'Je nutné vybrat soutěžní družstvo.')
			   ->setOption('description', $form->addRequestButton('addDruzstvo', 'Přidat nové', 'Druzstva:add'));

		$form->addSelect('specialni_vysledek', 'Typ času', array('1' => 'platný pokus') + Vysledky::$SPECIALNI_VYSLEDKY)
			   ->addRule(Form::FILLED, 'Je nutné vybrat typ času')
			   ->addCondition(Form::EQUAL, 1)->toggle('platnyPokus');

		$form->addGroup('Platný pokus')->setOption('container', Html::el('fieldset')->id('platnyPokus'));
		$form->addSelect('lepsi_terc', 'Lepší terč', array('' => 'neuveden který', 'l' => 'levý', 'p' => 'pravý'))
			   ->addRule(Form::FILLED, 'Je nutné vybrat lepší terč.')
			   ->addCondition(Form::EQUAL, '')->toggle('lepsiCas');
		$form->addText('lepsi_cas', 'Lepší čas')
			   ->addCondition(Form::FILLED)
			   ->addRule(Form::FLOAT, 'Čas musí být zadaný jako číslo.');
		$form->addText('vysledny_cas', 'Výsledný čas')
			->addConditionOn($form['specialni_vysledek'], Form::EQUAL, '1')
				->addRule(Form::FILLED, 'Je nutné vyplnit výsledný čas.')
			->addConditionOn($form['specialni_vysledek'], Form::EQUAL, '1')
				->addRule(Form::FLOAT, 'Čas musí být zadaný jako číslo.')
			->addCondition(Form::FILLED, 'Je nutné zadat jiný čas než nula.', 0);
		$form['vysledny_cas']->getControlPrototype()->autocomplete('off');

		$form->addGroup('Uložení');

		$form->addSubmit('save', 'Uložit výsledek družstva');
		$form->onSubmit[] = callback($this, 'pridatVysledekFormSubmitted');

		return $form;
	}

	public function pridatVysledekFormSubmitted(AppForm $form)
	{
		$data = $form->getValues();
		$id = (int) $this->getParam('id');

		if($form['save']->isSubmittedBy())
		{
			$dataDoDB = array();

			//$dataDoDB['id_zavodu%i'] = (int) $id;
			$dataDoDB['id_druzstva%i'] = (int) $data['id_druzstva'];
			$dataDoDB['id_ucasti%i'] = (int) $data['id_ucasti'];

			if((int) $data['specialni_vysledek'] < Vysledky::HRANICE_PLATNYCH_CASU)
			{
				$dataDoDB['lepsi_terc'] = $data['lepsi_terc'];
				$dataDoDB['lepsi_cas'] = self::float($data['lepsi_cas']);
				$dataDoDB['vysledny_cas'] = self::float($data['vysledny_cas']);
			}
			else
			{
				$dataDoDB['vysledny_cas%i'] = $data['specialni_vysledek'];
			}

			$vysledky = new Vysledky;

			try
			{
				$vysledky->insert($dataDoDB);
				$this->model->nezverejnitVysledky($id);
				$this->flashMessage('Výsledek byl uložen.', 'ok');

				$form->setValues(array('id_ucasti' => $form['id_ucasti']->getValue()), true);
				$this->invalidateControl('pridatVysledekForm');
				$this->invalidateControl('vysledky');
			}
			catch (AlreadyExistException $e)
			{
				$this->flashMessage('Výsledek k tomuto družstvu byl již přidán.', 'warning');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit nový výsledek. ' . $e->getCode(), 'error');
				Debug::processException($e, true);
			}
			if(!$this->isAjax()) $this->redirect('this');
		}
	}

	public function createComponentVysledkyForm($name)
	{
		$id = (int) $this->getParam('id');

		$form = new AppForm($this, $name);
		//$form->getElementPrototype()->class('ajax');

		$vysledky = new Vysledky;
		$vysledkyZavodu = $vysledky->findByZavod($id)->fetchAssoc('id_kategorie,id,=');

		$druzstvaModel = new Druzstva;

		foreach ($vysledkyZavodu as $id_kat => $kat)
		{
			foreach ($kat as $vysledek)
			{
				$katCont = $form->addContainer($vysledek['id']);
				$katCont->addSelect('id_druzstva', 'Družstvo', $druzstvaModel->findByKategorieToSelect($id_kat)->fetchPairs('id', 'kratke'));
				$katCont->addSelect('lepsi_terc', 'Lepší terč', array('' => 'neuveden', 'l' => 'levý', 'p' => 'pravý'));
				$katCont->addText('lepsi_cas', 'Lepší čas', 5)
					   ->addCondition(Form::FILLED)
					   ->addRule(Form::FLOAT, 'Čas musí být zadaný jako číslo.');
				$katCont->addSelect('specialni_vysledek', 'Typ času', array('1' => 'platný pokus') + Vysledky::$SPECIALNI_VYSLEDKY)
					   ->addCondition(Form::EQUAL, 1)->toggle('frmvysledkyForm-' . $vysledek['id'] . '-vysledny_cas');
				$katCont->addText('vysledny_cas', 'Výsledný čas', 5)
					   ->addConditionOn($katCont['specialni_vysledek'], Form::EQUAL, 1)
					   ->addRule(Form::FILLED, 'Je nutné vyplnit výsledný čas.')
					   ->addConditionOn($katCont['specialni_vysledek'], Form::EQUAL, 1)
					   ->addRule(Form::FLOAT, 'Čas musí být zadaný jako číslo.');
				//$katCont['vysledny_cas']->getControlPrototype()->id('vyslednyCas'.$vysledek['id']);
				$katCont->addCheckbox('platne_body', 'Platné body');
				$katCont->addCheckbox('platne_casy', 'Platný čas');
			}
		}

		$form->addCheckbox('platne_body', 'Mají výsledky platné body?');
		$form->addCheckbox('platne_casy', 'Mají výsledky platné časy?');

		$form->addSubmit('save', '1. Uložit změny');
		$form->addSubmit('vyhodnot', '2. Určit pořadí a body')
			   ->setValidationScope(false);
		$form->addSubmit('zverejnit', '3. Zveřejnit výsledky')
			   ->setValidationScope(false);

		$form->onSubmit[] = array($this, 'vysledkyFormSubmitted');
	}

	protected function seradPodleCasu($a, $b)
	{
		if($b['vysledny_cas'] > $a['vysledny_cas']) return -1;
		elseif($b['vysledny_cas'] < $a['vysledny_cas']) return 1;
		else return 0;
	}

	public function vysledkyFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');
		if($form['vyhodnot']->isSubmittedBy())
		{
			try
			{
				$vysledky = new Vysledky;
				$vysledkyZavodu = $vysledky->findByZavod($id)->fetchAssoc('id_souteze,id_kategorie,id,=');

				$bodoveHodnoceni = new Body;
				$body = $bodoveHodnoceni->findByZavod($id)->fetchAssoc('id_souteze,id_kategorie,poradi,=');
				foreach ($vysledkyZavodu as $id_souteze => $foo)
				{
					foreach ($foo as $id_kat => &$kat)
					{
						usort($kat, array($this, 'seradPodleCasu'));

						$poradi = 0;
						$predchozi_cas = 0;
						$krok = 1;

						foreach ($kat as $vysledek)
						{
							if($predchozi_cas < $vysledek['vysledny_cas'])
							{
								$poradi += $krok;
								$krok = 1;
							}
							else $krok++;

							if((int) $vysledek['vysledny_cas'] < Vysledky::HRANICE_PLATNYCH_CASU && isset($body[$id_souteze][$id_kat][$poradi])) $vysledek['body'] = $body[$id_souteze][$id_kat][$poradi]['body'];
							else $vysledek['body'] = 0;

							$vysledek['poradi'] = $poradi;

							$predchozi_cas = $vysledek['vysledny_cas'];

							$dataDoDB = array('body' => (int) $vysledek['body'], 'umisteni' => (int) $vysledek['poradi']);

							$vysledky->update($vysledek['id'], $dataDoDB);
						}
					}
				}

				$this->flashMessage('Pořadí a bodové hodnocení bylo určeno.', 'ok');
				$this->invalidateControl('pridatVysledekForm');
				$this->invalidateControl('vysledky');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Pořadí a bodové hodnocení se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
			if(!$this->isAjax()) $this->redirect('this');
		}
		elseif($form['save']->isSubmittedBy())
		{
			$data = $form->getValues();
			$vysledky = new Vysledky;
			try
			{
				$this->model->nezverejnitVysledky($id);
				foreach ($data as $id_vysledku => $vysledek)
				{
					if($vysledek['specialni_vysledek'] > Vysledky::HRANICE_PLATNYCH_CASU)
					{
						$vysledek['lepsi_cas'] = 0;
						$vysledek['lepsi_terc'] = '';
						$vysledek['vysledny_cas'] = $vysledek['specialni_vysledek'];
					}

					$dataDoDB = array('id_druzstva' => $vysledek['id_druzstva'], 'lepsi_cas' => self::float($vysledek['lepsi_cas']), 'lepsi_terc' => $vysledek['lepsi_terc'], 'vysledny_cas' => self::float($vysledek['vysledny_cas']), 'platne_body%i' => (int) $vysledek['platne_body'], 'platne_casy%i' => (int) $vysledek['platne_casy']);

					$vysledky->update($id_vysledku, $dataDoDB);
				}

				//platné body a casy
				$this->model->update($id, array('platne_body' => true, 'platne_casy' => true));

				$this->flashMessage('Výsledky byly uloženy.', 'ok');
				$this->invalidateControl();
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Výsledky se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
			if(!$this->isAjax()) $this->redirect('this');
		}
		elseif($form['zverejnit']->isSubmittedBy())
		{
			try
			{
				$this->model->zverejnitVysledky($id);
				$this->flashMessage('Výsledky jsou zveřejněny.', 'ok');
				$this->invalidateControl('vysledky');
				if(!$this->isAjax()) $this->redirect('this');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Výsledky se nepodařilo zveřejnit.', 'error');
			}
		}
	}

	public function handleSmazatVysledek($id_vysledku)
	{
		try
		{
			$vysledky = new Vysledky;
			$vysledky->delete($id_vysledku);
			$this->flashMessage('Výsledek byl úspěšně odstraněn.', 'ok');
			$this->invalidateControl('vysledky');
			//$this->getApplication()->restoreRequest($this->backlink);
			if(!$this->isAjax()) $this->redirect('this');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Výsledek se nepodařilo odstranit.', 'error');
			$this->invalidateControl('vysledky');
			//$this->getApplication()->restoreRequest($this->backlink);
			if(!$this->isAjax()) $this->redirect('this');
		}
	}

	private function pripravRekordy($id)
	{
		$vysledkyModel = new Vysledky;

		$this->template->zavod['dosavadniRekordy'] = $vysledkyModel->dosavadniRekordyZavodu($id)->fetchAssoc('soutez,kategorie,=');
		foreach ($this->template->zavod['dosavadniRekordy'] as $soutez)
			foreach ($soutez as &$rekord)
			{
				$rekord['vysledny_cas'] = sprintf('%.2f', $rekord['vysledny_cas']);
				$rekord['druzstvo'] = trim($rekord['druzstvo']);
			}

		if($this->template->zavod['datum'] < Datum::$dnes['dnes'])
		{
			$this->template->zavod['rekordy'] = $vysledkyModel->rekordyZavodu($id)->fetchAssoc('soutez,kategorie,=');
			foreach ($this->template->zavod['rekordy'] as $soutez)
				foreach ($soutez as &$rekord)
				{
					$rekord['vysledny_cas'] = sprintf('%.2f', $rekord['vysledny_cas']);
					$rekord['druzstvo'] = trim($rekord['druzstvo']);
				}
		}
		else
		{
			$this->template->zavod['rekordy'] = array();
		}

		$this->template->rekordyLigy = array('dosavadni' => array(), 'aktualni' => array());
		$this->template->rekordyLigy['dosavadni'] = $vysledkyModel->rekordyLigy($id, true)->fetchAssoc('soutez,kategorie,=');
		if($this->template->zavod['datum'] < Datum::$dnes['dnes'])$this->template->rekordyLigy['aktualni'] = $vysledkyModel->rekordyLigy($id, false)->fetchAssoc('soutez,kategorie,=');
	}

	public function renderPripravaProKomentatora($id)
	{
		$ucastiModel = new Ucasti;
		$startovniPoradiModel = new StartovniPoradi;
		$vysledkyModel = new Vysledky();

		$this->template->zavod = (array) $this->model->find($id)->fetch();

		$ucasti = $ucastiModel->findByZavod($id)->fetchAssoc('nazev,kategorie,=');

		$prubeznaUmisteni = $vysledkyModel->findByRocnikAndZavod($this->template->zavod['id_rocniku'], $id)->fetchAssoc('soutez,kategorie,id_druzstva,=');
		$vysledkyModel->vyhodnotVysledkyRocniku($prubeznaUmisteni);

		$this->template->informace = array();
		$this->template->informaceZahlavi = array();
		$prihlasky = $startovniPoradiModel->findByZavod($id)->fetchAssoc('kategorie,id_druzstva,=');
		foreach ($ucasti as $soutez => $ucfoo)
		{
			//$this->template->informace[$soutez] = array();
			// přiřadí do soutěže všechna přihlášená družstva
			//$this->template->informace[$soutez] =

			// žádná družstva nejsou přihlášena
			if(0 && !count($this->template->informace[$soutez]))
			{
				unset($this->template->informace[$soutez]);
				continue;
			}

			foreach ($ucfoo as $kategorie => $ucbar)
			{
				if(!isset($prihlasky[$kategorie])) continue;
				$this->template->informaceZahlavi[$soutez][$kategorie] = array();
				$this->template->informace[$soutez][$kategorie] = $prihlasky[$kategorie];
				foreach ($this->template->informace[$soutez][$kategorie] as $id_druzstva => &$druzstvo)
				{
					$this->template->informaceZahlavi[$soutez][$kategorie][] = 'SP';
					$this->template->informaceZahlavi[$soutez][$kategorie][] = 'Družstvo';
					$this->template->informaceZahlavi[$soutez][$kategorie][] = 'Průběžné pořadí';

					// nastaví se průběžné umístění družstva v sezóně
					$druzstvo['prubezneUmisteni'] = isset($prubeznaUmisteni[$soutez][$kategorie][$id_druzstva]) ? $prubeznaUmisteni[$soutez][$kategorie][$id_druzstva] : NULL;

					// nalezneme pro každé jednotlivé družstvo nejlepší letošní a průměrný čas na stejný typ terčů
					$casyDruzstva = $vysledkyModel->vyznacneCasySezonDruzstva($id_druzstva)->where('[rocniky].[id] = %sql', "(SELECT [id_rocniku] FROM [zavody] WHERE [id] = " . $id . ") AND [typy_tercu].[id] = (SELECT [terce].[id_typu] FROM [zavody] LEFT JOIN [terce] ON [terce].[id] = [zavody].[id_tercu] WHERE [zavody].[id] = " . $id . ") AND [souteze].[id] = " . $ucbar['id_souteze'])->fetch();
					$casy = explode(',', $casyDruzstva['casy']);
					if(count($casy) > 2)
					{
						sort($casy);
						$median = $casy[(int) ((count($casy) + 1) / 2.0) - 1];
						$casyDruzstva['prumer'] = $median;
					}
					$druzstvo['letosniRekord'] = $casyDruzstva['rekord'] != 0.0 ? sprintf("%.2f", $casyDruzstva['rekord']) : NULL;
					$druzstvo['letosniPrumer'] = $casyDruzstva['prumer'] != 0.0 ? sprintf("%.2f", $casyDruzstva['prumer']) : NULL;

					// nastaví se rekord družstva
					if(!isset($rekordy[$id_druzstva])) $rekordy[$id_druzstva] = $vysledkyModel->rekordyDruzstva($id_druzstva)->fetchAssoc('soutez,kategorie,terce,=');
					$druzstvo['rekord'] = isset($rekordy[$id_druzstva][$soutez][$kategorie][$this->template->zavod['terce']]) ? $rekordy[$id_druzstva][$soutez][$kategorie][$this->template->zavod['terce']] : NULL;

					if(!isset($rekordyNaZavodu[$id_druzstva])) $rekordyNaZavodu[$id_druzstva] = $vysledkyModel->rekordyZavodu($id, $id_druzstva)->fetchAssoc('soutez,=');
					$druzstvo['tratovyRekord'] = isset($rekordyNaZavodu[$id_druzstva][$soutez]) ? $rekordyNaZavodu[$id_druzstva][$soutez] : NULL;
				}
			}
		}

		$this->pripravRekordy($id);

		$datum = new Datum;
		$this->setTitle('Informace pro komentátora, ' . $this->template->zavod['nazev'] . ' ' . $datum->date(substr($this->template->zavod['datum'], 0, 10), 0, 0, 0));
	}

	public function actionVysledkyExcel($id)
	{
		$this->template->zavod = $this->model->find($id)->fetch();
		if(!$this->template->zavod) throw new BadRequestException('Závod nebyl nalezen.');

		$zavod['poradatele'] = $this->model->findPoradatele($id);
		$this->template->zavod['muze_editovat'] = (bool) $this->user->isAllowed(new ZavodResource($this->template->zavod), 'edit');
		$this->template->zavod['muze_hodnotit'] = (bool) (strtotime($this->template->zavod['datum']) < strtotime('NOW'));

		$datum = new Datum;
		$this->setTitle('Závod ' . $this->template->zavod['nazev'] . ', ' . $datum->date(substr($this->template->zavod['datum'], 0, 10), 1, 0, 0));

		$this->pripravVysledky($id, false);

		$this->template->nasledujici = $this->model->findNext($id)->fetch();
		$this->template->predchozi = $this->model->findPrevious($id)->fetch();

		$this->pripravRekordy($id);

		$this->template->startovni_poradi = array();
		$this->startovniPoradi($id, false);

		$this->template->predchoziKola = array();
		$this->template->predchoziKola = $this->model->findPredchoziKola($id); //Poradatel($this->template->zavod['id_poradatele'])->where('[zavody].[id] != %i', $id);

		$this->template->backlink = $this->application->storeRequest();
		$this->template->adresaLigy = $this->getHttpRequest()->getUri()->getScheme().'://'.$this->getHttpRequest()->getUri()->getHost();

		$kategorie2index = array();
		$kategoriePosledniPozice = array();

		require LIBS_DIR.'/PHPExcel/PHPExcel.php';
		$dokument = new PHPExcel();
		$dokument->removeSheetByIndex(0);

		$stylZahlaviTabulky = array(
		    'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM
		    ),
		    'font' => array(
			   'bold' => true
		    ),
		    'alignment' => array(
			   'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
		    )
		);

		$stylRamecekOkolo = array('allborders' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN
		));

		$bodoveTabulkyModel = new BodoveTabulky;

		foreach($this->template->vysledky['vysledky'] as $soutez => $foo)
		{
			foreach($foo as $kategorie => $bar)
			{
				if(!array_key_exists($kategorie, $kategorie2index))
				{
					$kategorie2index[$kategorie] = count($kategorie2index);
					$dokument->createSheet($kategorie2index[$kategorie]);

					$kategoriePosledniPozice[$kategorie] = 1;
				}
				$list = $dokument->setActiveSheetIndex($kategorie2index[$kategorie]);
				$list->setTitle($this->zvetsPrvni($kategorie));

				$aktualniRadek = $kategoriePosledniPozice[$kategorie];

				$list->mergeCells('A'.$aktualniRadek.':F'.$aktualniRadek);
				$styl = $list->getStyle('A'.$aktualniRadek);
				$styl->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$styl->getFont()->setBold(true)->setSize(18);
				$list->setCellValue('A'.$aktualniRadek, $this->zvetsPrvni($soutez), true);
				$aktualniRadek++;

				$vysledky = $bar['vysledky'];

				$list->setCellValueByColumnAndRow(0, $aktualniRadek, 'Pořadí', true);
				$list->setCellValueByColumnAndRow(1, $aktualniRadek, 'Družstvo', true);
				$list->setCellValueByColumnAndRow(2, $aktualniRadek, 'Okres', true);
				$list->setCellValueByColumnAndRow(3, $aktualniRadek, 'Zadejte čas', true);
				$list->setCellValueByColumnAndRow(4, $aktualniRadek, 'Výsledný čas', true);
				$list->setCellValueByColumnAndRow(5, $aktualniRadek, 'Body', true);

				$list->getStyle('A'.$aktualniRadek.':F'.$aktualniRadek)->applyFromArray($stylZahlaviTabulky)->getBorders()->applyFromArray($stylRamecekOkolo);

				$aktualniRadek++;
				$offsetPrvnihoDruzstva = NULL;
				$vysledek = reset($vysledky);
				$bodovaTabulka = $bodoveTabulkyModel->findByUcast($vysledek['id_ucasti'])->fetch();
				$bodyModel = new Body();
				$body = $bodyModel->findByTabulka($bodovaTabulka['id']);
				foreach($vysledky as $vysledek)
				{
					if($offsetPrvnihoDruzstva === NULL) $offsetPrvnihoDruzstva = $aktualniRadek;

					// IF(A2=1,10,IF(A2=2,9,0))
					$vzorecBody = '=IF(A'.$aktualniRadek.'<>"",IF(D'.$aktualniRadek.'=1000,0,';
					$zavorky = '';
					foreach($body as $bod)
					{
						$vzorecBody .= 'IF(A'.$aktualniRadek.'='.$bod['poradi'].','.$bod['body'].',';
						$zavorky .= ')';
					}
					$vzorecBody .= '0'.$zavorky.'),"")';

					$vzorecPoradi = '=IF(D'.$aktualniRadek.'<>"",RANK(D'.$aktualniRadek.',D$'.$offsetPrvnihoDruzstva.':D$'.($offsetPrvnihoDruzstva+count($vysledky)-1).',1),"")';
					// Výsledné pořadí
					$list->setCellValueByColumnAndRow(0, $aktualniRadek, $vzorecPoradi);
					$list->getStyleByColumnAndRow(0, $aktualniRadek)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

					// Družstvo
					$list->setCellValueByColumnAndRow(1, $aktualniRadek, $vysledek['druzstvo']);

					// Okres
					$list->setCellValueByColumnAndRow(2, $aktualniRadek, $vysledek['okres_zkratka']);
					$list->getStyleByColumnAndRow(2, $aktualniRadek)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

					// Výsledný čas pro tisk
					$list->setCellValueByColumnAndRow(4, $aktualniRadek, '=IF(D'.$aktualniRadek.'<>"",IF(D'.$aktualniRadek.'>500,"NP",D'.$aktualniRadek.'),"")');

					$list->setCellValueByColumnAndRow(5, $aktualniRadek, $vzorecBody);
					$list->getStyleByColumnAndRow(5, $aktualniRadek)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

					$vzhled = $list->getStyleByColumnAndRow(4, $aktualniRadek);
					$vzhled->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
					$vzhled->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

					// Zadávání výsledného času
					$dv = $list->getCellByColumnAndRow(3, $aktualniRadek)->getDataValidation();
					$dv->setType(PHPExcel_Cell_DataValidation::TYPE_DECIMAL);
					$dv->setAllowBlank(false);

					$dv->setShowErrorMessage(true);
					$dv->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_WARNING);
					$dv->setErrorTitle('Chyba');
					$dv->setError('Zadejte čas jako desetinné číslo, nebo 1000 jako NP.');

					$dv->setShowInputMessage(true);
					$dv->setPromptTitle('Zadejte čas');
					$dv->setPrompt('Zadejte čas jako desetinné číslo, nebo 1000 jako NP.');

					$list->getColumnDimension('D')->setOutlineLevel(1);

					// rámaček okolo celého řádku
					$list->getStyle('A'.$aktualniRadek.':F'.$aktualniRadek)->getBorders()->applyFromArray($stylRamecekOkolo);

					$aktualniRadek++;
				}
				$kategoriePosledniPozice[$kategorie] = ++$aktualniRadek;
			}
			$list->getColumnDimensionByColumn(0)->setAutoSize(true);
			$list->getColumnDimensionByColumn(1)->setAutoSize(true);
			$list->getColumnDimensionByColumn(2)->setAutoSize(true);
			$list->getColumnDimensionByColumn(3)->setAutoSize(true);
			$list->getColumnDimensionByColumn(4)->setAutoSize(true);
			$list->getColumnDimensionByColumn(5)->setAutoSize(true);

			$list->getPageSetup()->setHorizontalCentered(true);
			$list->getPageSetup()->setVerticalCentered(false);
			$hf = new PHPExcel_Worksheet_HeaderFooter();
			$hf->setEvenHeader('&B&20Výsledky ze soutěže '.$this->template->zavod['nazev'].' '.$this->template->zavod['rok'].' - &A');
			$hf->setOddHeader('&B&20Výsledky ze soutěže '.$this->template->zavod['nazev'].' '.$this->template->zavod['rok'].' - &A');
			$list->setHeaderFooter($hf);
		}

		$vlastnosti = $dokument->getProperties();
		$vlastnosti->setCreator('Informační systém '.self::$liga['zkratka']);
		$vlastnosti->setCreated(time());
		$vlastnosti->setTitle('Výsledky ze soutěže '.$this->template->zavod['nazev'].' '.$this->template->zavod['rok']);

		$writer = new PHPExcel_Writer_Excel2007($dokument);
		$writer->setPreCalculateFormulas(false);

		$this->getHttpResponse()->addHeader('Content-Type', 'pplication/vnd.openxmlformats-officedocument.spreadsheetml.document; charset=utf-8');
		$this->getHttpResponse()->addHeader('Content-Disposition', 'attachment; filename='.String::webalize($this->template->zavod['nazev']).'-'.$this->template->zavod['rok'].'-vysledky.xlsx');

		$writer->save('php://output');
		$this->terminate();
	}

}
