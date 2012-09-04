<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
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
