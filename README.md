# A tool for working with images.

[![Build Status](https://travis-ci.org/harmim/images.svg?branch=master)](https://travis-ci.org/harmim/images)
[![Coverage Status](https://coveralls.io/repos/github/harmim/images/badge.svg?branch=master)](https://coveralls.io/github/harmim/images?branch=master)
[![Monthly Downloads](https://poser.pugx.org/harmim/images/d/monthly)](https://packagist.org/packages/harmim/images)
[![Total Downloads](https://poser.pugx.org/harmim/images/downloads)](https://packagist.org/packages/harmim/images)
[![Latest Stable Version](https://poser.pugx.org/harmim/images/v/stable)](https://github.com/harmim/images/releases)
[![License](https://poser.pugx.org/harmim/images/license)](https://github.com/harmim/images/blob/master/LICENSE.md)


---------------------------------------------------------------------------------------------------------------------


## About

A tool for working with images. It can be used as extension to [Nette Framework](https://nette.org).

There is an `Image storage` for easy storing images and/or deleting them from a storage.
There are also several ways to resize and/or process images, then you can get a stored image path directly
or you can use prepared [Latte](https://latte.nette.org) macros to generate finaly HTML tags. See [usage](#usage).

**Requires PHP version 7.1 or newer and PHP extensions `gd` and `fileinfo`.**


---------------------------------------------------------------------------------------------------------------------


## Installation

Download a [latest package](https://github.com/harmim/images/releases) or use a Composer:
```bash
composer require harmim/images
```

---------------------------------------------------------------------------------------------------------------------


## Usage

For working with images, we need `Harmim\Images\ImageStorage`:

##### Without Nette

```php
use Harmim\Images\DI\ImagesExtension;
use Harmim\Images\ImageStorage;

$config = ImagesExtension::DEFAULTS;
$config['wwwDir'] = __DIR__ . '/www'; // path to resource root directory
$customConfig = [ // custom configuration
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

$imageStorage = new ImageStorage(array_merge_recursive($config, $customConfig));
```

In `$customConfig` you can specify custom configuration. See [configuration](#configuration).

##### With Nette

You can enable and customize the extension using your neon config.

```yaml
extensions:
    images: Harmim\Images\DI\ImagesExtension

images: # custom configuration
    compression: 90
    placeholder: "images/foo.png"
    types:
        img-small:
            width: 50
            height: 50
            transform: Harmim\Images\ImageStorage::RESIZE_EXACT
            ...
       ...
    ...
```

In `images` section you can specify custom configuration. See [configuration](#configuration).

`Harmim\Images\ImageStorage` is now registrated in DI container. You can get it directly from container:

```php
use Harmim\Images\ImageStorage;

/** @var Nette\DI\Container $container */

$imageStorage = $container->getService('images.images');
// or
$imageStorage = $container->getByType(ImageStorage::class);
```

Of course you can inject `Harmim\Images\ImageStorage` through constructor, inject method, inject annotation or
another way.

If you want to use `Harmim\Images\ImageStorage` in presenter or control where are called inject methods, then you can
use trait `Harmim\Images\TImageStorage`. In your presenters, controls and theire templates will be
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

Extension installs images macros to Latte. See [macros](#macros).


----------------------------------------------------------------------------------------------------------------------

### Storing images

You can store image using method `Harmim\Images\ImageStorage::saveImage(string $name, string $path): string` or
using `Harmim\Images\ImageStorage::saveUpload(Nette\Http\FileUpload $file): string`.
Original image will be stored, then original image will be compresed and also stored.

Both methods returns stored image file name. You can use this file name to deleting or resizing and getting image.

Images are stored with unique file name and location.


----------------------------------------------------------------------------------------------------------------------


### Deleting images

Using method `Harmim\Images\ImageStorage::deleteImage($fileName, array $excludedTypes = []): void`,
you can delete image by `$fileName` which should be file name returned by `Harmim\Images\ImageStorage::saveImage`
or `Harmim\Images\ImageStorage::saveUpload`, or object implementing `Harmim\Images\IImage`.

If you pass `$excludedTypes`, only other types will be deleted, otherwise all types, original image and compressed
image will be deleted.


----------------------------------------------------------------------------------------------------------------------


### Getting stored images path

You can get stored image path using method
`Harmim\Images\ImageStorage::getImageLink($fileName, ?string $type = null, array $options = []): ?string`
or using [macros](#macros). You can pass specific type defined in inital options or pass specific options.
See [configuration](#configuration). `$fileName` should be file name returned by `Harmim\Images\ImageStorage::saveImage`
or `Harmim\Images\ImageStorage::saveUpload`, or object implementing `Harmim\Images\IImage`.

If you are trying to get image of any size or any type for first time, this image wont be created yet, so it will be created.
Next time you'll get resized image.

If the image does not exist, placeholder will be returned.


----------------------------------------------------------------------------------------------------------------------


### Macros

#### img

```latte
{img [$image] [image-type] [options]}
```

Renders img tag:

```html
<img src="foo.jpg" width="100" height="100">
```

or tags for lazy load with lazy option:

```html
<img class="lazy" data-src="foo.jpg" width="100" height="100">
<noscript><img src="foo.jpg" width="100" height="100"></noscript>
```

Examples:

```latte
{img} {* returns path to placeholder *}

{* $image is file name or object implementing Harmim\Images\IImage *}
{img $image}
{img $image width => 200, height => 200}

{* img-small is image type defined in configuration *}
{img $image img-small}
{img $image img-small compression => 90}

{img $image img-small lazy => TRUE}
{img $image img-small lazy => TRUE, width => 500, height => 650}
```

#### n:img

```latte
n:img="[$image] [image-type] [options]"
```

Renders src tag. It can be used in img element.

Examples:

```latte
<img n:img=""> {* renders path to placeholder *}

{* $image is file name or object implementing Harmim\Images\IImage *}
<img n:img="$image">
<img n:img="$image width => 200, height => 200">

{* img-small is image type defined in configuration *}
<img n:img="$image img-small">
<img n:img="$image img-small compression => 90">
```

#### imgLink

```latte
{imgLink [$image] [image-type] [options]}
```

Returns relative path (from resource root directory) to given image.

Examples:

```latte
{imgLink} {* returns path to placeholder *}

{* $image is file name or object implementing Harmim\Images\IImage *}
{imgLink $image}
{imgLink $image width => 200, height => 200}

{* img-small is image type defined in configuration *}
{imgLink $image img-small}
{imgLink $image img-small compression => 90}
```


----------------------------------------------------------------------------------------------------------------------


### Configuration

- **wwwDir**: (string) absolute path to resource root directory
	- default: `%wwwDir%` in Nette, otherwise you have to specify this parameter
- **imagesDir**: (string) relative path (from `wwwDir`) to directory for storing images
	- default: `data/images`
- **origDir**: (string) relative path (from `imagesDir`) to directory for storing original images
	- default: `orig`
- **compressionDir**: (string) relative path (from `imagesDir`) to directory for storing compressed images
	- default: `imgs`
- **placeholder**: (string) relative path (from `wwwDir`) to image placeholder when image not found
	- default: `img/noimg.jpg`
- **width**: (int) image width
	- default: `1024`
- **height**: (int) image height
	- default: `1024`
- **transform**: (string) one of Harmim\Images\ImageStorage `RESIZE_...` constants or more constants separated by `|`:

	Option             | Description
	-------------------| ------------
	RESIZE_SHRINK_ONLY | only shrinking (prevents the small image from being stretched)
	RESIZE_STRETCH     | do not keep the aspect ratio
	RESIZE_FIT         | the resulting dimensions will be smaller or equal to the required dimensions
	RESIZE_FILL        | fills (and possibly exceeds in one dimension) the target area
	RESIZE_EXACT       | fills the target area and cuts off what goes beyond
	RESIZE_FILL_EXACT  | place not stretched image to exact blank area

	- default: `RESIZE_FIT`
- **imgTagAttributes**: (array) HTML img tags which you can use in `{img}` Latte macro, other tags are ignored
	- default: `[alt, height, width, class, hidden, id, style, title, data]`
- **types**: (array) configuration for image types which overrides default configuration
	- default: `[]`
	- example:
		```yaml
		types:
			img-small:
				width: 50
				height: 50
			img-gallery:
				lazy: true
				transform: Harmim\Images\ImageStorage::RESIZE_STRETCH
		```
- **lazy**: (bool) render `{img}` Latte macro as lazy img (with data-src, lazy class and normal img tag in noscript tag)
	- default: `false`


----------------------------------------------------------------------------------------------------------------------


## License

This tool is licensed under the [MIT license](https://github.com/harmim/images/blob/master/LICENSE.md).
