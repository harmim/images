<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images;

use Nette;


enum Resize: string
{
	case SHRINK_ONLY = 'shrink_only';

	case STRETCH = 'stretch';

	case OR_SMALLER = 'or_smaller';

	case OR_BIGGER = 'or_bigger';

	case COVER = 'cover';

	case EXACT = 'exact';


	final public function flag(): ?int
	{
		return match ($this) {
			self::SHRINK_ONLY => Nette\Utils\Image::ShrinkOnly,
			self::STRETCH => Nette\Utils\Image::Stretch,
			self::OR_SMALLER => Nette\Utils\Image::OrSmaller,
			self::OR_BIGGER => Nette\Utils\Image::OrBigger,
			self::COVER => Nette\Utils\Image::Cover,
			self::EXACT => null,
		};
	}
}
