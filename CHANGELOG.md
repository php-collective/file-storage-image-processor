# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - TBD

### Breaking

- **PHP 8.3+** required (was 8.1)
- **Intervention Image v4** required (was v3)
- `processOnlyTheseVariants()` and `processAll()` removed; the variant filter is now a per-call argument: `$processor->process($file, ['thumbnail'])`
- `Operations` class is gone — operations are now individual classes under `src/Operation/` resolved via `OperationRegistry`. Existing single-operation entries keep the same serialized shape; repeated operations of the same name are now serialized as ordered lists
- `Operations::POSITION_*` string constants replaced by the `Position` enum (`Position::Center`, `Position::TopCenter`, …)
- `ImageVariant::FLIP_HORIZONTAL` / `FLIP_VERTICAL` constants replaced by the `FlipDirection` enum
- `flip()` no longer accepts arbitrary strings — use `FlipDirection` or one of `'h'`/`'v'`/`'horizontal'`/`'vertical'`
- `cover()` no longer accepts a `$callback` parameter (it was always ignored)
- `flipHorizontal()` now produces an actual horizontal mirror (1.x silently produced a vertical flip due to a v3 quirk; output of pipelines using this op will change)
- EXIF/metadata is now stripped from encoded output by default; call `setStripExif(false)` to preserve
- Several internal throws moved into the `ImageProcessingException` hierarchy. Code catching the previous raw types may need to widen to the typed equivalents:
  - `Driver::Auto` with neither Imagick nor GD installed now throws `DriverUnavailableException` (was a raw `RuntimeException`)
  - `process()` failing to decode the source now throws `ImageCorruptedException` wrapping the underlying intervention exception (was the raw intervention exception itself)
  - The encode path with a missing file extension now throws `UnsupportedFormatException` (was a raw `InvalidArgumentException`)

### Added

- `ImageProcessor::create(Driver|string $driver, …)` factory — builds the underlying `ImageManager` for you. Pass `Driver::Auto` / `Driver::Gd` / `Driver::Imagick`, or any equivalent string for config-driven setups
- `setQuality(int|array $quality)` — accepts a single int or a per-extension map, e.g. `['webp' => 80, 'jpg' => 90, 'avif' => 70]`
- `setStripExif(bool $strip)` toggle (default `true`)
- `setPreserveProfile(bool $preserve)` toggle (default `true`) — captures the source ICC profile and re-applies it after the operations chain so wide-gamut sources keep their intended color rendering
- `setPreserveAnimation(bool $preserve)` toggle (default `true`) — animated GIF / WebP sources keep all frames through the pipeline. Disable to flatten to a single static frame (useful for thumbnail variants or when converting to a non-animated format)
- `setMimeTypes(array $mimeTypes)` is now `public` with input validation
- New first-class operations: `orient` (EXIF auto-rotate), `brightness`, `contrast`, `grayscale` (+ `greyscale` alias), `colorize`, `blur`, `pixelate`, `trim`, `resizeCanvas`, `padding`, `place` (watermark), `convert` (output format swap)
- `ImageVariant::add(Operation $op)` primitive — attach `Operation` instances directly without going through a fluent builder method
- `OperationRegistry` is the single source of truth for which operations exist; pass a custom registry to `ImageProcessor` to add app-specific operations without forking
- `Driver`, `Position`, `FlipDirection`, `Format` enums replacing magic strings throughout the public API. Each enum has a `fromName()` method that does case/whitespace normalisation for config-driven callers. `Format::fromName()` also recognises common aliases (`jpg` → `Jpeg`, `tif` → `Tiff`, `pjpeg` → `Pjpg`, `heif` → `Heic`, `j2k` / `jp2k` → `Jp2`)
- `convert()` operation now accepts a `Format` enum case (preferred) in addition to a string
- New typed exceptions extending `ImageProcessingException`:
  - `ImageCorruptedException` — wraps `intervention/image` decode failures during `process()`. Catch this to handle truncated uploads / wrong-extension files / unsupported codecs uniformly
  - `UnsupportedFormatException` — thrown by `encodeImage()` when the variant has no usable file extension; replaces the previous raw `InvalidArgumentException` at that boundary
  - `DriverUnavailableException` — thrown by `Driver::Auto` when neither Imagick nor GD is installed; replaces the previous raw `RuntimeException`
- Wider default MIME type allowlist: `image/avif`, `image/bmp`, `image/heic`, `image/heif`, `image/tiff`, `image/webp` on top of the existing `image/gif`/`jpg`/`jpeg`/`png`
- Encoder allowlist for `quality:` / `strip:` named args extended to cover `pjpg`/`pjpeg`/`heif`/`tif`/`jp2`/`j2k`

### Fixed

- `ImageVariantCollection::fromArray()` silently accepted unknown operation names (the unsupported-operation check built an exception but never threw it)
- `ImageProcessor::process()` leaked the source temp file and the encode stream when any variant operation, encoder, or `writeStream` threw. Cleanup is now in `try/finally` blocks
- `heighten()` and `widen()` builders had no matching executor in the old `Operations` class — they would have thrown `UnsupportedOperationException` at runtime. Both now have working operation classes
- `flipHorizontal()` mirrors horizontally instead of vertically (see Breaking)
- `$this->image` is no longer kept as instance state on `ImageProcessor`, so `process()` is now safely re-entrant and doesn't leak the last variant's image to anything that touches the processor afterwards
- Repeating the same operation type on a variant no longer overwrites the earlier step. Repeated operations are preserved and executed in insertion order
- `ImageVariantCollection::fromArray()` now preserves serialized variant URLs in addition to paths and operations
- `convert()` now appends the requested extension when the source variant path has no extension to replace
- CI now installs `imagick`, so the Imagick-specific ICC, animation, and driver-selection tests run instead of being skipped

### Removed

- `Operations` class and `Operations::POSITION_*` constants
- `ImageVariant::FLIP_HORIZONTAL` and `ImageVariant::FLIP_VERTICAL` constants
- `processOnlyTheseVariants()` / `processAll()` methods
- The dead `$callback` parameter on `cover()`

## [1.0.0] - 2025-11-10

### Added
- Added `scale()` method to `ImageVariant` for resizing while preserving aspect ratio
- Comprehensive documentation for all available image operations in [docs/Available-Operations.md](/docs/Available-Operations.md)
- Enhanced README with quick examples and upgrade guide
- Support for Intervention Image v3

### Changed
- **BREAKING**: Upgraded to Intervention Image v3 (from v2)
- **BREAKING**: `ImageManager` constructor now requires a `DriverInterface` instance instead of array configuration
  ```php
  // Before (v2):
  new ImageManager(['driver' => 'gd'])

  // After (v3):
  new ImageManager(new \Intervention\Image\Drivers\Gd\Driver())
  ```
- **BREAKING**: Removed `aspectRatio` parameter from `resize()` method in `ImageVariant`
  - `resize()` now always stretches to exact dimensions (does not preserve aspect ratio)
  - Use `scale()` instead for aspect ratio preservation (recommended for most cases)
  ```php
  // Before:
  ->resize(300, 300, true, false)  // aspectRatio = true

  // After:
  ->scale(300, 300)  // Maintains aspect ratio
  // OR
  ->resize(300, 300)  // Stretches to exact 300x300
  ```
- **BREAKING**: Fixed `crop()` method signature in `ImageVariant` - parameters now in order: width, height, x, y
  ```php
  // Before:
  ->crop($height, $width, $x, $y)

  // After:
  ->crop($width, $height, $x, $y)
  ```
- **BREAKING**: `crop()` position parameter now uses named parameter syntax for Intervention Image v3 compatibility
- Updated image encoding from v2's `stream()` to v3's `encodeByExtension()->toFilePointer()`

### Removed
- **BREAKING**: Removed dependency on `guzzlehttp/psr7` (no longer needed with Intervention Image v3)
- **BREAKING**: Removed backward compatibility code for deprecated `aspectRatio` parameter in `resize()` operation

### Fixed
- Fixed compatibility with Intervention Image v3 crop operation using named parameters
- Fixed `OperationsTest` to use proper mocking of `ImageInterface` instead of concrete class
- Fixed `optimizeAndStore()` to explicitly encode image with file extension (resolves "No encoder found for empty file extension" error)
- Updated all documentation examples to use correct Intervention Image v3 API

### Documentation
- Added comprehensive [Available Operations](/docs/Available-Operations.md) guide covering all image manipulation methods
- Updated [Processing Images](/docs/Processing-Images.md) with v3 examples
- Added upgrade notes in README for migrating from v2
- Added clear distinction between `resize()` (exact dimensions) and `scale()` (aspect ratio preserved)

## [0.1.0] - Previous Release

Initial release with Intervention Image v2 support.

---

## Upgrade Guide from 0.x to 1.0

### Step 1: Update ImageManager Instantiation

```php
// Old (v0.x with Intervention Image v2):
use Intervention\Image\ImageManager;
$imageManager = new ImageManager(['driver' => 'gd']);

// New (v1.0 with Intervention Image v3):
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
$imageManager = new ImageManager(new Driver());
```

### Step 2: Replace aspectRatio Parameter

If you were using `resize()` with `aspectRatio` parameter:

```php
// Old:
$collection->addNew('thumbnail')
    ->resize(300, 300, true);  // aspectRatio = true

// New:
$collection->addNew('thumbnail')
    ->scale(300, 300);  // Use scale() to preserve aspect ratio
```

If you need exact dimensions without aspect ratio:

```php
// Old:
$collection->addNew('exact')
    ->resize(300, 300, false);  // aspectRatio = false

// New:
$collection->addNew('exact')
    ->resize(300, 300);  // resize() now always uses exact dimensions
```

### Step 3: Update Crop Calls (if using coordinate-based cropping)

```php
// Old (wrong parameter order):
->crop($height, $width, $x, $y)

// New (correct parameter order):
->crop($width, $height, $x, $y)
```

### Step 4: Update Test Mocks

If you have custom tests mocking the Image class, update to mock `ImageInterface` instead:

```php
// Old:
use TestApp\Image;
$mock = $this->getMockBuilder(Image::class)...

// New:
use Intervention\Image\Interfaces\ImageInterface;
$mock = $this->createMock(ImageInterface::class);
```

### What Stays the Same

- All other image operations (flip, rotate, sharpen, etc.) work the same way
- Chaining operations works the same way
- The `optimize()` method works the same way
- File storage integration works the same way

### Recommended Changes

While not required, we recommend reviewing your image variants and ensuring you're using the most appropriate method:

- **For thumbnails**: Use `scale()` to maintain aspect ratio
- **For avatars/profile pics**: Use `cover()` to fill exact dimensions
- **For exact sizes (cards/banners)**: Use `resize()` or `cover()` depending on whether you want stretching or cropping
- **For responsive images**: Use `scale()` with different dimensions

See the [Available Operations](/docs/Available-Operations.md) documentation for detailed examples and use cases.
