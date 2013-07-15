<?php

/**
 * FSL CMS - Redakční systém pro hasičské ligy
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */



/**
 * Model uživatele přihlašujícího se přes Facebook Connect
 *
 * @author	Milan Pála
 */
class FacebookUzivatele extends Uzivatele
{
	public function authenticate(array $credentials)
	{
		$login = $credentials[self::USERNAME];

		// přečteme záznam o uživateli z databáze
		$row = $this->findByFacebookId($login)->fetch();

		if (!$row) { // uživatel nenalezen?
			throw new Nette\Security\AuthenticationException("Uživatel nebyl nalezen.", self::IDENTITY_NOT_FOUND);
		}

		$identita = new Nette\Security\Identity($row->jmeno, $row->opravneni); // vrátíme identitu
		$identita->id = $row->id;
		$identita->id_sboru = $row->id_sboru;
		return $identita;
	}

	public function findByFacebookId($id)
	{
		return $this->findAll()
               ->where('[uzivatele].[facebookId] = %i', $id);
	}

}
