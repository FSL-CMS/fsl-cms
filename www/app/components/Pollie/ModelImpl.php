<?php

namespace OndrejBrejla\Pollie;

use Nette\Object;
use Nette\Environment;
use DibiConnection;

/**
 * ModelImpl - part of Pollie plugin for Nette Framework for voting.
 *
 * @copyright  Copyright (c) 2009 Ondřej Brejla
 * @license    New BSD License
 * @link       http://github.com/OndrejBrejla/Pollie
 */
class ModelImpl extends Object implements Model
{
	const SESSION_NAMESPACE = '__pollie';

	/**
	 * Connection to the database.
	 *
	 * @var DibiConnection Connection to the database.
	 */
	private $connection;

	/**
	 * Id of the current poll.
	 *
	 * @var mixed Id of the current poll.
	 */
	private $id;

	public function getId()
	{
		return $this->id;
	}

	/**
	 * Constructor of the poll control model layer.
	 *
	 * @param mixed $id Id of the current poll.
	 */
	public function __construct(\DibiConnection $connection)
	{
		$this->connection = $connection;

		$this->id = $this->connection->fetchSingle('SELECT [id] FROM [pollie_questions] WHERE [datum_zverejneni] IS NOT NULL ORDER BY [datum_zverejneni] DESC LIMIT 1');

		//$sess = Environment::getSession(self::SESSION_NAMESPACE);
		//$sess->poll[$id] = FALSE;
	}

	/**
	 * @see Model::getAllVotesCount()
	 */
	public function getAllVotesCount()
	{
		return $this->connection->fetchSingle('SELECT SUM(votes) FROM pollie_answers WHERE questionId = %i', $this->id);
	}

	/**
	 * @see Model::getQuestion()
	 */
	public function getQuestion()
	{
		return $this->connection->fetchSingle('SELECT question FROM pollie_questions WHERE id = %i', $this->id);
	}

	/**
	 * @see Model::getAnswers()
	 */
	public function getAnswers()
	{
		$this->connection->fetchAll('SELECT id, answer, votes FROM pollie_answers WHERE questionId = %i', $this->id);

		$answers = array();
		foreach ($this->connection->fetchAll('SELECT id, answer, votes FROM pollie_answers WHERE questionId = %i', $this->id) as $row)
		{
			$answers[] = new Answer($row->answer, $row->id, $row->votes);
		}

		return $answers;
	}

	/**
	 * Makes vote for specified answer id.
	 *
	 * @param int $id Id of specified answer.
	 */
	public function vote($id)
	{
		if ($this->isVotable())
		{
			$this->connection->query('UPDATE pollie_answers SET votes = votes + 1 WHERE id = %i', $id, ' AND questionId = %i', $this->id);

			$this->denyVotingForUser();
		}
		else
		{
			throw new \Nette\Application\BadRequestException('Můžete hlasovat jednou za hodinu.');
		}
	}

	/**
	 * @see Model::isVotable()
	 */
	public function isVotable()
	{
		if ($this->connection->fetchSingle('SELECT COUNT(*) FROM [pollie_votes] WHERE [ip] = %s AND [questionId] = %i AND [date] > DATE_SUB(CURDATE(), INTERVAL 7 DAY)', $_SERVER['REMOTE_ADDR'], $this->id))
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Disables voting for the user who had currently voted.
	 */
	private function denyVotingForUser()
	{
		/* $sess = Environment::getSession(self::SESSION_NAMESPACE);

		  $sess->poll[$this->id] = TRUE; */

		$this->connection->query("INSERT INTO pollie_votes (questionId, ip, date) VALUES ($this->id, '$_SERVER[REMOTE_ADDR]', NOW())");
	}

}
