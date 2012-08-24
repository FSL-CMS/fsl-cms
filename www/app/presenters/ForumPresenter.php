<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Presenter diskuzních fór
 *
 * @author	Milan Pála
 */
class ForumPresenter extends BasePresenter
{
	private $model = NULL;

	protected function startup()
	{
		$this->model = new Diskuze;

		parent::startup();
	}

	public function actionDefault()
	{
		//$this->setView('defaultByTemata');
		$this->setView('defaultByFora');
	}

	/**
	 * Připraví přehled témat
	 */
	public function renderDefaultByTemata()
	{
		$this->template->fora = array();
		$this->template->fora['fora'] = $this->model->prehledTemat()->fetchAll();
		foreach($this->template->fora['fora'] as &$forum)
		{
			$forum['muze_editovat'] = $this->user->isAllowed('temata', 'edit');
			$forum['muze_smazat'] = $this->user->isAllowed('temata', 'delete');
		}

		$this->template->temata = array();
		$this->template->temata['muze_pridavat'] = $this->user->isAllowed('temata', 'add');

		$this->template->diskuze = array();
		$this->template->diskuze['aktivni'] = $this->model->findAktivni()->fetchAssoc('id_diskuze,=');

		$this->setTitle('Diskuzní fórum');
	}

	public function renderDefaultByFora()
	{
		$this->template->forum = array();
		$this->template->forum['diskuze'] = $this->model->findByAll()->fetchAll(); // jednotlivé diskuze vedená na téma $id

		$this->template->diskuze = array();
		$this->template->diskuze['muze_pridavat'] = $this->user->isAllowed('diskuze', 'add');

		$komentare = new Komentare;

		foreach( $this->template->forum['diskuze'] as &$disk )
		{
			$disk['posledni'] = $komentare->findLastByDiskuze($disk['id_diskuze'])->fetch(); // kvůli informacím o diskuzi
			$disk['muze_smazat'] = $this->user->isAllowed('diskuze', 'delete') || $this->jeAutor($disk['id_autora']);
		}

		//$this->template->tema = array();
		//$this->template->tema = $this->model->findTema($id)->fetch();

		$this->setTitle('Diskuzní fórum');
	}

	/**
	 * Přesměruje na výpis hlavních témat, pokud není uvedeno $id tématu
	 * @param int $id ID tematu fóra
	 */
	public function actionForum($id = NULL)
	{
		if( $id === NULL )
		{
			$this->flashMessage('Nebylo vybráno téma diskuzního fóra.', 'warning');
			$this->redirect('default');
		}
	}

	/**
	 * Připraví data pro šablonu jednoho diskuzního fóra
	 * @param int $id
	 */
	public function renderForum($id)
	{
		$this->template->forum = array();
		$this->template->forum['diskuze'] = $this->model->findByTema($id)->fetchAll(); // jednotlivé diskuze vedená na téma $id

		$this->template->diskuze = array();
		$this->template->diskuze['muze_pridavat'] = $this->user->isAllowed('diskuze', 'add');

		$komentare = new Komentare;

		foreach( $this->template->forum['diskuze'] as &$disk )
		{
			$disk['posledni'] = $komentare->findLastByDiskuze($disk['id_diskuze'])->fetch(); // kvůli informacím o diskuzi
			$disk['muze_smazat'] = $this->user->isAllowed('diskuze', 'delete') || $this->jeAutor($disk['id_autora']);
		}

		$this->template->tema = array();
		$this->template->tema = $this->model->findTema($id)->fetch();

		$this->setTitle($this->template->tema['nazev']);
	}

	public function renderDiskuze($id)
	{
		$this->template->diskuze = array('id' => $id);
		$disk = $this->model->find($id)->fetch();
		$this->setTitle('Diskuze na téma: '.$disk['tema_diskuze']);
	}

	public function actionOdpovedet($id)
	{
		if( !$this->user->isLoggedIn() )
		{
			$backlink = $this->getApplication()->storeRequest();
			$this->flashMessage('Nejste přihlášen.', 'warning');
			$this->forward('Sprava:login', $backlink);
		}
	}

	/**
	 * Stránka s odpovědí do diskuze
	 */
	public function renderOdpovedet($id)
	{
		$this['komentarForm']['id_diskuze']->value = $id;

		//$this->template->diskuze = $this->pripravDiskuze($id);
		//Debug::dump($this->template->diskuze);

		//$prvni = current($this->template->diskuze['diskuze']);

		//$this->setTitle('Odpovědět do diskuze na téma: '.$prvni['tema_diskuze']);
	}

	/**
	 * Stránka s vytvořením diskuze
	 * @param int $id ID tématu
	 * @param int $id_souvisejiciho ID souvisejícího prvku, nebo NULL
	 */
	public function renderZeptatse($id, $id_souvisejiciho = NULL)
	{
		$this->template->tema = $this->model->findTema($id)->fetch();
		if( $id_souvisejiciho !== NULL) $this['editForm']['id_souvisejiciho']->value = $id_souvisejiciho;

		$this->template->diskuze = array();
		$this->template->diskuze['id_souvisejiciho'] = $id_souvisejiciho;

		$this->setTitle('Položit dotaz na téma: '.$this->template->tema['nazev']);
	}

	/*public function komentarFormSubmitted(AppForm $form)
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
	}*/

	public function createComponentEditForm($name)
	{
		$form = new AppForm($this, $name);
		$form->addHidden('id_souvisejiciho');
		$form->addText('nazev', 'Téma diskuze', 40)
			->addRule(Form::FILLED, 'Je nutné vyplnit téma diskuze.');
		$form->addTexylaTextArea('text', 'Text zprávy')
			->addRule(Form::FILLED, 'Je nutné vyplnit text zprávy.');
		$form->addSubmit('save', 'Založit téma');
		$form->addSubmit('cancel', 'Vrátit se zpět')
			->setValidationScope(FALSE);

		$form->onSubmit[] = array($this, 'editFormSubmitted');

		$form->getRenderer()->setClientScript(new LiveClientScript($form));
	}

	public function editFormSubmitted(AppForm $form)
	{
		$id_tematu = (int) $this->getParam('id');
		$id_souvisejiciho = (int) $form['id_souvisejiciho']->value;

		if( $form['cancel']->isSubmittedBy() )
		{
			if( $id_souvisejiciho != 0 )
			{
				$tema = $this->model->findTema($id_tematu)->fetch();
				if( $tema['souvisejiciTabulka'] == 'zavody' ) $this->redirect('Zavody:zavod', $id_souvisejiciho);
				elseif( $tema['souvisejiciTabulka'] == 'sbory' ) $this->redirect('Sbory:sbor', $id_souvisejiciho);
				elseif( $tema['souvisejiciTabulka'] == 'terce' ) $this->redirect('Terce:terc', $id_souvisejiciho);
                    elseif( $tema['souvisejiciTabulka'] == 'druzstva' ) $this->redirect('Druzstva:druzstvo', $id_souvisejiciho);
                    elseif( $tema['souvisejiciTabulka'] == 'clanky' ) $this->redirect('Clanky:clanek', $id_souvisejiciho);
				else $this->redirect('Diskuze:tema', $id_tematu);
			}
			else $this->redirect('Diskuze:tema', $id_tematu);
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

				$this->redirect('Diskuze:diskuze', $id_diskuze);
			}
			catch(DibiException $e)
			{
				$this->flashMessage('Téma se nepodařilo založit.', 'error');
			}
		}
	}

	public function actionSmazat($id, $force = 0)
	{
		try
		{
			$diskuze = $this->model->find($id)->fetch();

			$this->model->delete($id, $force);
			$this->flashMessage('Diskuze byla odstraněna.');

			$this->redirect('Diskuze:tema', $diskuze['id_tematu']);
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Diskuzi se nepodařilo odstranit.');
			Debug::processException($e);
			$this->redirect('Diskuze:diskuze', $id);
		}
		catch(RestrictionException $e)
		{
			$this->flashMessage($e->getMessage().' "Přesto smazat!":'.$this->link('smazat', array('id' => $id, 'force' => 1)), 'error');
			$this->redirect('Diskuze:diskuze', $id);
		}
	}



}
