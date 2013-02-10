<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

/**
 * Model nastavení
 *
 * @author	Milan Pála
 */
class Nastaveni extends BaseModel
{

	/** @var string */
	protected $table = 'nastaveni';

	/** @var DibiConnection */
	protected $connection;

	public function __construct(\DibiConnection $connection)
    {
        $this->connection = $connection;
    }

	public function find()
	{
		return $this->connection
				->select('[liga_nazev], [liga_zkratka], [liga_popis], [verze]')
				->from($this->table);
	}

	public function save(array $data)
	{
		parent::update(1, $data)->execute();
	}

}
