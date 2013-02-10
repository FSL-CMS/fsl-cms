<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */
use Nette\Security\IResource;

/**
 * Resource pro startovní pořadí.
 *
 * @author Milan Pála
 */
class StartovniPoradiResource extends Nette\Object implements IResource
{

	private $data;

	/**
	 * Konsktruktor příjmá data o startovním pořadí.
	 * @param array $data Jedno konkrétní startovní pořadí včetně detailů o družstvu.
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	public function &__get($name)
	{
		return $this->data[$name];
	}

	public function getId()
	{
		return $this->data['id'];
	}

	public function getResourceId()
	{
		return 'startovni_poradi';
	}

}
