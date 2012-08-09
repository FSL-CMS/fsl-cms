<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Presenter fotogalerií
 *
 * @author	Milan Pála
 */
class FotogaleriePresenter extends BasePresenter
{

	protected $model;

	protected function startup()
	{
		$this->model = new Fotogalerie;
		parent::startup();
		if($this->user->isAllowed('fotogalerie', 'edit')) $this->model->zobrazitNezverejnene();
	}

	public function actionFotogalerie($id = 0)
	{
		if($id == 0) $this->redirect('default');

		$fotogalerie = (array) $this->model->find($id)->fetch();

		if(!$fotogalerie)
		{
			throw new BadRequestException('Fotogalerie nebyla nalezena.');
		}
		elseif(!$this->user->isAllowed('fotogalerie', 'edit') && (empty($fotogalerie['datum_zverejneni']) || strtotime($fotogalerie['datum_zverejneni']) > strtotime('NOW')))
		{
			$this->flashMessage('Požadovaná fotogalerie nebyla zveřejněna.', 'error');
			$this->redirect('default');
		}
		$this->model->noveZhlednuti($id);

		if($fotogalerie['typ'] == 'rajce') $this->setView('fotogalerieRajce');
	}

	public function vyberZfotogalerie($id = 0)
	{

	}

	public function actionFotka($id = 0)
	{
		if($id == 0) $this->redirect('Fotogalerie:default');

		$fotky = new Fotky;
		$fotka = (array) $fotky->find($id)->fetch();
		if($fotka === NULL) throw new BadRequestException('Fotka nebyla nalezena.');
	}

	public function actionPridatFotky($id = 0)
	{
		if(!$this->user->isAllowed('fotogalerie', 'edit')) throw new ForbiddenRequestException('Nemáte oprávnění přidávat fotky.');

		$fotogalerie = (array) $this->model->find($id)->fetch();
		if($id == 0) $this->redirect('default');
		elseif(!$fotogalerie)
		{
			throw new BadRequestException('Fotogalerie nebyla nalezena.');
		}
	}

	public function actionAdd()
	{
		parent::actionAdd();

		$this->setView('edit');
	}

	public function actionEdit($id = 0)
	{
		parent::actionEdit($id);
	}

	public function beforeRender()
	{
		$this->template->galerie = array();
		$this->template->fotky = array();

		$this->template->galerie['muze_pridavat'] = $this->user->isAllowed('fotogalerie', 'add');
		$this->template->galerie['muze_editovat'] = $this->user->isAllowed('fotogalerie', 'edit');
		$this->template->galerie['muze_smazat'] = false;

		parent::beforeRender();
	}

	public function renderDefault()
	{
		$this->template->galerie['galerie'] = $this->model->findAll()->fetchAll();

		foreach ($this->template->galerie['galerie'] as &$galerie)
		{
			if(empty($galerie['id_fotky']))
			{
				$fotky = new Fotky;
				$fotka = $fotky->findRandomFromGalerie($galerie['id'])->fetch();
				$galerie['id_fotky'] = $fotka['id'];
			}
			$galerie['muze_editovat'] = $this->user->isAllowed('fotogalerie', 'edit') || $this->jeAutor($galerie['id_autora']);
		}

		$this->setTitle('Fotogalerie');
	}

	public function renderVyber()
	{
		$this->getPresenter()->setLayout(false);

		$this->template->galerie['galerie'] = $this->model->findAll()->fetchAll();

		foreach ($this->template->galerie['galerie'] as &$galerie)
		{
			if(empty($galerie['id_fotky']))
			{
				$fotky = new Fotky;
				$fotka = $fotky->findRandomFromGalerie($galerie['id'])->fetch();
				$galerie['id_fotky'] = $fotka['id'];
			}
		}
	}

	public function renderVyberZfotogalerie($id)
	{
		$this->getPresenter()->setLayout(false);

		$fotky = new Fotky;

		$this->template->galerie += (array) $this->model->find($id)->fetch();
		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_smazat'] |= $this->user->isAllowed('fotogalerie', 'delete') || $this->jeAutor($this->template->galerie['id_autora']);

		$this->template->fotky['fotky'] = $fotky->findBySouvisejici($id)->fetchAll();
		$this->template->fotky['muze_pridavat'] = $this->user->isAllowed('fotky', 'add') || $this->jeAutor($this->template->galerie['id_autora']);

		foreach ($this->template->fotky['fotky'] as $key => &$fotka)
		{
			if(!file_exists(APP_DIR . '/../data/' . $fotka['id'] . '.' . $fotka['pripona']))
			{
				unset($this->template->fotky['fotky'][$key]);
				continue;
			}
			$rozmery = getimagesize(APP_DIR . '/../data/' . $fotka['id'] . '.' . $fotka['pripona']);
			$fotka['sirka'] = $rozmery[0];
			$fotka['vyska'] = $rozmery[1];
		}

		$this->template->title = 'Galerie: ' . $this->template->galerie['nazev'];
	}

	/**
	 * Připraví výpis jedné fotogalerie
	 * @param int $id ID fotogalerie, který se má zobrazit
	 */
	public function renderFotogalerie($id)
	{
		$fotky = new Fotky;

		$this->template->galerie += (array) $this->model->find($id)->fetch();
		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_smazat'] |= $this->user->isAllowed('fotogalerie', 'delete') || $this->jeAutor($this->template->galerie['id_autora']);

		$this->template->fotky['fotky'] = $fotky->findByFotogalerie($id)->fetchAll();
		$this->template->fotky['muze_pridavat'] = $this->user->isAllowed('fotky', 'add') || $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->fotky['muze_smazat'] = true;

		foreach ($this->template->fotky['fotky'] as $key => &$fotka)
		{
			if(!file_exists(APP_DIR . '/../data/' . $fotka['id'] . '.' . $fotka['pripona']))
			{
				unset($this->template->fotky['fotky'][$key]);
				continue;
			}
			$rozmery = getimagesize(APP_DIR . '/../data/' . $fotka['id'] . '.' . $fotka['pripona']);
			$fotka['sirka'] = $rozmery[0];
			$fotka['vyska'] = $rozmery[1];
		}

		$this->setTitle('Galerie: ' . $this->template->galerie['nazev']);
	}

	/**
	 * Zobrazí fotogalerie ze serveru Rajče.cz.
	 * Pokud to je možné, pokusí se stáhnout stránku z galerií a z ní vyparsovat
	 * informace o fotkách a ty zobrazit jako běžné fotky. Jinak zobrazí
	 * galerii běžnou cestou pomocí značky iframe.
	 * @param type $id ID galerie
	 */
	public function renderFotogalerieRajce($id)
	{
		$this->template->galerie += (array) $this->model->find($id)->fetch();
		$rajceStranka = @file_get_contents($this->template->galerie['typ_key'] . '?insert=1');
		$this->template->fotky = array('fotky' => array());
		if($rajceStranka !== false && preg_match('/var storage = "([^"]+)"/', $rajceStranka, $matches) != 0)
		{
			$this->template->rajceStorage = $matches[1];

			preg_match_all('/photo\d+\["fileName"] = "([^"]+)";/', $rajceStranka, $matches);
			foreach ($matches[1] as $match)
			{
				$this->template->fotky['fotky'][] = array('soubor' => $match);
			}
		}

		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_smazat'] |= $this->user->isAllowed('fotogalerie', 'delete') || $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->fotky['muze_pridavat'] = false;
		$this->template->fotky['muze_smazat'] = false;

		$this->setTitle('Galerie: ' . $this->template->galerie['nazev']);
	}

	public function renderPridatFotky($id)
	{
		$this['imageUploader']->setId($id);
		$this->template->galerie = $this->model->find($id)->fetch();

		$this->setTitle('Přidání fotek do galerie "' . $this->template->galerie['nazev'] . '"');
	}

	public function renderFotka($id)
	{
		$fotky = new Fotky;

		$this->template->fotka = array();
		$this->template->fotka = $fotky->find($id)->fetch();
		$this->template->fotka['muze_editovat'] = $this->user->isAllowed('fotky', 'edit') || $this->jeAutor($this->template->fotka['id_autora']);

		$this->template->galerie += (array) $this->model->find($this->template->fotka['id_fotogalerie'])->fetch();
		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);

		$this->template->fotky['muze_pridavat'] = $this->user->isAllowed('fotky', 'add') || $this->jeAutor($this->template->galerie['id_autora']);

		$this->setTitle('Fotka: ' . $this->template->fotka['soubor'] . '.' . $this->template->fotka['pripona']);
	}

	public function createComponentPridaniFotekDoAlba5Form()
	{
		$form = new AppForm($this, 'pridaniFotekDoAlba5Form');

		//$form->addInput('file');
	}

	public function createComponentPridaniFotekDoAlbaForm($name)
	{
		$form = new AppForm($this, $name);
		$form->getElementPrototype()->class[] = "ajax";

		$form->addMultipleFileUpload('upload', 'Uložit soubory do alba', 20)
		/* ->addRule("MultipleFileUpload::validateFilled", "Musíte odeslat alespoň jeden soubor!")
		  ->addRule("MultipleFileUpload::validateFileSize", "Soubory jsou dohromady moc veliké!",1024*1024) */;

		$form->addSubmit('save', 'Uložit soubory do alba');
		$form->onSubmit[] = array($this, 'pridaniFotekDoAlbaFormSubmitted');

		$form->addGroup('Vyberte soubory k nahrání', true);

		$form->onInvalidSubmit[] = array($this, "handlePrekresliForm");
		$form->onSubmit[] = array($this, "handlePrekresliForm");
	}

	public function pridaniFotekDoAlbaFormSubmitted(AppForm $form)
	{
		$data = $form->getValues();
		$id_fotogalerie = (int) $this->getParam('id');

		// zpracovat fotku
		// Přesumene uploadované soubory
		foreach ($data["upload"] AS $file)
		{
			try
			{
				$fotka = new Fotky($file);
				$fotka->id_autora = $this->user->getIdentity()->id;
				$fotka->uloz($id_fotogalerie);
				$this->flashMessage('Soubor ' . $file->getName() . ' byl úspěšně uložen.', 'ok');
			}
			catch (Exception $e)
			{
				$this->flashMessage('Nepodařilo se uložit soubor ' . $file->getName() . '. Chyba: ' . $e->getMessage(), 'error');
				//Debug::processException($e, true);
			}
		}

		// Předáme data do šablony
		$this->template->values = $data;
		$this->invalidateControl('flashes');
	}

	public function handlePrekresliForm()
	{
		parent::handlePrekresliForm();
	}

	public function renderEdit($id = 0, $souvisejici = '', $id_souvisejiciho = 0)
	{
		$this->template->fotogalerie = array();
		if($id != 0) $this->template->fotogalerie = (array) $this->model->find($id)->fetch();

		if($id != 0) $this->template->fotogalerie[$this->template->fotogalerie['typ']]['typ_key'] = $this->template->fotogalerie['typ_key'];

		if($id != 0) $this['editForm']->setDefaults($this->template->fotogalerie);

		if($id == 0) $this->setTitle('Přidání fotogalerie');
		else $this->setTitle('Úprava fotogalerie');

		if($id != 0 && !empty($this->template->fotogalerie['souvisejici']))
		{
			$souvisejici = $this->template->fotogalerie['souvisejici'];
			$id_souvisejici = $this->template->fotogalerie['id_souvisejiciho'];
		}

		switch ($souvisejici)
		{
			case 'zavody': $souvisejiciModel = new Zavody;
				break;
			case 'clanky': $souvisejiciModel = new Clanky;
				break;
			case 'terce': $souvisejiciModel = new Terce;
				break;
			case 'sbory': $souvisejiciModel = new Sbory;
				break;
			case 'druzstva': $souvisejiciModel = new Druzstva;
				break;
			default: $souvisejiciModel = NULL;
				break;
		}
		if($souvisejiciModel !== NULL)
		{
			$this['editForm']['souvisejici']->setValue($souvisejici);
			$this['editForm']['id_souvisejiciho']->setValue($id_souvisejiciho);
		}
	}

	public function createComponentEditForm($name)
	{
		$id = (int) $this->getParam('id');

		$form = new AppForm($this, $name);

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o fotogalerii');
		$form->addText('nazev', 'Název galerie', 30)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit název galerie.')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka názvu je %d znaků.', 255);
		$form->addAdminTexylaTextArea('text', 'Popis galerie')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka textu je %d znaků.', 65535);

		$form->addGroup('Typ galerie');
		$form->addSelect('typ', 'Typ galerie', array('nativni' => 'integrovaná', 'rajce' => 'Rajče.cz'))
			   ->addCondition(Form::EQUAL, 'rajce')->toggle('rajce', true)
			   ->addCondition(Form::EQUAL, 'nativni')->toggle('nativni', true);

		$form->addGroup('Galerie uložená na Rajče.cz')->setOption("container", Html::el("fieldset")->id("rajce"));
		$rajceCont = $form->addContainer('rajce');
		$rajceCont->addText('typ_key', 'Odkaz na galerii')->setOption('description', 'Uveďte odkaz na galerii, např.: http://ukazka.rajce.idnes.cz/zvirata_v_ZOO');

		$form->addGroup('Integrovaná galerie')->setOption("container", Html::el("fieldset")->id("nativni"));
		$nativniCont = $form->addContainer('nativni');
		$nativniCont->addTextArea('typ_key', 'Integrovaná galerie pro nahrávání fotek');

		$form->addContainer('jizSouvisejici');

		if($id == 0) $zverejneni = array('ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		else $zverejneni = array('ponechat' => 'nechat bez změny', 'ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		$form->addGroup('Zveřejnění galerie');
		$form->addRadioList('zverejneni', 'Zveřejnění galerie', $zverejneni)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit, kdy se má galerie zveřejnit.')
			   ->addCondition(Form::EQUAL, 'ulozit')
			   ->toggle('datum_zverejneniContainer');
		$form->addDateTimePicker('datum_zverejneni', 'Datum zveřejnění fotogalerie')
			   ->setOption('container', Html::el('div')->id('datum_zverejneniContainer'))
			   ->addConditionOn($form['zverejneni'], Form::EQUAL, 'datum_zverejneni')
			   ->addRule(Form::FILLED, 'Je nutné vyplnit datum zveřejnění fotogalerie.');

		if($id == 0) $form['zverejneni']->setDefaultValue('ulozit');
		else $form['zverejneni']->setDefaultValue('ponechat');

		$form->addGroup('Uložit');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zrušit')
			   ->setValidationScope(FALSE);
		;

		$form->onSubmit[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		if($form['cancel']->isSubmittedBy())
		{
			$this->redirect('Fotogalerie:fotogalerie', $id);
		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			$data = $form->getValues();

			$dataDoDB = array('nazev' => $data['nazev'], 'text' => $data['text'], 'typ' => $data['typ'], 'typ_key' => $data[$data['typ']]['typ_key']);
			if($data['zverejneni'] == 'ulozit') $dataDoDB['datum_zverejneni%sn'] = '';
			elseif($data['zverejneni'] == 'ihned') $dataDoDB['datum_zverejneni%sql'] = "NOW()";
			elseif($data['zverejneni'] == 'ponechat')
			{

			}
			else $dataDoDB['datum_zverejneni%t'] = $data['datum_zverejneni'];

			try
			{
				if($id == 0)
				{
					$dataDoDB['datum_pridani%sql'] = 'NOW()';
					$dataDoDB['id_autora'] = $this->user->getIdentity()->id;
					$this->model->insert($dataDoDB);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$dataDoDB['posledni_aktualizace%sql'] = 'NOW()';
					$this->model->update($id, $dataDoDB);
				}
				$this->flashMessage('Fotogalerie byla úspěšně uložena.', 'ok');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Fotogalerii se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}

			if($form['saveAndReturn']->isSubmittedBy() || $form['cancel']->isSubmittedBy()) $this->redirect('Fotogalerie:fotogalerie', $id);
			else $this->redirect('this');
		}
	}

	public function handleSmazatFotku($id)
	{
		$fotky = new Fotky;
		$fotka = $fotky->find($id)->fetch();

		$fotogalerie = $this->model->find($fotka['id_fotogalerie'])->fetch();

		if(!$this->user->isAllowed('fotky', 'delete') || !$this->jeAutor($fotka['id_autora']) || !$this->jeAutor($fotogalerie['id_autora'])) throw new ForbiddenRequestException('Nemáte oprávnění na odstranění fotografie.');

		try
		{
			$fotky->delete($id);
			$this->flashMessage('Fotka byla odstraněna.');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Fotku se nepodařilo odstranit.');
			Debug::processException($e);
		}

		$this->invalidateControl('flashes');
		$this->invalidateControl('galerie');

		$this->redirect('Fotogalerie:fotogalerie', $fotka['id_fotogalerie']);
	}

	public function handleDelete($id, $force = 0)
	{
		$fotogalerie = $this->model->find($id)->fetch();
		if(!$this->user->isAllowed('fotogalerie', 'delete') && !$this->jeAutor($fotogalerie['id_autora'])) throw new ForbiddenRequestException('Nemáte oprávnění na odstranění fotogalerie.');

		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Fotogalerie byla odstraněna.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Fotogalerii se nepodařilo odstranit.', 'error');
			Debug::processException($e);
		}
		catch (RestrictionException $e)
		{
			$this->flashMessage($e->getMessage() . ' <a href="' . $this->link('delete', array('id' => $id, 'force' => true)) . '" class="delete">Přesto smazat!</a>', 'error');
		}

		$this->redirect('this');
	}

	public function handleTruncate($id)
	{
		$fotogalerie = $this->model->find($id)->fetch();
		if(!$this->user->isAllowed('fotogalerie', 'delete') && !$this->jeAutor($fotogalerie['id_autora'])) throw new ForbiddenRequestException('Nemáte oprávnění na vyprázdnění fotogalerie.');

		try
		{
			$this->model->truncate($id);
			$this->flashMessage('Fotogalerie byla vyprázdněna.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Fotogalerii se nepodařilo vyprázdnit.', 'error');
			Debug::processException($e, true);
		}

		$this->redirect('this');
	}

}
