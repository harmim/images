<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

namespace Harmim\Images\DI;

use Harmim;
use Nette;


class ImagesExtension extends Nette\DI\CompilerExtension
{
	public const DEFAULTS = [
		'wwwDir' => '%wwwDir%',
		'imagesDir' => 'data/images',
		'origDir' => 'orig',
		'compressionDir' => 'imgs',
		'placeholder' => 'img/noimg.jpg',
		'width' => 1024,
		'height' => 1024,
		'compression' => 85,
		'transform' => Harmim\Images\ImageStorage::RESIZE_FIT,
		'imgTagAttributes' => ['alt', 'height', 'width', 'class', 'hidden', 'id', 'style', 'title', 'data'],
		'types' => [],
		'lazy' => false,
	];


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('images'))
			->setFactory(Harmim\Images\ImageStorage::class)
			->setArguments([$this->getSettings()]);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('latte.latteFactory')
			->addSetup(Harmim\Images\Template\Macros::class . '::install(?->getCompiler())', ['@self']);
	}


	private function getSettings(): array
	{
		$config = $this->validateConfig(static::DEFAULTS, $this->config);
		$config['wwwDir'] = Nette\DI\Helpers::expand($config['wwwDir'], $this->getContainerBuilder()->parameters);

		return $config;
	}
}
