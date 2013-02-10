<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Továrna pro model ukládající soubory
 *
 * @author Milan Pála
 */
interface IFileUploaderFileManager
{
	public function save(Nette\Http\FileUpload $soubor);
}
