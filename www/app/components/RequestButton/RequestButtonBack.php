<?php
/**
 * RequestButton
 * Umožnuje přesměrovávat se mezi formuláři a neztratit neuložený obsah.
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.1
 */
 
require_once dirname(__FILE__) . '/RequestButton.php';
require_once dirname(__FILE__) . '/RequestButtonStorage.php';
require_once dirname(__FILE__) . '/RequestButtonHelper.php';

/**
 * Neuloží formulář a vrátí se zpět na RequestButton.
 * Když není RequestButton požadavek, tak se tento button nezobrazuje.
 */
class RequestButtonBack extends SubmitButton
{

	/**
	 * @param string Text v buttonu.
	 */
	public function __construct($caption = NULL)
	{
		parent::__construct($caption);
		parent::setValidationScope(false);
		$this->monitor('Nette\Application\Presenter');
	}

	/**
	 * Když není RequestButton požadavek, tak se tento button nezobrazuje.
	 *
	 * @param IPresenter
	 */
	protected function attached($parent)
	{
		if ($parent instanceof IPresenter)
		{
	    $backlinkId = $this->form->presenter->getParam(RequestButton::BACKLINK_KEY);
	    if (!$backlinkId OR !RequestButtonStorage::is($backlinkId))
			{
			  $this->setDisabled(true);
			  $this->getParent()->removeComponent($this);
			}
		}
		parent::attached($parent);
	}

	/**
	 * Po kliknutí redirect na RequestButton.
	 */
	public function click()
	{
		RequestButtonHelper::redirectBack($this->form);
	}
}
