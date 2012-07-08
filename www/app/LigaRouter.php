<?php

class LigaRouter extends Route implements IRouter
{
	private $table = NULL;
	private $column = NULL;
	private $actionName = NULL;
	private $presenterName = NULL;
	private $paramName = 'id';
	private $flags;

	public function __construct($foo, $params, $flags = 0)
	{
		$this->actionName = $params['action'];
		$this->presenterName = $params['presenter'];
		$this->column = $params['column'];
		$this->table = $params['table'];

		$this->flags = $flags | self::$defaultFlags;
	}
		
	public function match(IHttpRequest $context)
	{
		//echo $context->getUri();
		//echo $this->table;
		$params = $context->getQuery();
		// závody
		if ($this->table == 'zavody' && preg_match('#/zavody/([^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// staré závody
		elseif ($this->table == 'zavody' && preg_match('#/(souteze\.php\?akce=vysledky&soutez=[0-9]+)$#', $context->getUri(), $matches)) {
			$params = array();
		}
		// články
		elseif ($this->table == 'clanky' && preg_match('#/clanky/([0-9]+)[^/]+\.html$#', $context->getUri()->path, $matches)) {
		//elseif (preg_match('#/clanky/([^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// staré články
		elseif ($this->table == 'clanky' && preg_match('#/(view\.php\?cisloclanku=[0-9]+)$#', $context->getUri(), $matches)) {
			$params = array();
		}
		// sbory
		elseif ($this->table == 'sbory' && preg_match('#/sbory/([^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// družstva
		elseif ($this->table == 'druzstva' && preg_match('#/druzstva/([^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// uživatele
		elseif ($this->table == 'uzivatele' && preg_match('#/uzivatele/([^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// fóra
		elseif ($this->table == 'temata' && preg_match('#/forum/([^/]+)/$#', $context->getUri()->path, $matches)) {
		}
		// diskuze
		elseif ($this->table == 'diskuze' && preg_match('#/forum/([^/]+/[^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// fotogalerie
		elseif ($this->table == 'fotogalerie' && preg_match('#/fotogalerie/([^/]+)/$#', $context->getUri()->path, $matches)) {
		}
		// staré fotogalerie
		elseif ($this->table == 'fotogalerie' && preg_match('#/(gallery\.php\?akce=galerie_ukaz&galerie_id=[0-9]+)$#', $context->getUri(), $matches)) {
			$params = array();
		}
		// fotky
		elseif ($this->table == 'soubory' && preg_match('#/fotogalerie/([^/]+/[^/]+)$#', $context->getUri()->path, $matches)) {
		}
		// stránky
		elseif ($this->table == 'stranky' && preg_match('#/([^/]+)\.html$#', $context->getUri()->path, $matches)) {
		}
		// terče
		elseif ($this->table == 'terce' && preg_match('#/terce/([0-9]+)[^/]+$#', $context->getUri()->path, $matches)) {
		}
		else
		{
			return NULL;
			
		}
		//print_r($matches);
		$id = $matches[1];
		if( in_array( $this->table, array('zavody','clanky','stranky','sbory', 'druzstva', 'uzivatele', 'temata', 'diskuze', 'fotogalerie', 'soubory', 'terce') ) )
		{
			if( $this->actionName == 'fotka' ) $tridaModelu = 'Fotky';
			else $tridaModelu = strtoupper($this->table[0]).substr($this->table, 1);
			$model = new $tridaModelu;
			if( in_array($this->table, array('clanky', 'terce')) ) $radek = $model->find($id)->fetch();
			else $radek = $model->findIdByUri($id, $this->column)->fetchSingle();
			if( !$radek ) return NULL;
		}		
		if( in_array($this->table, array('clanky', 'terce')) ) $params['id'] = $radek['id'];
		else $params['id'] = $radek;
		$params['action'] = $this->actionName;

		//print_r($this->presenterName);
		
		$prequest = new PresenterRequest(
			$this->presenterName,
			$context->getMethod(),
			$params,
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
		
		if( !in_array( $actualParams['action'], array('zavod', 'stranka', 'clanek', 'sbor', 'druzstvo', 'uzivatel', 'forum', 'diskuze', 'fotogalerie', 'fotka', 'terce') ) ) return NULL;
		
		if ($actualPresenter != $this->presenterName && $actualParams['action'] != $this->actionName)
		{
			return NULL;
		}

		if (!isset($actualParams[$this->paramName]))
		{
			return NULL;
		}

		$nazev = dibi::fetchSingle('SELECT %n FROM %n WHERE id = %i', $this->column, $this->table, $actualParams[$this->paramName] );		

		if( empty($nazev) ) return NULL;

		if( $this->table == 'stranky' ) $uri = $context->getUri()->basePath . rawurlencode($nazev).'.html';
		if( $this->table == 'zavody' ) $uri = $context->getUri()->basePath . 'zavody/'.$nazev.'.html';
		if( $this->table == 'clanky' ) $uri = $context->getUri()->basePath . 'clanky/'.$nazev.'.html';
		if( $this->table == 'sbory' ) $uri = $context->getUri()->basePath . 'sbory/'.$nazev.'.html';
		if( $this->table == 'druzstva' ) $uri = $context->getUri()->basePath . 'druzstva/'.$nazev.'.html';
		if( $this->table == 'uzivatele' ) $uri = $context->getUri()->basePath . 'uzivatele/'.$nazev.'.html';
		if( $this->table == 'temata' ) $uri = $context->getUri()->basePath . 'forum/'.$nazev.'/';
		if( $this->table == 'diskuze' ) $uri = $context->getUri()->basePath . 'forum/'.$nazev.'.html';
		if( $this->table == 'fotogalerie' ) $uri = $context->getUri()->basePath . 'fotogalerie/'.$nazev.'/';
		if( $this->table == 'soubory' ) $uri = $context->getUri()->basePath . 'fotogalerie/'.$nazev;
		if( $this->table == 'terce' ) $uri = $context->getUri()->basePath . 'terce/'.$nazev;
		unset($actualParams[$this->paramName], $actualParams['action']);

		$query = http_build_query($actualParams, '', '&');
		if ($query !== '') $uri .= '?' . $query;

		return $uri;
	}
}