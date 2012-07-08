<?php
/**
 * RequestButton
 * Umožnuje přesměrovávat se mezi formuláři a neztratit neuložený obsah.
 *
 * @author Petr Procházka http://petrp.cz petr@petrp.cz
 * @copyright 2009 Petr Procházka
 * @version 0.1
 */
 
/*
TODO
kdyz je RequestButton prvni tak ho enterem odeslu (viz listopadova PS)
kontrola jestli je uz presenter nebo form
RequestButtonBack nebo RequestBackButton
*/

require_once dirname(__FILE__) . '/RequestButtonReceiver.php';
require_once dirname(__FILE__) . '/RequestButtonBack.php';

require_once dirname(__FILE__) . '/RequestButtonStorage.php';
require_once dirname(__FILE__) . '/RequestButtonHelper.php';

/**
 * Uloží stav formuláře a přesměruje se jinam,
 * kde je možné vrátit se zpět na formulár.
 */
class RequestButton extends SubmitButton
{

	/** @var string Klíč do url, kterí určije na který RequestButton se mám vrátit. */
	const BACKLINK_KEY = '__rbb';
	
	/** @var string Klíč do url, kterí určije jaký stav formuláře mám obnovit. */
	const RECEIVED_KEY = '__rbr';

	/** @var string Kam se RequestButtonem dostanu (presenter a action). */
	private $destination;
	
	/** @var string Kam se RequestButtonem dostanu (parametry). */
	private $destinationArgs = array();

	/**
	 * Kontrola jestli je form upraven pro potřeby RequestButtonu.
	 * Tedy že je použit RequestButtonReceiver, nebo zavolán RequestButtonHelper::prepareForm()
	 * @var bool
	 * @see RequestButtonReceiver
	 * @see RequestButtonHelper::prepareForm()
	 */
	public $formIsPrepared = false;

	/**
	 * @param string
	 * @param string Kam se RequestButtonem dostanu (presenter a action).
	 * @param array Kam se RequestButtonem dostanu (parametry).
	 */
	public function __construct($caption = NULL, $destination = NULL, $destinationArgs = array())
	{
		parent::__construct($caption);
		parent::setValidationScope(false);
		$this->onClick = array(
			array($this, 'saveRequstAndRedirect'),
		);
		$this->monitor('Nette\Forms\Form');
		$this->monitor('Nette\Application\Presenter');
		$this->setDestination($destination, $destinationArgs);
	}

	/**
	 * @param IPresenter|RequestButtonReceiver
	 */
	protected function attached($parent)
	{
		if ($parent instanceof IPresenter)
		{
			$receivedId = $this->form->presenter->getParam(RequestButton::RECEIVED_KEY);
			if ($receivedId AND !($this->lookup('Nette\Forms\Form') instanceof RequestButtonReceiver) AND $this->lookupPath('Nette\Application\Presenter', TRUE) === RequestButtonStorage::getName($receivedId) AND RequestButtonStorage::is($receivedId))
			{
				$this->form->setValues(RequestButtonStorage::getData($receivedId));// todo tohle asi neni potreba, protoze to uz obstarave RequestButtonReceiver nebo RequestButtonHelper::prepareForm()
			}
		}
		else if ($parent instanceof RequestButtonReceiver)
		{
			$this->formIsPrepared = true;
		}
		parent::attached($parent);
	}
	
	/**
	 * @param AppForm
	 */
	protected function detached($parent)
	{
		if ($parent instanceof AppForm)
		{
			$this->formIsPrepared = false;
		}
		parent::detached($parent);
	}

	/**
	 * Kontroluje jestli je formulář upraven pro potřeby RequestButtonu.
	 * Tedy že je použit RequestButtonReceiver, nebo zavolán RequestButtonHelper::prepareForm()
	 *
	 * @see RequestButtonReceiver
	 * @see RequestButtonHelper::prepareForm()
	 * @param string
	 * @return Html
	 */
	public function getControl($caption = NULL)
	{
	  if (!$this->formIsPrepared)
	  {
			throw new InvalidStateException('Use RequestButtonReceiver instead AppForm , or call method `RequestButtonHelper::prepareForm($form);` after added all FormControls.');
		}
		return parent::getControl();
	}

	/**
	 * Uloží stav formuláře a přesměruje.
	 *
	 * @throw AbortException
	 */
	public function saveRequstAndRedirect()
	{
	  $key = RequestButtonStorage::save(
			$this->form->presenter->backlink(),
			$this->form->presenter->getParam(),
			$this->lookupPath('Nette\Application\Presenter', TRUE),
			$this->form->lookupPath('Nette\Application\Presenter', TRUE),
			$this->form->getValues()
		);
		$this->form->presenter->redirect($this->getDestination(), array(RequestButton::BACKLINK_KEY => $key) + $this->getDestinationArgs());
	}

	/**
	 * Kam se RequestButtonem dostanu (presenter a action)
	 *
	 * @return string
	 */
	public function getDestination()
	{
		if (!$this->destination) throw new InvalidStateException('Destination is required.');
		return $this->destination;
	}

	/**
	 * Kam se RequestButtonem dostanu (parametry)
	 *
	 * @return array
	 */
	public function getDestinationArgs()
	{
		return $this->destinationArgs;
	}
	
	/**
	 * Kam se RequestButtonem dostanu.
	 *
	 * @param string presenter a action
	 * @param array parametry
	 * @return self
	 */
	public function setDestination($destination, $args = array())
	{
    $this->destination = $destination;
    if (!is_array($args))
    {
      $args = func_get_args();
      array_shift($args);
		}
    $this->destinationArgs = $args;
	}
}
