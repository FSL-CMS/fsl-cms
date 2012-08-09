<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující odkazy na souvisejici polozky
 *
 * @author	Milan Pála
  */
class SouvisejiciControl extends BaseControl
{
	private $model;

	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
	}

	public function render($souvisejiciTabulka, $id)
	{
		$this->model = new Souvisejici();
		$return = array();
		$return = $this->model->findByRodic($souvisejiciTabulka, $id)->fetchAll();
		foreach( $return as $id_ => &$souv )
		{
			if( $souv['souvisejiciTabulka'] == 'diskuze' ) { unset($return[$id_]); continue; }
			$model = ucfirst( $souv['souvisejiciTabulka'] );
			$souvisejiciModel = new $model;
			$souv['souvisejici'] = $souvisejiciModel->find($souv['id_souvisejiciho'])->fetch();
		}

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/souvisejici.phtml');

		$template->souvisejici = array();
		$template->souvisejici['rodic'] = strtolower($this->getPresenter()->getName());
		$template->souvisejici['souvisejici'] = $return;
		$template->souvisejici['muze_pridavat'] = $this->getPresenter()->user->isAllowed('souvisejici', 'add');
		$template->souvisejici['muze_smazat'] = $this->getPresenter()->user->isAllowed('souvisejici', 'delete');

		$template->render();
	}

	public function renderAktualni($souvisejiciTabulka, $id)
	{
		$this->model = new Souvisejici();
		$return = array();
		$return = $this->model->findByRodic($souvisejiciTabulka, $id)->fetchAll();
		foreach( $return as $id_ => &$souv )
		{
			if( $souv['souvisejiciTabulka'] == 'diskuze' ) { unset($return[$id_]); continue; }
			$model = ucfirst( $souv['souvisejiciTabulka'] );
			$souvisejiciModel = new $model;
			$souv['souvisejici'] = $souvisejiciModel->find($souv['id_souvisejiciho'])->fetch();
		}

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/aktualni.phtml');

		$template->souvisejici = array();
		$template->souvisejici['rodic'] = strtolower($this->getPresenter()->getName());
		$template->souvisejici['souvisejici'] = $return;
		$template->souvisejici['muze_pridavat'] = $this->getPresenter()->user->isAllowed('souvisejici', 'add');
		$template->souvisejici['muze_smazat'] = $this->getPresenter()->user->isAllowed('souvisejici', 'delete');

		$template->render();
	}

	public function createComponentPridatSouvisejiciForm()
	{
		$form = new AppForm($this, 'pridatSouvisejiciForm');

		$form->getElementPrototype()->class('ajax');

		$souvisejiciTabulky = array( 'Druzstva' => 'Družstva', 'Zavody' => 'Závody', 'Fotogalerie' => 'Fotogalerie', 'Clanky' => 'Články' );
		$form->addGroup('Související položky');
		$form->addSelect('souvisejici', 'Související skupina', array('' => 'Vyberte kategorii')+$souvisejiciTabulky);
		$form->addJsonDependentSelectBox('id_souvisejiciho', 'Položka', $form['souvisejici'], array($this, "getSouvisejici"));

		$form->addSubmit('save', 'Přidat');

		$form->onSubmit[] = array($this, 'pridatSouvisejiciFormSubmitted');
	}

	public function pridatSouvisejiciFormSubmitted(AppForm $form)
	{
		$data = $form->getValues();
		$this->model = new Souvisejici();

		$rodic = strtolower($this->getPresenter()->getName());
		$id = intval($this->getPresenter()->getParam('id'));

		if( $id != 0 && !empty($data['souvisejici']) && !empty($data['id_souvisejiciho']) )
		{
			$dataDoDB = array('rodic' => $rodic, 'id_rodice' => $id);
			$dataDoDB['souvisejici'] = strtolower($data['souvisejici']);
			$dataDoDB['id_souvisejiciho'] = $data['id_souvisejiciho'];
			try
			{
				$this->model->insert($dataDoDB);
				$this->getPresenter()->flashMessage('Související položka byla přidána.', 'ok');
			}
			catch(DibiException $e)
			{
				$this->getPresenter()->flashMessage('Nepodařilo se uložit související položku.', 'error');
				Debug::processException($e, true);
			}

			/*if( $this->getPresenter()->isAjax() )
			{
				//$this->getPresenter()->invalidateControl('souvisejici');
				//$this->getPresenter()->invalidateControl();
				$this->invalidateControl('souvisejici');
				//$this->invalidateControl();
			}
			else */$this->redirect('this');
		}
	}

	public function getSouvisejici($form)
	{
		$tabulka = $form['souvisejici']->getValue();
		$vystup = array('' => 'žádná');
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

		return $vystup;
	}

	public function handleDelete($id)
	{
		$this->model = new Souvisejici();
		try
		{
			$this->model->delete($id);
			$this->getPresenter()->flashMessage('Související položka byla odstraněna.', 'ok');
		}
		catch(DibiException $e)
		{
			$this->getPresenter()->flashMessage('Nepodařilo se odstranit související položku.', 'error');
			Debug::processException($e, true);
		}

		/*if( $this->getPresenter()->isAjax() ) $this->invalidateControl('souvisejici');
		else $this->redirect('this');

		$this->getPresenter()->invalidateControl('souvisejici');
		$this->getPresenter()->invalidateControl();
		$this->invalidateControl('souvisejici');
		$this->invalidateControl();*/
		$this->redirect('this');
	}
}