# A tool for working with images

[![Build](https://github.com/harmim/images/actions/workflows/build.yml/badge.svg)](https://github.com/harmim/images/actions/workflows/build.yml)
[![Coding Style](https://github.com/harmim/images/actions/workflows/coding-style.yml/badge.svg)](https://github.com/harmim/images/actions/workflows/coding-style.yml)
[![Tests](https://github.com/harmim/images/actions/workflows/tests.yml/badge.svg)](https://github.com/harmim/images/actions/workflows/tests.yml)
[![Coverage](https://coveralls.io/repos/github/harmim/images/badge.svg)](https://coveralls.io/github/harmim/images)
[![Monthly Downloads](https://poser.pugx.org/harmim/images/d/monthly)](https://packagist.org/packages/harmim/images)
[![Total Downloads](https://poser.pugx.org/harmim/images/downloads)](https://packagist.org/packages/harmim/images)
[![Version](http://poser.pugx.org/harmim/images/version)](https://github.com/harmim/images/tags)
[![PHP Version Require](http://poser.pugx.org/harmim/images/require/php)](https://packagist.org/packages/harmim/images)
[![License](https://poser.pugx.org/harmim/images/license)](https://github.com/harmim/images/blob/master/LICENSE.md)


## About

A tool for working with images. It can be used as an extension of the [Nette Framework](https://nette.org).

There is `Image storage` for storing images easily and/or deleting them from the storage.
There are also several ways how to resize and/or process images. Then, you can get a stored image path directly,
or you can use prepared [Latte](https://latte.nette.org) macros to generate HTML tags. See [Usage](#Usage).

**Requires the PHP version `7.4` or newer and PHP extensions `gd` and `fileinfo`.**


## Installation

Download the [latest release](https://github.com/harmim/images/tags) or use Composer:
```bash
composer require harmim/images
```


## Usage

For working with images, we need `Harmim\Images\ImageStorage`:

### Without Nette

```php
use Harmim\Images\DI\ImagesExtension;
use Harmim\Images\ImageStorage;

$customConfig = [
	'wwwDir' => __DIR__ . DIRECTORY_SEPARATOR . 'www',
	'compression' => 90,
	'placeholder' => 'images/foo.png',
	'types' => [
		'img-small' => [
			'width' => 50,
			'height' => 50,
			'transform' => ImageStorage::RESIZE_EXACT,
			...
		],
		...
	],
	...
];

$imageStorage = new ImageStorage(array_merge_recursive(
	ImagesExtension::DEFAULTS,
	$customConfig,
));
```

In `$customConfig`, you can specify a custom configuration. See [Configuration](#Configuration).

### With Nette

You can enable and customise the extension using your NEON config:
```neon
extensions:
  images: Harmim\Images\DI\ImagesExtension

images:
  compression: 90
  placeholder: images/foo.png
  types:
    img-small:
      width: 50
      height: 50
      transform: Harmim\Images\ImageStorage::RESIZE_EXACT
      ...
    ...
  ...
```

In the `images` section, you can specify a custom configuration. See [Configuration](#Configuration).

`Harmim\Images\ImageStorage` is now registrated in the DI container. You can get it directly from the container:
```php
use Harmim\Images\ImageStorage;

/** @var Nette\DI\Container $container */

$imageStorage = $container->getService('images.imageStorage');
// or
$imageStorage = $container->getByType(ImageStorage::class);
```

Of course, you can inject `Harmim\Images\ImageStorage` through a constructor, inject method, inject annotation, or
any other way.

If you want to use `Harmim\Images\ImageStorage` in a presenter or control where inject methods are called, you
can use trait `Harmim\Images\TImageStorage`. In your presenters, controls, and theire templates, there will be
variable `$imageStorage`.
```php
use Harmim\Images\TImageStorage
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
	use TImageStorage;
}

abstract class BaseControl extends Control
{
	use TImageStorage;
}
```

The extension installs images macros to Latte. See [Macros](#Macros).

### Storing Images

You can store an image using method `Harmim\Images\ImageStorage::saveImage(string $name, string $path): string` or
`Harmim\Images\ImageStorage::saveUpload(Nette\Http\FileUpload $file): string`. An original image will be stored;
then, it will be compresed.

Both methods return a stored image file name. You can use this file name to delete, resize, or retrieve the image.

Images are stored with a unique file name and location.

### Deleting Images

Using method `Harmim\Images\ImageStorage::deleteImage(string $fileName, array $excludedTypes = []): void`,
you can delete an image by `$fileName` which should be a file name returned by `Harmim\Images\ImageStorage::saveImage`
or `Harmim\Images\ImageStorage::saveUpload`.

If you pass `$excludedTypes`, only other types will be deleted; otherwise, all types, the original image, and
the compressed image will be deleted.

### Getting Stored Images' Paths

You can get a stored image path using method
`Harmim\Images\ImageStorage::getImageLink(string $fileName, ?string $type = null, array $options = []): ?Harmim\Images\Image`
or [Macros](#Macros). You can pass a specific type defined in inital options, or you can pass specific options.
See [Configuration](#Configuration). `$fileName` should be a file name returned by
`Harmim\Images\ImageStorage::saveImage`or `Harmim\Images\ImageStorage::saveUpload`.

If you try to get an image of a size or a type for a first time, this image is not yet created, so it will be created
now. Next time, you will get a resized image.

If the image does not exist, a placeholder will be returned.

### Macros

#### `img`

```latte
{img [$image] [image-type] [options]}
```
Renders the `img` tag:
```html
<img src="foo.jpg" width="100" height="100" alt="foo">
```
or tags for lazy loading with the `lazy` option:
```html
<img class="lazy" data-src="foo.jpg" width="100" height="100" alt="foo">
<noscript><img src="foo.jpg" width="100" height="100" alt="foo"></noscript>
```

Examples:
```latte
{img alt => 'foo'} {* returns a path to a placeholder *}

{* '$image' is a file name *}
{img $image alt => 'foo'}
{img $image width => 200, height => 200, alt => 'foo'}

{* 'img-small' is an image type defined in the configuration *}
{img $image img-small alt => 'foo'}
{img $image img-small compression => 90, alt => 'foo', class => 'bar'}

{img $image img-small lazy => true, alt => 'foo'}
{img $image img-small lazy => true, width => 500, height => 650, alt => 'foo'}
```

#### `n:img`

```latte
<img n:img="[$image] [image-type] [options]" alt="foo">
```
Renders the `src` attribute. It can be used, e.g., in the `img` element.

Examples:
```latte
<img n:img alt="foo"> {* renders a path to a placeholder *}

{* '$image' is a file name *}
<img n:img="$image" alt="foo">
<img n:img="$image width => 200, height => 200" width="200" height="200" alt="foo">

{* 'img-small' is an image type defined in the configuration *}
<img n:img="$image img-small" alt="foo">
<img n:img="$image img-small compression => 90" alt="foo" class="bar">
```

#### `imgLink`

```latte
{imgLink [$image] [image-type] [options]}
```
Returns a relative path (from the resource root directory) to a given image.

Examples:
```latte
{imgLink} {* returns a path to a placeholder *}

{* '$image' is a file name *}
{imgLink $image}
{imgLink $image width => 200, height => 200}

{* 'img-small' is an image type defined in the configuration *}
{imgLink $image img-small}
{imgLink $image img-small compression => 90}

<div class="image-box" data-src="{imgLink $image img-small}}"></div>
```


## Configuration

- `wwwDir`: (`string`) An absolute path to the resource root directory.
  * Default: `%wwwDir%` in Nette; otherwise, you have to specify this parameter.
- `imagesDir`: (`string`) A relative path (from `wwwDir`) to a directory for storing images.
  * Default: `data/images`.
- `origDir`: (`string`) A relative path (from `imagesDir`) to a directory for storing original images.
  * Default: `orig`.
- `compressionDir`: (`string`) A relative path (from `imagesDir`) to a directory for storing compressed images.
  * Default: `imgs`.
- `placeholder`: (`string`) A relative path (from `wwwDir`) to an image placeholder (when an image is not found).
  * Default: `img/noimg.jpg`.
- `width`: (`int`) An image width.
  * Default: `1024`.
- `height`: (`int`) An image height.
  * Default: `1024`.
- `compression`: (`int`) A compression quality. See `Nette\Utils\Image::save`.
  * Default: `85`.
- `transform`: (`string`) One of `Harmim\Images\ImageStorage::RESIZE_...` constants, or more constants separated by `|`:

| Option                | Description                                                                   |
|-----------------------|-------------------------------------------------------------------------------|
| `RESIZE_SHRINK_ONLY`  | Only shrinking (prevents a small image from being stretched).                 |
| `RESIZE_STRETCH`      | Do not keep the aspect ratio.                                                 |
| `RESIZE_FIT`          | The resulting dimensions will be smaller or equal to the required dimensions. |
| `RESIZE_FILL`         | Fills (and possibly exceeds in one dimension) the target area.                |
| `RESIZE_EXACT`        | Fills the target area and cuts off what goes beyond.                          |
| `RESIZE_FILL_EXACT`   | Placees a not stretched image to the exact blank area.                        |

  * Default: ` Harmim\Images\ImageStorage::RESIZE_FIT`.
- `imgTagAttributes`: (`array`) `img` attributes you can use in the `{img}` Latte macro, other attributes are ignored.
  * Default: `[alt, height, width, class, hidden, id, style, title, data]`.
- `types`: (`array`) A configuration for image types overriding the default configuration.
  * Default: `[]`.
  * Example:

```neon
types:
  img-small:
    width: 50
    height: 50
  img-gallery:
    lazy: true
    transform: Harmim\Images\ImageStorage::RESIZE_STRETCH
```

- `lazy`: (`bool`) Render the `{img}` Latte macro as a lazy image (with the `data-src` attribute, `lazy` class, and
  normal `img` tag in the `noscript` tag).
  * Default: `false`.


## License

This tool is licensed under the [MIT license](https://github.com/harmim/images/blob/master/LICENSE.md).


---

Author: **Dominik Harmim <[harmim6@gmail.com](mailto:harmim6@gmail.com)>**
