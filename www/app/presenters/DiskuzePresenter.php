<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter diskuzí
 *
 * @author	Milan Pála
 */
class DiskuzePresenter extends BasePresenter
{

	private $model = NULL;

	protected function startup()
	{
		$this->model = new Diskuze;

		parent::startup();
	}

	public function actionDefault()
	{
		$this->redirect('Forum:default');
	}

	public function actionDiskuze($id = NULL)
	{
		if( $id === NULL ) $this->redirect('Diskuze:default');

		if( $this->model->find($id)->fetch() === FALSE ) throw new BadRequestException('Hledaná diskuze neexistuje');
	}

	/**
	 * Zkontroluje možnost založení diskuze
	 * @param int $id ID tématu
	 * @param $id_souvisejiciho ID souvisejici polozky
	 */
	public function actionZeptatse($id = NULL, $id_souvisejiciho = NULL)
	{
		//if( $id === NULL ) $this->redirect('Diskuze:default');

		if( !$this->user->isLoggedIn() )
		{
			$backlink = $this->getApplication()->storeRequest();
			$this->flashMessage('Nejste přihlášen.', 'warning');
			$this->forward('Sprava:login', $backlink);
		}

		if( !$this->user->isAllowed('diskuze', 'add') ) throw new ForbiddenRequestException('Pro založení diskuze nemáte dostatečná oprávnění.');
	}

	/**
	 * Stránka se založením diskuze
	 * @param int $id ID tématu
	 * @param int $id_souvisejiciho ID související položky, nebo NULL
	 */
	public function renderZeptatse($id = 0, $id_souvisejiciho = NULL)
	{
		if($id != 0) { $this['zeptatseForm']['id_tematu']->value = $id; $this['zeptatseForm']['id_souvisejiciho']->refresh(); }
		if($id_souvisejiciho !== NULL) $this['zeptatseForm']['id_souvisejiciho']->value = $id_souvisejiciho;

		$this->template->diskuze = array();
		$this->template->diskuze['id_souvisejiciho'] = $id_souvisejiciho;

		$this->setTitle('Založit novou diskuzi');
	}

	public function createComponentZeptatseForm()
	{
		$form = new AppForm($this, 'zeptatseForm');
		$form->getElementPrototype()->class('ajax');
		$temataModel = new Temata();

		DependentSelectBox::$disableChilds = false;
		$form->addGroup('Nový dotaz');
		$form->addSelect('id_tematu', 'Téma diskuze', $temataModel->findAllToSelect()->fetchPairs('id','nazev'));
		$form->addJsonDependentSelectBox('id_souvisejiciho', 'Související', $form['id_tematu'], array($this, 'getSouvisejici'));
		$form->addText('nazev', 'Název diskuze', 40)
			->addRule(Form::FILLED, 'Je nutné vyplnit téma diskuze.');
		$form->addTexylaTextArea('text', 'Text zprávy')
			->addRule(Form::FILLED, 'Je nutné vyplnit text zprávy.');
		$form->addSubmit('save', 'Založit téma');
		$form->addSubmit('cancel', 'Vrátit se zpět')
			->setValidationScope(FALSE);

		$form->onSubmit[] = array($this, 'zeptatseFormSubmitted');

		$form->getRenderer()->setClientScript(new LiveClientScript($form));
	}

	public function createComponentEditForm($name)
	{
		$form = new AppForm($this, $name);
		$form->getElementPrototype()->class('ajax');
		$temataModel = new Temata();

		DependentSelectBox::$disableChilds = false;

		$form->addHidden('id');
		$form->addGroup('Úprava informací o diskuzi');
		$form->addSelect('id_tematu', 'Téma diskuze', $temataModel->findAllToSelect()->fetchPairs('id','nazev'))
			   ->addRule(Form::FILLED, 'Je nutné vybrat téma diskuze.');
		$form->addJsonDependentSelectBox('id_souvisejiciho', 'Související', $form['id_tematu'], array($this, 'getSouvisejici'))
			   ->addRule(Form::FILLED, 'Je nutné vybrat související položku.');
		$form->addText('tema_diskuze', 'Název diskuze', 40)
			->addRule(Form::FILLED, 'Je nutné vyplnit téma diskuze.');
		$form->addSubmit('save', 'Uložit');

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		$form->getRenderer()->setClientScript(new LiveClientScript($form));
	}

	public function getSouvisejici($form)
	{
		$id_tematu = $form['id_tematu']->getValue();
		$tema = $this->model->findTema($id_tematu)->fetch();
		$tabulka = ucfirst($tema['souvisejiciTabulka']);
		//$vystup = array('' => 'žádná');
		$vystup = array();
		if( $tabulka == 'Zavody' )
		{
			$model = new Zavody;
			$polozky = $model->findAllToSelect()->fetchAssoc('rok,id,=');
			foreach( $polozky as &$skupina )
				foreach( $skupina as &$polozka )
				{
					$vystup[$polozka['id']] = $polozka['nazev'].', '.datum::date($polozka['datum'], 0, 0, 0);
				}
		}
		elseif( $tabulka == 'Druzstva' )
		{
			$model = new Druzstva;
			$polozky = $model->findAllToSelect()->fetchAssoc('kategorie,id,=');
			foreach( $polozky as &$skupina )
				foreach( $skupina as &$polozka )
				{
					$vystup[$polozka['id']] = $polozka['nazev'];
				}
		}
		elseif( $tabulka == 'Fotogalerie' )
		{
			$model = new Fotogalerie;
			$polozky = $model->findAllToSelect()->fetchAssoc('id,=');
			foreach( $polozky as &$polozka )
			{
				$vystup[$polozka['id']] = $polozka['nazev'].', '.datum::date($polozka['datum_zverejneni'], 0, 0, 0);;
			}
		}
		elseif( $tabulka == 'Clanky' )
		{
			$model = new Clanky;
			$polozky = $model->findAllToSelect()->fetchAssoc('id,=');
			foreach( $polozky as &$polozka )
			{
				$vystup[$polozka['id']] = $polozka['nazev'].', '.datum::date($polozka['datum_zverejneni'], 0, 0, 0);;
			}
		}
		elseif( $tabulka == 'Sbory' )
		{
			$model = new Sbory;
			$polozky = $model->findAllToSelect()->fetchAssoc('id,=');
			foreach( $polozky as &$polozka )
			{
				$vystup[$polozka['id']] = $polozka['nazev'];
			}
		}
		else $vystup = array('' => 'není k dispozici');

		return $vystup;
	}

	public function zeptatseFormSubmitted(AppForm $form)
	{
		$id_tematu = (int) $form['id_tematu']->value;
		$id_souvisejiciho = (int) $form['id_souvisejiciho']->value;

		if( $form['cancel']->isSubmittedBy() )
		{
			if( $id_souvisejiciho != 0 )
			{
				$tema = $this->model->findTema($id_tematu)->fetch();
				if( $tema['souvisejiciTabulka'] == 'zavody' ) $this->redirect('Zavody:zavod', $id_souvisejiciho);
				elseif( $tema['souvisejiciTabulka'] == 'sbory' ) $this->redirect('Sbory:sbor', $id_souvisejiciho);
				elseif( $tema['souvisejiciTabulka'] == 'terce' ) $this->redirect('Terce:terce', $id_souvisejiciho);
                    elseif( $tema['souvisejiciTabulka'] == 'druzstva' ) $this->redirect('Druzstva:druzstvo', $id_souvisejiciho);
                    elseif( $tema['souvisejiciTabulka'] == 'clanky' ) $this->redirect('Clanky:clanek', $id_souvisejiciho);
				else $this->redirect('Diskuze:tema', $id_tematu);
			}
			else $this->redirect('Forum:forum', $id_tematu);
		}
		elseif( $form['save']->isSubmittedBy() )
		{
			try
			{
				$komentare = new Komentare;

				$tema = $this->model->findTema($id_tematu)->fetch();
				$data = array('nazev' => $form['nazev']->value, 'id_autora' => (int)$this->user->getIdentity()->id, 'id_tematu' => $id_tematu);

				$this->model->insert( $data );
				$id_diskuze = $this->model->lastInsertedId();

				$komentare->insert( array('id_diskuze' => $id_diskuze, 'id_autora' => (int)$this->user->getIdentity()->id, 'text' => $form['text']->value, 'datum_pridani%sql' => 'NOW()') );

				if( $id_souvisejiciho != 0 && !empty($tema['souvisejiciTabulka']) )
				{
					$souvisejici = new Souvisejici;
					$souvisejici->insert(array('rodic' => 'diskuze', 'id_rodice' => $id_diskuze, 'souvisejici' => $tema['souvisejiciTabulka'], 'id_souvisejiciho' => $id_souvisejiciho) );
				}

				$this->flashMessage('Téma bylo úspěšně založeno.', 'ok');

				$sledovani = new Sledovani;

				// nastaví sledování pro autora nebo správce sboru/závodu
				if( !empty($tema['souvisejiciTabulka']) && in_array( $tema['souvisejiciTabulka'], array('zavody', 'sbory', 'terce', 'druzstva', 'clanky') ) )
				{
					$souvisejiciTabulka = ucfirst($tema['souvisejiciTabulka']);
					$souvisejiciModel = new $souvisejiciTabulka;
					$souvisejiciPrvek = $souvisejiciModel->find($id_souvisejiciho)->fetch();
					if( $souvisejiciPrvek !== false && isset($souvisejiciPrvek['id_autora']) && !empty($souvisejiciPrvek['id_autora']) ) $sledovani->sledovat("diskuze", $id_diskuze, $souvisejiciPrvek['id_autora']);
					if( $souvisejiciPrvek !== false && isset($souvisejiciPrvek['id_spravce']) && !empty($souvisejiciPrvek['id_spravce']) ) $sledovani->sledovat("diskuze", $id_diskuze, $souvisejiciPrvek['id_spravce']);
					elseif( $souvisejiciPrvek !== false && isset($souvisejiciPrvek['id_kontaktni_osoby']) && !empty($souvisejiciPrvek['id_kontaktni_osoby']) ) $sledovani->sledovat("diskuze", $id_diskuze, $souvisejiciPrvek['id_kontaktni_osoby']);

				}

				$sledovani->sledovat("diskuze", $id_diskuze, (int)$this->user->getIdentity()->id);

				$sledovani->upozornit("temata", $id_tematu);

				if( $id_souvisejiciho != 0 && !empty($tema['souvisejiciTabulka']) )
				{
					$tema = $this->model->findTema($id_tematu)->fetch();
					if( $tema['souvisejiciTabulka'] == 'zavody' ) $this->redirect('Zavody:zavod', $id_souvisejiciho);
					elseif( $tema['souvisejiciTabulka'] == 'sbory' ) $this->redirect('Sbory:sbor', $id_souvisejiciho);
					elseif( $tema['souvisejiciTabulka'] == 'terce' ) $this->redirect('Terce:terce', $id_souvisejiciho);
					elseif( $tema['souvisejiciTabulka'] == 'druzstva' ) $this->redirect('Druzstva:druzstvo', $id_souvisejiciho);
					elseif( $tema['souvisejiciTabulka'] == 'clanky' ) $this->redirect('Clanky:clanek', $id_souvisejiciho);
					else $this->redirect('Diskuze:tema', $id_tematu);
				}
				else $this->redirect('Diskuze:diskuze', $id_diskuze);
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Téma se nepodařilo založit.', 'error');
				Debug::processException($e, true);
				$this->redirect('this');
			}
		}
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id = (int) $form['id']->value;
		$id_tematu = (int) $form['id_tematu']->value;
		$id_souvisejiciho = (int) $form['id_souvisejiciho']->value;
		$tema = $this->model->findTema($id_tematu)->fetch();

		if( $form['save']->isSubmittedBy() )
		{
			try
			{
				if( $id_souvisejiciho != 0 && !empty($tema['souvisejiciTabulka']) )
				{
					$souvisejiciModel = new Souvisejici;
					$souvisejici = $souvisejiciModel->findByRodic('diskuze', $id)->fetch();
					if($souvisejici !== false)
					{
						if($souvisejici['souvisejiciTabulka'] != $tema['souvisejiciTabulka'] || $souvisejici['id_souvisejiciho'] != $id_souvisejiciho)
						{
							$souvisejiciModel->delete($souvisejici['id']);
							$souvisejiciModel->insert(array('rodic' => 'diskuze', 'id_rodice' => $id, 'souvisejici' => $tema['souvisejiciTabulka'], 'id_souvisejiciho' => $id_souvisejiciho) );
						}
					}
					else
					{
						$souvisejiciModel->insert(array('rodic' => 'diskuze', 'id_rodice' => $id, 'souvisejici' => $tema['souvisejiciTabulka'], 'id_souvisejiciho' => $id_souvisejiciho) );
					}
				}

				$data = array('nazev' => $form['tema_diskuze']->value, 'id_tematu' => $id_tematu);
				$this->model->update($id, $data);

				$this->flashMessage('Téma bylo úspěšně změněno.', 'ok');
				$this->redirect('Diskuze:diskuze', $id);
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Téma se nepodařilo založit.', 'error');
				Debug::processException($e, true);
				$this->redirect('this');
			}
		}
	}

	public function renderDiskuze($id)
	{
		$this->template->diskuze = array('id' => $id);
		$this->template->user = $this->getPresenter()->getUser();
		$disk = $this->model->find($id)->fetch();
		$souvisejiciModel = new Souvisejici();
		$souv = $souvisejiciModel->findByRodic('diskuze', $disk['id_diskuze'])->fetch();
		$disk['id_souvisejiciho'] = $souv['id_souvisejiciho'];
		$disk['id'] = $id;
		$this['editForm']->setValues($disk);
		$this['editForm']['id_souvisejiciho']->refresh();
		$this['editForm']->setValues($disk);
		$this->setTitle('Diskuze na téma: '.$disk['tema_diskuze']);
	}

	public function renderUpravitKomentar($id)
	{
		$this->template->komentar = array('id' => $id);

		$this->setTitle('Úprava komentáře');
	}

	public function komentarFormSubmitted(AppForm $form)
	{
		if( $form['cancel']->isSubmittedBy() )
		{
			if($this->isAjax()) $this->invalidateControl('zamykani'.$form['id_diskuze']->value);
			else $this->redirect('this');
		}
		elseif( $form['save']->isSubmittedBy() )
		{
			$komentare = new Komentare;
			$komentare->insert( array( 'id_diskuze' => $form['id_diskuze']->value, 'text' => $form['text']->value, 'id_autora' => (int)$this->user->getIdentity()->__get('id'), 'datum_pridani%sql' => 'NOW()' ) ); //Debug::dump(dibi::$sql);
			$this->flashMessage('Odpověď byla vložena do diskuze.');

			if($this->isAjax())
			{
				$this->invalidateControl('zamykani'.$form['id_diskuze']->value);
				$this->invalidateControl('komentare'.$form['id_diskuze']->value);
			}
			else $this->redirect('this');
		}
	}

	public function actionSmazat($id, $force = 0)
	{
		try
		{
			$this->model->delete($id, $force);
			$this->flashMessage('Diskuze byla odstraněna.');

			$this->redirect('Forum:default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Diskuzi se nepodařilo odstranit.');
			Debug::processException($e, true);
			$this->redirect('Diskuze:diskuze', $id);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage().' "Přesto smazat!":'.$this->link('smazat', array('id' => $id, 'force' => 1)), 'warning');
			$this->redirect('Diskuze:diskuze', $id);
		}
	}



}
