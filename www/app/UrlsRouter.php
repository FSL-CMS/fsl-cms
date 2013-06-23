<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\IRouter;

/**
 * Router využívající tabulku urls.
 *
 * @author Milan Pála
 */
class UrlsRouter extends Nette\Application\Routers\Route implements IRouter
{

	private $flags;

	/*public function __construct($foo, $params, $flags = 0)
	{
		parent::__construct($foo, $params, $flags);
		dump($foo);
		$this->flags = $flags;
	}*/

	public function match(Nette\Http\IRequest $httpRequest)
	{
		//$httpRequest->getUrl()->getPath();

		$params = $httpRequest->getQuery();

		$urlsModel = Nette\Environment::getService('urls');
		$context = Nette\Environment::getHttpRequest();
		$page = array();

		$url = $context->getUrl()->path.'?'.$context->getUrl()->query;

		if(($page = $urlsModel->findByUrl($url)->fetch()) == false)
		{
			if(
					($page = $urlsModel->findRedirectedByUrl($url)->fetch()) == false
					&&
					($page = $urlsModel->findByUrl($context->getUrl()->path)->fetch()) == false
					&&
					($page = $urlsModel->findByUrl(substr($context->getUrl()->path, strlen($context->getUrl()->scriptPath)-1))->fetch()) == false
			) return NULL;
		}

		$prequest = new Nette\Application\Request(
						$page['presenter'],
						$context->getMethod(),
						$params + array('action' => $page['action'], 'id' => $page['param']),
						$context->getPost(),
						$context->getFiles(),
						array('secured' => $context->isSecured())
		);

		return $prequest;
	}

	/**
	 * Constructs absolute URL from Request object.
	 * @return string|NULL
	 */
	public function constructUrl(Nette\Application\Request $request, Nette\Http\URL $url)
	{
		if($this->flags & self::ONE_WAY)
		{
			return NULL;
		}

		$actualPresenter = $request->getPresenterName();
		$actualParams = $request->getParameters();

		$urlsModel = Nette\Environment::getService('urls');
		$context = Nette\Environment::getHttpRequest();

		if(isset($actualParams['id']) && ($url = $urlsModel->findUrlByPresenterAndActionAndParam($actualPresenter, $actualParams['action'], $actualParams['id'])) == FALSE) return NULL;
		if(!isset($actualParams['id']) && ($url = $urlsModel->findUrlByPresenterAndAction($actualPresenter, $actualParams['action'])) == FALSE) return NULL;

		$url = $context->getUrl()->basePath . substr($url['url'], 1);

		unset($actualParams['action']);
		unset($actualParams['id']);

		$query = http_build_query($actualParams, '', '&');
		if($query !== '') $url .= '?' . $query;

		return $url;
	}

}
