<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Presenter chyb
 *
 * @author	Milan Pála
 */
class ErrorPresenter extends BasePresenter
{

	/**
	 * @param  Exception
	 * @return void
	 */
	public function actionDefault($exception)
	{
		if ($this->isAjax())
		{ // AJAX request? Just note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();

		}
		elseif ($exception instanceof ForbiddenRequestException)
		{
			$this->setView('403'); // load template 403.phtml
		}
		elseif ($exception instanceof BadRequestException)
		{
			$this->setView('404'); // load template 404.phtml
			//Debug::processException($exception, true);
		}
		else
 		{
			$this->setView('500'); // load template 500.phtml
 			Debug::processException($exception); // and handle error by Nette\Debug
		}
	}
	public function render403()
	{
		$this->setTitle('Nepovolený přístup');
	}

	public function render404()
	{
		$this->setTitle('Nenalezená stránka');
	}

	public function render500($e)
	{
		$this->setTitle('Závažná chyba');
	}

}
