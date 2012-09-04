<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter výsledků
 *
 * @author	Milan Pála
 */
class VysledkyPresenter extends BasePresenter
{

	public function renderVysledek($id)
	{
		$vysledky = new Vysledky;
		$this->template->vysledky = $vysledky->findByZavod($id)->fetchAssoc('kategorie,id,=');
		
		foreach( $this->template->vysledky as $kategorie => $foo )
		{
			$i = 1;
			foreach( $foo as $vysledkyKategorie => $bar )
			{
				$this->template->vysledky[$kategorie][$vysledkyKategorie]['poradi'] = $i++;
			}
		}

		$this->template->nasledujici = $zavody->findNext($id)->fetch();
          $this->template->predchozi = $zavody->findPrevious($id)->fetch();
  	}

}
