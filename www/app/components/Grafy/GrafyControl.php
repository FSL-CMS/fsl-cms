<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Komponenta vykreslující grafy
 *
 * @author	Milan Pála
  */
class GrafyControl extends Control
{
	public $width;
	public $height;
	public $source;
	public $dataUrl;
	public $dataUrlParams;

	public function __construct()
	{
		$this->width = 450;
		$this->height = 300;
		$this->source = 'grafy/Grafy.xap';
	}

	public function render(array $params)
	{
		$this->width = $params[0];
          $this->height = $params[1];

		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/grafy.phtml');

          $uri = Environment::getHttpRequest()->getUri();
		$this->template->hostUri = $uri->hostUri;
		$this->source = $uri->baseUri.$this->source;

		if( !isset($params[3]) ) $this->dataUrl = $this->parent->link( $params[2] );
		else $this->dataUrl = $this->parent->link( $params[2], $params[3] );
		
		$dataProGrafJSON = file_get_contents( $uri->scheme.'://'.$uri->getAuthority().'/'.$this->dataUrl);
		if( $dataProGrafJSON !== false && ($dataProGraf = json_decode($dataProGrafJSON, true)) !== NULL )
		{
			$this->height = -20;
			foreach( $dataProGraf as $graf )
			{
				$this->width = $graf['sirka'];
				$this->height += $graf['vyska'] + 20;
			}
		}
		
		$template->render();
	}
}