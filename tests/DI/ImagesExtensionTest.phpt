<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

/**
 * Test: ImagesExtension
 *
 * @author Dominik Harmim <harmim6@gmail.com>
 */


require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';


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
$builder->addDefinition('latte.latteFactory')->setFactory(Latte\Engine::class);

$code = $compiler->setClassName('Container1')->compile();
eval($code);

/** @var Nette\DI\Container $container */
/** @noinspection PhpUndefinedClassInspection */
$container = new Container1;
Tester\Assert::true($container->hasService('images.imageStorage'));
Tester\Assert::type(Harmim\Images\ImageStorage::class, $container->getService('images.imageStorage'));
Tester\Assert::same(Harmim\Images\ImageStorage::class, $container->getServiceType('images.imageStorage'));
Tester\Assert::type(
	Harmim\Images\ImageStorage::class,
	$container->getByType(Harmim\Images\ImageStorage::class),
);

/** @var Harmim\Images\ImageStorage $imageStorage */
$imageStorage = $container->getService('images.imageStorage');
Tester\Assert::true($container->isCreated('images.imageStorage'));

Tester\Assert::same('foo', $imageStorage->getOptions()['wwwDir']);
Tester\Assert::same('_orig', $imageStorage->getOptions()['origDir']);
Tester\Assert::same('_imgs', $imageStorage->getOptions()['compressionDir']);
Tester\Assert::same(Harmim\Images\DI\ImagesExtension::DEFAULTS['width'], $imageStorage->getOptions()['width']);
Tester\Assert::same(50, $imageStorage->getOptions()['types']['img-small']['width']);

Tester\Assert::same(100, $imageStorage->getOptions(['width' => 100])['width']);
Tester\Assert::same(
	Harmim\Images\DI\ImagesExtension::DEFAULTS['height'],
	$imageStorage->getOptions(['width' => 100])['height'],
);

Tester\Assert::same(50, $imageStorage->getOptions(['type' => 'img-small'])['width']);
Tester\Assert::same(100, $imageStorage->getOptions(['width' => 100, 'type' => 'img-small'])['width']);
