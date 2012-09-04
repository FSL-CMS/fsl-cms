<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter kategorií článků
 *
 * @author	Milan Pála
 */
class KategorieClankuPresenter extends SecuredPresenter
{
	protected $model = NULL;
	/** @persistent */
	public $backlink = ''; 

	public function startup()
	{
		$this->model = new ClankyKategorie;
		
		parent::startup();
	}

	public function actionAdd()
	{
		$this->setView('edit');
	}

	public function renderDefault()
	{
		$this->setTitle('Kategorie článků');
		
		$this->template->kategorie = array();
		
		$this->template->kategorie['muze_editovat'] = $this->user->isAllowed('kategorieclanku', 'edit');
		$this->template->kategorie['kategorie'] = $this->model->findAll();		
	}

	public function createComponentKategorieForm()
	{
		$form = new AppForm;

		$form->getElementPrototype()->class('ajax');
		
		foreach( $this->model->findAll()->fetchAll() as $kategorie )
		{
			$poradi = $form->addContainer($kategorie['id']);
				
			$poradi->addHidden('id')->setDefaultValue($kategorie['id']);
			$poradi->addText('cssstyl', 'Název stylu')->setDefaultValue($kategorie['cssstyl']);
			$poradi->addText('poradi', 'Pořadí', 4)->setDefaultValue($kategorie['poradi']);
		}
		$form->addSubmit('save', 'Uložit');
		
		$form->onSubmit[] = array($this, 'kategorieFormSubmitted');
		
		return $form;
	}
	
	public function kategorieFormSubmitted(AppForm $form)
	{
		try
		{
			$data = $form->getValues();
			foreach( $data as $poradi )
			{
				$this->model->update( $poradi['id'], array('poradi' => $poradi['poradi']) );	
			}
			$this->flashMessage('Údaje o kategoriích byly úspěšně uloženy.', 'ok');

			if( !$this->isAjax() ) $this->redirect('default');
		}
		catch(DibiException $e)
		{
			$this->flashMessage('Údaje o kategoriích se nepodařilo uložit.', 'error');	
		}	
	}
	
	public function renderEdit($id = 0)
	{
		if( $id != 0 && ($zDB = $this->model->find($id)->fetch()) !== false )
		{
			$this['editForm']->setDefaults($zDB);
		}
		
		if($id == 0) $this->setTitle('Přidání kategorie');
		else $this->setTitle('Úprava kategorie');
	}
	
	public function createComponentEditForm()
	{
		$form = new RequestButtonReceiver;
		
		$form->addGroup('Informace o kategorii');
		$form->addText('nazev', 'Název')
			->addRule(Form::FILLED, 'Je nutné vyplnit název kategorie.');
		$form->addText('cssstyl', 'Název stylu');

		$form->setCurrentGroup(NULL);			
			
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
			else $this->redirect('default');
		}
		elseif( $form['save']->isSubmittedBy() || $form['saveAndAdd']->isSubmittedBy() )
		{
			$data = $form->getValues();
			$dataDoDB = array( 'nazev' => $data['nazev'], 'cssstyl' => $data['cssstyl'] );

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

				$this->flashMessage('Informace o kategorii byly úspěšně uloženy.', 'ok');
				
				$this->getApplication()->restoreRequest($this->backlink);
				
				if( $form['save']->isSubmittedBy() ) $this->redirect('default');
				elseif( $form['saveAndAdd']->isSubmittedBy() ) $this->redirect('edit');
				else $this->redirect('default');
			}
			catch( DibiException $e )
			{
				$this->flashMessage('Informace o kategorii se nepodařilo uložit.', 'error');
			}
		}
	}
	

}
