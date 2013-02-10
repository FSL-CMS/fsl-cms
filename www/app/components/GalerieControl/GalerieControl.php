<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Komponenta vykreslující galerie
 *
 * @author	Milan Pála
 */
class GalerieControl extends BaseControl
{
	public function createComponentFotka()
	{
		return new FotkaControl();
	}

	public function render()
	{
		$this->renderNahled();
	}

	/**
	 *
	 * @param int $id ID galerie
	 */
	public function renderNahled($id)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/nahled.phtml');

		$galerieModel = $this->presenter->context->galerie;
		$template->galerie = $galerieModel->find($id)->fetch();

		$fotkyModel = $this->presenter->context->fotky;
		$template->fotky = array();
		$template->fotky['fotky'] = $fotkyModel->findByGalerie($id)->fetchAll();
		$i = 0;
		foreach ($template->fotky['fotky'] as $key => &$fotka)
		{
			if($i >= 3) { unset($this->template->fotky['fotky'][$key]); continue; }
			if(!file_exists(DATA_DIR . '/' . $fotka['id'] . '.' . $fotka['pripona']))
			{
				unset($this->template->fotky['fotky'][$key]);
				continue;
			}
			$rozmery = getimagesize(DATA_DIR . '/' . $fotka['id'] . '.' . $fotka['pripona']);
			$fotka['sirka'] = $rozmery[0];
			$fotka['vyska'] = $rozmery[1];
			$i++;
		}

		$template->render();
	}

}
