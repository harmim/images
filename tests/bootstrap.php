<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */


require __DIR__ . '/../vendor/autoload.php';


date_default_timezone_set('Europe/Prague');
Tester\Environment::setup();


define('__TEMP_DIR__', __DIR__ . '/temp/' . random_int(0, PHP_INT_MAX));
Nette\Utils\FileSystem::createDir(dirname(__TEMP_DIR__));
Tester\Helpers::purge(__TEMP_DIR__);


function run(Tester\TestCase $testCase): void
{
	$testCase->run();
}


function test(\Closure $function): void
{
	$function();
}


define('IMAGES_EXTENSION_CONFIG', [
	'wwwDir' => __TEMP_DIR__,
	'placeholder' => 'noimg.png',
	'types' => [
		'img-small' => [
			'width' => 1000,
			'height' => 1000,
			'class' => 'small-class',
			'title' => 'small-title',
		],
	],
] + Harmim\Images\DI\ImagesExtension::DEFAULTS);
