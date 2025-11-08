# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - TBD

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
