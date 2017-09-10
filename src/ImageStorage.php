<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

namespace Harmim\Images;

use Nette;


class ImageStorage
{
	use Nette\SmartObject;


	public const RESIZE_SHRINK_ONLY = 'shrink_only',
		RESIZE_STRETCH = 'stretch',
		RESIZE_FIT = 'fit',
		RESIZE_FILL = 'fill',
		RESIZE_EXACT = 'exact',
		RESIZE_FILL_EXACT = 'fill_exact';

	private const RESIZE_FLAGS = [
		self::RESIZE_SHRINK_ONLY => Nette\Utils\Image::SHRINK_ONLY,
		self::RESIZE_STRETCH => Nette\Utils\Image::STRETCH,
		self::RESIZE_FIT => Nette\Utils\Image::FIT,
		self::RESIZE_FILL => Nette\Utils\Image::FILL,
		self::RESIZE_EXACT => Nette\Utils\Image::EXACT,
	];


	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var array
	 */
	private $types = [];

	/**
	 * @var string
	 */
	private $baseDir;

	/**
	 * @var string
	 */
	private $placeholder;

	/**
	 * @var string
	 */
	private $origDir;

	/**
	 * @var string
	 */
	private $compressionDir;


	public function __construct(array $config)
	{
		if ($config['types'] && is_array($config['types'])) {
			$this->types = $config['types'];
		}
		$this->config = $config;
		$this->baseDir = $config['wwwDir'] . DIRECTORY_SEPARATOR . $config['imagesDir'];
		$this->placeholder = $config['wwwDir'] . DIRECTORY_SEPARATOR . $config['placeholder'];
		$this->origDir = $this->baseDir . DIRECTORY_SEPARATOR . $config['origDir'];
		$this->compressionDir = $this->baseDir . DIRECTORY_SEPARATOR . $config['compressionDir'];
	}


	public function saveUpload(Nette\Http\FileUpload $file): string
	{
		if ($file->isOk()) {
			return $this->saveImage((string) $file->getName(), (string) $file->getTemporaryFile());
		}

		throw new Nette\IOException($file->getError());
	}


	public function saveImage(string $name, string $path): string
	{
		$file = new Nette\Http\FileUpload([
			'name' => $name,
			'size' => 0,
			'tmp_name' => $path,
			'error' => UPLOAD_ERR_OK,
		]);

		$fileName = $this->getUniqueFileName($file->getName());
		$origPath = $this->getOrigPath($fileName);

		$file->move($origPath);

		try {
			$this->createImage($origPath, $this->getCompressionPath($fileName));
		} catch (Nette\Utils\ImageException $e) {
			Nette\Utils\FileSystem::delete($origPath);
			throw $e;
		}

		return $fileName;
	}


	/**
	 * @param string|IImage|mixed $fileName
	 * @param array $excludedTypes
	 * @return void
	 */
	public function deleteImage($fileName, array $excludedTypes = []): void
	{
		$fileName = $this->resolveFileName($fileName);

		if (!$excludedTypes) {
			Nette\Utils\FileSystem::delete($this->getOrigPath($fileName));
			Nette\Utils\FileSystem::delete($this->getCompressionPath($fileName));
		}

		foreach ($this->types as $key => $value) {
			if (!$excludedTypes || !in_array($key, $excludedTypes, true)) {
				Nette\Utils\FileSystem::delete($this->getDestPath($fileName, ['type' => $key]));
			}
		}

		if (is_readable($this->baseDir)) {
			$excludedFolders = array_keys($this->types) + [
					$this->origDir,
					$this->compressionDir,
				];

			/** @var \SplFileInfo $file */
			foreach (Nette\Utils\Finder::find($this->getSubDir($fileName) . '/' . $fileName)
				->from($this->baseDir)
				->exclude($excludedFolders) as $file) {
				Nette\Utils\FileSystem::delete($file->getRealPath());
			}
		}
	}


	/**
	 * @param string|IImage|mixed $fileName
	 * @param string|null $type
	 * @param array $options
	 * @return string|null
	 */
	public function getImageLink($fileName, ?string $type = null, array $options = []): ?string
	{
		if ($type !== null) {
			$options['type'] = $type;
		}

		$image = $this->getImage($this->resolveFileName($fileName), $options);

		return $image ? (string) $image : null;
	}


	/**
	 * @internal
	 * @param string|IImage|mixed $fileName
	 * @param array $args
	 * @return Image|null
	 */
	public function getImage($fileName, array $args = []): ?Image
	{
		$fileName = $this->resolveFileName($fileName);

		$options = $this->getOptions($args);
		$srcPath = $this->getCompressionPath($fileName);

		if (!$fileName || !is_readable($srcPath)) {
			return $this->getPlaceholderImage($options);
		}

		$destPath = $this->getDestPath($fileName, $options);

		if (is_readable($destPath)) {
			[$width, $height] = getimagesize($destPath);

		} elseif ($image = $this->createImage($srcPath, $destPath, $options)) {
			[$width, $height] = $image;

		} else {
			return $this->getPlaceholderImage($options);
		}

		return new Image($this->createRelativeWWWPath($destPath), (int) $width, (int) $height);
	}


	/**
	 * @internal
	 * @param string $fileName
	 * @return string
	 */
	public function getSubDir(string $fileName): string
	{
		return (string) (ord(substr($fileName, 0, 1)) % 42);
	}


	/**
	 * @internal
	 * @param array $args
	 * @return array
	 */
	public function getOptions(array $args = []): array
	{
		$type = [];

		if (!empty($args['type']) && array_key_exists($args['type'], $this->types)) {
			$type = $this->types[$args['type']];
		}

		return (array) (($args ?: []) + $type + $this->config);
	}


	private function createImage(string $srcPath, string $destPath, array $options = []): array
	{
		if (!$options) {
			$options = $this->config;
		}

		try {
			$type = null;
			$image = Nette\Utils\Image::fromFile($srcPath);

			Nette\Utils\FileSystem::createDir(dirname($destPath));

			$this->transformImage($image, $options, $srcPath, $type);

			$image->sharpen()->save($destPath, $options['compression'] ?: null, $type);

			return [$image->getWidth(), $image->getHeight()];

		} catch (\Throwable $e) {
			throw new Nette\Utils\ImageException($e->getMessage(), $e->getCode(), $e);
		}
	}


	private function transformImage(Nette\Utils\Image &$image, array $options, string $srcPath, ?int &$type)
	{
		$resizeFlags = Nette\Utils\Image::FIT;

		if (!empty($options['transform'])) {
			if (strpos($options['transform'], '|') !== false) {
				$resizeFlags = 0;

				foreach (explode('|', $options['transform']) as $flag) {
					if (isset(self::RESIZE_FLAGS[$flag])) {
						$resizeFlags |= self::RESIZE_FLAGS[$flag];

					} elseif ($flag === self::RESIZE_FILL_EXACT) {
						$this->transformFillExact($image, $options, $srcPath, $type);

						return;
					}
				}

			} elseif (isset(self::RESIZE_FLAGS[$options['transform']])) {
				$resizeFlags = self::RESIZE_FLAGS[$options['transform']];

			} elseif ($options['transform'] === self::RESIZE_FILL_EXACT) {
				$this->transformFillExact($image, $options, $srcPath, $type);

				return;
			}
		}

		$image->resize($options['width'], $options['height'], $resizeFlags);
	}


	private function transformFillExact(Nette\Utils\Image &$image, array $options, string $srcPath, ?int &$type)
	{
		if ($this->isTransparentPng($srcPath)) {
			$color = Nette\Utils\Image::rgb(255, 255, 255, 127);
		} else {
			$color = Nette\Utils\Image::rgb(255, 255, 255);
		}

		$blank = Nette\Utils\Image::fromBlank($options['width'], $options['height'], $color);
		$image->resize($options['width'], null);
		$image->resize(null, $options['height']);
		$blank->place(
			$image,
			$options['width'] / 2 - $image->getWidth() / 2,
			$options['height'] / 2 - $image->getHeight() / 2
		);
		$image = $blank;
		$type = Nette\Utils\Image::PNG;
	}


	private function isTransparentPng(string $path): bool
	{
		$type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
		if ($type !== image_type_to_mime_type(IMAGETYPE_PNG)) {
			return false;
		}

		$image = imagecreatefrompng($path);
		$width = imagesx($image);
		$height = imagesy($image);

		for ($i = 0; $i < $width; $i++) {
			for ($j = 0; $j < $height; $j++) {
				$rgba = imagecolorat($image, $i, $j);
				if (($rgba & 0x7F000000) >> 24) {
					return true;
				}
			}
		}

		return false;
	}


	private function getPlaceholderImage(array $options): ?Image
	{
		if (is_readable($this->placeholder)) {
			return new Image(
				$this->createRelativeWWWPath($this->placeholder),
				(int) $options['width'],
				(int) $options['height']
			);
		}

		return null;
	}


	private function createRelativeWWWPath(string $path): string
	{
		return substr($path, strlen($this->config['wwwDir']));
	}


	private function getCompressionPath(string $fileName): string
	{
		return $this->compressionDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR
			. $fileName;
	}


	private function getOrigPath(string $fileName): string
	{
		return $this->origDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR . $fileName;
	}


	private function getDestPath(string $fileName, array $options = []): string
	{
		if (!$options) {
			$options = $this->config;
		}

		if (!empty($options['destDir'])) {
			$destDir = $options['destDir'];

		} elseif (!empty($options['type']) && array_key_exists($options['type'], $this->types)) {
			$destDir = $options['type'];

		} else {
			$destDir = "w{$options['width']}h{$options['height']}";
		}

		return $this->baseDir . DIRECTORY_SEPARATOR . $destDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName)
			. DIRECTORY_SEPARATOR . $fileName;
	}


	private function getUniqueFileName(string $fileName): string
	{
		do {
			$fileName = $this->getRandomFileName($fileName);
		} while (file_exists($this->getOrigPath($fileName)));

		return $fileName;
	}


	private function getRandomFileName(string $fileName): string
	{
		$name = Nette\Utils\Random::generate(10);
		$name = $name . substr(md5($name), -5) . substr(str_shuffle(md5($fileName)), -5) . '.'
			. pathinfo($fileName, PATHINFO_EXTENSION);

		return $name;
	}


	/**
	 * @param string|IImage|mixed $fileName
	 * @return string
	 */
	private function resolveFileName($fileName): string
	{
		if ($fileName instanceof IImage) {
			return $fileName->getFileName();
		}

		return (string) $fileName;
	}
}
