<?php

declare(strict_types=1);

namespace Harmim\Images\Config;

use Harmim;
use Nette;


/**
 * @property-read string $wwwDir
 * @property-read string $imagesDir
 * @property-read string $origDir
 * @property-read string $compressionDir
 * @property-read string $placeholder
 * @property-read int $width
 * @property-read int $height
 * @property-read int $compression
 * @property-read Harmim\Images\Resize|list<Harmim\Images\Resize> $transform
 * @property-read list<string> $allowedImgTagAttrs
 * @property-read array<string,scalar> $imgTagAttrs
 * @property-read bool $lazy
 */
readonly class Items
{
	use Nette\SmartObject;

	public const Defaults = [
		'wwwDir' => '%wwwDir%',
		'imagesDir' => 'data' . DIRECTORY_SEPARATOR . 'images',
		'origDir' => 'orig',
		'compressionDir' => 'imgs',
		'placeholder' => 'img' . DIRECTORY_SEPARATOR . 'noimg.jpg',
		'width' => 1_024,
		'height' => 1_024,
		'compression' => 85,
		'transform' => Harmim\Images\Resize::OrSmaller,
		'allowedImgTagAttrs' => [
			'alt',
			'width',
			'height',
			'class',
			'hidden',
			'id',
			'style',
			'title',
			'data',
		],
		'imgTagAttrs' => [],
		'lazy' => false,
	];


	/**
	 * @param  Harmim\Images\Resize|list<Harmim\Images\Resize>  $transform
	 * @param  list<string>  $allowedImgTagAttrs
	 * @param  array<string, scalar>  $imgTagAttrs
	 */
	public function __construct(
		private string $wwwDir = self::Defaults['wwwDir'],
		private string $imagesDir = self::Defaults['imagesDir'],
		private string $origDir = self::Defaults['origDir'],
		private string $compressionDir = self::Defaults['compressionDir'],
		private string $placeholder = self::Defaults['placeholder'],
		private int $width = self::Defaults['width'],
		private int $height = self::Defaults['height'],
		private int $compression = self::Defaults['compression'],
		private Harmim\Images\Resize|array $transform =
			self::Defaults['transform'],
		private array $allowedImgTagAttrs =
			self::Defaults['allowedImgTagAttrs'],
		private array $imgTagAttrs = self::Defaults['imgTagAttrs'],
		private bool $lazy = self::Defaults['lazy'],
	) {
	}


	final public function getWwwDir(): string
	{
		return $this->wwwDir;
	}


	final public function getImagesDir(): string
	{
		return $this->imagesDir;
	}


	final public function getOrigDir(): string
	{
		return $this->origDir;
	}


	final public function getCompressionDir(): string
	{
		return $this->compressionDir;
	}


	final public function getPlaceholder(): string
	{
		return $this->placeholder;
	}


	final public function getWidth(): int
	{
		return $this->width;
	}


	final public function getHeight(): int
	{
		return $this->height;
	}


	final public function getCompression(): int
	{
		return $this->compression;
	}


	/**
	 * @return Harmim\Images\Resize|list<Harmim\Images\Resize>
	 */
	final public function getTransform(): Harmim\Images\Resize|array
	{
		return $this->transform;
	}


	/**
	 * @return list<string>
	 */
	final public function getAllowedImgTagAttrs(): array
	{
		return $this->allowedImgTagAttrs;
	}


	/**
	 * @return array<string, scalar>
	 */
	final public function getImgTagAttrs(): array
	{
		return $this->imgTagAttrs;
	}


	final public function isLazy(): bool
	{
		return $this->lazy;
	}


	/**
	 * @param  array<string, mixed>  $array
	 */
	public static function fromArray(array $array): self
	{
		assert(isset($array['wwwDir']) && is_string($array['wwwDir']));
		assert(isset($array['imagesDir']) && is_string($array['imagesDir']));
		assert(isset($array['origDir']) && is_string($array['origDir']));
		assert(
			isset($array['compressionDir'])
			&& is_string($array['compressionDir']),
		);
		assert(
			isset($array['placeholder']) && is_string($array['placeholder']),
		);
		assert(isset($array['width']) && is_int($array['width']));
		assert(isset($array['height']) && is_int($array['height']));
		assert(isset($array['compression']) && is_int($array['compression']));
		assert(
			isset($array['transform'])
			&& (
				$array['transform'] instanceof Harmim\Images\Resize
				|| (
					is_array($array['transform'])
					&& array_is_list($array['transform'])
					&& Nette\Utils\Arrays::every(
						$array['transform'],
						static fn(
							mixed $v,
						): bool => $v instanceof Harmim\Images\Resize,
					)
				)
			),
		);
		assert(
			isset($array['allowedImgTagAttrs'])
			&& is_array($array['allowedImgTagAttrs'])
			&& array_is_list($array['allowedImgTagAttrs'])
			&& Nette\Utils\Arrays::every(
				$array['allowedImgTagAttrs'],
				static fn(mixed $v): bool => is_string($v),
			),
		);
		assert(isset($array['lazy']) && is_bool($array['lazy']));
		if (
			isset($array['imgTagAttrs'])
			&& is_array($array['imgTagAttrs'])
			&& count($array['imgTagAttrs']) > 0
		) {
			assert(
				Nette\Utils\Arrays::every(
					$array['imgTagAttrs'],
					static fn(
						mixed $v,
						mixed $k,
					): bool => is_string($k) && is_scalar($v)
				),
			);
			$imgTagAttrs = $array['imgTagAttrs'];
		} else {
			$imgTagAttrs = array_filter(
				$array,
				static function (mixed $v, string $k) use ($array): bool {
					foreach ($array['allowedImgTagAttrs'] as $attr) {
						if (str_starts_with($k, $attr)) {
							assert(is_scalar($v));
							return true;
						}
					}

					return false;
				},
				ARRAY_FILTER_USE_BOTH,
			);
		}
		/** @var array<string, scalar> $imgTagAttrs */

		return new self(
			wwwDir: $array['wwwDir'],
			imagesDir: $array['imagesDir'],
			origDir: $array['origDir'],
			compressionDir: $array['compressionDir'],
			placeholder: $array['placeholder'],
			width: $array['width'],
			height: $array['height'],
			compression: $array['compression'],
			transform: $array['transform'],
			allowedImgTagAttrs: $array['allowedImgTagAttrs'],
			imgTagAttrs: $imgTagAttrs,
			lazy: $array['lazy'],
		);
	}


	/**
	 * @internal
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
