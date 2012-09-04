<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model funkcí rady
 *
 * @author	Milan Pála
 */
class FunkceRady extends BaseModel
{

	/** @var string */
	protected $table = 'funkce_rady';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->findAll()->where('[id] = %i', $id);
	}
	
	public function findAll()
	{
		return $this->connection
			->select('*')
		     ->from($this->table)
		     ->orderBy('[poradi]');
	}

	public function findAllToSelect()
	{
		return $this->connection
			->select('[id], [nazev]')
			->from($this->table);
	}

	public function insert( array $data)
	{
		$max = $this->connection->query('SELECT MAX(poradi) FROM funkce_rady LIMIT 1')->fetchSingle();
		$data['poradi%i'] = $max+1;
		$ret = parent::insert($data)->execute(Dibi::IDENTIFIER);
		$this->lastInsertedId($this->connection->insertId());
		return $ret;
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}

	public function delete($id, $force = 0)
	{
		$uzivateleModel = new Uzivatele;
		if( $force == 0 )
		{
			if( $uzivateleModel->findByFunkce($id)->count() != 0 ) throw new RestrictionException('Funkci nelze odstranit, je nastavená u některých uživatelů.');
		}
		foreach( $uzivateleModel->findByFunkce($id)->fetchAll() as $uzivatel )
		{
			$uzivateleModel->odstranFunkci($uzivatel->id);
		}
		return parent::delete($id)->execute();
	}
	
}
