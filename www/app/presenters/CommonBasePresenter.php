<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Diagnostics\Debugger;
use Nette\Application\BadRequestException;

/**
 * Bázový presenter
 *
 * @author	Milan Pála
 */
abstract class CommonBasePresenter extends Nette\Application\UI\Presenter
{

	protected $user = NULL;
	public static $SOUVISEJICI = array('clanky' => 'články', 'zavody' => 'závody', 'terce' => 'terče', 'sbory' => 'sbory', 'druzstva' => 'družstva');
	protected $texy;

	/**
	 * Informace o dané lize
	 * - název
	 * - zkratka
	 * - krátký popis
	 * @var array
	 */
	protected static $liga;

	/**
	 * Název projektu FSL CMS
	 */

	const FSL_CMS = 'FSL CMS';

	/**
	 * Verze FSL CMS
	 */
	const FSL_CMS_VERZE = '1.1.0-dev';

	/**
	 * Odkaz na hlavní stránku FSL CMS
	 */
	const FSL_CMS_URL = 'https://github.com/FSL-CMS/fsl-cms/wiki';

	/** @var int Verze DB, její hodnota je uložená v databázi */
	private static $verzeDB = false;

	public function __construct()
	{
		// Verze databáze, kterou požaduje aplikace
		if(!defined('VERZE_DB')) define('VERZE_DB', 8);
	}

	protected function startup()
	{
		// Získání aktuálního nastavení pro kontrétní ligu
		$this->loadConfiguration();

		// Provede kontrolu správné verze databáze
		$this->checkDbVersion();

		$this->user = $this->getUser();

		Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addRequestButton', array('RequestButtonHelper', 'addRequestButton'));
		Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addRequestButtonBack', array('RequestButtonHelper', 'addRequestButtonBack'));

		RequestButtonStorage::setSession($this->getSession('RequestButtonStorage'));

		// DependentSelectBox
		\DependentSelectBox\DependentSelectBox::register('addDependentSelectBox');
		\DependentSelectBox\JsonDependentSelectBox::register('addJsonDependentSelectBox');

		Debugger::addPanel(new IncludePanel);

		$auth = new AuthControl;
		$this->addComponent($auth, 'auth');

		$grafy = new GrafyControl;
		$this->addComponent($grafy, 'grafy');

		$hodnoceni = new HodnoceniControl;
		$this->addComponent($hodnoceni, 'hodnoceni');

		$fotkaControl = new FotkaControl;
		$this->addComponent($fotkaControl, 'fotka');

		$kontakty = new KontaktyControl;
		$this->addComponent($kontakty, 'kontakty');

		$souteze = new SoutezeControl;
		$this->addComponent($souteze, 'souteze');

		$poradi = new PoradiControl;
		$this->addComponent($poradi, 'poradi');

		$pollie = new OndrejBrejla\Pollie\PollieLink();
		$pollie->setModel($this->context->pollie);
		$this->addComponent($pollie, 'anketa');

		$imageUploader = new FileUploaderControl;
		$this->addComponent($imageUploader, 'fileUploader');

		$prilohy = new PrilohyControl;
		$this->addComponent($prilohy, 'prilohy');

		$videa = new VideoControl;
		$this->addComponent($videa, 'video');

		$diskuze = new DiskuzeControl;
		$this->addComponent($diskuze, 'diskuze');

		$gal = new GalerieControl;
		$this->addComponent($gal, 'galerie');

		$souvisejici = new SouvisejiciControl;
		$this->addComponent($souvisejici, 'souvisejici');

		$mapa = new MapaControl;
		$this->addComponent($mapa, 'mapa');

		$toc = new TocControl;
		$this->addComponent($toc, 'toc');

		$aktualni = new AktualniControl;
		$this->addComponent($aktualni, 'aktualni');

		$prehledRocniku = new PrehledRocnikuControl;
		$this->addComponent($prehledRocniku, 'prehledRocniku');

		$slideshow = new SlideshowControl;
		$this->addComponent($slideshow, 'slideshow');

		$sablonyclanku = new SablonyClankuControl;
		$this->addComponent($sablonyclanku, 'sablonyclanku');

		$acl = new Nette\Security\Permission;

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
		$acl->addResource('sablonyclanku');
		$acl->addResource('nastaveni');

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

		$acl->allow('user', 'zavody', NULL, array($this, 'assertion'));
		$acl->allow('user', 'startovni_poradi', 'add');
		$acl->allow('user', 'startovni_poradi', NULL, array($this, 'assertion'));

		$acl->allow('author', 'clanky', Nette\Security\Permission::ALL);
		$acl->allow('author', 'galerie', Nette\Security\Permission::ALL);
		$acl->allow('author', 'fotky', Nette\Security\Permission::ALL);
		$acl->allow('author', 'videa', Nette\Security\Permission::ALL);
		$acl->allow('author', 'souvisejici', Nette\Security\Permission::ALL);

		$acl->allow('admin', Nette\Security\Permission::ALL, Nette\Security\Permission::ALL);

		// zaregistrujeme autorizační handler
		$this->user->setAuthorizator($acl);

		parent::startup();

		/* if(!($this->getPresenter() instanceOf UzivatelePresenter) && $this->action != 'edit' && $this->getUser()->isLoggedIn() && (empty($this->getUser()->getIdentity()->name)))
		  {
		  $this->flashMessage('Vyplňte údaje o sobě.', 'warning');
		  $this->redirect('Uzivatele:edit', $this->user->getIdentity()->id);
		  } */
	}

	/**
	 * Načte konfiguraci z DB specifickou pro dannou ligu
	 */
	private function loadConfiguration()
	{
		$config = $this->context->nastaveni->find()->fetch();

		self::$liga['nazev'] = $config->liga_nazev;
		self::$liga['zkratka'] = $config->liga_zkratka;
		self::$liga['popis'] = $config->liga_popis;

		if(empty(self::$liga['popis'])) self::$liga['popis'] = self::$liga['nazev'];

		self::$verzeDB = $config->verze;
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
			if(self::$verzeDB === false || self::$verzeDB == 0)
			{
				throw new DBVersionMismatchException('Není uvedena verze DB v databázi.');
			}
			// Databáze může být aktualizována
			if(VERZE_DB > self::$verzeDB)
			{
				$aktualizaceDBModel = $this->context->aktualizaceDB;
				try
				{
					$aktualizaceDBModel->getConnection()->begin();
					$aktualizaceDBModel->aktualizuj(self::$verzeDB, VERZE_DB);
					$aktualizaceDBModel->getConnection()->commit();
					$this->flashMessage('Databáze byla aktualizovaná.', 'ok');
					//$this->redirect('default');
				}
				catch (DibiException $e)
				{
					$aktualizaceDBModel->getConnection()->rollback();
					$this->flashMessage('Databázi se nepodařilo aktualizovat.', 'error');
					Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
					//$this->redirect('default');
				}
			}
			// Aplikace by měla být povýšena na vyšší verzi databáze
			elseif(VERZE_DB < self::$verzeDB)
			{
				throw new DBVersionMismatchException('Nesouhlasí verze databází. Současná ' . self::$verzeDB . ', požadovaná ' . VERZE_DB . '.');
			}
		}
		// Došlo k chybě při zjišťování verze databáze. Možná první spuštění.
		catch (DibiException $e)
		{
			// Tabulka verze neexistuje, ani ostatní tabulky neexistují => inicializace DB
			if($e->getCode() == 1146 && count(dibi::fetchAll('SHOW TABLES')) == 0)
			{
				$aktualizaceDBModel = $this->context->aktualizaceDB;
				try
				{
					dibi::begin();
					$aktualizaceDBModel->inicializuj();
					dibi::commit();
					$this->flashMessage('Databáze byla inicializovaná.', 'ok');
					//$this->redirect('default');
				}
				catch (DibiException $e)
				{
					dibi::rollback();
					$this->flashMessage('Databázi se nepodařilo inicializovat.', 'error');
					Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
					//$this->redirect('default');
				}
			}
			else // Tabulka "verze" neexistuje, ostatní možná ano => nespecifikovaná chyba
			{
				throw $e;
			}
		}
	}

	public function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);

		$texy = $this->context->Texy;
		$this->texy = $texy;
		$texy2 = $this->context->Texy2;
		//$texy3 = new NoImageTexy();

		$texy->addHandler('script', array($this, 'scriptHandler'));
		$texy->addHandler('image', array($this, 'videoHandler'));
		$texy->addHandler('image', array($this, 'imageHandler'));
		$texy->addHandler('afterTable', array($this, 'afterTable'));

		$template->registerHelper('texy', array($texy, 'process'));
		$template->registerHelper('texy2', array($texy2, 'process'));
		//$template->registerHelper('noimagetexy', array($texy3, 'process'));

		$datum = new Datum();
		$template->registerHelper('datum', array($datum, 'date'));

		$template->registerHelper('zvetsPrvni', array($this, 'zvetsPrvni'));

		$template->registerHelper('vyslednyCas', array($this, 'vyslednyCas'));

		return $template;
	}

	public function formatTemplateFiles()
	{
		$paths = parent::formatTemplateFiles();
		foreach ($paths as $key => $path)
		{
			$paths_[] = str_replace('templates/', '../app_custom/templates/', $path);
		}
		return array_merge($paths_, $paths);
	}

	public function formatLayoutTemplateFiles()
	{
		$paths = parent::formatLayoutTemplateFiles();
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
		$nav = new Navigation\Navigation($this, $name);
		$datum = new Datum;

		$presenter = $this->getPresenter()->name;

		$vsechno = false;
		$sprava = false;

		if($presenter == 'Stranky' && $this->getParam('id', 0) === 3) $vsechno = true;

		$hp = $nav->setupHomepage('Úvod', $this->link('Homepage:'));
		if($presenter == 'Homepage') $nav->setCurrentNode($hp);

		if($vsechno || $sprava || in_array($presenter, array('Clanky')))
		{
			$l1 = $nav->add('Články', $this->link('Clanky:'));
			if($presenter == 'Clanky' && $this->getAction() == 'default') $nav->setCurrentNode($l1);

			$clankyModel = $this->context->clanky;
			if($this->user->isAllowed('clanky', 'edit')) $clankyModel->zobrazitNezverejnene();
			$clanky = $clankyModel->findAll();

			if($this->user->isAllowed('clanky', 'add'))
			{
				$add = $l1->add('Nový', $this->link('Clanky:add'));
				if($presenter == 'Clanky' && $this->getAction() == 'add') $nav->setCurrentNode($add);
			}

			foreach ($clanky as $clanek)
			{
				$l2 = $l1->add($clanek->nazev, $this->link('Clanky:clanek', $clanek->id));
				if($presenter == 'Clanky' && $this->getParam('id') == $clanek->id) $nav->setCurrentNode($l2);

				if($this->user->isAllowed('clanky', 'edit'))
				{
					$ed = $l2->add('Úprava', $this->link('Clanky:edit', $clanek->id));
					if($presenter == 'Clanky' && $this->getAction() == 'edit' && $this->getParam('id') == $clanek->id) $nav->setCurrentNode($ed);
				}
			}
		}

		if($vsechno || in_array($presenter, array('Zavody', 'Rocniky', 'Pravidla')))
		{
			$navRocniky = $nav->add('Závody', $this->link('Rocniky:'));
			if($presenter == 'Rocniky' && $this->getAction() == 'default') $nav->setCurrentNode($navRocniky);

			$rocnikyModel = $this->context->rocniky;
			$zavodyModel = $this->context->zavody;
			$rocniky = $rocnikyModel->findAll();
			foreach ($rocniky as $rocnik)
			{
				$rocnikNode = $navRocniky->add($rocnik->rocnik . '. ročník', $this->link('Rocniky:rocnik', $rocnik->id));
				if($presenter == 'Rocniky' && $this->getAction() == 'rocnik' && $this->getParam('id') == $rocnik->id) $nav->setCurrentNode($rocnikNode);

				if($this->user->isAllowed('rocniky', 'edit'))
				{
					$ed = $rocnikNode->add('Upravit', $this->link('Rocniky:edit', $rocnik->id));
					if($presenter == 'Rocniky' && $this->getAction() == 'edit' && $this->getParam('id') == $rocnik->id) $nav->setCurrentNode($ed);
				}

				$roc = $rocnikNode->add('Bodová tabulka', $this->link('Rocniky:vysledky', $rocnik->id));
				if($presenter == 'Rocniky' && $this->getAction() == 'vysledky' && $this->getParam('id', 0) != 0)
				{
					if($this->getParam('id') == $rocnik->id) $nav->setCurrentNode($roc);
				}

				$roc = $rocnikNode->add('Pravidla', $this->link('Pravidla:pravidla', $this->getParam('id', 0)));
				if($presenter == 'Pravidla' && $this->getAction() == 'pravidla' && $this->getParam('id', 0) != 0) $nav->setCurrentNode($roc);

				$zavody = $zavodyModel->findByRocnik($rocnik->id);
				foreach ($zavody as $zavod)
				{
					$zavodNode = $rocnikNode->add($zavod->nazev . ', ' . $datum->date(substr($zavod->datum, 0, 10), 0, 0, 0), $this->link('Zavody:zavod', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'zavod' && $this->getParam('id') == $zavod->id) $nav->setCurrentNode($zavodNode);

					if($this->user->isAllowed('zavody', 'edit'))
					{
						$ed = $zavodNode->add('Upravit', $this->link('Zavody:edit', $zavod->id));
						if($presenter == 'Zavody' && $this->getAction() == 'edit' && $this->getParam('id') == $zavod->id) $nav->setCurrentNode($ed);
					}

					$ed = $zavodNode->add('Výsledky', $this->link('Zavody:vysledky', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'vysledky' && $this->getParam('id') == $zavod->id) $nav->setCurrentNode($ed);

					if($this->user->isAllowed('zavody', 'edit'))
					{
						$ed = $zavodNode->add('Přidání výsledků', $this->link('Zavody:pridatVysledky', $zavod->id));
						if($presenter == 'Zavody' && $this->getAction() == 'pridatVysledky' && $this->getParam('id') == $zavod->id) $nav->setCurrentNode($ed);
					}

					$ed = $zavodNode->add('Informace pro komentátora', $this->link('Zavody:pripravaProKomentatora', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'pripravaProKomentatora' && $this->getParam('id') == $zavod->id) $nav->setCurrentNode($ed);

					$ed = $zavodNode->add('Bodová tabulka před závodem', $this->link('Rocniky:vysledkyPredZavodem', $zavod->id));
					if($presenter == 'Rocniky' && $this->getAction() == 'vysledky' && $this->getParam('id_zavodu') == $zavod->id) $nav->setCurrentNode($ed);

					$ed = $zavodNode->add('Startovní pořadí', $this->link('Zavody:startovniPoradi', $zavod->id));
					if($presenter == 'Zavody' && $this->getAction() == 'startovniPoradi' && $this->getParam('id') == $zavod->id) $nav->setCurrentNode($ed);
				}
			}
		}

		if($vsechno || in_array($presenter, array('Sbory', 'Druzstva', 'Uzivatele', 'Terce', 'TypySboru')))
		{
			$navSbory = $nav->add('Sbory', $this->link('Sbory:'));
			if($presenter == 'Sbory' && $this->getAction() == 'default') $nav->setCurrentNode($navSbory);

			$sboryModel = $this->context->sbory;
			$druzstvaModel = $this->context->druzstva;
			$uzivateleModel = $this->context->uzivatele;
			$terceModel = $this->context->terce;
			$sbory = $sboryModel->findAll();

			if($this->user->isAllowed('sbory', 'add'))
			{
				$node = $navSbory->add('Přidání nového', $this->link('Sbory:add'));
				if($presenter == 'Sbory' && $this->getAction() == 'add') $nav->setCurrentNode($node);
			}

			foreach ($sbory as $sbor)
			{
				$sborNode = $navSbory->add(mb_substr($sbor->nazev, 0, 15), $this->link('Sbory:sbor', $sbor->id));
				if($presenter == 'Sbory' && $this->getParam('id') == $sbor->id) $nav->setCurrentNode($sborNode);

				if($vsechno || $presenter == 'Druzstva')
				{
					$druzstva = $druzstvaModel->findBySbor($sbor->id);
					foreach ($druzstva as $druzstvo)
					{
						$node = $sborNode->add('Družstvo ' . $druzstvo->nazev, $this->link('Druzstva:druzstvo', $druzstvo->id));
						if($presenter == 'Druzstva' && $this->getAction() == 'druzstvo' && $this->getParam('id') == $druzstvo->id) $nav->setCurrentNode($node);

						if($this->user->isAllowed('druzstva', 'edit'))
						{
							$ed = $node->add('Úprava', $this->link('Druzstva:edit', $druzstvo->id));
							if($presenter == 'Druzstva' && $this->getAction() == 'edit' && $this->getParam('id') == $druzstvo->id) $nav->setCurrentNode($ed);
						}
					}
				}

				if($vsechno || $presenter == 'Uzivatele')
				{
					$uzivatele = $uzivateleModel->findBySbor($sbor->id);
					foreach ($uzivatele as $uzivatel)
					{
						$node = $sborNode->add('Uživatel ' . $uzivatel->jmeno . ' ' . $uzivatel->prijmeni, $this->link('Uzivatele:uzivatel', $uzivatel->id));
						if($presenter == 'Uzivatele' && $this->getAction() == 'uzivatel' && $this->getParam('id') == $uzivatel->id) $nav->setCurrentNode($node);

						if($this->user->isAllowed('uzivatele', 'edit'))
						{
							$ed = $node->add('Úprava', $this->link('Uzivatele:edit', $uzivatel->id));
							if($presenter == 'Uzivatele' && $this->getAction() == 'edit' && $this->getParam('id') == $uzivatel->id) $nav->setCurrentNode($ed);
						}
					}
				}

				if($vsechno || $presenter == 'Terce')
				{
					$terce = $terceModel->findBySbor($sbor->id);
					foreach ($terce as $terc)
					{
						$node = $sborNode->add($this->zvetsPrvni($terc->typ) . ' terče', $this->link('Terce:terce', $terc->id));
						if($presenter == 'Terce' && $this->getAction() == 'terce' && $this->getParam('id') == $terc->id) $nav->setCurrentNode($node);

						if($this->user->isAllowed('terce', 'edit'))
						{
							$ed = $node->add('Úprava', $this->link('Terce:edit', $terc->id));
							if($presenter == 'Terce' && $this->getAction() == 'edit' && $this->getParam('id') == $terc->id) $nav->setCurrentNode($ed);
						}
					}
				}
			}

			if($presenter == 'Uzivatele' && $this->getAction() == 'add') $node = $nav->add('Přidání nového uživatele', $this->link('Uzivatele:add'));
			if($presenter == 'Uzivatele' && $this->getAction() == 'add') $nav->setCurrentNode($node);

			$node = $nav->add('Uživatelé', $this->link('Uzivatele:default'));
			if($presenter == 'Uzivatele' && $this->getAction() == 'default') $nav->setCurrentNode($node);

			$nod20 = $nav->add('Typ sborů', $this->link('TypySboru:'));
			if($presenter == 'TypySboru' && $this->getAction() == 'default') $nav->setCurrentNode($nod20);

			$nod21 = $nod20->add('Nový', $this->link('TypySboru:add'));
			if($presenter == 'TypySboru' && $this->getAction() == 'add') $nav->setCurrentNode($nod21);

			$nod20 = $nav->add('Místa', $this->link('Mista:'));
			if($presenter == 'Mista' && $this->getAction() == 'default') $nav->setCurrentNode($nod20);

			$nod21 = $nod20->add('Nové', $this->link('Mista:add'));
			if($presenter == 'Mista' && $this->getAction() == 'add') $nav->setCurrentNode($nod21);
		}

		if($vsechno || in_array($presenter, array('Forum', 'Diskuze')))
		{
			$navForum = $nav->add('Fórum', $this->link('Forum:'));
			if($presenter == 'Forum' && $this->getAction() == 'default') $nav->setCurrentNode($navForum);

			$temataModel = $this->context->temata;
			$diskuzeModel = $this->context->diskuze;
			$temata = $temataModel->findAll();

			foreach ($temata as $tema)
			{
				$roc = $navForum->add($tema->nazev, $this->link('Forum:forum', $tema->id));
				if($presenter == 'Forum' && $this->getParam('id') == $tema->id) $nav->setCurrentNode($roc);

				$node = $roc->add('Zeptat se', $this->link('Diskuze:zeptatse', array('id' => $tema->id, 'id_souvisejiciho' => $this->getParam('id_souvisejiciho', NULL))));
				if($presenter == 'Diskuze' && $this->getAction() == 'zeptatse' && $this->getParam('id') == $tema->id) $nav->setCurrentNode($node);

				$diskuze = $diskuzeModel->findByTema($tema->id);
				foreach ($diskuze as $disk)
				{
					$node = $roc->add($disk->tema_diskuze, $this->link('Diskuze:diskuze', $disk->id_diskuze));
					if($presenter == 'Diskuze' && $this->getAction() == 'diskuze' && $this->getParam('id') == $disk->id_diskuze) $nav->setCurrentNode($node);
				}
			}
		}

		// Stránky generovat vždy
		if($vsechno || in_array($presenter, array('Stranky')))
		{
			$strankyModel = $this->context->stranky;
			$stranky = $strankyModel->findAll();
			foreach ($stranky as $stranka)
			{
				$roc = $nav->add($stranka->nazev, $this->link('Stranky:stranka', $stranka->id));
				if($presenter == 'Stranky' && $this->getParam('id') == $stranka->id) $nav->setCurrentNode($roc);
			}
		}

		if($vsechno || in_array($presenter, array('Galerie')))
		{
			$navGalerie = $nav->add('Galerie', $this->link('Galerie:'));
			if($presenter == 'Galerie' && $this->getAction() == 'default') $nav->setCurrentNode($navGalerie);

			$galerieModel = $this->context->galerie;
			if($this->user->isAllowed('galerie', 'edit')) $galerieModel->zobrazitNezverejnene();
			$galerie = $galerieModel->findAll();
			foreach ($galerie as $fotogal)
			{
				$roc = $navGalerie->add($fotogal->nazev, $this->link('Galerie:galerie', $fotogal->id));
				if($presenter == 'Galerie' && $this->getAction() == 'galerie' && $this->getParam('id') == $fotogal->id) $nav->setCurrentNode($roc);

				if($this->user->isAllowed('galerie', 'edit'))
				{
					$ed = $roc->add('Upravit', $this->link('Galerie:edit', $fotogal->id));
					if($presenter == 'Galerie' && $this->getAction() == 'edit' && $this->getParam('id') == $fotogal->id) $nav->setCurrentNode($ed);

					$ed = $roc->add('Přidat fotky', $this->link('Galerie:pridatFotky', $fotogal->id));
					if($presenter == 'Galerie' && $this->getAction() == 'pridatFotky' && $this->getParam('id') == $fotogal->id) $nav->setCurrentNode($ed);

					$ed = $roc->add('Přidat videa', $this->link('Galerie:pridatVidea', $fotogal->id));
					if($presenter == 'Galerie' && $this->getAction() == 'pridatVidea' && $this->getParam('id') == $fotogal->id) $nav->setCurrentNode($ed);
				}
			}
		}

		if($vsechno || in_array($presenter, array('Statistiky')))
		{
			$nav0 = $nav->add('Statistiky', $this->presenter->link('Statistiky:default'));
			if($presenter == 'Statistiky' && $this->getAction() == 'default') $nav->setCurrentNode($nav0);

			$nav1 = $nav0->add('Průměrné časy sezón', $this->link('Statistiky:prumerneCasy'));
			if($this->getAction() == 'prumerneCasy') $nav->setCurrentNode($nav1);

			$nav2 = $nav0->add('Nejlépe bodovaná družstva', $this->link('Statistiky:nejlepeBodovanaDruzstva'));
			if($this->getAction() == 'nejlepeBodovanaDruzstva') $nav->setCurrentNode($nav2);

			$nav3 = $nav0->add('Nejrychlejší dráhy', $this->link('Statistiky:nejrychlejsiDrahy'));
			if($this->getAction() == 'nejrychlejsiDrahy') $nav->setCurrentNode($nav3);

			$nav4 = $nav0->add('Nejrychlejší časy', $this->link('Statistiky:nejrychlejsiCasy'));
			if($this->getAction() == 'nejrychlejsiCasy') $nav->setCurrentNode($nav4);

			$nav5 = $nav0->add('Vítězové ročníků', $this->link('Statistiky:vitezoveRocniku'));
			if($this->getAction() == 'vitezoveRocniku') $nav->setCurrentNode($nav5);

			$nav6 = $nav0->add('Počty pořádaných závodů', $this->link('Statistiky:poradaneZavody'));
			if($this->getAction() == 'poradaneZavody') $nav->setCurrentNode($nav6);
		}

		if($vsechno || in_array($presenter, array('Sprava', 'Stranky', 'Rocniky', 'Zavody', 'Kategorie', 'BodoveTabulky', 'Uzivatele', 'Sledovani', 'Mista', 'Okresy', 'Souteze', 'Druzstva', 'Ankety', 'TypySboru', 'SablonyClanku')))
		{
			if($this->user->isAllowed('sprava', 'edit'))
			{
				$sprava = $nav->add('Správa', $this->link('Sprava:'));
				if($presenter == 'Sprava' && $this->getAction() == 'default') $nav->setCurrentNode($sprava);

				$nod0 = $sprava->add('Kotrola údajů', $this->link('Sprava:kontrola'));
				if($presenter == 'Sprava' && $this->getAction() == 'kontrola') $nav->setCurrentNode($nod0);

				if($this->user->isAllowed('stranky', 'edit'))
				{
					$nod0 = $sprava->add('Správa stránek', $this->link('Stranky:'));
					if($presenter == 'Stranky' && $this->getAction() == 'default') $nav->setCurrentNode($nod0);

					$nod1 = $nod0->add('Nová', $this->link('Stranky:add'));
					if($presenter == 'Stranky' && $this->getAction() == 'add') $nav->setCurrentNode($nod1);

					/* $nod2 = $sprava->add('Úprava', $this->link('Stranky:add'));
					  if( $presenter == 'Stranky' && $this->getAction() == 'add' ) $nav->setCurrentNode($nod1); */
				}

				if($this->user->isAllowed('rocniky', 'edit'))
				{
					$nod3 = $sprava->add('Ročníky', $this->link('Rocniky:'));
					if($presenter == 'Rocniky' && $this->getAction() == 'default') $nav->setCurrentNode($nod3);

					$nod4 = $nod3->add('Přidání ročníku', $this->link('Rocniky:add'));
					if($presenter == 'Rocniky' && $this->getAction() == 'add') $nav->setCurrentNode($nod4);
				}

				if($this->user->isAllowed('zavody', 'edit'))
				{
					$nod6 = $sprava->add('Závody', $this->link('Zavody:'));

					$nod5 = $nod6->add('Přidání závodu', $this->link('Zavody:add'));
					if($presenter == 'Zavody' && $this->getAction() == 'add') $nav->setCurrentNode($nod5);

					$nod7 = $sprava->add('Sportovní kategorie', $this->link('Kategorie:'));
					if($presenter == 'Kategorie' && $this->getAction() == 'default') $nav->setCurrentNode($nod7);

					$nod8 = $nod7->add('Nová', $this->link('Kategorie:add'));
					if($presenter == 'Kategorie' && $this->getAction() == 'add') $nav->setCurrentNode($nod8);

					if($presenter == 'Kategorie' && $this->getAction() == 'edit' && $this->getParam('id', 0) != 0)
					{
						$nod8 = $nod7->add('Úprava', $this->link('Kategorie:edit', $this->getParam('id')));
						if($presenter == 'Kategorie' && $this->getAction() == 'edit') $nav->setCurrentNode($nod8);
					}

					$nod9 = $sprava->add('Bodové tabulky', $this->link('BodoveTabulky:'));
					if($presenter == 'BodoveTabulky' && $this->getAction() == 'default') $nav->setCurrentNode($nod9);

					$nod10 = $nod9->add('Přidání bodové tabulky', $this->link('BodoveTabulky:add'));
					if($presenter == 'BodoveTabulky' && $this->getAction() == 'add') $nav->setCurrentNode($nod10);
				}

				if($this->user->isAllowed('uzivatele', 'edit'))
				{
					$nod11 = $sprava->add('Uživatelé', $this->link('Uzivatele:default'));
					if($presenter == 'Uzivatele' && $this->getAction() == 'default') $nav->setCurrentNode($nod11);

					$uzivateleModel = $this->context->uzivatele;

					$uzivatele = $uzivateleModel->findAll();
					foreach ($uzivatele as $uzivatel)
					{
						$nod12 = $nod11->add('Uživatel ' . $uzivatel->jmeno . ' ' . $uzivatel->prijmeni, $this->link('Uzivatele:uzivatel', $uzivatel->id));
						if($presenter == 'Uzivatele' && $this->getParam('id') == $uzivatel->id) $nav->setCurrentNode($nod12);
					}

					$nod13 = $nod12->add('Úprava', $this->link('Uzivatele:edit', array('id' => $this->getParam('id'))));
					if($presenter == 'Uzivatele' && $this->getAction() == 'edit' && $this->getParam('id') == $uzivatel->id) $nav->setCurrentNode($nod12);
				}

				$nod15 = $sprava->add('Místa', $this->link('Mista:'));
				if($presenter == 'Mista' && $this->getAction() == 'default') $nav->setCurrentNode($nod15);

				$nod15_ = $nod15->add('Úprava', $this->link('Mista:'));
				if($presenter == 'Mista' && $this->getAction() == 'edit') $nav->setCurrentNode($nod15_);

				$nod15_ = $nod15->add('Přidat nové', $this->link('Mista:add'));
				if($presenter == 'Mista' && $this->getAction() == 'add') $nav->setCurrentNode($nod15_);

				$nod16 = $sprava->add('Okresy', $this->link('Okresy:'));
				if($presenter == 'Okresy' && $this->getAction() == 'default') $nav->setCurrentNode($nod16);

				$nod17 = $nod16->add('Přidat nový', $this->link('Okresy:add'));
				if($presenter == 'Okresy' && $this->getAction() == 'add') $nav->setCurrentNode($nod17);

				if($presenter == 'Okresy' && $this->getParam('id', 0) != 0)
				{
					$okresyModel = $this->context->okresy;
					$okres = $okresyModel->find($this->getParam('id'))->fetch();
					$okresNode = $nod16->add($okres['nazev'], $this->link('Okresy:edit', $okres['id']));
					$nav->setCurrentNode($okresNode);
				}

				$nod18 = $sprava->add('Sportovní soutěže', $this->link('Souteze:'));
				if($presenter == 'Souteze' && $this->getAction() == 'default') $nav->setCurrentNode($nod18);

				$nod19 = $nod18->add('Přidat novou', $this->link('Souteze:add'));
				if($presenter == 'Souteze' && $this->getAction() == 'add') $nav->setCurrentNode($nod19);

				if($presenter == 'Souteze' && $this->getParam('id', 0) != 0)
				{
					$soutezeModel = $this->context->souteze;
					$souteze = $soutezeModel->findAll();
					foreach ($souteze as $soutez)
					{
						$soutezNode = $nod18->add($soutez['nazev'], $this->link('Souteze:soutez', $soutez['id']));
						if($this->getAction() == 'soutez' && $this->getParam('id') == $soutez['id']) $nav->setCurrentNode($soutezNode);

						$ed = $soutezNode->add('Úprava', $this->link('Souteze:edit', $soutez['id']));
						if($this->getAction() == 'edit' && $this->getParam('id') == $soutez['id']) $nav->setCurrentNode($ed);
					}
				}

				$nod20 = $sprava->add('Družstva', $this->link('Druzstva:'));
				if($presenter == 'Druzstva' && $this->getAction() == 'default') $nav->setCurrentNode($nod20);

				$nod21 = $nod20->add('Přidat nové', $this->link('Druzstva:add'));
				if($presenter == 'Druzstva' && $this->getAction() == 'add') $nav->setCurrentNode($nod21);

				$nod20 = $sprava->add('Ankety', $this->link('Ankety:'));
				if($presenter == 'Ankety' && $this->getAction() == 'default') $nav->setCurrentNode($nod20);

				$nod21 = $nod20->add('Nová', $this->link('Ankety:add'));
				if($presenter == 'Ankety' && $this->getAction() == 'add') $nav->setCurrentNode($nod21);

				$nod21 = $nod20->add('Úprava', $this->link('Ankety:edit', $this->getParam('id', NULL)));
				if($presenter == 'Ankety' && $this->getAction() == 'edit') $nav->setCurrentNode($nod21);

				$nod20 = $sprava->add('Typ sborů', $this->link('TypySboru:'));
				if($presenter == 'TypySboru' && $this->getAction() == 'default') $nav->setCurrentNode($nod20);

				$nod21 = $nod20->add('Nový', $this->link('TypySboru:add'));
				if($presenter == 'TypySboru' && $this->getAction() == 'add') $nav->setCurrentNode($nod21);

				$nod21 = $nod20->add('Úprava', $this->link('TypySboru:edit', $this->getParam('id', NULL)));
				if($presenter == 'TypySboru' && $this->getAction() == 'edit') $nav->setCurrentNode($nod21);

				$nod18 = $sprava->add('Šablony článků', $this->link('SablonyClanku:'));
				if($presenter == 'SablonyClanku' && $this->getAction() == 'default') $nav->setCurrentNode($nod18);

				$nod19 = $nod18->add('Přidat novou', $this->link('SablonyClanku:add'));
				if($presenter == 'SablonyClanku' && $this->getAction() == 'add') $nav->setCurrentNode($nod19);

				if($presenter == 'SablonyClanku' && $this->getParam('id', 0) !== 0)
				{
					$nod19 = $nod18->add('Úprava', $this->link('SablonyClanku:edit', $this->getParam('id')));
					if($this->getAction() == 'edit') $nav->setCurrentNode($nod19);
				}

				$nod18 = $sprava->add('Nastavení', $this->link('Nastaveni:'));
				if($presenter == 'Nastaveni' && $this->getAction() == 'default') $nav->setCurrentNode($nod18);
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
		if($title === NULL) $this->template->title = self::$liga['nazev'];
		else $this->template->title = self::$liga['nazev'] . ' >> ' . $title;

		$this->template->nadpis = $title !== NULL ? trim($title) : '';
	}

	public function beforeRender()
	{
		$this->setTitle();

		// odhlášení z dlouhé neaktivity
		if($this->getUser()->getLogoutReason() === Nette\Security\IUserStorage::INACTIVITY)
		{
			$this->flashMessage('Byl jste odhlášen z důvodu dlouhé neaktivity. Přihlašte se znovu.', 'warning');
		}

		\DependentSelectBox\JsonDependentSelectBox::tryJsonResponse($this);

		// nastaví položky menu do šablony
		$this->renderMenu();

		// nastavení identity do šablony
		//$this->template->user = $this->user->isLoggedIn() ? $this->user : NULL;

		$this->template->backlink = $this->getApplication()->storeRequest();

		$this->template->isProduction = $this->context->params['productionMode'];

		$this->template->aktualniRok = date('Y');

		$this->template->FSL_CMS = self::FSL_CMS;
		$this->template->FSL_CMS_URL = self::FSL_CMS_URL;
		$this->template->FSL_CMS_VERZE = self::FSL_CMS_VERZE;

		$this->template->liga = self::$liga;
	}

	/**
	 * Připraví položky menu do šablony. Každá liga si připraví vlastní položky.
	 */
	abstract protected function renderMenu();

	/**
	 *
	 * @param type $ceho
	 * @return type
	 * @deprecated Používat ucfirst()
	 */
	public function zvetsPrvni($ceho)
	{
		return ucfirst($ceho);
	}

	public static function float($cislo)
	{
		if(strpos($cislo, ',') !== false) $cislo = preg_replace('~,~', '.', $cislo);

		return (float) $cislo;
	}

	public function vyslednyCas($cas)
	{
		if(strpos($cas, '1000') !== false) return 'NP';
		else return $cas;
	}

	public function jeAutor($id_autora)
	{
		return $this->getUser()->getIdentity() !== NULL && $id_autora !== NULL && (int) $this->user->getIdentity()->id == (int) $id_autora;
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

			case Galerie::$VIDEO_YOUTUBEPLAYLIST:
				ob_start();
				$this['video']->renderYoutubePlaylist($image->width, $image->height, $key);
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
	 * Texyla loader factory
	 * @return TexylaLoader
	 */
	protected function createComponentTexyla()
	{
		$baseUri = $this->context->httpRequest->url->baseUrl;
		$filter = new WebLoader\Filter\VariablesFilter(array(
					"baseUri" => $baseUri,
					"previewPath" => $this->link("Texyla:preview"),
					"filesPath" => $this->link("Texyla:listFiles"),
					"filesUploadPath" => $this->link("Texyla:upload"),
					"filesMkDirPath" => $this->link("Texyla:mkDir"),
					"filesRenamePath" => $this->link("Texyla:rename"),
					"filesDeletePath" => $this->link("Texyla:delete"),
					"galerieVyberPath" => $this->link("Galerie:vyber"),
					"souboryVyberPath" => $this->link('Soubory:vyber', array('souvisejici' => $this->presenter->getName(), 'id_souvisejiciho' => $this->presenter->getParam('id', 0)))
				));

		$texyla = new TexylaLoader($filter, $baseUri . "webtemp");
		return $texyla;
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

		if(!$this->user->isAllowed($this->getParam('presenter'), $this->getAction())) throw new ForbiddenRequestException();
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
		$user = $this->getUser();
		if(isset($udaje['login']) && isset($udaje['heslo']))
		{
			// zaregistrujeme autentizační handler
			$user->setAuthenticator($this->context->uzivatele);

			$user->login($udaje['login'], $udaje['heslo']);

			// nastavíme expiraci
			$user->setExpiration('+3 hours', true, true);

			setCookie('user', $udaje['login'], time() + 365 * 24 * 3600, '/');
		}
		elseif(isset($udaje['facebookId']))
		{
			// zaregistrujeme autentizační handler
			$user->setAuthenticatior(new FacebookUzivatele);

			$user->login($udaje['facebookId'], '');

			// nastavíme expiraci
			$user->setExpiration('+3 hours', true, true);
		}
		else
		{
			throw new Exception('Špatné údaje pro přihlášení.');
		}
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
			$vysledky = $this->context->vysledky;
			$umisteni = $vysledky->porovnejDruzstva($a, $b)->fetchAll();

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

	/**
	 * Implementace oprávnění pro konkrétního uživatele a konkrétní zdroj
	 * @param type $permission
	 * @param type $role
	 * @param type $resource
	 * @param type $privilege
	 * @return boolean
	 */
	public function assertion($permission, $role, $resource, $privilege)
	{
		$user = $this->getUser();

		if($resource == 'zavody')
		{
			$zavod = $permission->getQueriedResource();
			if(!($zavod instanceof ZavodyResource)) return false;

			// je správce sboru nebo jeho kontaktní osoba
			foreach ($zavod->poradatele as $poradatel)
			{
				if($poradatel->id_spravce == $user->getIdentity()->id) return true;
				if($poradatel->id_kontaktni_osoby == $user->getIdentity()->id) return true;
			}
		}

		elseif($resource == 'startovni_poradi')
		{
			$sp = $permission->getQueriedResource();
			if(!($sp instanceof Nette\Security\IResource)) return false;

			// Daný uživatel přihlásil toto SP
			if($sp->id_autora == $user->getIdentity()->id) return true;

			// Uživatel je správce sboru nebo jeho kontaktní osoba
			$sboryModel = $this->context->sbory;
			foreach ($sboryModel->findByZavod($sp->id_zavodu)->fetchAll() as $poradatel)
			{
				if($poradatel->id_spravce == $user->getIdentity()->id) return true;
				if($poradatel->id_kontaktni_osoby == $user->getIdentity()->id) return true;
			}

			// Uživatel je správce sboru přihlášeného družstva
			if($sp->id_sboru_druzstva == $user->getIdentity()->id_sboru) return true;
		}

		return false;
	}

}
