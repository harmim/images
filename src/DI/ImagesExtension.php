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
		'transform' => Harmim\Images\ImageStorage::RESIZE_FIT,
		'imgTagAttributes' => ['alt', 'height', 'width', 'class', 'hidden', 'id', 'style', 'title', 'data'],
		'types' => [],
		'lazy' => false,
	];


	public function loadConfiguration(): void
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('imageStorage'))
			->setFactory(Harmim\Images\ImageStorage::class)
			->setArguments([$this->getSettings()]);
	}


	public function beforeCompile(): void
	{
		$this->getContainerBuilder()
			->getDefinition('latte.latteFactory')
			->addSetup(Harmim\Images\Template\Macros::class . '::install(?->getCompiler())', ['@self']);
	}


	private function getSettings(): array
	{
		$config = $this->validateConfig(static::DEFAULTS, $this->config);
		$config['wwwDir'] = Nette\DI\Helpers::expand($config['wwwDir'], $this->getContainerBuilder()->parameters);

		return $config;
	}
}
