<?php

class Zavod implements IResource
{

	public $data;

	public function __get($name)
	{
		if($name == 'poradatele' && !isset($this->data['poradatele']))
		{
			$zavodyModel = new Zavody;
			return $zavodyModel->findPoradatele($this->getId());
		}

		return $this->data[$name];
	}

	public function __construct($data)
	{
		$this->data = $data;
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
