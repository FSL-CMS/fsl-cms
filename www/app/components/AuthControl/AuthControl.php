<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\UI\Form;

/**
 * Komponenta vykreslující přihlašování uživatelů
 *
 * @author	Milan Pála
 */
class AuthControl extends BaseControl
{

	/** @persistent */
	public $backlink = NULL;

	/** @var string */
	public $render = NULL;

	public $uzivatel = NULL;

	public function __construct()
	{
		parent::__construct();

		if($this->render === NULL) $this->render = 'login';
	}

	/**
	 * Login form component factory.
	 * @return mixed
	 */
	protected function createComponentLoginForm($name)
	{
		$form = new Form($this, $name);

		$form->getElementPrototype()->class('ajax');

		$form->addText('login', 'E-mail')
			->addRule(Form::FILLED, 'Je nutné vyplnit přihlašovací e-mail.');

		if(isset($_COOKIE['user'])) $form['login']->setDefaultValue($_COOKIE['user']);

		$form->addPassword('heslo', 'Heslo')
			->addRule(Form::FILLED, 'Je nutné vyplnit heslo.');

		$form->addSubmit('save', 'Přihlásit se');

		$form->onSuccess[] = array($this, 'loginFormSubmitted');
	}

	public function loginFormSubmitted($form)
	{
		try
		{
			$this->parent->prihlas(array('login' => $form['login']->getValue(), 'heslo' => $form['heslo']->getValue()));

			$this->parent->flashMessage('Přihlášení proběhlo úspěšně.', 'ok');

			$this->parent->getApplication()->restoreRequest($this->getPresenter()->getParam('backlink'));
			$this->presenter->redirect('this');
		}
		catch (Nette\Security\AuthenticationException $e)
		{
			$this->flashMessage($e->getMessage(), 'warning');
			$this->invalidateControl('flashes');
			if(!$this->getPresenter()->isAjax()) $this->redirect('this');
		}
	}

	public function render()
	{
		$this->renderLogin();
	}

	public function renderLogin()
	{
		$user = $this->presenter->getUser();

		$template = $this->template;
		if(!$user->isLoggedIn())
		{
			$template->setFile(dirname(__FILE__) . '/login.phtml');
		}
		else
		{
			$uzivatele = $this->presenter->context->uzivatele;
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
		$this->presenter->getUser()->logout(true);
		$this->parent->flashMessage('Odhlášení bylo úspěšné.');
		$this->redirect('this');
	}

}
