<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;


interface IItem
{
	/**
	 * @return string
	 */
	function getFileName(): string;
}
