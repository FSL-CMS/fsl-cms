<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */

/**
 * Bázový presenter
 *
 * @author	Milan Pála
 */
abstract class CommonBasePresenter extends Presenter
{

	public $oldLayoutMode = FALSE;
	public $user = NULL;
	public static $SOUVISEJICI = array('clanky' => 'články', 'zavody' => 'závody', 'terce' => 'terče', 'sbory' => 'sbory', 'druzstva' => 'družstva');
	protected $texy;
	private static $nazev;

	/**
	 * Název projektu FSL CMS
	 */
	const FSL_CMS = 'FSL CMS';

	/**
	 * Verze FSL CMS
	 */
	const FSL_CMS_VERZE = '1.0.0-rc';

	/**
	 * Odkaz na hlavní stránku FSL CMS
	 */
	const FSL_CMS_URL = 'https://github.com/FSL-CMS/fsl-cms/wiki';

	public function __construct()
	{
		self::$nazev = Environment::getVariable('name');

		// Verze databáze, kterou požaduje aplikace
		if(!defined('VERZE_DB')) define('VERZE_DB', 2);
	}

	protected function startup()
	{
		$this->user = Environment::getUser();

		FormContainer::extensionMethod('FormContainer::addRequestButton', array('RequestButtonHelper', 'addRequestButton'));
		FormContainer::extensionMethod('FormContainer::addRequestButtonBack', array('RequestButtonHelper', 'addRequestButtonBack'));

		// Provede kontrolu správné verze databáze
		$this->checkDbVersion();

		$auth = new AuthControl;
		$this->addComponent($auth, 'auth');

		$grafy = new GrafyControl;
		$this->addComponent($grafy, 'grafy');

		$hodnoceni = new HodnoceniControl;
		$this->addComponent($hodnoceni, 'hodnoceni');

		$fotka = new FotkaControl;
		$this->addComponent($fotka, 'fotka');

		$kontakty = new KontaktyControl;
		$this->addComponent($kontakty, 'kontakty');

		$souteze = new SoutezeControl;
		$this->addComponent($souteze, 'souteze');

		$poradi = new PoradiControl;
		$this->addComponent($poradi, 'poradi');

		$poll = new LinkPollControl();
		$poll->setModel(new PollControlModel());
		$this->addComponent($poll, 'anketa');

		$imageUploader = new FileUploaderControl($this, 'fileUploader');
		$this->addComponent($imageUploader, 'fileUploader');

		$prilohy = new PrilohyControl;
		$this->addComponent($prilohy, 'prilohy');

		$videa = new VideoControl;
		$this->addComponent($videa, 'video');

		$diskuze = new DiskuzeControl($this, 'diskuze');
		//$this->addComponent($diskuze, 'diskuze');

		$gal = new GalerieControl;
		$this->addComponent($gal, 'galerie');

		$souvisejici = new SouvisejiciControl($this, 'souvisejici');
		//$this->addComponent($souvisejici, 'souvisejici');

		$mapa = new MapaControl($this, 'mapa');
		//$this->addComponent($mapa, 'mapa');

		$toc = new TocControl($this, 'toc');
		$this->addComponent($toc, 'toc');

		$aktualni = new AktualniControl($this, 'aktualni');
		$this->addComponent($aktualni, 'aktualni');

		$prehledRocniku = new PrehledRocnikuControl($this, 'prehledRocniku');
		$this->addComponent($prehledRocniku, 'prehledRocniku');

		$slideshow = new SlideshowControl($this, 'slideshow');
		$this->addComponent($slideshow, 'slideshow');

		$acl = new Permission;

		$acl->addRole('guest');
		$acl->addRole('user', 'guest');
		$acl->addRole('author', 'user');
		$acl->addRole('admin', 'author');

		$acl->addResource('zavody');
		$acl->addResource('vysledky');
		$acl->addResource('clanky');
		$acl->addResource('startovni_poradi');
		$acl->addResource('komentare');
		$acl->addResource('diskuze');
		$acl->addResource('temata');
		$acl->addResource('terce');
		$acl->addResource('uzivatele');
		$acl->addResource('galerie');
		$acl->addResource('fotky');
		$acl->addResource('videa');
		$acl->addResource('stranky');
		$acl->addResource('druzstva');
		$acl->addResource('sbory');
		$acl->addResource('kategorie');
		$acl->addResource('kategorieclanku');
		$acl->addResource('sprava');
		$acl->addResource('funkcerady');
		$acl->addResource('mista');
		$acl->addResource('rocniky');
		$acl->addResource('typysboru');
		$acl->addResource('typytercu');
		$acl->addResource('okresy');
		$acl->addResource('ankety');
		$acl->addResource('soubory');
		$acl->addResource('sledovani');
		$acl->addResource('souvisejici');
		$acl->addResource('sportoviste');
		$acl->addResource('souteze');
		$acl->addResource('bodovetabulky');
		$acl->addResource('pravidla');

		$acl->allow('guest', 'uzivatele', 'add');
		$acl->allow('guest', 'sbory', 'add');
		$acl->allow('guest', 'mista', 'add');
		$acl->allow('guest', 'okresy', 'add');
		$acl->allow('guest', 'typysboru', 'add');

		$acl->allow('user', 'komentare', 'add');
		$acl->allow('user', 'diskuze', 'add');
		$acl->allow('user', 'druzstva', 'add');
		$acl->allow('user', 'sbory', 'add');
		$acl->allow('user', 'mista', 'add');
		$acl->allow('user', 'okresy', 'add');
		$acl->allow('user', 'sportoviste', 'add');
		$acl->allow('user', 'typysboru', 'add');
		$acl->allow('user', 'sledovani', 'add');

		$acl->allow('user', 'zavody', NULL, new LigaAssertion());
		$acl->allow('user', 'startovni_poradi', 'add');
		$acl->allow('user', 'startovni_poradi', NULL, new LigaAssertion());

		$acl->allow('author', 'clanky', Permission::ALL);
		$acl->allow('author', 'galerie', Permission::ALL);
		$acl->allow('author', 'fotky', Permission::ALL);
		$acl->allow('author', 'videa', Permission::ALL);
		$acl->allow('author', 'souvisejici', Permission::ALL);

		$acl->allow('admin', Permission::ALL, Permission::ALL);

		// zaregistrujeme autorizační handler
		$this->user->setAuthorizationHandler($acl);

		parent::startup();

		if($this->getPresenter()->getName() != 'Uzivatele' && $this->action != 'edit' && $this->user->isLoggedIn() && (trim($this->user->getIdentity()->getName()) == '' || intval($this->user->getIdentity()->id_sboru) == 0))
		{
			$this->flashMessage('Vyplňte údaje o sobě.', 'warning');
			$this->redirect('Uzivatele:edit', $this->user->getIdentity()->id);
		}
	}

	/**
	 * Provede kontrolu na správnou verzi databáze. Pokusí se povýšit, pokud to
	 * lze. Jinak skončí vyjímkou. Umí počáteční inicializaci prázdné databáze.
	 * @throws DBVersionMismatchException
	 * @throws DibiException
	 */
	private function checkDbVersion()
	{
		// 1. Zkontroluje se, zda je v aplikaci uvedená potřebná verze DB
		if(!defined('VERZE_DB')) throw new DBVersionMismatchException('Není uvedena verze DB v aplikaci.');
		try
		{
			// 2. Zkontroluje, zda je verze uvedená v DB
			$verzeDB = dibi::fetchSingle('SELECT [verze] FROM [verze] LIMIT 1');
			if($verzeDB === false || $verzeDB == 0)
			{
				throw new DBVersionMismatchException('Není uvedena verze DB v databázi.');
			}
			// Databáze může být aktualizována
			if( VERZE_DB > $verzeDB)
			{
				$aktualizaceDBModel = new AktualizaceDB;
				try
				{
					dibi::begin();
					$aktualizaceDBModel->aktualizuj($verzeDB, VERZE_DB);
					dibi::commit();
					$this->flashMessage('Databáze byla aktualizovaná.', 'ok');
					//$this->redirect('default');
				}
				catch(DibiException $e)
				{
					dibi::rollback();
					$this->flashMessage('Databázi se nepodařilo aktualizovat.', 'error');
					Debug::processException($e, true);
					//$this->redirect('default');
				}
			}
			// Aplikace by měla být povýšena na vyšší verzi databáze
			elseif( VERZE_DB < $verzeDB )
			{
				throw new DBVersionMismatchException('Nesouhlasí verze databází. Současná '.$verzeDB.', požadovaná '.VERZE_DB.'.');
			}

		}
		// Došlo k chybě při zjišťování verze databáze. Možná první spuštění.
		catch(DibiException $e)
		{
			// Tabulka verze neexistuje, ani ostatní tabulky neexistují => inicializace DB
			if($e->getCode() == 1146 && count(dibi::fetchAll('SHOW TABLES')) == 0)
			{
				$aktualizaceDBModel = new AktualizaceDB;
				try
				{
					dibi::begin();
					$aktualizaceDBModel->inicializuj();
					dibi::commit();
					$this->flashMessage('Databáze byla inicializovaná.', 'ok');
					//$this->redirect('default');
				}
				catch(DibiException $e)
				{
					dibi::rollback();
					$this->flashMessage('Databázi se nepodařilo inicializovat.', 'error');
					Debug::processException($e, true);
					//$this->redirect('default');
				}
			}
			else // Tabulka "verze" neexistuje, ostatní možná ano => nespecifikovaná chyba
			{
				throw $e;
			}
		}
	}

	public function createTemplate()
	{
		$template = parent::createTemplate();

		$texy = new MyTexy();
		$this->texy = $texy;
		$texy2 = new MyTexy();
		$texy3 = new NoImageTexy();

		$texy->addHandler('script', array($this, 'scriptHandler'));
		$texy->addHandler('image', array($this, 'videoHandler'));
		$texy->addHandler('image', array($this, 'imageHandler'));
		$texy->addHandler('afterTable', array($this, 'afterTable'));

		$template->registerHelper('texy', array($texy, 'process'));
		$template->registerHelper('texy2', array($texy2, 'process'));
		$template->registerHelper('noimagetexy', array($texy3, 'process'));

		$datum = new Datum();
		$template->registerHelper('datum', array($datum, 'date'));

		$template->registerHelper('zvetsPrvni', array($this, 'zvetsPrvni'));

		$template->registerHelper('vyslednyCas', array($this, 'vyslednyCas'));

		return $template;
	}

	public function formatTemplateFiles($presenter, $view)
	{
		$paths = parent::formatTemplateFiles($presenter, $view);
		foreach ($paths as $key => $path)
		{
			$paths_[] = str_replace('templates/', '../app_custom/templates/', $path);
		}
		return array_merge($paths_, $paths);
	}

	public function formatLayoutTemplateFiles($presenter, $view)
	{
		$paths = parent::formatLayoutTemplateFiles($presenter, $view);
		foreach ($paths as $key => $path)
		{
			$paths_[] = str_replace('templates/', '../app_custom/templates/', $path);
		}
		return array_merge($paths_, $paths);
	}

	public function generateToc()
	{
		$mainNode = TexyHTML::el('ul');
		$lastLevel = 1;
		$texy = $this->texy;

		foreach ($texy->headingModule->TOC as $heading)
		{
			$level = $heading['level'];
			if($level == 1)
			{  // If first header, write to main node.
				$node{$level} = $mainNode->create('li');
			}
			elseif($level > $lastLevel)
			{  // If child, make a new node
				$node{$level} = $node{$lastLevel}->create('ul')->create('li');
			}
			elseif($level <= $lastLevel)
			{  // If child, make add a node into parent
				$node{$level} = $node{$level - 1}->create('ul')->create('li');
			}
			$a = $node{$level}->create('a')->href('#' . $heading['el']->attrs['id'])->setText($heading['title']);
			$lastLevel = $level;
		}

		return $mainNode->toHtml($texy);
	}

	protected function createComponentNavigation($name)
	{
		$nav = new Navigation($this, $name);
		$datum = new Datum;

		$presenter = $this->getPresenter()->name;

		$vsechno = true;
		$sprava = true;

		$hp = $nav->setupHomepage('Úvod', $this->link('Homepage:'));
		if($presenter == 'Homepage') $nav->setCurrent($hp);

		$l1 = $nav->add('Články', $this->link('Clanky:'));
		if($presenter == 'Clanky' && $this->getAction() == 'default') $nav->setCurrent($l1);
		if($vsechno || $sprava || in_array($presenter, array('Clanky')))
		{
			$clankyModel = new Clanky;
			if($this->user->isAllowed('clanky', 'edit')) $clankyModel->zobrazitNezverejnene();
			$clanky = $clankyModel->findAll();

			if($this->user->isAllowed('clanky', 'add'))
			{
				$add = $l1->add('Nový', $this->link('Clanky:add'));
				if($presenter == 'Clanky' && $this->getAction() == 'add') $nav->setCurrent($add);
			}

			foreach ($clanky as $clanek)
			{
				$l2 = $l1->add($clanek->nazev, $this->link('Clanky:clanek', $clanek->id));
				if($presenter == 'Clanky' && $this->getParam('id') == $clanek->id) $nav->setCurrent($l2);

				if($this->user->isAllowed('clanky', 'edit'))
				{
					$ed = $l2->add('Úprava', $this->link('Clanky:edit', $clanek->id));
					if($presenter == 'Clanky' && $this->getAction() == 'edit' && $this->getParam('id') == $clanek->id) $nav->setCurrent($ed);
				}
			}
		}

		$navRocniky = $nav->add('Závody', $this->link('Rocniky:'));
		if($presenter == 'Rocniky' && $this->getAction() == 'default') $nav->setCurrent($navRocniky);

		if($vsechno || in_array($presenter, array('Zavody', 'Rocniky', 'Pravidla')))
		{
			$rocnikyModel = new Rocniky;
			$zavodyModel = new Zavody;
			$rocniky = $rocnikyModel->findAll();
			foreach ($rocniky as $rocnik)
			{
				$rocnikNode = $navRocniky->add($rocnik->rocnik . '. ročník', $this->link('Rocniky:rocnik', $rocnik->id));
				if($presenter == 'Rocniky' && $this->getAction() == 'rocnik' && $this->getParam('id') == $rocnik->id) $nav->setCurrent($rocnikNode);

				if($this->user->isAllowed('rocniky', 'edit'))
				{
					$ed = $rocnikNode->add('Upravit', $this->link('Rocniky:edit', $rocnik->id));
					if($presenter == 'Rocniky' && $this->getAction() == 'edit' && $this->getParam('id') == $rocnik->id) $nav->setCurrent($ed);
				}

				$roc = $rocnikNode->add('Bodová tabulka', $this->link('Rocniky:vysledky', $rocnik->id));
				if($presenter == 'Rocniky' && $this->getAction() == 'vysledky' && $this->getParam('id', 0) != 0)
				{
					if($this->getParam('id') == $rocnik->id) $nav->setCurrent($roc);
				}

				$roc = $rocnikNode->add('Pravidla', $this->link('Pravidla:pravidla', $this->getParam('id', 0)));
				if($presenter == 'Pravidla' && $this->getAction() == 'pravidla' && $this->getParam('id', 0) != 0) $nav->setCurrent($roc);

				$zavody = $zavodyModel->findByRocnik($rocnik->id);
				foreach ($zavody as $zavod)
				{
					$zavodNode = $rocnikNode->add($zavod->nazev . ', ' . $datum->date(substr($zavod->datum, 0, 10), 0, 0, 0), $this->link('Zavody:zavod', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'zavod' && $this->getParam('id') == $zavod->id) $nav->setCurrent($zavodNode);

					if($this->user->isAllowed('zavody', 'edit'))
					{
						$ed = $zavodNode->add('Upravit', $this->link('Zavody:edit', $zavod->id));
						if($presenter == 'Zavody' && $this->getAction() == 'edit' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);
					}

					$ed = $zavodNode->add('Výsledky', $this->link('Zavody:vysledky', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'vysledky' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);

					if($this->user->isAllowed('zavody', 'edit'))
					{
						$ed = $zavodNode->add('Přidání výsledků', $this->link('Zavody:pridatVysledky', $zavod->id));
						if($presenter == 'Zavody' && $this->getAction() == 'pridatVysledky' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);
					}

					$ed = $zavodNode->add('Informace pro komentátora', $this->link('Zavody:pripravaProKomentatora', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'pripravaProKomentatora' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Bodová tabulka před závodem', $this->link('Rocniky:vysledkyPredZavodem', $zavod->id));
					if($presenter == 'Rocniky' && $this->getAction() == 'vysledky' && $this->getParam('id_zavodu') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Startovní pořadí', $this->link('Zavody:startovniPoradi', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'startovniPoradi' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);
				}
			}
		}

		$navSbory = $nav->add('Sbory', $this->link('Sbory:'));
		if($presenter == 'Sbory' && $this->getAction() == 'default') $nav->setCurrent($navSbory);
		if($vsechno || in_array($presenter, array('Sbory', 'Druzstva', 'Uzivatele', 'Terce', 'TypySboru')))
		{
			$sboryModel = new Sbory;
			$druzstvaModel = new Druzstva;
			$uzivateleModel = new Uzivatele;
			$terceModel = new Terce;
			$sbory = $sboryModel->findAll();

			if($this->user->isAllowed('sbory', 'add'))
			{
				$node = $navSbory->add('Přidání nového', $this->link('Sbory:add'));
				if($presenter == 'Sbory' && $this->getAction() == 'add') $nav->setCurrent($node);
			}

			foreach ($sbory as $sbor)
			{
				$sborNode = $navSbory->add(mb_substr($sbor->nazev, 0, 15), $this->link('Sbory:sbor', $sbor->id));
				if($presenter == 'Sbory' && $this->getParam('id') == $sbor->id) $nav->setCurrent($sborNode);

				if($vsechno || $presenter == 'Druzstva')
				{
					$druzstva = $druzstvaModel->findBySbor($sbor->id);
					foreach ($druzstva as $druzstvo)
					{
						$node = $sborNode->add('Družstvo ' . $druzstvo->nazev, $this->link('Druzstva:druzstvo', $druzstvo->id));
						if($presenter == 'Druzstva' && $this->getAction() == 'druzstvo' && $this->getParam('id') == $druzstvo->id) $nav->setCurrent($node);

						if($this->user->isAllowed('druzstva', 'edit'))
						{
							$ed = $node->add('Úprava', $this->link('Druzstva:edit', $druzstvo->id));
							if($presenter == 'Druzstva' && $this->getAction() == 'edit' && $this->getParam('id') == $druzstvo->id) $nav->setCurrent($ed);
						}
					}
				}

				if($vsechno || $presenter == 'Uzivatele')
				{
					$uzivatele = $uzivateleModel->findBySbor($sbor->id);
					foreach ($uzivatele as $uzivatel)
					{
						$node = $sborNode->add('Uživatel ' . $uzivatel->jmeno . ' ' . $uzivatel->prijmeni, $this->link('Uzivatele:uzivatel', $uzivatel->id));
						if($presenter == 'Uzivatele' && $this->getAction() == 'uzivatel' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($node);

						if($this->user->isAllowed('uzivatele', 'edit'))
						{
							$ed = $node->add('Úprava', $this->link('Uzivatele:edit', $uzivatel->id));
							if($presenter == 'Uzivatele' && $this->getAction() == 'edit' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($ed);
						}
					}
				}

				if($vsechno || $presenter == 'Terce')
				{
					$terce = $terceModel->findBySbor($sbor->id);
					foreach ($terce as $terc)
					{
						$node = $sborNode->add($this->zvetsPrvni($terc->typ) . ' terče', $this->link('Terce:terce', $terc->id));
						if($presenter == 'Terce' && $this->getAction() == 'terce' && $this->getParam('id') == $terc->id) $nav->setCurrent($node);

						if($this->user->isAllowed('terce', 'edit'))
						{
							$ed = $node->add('Úprava', $this->link('Terce:edit', $terc->id));
							if($presenter == 'Terce' && $this->getAction() == 'edit' && $this->getParam('id') == $terc->id) $nav->setCurrent($ed);
						}
					}
				}
			}

			if($presenter == 'Uzivatele' && $this->getAction() == 'add') $node = $nav->add('Přidání nového uživatele', $this->link('Uzivatele:add'));
			if($presenter == 'Uzivatele' && $this->getAction() == 'add') $nav->setCurrent($node);

			$node = $nav->add('Uživatelé', $this->link('Uzivatele:default'));
			if($presenter == 'Uzivatele' && $this->getAction() == 'default') $nav->setCurrent($node);

			$nod20 = $nav->add('Typ sborů', $this->link('TypySboru:'));
			if($presenter == 'TypySboru' && $this->getAction() == 'default') $nav->setCurrent($nod20);

			$nod21 = $nod20->add('Nový', $this->link('TypySboru:add'));
			if($presenter == 'TypySboru' && $this->getAction() == 'add') $nav->setCurrent($nod21);

			$nod20 = $nav->add('Místa', $this->link('Mista:'));
			if($presenter == 'Mista' && $this->getAction() == 'default') $nav->setCurrent($nod20);

			$nod21 = $nod20->add('Nové', $this->link('Mista:add'));
			if($presenter == 'Mista' && $this->getAction() == 'add') $nav->setCurrent($nod21);
		}

		$navForum = $nav->add('Fórum', $this->link('Forum:'));
		if($presenter == 'Forum' && $this->getAction() == 'default') $nav->setCurrent($navForum);
		if($vsechno || in_array($presenter, array('Forum', 'Diskuze')))
		{
			$temataModel = new Temata;
			$diskuzeModel = new Diskuze;
			$temata = $temataModel->findAll();

			foreach ($temata as $tema)
			{
				$roc = $navForum->add($tema->nazev, $this->link('Forum:forum', $tema->id));
				if($presenter == 'Forum' && $this->getParam('id') == $tema->id) $nav->setCurrent($roc);

				$node = $roc->add('Zeptat se', $this->link('Diskuze:zeptatse', array('id' => $tema->id, 'id_souvisejiciho' => $this->getParam('id_souvisejiciho', NULL))));
				if($presenter == 'Diskuze' && $this->getAction() == 'zeptatse' && $this->getParam('id') == $tema->id) $nav->setCurrent($node);

				$diskuze = $diskuzeModel->findByTema($tema->id);
				foreach ($diskuze as $disk)
				{
					$node = $roc->add($disk->tema_diskuze, $this->link('Diskuze:diskuze', $disk->id_diskuze));
					if($presenter == 'Diskuze' && $this->getAction() == 'diskuze' && $this->getParam('id') == $disk->id_diskuze) $nav->setCurrent($node);
				}
			}
		}

		// Stránky generovat vždy
		if(1 || in_array($presenter, array('Stranky')))
		{
			$strankyModel = new Stranky;
			$stranky = $strankyModel->findAll();
			foreach ($stranky as $stranka)
			{
				$roc = $nav->add($stranka->nazev, $this->link('Stranky:stranka', $stranka->id));
				if($presenter == 'Stranky' && $this->getParam('id') == $stranka->id) $nav->setCurrent($roc);
			}
		}

		$navGalerie = $nav->add('Galerie', $this->link('Galerie:'));
		if($presenter == 'Galerie' && $this->getAction() == 'default') $nav->setCurrent($navGalerie);

		if($vsechno || in_array($presenter, array('Galerie')))
		{
			$galerieModel = new Galerie;
			if($this->user->isAllowed('galerie', 'edit')) $galerieModel->zobrazitNezverejnene();
			$galerie = $galerieModel->findAll();
			foreach ($galerie as $fotogal)
			{
				$roc = $navGalerie->add($fotogal->nazev, $this->link('Galerie:galerie', $fotogal->id));
				if($presenter == 'Galerie' && $this->getAction() == 'galerie' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($roc);

				if($this->user->isAllowed('galerie', 'edit'))
				{
					$ed = $roc->add('Upravit', $this->link('Galerie:edit', $fotogal->id));
					if($presenter == 'Galerie' && $this->getAction() == 'edit' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($ed);

					$ed = $roc->add('Přidat fotky', $this->link('Galerie:pridatFotky', $fotogal->id));
					if($presenter == 'Galerie' && $this->getAction() == 'pridatFotky' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($ed);

					$ed = $roc->add('Přidat videa', $this->link('Galerie:pridatVidea', $fotogal->id));
					if($presenter == 'Galerie' && $this->getAction() == 'pridatVidea' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($ed);
				}
			}
		}

		$nav0 = $nav->add('Statistiky', $this->presenter->link('Statistiky:default'));
		if($presenter == 'Statistiky' && $this->getAction() == 'default') $nav->setCurrent($nav0);

		if($vsechno || in_array($presenter, array('Statistiky')))
		{
			$nav1 = $nav0->add('Průměrné časy sezón', $this->link('Statistiky:prumerneCasy'));
			if($this->getAction() == 'prumerneCasy') $nav->setCurrent($nav1);

			$nav2 = $nav0->add('Nejlépe bodovaná družstva', $this->link('Statistiky:nejlepeBodovanaDruzstva'));
			if($this->getAction() == 'nejlepeBodovanaDruzstva') $nav->setCurrent($nav2);

			$nav3 = $nav0->add('Nejrychlejší dráhy', $this->link('Statistiky:nejrychlejsiDrahy'));
			if($this->getAction() == 'nejrychlejsiDrahy') $nav->setCurrent($nav3);

			$nav4 = $nav0->add('Nejrychlejší časy', $this->link('Statistiky:nejrychlejsiCasy'));
			if($this->getAction() == 'nejrychlejsiCasy') $nav->setCurrent($nav4);

			$nav5 = $nav0->add('Vítězové ročníků', $this->link('Statistiky:vitezoveRocniku'));
			if($this->getAction() == 'vitezoveRocniku') $nav->setCurrent($nav5);

			$nav6 = $nav0->add('Počty pořádaných závodů', $this->link('Statistiky:poradaneZavody'));
			if($this->getAction() == 'poradaneZavody') $nav->setCurrent($nav6);
		}

		if($vsechno || in_array($presenter, array('Sprava', 'Stranky', 'Rocniky', 'Zavody', 'Kategorie', 'BodoveTabulky', 'Uzivatele', 'Sledovani', 'Mista', 'Okresy', 'Souteze', 'Druzstva', 'Ankety', 'TypySboru')))
		{
			if($this->user->isAllowed('sprava', 'edit'))
			{
				$sprava = $nav->add('Správa', $this->link('Sprava:'));
				if($presenter == 'Sprava' && $this->getAction() == 'default') $nav->setCurrent($sprava);

				$nod0 = $sprava->add('Kotrola údajů', $this->link('Sprava:kontrola'));
				if($presenter == 'Sprava' && $this->getAction() == 'kontrola') $nav->setCurrent($nod0);

				if($this->user->isAllowed('stranky', 'edit'))
				{
					$nod0 = $sprava->add('Správa stránek', $this->link('Stranky:'));
					if($presenter == 'Stranky' && $this->getAction() == 'default') $nav->setCurrent($nod0);

					$nod1 = $nod0->add('Nová', $this->link('Stranky:add'));
					if($presenter == 'Stranky' && $this->getAction() == 'add') $nav->setCurrent($nod1);

					/* $nod2 = $sprava->add('Úprava', $this->link('Stranky:add'));
					  if( $presenter == 'Stranky' && $this->getAction() == 'add' ) $nav->setCurrent($nod1); */
				}

				if($this->user->isAllowed('rocniky', 'edit'))
				{
					$nod3 = $sprava->add('Ročníky', $this->link('Rocniky:'));
					if($presenter == 'Rocniky' && $this->getAction() == 'default') $nav->setCurrent($nod3);

					$nod4 = $sprava->add('Přidání ročníku', $this->link('Rocniky:add'));
					if($presenter == 'Rocniky' && $this->getAction() == 'add') $nav->setCurrent($nod4);
				}

				if($this->user->isAllowed('zavody', 'edit'))
				{
					$nod6 = $sprava->add('Závody', $this->link('Zavody:'));

					$nod5 = $nod6->add('Přidání závodu', $this->link('Zavody:add'));
					if($presenter == 'Zavody' && $this->getAction() == 'add') $nav->setCurrent($nod5);

					$nod7 = $sprava->add('Sportovní kategorie', $this->link('Kategorie:'));
					if($presenter == 'Kategorie' && $this->getAction() == 'default') $nav->setCurrent($nod7);

					$nod8 = $nod7->add('Nová', $this->link('Kategorie:add'));
					if($presenter == 'Kategorie' && $this->getAction() == 'add') $nav->setCurrent($nod8);

					if($presenter == 'Kategorie' && $this->getAction() == 'edit' && $this->getParam('id', 0) != 0)
					{
						$nod8 = $nod7->add('Úprava', $this->link('Kategorie:edit', $this->getParam('id')));
						if($presenter == 'Kategorie' && $this->getAction() == 'edit') $nav->setCurrent($nod8);
					}

					$nod9 = $sprava->add('Bodové tabulky', $this->link('BodoveTabulky:'));
					if($presenter == 'BodoveTabulky' && $this->getAction() == 'default') $nav->setCurrent($nod9);

					$nod10 = $nod9->add('Přidání bodové tabulky', $this->link('BodoveTabulky:add'));
					if($presenter == 'BodoveTabulky' && $this->getAction() == 'add') $nav->setCurrent($nod10);
				}

				if($this->user->isAllowed('uzivatele', 'edit'))
				{
					$nod11 = $sprava->add('Uživatelé', $this->link('Uzivatele:default'));
					if($presenter == 'Uzivatele' && $this->getAction() == 'default') $nav->setCurrent($nod11);

					$uzivateleModel = new Uzivatele();

					$uzivatele = $uzivateleModel->findAll();
					foreach ($uzivatele as $uzivatel)
					{
						$nod12 = $nod11->add('Uživatel ' . $uzivatel->jmeno . ' ' . $uzivatel->prijmeni, $this->link('Uzivatele:uzivatel', $uzivatel->id));
						if($presenter == 'Uzivatele' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($nod12);
					}

					$nod13 = $nod12->add('Úprava', $this->link('Uzivatele:edit', array('id' => $this->getParam('id'))));
					if($presenter == 'Uzivatele' && $this->getAction() == 'edit' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($nod12);
				}

				$nod15 = $sprava->add('Místa', $this->link('Mista:'));
				if($presenter == 'Mista' && $this->getAction() == 'default') $nav->setCurrent($nod15);

				$nod15_ = $nod15->add('Přidat nové', $this->link('Mista:add'));
				if($presenter == 'Mista' && $this->getAction() == 'add') $nav->setCurrent($nod15_);

				$nod16 = $sprava->add('Okresy', $this->link('Okresy:'));
				if($presenter == 'Okresy' && $this->getAction() == 'default') $nav->setCurrent($nod16);

				$nod17 = $nod16->add('Přidat nový', $this->link('Okresy:add'));
				if($presenter == 'Okresy' && $this->getAction() == 'add') $nav->setCurrent($nod17);

				if($presenter == 'Okresy' && $this->getParam('id', 0) != 0)
				{
					$okresyModel = new Okresy;
					$okres = $okresyModel->find($this->getParam('id'))->fetch();
					$okresNode = $nod16->add($okres['nazev'], $this->link('Okresy:edit', $okres['id']));
					$nav->setCurrent($okresNode);
				}

				$nod18 = $sprava->add('Sportovní soutěže', $this->link('Souteze:'));
				if($presenter == 'Souteze' && $this->getAction() == 'default') $nav->setCurrent($nod18);

				$nod19 = $nod18->add('Přidat novou', $this->link('Souteze:add'));
				if($presenter == 'Souteze' && $this->getAction() == 'add') $nav->setCurrent($nod19);

				if($presenter == 'Souteze' && $this->getParam('id', 0) != 0)
				{
					$soutezeModel = new Souteze;
					$souteze = $soutezeModel->findAll();
					foreach ($souteze as $soutez)
					{
						$soutezNode = $nod18->add($soutez['nazev'], $this->link('Souteze:soutez', $soutez['id']));
						if($this->getAction() == 'soutez' && $this->getParam('id') == $soutez['id']) $nav->setCurrent($soutezNode);

						$ed = $soutezNode->add('Úprava', $this->link('Souteze:edit', $soutez['id']));
						if($this->getAction() == 'edit' && $this->getParam('id') == $soutez['id']) $nav->setCurrent($ed);
					}
				}

				$nod20 = $sprava->add('Družstva', $this->link('Druzstva:'));
				if($presenter == 'Druzstva' && $this->getAction() == 'default') $nav->setCurrent($nod20);

				$nod21 = $nod20->add('Přidat nové', $this->link('Druzstva:add'));
				if($presenter == 'Druzstva' && $this->getAction() == 'add') $nav->setCurrent($nod21);

				$nod20 = $sprava->add('Ankety', $this->link('Ankety:'));
				if($presenter == 'Ankety' && $this->getAction() == 'default') $nav->setCurrent($nod20);

				$nod21 = $nod20->add('Nová', $this->link('Ankety:add'));
				if($presenter == 'Ankety' && $this->getAction() == 'add') $nav->setCurrent($nod21);

				$nod21 = $nod20->add('Úprava', $this->link('Ankety:edit', $this->getParam('id', NULL)));
				if($presenter == 'Ankety' && $this->getAction() == 'edit') $nav->setCurrent($nod21);

				$nod20 = $sprava->add('Typ sborů', $this->link('TypySboru:'));
				if($presenter == 'TypySboru' && $this->getAction() == 'default') $nav->setCurrent($nod20);

				$nod21 = $nod20->add('Nový', $this->link('TypySboru:add'));
				if($presenter == 'TypySboru' && $this->getAction() == 'add') $nav->setCurrent($nod21);

				$nod21 = $nod20->add('Úprava', $this->link('TypySboru:edit', $this->getParam('id', NULL)));
				if($presenter == 'TypySboru' && $this->getAction() == 'edit') $nav->setCurrent($nod21);
			}
		}
	}

	/**
	 * Nastaví do šablony název stránky, jak pro "title", tak pro "h1".
	 * Pokud není název zadán, vygeneruje se pouze název webu.
	 * @param string $title Název stránky
	 */
	public function setTitle($title = NULL)
	{
		if($title === NULL) $this->template->title = self::$nazev;
		else $this->template->title = self::$nazev . ' >> ' . $title;

		$this->template->nadpis = $title !== NULL ? trim($title) : '';
	}

	public function beforeRender()
	{
		$this->setTitle();

		// odhlášení z dlouhé neaktivity
		if($this->user->getLogoutReason() === User::INACTIVITY)
		{
			$this->flashMessage('Byl jste odhlášen z důvodu dlouhé neaktivity. Přihlašte se znovu.', 'warning');
		}

		JsonDependentSelectBox::tryJsonResponse();

		$this->renderMenu();

		// nastavení identity do šablony
		$this->template->user = $this->user->isLoggedIn() ? $this->user->getIdentity() : NULL;

		$this->template->backlink = $this->getApplication()->storeRequest();

		$this->template->isProduction = Environment::isProduction();

		$this->template->aktualniRok = date('Y');

		$this->template->FSL_CMS = self::FSL_CMS;
		$this->template->FSL_CMS_URL = self::FSL_CMS_URL;
		$this->template->FSL_CMS_VERZE = self::FSL_CMS_VERZE;
	}

	abstract protected function renderMenu();

	protected function createComponentLoginForm()
	{
		$sprava = new SpravaPresenter;
		return $sprava->createComponentLoginForm();
	}

	public function zvetsPrvni($ceho)
	{
		return mb_strtoupper(mb_substr($ceho, 0, 1)) . mb_substr($ceho, 1);
	}

	public function jeAutor($id_autora)
	{
		return $this->user->getIdentity() !== NULL && $id_autora !== NULL && (int) $this->user->getIdentity()->id == (int) $id_autora;
	}

	public function flashMessage($message, $typ = 'ok')
	{
		if($typ == 'error') $message .= ' Podrobnosti o chybě byly uloženy a chyba byla oznámena správci stránek.';
		parent::flashMessage($message, $typ);
		$this->invalidateControl('flashes');
	}

	public function boxFlashMessage($message, $typ = 'ok')
	{
		if($typ == 'error') $message .= ' Podrobnosti o chybě byly uloženy a chyba byla oznámena správci stránek.';
		parent::flashMessage($message, $typ);
		$this->invalidateControl('boxFlashes');
	}

	function scriptHandler($invocation, $cmd, $args, $raw)
	{
		switch ($cmd)
		{
			case 'kontakty': // převede značku {{kontakty}} na widget
				ob_start();
				$this['kontakty']->render();
				$vystup = ob_get_clean();
				return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
			case 'prubezne-poradi': // převede značku {{prubezne-poradi}} na widget
				ob_start();
				if(!empty($args)) $this['poradi']->render($args[0]);
				else $this['poradi']->render();
				$vystup = ob_get_clean();
				return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
			case 'mapa-stranek': // převede značku {{mapa-stranek}} na widget
				ob_start();
				if(!empty($args)) $this['navigation']->renderSitemap($args[0]);
				else $this['navigation']->renderSitemap();
				$vystup = ob_get_clean();
				return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
			default: // neumime zpracovat, zavolame dalsi handler v rade
				return $invocation->proceed();
		}
	}

	/**
	 * User handler for images
	 *
	 * @param TexyHandlerInvocation  handler invocation
	 * @param TexyImage
	 * @param TexyLink
	 * @return TexyHtml|string|FALSE
	 */
	public function videoHandler($invocation, $image, $link)
	{
		$parts = explode(':', $image->URL);

		if(count($parts) !== 2) return $invocation->proceed();
		$key = $parts[1];

		switch ($parts[0])
		{
			case 'youtube':
				ob_start();
				$this['video']->renderYoutube($image->width, $image->height, $key);
				$vystup = ob_get_clean();
				return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
				break;

			case 'stream':
				ob_start();
				$this['video']->renderStream($image->width, $image->height, $key);
				$vystup = ob_get_clean();
				return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
				break;

			case 'facebook':
				ob_start();
				$this['facebook']->renderFacebook($image->width, $image->height, $key);
				$vystup = ob_get_clean();
				return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
				break;

			default:
				return $invocation->proceed();
				break;
		}
	}

	/**
	 * User handler for images
	 *
	 * @param TexyHandlerInvocation  handler invocation
	 * @param TexyImage
	 * @param TexyLink
	 * @return TexyHtml|string|FALSE
	 */
	public function imageHandler($invocation, $image, $link)
	{
		if(preg_match('/^[0-9]+$/', $image->URL))
		{ // tvar [* id_obrázku *]
			ob_start();
			$this['fotka']->renderNahled($image->URL);
			$vystup = ob_get_clean();
			return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
		}
		elseif(preg_match('/^velka:[0-9]+$/', $image->URL))
		{ // tvar [* velka:id_obrázku *]
			$id = substr($image->URL, 6);
			ob_start();
			$this['fotka']->renderVelky($id);
			$vystup = ob_get_clean();
			return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
		}
		elseif(preg_match('/^([0-9]+,)*[0-9]+$/', $image->URL))
		{
			$fotky = explode(',', $image->URL);
			$vystup = '<div class="fotky-v-textu">';
			if(is_array($fotky)) foreach ($fotky as $fotka)
				{
					ob_start();
					$this['fotka']->renderNahled($fotka);
					$vystup .= ob_get_clean();
				}
			$vystup .= '<span class="cleaner"></span></div>';
			return $invocation->getTexy()->protect($vystup, Texy::CONTENT_BLOCK);
		}
		return $invocation->proceed($image, $link);
	}

	/**
	 * Nastaví třídu tabulkám převedeným pomocí texy
	 * @param type $parser
	 * @param type $element
	 * @param type $modifier
	 */
	public function afterTable($parser, $element, $modifier)
	{
		// $element je TexyHtml element se stromem
		$element->attrs['class'][] = 'tabulka-v-textu';
	}

	public function actionAdd()
	{
		$backlink = $this->getApplication()->storeRequest();

		if(!$this->user->isAllowed(strtolower(preg_replace('/Presenter/', '', $this->reflection->name)), $this->getAction())) throw new ForbiddenRequestException();
	}

	public function actionEdit($id = 0)
	{
		if($id == 0) $this->redirect('add');

		if(!$this->model->find($id)->fetch()) throw new BadRequestException();

		$backlink = $this->getApplication()->storeRequest();
		if(($this->user === NULL || !$this->user->isLoggedIn())) $this->forward('Sprava:login', $backlink);

		if(!$this->user->isAllowed(strtolower(preg_replace('/Presenter/', '', $this->reflection->name)), $this->getAction()) && ($this->getAction() == 'edit' && !$this->jeAutor($this->getParam('id')) && $this->getPresenter()->getName() == 'Uzivatele')) throw new ForbiddenRequestException();
	}

	public function handleLoginByFacebook()
	{
		$uzivatele = new Uzivatele;

		$apiKey = '755802d99e8b08b04bea91d60a5f235e';
		$apiSecret = 'e3a689dd77880081fb9b4380da243f0f';

		include_once LIBS_DIR . "/facebook-platform/php/facebook.php";

		$fb = new Facebook($apiKey, $apiSecret);
		$fbUserId = $fb->user;

		if($this->user->isLoggedIn())
		{
			$this->redirect('Uzivatele:edit', array('id' => $this->user->getIdentity()->id, 'facebook' => true));
		}
		else
		{
			try
			{
				$this->prihlas(array('facebookId' => $fbUserId));
				$this->redirect('this');
			}
			catch (AuthenticationException $e)
			{
				// nepodařilo se přihlásit, vytvoříme uživatele
				if($e->getCode() == Uzivatele::IDENTITY_NOT_FOUND) $this->redirect('Uzivatele:add', array('facebook' => true));
				else $this->flashMessage($e->getMessage(), 'error');
			}
		}
	}

	public function prihlas($udaje)
	{
		$user = Environment::getUser();
		if(isset($udaje['login']) && isset($udaje['heslo']))
		{
			// zaregistrujeme autentizační handler
			$user->setAuthenticationHandler(new Uzivatele);

			$user->login($udaje['login'], $udaje['heslo']);

			// nastavíme expiraci
			$user->setExpiration('+3 hours', true, true);

			setCookie('user', $udaje['login'], time() + 365 * 24 * 3600, '/');
		}
		elseif(isset($udaje['facebookId']))
		{
			// zaregistrujeme autentizační handler
			$user->setAuthenticationHandler(new FacebookUzivatele);

			$user->login($udaje['facebookId'], '');

			// nastavíme expiraci
			$user->setExpiration('+3 hours', true, true);
		}
		else
		{
			throw new Exception('Špatné údaje pro přihlášení.');
		}
	}

	public static function float($cislo)
	{
		if(strpos($cislo, ',') !== false) $cislo = preg_replace('~,~', '.', $cislo);

		return (float) $cislo;
	}

	/**
	 * Porovnávací funkce pro celkové bodování ligy
	 * Porovnává lepší umístění v průběhu sezóny
	 * @param array $a porovnávané družstvo
	 * @param array $b porovnávané družstvo
	 * @return int -1, pokud má první víc bodů, 0, pokud stejně a 1 pokud má druhý víc bodů
	 */
	public function orderVysledky(&$a, &$b)
	{
		if($a['celkem_bodu'] > $b['celkem_bodu']) return -1;
		elseif($a['celkem_bodu'] < $b['celkem_bodu']) return 1;
		else
		{
			// obě družstva mají stejně bodů, rozhodne se podle počtu lepších umístění v sezóně
			// kdo má víc lepších pozic, vyhrává
			$vysledky = new Vysledky;
			$umisteni = $vysledky->porovnejDruzstva($a, $b)->fetchAll();
			//Debug::dump($umisteni);

			$a_umisteni = array();
			$b_umisteni = array();

			$nejhorsiUmisteni = 0;
			foreach ($umisteni as $misto)
			{
				$misto->a_umisteni = intval($misto->a_umisteni);
				$misto->b_umisteni = intval($misto->b_umisteni);

				if($nejhorsiUmisteni < $misto->a_umisteni) $nejhorsiUmisteni = $misto->a_umisteni;
				if($nejhorsiUmisteni < $misto->b_umisteni) $nejhorsiUmisteni = $misto->b_umisteni;

				if(!isset($a_umisteni[$misto->a_umisteni])) $a_umisteni[$misto->a_umisteni] = 1;
				else $a_umisteni[$misto->a_umisteni]++;

				if(!isset($b_umisteni[$misto->b_umisteni])) $b_umisteni[$misto->b_umisteni] = 1;
				else $b_umisteni[$misto->b_umisteni]++;
			}
			$prubeh_umisteni_a = array();
			$prubeh_umisteni_b = array();
			//print_r($a_umisteni);
			for ($i = 1; $i < ($nejhorsiUmisteni + 1); $i++)
			{
				if(isset($a_umisteni[$i])) for ($j = 0; $j < $a_umisteni[$i]; $j++)
						$prubeh_umisteni_a[] = $i;

				if(isset($b_umisteni[$i])) for ($j = 0; $j < $b_umisteni[$i]; $j++)
						$prubeh_umisteni_b[] = $i;
			}
			$a['prubeh'] = $prubeh_umisteni_a;
			$b['prubeh'] = $prubeh_umisteni_b;
			$b_temp = $b;
			unset($b_temp['shoda']);
			unset($b_temp['lepsi']);
			unset($b_temp['horsi']);
			$a_temp = $a;
			unset($a_temp['shoda']);
			unset($a_temp['lepsi']);
			unset($a_temp['horsi']);
			for ($i = 1; $i < ($nejhorsiUmisteni + 1); $i++)
			{
				if(!isset($a_umisteni[$i])) $a_umisteni[$i] = 0;
				if(!isset($b_umisteni[$i])) $b_umisteni[$i] = 0;

				if($a_umisteni[$i] > $b_umisteni[$i])
				{
					$b_temp['pocet'] = $a_umisteni[$i];
					$b_temp['rozhodujici'] = $i;
					$a_temp['rozhodujici'] = $i;
					$a_temp['pocet'] = $b_umisteni[$i];
					$a['lepsi'][] = $b_temp;
					$b['horsi'][] = $a_temp;
					return -1;
				}
				if($a_umisteni[$i] < $b_umisteni[$i])
				{
					$a_temp['pocet'] = $b_umisteni[$i];
					$b_temp['rozhodujici'] = $i;
					$a_temp['rozhodujici'] = $i;
					$b_temp['pocet'] = $a_umisteni[$i];
					$b['lepsi'][] = $a_temp;
					$a['horsi'][] = $b_temp;
					return 1;
				}
			}
			$a['shoda'][] = $b_temp;
			$b['shoda'][] = $a_temp;
			return strcmp($a['druzstvo'], $b['druzstvo']);
		}
	}

	public function orderVysledkyReverse($a, $b)
	{
		return self::orderVysledky($a, $b) * -1;
	}

	public function vyslednyCas($cas)
	{
		if(strpos($cas, '1000') !== false) return 'NP';
		else return $cas;
	}

}
