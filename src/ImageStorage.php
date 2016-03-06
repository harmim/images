<?php

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;

use Nette;
use Tracy;


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
		$this->config['baseDir'] = $baseDir = $config['wwwDir'] . '/' . $config['imagesDir'];
		$this->config['placeholder'] = $config['wwwDir'] . '/' . $config['placeholder'];
		$this->config['absoluteOrigDir'] = $baseDir . '/' . $config['origDir'];
		$this->config['absoluteCompressionDir'] = $baseDir . '/' . $config['compressionDir'];
	}


	/**
	 * @param string|IItem $fileName
	 * @param array $args
	 * @return Image
	 */
	public function getImage($fileName, array $args = [])
	{
		if ($fileName instanceof IItem) {
			$fileName = $fileName->getFilename();
		}

		$options = $this->getOptions($args);
		$srcPath = $this->getCompressionPath($fileName, $options);

		if ( ! $fileName || ! file_exists($srcPath)) {
			if (file_exists($options['placeholder'])) {
				return new Image($this->createSrc($options['placeholder']), $options['width'], $options['height']);
			} else {
				return [];
			}
		}

		$destPath = $this->getDestPath($fileName, $options);

		if (file_exists($destPath)) {
			list($width, $height) = getimagesize($destPath);
		} else {
			if ($image = $this->createImage($srcPath, $destPath, $options)) {
				list($width, $height) = $image;
			} else {
				if (file_exists($options['placeholder'])) {
					return new Image($this->createSrc($options['placeholder']), $options['width'], $options['height']);
				} else {
					return [];
				}
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
			$imagick = new \Imagick();

			$this->checkDir($destPath);

			$imagick->readImage($srcPath);
			$mimetype = $imagick->getImageMimeType();

			if ($imagick->getImageWidth() > $options['width'] || $imagick->getImageHeight() > $options['height']) {
				$imagick->resizeImage($options['width'], $options['height'], \Imagick::FILTER_BESSEL, 0.7, TRUE);
			}

			if ($options['square']) {
				$squareColor = 'gray97';
				if ($mimetype === image_type_to_mime_type(IMAGETYPE_PNG) && $imagick->getImageAlphaChannel() === \Imagick::ALPHACHANNEL_TRANSPARENT) {
					$squareColor = 'transparent';
				}

				$blank = new \Imagick();
				$squareWidth = $options['width'];
				$squareHeight = $options['height'];
				if ($imagick->getImageWidth() < $squareWidth && $imagick->getImageHeight() < $squareHeight) {
					$squareWidth = $imagick->getImageWidth();
					$squareHeight = $imagick->getImageHeight();
				}

				if ($imagick->getimagecolorspace() === \Imagick::COLORSPACE_CMYK) {
					$imagick->negateimage(FALSE);
				}

				$blank->newImage($squareWidth, $squareHeight, $squareColor);
				$blank->setImageFormat('png');
				$blank->compositeImage($imagick, \Imagick::COMPOSITE_OVER, $squareWidth / 2 - $imagick->getImageWidth() / 2, $squareHeight / 2 - $imagick->getImageHeight() / 2);
				$imagick = $blank;
			}

			if ($options['compression']) {
				switch ($mimetype) {
					case image_type_to_mime_type(IMAGETYPE_JPEG):
						$imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
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
			Tracy\Debugger::log($e);
			return [];
		}
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
		$path = $options['absoluteCompressionDir'] . '/' . $this->getSubDir($fileName) . '/' . $fileName;

		return $path;
	}


	protected function getOrigPath($fileName, array $options = [])
	{
		if ( ! $options) {
			$options = $this->config;
		}
		$path = $options['absoluteOrigDir'] . '/' . $this->getSubDir($fileName) . '/' . $fileName;

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
		$path = $this->config['baseDir'] . '/' . $destDir . '/' . $this->getSubDir($fileName) . '/' . $fileName;

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
	 * @param string $fileName
	 * @return void
	 */
	protected function checkDir($path)
	{
		$dir = dirname($path);

		if ( ! is_dir($dir)) {
			mkdir($dir, 0777, TRUE);
		}
	}


	/**
	 * @param Nette\Http\FileUpload $file
	 * @return string|null
	 */
	public function saveUpload(Nette\Http\FileUpload $file)
	{
		if ($file->isOk()) {
			$fileName = $this->getFileNameForSave($file->getName());
			$origPath = $this->getOrigPath($fileName);
			$this->checkDir($origPath);
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
		$nameParts = pathinfo($fileName);
		$name = str_replace('-', '_', Nette\Utils\Strings::webalize($nameParts['filename']));
		$name = $name . substr(md5($name), -6) . '.' . $nameParts['extension'];

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
			@unlink($this->getOrigPath($fileName));
			@unlink($this->getCompressionPath($fileName));
		}

		foreach ($this->types as $key => $value) {
			if ( ! $types || ! in_array($key, $types)) {
				@unlink($this->getDestPath($fileName, ['type' => $key]));
			}
		}

		$excludedFolders = array_keys($this->types) + [
			$this->config['origDir'],
			$this->config['compressionDir'],
		];
		/** @var \SplFileInfo $file */
		foreach (Nette\Utils\Finder::find($fileName)
			->from($this->config['baseDir'])
			->exclude($excludedFolders) as $file) {
			@unlink($file->getRealPath());
		}

		return TRUE;
	}

}
