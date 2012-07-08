<?php

class NoImageTexy extends MyTexy
{
	public function __construct()
	{
		parent::__construct();
		$this->allowed['image/definition'] = FALSE;
		$this->allowed['image'] = FALSE;
	}
}

