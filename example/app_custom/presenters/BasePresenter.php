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
abstract class BasePresenter extends Presenter
{

	public $oldLayoutMode = FALSE;
	public $user = NULL;
	public static $SOUVISEJICI = array('clanky' => 'články', 'zavody' => 'závody', 'terce' => 'terče', 'sbory' => 'sbory', 'druzstva' => 'družstva');
	protected $texy;
	private static $nazev;

	public function __construct()
	{
		self::$nazev = Environment::getVariable('name');
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
		//$template->registerHelper('imlode', array($this, 'implode'));

		return $template;
	}

	public function formatTemplateFiles($presenter, $view)
	{
		$paths = parent::formatTemplateFiles($presenter, $view);
		foreach ($paths as $key => $path)
		{
			$paths_[] = str_replace('templates/', 'mytemplates/', $path);
		}
		return array_merge($paths_, $paths);
	}

	public function formatLayoutTemplateFiles($presenter, $view)
	{
		$paths = parent::formatLayoutTemplateFiles($presenter, $view);
		foreach ($paths as $key => $path)
		{
			$paths_[] = str_replace('templates/', 'mytemplates/', $path);
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
		$presenter = $this->getReflection()->getName();
		$nav->setupHomepage('Úvod', $this->link('Homepage:'));

		$datum = new Datum;

		$navClanky = $nav->add('Články', $this->link('Clanky:'));
		if($presenter == 'ClankyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($navClanky);
		if(in_array($presenter, array('ClankyPresenter')))
		{
			$clankyModel = new Clanky;
			if($this->user->isAllowed('clanky', 'edit')) $clankyModel->zobrazitNezverejnene();
			$clanky = $clankyModel->findAll();

			$add = $navClanky->add('Nový', $this->link('Clanky:add'));
			if($presenter == 'ClankyPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($add);

			foreach ($clanky as $clanek)
			{
				$roc = $navClanky->add($clanek->nazev, $this->link('Clanky:clanek', $clanek->id));
				if($presenter == 'ClankyPresenter' && $this->getParam('id') == $clanek->id) $nav->setCurrent($roc);

				$ed = $roc->add('Úprava', $this->link('Clanky:edit', $clanek->id));
				if($presenter == 'ClankyPresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $clanek->id) $nav->setCurrent($ed);
			}
		}

		$navRocniky = $nav->add('Závody', $this->link('Rocniky:'));
		if($presenter == 'RocnikyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($navRocniky);

		if(in_array($presenter, array('ZavodyPresenter', 'RocnikyPresenter', 'PravidlaPresenter')))
		{
			$rocnikyModel = new Rocniky;
			$zavodyModel = new Zavody;
			$rocniky = $rocnikyModel->findAll();
			foreach ($rocniky as $rocnik)
			{
				$rocnikNode = $navRocniky->add($rocnik->rocnik . '. ročník', $this->link('Rocniky:rocnik', $rocnik->id));
				if($presenter == 'RocnikyPresenter' && $this->getParam('action') == 'rocnik' && $this->getParam('id') == $rocnik->id) $nav->setCurrent($rocnikNode);

				$roc = $rocnikNode->add('Bodová tabulka', $this->link('Rocniky:vysledky', $rocnik->id));
				if($presenter == 'RocnikyPresenter' && $this->getParam('action') == 'vysledky' && $this->getParam('id', 0) != 0)
				{
					if($this->getParam('id') == $rocnik->id) $nav->setCurrent($roc);
				}

				$roc = $rocnikNode->add('Pravidla', $this->link('Pravidla:pravidla', $this->getParam('id', 0)));
				if($presenter == 'PravidlaPresenter' && $this->getParam('action') == 'pravidla' && $this->getParam('id', 0) != 0)
				{
					$nav->setCurrent($roc);
				}

				$zavody = $zavodyModel->findByRocnik($rocnik->id);
				foreach ($zavody as $zavod)
				{
					$zavodNode = $rocnikNode->add($zavod->nazev . ', ' . $datum->date(substr($zavod->datum, 0, 10), 0, 0, 0), $this->link('Zavody:zavod', $zavod->id));
					if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'zavod' && $this->getParam('id') == $zavod->id) $nav->setCurrent($zavodNode);

					$ed = $zavodNode->add('Upravit', $this->link('Zavody:edit', $zavod->id));
					if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Výsledky', $this->link('Zavody:vysledky', $zavod->id));
					if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'vysledky' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Přidání výsledků', $this->link('Zavody:pridatVysledky', $zavod->id));
					if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'pridatVysledky' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Informace pro komentátora', $this->link('Zavody:pripravaProKomentatora', $zavod->id));
					if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'pripravaProKomentatora' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Bodová tabulka před závodem', $this->link('Rocniky:vysledkyPredZavodem', $zavod->id));
					if($presenter == 'RocnikyPresenter' && $this->getParam('action') == 'vysledky' && $this->getParam('id_zavodu') == $zavod->id) $nav->setCurrent($ed);

					$ed = $zavodNode->add('Startovní pořadí', $this->link('Zavody:startovniPoradi', $zavod->id));
					if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'startovniPoradi' && $this->getParam('id') == $zavod->id) $nav->setCurrent($ed);
				}
			}
		}

		$navSbory = $nav->add('Sbory', $this->link('Sbory:'));
		if($presenter == 'SboryPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($navSbory);
		if(in_array($presenter, array('SboryPresenter', 'DruzstvaPresenter', 'UzivatelePresenter', 'TercePresenter')))
		{
			$sboryModel = new Sbory;
			$druzstvaModel = new Druzstva;
			$uzivateleModel = new Uzivatele;
			$terceModel = new Terce;
			$sbory = $sboryModel->findAll();

			$node = $navSbory->add('Přidání nového', $this->link('Sbory:add'));
			if($presenter == 'SboryPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($node);

			foreach ($sbory as $sbor)
			{
				$sborNode = $navSbory->add(mb_substr($sbor->nazev, 0, 15), $this->link('Sbory:sbor', $sbor->id));
				if($presenter == 'SboryPresenter' && $this->getParam('id') == $sbor->id) $nav->setCurrent($sborNode);

				if($presenter == 'DruzstvaPresenter')
				{
					$druzstva = $druzstvaModel->findBySbor($sbor->id);
					foreach ($druzstva as $druzstvo)
					{
						$node = $sborNode->add('Družstvo ' . $druzstvo->nazev, $this->link('Druzstva:druzstvo', $druzstvo->id));
						if($presenter == 'DruzstvaPresenter' && $this->getParam('action') == 'druzstvo' && $this->getParam('id') == $druzstvo->id) $nav->setCurrent($node);

						$ed = $node->add('Úprava', $this->link('Druzstva:edit', $druzstvo->id));
						if($presenter == 'DruzstvaPresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $druzstvo->id) $nav->setCurrent($ed);
					}
				}

				if($presenter == 'UzivatelePresenter')
				{
					$uzivatele = $uzivateleModel->findBySbor($sbor->id);
					foreach ($uzivatele as $uzivatel)
					{
						$node = $sborNode->add('Uživatel ' . $uzivatel->jmeno . ' ' . $uzivatel->prijmeni, $this->link('Uzivatele:uzivatel', $uzivatel->id));
						if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'uzivatel' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($node);

						$ed = $node->add('Úprava', $this->link('Uzivatele:edit', $uzivatel->id));
						if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($ed);
					}
				}

				if($presenter == 'TercePresenter')
				{
					$terce = $terceModel->findBySbor($sbor->id);
					foreach ($terce as $terc)
					{
						$node = $sborNode->add($this->zvetsPrvni($terc->typ) . ' terče', $this->link('Terce:terce', $terc->id));
						if($presenter == 'TercePresenter' && $this->getParam('action') == 'terce' && $this->getParam('id') == $terc->id) $nav->setCurrent($node);

						$ed = $node->add('Úprava', $this->link('Terce:edit', $terc->id));
						if($presenter == 'TercePresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $terc->id) $nav->setCurrent($ed);
					}
				}
			}

			if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'add') $node = $nav->add('Přidání nového uživatele', $this->link('Uzivatele:add'));
			if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'add') $nav->setCurrent($node);

			$node = $nav->add('Uživatelé', $this->link('Uzivatele:default'));
			if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'default') $nav->setCurrent($node);
		}

		$navForum = $nav->add('Fórum', $this->link('Forum:'));
		if($presenter == 'ForumPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($navForum);
		if(in_array($presenter, array('ForumPresenter', 'DiskuzePresenter')))
		{
			$temataModel = new Temata;
			$diskuzeModel = new Diskuze;
			$temata = $temataModel->findAll();

			foreach ($temata as $tema)
			{
				$roc = $navForum->add('' . $tema->nazev, $this->link('Forum:forum', $tema->id));
				if($presenter == 'ForumPresenter' && $this->getParam('id') == $tema->id) $nav->setCurrent($roc);

				$node = $navForum->add('Zeptat se', $this->link('Diskuze:zeptatse', array('id' => $tema->id, 'id_souvisejiciho' => $this->getParam('id_souvisejiciho', NULL))));
				if($presenter == 'DiskuzePresenter' && $this->getAction() == 'zeptatse' && $this->getParam('id') == $tema->id) $nav->setCurrent($node);

				$diskuze = $diskuzeModel->findByTema($tema->id);
				foreach ($diskuze as $disk)
				{
					$node = $navForum->add('' . $disk->tema_diskuze, $this->link('Diskuze:diskuze', $disk->id_diskuze));
					if($presenter == 'DiskuzePresenter' && $this->getParam('id') == $disk->id_diskuze) $nav->setCurrent($node);

					$node_ = $node->add('Zeptat se', $this->link('Diskuze:zeptatse', $disk->id));
					if($presenter == 'DiskuzePresenter' && $this->getAction() == 'zeptatse' && $this->getParam('id') == $disk->id_diskuze) $nav->setCurrent($node_);
				}
			}
		}

		if(1 || in_array($presenter, array('StrankyPresenter')))
		{
			$strankyModel = new Stranky;
			$stranky = $strankyModel->findAll();
			foreach ($stranky as $stranka)
			{
				$roc = $nav->add($stranka->nazev, $this->link('Stranky:stranka', $stranka->id));
				if($presenter == 'StrankyPresenter' && $this->getParam('id') == $stranka->id) $nav->setCurrent($roc);
			}
		}

		$navFotogalerie = $nav->add('Fotogalerie', $this->link('Fotogalerie:'));
		if($presenter == 'FotogaleriePresenter' && $this->getParam('action') == 'default') $nav->setCurrent($navFotogalerie);

		if(in_array($presenter, array('FotogaleriePresenter')))
		{
			$fotogalerieModel = new Fotogalerie;
			if($this->user->isAllowed('fotogalerie', 'edit')) $fotogalerieModel->zobrazitNezverejnene();
			$fotogalerie = $fotogalerieModel->findAll();
			foreach ($fotogalerie as $fotogal)
			{
				$roc = $navFotogalerie->add($fotogal->nazev, $this->link('Fotogalerie:fotogalerie', $fotogal->id));
				if($presenter == 'FotogaleriePresenter' && $this->getParam('action') == 'fotogalerie' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($roc);

				$ed = $roc->add('Upravit', $this->link('Fotogalerie:edit', $fotogal->id));
				if($presenter == 'FotogaleriePresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($ed);

				$ed = $roc->add('Přidat fotky', $this->link('Fotogalerie:pridatFotky', $fotogal->id));
				if($presenter == 'FotogaleriePresenter' && $this->getParam('action') == 'pridatFotky' && $this->getParam('id') == $fotogal->id) $nav->setCurrent($ed);
			}
		}

		$nav0 = $nav->add('Statistiky', $this->link('Statistiky:'));
		if($presenter == 'StatistikyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nav0);

		if(in_array($presenter, array('StatistikyPresenter')))
		{
			$nav1 = $nav0->add('Průměrné časy sezón', $this->link('Statistiky:prumerneCasy'));
			if($this->getParam('action') == 'prumerneCasy') $nav->setCurrent($nav1);

			$nav2 = $nav0->add('Nejlépe bodovaná družstva', $this->link('Statistiky:nejlepeBodovanaDruzstva'));
			if($this->getParam('action') == 'nejlepeBodovanaDruzstva') $nav->setCurrent($nav2);

			$nav3 = $nav0->add('Nejrychlejší dráhy', $this->link('Statistiky:nejrychlejsiDrahy'));
			if($this->getParam('action') == 'nejrychlejsiDrahy') $nav->setCurrent($nav3);

			$nav4 = $nav0->add('Nejrychlejší časy', $this->link('Statistiky:nejrychlejsiCasy'));
			if($this->getParam('action') == 'nejrychlejsiCasy') $nav->setCurrent($nav4);

			$nav5 = $nav0->add('Vítězové ročníků', $this->link('Statistiky:vitezoveRocniku'));
			if($this->getParam('action') == 'vitezoveRocniku') $nav->setCurrent($nav5);

			$nav6 = $nav0->add('Počty pořádaných závodů', $this->link('Statistiky:poradaneZavody'));
			if($this->getParam('action') == 'poradaneZavody') $nav->setCurrent($nav6);
		}

		if(in_array($presenter, array('SpravaPresenter', 'StrankyPresenter', 'RocnikyPresenter', 'ZavodyPresenter', 'KategoriePresenter', 'BodoveTabulkyPresenter', 'UzivatelePresenter', 'SledovaniPresenter', 'MistaPresenter', 'OkresyPresenter', 'SoutezePresenter', 'DruzstvaPresenter')))
		{
			$sprava = $nav->add('Správa', $this->link('Sprava:'));
			if($presenter == 'SpravaPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($sprava);

			$nod0 = $sprava->add('Kotrola údajů', $this->link('Sprava:kontrola'));
			if($presenter == 'SpravaPresenter' && $this->getParam('action') == 'kontrola') $nav->setCurrent($nod0);

			$nod1 = $sprava->add('Správa stránek', $this->link('Stranky:'));
			if($presenter == 'StrankyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod1);

			$nod2 = $sprava->add('Přidání stránky', $this->link('Stranky:add'));
			if($presenter == 'StrankyPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod2);

			/* $nod2 = $sprava->add('Úprava', $this->link('Stranky:add'));
			  if( $presenter == 'StrankyPresenter' && $this->getParam('action') == 'add' ) $nav->setCurrent($nod1); */

			$nod3 = $sprava->add('Ročníky', $this->link('Rocniky:'));
			if($presenter == 'RocnikyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod3);

			$nod4 = $sprava->add('Přidání ročníku', $this->link('Rocniky:add'));
			if($presenter == 'RocnikyPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod4);

			$nod6 = $sprava->add('Závody', $this->link('Zavody:'));

			$nod5 = $nod6->add('Přidání závodu', $this->link('Zavody:add'));
			if($presenter == 'ZavodyPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod5);

			$nod7 = $sprava->add('Sportovní kategorie', $this->link('Kategorie:'));
			if($presenter == 'KategoriePresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod7);

			$nod8 = $nod7->add('Přidání kategorie', $this->link('Kategorie:add'));
			if($presenter == 'KategoriePresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod8);

			$nod9 = $sprava->add('Bodové tabulky', $this->link('BodoveTabulky:'));
			if($presenter == 'BodoveTabulkyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod9);

			$nod10 = $nod9->add('Přidání bodové tabulky', $this->link('BodoveTabulky:add'));
			if($presenter == 'BodoveTabulkyPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod10);

			if($this->user->isAllowed('uzivatele', 'edit'))
			{
				$nod11 = $sprava->add('Uživatelé', $this->link('Uzivatele:default'));
				if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod11);

				$uzivateleModel = new Uzivatele();

				$uzivatele = $uzivateleModel->findAll();
				foreach ($uzivatele as $uzivatel)
				{
					$nod12 = $nod11->add('Uživatel ' . $uzivatel->jmeno . ' ' . $uzivatel->prijmeni, $this->link('Uzivatele:uzivatel', $uzivatel->id));
					if($presenter == 'UzivatelePresenter' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($nod12);
				}

				$nod13 = $nod12->add('Úprava', $this->link('Uzivatele:edit', array('id' => $this->getParam('id'))));
				if($presenter == 'UzivatelePresenter' && $this->getParam('action') == 'edit' && $this->getParam('id') == $uzivatel->id) $nav->setCurrent($nod12);
			}

			$nod15 = $sprava->add('Místa', $this->link('Mista:'));
			if($presenter == 'MistaPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod15);

			$nod15_ = $nod15->add('Přidat nové', $this->link('Mista:add'));
			if($presenter == 'MistaPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod15_);

			$nod16 = $sprava->add('Okresy', $this->link('Okresy:'));
			if($presenter == 'OkresyPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod16);

			$nod17 = $nod16->add('Přidat nový', $this->link('Okresy:add'));
			if($presenter == 'OkresyPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod17);

			if($presenter == 'OkresyPresenter' && $this->getParam('id', 0) != 0)
			{
				$okresyModel = new Okresy;
				$okres = $okresyModel->find($this->getParam('id'))->fetch();
				$okresNode = $nod16->add($okres['nazev'], $this->link('Okresy:edit', $okres['id']));
				$nav->setCurrent($okresNode);
			}

			$nod18 = $sprava->add('Soutěže', $this->link('Souteze:'));
			if($presenter == 'SoutezePresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod18);

			$nod19 = $nod18->add('Přidat novou', $this->link('Souteze:add'));
			if($presenter == 'SoutezePresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod19);

			if($presenter == 'SoutezePresenter' && $this->getParam('id', 0) != 0)
			{
				$soutezeModel = new Souteze;
				$souteze = $soutezeModel->findAll();
				foreach ($souteze as $soutez)
				{
					$soutezNode = $nod18->add($soutez['nazev'], $this->link('Souteze:edit', $soutez['id']));
					if($this->getParam('id') == $soutez['id']) $nav->setCurrent($soutezNode);
				}
			}

			$nod20 = $sprava->add('Družstva', $this->link('Druzstva:'));
			if($presenter == 'DruzstvaPresenter' && $this->getParam('action') == 'default') $nav->setCurrent($nod20);

			$nod21 = $nod20->add('Přidat nové', $this->link('Druzstva:add'));
			if($presenter == 'DruzstvaPresenter' && $this->getParam('action') == 'add') $nav->setCurrent($nod21);
		}
	}

	protected function startup()
	{
		$this->user = Environment::getUser();

		FormContainer::extensionMethod('FormContainer::addRequestButton', array('RequestButtonHelper', 'addRequestButton'));
		FormContainer::extensionMethod('FormContainer::addRequestButtonBack', array('RequestButtonHelper', 'addRequestButtonBack'));

		$fbconnect = new FacebookConnectControl;
		$this->addComponent($fbconnect, 'facebookConnect');

		$auth = new AuthControl;
		$this->addComponent($auth, 'auth');

		$grafy = new GrafyControl;
		$this->addComponent($grafy, 'grafy');

		$hodnoceni = new HodnoceniControl;
		$this->addComponent($hodnoceni, 'hodnoceni');

		$foo = new Soubory();

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

		$prilohy = new PrilohyControl;
		$this->addComponent($prilohy, 'prilohy');

		$videa = new VideoControl;
		$this->addComponent($videa, 'video');

		$diskuze = new DiskuzeControl($this, 'diskuze');
		//$this->addComponent($diskuze, 'diskuze');

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

		$imageUploader = new ImageUploaderControl($this, 'imageUploader');
		$this->addComponent($imageUploader, 'imageUploader');

		//if( $this->getAction() != 'login' )
		{
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
			$acl->addResource('fotogalerie');
			$acl->addResource('fotky');
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

			$acl->allow('user', 'startovni_poradi', 'add');
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

			$acl->allow('author', 'clanky', Permission::ALL);
			$acl->allow('author', 'fotogalerie', Permission::ALL);
			$acl->allow('author', 'fotky', Permission::ALL);
			$acl->allow('author', 'souvisejici', Permission::ALL);

			$acl->allow('admin', Permission::ALL, Permission::ALL);

			// zaregistrujeme autorizační handler
			$this->user->setAuthorizationHandler($acl);
		}

		parent::startup();

		if($this->getPresenter()->getName() != 'Uzivatele' && $this->action != 'edit' && $this->user->isLoggedIn() && (trim($this->user->getIdentity()->getName()) == '' || intval($this->user->getIdentity()->id_sboru) == 0))
		{
			$this->flashMessage('Vyplňte údaje o sobě.', 'warning');
			$this->redirect('Uzivatele:edit', $this->user->getIdentity()->id);
		}
	}

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
	}

	private function renderMenu()
	{
		$stranky = new Stranky;
		$strankyDoMenu = $stranky->findAllToMenu()->fetchAll();

		$presenter = $this->getPresenter()->getName();
		$action = $this->getAction();
		$id = $this->getParam('id', 0);

		$this->template->menu = array(
		    array('odkaz' => 'Rocniky:', 'id' => NULL, 'nazev' => 'Soutěže', 'class' => 'souteze', 'aktivni' => $presenter == 'Rocniky' || $presenter == 'Zavody'),
		    array('odkaz' => 'Pravidla:', 'id' => NULL, 'nazev' => 'Pravidla', 'class' => 'pravidla', 'aktivni' => $presenter == 'Pravidla'),
		    array('odkaz' => 'Stranky:stranka', 'id' => 2, 'nazev' => 'Kronika', 'class' => 'kronika', 'aktivni' => $presenter == 'Stranky' && $action == 'stranka' && $id == 2),
		    array('odkaz' => 'Fotogalerie:', 'id' => NULL, 'nazev' => 'Fotogalerie', 'class' => 'fotogalerie', 'aktivni' => $presenter == 'Fotogalerie'),
		    array('odkaz' => 'Stranky:stranka', 'id' => 1, 'nazev' => 'Kontakt', 'class' => 'kontakt', 'aktivni' => $presenter == 'Stranky' && $action == 'stranka' && $id == 1),
		    array('odkaz' => 'Forum:', 'id' => NULL, 'nazev' => 'Fórum', 'class' => 'diskuze', 'aktivni' => $presenter == 'Forum' || $presenter == 'Diskuze'),
		);

		//$this->template->menu = array_merge($this->template->menu, $strankyDoMenu);
	}

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

	/**
	 * Vloží do formuláře možnost nahrát soubory
	 * @param AppForm $form
	 * @return unknown_type
	 */
	public function nahravaniSouboru(AppForm $form)
	{
		$form->addGroup('Nahrávání souborů');

		$form->addMultipleFileUpload('fileUpload', 'Nahrát soubory', 20)
		/* ->addRule("MultipleFileUpload::validateFilled", "Musíte odeslat alespoň jeden soubor!")
		  ->addRule("MultipleFileUpload::validateFileSize", "Soubory jsou dohromady moc veliké!",1024*1024) */;

		$form->onSubmit[] = array($this, 'pridaniFotekDoAlbaFormSubmitted');

		$form->onInvalidSubmit[] = array($this, "handlePrekresliForm");
		$form->onSubmit[] = array($this, "handlePrekresliForm");
	}

	public function handlePrekresliForm()
	{
		$this->invalidateControl("form");
	}

	public function vyslednyCas($cas)
	{
		if(strpos($cas, '1000') !== false) return 'NP';
		else return $cas;
	}

}
