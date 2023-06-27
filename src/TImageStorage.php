<?php

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images;

use Nette;


trait TImageStorage
{
	protected ImageStorage $imageStorage;


	public function injectImageStorage(ImageStorage $imageStorage): void
	{
		$this->imageStorage = $imageStorage;
	}


	protected function createTemplate(): Nette\Application\UI\ITemplate
	{
		$template = parent::createTemplate();
		$template->imageStorage = $this->imageStorage;

		return $template;
	}
}
