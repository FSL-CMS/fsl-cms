<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Router využívající tabulku urls.
 *
 * @author Milan Pála
 */
class UrlsRouter extends Route implements IRouter
{
	private $flags;

	public function __construct($foo, $params, $flags = 0)
	{
		parent::__construct($foo, $params, $flags);
		$this->flags = $flags;
	}

	public function match(IHttpRequest $context)
	{
		$context->getUri()->path;

		$params = $context->getQuery();

		$urlsModel = new Urls;

		$page = array();

		if( ($page = $urlsModel->findByUrl($context->getUri()->path)->fetch()) == false )
		{
			if( ($page = $urlsModel->findRedirectedByUrl($context->getUri()->path)->fetch()) == false ) return NULL;
		}

		$prequest = new PresenterRequest(
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
 	* Constructs URL path from NPresenterRequest object.
 	* @param  Nette\Web\IHttpRequest
 	* @param  PresenterRequest
 	* @return string|NULL
 	*/
	public function constructUrl(PresenterRequest $request, IHttpRequest $context)
	{
		if ($this->flags & self::ONE_WAY) {
			return NULL;
		}

		$actualPresenter = $request->getPresenterName();
		$actualParams = $request->getParams();

		$urlsModel = new Urls;

		if( isset($actualParams['id']) && ($url = $urlsModel->findUrlByPresenterAndActionAndParam($actualPresenter, $actualParams['action'], $actualParams['id'])->fetch()) == FALSE ) return NULL;
		if( !isset($actualParams['id']) && ($url = $urlsModel->findUrlByPresenterAndAction($actualPresenter, $actualParams['action'])->fetch()) == FALSE ) return NULL;

		$uri = $context->getUri()->basePath.substr($url['url'], 1);

		unset($actualParams['action']);
		unset($actualParams['id']);

		$query = http_build_query($actualParams, '', '&');
		if ($query !== '') $uri .= '?' . $query;

		return $uri;
	}
}