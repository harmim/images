<?php

/** @noinspection HtmlUnknownTarget */
/** @noinspection HtmlRequiredAltAttribute */
/** @noinspection RequiredAttributes */

/**
 * TEST: Latte ImagesExtension.
 */

declare(strict_types=1);

namespace Harmim\Tests\Latte;

use Harmim;
use Latte;
use Nette;
use Tester;


require __DIR__
	. DIRECTORY_SEPARATOR
	. '..'
	. DIRECTORY_SEPARATOR
	. 'bootstrap.php';


/**
 * @testCase
 */
final class ImagesExtension extends Tester\TestCase
{
	private Latte\Engine $latteEngine;

	private Harmim\Images\ImageStorage $imageStorage;

	private int $defaultWidth;

	private int $defaultHeight;

	private string $imagesDir;

	private string $fileName;

	private string $fileSubDir;


	/**
	 * @throws Nette\Utils\ImageException
	 * @throws Nette\IOException
	 */
	protected function setUp(): void
	{
		$this->latteEngine = new Latte\Engine();
		$this->latteEngine->addExtension(
			new Harmim\Images\Latte\ImagesExtension(),
		);

		$this->imageStorage = new Harmim\Images\ImageStorage(
			IMAGES_EXTENSION_CONFIG,
		);

		$this->defaultWidth = IMAGES_EXTENSION_CONFIG['width'];
		$this->defaultHeight = IMAGES_EXTENSION_CONFIG['height'];
		$this->imagesDir = IMAGES_EXTENSION_CONFIG['imagesDir'];

		Nette\Utils\FileSystem::copy(
			TEMP_DIR
			. DIRECTORY_SEPARATOR
			. '..'
			. DIRECTORY_SEPARATOR
			. '..'
			. DIRECTORY_SEPARATOR
			. 'noimg.png',
			TEMP_DIR . DIRECTORY_SEPARATOR . 'noimg.png',
		);
		$this->fileName = $this->createImage();
		$this->fileSubDir = $this->imageStorage->getSubDir($this->fileName);
	}


	/**
	 * @throws Nette\Utils\ImageException
	 * @throws Nette\IOException
	 */
	private function createImage(): string
	{
		try {
			$rndInt = random_int(0, PHP_INT_MAX);
		} catch (\Throwable) {
			$rndInt = 42;
		}

		$tmpFileName =
			TEMP_DIR . DIRECTORY_SEPARATOR . 'foo' . $rndInt . '.png';
		Nette\Utils\FileSystem::copy(
			TEMP_DIR . DIRECTORY_SEPARATOR . 'noimg.png',
			$tmpFileName,
		);

		$upload = new Nette\Http\FileUpload([
			'name' => 'foo.png',
			'type' => 'image/png',
			'size' => 373,
			'tmp_name' => $tmpFileName,
			'error' => UPLOAD_ERR_OK,
		]);

		return $this->imageStorage->saveUpload($upload);
	}


	public function testMacroImg(): void
	{
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png" width="'
			. $this->defaultWidth
			. '" height="'
			. $this->defaultHeight
			. '" alt="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png">',
			$this->evalMacro('{img}'),
		);
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png" class="small-class" alt="foo"'
			. ' width="1000" height="1000" title="small-title">',
			$this->evalMacro('{img foo.png img-small alt => "foo"}'),
		);
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'w1024h1024'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="class" title="title" alt="foo" width="'
			. $this->defaultWidth
			. '" height="'
			. $this->defaultHeight
			. '">',
			$this->evalMacro(
				'{img '
				. $this->fileName
				. " class => 'class', title => 'title', alt => 'foo'}",
			),
		);
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="small-class" alt="alt"'
			. ' width="500" height="1000" title="small-title">',
			$this->evalMacro(
				'{img '
				. $this->fileName
				. " img-small alt => 'alt', width => 500}",
			),
		);
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. IMAGES_EXTENSION_CONFIG['origDir']
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="small-class" alt="alt"'
			. ' width="1000" height="1000" title="small-title">',
			$this->evalMacro(
				'{img '
				. $this->fileName
				. " img-small alt => 'alt', orig => true}",
			),
		);
	}


	public function testMacroImgLazy(): void
	{
		Tester\Assert::same(
			'<img data-src="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png" class="lazy" width="'
			. $this->defaultWidth
			. '" height="'
			. $this->defaultHeight
			. '" alt="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png"><noscript><img src="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png" width="'
			. $this->defaultWidth
			. '" height="'
			. $this->defaultHeight
			. '" alt="'
			. DIRECTORY_SEPARATOR
			. 'noimg.png"></noscript>',
			$this->evalMacro('{img foo.png lazy => true}'),
		);
		Tester\Assert::same(
			'<img data-src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'w1024h1024'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="lazy" alt="bar" width="'
			. $this->defaultWidth
			. '" height="'
			. $this->defaultHeight
			. '"><noscript><img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'w1024h1024'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" alt="bar" width="'
			. $this->defaultWidth
			. '" height="'
			. $this->defaultHeight
			. '"></noscript>',
			$this->evalMacro(
				'{img ' . $this->fileName . " lazy => true, alt => 'bar'}",
			),
		);
		Tester\Assert::same(
			'<img data-src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="lazy small-class" alt="x" width="1000" height="1000"'
			. ' title="small-title"><noscript><img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="small-class" alt="x" width="1000" height="1000"'
			. ' title="small-title"></noscript>',
			$this->evalMacro(
				'{img '
				. $this->fileName
				. " img-small lazy => true, alt => 'x'}",
			),
		);
		Tester\Assert::same(
			'<img data-src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="lazy small-class" alt="alt" width="500" height="1000"'
			. ' title="small-title"><noscript><img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="small-class" alt="alt" width="500" height="1000"'
			. ' title="small-title"></noscript>',
			$this->evalMacro(
				'{img '
				. $this->fileName
				. " img-small lazy => true, alt => 'alt', width => 500}",
			),
		);
		Tester\Assert::same(
			'<img data-src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="lazy foo bar" alt="z" width="1000" height="1000"'
			. ' title="small-title"><noscript><img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" class="foo bar" alt="z" width="1000" height="1000"'
			. ' title="small-title"></noscript>',
			$this->evalMacro(
				'{img '
				. $this->fileName
				. " img-small lazy => true, class => 'foo bar', alt => 'z'}",
			),
		);
	}


	public function testAttrMacroImg(): void
	{
		Tester\Assert::same(
			'<img src="' . DIRECTORY_SEPARATOR . 'noimg.png">',
			$this->evalMacro('<img n:img>'),
		);
		Tester\Assert::same(
			'<img src="' . DIRECTORY_SEPARATOR . 'noimg.png" alt="alt">',
			$this->evalMacro('<img n:img="foo.png img-small" alt="alt">'),
		);
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'w1024h1024'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" alt="alt">',
			$this->evalMacro('<img n:img="' . $this->fileName . '" alt="alt">'),
		);
		Tester\Assert::same(
			'<img src="'
			. DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName
			. '" alt="alt" class="class">',
			$this->evalMacro(
				'<img n:img="'
				. $this->fileName
				. ' img-small" alt="alt" class="class">',
			),
		);
		Tester\Assert::exception(
			fn(): string => $this->evalMacro(
				'<img n:img="'
				. $this->fileName
				. ' img-foo" alt="alt" class="class">',
			),
			\AssertionError::class,
		);
	}


	public function testMacroImgLink(): void
	{
		Tester\Assert::same(
			DIRECTORY_SEPARATOR . 'noimg.png',
			$this->evalMacro('{imgLink}'),
		);
		Tester\Assert::same(
			DIRECTORY_SEPARATOR . 'noimg.png',
			$this->evalMacro('{imgLink foo.png}'),
		);
		Tester\Assert::same(
			DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'w1024h1024'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . '}'),
		);
		Tester\Assert::same(
			DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'w20h1024'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . ' width => 20}'),
		);
		Tester\Assert::same(
			DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName,
			$this->evalMacro('{imgLink ' . $this->fileName . ' img-small}'),
		);
		Tester\Assert::same(
			DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. 'img-small'
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName,
			$this->evalMacro(
				'{imgLink ' . $this->fileName . ' img-small width => 20}',
			),
		);
		Tester\Assert::same(
			DIRECTORY_SEPARATOR
			. $this->imagesDir
			. DIRECTORY_SEPARATOR
			. IMAGES_EXTENSION_CONFIG['compressionDir']
			. DIRECTORY_SEPARATOR
			. $this->fileSubDir
			. DIRECTORY_SEPARATOR
			. $this->fileName,
			$this->evalMacro(
				'{imgLink '
				. $this->fileName
				. ' img-small compressed => true}',
			),
		);
	}


	private function evalMacro(string $content): string
	{
		return $this->latteEngine->renderToString(
			Tester\FileMock::create($content, 'latte'),
			[
				'imageStorage' => $this->imageStorage,
			],
		);
	}
}


run(new ImagesExtension());
