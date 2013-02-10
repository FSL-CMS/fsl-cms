<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Application\UI\Form;

/**
 * Komponenta vykreslující diskuze
 *
 * @author	Milan Pála
 */
class DiskuzeControl extends BaseControl
{

	/** @var Diskuze */
	protected $model;
	protected $render;
	protected $odpovedet;

	public function __construct()
	{
		$this->render = 'diskuze';
		$this->odpovedet = false;
	}

	public function handleZamknout($id)
	{
		$this->model = $this->presenter->context->diskuze;
		$this->model->zamknout($id);
		$this->invalidateControl('nastaveni' . $id);

		if(!$this->parent->isAjax()) $this->redirect('this');
		else $this->render = 'nastaveni';
	}

	public function handleOdemknout($id)
	{
		$this->model = $this->presenter->context->diskuze;
		$this->model->odemknout($id);
		$this->invalidateControl('nastaveni' . $id);

		if(!$this->parent->isAjax()) $this->redirect('this');
		else $this->render = 'nastaveni';
	}

	public function handleOdpovedet($id)
	{
		$this->odpovedet = true;

		$this->invalidateControl('nastaveni' . $id);

		//if( !$this->parent->isAjax() ) $this->redirect('this');
		$this->render = 'nastaveni';
	}

	public function handleSledovat($id)
	{
		$this->model = $this->presenter->context->diskuze;
		try
		{
			$this->model->sledovat($id, $this->presenter->user->getIdentity()->id);
		}
		catch (DibiException $e)
		{
			$this->parent->flashMessage('Nepodařilo se uložit sledování.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

		$this->invalidateControl('nastaveni' . $id);

		if(!$this->parent->isAjax()) $this->redirect('this');
		else $this->render = 'nastaveni';
	}

	public function handleNesledovat($id)
	{
		$this->model = $this->presenter->context->diskuze;
		try
		{
			$this->model->nesledovat($id, $this->parent->user->getIdentity()->id);
		}
		catch (DibiException $e)
		{
			$this->parent->flashMessage('Nepodařilo se odstranit sledování.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}

		$this->invalidateControl('nastaveni' . $id);

		if(!$this->parent->isAjax()) $this->redirect('this');
		else $this->render = 'nastaveni';
	}

	public function handleSmazatKomentar($id)
	{
		$komentareModel = $this->presenter->context->komentare;
		$komentar = $komentareModel->find($id)->fetch();

		try
		{
			$komentareModel->delete($id);
			$this->parent->flashMessage('Komentář byl úspěšně odstraněn.', 'ok');
		}
		catch (DibiException $e)
		{
			$this->parent->flashMessage('Nepodařilo se odstranit komentář.', 'error');
			Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		}
		catch (RestrictionException $e)
		{
			$this->parent->flashMessage($e->getMessage() . ' "Smazat diskuzi?":' . $this->presenter->link('Diskuze:smazat', array('id' => $komentar['id_diskuze'], 'force' => 1)), 'error');
		}

		if($this->parent->isAjax()) $this->invalidateControl('komentare' . $komentar['id_diskuze']);
		else $this->redirect('this');
	}

	public function handleUpravitKomentar($id)
	{
		$this->render = 'upravitKomentar';
		if($this->parent->isAjax()) $this->getPresenter()->setLayout(false);
	}

	public function renderUpravitKomentar($id)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/upravitKomentar.phtml');
		$komentareModel = $this->presenter->context->komentare;

		if($id != 0 && ($hodnoty = $komentareModel->find($id)->fetch()) !== false) $this['upravitKomentarForm']->setValues($hodnoty);

		$template->render();
	}

	public function komentarFormSubmitted(Nette\Application\UI\Form $form)
	{
		if($form['cancel']->isSubmittedBy())
		{
			if($this->parent->isAjax()) $this->invalidateControl('nastaveni' . $form['id_diskuze']->getValue());
			else $this->redirect('this');
		}
		elseif($form['save']->isSubmittedBy())
		{
			$komentareModel = $this->presenter->context->komentare;
			try
			{
				$komentareModel->insert(array('id_diskuze' => $form['id_diskuze']->value, 'text' => $form['text']->value, 'id_autora' => (int) $this->getPresenter()->user->getIdentity()->id, 'datum_pridani%sql' => 'NOW()')); //Debug::dump(dibi::$sql);
				$this->getPresenter()->flashMessage('Odpověď byla vložena do diskuze.');

				$sledovani = $this->presenter->context->sledovani;
				$sledovani->upozornit("diskuze", $form['id_diskuze']->value);
			}
			catch (DibiException $e)
			{
				$this->getPresenter()->flashMessage('Nepodařilo se vložit komentář.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}

			if($this->getPresenter()->isAjax())
			{
				$this->invalidateControl('komentare' . $form['id_diskuze']->getValue());
				$this->odpovedet = false;
			}
			else $this->redirect('this');
		}
	}

	public function upravitKomentarFormSubmitted(Nette\Application\UI\Form $form)
	{
		if($form['cancel']->isSubmittedBy())
		{
			if(!$this->isAjax()) $this->redirect('Diskuze:diskuze', $form['id_diskuze']->getValue());
		}
		elseif($form['save']->isSubmittedBy())
		{
			$komentare = $this->presenter->context->komentare;
			try
			{
				$komentare->update($form['id']->value, array('text' => $form['text']->value)); //Debug::dump(dibi::$sql);
				$this->parent->flashMessage('Komentář byl uložen.');
			}
			catch (DibiException $e)
			{
				$this->parent->flashMessage('Nepodařilo se uložit komentář.', 'error');
				Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
			}

			if($this->getPresenter()->isAjax())
			{
				$this->invalidateControl('komentare' . $form['id_diskuze']->getValue());
				$this->odpovedet = false;
				$this->render = 'diskuze';
			}
			else $this->parent->redirect('Diskuze:diskuze', $form['id_diskuze']->getValue());
		}
	}

	public function render($id, $souvisejiciTabulka = NULL)
	{
		if($id === NULL || $this->render == 'nastaveni') $id = $this->getParam('id');
		if($souvisejiciTabulka === NULL) $souvisejiciTabulka = $this->getPresenter()->getParam('souvisejiciTabulka');

		if($this->render == 'nastaveni') $this->renderNastaveni($id);
		elseif($this->render == 'upravitKomentar') $this->renderUpravitKomentar($id);
		else $this->renderDiskuze($id, $souvisejiciTabulka);
	}

	public function renderNastaveni($id)
	{
		$this->model = $this->presenter->context->diskuze;
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/nastaveni.phtml');

		$diskuze = $this->model->find($id)->fetchAll();
		$prvni = current($diskuze);

		//$this['komentarForm_'.$id]['id_diskuze']->value = $id;

		$template->diskuze = array();
		$template->mojeid = $id;
		$template->diskuze['zamknuto'] = $prvni['zamknuto'];
		$template->diskuze['id_diskuze'] = $prvni['id_diskuze'];
		$template->diskuze['odpovedet'] = $this->odpovedet;
		$template->diskuze['muze_pridat'] = $this->parent->user->isAllowed('komentare', 'add');
		$template->diskuze['muze_zamknout'] = $this->parent->user->isAllowed('diskuze', 'edit') || ($this->parent->user->isLoggedIn() == true && $prvni['id_autora'] == $this->parent->user->getIdentity()->id);
		$template->diskuze['sledovano'] = $prvni['zamknuto'];
		$template->diskuze['id_diskuze'] = $prvni['id_diskuze'];
		$template->diskuze['muze_sledovat'] = $this->parent->user->isAllowed('sledovani', 'add');
		$template->diskuze['sledovano'] = $this->parent->user->isLoggedIn() && $this->model->jeSledovana($id, $this->parent->user->getIdentity()->id);

		$template->render();
	}

	/**
	 * Vygeneruje diskuzi.
	 * @param int $id id diskuze nebo id prvku ze související tabulky
	 * @param string $souvisejiciTabulka název související tabulky
	 */
	public function renderDiskuze($id, $souvisejiciTabulka = NULL)
	{
		$this->model = $this->presenter->context->diskuze;
		$return = array();

		// vybere témata diskuzí s komentáři
		if($souvisejiciTabulka == 'zavody') $tmp_diskuze = $this->model->findByZavod($id);
		elseif($souvisejiciTabulka == 'clanky') $tmp_diskuze = $this->model->findByClanek($id);
		elseif($souvisejiciTabulka == 'terce') $tmp_diskuze = $this->model->findByTerce($id);
		elseif($souvisejiciTabulka == 'sbory') $tmp_diskuze = $this->model->findBySbor($id);
		elseif($souvisejiciTabulka == 'druzstva') $tmp_diskuze = $this->model->findByDruzstvo($id);
		else $tmp_diskuze = $this->model->find($id);

		// asociativní pole témat diskuzí a komentářů
		$tmp_diskuze = $tmp_diskuze->fetchAssoc('id_diskuze,id_komentare,=');

		// nastavení oprávnění diskuzím a komentářům
		$return['diskuze'] = array();
		foreach ($tmp_diskuze as $id_ => $disk)
		{
			//$this['komentarForm']['id_diskuze']->value = $id_;
			$prvni = current($disk);
			$return['diskuze'][$id_] = $prvni;
			$return['id_tematu'] = $prvni['id_tematu'];
			$return['id_souvisejiciho'] = 0;
			$return['tema'] = $prvni['tema'];
			$return['diskuze'][$id_]['komentare'] = $disk;
			$return['diskuze'][$id_]['muze_pridat'] = $this->parent->user->isAllowed('komentare', 'add');
			$return['diskuze'][$id_]['muze_zamknout'] = ($this->parent->user->isAllowed('diskuze', 'edit') && $this->parent->user->isLoggedIn() == true && $this->parent->jeAutor($prvni['id_autora'])) || ($this->parent->user->isLoggedIn() == true && $this->parent->jeAutor($prvni['id_autora']));
			$i = 0;
			foreach ($return['diskuze'][$id_]['komentare'] as &$komentar)
			{
				$komentar['muze_smazat'] = $this->parent->user->isAllowed('komentare', 'delete') || ($this->parent->user->isLoggedIn() == true && $this->parent->jeAutor($komentar['id_autora']) && $i >= count($return['diskuze'][$id_]['komentare']));
				$komentar['muze_upravit'] = $this->parent->user->isAllowed('komentare', 'edit') || ($this->parent->user->isLoggedIn() == true && $this->parent->jeAutor($komentar['id_autora']) && $i >= count($return['diskuze'][$id_]['komentare']));
				$i++;
			}

			$return['diskuze'][$id_]['muze_sledovat'] = $this->parent->user->isAllowed('sledovani', 'add');
			$return['diskuze'][$id_]['sledovano'] = $this->parent->user->isLoggedIn() && $this->model->jeSledovana($id_, $this->parent->user->getIdentity()->id);
		}

		$temata = $this->presenter->context->temata;
		if($souvisejiciTabulka !== NULL)
		{
			$tema = $temata->findBySouvisejici($souvisejiciTabulka)->fetch();
			$return['id_tematu'] = $tema['id'];
		}

		$return['muze_pridavat'] = $this->parent->user->isAllowed('diskuze', 'add');
		$return['souvisejiciTabulka'] = $souvisejiciTabulka;
		$return['odpovedet'] = $this->odpovedet;
		$return['id_souvisejiciho'] = $id;

		$this->model->noveZhlednuti($id);

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/diskuze.phtml');

		$souvisejiciControl = new SouvisejiciControl;
		$this->addComponent($souvisejiciControl, 'souvisejici');

		$template->diskuze = $return;
		$template->render();
	}

	public function createComponent($name)
	{
		if(preg_match('~komentarForm_([0-9]+)~', $name, $matches))
		{
			$idDiskuze = $matches[1];
			$this->createInstanceKomentarForm($name, $idDiskuze);
		}
		elseif($name == 'komentarForm')
		{
			$this->createInstanceKomentarForm($name);
		}
		elseif($name == 'upravitKomentarForm')
		{
			$this->createInstanceUpravitKomentarForm($name);
		}
	}

	public function createInstanceKomentarForm($name, $idDiskuze = 0)
	{
		$form = new Form($this, $name);

		//$form->getElementPrototype()->class('ajax-komentar');

		$form->addHidden('id_diskuze', $idDiskuze);
		$form->addTexylaTextArea('text', 'Text zprávy')
				->addRule(Form::FILLED, 'Vyplňte, prosím, text zprávy.');
		$form->addSubmit('save', 'Odeslat odpověď');
		$form->addSubmit('cancel', 'Vrátit se zpět')
				->setValidationScope(FALSE);

		$form->onSuccess[] = array($this, 'komentarFormSubmitted');
	}

	public function createInstanceUpravitKomentarForm($name)
	{
		$form = new Form($this, $name);

		//$form->getElementPrototype()->class('ajax-komentar');

		$form->addHidden('id');
		$form->addHidden('id_diskuze');
		$form->addTexylaTextArea('text', 'Text zprávy')
				->addRule(Form::FILLED, 'Vyplňte, prosím, text zprávy.');
		$form->addSubmit('save', 'Uložit komentář');
		$form->addSubmit('cancel', 'Zrušit')
				->setValidationScope(FALSE);

		$form->onSuccess[] = array($this, 'upravitKomentarFormSubmitted');
	}

}
