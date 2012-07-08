<?php
/**
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.1
 */
 
/**
 * Uložný prostor pro stav formuláře přesměrovaného RequestButtonem.
 */
final class RequestButtonStorage extends Object
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}
	
	/**
	 * Uloží stav formuláře.
	 * Vrací klíč pro získání dat.
	 *
	 * @param string Adresa kam se vrací, presenter a action.
	 * @param array Adresa kam se vrací, parametry.
	 * @param string Cesta v komponentovém stromě k RequestButtonu, kterým se přejměrovává.
	 * @param string Cesta v komponentovém stromě k Formu z kterého se přejměrovává.
	 * @param array Stav formuláře.
	 * @return string
	 */
	static public function save($destination, $args, $name, $formName, $data)
	{
	  $key = substr(md5(lcg_value()), 0, 4);
    self::getSession()->{$key} = array(
			'destination' => $destination,
			'args' => $args,
			'name' => $name,
			'formName' => $formName,
			'data' => $data,
		);
		return $key;
	}

	/**
	 * Existují data pod tímto klíčem?
	 *
	 * @param string
	 * @return bool
	 */
	static public function is($backlinkId)
	{
	  if (!$backlinkId) return false;
		return (bool) self::getDestination($backlinkId);
	}

	/**
	 * Adresa kam se vrací, presenter a action.
	 *
	 * @param string
	 * @return string
	 */
	static public function getDestination($backlinkId)
	{
	  return self::getSessionData($backlinkId, 'destination');
	}

	/**
	 * Adresa kam se vrací, parametry.
	 *
	 * @param string
	 * @return array
	 */
	static public function getDestinationArgs($backlinkId)
	{
		return (array) self::getSessionData($backlinkId, 'args');
	}

	/**
	 * Cesta v komponentovém stromě k RequestButtonu, kterým se přejměrovává.
	 *
	 * @param string
	 * @return string
	 */
	static public function getName($backlinkId)
	{
		return self::getSessionData($backlinkId, 'name');
	}

	/**
	 * Cesta v komponentovém stromě k Formu z kterého se přejměrovává.
	 *
	 * @param string
	 * @return string
	 */
	static public function getFormName($backlinkId)
	{
		return self::getSessionData($backlinkId, 'formName');
	}

	/**
	 * Stav formuláře.
	 *
	 * @param string
	 * @return array
	 */
	static public function getData($backlinkId)
	{
		return (array) self::getSessionData($backlinkId, 'data');
	}

	/**
	 * @return Session
	 */
	static private function getSession()
	{
	  return Environment::getSession('RequestButtonStorage');
	}
	
	/**
	 * @param string
	 * @param string Která data?
	 * @return mixed
	 */
	static private function getSessionData($backlinkId, $name)
	{
	  static $cache;
		if (!isset($cache[$backlinkId]))
		{
      $cache[$backlinkId] = isset(self::getSession()->{$backlinkId})?self::getSession()->{$backlinkId}:array();
		}
		return isset($cache[$backlinkId][$name])?$cache[$backlinkId][$name]:NULL;
	}
}
