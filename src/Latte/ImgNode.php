<?php

declare(strict_types=1);

namespace Harmim\Images\Latte;

use Harmim;
use Latte;
use Nette;


class ImgNode extends Latte\Compiler\Nodes\StatementNode
{
	public function __construct(
		private readonly ?Latte\Compiler\Nodes\Php\ExpressionNode $file,
		private readonly ?Latte\Compiler\Nodes\Php\ExpressionNode $type,
		private readonly Latte\Compiler\Nodes\Php\Expression\ArrayNode $options,
		private readonly string $tagName,
		private readonly bool $isNattribute,
	) {
	}


	public static function create(Latte\Compiler\Tag $tag): self
	{
		$i = $tag->parser->stream->getIndex();
		$file = $tag->parser->isEnd()
			? null
			: $tag->parser->parseUnquotedStringOrExpression();
		if ($tag->parser->stream->tryConsume('=>', ':') !== null) {
			$file = null;
			$tag->parser->stream->seek($i);
		}

		$i = $tag->parser->stream->getIndex();
		$type = $tag->parser->isEnd()
			? null
			: $tag->parser->parseUnquotedStringOrExpression();
		if ($tag->parser->stream->tryConsume('=>', ':') !== null) {
			$type = null;
			$tag->parser->stream->seek($i);
		}

		if ($tag->isNAttribute()) {
			$node = new self(
				$file,
				$type,
				$tag->parser->parseArguments(),
				$tag->name,
				isNattribute: true,
			);
			$node->position = $tag->position;
			$children = $tag->htmlElement?->attributes?->children;
			if ($children !== null) {
				array_unshift($children, $node);
			}

			return $node;
		}

		return new self(
			$file,
			$type,
			$tag->parser->parseArguments(),
			$tag->name,
			isNattribute: false,
		);
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
				$this->formatOptions($context),
			);
			$context->restoreEscape();

			return $res;
		}

		return sprintf(
			'echo %s::%s(%s, %s, $imageStorage);',
			static::class,
			$this->tagName === 'imgLink' ? 'imgLink' : 'img',
			$this->formatFile($context),
			$this->formatOptions($context),
		);
	}


	/**
	 * @return \Generator<Latte\Compiler\Nodes\Php\ExpressionNode>
	 */
	public function &getIterator(): \Generator
	{
		if ($this->file !== null) {
			yield $this->file;
		}
		if ($this->type !== null) {
			yield $this->type;
		}
		yield $this->options;
	}


	private function formatFile(Latte\Compiler\PrintContext $context): string
	{
		return $this->file === null
			? "''"
			: $context->format('%node', $this->file);
	}


	private function formatOptions(Latte\Compiler\PrintContext $context): string
	{
		$options = $this->options->toArguments();

		return sprintf(
			'[%s%s]',
			$this->type === null
				? ''
				: $context->format("'type' => %node, ", $this->type),
			count($options) === 0
				? ''
				: preg_replace(
					'~(^|,\s)([^:]+):\s~',
					"$1'$2' => ",
					$context->format('%args', $options),
				),
		);
	}


	/**
	 * @param  array<string, mixed>  $options
	 * @throws Nette\Utils\ImageException
	 */
	public static function img(
		string $file,
		array $options,
		Harmim\Images\ImageStorage $imageStorage,
	): ?string
	{
		if (($image = $imageStorage->getImage($file, $options)) === null) {
			return null;
		}

		$config = $imageStorage->getConfig($options);
		$attrs = $config->imgTagAttrs;

		$classes = explode(
			' ',
			isset($attrs['class']) ? (string) $attrs['class'] : '',
		);
		unset($attrs['class']);

		$imgTag = Nette\Utils\Html::el('img');
		$imgTag->src = (string) $image;
		$imgTag->class = $classes;
		$imgTag->addAttributes($attrs);
		if (!isset($attrs['alt']) || (string) $attrs['alt'] === '') {
			$imgTag->alt = (string) $image;
		}

		if ($config->lazy) {
			$lazyImgTag = Nette\Utils\Html::el('img');
			$lazyImgTag->data('src', (string) $image);
			array_unshift($classes, 'lazy');
			$lazyImgTag->class = $classes;
			$lazyImgTag->addAttributes($attrs);
			if (!isset($attrs['alt']) || (string) $attrs['alt'] === '') {
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
	 * @param  array<string, mixed>  $options
	 * @throws Nette\Utils\ImageException
	 */
	public static function imgLink(
		string $file,
		array $options,
		Harmim\Images\ImageStorage $imageStorage,
	): ?string
	{
		return $imageStorage->getImageLink($file, options: $options);
	}
}
