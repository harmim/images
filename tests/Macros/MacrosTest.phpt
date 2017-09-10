<?php

declare(strict_types=1);

/**
 * Test: Macros
 *
 * @testCase Harmim\Tests\Macros\MacrosTest
 *
 * @author Dominik Harmim <harmim6@gmail.com>
 * @copyright Copyright (c) 2017 Dominik Harmim
 */

namespace Harmim\Tests\Macros;

use Harmim;
use Latte;
use Nette;
use Tester;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MacrosTest extends Tester\TestCase
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
	private $imagesDir;

	/**
	 * @var string
	 */
	private $fileName;

	/**
	 * @var string
	 */
	private $fileSubDir;


	protected function setUp(): void
	{
		$this->latteEngine = new Latte\Engine();
		Harmim\Images\Template\Macros::install($this->latteEngine->getCompiler());

		$this->imageStorage = new Harmim\Images\ImageStorage((array) IMAGES_EXTENSION_CONFIG);

		$this->defaultWidth = IMAGES_EXTENSION_CONFIG['width'];
		$this->defaultHeight = IMAGES_EXTENSION_CONFIG['height'];
		$this->imagesDir = IMAGES_EXTENSION_CONFIG['imagesDir'];

		Nette\Utils\FileSystem::copy(__TEMP_DIR__ . '/../../noimg.png', __TEMP_DIR__ . '/noimg.png');
		$this->fileName = $this->createImage();
		$this->fileSubDir = $this->imageStorage->getSubDir($this->fileName);
	}


	private function createImage(): string
	{
		$tmpFileName = __TEMP_DIR__ . '/foo' . random_int(0, PHP_INT_MAX) . '.png';
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
			'<img src="/noimg.png" width="' . $this->defaultWidth . '" height="' . $this->defaultHeight . '">',
			$this->evalMacro('{img}')
		);
		Assert::same(
			'<img src="/noimg.png" class="small-class" width="1000" height="1000" title="small-title">',
			$this->evalMacro('{img foo.png img-small}')
		);
		Assert::same(
			'<img src="/' . $this->imagesDir . '/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '" class="class" title="title" width="' . $this->defaultWidth . '" height="' . $this->defaultHeight . '">',
			$this->evalMacro('{img ' . $this->fileName . ' class => "class", title => "title"}')
		);
		Assert::same(
			'<img src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="small-class" alt="alt" width="500" height="1000" title="small-title">',
			$this->evalMacro('{img ' . $this->fileName . ' img-small alt => "alt", width => 500}')
		);
	}


	public function testMacroImgLazy()
	{
		Assert::same(
			'<img data-src="/noimg.png" class="lazy" width="' . $this->defaultWidth . '" height="' . $this->defaultHeight . '">'
			. '<noscript><img src="/noimg.png" width="' . $this->defaultWidth . '" height="' . $this->defaultHeight . '"></noscript>',
			$this->evalMacro('{img foo.png lazy => true}')
		);
		Assert::same(
			'<img data-src="/' . $this->imagesDir . '/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '" class="lazy" width="' . $this->defaultWidth . '" height="' . $this->defaultHeight . '">'
			. '<noscript><img src="/' . $this->imagesDir . '/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '" width="' . $this->defaultWidth . '" height="' . $this->defaultHeight . '"></noscript>',
			$this->evalMacro('{img ' . $this->fileName . ' lazy => true}')
		);
		Assert::same(
			'<img data-src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="lazy small-class" width="1000" height="1000" title="small-title">'
			. '<noscript><img src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="small-class" width="1000" height="1000" title="small-title"></noscript>',
			$this->evalMacro('{img ' . $this->fileName . ' img-small lazy => true}')
		);
		Assert::same(
			'<img data-src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="lazy small-class" alt="alt" width="500" height="1000" title="small-title">'
			. '<noscript><img src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="small-class" alt="alt" width="500" height="1000" title="small-title"></noscript>',
			$this->evalMacro('{img ' . $this->fileName . ' img-small lazy => true, alt => "alt", width => 500}')
		);
		Assert::same(
			'<img data-src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="lazy foo bar" width="1000" height="1000" title="small-title">'
			. '<noscript><img src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '" class="foo bar" width="1000" height="1000" title="small-title"></noscript>',
			$this->evalMacro('{img ' . $this->fileName . ' img-small lazy => true, class => "foo bar"}')
		);
	}


	public function testAttrMacroImg()
	{
		Assert::same('<img src="/noimg.png">', $this->evalMacro('<img n:img="">'));
		Assert::same(
			'<img alt="alt" src="/noimg.png">',
			$this->evalMacro('<img n:img="foo.png img-small" alt="alt">')
		);
		Assert::same(
			'<img alt="alt" src="/' . $this->imagesDir . '/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '">',
			$this->evalMacro('<img n:img="' . $this->fileName . '" alt="alt">')
		);
		Assert::same(
			'<img alt="alt" class="class" src="/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName . '">',
			$this->evalMacro('<img n:img="' . $this->fileName . ' img-small" alt="alt" class="class">')
		);
		Assert::same(
			'<img alt="alt" class="class" src="/' . $this->imagesDir . '/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName . '">',
			$this->evalMacro('<img n:img="' . $this->fileName . ' img-foo" alt="alt" class="class">')
		);
	}


	public function testMacroImgLink()
	{
		Assert::same('/noimg.png', $this->evalMacro('{imgLink}'));
		Assert::same('/noimg.png', $this->evalMacro('{imgLink foo.png}'));
		Assert::same(
			'/' . $this->imagesDir . '/w1024h1024/' . $this->fileSubDir . '/' . $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . '}')
		);
		Assert::same(
			'/' . $this->imagesDir . '/w20h1024/' . $this->fileSubDir . '/' . $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . ' width => 20}')
		);
		Assert::same(
			'/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . ' img-small}')
		);
		Assert::same(
			'/' . $this->imagesDir . '/img-small/' . $this->fileSubDir . '/' . $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . ' img-small width => 20}')
		);
	}


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


run(new MacrosTest());
