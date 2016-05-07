<?php

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;

use Imagick;
use Nette;


class ImageStorage extends Nette\Object
{

	/** @var array */
	private $config;

	/** @var array */
	private $types;


	public function __construct(array $config)
	{
		if ($config['types'] && is_array($config['types'])) {
			$this->types = $config['types'];
		}
		$this->config = $config;
		$this->config['baseDir'] = $baseDir = $config['wwwDir'] . DIRECTORY_SEPARATOR . $config['imagesDir'];
		$this->config['placeholder'] = $config['wwwDir'] . DIRECTORY_SEPARATOR . $config['placeholder'];
		$this->config['absoluteOrigDir'] = $baseDir . DIRECTORY_SEPARATOR . $config['origDir'];
		$this->config['absoluteCompressionDir'] = $baseDir . DIRECTORY_SEPARATOR . $config['compressionDir'];
	}


	/**
	 * @param string|IItem $fileName
	 * @param array $args
	 * @return Image
	 */
	public function getImage($fileName, array $args = [])
	{
		if ($fileName instanceof IItem) {
			$fileName = $fileName->getFileName();
		}

		$options = $this->getOptions($args);
		$srcPath = $this->getCompressionPath($fileName, $options);

		if ( ! $fileName || ! file_exists($srcPath)) {
			return $this->getPlaceholderImage($options);
		}

		$destPath = $this->getDestPath($fileName, $options);

		if (file_exists($destPath)) {
			list($width, $height) = getimagesize($destPath);
		} else {
			if ($image = $this->createImage($srcPath, $destPath, $options)) {
				list($width, $height) = $image;
			} else {
				return $this->getPlaceholderImage($options);
			}
		}

		return new Image($this->createSrc($destPath), $width, $height);
	}


	/**
	 * @param string $srcPath
	 * @param string $destPath
	 * @param array $options
	 * @return array
	 */
	protected function createImage($srcPath, $destPath, array $options = [])
	{
		if ( ! $options) {
			$options = $this->config;
		}

		try {
			$imagick = new Imagick();

			Nette\Utils\FileSystem::createDir(dirname($destPath));

			$imagick->readImage($srcPath);
			$mimetype = $imagick->getImageMimeType();

			if ($imagick->getImageWidth() > $options['width'] || $imagick->getImageHeight() > $options['height']) {
				$imagick->resizeImage($options['width'], $options['height'], Imagick::FILTER_BESSEL, 0.7, TRUE);
			}

			if ($options['square']) {
				$squareColor = 'gray97';
				if ($mimetype === image_type_to_mime_type(IMAGETYPE_PNG) && $imagick->getImageAlphaChannel() === Imagick::ALPHACHANNEL_TRANSPARENT) {
					$squareColor = 'transparent';
				}

				$blank = new Imagick();
				$squareWidth = $options['width'];
				$squareHeight = $options['height'];
				if ($imagick->getImageWidth() < $squareWidth && $imagick->getImageHeight() < $squareHeight) {
					$squareWidth = $imagick->getImageWidth();
					$squareHeight = $imagick->getImageHeight();
				}

				if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
					$imagick->negateImage(FALSE);
				}

				$blank->newImage($squareWidth, $squareHeight, $squareColor);
				$blank->setImageFormat('png');
				$blank->compositeImage($imagick, Imagick::COMPOSITE_OVER, $squareWidth / 2 - $imagick->getImageWidth() / 2, $squareHeight / 2 - $imagick->getImageHeight() / 2);
				$imagick = $blank;
			}

			if ($options['compression']) {
				switch ($mimetype) {
					case image_type_to_mime_type(IMAGETYPE_JPEG):
						$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
						$imagick->setImageCompressionQuality($options['compression']);
						break;

					case image_type_to_mime_type(IMAGETYPE_PNG):
					case image_type_to_mime_type(IMAGETYPE_GIF):
						$imagick->setCompressionQuality($options['compression']);
						break;
				}
			}

			$imagick->writeImage($destPath);

			return [$imagick->getImageWidth(), $imagick->getImageHeight()];
		} catch (\ImagickException $e) {
			trigger_error($e, E_USER_WARNING);
			return [];
		}
	}


	/**
	 * @return Image|NULL
	 */
	protected function getPlaceholderImage(array $options)
	{
		if (file_exists($options['placeholder'])) {
			return new Image($this->createSrc($options['placeholder']), $options['width'], $options['height']);
		}

		return NULL;
	}


	/**
	 * @param string $path
	 * @return string
	 */
	protected function createSrc($path)
	{
		return substr($path, strlen($this->config['wwwDir']));
	}


	/**
	 * @param string $fileName
	 * @param array $options
	 * @return string
	 */
	protected function getCompressionPath($fileName, array $options = [])
	{
		if ( ! $options) {
			$options = $this->config;
		}
		$path = $options['absoluteCompressionDir'] . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR . $fileName;

		return $path;
	}


	/**
	 * @param string $fileName
	 * @param array $options
	 * @return string
	 */
	protected function getOrigPath($fileName, array $options = [])
	{
		if ( ! $options) {
			$options = $this->config;
		}
		$path = $options['absoluteOrigDir'] . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR . $fileName;

		return $path;
	}


	/**
	 * @param string $fileName
	 * @param array $options
	 * @return string
	 */
	protected function getDestPath($fileName, array $options = [])
	{
		if ( ! $options) {
			$options = $this->config;
		}
		if ( ! empty($options['destDir'])) {
			$destDir = $options['destDir'];
		} elseif ( ! empty($options['type']) && array_key_exists($options['type'], $this->types)) {
			$destDir = $options['type'];
		} else {
			$destDir = "w{$options['width']}h{$options['height']}";
		}
		$path = $this->config['baseDir'] . DIRECTORY_SEPARATOR . $destDir . DIRECTORY_SEPARATOR . $this->getSubDir($fileName) . DIRECTORY_SEPARATOR . $fileName;

		return $path;
	}


	/**
	 * @return string
	 */
	protected function getSubDir($fileName)
	{
		return ord(substr($fileName, 0, 1)) % 3;
	}


	/**
	 * @param array $args
	 * @return array
	 */
	protected function getOptions(array $args)
	{
		$type = [];
		if ( ! empty($args['type']) && array_key_exists($args['type'], $this->types)) {
			$type = array_intersect_key($this->types[$args['type']], $this->config);
		}

		return ($args ?: []) + $type + $this->config;
	}


	/**
	 * @param Nette\Http\FileUpload $file
	 * @return string|NULL
	 */
	public function saveUpload(Nette\Http\FileUpload $file)
	{
		if ($file->isOk()) {
			$fileName = $this->getFileNameForSave($file->getName());
			$origPath = $this->getOrigPath($fileName);
			$file->move($origPath);
			if ($this->createImage($origPath, $this->getCompressionPath($fileName))) {
				return basename($origPath);
			}
		}

		return NULL;
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getFileNameForSave($fileName)
	{
		$name = Nette\Utils\Random::generate(10);
		$name = $name . substr(md5($name), -6) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);

		return $name;
	}


	/**
	 * @param string $fileName
	 * @param array $types
	 * @return bool
	 */
	public function deleteImage($fileName, array $types = [])
	{
		if ( ! $types) {
			Nette\Utils\FileSystem::delete($this->getOrigPath($fileName));
			Nette\Utils\FileSystem::delete($this->getCompressionPath($fileName));
		}

		foreach ($this->types as $key => $value) {
			if ( ! $types || ! in_array($key, $types)) {
				Nette\Utils\FileSystem::delete($this->getDestPath($fileName, ['type' => $key]));
			}
		}

		$excludedFolders = array_keys($this->types) + [
			$this->config['origDir'],
			$this->config['compressionDir'],
		];

		if (file_exists($this->config['baseDir'])) {
			/** @var \SplFileInfo $file */
			foreach (Nette\Utils\Finder::find($fileName)
				->from($this->config['baseDir'])
				->exclude($excludedFolders) as $file) {
				Nette\Utils\FileSystem::delete($file->getRealPath());
			}
		}

		return TRUE;
	}

}
