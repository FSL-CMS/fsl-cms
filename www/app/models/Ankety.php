<?php

/**
 * Informační systém Krušnohorské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



/**
 * Model anket
 *
 * @author	Milan Pála
 */
class Ankety extends BaseModel
{
	/** @var string */
	protected $table = 'poll_control_questions';

	/** @var DibiConnection */
	protected $connection;
	

	public function __construct(HttpUploadedFile $fotka = NULL)
	{
		$this->connection = dibi::getConnection();
	}
	
	public function findAll()
	{
		return $this->connection->select('*')->from($this->table)->orderBy('[datum_zverejneni] ASC');
	}
	
	public function find($id)
	{
		return $this->connection->select('*')->from($this->table)->leftJoin('poll_control_answers ON [poll_control_answers].[questionId] = [poll_control_questions].[id]')->where('[poll_control_questions].[id] = %i', $id);
	}
	
	public function findOdpovediByAnketa($id)
	{
		return $this->connection
			->select('*')
			->from('poll_control_answers')
			->where('[questionID] = %i', $id);
	}
	
	/**
	 * Přidá odpověď k anketě
	 * @param int $id ID ankety
	 * @param array $data informace o odpovědi
	 * @return unknown_type
	 */
	public function pridejOdpoved($id, $data)
	{
		$data['questionId'] = $id;
		return $this->connection->insert('poll_control_answers', $data)->execute(Dibi::IDENTIFIER);
	}
	
	/**
	 * Upraví odpověď ankety
	 * @param $id ID odpovědi
	 * @param $data informace odpovědi
	 * @return unknown_type
	 */
	public function upravOdpoved($id, $data)
	{
		return $this->connection->update('poll_control_answers', $data)->where('[id] = %i', $id)->execute();
	}
	
	public function smazOdpoved($id)
	{
		return $this->connection->delete('poll_control_answers')->where('[id] = %i', $id)->execute();
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

	public function jeZverejnena($id)
	{
		$nalezeno = $this->connection->fetch('SELECT [id] FROM %n', $this->table, 'WHERE [datum_zverejneni] IS NOT NULL AND [id] = %i', $id);
		return (bool)$nalezeno;
	}
	
}