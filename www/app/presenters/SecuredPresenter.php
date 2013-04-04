<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\ForbiddenRequestException;

/**
 * Zabezpečený presenter, všechny od něj odvozené presentery vyžadují přihlášení
 *
 * @author	Milan Pála
  */
class SecuredPresenter extends BasePresenter
{
	public function  __construct()
	{
		parent::__construct();
	}

	protected function startup()
	{
		parent::startup();

		$backlink = $this->getApplication()->storeRequest();
		if( $this->user === NULL || !$this->user->isLoggedIn() )
		{
			$this->flashMessage('Je nutný být přihlášen.', 'warning');
			$this->forward('Sprava:login', $backlink);
		}

		if( !$this->user->isAllowed(strtolower(preg_replace('/Presenter/', '', $this->reflection->name)), $this->getAction()) )
		{
			$this->flashMessage('Bohužel nemáte dostatečná oprávnění.', 'warning');
			throw new ForbiddenRequestException('Nedostatečná oprávnění.');
		}
	}
}
