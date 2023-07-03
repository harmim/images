<?php

declare(strict_types=1);

namespace Harmim\Images;

use Nette;


/**
 * @property-read string $src
 * @property-read int $width
 * @property-read int $height
 */
final readonly class Image implements \Stringable
{
	use Nette\SmartObject;

	public function __construct(
		private string $src,
		private int $width,
		private int $height,
	) {
	}


	public function getSrc(): string
	{
		return $this->src;
	}


	public function getWidth(): int
	{
		return $this->width;
	}


	public function getHeight(): int
	{
		return $this->height;
	}


	public function __toString(): string
	{
		return $this->src;
	}
}
