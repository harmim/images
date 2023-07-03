# A tool for working with images

[![Build](
https://github.com/harmim/images/actions/workflows/build.yml/badge.svg)](
https://github.com/harmim/images/actions/workflows/build.yml)
[![Coding Style](
https://github.com/harmim/images/actions/workflows/coding-style.yml/badge.svg)](
https://github.com/harmim/images/actions/workflows/coding-style.yml)
[![Static Analysis](
https://github.com/harmim/images/actions/workflows/static-analysis.yml/badge.svg)](
https://github.com/harmim/images/actions/workflows/static-analysis.yml)
[![Tests](
https://github.com/harmim/images/actions/workflows/tests.yml/badge.svg)](
https://github.com/harmim/images/actions/workflows/tests.yml)
[![Coverage](
https://coveralls.io/repos/github/harmim/images/badge.svg)](
https://coveralls.io/github/harmim/images)
[![Monthly Downloads](
https://poser.pugx.org/harmim/images/d/monthly)](
https://packagist.org/packages/harmim/images)
[![Total Downloads](
https://poser.pugx.org/harmim/images/downloads)](
https://packagist.org/packages/harmim/images)
[![Version](
http://poser.pugx.org/harmim/images/version)](
https://github.com/harmim/images/tags)
[![PHP Version Require](
http://poser.pugx.org/harmim/images/require/php)](
https://packagist.org/packages/harmim/images)
[![License](
https://poser.pugx.org/harmim/images/license)](
https://github.com/harmim/images/blob/master/LICENSE.md)


## About

A tool for working with images. It can be used as an extension of the
[Nette Framework](https://nette.org).

There is `Image storage` for storing images easily and/or deleting them from
the storage. There are also several ways how to resize and/or process images.
Then, you can get a stored image path directly, or you can use prepared
[Latte](https://latte.nette.org) macros to generate HTML tags.
See [Usage](#Usage).

**Requires the PHP version `8.2` or newer and PHP extensions `fileinfo`, `gd`,
and `intl`.**


## Installation

Download the [latest release](https://github.com/harmim/images/tags) or use
Composer:
```bash
composer require harmim/images
```


## Usage

For working with images, we need `\Harmim\Images\ImageStorage`:

### Without Nette

```php
$customConfig = [
	'wwwDir' => __DIR__ . DIRECTORY_SEPARATOR . 'www',
	'compression' => 90,
	'placeholder' => 'images/foo.png',
	'types' => [
		'img-small' => [
			'width' => 50,
			'height' => 50,
			'transform' => \Harmim\Images\Resize::Exact,
			...
		],
		...
	],
	...
];
$imageStorage = $customConfig + \Harmim\Images\Config\Config::Defaults;
```

In `$customConfig`, you can specify a custom configuration.
See [Configuration](#Configuration).

### With Nette

You can enable and customise the extension using your NEON config:
```neon
extensions:
  images: \Harmim\Images\DI\ImagesExtension

images:
  compression: 90
  placeholder: images/foo.png
  types:
    img-small:
      width: 50
      height: 50
      transform: ::constant(\Harmim\Images\Resize::Exact)
      ...
    ...
  ...
```

In the `images` section, you can specify a custom configuration.
See [Configuration](#Configuration).

`\Harmim\Images\ImageStorage` is now registrated in the DI container. You can
get it directly from the container:
```php
/** @var \Nette\DI\Container $container */
$imageStorage = $container->getService('images.imageStorage');
// or
$imageStorage = $container->getByType(\Harmim\Images\ImageStorage::class);
```

Of course, you can inject `\Harmim\Images\ImageStorage` through a constructor,
inject method, inject annotation, or any other way.

If you want to use `\Harmim\Images\ImageStorage` in a presenter or control
where inject methods are called, you can use trait
`\Harmim\Images\TImageStorage`. In your presenters, controls, and theire
templates, there will be variable `$imageStorage`.
```php
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{
	use \Harmim\Images\TImageStorage;
}

abstract class BaseControl extends \Nette\Application\UI\Control
{
	use \Harmim\Images\TImageStorage;
}
```

The extension installs images macros to Latte. See [Macros](#Macros).

### Storing Images

You can store an image using method
`\Harmim\Images\ImageStorage::saveImage(string $name, string $path): string` or
`\Harmim\Images\ImageStorage::saveUpload(\Nette\Http\FileUpload $file): string`.
An original image will be stored; then, it will be compresed.

Both methods return a stored image file name. You can use this file name to
delete, resize, or retrieve the image.

Images are stored with a unique file name and location.

### Deleting Images

Using method
`\Harmim\Images\ImageStorage::deleteImage(string $fileName, array $excludedTypes = []): void`,
you can delete an image by `$fileName` which should be a file name returned by
`\Harmim\Images\ImageStorage::saveImage` or
`\Harmim\Images\ImageStorage::saveUpload`.

If you pass `$excludedTypes`, only other types will be deleted; otherwise, all
types, the original image, and the compressed image will be deleted.

### Getting Stored Images' Paths

You can get a stored image path using method
`\Harmim\Images\ImageStorage::getImageLink(string $fileName, ?string $type = null, array $options = []): ?string`
or [Macros](#Macros). You can pass a specific type defined in an inital
configuration, or you can pass specific options. See
[Configuration](#Configuration). `$fileName` should be a file name returned by
`\Harmim\Images\ImageStorage::saveImage` or
`\Harmim\Images\ImageStorage::saveUpload`.

If you try to get an image of a size or a type for a first time, this image is
not yet created, so it will be created now. Next time, you will get a resized
image.

If the image does not exist, a placeholder will be returned.

In case you need to get an original/compressed image, in the configuration,
you can use `orig/compressed`, respectively. For example, `['orig' => true]`.
It is also possible to use these options in macros.

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
<img n:img="$image width => 200, height => 200" width="200" height="200"
     alt="foo">

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
- `imagesDir`: (`string`) A relative path (from `wwwDir`) to a directory for
  storing images.
  * Default: `data/images`.
- `origDir`: (`string`) A relative path (from `imagesDir`) to a directory for
  storing original images.
  * Default: `orig`.
- `compressionDir`: (`string`) A relative path (from `imagesDir`) to a
  directory for storing compressed images.
  * Default: `imgs`.
- `placeholder`: (`string`) A relative path (from `wwwDir`) to an image
  placeholder (when an image is not found).
  * Default: `img/noimg.jpg`.
- `width`: (`int`) An image width.
  * Default: `1024`.
- `height`: (`int`) An image height.
  * Default: `1024`.
- `compression`: (`int`) A compression quality. See `\Nette\Utils\Image::save`.
  * Default: `85`.
- `transform`: (`\Harmim\Images\Resize|list<\Harmim\Images\Resize>`) See
  [Transform-Options](#Transform-Options).
  * Default: `\Harmim\Images\Resize::OrSmaller`.
- `allowedImgTagAttrs`: (`list<string>`) `img` attributes you can use in the
  `{img}` Latte macro, other attributes are ignored.
  * Default: `[alt, height, width, class, hidden, id, style, title, data]`.
- `lazy`: (`bool`) Render the `{img}` Latte macro as a lazy image (with the
  `data-src` attribute, `lazy` class, and normal `img` tag in the `noscript`
  tag).
  * Default: `false`.
- `types`: (`array<string, mixed>`) A configuration for image types overriding
  the default configuration.
  * Default: `[]`.
  * Example:
```neon
types:
  img-small:
    width: 50
    height: 50
  img-gallery:
    lazy: true
    transform:
    	- ::constant(\Harmim\Images\Resize::Stretch)
    	- ::constant(\Harmim\Images\Resize::Cover)
```
- `destDir`: (`?string`) A directory where to find images.
  * Default: `null`.
- `orig`: (`?bool`) When set to `true`, the original image will be returned.
  * Default: `null`.
- `compressed`: (`?bool`) When set to `true`, the original compressed image
  will be returned.
  * Default: `null`.

### Transform-Options

| Option                              | Description                                                                   |
|-------------------------------------|-------------------------------------------------------------------------------|
| `\Harmim\Images\Resize::ShrinkOnly` | Only shrinking (prevents a small image from being stretched).                 |
| `\Harmim\Images\Resize::Stretch`    | Do not keep the aspect ratio.                                                 |
| `\Harmim\Images\Resize::OrSmaller`  | The resulting dimensions will be smaller or equal to the required dimensions. |
| `\Harmim\Images\Resize::OrBigger`   | Fills (and possibly exceeds in one dimension) the target area.                |
| `\Harmim\Images\Resize::Cover`      | Fills the target area and cuts off what goes beyond.                          |
| `\Harmim\Images\Resize::Exact`      | Placees a not stretched image to the exact blank area.                        |


## License

This tool is licensed under the
[MIT license](https://github.com/harmim/images/blob/master/LICENSE.md).


---

Author: **Dominik Harmim <[harmim6@gmail.com](mailto:harmim6@gmail.com)>**
