<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
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
	public $rocnik = NULL;

	private $nadpis;
	public $celkove_vysledky = false;
	public $relativni = array( 'predchozi' => false, 'nasledujici' => false );

	public function __construct()
	{
		parent::__construct();

		if($this->getParam('presenter') == 'Zavody' && $this->getParam('id') !== NULL)
		{
			$zavod = $this->model->find($this->getParam('id'))->fetch();
			if($zavod) $this->setRocnik($zavod['rocnik']);
			else $this->setRocnik();
		}
	}

	/*public static function getPersistentParams()
	{
		return array('rocnik');
	}*/

	private function pripravZavody()
	{
		if($this->rocnik === NULL) $this->setRocnik();
		$zavody = $this->model = $this->presenter->context->zavody;
		$this->zavody = $zavody->findByRocnik( $this->rocnik )->fetchAll();
		foreach($this->zavody as $id => $zavod)
		{
			$datum_prihlasovani_od = date('Y-m-d H:i:s', strtotime('-14 days next monday', strtotime($zavod['datum'])));
			$datum_prihlasovani_do = date('Y-m-d 20:00:00', strtotime('-1 day', strtotime($zavod['datum'])));
			$this->zavody[$id]['lze_prihlasit'] = $zavod['zruseno'] == false && date('Y-m-d H:i:s') >= $datum_prihlasovani_od && date('Y-m-d H:i:s') < $datum_prihlasovani_do;
		}

		$vysledkyModel = $this->presenter->context->vysledky;
		if( count($vysledkyModel->findByRocnik($this->rocnik)->fetchAll()) != 0 )
		{
			$this->celkove_vysledky = true;
		}
	}

	public function setRocnik($id = NULL)
	{
		$rocnikyModel = $this->presenter->context->rocniky;
		if( $id === NULL )
		{
			$this->rocnik = (int)$rocnikyModel->findLast()->fetchSingle();
			$rocnik = $rocnikyModel->find($this->rocnik)->fetch();
			if( $rocnik['rok'] == date('Y') ) $this->nadpis = 'Aktuální ročník';
			//if( $rocnik['rok'] > date('Y') ) $this->nadpis = 'Následující ročník';
			else $this->nadpis = 'Ročník '.substr($rocnik['rok'], 0, 2).'**'.substr($rocnik['rok'], 2).'**';
		}
		else
		{
			$this->rocnik = $id;
			$rocnik = $rocnikyModel->find($this->rocnik)->fetch();
			$this->nadpis = 'Ročník '.substr($rocnik['rok'], 0, 2).'**'.substr($rocnik['rok'], 2).'**';
		}
		$this->relativni['predchozi'] = count($rocnikyModel->findPredchozi($this->rocnik)->fetchAll()) != 0;
		$this->relativni['nasledujici'] = count($rocnikyModel->findNasledujici($this->rocnik)->fetchAll()) != 0;
	}

	public function handlePredchozi()
	{
		$rocnikyModel = $this->presenter->context->rocniky;
		$rocnik = $rocnikyModel->findPredchozi($this->rocnik)->fetch();
		$this->setRocnik($rocnik['id']);

		$this->invalidateControl();
	}

	public function handleNasledujici()
	{
		$rocnikyModel = $this->presenter->context->rocniky;
		$rocnik = $rocnikyModel->findNasledujici($this->rocnik)->fetch();
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
