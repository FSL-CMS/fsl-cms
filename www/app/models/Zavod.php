<?php

/**
 * Resource pro závody
 * @author Milan Pála
 */
class ZavodResource implements IResource
{
	private $data;

	/**
	 * Konsktruktor příjmá data o jednom závodu.
	 * @param array $data Jeden konkrétní závod.
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	public function __get($name)
	{
		if($name == 'poradatele' && !isset($this->data['poradatele']))
		{
			$zavodyModel = new Zavody;
			return $zavodyModel->findPoradatele($this->getId());
		}

		return $this->data[$name];
	}

	public function getId()
	{
		return $this->data['id'];
	}

	public function getResourceId()
	{
		return 'zavody';
	}

}
