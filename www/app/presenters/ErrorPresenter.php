<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Diagnostics\Debugger;

/**
 * Presenter chyb
 *
 * @author	Milan Pála
 */
class ErrorPresenter extends BasePresenter
{

	public function startup()
	{
		try
		{
			parent::startup();
		}
		catch (Exception $e)
		{
			// chybné připojení do databáze nesmí projít dále, ale rovnou se vykreslí
			if(!($e instanceof DibiException) || !in_array($e->getCode(), array(2002, 2006))) throw $e;
		}
	}

	/**
	 * @param  Exception
	 * @return void
	 */
	public function actionDefault($exception)
	{
		if($this->isAjax())
		{ // AJAX request? Just note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();
		}
		elseif($exception instanceof Nette\Application\BadRequestException)
		{
			$code = $exception->getCode();
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(in_array($code, array(403, 404, 500)) ? $code : '400');
			// log to access.log
			//Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
		}
		else
		{
			$this->setView('500'); // load template 500.latte
			if(!($exception instanceof DibiException) || !in_array($exception->getCode(), array(2002, 2006)))Debugger::log($exception, Debugger::ERROR); // and log exception
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
