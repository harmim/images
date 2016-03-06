<?php

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images\Template;

use Harmim;
use Latte;
use Nette;


class Macros extends Latte\Macros\MacroSet
{

	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);

		$me->addMacro('img', [$me, 'macroImg']);
	}


	/**
	 * {img $img [type] {alt, title}}

	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		$img = $node->tokenizer->fetchWord();
		$type = NULL;
		if ($node->tokenizer->isNext()) {
			$type = $node->tokenizer->fetchWord();

			if ($node->tokenizer->isNext() && $node->tokenizer->isNext('=>')) {
				$node->tokenizer->reset();
				$img = $node->tokenizer->fetchWord();
				$type = NULL;
			}
		}

		return '
			$args = [' . ($type ? "'type' => '$type'," : '') . "'storage' => " . '$imageStorage' . ($node->args ? ', ' . $writer->formatArgs() : '') . '];
			echo ' . get_class($this) .'::img(' . $img . ', $args);
		';
	}


	/**
	 * generate HTML
	 *
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return string|null
	 */
	public static function img($img, array $args)
	{
		if (empty($args['storage']) || ! $args['storage'] instanceof Harmim\Images\ImageStorage) {
			throw new Nette\InvalidArgumentException('The template was not forwarded instance of ' . Harmim\Images\ImageStorage::class . ' to macro img, it should have in variable $imageStorage');
		}

		/** @var Harmim\Images\ImageStorage $imageStorage */
		$imageStorage = $args['storage'];
		unset($args['storage']);

		$lazyLoad = isset($args['lazy']) ? (bool) $args['lazy'] : FALSE;
		$alt = ! empty($args['alt']) ? $args['alt'] : '';
		$classes = ! empty($args['class']) ? $args['class'] : NULL;
		$title = ! empty($args['title']) ? $args['title'] : NULL;

		if ($image = $imageStorage->getImage($img, $args)) {
			$staticImg = Nette\Utils\Html::el('img', [
				'src' => $image->src,
				'width' => $image->width,
				'height' => $image->height,
				'alt' => $alt,
				'title' => $title
			]);
			$staticImg->class[] = $classes;

			if ($lazyLoad) {
				$lazyLoadEl = Nette\Utils\Html::el();
				$lazyLoadImg = $lazyLoadEl->create('img', [
					'src' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==',
					'width' => $image->width,
					'height' => $image->height,
					'alt' => $alt,
					'title' => $title,
				]);
				$lazyLoadImg->class[] = $classes;
				$lazyLoadImg->class[] = 'lazy';
				$lazyLoadImg->data['original'] = $image->src;

				$lazyLoadEl->create('span', ['class' => 'lazy-spinner']);
				$lazyLoadEl->create('noscript')->add($staticImg);

				return (string) $lazyLoadImg;
			}

			return (string) $staticImg;
		}

		return NULL;
	}

}
