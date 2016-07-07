<?php

include __DIR__ . '/../../vendor/autoload.php';

date_default_timezone_set('Europe/Prague');
class_alias('Tester\Assert', 'Assert');
Tester\Environment::setup();

define('TEMP_DIR', __DIR__ . '/../temp');
Tester\Helpers::purge(TEMP_DIR);

function run(Tester\TestCase $testCase)
{
	$testCase->run();
}
