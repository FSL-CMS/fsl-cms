<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující přehled závodů do postraního menu
 *
 * @author	Milan Pála
  */
class SoutezeControl extends BaseControl
{
	private $zavody;
	private $model;
	public $rocnik;

	private $nadpis;
	public $celkove_vysledky = false;
	public $relativni = array( 'predchozi' => false, 'nasledujici' => false );

	public function __construct()
	{
		$this->model = new Zavody;
		if($this->getParam('presenter') == 'Zavody' && $this->getParam('id') !== NULL)
		{
			$zavod = $this->model->find($this->getParam('id'))->fetch();
			if($zavod) $this->setRocnik($zavod['rocnik']);
			else $this->setRocnik();
		}
		else $this->setRocnik();

		parent::__construct();
	}

	/*public static function getPersistentParams()
	{
		return array('rocnik');
	}*/

	private function pripravZavody()
	{
		$zavody = new Zavody;
		$this->zavody = $zavody->findByRocnik( $this->rocnik )->fetchAll();
		foreach($this->zavody as $id => $zavod)
		{
			$datum_prihlasovani_od = date('Y-m-d H:i:s', strtotime('-14 days next monday', strtotime($zavod['datum'])));
			$datum_prihlasovani_do = date('Y-m-d 20:00:00', strtotime('-1 day', strtotime($zavod['datum'])));
			$this->zavody[$id]['lze_prihlasit'] = $zavod['zruseno'] == false && date('Y-m-d H:i:s') >= $datum_prihlasovani_od && date('Y-m-d H:i:s') < $datum_prihlasovani_do;
		}

		$vysledky = new Vysledky;
		if( count($vysledky->findByRocnik($this->rocnik)->fetchAll()) != 0 )
		{
			$this->celkove_vysledky = true;
		}
	}

	public function setRocnik($id = NULL)
	{
		$rocniky = new Rocniky;
		if( $id === NULL )
		{
			$this->rocnik = (int)$rocniky->findLast()->fetchSingle();
			$rocnik = $rocniky->find($this->rocnik)->fetch();
			if( $rocnik['rok'] == date('Y') ) $this->nadpis = 'Aktuální ročník';
			//if( $rocnik['rok'] > date('Y') ) $this->nadpis = 'Následující ročník';
			else $this->nadpis = 'Ročník '.substr($rocnik['rok'], 0, 2).'**'.substr($rocnik['rok'], 2).'**';
		}
		else
		{
			$this->rocnik = $id;
			$rocnik = $rocniky->find($this->rocnik)->fetch();
			$this->nadpis = 'Ročník '.substr($rocnik['rok'], 0, 2).'**'.substr($rocnik['rok'], 2).'**';
		}
		$this->relativni['predchozi'] = count($rocniky->findPredchozi($this->rocnik)->fetchAll()) != 0;
		$this->relativni['nasledujici'] = count($rocniky->findNasledujici($this->rocnik)->fetchAll()) != 0;
	}

	public function handlePredchozi()
	{
		$rocniky = new Rocniky;
		$rocnik = $rocniky->findPredchozi($this->rocnik)->fetch();
		$this->setRocnik($rocnik['id']);

		$this->invalidateControl();
	}

	public function handleNasledujici()
	{
		$rocniky = new Rocniky;
		$rocnik = $rocniky->findNasledujici($this->rocnik)->fetch();
		$this->setRocnik($rocnik['id']);

		$this->invalidateControl();
	}

	public function render()
	{
		$this->pripravZavody();

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/souteze.phtml');
		$template->zavody = $this->zavody;
		$template->nadpis = $this->nadpis;
		$template->celkove_vysledky = $this->celkove_vysledky;
		if( $this->rocnik != 0 ) $template->render();
	}

}
