<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images\DI;

use Harmim;
use Nette;


class ImagesExtension extends Nette\DI\CompilerExtension
{
	public const DEFAULTS = [
		'wwwDir' => '%wwwDir%',
		'imagesDir' => 'data' . DIRECTORY_SEPARATOR . 'images',
		'origDir' => 'orig',
		'compressionDir' => 'imgs',
		'placeholder' => 'img' . DIRECTORY_SEPARATOR . 'noimg.jpg',
		'width' => 1_024,
		'height' => 1_024,
		'compression' => 85,
		'transform' => Harmim\Images\Resize::OR_SMALLER,
		'imgTagAttributes' => ['alt', 'height', 'width', 'class', 'hidden', 'id', 'style', 'title', 'data'],
		'types' => [],
		'lazy' => false,
	];


	public function getConfigSchema(): Nette\Schema\Schema
	{
		$configItems = [
			'wwwDir' => Nette\Schema\Expect::string(static::DEFAULTS['wwwDir']),
			'imagesDir' => Nette\Schema\Expect::string(static::DEFAULTS['imagesDir']),
			'origDir' => Nette\Schema\Expect::string(static::DEFAULTS['origDir']),
			'compressionDir' => Nette\Schema\Expect::string(static::DEFAULTS['compressionDir']),
			'placeholder' => Nette\Schema\Expect::string(static::DEFAULTS['placeholder']),
			'width' => Nette\Schema\Expect::int(static::DEFAULTS['width'])->min(1),
			'height' => Nette\Schema\Expect::int(static::DEFAULTS['height'])->min(1),
			'compression' => Nette\Schema\Expect::int(static::DEFAULTS['compression'])->min(0)->max(100),
			'transform' => Nette\Schema\Expect::anyOf(
				Nette\Schema\Expect::type(Harmim\Images\Resize::class)->dynamic(),
				Nette\Schema\Expect::listOf(Nette\Schema\Expect::type(Harmim\Images\Resize::class)->dynamic()),
			)->default(static::DEFAULTS['transform']),
			'imgTagAttributes' => Nette\Schema\Expect::listOf(Nette\Schema\Expect::string())->default(
				static::DEFAULTS['imgTagAttributes'],
			),
			'lazy' => Nette\Schema\Expect::bool(static::DEFAULTS['lazy']),
		];

		return Nette\Schema\Expect::structure($configItems + [
			'types' => Nette\Schema\Expect::arrayOf(
				Nette\Schema\Expect::structure($configItems)->skipDefaults()->castTo('array'),
				Nette\Schema\Expect::string(),
			)->default(static::DEFAULTS['types']),
		]);
	}


	public function loadConfiguration(): void
	{
		/** @noinspection PhpInternalEntityUsedInspection */
		$this->config->wwwDir = Nette\DI\Helpers::expand(
			$this->config->wwwDir, $this->getContainerBuilder()->parameters,
		);

		$this->getContainerBuilder()
			->addDefinition($this->prefix('imageStorage'))
			->setFactory(Harmim\Images\ImageStorage::class)
			->setArguments([(array) $this->config]);
	}


	public function beforeCompile(): void
	{
		/** @var Nette\DI\Definitions\FactoryDefinition $latteFactory */
		$latteFactory = $this->getContainerBuilder()
			->getDefinitionByType(Nette\Bridges\ApplicationLatte\LatteFactory::class);
		$latteFactory->getResultDefinition()->addSetup('addExtension', [new Harmim\Images\Latte\ImagesExtension()]);
	}
}
