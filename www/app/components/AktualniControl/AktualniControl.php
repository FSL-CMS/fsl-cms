<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující aktuální závody a články
 *
 * @author	Milan Pála
  */
class AktualniControl extends BaseControl
{
	public function __construct()
	{
		parent::__construct();

		$mapa = new MapaControl($this, 'mapa');
		$souvisejici = new SouvisejiciControl($this, 'souvisejici');
	}

	public function render()
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/aktualni.phtml');

		$zavodyModel = new Zavody;

		$template->aktualniZavody = array();
		$template->aktualniZavody = $zavodyModel->findAktualni()->fetchAll();

		$template->polohy = array();

		foreach($template->aktualniZavody as $key => $zavod)
		{
			$template->aktualniZavody[$key]['datum_prihlasovani_od'] = date('Y-m-d H:i:s', strtotime('-2 weeks next monday', strtotime($template->aktualniZavody[$key]['datum'])));
			$template->aktualniZavody[$key]['datum_prihlasovani_do'] = date('Y-m-d H:i:s', strtotime('-1 day +20 hours', strtotime(substr($template->aktualniZavody[$key]['datum'], 0, 10))));
			if( strtotime($template->aktualniZavody[$key]['datum_prihlasovani_od']) > strtotime('now') ) $template->aktualniZavody[$key]['prihlasovani'] = 'bude';
			elseif( strtotime($template->aktualniZavody[$key]['datum_prihlasovani_do']) < strtotime('now') ) $template->aktualniZavody[$key]['prihlasovani'] = 'bylo';
			else $template->aktualniZavody[$key]['prihlasovani'] = 'je';

			$template->polohy[] = array('sirka' => $zavod['sirka'], 'delka' => $zavod['delka'], 'nazev' => $zavod['nazev']);
		}

		if( $this->getPresenter()->getName() == 'Homepage' && $this->getPresenter()->getAction() == 'default' ) $template->render();
	}

}
