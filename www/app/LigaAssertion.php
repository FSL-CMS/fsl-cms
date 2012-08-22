<?php

/**
 * Správce oprávnění pro zdroje závislé detailních informacích.
 *
 * @author Milan Pála
 */
class LigaAssertion implements IPermissionAssertion
{

	public function assert(Permission $acl, $roleId, $resourceId, $privilege)
	{
		$user = Environment::getUser();

		if ($resourceId == 'zavody')
		{
			$zavod = $acl->getQueriedResource();
			if(is_string($zavod)) return false;

			// je správce sboru nebo jeho kontaktní osoba
			foreach ($zavod->poradatele as $poradatel)
			{
				if ($poradatel->id_spravce == $user->getIdentity()->id) return true;
				if ($poradatel->id_kontaktni_osoby == $user->getIdentity()->id) return true;
			}
		}

		if ($resourceId == 'startovni_poradi')
		{
			$sp = $acl->getQueriedResource();
			if(is_string($sp)) return false;

			// Daný uživatel přihlásil toto SP
			if ($sp->id_autora == $user->getIdentity()->id) return true;

			// Uživatel je správce závodu
			$sboryModel = new Sbory;
			foreach($sboryModel->findByZavod($sp->id_zavodu)->fetchAll() as $poradatel)
			{
				if ($poradatel->id_spravce == $user->getIdentity()->id) return true;
				if ($poradatel->id_kontaktni_osoby == $user->getIdentity()->id) return true;
			}

			// Uživatel je správce sboru přihlášeného družstva
			if($sp->id_sboru_druzstva == $user->getIdentity()->id_sboru) return true;

		}

		return false;
	}

}
