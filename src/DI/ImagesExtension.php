<?php

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images\DI;

use Harmim;
use Nette;


class ImagesExtension extends Nette\DI\CompilerExtension
{

	/** @var array */
	private $defaults = [
		'wwwDir' => '%wwwDir%',
		'imagesDir' => 'data/images',
		'origDir' => '_orig',
		'compressionDir' => '_imgs',
		'placeholder' => 'img/noimg.jpg',
		'width' => 1024,
		'height' => 1024,
		'compression' => 85,
		'types' => [],
		'square' => FALSE,
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('storage'))
			->setClass(Harmim\Images\ImageStorage::class)
			->setArguments([$this->getSettings()]);
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('nette.latteFactory')
			->addSetup(Harmim\Images\Template\Macros::class .'::install(?->getCompiler())', ['@self']);
	}


	public function getSettings()
	{
		$config = $this->validateConfig($this->defaults, $this->config);
		$config['wwwDir'] = Nette\DI\Helpers::expand($config['wwwDir'], $this->getContainerBuilder()->parameters);

		return $config;
	}

}
