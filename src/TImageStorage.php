<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;

use Harmim;


/**
 * This trait can be used in component attached to Nette\Application\UI\Presenter
 */
trait TImageStorage
{

	/**
	 * @var Harmim\Images\ImageStorage
	 */
	protected $imageStorage;


	public function injectImageStorage(Harmim\Images\ImageStorage $imageStorage)
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
