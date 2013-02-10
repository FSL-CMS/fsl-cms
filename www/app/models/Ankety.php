<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model anket
 *
 * @author	Milan Pála
 */
class Ankety extends BaseModel
{
	/** @var string */
	protected $table = 'pollie_questions';

	public function findAll()
	{
		return $this->connection->select('*')->from($this->table)->orderBy('[datum_zverejneni] ASC');
	}

	public function find($id)
	{
		return $this->connection->select('*')->from($this->table)->leftJoin('pollie_answers ON [pollie_answers].[questionId] = [pollie_questions].[id]')->where('[pollie_questions].[id] = %i', $id);
	}

	public function findOdpovediByAnketa($id)
	{
		return $this->connection
			->select('*')
			->from('pollie_answers')
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
		return $this->connection->insert('pollie_answers', $data)->execute(Dibi::IDENTIFIER);
	}

	/**
	 * Upraví odpověď ankety
	 * @param $id ID odpovědi
	 * @param $data informace odpovědi
	 * @return unknown_type
	 */
	public function upravOdpoved($id, $data)
	{
		return $this->connection->update('pollie_answers', $data)->where('[id] = %i', $id)->execute();
	}

	public function smazOdpoved($id)
	{
		return $this->connection->delete('pollie_answers')->where('[id] = %i', $id)->execute();
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
