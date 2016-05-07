<?php

/**
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Images;

use Nette;


/**
 * @property string $src
 * @property int $width
 * @property int $height
 */
class Image extends Nette\Object
{

	/** @var string */
	private $src;

	/** @var int */
	private $width;

	/** @var int */
	private $height;


	public function __construct($src, $width, $height)
	{
		$this->src = $src;
		$this->width = $width;
		$this->height = $height;
	}


	/**
	 * @return string
	 */
	public function getSrc()
	{
		return $this->src;
	}


	/**
	 * @param string $src
	 */
	public function setSrc($src)
	{
		$this->src = $src;
	}


	/**
	 * @return int
	 */
	public function getWidth()
	{
		return $this->width;
	}


	/**
	 * @param int $width
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}


	/**
	 * @return int
	 */
	public function getHeight()
	{
		return $this->height;
	}


	/**
	 * @param int $height
	 */
	public function setHeight($height)
	{
		$this->height = $height;
	}

}
