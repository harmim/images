<?php

declare(strict_types=1);

namespace Harmim\Images;

use Nette;


enum Resize
{
	case ShrinkOnly;
	case Stretch;
	case OrSmaller;
	case OrBigger;
	case Cover;
	case Exact;


	/**
	 * @return Nette\Utils\Image::ShrinkOnly|Nette\Utils\Image::Stretch|Nette\Utils\Image::OrSmaller|Nette\Utils\Image::OrBigger|Nette\Utils\Image::Cover|null
	 */
	public function flag(): ?int
	{
		return match ($this) {
			self::ShrinkOnly => Nette\Utils\Image::ShrinkOnly,
			self::Stretch => Nette\Utils\Image::Stretch,
			self::OrSmaller => Nette\Utils\Image::OrSmaller,
			self::OrBigger => Nette\Utils\Image::OrBigger,
			self::Cover => Nette\Utils\Image::Cover,
			self::Exact => null,
		};
	}
}
