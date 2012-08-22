<?php

/**
 * Bootstrap soubor.
 *
 * @copyright  Copyright (c) 2010 Milan Pála
 */



// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/Nette/loader.php';

Environment::loadConfig(__DIR__.'/config.ini');

$emailHeaders = array(
	'From' => 'error@%host%',
	'To'   => 'spravce@%host%',
	'Subject' => 'Chyba na serveru %host%',
	'Body' => '%date% - %message%. Pro více informací shlédněte error log.',
);
Debug::enable(Debug::DETECT, NULL, $emailHeaders);
//Environment::setMode(Environment::DEVELOPMENT);
Debug::$strictMode = TRUE;

//RoutingDebugger::enable();
// 2b) load configuration from config.ini file
try
{
	dibi::connect(Environment::getConfig('database'));
}
catch(DibiException $e)
{
	Debug::processException($e, true);
	include '../app/templates/error.phtml';
	exit;
}

// Step 3: Configure application
// 3a) get and setup a front controller
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
//$application->catchExceptions = FALSE;

// Step 4: Setup application router
$router = $application->getRouter();

if( Environment::isProduction() )
{
	$router[] = new Route('/rocniky/', array(
		'presenter' => 'Rocniky',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/zavody/', array(
		'presenter' => 'Zavody',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/fotogalerie/', array(
		'presenter' => 'Fotogalerie',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/statistiky/', array(
		'presenter' => 'Statistiky',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/forum/', array(
		'presenter' => 'Forum',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/clanky/', array(
		'presenter' => 'Clanky',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/sbory/', array(
		'presenter' => 'Sbory',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('/facebook-sdileni/', array(
		'presenter' => 'FacebookSdileni',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new UrlsRouter('', array());

	$router[] = new Route('<presenter>/<action>', array(
		'presenter' => 'Homepage',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new Route('<presenter>/<id>/<action>', array(
		'presenter' => 'Homepage',
		'action' => 'default',
		'id' => NULL,
	));

	$router[] = new SimpleRouter(array(
		'presenter' => 'Homepage',
		'action' => 'default',
		'id' => NULL,
	));
}
else
{
	$router[] = new SimpleRouter(array(
		'presenter' => 'Homepage',
		'action' => 'default',
		'id' => NULL,
	));
}

// budoucí metoda Form::addDatePicker()
function Form_addDatePicker(Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
	return $_this[$name] = new DatePicker($label, $cols, $maxLength);
}
Form::extensionMethod('Form::addDatePicker', 'Form_addDatePicker'); // v PHP 5.2


function Form_addDateTimePicker(Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
	return $_this[$name] = new DateTimePicker($label, $cols, $maxLength);
}
Form::extensionMethod('Form::addDateTimePicker', 'Form_addDateTimePicker'); // v PHP 5.2

function Form_addTexylaTextArea(Form $_this, $name, $label = NULL, $cols = NULL, $rows = NULL)
{
	return $_this[$name] = new TexylaTextArea($label, $cols, $rows);
}
Form::extensionMethod('Form::addTexylaTextArea', 'Form_addTexylaTextArea');

function Form_addAdminTexylaTextArea(Form $_this, $name, $label = NULL, $cols = NULL, $rows = NULL)
{
	return $_this[$name] = new AdminTexylaTextArea($label, $cols, $rows);
}
Form::extensionMethod('Form::addAdminTexylaTextArea', 'Form_addAdminTexylaTextArea');

function Form_addSouradniceInput(Form $_this, $name, $label, $sirkaInput, $delkaInput)
{
	return $_this[$name] = new SouradniceInput($label, $sirkaInput, $delkaInput);
}
Form::extensionMethod('Form::addSouradnice', 'Form_addSouradniceInput');

FormContainer::extensionMethod('FormContainer::addAjaxSelect', array('AjaxSelectBox', 'addAjaxSelect'));

FormContainer::extensionMethod("FormContainer::addJsonDependentSelectBox", "JsonDependentSelectBox::formAddJsonDependentSelectBox");
FormContainer::extensionMethod("FormContainer::addDependentSelectBox", array("DependentSelectBox", "formAddDependentSelectBox"));

// Step 5: Run the application!
if (Environment::getName() !== "console") $application->run();
