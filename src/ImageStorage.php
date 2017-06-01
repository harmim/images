<?php

declare(strict_types=1);

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;

use Nette;


class ImageStorage
{

	use Nette\SmartObject;


	protected const RESIZE_FLAGS = [
		"shrink_only" => Nette\Utils\Image::SHRINK_ONLY,
		"stretch" => Nette\Utils\Image::STRETCH,
		"fit" => Nette\Utils\Image::FIT,
		"fill" => Nette\Utils\Image::FILL,
		"exact" => Nette\Utils\Image::EXACT,
	];


	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var array
	 */
	protected $types;

	/**
	 * @var string
	 */
	protected $baseDir;

	/**
	 * @var string
	 */
	protected $placeholder;

	/**
	 * @var string
	 */
	protected $origDir;

	/**
	 * @var string
	 */
	protected $compressionDir;


	public function __construct(array $config)
	{
		if ($config["types"] && is_array($config["types"])) {
			$this->types = $config["types"];
		}
		$this->config = $config;
		$this->baseDir = $config["wwwDir"] . DIRECTORY_SEPARATOR . $config["imagesDir"];
		$this->placeholder = $config["wwwDir"] . DIRECTORY_SEPARATOR . $config["placeholder"];
		$this->origDir = $this->baseDir . DIRECTORY_SEPARATOR . $config["origDir"];
		$this->compressionDir = $this->baseDir . DIRECTORY_SEPARATOR . $config["compressionDir"];
	}


	/**
	 * @param string|IItem $fileName
	 * @param array $args
	 * @return Image|NULL
	 */
	public function getImage($fileName, array $args = [])
	{
		if ($fileName instanceof IItem) {
			$fileName = $fileName->getFileName();
		}

		$options = $this->getOptions($args);
		$srcPath = $this->getCompressionPath($fileName);

		if ( ! $fileName || ! is_readable($srcPath)) {
			return $this->getPlaceholderImage($options);
		}

		$destPath = $this->getDestPath($fileName, $options);

		if (is_readable($destPath)) {
			list($width, $height) = getimagesize($destPath);

		} else {
			if ($image = $this->createImage($srcPath, $destPath, $options)) {
				list($width, $height) = $image;

			} else {
				return $this->getPlaceholderImage($options);
			}
		}

		return new Image($this->createRelativeWWWPath($destPath), $width, $height);
	}


	/**
	 * @param string $srcPath
	 * @param string $destPath
	 * @param array $options
	 * @return array
	 */
	protected function createImage(string $srcPath, string $destPath, array $options = []): array
	{
		if ( ! $options) {
			$options = $this->config;
		}

		try {
			$type = NULL;
			$image = Nette\Utils\Image::fromFile($srcPath);

			Nette\Utils\FileSystem::createDir(dirname($destPath));

			$resizeFlags = Nette\Utils\Image::FIT;
			if ($options["transform"]) {
				if (strpos($options["transform"], "|") !== FALSE) {
					$resizeFlags = 0;

					foreach (explode("|", $options["transform"]) as $flag) {
						if (isset(static::RESIZE_FLAGS[$flag])) {
							$resizeFlags |= static::RESIZE_FLAGS[$flag];
						}
					}

				} elseif (isset(static::RESIZE_FLAGS[$options["transform"]])) {
					$resizeFlags = static::RESIZE_FLAGS[$options["transform"]];
				}
			}
			$image->resize($options["width"], $options["height"], $resizeFlags);

			if ($options["square"]) {
				$squareWidth = $options["width"];
				$squareHeight = $options["height"];
				$color = Nette\Utils\Image::rgb(255, 255, 255);
				if ($this->isTransparentPng($srcPath)) {
					$color = Nette\Utils\Image::rgb(255, 255, 255, 127);
				}

				if ($image->getWidth() < $squareWidth || $image->getHeight() < $squareHeight) {
					$squareWidth = $image->getWidth();
					$squareHeight = $image->getHeight();
				}

				$blank = Nette\Utils\Image::fromBlank($squareWidth, $squareHeight, $color);
				$blank->place($image, $squareWidth / 2 - $image->getWidth() / 2, $squareHeight / 2 - $image->getHeight() / 2);
				$image = $blank;
				$type = Nette\Utils\Image::PNG;
			}

			$image->sharpen()->save($destPath, $options["compression"] ?: NULL, $type);

			return [$image->getWidth(), $image->getHeight()];

		} catch (\Throwable $e) {
			trigger_error($e, E_USER_ERROR);

			return [];
		}
	}


	/**
	 * @param string $path
	 * @return bool
	 */
	protected function isTransparentPng(string $path): bool
	{
		$type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
		if ($type !== image_type_to_mime_type(IMAGETYPE_PNG)) {
			return FALSE;
		}

		$image = imagecreatefrompng($path);
		$width = imagesx($image);
		$height = imagesy($image);

		for ($i = 0; $i < $width; $i++) {
			for ($j = 0; $j < $height; $j++) {
				$rgba = imagecolorat($image, $i, $j);
				if (($rgba & 0x7F000000) >> 24) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}


	/**
	 * @param array $options
	 * @return Image|NULL
	 */
	protected function getPlaceholderImage(array $options)
	{
		if (is_readable($this->placeholder)) {
			return new Image($this->createRelativeWWWPath($this->placeholder), $options["width"], $options["height"]);
		}

		return NULL;
	}


	/**
	 * @param string $path
	 * @return string
	 */
	protected function createRelativeWWWPath(string $path): string
	{
		return substr($path, strlen($this->config["wwwDir"]));
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getCompressionPath(string $fileName): string
	{
		return $this->compressionDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR . $fileName;
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getOrigPath(string $fileName): string
	{
		return $this->origDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR . $fileName;
	}


	/**
	 * @param string $fileName
	 * @param array $options
	 * @return string
	 */
	protected function getDestPath(string $fileName, array $options = []): string
	{
		if ( ! $options) {
			$options = $this->config;
		}

		if ( ! empty($options["destDir"])) {
			$destDir = $options["destDir"];

		} elseif ( ! empty($options["type"]) && array_key_exists($options["type"], $this->types)) {
			$destDir = $options["type"];

		} else {
			$destDir = "w{$options["width"]}h{$options["height"]}";
		}

		return $this->baseDir . DIRECTORY_SEPARATOR . $destDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName)
			. DIRECTORY_SEPARATOR . $fileName;
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getSubDir(string $fileName): string
	{
		return (string) (ord(substr($fileName, 0, 1)) % 42);
	}


	/**
	 * @param array $args
	 * @return array
	 */
	protected function getOptions(array $args): array
	{
		$type = [];

		if ( ! empty($args["type"]) && array_key_exists($args["type"], $this->types)) {
			$type = array_intersect_key($this->types[$args["type"]], $this->config);
		}

		return ($args ?: []) + $type + $this->config;
	}


	/**
	 * @param Nette\Http\FileUpload $file
	 * @return string
	 * @throws Nette\IOException
	 */
	public function saveUpload(Nette\Http\FileUpload $file): string
	{
		if ($file->isOk()) {
			$fileName = $this->getFileNameForSave($file->getName());
			$origPath = $this->getOrigPath($fileName);

			$file->move($origPath);

			if ($this->createImage($origPath, $this->getCompressionPath($fileName))) {
				return $fileName;
			} else {
				Nette\Utils\FileSystem::delete($origPath);
			}
		}

		throw new Nette\IOException($file->getError());
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getFileNameForSave(string $fileName): string
	{
		$name = Nette\Utils\Random::generate(10);
		$name = $name . substr(md5($name), -5) . substr(str_shuffle(md5($fileName)), -5) . "." . pathinfo($fileName, PATHINFO_EXTENSION);

		return $name;
	}


	/**
	 * @param string $fileName
	 * @param array $excludedTypes
	 * @return bool
	 */
	public function deleteImage(string $fileName, array $excludedTypes = []): bool
	{
		if ( ! $excludedTypes) {
			Nette\Utils\FileSystem::delete($this->getOrigPath($fileName));
			Nette\Utils\FileSystem::delete($this->getCompressionPath($fileName));
		}

		foreach ($this->types as $key => $value) {
			if ( ! $excludedTypes || ! in_array($key, $excludedTypes)) {
				Nette\Utils\FileSystem::delete($this->getDestPath($fileName, ["type" => $key]));
			}
		}

		$excludedFolders = array_keys($this->types) + [
			$this->origDir,
			$this->compressionDir,
		];

		if (is_readable($this->baseDir)) {
			/** @var \SplFileInfo $file */
			foreach (Nette\Utils\Finder::find($this->getSubDir($fileName) . "/" . $fileName)
				->from($this->baseDir)
				->exclude($excludedFolders) as $file) {
				Nette\Utils\FileSystem::delete($file->getRealPath());
			}
		}

		return TRUE;
	}

}
