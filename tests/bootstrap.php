<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */


require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';


function run(Tester\TestCase $testCase): void
{
	/** @noinspection PhpUnhandledExceptionInspection */
	$testCase->run();
}


date_default_timezone_set('Europe/Prague');
Tester\Environment::setup();
Tester\Environment::setupFunctions();

try {
	$rndInt = random_int(0, PHP_INT_MAX);
} catch (\Throwable) {
	$rndInt = 42;
}
define('__TEMP_DIR__', __DIR__ . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $rndInt);
Nette\Utils\FileSystem::createDir(dirname(__TEMP_DIR__));
Tester\Helpers::purge(__TEMP_DIR__);

const IMAGES_EXTENSION_CONFIG = [
	'wwwDir' => __TEMP_DIR__,
	'placeholder' => 'noimg.png',
	'types' => [
		'img-small' => [
			'width' => 1_000,
			'height' => 1_000,
			'class' => 'small-class',
			'title' => 'small-title',
		],
	],
] + Harmim\Images\DI\ImagesExtension::DEFAULTS;
