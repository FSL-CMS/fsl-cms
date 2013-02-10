<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Komponenta vykreslující hlasování o kvalitě závodů
 *
 * @author	Milan Pála
  */
class HodnoceniControl extends BaseControl
{
	public function __construct()
	{
		parent::__construct();
	}

	private function hodnoceni($id)
	{
		$zavody = $this->presenter->context->zavody;
		$zavod = $zavody->find($id)->fetch();
		return array(round($zavod['hodnoceni']), $zavod['hodnoceni_pocet']);
	}

	public function render($id)
	{
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/hodnoceni.phtml');
		$this['hodnoceniForm']['id_zavodu']->setValue($id);
		$hodnoceni = $this->hodnoceni($id);
          $this['hodnoceniForm']['hodnoceni']->setValue($hodnoceni[0]);
		$template->pocetHlasu = $hodnoceni[1];

		$template->render();
	}

	public function createComponentHodnoceniForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addHidden('id_zavodu');
		$form->addSelect('hodnoceni', 'Hodnocení', array(1=>'Škoda času', 2 => 'Slušné', 3 => 'Průměrné', 4 => 'Podařené', 5 => 'Bezchybné'));
		$form['hodnoceni']->getControlPrototype()->class('hodnoceni');

		$form->addSubmit('save', 'Ohodnoť');

		$form->onSuccess[] = array($this, 'hodnoceniFormSubmitted');

		return $form;
	}

	public function hodnoceniFormSubmitted(Nette\Application\UI\Form $form)
	{
          if( $form['save']->isSubmittedBy() )
		{
			try
			{
				$zavody = $this->presenter->context->zavody;
				$zavody->ohodnot( $form['id_zavodu']->value, $form['hodnoceni']->value );
               }
			catch( DibiException $e )
			{
				$this->flashMessage('Závod se nepodařilo ohodnotit.', 'error');
			}
		}
		//$this->invalidateControl();
		$this->presenter->payload->hodnoceni = $this->hodnoceni($form['id_zavodu']->value);
		if (!$this->getPresenter()->isAjax())
		{
			$this->redirect('this');
		}
	}
}
