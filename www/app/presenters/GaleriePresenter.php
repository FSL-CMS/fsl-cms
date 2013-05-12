<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;
use Nette\Diagnostics\Debugger;

/**
 * Presenter galerií
 *
 * @author	Milan Pála
 */
class GaleriePresenter extends BasePresenter
{

	protected $model;

	protected function startup()
	{
		$this->model = $this->context->galerie;
		parent::startup();
		if($this->user->isAllowed('galerie', 'edit')) $this->model->zobrazitNezverejnene();
	}

	public function actionGalerie($id = 0)
	{
		if($id == 0) $this->redirect('default');

		$galerie = (array) $this->model->find($id)->fetch();

		if(!$galerie)
		{
			throw new BadRequestException('Galerie nebyla nalezena.');
		}
		elseif(!$this->user->isAllowed('galerie', 'edit') && (empty($galerie['datum_zverejneni']) || strtotime($galerie['datum_zverejneni']) > strtotime('NOW')))
		{
			$this->flashMessage('Požadovaná galerie nebyla zveřejněna.', 'error');
			$this->redirect('default');
		}
		$this->model->noveZhlednuti($id);

		if($galerie['typ'] == Galerie::$TYP_RAJCE) $this->setView('galerieRajce');
	}

	public function actionFotka($id = 0)
	{
		if($id == 0) $this->redirect('Galerie:default');

		$fotky = $this->context->fotky;
		$fotka = (array) $fotky->find($id)->fetch();
		if($fotka === NULL) throw new BadRequestException('Fotka nebyla nalezena.');
	}

	public function actionPridatFotky($id = 0)
	{
		if(!$this->user->isAllowed('galerie', 'edit')) throw new ForbiddenRequestException('Nemáte oprávnění přidávat fotky.');

		$galerie = (array) $this->model->find($id)->fetch();
		if($id == 0) $this->redirect('default');
		elseif(!$galerie)
		{
			throw new BadRequestException('Galerie nebyla nalezena.');
		}

		$fotkyModel = new FotkyManager();
		$fotkyModel->setAutor($this->user->getIdentity()->id);
		$fotkyModel->setSouvisejici($id);
		$this['fileUploader']->setFileModel($fotkyModel);
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
		$this->template->galerie['muze_pridavat'] = $this->user->isAllowed('galerie', 'add');
		$this->template->galerie['muze_editovat'] = $this->user->isAllowed('galerie', 'edit');
		$this->template->galerie['muze_smazat'] = false;

		$this->template->fotky = array('fotky' => array());
		$this->template->videa = array('videa' => array());

		parent::beforeRender();
	}

	public function renderDefault()
	{
		$this->template->galerie['galerie'] = $this->model->findAll()->fetchAll();

		foreach ($this->template->galerie['galerie'] as &$galerie)
		{
			if(empty($galerie['id_fotky']))
			{
				$fotky = $this->context->fotky;
				$fotka = $fotky->findRandomFromGalerie($galerie['id'])->fetch();
				$galerie['id_fotky'] = $fotka['id'];
			}
			$galerie['muze_editovat'] = $this->user->isAllowed('galerie', 'edit') || $this->jeAutor($galerie['id_autora']);
		}

		$this->setTitle('Galerie');
	}

	public function renderVyber()
	{
		$this->getPresenter()->setLayout(false);

		$this->template->galerie['galerie'] = $this->model->findAll()->fetchAll();

		foreach ($this->template->galerie['galerie'] as &$galerie)
		{
			if(empty($galerie['id_fotky']))
			{
				$fotky = $this->context->fotky;
				$fotka = $fotky->findRandomFromGalerie($galerie['id'])->fetch();
				$galerie['id_fotky'] = $fotka['id'];
			}
		}
	}

	public function renderVyberZgalerie($id)
	{
		$this->getPresenter()->setLayout(false);

		$fotky = $this->context->fotky;

		$this->template->galerie += (array) $this->model->find($id)->fetch();
		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_smazat'] |= $this->user->isAllowed('galerie', 'delete') || $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_editovat'] |= $this->jeAutor($this->template->galerie['id_autora']);

		$this->template->fotky['fotky'] = $fotky->findBySouvisejici($id)->fetchAll();
		$this->template->fotky['muze_pridavat'] = $this->user->isAllowed('fotky', 'add') || $this->jeAutor($this->template->galerie['id_autora']);

		foreach ($this->template->fotky['fotky'] as $key => &$fotka)
		{
			if(!file_exists(DATA_DIR . '/' . $fotka['id'] . '.' . $fotka['pripona']))
			{
				unset($this->template->fotky['fotky'][$key]);
				continue;
			}
			$rozmery = getimagesize(DATA_DIR . '/' . $fotka['id'] . '.' . $fotka['pripona']);
			$fotka['sirka'] = $rozmery[0];
			$fotka['vyska'] = $rozmery[1];
		}

		$this->template->title = 'Galerie: ' . $this->template->galerie['nazev'];
	}

	/**
	 * Připraví výpis jedné galerie
	 * @param int $id ID galerie, který se má zobrazit
	 */
	public function renderGalerie($id)
	{
		$fotky = $this->context->fotky;

		$this->template->galerie += (array) $this->model->find($id)->fetch();
		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_smazat'] |= $this->user->isAllowed('galerie', 'delete') || $this->jeAutor($this->template->galerie['id_autora']);

		$this->template->fotky['fotky'] = $fotky->findByGalerie($id)->fetchAll();
		$this->template->fotky['muze_pridavat'] = $this->user->isAllowed('fotky', 'add') || $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->fotky['muze_smazat'] = true;

		$this->template->videa['muze_pridavat'] = $this->user->isAllowed('videa', 'add') || $this->jeAutor($this->template->galerie['id_autora']);

		foreach ($this->template->fotky['fotky'] as $key => &$fotka)
		{
			if(!file_exists(DATA_DIR . '/' . $fotka['id'] . '.' . $fotka['pripona']))
			{
				unset($this->template->fotky['fotky'][$key]);
				continue;
			}
			$rozmery = getimagesize(DATA_DIR . '/' . $fotka['id'] . '.' . $fotka['pripona']);
			$fotka['sirka'] = $rozmery[0];
			$fotka['vyska'] = $rozmery[1];
		}

		$videaModel = $this->context->videa;
		$this->template->videa['videa'] = $videaModel->findByGalerie($id);

		$this->setTitle('Galerie: ' . $this->template->galerie['nazev']);
	}

	/**
	 * Zobrazí galerie ze serveru Rajče.cz.
	 * Pokud to je možné, pokusí se stáhnout stránku z galerií a z ní vyparsovat
	 * informace o fotkách a ty zobrazit jako běžné fotky. Jinak zobrazí
	 * galerii běžnou cestou pomocí značky iframe.
	 * @param type $id ID galerie
	 */
	public function renderGalerieRajce($id)
	{
		$this->template->galerie += (array) $this->model->find($id)->fetch();
		$rajceStranka = @file_get_contents($this->template->galerie['typ_key'] . '?insert=1');
		if($rajceStranka !== false && preg_match('/var storage = "([^"]+)"/', $rajceStranka, $matches) != 0)
		{
			$this->template->rajceStorage = $matches[1];

			preg_match_all('/photo\d+\["fileName"] = "([^"]+)";/', $rajceStranka, $matches);
			foreach ($matches[1] as $match)
			{
				$this->template->fotky['fotky'][] = array('soubor' => $match);
			}
		}

		$videaModel = $this->context->videa;

		$this->template->videa['videa'] = $videaModel->findByGalerie($id);

		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->galerie['muze_smazat'] |= $this->user->isAllowed('galerie', 'delete') || $this->jeAutor($this->template->galerie['id_autora']);
		$this->template->fotky['muze_pridavat'] = false;
		$this->template->fotky['muze_smazat'] = false;
		$this->template->videa['muze_pridavat'] = true;
		$this->template->videa['muze_smazat'] = true;

		$this->setTitle('Galerie: ' . $this->template->galerie['nazev']);
	}

	public function renderPridatFotky($id)
	{
		$this->template->galerie = $this->model->find($id)->fetch();

		$this->setTitle('Přidání fotek do galerie "' . $this->template->galerie['nazev'] . '"');
	}

	public function actionPridatVidea($id = 0)
	{
		if($id == 0) $this->redirect('Galerie:default');

		if(!$this->user->isAllowed('galerie', 'edit')) throw new ForbiddenRequestException('Nemáte oprávnění přidávat videa.');
	}

	public function renderPridatVidea($id)
	{
		$this->template->galerie = $this->model->find($id)->fetch();

		$videaModel = $this->context->videa;
		$videa = $videaModel->findByGalerie($id)->fetchAssoc('id,=');
		$this['videosEditForm']->setValues($videa);

		$this->setTitle('Přidání videí do galerie "' . $this->template->galerie['nazev'] . '"');
	}

	public function createComponentVideosEditForm($name)
	{
		$videaModel = $this->context->videa;
		$id = $this->getParam('id');

		$f = new Nette\Application\UI\Form($this, $name);

		$videa = $videaModel->findByGalerie($id)->fetchAssoc('id,=');
		$videa += array(0 => array());

		foreach ($videa as $id_videa => $video)
		{
			if($id_videa == 0) $f->addGroup('Nové video', true);
			else $f->addGroup(null, true);
			$c = $f->addContainer($id_videa);
			$c->addHidden('id')->setDefaultValue(0);
			$c->addText('nazev', 'Název videa', 50, 255);
			if($id_videa != 0)
			{
				$c['nazev']->setRequired('Je nutné vyplnit název videa.');
			}
			$c->addSelect('typ', 'Typ videa', array(Galerie::$VIDEO_YOUTUBE => 'Youtube', Galerie::$VIDEO_STREAM => 'Stream', Galerie::$VIDEO_YOUTUBEPLAYLIST => 'Youtube Playlist'))->setRequired('Je nutné vybrat typ videa.');
			$c->addText('url', 'Adresa videa', 35, 255)->setOption('description', 'Např. http://www.youtube.com/watch?v=cmYbd2Qwq');
			if($id_videa != 0)
			{
				$c['url']->setRequired('Je nutné vyplnit adresu videa.');
			}
			else
			{
				$c['nazev']->addConditionOn($c['url'], Form::FILLED)
					   ->addRule(Form::FILLED, 'Je nutné vyplnit název videa.');
				$c['url']->addConditionOn($c['nazev'], Form::FILLED)
					   ->addRule(Form::FILLED, 'Je nutné vyplnit adresu videa.');
			}
			$c->addText('identifikator', 'Identifikátor videa', 35, 255)->setOption('description', 'Získá se automaticky z adresy videa po uložení.')->setDisabled(true);
			if($id_videa != 0) $c->addCheckbox('delete', 'Odstranit');
		}

		$f->addGroup('Uložení');
		$f->addSubmit('save', 'Uložit');
		$f->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$f->addSubmit('cancel', 'Zpět')
			   ->setValidationScope(FALSE);

		$f->onSuccess[] = array($this, 'videosEditFormSubmitted');
	}

	public function videosEditFormSubmitted(Nette\Application\UI\Form $f)
	{
		$videaModel = $this->context->videa;
		$id = $this->getParam('id');

		if($f['cancel']->isSubmittedBy())
		{
			$this->redirect('galerie', $id);
		}
		elseif($f['save']->isSubmittedBy() || $f['saveAndReturn']->isSubmittedBy())
		{
			try
			{
				foreach ($f->getValues() as $video)
				{
					if($video['id'] == 0 && empty($video['url'])) continue;
					if(isset($video['delete']) && $video['delete'] == true)
					{
						try
						{
							$videaModel->delete($video['id']);
							$this->flashMessage('Video *'.$video['nazev'].'* bylo odstraněno.', 'ok');
						}
						catch (DibiException $e)
						{
							$this->flashMessage('Nepodařilo se smazat video *'.$video['nazev'].'*', 'error');
							Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
						}
						continue;
					}
					$data = array('nazev' => $video['nazev'], 'souvisejici' => 'galerie', 'id_souvisejiciho' => $id, 'typ' => $video['typ'], 'url' => $video['url']);
					if(strpos($video['url'], 'http://') !== false)
					{
						if($video['typ'] == Galerie::$VIDEO_YOUTUBE)
						{
							if(preg_match('/watch\?v=([^&]+)/', $video['url'], $matched))
							{
								$data['identifikator'] = $matched['1'];
							}
							elseif(preg_match('/youtu\.be\/([^?]+)/', $video['url'], $matched))
							{
								$data['identifikator'] = $matched['1'];
							}
							else
							{
								$this->flashMessage('Nepodařilo se ověřit identifikátor videa *' . $video['url'] . '*.', 'warning');
								continue;
							}
						}
						elseif($video['typ'] == Galerie::$VIDEO_YOUTUBEPLAYLIST)
						{
							if(preg_match('~http://www\.youtube\.com/playlist\?list=([^&]+)~', $video['url'], $matched))
							{
								$data['identifikator'] = $matched['1'];
							}
							else
							{
								$this->flashMessage('Nepodařilo se ověřit identifikátor videa *' . $video['url'] . '*.', 'warning');
								continue;
							}
						}
						elseif($video['typ'] == Galerie::$VIDEO_STREAM)
						{
							if(preg_match('/stream\.cz\/[^\/]+\/([^\/?]+)/', $video['url'], $matched))
							{
								$data['identifikator'] = $matched['1'];
							}
							else
							{
								$this->flashMessage('Nepodařilo se ověřit identifikátor videa *' . $video['url'] . '*.', 'warning');
								continue;
							}
						}
					}
					if($video['id'] == 0)
					{
						$videaModel->insert($data);
					}
					else
					{
						$videaModel->update($video['id'], $data);
					}
					$this->flashMessage('Video *'.$video['nazev'].'* bylo úspěšně uloženo.', 'ok');
				}
				if($f['saveAndReturn']->isSubmittedBy()) $this->redirect('Galerie:galerie', $id);
				else $this->redirect('this');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Nepodařilo se uložit videa.', 'error');
				Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}
		}
	}

	public function renderFotka($id)
	{
		$fotky = $this->context->fotky;

		$this->template->fotka = array();
		$this->template->fotka = $fotky->find($id)->fetch();
		$this->template->fotka['muze_editovat'] = $this->user->isAllowed('fotky', 'edit') || $this->jeAutor($this->template->fotka['id_autora']);

		$this->template->galerie += (array) $this->model->find($this->template->fotka['id_galerie'])->fetch();
		$this->template->galerie['muze_pridavat'] |= $this->jeAutor($this->template->galerie['id_autora']);

		$this->template->fotky['muze_pridavat'] = $this->user->isAllowed('fotky', 'add') || $this->jeAutor($this->template->galerie['id_autora']);

		$this->setTitle('Fotka: ' . $this->template->fotka['soubor'] . '.' . $this->template->fotka['pripona']);
	}

	public function createComponentPridaniFotekDoAlba5Form()
	{
		$form = new Nette\Application\UI\Form($this, 'pridaniFotekDoAlba5Form');

//$form->addInput('file');
	}

	public function createComponentPridaniFotekDoAlbaForm($name)
	{
		$form = new Nette\Application\UI\Form($this, $name);
		$form->getElementPrototype()->class[] = "ajax";

		$form->addMultipleFileUpload('upload', 'Uložit soubory do alba', 20)
		/* ->addRule("MultipleFileUpload::validateFilled", "Musíte odeslat alespoň jeden soubor!")
		  ->addRule("MultipleFileUpload::validateFileSize", "Soubory jsou dohromady moc veliké!",1024*1024) */;

		$form->addSubmit('save', 'Uložit soubory do alba');
		$form->onSuccess[] = array($this, 'pridaniFotekDoAlbaFormSubmitted');

		$form->addGroup('Vyberte soubory k nahrání', true);

		$form->onInvalidSubmit[] = array($this, "handlePrekresliForm");
		$form->onSuccess[] = array($this, "handlePrekresliForm");
	}

	public function pridaniFotekDoAlbaFormSubmitted(Nette\Application\UI\Form $form)
	{
		$data = $form->getValues();
		$id_galerie = (int) $this->getParam('id');

		// zpracovat fotku
		// Přesumene uploadované soubory
		foreach ($data["upload"] AS $file)
		{
			try
			{
				$fotka = $this->context->fotky;
				$fotka->id_autora = $this->user->getIdentity()->id;
				$fotka->uloz($id_galerie);
				$this->flashMessage('Soubor ' . $file->getName() . ' byl úspěšně uložen.', 'ok');
			}
			catch (Exception $e)
			{
				$this->flashMessage('Nepodařilo se uložit soubor ' . $file->getName() . '. Chyba: ' . $e->getMessage(), 'error');
				//Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
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
		$this->template->galerie = array();
		if($id != 0) $this->template->galerie = (array) $this->model->find($id)->fetch();

		if($id != 0) $this->template->galerie[$this->template->galerie['typ']]['typ_key'] = $this->template->galerie['typ_key'];

		if($id != 0) $this['editForm']->setDefaults($this->template->galerie);

		if($id == 0) $this->setTitle('Přidání galerie');
		else $this->setTitle('Úprava galerie');

		if($id != 0 && !empty($this->template->galerie['souvisejici']))
		{
			$souvisejici = $this->template->galerie['souvisejici'];
			$id_souvisejici = $this->template->galerie['id_souvisejiciho'];
		}

		switch ($souvisejici)
		{
			case 'zavody': $souvisejiciModel = $this->context->$souvisejici;
				break;
			case 'clanky': $souvisejiciModel = $this->context->$souvisejici;
				break;
			case 'terce': $souvisejiciModel = $this->context->$souvisejici;
				break;
			case 'sbory': $souvisejiciModel = $this->context->$souvisejici;
				break;
			case 'druzstva': $souvisejiciModel = $this->context->$souvisejici;
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

		$form = new Nette\Application\UI\Form($this, $name);

		$form->addGroup('Informace o galerii');
		$form->addText('nazev', 'Název galerie', 30)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit název galerie.')
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka názvu je %d znaků.', 255);
		$form->addAdminTexylaTextArea('text', 'Popis galerie', null, null, $this->getPresenter()->getName(), $id)
			   ->addRule(Form::MAX_LENGTH, 'Maximální délka textu je %d znaků.', 65535);

		$form->addGroup('Typ fotogalerie');
		$form->addSelect('typ', 'Typ galerie', array(Galerie::$TYP_INTERNI => 'integrovaná', Galerie::$TYP_RAJCE => 'Rajče.cz'))
			   ->addCondition(Form::EQUAL, Galerie::$TYP_RAJCE)->toggle(Galerie::$TYP_RAJCE, true)
			   ->addCondition(Form::EQUAL, Galerie::$TYP_INTERNI)->toggle(Galerie::$TYP_INTERNI, true);

		// Možnost zadat odkaz na galerii na rajčeti
		$form->addGroup('Galerie uložená na Rajče.cz')->setOption("container", Html::el("fieldset")->id(Galerie::$TYP_RAJCE));
		$rajceCont = $form->addContainer(Galerie::$TYP_RAJCE);
		$rajceCont->addText('typ_key', 'Odkaz na galerii')->setOption('description', 'Uveďte odkaz na galerii, např.: http://ukazka.rajce.idnes.cz/zvirata_v_ZOO');

		$form->addGroup('Integrovaná galerie')->setOption("container", Html::el("fieldset")->id(Galerie::$TYP_INTERNI));
		$nativniCont = $form->addContainer(Galerie::$TYP_INTERNI);
		$nativniCont->addTextArea('typ_key', 'Integrovaná galerie pro nahrávání fotek');

		$form->addContainer('jizSouvisejici');

		if($id == 0) $zverejneni = array('ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		else $zverejneni = array('ponechat' => 'nechat bez změny', 'ihned' => 'ihned', 'datum_zverejneni' => 'určit datum', 'ulozit' => 'pouze uložit, nezveřejňovat');
		$form->addGroup('Zveřejnění galerie');
		$form->addRadioList('zverejneni', 'Zveřejnění galerie', $zverejneni)
			   ->addRule(Form::FILLED, 'Je nutné vyplnit, kdy se má galerie zveřejnit.')
			   ->addCondition(Form::EQUAL, 'ulozit')
			   ->toggle('datum_zverejneniContainer');
		$form->addDateTimePicker('datum_zverejneni', 'Datum zveřejnění galerie')
			   ->setOption('container', Html::el('div')->id('datum_zverejneniContainer'))
			   ->addConditionOn($form['zverejneni'], Form::EQUAL, 'datum_zverejneni')
			   ->addRule(Form::FILLED, 'Je nutné vyplnit datum zveřejnění galerie.');

		if($id == 0) $form['zverejneni']->setDefaultValue('ulozit');
		else $form['zverejneni']->setDefaultValue('ponechat');

		$form->addGroup('Uložit');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít zpět');
		$form->addSubmit('cancel', 'Zpět')
			   ->setValidationScope(FALSE);

		$form->onSuccess[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(Nette\Application\UI\Form $form)
	{
		$id = (int) $this->getParam('id');

		if($form['cancel']->isSubmittedBy())
		{
			$this->redirect('galerie', $id);
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
				$this->flashMessage('Galerie byla úspěšně uložena.', 'ok');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Galerii se nepodařilo uložit.', 'error');
				Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}

			if($form['saveAndReturn']->isSubmittedBy() || $form['cancel']->isSubmittedBy()) $this->redirect('galerie', $id);
			else $this->redirect('edit', $id);
		}
	}

	public function handleSmazatFotku($id)
	{
		$fotky = $this->context->fotky;
		$fotka = $fotky->find($id)->fetch();

		$galerie = $this->model->find($fotka['id_galerie'])->fetch();

		if(!$this->user->isAllowed('fotky', 'delete') || !$this->jeAutor($fotka['id_autora']) || !$this->jeAutor($galerie['id_autora'])) throw new ForbiddenRequestException('Nemáte oprávnění na odstranění grafie.');

		try
		{
			$fotky->delete($id);
			$this->flashMessage('Fotka byla odstraněna.');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Fotku se nepodařilo odstranit.');
			Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

		$this->invalidateControl('flashes');
		$this->invalidateControl('galerie');

		$this->redirect('galerie', $fotka['id_galerie']);
	}

	public function handleDelete($id, $force = 0)
	{
		$galerie = $this->model->find($id)->fetch();
		if(!$this->user->isAllowed('galerie', 'delete') && !$this->jeAutor($galerie['id_autora'])) throw new ForbiddenRequestException('Nemáte oprávnění na odstranění galerie.');

		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Galerie byla odstraněna.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Galerii se nepodařilo odstranit.', 'error');
			Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch (RestrictionException $e)
		{
			$this->flashMessage($e->getMessage() . ' <a href="' . $this->link('delete!', array('id' => $id, 'force' => true)) . '" class="delete">Přesto smazat!</a>', 'warning');
		}

		$this->redirect('this');
	}

	public function handleTruncate($id)
	{
		$galerie = $this->model->find($id)->fetch();
		if(!$this->user->isAllowed('galerie', 'delete') && !$this->jeAutor($galerie['id_autora'])) throw new ForbiddenRequestException('Nemáte oprávnění na vyprázdnění galerie.');

		try
		{
			$this->model->truncate($id);
			$this->flashMessage('Galerie byla vyprázdněna.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Galerii se nepodařilo vyprázdnit.', 'error');
			Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

		$this->redirect('this');
	}

}
