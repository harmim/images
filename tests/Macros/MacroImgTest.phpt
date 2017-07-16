<?php

declare(strict_types=1);

/**
 * Test: MacroImg
 *
 * @testCase Harmim\Tests\Macros\MacroImgTest
 *
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2016 Dominik Harmim
 */

namespace Harmim\Tests\Macros;

use Harmim;
use Latte;
use Nette;
use Tester;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MacroImgTest extends Tester\TestCase
{
	/**
	 * @var Latte\Engine
	 */
	private $latteEngine;

	/**
	 * @var Harmim\Images\ImageStorage
	 */
	private $imageStorage;

	/**
	 * @var int
	 */
	private $defaultWidth;

	/**
	 * @var int
	 */
	private $defaultHeight;

	/**
	 * @var string
	 */
	private $fileName;

	/**
	 * @var string
	 */
	private $fileSubDir;


	protected function setUp()
	{
		$this->latteEngine = new Latte\Engine();
		Harmim\Images\Template\Macros::install($this->latteEngine->getCompiler());

		$this->imageStorage = new Harmim\Images\ImageStorage(IMAGES_EXTENSION_CONFIG);

		$this->defaultWidth = IMAGES_EXTENSION_CONFIG['width'];
		$this->defaultHeight = IMAGES_EXTENSION_CONFIG['height'];

		Nette\Utils\FileSystem::copy(__TEMP_DIR__ . '/../../noimg.png', __TEMP_DIR__ . '/noimg.png');
		$this->fileName = $this->createImage();
		$this->fileSubDir = $this->imageStorage->getSubDir($this->fileName);
	}


	/**
	 * @return string
	 */
	private function createImage(): string
	{
		$tmpFileName = __TEMP_DIR__ . '/foo' . random_int(0, PHP_INT_MAX) .'.png';
		Nette\Utils\FileSystem::copy(__TEMP_DIR__ . '/noimg.png', $tmpFileName);

		$upload = new Nette\Http\FileUpload([
			'name' => 'foo.png',
			'type' => 'image/png',
			'size' => 373,
			'tmp_name' => $tmpFileName,
			'error' => UPLOAD_ERR_OK,
		]);

		return $this->imageStorage->saveUpload($upload);
	}


	public function testMacroImg()
	{
		Assert::same(
			'<img width="' . $this->defaultWidth .'" height="' . $this->defaultHeight . '" src="/noimg.png">',
			$this->evalMacro('{img}')
		);
		Assert::same(
			'<img width="1000" height="1000" class="small-class" title="small-title" src="/noimg.png">',
			$this->evalMacro('{img foo.png img-small}')
		);
		Assert::same(
			'<img class="class" title="title" width="' . $this->defaultWidth .'" height="' . $this->defaultHeight . '" src="/data/images/w1024h1024/' . $this->fileSubDir .'/' . $this->fileName .'">',
			$this->evalMacro('{img ' . $this->fileName .' class => "class", title => "title"}')
		);
		Assert::same(
			'<img alt="alt" width="500" height="1000" class="small-class" title="small-title" src="/data/images/img-small/' . $this->fileSubDir . '/' . $this->fileName . '">',
			$this->evalMacro('{img ' . $this->fileName . ' img-small alt => "alt", width => "500"}')
		);
	}


	public function testAttrMacroImg()
	{
		Assert::same('<img src="/noimg.png">', $this->evalMacro('<img n:img="">'));
		Assert::same('<img alt="alt" src="/noimg.png">', $this->evalMacro('<img n:img="foo.jpg img-small" alt="alt">'));
		Assert::same('<img alt="alt" src="/data/images/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '">', $this->evalMacro('<img n:img="' . $this->fileName .'" alt="alt">'));
		Assert::same('<img alt="alt" class="class" src="/data/images/img-small/' . $this->fileSubDir . '/' . $this->fileName . '">', $this->evalMacro('<img n:img="' . $this->fileName . ' img-small" alt="alt" class="class">'));
		Assert::same('<img alt="alt" class="class" src="/data/images/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '">', $this->evalMacro('<img n:img="' . $this->fileName .' img-foo" alt="alt" class="class">'));
	}


	public function testMacroImgLink()
	{
		Assert::same('/noimg.png', $this->evalMacro('{imgLink}'));
		Assert::same('/noimg.png', $this->evalMacro('{imgLink foo.png}'));
		Assert::same('/data/images/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName, $this->evalMacro('{imgLink ' . $this->fileName . '}'));
		Assert::same('/data/images/img-small/' . $this->fileSubDir . '/' . $this->fileName, $this->evalMacro('{imgLink ' . $this->fileName . ' img-small}'));
	}


	/**
	 * @param string $macro
	 * @return string
	 */
	private function evalMacro(string $content): string
	{
		return $this->latteEngine->renderToString(
			Tester\FileMock::create($content, 'latte'),
			[
				'imageStorage' => $this->imageStorage,
			]
		);
	}
}


run(new MacroImgTest());
