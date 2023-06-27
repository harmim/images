<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images;

use Nette;


class ImageStorage
{
	use Nette\SmartObject;


	public const RETURN_ORIG = 'return_orig',
		RETURN_COMPRESSED = 'return_compressed';

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

	private array $config;

	private array $types = [];

	private string $baseDir;

	private string $placeholder;

	private string $origDir;

	private string $compressionDir;


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


	/**
	 * @param Nette\Http\FileUpload $file
	 * @return string
	 *
	 * @throws Nette\Utils\ImageException
	 * @throws Nette\IOException
	 */
	public function saveUpload(Nette\Http\FileUpload $file): string
	{
		if ($file->isOk()) {
			return $this->saveImage((string) $file->getName(), (string) $file->getTemporaryFile());
		}

		throw new Nette\IOException($file->getError());
	}


	/**
	 * @param string $name
	 * @param string $path
	 * @return string
	 *
	 * @throws Nette\Utils\ImageException
	 */
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
	 * @param string $file
	 * @param string[] $excludedTypes
	 * @return void
	 */
	public function deleteImage(string $file, array $excludedTypes = []): void
	{
		if (!$excludedTypes) {
			Nette\Utils\FileSystem::delete($this->getOrigPath($file));
			Nette\Utils\FileSystem::delete($this->getCompressionPath($file));
		}

		foreach ($this->types as $key => $value) {
			if (!$excludedTypes || !in_array($key, $excludedTypes, true)) {
				Nette\Utils\FileSystem::delete($this->getDestPath($file, ['type' => $key]));
			}
		}

		if (is_readable($this->baseDir)) {
			$excludedFolders = array_keys($this->types) + [
				$this->config['origDir'],
				$this->config['compressionDir'],
			];

			foreach (Nette\Utils\Finder::find($this->getSubDir($file) . DIRECTORY_SEPARATOR . $file)
				->from($this->baseDir)
				->exclude(...$excludedFolders) as $file) {
				Nette\Utils\FileSystem::delete($file->getRealPath());
			}
		}
	}


	/**
	 * @param string $file
	 * @param string|null $type
	 * @param array $options
	 * @return string|null
	 *
	 * @throws Nette\Utils\ImageException
	 */
	public function getImageLink(string $file, ?string $type = null, array $options = []): ?string
	{
		if ($type !== null) {
			$options['type'] = $type;
		}

		return ($image = $this->getImage($file, $options)) ? (string) $image : null;
	}


	/**
	 * @internal
	 *
	 * @param string $file
	 * @param array $args
	 * @return Image|null
	 *
	 * @throws Nette\Utils\ImageException
	 */
	public function getImage(string $file, array $args = []): ?Image
	{
		$options = $this->getOptions($args);
		$srcPath = $this->getCompressionPath($file);

		if (!$file || !is_readable($srcPath)) {
			return $this->getPlaceholderImage($options);
		}

		$destPath = $this->getDestPath($file, $options);

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
	 *
	 * @param string $fileName
	 * @return string
	 */
	public function getSubDir(string $fileName): string
	{
		return (string) (ord(substr($fileName, 0, 1)) % 42);
	}


	/**
	 * @internal
	 *
	 * @param array $args
	 * @return array
	 */
	public function getOptions(array $args = []): array
	{
		$type = [];

		if (
			!empty($args['type'])
			&& array_key_exists($args['type'], $this->types)
			&& is_array($this->types[$args['type']])
		) {
			$type = $this->types[$args['type']];
		}

		return $args + $type + $this->config;
	}


	/**
	 * @param string $srcPath
	 * @param string $destPath
	 * @param array $options
	 * @return array
	 *
	 * @throws Nette\Utils\ImageException
	 */
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


	private function transformImage(Nette\Utils\Image &$image, array $options, string $srcPath, ?int &$type): void
	{
		$resizeFlags = static::RESIZE_FLAGS[static::RESIZE_FIT];

		if (!empty($options['transform'])) {
			if (strpos($options['transform'], '|') !== false) {
				$resizeFlags = 0;

				foreach (explode('|', $options['transform']) as $flag) {
					if (isset(static::RESIZE_FLAGS[$flag])) {
						$resizeFlags |= static::RESIZE_FLAGS[$flag];
					} elseif ($flag === static::RESIZE_FILL_EXACT) {
						$this->transformFillExact($image, $options, $srcPath, $type);

						return;
					}
				}
			} elseif (isset(static::RESIZE_FLAGS[$options['transform']])) {
				$resizeFlags = static::RESIZE_FLAGS[$options['transform']];
			} elseif ($options['transform'] === static::RESIZE_FILL_EXACT) {
				$this->transformFillExact($image, $options, $srcPath, $type);

				return;
			}
		}

		$image->resize($options['width'], $options['height'], $resizeFlags);
	}


	private function transformFillExact(Nette\Utils\Image &$image, array $options, string $srcPath, ?int &$type): void
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
			(int) ($options['width'] / 2 - $image->getWidth() / 2),
			(int) ($options['height'] / 2 - $image->getHeight() / 2),
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

		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				if ((imagecolorat($image, $x, $y) & 0x7F00_0000) >> 24) {
					return true;
				}
			}
		}

		return false;
	}


	private function getPlaceholderImage(array $options): ?Image
	{
		if (!is_readable($this->placeholder)) {
			return null;
		}

		return new Image(
			$this->createRelativeWWWPath($this->placeholder),
			(int) $options['width'],
			(int) $options['height'],
		);
	}


	private function createRelativeWWWPath(string $path): string
	{
		return substr($path, strlen($this->config['wwwDir']));
	}


	private function getCompressionPath(string $fileName): string
	{
		return
			$this->compressionDir
			. DIRECTORY_SEPARATOR
			. $this->getSubDir($fileName)
			. DIRECTORY_SEPARATOR
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

		if (!empty($options[static::RETURN_ORIG])) {
			return $this->getOrigPath($fileName);
		} elseif (!empty($options[static::RETURN_COMPRESSED])) {
			return $this->getCompressionPath($fileName);
		} elseif (!empty($options['destDir'])) {
			$destDir = $options['destDir'];
		} elseif (!empty($options['type']) && array_key_exists($options['type'], $this->types)) {
			$destDir = $options['type'];
		} else {
			$destDir = "w{$options['width']}h{$options['height']}";
		}

		return
			$this->baseDir
			. DIRECTORY_SEPARATOR
			. $destDir
			. DIRECTORY_SEPARATOR
			. $this->getSubDir($fileName)
			. DIRECTORY_SEPARATOR
			. $fileName;
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
		$name = Nette\Utils\Random::generate();

		return
			$name
			. substr(md5($name), -5)
			. substr(str_shuffle(md5($fileName)), -5)
			. '.'
			. pathinfo($fileName, PATHINFO_EXTENSION);
	}
}
