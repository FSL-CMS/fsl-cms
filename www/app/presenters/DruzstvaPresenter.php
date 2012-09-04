<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter družstev
 *
 * @author	Milan Pála
 */
class DruzstvaPresenter extends BasePresenter
{
	private $model = NULL;

	public function startup()
	{
		$this->model = new Druzstva;

		parent::startup();
	}

	public function renderDefault()
	{
		$this->template->druzstva = array();

		$this->template->druzstva['muze_pridavat'] = $this->user->isAllowed('druzstva', 'add');
		$this->template->druzstva['muze_editovat'] = $this->user->isAllowed('druzstva', 'edit');
		$this->template->druzstva['muze_smazat'] = $this->user->isAllowed('druzstva', 'delete');
		$this->template->druzstva['druzstva'] = $this->model->findAll();

		$this->setTitle('Družstva, která se účastnila ligy');
	}

	public function actionDruzstvo($id)
	{
		if( !$this->model->find($id)->fetch() ) throw new BadRequestException("Družstvo nebylo nalezeno.");
	}

	public function actionAdd()
	{
		if( $this->user === NULL || !$this->user->isAllowed('druzstva', 'add') ) throw new ForbiddenRequestException();

		$this->setView('edit');
	}

	public function actionEdit($id = 0)
	{
		if( $id != 0 && !$this->model->find($id)->fetch() ) throw new BadRequestException("Družstvo nebylo nalezeno.");

		if( $this->user === NULL || !$this->user->isAllowed('druzstva', 'edit') ) throw new ForbiddenRequestException();
	}

	public function actionDelete($id)
	{
		try
		{
			$this->model->delete($id);
			$this->flashMessage('Družstvo bylo úspěšně smazáno.');
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Nepodařilo se smazat družstvo.', 'error');
			Debug::processException($e);
		}
		$this->redirect('default');
	}

	public function renderDruzstvo($id)
	{
		$this->template->druzstvo = $this->model->find($id)->fetch();
		$this->template->druzstvo['muze_editovat'] = $this->user->isAllowed('druzstva', 'edit');

		$this->setTitle("Družstvo ".$this->template->druzstvo['nazev']);

		// Průměrné časy sezón
		$vysledky = new Vysledky;
		$this->template->vyznacneCasySezon = $vysledky->vyznacneCasySezonDruzstva($id)->fetchAssoc('kategorie,typ,rok,=');

		// vytvoření pole pro záhlaví tabulky s průměrnými časy sezón
		$this->template->vyznacneCasySezon_zahlavi = array();
		foreach($this->template->vyznacneCasySezon as $kategorie => $foo )
		{
			$this->template->vyznacneCasySezon_zahlavi[0][0] = array('nazev' => 'Sezóna', 'sirka' => 1, 'vyska'=> 2);
			$this->template->vyznacneCasySezon_zahlavi[0]['prumer'] = array( 'nazev' => 'Průměrné časy', 'sirka' => count($foo), 'vyska' => 1 );
			foreach( $foo as $terce => $bar )
			{
				$this->template->vyznacneCasySezon_zahlavi[1]['prumer'.$terce] = array( 'nazev' => $terce, 'sirka' => 1, 'vyska' => 1);
			}
		}

		foreach($this->template->vyznacneCasySezon as $kategorie => $foo )
		{
			$this->template->vyznacneCasySezon_zahlavi[0]['minimum'] = array( 'nazev' => 'Rekordní časy', 'sirka' => count($foo), 'vyska' => 1 );
			foreach( $foo as $terce => $bar )
			{
				$this->template->vyznacneCasySezon_zahlavi[1]['minimum'.$terce] = array( 'nazev' => $terce, 'sirka' => 1, 'vyska' => 1);
			}
		}

		$tmp = $this->template->vyznacneCasySezon;

		$this->template->vyznacneCasySezon = array();
		$j=0;
		$kategorie = array();
		foreach( $tmp as $kat => $foo )
		{
			foreach( $foo as $terce => $bar )
			{
				if( !isset($kategorie[$terce]) ) $kategorie[$terce] = $j++;
				foreach( $bar as $cas )
				{
					$casy = explode( ',', $cas['casy'] );
					if( count($casy) > 2 )
					{
						sort($casy);
						$median = $casy[(int)((count($casy)+1)/2.0)-1];
						$cas['prumer'] = $median;
					}
					$this->template->vyznacneCasySezon[$cas['rok']]['prumer'][$kategorie[$terce]] = array( 'prumer' => sprintf( "%.2f", $cas['prumer'] ) );
					$this->template->vyznacneCasySezon[$cas['rok']]['rekord'][$kategorie[$terce]] = array( 'rekord' => sprintf( "%.2f", $cas['rekord'] ) );

				}
			}

		}
		ksort($this->template->vyznacneCasySezon);
		$this->template->vyznacneCasySezon_oddilu = count($kategorie);

		$zavodyModel = new Zavody;
		$this->template->zavody = $zavodyModel->findAll()->fetchAssoc('sezona,id,=');
		$this->template->casyDruzstva = $vysledky->findByDruzstvo($id)->fetchAssoc('soutez,sezona,id_zavodu,=');
		$return = array();
		$pocet_zavodu = 0;
		$pocet_zavodu_tmp = 0;
		foreach($this->template->casyDruzstva as $soutez => $foo)
		{
			$return[$soutez] = array();
			foreach($this->template->zavody as $sezona => $zavodyVsezone)
			{
				$nulujRocnik = true;
				$return[$soutez][$sezona] = array();
				foreach($zavodyVsezone as $id_zavodu => $zavod)
				{
					$pocet_zavodu_tmp++;
					if($zavod['zruseno'] == true) continue;
					$return[$soutez][$sezona][$id_zavodu] = array();
					if( isset($this->template->casyDruzstva[$soutez][$sezona][$id_zavodu]) )
					{
						$return[$soutez][$sezona][$id_zavodu] = $this->template->casyDruzstva[$soutez][$sezona][$id_zavodu];
						$nulujRocnik = false;
					}
				}
				if($nulujRocnik) unset($return[$soutez][$sezona]);
			}
			$pocet_zavodu = $pocet_zavodu > $pocet_zavodu_tmp ? $pocet_zavodu : $pocet_zavodu_tmp;
		}
		$this->template->casyDruzstva = $return;
		$this->template->casyDruzstvaPocetZavodu = $pocet_zavodu;
	}

	public function actionGrafPrumerneCasySezon($id)
	{
		if( !$this->model->find($id)->fetch() ) throw new BadRequestException('Požadované družstvo neexistuje.');

		// Průměrné časy sezón
		$vysledky = new Vysledky;
		$pcsd = $vysledky->casySezonDruzstva($id)->fetchAssoc('kategorie,typ,id,=');

		$j=0;
		$kategorie = array();
		$data['rady'] = array();
		foreach( $pcsd as $kat => $foo )
		{
			foreach( $foo as $terce => $bar )
			{
				$tmp = array();
				$tmp2 = array();
				if( !isset($kategorie[$kat.$terce]) ) $kategorie[$kat.$terce] = $j++;
				foreach( $bar as $cas )
				{
					$casy = explode( ',', $cas['casy'] );
					if( count($casy) > 2 )
					{
						sort($casy);
						$median = $casy[(int)((count($casy)+1)/2.0)-1];
						$cas['prumer'] = $median;
					}
					$tmp[] = array('nazev' => $cas['rok'], 'hodnota' => (float)sprintf( "%.2f", $cas['prumer'] ));
					//$tmp2[] = array('nazev' => $cas['rok'], 'hodnota' => (float)sprintf( "%.2f", $median ));

				}
				//$data['rady'][] = array('nazev'=>'Časy na '.$terce.' terče - med', 'hodnoty' => $tmp2, 'typ' => 'Line' );
				$data['rady'][] = array('nazev'=>'Časy na '.$terce.' terče', 'hodnoty' => $tmp, 'typ' => 'Line' );
			}
		}
		usort( $data['rady'], array($this, 'sort4chart') );
		$data['nazev'] = 'Průměrné časy družstva';
		$data['sirka'] = 600;
		$data['vyska'] = 400;
		$grafy = array( $data );

		echo json_encode($grafy);
		$this->terminate();
	}

	private function sort4chart($a, $b)
	{
		$ca = count($a['hodnoty']);
		$cb = count($b['hodnoty']);
		if( $ca > $cb ) return 1;
		elseif( $ca < $cb ) return -1;
		else 0;
	}

	public function renderEdit($id = 0)
	{
		if( $id != 0 && ($zDB = $this->model->find($id)->fetch()) !== false )
		{
			$this['editForm']->setDefaults($zDB);
		}

		$id_kategorie = (int) $this->getParam('id_kategorie');
		if($id_kategorie != 0) $this['editForm']['id_kategorie']->setValue($id_kategorie);

		if($id == 0) $this->setTitle('Přidání družstva');
		else $this->setTitle('Úprava družstva');
	}

	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;
		$kategorie = new Kategorie;
		$sbory = new Sbory;

		$form->getRenderer()->setClientScript(new LiveClientScript($form));

		$form->addGroup('Informace o družstvu');
		$form->addSelect('id_kategorie', 'Kategorie', $kategorie->findAllToSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat kategorii.')
			->setOption('description', $form->addRequestButton('addKategorie', 'Přidat novou', 'Kategorie:edit'));
		$form->addSelect('id_sboru', 'Sbor', $sbory->findAlltoSelect()->fetchPairs('id', 'nazev'))
			->addRule(Form::FILLED, 'Je nutné vybrat majitele terčů.')
			->setOption('description', $form->addRequestButton('addSbory', 'Přidat nový', 'Sbory:add'));
		$form->addText('poddruzstvo', 'Poddružstvo', 4)
			->setOption('description', 'A, B, ...');

		$form->addGroup('Uložení');
		$form->addSubmit('save', 'Uložit');
		$form->addSubmit('saveAndAdd', 'Uložit a přidat nový');
		$form->addSubmit('cancel', 'Zrušit')
			->setValidationScope(false);
		$form->addRequestButtonBack('back', 'Vrátit se zpět');

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		return $form;
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $this->getParam('id');

		if( $form['cancel']->isSubmittedBy() )
		{
			if( $id == 0 ) $this->redirect('default');
			else $this->redirect('druzstvo', $id);
		}
		else
		{
			$data = $form->getValues();
			$dataDoDB = array( 'id_kategorie' => (int)$data['id_kategorie'], 'id_sboru' => (int)$data['id_sboru'], 'poddruzstvo' => $data['poddruzstvo']);

			try
			{
				if( $id == 0 )
				{
					$this->model->insert( $dataDoDB );
					$id = $this->model->lastInsertedId();
				}
				else
				{
					$this->model->update( $id, $dataDoDB );
				}

				$this->flashMessage('Družstvo bylo úspěšně uloženo.', 'ok');

				if( $form['save']->isSubmittedBy() ) $this->redirect('edit', $id);
				elseif( $form['saveAndAdd']->isSubmittedBy() ) $this->redirect('edit');
				else { $this->redirect('Druzstva:druzstvo', $id); }
			}
			catch( DibiException $e )
			{
				$this->flashMessage('Družstvo se nepodařilo uložit.', 'error');
				Debug::processException($e, true);
			}
			catch(AlreadyExistException $e)
			{
				$this->flashMessage('Družstvo již existuje.', 'warning');
			}
		}
	}


}
