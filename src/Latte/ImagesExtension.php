<?php

declare(strict_types=1);

namespace Harmim\Images\Latte;

use Latte;


class ImagesExtension extends Latte\Extension
{
	public function getTags(): array
	{
		return [
			'img' => [ImgNode::class, 'create'],
			'n:img' => [ImgNode::class, 'create'],
			'imgLink' => [ImgNode::class, 'create'],
		];
	}
}
