<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Security
 */



/**
 * Performs authentication.
 *
 * @author     David Grudl
 */
interface IAuthenticator
{
	/**#@+ Credential key */
	const USERNAME = 'username';
	const PASSWORD = 'password';
	/**#@-*/

	/**#@+ Exception error code */
	const IDENTITY_NOT_FOUND = 1;
	const INVALID_CREDENTIAL = 2;
	const FAILURE = 3;
	const NOT_APPROVED = 4;
	/**#@-*/

	/**
	 * Performs an authentication against e.g. database.
	 * and returns IIdentity on success or throws AuthenticationException
	 * @param  array
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	function authenticate(array $credentials);

}
