<?php

/**
 * Description of LigaAssertion
 *
 * @author Milan
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
			foreach ($zavod->poradatele as $poradatel)
			{
				if ($poradatel->id_spravce == $user->getIdentity()->id) return true;
				if ($poradatel->id_kontaktni_osoby == $user->getIdentity()->id) return true;
			}
		}

		return false;
	}

}
