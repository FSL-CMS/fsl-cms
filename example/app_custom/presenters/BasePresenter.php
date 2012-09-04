<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Bázový presenter
 * Obsahuje vlastní rozšíření nad kódem v CommonBasePresenteru.
 *
 * @author	Milan Pála
 */
abstract class BasePresenter extends CommonBasePresenter
{
	/**
	 * Vykreslení odkazů v menu
	 */
	protected function renderMenu()
	{
		$presenter = $this->getPresenter()->getName();
		$action = $this->getAction();
		$id = $this->getParam('id', 0);

		$this->template->menu = array(
		    array('odkaz' => 'Rocniky:', 'id' => NULL, 'nazev' => 'Soutěže', 'class' => 'souteze', 'aktivni' => $presenter == 'Rocniky' || $presenter == 'Zavody'),
		    array('odkaz' => 'Pravidla:', 'id' => NULL, 'nazev' => 'Pravidla', 'class' => 'pravidla', 'aktivni' => $presenter == 'Pravidla'),
		    array('odkaz' => 'Stranky:stranka', 'id' => 2, 'nazev' => 'Kronika', 'class' => 'kronika', 'aktivni' => $presenter == 'Stranky' && $action == 'stranka' && $id == 2),
		    array('odkaz' => 'Galerie:', 'id' => NULL, 'nazev' => 'Galerie', 'class' => 'galerie', 'aktivni' => $presenter == 'Galerie'),
		    array('odkaz' => 'Stranky:stranka', 'id' => 1, 'nazev' => 'Kontakt', 'class' => 'kontakt', 'aktivni' => $presenter == 'Stranky' && $action == 'stranka' && $id == 1),
		    array('odkaz' => 'Forum:', 'id' => NULL, 'nazev' => 'Fórum', 'class' => 'diskuze', 'aktivni' => $presenter == 'Forum' || $presenter == 'Diskuze'),
		);
	}
}
