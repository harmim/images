<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

namespace Harmim\Images;

use Nette;


/**
 * @property string $src
 * @property int $width
 * @property int $height
 */
class Image
{
	use Nette\SmartObject;


	/**
	 * @var string
	 */
	private $src;

	/**
	 * @var int
	 */
	private $width;

	/**
	 * @var int
	 */
	private $height;


	public function __construct(string $src, int $width, int $height)
	{
		$this->src = $src;
		$this->width = $width;
		$this->height = $height;
	}


	public function getSrc(): string
	{
		return $this->src;
	}


	public function setSrc(string $src): Image
	{
		$this->src = $src;

		return $this;
	}


	public function getWidth(): int
	{
		return $this->width;
	}


	public function setWidth(int $width): Image
	{
		$this->width = $width;

		return $this;
	}


	public function getHeight(): int
	{
		return $this->height;
	}


	public function setHeight(int $height): Image
	{
		$this->height = $height;

		return $this;
	}


	public function __toString()
	{
		return $this->getSrc();
	}
}
