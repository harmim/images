<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
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


	public function __construct($src, $width, $height)
	{
		$this->src = $src;
		$this->width = $width;
		$this->height = $height;
	}


	/**
	 * @return string
	 */
	public function getSrc(): string
	{
		return $this->src;
	}


	/**
	 * @param string $src
	 */
	public function setSrc(string $src)
	{
		$this->src = $src;
	}


	/**
	 * @return int
	 */
	public function getWidth(): int
	{
		return $this->width;
	}


	/**
	 * @param int $width
	 */
	public function setWidth(int $width)
	{
		$this->width = $width;
	}


	/**
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->height;
	}


	/**
	 * @param int $height
	 */
	public function setHeight(int $height)
	{
		$this->height = $height;
	}


	public function __toString()
	{
		return $this->getSrc();
	}
}
