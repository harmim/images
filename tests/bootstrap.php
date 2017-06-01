<?php

declare(strict_types=1);


require __DIR__ . "/../vendor/autoload.php";


date_default_timezone_set("Europe/Prague");
class_alias("Tester\Assert", "Assert");
Tester\Environment::setup();


define("__TEMP_DIR__",realpath(__DIR__ . "/../temp"));
Tester\Helpers::purge(__TEMP_DIR__);


function run(Tester\TestCase $testCase): void
{
	$testCase->run();
}
