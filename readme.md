# File Storage Image Processing

[![CI](https://github.com/php-collective/file-storage-image-processor/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/php-collective/file-storage-image-processor/actions/workflows/ci.yml)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Image Processing for the [File Storage Library](https://github.com/php-collective/file-storage).

Built on top of [Intervention Image v4](https://image.intervention.io/v4).

## Features

* Generates and stores variants of an uploaded image (thumbnails, avatars, hero crops, …)
* 24+ first-class image operations: resize, scale, cover, crop, rotate, flip, sharpen, orient (EXIF auto-rotate), brightness, contrast, grayscale, colorize, blur, pixelate, trim, resizeCanvas, padding, place (watermark), convert (format swap), …
* Per-format encoder quality, EXIF/metadata strip toggle, ICC color-profile preservation
* Pluggable operation registry — add custom operations without forking
* Optional file-size optimization via [spatie/image-optimizer](https://github.com/spatie/image-optimizer)
* Works with League Flysystem for flexible storage backends
* Fluent API for chaining operations

## Requirements

- PHP 8.3 or higher
- GD or Imagick extension
- [Intervention Image v4](https://image.intervention.io/v4)

## Installation

```sh
composer require php-collective/file-storage-image-processor
```

## Quick Example

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\Driver;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;

// Driver::Auto picks Imagick when the extension is loaded and falls
// back to GD; use Driver::Gd or Driver::Imagick to choose explicitly.
$imageProcessor = ImageProcessor::create(Driver::Auto, $fileStorage, $pathBuilder);

$collection = ImageVariantCollection::create();

// Create a thumbnail with aspect ratio preserved
$collection->addNew('thumbnail')
    ->scale(300, 300)
    ->optimize();

// Create an avatar that fills exact dimensions
$collection->addNew('avatar')
    ->cover(150, 150, Position::TopCenter)
    ->optimize();

// Re-encode a JPEG source as WebP
$collection->addNew('webp')
    ->scale(800, 600)
    ->convert('webp');

$file = $file->withVariants($collection->toArray());
$file = $imageProcessor->process($file);
```

### Tuning the encoder

```php
$imageProcessor
    ->setQuality(['webp' => 80, 'jpg' => 90, 'avif' => 70]) // per-format
    ->setStripExif(true)            // privacy + smaller files (default)
    ->setPreserveProfile(true)      // keep wide-gamut color rendering (default)
    ->setPreserveAnimation(true);   // animated GIF/WebP keep all frames (default)
```

`setPreserveAnimation(false)` flattens animated sources to a single frame — useful for static thumbnail variants or when converting to a non-animated format like JPEG.

### Processing a subset of variants

```php
// Per-call filter — does not leak into subsequent process() calls.
$file = $imageProcessor->process($file, ['thumbnail']);
```

### Custom operations

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\OperationRegistry;

$registry = OperationRegistry::default()
    ->register('myFilter', static fn (array $args): Operation => new MyFilter(...$args));

$processor = new ImageProcessor($storage, $pathBuilder, $imageManager, urlBuilder: null, operationRegistry: $registry);
```

## Documentation

Please start by reading the documentation in the [docs/](/docs/readme.md) directory:

- [Installation & Setup](/docs/Installation-and-Setup.md)
- [Processing Images](/docs/Processing-Images.md)
- [Available Operations](/docs/Available-Operations.md) — complete reference of all image operations

## Upgrading from 1.x to 2.x

This major release migrates to Intervention Image v4 and replaces the stringly-typed dispatcher with a typed Operation class hierarchy. See the [CHANGELOG](/CHANGELOG.md) for the full list; the headline changes:

- **PHP 8.3+** required (was 8.1)
- **Intervention Image v4** required (was v3)
- `processOnlyTheseVariants()` / `processAll()` removed — use the new `process($file, ['thumbnail'])` per-call filter instead
- `Operations::POSITION_*` string constants replaced by the `Position` enum (`Position::Center`, `Position::TopCenter`, …)
- `ImageVariant::FLIP_HORIZONTAL`/`FLIP_VERTICAL` constants replaced by the `FlipDirection` enum
- `Operations` class is gone — operations are now individual classes under `src/Operation/` resolved via `OperationRegistry`
- `flip()` now accepts `FlipDirection|string` (string form still works for config-driven setups)
- `cover()` no longer takes a (always-ignored) `$callback` parameter
