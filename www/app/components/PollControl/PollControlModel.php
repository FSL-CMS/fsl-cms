<?php

/**
 * PollControlModel - part of PollControl plugin for Nette Framework for voting.
 *
 * @copyright  Copyright (c) 2009 Ondřej Brejla
 * @license    New BSD License
 * @link       http://nettephp.com/cs/extras/poll-control
 * @package    Nette\Extras
 * @version    0.1
 */
class PollControlModel extends Nette\Object implements IPollControlModel {

    const SESSION_NAMESPACE = '__poll_control';

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
    public $id;

    public $oldId;

    /**
     * Constructor of the poll control model layer.
     *
     * @param mixed $id Id of the current poll.
     */
    public function __construct($id = 0) {
		$this->id = $id;
		$this->oldId = $id;
		$this->connection = dibi::getConnection();

		if($this->id == 0) $this->id = $this->connection->fetchSingle('SELECT [id] FROM [poll_control_questions] WHERE [datum_zverejneni] IS NOT NULL ORDER BY [datum_zverejneni] DESC LIMIT 1');
    }

    /**
     * @see IPollControlModel::getAllVotesCount()
     */
    public function getAllVotesCount() {
        return $this->connection->fetchSingle('SELECT SUM(votes) FROM poll_control_answers WHERE questionId = %i', $this->id);
    }

    /**
     * @see IPollControlModel::getQuestion()
     */
    public function getQuestion() {
	    return $this->connection->fetchSingle('SELECT question FROM poll_control_questions WHERE id = %i', $this->id);
    }

    /**
     * @see IPollControlModel::getAnswers()
     */
    public function getAnswers() {
        $this->connection->fetchAll('SELECT id, answer, votes FROM poll_control_answers WHERE questionId = %i', $this->id);

        $answers = array();
        foreach ($this->connection->fetchAll('SELECT id, answer, votes FROM poll_control_answers WHERE questionId = %i', $this->id) as $row) {
            $answers[] = new PollControlAnswer($row->answer, $row->id, $row->votes);
        }

        return $answers;
    }

    /**
     * Makes vote for specified answer id.
     *
     * @param int $id Id of specified answer.
     */
    public function vote($id) {
        if ($this->isVotable()) {
            $this->connection->query('UPDATE poll_control_answers SET votes = votes + 1 WHERE id = %i', $id, ' AND questionId = %i', $this->id);

            $this->denyVotingForUser();
        } else {
            throw new BadRequestException('You can vote only once per hour.');
        }
    }

    /**
     * @see IPollControlModel::isVotable()
     */
    public function isVotable() {
	    $sess = Environment::getSession(self::SESSION_NAMESPACE);

        if ($sess->poll[$this->id] === 'TRUE') {
            return FALSE;
        } else {
            if ($this->connection->fetchSingle("SELECT COUNT(*) FROM poll_control_votes WHERE ip = '$_SERVER[REMOTE_ADDR]' AND questionId = %i AND date + INTERVAL 30 SECOND > NOW()", $this->id)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Disables voting for the user who had currently voted.
     */
    private function denyVotingForUser() {
        $sess = Environment::getSession(self::SESSION_NAMESPACE);

        $sess->poll[$this->id] = 'TRUE';

        $this->connection->query("INSERT INTO poll_control_votes (questionId, ip, date) VALUES ($this->id, '$_SERVER[REMOTE_ADDR]', NOW())");
    }

}
