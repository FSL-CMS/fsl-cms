<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model souborů
 *
 * @author	Milan Pála
 */
class Soubory extends BaseSoubory
{

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		return $ret;
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

}
