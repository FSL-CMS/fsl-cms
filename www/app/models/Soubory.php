<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model souborů
 *
 * @author	Milan Pála
 */
class Soubory extends BaseSoubory
{
	public function __construct(HttpUploadedFile $soubor = NULL)
	{
		parent::__construct($soubor);
	}

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

	public function findIdByUri($uri)
	{
		return $this->findBy()
			->where('%n.uri = %s', $this->table, $uri);
	}

}