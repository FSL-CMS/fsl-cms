<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
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
	}

	public function render()
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/aktualni.phtml');

		$zavodyModel = $this->presenter->context->zavody;

		$template->aktualniZavody = array();
		$template->aktualniZavody = $zavodyModel->findAktualni()->fetchAll();

		$template->polohy = array();

		foreach($template->aktualniZavody as $key => $zavod)
		{
			$template->aktualniZavody[$key]['datum_prihlasovani_od'] = $template->aktualniZavody[$key]['prihlasovani_od'];
			$template->aktualniZavody[$key]['datum_prihlasovani_do'] = $template->aktualniZavody[$key]['prihlasovani_do'];
			if( strtotime($template->aktualniZavody[$key]['datum_prihlasovani_od']) > strtotime('now') ) $template->aktualniZavody[$key]['prihlasovani'] = 'bude';
			elseif( strtotime($template->aktualniZavody[$key]['datum_prihlasovani_do']) < strtotime('now') ) $template->aktualniZavody[$key]['prihlasovani'] = 'bylo';
			else $template->aktualniZavody[$key]['prihlasovani'] = 'je';

			$template->polohy[] = array('sirka' => $zavod['sirka'], 'delka' => $zavod['delka'], 'nazev' => $zavod['nazev']);
		}

		if( $this->getPresenter() instanceof HomepagePresenter && $this->getPresenter()->getAction() == 'default' ) $template->render();
	}

}
