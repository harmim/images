<?php

declare(strict_types=1);

namespace Harmim\Images;

use Harmim;
use Nette;


readonly class ImageStorage
{
	private Harmim\Images\Config\Config $config;

	private string $baseDir;

	private string $placeholder;

	private string $origDir;

	private string $compressionDir;


	/**
	 * @param  array<string, mixed>  $config
	 */
	public function __construct(array $config)
	{
		$this->config = Harmim\Images\Config\Config::fromArray($config);
		$this->baseDir =
			$this->config->wwwDir
			. DIRECTORY_SEPARATOR
			. $this->config->imagesDir;
		$this->placeholder =
			$this->config->wwwDir
			. DIRECTORY_SEPARATOR
			. $this->config->placeholder;
		$this->origDir =
			$this->baseDir . DIRECTORY_SEPARATOR . $this->config->origDir;
		$this->compressionDir =
			$this->baseDir
			. DIRECTORY_SEPARATOR
			. $this->config->compressionDir;
	}


	/**
	 * @throws Nette\Utils\ImageException
	 * @throws Nette\IOException
	 */
	public function saveUpload(Nette\Http\FileUpload $file): string
	{
		if ($file->isOk()) {
			return $this->saveImage(
				$file->getSanitizedName(),
				$file->getTemporaryFile(),
			);
		}

		throw new Nette\IOException(
			sprintf('Upload error code: %d.', $file->getError()),
		);
	}


	/**
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

		$fileName = $this->getUniqueFileName($file->getSanitizedName());
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
	 * @param  list<string>  $excludedTypes
	 */
	public function deleteImage(string $file, array $excludedTypes = []): void
	{
		if (count($excludedTypes) === 0) {
			Nette\Utils\FileSystem::delete($this->getOrigPath($file));
			Nette\Utils\FileSystem::delete($this->getCompressionPath($file));
		}

		foreach (array_keys($this->config->types) as $type) {
			if (
				count($excludedTypes) === 0
				|| !in_array($type, $excludedTypes, strict: true)
			) {
				Nette\Utils\FileSystem::delete(
					$this->getDestPath($file, ['type' => $type]),
				);
			}
		}

		if (is_readable($this->baseDir)) {
			$excludedFolders = array_keys($this->config->types) + [
				$this->config->origDir,
				$this->config->compressionDir,
			];

			foreach (Nette\Utils\Finder::find(
				$this->getSubDir($file) . DIRECTORY_SEPARATOR . $file,
			)->from($this->baseDir)->exclude(...$excludedFolders) as $f) {
				Nette\Utils\FileSystem::delete($f->getRealPath());
			}
		}
	}


	/**
	 * @param  array<string, mixed>  $options
	 * @throws Nette\Utils\ImageException
	 */
	public function getImageLink(
		string $file,
		?string $type = null,
		array $options = [],
	): ?string
	{
		if ($type !== null) {
			$options['type'] = $type;
		}

		return ($image = $this->getImage($file, $options)) === null
			? null
			: (string) $image;
	}


	/**
	 * @internal
	 * @param  array<string, mixed>  $options
	 * @throws Nette\Utils\ImageException
	 */
	final public function getImage(string $file, array $options = []): ?Image
	{
		$config = $this->getConfig($options);
		$srcPath = $this->getCompressionPath($file);

		if ($file === '' || !is_readable($srcPath)) {
			return $this->getPlaceholderImage($config);
		}

		$destPath = $this->getDestPath($file, $config);

		if (
			is_readable($destPath)
			&& ($size = getimagesize($destPath)) !== false
		) {
			[$width, $height] = $size;
		} else {
			[$width, $height] = $this->createImage(
				$srcPath,
				$destPath,
				$config,
			);
		}

		return new Image(
			$this->createRelativeWWWPath($destPath),
			$width,
			$height,
		);
	}


	/**
	 * @internal
	 */
	final public function getSubDir(string $fileName): string
	{
		return (string) (ord(substr($fileName, 0, 1)) % 42);
	}


	/**
	 * @internal
	 * @param  array<string, mixed>  $options
	 */
	final public function getConfig(
		array $options = [],
	): Harmim\Images\Config\Config
	{
		return $this->config->mergeWithOptions($options);
	}


	/**
	 * @return array{int, int}
	 * @throws Nette\Utils\ImageException
	 */
	private function createImage(
		string $srcPath,
		string $destPath,
		Harmim\Images\Config\Config $config = null,
	): array
	{
		if ($config === null) {
			$config = $this->config;
		}

		try {
			$type = null;
			$image = Nette\Utils\Image::fromFile($srcPath);
			Nette\Utils\FileSystem::createDir(dirname($destPath));
			$this->transformImage($image, $config, $srcPath, $type);
			$image->sharpen()->save($destPath, $config->compression, $type);

			return [$image->getWidth(), $image->getHeight()];

		} catch (\Throwable $e) {
			throw new Nette\Utils\ImageException(
				$e->getMessage(),
				$e->getCode(),
				$e,
			);
		}
	}


	/**
	 * @param-out  Nette\Utils\Image  $image
	 * @param-out  ?int  $type
	 */
	private function transformImage(
		Nette\Utils\Image &$image,
		Harmim\Images\Config\Config $config,
		string $srcPath,
		?int &$type,
	): void
	{
		$resizeFlags = Resize::OrSmaller->flag();

		if (is_array($config->transform) && count($config->transform) > 1) {
			foreach ($config->transform as $resize) {
				if ($resize === Resize::Exact) {
					$this->transformExact($image, $config, $srcPath, $type);
					return;
				} elseif ($resize instanceof Resize) {
					$resizeFlags |= $resize->flag();
				}
			}
		} else {
			$resize = is_array($config->transform)
				? $config->transform[0]
				: $config->transform;

			if ($resize === Resize::Exact) {
				$this->transformExact($image, $config, $srcPath, $type);
				return;
			} elseif ($resize instanceof Resize) {
				$resizeFlags = $resize->flag();
			}
		}

		assert(in_array(
			$resizeFlags,
			[
				Nette\Utils\Image::ShrinkOnly,
				Nette\Utils\Image::Stretch,
				Nette\Utils\Image::OrSmaller,
				Nette\Utils\Image::OrBigger,
				Nette\Utils\Image::Cover,
			],
			strict: true,
		));
		$image->resize($config->width, $config->height, $resizeFlags);
	}


	/**
	 * @param-out  Nette\Utils\Image  $image
	 * @param-out  ?int  $type
	 */
	private function transformExact(
		Nette\Utils\Image &$image,
		Harmim\Images\Config\Config $config,
		string $srcPath,
		?int &$type,
	): void
	{
		if ($this->isTransparentPng($srcPath)) {
			$color = Nette\Utils\Image::rgb(255, 255, 255, 127);
		} else {
			$color = Nette\Utils\Image::rgb(255, 255, 255);
		}

		$blank = Nette\Utils\Image::fromBlank(
			$config->width,
			$config->height,
			$color,
		);
		$image->resize($config->width, null);
		$image->resize(null, $config->height);
		$blank->place(
			$image,
			(int) ($config->width / 2 - $image->getWidth() / 2),
			(int) ($config->height / 2 - $image->getHeight() / 2),
		);
		$image = $blank;
		$type = Nette\Utils\Image::PNG;
	}


	private function isTransparentPng(string $path): bool
	{
		if (($finfo = finfo_open(FILEINFO_MIME_TYPE)) === false) {
			return false;
		}

		if (
			finfo_file($finfo, $path) !== image_type_to_mime_type(IMAGETYPE_PNG)
		) {
			return false;
		}

		if (($image = imagecreatefrompng($path)) === false) {
			return false;
		}

		$width = imagesx($image);
		$height = imagesy($image);

		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$color = imagecolorat($image, $x, $y);
				if ($color !== false && ($color & 0x7F00_0000 >> 24) !== 0) {
					return true;
				}
			}
		}

		return false;
	}


	private function getPlaceholderImage(
		Harmim\Images\Config\Config $config,
	): ?Image
	{
		if (!is_readable($this->placeholder)) {
			return null;
		}

		return new Image(
			$this->createRelativeWWWPath($this->placeholder),
			$config->width,
			$config->height,
		);
	}


	private function createRelativeWWWPath(string $path): string
	{
		return substr($path, strlen($this->config->wwwDir));
	}


	private function getCompressionPath(string $fileName): string
	{
		return $this->compressionDir
			. DIRECTORY_SEPARATOR
			. $this->getSubDir($fileName)
			. DIRECTORY_SEPARATOR
			. $fileName;
	}


	private function getOrigPath(string $fileName): string
	{
		return $this->origDir
			. DIRECTORY_SEPARATOR
			. $this->getSubDir($fileName)
			. DIRECTORY_SEPARATOR
			. $fileName;
	}


	/**
	 * @param  array<string, mixed>|Harmim\Images\Config\Config  $config
	 */
	private function getDestPath(
		string $fileName,
		array|Harmim\Images\Config\Config $config,
	): string
	{
		if (is_array($config)) {
			if (count($config) === 0) {
				$config = $this->config;
			} else {
				$config = $this->getConfig($config);
			}
		}

		if ($config->orig !== null && $config->orig) {
			return $this->getOrigPath($fileName);
		} elseif ($config->compressed !== null && $config->compressed) {
			return $this->getCompressionPath($fileName);
		} elseif ($config->destDir !== null && $config->destDir !== '') {
			$destDir = $config->destDir;
		} elseif ($config->type !== null) {
			$destDir = $config->type;
		} else {
			$destDir = sprintf('w%dh%d', $config->width, $config->height);
		}

		return $this->baseDir
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

		return $name
			. substr(md5($name), -5)
			. substr(str_shuffle(md5($fileName)), -5)
			. '.'
			. pathinfo($fileName, PATHINFO_EXTENSION);
	}
}
