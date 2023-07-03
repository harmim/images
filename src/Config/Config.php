<?php

declare(strict_types=1);

namespace Harmim\Images\Config;

use Harmim;
use Nette;


/**
 * @property-read array<string,Items> $types
 * @property-read ?string $type
 * @property-read ?string $destDir
 * @property-read ?bool $orig
 * @property-read ?bool $compressed
 */
readonly class Config extends Items
{
	public const Defaults = [
		'types' => [],
		'type' => null,
		'destDir' => null,
		'orig' => null,
		'compressed' => null,
	] + parent::Defaults;


	/**
	 * @param  array<string, Items>  $types
	 */
	public function __construct(
		string $wwwDir = self::Defaults['wwwDir'],
		string $imagesDir = self::Defaults['imagesDir'],
		string $origDir = self::Defaults['origDir'],
		string $compressionDir = self::Defaults['compressionDir'],
		string $placeholder = self::Defaults['placeholder'],
		int $width = self::Defaults['width'],
		int $height = self::Defaults['height'],
		int $compression = self::Defaults['compression'],
		Harmim\Images\Resize|array $transform = self::Defaults['transform'],
		array $allowedImgTagAttrs = self::Defaults['allowedImgTagAttrs'],
		array $imgTagAttrs = self::Defaults['imgTagAttrs'],
		bool $lazy = self::Defaults['lazy'],
		private array $types = self::Defaults['types'],
		private ?string $type = self::Defaults['type'],
		private ?string $destDir = self::Defaults['destDir'],
		private ?bool $orig = self::Defaults['orig'],
		private ?bool $compressed = self::Defaults['compressed'],
	) {
		parent::__construct(
			wwwDir: $wwwDir,
			imagesDir: $imagesDir,
			origDir: $origDir,
			compressionDir: $compressionDir,
			placeholder: $placeholder,
			width: $width,
			height: $height,
			compression: $compression,
			transform: $transform,
			allowedImgTagAttrs: $allowedImgTagAttrs,
			imgTagAttrs: $imgTagAttrs,
			lazy: $lazy,
		);
	}


	/**
	 * @return array<string, Items>
	 */
	final public function getTypes(): array
	{
		return $this->types;
	}


	final public function getType(): ?string
	{
		return $this->type;
	}


	final public function getDestDir(): ?string
	{
		return $this->destDir;
	}


	final public function isOrig(): ?bool
	{
		return $this->orig;
	}


	final public function isCompressed(): ?bool
	{
		return $this->compressed;
	}


	public static function fromArray(array $array): self
	{
		$items = parent::fromArray($array);
		assert(isset($array['types']) && is_array($array['types']));
		$types = Nette\Utils\Arrays::map(
			$array['types'],
			static function (
				mixed $typeItems,
				mixed $type,
			) use ($array): parent {
				assert(is_string($type) && is_array($typeItems));
				return parent::fromArray($typeItems + $array);
			},
		);
		$type = isset($array['type']) && is_string($array['type'])
			? $array['type']
			: null;
		$destDir = isset($array['destDir']) && is_string($array['destDir'])
			? $array['destDir']
			: null;
		$orig = isset($array['orig']) && is_bool($array['orig'])
			? $array['orig']
			: null;
		$compressed =
			isset($array['compressed']) && is_bool($array['compressed'])
				? $array['compressed']
				: null;

		return new self(
			wwwDir: $items->wwwDir,
			imagesDir: $items->imagesDir,
			origDir: $items->origDir,
			compressionDir: $items->compressionDir,
			placeholder: $items->placeholder,
			width: $items->width,
			height: $items->height,
			compression: $items->compression,
			transform: $items->transform,
			allowedImgTagAttrs: $items->allowedImgTagAttrs,
			imgTagAttrs: $items->imgTagAttrs,
			lazy: $items->lazy,
			types: $types,
			type: $type,
			destDir: $destDir,
			orig: $orig,
			compressed: $compressed,
		);
	}


	public function toArray(): array
	{
		return [
			'types' => Nette\Utils\Arrays::map(
				$this->types,
				static fn(Items $typeItems): array => $typeItems->toArray(),
			),
		] + get_object_vars($this) + parent::toArray();
	}


	/**
	 * @param  array<string, mixed>  $options
	 */
	public function mergeWithOptions(array $options): self
	{
		$typeConfig = [];
		if (isset($options['type'])) {
			assert(
				is_string($options['type'])
				&& array_key_exists($options['type'], $this->types),
			);
			$typeConfig = $this->types[$options['type']]->toArray();
		}

		$merged = $options + $typeConfig + $this->toArray();

		if (isset($merged['imgTagAttrs'])) {
			$merged['imgTagAttrs'] = array_filter(
				$options,
				static function (mixed $v, string $k) use ($merged): bool {
					if (
						isset($merged['allowedImgTagAttrs'])
						&& is_array($merged['allowedImgTagAttrs'])
					) {
						foreach ($merged['allowedImgTagAttrs'] as $attr) {
							assert(is_string($attr));
							if (str_starts_with($k, $attr)) {
								assert(is_scalar($v));
								return true;
							}
						}
					}

					return false;
				},
				ARRAY_FILTER_USE_BOTH,
			) + $merged['imgTagAttrs'];
		}

		return self::fromArray($merged);
	}
}
