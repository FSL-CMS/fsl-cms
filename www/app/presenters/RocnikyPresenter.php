<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Presenter ročníků soutěží
 *
 * @author	Milan Pála
 */
class RocnikyPresenter extends BasePresenter
{

	/** @persistent */
	public $backlink = '';
	protected $model = NULL;

	protected function startup()
	{
		parent::startup();
		$this->model = new Rocniky;
		if($this->user->isAllowed('rocniky', 'edit')) $this->model->zobrazitNezverejnene();
	}

	/**
	 * Přesměruje na aktuální ročník
	 */
	public function actionDefault()
	{
		$posledni = $this->model->findLast();

		// Neexistuje zatím žádný ročník
		if($posledni->count() == 0)
		{
			$this->flashMessage('Žádné ročníky nebyly vytvořeny.', 'warning');
			$this->redirect('Rocniky:add');
		}

		$this->redirect('rocnik', $posledni->fetchSingle());
	}

	public function actionRocnik($id)
	{
		if($this->model->find($id)->count() == 0) throw new BadRequestException('Nebyl nalezen požadovaný ročník.');
	}

	public function actionAktualniVysledky()
	{
		$this->redirect('Rocniky:vysledky', $this->model->findLast()->fetchSingle());
	}

	public function actionVysledky($id)
	{
		if($this->model->find($id)->count() == 0) throw new BadRequestException('Nebyl nalezen požadovaný ročník.');
	}

	public function beforeRender()
	{
		$this->template->rocniky = array();
		$this->template->rocniky['rocniky'] = $this->model->findAll();
		$this->template->rocniky['muze_editovat'] = $this->user->isAllowed('rocniky', 'edit');
		$this->template->rocniky['muze_smazat'] = $this->user->isAllowed('rocniky', 'delete');
		$this->template->rocniky['muze_pridat'] = $this->user->isAllowed('rocniky', 'add');

		parent::beforeRender();
	}

	public function actionAdd()
	{
		if(!$this->user->isAllowed('rocniky', 'add')) throw new ForbiddenRequestException('Nemáte oprávnění přidat nový ročník.');

		$this->setView('edit');
	}

	/**
	 * Výpis závodů v jednom ročníku
	 * @param int $id ID ročníku
	 */
	public function renderRocnik($id)
	{
		$this->template->rocnik = $this->model->find($id)->fetch();

		$zavody = new Zavody;
		if($this->user->isAllowed('rocniky', 'edit')) $zavody->zobrazitNezverejnene();
		$this->template->zavody = array();
		$this->template->zavody['zavody'] = $zavody->findByRocnik($id)->fetchAll();
		$this->template->zavody['sprava'] = false;
		$this->template->zavody['pozice'] = array();
		foreach ($this->template->zavody['zavody'] as &$zavod)
		{
			$zavod['muze_editovat'] = $this->user->isAllowed('zavody', 'edit');
			$this->template->zavody['sprava'] |= $zavod['muze_editovat'];
			$this->template->zavody['pozice'][] = array('sirka' => $zavod['sirka'], 'delka' => $zavod['delka'], 'nazev' => $zavod['nazev'], 'odkaz' => $this->link('Zavody:zavod', $zavod['id']));
		}

		$this->template->zavody['muze_editovat'] = $this->user->isAllowed('zavody', 'edit');

		$this->setTitle('Závody ' . $this->template->rocnik['rocnik'] . '. ročníku');
	}

	public function renderEdit($id = 0)
	{
		$soutezeRocniku = new SoutezeRocniku;
		$data = $this->model->find($id)->fetch();
		$data['souteze'] = $soutezeRocniku->findByRocnik($id)->fetchAssoc('id_souteze,id_kategorie,=');
		if($id != 0) $this['editForm']->setDefaults($data);

		if($id == 0) $this->setTitle('Přidání ročníku');
		else $this->setTitle('Úprava ročníku');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver($this, 'editForm');

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o ročníku');
		$form->addText('rocnik', 'Číslo ročníku')
			   ->addRule(Form::INTEGER, 'Číslo ročníku musí být číslo.')
			   ->addRule(Form::FILLED, 'Je nutné vyplnit číslo ročníku.');
		$form->addText('rok', 'Rok')
			   ->addRule(Form::INTEGER, 'Rok musí být číslo.')
			   ->addRule(Form::FILLED, 'Je nutné vyplnit rok ročníku.');

		$soutezeModel = new Souteze;
		$souteze = $soutezeModel->findAll()->fetchAssoc('id');

		$form->addGroup('Informace o soutěžích');

		$bodoveTabulkyModel = new BodoveTabulky;
		$kategorieModel = new Kategorie;
		$kategorie = $kategorieModel->findAll()->fetchAssoc('id');
		$bodoveTabulky = $bodoveTabulkyModel->findAllToSelect()->fetchPairs('id', 'nazev');
		$soutezeCont = $form->addContainer('souteze');

		foreach ($souteze as $id_souteze => $soutez)
		{
			$soutCont = $soutezeCont->addContainer($id_souteze);
			$soutCont->setCurrentGroup($form->addGroup('Soutěž: ' . $soutez->nazev, false));
			foreach ($kategorie as $id_kategorie => $kat)
			{
				$katCont = $soutCont->addContainer($id_kategorie);
				$katCont->addCheckBox('id_kategorie', 'Kategorie ' . $kat->nazev);
				$katCont->addSelect('id_bodove_tabulky', 'Bodová tabulka', $bodoveTabulky);
			}
		}

		$form->addGroup(null);
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndReturn', 'Uložit a přejít na ročník');
		$form->addRequestButtonBack('back', 'Vrátit se zpět')
			   ->setValidationScope(false);
		$form->addSubmit('cancel', 'Zrušit')
			   ->setValidationScope(false);

		$form->onSubmit[] = array($this, 'editFormSubmitted');
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');
		if($form['cancel']->isSubmittedBy())
		{

		}
		elseif($form['save']->isSubmittedBy() || $form['saveAndReturn']->isSubmittedBy())
		{
			try
			{
				$dataDoDb = array('rok' => (int) $form['rok']->value, 'rocnik' => (int) $form['rocnik']->value);
				if($id == 0)
				{
					$this->model->insert($dataDoDb);
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update($id, $dataDoDb);
				}

				$this->flashMessage('Ročník byl úspěšně uložen.', 'ok');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Ročník se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}

			$data = $form->getValues();
//var_dump($data['souteze']);
			try
			{
				if(isset($data['souteze']))
				{
					$update = array();
					$delete = array();
					$insert = array();
					$soutezeRocnikuModel = new SoutezeRocniku;
					$soutezeRocniku = $soutezeRocnikuModel->findByRocnik($id)->fetchAssoc('id_souteze,id_kategorie,=');
					//var_dump($soutezeRocniku);
					foreach ($data['souteze'] as $id_souteze => $foo)
					{
						foreach ($foo as $id_kategorie => $ucast)
						{
							if($ucast['id_kategorie'] === true && !isset($soutezeRocniku[$id_souteze][$id_kategorie])) $insert[] = array('id_rocniku' => (int) $id, 'id_bodove_tabulky' => (int) $ucast['id_bodove_tabulky'], 'id_kategorie' => (int) $id_kategorie, 'id_souteze' => (int) $id_souteze);
							if($ucast['id_kategorie'] === true && isset($soutezeRocniku[$id_souteze][$id_kategorie])) $update[$id_souteze] = array('id_rocniku' => (int) $id, 'id_bodove_tabulky' => (int) $ucast['id_bodove_tabulky'], 'id_kategorie' => (int) $id_kategorie, 'id_souteze' => (int) $id_souteze);
							if($ucast['id_kategorie'] === false && isset($soutezeRocniku[$id_souteze][$id_kategorie])) $delete[] = $id_souteze;
						}
					}
					  /*var_dump($update);
					  var_dump($delete);
					  var_dump($insert);
					  exit;*/
					if(count($delete)) foreach ($delete as $ucast)
							$soutezeRocnikuModel->delete($ucast);
					if(count($update)) foreach ($update as $id_ => $ucast)
							$soutezeRocnikuModel->update($id_, $ucast);
					if(count($insert)) foreach ($insert as $ucast)
							$soutezeRocnikuModel->insert($ucast);
				}
				$this->flashMessage('Informace o soutěžích byly úspěšně uloženy.', 'ok');
			}
			catch (DibiException $e)
			{
				$this->flashMessage('Informace o soutěžích se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
		}

		$this->getApplication()->restoreRequest($this->backlink);

		if($id != 0)
		{
			if($form['save']->isSubmittedBy()) $this->redirect('Rocniky:edit', $id);
			else $this->redirect('Rocniky:rocnik', $id);
		}
	}

	public function actionVysledkyPredZavodem($id)
	{
		$zavody = new Zavody;
		if($this->user->isAllowed('rocniky', 'edit')) $zavody->zobrazitNezverejnene();
		$zavod = $zavody->find($id)->fetch();
		if(!$zavod) throw new BadRequestException();

		$this->forward('vysledky', array('id' => $zavod->id_rocniku, 'id_zavodu' => $id));
	}

	/**
	 * Výpis celkových výsledků po jednom ročníku
	 * @param int $id ID ročníku
	 */
	public function renderVysledky($id, $id_zavodu = NULL)
	{
		$vysledky = new Vysledky;
		$zavody = new Zavody;

		if($id_zavodu !== NULL) $this->template->zavod = $zavody->find($id_zavodu)->fetch();

		if($id_zavodu !== NULL) $this->template->vysledky = $vysledky->findByRocnikAndZavod($id, $id_zavodu);
		else $this->template->vysledky = $vysledky->findByRocnik($id);

		if(!$this->template->vysledky) throw new BadRequestException();

		$this->template->vysledky = $this->template->vysledky->fetchAssoc('soutez,kategorie,id_druzstva,=');

		$vysledky->vyhodnotVysledkyRocniku($this->template->vysledky);

		$this->template->komentare = array();
		$cisloPoznamky = 0;
		foreach ($this->template->vysledky as $soutez => $foobar)
		{
			foreach ($foobar as $kategorie => $foo)
			{
				foreach ($foo as $vysledkyKategorie => $bar)
				{
					if(isset($bar['lepsi']))
					{
						$lepsi_ = array_unique($bar['lepsi'], SORT_REGULAR);
						foreach ($lepsi_ as $lepsi)
						{
							// označí se lepší umístění
							$bar_ = $bar;
							foreach ($bar_['prubeh'] as &$misto)
							{
								if($misto == $lepsi['rozhodujici']) $misto = '**' . $misto . '**';
							}
							foreach ($lepsi['prubeh'] as &$misto)
							{
								if($misto == $lepsi['rozhodujici']) $misto = '**' . $misto . '**';
							}
							$cisloPoznamky++;
							$this->template->vysledky[$soutez][$kategorie][$vysledkyKategorie]['odkazy'][] = $cisloPoznamky;
							$this->template->komentare[] = array('odkaz' => $cisloPoznamky, 'druzstvo' => $bar_, 'srovnavane' => $lepsi);
						}
					}
				}
			}
		}

		$this->model = new Rocniky;
		$this->template->rocnik = $this->model->find($id)->fetch();

		if($id_zavodu === NULL) $this->setTitle('Bodová tabulka ' . $this->template->rocnik['rocnik'] . '. ročníku');
		else $this->setTitle('Bodová tabulka ' . $this->template->rocnik['rocnik'] . '. ročníku před závodem ' . $this->template->zavod['nazev']);
	}

	/**
	 * Výpis celkových výsledků po jednom ročníku
	 * - data určená pro graf
	 * @param int $id ID ročníku
	 */
	public function actionGrafVysledky($id)
	{
		$vysledky = new Vysledky;

		$data = $vysledky->findByRocnik($id)->fetchAssoc('kategorie,id_druzstva,=');

		$this->model = new Rocniky;
		$rocnik = $this->model->find($id)->fetch();

		foreach ($data as $kategorie => $foo)
		{
			usort($data[$kategorie], array($this, "orderVysledkyReverse"));
		}

		$grafy = array();
		foreach ($data as $kategorie => $foo)
		{
			$i = 1;
			$hodnoty = array();
			foreach ($foo as $vysledkyKategorie => $bar)
			{
				$i++;
				if($i < count($foo) - 10) continue;

				$hodnoty[] = array('nazev' => $bar['druzstvo'], 'hodnota' => (int) $bar['celkem_bodu']);
			}
			$grafy[] = array('nazev' => 'Bodová tabulka ' . $rocnik['rocnik'] . '. ročníku', 'sirka' => 550, 'vyska' => 620, 'rady' => array(array('nazev' => $kategorie, 'typ' => 'Bar', 'hodnoty' => $hodnoty)));
		}
		$this->getHttpResponse()->setHeader('Content-type', 'text/plain');
		echo json_encode($grafy);
		$this->terminate();
	}

	public function handleZverejnit($id)
	{
		try
		{
			$this->model->zverejnit($id);
			$this->flashMessage('Ročník byl úspěšně zveřejněn.');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Ročník se nepodařilo zveřejnit.', 'error');
			Debug::process($e);
		}
		catch (RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}
		$this->redirect('this');
	}

	public function actionDelete($id, $force = 0)
	{
		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Ročník byl úspěšně odstraněn.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->flashMessage('Ročník se nepodařilo odstranit.', 'error');
		}
		catch (RestrictionException $e)
		{
			$this->flashMessage($e->getMessage() . ' "Přesto smazat!":' . $this->link('delete', array('id' => $id, 'force' => true)), 'warning');
		}
		$this->getApplication()->restoreRequest($this->backlink);
		$this->redirect('Rocniky:default');
	}

}
