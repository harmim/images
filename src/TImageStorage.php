<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace Harmim\Images;

use Nette;


trait TImageStorage
{
	protected ImageStorage $imageStorage;


	final public function injectImageStorage(ImageStorage $imageStorage): void
	{
		$this->imageStorage = $imageStorage;
	}


	protected function createTemplate(
		?string $class = null,
	): Nette\Application\UI\Template
	{
		$template = parent::createTemplate($class);
		$template->imageStorage = $this->imageStorage;

		return $template;
	}
}
