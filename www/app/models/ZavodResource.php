<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Security\IResource;

/**
 * Resource pro závody
 * @author Milan Pála
 */
class ZavodResource extends Nette\Object implements IResource
{

	private $data;

	/** @var Zavody */
	private $model;

	public function __construct(Zavody $model)
	{
		$this->model = $model;
	}

	/**
	 * @param array $data Jeden konkrétní závod.
	 */
	public function setInstance($data)
	{
		$this->data = $data;
	}

	public function &__get($name)
	{
		if($name == 'poradatele' && !isset($this->data['poradatele']))
		{
			return $this->model->findPoradatele($this->getId());
		}

		if(isset($this->data[$name])) return $this->data[$name];

		return parent::__get($name);
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
