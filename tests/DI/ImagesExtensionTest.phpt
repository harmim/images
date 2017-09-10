<?php

declare(strict_types=1);

/**
 * Test: ImagesExtension
 *
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$compiler = new Nette\DI\Compiler();
	$compiler->addExtension('images', new Harmim\Images\DI\ImagesExtension());
	$compiler->loadConfig(Tester\FileMock::create('
		parameters:
			wwwDir: foo

		images:
			origDir: _orig
			compressionDir: _imgs
			types:
				img-small:
					width: 50
					height: 50
		', 'neon'));
	$builder = $compiler->getContainerBuilder();
	$builder->addDefinition('latte.latteFactory')
		->setFactory(Latte\Engine::class);

	$code = $compiler->setClassName('Container1')->compile();
	eval($code);

	/** @var Nette\DI\Container $container */
	$container = new Container1;
	Assert::true($container->hasService('images.images'));
	Assert::type(Harmim\Images\ImageStorage::class, $container->getService('images.images'));
	Assert::same(Harmim\Images\ImageStorage::class, $container->getServiceType('images.images'));
	Assert::type(
		Harmim\Images\ImageStorage::class,
		$container->getByType(Harmim\Images\ImageStorage::class)
	);

	/** @var Harmim\Images\ImageStorage $imageStorage */
	$imageStorage = $container->getService('images.images');
	Assert::true($container->isCreated('images.images'));

	Assert::same('foo', $imageStorage->getOptions()['wwwDir']);
	Assert::same('_orig', $imageStorage->getOptions()['origDir']);
	Assert::same('_imgs', $imageStorage->getOptions()['compressionDir']);
	Assert::same(Harmim\Images\DI\ImagesExtension::DEFAULTS['width'], $imageStorage->getOptions()['width']);
	Assert::same(50, $imageStorage->getOptions()['types']['img-small']['width']);

	Assert::same(100, $imageStorage->getOptions(['width' => 100])['width']);
	Assert::same(
		Harmim\Images\DI\ImagesExtension::DEFAULTS['height'],
		$imageStorage->getOptions(['width' => 100])['height']
	);

	Assert::same(50, $imageStorage->getOptions(['type' => 'img-small'])['width']);
	Assert::same(100, $imageStorage->getOptions(['width' => 100, 'type' => 'img-small'])['width']);
});
