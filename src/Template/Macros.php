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

	/**
	 * @param Latte\Compiler $compiler
	 */
	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);

		$me->addMacro('img', [$me, 'macroImg']);
		$me->addMacro('imgLink', [$me, 'macroImgLink']);
	}


	/**
	 * {img $img type alt => '...'[, width, height, lazy, title, class]}
	 *
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		list($img, $type) = $this->getImageFromNode($node);

		return '
			$args = [' . ($type ? "'type' => '$type', " : NULL) . "'storage' => " . '$imageStorage' . ($node->args ? ', ' . $writer->formatArgs() : NULL) . '];
			echo ' . get_class($this) .'::img(' . $img . ', $args);
		';
	}


	/**
	 * {imgLink $img type}
	 *
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroImgLink(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		list($img, $type) = $this->getImageFromNode($node);

		return '
			$args = [' . ($type ? "'type' => '$type', " : NULL) . "'storage' => " . '$imageStorage' . ($node->args ? ', ' . $writer->formatArgs() : NULL) . '];
			echo ' . get_class($this) . '::imgLink(' . $img . ', $args);
		';
	}


	/**
	 * @param Latte\MacroNode $node
	 * @return array
	 */
	private function getImageFromNode(Latte\MacroNode $node)
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

		return [$img, $type];
	}


	/**
	 * generate HTML
	 *
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return string|NULL
	 */
	public static function img($img, array $args)
	{
		if ($image = \Harmim\Images\Template\Macros::getImage($img, $args)) {
			$lazyLoad = isset($args['lazy']) ? (bool) $args['lazy'] : in_array('lazy', $args, TRUE) !== FALSE;
			$alt = ! empty($args['alt']) ? $args['alt'] : '';
			$classes = ! empty($args['class']) ? $args['class'] : NULL;
			$title = ! empty($args['title']) ? $args['title'] : NULL;

			$staticImg = Nette\Utils\Html::el('img', [
				'src' => $image->getSrc(),
				'width' => $image->getWidth(),
				'height' => $image->getHeight(),
				'alt' => $alt,
				'title' => $title,
			]);
			$staticImg->class[] = $classes;

			if ($lazyLoad) {
				$lazyLoadEl = Nette\Utils\Html::el();
				$lazyLoadImg = $lazyLoadEl->create('img', [
					'src' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==',
					'width' => $image->getWidth(),
					'height' => $image->getHeight(),
					'alt' => $alt,
					'title' => $title,
				]);
				$lazyLoadImg->class[] = $classes;
				$lazyLoadImg->class[] = 'lazy';
				$lazyLoadImg->data('original', $image->getSrc());

				$lazyLoadEl->create('span', ['class' => 'lazy-spinner']);
				$lazyLoadEl->create('noscript')->addHtml($staticImg);

				return (string) $lazyLoadImg;
			}

			return (string) $staticImg;
		}

		return NULL;
	}


	/**
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return string|NULL
	 */
	public static function imgLink($img, array $args)
	{
		if ($image = \Harmim\Images\Template\Macros::getImage($img, $args)) {
			return $image->getSrc();
		}

		return NULL;
	}


	/**
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return Harmim\Images\Image
	 */
	public static function getImage($img, array $args)
	{
		if (empty($args['storage']) || ! $args['storage'] instanceof \Harmim\Images\ImageStorage) {
			throw new Nette\InvalidArgumentException('The template was not forwarded instance of ' . \Harmim\Images\ImageStorage::class . ' to macro img/imgLink, it should have in variable $imageStorage.');
		}

		/** @var Harmim\Images\ImageStorage $imageStorage */
		$imageStorage = $args['storage'];
		unset($args['storage']);

		return $imageStorage->getImage($img, $args);
	}

}
