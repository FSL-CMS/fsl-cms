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
 * The bi-directional router.
 *
 * @author     David Grudl
 */
interface IRouter
{
	/**#@+ flag */
	const ONE_WAY = 1;
	const SECURED = 2;
	/**#@-*/

	/**
	 * Maps HTTP request to a PresenterRequest object.
	 * @param  IHttpRequest
	 * @return PresenterRequest|NULL
	 */
	function match(IHttpRequest $httpRequest);

	/**
	 * Constructs absolute URL from PresenterRequest object.
	 * @param  IHttpRequest
	 * @param  PresenterRequest
	 * @return string|NULL
	 */
	function constructUrl(PresenterRequest $appRequest, IHttpRequest $httpRequest);

}
