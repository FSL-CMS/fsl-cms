<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující celkové pořadí
 *
 * @author	Milan Pála
  */
class PoradiControl extends BaseControl
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function render()
	{
		$vysledky = new Vysledky;
		$rocniky = new Rocniky;
		
		$idPoslednihoRocniku = $rocniky->findLast()->fetchSingle();
		$vysledkyPoslednihoRocniku = $vysledky->findByRocnik($idPoslednihoRocniku)->fetchAssoc('soutez,kategorie,id_druzstva,=');

		foreach( $vysledkyPoslednihoRocniku as $soutez => $bar)
		{
			foreach( $bar as $kategorie => $foo )
			{
				usort( $vysledkyPoslednihoRocniku[$soutez][$kategorie], array($this->getPresenter(), "orderVysledky") );
			}
		}
		
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/poradi.phtml');
		$template->vysledky = array();
		foreach( $vysledkyPoslednihoRocniku as $soutez => $bar)
		{
			$template->vysledky[$soutez] = array();
			foreach( $bar as $kategorie => $foo )
			{
				$template->vysledky[$soutez][$kategorie] = array();
				$i = 1;
				foreach( $foo as $vysledkyKategorie => $foobar )
				{
					$template->vysledky[$soutez][$kategorie][$vysledkyKategorie] = $vysledkyPoslednihoRocniku[$soutez][$kategorie][$vysledkyKategorie];
					$template->vysledky[$soutez][$kategorie][$vysledkyKategorie]['poradi'] = $i++;
					if($i == 4) break;
				}
			}		
		}		

		$template->render();
	}

}
