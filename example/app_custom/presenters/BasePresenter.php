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
abstract class BasePresenter extends CommonBasePresenter
{

	protected function renderMenu()
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

}
