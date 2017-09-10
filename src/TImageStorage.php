<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

namespace Harmim\Images;

use Harmim;


trait TImageStorage
{
	/**
	 * @var Harmim\Images\ImageStorage
	 */
	protected $imageStorage;


	public function injectImageStorage(Harmim\Images\ImageStorage $imageStorage): void
	{
		$this->imageStorage = $imageStorage;
	}


	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->imageStorage = $this->imageStorage;

		return $template;
	}
}
