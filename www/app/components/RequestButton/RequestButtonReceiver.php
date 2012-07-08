<?php
/**
 * RequestButton
 * Umožnuje přesměrovávat se mezi formuláři a neztratit neuložený obsah.
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.1
 */
 
 
require_once dirname(__FILE__) . '/RequestButtonHelper.php';

/**
 * Umožnuje zpracovat a vrátit požadavek od RequestButtonu.
 * Zjednodušuje práci s RequestButtonem (není potřeba volat pomocnou metodu).
 */
class RequestButtonReceiver extends AppForm
{
	/**
	 * Přidá do action backlink, aby formulář i po odeslání věděl kam se má vrátit.
	 *
	 * @param Link|string
	 * @return self
	 */
	public function setAction($url)
	{
		return parent::setAction(RequestButtonHelper::prepareAction($this, $url));
	}

	/**
	 * Když je vráceno na určitý stav formuláře, naplní ho daty.
	 *
	 * @return self
	 */
	protected function receiveHttpData()
	{
		return RequestButtonHelper::prepareHttpData($this, parent::receiveHttpData());
	}

	/**
	 * Přesměruje zpět na RequestButton a potlačí v uživatelských událostech případný redirect.
	 *
	 * @return self
	 * @throw AbortException
	 */
	public function fireEvents()
	{
		try {
			parent::fireEvents();
		} catch (AbortException $e) {}
		RequestButtonHelper::redirectBack($this);
		if (isset($e)) throw $e;
	}

}
