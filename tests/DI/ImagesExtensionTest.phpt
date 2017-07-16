<?php

declare(strict_types=1);

/**
 * Test: ImagesExtension
 *
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */


use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$compiler = new Nette\DI\Compiler();
	$compiler->addExtension('images', new Harmim\Images\DI\ImagesExtension());
	$compiler->loadConfig(Tester\FileMock::create('
		parameters:
			wwwDir: foo
		', 'neon'));
	$builder = $compiler->getContainerBuilder();
	$builder->addDefinition('latte.latteFactory')->setClass(Latte\Engine::class);

	$code = $compiler->setClassName('Container1')->compile();
	eval($code);

	$container = new Container1;
	Assert::type(Harmim\Images\ImageStorage::class, $container->getService('images.images'));
});
