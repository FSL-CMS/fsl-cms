<?php

// Uncomment this line if you must temporarily take down your site for maintenance.
// require __DIR__ . '/../app/templates/maintenance.phtml';

// absolute filesystem path to this web root
define('WWW_DIR', __DIR__);

define('LIBS_DIR', __DIR__ . '/../libs/');

// Let bootstrap create Dependency Injection container.
$container = require __DIR__ . '/../app/bootstrap.php';

// Run application.
$container->application->run();
