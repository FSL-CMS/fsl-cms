<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Application
 */



/**
 * Signal exception.
 *
 * @author     David Grudl
 */
class BadSignalException extends BadRequestException
{
	/** @var int */
	protected $defaultCode = 403;

}
