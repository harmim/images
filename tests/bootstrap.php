<?php

declare(strict_types=1);


require __DIR__
	. DIRECTORY_SEPARATOR
	. '..'
	. DIRECTORY_SEPARATOR
	. 'vendor'
	. DIRECTORY_SEPARATOR
	. 'autoload.php';


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
} catch (Throwable) {
	$rndInt = 42;
}
define(
	'TEMP_DIR',
	__DIR__ . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $rndInt,
);
Nette\Utils\FileSystem::createDir(dirname(TEMP_DIR));
Tester\Helpers::purge(TEMP_DIR);

const IMAGES_EXTENSION_CONFIG = [
	'wwwDir' => TEMP_DIR,
	'placeholder' => 'noimg.png',
	'types' => [
		'img-small' => [
			'width' => 1_000,
			'height' => 1_000,
			'class' => 'small-class',
			'title' => 'small-title',
		],
	],
] + Harmim\Images\Config\Config::Defaults;
