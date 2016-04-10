<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Image;
use Nette\Diagnostics\Debugger;

/**
 * Komponenta vykreslující jednotlivé fotografie
 *
 * @author	Milan Pála
  */
class FotkaControl extends BaseControl
{
	private $model;
	public $fotka;

	private $NAHLED_DIR;
	private $VELKE_DIR;

	public function __construct()
	{
		parent::__construct();

		$this->NAHLED_DIR = DATA_DIR . '/nahled';
		$this->VELKE_DIR = DATA_DIR;
	}

	public function render($fotka)
	{
		$this->model = $this->presenter->context->fotky;

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/fotka.phtml');

		$this->fotka = $fotka;

		if(is_int($this->fotka))
		{
			$this->fotka = $this->model->find($this->fotka)->fetch();
		}

		if( $this->fotka !== false )
		{
			$soubor = $this->VELKE_DIR.'/'.$this->fotka['id'].'.'.$this->fotka['pripona'];

			if( !file_exists($soubor) ) return;

			$rozmery = getimagesize($soubor);
			$this->fotka['sirka'] = $rozmery[0];
			$this->fotka['vyska'] = $rozmery[1];
			$this->fotka['muze_smazat'] = $this->getPresenter()->user->isAllowed('fotky', 'smazat') && ($this->getPresenter()->jeAutor($this->fotka['id_autora']));
		}
		$template->render();
	}

	public function renderVelky($fotka)
	{
		$this->model = $this->presenter->context->fotky;

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/velky.phtml');

		$this->fotka = $fotka;

		if( $this->fotka !== false && !($this->fotka instanceof DibiRow) && !is_array($this->fotka) && intval($this->fotka) == $this->fotka )
		{
			$this->fotka = $this->model->find($this->fotka)->fetch();
		}

		try
		{
			if( $this->fotka !== false )
			{
				$soubor = $this->VELKE_DIR.'/'.$this->fotka['id'].'.'.$this->fotka['pripona'];

				if( !file_exists($soubor) ) return;

				$rozmery = getimagesize($soubor);
				$noveRozmery = \Nette\Image::calculateSize($rozmery[0], $rozmery[1], 380, 380);
				$this->fotka['sirka'] = $noveRozmery[0];
				$this->fotka['vyska'] = $noveRozmery[1];
				$this->fotka['muze_smazat'] = $this->getPresenter()->user->isAllowed('fotky', 'smazat') && ($this->getPresenter()->jeAutor($this->fotka['id_autora']));
			}
			$template->render();
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	public function renderNahled($fotka)
	{
		$this->model = $this->presenter->context->fotky;
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/nahled.phtml');

		$this->fotka = $fotka;
		try
		{
			$this->pripravFotku();
			$template->render();
		}
		catch(Exception $e)
		{
			Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

	}

	public function renderSablonaclanku($fotka)
	{
		$this->model = $this->presenter->context->fotky;
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/sablonaclanku.phtml');

		$this->fotka = $fotka;
		try
		{
			$this->pripravFotku();
			$template->render();
		}
		catch(Exception $e)
		{
			//Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

	}

	public function renderVyber($fotka)
	{
		$this->model = $this->presenter->context->fotky;
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/vyber.phtml');

		$this->fotka = $fotka;
		try
		{
			$this->pripravFotku();
			$template->render();
		}
		catch(Exception $e)
		{
			//Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

	}

	private function vytvorNahled()
	{
		$fotka = Image::fromFile($this->VELKE_DIR.'/'.$this->fotka['id'].'.'.$this->fotka['pripona']);
		$fotka->resize(180, 135);
		self::vytvor_cestu($this->NAHLED_DIR.'/');
		$fotka->save($this->NAHLED_DIR.'/'.$this->fotka['id'].'.'.$this->fotka['pripona']);
	}

	public static function vytvor_cestu( $pathname, $mode = 0755 )
	{
		//echo 'Kontroluji cestu '.$pathname;
		if( !preg_match( '~.+/$~', $pathname ) ) $pathname = dirname( $pathname ).'/';
		is_dir($pathname) || self::vytvor_cestu(dirname($pathname).'/', $mode);
		return is_dir($pathname) || @mkdir($pathname, $mode);
	}

	private function pripravFotku()
	{
		$this->model = $this->presenter->context->fotky;

		if( $this->fotka !== false && !($this->fotka instanceof DibiRow) && !is_array($this->fotka) && intval($this->fotka) == $this->fotka )
		{
			$this->fotka = $this->model->find($this->fotka)->fetch();
		}

		if($this->fotka !== false)
		{
			$soubor = $this->NAHLED_DIR.'/'.$this->fotka['id'].'.'.$this->fotka['pripona'];
			if( !file_exists($soubor) )
			{
				$this->vytvorNahled();
			}
			if( !file_exists($soubor) ) throw new Exception('Fotografie neexistuje.');

			$rozmery = getimagesize($soubor);
			$this->fotka['sirka'] = $rozmery[0];
			$this->fotka['vyska'] = $rozmery[1];

			/*$fotogalerie = $this->presenter->context->galerie;
			$jednaGalerie = $fotogalerie->find($this->fotka['id_autora'])->fetch();*/
			$this->fotka['muze_smazat'] = $this->getPresenter()->user->isAllowed('fotky', 'smazat') && ($this->getPresenter()->jeAutor($this->fotka['id_autora']));
		}
	}

	/**
	 *
	 * @param type $id ID fotky
	 */
	public function renderProfilova($id)
	{
		$this->model = $this->presenter->context->fotky;
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/profilova.phtml');

		$this->fotka = $this->model->find($id)->fetch();
		try
		{
			$this->pripravFotku();
			$template->render();
		}
		catch(Exception $e)
		{
			//Debug::processException($e);
		}
	}

	public function handleSmazat($id)
	{
          $this->model = $this->presenter->context->fotky;
		try
		{
               /*$fotka = $this->model->find($id);
			if( $fotka === false) throw new DibiException('NotFound');
			else $fotka = $fotka->fetch();*/
			$this->model->delete($id);
			$this->getPresenter()->flashMessage('Fotka byla odstraněna.');
		}
		catch(DibiException $e)
		{
			$this->getPresenter()->flashMessage('Fotku se nepodařilo odstranit.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

		$this->getPresenter()->invalidateControl('flashes');
		$this->getPresenter()->invalidateControl('galerie');

		if(!$this->getPresenter()->isAjax()) $this->redirect('this');
	}
}
