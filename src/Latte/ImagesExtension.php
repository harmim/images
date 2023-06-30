<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images\Latte;

use Latte;


class ImagesExtension extends Latte\Extension
{
	/**
	 * @return array<string, callable(Latte\Compiler\Tag): ?Latte\Compiler\Nodes\StatementNode>
	 */
	public function getTags(): array
	{
		return [
			'img' => [ImgNode::class, 'create'],
			'n:img' => [ImgNode::class, 'create'],
			'imgLink' => [ImgNode::class, 'create'],
		];
	}
}
