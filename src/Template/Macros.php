<?php

declare(strict_types=1);

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
	public static function install(Latte\Compiler $compiler): void
	{
		$self = new static($compiler);

		$self->addMacro('img', [$self, 'macroImg'], NULL, [$self, 'attrMacroImg']);
		$self->addMacro('imgLink', [$self, 'macroImgLink']);
	}


	/**
	 * {img $img [type] alt => '...'[, width, height, transform, title, class, compression, ...]}
	 *
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroImg(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		list($img, $type) = $this->getImageFromNode($node);

		return  sprintf('echo %s::img("%s", %s)', get_class($this), $img, $this->formatMacroArgs($type, $node, $writer));
	}


	/**
	 * n:img='$img [type] [, width, height, transform, compression, ...]'
	 *
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function attrMacroImg(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		list($img, $type) = $this->getImageFromNode($node);

		return sprintf('echo \' src="\' . %s::imgLink("%s", %s) . \'"\'', get_class($this), $img, $this->formatMacroArgs($type, $node, $writer));
	}


	/**
	 * {imgLink $img [type] [, width, height, transform, compression, ...]}
	 *
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroImgLink(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		list($img, $type) = $this->getImageFromNode($node);

		return sprintf('echo %s::imgLink("%s", %s)', get_class($this), $img, $this->formatMacroArgs($type, $node, $writer));
	}


	/**
	 * @param string $type
	 * @param Latte\MacroNode $node
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	private function formatMacroArgs(string $type, Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		return sprintf(
			'[%s\'storage\' => $imageStorage%s]',
			$type ? "'type' => '$type', " : '',
			$node->args ? ", {$writer->formatArgs()}" : ''
		);
	}


	/**
	 * @param Latte\MacroNode $node
	 * @return array
	 */
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

		return [$img, $type];
	}


	/**
	 * generate HTML
	 *
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return string|NULL
	 */
	public static function img($img, array $args): ?string
	{
		if ($image = static::getImage($img, $args)) {
			$args = array_filter($args, function($key) {
				return in_array($key, Harmim\Images\DI\ImagesExtension::DEFAULTS['imgTagAttributes']);
			}, ARRAY_FILTER_USE_KEY);

			$args['src'] = (string) $image;

			return (string) Nette\Utils\Html::el('img', $args);
		}

		return NULL;
	}


	/**
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return string|NULL
	 */
	public static function imgLink($img, array $args): ?string
	{
		if ($image = static::getImage($img, $args)) {
			return (string) $image;
		}

		return NULL;
	}


	/**
	 * @param string|Harmim\Images\IItem $img
	 * @param array $args
	 * @return Harmim\Images\Image|NULL
	 * @throws Nette\InvalidStateException
	 */
	public static function getImage($img, array &$args): ?Harmim\Images\Image
	{
		if (empty($args['storage']) || ! $args['storage'] instanceof Harmim\Images\ImageStorage) {
			throw new Nette\InvalidStateException(sprintf(
				'The template was not forwarded instance of %s to macro img/imgLink,
				it should have in variable $imageStorage.',
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
