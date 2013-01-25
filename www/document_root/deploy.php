<?php

header("Content-Type: text/html; charset=windows-1250");

if(!isset($_GET['token']) || $_GET['token'] != '21f6hg5f')
{
	header('HTTP/1.1 404 Not Found');
	exit;
}

if(isset($_GET['before']))
{
	rename('index.php', 'index.php.production');
	rename('index.php.maintenance', 'index.php');

	echo "\nProvoz webu byl uspesne preveden do rezimu udrzby\n";
}
elseif(isset($_GET['after']))
{
	rename('index.php', 'index.php.maintenance');
	rename('index.php.production', 'index.php');

	echo "\nProvoz webu byl uspesne preveden do produkcniho rezimu\n";
}