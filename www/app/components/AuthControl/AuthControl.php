<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Komponenta vykreslující přihlašování uživatelů
 *
 * @author	Milan Pála
  */
class AuthControl extends BaseControl
{
	/** @persistent */
	public $backlink = NULL;
	
	public $render = NULL;
	public $uzivatel = NULL;

	public function __construct()
	{
		if( $this->render === NULL ) $this->render = 'login';
		parent::__construct();
	}

	/**
	 * Login form component factory.
	 * @return mixed
	 */
	protected function createComponentLoginForm()
	{
		$form = new AppForm;
		
		$form->getElementPrototype()->class('ajax');
		
		$form->addText('login', 'Email')
			->addRule(Form::FILLED, 'Je nutné vyplnit přihlašovací jméno.');
			
		if( isset($_COOKIE['user']) ) $form['login']->setDefaultValue($_COOKIE['user']);

		$form->addPassword('heslo', 'Heslo')
			->addRule(Form::FILLED, 'Je nutné vyplnit heslo.');

		$form->addSubmit('save', 'Přihlásit se');

		$form->onSubmit[] = array($this, 'loginFormSubmitted');

		return $form;
	}

	public function loginFormSubmitted($form)
	{
		try
		{
			$this->parent->prihlas( array( 'login' => $form['login']->getValue(), 'heslo' => $form['heslo']->getValue() ) );
			
			$this->parent->flashMessage('Přihlášení proběhlo úspěšně.', 'ok');
			
			$this->parent->getApplication()->restoreRequest($this->getPresenter()->getParam('backlink'));
			
			$this->redirect('this');

		}
		catch (AuthenticationException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
			$this->invalidateControl('flashes');
			if( !$this->getPresenter()->isAjax() ) $this->redirect('this');
		}
	}
	
	public function render()
	{
		$this->renderLogin();
 	}
	
	public function renderLogin()
	{
		$user = Environment::getUser();
		
		$template = $this->template;
		if( !$user->isLoggedIn() )
		{
			$template->setFile(dirname(__FILE__) . '/login.phtml');
		}
		else
		{
			$uzivatele = new Uzivatele;
			$this->uzivatel = $uzivatele->find($user->getIdentity()->id)->fetch();
			$template->setFile(dirname(__FILE__) . '/logined.phtml');
		}
		$template->render();
	}
	
	public function handleLogin()
	{
		$this->render = 'login';
		$this->invalidateControl();	
	}
	
	public function handleLogout()
	{
		Environment::getUser()->logout(true);
		$this->parent->flashMessage('Odhlášení bylo úspěšné.');
		$this->redirect('this');
	}

}
