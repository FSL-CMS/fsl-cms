<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter správy
 *
 * @author	Milan Pála
 */
class SpravaPresenter extends BasePresenter
{

	public function startup()
	{
		parent::startup();

		if( $this->view != 'login' && ($this->user === NULL || !$this->user->isLoggedIn()) )
		{
			$this->forward('Sprava:login', array('backlink' => $this->getApplication()->storeRequest()) );
		}
	}

	public function actionDefault()
	{
		if( !$this->user->isAllowed('sprava', 'view') ) $this->redirect('Uzivatele:edit', $this->user->getIdentity()->id);
	}

	public function renderDefault()
	{
		$this->setTitle('Správa webu');
	}

	public function actionKontrola()
	{
		if( !$this->user->isAllowed('sprava', 'edit') ) throw new RestrictionException('Nemáte dostatečná oprávnění.');
	}

	public function renderKontrola()
	{
		$zavody = $this->context->zavody;
		$rocniky = $this->context->rocniky;

		$this->setTitle('Kontrola správně zadaných údajů pro chod webu');

		$this->template->zavody = array();
		$this->template->zavody['muze_editovat'] = $this->user->isAllowed('zavody', 'edit');

		$posledni_rocnik = $rocniky->find($rocniky->findLast()->fetchSingle())->fetch();

		$this->template->existujeLetosniRocnik = $posledni_rocnik['rok'] >= date('Y');

		$this->template->zavodyRocniku = array();
		if( $this->template->existujeLetosniRocnik == true ) $this->template->zavodyRocniku = $zavody->findByRocnik($posledni_rocnik['id']);

		$zavody_bez_kontaktu = array();
		$this->template->zavodyBezKontaktu = array();
		if( $this->template->existujeLetosniRocnik == true )
		{
			$zavody_bez_kontaktu = $zavody->findZavodyBezKontaktu($posledni_rocnik['id'])->fetchAll();
		}
		$this->template->zavodyBezKontaktu['zavody'] = $zavody_bez_kontaktu;
		$this->template->zavodyBezKontaktu += $this->template->zavody;

		$zavodyBezSP = array();
		$this->template->zavodyBezSP = array();
		if( $this->template->existujeLetosniRocnik == true )
		{
			$zavodyBezSP = $zavody->findZavodyBezSP($posledni_rocnik['id'])->fetchAll();
		}
		$this->template->zavodyBezSP['zavody'] = $zavodyBezSP;
		$this->template->zavodyBezSP += $this->template->zavody;

		$zavodyBezVysledku = array();
		$this->template->zavodyBezVysledku = array();
		if( $this->template->existujeLetosniRocnik == true )
		{
			$zavodyBezVysledku = $zavody->findZavodyBezVysledku($posledni_rocnik['id'])->fetchAll();
		}
		$this->template->zavodyBezVysledku['zavody'] = $zavodyBezVysledku;
		$this->template->zavodyBezVysledku += $this->template->zavody;
	}

	/**
	 * Vygeneruje požadavek na přihlášení uživatele
	 * @param string $backlink je storeRequest stránky, která požaduje přihlášení
	 */
	public function renderLogin($backlink = '')
	{
		$this['auth']->backlink = $backlink;
		$this->setTitle('Přihlášení uživatele');
	}

	public function actionUdrzba()
	{
		if( !$this->user->isAllowed('sprava', 'edit') ) throw new RestrictionException('Nemáte dostatečná oprávnění.');

		try
		{
			$zavody = $this->context->zavody;
			$zavody->udrzba();

			$clanky = $this->context->clanky;
			$clanky->udrzba();

			$sbory = $this->context->sbory;
			$sbory->udrzba();

			$druzstva = $this->context->druzstva;
			$druzstva->udrzba();

			$uzivatele = $this->context->uzivatele;
			$uzivatele->udrzba();

			$temata = $this->context->temata;
			$temata->udrzba();

			$diskuze = $this->context->diskuze;
			$diskuze->udrzba();

			$terceModel = $this->context->terce;
			$terceModel->udrzba();

			$fotkyModel = $this->context->fotky;
			$fotkyModel->udrzba();

			/*$fotogalerie = new Fotogalerie;
			$fotky = new Fotky;
			$vsechnyFotogalerie = $fotogalerie->findAll();
			foreach( $vsechnyFotogalerie as $data )
			{
				$dataDoDB = array( 'nazev' => $data['nazev'] );
				$fotogalerie->update($data['id'], $dataDoDB);

				$vsechnyFotky = $fotky->findAll($data['id']);
				foreach( $vsechnyFotky as $data )
				{
					$dataDoDB = array( 'soubor' => $data['soubor'], 'pripona' => $data['pripona'], 'id_souvisejiciho' => $data['id_fotogalerie'] );
					$fotky->update($data['id'], $dataDoDB);
				}
			}*/


			$this->flashMessage('Údržba webu byla provedena.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Při údržbě došlo k chybě.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('default');
	}

	public function actionPrevodSportovist()
	{
		try
		{
			$this->flashMessage('Začíná převod.', 'ok');
			$mistaModel = $this->context->mista;
			$sportovisteModel = $this->context->sportoviste;

			$mista = $mistaModel->findAll()->orderBy(NULL)->orderBy('[mista].[id]');

			dibi::query('TRUNCATE TABLE sportoviste');

			foreach($mista as $misto)
			{
				$sportovisteModel->insert(array('id_mista' => $misto->id));
			}

			$zavodyModel = $this->context->zavody;
			$zavody = $zavodyModel->findAll();
			foreach($zavody as $zavod)
			{
				dibi::query('UPDATE zavody SET id_mista = (SELECT id FROM sportoviste WHERE sportoviste.id_mista = (SELECT id_mista FROM sbory WHERE id = %i)) WHERE [id] = %i;', $zavod->id_poradatele, $zavod->id);
			}
			$this->flashMessage('Převod proběhl v pořádku.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Převod se nezdařil', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('Clanky:default');
	}

	public function actionPrevodSoutezi()
	{
		try
		{
			$this->flashMessage('Začíná převod.', 'ok');
			$vysledkyModel = $this->context->vysledky;
			$ucastiModel = $this->context->ucasti;
			dibi::query('truncate table ucasti');
			$vysledky = $vysledkyModel->findAll();
			//print_r($vysledky->fetchAll());
			foreach($vysledky as $vysledek)
			{
					//print_r($vysledek);
					$id_kategorie =  dibi::fetchSingle('SELECT id_kategorie FROM druzstva WHERE id = '.$vysledek['id_druzstva']);
					$pocet_mist =  dibi::fetchSingle('SELECT pocet_startovnich_mist FROM kategorie WHERE id = '.$id_kategorie);
					$id_bodove_tabulky = ($id_kategorie == 1 ? 1 : 2);
					$id_ucasti = dibi::fetchSingle("SELECT id FROM ucasti WHERE %and", array('id_zavodu%i' => $vysledek['id_zavodu'], 'id_kategorie%i' => $id_kategorie, 'id_souteze' => 1));
					//var_dump($id_ucasti);
					//echo "\n";
					if( $id_ucasti === false )
					{
						dibi::query('INSERT INTO ucasti %v ON DUPLICATE KEY UPDATE [datum] = 0', array('id_zavodu%i' => $vysledek['id_zavodu'], 'id_kategorie%i' => $id_kategorie, 'id_bodove_tabulky%i' => $id_bodove_tabulky, 'id_souteze%i' => 1, 'pocet%i' => $pocet_mist));
						//$id_ucasti = (mysql_affected_rows() == 1 ? mysql_insert_id() : dibi::query("SELECT id FROM ucasti WHERE %and", array('id_zavodu%i' => $vysledek['id_zavodu'], 'id_kategorie%i' => $id_kategorie, 'id_souteze' => 1))->fetchSingle());
						//echo dibi::$sql."\n";
						//$id_ucasti = mysql_insert_id();
						$id_ucasti = dibi::fetchSingle("SELECT id FROM ucasti WHERE %and", array('id_zavodu%i' => $vysledek['id_zavodu'], 'id_kategorie%i' => $id_kategorie, 'id_souteze' => 1));
					}
					//var_dump($id_ucasti);
					$vysledkyModel->update($vysledek['id'], array('id_ucasti%i' => $id_ucasti));
			}

			//dibi::query('UPDATE startovani_poradi SET id_ucasti = (SELECT )');

			$this->flashMessage('Převod proběhl v pořádku.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Převod se nezdařil', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('Zavody:default');
	}

	public function actionPrevodUrl()
	{
		$urlsModel = $this->context->urls;

		$rocnikyModel = $this->context->rocniky;
		$rocniky = $rocnikyModel->findAll();
		foreach($rocniky as $rocnik)
		{
			$urlsModel->setUrl('Rocniky', 'rocnik', $rocnik['id'], '/rocniky/'.$rocnik['id']);
			$urlsModel->setUrl('Rocniky', 'vysledky', $rocnik['id'], '/rocniky/vysledky/'.$rocnik['id']);
		}
		$rocnikyModel = null;
		$rocniky = null;

		$zavodyModel = $this->context->zavody;
		$zavody = $zavodyModel->findAll();
		foreach($zavody as $zavod)
		{
			$urlsModel->setUrl('Zavody', 'zavod', $zavod['id'], '/'.$zavod['stare_uri']);
			$urlsModel->setUrl('Zavody', 'zavod', $zavod['id'], '/zavody/'.$zavod['uri'].'.html');
		}
		$zavodyModel = null;
		$zavody = null;

		$clankyModel = $this->context->clanky;
		$clanky = $clankyModel->findAll();
		foreach($clanky as $clanek)
		{
			$urlsModel->setUrl('Clanky', 'clanek', $clanek['id'], '/'.$clanek['stare_uri']); // pro KL
			$urlsModel->setUrl('Clanky', 'clanek', $clanek['id'], '/clanky/'.$clanek['uri'].'.html');
		}
		$clankyModel = null;
		$clanky = null;

		$terceModel = $this->context->terce;
		$terce = $terceModel->findAll();
		foreach($terce as $terc)
		{
			$urlsModel->setUrl('Terce', 'terce', $terc['id'], '/terce/'.$terc['uri']);
		}
		unset($terceModel);
		unset($terce);

		$diskuzeModel = $this->context->diskuze;
		$diskuze = $diskuzeModel->findAll();
		foreach($diskuze as $disk)
		{
			$urlsModel->setUrl('Diskuze', 'diskuze', $disk['id'], '/forum/'.$disk['uri'].'.html');
		}

		$sboryModel = $this->context->sbory;
		$sbory = $sboryModel->findAll();
		foreach($sbory as $sbor)
		{
			$urlsModel->setUrl('Sbory', 'sbor', $sbor['id'], '/sbory/'.$sbor['uri'].'.html');
		}

		$druzstvaModel = $this->context->druzstva;
		$druzstva = $druzstvaModel->findAll();
		foreach($druzstva as $druzstvo)
		{
			$urlsModel->setUrl('Druzstva', 'druzstvo', $druzstvo['id'], '/druzstva/'.$druzstvo['uri'].'.html');
		}

		$strankyModel = $this->context->stranky;
		$stranky = $strankyModel->findAll();
		foreach($stranky as $stranka)
		{
			$urlsModel->setUrl('Stranky', 'stranka', $stranka['id'], '/'.$stranka['uri'].'.html');
		}

		$fotogalerieModel = $this->context->fotogalerie;
		$fotogalerie = $fotogalerieModel->findAll();
		foreach($fotogalerie as $fotog)
		{
			$urlsModel->setUrl('Fotogalerie', 'fotogalerie', $fotog['id'], '/fotogalerie/'.$fotog['uri'].'/');
		}

		/*$souboryModel = new Soubory;
		$soubory = $souboryModel->findAll();
		foreach($soubory as $soubor)
		{
			$urlsModel->setUrl('Soubory', 'soubor', $soubor['id'], $soubor['uri']);
		}*/

		$temataModel = $this->context->temata;
		$temata = $temataModel->findAll();
		foreach($temata as $tema)
		{
			$urlsModel->setUrl('Forum', 'forum', $tema['id'], '/forum/'.$tema['uri'].'/');
		}

		$uzivateleModel = $this->context->uzivatele;
		$uzivatele = $uzivateleModel->findAll();
		foreach($uzivatele as $uzivatel)
		{
			$urlsModel->setUrl('Uzivatele', 'uzivatel', $uzivatel['id'], '/uzivatele/'.$uzivatel['uri'].'.html');
		}

		$this->flashMessage('Převod URL proběhl v pořádku', 'ok');

		$this->redirect('Sprava:');
	}

	public function actionOpravaUrlFotek()
	{
		try
		{
			$souboryModel = $this->context->fotky;
			$soubory = $souboryModel->findAll();
			foreach($soubory as $soubor)
			{
				//$urlsModel->setUrl('Soubory', 'soubor', $soubor['id'], $soubor['uri']);
				dibi::update('urls', array('url' => '/fotogalerie/'.$soubor['uri']))->where(array('presenter' => 'Fotky', 'param' => $soubor['id']))->execute();
			}
			$this->flashMessage('Převod URL proběhl v pořádku', 'ok');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Převod URL neproběhl v pořádku', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		$this->redirect('default');
	}

	public function actionAktualizaceDB()
	{
		$verzeDB = dibi::fetchSingle('SELECT verze FROM verze LIMIT 1');

		if($verzeDB == VERZE_DB)
		{
			$this->flashMessage('Verze databází byly shodné.', 'ok');
			$this->redirect('default');
		}

		$aktualizaceDBModel = $this->context->aktualizaceDB;
		try
		{
			dibi::begin();
			$aktualizaceDBModel->aktualizuj($verzeDB, VERZE_DB);
			dibi::commit();
			$this->flashMessage('Databáze byla aktualizovaná.', 'ok');
			$this->redirect('default');
		}
		catch(DibiException $e)
		{
			dibi::rollback();
			$this->flashMessage('Databázi se nepodařilo aktualizovat.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			$this->redirect('default');
		}
	}

}
