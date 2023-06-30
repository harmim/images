<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images\Latte;

use Harmim;
use Latte;
use Nette;


class ImgNode extends Latte\Compiler\Nodes\StatementNode
{
	public function __construct(
		private readonly ?Latte\Compiler\Nodes\Php\ExpressionNode $file,
		private readonly ?Latte\Compiler\Nodes\Php\ExpressionNode $type,
		private readonly Latte\Compiler\Nodes\Php\Expression\ArrayNode $config,
		private readonly string $tagName,
		private readonly bool $isNattribute,
	) {
	}


	public static function create(Latte\Compiler\Tag $tag): ?static
	{
		$i = $tag->parser->stream->getIndex();
		$file = $tag->parser->isEnd() ? null : $tag->parser->parseUnquotedStringOrExpression();
		if ($tag->parser->stream->tryConsume('=>', ':')) {
			$file = null;
			$tag->parser->stream->seek($i);
		}

		$i = $tag->parser->stream->getIndex();
		$type = $tag->parser->isEnd() ? null : $tag->parser->parseUnquotedStringOrExpression();
		if ($tag->parser->stream->tryConsume('=>', ':')) {
			$type = null;
			$tag->parser->stream->seek($i);
		}

		if ($tag->isNAttribute()) {
			$node = new static($file, $type, $tag->parser->parseArguments(), $tag->name, isNattribute: true);
			$node->position = $tag->position;
			array_unshift($tag->htmlElement->attributes->children, $node);

			return null;
		}

		return new static($file, $type, $tag->parser->parseArguments(), $tag->name, isNattribute: false);
	}


	public function print(Latte\Compiler\PrintContext $context): string
	{
		if ($this->isNattribute) {
			$escaper = $context->beginEscape();
			$escaper->enterHtmlAttribute();
			$escaper->enterHtmlAttributeQuote();
			$res = sprintf(
				'echo \' src="\' . %s::imgLink(%s, %s, $imageStorage) . \'"\';',
				static::class,
				$this->formatFile($context),
				$this->formatConfig($context),
			);
			$context->restoreEscape();

			return $res;
		}

		return sprintf(
			'echo %s::%s(%s, %s, $imageStorage);',
			static::class,
			$this->tagName === 'imgLink' ? 'imgLink' : 'img',
			$this->formatFile($context),
			$this->formatConfig($context),
		);
	}


	/**
	 * @return \Generator<Latte\Compiler\Nodes\Php\ExpressionNode>
	 */
	public function &getIterator(): \Generator
	{
		if ($this->file) {
			yield $this->file;
		}
		if ($this->type) {
			yield $this->type;
		}
		yield $this->config;
	}


	private function formatFile(Latte\Compiler\PrintContext $context): string
	{
		return $this->file ? $context->format('%node', $this->file) : "''";
	}


	private function formatConfig(Latte\Compiler\PrintContext $context): string
	{
		$config = $this->config->toArguments();

		return sprintf(
			"[%s%s]",
			$this->type ? $context->format("'type' => %node, ", $this->type) : '',
			$config ? preg_replace('~(^|,\s)([^:]+):\s~', "$1'$2' => ", $context->format('%args', $config)) : '',
		);
	}


	/**
	 * @param string $file
	 * @param array<string, mixed> $config
	 * @param Harmim\Images\ImageStorage $imageStorage
	 * @return ?string
	 *
	 * @throws Nette\Utils\ImageException
	 */
	public static function img(string $file, array $config, Harmim\Images\ImageStorage $imageStorage): ?string
	{
		if (!($image = $imageStorage->getImage($file, $config))) {
			return null;
		}

		$lazy = !empty($config['lazy']);

		$attrs = array_filter($config, static function (string $key) use ($config): bool {
			foreach ($config['imgTagAttributes'] as $attr) {
				if (str_starts_with($key, $attr)) {
					return true;
				}
			}

			return false;
		}, ARRAY_FILTER_USE_KEY);

		$classes = explode(' ', $attrs['class'] ?? '');
		unset($attrs['class']);

		$imgTag = Nette\Utils\Html::el('img');
		$imgTag->src = (string) $image;
		$imgTag->class = $classes;
		$imgTag->addAttributes($attrs);
		if (empty($attrs['alt'])) {
			$imgTag->alt = (string) $image;
		}

		if ($lazy) {
			$lazyImgTag = Nette\Utils\Html::el('img');
			$lazyImgTag->data('src', (string) $image);
			array_unshift($classes, 'lazy');
			$lazyImgTag->class = $classes;
			$lazyImgTag->addAttributes($attrs);
			if (empty($attrs['alt'])) {
				$lazyImgTag->alt = (string) $image;
			}

			$noscriptTag = Nette\Utils\Html::el('noscript');
			$noscriptTag->addHtml($imgTag);

			$wrapper = Nette\Utils\Html::el()
				->addHtml($lazyImgTag)
				->addHtml($noscriptTag);

			return (string) $wrapper;
		}

		return (string) $imgTag;
	}


	/**
	 * @param string $file
	 * @param array<string, mixed> $config
	 * @param Harmim\Images\ImageStorage $imageStorage
	 * @return ?string
	 *
	 * @throws Nette\Utils\ImageException
	 */
	public static function imgLink(string $file, array $config, Harmim\Images\ImageStorage $imageStorage): ?string
	{
		return ($image = $imageStorage->getImage($file, $config)) ? (string) $image : null;
	}
}
