# File Storage Image Processing

[![CI](https://github.com/php-collective/file-storage-image-processor/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/php-collective/file-storage-image-processor/actions/workflows/ci.yml)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Image Processing for the [File Storage Library](https://github.com/php-collective/file-storage).

Built on top of [Intervention Image v3](https://image.intervention.io/v3).

## Features

 * Manipulates images and produces variants of the original image and stores them
 * Supports multiple image operations: resize, scale, crop, rotate, flip, sharpen, and more
 * Optimizes image file size using [spatie/image-optimizer](https://github.com/spatie/image-optimizer)
 * Works with League Flysystem for flexible storage backends
 * Fluent API for chaining image operations

## Requirements

- PHP 8.1 or higher
- GD or Imagick extension
- Intervention Image v3

## Installation

```sh
composer require php-collective/file-storage-image-processor
```

## Quick Example

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;

$collection = ImageVariantCollection::create();

// Create a thumbnail with aspect ratio preserved
$collection->addNew('thumbnail')
    ->scale(300, 300)
    ->optimize();

// Create a specific-sized avatar
$collection->addNew('avatar')
    ->cover(150, 150)
    ->optimize();

$file = $file->withVariants($collection->toArray());
$file = $imageProcessor->process($file);
```

## Documentation

Please start by reading the documentation in the [docs/](/docs/readme.md) directory:

- [Installation & Setup](/docs/Installation-and-Setup.md)
- [Processing Images](/docs/Processing-Images.md)
- [Available Operations](/docs/Available-Operations.md) - Complete reference of all image operations

## Upgrade from v2

This package uses **Intervention Image v3**, which has breaking changes from v2:

- `ImageManager` now requires a Driver instance instead of array configuration
- The `resize()` method no longer has an `aspectRatio` parameter
- Use `scale()` for aspect ratio preservation (recommended for most cases)
- Use `resize()` only when you need exact dimensions (may distort image)

See the [Available Operations](/docs/Available-Operations.md) documentation for detailed examples.
