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
 * Pomocná trída.
 * Pro použítí vlastností RequestButtonu bez RequestButtonReceiveru.
 */
final class RequestButtonHelper extends Nette\Object
{

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}

	/**
	 * Přidá do action backlink, aby formulář i po odeslání věděl kam se má vrátit.
	 *
	 * @see RequestButtonReceiver::setAction()
	 * @param Nette\Application\UI\Form
	 * @param Link|string
	 * @return Link|string
	 */
	static public function prepareAction(Nette\Application\UI\Form $form, $url)
	{
		if ($url instanceof Link)
		{
			$url->setParam(RequestButton::BACKLINK_KEY, $form->presenter->getParam(RequestButton::BACKLINK_KEY));
		}
		else
		{
    	$url .= (strpos($url,'?') === false?'?':'&') . http_build_query(array(RequestButton::BACKLINK_KEY => $form->presenter->getParam(RequestButton::BACKLINK_KEY)));
		}
		return $url;
	}

	/**
	 * Když je vráceno na určitý stav formuláře, vrátí jeho data.
	 *
	 * @param Nette\Application\UI\Form
	 * @param array Data odeslaná např POSTem. Má přednost před stavem formuláře.
	 * @return array
	 */
	static public function prepareHttpData(Nette\Application\UI\Form $form, $data)
	{
	  if (!$data)
	  {
			$receivedId = $form->presenter->getParam(RequestButton::RECEIVED_KEY);
			if ($receivedId AND $form->lookupPath('Nette\Application\UI\Presenter', TRUE) === RequestButtonStorage::getFormName($receivedId) AND RequestButtonStorage::is($receivedId))
			{
				$data = RequestButtonStorage::getData($receivedId);
			}
		}
		return $data;
	}

	/**
	 * Přesměruje zpět na RequestButton, když je z něj požadavek.
	 * Používá ho RequestButtonReceiver a tuto funkcy lze zavolat i třeba v signálu.
	 *
	 * @param Nette\Application\UI\Form|PresenterComponent|NULL Null znamená vzít presenter z prostředí, u Nette\Application\UI\Form se kontroluje jestli nebyl stisknut další RequestButton.
	 * @throw AbortException
	 */
	static public function redirectBack($form = NULL)
	{
		if ($form === NULL) $presenter = Nette\Environment::getApplication()->getPresenter();
		else if ($form instanceof PresenterComponent OR $form instanceof Nette\Application\UI\Form) $presenter = $form->getPresenter();
    $backlinkId = $presenter->getParam(RequestButton::BACKLINK_KEY);
    if ($backlinkId AND ($form === NULL OR !($form->isSubmitted() instanceof RequestButton)) AND RequestButtonStorage::is($backlinkId))
		{
			$presenter->redirect(RequestButtonStorage::getDestination($backlinkId), array(RequestButton::RECEIVED_KEY => $backlinkId, 'do' => NULL) + RequestButtonStorage::getDestinationArgs($backlinkId));
		}
	}

	/**
	 * Upraví formulář pro potřeby RequestButtonu.
	 * 	Přidá do action backlink, aby formulář i po odeslání věděl kam se má vrátit.
	 * 	Když je vráceno na určitý stav formuláře, nastaví mu data.
	 * MUSÍ SE VOLAT PO přidání všech FormControlů do Formu a po připojení na Presenter.
	 * MÍSTO VOLÁNÍ TÉTO FUNCKE SE MÚŽE POUŽÍT JAKO FORM RequestButtonReceiver
	 *
	 * @param Nette\Application\UI\Form
	 * @see RequestButtonReceiver
	 *
	 */
	static public function prepareForm(Nette\Application\UI\Form $form)
	{
		if (!($form instanceof RequestButtonReceiver))
		{
			if ($form->getPresenter(false))
			{
        $form->setAction(RequestButtonHelper::prepareAction($form, $form->getAction()));
        $form->setValues(RequestButtonHelper::prepareHttpData($form, $form->getHttpData()));

				foreach ($form->getComponents(TRUE, 'RequestButton') as $rb)
				{
          $rb->formIsPrepared = true;
				}
			}
			else throw new InvalidStateException("Form is not attached to Presenter.");
		}
	}

	/**
	 * Přidá RequestButton do Formu.
	 * Pro object extension:
	 * <code>
	 * 	FormContainer::extensionMethod('FormContainer::addRequestButton', array('RequestButtonHelper','addRequestButton'));
	 * </code>
	 *
	 * @see RequestButton
	 * @param FormContainer
	 * @param string
	 * @param string
	 * @param string Kam se RequestButtonem dostanu (presenter a action).
	 * @param array Kam se RequestButtonem dostanu (parametry).
	 * @return RequestButton
	 */
	static public function addRequestButton(Nette\Forms\Container $form, $name, $caption = NULL, $destination = NULL, $destinationArgs = array())
	{
    return $form[$name] = new RequestButton($caption, $destination, $destinationArgs);
	}

	/**
	 * Přidá RequestButtonBack do Formu.
	 * Pro object extension:
	 * <code>
	 * 	FormContainer::extensionMethod('FormContainer::addRequestButtonBack', array('RequestButtonHelper','addRequestButtonBack'));
	 * </code>
	 *
	 * @see RequestButtonBack
	 * @param FormContainer
	 * @param string
	 * @param string
	 * @return RequestButtonBack
	 */
	static public function addRequestButtonBack(Nette\Forms\Container $form, $name, $caption = NULL)
	{
	  return $form[$name] = new RequestButtonBack($caption);
	}
}
