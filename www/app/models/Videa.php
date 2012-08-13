<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model videí
 *
 * @author	Milan Pála
 */
class Videa extends BaseModel
{
	/** @var string */
	protected $table = 'videa';

	/** @var DibiConnection */
	protected $connection;


	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function findAll()
	{
		return $this->connection->select('*')->from($this->table)->orderBy('[datum_pridani] ASC');
	}

	public function findByGalerie($id_galerie)
	{
		return $this->findAll()->where('[souvisejici] = "galerie" AND [id_souvisejiciho] = '.$id_galerie);
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

	public function insert(array $data)
	{
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$id = $this->connection->insertId();
		$this->lastInsertedId($id);
		return $ret;
	}

	public function delete($id)
	{
		return parent::delete($id)->execute();
	}

	public function deleteByGalerie($id)
	{
		return parent::delete(NULL)->clause('where', true)->where('[soubory].[souvisejici] = "galerie" AND [soubory].[id_souvisejiciho] = %i', $id)->execute();
	}

}