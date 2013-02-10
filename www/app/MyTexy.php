<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Utils\Strings;
use Nette\Templating\FileTemplate,
	Nette\Latte\Engine;

/**
 * Vlastní nastavení Texy
 *
 * @author Milan Pála
 *
 * @author Jan Marek
 * @license MIT
 */
class MyTexy extends \Texy
{

	/**
	 * @param \Nette\Http\Request $httpRequest
	 */
	public function __construct(\Nette\Http\Request $httpRequest)
	{
		parent::__construct();

		// output
		$this->setOutputMode(self::HTML5);
		$this->htmlOutputModule->removeOptional = false;
		self::$advertisingNotice = false;

		// headings
		$this->headingModule->balancing = \TexyHeadingModule::FIXED;

		// phrases
		$this->allowed['phrase/ins'] = true;   // ++inserted++
		$this->allowed['phrase/del'] = true;   // --deleted--
		$this->allowed['phrase/sup'] = true;   // ^^superscript^^
		$this->allowed['phrase/sub'] = true;   // __subscript__
		$this->allowed['phrase/cite'] = true;   // ~~cite~~
		$this->allowed['deprecated/codeswitch'] = true; // `=code
		// images
		$this->imageModule->fileRoot = DATA_DIR;
		$this->imageModule->root = $httpRequest->url->baseUrl;

		// flash, youtube.com, stream.cz, gravatar handlers
		$this->addHandler("phrase", array($this, "netteLink"));
	}

	/**
	 * Template factory
	 * @return Template
	 */
	private function createTemplate()
	{
		$template = new FileTemplate;
		$template->registerFilter(new Engine);
		return $template;
	}

	/**
	 * @param TexyHandlerInvocation  handler invocation
	 * @param string
	 * @param string
	 * @param TexyModifier
	 * @param TexyLink
	 * @return TexyHtml|string|FALSE
	 */
	public function netteLink($invocation, $phrase, $content, $modifier, $link)
	{
		// is there link?
		if(!$link) return $invocation->proceed();

		$url = $link->URL;

		if(Strings::startsWith($url, "plink://"))
		{
			$url = substr($url, 8);
			list($presenter, $params) = explode("?", $url, 2);

			$arr = array();

			if($params)
			{
				parse_str($params, $arr);
			}

			$link->URL = $this->presenter->link($presenter, $arr);
		}

		return $invocation->proceed();
	}

}
