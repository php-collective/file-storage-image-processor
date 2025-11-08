# Available Image Operations

This document lists all available image manipulation operations that can be used with `ImageVariant`.

## Basic Usage

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;

$collection = ImageVariantCollection::create();
$collection->addNew('thumbnail')
    ->scale(300, 300)
    ->optimize();
```

## Resizing & Scaling Operations

### resize()

Resizes the image to exact dimensions. **Does not preserve aspect ratio** - the image will be stretched to fit.

```php
->resize(int $width, int $height, bool $preventUpscale = false)
```

**Parameters:**
- `$width` - Target width in pixels
- `$height` - Target height in pixels
- `$preventUpscale` - If true, prevents making the image larger than original (default: false)

**Example:**
```php
$collection->addNew('exact-size')
    ->resize(200, 200);  // Will stretch to exactly 200x200
```

### scale()

Scales the image while **preserving aspect ratio**. The resulting size may differ from the given arguments as the aspect ratio is maintained.

```php
->scale(int $width, int $height, bool $preventUpscale = false)
```

**Parameters:**
- `$width` - Maximum width in pixels
- `$height` - Maximum height in pixels
- `$preventUpscale` - If true, prevents making the image larger than original (default: false)

**Example:**
```php
$collection->addNew('thumbnail')
    ->scale(300, 300);  // Will fit within 300x300 while maintaining aspect ratio
```

**Note:** Use `scale()` when you want to maintain the original proportions of the image. Use `resize()` when you need exact dimensions.

### heighten()

Resizes the image to a specific height while maintaining aspect ratio.

```php
->heighten(int $height, bool $preventUpscale = false)
```

**Example:**
```php
$collection->addNew('portrait')
    ->heighten(400);  // Height will be 400px, width will adjust proportionally
```

### widen()

Resizes the image to a specific width while maintaining aspect ratio.

```php
->widen(int $width, bool $preventUpscale = false)
```

**Example:**
```php
$collection->addNew('landscape')
    ->widen(600);  // Width will be 600px, height will adjust proportionally
```

## Cropping Operations

### crop()

Crops a rectangular area from the image.

```php
->crop(int $width, int $height, ?int $x = null, ?int $y = null)
```

**Parameters:**
- `$width` - Width of the crop area
- `$height` - Height of the crop area
- `$x` - Optional X-axis offset (left edge)
- `$y` - Optional Y-axis offset (top edge)

**Examples:**
```php
// Crop 200x200 from center (default position)
$collection->addNew('square')
    ->crop(200, 200);

// Crop 200x200 starting at specific coordinates
$collection->addNew('specific-area')
    ->crop(200, 200, 50, 50);
```

### cover()

Crops the image to cover the specified dimensions while maintaining aspect ratio. The image will be cropped to fill the entire area.

```php
->cover(int $width, int $height, ?callable $callback = null, bool $preventUpscale = false, string $position = 'center')
```

**Parameters:**
- `$width` - Target width
- `$height` - Target height
- `$callback` - Optional callback function
- `$preventUpscale` - Prevent upscaling (default: false)
- `$position` - Position anchor point (default: 'center')

**Example:**
```php
$collection->addNew('avatar')
    ->cover(100, 100);  // Creates a 100x100 image, cropped to fill
```

## Rotation & Flipping

### rotate()

Rotates the image by the specified angle.

```php
->rotate(int $angle)
```

**Parameters:**
- `$angle` - Rotation angle in degrees

**Example:**
```php
$collection->addNew('rotated')
    ->rotate(90);  // Rotate 90 degrees clockwise
```

### flipHorizontal()

Flips the image horizontally (mirror image).

```php
->flipHorizontal()
```

**Example:**
```php
$collection->addNew('mirrored')
    ->flipHorizontal();
```

### flipVertical()

Flips the image vertically (upside down).

```php
->flipVertical()
```

**Example:**
```php
$collection->addNew('flipped')
    ->flipVertical();
```

### flip()

Flips the image in a specific direction.

```php
->flip(string $direction)
```

**Parameters:**
- `$direction` - Either 'h' (horizontal) or 'v' (vertical)

**Example:**
```php
$collection->addNew('custom-flip')
    ->flip('h');  // Same as flipHorizontal()
```

## Enhancement Operations

### sharpen()

Sharpens the image.

```php
->sharpen(int $amount)
```

**Parameters:**
- `$amount` - Sharpening intensity (typically 0-100)

**Example:**
```php
$collection->addNew('sharp')
    ->sharpen(15);
```

### optimize()

Enables image optimization. When this is set, the image will be optimized using image optimization tools (requires spatie/image-optimizer).

```php
->optimize()
```

**Example:**
```php
$collection->addNew('web-optimized')
    ->scale(800, 600)
    ->optimize();  // Will reduce file size
```

## Advanced Operations

### callback()

Allows custom processing via a callback function.

```php
->callback(callable $callback)
```

**Parameters:**
- `$callback` - A callable that receives the image instance and arguments

**Example:**
```php
$collection->addNew('custom')
    ->callback(function($image, $args) {
        // Perform custom operations on $image
        $image->brightness(10);
        $image->contrast(5);
    });
```

## Chaining Operations

All operations can be chained together to create complex transformations:

```php
$collection->addNew('complex-variant')
    ->scale(800, 600)           // Scale to max 800x600
    ->sharpen(10)               // Apply sharpening
    ->flipHorizontal()          // Mirror the image
    ->rotate(90)                // Rotate 90 degrees
    ->optimize();               // Optimize file size
```

## Common Patterns

### Profile Pictures / Avatars
```php
$collection->addNew('avatar')
    ->cover(150, 150)
    ->optimize();
```

### Thumbnails
```php
$collection->addNew('thumbnail')
    ->scale(300, 300)
    ->sharpen(5)
    ->optimize();
```

### Web-Optimized Images
```php
$collection->addNew('web-large')
    ->scale(1920, 1080)
    ->optimize();

$collection->addNew('web-medium')
    ->scale(1024, 768)
    ->optimize();

$collection->addNew('web-small')
    ->scale(640, 480)
    ->optimize();
```

### Exact Dimensions (e.g., for cards/banners)
```php
$collection->addNew('banner')
    ->cover(1200, 400)  // Fills entire 1200x400, crops excess
    ->optimize();
```

## Position Constants

When using operations that support positioning (like `cover`), you can use these position values:

- `'center'` (default)
- `'top-center'`
- `'bottom-center'`
- `'left-top'`
- `'right-top'`
- `'left-center'`
- `'right-center'`
- `'left-bottom'`
- `'right-bottom'`

These constants are available in `PhpCollective\Infrastructure\Storage\Processor\Image\Operations`.
