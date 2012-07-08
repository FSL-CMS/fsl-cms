<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model entit, které se zveřejňují
 *
 * @author	Milan Pála
 */
class Zverejnovane extends BaseModel
{
	protected $zverejnene = true;
	
	public function zobrazitNezverejnene()
	{
		$this->zverejnene = false;
	}
}
