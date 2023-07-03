<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * TEST: DI ImagesExtension.
 */

declare(strict_types=1);


require __DIR__
	. DIRECTORY_SEPARATOR
	. '..'
	. DIRECTORY_SEPARATOR
	. 'bootstrap.php';


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
						transform: ::constant(Harmim\Images\Resize::Cover)
					foo:
						transform:
							- ::constant(Harmim\Images\Resize::Cover)
							- ::constant(Harmim\Images\Resize::Stretch)
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
	Tester\Assert::type(
		Harmim\Images\ImageStorage::class,
		$container->getService('images.imageStorage'),
	);
	Tester\Assert::same(
		Harmim\Images\ImageStorage::class,
		$container->getServiceType('images.imageStorage'),
	);
	Tester\Assert::type(
		Harmim\Images\ImageStorage::class,
		$container->getByType(Harmim\Images\ImageStorage::class),
	);

	$imageStorage = $container->getService('images.imageStorage');
	assert($imageStorage instanceof Harmim\Images\ImageStorage);
	Tester\Assert::true($container->isCreated('images.imageStorage'));

	Tester\Assert::same('foo', $imageStorage->getConfig()->wwwDir);
	Tester\Assert::same('_orig', $imageStorage->getConfig()->origDir);
	Tester\Assert::same('_imgs', $imageStorage->getConfig()->compressionDir);
	Tester\Assert::same(
		Harmim\Images\Config\Config::Defaults['width'],
		$imageStorage->getConfig()->width,
	);
	Tester\Assert::same(
		50,
		$imageStorage->getConfig()->types['img-small']->width,
	);

	Tester\Assert::same(100, $imageStorage->getConfig(['width' => 100])->width);
	Tester\Assert::same(
		Harmim\Images\Config\Config::Defaults['height'],
		$imageStorage->getConfig(['width' => 100])->height,
	);

	Tester\Assert::same(
		50,
		$imageStorage->getConfig(['type' => 'img-small'])->width,
	);
	Tester\Assert::same(
		100,
		$imageStorage->getConfig([
			'width' => 100,
			'type' => 'img-small',
		])->width,
	);

	Tester\Assert::same(
		Harmim\Images\Resize::OrSmaller,
		$imageStorage->getConfig()->transform,
	);
	Tester\Assert::same(
		Harmim\Images\Resize::Cover,
		$imageStorage->getConfig(['type' => 'img-small'])->transform,
	);
	Tester\Assert::same(
		[Harmim\Images\Resize::Cover, Harmim\Images\Resize::Stretch],
		$imageStorage->getConfig(['type' => 'foo'])->transform,
	);
});


test('DI ImagesExtension - invalid configuration.', static function (): void {
	$compile = static fn(string $config): string => (new Nette\DI\Compiler())
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
			allowedImgTagAttrs: alt
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
					transform: [::constant(Harmim\Images\Resize::Cover), 42]
	'), Nette\DI\InvalidConfigurationException::class);
});
