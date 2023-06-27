<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
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


	private string $src;

	private int $width;

	private int $height;


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


	public function setSrc(string $src): self
	{
		$this->src = $src;

		return $this;
	}


	public function getWidth(): int
	{
		return $this->width;
	}


	public function setWidth(int $width): self
	{
		$this->width = $width;

		return $this;
	}


	public function getHeight(): int
	{
		return $this->height;
	}


	public function setHeight(int $height): self
	{
		$this->height = $height;

		return $this;
	}


	public function __toString(): string
	{
		return $this->src;
	}
}
