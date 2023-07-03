<?php

declare(strict_types=1);

namespace Harmim\Images\DI;

use Harmim;
use Nette;


class ImagesExtension extends Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		$configItems = [
			'wwwDir' => Nette\Schema\Expect::string(
				Harmim\Images\Config\Config::Defaults['wwwDir'],
			),
			'imagesDir' => Nette\Schema\Expect::string(
				Harmim\Images\Config\Config::Defaults['imagesDir'],
			),
			'origDir' => Nette\Schema\Expect::string(
				Harmim\Images\Config\Config::Defaults['origDir'],
			),
			'compressionDir' => Nette\Schema\Expect::string(
				Harmim\Images\Config\Config::Defaults['compressionDir'],
			),
			'placeholder' => Nette\Schema\Expect::string(
				Harmim\Images\Config\Config::Defaults['placeholder'],
			),
			'width' => Nette\Schema\Expect::int(
				Harmim\Images\Config\Config::Defaults['width'],
			)->min(1),
			'height' => Nette\Schema\Expect::int(
				Harmim\Images\Config\Config::Defaults['height'],
			)->min(1),
			'compression' => Nette\Schema\Expect::int(
				Harmim\Images\Config\Config::Defaults['compression'],
			)->min(0)->max(100),
			'transform' => Nette\Schema\Expect::anyOf(
				Nette\Schema\Expect::type(Harmim\Images\Resize::class)
					->dynamic(),
				Nette\Schema\Expect::listOf(
					Nette\Schema\Expect::type(Harmim\Images\Resize::class)
						->dynamic(),
				),
			)->default(Harmim\Images\Config\Config::Defaults['transform']),
			'allowedImgTagAttrs' => Nette\Schema\Expect::listOf(
				Nette\Schema\Expect::string(),
			)->default(
				Harmim\Images\Config\Config::Defaults['allowedImgTagAttrs'],
			),
			'lazy' => Nette\Schema\Expect::bool(
				Harmim\Images\Config\Config::Defaults['lazy'],
			),
		];

		return Nette\Schema\Expect::structure([
			'types' => Nette\Schema\Expect::arrayOf(
				Nette\Schema\Expect::structure($configItems)
					->skipDefaults()
					->castTo('array'),
				Nette\Schema\Expect::string(),
			)->default(Harmim\Images\Config\Config::Defaults['types']),
		] + $configItems);
	}


	public function loadConfiguration(): void
	{
		assert($this->config instanceof \stdClass);

		if (isset($this->config->wwwDir) && is_string($this->config->wwwDir)) {
			/** @noinspection PhpInternalEntityUsedInspection */
			$this->config->wwwDir = Nette\DI\Helpers::expand(
				$this->config->wwwDir,
				$this->getContainerBuilder()->parameters,
			);
		}

		$this->getContainerBuilder()
			->addDefinition($this->prefix('imageStorage'))
			->setFactory(Harmim\Images\ImageStorage::class)
			->setArguments([(array) $this->config]);
	}


	public function beforeCompile(): void
	{
		$latteFactory = $this->getContainerBuilder()->getDefinitionByType(
			Nette\Bridges\ApplicationLatte\LatteFactory::class,
		);
		assert($latteFactory instanceof Nette\DI\Definitions\FactoryDefinition);
		$latteFactory->getResultDefinition()->addSetup(
			'addExtension',
			[new Harmim\Images\Latte\ImagesExtension()],
		);
	}
}
