<?php

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;

use Harmim;


trait TImageStorage
{

	/** @var Harmim\Images\ImageStorage */
	protected $imageStorage;


	public function injectImageStorage(Harmim\Images\ImageStorage $imageStorage)
	{
		$this->imageStorage = $imageStorage;
	}


	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		$template->imageStorage = $this->imageStorage;

		return $template;
	}

}
