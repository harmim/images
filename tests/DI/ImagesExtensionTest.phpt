<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

/**
 * TEST: DI ImagesExtension.
 *
 * @author Dominik Harmim <harmim6@gmail.com>
 */


require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';


test('DI ImagesExtension - valid configuration.', static function (): void {
	$compiler = (new Nette\DI\Compiler())
		->addExtension('images', new Harmim\Images\DI\ImagesExtension())
		->loadConfig(Tester\FileMock::create('
			parameters:
				wwwDir: foo

			images:
				origDir: _orig
				compressionDir: _imgs
				types:
					img-small:
						width: 50
						height: 50
						transform: ::constant(Harmim\Images\Resize::COVER)
					foo:
						transform:
							- ::constant(Harmim\Images\Resize::COVER)
							- ::constant(Harmim\Images\Resize::STRETCH)
		', 'neon'));
	$compiler->getContainerBuilder()->addFactoryDefinition('latte.latteFactory')
		->setImplement(Nette\Bridges\ApplicationLatte\LatteFactory::class)
		->getResultDefinition()
			->setFactory(Latte\Engine::class);
	eval($compiler->compile());

	/** @var Nette\DI\Container $container */
	/** @noinspection PhpUndefinedClassInspection */
	$container = new Container;
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

	Tester\Assert::same('foo', $imageStorage->getConfig()['wwwDir']);
	Tester\Assert::same('_orig', $imageStorage->getConfig()['origDir']);
	Tester\Assert::same('_imgs', $imageStorage->getConfig()['compressionDir']);
	Tester\Assert::same(Harmim\Images\DI\ImagesExtension::DEFAULTS['width'], $imageStorage->getConfig()['width']);
	Tester\Assert::same(50, $imageStorage->getConfig()['types']['img-small']['width']);

	Tester\Assert::same(100, $imageStorage->getConfig(['width' => 100])['width']);
	Tester\Assert::same(
		Harmim\Images\DI\ImagesExtension::DEFAULTS['height'],
		$imageStorage->getConfig(['width' => 100])['height'],
	);

	Tester\Assert::same(50, $imageStorage->getConfig(['type' => 'img-small'])['width']);
	Tester\Assert::same(100, $imageStorage->getConfig(['width' => 100, 'type' => 'img-small'])['width']);

	Tester\Assert::same(Harmim\Images\Resize::OR_SMALLER, $imageStorage->getConfig()['transform']);
	Tester\Assert::same(Harmim\Images\Resize::COVER, $imageStorage->getConfig(['type' => 'img-small'])['transform']);
	Tester\Assert::notSame(
		[Harmim\Images\Resize::COVER->value, Harmim\Images\Resize::STRETCH->value],
		$imageStorage->getConfig(['type' => 'foo'])['transform'],
	);
	Tester\Assert::same(
		[Harmim\Images\Resize::COVER, Harmim\Images\Resize::STRETCH],
		$imageStorage->getConfig(['type' => 'foo'])['transform'],
	);
});


test('DI ImagesExtension - invalid configuration.', static function (): void {
	$compile = static fn(string $config): string =>
		(new Nette\DI\Compiler())
			->addExtension('images', new Harmim\Images\DI\ImagesExtension())
			->loadConfig(Tester\FileMock::create($config, 'neon'))
			->compile();

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			foo: bar
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			compression: 142
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			width: 0
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			height: -42
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			imgTagAttributes: alt
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			types:
				foo:
					bar: baz
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			transform: foo
	'), Nette\DI\InvalidConfigurationException::class);

	Tester\Assert::exception(static fn(): string => $compile('
		images:
			types:
				foo:
					transform: [::constant(Harmim\Images\Resize::COVER), 42]
	'), Nette\DI\InvalidConfigurationException::class);
});
