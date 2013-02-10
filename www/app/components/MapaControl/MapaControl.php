<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Komponenta vykreslující mapy
 *
 * @author	Milan Pála
 */
class MapaControl extends BaseControl
{

	protected $render;

	public function __construct()
	{
		parent::__construct();
		$this->render = 'mapa';
	}

	public function renderMapa($atributy)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/mapa.phtml');

		$template->body = array();
		$template->stred = array('sirka' => 0, 'delka' => 0);
		foreach ($atributy as $bod)
		{
			if(isset($bod['sirka']) && $bod['sirka'] != 0)
			{
				$template->body[] = array('sirka' => (int) $bod['sirka'], 'delka' => (int) $bod['delka'], 'id' => 'marker' . (int) $bod['sirka'], 'nazev' => $bod['nazev']);
				$template->stred['sirka'] = $bod['sirka'];
				$template->stred['delka'] = $bod['delka'];
			}
			$template->nazev = $bod['nazev'];
		}
		if($template->stred['sirka'] != 0 && $template->stred['delka'] != 0) $template->render();
	}

	public function render($atributy)
	{
		$this->renderMapa($atributy);
	}

	public function renderPrehledZavodu($atributy)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/prehledZavodu.phtml');

		$template->body = array();
		$template->stred = array();
		foreach ($atributy as $bod)
		{
			if($bod['sirka'] != 0)
			{
				$template->body[] = array('sirka' => (int) $bod['sirka'], 'delka' => (int) $bod['delka'], 'id' => 'marker' . (int) $bod['sirka'], 'nazev' => $bod['nazev']);
				$template->stred['sirka'] = $bod['sirka'];
				$template->stred['delka'] = $bod['delka'];
			}
		}
		if($template->stred['sirka'] != 0 && $template->stred['delka'] != 0) $template->render();
	}

	public function renderMalaMapa($atributy)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/malaMapa.phtml');

		$template->id = rand(1000, 9999);

		$template->body = array();
		$template->stred = array('sirka' => 0, 'delka' => 0);
		foreach ($atributy as $bod)
		{
			if(isset($bod['sirka']) && $bod['sirka'] != 0)
			{
				$template->body[] = array('sirka' => (int) $bod['sirka'], 'delka' => (int) $bod['delka'], 'id' => 'marker' . (int) $bod['sirka'], 'nazev' => $bod['nazev']);
				$template->stred['sirka'] = $bod['sirka'];
				$template->stred['delka'] = $bod['delka'];
			}
		}
		if($template->stred['sirka'] != 0 && $template->stred['delka'] != 0) $template->render();
	}

}
