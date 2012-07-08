<?php

class MyTexy extends Texy
{
	public function __construct()
	{
		parent::__construct();
		$this->imageModule->fileRoot = APP_DIR . "../data";
		$this->imageModule->root = Environment::getVariable("baseUri") . "files";
		$this->headingModule->top = 2;
		$this->allowed['phrase/del'] = true;   // --deleted--
		TEXY::$advertisingNotice = false;
	}
}

