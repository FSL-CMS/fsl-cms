<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette
 */



/**
 * Custom output for Debug.
 *
 * @author     David Grudl
 */
interface IDebuggable
{

	/**
	 * Returns custom panels.
	 * @return array
	 */
	function getPanels();

}
