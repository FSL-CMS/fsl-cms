<?php

/**
 * Bootstrap soubor.
 *
 * @copyright  Copyright (c) 2010 Milan Pála, fslcms.milanpala.cz
 */

use Nette\Application\Routers\Route;

// Load Nette Framework or autoloader generated by Composer
require __DIR__ . '/../libs/autoload.php';

$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
//$configurator->setDebugMode($configurator::NONE);
$configurator->enableDebugger(__DIR__ . '/../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../app')
	->addDirectory(__DIR__ . '/../app_custom')
	->addDirectory(__DIR__ . '/../libs')
	->register();

define('DATA_DIR', __DIR__.'/../data');


// Přidání podpory pro dibi
$configurator->onCompile[] = function ($configurator, $compiler) {
    $compiler->addExtension('dibi', new DibiNetteExtension);
};

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app_custom/config/config.neon');
$container = $configurator->createContainer();

// Nastavení routování
$container->router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
$container->router[] = new UrlsRouter('<url>', array());
$container->router[] = new Route('<presenter>/<id [0-9]+>/<action>', 'Homepage:default');
$container->router[] = new Route('<presenter>/<action>', 'Homepage:default');


// Rozšíření formulářů
Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addTexylaTextArea', function(Nette\Application\UI\Form $_this, $name, $label = NULL, $cols = NULL, $rows = NULL)
{
	return $_this[$name] = new TexylaTextArea($label, $cols, $rows);
});

Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addAdminTexylaTextArea', function(Nette\Application\UI\Form $_this, $name, $label = NULL, $cols = NULL, $rows = NULL, $souvisejici, $id_souvisejiciho)
{
	return $_this[$name] = new AdminTexylaTextArea($label, $cols, $rows, $souvisejici, $id_souvisejiciho);
});

Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addDatePicker', function(Nette\Application\UI\Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
	return $_this[$name] = new DatePicker($label, $cols, $maxLength);
}); // v PHP 5.2


Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addDateTimePicker', function(Nette\Application\UI\Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
	return $_this[$name] = new Nette\Extras\DateTimePicker($label, $cols, $maxLength);
});

Nette\Forms\Container::extensionMethod('Nette\Forms\Container::addSouradnice', function(Nette\Application\UI\Form $_this, $name, $label, $sirkaInput, $delkaInput)
{
	return $_this[$name] = new SouradniceInput($label, $sirkaInput, $delkaInput);
});

return $container;
