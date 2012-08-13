<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující jednotlivé fotografie
 *
 * @author	Milan Pála
  */
class FotkaControl extends Control
{
	private $model;
	public $fotka;

	private $NAHLED_DIR;
	private $VELKE_DIR;

	public function __construct()
	{
		$this->model = new Fotky;
		$this->NAHLED_DIR = APP_DIR.'/../data/nahled';
		$this->VELKE_DIR = APP_DIR.'/../data';
		parent::__construct();
	}

	public function render($fotka)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/fotka.phtml');

		$this->fotka = $fotka;
		if( $this->fotka !== false )
		{
			$soubor = $this->VELKE_DIR.$this->fotka['id'].'.'.$this->fotka['pripona'];

			if( !file_exists($soubor) ) throw new Exception('Fotografie neexistuje.');

			$rozmery = getimagesize($soubor);
			$this->fotka['sirka'] = $rozmery[0];
			$this->fotka['vyska'] = $rozmery[1];
			$this->fotka['muze_smazat'] = $this->getPresenter()->user->isAllowed('fotky', 'smazat') && ($this->parent->jeAutor($this->fotka['id_autora']));
		}
		$template->render();
	}

	public function renderVelky($fotka)
	{
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
				$soubor = APP_DIR.'/../data/'.$this->fotka['id'].'.'.$this->fotka['pripona'];

				if( !file_exists($soubor) ) throw new Exception('Fotografie neexistuje.');

				$rozmery = getimagesize($soubor);
				$noveRozmery = Image::calculateSize($rozmery[0], $rozmery[1], 380, 380);
				$this->fotka['sirka'] = $noveRozmery[0];
				$this->fotka['vyska'] = $noveRozmery[1];
				$this->fotka['muze_smazat'] = $this->getPresenter()->user->isAllowed('fotky', 'smazat') && ($this->parent->jeAutor($this->fotka['id_autora']));
			}
			$template->render();
		}
		catch(Exception $e)
		{
		}
	}

	public function renderNahled($fotka)
	{
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
			//Debug::processException($e);
		}

	}

	public function renderVyber($fotka)
	{
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
			//Debug::processException($e, true);
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

			$fotogalerie = new Galerie;
			$jednaGalerie = $fotogalerie->find($this->fotka['id_autora'])->fetch();
			$this->fotka['muze_smazat'] = $this->getPresenter()->user->isAllowed('fotky', 'smazat') && ($this->parent->jeAutor($this->fotka['id_autora']));
		}
	}

	/**
	 *
	 * @param type $id ID fotky
	 */
	public function renderProfilova($id)
	{
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
		$fotka = $this->model->find($id)->fetch();

		try
		{
			$this->model->delete($id);
			$this->parent->flashMessage('Fotka byla odstraněna.');
		}
		catch(DibiException $e)
		{
			$this->parent->flashMessage('Fotku se nepodařilo odstranit.', 'error');
			Debug::processException($e, true);
		}

		$this->parent->invalidateControl('flashes');
		$this->parent->invalidateControl('galerie');

		if(!$this->getPresenter()->isAjax()) $this->redirect('this');
	}
}