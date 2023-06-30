<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 */

namespace Harmim\Images;

use Nette;


readonly class ImageStorage
{
	final public const
		RETURN_ORIG = 'return_orig',
		RETURN_COMPRESSED = 'return_compressed';


	/**
	 * @var array<string, mixed>
	 */
	private array $types;

	private string $baseDir;

	private string $placeholder;

	private string $origDir;

	private string $compressionDir;


	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(private array $config)
	{
		$this->types = $config['types'] && is_array($config['types']) ? $config['types'] : [];
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
			return $this->saveImage($file->getSanitizedName(), $file->getTemporaryFile());
		}

		throw new Nette\IOException("Upload error code: {$file->getError()}.");
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
	 * @param string $file
	 * @param list<string> $excludedTypes
	 * @return void
	 */
	public function deleteImage(string $file, array $excludedTypes = []): void
	{
		if (!$excludedTypes) {
			Nette\Utils\FileSystem::delete($this->getOrigPath($file));
			Nette\Utils\FileSystem::delete($this->getCompressionPath($file));
		}

		foreach ($this->types as $key => $value) {
			if (!$excludedTypes || !in_array($key, $excludedTypes, strict: true)) {
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
	 * @param ?string $type
	 * @param array<string, mixed> $config
	 * @return ?string
	 *
	 * @throws Nette\Utils\ImageException
	 */
	public function getImageLink(string $file, ?string $type = null, array $config = []): ?string
	{
		if ($type !== null) {
			$config['type'] = $type;
		}

		return ($image = $this->getImage($file, $config)) ? (string) $image : null;
	}


	/**
	 * @internal
	 *
	 * @param string $file
	 * @param array<string, mixed> $config
	 * @return ?Image
	 *
	 * @throws Nette\Utils\ImageException
	 */
	final public function getImage(string $file, array &$config = []): ?Image
	{
		$config = $this->getConfig($config);
		$srcPath = $this->getCompressionPath($file);

		if (!$file || !is_readable($srcPath)) {
			return $this->getPlaceholderImage($config);
		}

		$destPath = $this->getDestPath($file, $config);

		if (is_readable($destPath)) {
			[$width, $height] = getimagesize($destPath);
		} elseif ($image = $this->createImage($srcPath, $destPath, $config)) {
			[$width, $height] = $image;
		} else {
			return $this->getPlaceholderImage($config);
		}

		return new Image($this->createRelativeWWWPath($destPath), (int) $width, (int) $height);
	}


	/**
	 * @internal
	 *
	 * @param string $fileName
	 * @return string
	 */
	final public function getSubDir(string $fileName): string
	{
		return (string) (ord(substr($fileName, 0, 1)) % 42);
	}


	/**
	 * @internal
	 *
	 * @param array<string, mixed> $config
	 * @return array<string, mixed>
	 */
	final public function getConfig(array $config = []): array
	{
		$type = [];

		if (
			!empty($config['type'])
			&& array_key_exists($config['type'], $this->types)
			&& is_array($this->types[$config['type']])
		) {
			$type = $this->types[$config['type']];
		}

		return $config + $type + $this->config;
	}


	/**
	 * @param string $srcPath
	 * @param string $destPath
	 * @param array<string, mixed> $config
	 * @return array{int, int}
	 *
	 * @throws Nette\Utils\ImageException
	 */
	private function createImage(string $srcPath, string $destPath, array $config = []): array
	{
		if (!$config) {
			$config = $this->config;
		}

		try {
			$type = null;
			$image = Nette\Utils\Image::fromFile($srcPath);

			Nette\Utils\FileSystem::createDir(dirname($destPath));

			$this->transformImage($image, $config, $srcPath, $type);

			$image->sharpen()->save($destPath, $config['compression'] ?: null, $type);

			return [$image->getWidth(), $image->getHeight()];

		} catch (\Throwable $e) {
			throw new Nette\Utils\ImageException($e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * @param Nette\Utils\Image $image
	 * @param array<string, mixed> $config
	 * @param string $srcPath
	 * @param ?int $type
	 * @return void
	 */
	private function transformImage(Nette\Utils\Image &$image, array $config, string $srcPath, ?int &$type): void
	{
		$resizeFlags = Resize::OR_SMALLER->flag();

		if (!empty($config['transform'])) {
			if (is_array($config['transform']) && count($config['transform']) > 1) {
				foreach ($config['transform'] as $resize) {
					if ($resize === Resize::EXACT) {
						$this->transformExact($image, $config, $srcPath, $type);

						return;
					} elseif ($resize instanceof Resize) {
						$resizeFlags |= $resize->flag();
					}
				}
			} else {
				$resize = is_array($config['transform']) ? $config['transform'][0] : $config['transform'];

				if ($resize === Resize::EXACT){
					$this->transformExact($image, $config, $srcPath, $type);

					return;
				} elseif ($resize instanceof Resize){
					$resizeFlags = $resize->flag();
				}
			}
		}

		$image->resize($config['width'], $config['height'], $resizeFlags);
	}


	/**
	 * @param Nette\Utils\Image $image
	 * @param array<string, mixed> $config
	 * @param string $srcPath
	 * @param ?int $type
	 * @return void
	 */
	private function transformExact(Nette\Utils\Image &$image, array $config, string $srcPath, ?int &$type): void
	{
		if ($this->isTransparentPng($srcPath)) {
			$color = Nette\Utils\Image::rgb(255, 255, 255, 127);
		} else {
			$color = Nette\Utils\Image::rgb(255, 255, 255);
		}

		$blank = Nette\Utils\Image::fromBlank($config['width'], $config['height'], $color);
		$image->resize($config['width'], null);
		$image->resize(null, $config['height']);
		$blank->place(
			$image,
			(int) ($config['width'] / 2 - $image->getWidth() / 2),
			(int) ($config['height'] / 2 - $image->getHeight() / 2),
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


	/**
	 * @param array<string, mixed> $config
	 * @return ?Image
	 */
	private function getPlaceholderImage(array $config): ?Image
	{
		if (!is_readable($this->placeholder)) {
			return null;
		}

		return new Image(
			$this->createRelativeWWWPath($this->placeholder),
			(int) $config['width'],
			(int) $config['height'],
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


	/**
	 * @param string $fileName
	 * @param array<string, mixed> $config
	 * @return string
	 */
	private function getDestPath(string $fileName, array $config): string
	{
		if (!$config) {
			$config = $this->config;
		}

		if (!empty($config[self::RETURN_ORIG])) {
			return $this->getOrigPath($fileName);
		} elseif (!empty($config[self::RETURN_COMPRESSED])) {
			return $this->getCompressionPath($fileName);
		} elseif (!empty($config['destDir'])) {
			$destDir = $config['destDir'];
		} elseif (!empty($config['type']) && array_key_exists($config['type'], $this->types)) {
			$destDir = $config['type'];
		} else {
			$destDir = "w{$config['width']}h{$config['height']}";
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
