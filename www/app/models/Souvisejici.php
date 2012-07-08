<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model související položek
 *
 * @author	Milan Pála
 */
class Souvisejici extends BaseModel
{

	/** @var string */
	protected $table = 'souvisejici';

	/** @var DibiConnection */
	protected $connection;

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}

	public function find($id)
	{
		return $this->connection
			->select('[id], [souvisejici] as [souvisejici], [id_souvisejiciho], [rodic], [id_rodice]')
			->from($this->table)
			->where('[id] = %i', $id);
	}

	public function findByRodic($rodic, $id_rodice, $souvisejici = false)
	{
		return $this->connection
			->select('[id], [souvisejici] as [souvisejiciTabulka], [id_souvisejiciho]')
			->from($this->table)
			->where('[rodic] = %s ', $rodic, 'AND [id_rodice] = %i', $id_rodice, '%if ', $souvisejici != false, ' AND [souvisejici] = %s', $souvisejici, ' %end');
	}

	public function insert(array $data)
	{
		if( $data['id_souvisejiciho'] == 0 ) throw new DibiException("ID souvisejícího nemůže být 0.");
		if( $data['id_rodice'] == 0 ) throw new DibiException("ID rodiče nemůže být 0.");
		$dataDoDB[] = $data;
		$data2['rodic'] = $data['souvisejici'];
		$data2['id_rodice'] = $data['id_souvisejiciho'];
		$data2['souvisejici'] = $data['rodic'];
		$data2['id_souvisejiciho'] = $data['id_rodice'];
		$dataDoDB[] = $data2;
		return $this->connection->query('INSERT INTO %n', $this->table, '%ex', $dataDoDB);
	}

	public function update($id, array $data)
	{
		return parent::update($id, $data)->execute();
	}
	
	public function delete($id)
	{
		$data = $this->find($id)->fetch();
		$data2['rodic'] = $data['souvisejici'];
		$data2['id_rodice'] = $data['id_souvisejiciho'];
		$data2['souvisejici'] = $data['rodic'];
		$data2['id_souvisejiciho'] = $data['id_rodice'];
		return parent::delete($id)->where(false)->where('%and OR %and', $data, $data2)->execute();
	}	
	
}
