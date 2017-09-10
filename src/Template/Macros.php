<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

namespace Harmim\Images\Template;

use Harmim;
use Latte;
use Nette;


class Macros extends Latte\Macros\MacroSet
{
	public static function install(Latte\Compiler $compiler): void
	{
		$self = new static($compiler);

		$self->addMacro('img', [$self, 'macroImg'], null, [$self, 'attrMacroImg']);
		$self->addMacro('imgLink', [$self, 'macroImgLink']);
	}


	public function macroImg(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		[$img, $type] = $this->getImageFromNode($node);

		return sprintf(
			'echo %s::img(%s, %s);',
			get_class($this),
			$writer->formatWord($img),
			$this->formatMacroArgs($type, $node, $writer)
		);
	}


	public function attrMacroImg(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		[$img, $type] = $this->getImageFromNode($node);

		return sprintf(
			'echo \' src="\' . %s::imgLink(%s, %s) . \'"\';',
			get_class($this),
			$writer->formatWord($img),
			$this->formatMacroArgs($type, $node, $writer)
		);
	}


	public function macroImgLink(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		[$img, $type] = $this->getImageFromNode($node);

		return sprintf(
			'echo %s::imgLink(%s, %s);',
			get_class($this),
			$writer->formatWord($img),
			$this->formatMacroArgs($type, $node, $writer)
		);
	}


	private function formatMacroArgs(string $type, Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		return sprintf(
			'[%s\'storage\' => $imageStorage%s]',
			$type ? "'type' => '$type', " : '',
			$node->args ? ", {$writer->formatArgs()}" : ''
		);
	}


	private function getImageFromNode(Latte\MacroNode $node): array
	{
		$img = $node->tokenizer->fetchWord();
		$type = '';

		if ($node->tokenizer->isNext()) {
			$type = $node->tokenizer->fetchWord();

			if ($node->tokenizer->isNext() && $node->tokenizer->isNext('=>')) {
				$node->tokenizer->reset();
				$img = $node->tokenizer->fetchWord();
				$type = '';
			}
		}

		return [(string) $img, (string) $type];
	}


	/**
	 * @param string|Harmim\Images\IImage|mixed $img
	 * @param array $args
	 * @return string|null
	 */
	public static function img($img, array $args): ?string
	{
		if ($image = static::getImage($img, $args)) {
			$lazy = !empty($args['lazy']);

			$args = array_filter($args, function ($key) {
				foreach (Harmim\Images\DI\ImagesExtension::DEFAULTS['imgTagAttributes'] as $attr) {
					if (Nette\Utils\Strings::startsWith($key, $attr)) {
						return true;
					}
				}

				return false;
			}, ARRAY_FILTER_USE_KEY);

			$classes = explode(' ', $args['class'] ?? '');
			unset($args['class']);

			$imgTag = Nette\Utils\Html::el('img');
			$imgTag->src = (string) $image;
			$imgTag->class = $classes;
			$imgTag->addAttributes($args);

			if ($lazy) {
				$lazyImgTag = Nette\Utils\Html::el('img');
				$lazyImgTag->data('src', (string) $image);
				array_unshift($classes, 'lazy');
				$lazyImgTag->class = $classes;
				$lazyImgTag->addAttributes($args);

				$noscriptTag = Nette\Utils\Html::el('noscript');
				$noscriptTag->addHtml($imgTag);

				$wrapper = Nette\Utils\Html::el()
					->addHtml($lazyImgTag)
					->addHtml($noscriptTag);

				return (string) $wrapper;
			}

			return (string) $imgTag;
		}

		return null;
	}


	/**
	 * @param string|Harmim\Images\IImage|mixed $img
	 * @param array $args
	 * @return string|null
	 */
	public static function imgLink($img, array $args): ?string
	{
		if ($image = static::getImage($img, $args)) {
			return (string) $image;
		}

		return null;
	}


	/**
	 * @param string|Harmim\Images\IImage|mixed $img
	 * @param array $args
	 * @return Harmim\Images\Image|null
	 * @throws Nette\InvalidStateException
	 */
	public static function getImage($img, array &$args): ?Harmim\Images\Image
	{
		if (empty($args['storage']) || !$args['storage'] instanceof Harmim\Images\ImageStorage) {
			throw new Nette\InvalidStateException(sprintf(
				'The template was not forwarded instance of %s to macro img/imgLink, it should have in variable $imageStorage.',
				Harmim\Images\ImageStorage::class
			));
		}

		/** @var Harmim\Images\ImageStorage $imageStorage */
		$imageStorage = $args['storage'];
		unset($args['storage']);


		$image = $imageStorage->getImage($img, $args);
		$args = $imageStorage->getOptions($args);

		return $image;
	}
}
