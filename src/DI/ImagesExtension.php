<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
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
		'transform' => 'fit',
		'imgTagAttributes' => ['alt', 'height', 'width', 'class', 'hidden', 'id', 'style', 'title'],
		'types' => [],
		'square' => false,
	];


	/**
	 * @inheritdoc
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('images'))
			->setClass(Harmim\Images\ImageStorage::class)
			->setArguments([$this->getSettings()]);
	}


	/**
	 * @inheritdoc
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('latte.latteFactory')
			->addSetup(Harmim\Images\Template\Macros::class . '::install(?->getCompiler())', ['@self']);
	}


	/**
	 * @return array
	 */
	public function getSettings(): array
	{
		$config = $this->validateConfig(self::DEFAULTS, $this->config);
		$config['wwwDir'] = Nette\DI\Helpers::expand($config['wwwDir'], $this->getContainerBuilder()->parameters);

		return $config;
	}
}
