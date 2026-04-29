# Available Image Operations

Complete reference for the operations exposed via `ImageVariant`. Every fluent method on `ImageVariant` is a thin wrapper that constructs an `Operation` instance under `src/Operation/` and stores it; the canonical source of truth for parameter types is the operation class.

For a custom operation that's not in this list, register it on a custom `OperationRegistry` and pass that registry into `ImageProcessor`. See [Custom operations](#custom-operations) at the bottom.

## Basic usage

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;

$collection = ImageVariantCollection::create();
$collection->addNew('thumbnail')
    ->scale(300, 300)
    ->optimize();
```

## Resize / scale

### `resize(int $width, int $height, bool $preventUpscale = false)`

Stretch the image to **exact** width and height. Aspect ratio is **not** preserved.

```php
$collection->addNew('exact-size')->resize(200, 200);
```

### `scale(int $width, int $height, bool $preventUpscale = false)`

Fit the image within `$width × $height` while preserving aspect ratio.

```php
$collection->addNew('thumbnail')->scale(300, 300);
```

### `heighten(int $height, bool $preventUpscale = false)`

Resize to a specific height; width follows the aspect ratio.

```php
$collection->addNew('portrait')->heighten(400);
```

### `widen(int $width, bool $preventUpscale = false)`

Resize to a specific width; height follows the aspect ratio.

```php
$collection->addNew('landscape')->widen(600);
```

## Crop / cover

### `crop(int $width, int $height, ?int $x = null, ?int $y = null, Position $position = Position::Center)`

Cut a rectangle out. When `$x` and `$y` are both set, the crop is positioned at those exact pixel coordinates; otherwise the crop is positioned by the alignment.

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;

$collection->addNew('square')->crop(200, 200);
$collection->addNew('top-strip')->crop(800, 100, position: Position::TopCenter);
$collection->addNew('exact')->crop(200, 200, x: 50, y: 50);
```

### `cover(int $width, int $height, Position $position = Position::Center, bool $preventUpscale = false)`

Zoom-crop to exact dimensions while preserving aspect ratio. The image is scaled to *cover* the area and the overflow is cropped at the chosen alignment.

```php
$collection->addNew('avatar')->cover(150, 150);
$collection->addNew('hero')->cover(1200, 400, Position::TopCenter);
```

## Rotation / flipping

### `rotate(int $angle)`

Rotate the image clockwise by the given angle in degrees.

```php
$collection->addNew('rotated')->rotate(90);
```

### `flipHorizontal()` / `flipVertical()`

Mirror horizontally or vertically.

```php
$collection->addNew('mirrored')->flipHorizontal();
$collection->addNew('flipped')->flipVertical();
```

### `flip(FlipDirection|string $direction)`

Mirror by direction. Accepts the `FlipDirection` enum (preferred) or the string codes `'h'` / `'v'` / `'horizontal'` / `'vertical'`.

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection;

$collection->addNew('typed')->flip(FlipDirection::Horizontal);
$collection->addNew('config-driven')->flip($config['flip'] ?? 'h');
```

### `orient()`

Auto-rotate based on EXIF orientation. Best placed first in a pipeline so subsequent operations work on the visually correct orientation.

```php
$collection->addNew('phone-upload')
    ->orient()
    ->cover(1024, 768)
    ->optimize();
```

## Color / tone

### `brightness(int $level)` / `contrast(int $level)`

Channel adjustments in the `-100..100` range. `0` leaves the image unchanged.

```php
$collection->addNew('punchy')
    ->brightness(10)
    ->contrast(15);
```

### `grayscale()`

Convert to grayscale.

```php
$collection->addNew('mono')->grayscale();
```

### `colorize(int $red = 0, int $green = 0, int $blue = 0)`

Tint by shifting each channel in the `-100..100` range.

```php
$collection->addNew('warm')->colorize(10, 0, -10);
```

## Effects

### `sharpen(int $amount)`

Sharpen, `0..100`.

```php
$collection->addNew('crisp')->sharpen(15);
```

### `blur(int $level = 5)`

Gaussian-style blur, `0..100`. Defaults to 5 (intervention's default).

```php
$collection->addNew('soft')->blur(20);
```

### `pixelate(int $size)`

Privacy-redaction effect. `$size` is the pixel block size.

```php
$collection->addNew('redacted')->pixelate(12);
```

### `trim(int $tolerance = 0)`

Strip uniform-coloured border areas. `$tolerance` is `0..100`; `0` only trims exact-match colours.

```php
$collection->addNew('content-only')->trim(5);
```

## Canvas

### `resizeCanvas(int $width, int $height, ?string $background = null, Position $position = Position::Center)`

Resize the working canvas without resampling the source image. New pixel area introduced by growing the canvas is filled with `$background`.

```php
$collection->addNew('framed')
    ->resizeCanvas(800, 600, background: '#ffffff', position: Position::Center);
```

### `padding(int $amount, ?string $background = null)`

Add `$amount` pixels of padding on every side, filled with `$background`.

```php
$collection->addNew('padded')->padding(20, '#000000');
```

## Watermark / overlay

### `place(string $image, Position $position = Position::BottomCenter, int $x = 0, int $y = 0, float $opacity = 1.0)`

Insert another image on top of the current one.

```php
$collection->addNew('watermarked')
    ->scale(1024, 1024)
    ->place('logo.png', Position::BottomRight, x: -10, y: -10, opacity: 0.6);
```

## Format / output

### `convert(string $format)`

Re-encode the variant in a different format than the source. The variant's stored path's extension is swapped to match.

```php
$collection->addNew('webp-version')
    ->scale(1200, 800)
    ->convert('webp');
```

### `optimize()`

Run the variant through [`spatie/image-optimizer`](https://github.com/spatie/image-optimizer) after encoding. Reduces file size; requires the optimizer's CLI tools (`jpegoptim`, `optipng`, …) to be installed on the system.

```php
$collection->addNew('web-large')
    ->scale(1920, 1080)
    ->optimize();
```

## Custom callbacks

### `callback(callable $callback)`

Escape hatch for one-off image manipulation that no first-class operation covers. The callback receives the intervention/image instance and modifies it in place.

```php
$collection->addNew('custom')
    ->callback(function ($image) {
        $image->brightness(10);
        $image->contrast(5);
    });
```

> Note: `Callback` operations don't survive a `toArray()` round-trip cleanly (closures are not serialisable). Use them for in-process pipelines, not for persisted variant configurations.

## Chaining

All operations chain:

```php
$collection->addNew('hero-mobile')
    ->orient()
    ->cover(750, 500, Position::TopCenter)
    ->sharpen(8)
    ->convert('webp')
    ->optimize();
```

## Position constants

The `Position` enum covers every alignment intervention/image accepts, in both `top-left` and `left-top` forms:

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;

Position::Center
Position::TopCenter      Position::BottomCenter
Position::TopLeft        Position::TopRight
Position::LeftTop        Position::RightTop
Position::LeftCenter     Position::RightCenter
Position::LeftBottom     Position::RightBottom
Position::BottomLeft     Position::BottomRight
```

For config-driven setups: `Position::fromName($string)` does case/whitespace normalisation and throws with a clear list of valid names on bad input.

## Common patterns

### Profile pictures / avatars

```php
$collection->addNew('avatar')
    ->orient()
    ->cover(150, 150)
    ->optimize();
```

### Responsive images

```php
foreach ([320 => 'mobile', 768 => 'tablet', 1920 => 'desktop'] as $width => $name) {
    $collection->addNew($name)
        ->scale($width, $width)
        ->convert('webp')
        ->optimize();
}
```

### Watermarked deliverable

```php
$collection->addNew('preview')
    ->scale(1200, 1200)
    ->place('watermark.png', Position::BottomCenter, opacity: 0.5)
    ->optimize();
```

## Custom operations

`ImageProcessor` accepts a custom `OperationRegistry` as its fifth constructor argument. This lets apps add operations the package doesn't ship with — for example a domain-specific recolouring step.

```php
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\OperationContext;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\OperationRegistry;

final class TintOperation implements Operation
{
    public function __construct(public readonly string $color) {}

    public function name(): string { return 'tint'; }

    public function apply(OperationContext $context): void {
        $context->image->fill($this->color);
    }

    public function toArray(): array { return ['color' => $this->color]; }
}

$registry = OperationRegistry::default()
    ->register('tint', static fn (array $args): Operation => new TintOperation((string)$args['color']));

$processor = new ImageProcessor(
    $storage,
    $pathBuilder,
    $imageManager,
    urlBuilder: null,
    operationRegistry: $registry,
);

// On the variant side, attach the operation directly via add()
$collection->addNew('tinted')->add(new TintOperation('#ffaa00'));
```
